<div class="wrap">
<?php self::template_menu('help') ?>
  <div class="wpr-tabs-and-contents">
    <ul class="wpr-tabs wpr-tabs-wide">
<?php
foreach (array('parameters', 'samples', 'faq') as $name) {
    $current = strtolower($name) == $route[1]
        ? ' class="wpr-current"'
        : '' ;
?>
      <li><a href="<?php self::util_path_to(admin_url('tools.php?page=wp-ranking-pro'), array('path' => 'help-' . strtolower($name))) ?>"<?php echo $current ?>><?php echo $names[$name] ?></a></li>
<?php
}
?>
    </ul>
    <div class="wpr-contents">
<?php
//if ($route[1] == 'faq') {
//    $locale = get_locale();
//
//    $path = dirname($this->file_path()) . "/templates/help-$route[1]-$locale.php";
//}
//else {
    $path = dirname($this->file_path()) . "/templates/help-$route[1].php";
//}

include($path);
?>
      <div style="clear: both;"></div>
    </div>
  </div>
</div>
