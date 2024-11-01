<?php
/**
 * Plugin Name: WP-Ranking PRO
 * Plugin URI: https://plugmize.jp/product/wp-ranking-pro
 * Description: "WP-Ranking PRO" totals a page view, and into which a popular article can be formed by various elements or periods.
 * Version: 1.0.3
 * Author: PLUGMIZE
 * Author URI: https://plugmize.jp/
 * Text Domain: wp-ranking-pro
 * Domain Path: /languages
 */

require_once(dirname(__FILE__) . '/lib/model/ranking.php');
require_once(dirname(__FILE__) . '/lib/model/views.php');
require_once(dirname(__FILE__) . '/lib/fill-in-form.php');
require_once(dirname(__FILE__) . '/lib/widget.php');

$wp_ranking_pro = new WP_Ranking_PRO();

register_activation_hook(__FILE__,   array('WP_Ranking_PRO', '_activation'));
register_deactivation_hook(__FILE__, array('WP_Ranking_PRO', '_deactivation'));
register_uninstall_hook( __FILE__,   array('WP_Ranking_PRO', '_uninstall'));

class WP_Ranking_PRO
{
    const _DB_VERSION   = '0.13';
    const TEXT_DOMAIN   = 'wp-ranking-pro';
    const UA_MOBILE     = "iPhone\niPod\nAndroid\ndream\nCUPCAKE\nWindows Phone\nwebOS\nBB10\nBlackBerry8707\nBlackBerry9000\nBlackBerry9300\nBlackBerry9500\nBlackBerry9530\nBlackBerry9520\nBlackBerry9550\nBlackBerry9700\nBlackBerry 93\nBlackBerry 97\nBlackBerry 99\nBlackBerry 98\nDoCoMo\nSoftBank\nJ-PHONE\nVodafone\nKDDI\nUP.Browser\nWILLCOM\nemobile\nDDIPOCKET\nWindows CE\nBlackBerry\nSymbian\nPalmOS\nHuawei\nIAC\nNokia";
    const UA_EXCLUSION  = "bot\nBot\nYahoo! Slurp\nHaosouSpider\nSteeler\nMediapartners";

    static $this_file_path;
    static $this_version;

    private $does_require_rebuild_cache     = false;
    private $does_require_reset_views       = false;
    private $does_require_cleanlog_views    = false;
    private $temporary                      = array();
    private $ids_for_styles                 = array();
    private $ids_of_html_templates          = array();

