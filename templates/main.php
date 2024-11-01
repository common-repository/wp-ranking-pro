<?php
$GET = stripslashes_deep($_GET);
$SERVER = stripslashes_deep($_SERVER);
?>
<div class="wrap">
<?php self::template_menu('main') ?>
  <form action="<?php self::util_path_to($base_url) ?>" method="get">
    <div>
      <?php settings_errors() ?>
    </div>
    <div>
<?php
foreach ($GET as $key => $value) {
    if ($key == 'limit') {
        continue;
    }
?>
      <input type="hidden" name="<?php echo esc_attr($key) ?>" value="<?php echo esc_attr($value) ?>">
<?php
}
?>
    </div>
    <div>
      <label for="entries_per_page"><?php _e('Number of Display', WP_Ranking_PRO::TEXT_DOMAIN) ?></label>
      <input  id="entries_per_page" name="limit" type="text" value="<?php echo esc_attr(isset($GET['limit']) ? $GET['limit'] : 10) ?>" class="small-text">
      <input type="submit" value="<?php _e('Save', WP_Ranking_PRO::TEXT_DOMAIN) ?>" class="button">
    </div>
  </form>
  <div class="wpr-tabs-and-contents">
    <ul class="wpr-tabs">
<?php
foreach (array('24H', '1week', '1month', '1year', 'ALL') as $name) {
    $current = strtolower($name) == $route[1]
        ? ' class="wpr-current"'
        : '' ;
?>
      <li><a href="<?php self::util_path_to($base_url, array('path' => 'main-' . strtolower($name))) ?>"<?php echo $current ?>><?php echo $name ?></a></li>
<?php
}
?>
      <li><a href="<?php self::util_path_to($base_url, array('path' => 'main-period')) ?>"<?php echo $route[1] == 'period' ? ' class="wpr-current"' : '' ?>><?php _e('Period designation', WP_Ranking_PRO::TEXT_DOMAIN) ?></a></li>
      <li><a href="<?php self::util_path_to($base_url, array('path' => 'main-custom')) ?>"<?php echo $route[1] == 'custom' ? ' class="wpr-current"' : '' ?>><?php _e('Custom', WP_Ranking_PRO::TEXT_DOMAIN) ?></a></li>
    </ul>
    <div class="wpr-contents">
<?php
if ($route[1] == 'custom') {
    include(dirname(WP_Ranking_PRO::file_path()) . '/templates/main-custom.php');
}
else {
    include(dirname(WP_Ranking_PRO::file_path()) . '/templates/main-others.php');
}
?>
      <div class="wpr-button-like">
        <a href="" id="wpr-add-to-dashboard" data-wpr-id="<?php echo $ranking->id ?>" data-wpr-already-added="<?php echo $already_added_dashboard ? 1 : 0 ?>"><?php _e('Add these statistics to a dashboard', WP_Ranking_PRO::TEXT_DOMAIN) ?></a>
      </div>
      <div style="clear: both;"></div>
    </div>
  </div>
</div>
