=== Tiqbiz API ===
Contributors: tiqbiz
Tags: api, admin
Requires at least: 3.7
Tested up to: 4.1
Stable tag: trunk
License: CC BY-SA 4.0
License URI: https://creativecommons.org/licenses/by-sa/4.0/legalcode

Integrates your WordPress site with the Tiqbiz API

== Description ==

This plugin synchronises post and CalPress events in your WordPress site to your Tiqbiz account.

For more information on Tiqbiz, please see http://www.tiqbiz.com/

== Installation ==

1. Install either via the WordPress.org plugin directory, or by uploading the plugin files to your server
2. After activating the plugin, you will need to go to the Tiqbiz API Settings page and provide some authentication details, which will be provided by the Tiqbiz team
3. Any new or updated posts or CalPress events will be synced across to your Tiqbiz account

== Frequently Asked Questions ==

For all informaton on this plugin and Tiqbiz, please see http://www.tiqbiz.com/

== Screenshots ==

1. An example of the notice shown while syncing
2. The Tiqbiz API Settings page

== Changelog ==

= 1.0.8 =
Resolved an issue where some special characters were not displaying properly

= 1.0.7 =
Sync via plugin rather than AJAX directly

= 1.0.6 =
Better handling of post/event content

= 1.0.5 =
Fix for single day all day events

= 1.0.4 =
Support syncing future posts

= 1.0.3 =
Support for CalPress Pro

= 1.0.2 =
* Better timezone handling for event times
* PHP 5.3 compatibility
* Add PHP version to to settings page

= 1.0.1 =
Change 'Options' to 'Settings'

= 1.0 =
Initial release

== Upgrade Notice ==

= 1.0 =
Initial release

= 1.0.1 =
Minor update

= 1.0.2 =
Minor update to handle timezones better, and support older versions of PHP

= 1.0.3 =
Minor update to support CalPress Pro

= 1.0.4 =
Minor update to support syncing future posts

= 1.0.5 =
Minor update to fix an issue with single day all day events

= 1.0.6 =
Minor update to fix an issue with unusual characters in post/event content, and to add support for shortcodes

= 1.0.7 =
Update to fix an issue with posts that have lots of content not syncing due to URL length limits

= 1.0.8 =
Update to fix an issue where some special characters were not displaying properly