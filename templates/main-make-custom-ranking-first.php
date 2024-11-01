<?php
$base_url = admin_url('tools.php?page=wp-ranking-pro');
?>
<div class="wrap">
<?php self::template_menu('main') ?>
  <p class="notice">
    <?php _e('A custom ranking is non-making', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    <a href="<?php self::util_path_to($base_url, array('path' => 'custom-ranking')) ?>"><?php _e('Make', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
  </p>
</div>
