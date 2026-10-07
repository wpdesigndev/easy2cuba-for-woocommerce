/* Easy2Cuba for WooCommerce — checkout */
( function ( $ ) {
	'use strict';

	var D = window.e2cCheckout;
	if ( ! D ) {
		return;
	}
	var T = D.i18n || {};

	function esc( s ) {
		return String( s ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' ).replace( />/g, '&gt;' );
	}

	/* ---------- Provincia y municipio ---------- */

	function zoneHint( code ) {
		var $h = $( '#e2c-zone-hint' );
		if ( ! $h.length ) {
			return;
		}
		var p = D.provinces[ code ];
		if ( ! p ) {
			$h.text( T.zone_empty );
			return;
		}
		$h.html( esc( T.zone_to ) + ' <b>' + esc( p.name ) + '</b>: <b>' + esc( p.price_text ) + '</b>' );
	}

	function fillMunicipalities( keep ) {
		var $mun = $( '#shipping_city' );
		var code = $( '#shipping_state' ).val();
		var p = D.provinces[ code ];
		$mun.empty();
		if ( ! p ) {
			$mun.append( new Option( T.mun_first, '' ) ).prop( 'disabled', true );
		} else {
			$mun.append( new Option( T.mun_pick, '' ) );
			$.each( p.municipalities, function ( i, m ) {
				$mun.append( new Option( m, m ) );
			} );
			$mun.prop( 'disabled', false );
			if ( keep && p.municipalities.indexOf( keep ) !== -1 ) {
				$mun.val( keep );
			} else if ( p.municipalities.length === 1 ) {
				$mun.val( p.municipalities[ 0 ] );
			}
		}
		zoneHint( code );
	}

	/* ---------- Prefijo → país ---------- */

	var displayNames = null;
	try {
		displayNames = new Intl.DisplayNames( [ D.lang ], { type: 'region' } );
	} catch ( e ) {}

	function countryName( iso ) {
		try {
			return displayNames ? displayNames.of( iso ) : iso;
		} catch ( e ) {
			return iso;
		}
	}

	var billCountry = '';

	function detectCountry() {
		var $p = $( '#e2c_phone_prefix' );
		if ( ! $p.length ) {
			return;
		}
		var d = String( $p.val() || '' ).replace( /\D/g, '' ).slice( 0, 4 );
		$p.val( d ? '+' + d : '' );

		var html;
		var found = false;
		var iso = '';
		billCountry = '';

		if ( ! d ) {
			html = esc( T.c_empty );
		} else {
			var k = d;
			while ( k && ! D.prefixes[ k ] ) {
				k = k.slice( 0, -1 );
			}
			if ( ! k ) {
				html = esc( T.c_unknown );
			} else {
				var codes = D.prefixes[ k ].split( ' ' );
				iso = codes[ 0 ];
				billCountry = k === '1' ? T.c_usca : codes.map( countryName ).join( T.or );
				found = true;
				html = esc( T.c_label ) + ': <b>' + esc( billCountry ) + '</b>';
			}
		}

		$( '.e2c-country-hint' ).html( html ).toggleClass( 'e2c-found', found );
		$( '#billing_country' ).val( iso || D.base || '' );
	}

	/* ---------- Validación antes de enviar ---------- */

	function setError( $input, msg ) {
		var $row = $input.closest( '.form-row' );
		$row.find( '.e2c-error' ).remove();
		if ( msg ) {
			$row.addClass( 'woocommerce-invalid' ).removeClass( 'woocommerce-validated' );
			$( '<span class="e2c-error" role="alert"></span>' ).text( msg ).appendTo( $row );
		} else {
			$row.removeClass( 'woocommerce-invalid' );
		}
	}

	function digits( v ) {
		return String( v || '' ).replace( /\D/g, '' );
	}

	function cubanOk( v ) {
		var d = digits( v );
		if ( d.length === 10 && d.indexOf( '53' ) === 0 ) {
			d = d.slice( 2 );
		}
		return d.length === 8;
	}

	function validate() {
		var first = null;

		function check( sel, test ) {
			var $el = $( sel );
			if ( ! $el.length ) {
				return;
			}
			var v = $.trim( $el.val() || '' );
			if ( ! v ) {
				return; // Vacío: ya lo revisa la comprobación de obligatorios.
			}
			var msg = test( v, $el );
			setError( $el, msg );
			if ( msg && ! first ) {
				first = $el;
			}
		}

		// Obligatorios de las secciones de Easy2Cuba.
		$( '.e2c-form .e2c-section .validate-required' ).find( 'input.input-text, select, textarea' ).filter( ':visible' ).each( function () {
			var $el = $( this );
			if ( $el.is( '#e2c_phone_prefix' ) ) {
				return;
			}
			var empty = ! $.trim( $el.val() || '' );
			setError( $el, empty ? T.e_req_js : '' );
			if ( empty && ! first ) {
				first = $el;
			}
		} );

		check( '#billing_email', function ( v ) {
			return v && ! /^\S+@\S+\.\S+$/.test( v ) ? T.e_email : '';
		} );
		check( '#billing_phone', function ( v ) {
			return v && ! billCountry ? T.e_pref : '';
		} );
		check( '#shipping_phone', function ( v ) {
			return v && ! cubanOk( v ) ? T.e_cu : '';
		} );
		check( '#e2c_landline', function ( v ) {
			return v && ! cubanOk( v ) ? T.e_cu : '';
		} );
		check( '#e2c_ci', function ( v ) {
			return v && ! /^\d{11}$/.test( digits( v ) ) ? T.e_ci : '';
		} );

		var $consent = $( '#e2c_consent' );
		if ( $consent.length ) {
			var noConsent = ! $consent.is( ':checked' );
			setError( $consent, noConsent ? T.e_consent : '' );
			if ( noConsent && ! first ) {
				first = $consent;
			}
		}

		if ( first ) {
			$( 'html, body' ).animate( { scrollTop: first.offset().top - 120 }, 300 );
			first.trigger( 'focus' );
			return false;
		}
		return true;
	}

	/* ---------- Arranque ---------- */

	$( function () {
		var $form = $( 'form.checkout.e2c-form' );
		if ( ! $form.length ) {
			return;
		}

		var keepCity = $( '#shipping_city' ).val();
		if ( $( '#shipping_state' ).val() ) {
			fillMunicipalities( keepCity );
		} else {
			$( '#shipping_city' ).prop( 'disabled', true );
		}

		$( document.body ).on( 'change', '#shipping_state', function () {
			fillMunicipalities( '' );
		} );

		$( document.body ).on( 'input change', '#e2c_phone_prefix', detectCountry );
		detectCountry();

		$form.on( 'input change', 'input, select, textarea', function () {
			var $row = $( this ).closest( '.form-row' );
			if ( $row.find( '.e2c-error' ).length ) {
				$row.find( '.e2c-error' ).remove();
				$row.removeClass( 'woocommerce-invalid' );
			}
		} );

		$form.on( 'checkout_place_order', function () {
			return validate();
		} );
	} );
} )( jQuery );
