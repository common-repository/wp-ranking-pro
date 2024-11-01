  <thead>
    <tr>
      <th><?php _e('Title', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php echo $current_ranking->type_name() ?></th>
    </tr>
  </thead>
  <tbody>
<?php
foreach ($posts as $post) {
?>
    <tr>
      <td class="for-dashboard wpr-title"><a href="<?php echo esc_url(get_permalink($post->ID)) ?>"><?php echo $post->post_title ?></a></td>
<?php
    if ($settings->order_by == 'comment_count') {
?>
      <td class="for-dashboard wpr-views"><?php echo number_format($post->views) ?></td>
<?php
    } else {
?>
      <td class="for-dashboard wpr-views"><?php echo number_format($post->views) ?> <?php _e('PV', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
<?php
    }
?>
    </tr>
<?php
        }
?>
  </tbody>
