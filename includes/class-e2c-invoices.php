<?php
/**
 * Facturas en PDF: se generan y envían cuando se confirma el pago, y quedan registradas.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class E2C_Invoices {

	const CPT     = 'e2c_invoice';
	const OPTION  = 'e2c_invoice_settings';
	const COUNTER = 'e2c_fac_next';
	const PREFIX  = 'FAC-';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'woocommerce_order_status_changed', array( __CLASS__, 'on_status' ), 20, 4 );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'maybe_send' ), 20 );
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'public'              => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title' ),
				'label'               => 'Easy2Cuba',
			)
		);
	}

	/* ------------------------------------------------------------------ */
	/* Ajustes                                                             */
	/* ------------------------------------------------------------------ */

	public static function defaults() {
		return array(
			'enabled' => 1,
			'email'   => get_option( 'admin_email' ),
			'footer'  => '',
			'logo_id' => 0,
			'smtp_on'     => 0,
			'smtp_host'   => '',
			'smtp_port'   => 587,
			'smtp_secure' => 'tls',
			'smtp_user'   => '',
			'smtp_pass'   => '',
			'from_email'  => '',
			'from_name'   => '',
			'retention'   => 0,
		);
	}

	/** Último error de envío (para mostrarlo en el panel). */
	public static $last_error = '';

	public static function capture_error( $error ) {
		if ( is_wp_error( $error ) ) {
			self::$last_error = $error->get_error_message();
		}
	}

	/** Configura PHPMailer con el SMTP de Easy2Cuba (solo para nuestros correos). */
	public static function phpmailer_smtp( $phpmailer ) {
		$s = self::settings();
		$phpmailer->isSMTP();
		$phpmailer->Host       = $s['smtp_host'];
		$phpmailer->Port       = (int) $s['smtp_port'];
		$phpmailer->SMTPSecure = in_array( $s['smtp_secure'], array( 'tls', 'ssl' ), true ) ? $s['smtp_secure'] : '';
		$phpmailer->SMTPAutoTLS = 'tls' === $s['smtp_secure'];
		$phpmailer->SMTPAuth   = '' !== $s['smtp_user'];
		$phpmailer->Username   = $s['smtp_user'];
		$phpmailer->Password   = $s['smtp_pass'];
		$phpmailer->Timeout    = 20;
	}

	public static function mail_from( $from ) {
		$s = self::settings();
		if ( ! empty( $s['from_email'] ) && is_email( $s['from_email'] ) ) {
			return $s['from_email'];
		}
		if ( ! empty( $s['smtp_on'] ) && is_email( $s['smtp_user'] ) ) {
			return $s['smtp_user']; // Muchos servidores SMTP exigen que el remitente sea la propia cuenta.
		}
		return $from;
	}

	public static function mail_from_name( $name ) {
		$s = self::settings();
		return ! empty( $s['from_name'] ) ? $s['from_name'] : wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	}

	public static function settings() {
		$s = get_option( self::OPTION, array() );
		return wp_parse_args( is_array( $s ) ? $s : array(), self::defaults() );
	}

	public static function default_footer() {
		return sprintf( E2C_I18n::t( 'pdf_copy_def' ), wp_date( 'Y' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	}

	public static function footer_text() {
		$s = self::settings();
		return '' !== trim( (string) $s['footer'] ) ? (string) $s['footer'] : self::default_footer();
	}

	/** Lista de correos válidos a partir de un texto separado por comas. */
	public static function recipients( $text ) {
		$out = array();
		foreach ( preg_split( '/[,;\s]+/', (string) $text ) as $mail ) {
			$mail = sanitize_email( $mail );
			if ( $mail && is_email( $mail ) ) {
				$out[] = $mail;
			}
		}
		return array_values( array_unique( $out ) );
	}

	/** Ruta local del logo para el PDF (solo PNG o JPG), o ''. */
	public static function logo_path() {
		$s  = self::settings();
		$id = (int) $s['logo_id'];
		if ( ! $id ) {
			return '';
		}
		$file = get_attached_file( $id );
		if ( ! $file || ! file_exists( $file ) ) {
			return '';
		}
		$type = wp_check_filetype( $file );
		if ( in_array( $type['ext'], array( 'png', 'jpg', 'jpeg' ), true ) ) {
			return $file;
		}
		// WebP o GIF: se convierte a PNG (el PDF solo admite PNG y JPG).
		$png = trailingslashit( get_temp_dir() ) . 'e2c-logo-' . $id . '-' . filemtime( $file ) . '.png';
		if ( file_exists( $png ) ) {
			return $png;
		}
		$img = false;
		if ( 'webp' === $type['ext'] && function_exists( 'imagecreatefromwebp' ) ) {
			$img = @imagecreatefromwebp( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		} elseif ( 'gif' === $type['ext'] && function_exists( 'imagecreatefromgif' ) ) {
			$img = @imagecreatefromgif( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		if ( $img ) {
			imagesavealpha( $img, true );
			$ok = imagepng( $img, $png );
			imagedestroy( $img );
			if ( $ok ) {
				return $png;
			}
		}
		return '';
	}

	/* ------------------------------------------------------------------ */
	/* Envío automático                                                    */
	/* ------------------------------------------------------------------ */

	public static function on_status( $order_id, $from, $to, $order = null ) {
		$paid = function_exists( 'wc_get_is_paid_statuses' ) ? wc_get_is_paid_statuses() : array( 'processing', 'completed' );
		if ( in_array( $to, $paid, true ) ) {
			self::maybe_send( $order_id );
		}
	}

	public static function maybe_send( $order_id ) {
		$s = self::settings();
		if ( empty( $s['enabled'] ) ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->get_meta( '_e2c_order' ) ) {
			return;
		}
		if ( $order->get_meta( '_e2c_invoice_id' ) ) {
			return; // Ya enviada (o en proceso).
		}
		// Se marca antes de enviar para no mandarla dos veces si llegan dos avisos de pago seguidos.
		$order->update_meta_data( '_e2c_invoice_id', 'pending' );
		$order->save_meta_data();

		self::send_for_order( $order );
	}

	public static function next_number() {
		$n = max( 1, (int) get_option( self::COUNTER, 1 ) );
		update_option( self::COUNTER, $n + 1, false );
		return self::PREFIX . str_pad( (string) $n, 4, '0', STR_PAD_LEFT );
	}

	public static function send_for_order( $order ) {
		$s      = self::settings();
		$to     = self::recipients( $s['email'] );
		if ( ! $to ) {
			$to = self::recipients( get_option( 'admin_email' ) );
		}
		$number = self::next_number();
		$data   = self::build_data( $order, $number );
		$ok     = self::mail( $to, $data );

		$post_id = wp_insert_post(
			array(
				'post_type'   => self::CPT,
				'post_status' => 'publish',
				'post_title'  => $number,
			)
		);
		if ( $post_id && ! is_wp_error( $post_id ) ) {
			update_post_meta( $post_id, '_e2c_order_id', $order->get_id() );
			update_post_meta( $post_id, '_e2c_sent_to', implode( ', ', $to ) );
			update_post_meta( $post_id, '_e2c_sent_ok', $ok ? 1 : 0 );
			update_post_meta( $post_id, '_e2c_data', wp_slash( $data ) );
			$order->update_meta_data( '_e2c_invoice_id', $post_id );
			$order->update_meta_data( '_e2c_invoice_number', $number );
			$order->save_meta_data();
		}

		$order->add_order_note( sprintf( E2C_I18n::t( $ok ? 'note_sent' : 'note_fail' ), $number, implode( ', ', $to ) ) );
		return $ok;
	}

	/* ------------------------------------------------------------------ */
	/* Datos de la factura                                                 */
	/* ------------------------------------------------------------------ */

	private static function money( $amount, $currency ) {
		$txt = html_entity_decode( wp_strip_all_tags( wc_price( $amount, array( 'currency' => $currency ) ) ), ENT_QUOTES, 'UTF-8' );
		return trim( str_replace( "\xC2\xA0", ' ', $txt ) );
	}

	private static function store() {
		$url = wp_parse_url( home_url(), PHP_URL_HOST );
		return array(
			'name' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'url'  => $url ? $url : home_url(),
		);
	}

	public static function build_data( $order, $number ) {
		$cur = $order->get_currency();

		$items = array();
		foreach ( $order->get_items() as $item ) {
			$qty   = (int) $item->get_quantity();
			$total = (float) $item->get_subtotal();
			$items[] = array(
				'name'  => wp_strip_all_tags( $item->get_name() ),
				'qty'   => (string) $qty,
				'unit'  => self::money( $qty ? $total / $qty : $total, $cur ),
				'total' => self::money( $total, $cur ),
			);
		}

		$totals   = array();
		$totals[] = array( 'label' => E2C_I18n::t( 'subtotal' ), 'value' => self::money( $order->get_subtotal(), $cur ) );
		if ( (float) $order->get_discount_total() > 0 ) {
			$totals[] = array( 'label' => E2C_I18n::t( 'pdf_discount' ), 'value' => '-' . self::money( $order->get_discount_total(), $cur ) );
		}
		$province = (string) $order->get_meta( '_e2c_province' );
		foreach ( $order->get_fees() as $fee ) {
			$is_ship = 0 === strpos( sanitize_title( $fee->get_name() ), sanitize_title( sprintf( E2C_I18n::t( 'fee_label' ), '' ) ) )
				|| ( $province && false !== strpos( $fee->get_name(), $province ) );
			$label   = $is_ship ? E2C_I18n::t( 'shipping' ) . ( $province ? ' · ' . $province : '' ) : $fee->get_name();
			$value   = ( (float) $fee->get_total() <= 0 && $is_ship ) ? E2C_I18n::t( 'free' ) : self::money( $fee->get_total(), $cur );
			$totals[] = array( 'label' => $label, 'value' => $value );
		}
		if ( (float) $order->get_shipping_total() > 0 ) {
			$totals[] = array( 'label' => E2C_I18n::t( 'shipping' ), 'value' => self::money( $order->get_shipping_total(), $cur ) );
		}
		if ( (float) $order->get_total_tax() > 0 ) {
			$totals[] = array( 'label' => E2C_I18n::t( 'pdf_tax' ), 'value' => self::money( $order->get_total_tax(), $cur ) );
		}
		$totals[] = array( 'label' => E2C_I18n::t( 'total' ), 'value' => self::money( $order->get_total(), $cur ), 'strong' => 1 );

		$country_code = $order->meta_exists( '_e2c_buyer_country' ) ? (string) $order->get_meta( '_e2c_buyer_country' ) : $order->get_billing_country();
		$countries    = WC()->countries ? WC()->countries->get_countries() : array();
		$country      = ( $country_code && isset( $countries[ $country_code ] ) ) ? html_entity_decode( $countries[ $country_code ], ENT_QUOTES, 'UTF-8' ) : '';
		$country      = trim( preg_replace( '/\s*\([A-Z]{2,3}\)$/', '', $country ) );

		$date = $order->get_date_paid() ? $order->get_date_paid() : $order->get_date_created();

		return array(
			'test'         => 0,
			'number'       => $number,
			'order_number' => $order->get_order_number(),
			'date'         => $date ? wc_format_datetime( $date, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) : wp_date( 'Y-m-d H:i' ),
			'store'        => self::store(),
			'footer'       => self::footer_text(),
			'rec'          => array(
				'name'    => trim( $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name() ),
				'mobile'  => $order->get_shipping_phone(),
				'land'    => (string) $order->get_meta( '_e2c_landline' ),
				'ci'      => (string) $order->get_meta( '_e2c_ci' ),
				'street'  => $order->get_shipping_address_1(),
				'between' => $order->get_shipping_address_2(),
				'reparto' => (string) $order->get_meta( '_e2c_reparto' ),
				'mun'     => $order->get_shipping_city(),
				'prov'    => $province ? $province : E2C_Data::province_name( $order->get_shipping_state() ),
				'refs'    => (string) $order->get_meta( '_e2c_refs' ),
			),
			'buyer'        => array(
				'name'    => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'email'   => $order->get_billing_email(),
				'phone'   => $order->get_billing_phone(),
				'country' => $country,
				'addr'    => $order->get_billing_address_1(),
			),
			'items'        => $items,
			'totals'       => $totals,
			'payment'      => wp_strip_all_tags( $order->get_payment_method_title() ),
			'notes'        => $order->get_customer_note(),
			'total'        => self::money( $order->get_total(), $cur ),
		);
	}

	/** Factura de ejemplo para la prueba de envío. */
	public static function sample_data() {
		$cur = get_woocommerce_currency();
		return array(
			'test'         => 1,
			'number'       => self::PREFIX . '0000',
			'order_number' => '1000',
			'date'         => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'store'        => self::store(),
			'footer'       => self::footer_text(),
			'rec'          => array(
				'name'    => 'Mercedes Hernández Gil',
				'mobile'  => '+53 53987654',
				'land'    => '+53 72034567',
				'ci'      => '48120312345',
				'street'  => 'Calle 23 No. 456, apto 4B',
				'between' => 'entre L y M',
				'reparto' => 'Vedado',
				'mun'     => 'Plaza de la Revolución',
				'prov'    => 'La Habana',
				'refs'    => 'Edificio con rejas verdes frente al parque. Tocar fuerte.',
			),
			'buyer'        => array(
				'name'    => 'Lisandra Pérez Hernández',
				'email'   => 'lisy.ph@gmail.com',
				'phone'   => '+1 305 555 1234',
				'country' => E2C_I18n::lang() === 'es' ? 'Estados Unidos' : 'United States',
				'addr'    => '1250 SW 8th St, Miami, FL 33135',
			),
			'items'        => array(
				array( 'name' => 'Combo de aseo familiar', 'qty' => '1', 'unit' => self::money( 35, $cur ), 'total' => self::money( 35, $cur ) ),
				array( 'name' => 'Pollo troceado 4 kg', 'qty' => '2', 'unit' => self::money( 14, $cur ), 'total' => self::money( 28, $cur ) ),
				array( 'name' => 'Aceite de girasol 1 L', 'qty' => '3', 'unit' => self::money( 3, $cur ), 'total' => self::money( 9, $cur ) ),
			),
			'totals'       => array(
				array( 'label' => E2C_I18n::t( 'subtotal' ), 'value' => self::money( 72, $cur ) ),
				array( 'label' => E2C_I18n::t( 'shipping' ) . ' · La Habana', 'value' => self::money( 5, $cur ) ),
				array( 'label' => E2C_I18n::t( 'total' ), 'value' => self::money( 77, $cur ), 'strong' => 1 ),
			),
			'payment'      => 'PayPal',
			'notes'        => E2C_I18n::lang() === 'es' ? 'Entregar por la tarde. Es un regalo de cumpleaños.' : 'Deliver in the afternoon. It is a birthday gift.',
			'total'        => self::money( 77, $cur ),
		);
	}

	/** Enlace del panel para ver el PDF de una factura registrada. */
	public static function pdf_url( $post_id ) {
		return wp_nonce_url( admin_url( 'admin-post.php?action=e2c_pdf&id=' . (int) $post_id ), 'e2c_pdf_' . (int) $post_id );
	}

	public static function pdf( $data ) {
		require_once E2C_PATH . 'includes/class-e2c-pdf.php';
		return E2C_PDF::render( $data, self::logo_path() );
	}

	/* ------------------------------------------------------------------ */
	/* Correo                                                              */
	/* ------------------------------------------------------------------ */

	public static function mail( $to, $data ) {
		if ( ! $to ) {
			return false;
		}
		$bytes = self::pdf( $data );
		$dir   = trailingslashit( get_temp_dir() ) . 'e2c-' . wp_generate_password( 12, false, false );
		if ( ! wp_mkdir_p( $dir ) ) {
			return false;
		}
		$file = $dir . '/' . sanitize_file_name( $data['number'] ) . '.pdf';
		file_put_contents( $file, $bytes ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents

		$subject = sprintf( E2C_I18n::t( 'mail_subject' ), $data['number'], $data['order_number'], $data['store']['name'] );
		if ( ! empty( $data['test'] ) ) {
			$subject = E2C_I18n::t( 'mail_test_pre' ) . $subject;
		}

		$r    = $data['rec'];
		$rows = array(
			E2C_I18n::t( 'pdf_name' )     => $r['name'],
			E2C_I18n::t( 'rec_mobile' )   => $r['mobile'],
			E2C_I18n::t( 'province' )     => $r['prov'],
			E2C_I18n::t( 'municipality' ) => $r['mun'],
			E2C_I18n::t( 'total' )        => $data['total'],
		);
		$body  = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#14233b;line-height:1.5">';
		$body .= '<p>' . esc_html( E2C_I18n::t( ! empty( $data['test'] ) ? 'mail_test' : 'mail_intro' ) ) . '</p>';
		$body .= '<p style="margin:16px 0 6px;font-weight:bold;color:#04397a;text-transform:uppercase;font-size:12px;letter-spacing:.05em">' . esc_html( E2C_I18n::t( 'pdf_deliver' ) ) . '</p>';
		$body .= '<table cellpadding="4" cellspacing="0" style="border-collapse:collapse">';
		foreach ( $rows as $label => $value ) {
			if ( '' === (string) $value ) {
				continue;
			}
			$body .= '<tr><td style="color:#4a6688;padding-right:16px">' . esc_html( $label ) . '</td><td><strong>' . esc_html( $value ) . '</strong></td></tr>';
		}
		$body .= '</table>';
		$body .= '<p style="margin-top:20px;color:#4a6688;font-size:12px">' . esc_html( $data['footer'] ) . '</p></div>';

		$s                = self::settings();
		$use_smtp         = ! empty( $s['smtp_on'] ) && '' !== trim( (string) $s['smtp_host'] );
		self::$last_error = '';
		add_action( 'wp_mail_failed', array( __CLASS__, 'capture_error' ) );
		add_filter( 'wp_mail_from', array( __CLASS__, 'mail_from' ), 99 );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'mail_from_name' ), 99 );
		if ( $use_smtp ) {
			add_action( 'phpmailer_init', array( __CLASS__, 'phpmailer_smtp' ), 99 );
		}

		$ok = wp_mail( $to, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ), array( $file ) );

		remove_action( 'wp_mail_failed', array( __CLASS__, 'capture_error' ) );
		remove_filter( 'wp_mail_from', array( __CLASS__, 'mail_from' ), 99 );
		remove_filter( 'wp_mail_from_name', array( __CLASS__, 'mail_from_name' ), 99 );
		if ( $use_smtp ) {
			remove_action( 'phpmailer_init', array( __CLASS__, 'phpmailer_smtp' ), 99 );
		}

		wp_delete_file( $file );
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		return (bool) $ok;
	}
}
