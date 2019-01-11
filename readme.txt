=== Automatic Domain Changer ===
Contributors: nuagelab
Donate link: http://www.nuagelab.com/products/wordpress-plugins/
Tags: admin, administration, links, resources, domain change, migration
Requires at least: 3.0
Tested up to: 4.9.8
Stable tag: trunk
License: GPLv2 or later

Automatically detects a domain name change, and updates all the WordPress tables in the database to reflect this change.

== Description ==

This plugin automatically detects a domain name change, and updates all the WordPress tables in the database to reflect this change.

= Features =

* Easily migrate a WordPress site from one domain to another
* Migrate www.domain.com and domain.com at once
* Migrate http and https links at once

= Feedback =
* We are open for your suggestions and feedback - Thank you for using or trying out one of our plugins!
* Drop us a line [@nuagelab](http://twitter.com/#!/nuagelab) on Twitter
* Follow us on [our Facebook page](https://www.facebook.com/pages/NuageLab/150091288388352)
* Drop us a line at [wordpress-plugins@nuagelab.com](mailto:wordpress-plugins@nuagelab.com)

= More =
* [Also see our other plugins](http://www.nuagelab.com/products/wordpress-plugins/) or see [our WordPress.org profile page](http://profiles.wordpress.org/users/nuagelab/)

== Installation ==

This section describes how to install the plugin and get it working.

= Requirements =

* The PHP CURL extension, usually installed on Un*x, Mac and Windows environments. See "Installing CURL on Linux" below for more help.
* Capability for your server to communicate with the outside work (or more specifically, to communicate with our servers)

= Installing the Plugin =

*(using the Wordpress Admin Console)*

1. From your dashboard, click on "Plugins" in the left sidebar
1. Add a new plugin
1. Search for "Automatic Domain Changer"
1. Install "Automatic Domain Changer"
1. Once Installed, if you want to manually change your domain, go to Tools > Domain Change
1. If your domain changes, a notice will appear at the top of the admin screen with a link to the domain changing tool

*(manually via FTP)*

1. Delete any existing 'auto-domain-change' folder from the '/wp-content/plugins/' directory
1. Upload the 'auto-domain-change' folder to the '/wp-content/plugins/' directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Once Installed, if you want to manually change your domain, go to Tools > Domain Change
1. If your domain changes, a notice will appear at the top of the admin screen with a link to the domain changing tool

= Making your blog/site address automatically reflect your server's name =

Add the following to your wp-config.php file:

<code>
define('WP_HOME', 'http://' . $_SERVER['SERVER_NAME']);
define('WP_SITEURL', 'http://' . $_SERVER['SERVER_NAME']);
</code>

See http://codex.wordpress.org/Editing_wp-config.php#WordPress_address_.28URL.29 for more information.

== Frequently Asked Questions ==

= What does this plugin do precisely? =

It scans all the tables with the same table prefix as WordPress. It fetches each row, unserialize values as needed, and replace the old domain by the new.

= Do you plan to localize this plugin in a near future? =

Yes, this plugin will be translated to french shortly. If you want to help with translation in other languages, we'll be happy to hear from you.

== Screenshots ==

1. The domain change and admin notice

== Changelog ==
= 2.0.2 =
* Tested up to WordPress 4.9.8
* Added a way to change the protocol to HTTP or HTTPS

= 2.0.1 =
* Tested up to WordPress 4.6.1
* Removed admin notice for users who don't have update_core permission

= 2.0.0 =
* Tested up to WordPress 4.4.2
* Added backup functionality
* Removed usage of mysql_* functions in favor of $wpdb

= 1.0.1 =
* Tested up to WordPress 4.2.2

= 1.0 =
* Tested up to WordPress 4.2.1

= 0.0.6 =
* Bug fix with the processValue function generating a warning (thanks to @sniemetz for letting us know about this issue)
* Slovak translation (thanks to Marek Letko)
* Tested up to WordPress 4.1.1

= 0.0.5 =
* Minor text change

= 0.0.4 =
* Added JSON detection to fix values not being handled for plugins like RevSlider (thanks to Alfred Dagenais for letting us know about this issue)
* Added double serialize detection for plugins like Global Content Blocks (thanks to @pixelkicks for letting us know about this issue)
* Tested plugin up to WordPress 4.0.0

= 0.0.3 =
* Tested plugin up to WordPress 3.8.0

= 0.0.2 =
* Added error suppression on unserialize calls, as failing unserialize are normal and part of the game. Thanks to Kailey Lampert for pointing this out.
* Added serialize(false) detection.

= 0.0.1 =
* First released version. Tested internally with about 10 sites.


== Upgrade Notice ==

== Translations ==

* English
* French
* Spanish
* Slovak
