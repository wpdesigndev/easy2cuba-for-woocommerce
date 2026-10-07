/* Easy2Cuba for WooCommerce — administración */
( function ( $ ) {
	'use strict';

	var L = window.e2cAdmin || {};

	/* ---------- Envíos ---------- */

	function syncRow( input ) {
		var tr = input.closest( 'tr' );
		if ( tr ) {
			tr.classList.toggle( 'e2c-off', ! input.checked );
		}
	}

	$( document ).on( 'change', '.e2c-table .e2c-act', function () {
		syncRow( this );
	} );

	function setAll( on ) {
		$( '.e2c-table .e2c-act' ).each( function () {
			this.checked = on;
			syncRow( this );
		} );
	}

	$( '#e2c-all-on' ).on( 'click', function () {
		setAll( true );
	} );
	$( '#e2c-all-off' ).on( 'click', function () {
		setAll( false );
	} );
	$( '#e2c-bulk-go' ).on( 'click', function () {
		var v = parseFloat( $( '#e2c-bulk' ).val() );
		if ( isNaN( v ) || v < 0 ) {
			return;
		}
		$( '.e2c-table .e2c-price' ).val( v.toFixed( 2 ) );
	} );

	/* ---------- Logo de la factura ---------- */

	var frame = null;
	$( '#e2c-logo-pick' ).on( 'click', function ( e ) {
		e.preventDefault();
		if ( ! window.wp || ! wp.media ) {
			return;
		}
		if ( ! frame ) {
			frame = wp.media( {
				title: L.pickLogo,
				button: { text: L.useLogo },
				library: { type: 'image' },
				multiple: false
			} );
			frame.on( 'select', function () {
				var a = frame.state().get( 'selection' ).first().toJSON();
				var url = ( a.sizes && a.sizes.medium ) ? a.sizes.medium.url : a.url;
				$( '#e2c_logo_id' ).val( a.id );
				$( '#e2c-logo-box' ).empty().append( $( '<img alt="">' ).attr( 'src', url ) );
				$( '#e2c-logo-rm' ).prop( 'hidden', false );
			} );
		}
		frame.open();
	} );

	$( '#e2c-logo-rm' ).on( 'click', function () {
		$( '#e2c_logo_id' ).val( '0' );
		$( '#e2c-logo-box' ).empty().append( $( '<span>' ).text( L.noLogo ) );
		$( this ).prop( 'hidden', true );
	} );

	/* ---------- SMTP ---------- */

	$( '#e2c_smtp_on' ).on( 'change', function () {
		$( '#e2c-smtp-fields' ).prop( 'hidden', ! this.checked );
	} );

	/* ---------- Eliminar factura (dos toques) ---------- */

	$( document ).on( 'submit', '.e2c-del-form', function ( e ) {
		var $b = $( this ).find( '.e2c-del' );
		if ( ! $b.hasClass( 'is-sure' ) ) {
			e.preventDefault();
			var original = $b.text();
			$b.addClass( 'is-sure' ).text( L.sure );
			setTimeout( function () {
				if ( $b.hasClass( 'is-sure' ) ) {
					$b.removeClass( 'is-sure' ).text( original );
				}
			}, 4000 );
		}
	} );

	/* ---------- Copiar correo de soporte ---------- */

	$( '#e2c-copy-mail' ).on( 'click', function () {
		var $btn = $( this );
		var text = $( '#e2c-support-mail' ).text();
		var original = $btn.text();
		function done() {
			$btn.text( L.copied );
			setTimeout( function () {
				$btn.text( original );
			}, 2000 );
		}
		function select() {
			var r = document.createRange();
			r.selectNodeContents( document.getElementById( 'e2c-support-mail' ) );
			var s = window.getSelection();
			s.removeAllRanges();
			s.addRange( r );
		}
		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( text ).then( done, select );
		} else {
			select();
		}
	} );
} )( jQuery );
