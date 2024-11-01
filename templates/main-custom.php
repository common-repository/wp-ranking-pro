<?php
$GET = stripslashes_deep($_GET);
?>
      <form action="<?php self::util_path_to($base_url) ?>" method="get">
        <div>
<?php
# <form />内に存在するクエリ以外を継続する
foreach ($GET as $key => $value) {
    if (in_array($key, array(
        'ranking_id',
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
          <label for="custom-ranking"><?php _e('Custom ranking choice', WP_Ranking_PRO::TEXT_DOMAIN) ?></label>
          <select id="custom-ranking" name="ranking_id" class="auto-submit">
<?php
    foreach ($custom_rankings as $custom_ranking) {
?>
            <option value="<?php echo $custom_ranking->id ?>"<?php selected($custom_ranking->id, $ranking->id) ?>><?php echo $custom_ranking->name ?></option>
<?php
    }
?>
          </select>
        </div>
      </form>
      <div class="submit">
        <?php _e('This ranking..', WP_Ranking_PRO::TEXT_DOMAIN) ?>
        <a href="<?php self::util_path_to($base_url, array('path' => 'custom-ranking', 'ranking_id' => $ranking->id)) ?>" class="button"><?php _e('Edit', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
        <a href="<?php self::util_path_to($base_url, array('path' => 'main-custom', 'ranking_id' => $ranking->id, 'action' => 'delete')) ?>" class="button"><?php _e('Delete', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
      </div>
      <table class="wpr-ranking">
        <tr>
          <th>Title</th>
          <th>
            <?php echo $ranking->type_name() ?>
<?php
if (preg_match('/\.desc\z/', $order)) {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views.asc')) ?>"><?php _e('▼', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
<?php
} else {
?>
            <a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('order' => 'views.desc')) ?>"><?php _e('▲', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
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
<?php
if ($ranking->settings->order_by == 'comment_count') {
?>
          <td class="wpr-views"><?php echo number_format($post->views) ?></td>
<?php
}
else {
?>
          <td class="wpr-views"><?php echo number_format($post->views) ?> <?php _e('PV', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
<?php
}
?>
          <td class="wpr-action"><a href="<?php echo esc_url(get_permalink($post->ID)) ?>"><?php _e('Indication', WP_Ranking_PRO::TEXT_DOMAIN) ?></a></td>
          <td class="wpr-action"><a href="<?php self::util_path_to(admin_url('post.php'), array('post' => $post->ID, 'action' => 'edit')) ?>"><?php _e('Edit', WP_Ranking_PRO::TEXT_DOMAIN) ?></a></td>
          <td class="wpr-action"><a href="<?php self::util_path_to($SERVER['REQUEST_URI'], array('action' => 'reset', 'post_id' => $post->ID)) ?>" class="wpr-reset require-confirmation" data-wpr-message="<?php _e('Do you really reset it?', WP_Ranking_PRO::TEXT_DOMAIN) ?>"><?php _e('Reset', WP_Ranking_PRO::TEXT_DOMAIN) ?></a></td>
        </tr>
<?php
}
?>
      </table>
