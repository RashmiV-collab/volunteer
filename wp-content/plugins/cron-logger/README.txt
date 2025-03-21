=== Cron Logger ===
Contributors: edwardbock, palasthotel
Donate link: http://palasthotel.de/
Tags: tool, log, debug, cron, wp-cron
Requires at least: 5.3
Tested up to: 6.2.2
Stable tag: 1.2.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl

Logs wp-cron.php runs.

== Description ==

Have you ever wondered what you WordPress is doing in wp-cron.php? Now you can see it. This plugin logs every schedule.

== Installation ==

1. Upload `cron-logger.zip` to the `/wp-content/plugins/` directory
1. Extract the Plugin to a `cron-logger` Folder
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Habe a look in Tools -> Cron Logs

== Frequently Asked Questions ==

== Screenshots ==


== Changelog ==

= 1.2.1
 * Optimization: Delete all data in the database on plugin deletion

= 1.2.0
 * Optimization: Reduced history to 14 days because of performance issues

= 1.1.1
 * Optimization: Performance optimization for post deletion

= 1.1.0
 * Refactoring: Code cleanup

= 1.0.5
 * Fix: missing sanitization fix thanks community member report.

= 1.0.4
 * Optimization: Multisite plugin activation (Thanks to @jcleaveland for report)
 * Optimization: use wp_timezone_string function (Thanks to @pothi for report)

= 1.0.3 =
 * Bugfix: database error while cleaning logs

= 1.0.2 =
 * scheduled posts status transition
 * clean logs
 * filer cron_logger_expire can modify the days before logs expire

= 1.0.1 =
 * Translations

= 1.0.0 =
 * Release

== Arbitrary section ==

== Upgrade Notice ==

With version 1.2.x we reduced the retention period of the logs to 14 days. Please use the "cron_logger_expire" filter if you want to keep logs for a longer period.