    public function __construct() {

        $data = get_file_data( __FILE__, array( 'version' => 'Version' ) );
        self::$this_version = $data['version'];

        add_action('plugins_loaded',     array($this, '_load_plugin_textdomain'));
        add_action('plugins_loaded',     array($this, '_create_or_update_database'));
        add_action('admin_menu',         array($this, '_show_menu'));
        add_action('admin_init',         array($this, '_register_settings'));
        add_action('added_option',       array($this, '_rebuild_cache'));
        add_action('added_option',       array($this, '_reset_views'));
        add_action('updated_option',     array($this, '_does_require_rebuild_cache'));
        add_action('updated_option',     array($this, '_does_require_cleanlog_views'));
        add_action('updated_option',     array($this, '_does_require_ranking_cache'));
        add_action('shutdown',           array($this, '_do_actions_delayed'));
        add_action('wp_dashboard_setup', array($this, '_add_dashboard'));

        if (get_option('wpr_use_ajax_logging', 0)) {
            add_action('wp_enqueue_scripts',        array($this, '_enqueue_scripts'));
            add_action('wp_ajax_wpr_count',         array($this, '_count_views_ajax'));
            add_action('wp_ajax_nopriv_wpr_count',  array($this, '_count_views_ajax'));
        }
        else {
            add_action('wp',                        array($this, '_count_views'));
        }

        add_action('wp_ajax_wpr_add_to_dashboard' , array($this, '_add_to_dashboard'));
        add_action('wp_ajax_wpr_fetch_ranking'    , array($this, '_fetch_ranking'));
        add_action('wp_ajax_wpr_remove_ranking'   , array($this, '_remove_ranking'));

        $views = new WP_Ranking_PRO_Model_Views();
        add_action('wpr_scheduled_cron',     array($this,  '_wpr_scheduled_cron'));
        add_action('wpr_scheduled_cleanlog', array($views, 'cleanlog_views'));

        add_shortcode('wpr', array($this, 'do_as_shortcode'));

        # shortcodeからCSSを追加するためのハック
        add_filter('the_posts', array($this, 'add_stylesheets_when_doing_shortcode'));

        # scheduleの実行間隔を追加
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));

        add_action('widgets_init', create_function('', 'return register_widget("WP_Ranking_PRO_Widget");'));
    }

    public function _load_plugin_textdomain() {
        load_plugin_textdomain(
            self::TEXT_DOMAIN,
            false,
            basename(dirname(WP_Ranking_PRO::file_path())) . '/languages'
        );
    }

    public function _create_or_update_database() {
        $installed_db_version = get_option('wpr_db_version', '0.00');

        if ($installed_db_version === self::_DB_VERSION) {
            return;
        }

        $this->_setup_schema();

        # $installed_db_version < 0.01
        if (strcmp($installed_db_version, '0.01') < 0) {
            $this->_insert_default_rankings();

            update_option('wpr_as_mobile_http_user_agent', self::UA_MOBILE);
            update_option('wpr_exclusion_http_user_agent', self::UA_EXCLUSION);
        }

        # $installed_db_version < 0.12
        if (strcmp($installed_db_version, '0.12') < 0) {
            $this->_update_default_ranking_title();
        }

        # $installed_db_version < 0.13
        if (strcmp($installed_db_version, '0.13') < 0) {
            $this->_check_default_rankings();
        }

        update_option('wpr_db_version', self::_DB_VERSION);
    }

    private function _setup_schema() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php'); # for dbDelta()

        global $wpdb;

        dbDelta(array("
            CREATE TABLE {$wpdb->prefix}wpr_views (
                accessed_at                 datetime                NOT NULL,
                post_id                     bigint(20) unsigned     NOT NULL,
                remote_addr                 text                    NOT NULL,
                http_user_agent             int(10) unsigned        NOT NULL,
                http_referer                text                    NOT NULL,
                is_logged_in                bool                    NOT NULL,
                KEY accessed_at (accessed_at),
                KEY post_id (post_id)
            )
        ", "
            CREATE TABLE {$wpdb->prefix}wpr_views_per_day (
                accessed_on                 date                    NOT NULL,
                post_id                     bigint(20) unsigned     NOT NULL,
                views_pc                    int(10) unsigned        NOT NULL,
                views_pc_only_visitors      int(10) unsigned        NOT NULL,
                views_mobile                int(10) unsigned        NOT NULL,
                views_mobile_only_visitors  int(10) unsigned        NOT NULL,
                PRIMARY KEY  (accessed_on,post_id)
            )
        ", "
            CREATE TABLE {$wpdb->prefix}wpr_ranking (
                id                          bigint(20) unsigned     NOT NULL    AUTO_INCREMENT,
                name                        varchar(255)            NOT NULL,
                settings                    text                    NOT NULL,
                PRIMARY KEY  (id)
            )
        ", "
            CREATE TABLE {$wpdb->prefix}wpr_http_user_agents (
                id                          int(10) unsigned        NOT NULL    AUTO_INCREMENT,
                string                      text                    NOT NULL,
                sha1_hash                   char(40)                NOT NULL,
                is_mobile                   bool                    NOT NULL,
                UNIQUE KEY sha1_hash (sha1_hash),
                PRIMARY KEY  (id)
            )
        "));
    }

    private function _insert_default_rankings() {
        $default_rankings = array(
            1 => array(
                'name'     => '24h',
                'settings' => array(
                    'title'  => '24H',
                    'period' => '24h',
                ),
            ),
            2 => array(
                'name'     => '1week',
                'settings' => array(
                    'title'  => '1week',
                    'period' => '1week',
                ),
            ),
            3 => array(
                'name'     => '1month',
                'settings' => array(
                    'title'  => '1month',
                    'period' => '1month',
                ),
            ),
            4 => array(
                'name'     => '1year',
                'settings' => array(
                    'title'  => '1year',
                    'period' => '1year',
                ),
            ),
            5 => array(
                'name'     => 'all',
                'settings' => array(
                    'title'  => 'ALL',
                    'period' => 'all',
                ),
            ),
            6 => array(
                'name'     => 'period',
                'settings' => array(
                    'title'             => 'Period designation',
                    'use_preset_period' => '0',
                    'begin_year'        => date_i18n('Y'),
                    'begin_month'       => date_i18n('n'),
                    'begin_day'         => '1',
                    'end_year'          => date_i18n('Y'),
                    'end_month'         => date_i18n('n'),
                    'end_day'           => date_i18n('j'),
                ),
            ),
        );

        global $wpdb;

        foreach ($default_rankings as $id => $ranking) {
            $wpdb->insert("{$wpdb->prefix}wpr_ranking",
                array(
                    'id'       => $id,
                    'name'     => $ranking['name'],
                    'settings' => json_encode($ranking['settings']),
                ),
                array(
                    '%d',
                    '%s',
                    '%s',
                )
            );
        }
    }

    private function _check_default_rankings() {
        global $wpdb;

        $ranking_count = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}wpr_ranking WHERE id BETWEEN 1 AND 6");
        if (!is_null($ranking_count) && $ranking_count == 0) {
            $this->_insert_default_rankings();
        }
    }

    private function _update_default_ranking_title() {
        global $wpdb;

        # 国際化で、タイトルのデフォルトを変更するため
        $settings = array(
            'title'             => 'Period designation',
            'use_preset_period' => '0',
            'begin_year'        => date_i18n('Y'),
            'begin_month'       => date_i18n('n'),
            'begin_day'         => '1',
            'end_year'          => date_i18n('Y'),
            'end_month'         => date_i18n('n'),
            'end_day'           => date_i18n('j'),
        );

        $wpdb->update("{$wpdb->prefix}wpr_ranking", array(
            'settings' => json_encode($settings),
        ),
        array(
            'id' => 6,
        ));
    }

    public function _show_menu() {
        $GET    = stripslashes_deep($_GET);
        $SERVER = stripslashes_deep($_SERVER);

        $path = isset($GET['path'])
            ? $GET['path']
            : '' ;

        if ($SERVER['REQUEST_METHOD'] == 'POST' && $path == 'custom-ranking') {
            # XXX こちらで対応しないと、template_menu()などが使えないため……。
            $mode = 'create';
            ob_start();
            include(dirname(WP_Ranking_PRO::file_path()) . '/templates/custom-ranking.php');
            $form_html = ob_get_clean();

            $ranking_obj = new WP_Ranking_PRO_Model_Ranking(null);
            $ranking_obj->save_custom_ranking($form_html);
        }
        elseif ($path == 'main-custom' && isset($GET['action']) && $GET['action'] == 'delete') {
            $ranking_obj = new WP_Ranking_PRO_Model_Ranking(null);
            $ranking_obj->delete_custom_ranking();
        }
        elseif ($path == 'custom-ranking' && isset($GET['ranking_id'])) {
            $ranking = new WP_Ranking_PRO_Model_Ranking($GET['ranking_id']);

            if ($ranking->id && $ranking->settings->use_html_template) {
                wp_enqueue_style(
                    'WPR_Stylesheet' . $ranking->settings->html_template,
                    plugins_url('css/ranking-'. $ranking->settings->html_template . '.css', $this->file_path()),
                    array(), # keep default
                    self::$this_version
                );
            }
        }
        elseif (strpos($path, 'main-') === 0 && isset($GET['action']) && $GET['action'] == 'reset') {
            $this->reset_post_views();

            $base_url = admin_url('tools.php?page=wp-ranking-pro');
            $url = $base_url . "&path=$path";

            wp_redirect($url);
            exit;
        }

        $page = add_management_page(
            __('WP-Ranking PRO Management screen', self::TEXT_DOMAIN),
            __('WP-Ranking PRO', self::TEXT_DOMAIN),
            'manage_options',
            'wp-ranking-pro',
            array($this, '_output_menu_html')
        );

        add_action('admin_print_scripts-' . $page, array($this, '_output_menu_scripts'));
        add_action('admin_print_styles-'  . $page, array($this, '_output_menu_stylesheets'));
    }

    public function _output_menu_scripts() {
        wp_enqueue_script(
            'WPR_AdminScript',
            plugins_url('wp-ranking-pro.js', $this->file_path()),
            array('jquery'),
            self::$this_version
        );

        wp_localize_script(
            'WPR_AdminScript',
            'WPR_Admin',
            array(
                'action' => 'wpr_add_to_dashboard',
                # 以下、メッセージ
                'msg_is_execute'    => __('Do you carry it out?', self::TEXT_DOMAIN),
                'msg_already_added' => __('Have been already added', self::TEXT_DOMAIN),
                'msg_added'         => __('Added it', self::TEXT_DOMAIN),
            )
        );
    }

    public function _output_menu_stylesheets() {
        wp_enqueue_style(
            'WPR_AdminStylesheet',
            plugins_url('wp-ranking-pro.css', $this->file_path()),
            array(), # keep default
            self::$this_version
        );
    }

    public function _output_menu_html() {
        $GET = stripslashes_deep($_GET);

        # XXX そもそもここでルーティングしてるのが間違え。
        $path = empty($GET['path'])
            ? 'main-24h'
            : $GET['path'] ;

        $routes = array(
            'main-24h',
            'main-1week',
            'main-1month',
            'main-1year',
            'main-all',
            'main-period',
            'main-custom',

            'settings-base',
            'settings-counting',

            'data-cache',
            'data-reset',
            'data-cleanlog',

            'custom-ranking',

            'help-parameters',
            'help-samples',
            'help-faq',
        );

        if (in_array($path, $routes)) {
            $route       = explode('-', $path, 2);
            $method_name = implode('_', $route);

            call_user_func(array($this, $method_name), $route);
        }
        else {
            wp_die('404 Not Found');
        }
    }

    public function _register_settings() {
        # 基本設定

        add_settings_section(
            'wpr-settings-base-1',
            __('Basic setting', self::TEXT_DOMAIN),
            array($this, '_nop'),
            'wpr-settings-base'
        );

        $this->util_add_settings_field(__('Targeted for count',                       self::TEXT_DOMAIN), 'settings_base', 'count_only_visitors');
        $this->util_add_settings_field(__('The log acquisition by the Ajax',               self::TEXT_DOMAIN), 'settings_base', 'use_ajax_logging');
        $this->util_add_settings_field(__('Statistics are displayed by a dashboard',     self::TEXT_DOMAIN), 'settings_base', 'require_authority');
        $this->util_add_settings_field(__('Substitute image',                       self::TEXT_DOMAIN), 'settings_base', 'alternative_image');
        $this->util_add_settings_field(__('Image type',                     self::TEXT_DOMAIN), 'settings_base', 'image_type');
        $this->util_add_settings_field(__('Ranking cache',           self::TEXT_DOMAIN), 'settings_base', 'ranking_cache_expire');
        $this->util_add_settings_field(__('The acquisition key to access former IP address', self::TEXT_DOMAIN), 'settings_base', 'remote_addr_key');

        # 画像タイプでカスタムフィールドを選んだ場合のカスタムフィールド値
        # 表示は画像タイプのフィールド内でするため、register_setting()のみ
        register_setting(
            'wpr-settings-base',
            'wpr_image_type_custom_field',
            array($this, '_sanitize_wpr_image_type_custom_field')
        );

        # 集計設定

        add_settings_section(
            'wpr-settings-counting-1',
            __('Count setting', self::TEXT_DOMAIN),
            array($this, '_nop'),
            'wpr-settings-counting'
        );

        $this->util_add_settings_field(__('Origin of access to exclude',    self::TEXT_DOMAIN), 'settings_counting' , 'exclusion_remote_addrs');
        $this->util_add_settings_field(__('HTTP referers to exclude',       self::TEXT_DOMAIN), 'settings_counting' , 'exclusion_http_referers');
        $this->util_add_settings_field(__('User agent to treat as mobile',  self::TEXT_DOMAIN), 'settings_counting' , 'as_mobile_http_user_agent');
        $this->util_add_settings_field(__('User agent to exclude',          self::TEXT_DOMAIN), 'settings_counting' , 'exclusion_http_user_agent');

        # キャッシュの再構築

        add_settings_section(
            'wpr-data-cache-1',
            __('Rebuilding of the cache', self::TEXT_DOMAIN),
            array($this, '_nop'),
            'wpr-data-cache'
        );

        register_setting(
            'wpr-data-cache',
            'wpr_rebuild_cache'
        );

        # ページビューのクリア

        add_settings_section(
            'wpr-data-reset-1',
            __('Clear of the page view', self::TEXT_DOMAIN),
            array($this, '_nop'),
            'wpr-data-reset'
        );

        register_setting(
            'wpr-data-reset',
            'wpr_reset_views'
        );

        # ログクリア

        add_settings_section(
            'wpr-data-cleanlog-1',
            __('Clean-up log', self::TEXT_DOMAIN),
            array($this, '_nop'),
            'wpr-data-cleanlog'
        );

        $this->util_add_settings_field(__('Automatic clean up the log',           self::TEXT_DOMAIN), 'data_cleanlog' , 'auto_cleanlog');
        $this->util_add_settings_field(__('Clean-up period',           self::TEXT_DOMAIN), 'data_cleanlog' , 'cleanlog_cycle');
        $this->util_add_settings_field(__('Time to clean it up automatically', self::TEXT_DOMAIN), 'data_cleanlog' , 'cleanlog_time');
        $this->util_add_settings_field(__('Period to delay the clean-up',     self::TEXT_DOMAIN), 'data_cleanlog' , 'cleanlog_keep_times');

    }

    public function _enqueue_scripts() {
        $SERVER = stripslashes_deep($_SERVER);

        if (!is_singular()) {
            return;
        }

        global $post;

        if (!isset($post)) {
            wp_die(__('Unexpected error: $post is not ready', self::TEXT_DOMAIN));
        }

        wp_enqueue_script(
            'WPR_VisitorScript',
            plugins_url('wp-ranking-pro-visitor.js', $this->file_path()),
            array('jquery'),
            self::$this_version
        );

        wp_localize_script(
            'WPR_VisitorScript',
            'WPR_Visitor',
            array(
                'url'          => admin_url('admin-ajax.php'),
                'action'       => 'wpr_count',
                'post_id'      => $post->ID,
                'http_referer' => isset($SERVER['HTTP_REFERER'])    ? $SERVER['HTTP_REFERER']    : ''
            )
        );
    }

    public function _count_views_ajax() {
        $GET    = stripslashes_deep($_GET);
        $SERVER = stripslashes_deep($_SERVER);

        $php_bug_33643 = isset($GET['post_id']) ? intval($GET['post_id']) : null;
        $post = get_post($php_bug_33643);

        if (!isset($post)) {
            return; # Ajax経由の場合post_idは信頼できないため、エラーにはしないでスルーする。
        }

        $remote_addr_key = get_option('wpr_remote_addr_key', 'REMOTE_ADDR');

        $post_id         = $post->ID;
        $remote_addr     = isset($SERVER[$remote_addr_key])  ? $SERVER[$remote_addr_key]  : '' ;
        $http_user_agent = isset($SERVER['HTTP_USER_AGENT']) ? $SERVER['HTTP_USER_AGENT'] : '' ;
        $http_referer    = isset($GET['http_referer'])       ? $GET['http_referer']       : '' ;

        $views = new WP_Ranking_PRO_Model_Views();
        $views->count($post_id, $remote_addr, $http_user_agent, $http_referer);
    }

    public function _count_views() {
        $SERVER = stripslashes_deep($_SERVER);

        # count only single pages
        # 取得時はすべて取得する。絞り込むのは、表示時。
        if (!is_singular()) {
            return;
        }

        # ignore Firefox prefetch
        # see also: https://developer.mozilla.org/ja/docs/Link_prefetching_FAQ#As_a_server_admin.2C_can_I_distinguish_prefetch_requests_from_normal_requests.3F
        if (isset($SERVER['HTTP_X_MOZ']) && $SERVER['HTTP_X_MOZ'] == 'prefetch') {
            return;
        }

        global $post;

        if (!isset($post)) {
            wp_die(__('Unexpected error: $post is not ready', self::TEXT_DOMAIN));
        }

        $remote_addr_key = get_option('wpr_remote_addr_key', 'REMOTE_ADDR');

        $post_id         = $post->ID;
        $remote_addr     = isset($SERVER[$remote_addr_key])  ? $SERVER[$remote_addr_key]  : '' ;
        $http_user_agent = isset($SERVER['HTTP_USER_AGENT']) ? $SERVER['HTTP_USER_AGENT'] : '' ;
        $http_referer    = isset($SERVER['HTTP_REFERER'])    ? $SERVER['HTTP_REFERER']    : '' ;

        $views = new WP_Ranking_PRO_Model_Views();
        $views->count($post_id, $remote_addr, $http_user_agent, $http_referer);
    }

    public function _wpr_scheduled_cron() {
        global $wpdb;

        $threshold = time() - MINUTE_IN_SECONDS;

        # delete transient data
        $wpdb->query(<<<SQL
            DELETE FROM t1, t2
            USING `{$wpdb->options}` t1 JOIN `{$wpdb->options}` t2 ON t2.option_name = REPLACE(t1.option_name, '_timeout', '')
            WHERE
                t1.option_name LIKE '\\_transient\\_timeout\\_wpr-%'
                    AND
                t1.option_value < '$threshold'
SQL
        );

        $wpdb->query(<<<SQL
            DELETE FROM
                `{$wpdb->options}`
            WHERE
                option_name LIKE '\\_transient\\_timeout\\_wpr-%'
                    AND
                option_value < '$threshold'
SQL
        );
    }

    public function _rebuild_cache($option_name) {
        if ($option_name == 'wpr_rebuild_cache') {
            add_settings_error(
                'wpr-rebuild-cache',
                'wpr-rebuild-cache',
                __('Rebuilt cache', self::TEXT_DOMAIN),
                'updated'
            );

            $this->does_require_rebuild_cache = true;

            delete_option('wpr_rebuild_cache');
        }
    }

    public function _reset_views($option_name) {
        if ($option_name == 'wpr_reset_views') {
            add_settings_error(
                'wpr-reset-views',
                'wpr-reset-views',
                __('Reset page view', self::TEXT_DOMAIN),
                'updated'
            );

            $this->does_require_reset_views = true;

            delete_option('wpr_reset_views');
        }
    }

    public function _does_require_rebuild_cache($option_name) {
        if (in_array($option_name, array(
            'wpr_exclusion_remote_addrs',
            'wpr_exclusion_http_referers',
            'wpr_as_mobile_http_user_agent',
            'wpr_exclusion_http_user_agent',
        ))) {
            $this->does_require_rebuild_cache = true;
        }
    }

    public function _does_require_cleanlog_views($option_name) {
        if (in_array($option_name, array(
            'wpr_auto_cleanlog',
            'wpr_cleanlog_cycle',
            'wpr_cleanlog_time',
            'wpr_cleanlog_keep_times',
        ))) {
            if (get_option('wpr_auto_cleanlog', 0)) {
                $this->does_require_cleanlog_views = true;
            }
    		wp_clear_scheduled_hook('wpr_scheduled_cleanlog');
        }
    }

    public function _does_require_ranking_cache($option_name) {
        if (in_array($option_name, array(
            'wpr_ranking_cache_expire',
        ))) {
            if (get_option('wpr_ranking_cache_expire')) {
				# Effectively
				# set schedule
                if(!wp_next_scheduled('wpr_scheduled_cron')) {
                    wp_schedule_event(time(), '6hours', 'wpr_scheduled_cron');
                }

			} else {
				# Invalidity
				# clear schedule
                wp_clear_scheduled_hook('wpr_scheduled_cron');

                # delete transient data
                global $wpdb;

                $wpdb->query(<<<SQL
                    DELETE FROM
                        `{$wpdb->options}`
                    WHERE
                        `option_name` LIKE '\\_transient\\_wpr-%'
                            OR
                        `option_name` LIKE '\\_transient\\_timeout\\_wpr-%'
SQL
                );
            }
        }
    }

    public function _do_actions_delayed() {
        if ($this->does_require_rebuild_cache) {
            $views = new WP_Ranking_PRO_Model_Views();
            $views->rebuild_cache();
        }

        if ($this->does_require_reset_views) {
            $views = new WP_Ranking_PRO_Model_Views();
            $views->reset_views();
        }

        if ($this->does_require_cleanlog_views) {
            $views = new WP_Ranking_PRO_Model_Views();
            $views->cleanlog_views();
        }
    }

    public function _add_dashboard() {
        if (!$this->_util_can_view_ranking()) {
            return;
        }

        wp_add_dashboard_widget(
            'wpr_dashboard_widget',
            __('WP-Ranking PRO', self::TEXT_DOMAIN),
            array($this, '_dashboard_widget')
        );

        $this->_fetch_ranking_scripts();
        $this->_output_menu_scripts();
        $this->_output_menu_stylesheets();
    }

    public function _dashboard_widget() {
        $GET = stripslashes_deep($_GET);

        $this->_util_fix_dashboard_rankings();

        $rankings = array();

        foreach (get_option('wpr_dashboard_rankings', range(1, 6)) as $ranking_id) {
            $rankings[$ranking_id] = new WP_Ranking_PRO_Model_Ranking($ranking_id);
        }

        $tmp = array_values($rankings);
        $current_ranking = $tmp[0];

        $limit = ctype_digit(isset($GET['limit']) ? $GET['limit'] : null)
            ? $GET['limit']
            : 10 ;

        if ($limit < 0) {
            $limit = 0;
        }

        $page = ctype_digit(isset($GET['page']) ? $GET['page'] : null)
            ? $GET['page']
            : 1 ;

        if ($page < 1) {
            $page = 1;
        }

        $posts = $current_ranking->get_posts(array(
            'limit'          => $limit,
            'page'           => $page,
            'fetch_next_one' => true,
        ));

        if (count($posts) == $limit + 1) {
            array_pop($posts); # drop '+ 1'

            $has_next = true;
        }
        else {
            $has_next = false;
        }
?>
<ul class="wpr-menu">
<?php
        foreach ($rankings as $ranking) {
            $current = $ranking->name == $current_ranking->name
                ? ' class="wpr-current"'
                : '' ;
?>
  <li><a href=""<?php echo $current ?> data-wpr-id="<?php echo $ranking->id ?>"><?php echo $ranking->name ?></a></li>
<?php
        }
?>
</ul>
<table class="wpr-ranking">
<?php
        include(dirname($this->file_path()) . '/templates/dashboard-table.php');
?>
</table>
<form>
  <div>
    <span><?php _e('Number of Display', self::TEXT_DOMAIN) ?></span>
    <select name="limit" id="wpr-dashboard-limit">
<?php
        foreach (array(10, 20, 30, 40, 50) as $i) {
?>
      <option value="<?php echo $i ?>"><?php echo $i ?></option>
<?php
        }
?>
    </select>
  </div>
</form>
<div class="wpr-pagenav">
  <a id="wpr-dashboard-prev-page" class="wpr-dashboard-update-ranking button hidden"><?php _e('Forward', self::TEXT_DOMAIN) ?></a>
  <a id="wpr-dashboard-next-page" class="wpr-dashboard-update-ranking button<?php echo $has_next ? '' : ' hidden' ?>"><?php _e('Next', self::TEXT_DOMAIN) ?></a>
</div>
<div class="wpr-remove-button">
  <a id="wpr-dashboard-remove-ranking" class="button"><?php _e('Deleted by a dashboard', self::TEXT_DOMAIN) ?></a>
</div>
<?php
    }

    public function _fetch_ranking_scripts() {
        wp_enqueue_script(
            'WPR_DashboardScript',
            plugins_url('wp-ranking-pro-dashboard.js', $this->file_path()),
            array('jquery'),
            self::$this_version
        );

        $wpr_dashboard_rankings = get_option('wpr_dashboard_rankings', range(1, 6));
        $current_ranking_id      = $wpr_dashboard_rankings[0];

        wp_localize_script(
            'WPR_DashboardScript',
            'WPR_Dashboard',
            array(
                'action_fetch'  => 'wpr_fetch_ranking',
                'action_remove' => 'wpr_remove_ranking',
                'ranking_id'    => $current_ranking_id,
            )
        );
    }

    public function _add_to_dashboard() {
        $GET = stripslashes_deep($_GET);

        $ranking = new WP_Ranking_PRO_Model_Ranking(intval($GET['id']));

        if (!$ranking->id) {
            exit;
        }

        $dashboard_rankings = get_option('wpr_dashboard_rankings', range(1, 6));

        if (in_array($ranking->id, $dashboard_rankings)) {
            exit;
        }

        $dashboard_rankings[] = $ranking->id;

        update_option('wpr_dashboard_rankings', $dashboard_rankings);

        exit;
    }

    public function _fetch_ranking() {
        $GET = stripslashes_deep($_GET);

        # _dashboard_widget()と同じ {
        $rankings = array();

        foreach (get_option('wpr_dashboard_rankings', range(1, 6)) as $ranking_id) {
            $rankings[$ranking_id] = new WP_Ranking_PRO_Model_Ranking($ranking_id);
        }

        # } ここだけ違う {
        $id = ctype_digit($GET['id'])
            ? intval($GET['id'])
            : 1 ;

        $current_ranking = $rankings[$id];
        # }

        $limit = ctype_digit($GET['limit'])
            ? $GET['limit']
            : 10 ;

        if ($limit < 0) {
            $limit = 0;
        }

        $page = ctype_digit($GET['page'])
            ? $GET['page']
            : 1 ;

        if ($page < 1) {
            $page = 1;
        }

        $posts = $current_ranking->get_posts(array(
            'limit'          => $limit,
            'page'           => $page,
            'fetch_next_one' => true,
        ));

        if (count($posts) == $limit + 1) {
            array_pop($posts); # drop '+ 1'

            $has_next = true;
        }
        else {
            $has_next = false;
        }
        # }

        ob_start();

        include(dirname($this->file_path()) . '/templates/dashboard-table.php');

        $html = ob_get_clean();

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(array('html' => $html, 'has_next' => $has_next));

        # wp_die()は3.4未満？　では以下のページの期待通りに動作しない。
        # http://codex.wordpress.org/AJAX_in_Plugins
        die('');
    }

    public function _remove_ranking() {
        $GET = stripslashes_deep($_GET);

        $id = ctype_digit($GET['id'])
            ? intval($GET['id'])
            : 1 ;

        $wpr_dashboard_rankings = get_option('wpr_dashboard_rankings', range(1, 6));

        if (count($wpr_dashboard_rankings) > 1) {
            $wpr_dashboard_rankings = array_values(preg_grep('/\A' . $id . '\z/', $wpr_dashboard_rankings, PREG_GREP_INVERT));

            update_option('wpr_dashboard_rankings', $wpr_dashboard_rankings);

            $is_success = true;
        }
        else {
            $is_success = false;
        }

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(array(
            'is_success'      => $is_success,
            'next_ranking_id' => $wpr_dashboard_rankings[0],
            'message'         => $is_success ? '' : __('Cannot delete last one', self::TEXT_DOMAIN),
        ));

        die('');
    }

    private function main_24h($route) {
        $this->main_others($route, 1);
    }

    private function main_1week($route) {
        $this->main_others($route, 2);
    }

    private function main_1month($route) {
        $this->main_others($route, 3);
    }

    private function main_1year($route) {
        $this->main_others($route, 4);
    }

    private function main_all($route) {
        $this->main_others($route, 5);
    }

    private function main_period($route) {
        $this->main_others($route, 6);
    }

    private function main_custom($route) {
        $GET = stripslashes_deep($_GET);

        $ranking_id = !empty($GET['ranking_id'])
            ? $GET['ranking_id']
            : get_option('wpr_current_ranking', 7);

        $current_ranking = new WP_Ranking_PRO_Model_Ranking($ranking_id);

        if (!$current_ranking->id) {
            global $wpdb;

            # get first no default (= custom) ranking
            $current_ranking = $wpdb->get_row("
                SELECT
                    *
                FROM
                    `{$wpdb->prefix}wpr_ranking`
                WHERE
                    `id` NOT IN (1, 2, 3, 4, 5, 6) -- exclude defaults
                ORDER BY
                    `id` ASC
                LIMIT
                    1
            ");
        }

        if (!$current_ranking) {
            include(dirname($this->file_path()) . '/templates/main-make-custom-ranking-first.php');

            return;
        }

        update_option('wpr_current_ranking', $current_ranking->id);

        $ranking = new WP_Ranking_PRO_Model_Ranking($current_ranking->id);

        $this->main__template($route, $ranking);
    }

    private function main_others($route, $ranking_id) {
        $GET = stripslashes_deep($_GET);

        $ranking = new WP_Ranking_PRO_Model_Ranking($ranking_id);

        $_settings = $ranking->settings;

        if (isset($GET['post_type_name'])) {
            $_settings->post_type = $GET['post_type_name'];

            if ($route[1] == 'period') {
                foreach (array('begin', 'end') as $prefix) {
                    $year_name  = $prefix . '_month';
                    $month_name = $prefix . '_day'  ;
                    $day_name   = $prefix . '_year' ;

                    $_settings->$year_name  = isset($GET[$year_name ]) ? $GET[$year_name ] : date_i18n('Y');
                    $_settings->$month_name = isset($GET[$month_name]) ? $GET[$month_name] : date_i18n('n');
                    $_settings->$day_name   = isset($GET[$day_name  ]) ? $GET[$day_name  ] : date_i18n('j');

                    if (!checkdate(
                        $_settings->$year_name,
                        $_settings->$month_name,
                        $_settings->$day_name
                    )) {
                        $_settings->$year_name  = date_i18n('Y');
                        $_settings->$month_name = date_i18n('n');
                        $_settings->$day_name   = date_i18n('j');
                    }
                }
            }

            global $wpdb;

            $wpdb->update("{$wpdb->prefix}wpr_ranking", array(
                'settings' => json_encode($_settings),
            ),
            array(
                'id' => $ranking->id,
            ));

            # re-fetch
            $ranking = new WP_Ranking_PRO_Model_Ranking($ranking->id);
        }

        $this->main__template($route, $ranking);
    }

    private function main__template($route, $ranking) {
        $GET = stripslashes_deep($_GET);

        # テンプレートで使用する変数の用意など

        $base_url = admin_url('tools.php?page=wp-ranking-pro');

        $wpr_dashboard_rankings = get_option('wpr_dashboard_rankings', range(1, 6));
        $already_added_dashboard = in_array($ranking->id, $wpr_dashboard_rankings);

        $order = isset($GET['order'])
            ? $GET['order']
            : 'views.desc' ;

        $limit = isset($GET['limit'])
            ? $GET['limit']
            : 10 ;

        $posts = $ranking->get_posts(array('order' => $order, 'limit' => $limit));

        # カスタムランキングでは、カスタムランキングの一覧が必要。
        if ($route[1] == 'custom') {
            global $wpdb;

            $custom_rankings = $wpdb->get_results("
                SELECT
                    *
                FROM
                    `{$wpdb->prefix}wpr_ranking`
                WHERE
                    `id` NOT IN (1, 2, 3, 4, 5, 6) -- exclude defaults
                ORDER BY
                    `id` ASC
            ");
        }

        include(dirname($this->file_path()) . '/templates/main.php');
    }

    private function settings_base() {
        $base_url = admin_url('tools.php?page=wp-ranking-pro');
?>
<div class="wrap">
<?php $this->template_menu('settings') ?>
  <div class="wpr-tabs-and-contents">
    <ul class="wpr-tabs">
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'settings-base')) ?>" class="wpr-current"><?php _e('Basic setting', self::TEXT_DOMAIN) ?></a></li>
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'settings-counting')) ?>"><?php _e('Count setting', self::TEXT_DOMAIN) ?></a></li>
    </ul>
    <div class="wpr-contents">
      <?php settings_errors() ?>
      <form action="options.php" method="post" enctype="multipart/form-data">
