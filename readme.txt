=== Avarda Schedule Order Completion feature plugin. ===
Contributors: krokedil, niklashogefjord
Tags: ecommerce, e-commerce, woocommerce, avarda
Requires at least: 5.0
Tested up to: 6.6.2
Requires PHP: 7.4
WC requires at least: 5.6.0
WC tested up to: 9.4.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Stable tag: 1.1.0

A feature plugin for Avarda Checkout for WooCommerce to allow for scheduling of order completion when using Avarda Checkout for orders that cant be activated directly.

== CHANGELOG ==
= 2024.11.21        - version 1.1.0 =
* Feature           - Don't prevent the order status from changing when its set to Completed, but update it to on-hold after if we need to schedule it.
* Feature           - Set the order status to failed after 5 attempts to schedule the order completion.
* Feature           - Add a order note when we schedule the order to be completed.

= 2024.10.18        - version 1.0.0 =
* Initial release
