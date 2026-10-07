<?php
/**
 * Actualizaciones automáticas desde las releases de GitHub.
 *
 * Cuando se publica una release nueva en GitHub (etiqueta "v1.2.3" con el archivo
 * easy2cuba-for-woocommerce.zip adjunto), WordPress muestra el aviso de actualización
 * en Plugins y permite actualizar con un clic, igual que con los plugins de WordPress.org.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class E2C_Updater {

	const REPO      = 'wpdesigndev/easy2cuba-for-woocommerce';
	const ASSET     = 'easy2cuba-for-woocommerce.zip';
	const SLUG      = 'easy2cuba-for-woocommerce';
	const CACHE_KEY = 'e2c_github_release';

	public static function init() {
		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'flush' ), 10, 2 );
	}

	private static function basename() {
		return plugin_basename( E2C_FILE );
	}

	/** Última release publicada en GitHub (se guarda 6 horas para no consultar en cada carga). */
	private static function latest( $force = false ) {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( ! $force && is_array( $cached ) ) {
			return $cached;
		}

		$response = wp_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept'     => 'application/vnd.github+json',
					'User-Agent' => 'Easy2Cuba-for-WooCommerce/' . E2C_VERSION,
				),
			)
		);

		$release = array();
		if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( is_array( $body ) && ! empty( $body['tag_name'] ) && empty( $body['draft'] ) && empty( $body['prerelease'] ) ) {
				$package = '';
				foreach ( isset( $body['assets'] ) ? (array) $body['assets'] : array() as $asset ) {
					if ( isset( $asset['name'], $asset['browser_download_url'] ) && self::ASSET === $asset['name'] ) {
						$package = $asset['browser_download_url'];
						break;
					}
				}
				$release = array(
					'version' => ltrim( (string) $body['tag_name'], 'vV' ),
					'package' => $package,
					'url'     => isset( $body['html_url'] ) ? $body['html_url'] : 'https://github.com/' . self::REPO,
					'notes'   => isset( $body['body'] ) ? (string) $body['body'] : '',
					'date'    => isset( $body['published_at'] ) ? (string) $body['published_at'] : '',
				);
			}
		}

		// Si GitHub no responde, se vuelve a intentar en 1 hora.
		set_site_transient( self::CACHE_KEY, $release, $release ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
		return $release;
	}

	public static function check( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}
		$release = self::latest();
		if ( empty( $release['version'] ) || empty( $release['package'] ) ) {
			return $transient;
		}

		$item = (object) array(
			'id'           => 'github.com/' . self::REPO,
			'slug'         => self::SLUG,
			'plugin'       => self::basename(),
			'new_version'  => $release['version'],
			'url'          => 'https://github.com/' . self::REPO,
			'package'      => $release['package'],
			'icons'        => array(
				'1x' => E2C_URL . 'assets/img/logo-128.png',
				'2x' => E2C_URL . 'assets/img/icon-256x256.png',
			),
			'requires'     => '6.0',
			'requires_php' => '7.4',
		);

		if ( version_compare( $release['version'], E2C_VERSION, '>' ) ) {
			$transient->response[ self::basename() ] = $item;
		} else {
			$item->new_version                           = E2C_VERSION;
			$transient->no_update[ self::basename() ] = $item;
		}
		return $transient;
	}

	/** Ventana "Ver detalles" de la actualización. */
	public static function info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}
		$release = self::latest();
		$notes   = ! empty( $release['notes'] ) ? wpautop( esc_html( $release['notes'] ) ) : '';

		return (object) array(
			'name'          => 'Easy2Cuba for WooCommerce',
			'slug'          => self::SLUG,
			'version'       => ! empty( $release['version'] ) ? $release['version'] : E2C_VERSION,
			'author'        => '<a href="https://gmeti.com" target="_blank" rel="noopener noreferrer">GMETI</a>',
			'homepage'      => 'https://github.com/' . self::REPO,
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'last_updated'  => ! empty( $release['date'] ) ? $release['date'] : '',
			'download_link' => ! empty( $release['package'] ) ? $release['package'] : '',
			'sections'      => array(
				'description' => '<p>' . esc_html( E2C_I18n::t( 'plugin_desc' ) ) . '</p>',
				'changelog'   => $notes ? $notes : '<p>' . esc_html( E2C_I18n::t( 'plugin_desc' ) ) . '</p>',
			),
			'banners'       => array(),
			'icons'         => array(
				'1x' => E2C_URL . 'assets/img/logo-128.png',
				'2x' => E2C_URL . 'assets/img/icon-256x256.png',
			),
		);
	}

	public static function flush( $upgrader = null, $options = array() ) {
		delete_site_transient( self::CACHE_KEY );
	}
}
