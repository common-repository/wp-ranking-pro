<h3><?php _e('Basic description', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <thead>
    <tr>
      <th><?php _e('Default', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('Original', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
    </tr>
    <tr>
<td width="50%">
# <?php _e('Describe it before the header output', WP_Ranking_PRO::TEXT_DOMAIN) ?><br><br>
<code>
&lt;?php<br>
$query = "post_type=post&period=24h&limit=5";<br>
<br>
global $wp_ranking_pro;<br>
$ranking = $wp_ranking_pro->new_ranking($query);<br>
?&gt;
</code><br>
<br>
<br>
# <?php _e('Describe it on the part which I want to display', WP_Ranking_PRO::TEXT_DOMAIN) ?><br><br>
<code>
&lt;?php<br>
echo $ranking->output_ranking();<br>
?&gt;<br>
<br>
</code>
<br>
<?php _e('In this case default CSS comes to be applied', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
<?php _e('* I can display it even if I describe it in a mass in the point that wants to let you display it depending on a template', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
<br>
</td>

<td width="50%">
# <?php _e('Describe it before the header output', WP_Ranking_PRO::TEXT_DOMAIN) ?><br><br>
<code>
&lt;?php<br>
$query = "<strong>use_html_template=0</strong>&post_type=post&period=24h&limit=5";<br>
<br>
global $wp_ranking_pro;<br>
$ranking = $wp_ranking_pro->new_ranking($query);<br>
?&gt;
</code><br>
<br>
<br>
# <?php _e('Describe it on the part which I want to display', WP_Ranking_PRO::TEXT_DOMAIN) ?><br><br>
<code>
&lt;?php<br>
echo $ranking->output_ranking();<br>
?&gt;<br>
<br>
</code>
<br>
<?php _e('* I can use original CSS and HTML by using "use_html_template=0". In addition, I can control the item letting you display it', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
<?php _e('** Specifically, please refer to "the PHP cord description example" of "the help"', WP_Ranking_PRO::TEXT_DOMAIN) ?>
<br>
</td>

</tr>
</tbody>
</table>


<h3><?php _e('Various period designation', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <thead>
    <tr>
      <th><?php _e('Condition', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('Description example', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td width="25%"><?php _e('Display a ranking of 2015', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">$query = 'use_preset_period=0&begin_year=2015&begin_month=1&end_month=12&end_year=2015'</td>
    </tr>
    <tr>
      <td width="25%"><?php _e('Display a ranking of February, 2015', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">$query = 'use_preset_period=0&begin_year=2015&begin_month=2&begin_day=1&end_month=2&end_day=28&end_year=2015'</td>
    </tr>
    <tr>
      <td width="25%"><?php _e('Display a ranking of 2/15 - 3/15 in 2015', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">$query = 'use_preset_period=0&begin_year=2015&begin_month=2&begin_day=15&end_month=3&end_day=15&end_year=2015'</td>
    </tr>
  </tbody>
</table>

<h3><?php _e('Ranking according to the category', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <thead>
    <tr>
      <th><?php _e('Condition', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('Description example', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td width="25%"><?php _e('Display a specific category ranking in a fixed page', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">$query = 'filter_type=category&filter_by=<?php _e('Category name', WP_Ranking_PRO::TEXT_DOMAIN) ?>'</td>
    </tr>
    <tr>
      <td width="25%"><?php _e('Display a category judgment, a ranking in single.php', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">$categories = get_the_category();<br>$category = $categories[0]->name;<br>$query = 'filter_type=category&filter_by='.$category.'</td>
    </tr>
  </tbody>
</table>

<h3><?php _e('Designated / exclusion setting', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <thead>
    <tr>
      <th><?php _e('Condition', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('Description example', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td width="25%"><?php _e('Exclude plural posts from a ranking', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">$query = 'exclude_post_ids=1234,6789'<br><?php _e('(Exclude posts ID "1234" and "6789" from a ranking)', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
    </tr>
    <tr>
      <td width="25%"><?php _e('Exclude specific category / tag from a ranking', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">$query = 'exclude_category_names=cat1&exclude_tag_names=tag1'<br><?php _e('(Exclude a contribution including category name "cat1" and tag name "tag1" from a ranking)', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
    </tr>
    <tr>
      <td width="25%"><?php _e('Appoint the specific custom post', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">$query = 'post_type=custom1&period=24h'<br><?php _e('(Display a ranking for 24h of custom post name "custom1")', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
    </tr>
  </tbody>
</table>

<h3><?php _e('Indication control', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <thead>
    <tr>
      <th><?php _e('Condition', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
      <th><?php _e('Description example', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
    </tr>
  </thead>
  <tbody>
<?php
$parameters = array(
    __('"Only thumbnail / title / order indication" displays it by original HTML', WP_Ranking_PRO::TEXT_DOMAIN) => '$query = "use_html_template=0&post_type=post&period=24h&limit=10&pc_or_mobile=both&html_title_prefix=&html_title_suffix=&html_ranking_prefix=<ul class="wprp-list">&html_ranking_suffix=</ul>&html_ranking_content=<li><a href=[permalink] title="[title_for_attr]"><img src="[thumbnail]" alt="[title_for_attr]" border="0" width="60" height="60" class="wprp-thumbnail wpp_cached_thumb wpp_featured"></a><span class="wprp-num">[rank]</span><span class="wprp-post-title"><a href="[permalink]" title="[title_for_attr]">[title]</a></span></li>"',
    __('Display all it by original HTML',                                 WP_Ranking_PRO::TEXT_DOMAIN) => '$query = "use_html_template=0&post_type=post&period=24h&limit=10&pc_or_mobile=both&html_title_prefix=<h2 class="title">&html_title_suffix=</h2>&html_ranking_prefix=<ul class="wprp-list">&html_ranking_suffix=</ul>&html_ranking_content=<li><a href="[permalink]" title="[title_for_attr]"><img src="[thumbnail]" alt="POST TITLE" border="0" width="[thumbnail_x]" height="[thumbnail_y]" class="wprp-thumbnail wpp_cached_thumb wpp_featured"></a><span class="wprp-num">[rank]</span><span class="wprp-post-title"><a href="[permalink]" title="[title_for_attr]">[title]</a></span><span class="wprp-excerpt">[excerpt]</span><div class="wprp-post-stats"><span class="wprp-category">category: [category]</span> | <span class="wprp-comments"><a href="[permalink]#comments">[comment_count] comments</a></span> |<span class="wprp-views">[views] views</span> | <span class="wprp-author">by: <a href="[author_link]">[author]</a></span> | <span class="wprp-date">[date]</span> </div></li>"',

);

foreach ($parameters as $name => $description) {
?>
    <tr>
      <td width="25%"><?php echo esc_html($name) ?></td>
      <td width="75%"><?php echo esc_html($description) ?></td>
    </tr>
<?php
}
?>
  </tbody>
</table>

