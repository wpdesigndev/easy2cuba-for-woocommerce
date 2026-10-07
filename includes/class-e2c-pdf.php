<?php
/**
 * Factura en PDF de Easy2Cuba (A4), dibujada con FPDF.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'E2C_FPDF' ) ) {
	require_once E2C_PATH . 'includes/lib/fpdf/e2c-fpdf.php';
}

class E2C_PDF extends E2C_FPDF {

	const NAVY  = array( 4, 57, 122 );
	const RED   = array( 229, 26, 58 );
	const INK   = array( 20, 35, 59 );
	const MUTED = array( 74, 102, 136 );
	const LINE  = array( 201, 212, 227 );
	const SOFT  = array( 238, 243, 250 );

	const LEFT  = 16;
	const RIGHT = 194; // 210 - 16.
	const WIDTH = 178;

	private $footer_text = '';
	private $is_test     = false;

	/**
	 * Genera el PDF y lo devuelve como texto binario.
	 *
	 * @param array  $d    Datos de la factura (ver E2C_Invoices::build_data()).
	 * @param string $logo Ruta local de la imagen del logo, o ''.
	 */
	public static function render( $d, $logo = '' ) {
		$pdf              = new self( 'P', 'mm', 'A4' );
		$pdf->footer_text = isset( $d['footer'] ) ? (string) $d['footer'] : '';
		$pdf->is_test     = ! empty( $d['test'] );
		$pdf->SetTitle( self::enc( E2C_I18n::t( 'pdf_title' ) . ' ' . $d['number'] ), false );
		$pdf->SetAuthor( self::enc( $d['store']['name'] ), false );
		$pdf->SetCreator( 'Easy2Cuba for WooCommerce' );
		$pdf->SetMargins( self::LEFT, 14, 16 );
		$pdf->SetAutoPageBreak( true, 24 );
		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->draw( $d, $logo );
		return $pdf->Output( 'S' );
	}

	/** Convierte UTF-8 a Windows-1252 (lo que usan las fuentes básicas de PDF). */
	public static function enc( $s ) {
		$s = str_replace( "\xC2\xA0", ' ', (string) $s );
		if ( function_exists( 'iconv' ) ) {
			$out = @iconv( 'UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $s ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( false !== $out ) {
				return $out;
			}
		}
		if ( function_exists( 'mb_convert_encoding' ) ) {
			return mb_convert_encoding( $s, 'Windows-1252', 'UTF-8' );
		}
		return $s;
	}

	private function color( $rgb, $what = 'text' ) {
		if ( 'fill' === $what ) {
			$this->SetFillColor( $rgb[0], $rgb[1], $rgb[2] );
		} elseif ( 'draw' === $what ) {
			$this->SetDrawColor( $rgb[0], $rgb[1], $rgb[2] );
		} else {
			$this->SetTextColor( $rgb[0], $rgb[1], $rgb[2] );
		}
	}

	/** Número de líneas que ocupará un texto en un ancho dado (con la fuente actual). */
	private function nb_lines( $w, $txt ) {
		$cw   = $this->CurrentFont['cw'];
		$wmax = ( $w - 2 * $this->cMargin ) * 1000 / $this->FontSize;
		$s    = str_replace( "\r", '', (string) $txt );
		$nb   = strlen( $s );
		if ( $nb > 0 && "\n" === $s[ $nb - 1 ] ) {
			$nb--;
		}
		$sep = -1;
		$i   = 0;
		$j   = 0;
		$l   = 0;
		$nl  = 1;
		while ( $i < $nb ) {
			$c = $s[ $i ];
			if ( "\n" === $c ) {
				$i++;
				$sep = -1;
				$j   = $i;
				$l   = 0;
				$nl++;
				continue;
			}
			if ( ' ' === $c ) {
				$sep = $i;
			}
			$l += isset( $cw[ $c ] ) ? $cw[ $c ] : 500;
			if ( $l > $wmax ) {
				if ( -1 === $sep ) {
					if ( $i === $j ) {
						$i++;
					}
				} else {
					$i = $sep + 1;
				}
				$sep = -1;
				$j   = $i;
				$l   = 0;
				$nl++;
			} else {
				$i++;
			}
		}
		return $nl;
	}

	private function ensure_space( $h ) {
		if ( $this->GetY() + $h > $this->GetPageHeight() - 24 ) {
			$this->AddPage();
		}
	}

	/* ------------------------------------------------------------------ */

	private function draw( $d, $logo ) {
		$l = self::LEFT;

		// Cabecera: logo o nombre de la tienda / FACTURA + datos.
		$head_bottom = 30;
		if ( $logo && file_exists( $logo ) ) {
			$size = @getimagesize( $logo ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			if ( $size && $size[0] > 0 && $size[1] > 0 ) {
				$ratio = $size[0] / $size[1];
				$h     = 20;
				$w     = $h * $ratio;
				if ( $w > 70 ) {
					$w = 70;
					$h = $w / $ratio;
				}
				try {
					$this->Image( $logo, $l, 14, $w, $h );
					$head_bottom = max( $head_bottom, 14 + $h );
				} catch ( Exception $e ) {
					$logo = '';
				}
			} else {
				$logo = '';
			}
		} else {
			$logo = '';
		}
		if ( ! $logo ) {
			$this->SetXY( $l, 15 );
			$this->SetFont( 'Helvetica', 'B', 17 );
			$this->color( self::NAVY );
			$this->Cell( 100, 8, self::enc( $d['store']['name'] ), 0, 2 );
			$this->SetFont( 'Helvetica', '', 9 );
			$this->color( self::MUTED );
			$this->Cell( 100, 5, self::enc( $d['store']['url'] ), 0, 2 );
		}

		$this->SetXY( 110, 14 );
		$this->SetFont( 'Helvetica', 'B', 20 );
		$this->color( self::RED );
		$this->Cell( 84, 9, self::enc( mb_strtoupper( E2C_I18n::t( 'pdf_title' ), 'UTF-8' ) ), 0, 2, 'R' );
		$y = 24;
		if ( $this->is_test ) {
			$this->SetFont( 'Helvetica', 'B', 9 );
			$this->Cell( 84, 5, self::enc( E2C_I18n::t( 'pdf_test' ) ), 0, 2, 'R' );
			$y += 5;
		}
		$y = $this->right_pair( E2C_I18n::t( 'pdf_no' ), $d['number'], $y );
		$y = $this->right_pair( E2C_I18n::t( 'pdf_order' ), '#' . $d['order_number'], $y );
		$y = $this->right_pair( E2C_I18n::t( 'pdf_date' ), $d['date'], $y );
		$head_bottom = max( $head_bottom, $y );

		$y = $head_bottom + 4;
		$this->color( self::NAVY, 'draw' );
		$this->SetLineWidth( 0.8 );
		$this->Line( $l, $y, self::RIGHT, $y );
		$this->SetY( $y + 6 );

		// ENTREGAR A (recuadro destacado para el mensajero).
		$r     = $d['rec'];
		$cells = array(
			array( E2C_I18n::t( 'pdf_name' ), $r['name'], true ),
			array( E2C_I18n::t( 'rec_mobile' ), $r['mobile'], false ),
			array( E2C_I18n::t( 'rec_landline' ), $r['land'], false ),
			array( E2C_I18n::t( 'rec_ci' ), $r['ci'], true ),
			array( E2C_I18n::t( 'province' ), $r['prov'], false ),
			array( E2C_I18n::t( 'municipality' ), $r['mun'], false ),
			array( E2C_I18n::t( 'street' ), $r['street'], true ),
			array( E2C_I18n::t( 'between' ), $r['between'], false ),
			array( E2C_I18n::t( 'rec_reparto' ), $r['reparto'], false ),
			array( E2C_I18n::t( 'refs' ), $r['refs'], true ),
		);
		$this->ensure_space( 60 );
		$box_top = $this->GetY();
		$this->color( self::NAVY, 'fill' );
		$this->Rect( $l, $box_top, self::WIDTH, 8, 'F' );
		$this->SetXY( $l + 4, $box_top + 1.5 );
		$this->SetFont( 'Helvetica', 'B', 9 );
		$this->SetTextColor( 255, 255, 255 );
		$this->Cell( self::WIDTH - 8, 5, self::enc( mb_strtoupper( E2C_I18n::t( 'pdf_deliver' ), 'UTF-8' ) ) );
		$this->SetY( $box_top + 11 );
		$this->grid( $cells, $l + 4, self::WIDTH - 8 );
		$box_bottom = $this->GetY() + 1;
		$this->color( self::NAVY, 'draw' );
		$this->SetLineWidth( 0.5 );
		$this->Rect( $l, $box_top, self::WIDTH, $box_bottom - $box_top );
		$this->SetY( $box_bottom + 6 );

		// QUIÉN COMPRA.
		$b     = $d['buyer'];
		$cells = array(
			array( E2C_I18n::t( 'pdf_name' ), $b['name'], false ),
			array( E2C_I18n::t( 'email' ), $b['email'], false ),
			array( E2C_I18n::t( 'phone' ), $b['phone'], false ),
			array( E2C_I18n::t( 'address' ), $b['addr'], true ),
			array( E2C_I18n::t( 'c_label' ), $b['country'], false ),
		);
		$this->ensure_space( 30 );
		$this->section_title( E2C_I18n::t( 'pdf_buyer' ) );
		$this->grid( $cells, $l, self::WIDTH );
		$this->Ln( 5 );

		// Productos.
		$this->items_table( $d['items'] );

		// Totales a la derecha; pago y notas a la izquierda.
		$this->Ln( 4 );
		$this->ensure_space( 34 );
		$top = $this->GetY();
		$this->totals( $d['totals'] );
		$right_end = $this->GetY();

		$this->SetY( $top + 1 );
		$lw = 84;
		foreach ( array( 'pdf_payment' => 'payment', 's3_title' => 'notes' ) as $title => $key ) {
			if ( empty( $d[ $key ] ) ) {
				continue;
			}
			$this->SetX( $l );
			$this->SetFont( 'Helvetica', 'B', 8.5 );
			$this->color( self::NAVY );
			$this->Cell( $lw, 5, self::enc( mb_strtoupper( E2C_I18n::t( $title ), 'UTF-8' ) ), 0, 2 );
			$this->SetFont( 'Helvetica', '', 10 );
			$this->color( self::INK );
			$this->MultiCell( $lw, 5, self::enc( $d[ $key ] ) );
			$this->Ln( 3 );
		}
		$this->SetY( max( $right_end, $this->GetY() ) );
	}

	private function right_pair( $label, $value, $y ) {
		$this->SetFont( 'Helvetica', 'B', 9.5 );
		$wv = $this->GetStringWidth( self::enc( $value ) ) + 1;
		$this->SetFont( 'Helvetica', '', 9.5 );
		$wl = $this->GetStringWidth( self::enc( $label ) ) + 2;
		$x  = self::RIGHT - $wv - $wl;
		$this->SetXY( $x, $y );
		$this->color( self::MUTED );
		$this->Cell( $wl, 5, self::enc( $label ), 0, 0, 'R' );
		$this->SetFont( 'Helvetica', 'B', 9.5 );
		$this->color( self::INK );
		$this->Cell( $wv, 5, self::enc( $value ), 0, 0, 'R' );
		return $y + 5;
	}

	private function section_title( $text ) {
		$this->SetFont( 'Helvetica', 'B', 9 );
		$this->color( self::NAVY );
		$this->Cell( self::WIDTH, 6, self::enc( mb_strtoupper( $text, 'UTF-8' ) ), 0, 1 );
		$this->Ln( 1 );
	}

	/** Rejilla de pares etiqueta/valor en dos columnas; las "anchas" ocupan toda la fila. */
	private function grid( $cells, $x, $w ) {
		$gap   = 6;
		$col_w = ( $w - $gap ) / 2;
		$rows  = array();
		$row   = array();
		foreach ( $cells as $c ) {
			if ( '' === trim( (string) $c[1] ) ) {
				continue;
			}
			if ( $c[2] ) {
				if ( $row ) {
					$rows[] = $row;
					$row    = array();
				}
				$rows[] = array( $c );
			} else {
				$row[] = $c;
				if ( 2 === count( $row ) ) {
					$rows[] = $row;
					$row    = array();
				}
			}
		}
		if ( $row ) {
			$rows[] = $row;
		}

		foreach ( $rows as $r ) {
			$wide  = 1 === count( $r ) && $r[0][2];
			$cw    = $wide ? $w : $col_w;
			$this->SetFont( 'Helvetica', 'B', 10 );
			$h = 0;
			foreach ( $r as $c ) {
				$h = max( $h, $this->nb_lines( $cw, self::enc( $c[1] ) ) * 5 );
			}
			$h += 4;
			$this->ensure_space( $h + 2 );
			$y = $this->GetY();
			foreach ( $r as $i => $c ) {
				$cx = $x + $i * ( $col_w + $gap );
				$this->SetXY( $cx, $y );
				$this->SetFont( 'Helvetica', '', 7.5 );
				$this->color( self::MUTED );
				$this->Cell( $cw, 4, self::enc( mb_strtoupper( $c[0], 'UTF-8' ) ), 0, 2 );
				$this->SetFont( 'Helvetica', 'B', 10 );
				$this->color( self::INK );
				$this->MultiCell( $cw, 5, self::enc( $c[1] ), 0, 'L' );
			}
			$this->SetXY( $x, $y + $h + 1.5 );
		}
	}

	private function items_table( $items ) {
		$l  = self::LEFT;
		$wq = 16;
		$wp = 30;
		$wt = 30;
		$wn = self::WIDTH - $wq - $wp - $wt;

		$this->ensure_space( 16 );
		$this->color( self::SOFT, 'fill' );
		$this->SetFont( 'Helvetica', 'B', 7.5 );
		$this->color( self::MUTED );
		$this->SetX( $l );
		$this->Cell( $wn, 7, self::enc( mb_strtoupper( E2C_I18n::t( 'product' ), 'UTF-8' ) ), 0, 0, 'L', true );
		$this->Cell( $wq, 7, self::enc( mb_strtoupper( E2C_I18n::t( 'pdf_qty' ), 'UTF-8' ) ), 0, 0, 'R', true );
		$this->Cell( $wp, 7, self::enc( mb_strtoupper( E2C_I18n::t( 'pdf_price' ), 'UTF-8' ) ), 0, 0, 'R', true );
		$this->Cell( $wt, 7, self::enc( mb_strtoupper( E2C_I18n::t( 'total' ), 'UTF-8' ) ), 0, 1, 'R', true );

		$this->color( self::LINE, 'draw' );
		$this->SetLineWidth( 0.2 );
		foreach ( $items as $it ) {
			$this->SetFont( 'Helvetica', '', 10 );
			$name = self::enc( $it['name'] );
			$h    = max( 1, $this->nb_lines( $wn, $name ) ) * 5 + 3;
			$this->ensure_space( $h );
			$y = $this->GetY();
			$this->color( self::INK );
			$this->SetXY( $l, $y + 1.5 );
			$this->MultiCell( $wn, 5, $name, 0, 'L' );
			$this->SetXY( $l + $wn, $y + 1.5 );
			$this->Cell( $wq, 5, self::enc( $it['qty'] ), 0, 0, 'R' );
			$this->Cell( $wp, 5, self::enc( $it['unit'] ), 0, 0, 'R' );
			$this->SetFont( 'Helvetica', 'B', 10 );
			$this->Cell( $wt, 5, self::enc( $it['total'] ), 0, 0, 'R' );
			$this->Line( $l, $y + $h, self::RIGHT, $y + $h );
			$this->SetY( $y + $h );
		}
	}

	private function totals( $totals ) {
		$w  = 84;
		$x  = self::RIGHT - $w;
		$wl = 54;
		$wv = $w - $wl;
		foreach ( $totals as $t ) {
			$strong = ! empty( $t['strong'] );
			$this->ensure_space( 9 );
			if ( $strong ) {
				$this->Ln( 1 );
				$this->color( self::NAVY, 'draw' );
				$this->SetLineWidth( 0.6 );
				$this->Line( $x, $this->GetY(), self::RIGHT, $this->GetY() );
				$this->Ln( 1.5 );
			}
			$this->SetX( $x );
			$this->SetFont( 'Helvetica', $strong ? 'B' : '', $strong ? 13 : 10 );
			$this->color( $strong ? self::NAVY : self::INK );
			$this->Cell( $wl, $strong ? 8 : 6, self::enc( $t['label'] ), 0, 0, 'L' );
			$this->SetFont( 'Helvetica', 'B', $strong ? 13 : 10 );
			$this->Cell( $wv, $strong ? 8 : 6, self::enc( $t['value'] ), 0, 1, 'R' );
		}
	}

	public function Footer() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- método de FPDF.
		$this->SetY( -18 );
		$this->color( self::LINE, 'draw' );
		$this->SetLineWidth( 0.2 );
		$this->Line( self::LEFT, $this->GetY(), self::RIGHT, $this->GetY() );
		$this->Ln( 2.5 );
		$this->SetFont( 'Helvetica', '', 8 );
		$this->color( self::MUTED );
		$this->Cell( self::WIDTH, 5, self::enc( $this->footer_text ), 0, 0, 'C' );
	}
}