<?php
        settings_fields('wpr-settings-base');
        do_settings_sections('wpr-settings-base');
        submit_button();
?>
      </form>
    </div>
  </div>
</div>
<?php
    }

    private function settings_counting() {
        $base_url = admin_url('tools.php?page=wp-ranking-pro');
?>
<div class="wrap">
<?php $this->template_menu('settings') ?>
  <div class="wpr-tabs-and-contents">
    <ul class="wpr-tabs">
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'settings-base')) ?>"><?php _e('Basic setting', self::TEXT_DOMAIN) ?></a></li>
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'settings-counting')) ?>" class="wpr-current"><?php _e('Count setting', self::TEXT_DOMAIN) ?></a></li>
    </ul>
    <div class="wpr-contents">
      <?php settings_errors() ?>
      <p class="notice">
        <?php _e('When I change the following setting and save it, cache is rebuilt', self::TEXT_DOMAIN) ?>
      </p>
<?php
        global $wpdb;
        if (preg_match('/_cs\z/', $wpdb->collate)) {
?>
      <p class="notice">
        <?php _e('The capital letter small letter is distinguished by setting of WordPress', self::TEXT_DOMAIN) ?>
      </p>
<?php
        }
?>
      <form action="options.php" method="post" enctype="multipart/form-data">
