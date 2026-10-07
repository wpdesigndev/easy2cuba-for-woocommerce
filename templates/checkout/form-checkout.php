<?php
/**
 * Checkout de Easy2Cuba (sustituye checkout/form-checkout.php de WooCommerce).
 *
 * @var WC_Checkout $checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}
?>
<form name="checkout" method="post" class="checkout woocommerce-checkout e2c-form" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">

	<nav class="e2c-steps" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">
		<a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php echo esc_html( E2C_I18n::t( 'step_cart' ) ); ?></a>
		<span aria-hidden="true">›</span>
		<b aria-current="step"><?php echo esc_html( E2C_I18n::t( 'step_details' ) ); ?></b>
		<span aria-hidden="true">›</span>
		<span><?php echo esc_html( E2C_I18n::t( 'step_confirm' ) ); ?></span>
	</nav>

	<div class="e2c-grid">
		<div class="e2c-main">
			<?php if ( $checkout->get_checkout_fields() ) : ?>
				<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>
				<div id="customer_details" class="e2c-customer">
					<?php do_action( 'woocommerce_checkout_billing' ); ?>
					<?php do_action( 'woocommerce_checkout_shipping' ); ?>
				</div>
				<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
			<?php endif; ?>
		</div>

		<aside class="e2c-aside">
			<div class="e2c-card e2c-summary">
				<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
				<h3 id="order_review_heading"><?php echo esc_html( E2C_I18n::t( 'your_order' ) ); ?></h3>
				<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
				<div id="order_review" class="woocommerce-checkout-review-order">
					<?php do_action( 'woocommerce_checkout_order_review' ); ?>
				</div>
				<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
			</div>
		</aside>
	</div>
</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
