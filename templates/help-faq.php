<h3><?php _e('Make a ranking', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <tbody>
    <tr>
      <td width="25%"><?php _e('* Basic how to use', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">
    	<strong><?php _e('*Ranking indication using the widget', WP_Ranking_PRO::TEXT_DOMAIN) ?></strong><br><br>
    	<?php _e('1. Make a favorite ranking in "custom ranking" of the menu', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	<?php _e('2. Add "WP-Ranking PRO" to the favorite point by a widget menu and choose the ranking that I made with 1', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	<br>
    	<strong><?php _e('*Ranking indication using the PHP cord', WP_Ranking_PRO::TEXT_DOMAIN) ?></strong><br><br>
    	<?php _e('1. Make a favorite ranking in "the custom ranking" of the menu', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	<?php _e('2. Paste it on the point that wants to let you display generated "PHP code"', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	</td>
    </tr>
    <tr>
      <td width="25%"><?php _e('* Edit a custom ranking', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">
    	<?php _e('Choose "statistics "->" custom" of the menu and choose the ranking that I want to edit', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	<?php _e('Just reflected automatically when I change the item which I want to edit', WP_Ranking_PRO::TEXT_DOMAIN) ?>
    	</td>
    </tr>
    <tr>
      <td width="25%"><?php _e('* Delete the custom ranking', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">
    	<?php _e('Choose "statistics "->" custom" of the menu and choose the ranking that I want to delete', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	<?php _e('Because "deletion" and a displayed point become the blank, please be careful', WP_Ranking_PRO::TEXT_DOMAIN) ?>
    	</td>
    </tr>
    <tr>
      <td width="25%"><?php _e('* Set "automatic deletion of the log"', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">
    	<?php _e('Choose "the deletion of data "->" log" of the menu and turn on automatic deletion in a designated period', WP_Ranking_PRO::TEXT_DOMAIN) ?>
    	</td>
    </tr>
  </tbody>
</table>

<h3><?php _e('Setting-related', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <tbody>
    <tr>
      <td width="25%"><?php _e('* The log acquisition by the Ajax', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%"><?php _e('When I use plug in of cache origin, I check it when a count is not acquired well', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
    </tr>
    <tr>
      <td width="25%"><?php _e('* Acquire the page view of the smartphone', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">
    	<?php _e('Count access from mobile by setting a user agent.', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	<?php _e('When you do not understand it, please paste follows.', WP_Ranking_PRO::TEXT_DOMAIN) ?><br><br>
    	<?php echo nl2br(WP_Ranking_PRO::UA_MOBILE); ?>
    	</td>
    </tr>
  </tbody>
</table>

<h3><?php _e('About "data"', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <tbody>
    <tr>
      <td width="25%"><?php _e('* About "deletion of the log"', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%">
    	<?php _e('* With "log deletion," it becomes the function to delete the raw log data of the user. (as for the TABLE name "wpr_views")', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	<?php _e('* These data become the data for the device distinction of the user. It is the data which do not have any problem even if I delete it.', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	<?php _e('* A schedule is started when I validate automatic deletion after having deleted the past data once.', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	<?php _e('* Confirm the confirmation of the schedule in plug in such as "WP Crontrol". (as for the schedule name "wpr_scheduled_cleanlog")', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    	<?php _e('* When data are going to delete it in an enlarged state, a server may stop by load of the deletion processing. Let\'s delete it diligently.', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
    </td>
    <tr>
      <td width="25%"><?php _e('* About the backup of data', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%"><?php _e('Because the access data of WP-Ranking PRO gain article data and restrictions, you should back it up in DB backup plug in such as "WP-DBManager" regularly.', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
    </tr>
    </tr>
  </tbody>
</table>

<h3><?php _e('Item of the custom ranking', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <tbody>
    <tr>
      <td width="25%"><?php _e('* Title setting', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%"><?php _e('The name to like "the ranking name." "A ranking title" is a title displayed on your WEB SITE', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
    <tr>
      <td width="25%"><?php _e('* About HTML', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%"><?php _e('"Number of number of category / comment / page view / contributor / date" is displayed other than "article title / thumbnail / order" in "a default".', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
          <?php _e('I can adjust the item which wants to let you display it by making "an original". In this case I will prepare for CSS beforehand.', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
    </tr>
  </tbody>
</table>

<h3><?php _e('Others', WP_Ranking_PRO::TEXT_DOMAIN) ?></h3>
<table class="wpr-help">
  <tbody>
    <tr>
      <td width="25%"><?php _e('* Operator information', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%"><a href="https://plugmize.jp/" target="_blank">PLUGMIZE</a><br></td>
    <tr>
      <td width="25%"><?php _e('* Use example', WP_Ranking_PRO::TEXT_DOMAIN) ?></td>
      <td width="75%"><a href="http://otakei.otakuma.net/" target="_blank">http://otakei.otakuma.net/</a> <?php _e('It is really company site using this plug in.', WP_Ranking_PRO::TEXT_DOMAIN) ?><br>
        <?php _e('If I discover malfunction I am happy when you can let me know', WP_Ranking_PRO::TEXT_DOMAIN) ?> <a href="https://plugmize.jp/contact/" target="_blank"><?php _e('CONTACT', WP_Ranking_PRO::TEXT_DOMAIN) ?></a></td>
    </tr>
  </tbody>
</table>