<?php
        settings_fields('wpr-settings-counting');
        do_settings_sections('wpr-settings-counting');
        submit_button();
?>
      </form>
    </div>
  </div>
</div>
<?php
    }

    private function data_cache() {
        $base_url = admin_url('tools.php?page=wp-ranking-pro');
?>
<div class="wrap">
<?php $this->template_menu('data') ?>
  <div class="wpr-tabs-and-contents">
    <ul class="wpr-tabs">
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'data-cache')) ?>" class="wpr-current"><?php _e('Cache', self::TEXT_DOMAIN) ?></a></li>
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'data-reset')) ?>"><?php _e('Reset', self::TEXT_DOMAIN) ?></a></li>
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'data-cleanlog')) ?>"><?php _e('Clean-up log', self::TEXT_DOMAIN) ?></a></li>
    </ul>
    <div class="wpr-contents">
      <?php settings_errors() ?>
      <p class="notice">
        <?php _e('Please push the button for some reason when you want to rebuild cache (the confirmation screen does not exit)', self::TEXT_DOMAIN) ?>
      </p>
      <form action="options.php" method="post" enctype="multipart/form-data">
<?php
        settings_fields('wpr-data-cache');
?>
        <div class="submit">
          <?php _e('Cache data', self::TEXT_DOMAIN) ?>
          <input type="hidden" name="wpr_rebuild_cache" value="1">
          <input type="submit" value="<?php _e('Rebuilding', self::TEXT_DOMAIN) ?>" class="button">
        </div>
      </form>
    </div>
  </div>
