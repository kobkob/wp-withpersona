=== WP WithPersona ===
Contributors: monsenhor
Donate link: https://kobkob.org/
Tags: comments, spam
Requires at least: 6.3
Tested up to: 6.5.2
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Identifies users integrating the With Persona tool

== Description ==

WP WithPersona is a plupin to integrate WordPress with the With Persona tool.

The default configuration uses the Embeded Flow for inquiry:

https://docs.withpersona.com/docs/quickstart-embedded-flow


Notice: The http headers must permit external I-Frames:

Must have 'X-Frame-Options' to 'allow'.


== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==


== Screenshots ==

== Changelog ==

= 1.0 =
* First test

= 1.1 =
* First working prototype

= 1.2 =
* Limit users per month
* Select pages to apply the Inquiry

= 1.2 =
* List versions from most recent at top to oldest at bottom.

= 1.2.1 =
* Consolidate release candidate

== Upgrade Notice ==


