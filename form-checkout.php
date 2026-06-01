<?php
/**
 * Checkout Form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-checkout.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

?>
<div class="main-checkout-container">
<form id="checkout" name="checkout" method="post" class="checkout woocommerce-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">
    <div class="checkout-form-wrapper">

	<?php if ( $checkout->get_checkout_fields() ) : ?>

		<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

		<div class="col2-set" id="customer_details">
			<div class="col-12">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
			</div>

			<div class="col-2">
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>
		</div>

		<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>
	<?php	do_action('woocommerce_custom_payment_relocation'); ?>

	<?php endif; ?>
	</div>
	</form>
	<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
	<div class="order-summary-container">
	<div class="order-summary-heading"><i class="icofont-price"></i><h3 id="order_review_heading"><?php esc_html_e( 'Order Summary', 'woocommerce' ); ?></h3></div>
	
	<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

	<div id="order_review" class="woocommerce-checkout-review-order">
		<?php do_action( 'woocommerce_checkout_order_review' ); ?>
	</div>

	<?php
	// Pay Now lives here (not in review-order.php). WC AJAX rebuilds review-order.php into
	// .woocommerce-checkout-review-order-table; a button there is left behind and duplicated.
	$formatted_total   = wp_strip_all_tags( wc_price( WC()->cart->get_total( 'edit' ) ) );
	$order_button_text = __( 'Pay Now', 'woocommerce' ) . ' ' . $formatted_total;
	$button_class      = 'button alt' . ( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ) : '' );
	?>
	<div class="place-order fhs-checkout-place-order" style="padding:0 30px;">
		<button type="submit" form="checkout" style="width:100%;" class="<?php echo esc_attr( $button_class ); ?>" name="woocommerce_checkout_place_order" id="place_order" value="<?php echo esc_attr( $order_button_text ); ?>" data-value="<?php echo esc_attr( $order_button_text ); ?>"><?php echo esc_html( $order_button_text ); ?></button>
	</div>

	<?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
	</div>
</div>



<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
