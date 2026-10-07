<?php
/**
 * Plugin Name:       Easy2Cuba for WooCommerce
 * Description:       Checkout pensado para vender desde cualquier país y enviar a Cuba. Separa quién compra y quién recibe, incluye las 15 provincias y los 168 municipios de Cuba, cobra el envío según la provincia (con provincias activables), muestra el pedido con fotos, cantidades y precio por unidad, y es compatible con las pasarelas de pago de WooCommerce. Al confirmarse el pago envía por correo un PDF informativo con todos los datos de la entrega, con registro de documentos, logo propio, SMTP opcional y herramientas de privacidad. En español e inglés.
 * Version:           1.5.1
 * Author:            GMETI
 * Author URI:        https://gmeti.com
 * Text Domain:       easy2cuba-for-woocommerce
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.0
 * WC tested up to:   9.9
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/wpdesigndev/easy2cuba-for-woocommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'E2C_VERSION', '1.5.1' );
define( 'E2C_FILE', __FILE__ );
define( 'E2C_PATH', plugin_dir_path( __FILE__ ) );
define( 'E2C_URL', plugin_dir_url( __FILE__ ) );

require_once E2C_PATH . 'includes/class-e2c-i18n.php';
require_once E2C_PATH . 'includes/class-e2c-data.php';
require_once E2C_PATH . 'includes/class-e2c-updater.php';
E2C_Updater::init();

/**
 * Compatibilidad con HPOS (pedidos en tablas propias).
 * El checkout de bloques se marca como no compatible: el plugin usa el checkout clásico.
 */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', E2C_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', E2C_FILE, false );
		}
	}
);

register_activation_hook(
	__FILE__,
	function () {
		if ( false === get_option( E2C_Data::OPTION ) ) {
			add_option( E2C_Data::OPTION, E2C_Data::defaults() );
		}
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'e2c_cleanup_invoices' );
	}
);

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-error"><p><strong>Easy2Cuba for WooCommerce:</strong> ' . esc_html( E2C_I18n::t( 'wc_missing' ) ) . '</p></div>';
				}
			);
			return;
		}

		require_once E2C_PATH . 'includes/class-e2c-checkout.php';
		require_once E2C_PATH . 'includes/class-e2c-orders.php';
		require_once E2C_PATH . 'includes/class-e2c-invoices.php';
		require_once E2C_PATH . 'includes/class-e2c-privacy.php';
		E2C_Privacy::init();
		E2C_Checkout::init();
		E2C_Orders::init();
		E2C_Invoices::init();

		if ( is_admin() ) {
			require_once E2C_PATH . 'includes/class-e2c-admin.php';
			E2C_Admin::init();
		}
	}
);

// Descripción del plugin en el idioma del panel.
add_filter(
	'all_plugins',
	function ( $plugins ) {
		$base = plugin_basename( E2C_FILE );
		if ( isset( $plugins[ $base ] ) ) {
			$plugins[ $base ]['Description'] = E2C_I18n::t( 'plugin_desc' );
		}
		return $plugins;
	}
);

// El enlace de GMETI en la lista de plugins se abre en una pestaña nueva.
add_filter(
	'plugin_row_meta',
	function ( $meta, $file ) {
		if ( plugin_basename( E2C_FILE ) !== $file ) {
			return $meta;
		}
		foreach ( $meta as $i => $item ) {
			if ( false !== strpos( $item, 'gmeti.com' ) && false === strpos( $item, 'target=' ) ) {
				$meta[ $i ] = str_replace( '<a ', '<a target="_blank" rel="noopener noreferrer" ', $item );
			}
		}
		$meta[] = '<a href="mailto:easy2cubaforwoo@gmeti.com" target="_blank" rel="noopener noreferrer">' . esc_html( E2C_I18n::t( 'i_support' ) ) . '</a>';
		return $meta;
	},
	10,
	2
);
