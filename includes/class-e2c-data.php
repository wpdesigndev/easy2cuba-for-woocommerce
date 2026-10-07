<?php
/**
 * Datos de Cuba (provincias y municipios), prefijos telefónicos y ajustes guardados.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class E2C_Data {

	const OPTION = 'e2c_settings';

	/** Provincias en orden de occidente a oriente. Código => nombre. */
	public static function provinces() {
		return array(
			'PRI' => 'Pinar del Río',
			'ART' => 'Artemisa',
			'HAB' => 'La Habana',
			'MAY' => 'Mayabeque',
			'MTZ' => 'Matanzas',
			'CFG' => 'Cienfuegos',
			'VCL' => 'Villa Clara',
			'SSP' => 'Sancti Spíritus',
			'CAV' => 'Ciego de Ávila',
			'CMG' => 'Camagüey',
			'LTU' => 'Las Tunas',
			'HOL' => 'Holguín',
			'GRA' => 'Granma',
			'SCU' => 'Santiago de Cuba',
			'GTM' => 'Guantánamo',
			'IJV' => 'Isla de la Juventud',
		);
	}

	/** Municipios por provincia (168 en total). */
	public static function municipalities() {
		return array(
			'PRI' => array( 'Consolación del Sur', 'Guane', 'La Palma', 'Los Palacios', 'Mantua', 'Minas de Matahambre', 'Pinar del Río', 'San Juan y Martínez', 'San Luis', 'Sandino', 'Viñales' ),
			'ART' => array( 'Alquízar', 'Artemisa', 'Bahía Honda', 'Bauta', 'Caimito', 'Candelaria', 'Guanajay', 'Güira de Melena', 'Mariel', 'San Antonio de los Baños', 'San Cristóbal' ),
			'HAB' => array( 'Arroyo Naranjo', 'Boyeros', 'Centro Habana', 'Cerro', 'Cotorro', 'Diez de Octubre', 'Guanabacoa', 'La Habana del Este', 'La Habana Vieja', 'La Lisa', 'Marianao', 'Playa', 'Plaza de la Revolución', 'Regla', 'San Miguel del Padrón' ),
			'MAY' => array( 'Batabanó', 'Bejucal', 'Güines', 'Jaruco', 'Madruga', 'Melena del Sur', 'Nueva Paz', 'Quivicán', 'San José de las Lajas', 'San Nicolás', 'Santa Cruz del Norte' ),
			'MTZ' => array( 'Calimete', 'Cárdenas', 'Ciénaga de Zapata', 'Colón', 'Jagüey Grande', 'Jovellanos', 'Limonar', 'Los Arabos', 'Martí', 'Matanzas', 'Pedro Betancourt', 'Perico', 'Unión de Reyes' ),
			'CFG' => array( 'Abreus', 'Aguada de Pasajeros', 'Cienfuegos', 'Cruces', 'Cumanayagua', 'Lajas', 'Palmira', 'Rodas' ),
			'VCL' => array( 'Caibarién', 'Camajuaní', 'Cifuentes', 'Corralillo', 'Encrucijada', 'Manicaragua', 'Placetas', 'Quemado de Güines', 'Ranchuelo', 'Remedios', 'Sagua la Grande', 'Santa Clara', 'Santo Domingo' ),
			'SSP' => array( 'Cabaiguán', 'Fomento', 'Jatibonico', 'La Sierpe', 'Sancti Spíritus', 'Taguasco', 'Trinidad', 'Yaguajay' ),
			'CAV' => array( 'Baraguá', 'Bolivia', 'Chambas', 'Ciego de Ávila', 'Ciro Redondo', 'Florencia', 'Majagua', 'Morón', 'Primero de Enero', 'Venezuela' ),
			'CMG' => array( 'Camagüey', 'Carlos Manuel de Céspedes', 'Esmeralda', 'Florida', 'Guáimaro', 'Jimaguayú', 'Minas', 'Najasa', 'Nuevitas', 'Santa Cruz del Sur', 'Sibanicú', 'Sierra de Cubitas', 'Vertientes' ),
			'LTU' => array( 'Amancio', 'Colombia', 'Jesús Menéndez', 'Jobabo', 'Las Tunas', 'Majibacoa', 'Manatí', 'Puerto Padre' ),
			'HOL' => array( 'Antilla', 'Báguanos', 'Banes', 'Cacocum', 'Calixto García', 'Cueto', 'Frank País', 'Gibara', 'Holguín', 'Mayarí', 'Moa', 'Rafael Freyre', 'Sagua de Tánamo', 'Urbano Noris' ),
			'GRA' => array( 'Bartolomé Masó', 'Bayamo', 'Buey Arriba', 'Campechuela', 'Cauto Cristo', 'Guisa', 'Jiguaní', 'Manzanillo', 'Media Luna', 'Niquero', 'Pilón', 'Río Cauto', 'Yara' ),
			'SCU' => array( 'Contramaestre', 'Guamá', 'Mella', 'Palma Soriano', 'San Luis', 'Santiago de Cuba', 'Segundo Frente', 'Songo-La Maya', 'Tercer Frente' ),
			'GTM' => array( 'Baracoa', 'Caimanera', 'El Salvador', 'Guantánamo', 'Imías', 'Maisí', 'Manuel Tames', 'Niceto Pérez', 'San Antonio del Sur', 'Yateras' ),
			'IJV' => array( 'Isla de la Juventud' ),
		);
	}

	/** Prefijo telefónico internacional => códigos de país (ISO), separados por espacio. */
	public static function prefixes() {
		return array(
			'1' => 'US CA', '1242' => 'BS', '1246' => 'BB', '1264' => 'AI', '1268' => 'AG', '1284' => 'VG', '1340' => 'VI', '1345' => 'KY', '1441' => 'BM', '1473' => 'GD', '1649' => 'TC', '1664' => 'MS', '1670' => 'MP', '1671' => 'GU', '1684' => 'AS', '1721' => 'SX', '1758' => 'LC', '1767' => 'DM', '1784' => 'VC', '1787' => 'PR', '1939' => 'PR', '1809' => 'DO', '1829' => 'DO', '1849' => 'DO', '1868' => 'TT', '1869' => 'KN', '1876' => 'JM', '1658' => 'JM',
			'7' => 'RU KZ', '20' => 'EG', '27' => 'ZA', '30' => 'GR', '31' => 'NL', '32' => 'BE', '33' => 'FR', '34' => 'ES', '36' => 'HU', '39' => 'IT', '40' => 'RO', '41' => 'CH', '43' => 'AT', '44' => 'GB', '45' => 'DK', '46' => 'SE', '47' => 'NO', '48' => 'PL', '49' => 'DE', '51' => 'PE', '52' => 'MX', '53' => 'CU', '54' => 'AR', '55' => 'BR', '56' => 'CL', '57' => 'CO', '58' => 'VE', '60' => 'MY', '61' => 'AU', '62' => 'ID', '63' => 'PH', '64' => 'NZ', '65' => 'SG', '66' => 'TH', '81' => 'JP', '82' => 'KR', '84' => 'VN', '86' => 'CN', '90' => 'TR', '91' => 'IN', '92' => 'PK', '93' => 'AF', '94' => 'LK', '95' => 'MM', '98' => 'IR',
			'211' => 'SS', '212' => 'MA', '213' => 'DZ', '216' => 'TN', '218' => 'LY', '220' => 'GM', '221' => 'SN', '222' => 'MR', '223' => 'ML', '224' => 'GN', '225' => 'CI', '226' => 'BF', '227' => 'NE', '228' => 'TG', '229' => 'BJ', '230' => 'MU', '231' => 'LR', '232' => 'SL', '233' => 'GH', '234' => 'NG', '235' => 'TD', '236' => 'CF', '237' => 'CM', '238' => 'CV', '239' => 'ST', '240' => 'GQ', '241' => 'GA', '242' => 'CG', '243' => 'CD', '244' => 'AO', '245' => 'GW', '248' => 'SC', '249' => 'SD', '250' => 'RW', '251' => 'ET', '252' => 'SO', '253' => 'DJ', '254' => 'KE', '255' => 'TZ', '256' => 'UG', '257' => 'BI', '258' => 'MZ', '260' => 'ZM', '261' => 'MG', '262' => 'RE', '263' => 'ZW', '264' => 'NA', '265' => 'MW', '266' => 'LS', '267' => 'BW', '268' => 'SZ', '269' => 'KM', '290' => 'SH', '291' => 'ER', '297' => 'AW', '298' => 'FO', '299' => 'GL',
			'350' => 'GI', '351' => 'PT', '352' => 'LU', '353' => 'IE', '354' => 'IS', '355' => 'AL', '356' => 'MT', '357' => 'CY', '358' => 'FI', '359' => 'BG', '370' => 'LT', '371' => 'LV', '372' => 'EE', '373' => 'MD', '374' => 'AM', '375' => 'BY', '376' => 'AD', '377' => 'MC', '378' => 'SM', '380' => 'UA', '381' => 'RS', '382' => 'ME', '383' => 'XK', '385' => 'HR', '386' => 'SI', '387' => 'BA', '389' => 'MK', '420' => 'CZ', '421' => 'SK', '423' => 'LI',
			'500' => 'FK', '501' => 'BZ', '502' => 'GT', '503' => 'SV', '504' => 'HN', '505' => 'NI', '506' => 'CR', '507' => 'PA', '508' => 'PM', '509' => 'HT', '590' => 'GP', '591' => 'BO', '592' => 'GY', '593' => 'EC', '594' => 'GF', '595' => 'PY', '596' => 'MQ', '597' => 'SR', '598' => 'UY', '599' => 'CW',
			'670' => 'TL', '673' => 'BN', '674' => 'NR', '675' => 'PG', '676' => 'TO', '677' => 'SB', '678' => 'VU', '679' => 'FJ', '680' => 'PW', '685' => 'WS', '686' => 'KI', '687' => 'NC', '689' => 'PF', '691' => 'FM', '692' => 'MH',
			'850' => 'KP', '852' => 'HK', '853' => 'MO', '855' => 'KH', '856' => 'LA', '880' => 'BD', '886' => 'TW', '960' => 'MV', '961' => 'LB', '962' => 'JO', '963' => 'SY', '964' => 'IQ', '965' => 'KW', '966' => 'SA', '967' => 'YE', '968' => 'OM', '970' => 'PS', '971' => 'AE', '972' => 'IL', '973' => 'BH', '974' => 'QA', '975' => 'BT', '976' => 'MN', '977' => 'NP', '992' => 'TJ', '993' => 'TM', '994' => 'AZ', '995' => 'GE', '996' => 'KG', '998' => 'UZ',
		);
	}

	/** Devuelve el primer código ISO del país según el prefijo, o '' si no se reconoce. */
	public static function country_from_prefix( $prefix ) {
		$digits = substr( preg_replace( '/\D/', '', (string) $prefix ), 0, 4 );
		$map    = self::prefixes();
		while ( '' !== $digits ) {
			if ( isset( $map[ $digits ] ) ) {
				$codes = explode( ' ', $map[ $digits ] );
				return $codes[0];
			}
			$digits = substr( $digits, 0, -1 );
		}
		return '';
	}

	public static function defaults() {
		$provinces = array();
		foreach ( array_keys( self::provinces() ) as $code ) {
			$provinces[ $code ] = array(
				'active' => 1,
				'price'  => 0,
			);
		}
		return array(
			'enabled'   => 1,
			'wide'      => 1,
			'consent'   => 1,
			'provinces' => $provinces,
		);
	}

	public static function settings() {
		$saved    = get_option( self::OPTION, array() );
		$defaults = self::defaults();
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$out              = $defaults;
		$out['enabled']   = isset( $saved['enabled'] ) ? (int) $saved['enabled'] : 1;
		$out['wide']      = isset( $saved['wide'] ) ? (int) $saved['wide'] : 1;
		$out['consent']   = isset( $saved['consent'] ) ? (int) $saved['consent'] : 1;
		foreach ( $defaults['provinces'] as $code => $def ) {
			if ( isset( $saved['provinces'][ $code ] ) && is_array( $saved['provinces'][ $code ] ) ) {
				$out['provinces'][ $code ] = array(
					'active' => empty( $saved['provinces'][ $code ]['active'] ) ? 0 : 1,
					'price'  => max( 0, (float) $saved['provinces'][ $code ]['price'] ),
				);
			}
		}
		return $out;
	}

	public static function enabled() {
		$s = self::settings();
		return ! empty( $s['enabled'] );
	}

	/** Provincias activas: código => nombre. */
	public static function active_provinces() {
		$s   = self::settings();
		$out = array();
		foreach ( self::provinces() as $code => $name ) {
			if ( ! empty( $s['provinces'][ $code ]['active'] ) ) {
				$out[ $code ] = $name;
			}
		}
		return $out;
	}

	public static function is_active( $code ) {
		$active = self::active_provinces();
		return isset( $active[ $code ] );
	}

	public static function price( $code ) {
		$s = self::settings();
		return isset( $s['provinces'][ $code ] ) ? (float) $s['provinces'][ $code ]['price'] : 0.0;
	}

	public static function province_name( $code ) {
		$p = self::provinces();
		return isset( $p[ $code ] ) ? $p[ $code ] : '';
	}

	/** Deja solo los dígitos. */
	public static function digits( $value ) {
		return preg_replace( '/\D/', '', (string) $value );
	}

	/** Número cubano de 8 dígitos (acepta que se escriba con 53 delante). */
	public static function cuban_number( $value ) {
		$d = self::digits( $value );
		if ( 10 === strlen( $d ) && 0 === strpos( $d, '53' ) ) {
			$d = substr( $d, 2 );
		}
		return 8 === strlen( $d ) ? $d : '';
	}
}
