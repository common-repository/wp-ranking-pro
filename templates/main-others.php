<?php
$GET = stripslashes_deep($_GET);
?>
      <form action="<?php self::util_path_to($base_url) ?>" method="get">
        <div>
<?php
# <form />内に存在するクエリ以外を継続する
foreach ($GET as $key => $value) {
    if (in_array($key, array(
        'post_type_name',
        'begin_year',
        'begin_month',
        'begin_day',
        'end_year',
        'end_month',
        'end_day',
    ))) {
        continue;
    }
?>
          <input type="hidden" name="<?php echo esc_attr($key) ?>" value="<?php echo esc_attr($value) ?>">
<?php
}
?>
        </div>
        <div>
          <label for="post_type"><?php _e('Post type choice', WP_Ranking_PRO::TEXT_DOMAIN) ?></label>
          <select id="post_type" name="post_type_name" class="auto-submit">
            <option value=""<?php selected('', (is_null($ranking->settings) ? null : $ranking->settings->post_type)) ?>><?php _e('All', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
<?php
foreach (get_post_types(array('public' => true), 'objects') as $post_type) {
?>
            <option value="<?php echo $post_type->name ?>"<?php selected($post_type->name, (is_null($ranking->settings) ? null : $ranking->settings->post_type)) ?>><?php echo $post_type->label ?></option>
<?php
}
?>
          </select>
        </div>
<?php
if ($route[1] == 'period') {
?>
        <div class="wpr-period-controls">
          <label><?php _e('Period designation', WP_Ranking_PRO::TEXT_DOMAIN) ?></label>
          <select name="begin_year">
<?php
    foreach (range(2015, date_i18n('Y')) as $year) {
?>
            <option value="<?php echo $year ?>"<?php selected($year, (is_null($ranking->settings) ? null : $ranking->settings->begin_year)) ?>><?php echo $year ?></option>
<?php
    }
?>
          </select>&nbsp;
          <select name="begin_month">
<?php
    foreach (range(1, 12) as $month) {
?>
            <option value="<?php echo $month ?>"<?php selected($month, (is_null($ranking->settings) ? null : $ranking->settings->begin_month)) ?>><?php echo $month ?></option>
<?php
    }
?>
          </select>&nbsp;
          <select name="begin_day">
<?php
    foreach (range(1, 31) as $day) {
?>
            <option value="<?php echo $day ?>"<?php selected($day, (is_null($ranking->settings) ? null : $ranking->settings->begin_day)) ?>><?php echo $day ?></option>
<?php
      }
?>
          </select>
          ～
          <select name="end_year">
<?php
    foreach (range(2015, date_i18n('Y')) as $year) {
?>
            <option value="<?php echo $year ?>"<?php selected($year, (is_null($ranking->settings) ? null : $ranking->settings->end_year)) ?>><?php echo $year ?></option>
<?php
    }
?>
          </select>&nbsp;
          <select name="end_month">
<?php
    foreach (range(1, 12) as $month) {
?>
            <option value="<?php echo $month ?>"<?php selected($month, (is_null($ranking->settings) ? null : $ranking->settings->end_month)) ?>><?php echo $month ?></option>
<?php
    }
?>
          </select>&nbsp;
          <select name="end_day">
<?php
    foreach (range(1, 31) as $day) {
?>
            <option value="<?php echo $day ?>"<?php selected($day, (is_null($ranking->settings) ? null : $ranking->settings->end_day)) ?>><?php echo $day ?></option>
<?php
    }
?>
          </select>
          <input type="submit" value="<?php _e('Save', WP_Ranking_PRO::TEXT_DOMAIN) ?>" class="button">
        </div>
<?php
}
?>
      </form>
      <table class="wpr-ranking">
        <tr>
          <th>Title</th>
          <th>
            <?php _e('PC', WP_Ranking_PRO::TEXT_DOMAIN) ?>
<?php
if (preg_match('/\Aviews_pc\./', $order)) {
    if (preg_match('/\.desc\z/', $order)) {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views_pc.asc')) ?>"><?php _e('▼', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
<?php
    } else {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views_pc.desc')) ?>"><?php _e('▲', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
<?php
    }
} else {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views_pc.desc')) ?>"><?php _e('▽', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
<?php
}
?>
          </th>
          <th>
            <?php _e('mobile', WP_Ranking_PRO::TEXT_DOMAIN) ?>
<?php
if (preg_match('/\Aviews_mobile\./', $order)) {
    if (preg_match('/\.desc\z/', $order)) {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views_mobile.asc')) ?>"><?php _e('▼', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
<?php
    } else {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views_mobile.desc')) ?>"><?php _e('▲', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
<?php
    }
} else {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views_mobile.desc')) ?>"><?php _e('▽', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
<?php
}
?>
          </th>
          <th>
            <?php _e('TOTAL', WP_Ranking_PRO::TEXT_DOMAIN) ?>
<?php
if (preg_match('/\Aviews(_both)?\./', $order)) {
    if (preg_match('/\.desc\z/', $order)) {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views_both.asc')) ?>"><?php _e('▼', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
<?php
    } else {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views_both.desc')) ?>"><?php _e('▲', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
<?php
    }
} else {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views_both.desc')) ?>"><?php _e('▽', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
<?php
}
?>
          </th>
          <th colspan="3"><?php _e('Operation', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
        </tr>
<?php
foreach ($posts as $post) {
?>
        <tr>
          <td class="wpr-title"><a href="<?php echo esc_url(get_permalink($post->ID)) ?>"><?php echo $post->post_title ?></a></td>
          <td class="wpr-views"><?php echo number_format($post->views_pc) ?> <?php _e('PV', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
          <td class="wpr-views"><?php echo number_format($post->views_mobile) ?> <?php _e('PV', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
          <td class="wpr-views"><?php echo number_format($post->views_both) ?> <?php _e('PV', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
          <td class="wpr-action"><a href="<?php echo esc_url(get_permalink($post->ID)) ?>"><?php _e('Indication', WP_Ranking_PRO::TEXT_DOMAIN) ?></a></td>
          <td class="wpr-action"><a href="<?php self::util_path_to(admin_url('post.php'), array('post' => $post->ID, 'action' => 'edit')) ?>"><?php _e('Edit', WP_Ranking_PRO::TEXT_DOMAIN) ?></a></td>
          <td class="wpr-action"><a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('action' => 'reset', 'post_id' => $post->ID)) ?>" class="wpr-reset require-confirmation" data-wpr-message="<?php _e('Do you really reset it?', WP_Ranking_PRO::TEXT_DOMAIN) ?>"><?php _e('Reset', WP_Ranking_PRO::TEXT_DOMAIN) ?></a></td>
        </tr>
<?php
}
?>
      </table>
