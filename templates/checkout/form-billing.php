<?php
/**
 * Easy2Cuba: sección "Quién compra" (sustituye checkout/form-billing.php).
 *
 * @var WC_Checkout $checkout
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="e2c-card e2c-section woocommerce-billing-fields">
	<div class="e2c-sec-head">
		<span class="e2c-num">1</span>
		<div>
			<h3><?php echo esc_html( E2C_I18n::t( 's1_title' ) ); ?></h3>
			<p><?php echo esc_html( E2C_I18n::t( 's1_sub' ) ); ?></p>
		</div>
	</div>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="e2c-fields woocommerce-billing-fields__field-wrapper">
		<?php
		foreach ( $checkout->get_checkout_fields( 'billing' ) as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</section>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<section class="e2c-card e2c-section woocommerce-account-fields">
		<?php if ( ! $checkout->is_registration_required() ) : ?>
			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
				</label>
			</p>
		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>
			<div class="create-account e2c-fields">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</section>
<?php endif; ?>
