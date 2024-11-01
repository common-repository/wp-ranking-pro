  <thead>
    <tr>
      <th><?php _e('Title',  WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('PC',     WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('mobile', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('TOTAL',  WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
    </tr>
  </thead>
  <tbody>
<?php
foreach ($posts as $post) {
?>
    <tr>
      <td class="for-dashboard wpr-title"><a href="<?php echo esc_url(get_permalink($post->ID)) ?>"><?php echo $post->post_title ?></a></td>
      <td class="for-dashboard wpr-views"><?php echo number_format($post->views_pc) ?> <?php _e('PV', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td class="for-dashboard wpr-views"><?php echo number_format($post->views_mobile) ?> <?php _e('PV', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td class="for-dashboard wpr-views"><?php echo number_format($post->views_both) ?> <?php _e('PV', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
    </tr>
<?php
        }
?>
  </tbody>
