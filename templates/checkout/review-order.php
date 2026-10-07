<?php
/**
 * Easy2Cuba: resumen "Tu pedido" (sustituye checkout/review-order.php).
 * Foto, nombre, (xN), precio unitario × cantidad y total de línea.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$e2c_fee_found = false;
?>
<table class="shop_table woocommerce-checkout-review-order-table e2c-review">
	<thead class="screen-reader-text">
		<tr>
			<th class="product-name"><?php echo esc_html( E2C_I18n::t( 'product' ) ); ?></th>
			<th class="product-total"><?php echo esc_html( E2C_I18n::t( 'subtotal' ) ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		do_action( 'woocommerce_review_order_before_cart_contents' );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

			if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
				$qty = (int) $cart_item['quantity'];
				?>
				<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
					<td class="product-name">
						<div class="e2c-prod">
							<span class="e2c-thumb"><?php echo wp_kses_post( $_product->get_image( array( 64, 64 ) ) ); ?></span>
							<span class="e2c-prod-text">
								<span class="e2c-prod-name">
									<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ); ?>
									<span class="e2c-qty">(x<?php echo esc_html( $qty ); ?>)</span>
								</span>
								<?php if ( $qty > 1 ) : ?>
									<span class="e2c-unit">
										<?php
										echo wp_kses_post( WC()->cart->get_product_price( $_product ) );
										echo ' ' . esc_html( E2C_I18n::t( 'each' ) ) . ' × ' . esc_html( $qty ) . ' = ';
										echo wp_kses_post( WC()->cart->get_product_subtotal( $_product, $qty ) );
										?>
									</span>
								<?php endif; ?>
								<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
						</div>
					</td>
					<td class="product-total">
						<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $qty ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</td>
				</tr>
				<?php
			}
		}

		do_action( 'woocommerce_review_order_after_cart_contents' );
		?>
	</tbody>
	<tfoot>

		<tr class="cart-subtotal">
			<th><?php echo esc_html( E2C_I18n::t( 'subtotal' ) ); ?></th>
			<td><?php wc_cart_totals_subtotal_html(); ?></td>
		</tr>

		<?php foreach ( WC()->cart->get_coupons() as $code => $coupon ) : ?>
			<tr class="cart-discount coupon-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
				<th><?php wc_cart_totals_coupon_label( $coupon ); ?></th>
				<td><?php wc_cart_totals_coupon_html( $coupon ); ?></td>
			</tr>
		<?php endforeach; ?>

		<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
			<?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
			<?php wc_cart_totals_shipping_html(); ?>
			<?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
		<?php endif; ?>

		<?php foreach ( WC()->cart->get_fees() as $fee ) : ?>
			<?php if ( E2C_Checkout::FEE_ID === $fee->id ) : ?>
				<?php
				$e2c_fee_found = true;
				$e2c_code      = E2C_Checkout::current_province();
				?>
				<tr class="fee e2c-ship-row">
					<th>
						<?php echo esc_html( E2C_I18n::t( 'shipping' ) ); ?>
						<small><?php echo esc_html( E2C_Data::province_name( $e2c_code ) ); ?></small>
					</th>
					<td><?php echo ( (float) $fee->amount <= 0 ) ? esc_html( E2C_I18n::t( 'free' ) ) : wp_kses_post( wc_price( $fee->amount ) ); ?></td>
				</tr>
			<?php else : ?>
				<tr class="fee">
					<th><?php echo esc_html( $fee->name ); ?></th>
					<td><?php wc_cart_totals_fee_html( $fee ); ?></td>
				</tr>
			<?php endif; ?>
		<?php endforeach; ?>

		<?php if ( ! $e2c_fee_found ) : ?>
			<tr class="e2c-ship-row e2c-ship-pending">
				<th><?php echo esc_html( E2C_I18n::t( 'shipping' ) ); ?></th>
				<td><small><?php echo esc_html( E2C_I18n::t( 'choose_prov' ) ); ?></small></td>
			</tr>
		<?php endif; ?>

		<?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
			<?php if ( 'itemized' === get_option( 'woocommerce_tax_total_display' ) ) : ?>
				<?php foreach ( WC()->cart->get_tax_totals() as $code => $tax ) : ?>
					<tr class="tax-rate tax-rate-<?php echo esc_attr( sanitize_title( $code ) ); ?>">
						<th><?php echo esc_html( $tax->label ); ?></th>
						<td><?php echo wp_kses_post( $tax->formatted_amount ); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr class="tax-total">
					<th><?php echo esc_html( WC()->countries->tax_or_vat() ); ?></th>
					<td><?php wc_cart_totals_taxes_total_html(); ?></td>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_before_order_total' ); ?>

		<tr class="order-total">
			<th><?php echo esc_html( E2C_I18n::t( 'total' ) ); ?></th>
			<td><?php wc_cart_totals_order_total_html(); ?></td>
		</tr>

		<?php do_action( 'woocommerce_review_order_after_order_total' ); ?>

	</tfoot>
</table>
