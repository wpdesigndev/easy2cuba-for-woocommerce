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
	const PAGES     = 'https://wpdesigndev.github.io/easy2cuba-for-woocommerce/';

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
		// "Comprobar de nuevo" en Escritorio › Actualizaciones consulta GitHub al momento.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( is_admin() && ! empty( $_GET['force-check'] ) && current_user_can( 'update_plugins' ) ) {
			$force = true;
		}
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

	/** Ventana "Ver detalles" del plugin (lista de Plugins y aviso de actualización). */
	public static function info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || self::SLUG !== $args->slug ) {
			return $result;
		}
		$release = self::latest();
		$readme  = self::readme();
		$s       = self::sections( $readme );

		$sections = array();
		foreach ( array( 'description', 'installation', 'faq', 'screenshots', 'changelog' ) as $key ) {
			if ( ! empty( $s[ $key ] ) ) {
				$sections[ $key ] = $s[ $key ];
			}
		}
		if ( empty( $sections['description'] ) ) {
			$sections['description'] = '<p>' . esc_html( E2C_I18n::t( 'plugin_desc' ) ) . '</p>';
		}
		if ( empty( $sections['changelog'] ) && ! empty( $release['notes'] ) ) {
			$sections['changelog'] = self::to_html( $release['notes'] );
		}

		// "Probado hasta 6.8" cubre 6.8.x, como en WordPress.org (WordPress compara la versión completa).
		$tested = self::head( $readme, 'tested up to', '' );
		$wp     = get_bloginfo( 'version' );
		if ( $tested && 0 === strpos( $wp, $tested . '.' ) ) {
			$tested = $wp;
		}

		$version = ! empty( $release['version'] ) ? $release['version'] : E2C_VERSION;
		if ( ! empty( $readme['header']['stable tag'] ) && version_compare( $readme['header']['stable tag'], $version, '>' ) ) {
			$version = $readme['header']['stable tag'];
		}

		return (object) array(
			'name'          => 'Easy2Cuba for WooCommerce',
			'slug'          => self::SLUG,
			'version'       => $version,
			'author'        => '<a href="https://gmeti.com" target="_blank" rel="noopener noreferrer">GMETI</a>',
			'homepage'      => self::PAGES,
			'requires'      => self::head( $readme, 'requires at least', '6.0' ),
			'tested'        => $tested,
			'requires_php'  => self::head( $readme, 'requires php', '7.4' ),
			'last_updated'  => ! empty( $release['date'] ) ? $release['date'] : '',
			'download_link' => ! empty( $release['package'] ) ? $release['package'] : '',
			'sections'      => $sections,
			'banners'       => array(
				'low'  => self::PAGES . 'img/banner-772x250.png',
				'high' => self::PAGES . 'img/banner-1544x500.png',
			),
			'icons'         => array(
				'1x' => E2C_URL . 'assets/img/logo-128.png',
				'2x' => E2C_URL . 'assets/img/icon-256x256.png',
			),
		);
	}

	/**
	 * Lee el readme de la versión publicada en GitHub (así "Ver detalles" muestra
	 * las novedades antes de actualizar). Si GitHub no responde, usa el del plugin instalado.
	 */
	private static function readme() {
		$file = 'en' === E2C_I18n::lang() ? 'readme-en.txt' : 'readme.txt';
		$key  = 'e2c_readme_' . md5( $file );
		$text = get_site_transient( $key );

		if ( false === $text ) {
			$text     = '';
			$response = wp_remote_get(
				'https://raw.githubusercontent.com/' . self::REPO . '/main/' . $file,
				array(
					'timeout' => 10,
					'headers' => array( 'User-Agent' => 'Easy2Cuba-for-WooCommerce/' . E2C_VERSION ),
				)
			);
			if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
				$body = (string) wp_remote_retrieve_body( $response );
				if ( false !== strpos( $body, '== Description ==' ) ) {
					$text = $body;
				}
			}
			set_site_transient( $key, $text, $text ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
		}

		if ( '' === $text ) {
			$local = E2C_PATH . $file;
			if ( ! is_readable( $local ) ) {
				$local = E2C_PATH . 'readme.txt';
			}
			$text = is_readable( $local ) ? (string) file_get_contents( $local ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		}
		return self::parse( $text );
	}

	private static function head( $readme, $key, $default ) {
		return ! empty( $readme['header'][ $key ] ) ? $readme['header'][ $key ] : $default;
	}

	/** Separa el readme en cabecera y secciones (formato de WordPress.org). */
	private static function parse( $text ) {
		$text  = str_replace( array( "\r\n", "\r" ), "\n", $text );
		$parts = preg_split( '/^==\s*([^=].*?)\s*==\s*$/m', $text, -1, PREG_SPLIT_DELIM_CAPTURE );
		$out   = array(
			'header'   => array(),
			'sections' => array(),
		);
		foreach ( explode( "\n", (string) array_shift( $parts ) ) as $line ) {
			if ( preg_match( '/^([A-Za-z ]+):\s*(.+)$/', trim( $line ), $m ) ) {
				$out['header'][ strtolower( trim( $m[1] ) ) ] = trim( $m[2] );
			}
		}
		for ( $i = 0; $i + 1 < count( $parts ); $i += 2 ) {
			$out['sections'][ strtolower( trim( $parts[ $i ] ) ) ] = trim( $parts[ $i + 1 ] );
		}
		return $out;
	}

	private static function sections( $readme ) {
		$map = array(
			'description'                => 'description',
			'installation'               => 'installation',
			'frequently asked questions' => 'faq',
			'faq'                        => 'faq',
			'screenshots'                => 'screenshots',
			'changelog'                  => 'changelog',
		);
		$out = array();
		foreach ( $readme['sections'] as $name => $body ) {
			if ( ! isset( $map[ $name ] ) ) {
				continue;
			}
			$key         = $map[ $name ];
			$out[ $key ] = 'screenshots' === $key ? self::screenshots( $body ) : self::to_html( $body );
		}
		return $out;
	}

	/** Capturas alojadas en la página del plugin (GitHub Pages). */
	private static function screenshots( $body ) {
		$files = array( 'checkout.png', 'admin-envios.png', 'admin-facturas.png', 'factura-pdf.png' );
		$html  = '<ol>';
		$n     = 0;
		foreach ( explode( "\n", $body ) as $line ) {
			if ( ! preg_match( '/^\d+\.\s+(.+)$/', trim( $line ), $m ) || ! isset( $files[ $n ] ) ) {
				continue;
			}
			$src   = self::PAGES . 'screenshots/' . $files[ $n ];
			$html .= '<li><a href="' . esc_url( $src ) . '" target="_blank" rel="noopener noreferrer"><img src="' . esc_url( $src ) . '" alt="' . esc_attr( $m[1] ) . '"></a><p>' . self::inline( $m[1] ) . '</p></li>';
			$n++;
		}
		return $n ? $html . '</ol>' : '';
	}

	/** Convierte el texto del readme (o de las notas de la release) en HTML sencillo. */
	private static function to_html( $text ) {
		$text  = str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
		$html  = '';
		$list  = '';
		$para  = array();
		$close = function () use ( &$html, &$list, &$para ) {
			if ( $para ) {
				$html .= '<p>' . implode( ' ', $para ) . '</p>';
				$para  = array();
			}
			if ( $list ) {
				$html .= '</' . $list . '>';
				$list  = '';
			}
		};

		foreach ( explode( "\n", $text ) as $raw ) {
			$line = trim( $raw );
			if ( '' === $line ) {
				$close();
				continue;
			}
			if ( preg_match( '/^=+\s*(.+?)\s*=+$/', $line, $m ) || preg_match( '/^#{1,6}\s+(.+)$/', $line, $m ) ) {
				$close();
				$html .= '<h4>' . self::inline( $m[1] ) . '</h4>';
				continue;
			}
			if ( preg_match( '/^[*\-]\s+(.+)$/', $line, $m ) || preg_match( '/^\d+\.\s+(.+)$/', $line, $m ) ) {
				$type = preg_match( '/^\d+\./', $line ) ? 'ol' : 'ul';
				if ( $para ) {
					$html .= '<p>' . implode( ' ', $para ) . '</p>';
					$para  = array();
				}
				if ( $list !== $type ) {
					if ( $list ) {
						$html .= '</' . $list . '>';
					}
					$html .= '<' . $type . '>';
					$list  = $type;
				}
				$html .= '<li>' . self::inline( $m[1] ) . '</li>';
				continue;
			}
			if ( $list ) {
				$html .= '</' . $list . '>';
				$list  = '';
			}
			$para[] = self::inline( $line );
		}
		$close();
		return $html;
	}

	private static function inline( $text ) {
		$text = esc_html( $text );
		$text = preg_replace( '/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text );
		$text = preg_replace( '/`(.+?)`/', '<code>$1</code>', $text );
		return preg_replace_callback(
			'/\[([^\]]+)\]\(([^)\s]+)\)/',
			function ( $m ) {
				return '<a href="' . esc_url( html_entity_decode( $m[2] ) ) . '" target="_blank" rel="noopener noreferrer">' . $m[1] . '</a>';
			},
			$text
		);
	}

	public static function flush( $upgrader = null, $options = array() ) {
		delete_site_transient( self::CACHE_KEY );
		delete_site_transient( 'e2c_readme_' . md5( 'readme.txt' ) );
		delete_site_transient( 'e2c_readme_' . md5( 'readme-en.txt' ) );
	}
}
