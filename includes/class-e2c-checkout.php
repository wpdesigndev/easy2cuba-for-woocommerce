<?php
/**
 * Checkout de Easy2Cuba: campos, plantillas, envío por provincia y validación.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class E2C_Checkout {

	const FEE_ID = 'e2c-shipping';

	/** Etiquetas de los campos, para los mensajes de error. */
	private static $labels = array();

	private static $assets_done = false;

	public static function init() {
		// Siempre: WooCommerce conoce las provincias de Cuba (para mostrar direcciones con su nombre).
		add_filter( 'woocommerce_states', array( __CLASS__, 'states' ) );

		if ( ! E2C_Data::enabled() ) {
			return;
		}

		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'fields' ), 9999 );
		add_filter( 'woocommerce_locate_template', array( __CLASS__, 'locate_template' ), 9999, 3 );
		add_filter( 'render_block', array( __CLASS__, 'render_block' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
		add_filter( 'body_class', array( __CLASS__, 'body_class' ) );

		add_filter( 'woocommerce_form_field_e2c_phone', array( __CLASS__, 'field_phone' ), 10, 4 );
		add_filter( 'woocommerce_form_field_e2c_cu_phone', array( __CLASS__, 'field_cu_phone' ), 10, 4 );
		add_filter( 'woocommerce_form_field', array( __CLASS__, 'add_hint' ), 10, 4 );
		add_filter( 'woocommerce_checkout_get_value', array( __CLASS__, 'get_value' ), 10, 2 );

		// El envío lo calcula Easy2Cuba según la provincia, no las zonas de envío de WooCommerce.
		add_filter( 'woocommerce_cart_needs_shipping', '__return_false', 99 );
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'update_review' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'on_process' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'add_fee' ) );

		add_filter( 'woocommerce_checkout_posted_data', array( __CLASS__, 'posted_data' ) );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'validate' ), 10, 2 );
		add_filter( 'woocommerce_checkout_required_field_notice', array( __CLASS__, 'required_notice' ), 10, 3 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'create_order' ), 10, 2 );
	}

	/* ------------------------------------------------------------------ */
	/* Datos de Cuba para WooCommerce                                      */
	/* ------------------------------------------------------------------ */

	public static function states( $states ) {
		$existing      = ( isset( $states['CU'] ) && is_array( $states['CU'] ) ) ? $states['CU'] : array();
		$states['CU']  = array_merge( $existing, E2C_Data::provinces() );
		return $states;
	}

	/* ------------------------------------------------------------------ */
	/* Campos                                                              */
	/* ------------------------------------------------------------------ */

	/** Provincia elegida en esta sesión (o enviada en el pedido). */
	public static function current_province() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifica el nonce del checkout.
		if ( isset( $_POST['shipping_state'], $_POST['woocommerce-process-checkout-nonce'] ) ) {
			$code = sanitize_text_field( wp_unslash( $_POST['shipping_state'] ) );
			return E2C_Data::is_active( $code ) ? $code : '';
		}
		// phpcs:enable
		if ( function_exists( 'WC' ) && WC()->session ) {
			$code = (string) WC()->session->get( 'e2c_province', '' );
			return E2C_Data::is_active( $code ) ? $code : '';
		}
		return '';
	}

	public static function get_value( $value, $input ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return $value;
		}
		if ( 'shipping_state' === $input ) {
			$code = self::current_province();
			if ( $code ) {
				return $code;
			}
		}
		if ( 'shipping_city' === $input ) {
			$city = (string) WC()->session->get( 'e2c_city', '' );
			if ( '' !== $city ) {
				return $city;
			}
		}
		return $value;
	}

	public static function price_text( $price ) {
		if ( (float) $price <= 0 ) {
			return E2C_I18n::t( 'free' );
		}
		return html_entity_decode( wp_strip_all_tags( wc_price( $price ) ), ENT_QUOTES, 'UTF-8' );
	}

	public static function zone_text( $code ) {
		if ( ! $code ) {
			return esc_html( E2C_I18n::t( 'zone_empty' ) );
		}
		return esc_html( E2C_I18n::t( 'zone_to' ) ) . ' <b>' . esc_html( E2C_Data::province_name( $code ) ) . '</b>: <b>' . esc_html( self::price_text( self::converted_price( E2C_Data::price( $code ) ) ) ) . '</b>';
	}

	public static function fields( $fields ) {
		$t = array( 'E2C_I18n', 't' );

		$fields['billing'] = array(
			'billing_first_name' => array(
				'label'        => call_user_func( $t, 'names' ),
				'placeholder'  => call_user_func( $t, 'ph_b_names' ),
				'required'     => true,
				'class'        => array( 'form-row-first' ),
				'autocomplete' => 'given-name',
				'priority'     => 10,
			),
			'billing_last_name'  => array(
				'label'        => call_user_func( $t, 'lastnames' ),
				'placeholder'  => call_user_func( $t, 'ph_b_last' ),
				'required'     => true,
				'class'        => array( 'form-row-last' ),
				'autocomplete' => 'family-name',
				'priority'     => 20,
			),
			'billing_email'      => array(
				'type'         => 'email',
				'label'        => call_user_func( $t, 'email' ),
				'placeholder'  => call_user_func( $t, 'ph_email' ),
				'required'     => true,
				'validate'     => array( 'email' ),
				'class'        => array( 'form-row-first' ),
				'autocomplete' => 'email',
				'priority'     => 30,
				'e2c_hint'     => esc_html( call_user_func( $t, 'email_hint' ) ),
			),
			'billing_phone'      => array(
				'type'         => 'e2c_phone',
				'label'        => call_user_func( $t, 'phone' ),
				'placeholder'  => call_user_func( $t, 'ph_b_phone' ),
				'required'     => false,
				'class'        => array( 'form-row-last' ),
				'autocomplete' => 'tel-national',
				'priority'     => 40,
			),
			'billing_address_1'  => array(
				'label'          => call_user_func( $t, 'address' ),
				'placeholder'    => call_user_func( $t, 'ph_address' ),
				'required'       => false,
				'class'          => array( 'form-row-wide' ),
				'autocomplete'   => 'street-address',
				'priority'       => 50,
				'e2c_hint'       => esc_html( call_user_func( $t, 'c_empty' ) ),
				'e2c_hint_class' => 'e2c-country-hint',
			),
		);

		// Provincia y municipio.
		$current   = self::current_province();
		$prov_opts = array( '' => call_user_func( $t, 'prov_pick' ) ) + E2C_Data::active_provinces();
		$all_mun   = E2C_Data::municipalities();
		if ( $current && isset( $all_mun[ $current ] ) ) {
			$mun_opts = array( '' => call_user_func( $t, 'mun_pick' ) ) + array_combine( $all_mun[ $current ], $all_mun[ $current ] );
		} else {
			$mun_opts = array( '' => call_user_func( $t, 'mun_first' ) );
		}

		$fields['e2c_recipient'] = array(
			'shipping_first_name' => array(
				'label'       => call_user_func( $t, 'names' ),
				'placeholder' => call_user_func( $t, 'ph_s_names' ),
				'required'    => true,
				'class'       => array( 'form-row-first' ),
				'priority'    => 10,
			),
			'shipping_last_name'  => array(
				'label'       => call_user_func( $t, 'lastnames' ),
				'placeholder' => call_user_func( $t, 'ph_s_last' ),
				'required'    => true,
				'class'       => array( 'form-row-last' ),
				'priority'    => 20,
			),
			'shipping_phone'      => array(
				'type'        => 'e2c_cu_phone',
				'label'       => call_user_func( $t, 'mobile' ),
				'placeholder' => '5 123 4567',
				'required'    => true,
				'class'       => array( 'form-row-first' ),
				'priority'    => 30,
				'e2c_hint'    => esc_html( call_user_func( $t, 'mobile_hint' ) ),
			),
			'e2c_landline'        => array(
				'type'        => 'e2c_cu_phone',
				'label'       => call_user_func( $t, 'landline' ),
				'placeholder' => '7 832 1234',
				'required'    => false,
				'class'       => array( 'form-row-last' ),
				'priority'    => 40,
				'e2c_hint'    => esc_html( call_user_func( $t, 'land_hint' ) ),
			),
			'e2c_ci'              => array(
				'label'             => call_user_func( $t, 'ci' ),
				'placeholder'       => '85010112345',
				'required'          => false,
				'class'             => array( 'form-row-wide' ),
				'priority'          => 50,
				'custom_attributes' => array(
					'maxlength' => '11',
					'inputmode' => 'numeric',
				),
				'e2c_hint'          => esc_html( call_user_func( $t, 'ci_hint' ) ),
			),
			'shipping_state'      => array(
				'type'        => 'select',
				'label'       => call_user_func( $t, 'province' ),
				'required'    => true,
				'class'       => array( 'form-row-first', 'update_totals_on_change' ),
				'input_class' => array( 'e2c-select' ),
				'options'     => $prov_opts,
				'priority'    => 60,
				'e2c_hint'    => self::zone_text( $current ),
				'e2c_hint_id' => 'e2c-zone-hint',
			),
			'shipping_city'       => array(
				'type'        => 'select',
				'label'       => call_user_func( $t, 'municipality' ),
				'required'    => true,
				'class'       => array( 'form-row-last' ),
				'input_class' => array( 'e2c-select' ),
				'options'     => $mun_opts,
				'priority'    => 70,
			),
			'shipping_address_1'  => array(
				'label'       => call_user_func( $t, 'street' ),
				'placeholder' => call_user_func( $t, 'ph_street' ),
				'required'    => true,
				'class'       => array( 'form-row-wide' ),
				'priority'    => 80,
			),
			'shipping_address_2'  => array(
				'label'       => call_user_func( $t, 'between' ),
				'placeholder' => call_user_func( $t, 'ph_between' ),
				'required'    => true,
				'class'       => array( 'form-row-first' ),
				'priority'    => 90,
			),
			'e2c_reparto'         => array(
				'label'       => call_user_func( $t, 'reparto' ),
				'placeholder' => call_user_func( $t, 'ph_reparto' ),
				'required'    => false,
				'class'       => array( 'form-row-last' ),
				'priority'    => 100,
			),
			'e2c_refs'            => array(
				'type'        => 'textarea',
				'label'       => call_user_func( $t, 'refs' ),
				'placeholder' => call_user_func( $t, 'ph_refs' ),
				'required'    => false,
				'class'       => array( 'form-row-wide' ),
				'priority'    => 110,
			),
		);

		$settings = E2C_Data::settings();
		if ( ! empty( $settings['consent'] ) ) {
			$fields['e2c_recipient']['e2c_consent'] = array(
				'type'     => 'checkbox',
				'label'    => call_user_func( $t, 'consent' ),
				'required' => true,
				'class'    => array( 'form-row-wide', 'e2c-consent' ),
				'priority' => 120,
			);
		}

		// Sin la sección de envío estándar: la sustituye "Quién recibe en Cuba".
		unset( $fields['shipping'] );

		if ( isset( $fields['order']['order_comments'] ) ) {
			$fields['order']['order_comments']['label']       = call_user_func( $t, 'notes' );
			$fields['order']['order_comments']['placeholder'] = call_user_func( $t, 'ph_notes' );
		}

		self::$labels = array();
		$who          = array(
			'billing'       => E2C_I18n::t( 'who_buys' ),
			'e2c_recipient' => E2C_I18n::t( 'who_gets' ),
		);
		foreach ( $who as $set => $suffix ) {
			foreach ( $fields[ $set ] as $key => $f ) {
				self::$labels[ $key ] = $f['label'] . ' (' . $suffix . ')';
			}
		}

		return $fields;
	}

	/** Etiqueta con "(opcional)" o asterisco, igual que WooCommerce. */
	private static function label_html( $key, $args ) {
		if ( empty( $args['label'] ) ) {
			return '';
		}
		$mark = $args['required']
			? '&nbsp;<abbr class="required" title="' . esc_attr__( 'required', 'woocommerce' ) . '">*</abbr>'
			: '&nbsp;<span class="optional">(' . esc_html__( 'optional', 'woocommerce' ) . ')</span>';
		return '<label for="' . esc_attr( $key ) . '">' . esc_html( $args['label'] ) . $mark . '</label>';
	}

	private static function row_open( $key, $args ) {
		$classes = isset( $args['class'] ) ? (array) $args['class'] : array();
		$classes[] = 'form-row';
		if ( ! empty( $args['required'] ) ) {
			$classes[] = 'validate-required';
		}
		$priority = isset( $args['priority'] ) ? (int) $args['priority'] : 0;
		return '<p class="' . esc_attr( implode( ' ', array_unique( $classes ) ) ) . '" id="' . esc_attr( $key ) . '_field" data-priority="' . esc_attr( $priority ) . '">';
	}

	/** Teléfono del comprador: prefijo libre + número. */
	public static function field_phone( $field, $key, $args, $value ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$prefix = isset( $_POST['e2c_phone_prefix'] ) ? E2C_Data::digits( wp_unslash( $_POST['e2c_phone_prefix'] ) ) : '';
		$number = (string) $value;
		if ( '' === $prefix && preg_match( '/^\+(\d{1,4})\s+(.*)$/', $number, $m ) ) {
			$prefix = $m[1];
			$number = $m[2];
		}

		$html  = self::row_open( $key, $args );
		$html .= self::label_html( $key, $args );
		$html .= '<span class="woocommerce-input-wrapper e2c-phone">';
		$html .= '<input type="tel" class="input-text e2c-prefix" name="e2c_phone_prefix" id="e2c_phone_prefix" value="' . esc_attr( $prefix ? '+' . $prefix : '' ) . '" maxlength="5" inputmode="tel" autocomplete="tel-country-code" aria-label="' . esc_attr( E2C_I18n::t( 'prefix' ) ) . '" />';
		$html .= '<input type="tel" class="input-text" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $number ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" autocomplete="tel-national" />';
		$html .= '</span>';
		$html .= '<span class="e2c-hint e2c-country-hint">' . esc_html( E2C_I18n::t( 'c_empty' ) ) . '</span>';
		$html .= '</p>';
		return $html;
	}

	/** Teléfono cubano: +53 fijo + número. */
	public static function field_cu_phone( $field, $key, $args, $value ) {
		$number = preg_replace( '/^\+53\s*/', '', (string) $value );

		$html  = self::row_open( $key, $args );
		$html .= self::label_html( $key, $args );
		$html .= '<span class="woocommerce-input-wrapper e2c-phone">';
		$html .= '<span class="e2c-cc" aria-hidden="true">+53</span>';
		$html .= '<input type="tel" class="input-text" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $number ) . '" placeholder="' . esc_attr( $args['placeholder'] ) . '" inputmode="tel" />';
		$html .= '</span>';
		$html .= '</p>';
		return $html;
	}

	/** Añade la descripción fija debajo del campo (siempre visible). */
	public static function add_hint( $field, $key, $args, $value ) {
		if ( empty( $args['e2c_hint'] ) || '' === $field ) {
			return $field;
		}
		$class = 'e2c-hint' . ( ! empty( $args['e2c_hint_class'] ) ? ' ' . $args['e2c_hint_class'] : '' );
		$id    = ! empty( $args['e2c_hint_id'] ) ? ' id="' . esc_attr( $args['e2c_hint_id'] ) . '"' : '';
		$hint  = '<span class="' . esc_attr( $class ) . '"' . $id . '>' . wp_kses( $args['e2c_hint'], array( 'b' => array() ) ) . '</span>';
		$pos   = strrpos( $field, '</p>' );
		if ( false === $pos ) {
			return $field . $hint;
		}
		return substr( $field, 0, $pos ) . $hint . substr( $field, $pos );
	}

	/* ------------------------------------------------------------------ */
	/* Plantillas y recursos                                               */
	/* ------------------------------------------------------------------ */

	public static function locate_template( $template, $name, $path ) {
		$ours = array(
			'checkout/form-checkout.php',
			'checkout/form-billing.php',
			'checkout/form-shipping.php',
			'checkout/review-order.php',
		);
		if ( in_array( $name, $ours, true ) ) {
			$file = E2C_PATH . 'templates/' . $name;
			if ( file_exists( $file ) ) {
				return $file;
			}
		}
		return $template;
	}

	/** Si la página usa el bloque de checkout, se muestra el checkout de Easy2Cuba en su lugar. */
	public static function render_block( $content, $block ) {
		if ( is_admin() || empty( $block['blockName'] ) || 'woocommerce/checkout' !== $block['blockName'] ) {
			return $content;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) {
			return $content;
		}
		self::enqueue_assets( true );
		return do_shortcode( '[woocommerce_checkout]' );
	}

	public static function body_class( $classes ) {
		$s = E2C_Data::settings();
		if ( ! empty( $s['wide'] ) && function_exists( 'is_checkout' ) && is_checkout() ) {
			$classes[] = 'e2c-wide';
		}
		return $classes;
	}

	public static function enqueue() {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
			return;
		}
		if ( is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) {
			return;
		}
		self::enqueue_assets( false );
	}

	public static function enqueue_assets( $force_wc ) {
		if ( self::$assets_done ) {
			return;
		}
		self::$assets_done = true;

		if ( $force_wc ) {
			wp_enqueue_script( 'wc-checkout' );
		}

		wp_enqueue_style( 'e2c-fonts', 'https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap', array(), null );
		wp_enqueue_style( 'e2c-checkout', E2C_URL . 'assets/css/e2c-checkout.css', array(), E2C_VERSION );
		wp_enqueue_script( 'e2c-checkout', E2C_URL . 'assets/js/e2c-checkout.js', array( 'jquery', 'wc-checkout' ), E2C_VERSION, true );

		$all_mun   = E2C_Data::municipalities();
		$provinces = array();
		foreach ( E2C_Data::active_provinces() as $code => $name ) {
			$provinces[ $code ] = array(
				'name'           => $name,
				'price_text'     => self::price_text( self::converted_price( E2C_Data::price( $code ) ) ),
				'municipalities' => $all_mun[ $code ],
			);
		}

		$keys = array( 'c_empty', 'c_unknown', 'c_label', 'c_usca', 'or', 'zone_empty', 'zone_to', 'mun_pick', 'mun_first', 'e_req_js', 'e_email', 'e_cu', 'e_ci', 'e_pref', 'e_consent' );
		$i18n = array();
		foreach ( $keys as $k ) {
			$i18n[ $k ] = E2C_I18n::t( $k );
		}

		wp_localize_script(
			'e2c-checkout',
			'e2cCheckout',
			array(
				'lang'      => E2C_I18n::lang(),
				'provinces' => $provinces,
				'prefixes'  => E2C_Data::prefixes(),
				'base'      => WC()->countries->get_base_country(),
				'i18n'      => $i18n,
			)
		);
	}

	/* ------------------------------------------------------------------ */
	/* Costo de envío por provincia                                        */
	/* ------------------------------------------------------------------ */

	public static function update_review( $post_data ) {
		if ( ! WC()->session ) {
			return;
		}
		$d = array();
		parse_str( (string) $post_data, $d );
		$code = isset( $d['shipping_state'] ) ? sanitize_text_field( $d['shipping_state'] ) : '';
		$city = isset( $d['shipping_city'] ) ? sanitize_text_field( $d['shipping_city'] ) : '';
		WC()->session->set( 'e2c_province', E2C_Data::is_active( $code ) ? $code : '' );
		WC()->session->set( 'e2c_city', $city );
	}

	public static function on_process() {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifica el nonce del checkout.
		$code = isset( $_POST['shipping_state'] ) ? sanitize_text_field( wp_unslash( $_POST['shipping_state'] ) ) : '';
		if ( WC()->session ) {
			WC()->session->set( 'e2c_province', E2C_Data::is_active( $code ) ? $code : '' );
		}
	}

	private static function cart_needs_delivery( $cart ) {
		foreach ( $cart->get_cart() as $item ) {
			if ( isset( $item['data'] ) && is_object( $item['data'] ) && $item['data']->needs_shipping() ) {
				return true;
			}
		}
		return false;
	}

	/** Convierte el precio de envío si hay un plugin de multimoneda activo (WPML/WCML, CURCY, filtros genéricos). */
	public static function converted_price( $amount ) {
		$amount = (float) apply_filters( 'wcml_raw_price_amount', $amount );
		if ( function_exists( 'wmc_get_price' ) ) {
			$amount = (float) wmc_get_price( $amount );
		}
		return (float) apply_filters( 'e2c_shipping_amount', $amount );
	}

	public static function add_fee( $cart ) {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
		if ( ! self::cart_needs_delivery( $cart ) ) {
			return;
		}
		$code = self::current_province();
		if ( ! $code ) {
			return;
		}
		$cart->fees_api()->add_fee(
			array(
				'id'        => self::FEE_ID,
				'name'      => sprintf( E2C_I18n::t( 'fee_label' ), E2C_Data::province_name( $code ) ),
				'amount'    => self::converted_price( E2C_Data::price( $code ) ),
				'taxable'   => false,
				'tax_class' => '',
			)
		);
	}

	/* ------------------------------------------------------------------ */
	/* Al enviar el pedido                                                 */
	/* ------------------------------------------------------------------ */

	public static function posted_data( $data ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifica el nonce del checkout.
		$prefix = isset( $_POST['e2c_phone_prefix'] ) ? E2C_Data::digits( wp_unslash( $_POST['e2c_phone_prefix'] ) ) : '';
		$iso    = E2C_Data::country_from_prefix( $prefix );
		// phpcs:enable

		// El país real del comprador sale del prefijo. Si no hay prefijo, WooCommerce y las pasarelas
		// de pago reciben el país de la tienda para no fallar, pero la factura lo deja en blanco.
		$data['e2c_buyer_country'] = $iso;
		$data['billing_country']   = $iso ? $iso : WC()->countries->get_base_country();
		if ( ! empty( $data['billing_phone'] ) && $prefix ) {
			$data['billing_phone'] = '+' . $prefix . ' ' . $data['billing_phone'];
		}

		$data['shipping_country'] = 'CU';
		foreach ( array( 'shipping_phone', 'e2c_landline' ) as $k ) {
			if ( ! empty( $data[ $k ] ) ) {
				$n = E2C_Data::cuban_number( $data[ $k ] );
				if ( $n ) {
					$data[ $k ] = '+53 ' . $n;
				}
			}
		}
		if ( isset( $data['e2c_ci'] ) ) {
			$data['e2c_ci'] = E2C_Data::digits( $data['e2c_ci'] );
		}
		return $data;
	}

	public static function validate( $data, $errors ) {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifica el nonce del checkout.
		$prefix = isset( $_POST['e2c_phone_prefix'] ) ? E2C_Data::digits( wp_unslash( $_POST['e2c_phone_prefix'] ) ) : '';
		$bphone = isset( $_POST['billing_phone'] ) ? trim( wc_clean( wp_unslash( $_POST['billing_phone'] ) ) ) : '';
		$mobile = isset( $_POST['shipping_phone'] ) ? trim( wc_clean( wp_unslash( $_POST['shipping_phone'] ) ) ) : '';
		$land   = isset( $_POST['e2c_landline'] ) ? trim( wc_clean( wp_unslash( $_POST['e2c_landline'] ) ) ) : '';
		$ci     = isset( $_POST['e2c_ci'] ) ? trim( wc_clean( wp_unslash( $_POST['e2c_ci'] ) ) ) : '';
		// phpcs:enable

		if ( '' !== $bphone && ! E2C_Data::country_from_prefix( $prefix ) ) {
			$errors->add( 'billing_phone_validation', E2C_I18n::t( 'e_pref' ), array( 'id' => 'billing_phone' ) );
		}
		if ( '' !== $mobile && ! E2C_Data::cuban_number( $mobile ) ) {
			$errors->add( 'shipping_phone_validation', '<strong>' . esc_html( E2C_I18n::t( 'mobile' ) ) . ':</strong> ' . esc_html( E2C_I18n::t( 'e_cu' ) ), array( 'id' => 'shipping_phone' ) );
		}
		if ( '' !== $land && ! E2C_Data::cuban_number( $land ) ) {
			$errors->add( 'e2c_landline_validation', '<strong>' . esc_html( E2C_I18n::t( 'landline' ) ) . ':</strong> ' . esc_html( E2C_I18n::t( 'e_cu' ) ), array( 'id' => 'e2c_landline' ) );
		}
		if ( '' !== $ci && ! preg_match( '/^\d{11}$/', E2C_Data::digits( $ci ) ) ) {
			$errors->add( 'e2c_ci_validation', esc_html( E2C_I18n::t( 'e_ci' ) ), array( 'id' => 'e2c_ci' ) );
		}

		$code = isset( $data['shipping_state'] ) ? (string) $data['shipping_state'] : '';
		if ( '' !== $code && ! E2C_Data::is_active( $code ) ) {
			$errors->add( 'shipping_state_validation', esc_html( E2C_I18n::t( 'e_prov' ) ), array( 'id' => 'shipping_state' ) );
		} elseif ( '' !== $code ) {
			$all  = E2C_Data::municipalities();
			$city = isset( $data['shipping_city'] ) ? (string) $data['shipping_city'] : '';
			if ( '' !== $city && ! in_array( $city, $all[ $code ], true ) ) {
				$errors->add( 'shipping_city_validation', esc_html( E2C_I18n::t( 'e_mun' ) ), array( 'id' => 'shipping_city' ) );
			}
		}
	}

	public static function required_notice( $notice, $label = '', $key = '' ) {
		if ( 'e2c_consent' === $key ) {
			return esc_html( E2C_I18n::t( 'e_consent' ) );
		}
		if ( $key && isset( self::$labels[ $key ] ) ) {
			$label = self::$labels[ $key ];
		}
		return sprintf( E2C_I18n::t( 'e_req' ), '<strong>' . esc_html( wp_strip_all_tags( $label ) ) . '</strong>' );
	}

	public static function create_order( $order, $data ) {
		$order->update_meta_data( '_e2c_order', 1 );
		$order->update_meta_data( '_e2c_buyer_country', isset( $data['e2c_buyer_country'] ) ? $data['e2c_buyer_country'] : '' );
		if ( ! empty( $data['e2c_consent'] ) ) {
			$order->update_meta_data( '_e2c_consent', current_time( 'mysql' ) );
		}
		foreach ( array( 'e2c_landline', 'e2c_ci', 'e2c_reparto', 'e2c_refs' ) as $k ) {
			if ( isset( $data[ $k ] ) && '' !== $data[ $k ] ) {
				$order->update_meta_data( '_' . $k, $data[ $k ] );
			}
		}
		if ( ! empty( $data['shipping_state'] ) ) {
			$order->update_meta_data( '_e2c_province', E2C_Data::province_name( $data['shipping_state'] ) );
		}
		if ( WC()->session ) {
			WC()->session->set( 'e2c_province', '' );
			WC()->session->set( 'e2c_city', '' );
		}
	}
}
