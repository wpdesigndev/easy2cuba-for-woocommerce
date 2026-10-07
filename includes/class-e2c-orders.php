<?php
/**
 * Muestra los datos de entrega en Cuba en el pedido: panel de administración, página de gracias y correos.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class E2C_Orders {

	public static function init() {
		add_filter( 'woocommerce_order_needs_shipping_address', array( __CLASS__, 'needs_address' ), 10, 3 );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( __CLASS__, 'admin_box' ) );
		add_action( 'woocommerce_order_details_after_customer_details', array( __CLASS__, 'front_box' ) );
		add_action( 'woocommerce_email_customer_details', array( __CLASS__, 'email_box' ), 30, 4 );
		// Página de "pedido recibido" en temas de bloques.
		add_filter( 'render_block', array( __CLASS__, 'confirmation_block' ), 10, 2 );
	}

	public static function confirmation_block( $content, $block ) {
		if ( empty( $block['blockName'] ) || 'woocommerce/order-confirmation-shipping-address' !== $block['blockName'] ) {
			return $content;
		}
		$order_id = absint( get_query_var( 'order-received' ) );
		$key      = isset( $_GET['key'] ) ? wc_clean( wp_unslash( $_GET['key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$order    = $order_id ? wc_get_order( $order_id ) : false;
		if ( ! $order || ! hash_equals( $order->get_order_key(), (string) $key ) ) {
			return $content;
		}
		ob_start();
		self::front_box( $order );
		return $content . ob_get_clean();
	}

	private static function is_e2c( $order ) {
		return $order instanceof WC_Order && (bool) $order->get_meta( '_e2c_order' );
	}

	public static function needs_address( $needs, $hide = array(), $order = null ) {
		if ( self::is_e2c( $order ) ) {
			return true;
		}
		return $needs;
	}

	/** Filas con los datos extra que no forman parte de la dirección estándar. */
	private static function rows( $order ) {
		$rows = array();
		$name = trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() );
		if ( $name ) {
			$rows[ E2C_I18n::t( 'recipient' ) ] = $name;
		}
		if ( $order->get_shipping_phone() ) {
			$rows[ E2C_I18n::t( 'rec_mobile' ) ] = $order->get_shipping_phone();
		}
		$map = array(
			'_e2c_landline' => 'rec_landline',
			'_e2c_ci'       => 'rec_ci',
		);
		foreach ( $map as $meta => $label ) {
			$v = $order->get_meta( $meta );
			if ( '' !== (string) $v ) {
				$rows[ E2C_I18n::t( $label ) ] = $v;
			}
		}
		// Mismo orden que el checkout: provincia, municipio, dirección, reparto y referencias.
		$province = $order->get_meta( '_e2c_province' );
		if ( ! $province ) {
			$province = E2C_Data::province_name( $order->get_shipping_state() );
		}
		if ( $province ) {
			$rows[ E2C_I18n::t( 'province' ) ] = $province;
		}
		if ( $order->get_shipping_city() ) {
			$rows[ E2C_I18n::t( 'municipality' ) ] = $order->get_shipping_city();
		}
		$address = trim( $order->get_shipping_address_1() . ', ' . $order->get_shipping_address_2(), ', ' );
		if ( $address ) {
			$rows[ E2C_I18n::t( 'rec_address' ) ] = $address;
		}
		$reparto = $order->get_meta( '_e2c_reparto' );
		if ( '' !== (string) $reparto ) {
			$rows[ E2C_I18n::t( 'rec_reparto' ) ] = $reparto;
		}
		$refs = $order->get_meta( '_e2c_refs' );
		if ( '' !== (string) $refs ) {
			$rows[ E2C_I18n::t( 'rec_refs' ) ] = $refs;
		}
		$consent = (string) $order->get_meta( '_e2c_consent' );
		if ( '' !== $consent ) {
			$rows[ E2C_I18n::t( 'consent_short' ) ] = mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $consent );
		}
		return $rows;
	}

	public static function admin_box( $order ) {
		if ( ! self::is_e2c( $order ) ) {
			return;
		}
		echo '<div class="e2c-order-box" style="margin-top:12px;padding:10px 12px;border:1px solid #d9e1ec;border-radius:6px;background:#f5f7fa">';
		echo '<p style="margin:0 0 6px"><strong>' . esc_html( E2C_I18n::t( 'box_title' ) ) . '</strong></p>';
		foreach ( self::rows( $order ) as $label => $value ) {
			echo '<p style="margin:0 0 4px"><span style="color:#4a6688">' . esc_html( $label ) . ':</span> ' . nl2br( esc_html( $value ) ) . '</p>';
		}
		$inv_id = $order->get_meta( '_e2c_invoice_id' );
		if ( is_numeric( $inv_id ) && $inv_id > 0 && class_exists( 'E2C_Invoices' ) ) {
			echo '<p style="margin:6px 0 0"><span style="color:#4a6688">' . esc_html( E2C_I18n::t( 'rec_invoice' ) ) . ':</span> <a href="' . esc_url( E2C_Invoices::pdf_url( $inv_id ) ) . '" target="_blank" rel="noopener">' . esc_html( $order->get_meta( '_e2c_invoice_number' ) ) . '</a></p>';
		}
		echo '</div>';
	}

	public static function front_box( $order ) {
		if ( ! self::is_e2c( $order ) ) {
			return;
		}
		echo '<section class="e2c-order-details" style="margin-top:24px">';
		echo '<h2 class="woocommerce-column__title">' . esc_html( E2C_I18n::t( 'box_title' ) ) . '</h2>';
		echo '<table class="woocommerce-table shop_table e2c-details" style="width:100%;border-collapse:collapse"><tbody>';
		foreach ( self::rows( $order ) as $label => $value ) {
			echo '<tr><th scope="row" style="text-align:left;vertical-align:top;width:35%;padding:8px 12px 8px 0;border-bottom:1px solid rgba(0,0,0,.08);font-weight:600">' . esc_html( $label ) . '</th><td style="text-align:left;vertical-align:top;padding:8px 0;border-bottom:1px solid rgba(0,0,0,.08)">' . nl2br( esc_html( $value ) ) . '</td></tr>';
		}
		echo '</tbody></table></section>';
	}

	public static function email_box( $order, $sent_to_admin = false, $plain_text = false, $email = null ) {
		if ( ! self::is_e2c( $order ) ) {
			return;
		}
		$rows = self::rows( $order );
		if ( $plain_text ) {
			echo "\n" . esc_html( strtoupper( E2C_I18n::t( 'box_title' ) ) ) . "\n";
			foreach ( $rows as $label => $value ) {
				echo esc_html( $label ) . ': ' . esc_html( $value ) . "\n";
			}
			echo "\n";
			return;
		}
		echo '<div style="margin:0 0 32px">';
		echo '<h2>' . esc_html( E2C_I18n::t( 'box_title' ) ) . '</h2>';
		echo '<table cellspacing="0" cellpadding="6" border="1" style="width:100%;border-collapse:collapse;border:1px solid #e5e5e5">';
		foreach ( $rows as $label => $value ) {
			echo '<tr><th style="text-align:left;border:1px solid #e5e5e5;width:35%">' . esc_html( $label ) . '</th><td style="text-align:left;border:1px solid #e5e5e5">' . nl2br( esc_html( $value ) ) . '</td></tr>';
		}
		echo '</table></div>';
	}
}