</div>
<?php
    }

    private function data_reset() {
        $base_url = admin_url('tools.php?page=wp-ranking-pro');
?>
<div class="wrap">
<?php $this->template_menu('data') ?>
  <div class="wpr-tabs-and-contents">
    <ul class="wpr-tabs">
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'data-cache')) ?>"><?php _e('Cache', self::TEXT_DOMAIN) ?></a></li>
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'data-reset')) ?>" class="wpr-current"><?php _e('Reset', self::TEXT_DOMAIN) ?></a></li>
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'data-cleanlog')) ?>"><?php _e('Clean-up log', self::TEXT_DOMAIN) ?></a></li>
    </ul>
    <div class="wpr-contents">
      <?php settings_errors() ?>
      <p class="notice">
        <?php _e('Start counting again from the stage when I reset it when I reset page view', self::TEXT_DOMAIN) ?><br>
        <?php _e('Please consider whether you take the backup because you cannot return this operation carefully', self::TEXT_DOMAIN) ?>
      </p>
      <form action="options.php" method="post" enctype="multipart/form-data">
<?php
        settings_fields('wpr-data-reset');
?>
        <div class="submit">
          <?php _e('Page view', self::TEXT_DOMAIN) ?>
          <input type="hidden" name="wpr_reset_views" value="1">
          <input type="submit" value="<?php _e('Reset', self::TEXT_DOMAIN) ?>" class="button require-confirmation" data-wpr-message="<?php _e('Do you really reset it?', self::TEXT_DOMAIN) ?>">
        </div>
      </form>
    </div>
  </div>
</div>
<?php
    }

    private function data_cleanlog() {
        $base_url = admin_url('tools.php?page=wp-ranking-pro');
?>
<div class="wrap">
<?php $this->template_menu('data') ?>
  <div class="wpr-tabs-and-contents">
    <ul class="wpr-tabs">
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'data-cache')) ?>"><?php _e('Cache', self::TEXT_DOMAIN) ?></a></li>
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'data-reset')) ?>"><?php _e('Reset', self::TEXT_DOMAIN) ?></a></li>
      <li><a href="<?php $this->util_path_to($base_url, array('path' => 'data-cleanlog')) ?>" class="wpr-current"><?php _e('Clean-up log', self::TEXT_DOMAIN) ?></a></li>
    </ul>
    <div class="wpr-contents">
      <?php settings_errors() ?>
      <p class="notice">
        <?php _e('When I change the following setting and save it, clean-up is carried out once at that point', self::TEXT_DOMAIN) ?>
      </p>
      <form action="options.php" method="post" enctype="multipart/form-data">
<?php
        settings_fields('wpr-data-cleanlog');
        do_settings_sections('wpr-data-cleanlog');
        submit_button();
?>
      </form>
    </div>
  </div>
