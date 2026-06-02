<?php
/**
 * Checkout billing form — default address from address book via fhs-address-defaults.php.
 *
 * @package WooCommerce\Templates
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/fhs-address-defaults.php';
?>

<div class="woocommerce-billing-fields">
	<?php if ( wc_ship_to_billing_address_only() && WC()->cart->needs_shipping() ) : ?>
		<h3><?php esc_html_e( 'Billing &amp; Shipping', 'woocommerce' ); ?></h3>
	<?php else : ?>
		<h3><?php esc_html_e( 'Billing details', 'woocommerce' ); ?></h3>
	<?php endif; ?>

	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<div class="woocommerce-billing-fields__field-wrapper">
		<?php
		$fields = $checkout->get_checkout_fields( 'billing' );

		$billing_field_order = array(
			'billing_first_name' => 10,
			'billing_last_name'  => 20,
			'billing_company'    => 30,
			'billing_address_1'  => 40,
			'billing_country'    => 50,
			'billing_state'      => 55,
			'billing_city'       => 60,
			'billing_postcode'   => 65,
			'billing_phone'      => 70,
			'billing_email'      => 80,
		);

		foreach ( $fields as $key => $field ) {
			if ( isset( $billing_field_order[ $key ] ) ) {
				$field['priority'] = $billing_field_order[ $key ];
			}

			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>
