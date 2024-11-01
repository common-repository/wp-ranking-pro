=== WP-Ranking PRO ===
Contributors: plugmize
Donate link: https://plugmize.jp/
Tags: ranking, popular, Post, posts, popularity, widget, AJAX, shortcode, cache, top
Requires at least: 4.5
Tested up to: 4.8.2
Stable tag: 1.0.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

"WP-Ranking PRO" totals a page view, and into which a popular article can be formed by various elements or periods.

== Description ==

"WP-Ranking PRO" is a widget plugin with advanced features to display the ranking of popular articles in each elements and period on your blog.

= Main Features =
* **Time Range** - It displays the ranking within the specified period of time.(eg. 24 h, 1 week, 1 month, 1 year, all, etc.)
* **Mobile-friendly** - Make an aggregate individually in the PC and mobile(smartphone, tablet, etc.). Mobile accesses is distinguished by the user agent and a rank is made.
* **Ranking Cache** - The ranking data has the ability to cache a certain period of time.
* **Shortcode support.** - You can make your own page on ranking.
* **PHP code support.** - By the specify the ranking information to be displayed in the PHP code, you can make any rankings page.
* **Multi-widget** support.
* **Custom-widgets capable** - Them rankings can freely customize title, characters, various articles, various categories, various tags, period, equipment or HTML tags, and more.
* **Display a thumbnail of your posts.** It is possible to select a thumbnail to display.
* **Exclusion from the aggregation target.** - You can exclude access from a particular environment from the aggregation target.(eg. the origin of access, HTTP referers, user agents, logged-in users.)
* **Custom Post-type** support!

= Other Features =
* Summary of rankings can be **displayed on the dashboard** (wp-admin).
* **Automatic clearance of log.** - Data is accumulated by a data base, but I have the function from which the log which accumulated can be eliminated automatically.
* Rebuild cache
* Clear cache

= There is following function. =
* Periods: 24 h, 1 week, 1 month, 1 year, all, and more
* Exclusions: the origin of access, HTTP referers, user agents, logged-in users

== Installation ==

1. Upload `wp-ranking-pro` directory to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently asked questions ==

= Can be displayed summary of rankings on the dashboard? =
Yes.

= As part of the rankings plug-in, is there a possibility that significantly performance reduced by using? =
It can not be denied.
However, as a countermeasure therefor, it has the ability to cache the ranking data predetermined time.

= Aggregation Can be carried out using ajax? =
Yes. You can specify in the management screen.

= Is the thumbnail to displayed can be selected? =
Yes. You can specify in the management screen.

= If you are in an environment that uses such as a proxy, do you have the ability to change the acquisition key of the IP address? =
Yes. You can specify in the management screen. Usually use the "REMOTE_ADDR", but can be changed as needed.

= If the log is accumulated, do you have the ability to remove the unnecessary log? =
Yes.
By the setting of the management screen, it can be done by using the function to be deleted automatically.

== Screenshots ==

1. Widget on theme's sidebar.
2. Widgets Control Panel.
3. Statistics screen.
4. Management screen.
5. Setup screen of custom rankings.
6. PHP code sample.

== Changelog ==

**1.0.3**
* Bug fix.

**1.0.2**
* Bug fix.

**1.0.1**
* Bug fix.

**1.0.0**
* Initial release

== Upgrade notice ==

Initial release

== Language support ==

* **Japanese**
* **English**
