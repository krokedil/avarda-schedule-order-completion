<?php
/**
 * Plugin Name:     Avarda Schedule Order Completion feature plugin.
 * Plugin URI:      http://krokedil.com/
 * Description:     Adds functionality to schedule order completion in cases where the order could not be completed in Avarda due to issues with the payment.
 * Version:         1.2.0
 * Author:          Krokedil
 * Author URI:      http://krokedil.com/
 * Developer:       Krokedil
 * Developer URI:   http://krokedil.com/
 * Text Domain:     avarda-checkout-for-woocommerce
 * Domain Path:     /languages
 *
 * Requires Plugins: woocommerce, avarda-checkout-for-woocommerce
 *
 * WC requires at least: 5.6.0
 * WC tested up to: 9.4.2
 *
 * Copyright:       © 2020-2024 Krokedil.
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package Avarda_Schedule_Order_Completion
 */

use Krokedil\Avarda\ScheduleOrderCompletion\Plugin;

defined( 'ABSPATH' ) || exit;

const ACO_SOC_VERSION = '1.2.0';
define( 'ACO_SOC_MAIN_FILE', __FILE__ );
define( 'ACO_SOC_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'ACO_SOC_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

$autoloader        = __DIR__ . '/vendor/autoload.php';
$autoloader_result = is_readable( $autoloader ) && require $autoloader;

if ( ! $autoloader_result ) {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		error_log( //phpcs:ignore
			sprintf(
				/* translators: 1: composer command. 2: plugin directory */
				esc_html__( 'Your installation of the Avarda Schedule Order Completion feature plugin is incomplete. Please run %1$s within the %2$s directory.', 'avarda-schedule-order-completion' ),
				'`composer install`',
				'`' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '`'
			)
		);
	}

	// Add a admin notice, use anonymous function to simplify, this does not need to be removable.
	add_action(
		'admin_notices',
		function () {
			?>
		<div class="notice notice-error">
			<p>
				<?php
					printf(
						/* translators: 1: composer command. 2: plugin directory */
						esc_html__( 'Your installation of the Avarda Schedule Order Completion feature plugin is incomplete. Please run %1$s within the %2$s directory.', 'avarda-schedule-order-completion' ),
						'<code>composer install</code>',
						'<code>' . esc_html( str_replace( ABSPATH, '', __DIR__ ) ) . '</code>'
					);
				?>
			</p>
		</div>
			<?php
		}
	);
	return;
}

/**
 * Get the instance of the plugin.
 *
 * @return Plugin
 */
function ACO_SOC() {  // phpcs:ignore -- allow non-snake case function name.
	return Plugin::get_instance();
}

ACO_SOC(); // Initialize the plugin.
