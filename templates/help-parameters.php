<h3><?php _e('Parameter for the original HTML', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <thead>
    <tr>
      <th><?php _e('Parameter',   WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('Explanation', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
    </tr>
  </thead>
  <tbody>
<?php
$parameters = array(
    'permalink'      => __('It is the permanent link of the article. URL escape finished',          WP_Ranking_PRO::TEXT_DOMAIN),
    'title'          => __('Title of the article',                                                  WP_Ranking_PRO::TEXT_DOMAIN),
    'title_for_attr' => __('Title of the article. It has been escaped for an HTML attribute price', WP_Ranking_PRO::TEXT_DOMAIN),
    'thumbnail'      => __('It is the URL of the thumbnail image. URL escape finished',             WP_Ranking_PRO::TEXT_DOMAIN),
    'thumbnail_x'    => __('Width of the thumbnail image',                                          WP_Ranking_PRO::TEXT_DOMAIN),
    'thumbnail_y'    => __('Vertical width of the thumbnail image',                                 WP_Ranking_PRO::TEXT_DOMAIN),
    'rank'           => __('Order',                                                                 WP_Ranking_PRO::TEXT_DOMAIN),
    'date'           => __('Date of the article',                                                   WP_Ranking_PRO::TEXT_DOMAIN),
    'excerpt'        => __('Extract of the article',                                                WP_Ranking_PRO::TEXT_DOMAIN),
    'category'       => __('A category. Output by HTML',                                            WP_Ranking_PRO::TEXT_DOMAIN),
    'comment_count'  => __('The number of the comment',                                             WP_Ranking_PRO::TEXT_DOMAIN),
    'views'          => __('Page View',                                                             WP_Ranking_PRO::TEXT_DOMAIN),
    'author'         => __('Author of the article',                                                 WP_Ranking_PRO::TEXT_DOMAIN),
    'author_link'    => __('Permanent link of the creator of the article. URL escape finished',     WP_Ranking_PRO::TEXT_DOMAIN),
    'meta'           => __('Value of the custom field',                                             WP_Ranking_PRO::TEXT_DOMAIN),
);

foreach ($parameters as $name => $description) {
?>
    <tr>
      <td><?php echo esc_html($name) ?></td>
      <td><?php echo esc_html($description) ?></td>
    </tr>
<?php
}
?>
  </tbody>
</table>

<h3><?php _e('Parameter for the PHP cord', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <thead>
    <tr>
      <th><?php _e('Parameter',           WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('Explanation',         WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('Default value',       WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('Pattern',             WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('Description example', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
    </tr>
  </thead>
  <tbody>
<?php
$parameters = array(
    'period'                 => array('',         __('Appoint a count period',                                                                                      WP_Ranking_PRO::TEXT_DOMAIN), '24h,1week,1month,year,all', '<?php $query = "period=24h"; ?>'),
    'post_type'              => array('',         __('Appoint a post type',                                                                                         WP_Ranking_PRO::TEXT_DOMAIN), 'post,page,attachment',      '<?php $query = "post_type=post"; ?>'),
    'order_by'               => array('views',    __('Appoint PV ranking or a comment ranking',                                                                     WP_Ranking_PRO::TEXT_DOMAIN), 'views,comment_count',       '<?php $query = "order_by=views"; ?>'),
    'filter_type'            => array('category', __('Appoint it which of a category tag, the creator you filter it by',                                            WP_Ranking_PRO::TEXT_DOMAIN), 'category,tag,post_author',  '<?php $query = "filter_type=category"; ?>'),
    'filter_by'              => array('',         __('Appoint the name of a category tag, the creator to filter',                                                   WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "filter_type=category&filter_by=' . __('Category name', WP_Ranking_PRO::TEXT_DOMAIN) . '"; ?>'),
    'use_preset_period'      => array('1',        __('When I use the ranking of the established period, I appoint 1. In the case of manual operation, I appoint 0', WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "use_preset_period=0"; ?>'),
    'begin_year'             => array('2015',     __('The start year when I appoint a period by manual operation',                                                  WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "use_preset_period=0&begin_year=2015"; ?>'),
    'begin_month'            => array('1',        __('It is a start month when I appoint a period by manual operation',                                             WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "use_preset_period=0&begin_month=2"; ?>'),
    'begin_day'              => array('1',        __('The starting date when I appoint a period by manual operation',                                               WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "use_preset_period=0&begin_day=1"; ?>'),
    'end_year'               => array('2015',     __('The end year when I appoint a period by manual operation',                                                    WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "use_preset_period=0&end_year=2014"; ?>'),
    'end_month'              => array('1',        __('End month when I appoint a period by manual operation',                                                       WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "use_preset_period=0&end_month=2"; ?>'),
    'end_day'                => array('1',        __('End day when I appoint a period by manual operation',                                                         WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "use_preset_period=0&end_day=30"; ?>'),
    'pc_or_mobile'           => array('both',     __('Appoint a count device',                                                                                      WP_Ranking_PRO::TEXT_DOMAIN), 'pc,mobile,both',            '<?php $query = "pc_or_mobile=pc"; ?>'),
    'limit'                  => array('10',       __('Appoint the number of indication',                                                                            WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "limit=10"; ?>'),
    'max_post_title'         => array('100',      __('Appoint the most big design number of characters of the article title',                                       WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "max_post_title=25"; ?>'),
    'max_excerpt'            => array('100',      __('Appoint the most big design number of characters of the article extract',                                     WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "max_excerpt=25"; ?>'),
    'date_format'            => array('',         __('Appoint the format of the date',                                                                              WP_Ranking_PRO::TEXT_DOMAIN), 'Y/m/d,Y/n/j',               '<?php $query = "date_format=Y/m/d"; ?>'),
    'thumbnail_y'            => array('60',       __('Appoint the vertical size of the thumbnail',                                                                  WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "thumbnail_y=100"; ?>'),
    'thumbnail_x'            => array('60',       __('Appoint the wide size of the thumbnail',                                                                      WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "thumbnail_x=100"; ?>'),
    'order_number_format'    => array('1',        __('Appoint a type of the order indication',                                                                      WP_Ranking_PRO::TEXT_DOMAIN), '1,2',                       '<?php $query = "order_number_format=1"; ?>'),
    'show_all_categories'    => array('1',        __('Appoint it whether you display all the categories',                                                           WP_Ranking_PRO::TEXT_DOMAIN), '0,1',                       '<?php $query = "show_all_categories=0"; ?>'),
    'show_all_tags'          => array('1',        __('Appoint it whether you display all the tags',                                                                 WP_Ranking_PRO::TEXT_DOMAIN), '0,1',                       '<?php $query = "show_all_tags=0"; ?>'),
    'post_meta_key'          => array('',         __('Appoint the name of the custom field to display',                                                             WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "post_meta_key=' . __('Custom field name', WP_Ranking_PRO::TEXT_DOMAIN) . '"; ?>'),
    'use_html_template'      => array('1',        __('Appoint it whether you use the HTML of the default',                                                          WP_Ranking_PRO::TEXT_DOMAIN), '0,1',                       '<?php $query = "use_html_template=0"; ?>'),
    'html_template'          => array('1',        __('When I use the HTML of the default, I appoint it which pattern you use',                                      WP_Ranking_PRO::TEXT_DOMAIN), '1,2',                       '<?php $query = "html_template=1"; ?>'),
    'html_title_prefix'      => array('',         __('Appoint HTML to attach before a ranking title',                                                               WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "html_title_prefix=<h2>"; ?>'),
    'html_title_suffix'      => array('',         __('Appoint HTML to attach behind a ranking title',                                                               WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "html_title_suffix=</h2>"; ?>'),
    'html_ranking_prefix'    => array('',         __('Appoint HTML to attach before the ranking body',                                                              WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "html_ranking_prefix=<ul>"; ?>'),
    'html_ranking_suffix'    => array('',         __('Appoint HTML to attach behind the ranking body',                                                              WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "html_ranking_suffix=</ul>"; ?>'),
    'html_ranking_content'   => array('',         __('Appoint the HTML of the ranking body',                                                                        WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "html_ranking_content=<li><a href=[permalink] title=\"[title_for_attr]\"></li>"; ?>'),
    'exclude_post_ids'       => array('',         __('Appoint Post ID to exclude',                                                                                  WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "exclude_post_ids=' . __('Post ID', WP_Ranking_PRO::TEXT_DOMAIN) . '"; ?>'),
    'exclude_category_names' => array('',         __('Appoint a category name to exclude',                                                                          WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "exclude_category_names=' . __('Category name', WP_Ranking_PRO::TEXT_DOMAIN) . '"; ?>'),
    'exclude_tag_names'      => array('',         __('Appoint a Tag name to exclude',                                                                               WP_Ranking_PRO::TEXT_DOMAIN), '',                          '<?php $query = "exclude_tag_names=' . __('Tag name', WP_Ranking_PRO::TEXT_DOMAIN) . '"; ?>'),
);

foreach ($parameters as $name => $values) {
?>
    <tr>
      <td><?php echo esc_html($name) ?></td>
      <td><?php echo esc_html($values[1]) ?></td>
      <td><?php echo esc_html($values[0]) ?></td>
      <td><?php echo esc_html($values[2]) ?></td>
      <td><?php echo esc_html($values[3]) ?></td>
    </tr>
<?php
}
?>
  </tbody>
</table>
