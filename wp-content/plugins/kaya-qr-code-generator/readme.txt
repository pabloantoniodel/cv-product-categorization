=== Kaya QR Code Generator ===

Plugin Name: Kaya QR Code Generator
Description: Generate QR Code through Widgets and Shortcodes, without any dependencies.
Tags: QR Code, qrcode, Widget, Shortcode, WooCommerce
Author: Kaya Studio
Author URI:  https://kayastudio.fr
Donate link: http://dotkaya.org/a-propos/
Contributors: kayastudio
Requires at least: 4.6.0
Tested up to: 6.5
Stable tag: 1.6.0
Version: 1.6.0
Requires PHP: 5.2
Text Domain: kaya-qr-code-generator
Domain Path: /languages
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Generate QR Code through Widgets and Shortcodes, without any dependencies.

== Description ==

**Why use "Kaya QR Code Generator"?**

This plugin creates QR Codes (Quick Response codes) through a widget or a shortCode for easy insertion into your pages, posts, sidebars, WooCommerce products, etc.

Easy install and use, generate dynamic QR Codes with your custom settings. Content can be any text, link and even a Bitcoin address or the current page URL.

The QR Code generator library is included (based on qr.js written by Kang Seonghoon) and don't need any dependencies.

= QR Code Features =

* Add a title and choose its horizontal alignment.
* Use static or dynamic content to encode in QR Code.
* Add a query string to the automatic current page url.
* Add an anchor link to the automatic current page url.
* Select the information repetition level (Ability to correct read errors).
* Set the QR Code image size.
* Set the QR Code image border level.
* QR Code image color and background color customizable.
* Set the QR Code image horizontal alignment.
* Add shadows to QR Code image.
* Image alternate text customizable.
* Clickable link on image customizable (support non-standard URL schemes).
* Add a button to download QR Code image on frontend, with custom text and alignment.

= Generator Features =

* Use as built-in Widget.
* Use as shortcode with generator assistant.
* QR Code preview and download on Shortcode generator assistant.
* The Shortcode generator assistant is available on pages, posts, WooCommerce products, any public custom post types and on the plugin option page.
* Setting to enable or disable the reduced shortcode generator assistant in editor.
* Setting to display or hide Shortcode generator assistant to selected user role.
* Setting to display or hide Shortcode generator assistant to selected post type.
* Setting to select Shortcode generator assistant Meta box context and priority in editor.
* Setting to choose the default QR Code image color and background color.
* Compatible with WordPress MultiSite and WooCommerce.

= Basic shortcode =

* Static content: `[kaya_qrcode content="my encoded content"]`.
* Dynamic content: `[kaya_qrcode_dynamic][example_shortcode][/kaya_qrcode_dynamic]`.

= Privacy =

This plugin does not collect or store any user data. It does not set any cookies and does not connect to any third-party applications. This plugin only generate QR Code image based on your custom content.

= Available Languages =

* English.
* French.

= Feedback =

Any suggestions or feedback is welcome, thank you for using or trying one of my plugins. Please take the time to let me know about your experiences and rate this plugin.

== Screenshots ==

1. Kaya QR Code Generator: Widget.
2. Kaya QR Code Generator: Shortcode generator assistant.
3. Kaya QR Code Generator: Shortcode generator assistant with dynamic content.
4. Kaya QR Code Generator: Settings page.
5. Kaya QR Code Generator: Custom QR Code display examples.

== Frequently Asked Questions ==

= Why my modifications are not saved when I update my post? =

The ‘Kaya QR Code Generator’ panel available in a page, a post, a WooCommerce product or in any public custom post type, is not used as custom fields for the post and don’t affect anything in the page content.

The shortcode generator assistant, is used to assist on the shortcode structure construction. The generated shortcode must be pasted on a “shortcode block” or directly in the page content.

= Can I use the shortcodes in a PDF or a mail? =

No, the QR Code shortcode must be present in a WordPress page, because it uses JavaScript functions to generate the QR Code image.

But you can download the generated image on Shortcode generator assistant for example and use it as you want.

= How to use dynamic content? =

No problem, Kaya QR Code Generator is easy to use with dynamic content (other shortcodes).

If you want to display dynamic content by a widget, the generator is used by default and you just need to check the checkbox "Use dynamic content (other shortcodes)".

If you want to display dynamic content by a shortcode, use this following shortcode: `[kaya_qrcode_dynamic][example_shortcode][/kaya_qrcode_dynamic]`.

= What if I wish to modify the QR Code options and display? =

No problem, Kaya QR Code Generator is fully customizable.

You can add a query string or an anchor link to the automatic current page URL.

You can modify the image size in pixels, the error correction level (Low ~7%, Medium ~15%, Quarter ~25% and High ~30%).

You can add a title, shadows on image, modify the horizontal alignment, and use custom colors and background colors.

= What if I wish to add a link on my QR Code? =

No problem, Kaya QR Code Generator is fully customizable.

You can add a destination URL (or use automatic current page URL / QR Code content URL) as clickable link, and make it open in a new window.

= How to find and use shortcode generator assistant? =

No problem, Kaya QR Code Generator is easy to use.

If you want to display the qr-code by a widget, the generator is used by default.

If you want to display the qr-code in a page, a post, a WooCommerce product or in any public custom post type, the generator is under the administration primary content of your page / post / product.

If the ‘Kaya QR Code Generator’ panel is not displayed, verify that ‘Kaya QR Code Generator’ is checked in the page / post / product options, in ‘Show more tools & options’ > ‘Options’ and ‘Advanced Panels’.

The shortcode generator assistant is also available in the plugin options page.

= How to support the advancement of this plugin? =

Any suggestions or feedback is welcome, please take the time to let me know about your experiences and rate this plugin.

You can help to support the advancement by donate to this plugin, see more details on http://dotkaya.org/a-propos/

== Installation ==

The quickest way:

1. Go to the Plugins Menu in WordPress and select Plugins > Add new
1. Search for "Kaya QR Code Generator"
1. Click "Install" and "activate".

The other way:

1. Upload the "kaya-qr-code-generator" folder to the "/wp-content/plugins/" directory
1. Activate the plugin through the "Plugins" menu in WordPress.

== Changelog ==

= 1.6.0 =
* Fix: Proper query string added to the automatic current page url if parameters are already present.
* Adding: Provides option to remove plugin style on QR Code image.
* Adding: Provides option to select border level on QR Code image.
* Adding: Provides option to enabling a button to download QR Code image on frontend, with custom text and alignment.
* Adding: On QR Code Generator settings page: Settings to select Meta box context and priority in editor.
* Adding: On QR Code Generator settings page: Settings to select default QR Code image color and background color.

To read the changelog of earlier versions of Kaya QR Code Generator, please navigate to the "changelog.txt" file.