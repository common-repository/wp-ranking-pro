<?php

require_once(dirname(WP_Ranking_PRO::file_path()) . '/lib/template-engine.php');
require_once(dirname(WP_Ranking_PRO::file_path()) . '/lib/form.php');
require_once(dirname(WP_Ranking_PRO::file_path()) . '/lib/validator.php');

class WP_Ranking_PRO_Model_Ranking
{
    private $id;
    private $name;
    private $settings;

    private $default_settings = array(
        'post_type'              => '',
        'order_by'               => 'views',
        'filter_type'            => 'category',
        'filter_by'              => '',
        'use_preset_period'      => '1',
        'begin_year'             => '2015',
        'begin_month'            => '1',
        'begin_day'              => '1',
        'end_year'               => '2015',
        'end_month'              => '1',
        'end_day'                => '1',
        'pc_or_mobile'           => 'both',
        'limit'                  => '10',
        'max_post_title'         => '100',
        'max_excerpt'            => '100',
        'date_format'            => '',
        'thumbnail_y'            => '60',
        'thumbnail_x'            => '60',
        'show_all_categories'    => '1',
        'show_all_tags'          => '1',
        'post_meta_key'          => '',
        'use_html_template'      => '1',
        'html_template'          => '1',
        'html_title_prefix'      => '',
        'html_title_suffix'      => '',
        'html_ranking_prefix'    => '',
        'html_ranking_suffix'    => '',
        'html_ranking_content'   => '',
        'exclude_post_ids'       => '',
        'exclude_category_names' => '',
        'exclude_tag_names'      => '',
    );

    public function __construct($id) {
        if ($id === 0) {
            $this->id   = $id;
            $this->name = '(no name)';

            return;
        }

        if (is_null($id)) {
            return;
        }

        global $wpdb;

        $ranking = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `{$wpdb->prefix}wpr_ranking` WHERE `id` = %d ", $id));

        if (!$ranking) {
            return;
        }

        $this->id       = $ranking->id;
        $this->name     = $ranking->name;
        $this->settings = (object)array_merge($this->default_settings, json_decode($ranking->settings, true));
    }

    public function __get($name) {
        # ここは空かのチェックは？

        switch ($name) {
        case 'id':
            return $this->id;
            break;

        case 'name':
            return $this->name;
            break;

        case 'settings':
            return $this->settings;
            break;

        default:
            error_log("$name is not available property");
        }
    }

    public function type_name() {
        if (is_null($this->id)) {
            wp_die("fatal error: can't get type_name from undefined ranking");
        }

        if ($this->settings->order_by == 'comment_count') {
            return 'comments';
        }
        else {
            switch ($this->settings->pc_or_mobile) {
            case 'pc':
                return __('PC', WP_Ranking_PRO::TEXT_DOMAIN);
                break;
            case 'mobile':
                return __('mobile', WP_Ranking_PRO::TEXT_DOMAIN);
                break;
            case 'both':
                return __('TOTAL', WP_Ranking_PRO::TEXT_DOMAIN);
                break;
            }
        }
    }

    public function set_settings(array $settings) {
        if ($this->id !== 0 || $this->settings !== null) {
            return;
        }

        $this->settings = (object)array_merge($this->default_settings, $settings);
    }

    public function get_posts($settings_overrider = array()) {
        if (is_null($this->id)) {
            return array();
        }

        $settings = $this->settings;

        foreach ($settings_overrider as $key => $value) {
            $settings->$key = $value;
        }

        if (!(isset($settings->order) && preg_match('/\Aviews(_pc|_mobile|_both)?\.(desc|asc)\z/', $settings->order))) {
            $settings->order = 'views.desc';
        }

        if (get_option('wpr_ranking_cache_expire', 0)) {
            $settings_hash = $this->_util_settings_to_hash((array)$settings);

            if ($cache = $this->_util_get_cache($settings_hash)) {
                return json_decode($cache);
            }
        }

        $posts = $settings->order_by == 'views'
            ? $this->views_ranking($settings)
            : $this->comments_ranking($settings) ;

        if (isset($settings_hash)) {
            $this->_util_set_cache($settings_hash, $posts);

            # delete expire transient data
            global $wpdb;

            $option_list = $wpdb->get_results($wpdb->prepare("SELECT `option_name` FROM `{$wpdb->options}` WHERE `option_name` LIKE '\\_transient\\_timeout\\_wpr-%%' AND option_value < %s", time()));
            if ($option_list) {
                foreach ($option_list as $option) {
                    $transient_key = substr($option->option_name, 19);
                    if (get_transient($transient_key) === false) {
                        delete_transient($transient_key);
                    }
                }
            }
        }

        return $posts;
    }

