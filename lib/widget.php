<?php

require_once(dirname(__FILE__) . '/model/ranking.php');

class WP_Ranking_PRO_Widget extends WP_Widget {
    private $rankings;

    public function __construct() {
        # widgets_initの優先度100の時点でウィジェットが生成されるため、それ以
        # 後、ヘッダ出力までの間にフックする。
        # 十分に追えていないが、0～100でも出力できる。ただし、なぜかデフォルト相
        # 当の10でのみ出力されない。
        # 元は安全を考慮して1000にしていたが、101～1000間でヘッダ出力をするよう
        # なプラグイン・テーマの存在のせいで失敗する可能性があったので、ぎりぎり
        # の101に変更。
        add_action('widgets_init', array($this, '_append_stylesheet'), 101);

        global $wpdb;

        $this->rankings = $wpdb->get_results("SELECT * FROM `{$wpdb->prefix}wpr_ranking` ORDER BY `id` ASC");

        parent::__construct(
            'wpr_widget',
            __('WP-Ranking PRO widget', WP_Ranking_PRO::TEXT_DOMAIN),
            array(
                'description' => __('Show WP-Ranking PRO widget', WP_Ranking_PRO::TEXT_DOMAIN),
            )
        );
    }

    public function widget($args, $instance) {
        $title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance );
        $ranking = new WP_Ranking_PRO_Model_Ranking($instance['ranking_id']);

        echo $args['before_widget'];

        if ( !empty( $title ) ) { echo $args['before_title'] . $title . $args['after_title']; }

?>
<div class="wprwidget">
<?php
        echo $ranking->output_ranking();
?>
</div>
<?php
        echo $args['after_widget'];
    }

    public function form($instance) {
        $instance = wp_parse_args( (array) $instance, array( 'title' => '', 'text' => '' ) );
        $title = strip_tags($instance['title']);
?>
<p>
  <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', WP_Ranking_PRO::TEXT_DOMAIN); ?></label>
  <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
</p>
<p>
  <label for="<?php echo $this->get_field_id('ranking_id') ?>"><?php _e('Choice of the ranking', WP_Ranking_PRO::TEXT_DOMAIN) ?></label>
  <select id="<?php echo $this->get_field_id('ranking_id') ?>" name="<?php echo $this->get_field_name('ranking_id') ?>">
<?php
        foreach ($this->rankings as $ranking) {
?>
    <option value="<?php echo $ranking->id ?>"<?php selected($ranking->id, $instance['ranking_id']) ?>><?php echo $ranking->name ?></option>
<?php
        }
?>
  </select>
</p>
<?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();

        if (empty($new_instance['ranking_id'])) {
            return $instance;
        }

        $instance['title'] = strip_tags($new_instance['title']);
        $ranking = new WP_Ranking_PRO_Model_Ranking($new_instance['ranking_id']);

        if ($ranking->id) {
            $instance['ranking_id'] = $ranking->id;
        }

        return $instance;
    }

    public function _append_stylesheet() {
        if (!is_active_widget(false, false, $this->id_base, true)) {
            return;
        }

        $instance = $this->get_settings();
        $instance = $instance[ $this->number ];

        $ranking = new WP_Ranking_PRO_Model_Ranking($instance['ranking_id']);

        if (!($ranking->id && $ranking->settings->use_html_template)) {
            return;
        }

        wp_enqueue_style(
            'WPR_Stylesheet' . $ranking->settings->html_template,
            plugins_url('css/ranking-' . $ranking->settings->html_template . '.css', WP_Ranking_PRO::file_path()),
            array(), # keep default
			WP_Ranking_PRO::$this_version
        );
    }
}
