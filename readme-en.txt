=== Easy2Cuba for WooCommerce ===
Contributors: gmeti
Tags: woocommerce, checkout, cuba, shipping
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.6.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The easiest way to sell and ship to Cuba.

== Description ==

**Easy2Cuba for WooCommerce** replaces the WooCommerce checkout with one built to sell from anywhere and deliver in Cuba. The buyer can be in Miami, Madrid or Havana; the recipient is in Cuba, and the checkout asks for exactly what the courier needs.

= Buyer =

* First names, last names and email, where the order confirmation arrives.
* Optional phone with a free prefix.
* Required address and country: the country is picked from the list or set automatically from the phone country code.

= Recipient in Cuba =

* Recipient's first and last names.
* Required mobile phone (+53) and optional landline.
* Optional ID card number, to verify the person on delivery.
* All 15 provinces, the special municipality Isla de la Juventud and its 168 municipalities.
* Street and number, cross streets, neighborhood and directions for the courier.
* Recipient consent checkbox (can be turned off).

= Shipping by province =

* Set the shipping price for each province in the admin.
* Enable only the provinces you deliver to.
* The cost appears when the province is chosen and is added to the total.

= Clear order summary =

* Product photo, name and quantity (x2).
* Unit price: `$14.00 each × 2 = $28.00`.

= Automatic delivery PDF =

* When the payment is confirmed, an informative PDF (FAC-0001, FAC-0002…) with all the delivery details is emailed, ready to forward to the courier or the owner.
* With your logo, a configurable recipient email and your own footer.
* Log of sent documents: view or delete them whenever you want.
* Test send and optional SMTP, with the exact reason if the email fails.

= Compatible =

* Any WooCommerce payment gateway: PayPal, Stripe, cards, bank transfers…
* Elementor, multi-currency plugins (WPML/WCML, CURCY) and HPOS orders.
* Spanish and English, following your WordPress language.

= Privacy =

* Integrates with the WordPress personal data export and erase tools.
* Suggests text for your privacy policy.
* Can automatically delete old documents from the log.

Developed by [GMETI](https://gmeti.com). Plugin page: [wpdesigndev.github.io/easy2cuba-for-woocommerce](https://wpdesigndev.github.io/easy2cuba-for-woocommerce/).

== Installation ==

1. Download `easy2cuba-for-woocommerce.zip` from the [latest release on GitHub](https://github.com/wpdesigndev/easy2cuba-for-woocommerce/releases/latest).
2. In WordPress go to Plugins › Add New › Upload Plugin, choose the zip and activate it.
3. Go to **Easy2Cuba** in the side menu and set the shipping cost for each province.
4. If it warns you that your checkout page uses blocks, click **Switch to classic checkout**.
5. In the **Invoices** tab check the email that will receive the PDFs and send a test.

**Requirements:** WordPress 6.0+, WooCommerce 7.0+, PHP 7.4+.

== Frequently Asked Questions ==

= Is it free? =

Yes, it is free and open source (GPLv2 or later).

= Does it work with my payment method? =

Yes. Easy2Cuba only changes the details asked for at checkout; payment is still handled by the gateway you have installed.

= Is the PDF a tax invoice? =

No. It is an informative document that gathers all the order and delivery details on one page.

= The test email doesn't arrive, what do I do? =

Many servers can't send email on their own. Turn on SMTP in the Invoices tab with your email account details; if it fails, the plugin shows the exact reason.

= How is it updated? =

Automatically. When a new version is published on GitHub, the notice appears in Plugins and it updates with one click. The plugin only checks which version is the latest (at most every 6 hours) and sends no store or customer data.

= Where do I get help? =

Write to easy2cubaforwoo@gmeti.com.

== Screenshots ==

1. Checkout: buyer, recipient in Cuba and order summary.
2. Admin: shipping price by province.
3. Admin: PDF documents, log and test send.
4. PDF document with all the delivery details.

== Changelog ==

= 1.6.1 =
* "Check again" in Dashboard › Updates looks for a new version right away, without waiting 6 hours.
* The "Delivery details in Cuba" box (admin order, thank-you page and WooCommerce emails) follows the same order as the checkout.

= 1.6.0 =
* New Country field for the buyer, with every country: choose it from the list or it is set automatically from the phone country code.
* The buyer's address and country are now required.
* The PDF shows the details in the same order as the checkout.
* Information tab: GMETI link, "Created by Chuck Gomez" and the plugin page.

= 1.5.1 =
* "View details" in Plugins now shows the full description, installation, FAQ, screenshots and what's new in each version, in Spanish or English.

= 1.5.0 =
* Automatic updates from GitHub: when a new version is released, the notice appears in Plugins and it updates with one click.

= 1.4.2 =
* Support link in the plugins list.

= 1.4.1 =
* The GMETI link in the plugins list is back and opens in a new tab.

= 1.4.0 =
* Privacy: Easy2Cuba data is included when exporting and erasing personal data from WordPress/WooCommerce.
* Suggested privacy policy text (Settings › Privacy).
* Optional automatic deletion of old invoices from the log.
* Recipient consent checkbox at checkout (can be turned off).
* Longer plugin description.

= 1.3.0 =
* Full-width checkout (can be turned off).
* Province before municipality in the email and the invoice.
* Better compatibility with payment gateways (billing country always present), Elementor and multi-currency plugins.
* New admin menu icon.

= 1.2.0 =
* Invoice numbering FAC-0001…
* Optional SMTP for invoices and the error reason when email fails.
* Full-width admin descriptions.
* More space at the end of each checkout section and at the end of the page.

= 1.1.0 =
* PDF invoices when the payment is confirmed, with log, logo and test send.
* Tabbed admin: Shipping, Invoices and Information.
* More space in the order summary and the payment section.
* Full-width admin.

= 1.0.0 =
* First release.
