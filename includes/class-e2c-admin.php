<?php
/**
 * Página de administración de Easy2Cuba: pestañas Envíos, Facturas e Información.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class E2C_Admin {

	const SLUG          = 'easy2cuba';
	const SUPPORT_EMAIL = 'easy2cubaforwoo@gmeti.com';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_e2c_save', array( __CLASS__, 'save' ) );
		add_action( 'admin_post_e2c_classic', array( __CLASS__, 'make_classic' ) );
		add_action( 'admin_post_e2c_save_inv', array( __CLASS__, 'save_invoices' ) );
		add_action( 'admin_post_e2c_test_inv', array( __CLASS__, 'test_invoice' ) );
		add_action( 'admin_post_e2c_pdf', array( __CLASS__, 'view_pdf' ) );
		add_action( 'admin_post_e2c_pdf_test', array( __CLASS__, 'view_test_pdf' ) );
		add_action( 'admin_post_e2c_del_inv', array( __CLASS__, 'delete_invoice' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_head', array( __CLASS__, 'menu_icon_css' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( E2C_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function menu() {
		add_menu_page(
			'Easy2Cuba for WooCommerce',
			'Easy2Cuba',
			'manage_woocommerce',
			self::SLUG,
			array( __CLASS__, 'render' ),
			E2C_URL . 'assets/img/menu-icon.png',
			56
		);
	}

	public static function menu_icon_css() {
		echo '<style>#adminmenu .toplevel_page_' . esc_attr( self::SLUG ) . ' .wp-menu-image img{width:20px;height:20px;padding:7px 0 0;opacity:1;border-radius:4px}</style>';
	}

	public static function action_links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( self::page_url() ) . '">' . esc_html( E2C_I18n::t( 'settings' ) ) . '</a>' );
		return $links;
	}

	private static function tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'shipping'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return in_array( $tab, array( 'shipping', 'invoices', 'info' ), true ) ? $tab : 'shipping';
	}

	public static function assets( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		if ( 'invoices' === self::tab() ) {
			wp_enqueue_media();
		}
		wp_enqueue_style( 'e2c-fonts', 'https://fonts.googleapis.com/css2?family=Sora:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap', array(), null );
		wp_enqueue_style( 'e2c-admin', E2C_URL . 'assets/css/e2c-admin.css', array(), E2C_VERSION );
		wp_enqueue_script( 'e2c-admin', E2C_URL . 'assets/js/e2c-admin.js', array( 'jquery' ), E2C_VERSION, true );
		wp_localize_script(
			'e2c-admin',
			'e2cAdmin',
			array(
				'pickLogo' => E2C_I18n::t( 'inv_logo' ),
				'useLogo'  => E2C_I18n::t( 'logo_use' ),
				'noLogo'   => E2C_I18n::t( 'no_logo' ),
				'sure'     => E2C_I18n::t( 'sure' ),
				'copied'   => E2C_I18n::t( 'copied' ),
			)
		);
	}

	private static function page_url( $args = array() ) {
		return add_query_arg( $args, admin_url( 'admin.php?page=' . self::SLUG ) );
	}

	private static function guard( $nonce_action ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
		}
		check_admin_referer( $nonce_action );
	}

	/* ------------------------------------------------------------------ */
	/* Acciones                                                            */
	/* ------------------------------------------------------------------ */

	public static function save() {
		self::guard( 'e2c_save' );

		$posted   = isset( $_POST['e2c'] ) && is_array( $_POST['e2c'] ) ? wp_unslash( $_POST['e2c'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- se sanea campo por campo abajo.
		$settings = array(
			'enabled'   => empty( $_POST['e2c_enabled'] ) ? 0 : 1,
			'wide'      => empty( $_POST['e2c_wide'] ) ? 0 : 1,
			'consent'   => empty( $_POST['e2c_consent'] ) ? 0 : 1,
			'provinces' => array(),
		);
		foreach ( array_keys( E2C_Data::provinces() ) as $code ) {
			$row   = isset( $posted[ $code ] ) && is_array( $posted[ $code ] ) ? $posted[ $code ] : array();
			$price = isset( $row['price'] ) ? wc_format_decimal( sanitize_text_field( $row['price'] ) ) : 0;
			$settings['provinces'][ $code ] = array(
				'active' => empty( $row['active'] ) ? 0 : 1,
				'price'  => max( 0, (float) $price ),
			);
		}
		update_option( E2C_Data::OPTION, $settings );

		wp_safe_redirect( self::page_url( array( 'e2c_msg' => 'saved' ) ) );
		exit;
	}

	public static function make_classic() {
		self::guard( 'e2c_classic' );
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.' ) );
		}
		$page_id = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
		if ( $page_id > 0 && get_post( $page_id ) ) {
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_content' => "<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->",
				)
			);
		}
		wp_safe_redirect( self::page_url( array( 'e2c_msg' => 'classic' ) ) );
		exit;
	}

	public static function save_invoices() {
		self::guard( 'e2c_save_inv' );

		$emails = E2C_Invoices::recipients( isset( $_POST['e2c_inv_email'] ) ? sanitize_text_field( wp_unslash( $_POST['e2c_inv_email'] ) ) : '' );
		$logo   = isset( $_POST['e2c_logo_id'] ) ? absint( $_POST['e2c_logo_id'] ) : 0;
		if ( $logo && ! wp_attachment_is_image( $logo ) ) {
			$logo = 0;
		}
		$old    = E2C_Invoices::settings();
		$secure = isset( $_POST['e2c_smtp_secure'] ) ? sanitize_key( wp_unslash( $_POST['e2c_smtp_secure'] ) ) : 'tls';
		$pass   = isset( $_POST['e2c_smtp_pass'] ) ? trim( wp_unslash( $_POST['e2c_smtp_pass'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- las contraseñas no se alteran.
		$from   = isset( $_POST['e2c_from_email'] ) ? sanitize_email( wp_unslash( $_POST['e2c_from_email'] ) ) : '';
		update_option(
			E2C_Invoices::OPTION,
			array(
				'enabled'     => empty( $_POST['e2c_inv_enabled'] ) ? 0 : 1,
				'email'       => $emails ? implode( ', ', $emails ) : get_option( 'admin_email' ),
				'footer'      => isset( $_POST['e2c_inv_footer'] ) ? sanitize_text_field( wp_unslash( $_POST['e2c_inv_footer'] ) ) : '',
				'logo_id'     => $logo,
				'smtp_on'     => empty( $_POST['e2c_smtp_on'] ) ? 0 : 1,
				'smtp_host'   => isset( $_POST['e2c_smtp_host'] ) ? sanitize_text_field( wp_unslash( $_POST['e2c_smtp_host'] ) ) : '',
				'smtp_port'   => isset( $_POST['e2c_smtp_port'] ) ? max( 1, absint( $_POST['e2c_smtp_port'] ) ) : 587,
				'smtp_secure' => in_array( $secure, array( 'tls', 'ssl', 'none' ), true ) ? $secure : 'tls',
				'smtp_user'   => isset( $_POST['e2c_smtp_user'] ) ? sanitize_text_field( wp_unslash( $_POST['e2c_smtp_user'] ) ) : '',
				'smtp_pass'   => '' !== $pass ? $pass : $old['smtp_pass'],
				'from_email'  => is_email( $from ) ? $from : '',
				'from_name'   => isset( $_POST['e2c_from_name'] ) ? sanitize_text_field( wp_unslash( $_POST['e2c_from_name'] ) ) : '',
				'retention'   => isset( $_POST['e2c_retention'] ) ? min( 240, absint( $_POST['e2c_retention'] ) ) : 0,
			)
		);
		wp_safe_redirect( self::page_url( array( 'tab' => 'invoices', 'e2c_msg' => 'inv_saved' ) ) );
		exit;
	}

	public static function test_invoice() {
		self::guard( 'e2c_test_inv' );
		$to = E2C_Invoices::recipients( isset( $_POST['e2c_test_email'] ) ? sanitize_text_field( wp_unslash( $_POST['e2c_test_email'] ) ) : '' );
		if ( ! $to ) {
			wp_safe_redirect( self::page_url( array( 'tab' => 'invoices', 'e2c_msg' => 'test_bad' ) ) );
			exit;
		}
		$ok = E2C_Invoices::mail( $to, E2C_Invoices::sample_data() );
		if ( ! $ok ) {
			set_transient( 'e2c_mail_err_' . get_current_user_id(), E2C_Invoices::$last_error, 300 );
		}
		wp_safe_redirect(
			self::page_url(
				array(
					'tab'     => 'invoices',
					'e2c_msg' => $ok ? 'test_ok' : 'test_fail',
					'e2c_to'  => rawurlencode( implode( ', ', $to ) ),
				)
			)
		);
		exit;
	}

	private static function output_pdf( $bytes, $name ) {
		while ( ob_get_level() ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/pdf' );
		header( 'Content-Disposition: inline; filename="' . sanitize_file_name( $name ) . '.pdf"' );
		header( 'Content-Length: ' . strlen( $bytes ) );
		echo $bytes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- contenido binario del PDF.
		exit;
	}

	public static function view_pdf() {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		self::guard( 'e2c_pdf_' . $id );
		$post = get_post( $id );
		$data = $post && E2C_Invoices::CPT === $post->post_type ? get_post_meta( $id, '_e2c_data', true ) : null;
		if ( ! is_array( $data ) ) {
			wp_die( esc_html( E2C_I18n::t( 'inv_empty' ) ) );
		}
		self::output_pdf( E2C_Invoices::pdf( $data ), $data['number'] );
	}

	public static function view_test_pdf() {
		self::guard( 'e2c_pdf_test' );
		$data = E2C_Invoices::sample_data();
		self::output_pdf( E2C_Invoices::pdf( $data ), $data['number'] );
	}

	public static function delete_invoice() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		self::guard( 'e2c_del_' . $id );
		$post = get_post( $id );
		if ( $post && E2C_Invoices::CPT === $post->post_type ) {
			wp_delete_post( $id, true );
		}
		$paged = isset( $_POST['paged'] ) ? absint( $_POST['paged'] ) : 1;
		wp_safe_redirect( self::page_url( array( 'tab' => 'invoices', 'e2c_msg' => 'deleted', 'paged' => max( 1, $paged ) ) ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/* Pantalla                                                            */
	/* ------------------------------------------------------------------ */

	public static function render() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$tab  = self::tab();
		$msg  = isset( $_GET['e2c_msg'] ) ? sanitize_key( wp_unslash( $_GET['e2c_msg'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tabs = array(
			'shipping' => E2C_I18n::t( 'tab_ship' ),
			'invoices' => E2C_I18n::t( 'tab_inv' ),
			'info'     => E2C_I18n::t( 'tab_info' ),
		);
		?>
		<div class="wrap e2c-wrap">
			<h1 class="screen-reader-text">Easy2Cuba for WooCommerce</h1>
			<?php self::notice( $msg ); ?>

			<div class="e2c-box">
				<div class="e2c-head">
					<img src="<?php echo esc_url( E2C_URL . 'assets/img/logo-128.png' ); ?>" alt="" width="64" height="64" />
					<div>
						<h2>Easy2Cuba for WooCommerce</h2>
						<p><?php echo esc_html( E2C_I18n::t( 'tagline' ) ); ?></p>
					</div>
				</div>
				<nav class="e2c-tabs" aria-label="Easy2Cuba">
					<?php foreach ( $tabs as $key => $label ) : ?>
						<a href="<?php echo esc_url( self::page_url( array( 'tab' => $key ) ) ); ?>" class="<?php echo $key === $tab ? 'is-active' : ''; ?>" <?php echo $key === $tab ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a>
					<?php endforeach; ?>
				</nav>
				<div class="e2c-body">
					<?php
					if ( 'invoices' === $tab ) {
						self::tab_invoices();
					} elseif ( 'info' === $tab ) {
						self::tab_info();
					} else {
						self::tab_shipping();
					}
					?>
				</div>
			</div>

			<footer class="e2c-foot">
				<?php
				printf(
					esc_html( E2C_I18n::t( 'copy' ) ),
					esc_html( wp_date( 'Y' ) ),
					'<a href="https://gmeti.com" target="_blank" rel="noopener">GMETI</a>'
				);
				?>
			</footer>
		</div>
		<?php
	}

	private static function notice( $msg ) {
		$map = array(
			'saved'     => array( 'success', E2C_I18n::t( 'saved' ) ),
			'classic'   => array( 'success', E2C_I18n::t( 'adm_page_done' ) ),
			'inv_saved' => array( 'success', E2C_I18n::t( 'inv_saved' ) ),
			'deleted'   => array( 'success', E2C_I18n::t( 'deleted' ) ),
			'test_bad'  => array( 'error', E2C_I18n::t( 'test_bad' ) ),
			'test_fail' => array( 'error', E2C_I18n::t( 'test_fail' ) ),
		);
		if ( 'test_fail' === $msg ) {
			$err = get_transient( 'e2c_mail_err_' . get_current_user_id() );
			if ( $err ) {
				$map['test_fail'][1] .= ' ' . sprintf( E2C_I18n::t( 'err_detail' ), $err );
				delete_transient( 'e2c_mail_err_' . get_current_user_id() );
			}
		}
		if ( 'test_ok' === $msg ) {
			$to = isset( $_GET['e2c_to'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['e2c_to'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$map['test_ok'] = array( 'success', sprintf( E2C_I18n::t( 'test_ok' ), $to ) );
		}
		if ( isset( $map[ $msg ] ) ) {
			echo '<div class="notice notice-' . esc_attr( $map[ $msg ][0] ) . ' is-dismissible"><p>' . esc_html( $map[ $msg ][1] ) . '</p></div>';
		}
	}

	/* ---------- Envíos ---------- */

	private static function tab_shipping() {
		$s        = E2C_Data::settings();
		$all_mun  = E2C_Data::municipalities();
		$symbol   = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );
		$currency = get_woocommerce_currency();
		$page_id  = function_exists( 'wc_get_page_id' ) ? (int) wc_get_page_id( 'checkout' ) : 0;
		$is_block = $page_id > 0 && has_block( 'woocommerce/checkout', $page_id );
		$active   = count( E2C_Data::active_provinces() );
		$t        = array( 'E2C_I18n', 't' );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="e2c_save" />
			<?php wp_nonce_field( 'e2c_save' ); ?>

			<section class="e2c-sec">
				<h3><?php echo esc_html( call_user_func( $t, 'adm_general' ) ); ?></h3>
				<label class="e2c-toggle-row" for="e2c_enabled">
					<span class="e2c-sw"><input type="checkbox" id="e2c_enabled" name="e2c_enabled" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> /><span></span></span>
					<span>
						<strong><?php echo esc_html( call_user_func( $t, 'adm_enable' ) ); ?></strong>
						<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'adm_enable_d' ) ); ?></span>
					</span>
				</label>
				<label class="e2c-toggle-row" for="e2c_wide">
					<span class="e2c-sw"><input type="checkbox" id="e2c_wide" name="e2c_wide" value="1" <?php checked( ! empty( $s['wide'] ) ); ?> /><span></span></span>
					<span>
						<strong><?php echo esc_html( call_user_func( $t, 'adm_wide' ) ); ?></strong>
						<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'adm_wide_d' ) ); ?></span>
					</span>
				</label>
				<label class="e2c-toggle-row" for="e2c_consent">
					<span class="e2c-sw"><input type="checkbox" id="e2c_consent" name="e2c_consent" value="1" <?php checked( ! empty( $s['consent'] ) ); ?> /><span></span></span>
					<span>
						<strong><?php echo esc_html( call_user_func( $t, 'adm_consent' ) ); ?></strong>
						<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'adm_consent_d' ) ); ?></span>
					</span>
				</label>
			</section>

			<section class="e2c-sec">
				<h3><?php echo esc_html( call_user_func( $t, 'adm_page' ) ); ?></h3>
				<?php if ( $page_id <= 0 ) : ?>
					<p class="e2c-status e2c-status-warn"><?php echo esc_html( call_user_func( $t, 'adm_page_none' ) ); ?></p>
				<?php elseif ( $is_block ) : ?>
					<p class="e2c-status e2c-status-warn"><?php echo esc_html( call_user_func( $t, 'adm_page_blk' ) ); ?></p>
					<p><button type="submit" form="e2c-classic-form" class="e2c-btn e2c-btn-sec"><?php echo esc_html( call_user_func( $t, 'adm_page_btn' ) ); ?></button></p>
				<?php else : ?>
					<p class="e2c-status e2c-status-ok"><?php echo esc_html( call_user_func( $t, 'adm_page_ok' ) ); ?></p>
				<?php endif; ?>
			</section>

			<section class="e2c-sec">
				<h3><?php echo esc_html( call_user_func( $t, 'adm_title' ) ); ?></h3>
				<p class="e2c-muted e2c-sub"><?php echo esc_html( call_user_func( $t, 'adm_sub' ) ); ?></p>

				<?php if ( 0 === $active ) : ?>
					<p class="e2c-status e2c-status-warn"><?php echo esc_html( call_user_func( $t, 'no_active' ) ); ?></p>
				<?php endif; ?>

				<div class="e2c-bulk">
					<label for="e2c-bulk"><?php echo esc_html( call_user_func( $t, 'bulk_lbl' ) ); ?></label>
					<span class="e2c-money"><span><?php echo esc_html( $symbol ); ?></span><input type="number" id="e2c-bulk" min="0" step="0.01" placeholder="10.00" /></span>
					<button type="button" class="e2c-btn e2c-btn-sec" id="e2c-bulk-go"><?php echo esc_html( call_user_func( $t, 'apply' ) ); ?></button>
					<span class="e2c-spacer"></span>
					<button type="button" class="e2c-btn e2c-btn-sec" id="e2c-all-on"><?php echo esc_html( call_user_func( $t, 'all_on' ) ); ?></button>
					<button type="button" class="e2c-btn e2c-btn-sec" id="e2c-all-off"><?php echo esc_html( call_user_func( $t, 'all_off' ) ); ?></button>
				</div>

				<div class="e2c-table-wrap">
					<table class="e2c-table">
						<thead>
							<tr>
								<th><?php echo esc_html( call_user_func( $t, 'th_del' ) ); ?></th>
								<th><?php echo esc_html( call_user_func( $t, 'th_prov' ) ); ?></th>
								<th><?php echo esc_html( call_user_func( $t, 'th_mun' ) ); ?></th>
								<th><?php echo esc_html( call_user_func( $t, 'th_cost' ) . ' (' . $currency . ')' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php foreach ( E2C_Data::provinces() as $code => $name ) : ?>
							<?php
							$on    = ! empty( $s['provinces'][ $code ]['active'] );
							$price = (float) $s['provinces'][ $code ]['price'];
							?>
							<tr class="<?php echo $on ? '' : 'e2c-off'; ?>">
								<td>
									<span class="e2c-sw">
										<input type="checkbox" class="e2c-act" id="e2c-act-<?php echo esc_attr( $code ); ?>" name="e2c[<?php echo esc_attr( $code ); ?>][active]" value="1" <?php checked( $on ); ?> aria-label="<?php echo esc_attr( $name ); ?>" /><span></span>
									</span>
								</td>
								<td class="e2c-name"><?php echo esc_html( $name ); ?></td>
								<td class="e2c-num"><?php echo esc_html( count( $all_mun[ $code ] ) ); ?></td>
								<td>
									<span class="e2c-money">
										<span><?php echo esc_html( $symbol ); ?></span>
										<input type="number" class="e2c-price" id="e2c-price-<?php echo esc_attr( $code ); ?>" name="e2c[<?php echo esc_attr( $code ); ?>][price]" min="0" step="0.01" value="<?php echo esc_attr( wc_format_decimal( $price, 2 ) ); ?>" aria-label="<?php echo esc_attr( call_user_func( $t, 'th_cost' ) . ' ' . $name ); ?>" />
									</span>
									<?php if ( $price <= 0 ) : ?>
										<span class="e2c-pill"><?php echo esc_html( call_user_func( $t, 'free' ) ); ?></span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</section>

			<div class="e2c-save">
				<button type="submit" class="e2c-btn e2c-btn-pri"><?php echo esc_html( call_user_func( $t, 'save' ) ); ?></button>
			</div>
		</form>

		<form id="e2c-classic-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="e2c_classic" />
			<?php wp_nonce_field( 'e2c_classic' ); ?>
		</form>
		<?php
	}

	/* ---------- Facturas ---------- */

	private static function tab_invoices() {
		$s       = E2C_Invoices::settings();
		$t       = array( 'E2C_I18n', 't' );
		$logo_id = (int) $s['logo_id'];
		$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
		$first   = E2C_Invoices::recipients( $s['email'] );
		$paged   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$q       = new WP_Query(
			array(
				'post_type'      => E2C_Invoices::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'paged'          => $paged,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'no_found_rows'  => false,
			)
		);
		$total   = (int) $q->found_posts;
		?>
		<section class="e2c-sec">
			<h3><?php echo esc_html( call_user_func( $t, 'inv_title' ) ); ?></h3>
			<p class="e2c-muted e2c-sub"><?php echo esc_html( call_user_func( $t, 'inv_sub' ) ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="e2c-panel">
				<input type="hidden" name="action" value="e2c_save_inv" />
				<?php wp_nonce_field( 'e2c_save_inv' ); ?>

				<label class="e2c-toggle-row e2c-full" for="e2c_inv_enabled">
					<span class="e2c-sw"><input type="checkbox" id="e2c_inv_enabled" name="e2c_inv_enabled" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?> /><span></span></span>
					<span>
						<strong><?php echo esc_html( call_user_func( $t, 'inv_on' ) ); ?></strong>
						<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'inv_on_d' ) ); ?></span>
					</span>
				</label>

				<div class="e2c-field">
					<label for="e2c_inv_email"><?php echo esc_html( call_user_func( $t, 'inv_email' ) ); ?></label>
					<input type="text" id="e2c_inv_email" name="e2c_inv_email" value="<?php echo esc_attr( $s['email'] ); ?>" />
					<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'inv_email_d' ) ); ?></span>
				</div>

				<div class="e2c-field">
					<label for="e2c_inv_footer"><?php echo esc_html( call_user_func( $t, 'inv_footer' ) ); ?></label>
					<input type="text" id="e2c_inv_footer" name="e2c_inv_footer" value="<?php echo esc_attr( $s['footer'] ); ?>" placeholder="<?php echo esc_attr( E2C_Invoices::default_footer() ); ?>" />
					<span class="e2c-muted"><?php echo esc_html( sprintf( call_user_func( $t, 'inv_footer_d' ), E2C_Invoices::default_footer() ) ); ?></span>
				</div>

				<div class="e2c-field e2c-full">
					<label for="e2c_retention"><?php echo esc_html( call_user_func( $t, 'ret_label' ) ); ?></label>
					<span class="e2c-inline"><input type="number" min="0" max="240" step="1" id="e2c_retention" name="e2c_retention" value="<?php echo esc_attr( (int) $s['retention'] ); ?>" /> <?php echo esc_html( call_user_func( $t, 'ret_months' ) ); ?></span>
					<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'ret_d' ) ); ?></span>
				</div>

				<div class="e2c-field e2c-full">
					<span class="e2c-label"><?php echo esc_html( call_user_func( $t, 'inv_logo' ) ); ?></span>
					<div class="e2c-logo-row">
						<div class="e2c-logo-box" id="e2c-logo-box">
							<?php if ( $logo ) : ?>
								<img src="<?php echo esc_url( $logo ); ?>" alt="" />
							<?php else : ?>
								<span><?php echo esc_html( call_user_func( $t, 'no_logo' ) ); ?></span>
							<?php endif; ?>
						</div>
						<input type="hidden" id="e2c_logo_id" name="e2c_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
						<button type="button" class="e2c-btn e2c-btn-sec" id="e2c-logo-pick"><?php echo esc_html( call_user_func( $t, 'pick_logo' ) ); ?></button>
						<button type="button" class="e2c-btn e2c-btn-del" id="e2c-logo-rm" <?php echo $logo ? '' : 'hidden'; ?>><?php echo esc_html( call_user_func( $t, 'rm_logo' ) ); ?></button>
					</div>
					<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'inv_logo_d' ) ); ?></span>
				</div>

				<div class="e2c-full e2c-divider"></div>

				<label class="e2c-toggle-row e2c-full" for="e2c_smtp_on">
					<span class="e2c-sw"><input type="checkbox" id="e2c_smtp_on" name="e2c_smtp_on" value="1" <?php checked( ! empty( $s['smtp_on'] ) ); ?> /><span></span></span>
					<span>
						<strong><?php echo esc_html( call_user_func( $t, 'smtp_on' ) ); ?></strong>
						<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'smtp_on_d' ) ); ?></span>
					</span>
				</label>

				<div class="e2c-full e2c-smtp" id="e2c-smtp-fields" <?php echo empty( $s['smtp_on'] ) ? 'hidden' : ''; ?>>
					<div class="e2c-field e2c-col-2">
						<label for="e2c_smtp_host"><?php echo esc_html( call_user_func( $t, 'smtp_host' ) ); ?></label>
						<input type="text" id="e2c_smtp_host" name="e2c_smtp_host" value="<?php echo esc_attr( $s['smtp_host'] ); ?>" placeholder="smtp.gmail.com" autocomplete="off" />
					</div>
					<div class="e2c-field">
						<label for="e2c_smtp_port"><?php echo esc_html( call_user_func( $t, 'smtp_port' ) ); ?></label>
						<input type="text" inputmode="numeric" id="e2c_smtp_port" name="e2c_smtp_port" value="<?php echo esc_attr( $s['smtp_port'] ); ?>" />
					</div>
					<div class="e2c-field">
						<label for="e2c_smtp_secure"><?php echo esc_html( call_user_func( $t, 'smtp_secure' ) ); ?></label>
						<select id="e2c_smtp_secure" name="e2c_smtp_secure">
							<option value="tls" <?php selected( $s['smtp_secure'], 'tls' ); ?>>TLS</option>
							<option value="ssl" <?php selected( $s['smtp_secure'], 'ssl' ); ?>>SSL</option>
							<option value="none" <?php selected( $s['smtp_secure'], 'none' ); ?>><?php echo esc_html( call_user_func( $t, 'smtp_none' ) ); ?></option>
						</select>
					</div>
					<div class="e2c-field e2c-col-2">
						<label for="e2c_smtp_user"><?php echo esc_html( call_user_func( $t, 'smtp_user' ) ); ?></label>
						<input type="text" id="e2c_smtp_user" name="e2c_smtp_user" value="<?php echo esc_attr( $s['smtp_user'] ); ?>" placeholder="tutienda@gmail.com" autocomplete="off" />
					</div>
					<div class="e2c-field e2c-col-2">
						<label for="e2c_smtp_pass"><?php echo esc_html( call_user_func( $t, 'smtp_pass' ) ); ?></label>
						<input type="password" id="e2c_smtp_pass" name="e2c_smtp_pass" value="" autocomplete="new-password" placeholder="<?php echo $s['smtp_pass'] ? '••••••••' : ''; ?>" />
						<?php if ( $s['smtp_pass'] ) : ?>
							<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'smtp_pass_ok' ) ); ?></span>
						<?php endif; ?>
					</div>
					<div class="e2c-field e2c-col-2">
						<label for="e2c_from_email"><?php echo esc_html( call_user_func( $t, 'from_email' ) ); ?></label>
						<input type="text" id="e2c_from_email" name="e2c_from_email" value="<?php echo esc_attr( $s['from_email'] ); ?>" />
						<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'from_email_d' ) ); ?></span>
					</div>
					<div class="e2c-field e2c-col-2">
						<label for="e2c_from_name"><?php echo esc_html( call_user_func( $t, 'from_name' ) ); ?></label>
						<input type="text" id="e2c_from_name" name="e2c_from_name" value="<?php echo esc_attr( $s['from_name'] ); ?>" placeholder="<?php echo esc_attr( wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ); ?>" />
						<span class="e2c-muted"><?php echo esc_html( call_user_func( $t, 'from_name_d' ) ); ?></span>
					</div>
				</div>

				<div class="e2c-full">
					<button type="submit" class="e2c-btn e2c-btn-pri"><?php echo esc_html( call_user_func( $t, 'save' ) ); ?></button>
				</div>
			</form>
		</section>

		<section class="e2c-sec">
			<h3><?php echo esc_html( call_user_func( $t, 'test_title' ) ); ?></h3>
			<p class="e2c-muted e2c-sub"><?php echo esc_html( call_user_func( $t, 'test_sub' ) ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="e2c-test">
				<input type="hidden" name="action" value="e2c_test_inv" />
				<?php wp_nonce_field( 'e2c_test_inv' ); ?>
				<label for="e2c_test_email"><?php echo esc_html( call_user_func( $t, 'test_email' ) ); ?></label>
				<input type="email" id="e2c_test_email" name="e2c_test_email" value="<?php echo esc_attr( $first ? $first[0] : get_option( 'admin_email' ) ); ?>" required />
				<button type="submit" class="e2c-btn e2c-btn-pri"><?php echo esc_html( call_user_func( $t, 'test_send' ) ); ?></button>
				<a class="e2c-btn e2c-btn-sec" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=e2c_pdf_test' ), 'e2c_pdf_test' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( call_user_func( $t, 'test_view' ) ); ?></a>
			</form>
		</section>

		<section class="e2c-sec e2c-sec-last">
			<div class="e2c-list-head">
				<h3><?php echo esc_html( call_user_func( $t, 'inv_list' ) ); ?></h3>
				<span class="e2c-muted"><?php echo esc_html( 1 === $total ? call_user_func( $t, 'inv_count1' ) : sprintf( call_user_func( $t, 'inv_countn' ), $total ) ); ?></span>
			</div>

			<?php if ( ! $q->have_posts() ) : ?>
				<p class="e2c-empty"><?php echo esc_html( call_user_func( $t, 'inv_empty' ) ); ?></p>
			<?php else : ?>
				<div class="e2c-table-wrap">
					<table class="e2c-table e2c-inv-table">
						<thead>
							<tr>
								<th><?php echo esc_html( call_user_func( $t, 'th_inv' ) ); ?></th>
								<th><?php echo esc_html( call_user_func( $t, 'th_date' ) ); ?></th>
								<th><?php echo esc_html( call_user_func( $t, 'th_order' ) ); ?></th>
								<th><?php echo esc_html( call_user_func( $t, 'th_buyer' ) ); ?></th>
								<th><?php echo esc_html( call_user_func( $t, 'th_recip' ) ); ?></th>
								<th><?php echo esc_html( call_user_func( $t, 'province' ) ); ?></th>
								<th class="e2c-r"><?php echo esc_html( call_user_func( $t, 'total' ) ); ?></th>
								<th><?php echo esc_html( call_user_func( $t, 'th_sent' ) ); ?></th>
								<th class="e2c-r"><?php echo esc_html( call_user_func( $t, 'th_actions' ) ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php
						foreach ( $q->posts as $p ) :
							$d        = get_post_meta( $p->ID, '_e2c_data', true );
							$d        = is_array( $d ) ? $d : array();
							$order_id = (int) get_post_meta( $p->ID, '_e2c_order_id', true );
							$order    = $order_id ? wc_get_order( $order_id ) : false;
							$ok       = (int) get_post_meta( $p->ID, '_e2c_sent_ok', true );
							$url      = E2C_Invoices::pdf_url( $p->ID );
							?>
							<tr>
								<td><a class="e2c-inv-no" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $p->post_title ); ?></a></td>
								<td class="e2c-num"><?php echo esc_html( get_the_date( get_option( 'date_format' ), $p ) . ' ' . get_the_time( get_option( 'time_format' ), $p ) ); ?></td>
								<td>
									<?php if ( $order ) : ?>
										<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a>
									<?php else : ?>
										#<?php echo esc_html( isset( $d['order_number'] ) ? $d['order_number'] : $order_id ); ?>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( isset( $d['buyer']['name'] ) ? $d['buyer']['name'] : '' ); ?></td>
								<td><?php echo esc_html( isset( $d['rec']['name'] ) ? $d['rec']['name'] : '' ); ?></td>
								<td><?php echo esc_html( isset( $d['rec']['prov'] ) ? $d['rec']['prov'] : '' ); ?></td>
								<td class="e2c-num e2c-r"><?php echo esc_html( isset( $d['total'] ) ? $d['total'] : '' ); ?></td>
								<td>
									<?php echo esc_html( get_post_meta( $p->ID, '_e2c_sent_to', true ) ); ?>
									<?php if ( ! $ok ) : ?>
										<span class="e2c-pill e2c-pill-err"><?php echo esc_html( call_user_func( $t, 'not_sent' ) ); ?></span>
									<?php endif; ?>
								</td>
								<td class="e2c-r">
									<div class="e2c-actions">
										<a class="e2c-btn e2c-btn-sec e2c-small" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( call_user_func( $t, 'view_pdf' ) ); ?></a>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="e2c-del-form">
											<input type="hidden" name="action" value="e2c_del_inv" />
											<input type="hidden" name="id" value="<?php echo esc_attr( $p->ID ); ?>" />
											<input type="hidden" name="paged" value="<?php echo esc_attr( $paged ); ?>" />
											<?php wp_nonce_field( 'e2c_del_' . $p->ID ); ?>
											<button type="submit" class="e2c-btn e2c-btn-del e2c-small e2c-del"><?php echo esc_html( call_user_func( $t, 'delete' ) ); ?></button>
										</form>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>

				<?php if ( $q->max_num_pages > 1 ) : ?>
					<div class="e2c-pager">
						<?php if ( $paged > 1 ) : ?>
							<a class="e2c-btn e2c-btn-sec e2c-small" href="<?php echo esc_url( self::page_url( array( 'tab' => 'invoices', 'paged' => $paged - 1 ) ) ); ?>"><?php echo esc_html( call_user_func( $t, 'prev' ) ); ?></a>
						<?php endif; ?>
						<span class="e2c-muted"><?php echo esc_html( $paged . ' / ' . $q->max_num_pages ); ?></span>
						<?php if ( $paged < $q->max_num_pages ) : ?>
							<a class="e2c-btn e2c-btn-sec e2c-small" href="<?php echo esc_url( self::page_url( array( 'tab' => 'invoices', 'paged' => $paged + 1 ) ) ); ?>"><?php echo esc_html( call_user_func( $t, 'next' ) ); ?></a>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</section>
		<?php
	}

	/* ---------- Información ---------- */

	private static function tab_info() {
		$t = array( 'E2C_I18n', 't' );
		?>
		<section class="e2c-sec e2c-sec-last">
			<h3><?php echo esc_html( call_user_func( $t, 'info_title' ) ); ?></h3>
			<p class="e2c-muted e2c-sub"><?php echo esc_html( call_user_func( $t, 'info_sub' ) ); ?></p>
			<dl class="e2c-info">
				<dt><?php echo esc_html( call_user_func( $t, 'i_name' ) ); ?></dt><dd>Easy2Cuba for WooCommerce</dd>
				<dt><?php echo esc_html( call_user_func( $t, 'i_version' ) ); ?></dt><dd><?php echo esc_html( E2C_VERSION ); ?></dd>
				<dt><?php echo esc_html( call_user_func( $t, 'i_by' ) ); ?></dt><dd>GMETI</dd>
				<dt><?php echo esc_html( call_user_func( $t, 'i_web' ) ); ?></dt><dd><a href="https://gmeti.com" target="_blank" rel="noopener">gmeti.com</a></dd>
				<dt><?php echo esc_html( call_user_func( $t, 'i_support' ) ); ?></dt>
				<dd>
					<span class="e2c-mail" id="e2c-support-mail"><?php echo esc_html( self::SUPPORT_EMAIL ); ?></span>
					<button type="button" class="e2c-btn e2c-btn-sec e2c-small" id="e2c-copy-mail"><?php echo esc_html( call_user_func( $t, 'copy_btn' ) ); ?></button>
				</dd>
				<dt><?php echo esc_html( call_user_func( $t, 'i_req' ) ); ?></dt><dd>WordPress 6.0+ · WooCommerce 7.0+ · PHP 7.4+</dd>
				<dt><?php echo esc_html( call_user_func( $t, 'i_langs' ) ); ?></dt><dd>Español, English</dd>
				<dt><?php echo esc_html( call_user_func( $t, 'i_license' ) ); ?></dt><dd>GPLv2</dd>
			</dl>
		</section>
		<?php
	}
}