</div>
<?php
    }

    private function custom_ranking() {
        $GET = stripslashes_deep($_GET);

        $base_url = admin_url('tools.php?page=wp-ranking-pro');

        $ranking_id = !empty($GET['ranking_id'])
            ? intval($GET['ranking_id'])
            : 0 ;

        if ($ranking_id) {
            global $wpdb;

            $ranking = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM `{$wpdb->prefix}wpr_ranking` WHERE `id` = %d", $ranking_id));

            if (!$ranking) {
                wp_die(__('Was going to edit a ranking not to exist', self::TEXT_DOMAIN));
            }

            $ranking_obj = new WP_Ranking_PRO_Model_Ranking($ranking->id);

            $image_preview = $ranking_obj->output_preview();

            # using in including
            $settings = $ranking_obj->settings;
            $posts    = $ranking_obj->get_posts();

            ob_start();

            $mode = 'update';

            include(dirname($this->file_path()) . '/templates/custom-ranking.php');

            $html = ob_get_clean();

            $params = json_decode($ranking->settings, true);
            $params['id']   = $ranking->id;
            $params['name'] = $ranking->name;

            $fif = new WP_Ranking_PRO_FillInForm();
            echo $fif->fill($html, $params, array('_wpnonce'));
        }
        else {
            $mode = 'create';

            include(dirname($this->file_path()) . '/templates/custom-ranking.php');
        }
    }

    private function help_parameters($route) {
        $this->_help($route);
    }

    private function help_samples($route) {
        $this->_help($route);
    }

    private function help_faq($route) {
        $this->_help($route);
    }

    private function _help($route) {
        $names = array(
            'parameters' => __('List of parameters',  self::TEXT_DOMAIN),
            'samples'    => __('PHP cord description example', self::TEXT_DOMAIN),
            'faq'        => __('FAQs',    self::TEXT_DOMAIN),
        );

        include(dirname($this->file_path()) . '/templates/help.php');
    }

    private function template_menu($current) {
        $base_url = admin_url('tools.php?page=wp-ranking-pro');

        $menus = array(
            'main'     => array($this->util_path_to($base_url, array(),                            false), __('Statistics',               self::TEXT_DOMAIN)),
            'settings' => array($this->util_path_to($base_url, array('path' => 'settings-base'),   false), __('Setting',       self::TEXT_DOMAIN)),
            'data'     => array($this->util_path_to($base_url, array('path' => 'data-cache'),      false), __('Data',             self::TEXT_DOMAIN)),
            'custom'   => array($this->util_path_to($base_url, array('path' => 'custom-ranking'),  false), __('Custom ranking', self::TEXT_DOMAIN)),
            'help'     => array($this->util_path_to($base_url, array('path' => 'help-parameters'), false), __('Help',             self::TEXT_DOMAIN)),
        );

?>
  <h1><?php _e('WP-Ranking PRO', self::TEXT_DOMAIN) ?></h1>
  <p><?php _e('"WP-Ranking PRO" creates rankings in various elements', self::TEXT_DOMAIN) ?></p>
  <ul class="wpr-menu">
<?php
        foreach ($menus as $name => $attrs) {
?>
    <li><a href="<?php echo $attrs[0] ?>"<?php echo $current == $name ? ' class="wpr-current"' : '' ?>><?php echo $attrs[1] ?></a></li>
<?php
        }
?>
  </ul>
<?php
    }

    private function reset_post_views() {
        $GET = stripslashes_deep($_GET);

        if (empty($GET['post_id'])) {
            return;
        }

        $post_id = $GET['post_id'];

        global $wpdb;

        $wpdb->query($wpdb->prepare("
            DELETE
            FROM
                `{$wpdb->prefix}wpr_views`
            WHERE
                post_id = %d
            ",
                $post_id
        ));

        $views = new WP_Ranking_PRO_Model_Views();
        $views->rebuild_cache();
    }

    public function do_as_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'wpr');

        $ranking = new WP_Ranking_PRO_Model_Ranking($atts['id']);

        echo $ranking->output_ranking();
    }

    # PHPコード用
    public function enqueue_style($id) {
        $this->ids_for_styles[] = $id;

        add_action('wp_enqueue_scripts', array($this, '_enqueue_style'));
    }

    public function _enqueue_style() {
        foreach ($this->ids_for_styles as $id) {
            $ranking = new WP_Ranking_PRO_Model_Ranking($id);

            if (!($ranking->id && $ranking->settings->use_html_template)) {
                continue;
            }

            wp_enqueue_style(
                'WPR_Stylesheet' . $ranking->settings->html_template,
                plugins_url('css/ranking-'. $ranking->settings->html_template . '.css', $this->file_path()),
                array(), # keep default
                self::$this_version
            );
        }
    }

    # PHPコード用
    public function output($id) {
        $ranking = new WP_Ranking_PRO_Model_Ranking($id);

        echo $ranking->output_ranking();
    }

    # PHPコード用
    public function new_ranking($query_like_string) {
        parse_str($query_like_string, $settings_array);
        $settings = stripslashes_deep($settings_array);

        # XXX こちらで対応しないと、template_menu()などが使えないため……。

        # 一般ページから呼び出される場合、settings_errors()などは定義されていな
        # い？　ため。
        $for_get_html = true;
		$base_url = admin_url('tools.php?page=wp-ranking-pro');
        $mode = 'create';
		$post_type_name = $settings['post_type'];
        ob_start();
        include(dirname(WP_Ranking_PRO::file_path()) . '/templates/custom-ranking.php');
        $form_html = ob_get_clean();

        $form = new WP_Ranking_PRO_Form($form_html);
        $rules = $form->build_validator_rules();

        $validator = new WP_Ranking_PRO_Validator($rules);
        $normalized_params = $validator->validate($settings);

        # 日付だけは別途チェック
        foreach (array('begin', 'end') as $prefix) {
            if (!checkdate(
                $normalized_params[$prefix . '_month'],
                $normalized_params[$prefix . '_day'],
                $normalized_params[$prefix . '_year']
            )) {
                $normalized_params[$prefix . '_month'] = date_i18n('Y');
                $normalized_params[$prefix . '_day']   = date_i18n('n');
                $normalized_params[$prefix . '_year']  = date_i18n('j');
            }
        }

        $ranking  = new WP_Ranking_PRO_Model_Ranking(0);
        $ranking->set_settings($normalized_params);

        if ($ranking->settings->use_html_template) {
            $this->ids_of_html_templates[] = $ranking->settings->html_template;

            add_action('wp_enqueue_scripts', array($this, '_enqueue_style2'));
        }

        return $ranking;
    }

    public function _enqueue_style2() {
        $html_templates = sort(array_unique($this->ids_of_html_templates), SORT_NUMERIC);

        foreach ($this->ids_of_html_templates as $html_template) {
            wp_enqueue_style(
                'WPR_Stylesheet' . $html_template,
                plugins_url('css/ranking-'. $html_template . '.css', $this->file_path()),
                array(), # keep default
                self::$this_version
            );
        }
    }

    public function add_stylesheets_when_doing_shortcode($posts) {
        foreach ($posts as $post) {
            $pattern = get_shortcode_regex();

            preg_match_all("/$pattern/s", $post->post_content, $m_all, PREG_SET_ORDER);

            foreach ($m_all as $m) {
                $tag = $m[2];
                $attr = shortcode_parse_atts($m[3]);

                if ($tag == 'wpr' && isset($attr['id'])) {
                    $ranking = new WP_Ranking_PRO_Model_Ranking($attr['id']);

                    if (!($ranking->id && $ranking->settings->use_html_template)) {
                        continue;
                    }

                    wp_enqueue_style(
                        'WPR_Stylesheet' . $ranking->settings->html_template,
                        plugins_url('css/ranking-'. $ranking->settings->html_template . '.css', $this->file_path()),
                        array(), # keep default
                        self::$this_version
                    );
                }
            }
        }

        return $posts;
    }

    public function add_cron_schedules($schedules) {
        $schedules['6hours'] = array(
            'interval'  => 21600,
            'display'   => 'every 6 hours'
        );
        $schedules['weekly'] = array(
            'interval'  => 604800,
            'display'   => 'weekly'
        );
        $schedules['monthly'] = array(
            'interval'  => date_i18n('t')*86400,
            'display'   => 'monthly'
        );
        return $schedules;
    }

    public function _nop() {
        # nop
    }

    # 基本設定: 集計対象 {
    public function _settings_base__count_only_visitors() {
?>
<select name="wpr_count_only_visitors">
  <option value="0"<?php selected(0, get_option('wpr_count_only_visitors', 0)) ?>><?php _e('All user', self::TEXT_DOMAIN) ?></option>
  <option value="1"<?php selected(1, get_option('wpr_count_only_visitors', 0)) ?>><?php _e('Remove a login user', self::TEXT_DOMAIN) ?></option>
</select>
<?php
    }

    public function _sanitize_wpr_count_only_visitors($input) {
        return $this->util_sanitize_int_range($input, 0, 1);
    }
    # }

    # 基本設定: Ajaxでのログ取得
    public function _settings_base__use_ajax_logging() {
?>
<select name="wpr_use_ajax_logging">
  <option value="0"<?php selected(0, get_option('wpr_use_ajax_logging', 0)) ?>><?php _e('Invalidity', self::TEXT_DOMAIN) ?></option>
  <option value="1"<?php selected(1, get_option('wpr_use_ajax_logging', 0)) ?>><?php _e('Effectively', self::TEXT_DOMAIN) ?></option>
</select>
<?php
    }

    public function _sanitize_wpr_use_ajax_logging($input) {
        return $this->util_sanitize_int_range($input, 0, 1);
    }
    # }

    # 基本設定: 閲覧に必要な権限
    public function _settings_base__require_authority() {
?>
<select name="wpr_require_authority">
  <option value="0"<?php selected(0, get_option('wpr_require_authority', 0)) ?>><?php _e('More than a subscriber', self::TEXT_DOMAIN) ?></option>
  <option value="1"<?php selected(1, get_option('wpr_require_authority', 0)) ?>><?php _e('More than a contributor', self::TEXT_DOMAIN) ?></option>
  <option value="2"<?php selected(2, get_option('wpr_require_authority', 0)) ?>><?php _e('More than a contributor', self::TEXT_DOMAIN) ?></option>
  <option value="3"<?php selected(3, get_option('wpr_require_authority', 0)) ?>><?php _e('More than an editor', self::TEXT_DOMAIN) ?></option>
  <option value="4"<?php selected(4, get_option('wpr_require_authority', 0)) ?>><?php _e('More than a administer', self::TEXT_DOMAIN) ?></option>
</select>
<?php
    }

    public function _sanitize_wpr_require_authority($input) {
        return $this->util_sanitize_int_range($input, 0, 4);
    }
    # }

    # 基本設定: 代替画像
    public function _settings_base__alternative_image() {
        $src = get_option('wpr_alternative_image');
?>
<input type="file" name="wpr_alternative_image" value=""><br>
<?php if (!empty($src)) { ?>
<p><img src="<?php echo esc_url($src) ?>"></p>
<?php } else { ?>
<p><img src="<?php echo esc_url(plugins_url('thumb.png', $this->file_path())) ?>"></p>
<?php } ?>
<?php
    }

    public function _sanitize_wpr_alternative_image($input) {
        if (empty($_FILES['wpr_alternative_image']['tmp_name'])) {
            return get_option('wpr_alternative_image', '');
        }
        else {
            $rv = wp_handle_upload($_FILES['wpr_alternative_image'], array('test_form' => false));

            if (is_array($rv) && isset($rv['url'])) {
                return $rv['url'];
            }
            else {
                # TODO: error!
            }
        }
    }
    # }

    # 基本設定: 画像タイプ
    public function _settings_base__image_type() {
?>
<select name="wpr_image_type" id="wpr-image-type">
  <option value="3"<?php selected(3, get_option('wpr_image_type', 0)) ?>><?php _e('Eye catch', self::TEXT_DOMAIN) ?></option>
  <option value="0"<?php selected(0, get_option('wpr_image_type', 0)) ?>><?php _e('Substitute image', self::TEXT_DOMAIN) ?></option>
  <option value="1"<?php selected(1, get_option('wpr_image_type', 0)) ?>><?php _e('The first image in the contribution', self::TEXT_DOMAIN) ?></option>
  <option value="2"<?php selected(2, get_option('wpr_image_type', 0)) ?>><?php _e('Custom Field', self::TEXT_DOMAIN) ?></option>
</select>
<br>
<input type="text" name="wpr_image_type_custom_field" value="<?php echo get_option('wpr_image_type_custom_field', '') ?>" id="wpr-image-type-custom-field" class="regular-text">
<?php
    }

    public function _sanitize_wpr_image_type($input) {
        return $this->util_sanitize_int_range($input, 0, 3);
    }

    public function _sanitize_wpr_image_type_custom_field($input) {
        $POST = stripslashes_deep($_POST);

        if (!isset($POST['wpr_image_type_custom_field'])) {
            return get_option('wpr_image_type_custom_field', '');
        }
        else {
            return $input;
        }
    }
    # }

    # 基本設定: ランキングキャッシュ {
    public function _settings_base__ranking_cache_expire() {
        $times = array(
            __('Invalidity',   self::TEXT_DOMAIN) => 0,
            __('5 minutes',    self::TEXT_DOMAIN) => 60 *  5,
            __('30 minutes',   self::TEXT_DOMAIN) => 60 * 30,
            __('1 hour',  self::TEXT_DOMAIN) => 60 * 60 *  1,
            __('3 hour',  self::TEXT_DOMAIN) => 60 * 60 *  3,
            __('6 hour',  self::TEXT_DOMAIN) => 60 * 60 *  6,
            __('12 hour', self::TEXT_DOMAIN) => 60 * 60 * 12,
            __('24 hour', self::TEXT_DOMAIN) => 60 * 60 * 24,
        );
?>
<select name="wpr_ranking_cache_expire" id="wpr-ranking-cache-expire">
<?php
        foreach ($times as $label => $sec) {
?>
  <option value="<?php echo $sec ?>"<?php selected($sec, get_option('wpr_ranking_cache_expire', 0)) ?>><?php echo $label ?></option>
<?php
        }
?>
</select>
<br>
<?php
    }

    public function _sanitize_wpr_ranking_cache_expire($input) {
        return $this->util_sanitize_int_range($input, 0, 60 * 60 * 24);
    }
    # }

    # 基本設定: アクセス元IPアドレスの取得キー {
    public function _settings_base__remote_addr_key() {
?>
<p><?php _e('When I input a wrong value into this setting and do it, I cannot acquire an IP address normally. Please be careful enough', self::TEXT_DOMAIN) ?></p>
<input name="wpr_remote_addr_key" id="wpr-remote_addr_key" value="<?php echo get_option('wpr_remote_addr_key', 'REMOTE_ADDR') ?>">
<br>
<?php
    }

    public function _sanitize_wpr_remote_addr_key($input) {
        return trim($input);
    }
    # }

    # 集計設定: 除外するアクセス元 {
    public function _settings_counting__exclusion_remote_addrs() {
?>
<p><?php _e('An end can input a plural number by a party. I do not add up the access from an applicable IP address', self::TEXT_DOMAIN) ?></p>
<textarea name="wpr_exclusion_remote_addrs" cols="50" rows="10" class="large-text code"><?php echo get_option('wpr_exclusion_remote_addrs', '') ?></textarea>
<?php
    }

    public function _sanitize_wpr_exclusion_remote_addrs($input) {
        return $input;
    }
    # }

    # 集計設定: 除外するリファラー {
    public function _settings_counting__exclusion_http_referers() {
?>
<p><?php _e('An end can input a plural number by a party. I do not add up access through applicable re-Farrah', self::TEXT_DOMAIN) ?></p>
<textarea name="wpr_exclusion_http_referers" cols="50" rows="10" class="large-text code"><?php echo get_option('wpr_exclusion_http_referers', '') ?></textarea>
<?php
    }

    public function _sanitize_wpr_exclusion_http_referers($input) {
        return $input;
    }
    # }

    # 集計設定: モバイルとして扱うユーザーエージェント {
    public function _settings_counting__as_mobile_http_user_agent() {
?>
<p><?php _e('An end can input a plural number by a party. I add up the access by the user agent that I appointed as mobile', self::TEXT_DOMAIN) ?></p>
<textarea name="wpr_as_mobile_http_user_agent" cols="50" rows="10" class="large-text code"><?php echo get_option('wpr_as_mobile_http_user_agent', '') ?></textarea>
<?php
    }

    public function _sanitize_wpr_as_mobile_http_user_agent($input) {
        return $input;
    }
    # }

    # 集計設定: 除外するユーザーエージェント {
    public function _settings_counting__exclusion_http_user_agent() {
?>
<p><?php _e('An end can input a plural number by a party. I do not add up access through applicable the user agent', self::TEXT_DOMAIN) ?></p>
<textarea name="wpr_exclusion_http_user_agent" cols="50" rows="10" class="large-text code"><?php echo get_option('wpr_exclusion_http_user_agent', '') ?></textarea>
<?php
    }

    public function _sanitize_wpr_exclusion_http_user_agent($input) {
        return $input;
    }
    # }

    # ログ削除: 自動ログ削除
    public function _data_cleanlog__auto_cleanlog() {
?>
<label><input type="radio" name="wpr_auto_cleanlog" value="1"<?php checked(1, get_option('wpr_auto_cleanlog', 0)) ?>> <?php _e('Effectively', self::TEXT_DOMAIN) ?></label>
<label><input type="radio" name="wpr_auto_cleanlog" value="0"<?php checked(0, get_option('wpr_auto_cleanlog', 0)) ?>> <?php _e('Invalidity', self::TEXT_DOMAIN) ?></label>
<?php
    }

    public function _sanitize_wpr_auto_cleanlog($input) {
        return $this->util_sanitize_int_range($input, 0, 1);
    }
    # }

    # ログ削除: ログ削除周期
    public function _data_cleanlog__cleanlog_cycle() {
?>
<select name="wpr_cleanlog_cycle">
  <option value="0"<?php selected(0, get_option('wpr_cleanlog_cycle', 0)) ?>><?php _e('Every day', self::TEXT_DOMAIN) ?></option>
  <option value="1"<?php selected(1, get_option('wpr_cleanlog_cycle', 0)) ?>><?php _e('Every week', self::TEXT_DOMAIN) ?></option>
  <option value="2"<?php selected(2, get_option('wpr_cleanlog_cycle', 0)) ?>><?php _e('Every month', self::TEXT_DOMAIN) ?></option>
</select>
<?php
    }

    public function _sanitize_wpr_cleanlog_cycle($input) {
        return $this->util_sanitize_int_range($input, 0, 2);
    }
    # }

    # ログ削除: ログ削除する時間帯 {
    public function _data_cleanlog__cleanlog_time() {
?>
<select name="wpr_cleanlog_time">
<?php
        foreach (range(0, 23) as $hour) {
?>
  <option value="<?php echo $hour ?>"<?php selected($hour, get_option('wpr_cleanlog_time', 0)) ?>><?php echo $hour ?><?php _e('Time', self::TEXT_DOMAIN) ?></option>
<?php
        }
?>
</select>
<span id="local-time"><?php _e('Local time', self::TEXT_DOMAIN) ?>: <code><?php echo date_i18n('Y-m-d H:i:s') ?></code></span>
<?php
    }

    public function _sanitize_wpr_cleanlog_time($input) {
        return $this->util_sanitize_int_range($input, 0, 23);
    }
    # }

    # ログ削除: ログ削除を保留する期間 {
    public function _data_cleanlog__cleanlog_keep_times() {
?>
<input type="text" name="wpr_cleanlog_keep_times" value="<?php echo get_option('wpr_cleanlog_keep_times', 0) ?>" class="small-text">
<?php
    }

    public function _sanitize_wpr_cleanlog_keep_times($input) {
        return $this->util_sanitize_int_range($input, 0, 99999999);
    }
    # }

    # href, srcに出力する前提なので注意
    private function util_path_to($url, $params = array(), $output = true) {
        $path = esc_url(add_query_arg($params, $url));

        if ($output) {
            echo $path;
        }

        return $path;
    }

    private function util_add_settings_field($title, $page_name, $field_name) {
        $page_name_hyphen  = strtr($page_name,  '_', '-');
        $field_name_hyphen = strtr($field_name, '_', '-');

        add_settings_field(
            "wpr-{$page_name_hyphen}-{$field_name_hyphen}",
            $title,
            array($this, "_{$page_name}__{$field_name}"),
            "wpr-{$page_name_hyphen}",
            "wpr-{$page_name_hyphen}-1"
        );

        register_setting(
            "wpr-{$page_name_hyphen}",
            "wpr_{$field_name}",
            array($this, "_sanitize_wpr_{$field_name}")
        );
    }

    private function util_sanitize_int_range($input, $min, $max) {
        $input = intval($input);

        if (!($min <= $input && $input <= $max)) {
            $input = $min;
        }

        return $input;
    }

    # __FILE__の代替。クラス定数にしたいところだが、PHPの制限でできないため、ク
    # ラスメソッドとして実装する
    public static function file_path() {
        if (!empty(self::$this_file_path)) {
            return self::$this_file_path;
        }

        # plugin_basename()は古いバージョンのWordPress (3.9未満？) では、シンボ
        # リックリンクに適切に対応できないため
        $dirname  = basename(dirname(__FILE__));
        $filename = basename(__FILE__);
        $basename = $dirname . '/' . $filename;

        # WP_PLUGIN_DIRはプラグインから直接使用するべきではないとされているが、
        # ほかに方法はない
        self::$this_file_path = WP_PLUGIN_DIR . '/' . $basename;

        return self::$this_file_path;
    }

    private function _util_can_view_ranking() {
        $require_authority = get_option('wpr_require_authority', 0);

        return $require_authority <= $this->_util_get_current_user_level();
    }

    # http://codex.wordpress.org/Function_Reference/current_user_can#Notes
    # http://docs.appthemes.com/tutorials/wordpress-check-user-role-function/
    private function _util_get_current_user_level() {
        $roles = array(
            'administrator' => 4, # 管理者
            'editor'        => 3, # 編集者
            'author'        => 2, # 投稿者
            'contributor'   => 1, # 寄稿者
            'subscriber'    => 0, # 購読者
        );

        $user = wp_get_current_user();

        foreach ($roles as $role => $level) {
            if (in_array($role, (array)$user->roles)) {
                return $level;
            }
        }

        return 0; # 非ログインユーザには、そもそもrolesは割り当てられない。
    }

    private function _util_fix_dashboard_rankings() {
        $current_rankings = get_option('wpr_dashboard_rankings', range(1, 6));
        $fixed_rankings   = array_filter($current_rankings, array($this, '_util_filter_exists_ranking'));

        if (empty($fixed_rankings)) {
            update_option('wpr_dashboard_rankings', range(1, 6));
        }
        elseif (count($current_rankings) != count($fixed_rankings)) {
            update_option('wpr_dashboard_rankings', $fixed_rankings);
        }
    }

    private function _util_filter_exists_ranking($ranking_id) {
        $ranking = new WP_Ranking_PRO_Model_Ranking($ranking_id);

        return $ranking->id;
    }


	public function _activation() {
		#add schedule
		if (get_option('wpr_ranking_cache_expire')) {
			# Effectively
            if(!wp_next_scheduled('wpr_scheduled_cron')) {
                wp_schedule_event(time(), '6hours', 'wpr_scheduled_cron');
            }
		}

		if (get_option('wpr_auto_cleanlog', 0)) {
            if(!wp_next_scheduled('wpr_scheduled_cleanlog')) {
                $cleanlog_cycle     = get_option('wpr_cleanlog_cycle', 0);
                $cleanlog_time	    = get_option('wpr_cleanlog_time' , 0);

                switch ($cleanlog_cycle) {
                    case 0: # 毎日
                        $next     = strtotime('today +1 day ' . $cleanlog_time . ' hour');
                        if ($next-(get_option('gmt_offset') * 3600) <= time()) {
                            $next += 86400;
                        }
                        $schedule = 'daily';
                        break;

                    case 1: # 毎週 (WordPressの「週のはじまり」に準ずる)
                        $this_week           = get_weekstartend(date_i18n('Y-m-d'));
                        $this_week_last_day  = date_i18n('Y-m-d', $this_week['end']);
                        $next     = strtotime($this_week_last_day . ' +1 day ' . $cleanlog_time . ' hour');
                        $schedule  = 'weekly';
                        break;

                    case 2: # 毎月
                        $next     = strtotime(date_i18n('Y-m-t') . ' +1 day ' . $cleanlog_time . ' hour');
                        $schedule  = 'monthly';
                        break;
                }

                # WordPress breaks PHP time-zone
                $next -= (get_option('gmt_offset') * 3600);

                wp_schedule_event($next, $schedule, 'wpr_scheduled_cleanlog');
            }
		}
	}

	public function _deactivation() {
		# clear schedule
		wp_clear_scheduled_hook('wpr_scheduled_cron');
		wp_clear_scheduled_hook('wpr_scheduled_cleanlog');
	}

    public function _uninstall() {
        global $wpdb;

        # delete settings
        delete_option('wpr_db_version');

        delete_option('wpr_count_only_visitors');
        delete_option('wpr_use_ajax_logging');
        delete_option('wpr_require_authority');
        delete_option('wpr_alternative_image');
        delete_option('wpr_image_type');
        delete_option('wpr_image_type_custom_field');
        delete_option('wpr_ranking_cache_expire');
        delete_option('wpr_remote_addr_key');

        delete_option('wpr_exclusion_remote_addrs');
        delete_option('wpr_exclusion_http_referers');
        delete_option('wpr_as_mobile_http_user_agent');
        delete_option('wpr_exclusion_http_user_agent');

        delete_option('wpr_auto_cleanlog');
        delete_option('wpr_cleanlog_cycle');
        delete_option('wpr_cleanlog_time');
        delete_option('wpr_cleanlog_keep_times');

        delete_option('wpr_current_ranking');
        delete_option('wpr_dashboard_rankings');

        # delete transient data
        $wpdb->query(<<<SQL
            DELETE FROM
                `{$wpdb->options}`
            WHERE
                `option_name` LIKE '\\_transient\\_wpr-%'
                    OR
                `option_name` LIKE '\\_transient\\_timeout\\_wpr-%'
SQL
        );

        # delete tables
        $wpdb->query("DROP TABLE IF EXISTS `{$wpdb->prefix}wpr_views`, `{$wpdb->prefix}wpr_views_per_day`, `{$wpdb->prefix}wpr_ranking`, `{$wpdb->prefix}wpr_http_user_agents`");
    }
}
