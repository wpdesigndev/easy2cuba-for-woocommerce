<?php
/**
 * Easy2Cuba: "Quién recibe en Cuba" y "Notas del pedido" (sustituye checkout/form-shipping.php).
 *
 * @var WC_Checkout $checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="e2c-card e2c-section woocommerce-shipping-fields e2c-recipient">
	<div class="e2c-sec-head">
		<span class="e2c-num">2</span>
		<div>
			<h3><?php echo esc_html( E2C_I18n::t( 's2_title' ) ); ?></h3>
			<p><?php echo esc_html( E2C_I18n::t( 's2_sub' ) ); ?></p>
		</div>
	</div>

	<div class="e2c-fields">
		<?php
		foreach ( $checkout->get_checkout_fields( 'e2c_recipient' ) as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>
</section>

<?php
$e2c_notes_on = apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) );
?>
<section class="e2c-card e2c-section woocommerce-additional-fields<?php echo $e2c_notes_on ? '' : ' e2c-empty'; ?>">
	<?php if ( $e2c_notes_on ) : ?>
		<div class="e2c-sec-head">
			<span class="e2c-num">3</span>
			<div>
				<h3><?php echo esc_html( E2C_I18n::t( 's3_title' ) ); ?></h3>
				<p><?php echo esc_html( E2C_I18n::t( 's3_sub' ) ); ?></p>
			</div>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

	<?php if ( $e2c_notes_on ) : ?>
		<div class="e2c-fields woocommerce-additional-fields__field-wrapper">
			<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
				<?php
				$field['class'] = array_merge( isset( $field['class'] ) ? (array) $field['class'] : array(), array( 'form-row-wide' ) );
				woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
				?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
</section>