    private function views_ranking($settings) {
        $periods_statements = '';
        $periods_parameters = array();

        # 期間指定
        if ($settings->use_preset_period) {
            $preset_periods = array(
                '24h'    => 'accessed_on = CURDATE()',
                '1week'  => 'accessed_on > CURDATE() - INTERVAL 1 WEEK',
                '1month' => 'accessed_on > CURDATE() - INTERVAL 1 MONTH',
                '1year'  => 'accessed_on > CURDATE() - INTERVAL 1 YEAR',
                'all'    => '1',
            );

            if (isset($preset_periods[ $settings->period ])) {
                $periods_statements = $preset_periods[ $settings->period ];
            }
            else {
                wp_die('fatal error: is not preset period');
            }
        }
        else {
            $periods_statements   = 'accessed_on BETWEEN %s AND %s';
            $periods_parameters[] = implode('-', array($settings->begin_year, $settings->begin_month, $settings->begin_day));
            $periods_parameters[] = implode('-', array($settings->end_year  , $settings->end_month  , $settings->end_day));
        }

        $suffix = 1;

        list($exclude_statements, $exclude_parameters) = $this->_build_exclude_queries(
            null,
            $suffix,
            $settings->exclude_post_ids,
            $settings->exclude_category_names,
            $settings->exclude_tag_names
        );

        if (!empty($exclude_statements)) {
            $suffix++;
        }

        list($include_statements, $include_parameters) = $this->_build_include_queries(
            $exclude_statements,
            $suffix,
            $settings->post_type,
            $settings->filter_type,
            $settings->filter_by
        );

        if (!empty($include_statements)) {
            $suffix++;
        }

        # taken from main__ranking()
        $counting_target = get_option('wpr_count_only_visitors', 0)
            ? '_only_visitors'
            : '' ;

        $ua    = $settings->pc_or_mobile;
        $order = explode('.', $settings->order);

        $offset = 0;
        $limit  = $settings->limit;

        if (isset($settings->page)) {
            $offset = $limit * ($settings->page - 1);
        }

        if (isset($settings->fetch_next_one) && $settings->fetch_next_one) {
            $limit++;
        }

        # $include_statementsは、空でなければ$exclude_statementsを含む。
        $where_clause = !empty($include_statements)
            ? $include_statements
            : $exclude_statements ;
        if (empty($where_clause)) {
            $where_clause = '1';
        }

        if (in_array($this->id, range(1, 6)) || $ua == 'both') {
            $exclude_0view_posts_statement = <<<SQL
                -- exclude not viewed posts
                (
                    views_pc{$counting_target}     <> 0 OR
                    views_mobile{$counting_target} <> 0
                )
SQL;
        }
        else {
            $exclude_0view_posts_statement = <<<SQL
                -- exclude not viewed posts
                views_{$ua}{$counting_target} <> 0
SQL;
        }

        global $wpdb;

        $suffix1 = $suffix + 1;
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT
                p$suffix1.*, -- use MySQL extension for speed
                c.sum_views_pc{$counting_target}                                        AS `views_pc`,
                c.sum_views_mobile{$counting_target}                                    AS `views_mobile`,
                c.sum_views_pc{$counting_target} + c.sum_views_mobile{$counting_target} AS `views_both`,
                CASE '$ua'
                    WHEN 'pc'     THEN c.sum_views_pc{$counting_target}
                    WHEN 'mobile' THEN c.sum_views_mobile{$counting_target}
                    WHEN 'both'   THEN c.sum_views_pc{$counting_target} + c.sum_views_mobile{$counting_target}
                END AS `views`
            FROM
                (
                    (
                        SELECT
                            accessed_on,
                            post_id,
                            SUM(views_pc{$counting_target})     AS `sum_views_pc{$counting_target}`,
                            SUM(views_mobile{$counting_target}) AS `sum_views_mobile{$counting_target}`
                        FROM
                            `{$wpdb->prefix}wpr_views_per_day`
                        WHERE
                            $periods_statements
                                AND
                            $exclude_0view_posts_statement
                        GROUP BY
                            post_id
                    ) AS `c`
                JOIN
                    (
                        SELECT
                            *
                        FROM
                            `{$wpdb->posts}` AS `p$suffix`
                        WHERE
                            $where_clause
                                AND
                            (
                                post_status = 'publish'
                                    OR
                                (
                                    post_type   = 'attachment'
                                        AND
                                    post_status = 'inherit'
                                )
                            )
                    ) AS `p$suffix1` ON c.post_id = p$suffix1.ID
                )
            ORDER BY
                `$order[0]` $order[1]
            LIMIT
                %d, %d
        ",
            array_merge(
                $periods_parameters,
                    
                $exclude_parameters,
                $include_parameters,
                    
                array($offset, $limit)
            )
        ));

        return $posts;
    }

    private function comments_ranking($settings) {
        $is_joined    = array();
        $join_clause  = array();
        $bind_params  = array();
        $where_clause = array();

        global $wpdb;

        # 投稿タイプの指定
        if (!empty($settings->post_type)) {
            $where_clause[] = 'p.post_type = %s';
            $bind_params[] = $settings->post_type;
        }

        # 絞り込み
        if (!empty($settings->filter_by)) {
            $filter_values = explode(',', $settings->filter_by);

            switch ($settings->filter_type) {
            case 'category':
                if (!isset($is_joined['term*'])) {
                    $join_clause[] = "JOIN `{$wpdb->term_relationships}` AS `t_r` ON p.ID = t_r.object_id";
                    $join_clause[] = "JOIN `{$wpdb->term_taxonomy}`      AS `t_t` ON t_r.term_taxonomy_id = t_t.term_taxonomy_id";
                    $join_clause[] = "JOIN `{$wpdb->terms}`              AS `t`   ON t_t.term_id = t.term_id";

                    $is_joined['term*'] = true;
                }

                $where_clause[] = "(t_t.taxonomy = 'category' AND t.name IN (" . implode(", ", array_fill(1, count($filter_values), "%d")) . "))";
                $bind_params[] = array_merge($bind_params, $filter_values);
                break;

            case 'tag':
                if (!isset($is_joined['term*'])) {
                    $join_clause[] = "JOIN `{$wpdb->term_relationships}` AS `t_r` ON p.ID = t_r.object_id";
                    $join_clause[] = "JOIN `{$wpdb->term_taxonomy}`      AS `t_t` ON t_r.term_taxonomy_id = t_t.term_taxonomy_id";
                    $join_clause[] = "JOIN `{$wpdb->terms}`              AS `t`   ON t_t.term_id = t.term_id";

                    $is_joined['term*'] = true;
                }

                $where_clause[] = "(t_t.taxonomy = 'post_tag' AND t.name IN (" . implode(", ", array_fill(1, count($filter_values), "%d")) . "))";
                $bind_params[] = array_merge($bind_params, $filter_values);
                break;

            case 'post_author':
                if (!isset($is_joined['users'])) {
                    $join_clause[] = "JOIN `{$wpdb->users}` AS `u` ON p.post_author = u.ID";

                    $is_joined['users'] = true;
                }

                $where_clause[] = 'u.user_login IN (' . implode(', ', array_fill(1, count($filter_values), '%d')) . ')';
                $bind_params = array_merge($bind_params, $filter_values);
                break;

            default:
                wp_die('fatal error');
            }
        }

        # 投稿の除外
        if (!empty($settings->exclude_post_ids)) {
            $post_ids = explode(',', $settings->exclude_post_ids);

            $where_clause[] = 'p.ID NOT IN (' . implode(', ', array_fill(1, count($post_ids), '%d')) . ')';
            $bind_params = array_merge($bind_params, $post_ids);
        }

        # カテゴリの除外
        if (!empty($settings->exclude_category_names)) {
            $category_names = explode(',', $settings->exclude_category_names);

            if (!isset($is_joined['term*'])) {
                $join_clause[] = "JOIN `{$wpdb->term_relationships}` AS `t_r` ON p.ID = t_r.object_id";
                $join_clause[] = "JOIN `{$wpdb->term_taxonomy}`      AS `t_t` ON t_r.term_taxonomy_id = t_t.term_taxonomy_id";
                $join_clause[] = "JOIN `{$wpdb->terms}`              AS `t`   ON t_t.term_id = t.term_id";

                $is_joined['term*'] = true;
            }

            $where_clause[] = "(t_t.taxonomy = 'category' AND t.name NOT IN (" . implode(", ", array_fill(1, count($category_names), "%d")) . "))";
            $bind_params = array_merge($bind_params, $category_names);
        }

        # タグの除外
        if (!empty($settings->exclude_tag_names)) {
            $tag_names = explode(',', $settings->exclude_tag_names);

            if (!isset($is_joined['term*'])) {
                $join_clause[] = "JOIN `{$wpdb->term_relationships}` AS `t_r` ON p.ID = t_r.object_id";
                $join_clause[] = "JOIN `{$wpdb->term_taxonomy}`      AS `t_t` ON t_r.term_taxonomy_id = t_t.term_taxonomy_id";
                $join_clause[] = "JOIN `{$wpdb->terms}`              AS `t`   ON t_t.term_id = t.term_id";

                $is_joined['term*'] = true;
            }

            $where_clause[] = "(t_t.taxonomy = 'post_tag' AND t.name NOT IN (" . implode(", ", array_fill(1, count($tag_names), "%d")) . "))";
            $bind_params = array_merge($bind_params, $tag_names);
        }

        $order = explode('.', $settings->order);

        $offset = 0;
        $limit  = $settings->limit;

        if (isset($settings->page)) {
            $offset = $limit * ($settings->page - 1);
        }

        if (isset($settings->fetch_next_one) && $settings->fetch_next_one) {
            $limit++;
        }

        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT
                p.*,
                p.comment_count AS `views`
            FROM
                `{$wpdb->posts}` AS `p`
                " . (empty($join_clause) ? '' : implode(' ', $join_clause)) . "
            WHERE
                (" . (empty($where_clause) ? '1' : implode("\nAND\n", $where_clause)) . ")
                    AND
                (
                    p$suffix.post_status = 'publish'
                        OR
                    (
                        p$suffix.post_type   = 'attachment'
                            AND
                        p$suffix.post_status = 'inherit'
                    )
                )
                    AND
                p.comment_count > 0
            ORDER BY
                `views` $order[1]
            LIMIT
                %d, %d
        ",
            array_merge(
                $bind_params,
                array($offset, $limit)
            )
        ));

        return $posts;
    }

    public function output_ranking() {
        if (is_null($this->id)) {
            return __('Do not exist whether this ranking was deleted', WP_Ranking_PRO::TEXT_DOMAIN);
        }

        $settings = $this->settings;
        $posts    = $this->get_posts(array('order' => 'views.desc'));

        if ($settings->use_html_template) {
            $template_file = dirname(WP_Ranking_PRO::file_path()) . '/templates/ranking-' . $settings->html_template . '.php';
            $template_string = file_get_contents($template_file);
        }
        else {
            $template_string =
                $settings->html_title_prefix .
                '[ranking_title]' .
                $settings->html_title_suffix .
                $settings->html_ranking_prefix .
                '<?php $this->begin_loop("posts") ?>' .
                $settings->html_ranking_content .
                '<?php $this->end_loop() ?>' .
                $settings->html_ranking_suffix ;
        }

        $template = new WP_Ranking_PRO_TemplateEngine($template_string);

        $formatted_posts = $this->format_posts($settings, $posts);

        $template_args = array(
            'ranking_title'  => esc_html(__($settings->title, WP_Ranking_PRO::TEXT_DOMAIN)),
            'posts'          => $formatted_posts,
            'inner_template' => dirname(WP_Ranking_PRO::file_path()) . '/templates/ranking-' . $settings->html_template . '-inner.php',
        );

        return $template->apply($template_args);
    }

    public function output_preview() {
        if (is_null($this->id)) {
            return __('Do not exist whether this ranking was deleted', WP_Ranking_PRO::TEXT_DOMAIN);
        }

        $settings = $this->settings;
        $posts    = $this->get_posts(array('order' => 'views.desc'));

        if ($settings->use_html_template) {
            $template_file = dirname(WP_Ranking_PRO::file_path()) . '/templates/ranking-' . $settings->html_template . '-inner.php';
            $template_string = file_get_contents($template_file);
        }
        else {
            $template_string =
                $settings->html_ranking_prefix .
                '<?php $this->begin_loop("posts") ?>' .
                $settings->html_ranking_content .
                '<?php $this->end_loop() ?>' .
                $settings->html_ranking_suffix ;
        }

        $template = new WP_Ranking_PRO_TemplateEngine($template_string);

        $formatted_posts = $this->format_posts($settings, $posts);

        if (empty($formatted_posts)) {
            $formatted_posts[] = array(
                'permalink'      => '#',
                'title'          => __('An article title becomes available', WP_Ranking_PRO::TEXT_DOMAIN),
                'title_for_attr' => __('An article title becomes available', WP_Ranking_PRO::TEXT_DOMAIN),
                # 代替画像以外は$postが必要なので、常に代替画像を使ってよい
                'thumbnail'      => esc_url(plugins_url('thumb.png', WP_Ranking_PRO::file_path())),
                'thumbnail_x'    => esc_attr($settings->thumbnail_x),
                'thumbnail_y'    => esc_attr($settings->thumbnail_y),
                'rank'           => '1',
                'date'           => esc_html(date_i18n(get_option('date_format', 'Y-m-d'))),
                'excerpt'        => $this->util_omit(str_repeat(__('An extract enters', WP_Ranking_PRO::TEXT_DOMAIN), 13), $settings->max_excerpt),
                'category'       => __('Non-classification', WP_Ranking_PRO::TEXT_DOMAIN),
                'comment_count'  => '1',
                'views'          => number_format('4321'),
                'author'         => __('Author', WP_Ranking_PRO::TEXT_DOMAIN),
                'author_link'    => '#',
                'meta'           => __('Custom Field', WP_Ranking_PRO::TEXT_DOMAIN),
            );
        }

        return $template->apply(array('posts' => array($formatted_posts[0])));
    }

    private function format_posts($settings, $posts) {
        $formatted_posts = array();
        $rank = 1;

        foreach ($posts as $post) {
            $formatted_posts[] = $this->format_post($settings, $post, $rank);

            $rank++;
        }

        return $formatted_posts;
    }

    private function format_post($settings, $post_, $rank) {
        global $post;
        $post = get_post($post_->ID);
        setup_postdata($post);

        $title = $this->util_omit(get_the_title(), $settings->max_post_title);

        switch (get_option('wpr_image_type', 0)) {
        case 0:
            # 代替画像を使う
            $thumbnail = get_option('wpr_alternative_image');

            break;

        case 1:
            # 投稿内の最初の画像を使う
            $thumbnail = $this->get_first_img_src_from_html(get_the_content());

            break;

        case 2:
            # カスタムフィールドの画像を使う
            $wpr_image_type_custom_field = get_option('wpr_image_type_custom_field');
            $attachment_id = !empty($wpr_image_type_custom_field)
                ? get_post_meta(get_the_ID(), $wpr_image_type_custom_field, true)
                : '' ;

            $thumbnails = wp_get_attachment_image_src($attachment_id);
            $thumbnail  = $thumbnails ? $thumbnails[0] : null ;

            break;

        case 3:
            # アイキャッチを使う
            $image_attributes = wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()));

            if ($image_attributes) {
                $thumbnail = $image_attributes[0];
            }

            break;
        }

        # fallback
        if (empty($thumbnail)) {
            $thumbnail = plugins_url('thumb.png', WP_Ranking_PRO::file_path());
        }

        $excerpt = $this->util_omit(get_the_excerpt(), $settings->max_excerpt);

        # カテゴリの変態仕様に対処
        if ($settings->show_all_categories) {
            $category = get_the_category_list(' ');
        }
        else {
            $categories = get_the_category();

            if (empty($categories)) {
                $category = '';
            }
            else {
                $category = '<a href="' . get_category_link($categories[0]->term_id) . '">' . $categories[0]->name . '</a>';
            }
        }

        $meta_value = !empty($settings->post_meta_key)
            ? get_post_meta(get_the_ID(), $settings->post_meta_key, true)
            : '' ;

        $formatted_post = array(
            'permalink'      => esc_url(get_permalink()),
            'title'          => esc_html($title),
            'title_for_attr' => esc_attr($title),
            'thumbnail'      => esc_url($thumbnail),
            'thumbnail_x'    => esc_attr($settings->thumbnail_x),
            'thumbnail_y'    => esc_attr($settings->thumbnail_y),
            'rank'           => esc_html($rank),
            'date'           => esc_html(get_the_date($settings->date_format)),
            'excerpt'        => esc_html($excerpt),
            'category'       => $category,
            'comment_count'  => esc_html($post->comment_count),
            'views'          => esc_html(number_format($post_->views)),
            'author'         => esc_html(get_the_author()),
            'author_link'    => esc_url(get_author_posts_url(get_the_author_meta('ID'))),
            'meta'           => esc_html($meta_value),
        );
        
        wp_reset_postdata();

        return $formatted_post;
    }

    public function save_custom_ranking($form_html) {
        $POST = stripslashes_deep($_POST);

        if (!wp_verify_nonce($POST['_wpnonce'], 'wpr-custom-ranking')) {
            wp_die('nonce error');
        }

        $_post = $POST;

        unset($_post['_wpnonce']);

        $id = $_post['id'];
        unset($_post['id']);

        $name = $_post['name'];
        unset($_post['name']);

        $form = new WP_Ranking_PRO_Form($form_html);
        $rules = $form->build_validator_rules();

        $validator = new WP_Ranking_PRO_Validator($rules);
        $normalized_params = $validator->validate($_post);

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

        global $wpdb;

        # update
        if ($id) {
            $wpdb->update("{$wpdb->prefix}wpr_ranking", array(
                'name'     => $name,
                'settings' => json_encode($normalized_params),
            ),
            array(
                'id' => $id,
            ));

            add_settings_error(
                'wpr-custom-ranking',
                'wpr-custom-ranking',
                __('Updated a custom ranking', WP_Ranking_PRO::TEXT_DOMAIN),
                'updated'
            );
        }
        # create
        else {
            $wpdb->insert("{$wpdb->prefix}wpr_ranking", array(
                'name'     => $name,
                'settings' => json_encode($normalized_params),
            ));

            add_settings_error(
                'wpr-custom-ranking',
                'wpr-custom-ranking',
                __('Created a custom ranking', WP_Ranking_PRO::TEXT_DOMAIN),
                'updated'
            );

            $id = $wpdb->insert_id;
        }

        # 独自管理画面のため
        set_transient('settings_errors', get_settings_errors(), 30);

        $base_url = admin_url('tools.php?page=wp-ranking-pro');
        $url = $base_url . "&path=custom-ranking&ranking_id=$id&settings-updated=true";

        wp_redirect($url);
        exit;
    }

    public function delete_custom_ranking() {
        $GET = stripslashes_deep($_GET);

        $ranking_id = isset($GET['ranking_id'])
            ? intval($GET['ranking_id'])
            : 0 ;

        if (!$ranking_id || in_array($ranking_id, range(1, 6))) {
            return;
        }

        global $wpdb;

        $wpdb->query($wpdb->prepare("
            DELETE
            FROM
                {$wpdb->prefix}wpr_ranking
            WHERE
                `id` = %d
        ",
            $ranking_id
        ));

        delete_option('wpr_current_ranking');

        add_settings_error(
            'wpr-main-custom',
            'wpr-main-custom',
            __('Deleted the custom ranking', WP_Ranking_PRO::TEXT_DOMAIN),
            'updated'
        );

        # 独自管理画面のため
        set_transient('settings_errors', get_settings_errors(), 30);

        $base_url = admin_url('tools.php?page=wp-ranking-pro');
        $url = $base_url . "&path=main-custom&settings-updated=true";

        wp_redirect($url);
        exit;
    }

    private function util_omit($str, $max) {
        return mb_strlen($str) > $max
            ? mb_substr($str, 0, $max - 1) . '…'
            : $str ;
    }

    private function get_first_img_src_from_html($html) {
        if (empty($html)) {
            return null;
        }

        # ignroe warnings
        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML($html);

        if (!$doc) {
            return null;
        }

        $xpath = new DOMXpath($doc);

        $result = $xpath->query('//img[@src]')->item(0);

        if (!$result) {
            return null;
        }

        libxml_use_internal_errors(false);

        return $result->getAttribute('src');
    }

    private function _build_include_queries($child, $suffix, $post_type, $filter_type, $filter_by) {
        $statements  = array();
        $parameters  = array();
        $join_clause = array();
        $is_joined   = array();

        global $wpdb; # only for table names

        # 投稿タイプの指定
        if (!empty($post_type)) {
            $statements[] = "p$suffix.post_type = %s";
            $parameters[] = $post_type;
        }

        # 絞り込み
        if (!empty($filter_by)) {
            $filter_values = explode(',', $filter_by);

            switch ($filter_type) {
            case 'category':
                if (!isset($is_joined['term*'])) {
                    $join_clause[] = "JOIN `{$wpdb->term_relationships}` AS `t_r$suffix` ON p$suffix.ID = t_r$suffix.object_id";
                    $join_clause[] = "JOIN `{$wpdb->term_taxonomy}`      AS `t_t$suffix` ON t_r$suffix.term_taxonomy_id = t_t$suffix.term_taxonomy_id";
                    $join_clause[] = "JOIN `{$wpdb->terms}`              AS `t$suffix`   ON t_t$suffix.term_id = t$suffix.term_id";

                    $is_joined['term*'] = true;
                }

                $statements[] = "(t_t$suffix.taxonomy = 'category' AND t$suffix.name IN (" . implode(", ", array_fill(1, count($filter_values), "%s")) . "))";
                $parameters   = array_merge($parameters, $filter_values);
                break;

            case 'tag':
                if (!isset($is_joined['term*'])) {
                    $join_clause[] = "JOIN `{$wpdb->term_relationships}` AS `t_r$suffix` ON p$suffix.ID = t_r$suffix.object_id";
                    $join_clause[] = "JOIN `{$wpdb->term_taxonomy}`      AS `t_t$suffix` ON t_r$suffix.term_taxonomy_id = t_t$suffix.term_taxonomy_id";
                    $join_clause[] = "JOIN `{$wpdb->terms}`              AS `t$suffix`   ON t_t$suffix.term_id = t$suffix.term_id";

                    $is_joined['term*'] = true;
                }

                $statements[] = "(t_t$suffix.taxonomy = 'post_tag' AND t$suffix.name IN (" . implode(", ", array_fill(1, count($filter_values), "%s")) . "))";
                $parameters   = array_merge($parameters, $filter_values);
                break;

            case 'post_author':
                # 現状重複の可能性はないが、追加なども考慮して、JOIN済みかチェック。
                if (!isset($is_joined['users'])) {
                    $join_clause[] = "JOIN `{$wpdb->users}` AS `u` ON p$suffix.post_author = u.ID";

                    $is_joined['users'] = true;
                }

                $statements[] = 'u.user_login IN (' . implode(', ', array_fill(1, count($filter_values), '%s')) . ')';
                $parameters   = array_merge($parameters, $filter_values);
                break;

            default:
                wp_die("Unknown filter_type: $filter_type");
            }
        }

        $statements = implode(' AND ', $statements);

        if (!empty($statements)) {
            if (!empty($child)) {
                $statements = implode(' AND ', array($child, $statements));
            }

            $statements = "
EXISTS (
    SELECT
        *
    FROM
        `$wpdb->posts` AS `p$suffix`
            " . implode("\n", $join_clause) . "
    WHERE
        $statements
            AND
        p" . ($suffix + 1) . ".ID = p$suffix.ID
)
";
        }

        return array($statements, $parameters);
    }

    private function _build_exclude_queries($child, $suffix, $post_ids, $category_names, $tag_names) {
        $statements  = array();
        $parameters  = array();
        $join_clause = array();

        global $wpdb; # only for table names

        # 投稿の除外
        if (!empty($post_ids)) {
            $post_ids = explode(',', $post_ids);

            $statements[] = "p$suffix.ID IN (" . implode(', ', array_fill(1, count($post_ids), '%d')) . ')';
            $parameters   = array_merge($parameters, $post_ids);
        }

        # カテゴリの除外
        if (!empty($category_names)) {
            $category_names = explode(',', $category_names);

            if (empty($join_clause)) {
                    $join_clause[] = "JOIN `{$wpdb->term_relationships}` AS `t_r$suffix` ON p$suffix.ID = t_r$suffix.object_id";
                    $join_clause[] = "JOIN `{$wpdb->term_taxonomy}`      AS `t_t$suffix` ON t_r$suffix.term_taxonomy_id = t_t$suffix.term_taxonomy_id";
                    $join_clause[] = "JOIN `{$wpdb->terms}`              AS `t$suffix`   ON t_t$suffix.term_id = t$suffix.term_id";
            }

            $statements[] = "(t_t$suffix.taxonomy = 'category' AND t$suffix.name IN (" . implode(", ", array_fill(1, count($category_names), "%s")) . "))";
            $parameters   = array_merge($parameters, $category_names);
        }

        # タグの除外
        if (!empty($tag_names)) {
            $tag_names = explode(',', $tag_names);

            if (empty($join_clause)) {
                $join_clause[] = "JOIN `{$wpdb->term_relationships}` AS `t_r$suffix` ON p$suffix.ID = t_r$suffix.object_id";
                $join_clause[] = "JOIN `{$wpdb->term_taxonomy}`      AS `t_t$suffix` ON t_r$suffix.term_taxonomy_id = t_t$suffix.term_taxonomy_id";
                $join_clause[] = "JOIN `{$wpdb->terms}`              AS `t$suffix`   ON t_t$suffix.term_id = t$suffix.term_id";
            }

            $statements[] = "(t_t$suffix.taxonomy = 'post_tag' AND t$suffix.name IN (" . implode(", ", array_fill(1, count($tag_names), "%s")) . "))";
            $parameters   = array_merge($parameters, $tag_names);
        }

        # 一つでも条件に合うなら除外、なのでOR
        $statements = implode(' OR ', $statements);

        if (!empty($statements)) {
            $statements = "
NOT EXISTS (
    SELECT
        *
    FROM
        `$wpdb->posts` AS `p$suffix`
            " . implode("\n", $join_clause) . "
    WHERE
        ($statements)
            AND
        p" . ($suffix + 1) . ".ID = p$suffix.ID
)
";
        }

        return array($statements, $parameters);
    }

    private function _util_settings_to_hash(Array $settings) {
        array_multisort($settings);

        return sha1(json_encode($settings));
    }

    private function _util_get_cache($settings_hash) {
        return get_transient('wpr-' . $settings_hash);
    }

    private function _util_set_cache($settings_hash, $posts) {
        return set_transient('wpr-' . $settings_hash, json_encode($posts), get_option('wpr_ranking_cache_expire', 0));
    }
}
