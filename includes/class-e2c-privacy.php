<?php
/**
 * Privacidad: exportar y borrar datos personales, texto para la política de privacidad
 * y borrado automático de facturas antiguas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class E2C_Privacy {

	const CRON = 'e2c_cleanup_invoices';

	/** Metadatos de Easy2Cuba con datos personales en el pedido. */
	private static function meta_keys() {
		return array(
			'_e2c_landline'      => 'rec_landline',
			'_e2c_ci'            => 'rec_ci',
			'_e2c_reparto'       => 'rec_reparto',
			'_e2c_refs'          => 'rec_refs',
			'_e2c_buyer_country' => 'c_label',
		);
	}

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'policy_text' ) );

		// Se suma a las herramientas de privacidad de WooCommerce (Herramientas › Exportar / Borrar datos personales).
		add_filter( 'woocommerce_privacy_export_order_personal_data', array( __CLASS__, 'export_order' ), 10, 2 );
		add_action( 'woocommerce_privacy_remove_order_personal_data', array( __CLASS__, 'erase_order' ) );

		// Borrado automático de facturas antiguas.
		add_action( self::CRON, array( __CLASS__, 'cleanup' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ) );
	}

	/* ------------------------------------------------------------------ */

	public static function policy_text() {
		if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
			return;
		}
		$p = array_map( 'esc_html', array( E2C_I18n::t( 'pp_1' ), E2C_I18n::t( 'pp_2' ), E2C_I18n::t( 'pp_3' ), E2C_I18n::t( 'pp_4' ) ) );
		$html  = '<h2>' . esc_html( E2C_I18n::t( 'pp_title' ) ) . '</h2>';
		$html .= '<p class="privacy-policy-tutorial">' . esc_html( E2C_I18n::t( 'pp_tutorial' ) ) . '</p>';
		$html .= '<p><strong class="privacy-policy-tutorial">' . esc_html( E2C_I18n::t( 'pp_suggested' ) ) . ' </strong>' . $p[0] . '</p>';
		$html .= '<p>' . $p[1] . '</p><p>' . $p[2] . '</p><p>' . $p[3] . '</p>';
		wp_add_privacy_policy_content( 'Easy2Cuba for WooCommerce', wp_kses_post( $html ) );
	}

	/** Añade los datos de Easy2Cuba a la exportación de cada pedido. */
	public static function export_order( $personal_data, $order ) {
		if ( ! $order instanceof WC_Order || ! $order->get_meta( '_e2c_order' ) ) {
			return $personal_data;
		}
		foreach ( self::meta_keys() as $meta => $label ) {
			$value = (string) $order->get_meta( $meta );
			if ( '' !== $value ) {
				$personal_data[] = array(
					'name'  => 'Easy2Cuba · ' . E2C_I18n::t( $label ),
					'value' => $value,
				);
			}
		}
		$number = (string) $order->get_meta( '_e2c_invoice_number' );
		if ( '' !== $number ) {
			$personal_data[] = array(
				'name'  => 'Easy2Cuba · ' . E2C_I18n::t( 'rec_invoice' ),
				'value' => $number,
			);
		}
		return $personal_data;
	}

	/** Cuando WooCommerce anonimiza un pedido, se borran también los datos y la factura de Easy2Cuba. */
	public static function erase_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		foreach ( array_keys( self::meta_keys() ) as $meta ) {
			$order->delete_meta_data( $meta );
		}
		$order->delete_meta_data( '_e2c_consent' );
		$inv = $order->get_meta( '_e2c_invoice_id' );
		if ( is_numeric( $inv ) && $inv > 0 ) {
			wp_delete_post( (int) $inv, true );
		}
		$order->save();
	}

	/* ------------------------------------------------------------------ */

	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON );
	}

	/** Borra del registro las facturas más antiguas que el plazo elegido. */
	public static function cleanup() {
		if ( ! class_exists( 'E2C_Invoices' ) ) {
			return 0;
		}
		$s      = E2C_Invoices::settings();
		$months = (int) $s['retention'];
		if ( $months <= 0 ) {
			return 0;
		}
		$deleted = 0;
		do {
			$ids = get_posts(
				array(
					'post_type'      => E2C_Invoices::CPT,
					'post_status'    => 'any',
					'fields'         => 'ids',
					'posts_per_page' => 100,
					'date_query'     => array(
						array(
							'before' => gmdate( 'Y-m-d H:i:s', strtotime( '-' . $months . ' months' ) ),
							'column' => 'post_date_gmt',
						),
					),
				)
			);
			foreach ( $ids as $id ) {
				wp_delete_post( $id, true );
				$deleted++;
			}
		} while ( count( $ids ) === 100 );
		return $deleted;
	}
}
