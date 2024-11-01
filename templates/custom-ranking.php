<div class="wrap" id="acMenu">
<?php self::template_menu('custom') ?>
  <div class="wpr-form-and-preview">
    <h2 class="wordpress-bug"></h2>
    <?php (isset($for_get_html) && $for_get_html) ? '' : settings_errors() ?>
    <div class="wpr-form">
      <form action="<?php self::util_path_to($base_url, array('path' => 'custom-ranking', 'settings-updated' => 'true')) ?>" method="post" enctype="multipart/form-data">
        <div>
          <input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce('wpr-custom-ranking') ?>">
          <input type="hidden" name="id" value="">
        </div>
        <dt><?php _e('Title setting', WP_Ranking_PRO::TEXT_DOMAIN) ?></dt>
        <dd class="none">
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><?php _e('Ranking name', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td><input type="text" name="name" value="">　<span><?php _e('(it is a management name and is plain let\'s name it)', WP_Ranking_PRO::TEXT_DOMAIN) ?></span></td>
          </tr>
          <tr valign="top" class="nb">
            <th scope="row"><?php _e('Ranking title', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td><input type="text" name="title" value="">　<span><?php _e('(A title displayed on WEB. Do not mind it in the blanks either)', WP_Ranking_PRO::TEXT_DOMAIN) ?></span></td>
          </tr>
        </table>
        <dt><?php _e('Basic setting', WP_Ranking_PRO::TEXT_DOMAIN) ?></dt>
        <dd>
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><?php _e('Post type', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <select name="post_type">
                <option value=""<?php selected('', (!isset($post_type_name) ? null : $post_type_name)) ?>><?php _e('All', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
<?php
foreach (get_post_types(array('public' => true), 'objects') as $post_type) {
?>
                <option value="<?php echo $post_type->name ?>"<?php selected($post_type->name, (!isset($post_type_name) ? null : $post_type_name)) ?>><?php echo $post_type->label ?></option>
<?php
}
?>
              </select>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Count data', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <select name="order_by" id="wpr-ranking-mode">
                <option value="views"><?php _e('Page view', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
                <option value="comment_count"><?php _e('Number of the comment', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
              </select>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Narrowing', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <select name="filter_type">
                <option value="category"><?php _e('Category', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
                <option value="tag"><?php _e('Tag', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
                <option value="post_author"><?php _e('Author', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
              </select>
              <br>
              <input type="text" name="filter_by" value="">
              <br>
              <?php _e('(when I appoint the name, and there is multiple it, it is Comma Separated Value)', WP_Ranking_PRO::TEXT_DOMAIN) ?>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Count period', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td class="wpr-disable-if-comment-count-mode">
              <input type="radio" name="use_preset_period" value="1" checked>&nbsp;
              <select name="period">
<?php
foreach (array('24H', '1week', '1month', '1year', 'ALL') as $period) {
?>
                <option value="<?php echo strtolower($period) ?>"><?php echo $period ?></option>
<?php
}
?>
              </select>
              <br>
              <input type="radio" name="use_preset_period" value="0">
              <br>
              <select name="begin_year">
<?php
foreach (range(2015, date_i18n('Y')) as $year) {
    $this_year = date_i18n('Y');
?>
                <option value="<?php echo $year ?>"<?php selected($year, $this_year) ?>><?php echo $year ?></option>
<?php
}
?>
              </select>&nbsp;
              <select name="begin_month">
<?php
foreach (range(1, 12) as $month) {
    $this_month = date_i18n('n');
?>
                <option value="<?php echo $month ?>"<?php selected($month, $this_month) ?>><?php echo $month ?></option>
<?php
}
?>
              </select>&nbsp;
              <select name="begin_day">
<?php
foreach (range(1, 31) as $day) {
?>
                <option value="<?php echo $day ?>"<?php selected($day, 1) ?>><?php echo $day ?></option>
<?php
}
?>
              </select>
              ～
              <br>
              <select name="end_year">
<?php
foreach (range(2015, date_i18n('Y')) as $year) {
    $this_year = date_i18n('Y');
?>
                <option value="<?php echo $year ?>"<?php selected($year, $this_year) ?>><?php echo $year ?></option>
<?php
}
?>
              </select>&nbsp;
              <select name="end_month">
<?php
foreach (range(1, 12) as $month) {
    $this_month = date_i18n('n');
?>
                <option value="<?php echo $month ?>"<?php selected($month, $this_month) ?>><?php echo $month ?></option>
<?php
}
?>
              </select>&nbsp;
              <select name="end_day">
<?php
foreach (range(1, 31) as $day) {
    $today = date_i18n('j');
?>
                <option value="<?php echo $day ?>"<?php selected($day, $today) ?>><?php echo $day ?></option>
<?php
}
?>
              </select>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Count Device', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td class="wpr-disable-if-comment-count-mode">
              <select name="pc_or_mobile">
                <option value="pc"><?php _e('PC', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
                <option value="mobile"><?php _e('Mobile', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
                <option value="both"><?php _e('TOTAL', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
              </select>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Number of Display', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td><input type="text" name="limit" value="10" class="small-text"> <?php _e('entries', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
          </tr>
        </table>
        </dd>
        <dt><?php _e('Display setting', WP_Ranking_PRO::TEXT_DOMAIN) ?></dt>
        <dd>
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><?php _e('The number of the letters of the article title', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td><?php _e('Max', WP_Ranking_PRO::TEXT_DOMAIN) ?> <input type="text" name="max_post_title" value="100" class="small-text"> <?php _e('Text', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('The number of the letters of the extract', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td><?php _e('Max', WP_Ranking_PRO::TEXT_DOMAIN) ?> <input type="text" name="max_excerpt" value="100" class="small-text"> <?php _e('Text', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Format of the date', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <select name="date_format">
                <option value=""><?php _e('I use the setting of WordPress', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
                <option value="Y/m/d"><?php echo date_i18n('Y/m/d') ?></option>
                <option value="Y/n/j"><?php echo date_i18n('Y/n/j') ?> <?php _e('(there is no first zero)', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
              </select>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Size of the thumbnail', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <?php _e('Height', WP_Ranking_PRO::TEXT_DOMAIN) ?> <input type="text" name="thumbnail_y" value="60" class="small-text"> px
              <?php _e('×', WP_Ranking_PRO::TEXT_DOMAIN) ?>
              <?php _e('Width', WP_Ranking_PRO::TEXT_DOMAIN) ?> <input type="text" name="thumbnail_x" value="60" class="small-text"> px
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Category', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <?php _e('In the case of plural', WP_Ranking_PRO::TEXT_DOMAIN) ?>
              <select name="show_all_categories">
                <option value="0"><?php _e('Only one displays it', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
                <option value="1"><?php _e('All indication', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
              </select>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Tag', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <?php _e('In the case of plural', WP_Ranking_PRO::TEXT_DOMAIN) ?>
              <select name="show_all_tags">
                <option value="0"><?php _e('Only one displays it', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
                <option value="1"><?php _e('All indication', WP_Ranking_PRO::TEXT_DOMAIN) ?></option>
              </select>
            </td>
          </tr>
          <tr valign="top" class="nb">
            <th scope="row"><?php _e('Custom Field', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <input type="text" name="post_meta_key" value="">
            </td>
          </tr>
        </table>
        </dd>
        <dt><?php _e('HTML setting', WP_Ranking_PRO::TEXT_DOMAIN) ?></dt>
        <dd>
        <table class="form-table">
          <tr valign="top" class="nb">
            <th scope="row"><?php _e('HTML', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <label>
                <?php _e('Default', WP_Ranking_PRO::TEXT_DOMAIN) ?>&nbsp;&nbsp;
                <input type="radio" name="use_html_template" value="1" checked>
              </label>
              <br>
              <label>
                <?php _e('Original', WP_Ranking_PRO::TEXT_DOMAIN) ?>&nbsp;
                <input type="radio" name="use_html_template" value="0">
              </label>
              <br><br>
              <?php _e('Ranking title', WP_Ranking_PRO::TEXT_DOMAIN) ?>
              <br>
              <input type="text" name="html_title_prefix" value="&lt;h2&gt;" class="small-text custom-inputarea">&nbsp;
              <input type="text" name="html_title_suffix" value="&lt;/h2&gt;" class="small-text custom-inputarea">
              <br><br>
              <?php _e('Before and after ranking', WP_Ranking_PRO::TEXT_DOMAIN) ?>
              <br>
              <input type="text" name="html_ranking_prefix" value="&lt;ul&gt;" class="small-text custom-inputarea">&nbsp;
              <input type="text" name="html_ranking_suffix" value="&lt;/ul&gt;" class="small-text custom-inputarea">
              <br><br>
              <?php _e('The loop', WP_Ranking_PRO::TEXT_DOMAIN) ?>　<span><?php _e('** The thing which I edited in follows by making the choice mentioned above "an original" is reflected', WP_Ranking_PRO::TEXT_DOMAIN) ?></span>
              <br>
              <textarea name="html_ranking_content" class="small-text custom-textarea">
&lt;li&gt;&lt;a href="[permalink]" title="[title_for_attr]"&gt;&lt;img src="[thumbnail]" alt="" border="0" width="[thumbnail_x]" height="[thumbnail_y]" class="wprp-thumbnail wpp_cached_thumb wpp_featured"&gt;&lt;/a&gt;&lt;span class="wprp-num"&gt;[rank]&lt;/span&gt;&lt;span class="wprp-post-title"&gt;&lt;a href="[permalink]" title="[title_for_attr]"&gt;[title]&lt;/a&gt;&lt;/span&gt;&lt;span class="wprp-excerpt"&gt;[excerpt]&lt;/span&gt;&lt;div class="wprp-post-stats"&gt;&lt;span class="wprp-category"&gt;category: [category]&lt;/span&gt; | &lt;span class="wprp-comments"&gt;&lt;a href="[permalink]#comments"&gt;[comment_count] comments&lt;/a&gt;&lt;/span&gt; |&lt;span class="wprp-views"&gt;[views] views&lt;/span&gt; | &lt;span class="wprp-author"&gt;by: &lt;a href="[author_link]"&gt;[author]&lt;/a&gt;&lt;/span&gt; | &lt;span class="wprp-date"&gt;[date]&lt;/span&gt; &lt;/div&gt;&lt;/li&gt;</textarea>
            </td>
          </tr>
        </table>
        </dd>
        <dt><?php _e('Exclusion setting', WP_Ranking_PRO::TEXT_DOMAIN) ?></dt>
        <dd>
        <table class="form-table">
          <tr valign="top">
            <th scope="row"><?php _e('Post to exclude', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <input type="text" name="exclude_post_ids" value="">
              <br>
              <?php _e('(when I appoint ID, and there is multiple it, it is Comma Separated Value)', WP_Ranking_PRO::TEXT_DOMAIN) ?>
            </td>
          </tr>
          <tr valign="top">
            <th scope="row"><?php _e('Category to exclude', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <input type="text" name="exclude_category_names" value="">
              <br>
              <?php _e('(when I appoint the name, and there is multiple it, it is Comma Separated Value)', WP_Ranking_PRO::TEXT_DOMAIN) ?>
            </td>
          </tr>
          <tr valign="top" class="nb">
            <th scope="row"><?php _e('Tag to exclude', WP_Ranking_PRO::TEXT_DOMAIN) ?></th>
            <td>
              <input type="text" name="exclude_tag_names" value="">
              <br>
              <?php _e('(when I appoint the name, and there is multiple it, it is Comma Separated Value)', WP_Ranking_PRO::TEXT_DOMAIN) ?>
            </td>
          </tr>
        </table>
        </dd>
        <p class="submit">
          <input type="submit" value="<?php echo $mode == 'create' ? __('Create', WP_Ranking_PRO::TEXT_DOMAIN) : __('Upload', WP_Ranking_PRO::TEXT_DOMAIN) ?>" class="button-primary">
        </p>
      </form>
    </div>
<?php
# この場合、$settingsは常にある。
if ($mode == 'update') {
?>
    <div class="wpr-preview">
      <div>
        <h2><?php _e('PHP code', WP_Ranking_PRO::TEXT_DOMAIN) ?></h2>
        <p>
        <?php _e('The following cords before the header output', WP_Ranking_PRO::TEXT_DOMAIN) ?><br><br>
        <code>
&lt;?php<br>
global $wp_ranking_pro;<br>
$wp_ranking_pro-&gt;enqueue_style(<?php echo $ranking->id ?>);<br>
?&gt;<br><br>
</code>

        <?php _e('On the part which wants to output a ranking with the following cords', WP_Ranking_PRO::TEXT_DOMAIN) ?><br><br>
        <code>
&lt;?php<br>
global $wp_ranking_pro;<br>
$wp_ranking_pro-&gt;output(<?php echo $ranking->id ?>);<br>
?&gt;<br>
</code></p>
      </div>
      <div>
        <h2><?php _e('Image Preview', WP_Ranking_PRO::TEXT_DOMAIN) ?></h2>
        <div class="image-preview">
        <?php echo $image_preview ?>
        </div>
      </div>
      <div>
        <h2><?php _e('Statistics Preview', WP_Ranking_PRO::TEXT_DOMAIN) ?></h2>
        <table class="wpr-ranking-preview">
          <tr>
            <th>Title</th>
            <th><?php echo $ranking_obj->type_name() ?></th>
          </tr>
<?php
    foreach ($posts as $post) {
?>
          <tr>
            <td><?php echo $post->post_title ?></td>
<?php
        if ($settings->order_by == 'comment_count') {
?>
            <td class="wpr-views"><?php echo number_format($post->views) ?></td>
<?php
        } else {
?>
            <td class="wpr-views"><?php echo number_format($post->views) ?> <?php _e('PV', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
<?php
        }
?>
          </tr>
<?php
    }
?>
        </table>
      </div>
      <div>
        <h2><?php _e('Short Code', WP_Ranking_PRO::TEXT_DOMAIN) ?></h2>
        <p>
        <code>[wpr id="<?php echo $ranking->id ?>"]</code>
        </p>
      </div>
    </div>
<?php
}
?>
  </div>
</div>
