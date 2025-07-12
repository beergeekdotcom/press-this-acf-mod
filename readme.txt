=== Press This ACF Mod ===
Contributors: jeffscott
Tags: press-this, acf, custom-taxonomy, advanced-custom-fields, editorial, admin
Requires at least: 6.7
Tested up to: 6.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html

Adds ACF field support and custom taxonomy integration to posts created with the Press This plugin. Hooks into Press This and moves things to the proper locations with JavaScript.

== Description ==
Press This ACF Mod extends the [Press This](https://wordpress.org/plugins/press-this/) plugin to support [Advanced Custom Fields (ACF)](https://wordpress.org/plugins/advanced-custom-fields/) and custom taxonomies when creating or editing posts. This plugin injects ACF field groups and custom taxonomy panels into the Press This UI, emulating the native category/tag experience. All data is saved using WordPress core APIs for maximum compatibility.

**Features:**
* Display and save ACF fields in Press This UI
* Show all registered custom taxonomies for the post type, with native-style selection modals and accessibility
* Save custom taxonomy terms reliably, including hierarchical and non-hierarchical types
* No core Press This modifications required; fully upgrade-safe
* Excludes specific taxonomies (e.g., post_format) as needed

== Installation ==
1. Install and activate [Press This](https://wordpress.org/plugins/press-this/) and [Advanced Custom Fields (ACF)](https://wordpress.org/plugins/advanced-custom-fields/).
2. Upload `press-this-acf-mod` to your `/wp-content/plugins/` directory.
3. Activate the plugin through the 'Plugins' menu in WordPress.
4. Use Press This as usual. ACF fields and custom taxonomy panels will appear in the UI.

== Frequently Asked Questions ==
= Does this work with all ACF field types? =
Yes, all ACF field types supported by acf_form() are rendered. Some complex field types may require additional styling.

= Does this support all custom taxonomies? =
All registered taxonomies for the post type are supported, except those explicitly excluded (e.g., post_format).

= Is this plugin upgrade-safe? =
Yes, it hooks into Press This via actions and filters and does not modify core files.

== Changelog ==
= 1.0.0 =
* Initial release: ACF field and custom taxonomy support in Press This UI.

== Upgrade Notice ==
= 1.0.0 =
First public release. Requires Press This and ACF plugins active.
