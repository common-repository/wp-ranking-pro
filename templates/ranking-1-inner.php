<ul class="wprp-list">
<?php
$this->begin_loop('posts');
?>
  <li>
    <a href="[permalink]" title="[title_for_attr]"><img src="[thumbnail]" alt="" border="0" width="[thumbnail_x]" height="[thumbnail_y]" class="wprp-thumbnail wpp_cached_thumb wpp_featured"></a>
    <span class="wprp-num">[rank]</span>
    <span class="wprp-post-title"><a href="[permalink]" title="[title_for_attr]">[title]</a></span>
    <span class="wprp-excerpt">[excerpt]</span>
    <div class="wprp-post-stats">
      <span class="wprp-category"><?php _e('category', WP_Ranking_PRO::TEXT_DOMAIN) ?>: [category]</span> | 
      <span class="wprp-comments"><a href="[permalink]#comments">[comment_count] comments</a></span> |
      <span class="wprp-views">[views] <?php _e('views', WP_Ranking_PRO::TEXT_DOMAIN) ?></span> |
      <span class="wprp-author"><?php _e('by', WP_Ranking_PRO::TEXT_DOMAIN) ?>: <a href="[author_link]">[author]</a></span> |
      <span class="wprp-date">[date]</span>
    </div>
  </li>
<?php
$this->end_loop();
?>
</ul>
