=== Product Country Restrictions ===
Contributors: Bizilica
Donate link: https://www.paypal.com/cgi-bin/webscr?hosted_button_id=Y8LDNXPFBDRBW&item_name=WooCommerce-PCR&cmd=_s-xclick
Tags: woocommerce
Requires at least: 4.0
Tested up to: 4.9
Stable tag: 4.9
License: GPLv2 
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Restrict WooCommerce products to certain countries.

== Description ==

Do you have products in your WooCommerce shop that you for some reason can't sell to customers in certain countries?

If you do, this is the plugin for you!

For each product in your shop, you will be able to specify a list of countries that you may sell that particular 
product to.  Or, you can specify a list of countries that you don't want to sell to.

Geolocation is used to determine what country the visitor is in, if their country is not in the approved list of countries 
for the product, the add-to-cart button will be disabled.

You will need WooCommerce 3.0 or newer.

Does support translation.


== Installation ==

1. Upload the folder 'product-country-restrictions` to the `/wp-content/plugins/` folder
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Make sure you have setup "shipping countries" in woocommerce general settings.
1. Edit a product to see your new options


== Screenshots ==

1. You can exclude a list of countries for a product
2. Allow a product to be sold only to selected countries

== Frequently Asked Questions ==

= Is this well tested? =

Not really.  But I do believe it will work, most of the time.

= Is geolocation reliable? =

Somewhat. For this purpose, it should be good enough.  A clever user could of course bypass geolocation by using a proxy service.

== Changelog ==
= 0.4.3 =
* Some improvements for variable products

= 0.4.1 =
* Minor bug fix for WooCommerce 3.0+

= 0.4 =
* You can now set restrictions for product variations
* Under woocommerce/settings/products/display, you can configure the message that will be displayed when a product is restricted.

= 0.3 =
* Initialization of the Geolocation DB at plugin activation

= 0.2 =
* added check for proper WooCommerce version

= 0.1 =
* Initial version.

