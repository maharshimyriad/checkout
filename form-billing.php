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

<?php
$fhs_billing_defaults = fhs_get_billing_defaults_for_js();

if ( ! empty( $fhs_billing_defaults ) ) :
	?>
	<script>
	(function () {
		var billingDefaults = <?php echo wp_json_encode( $fhs_billing_defaults ); ?>;
		var restoreTimer = null;

		function setBillingField(fieldId, value) {
			var field = document.getElementById(fieldId);
			if (!field || value == null || value === '') {
				return;
			}
			if (window.jQuery) {
				var $field = window.jQuery(field);
				if ($field.val() === value) {
					return;
				}
				$field.val(value);
				if ($field.hasClass('select2-hidden-accessible')) {
					$field.trigger('change.select2');
				}
			} else if (field.value !== value) {
				field.value = value;
			}
		}

		function restoreBillingIfCopiedFromShipping() {
			Object.keys(billingDefaults).forEach(function (billingId) {
				var expected = billingDefaults[billingId];
				var shippingId = billingId.replace(/^billing_/, 'shipping_');
				var billingEl = document.getElementById(billingId);
				var shippingEl = document.getElementById(shippingId);
				if (!billingEl || !expected) {
					return;
				}

				var current = window.jQuery ? window.jQuery(billingEl).val() : billingEl.value;
				var shippingVal = shippingEl
					? (window.jQuery ? window.jQuery(shippingEl).val() : shippingEl.value)
					: '';

				// Only fix when billing was overwritten to match shipping but should differ.
				if (current === expected) {
					return;
				}
				if (shippingVal && current === shippingVal && expected !== shippingVal) {
					setBillingField(billingId, expected);
				}
			});
		}

		function scheduleRestore() {
			if (restoreTimer) {
				window.clearTimeout(restoreTimer);
			}
			restoreTimer = window.setTimeout(restoreBillingIfCopiedFromShipping, 50);
		}

		document.addEventListener('DOMContentLoaded', function () {
			restoreBillingIfCopiedFromShipping();
			scheduleRestore();
			window.setTimeout(restoreBillingIfCopiedFromShipping, 0);
			window.setTimeout(scheduleRestore, 200);
			window.setTimeout(scheduleRestore, 600);
		});

		if (window.jQuery) {
			window.jQuery(document.body).on('updated_checkout.fhsBillingRestore', scheduleRestore);
			window.jQuery(document.body).on('country_to_state_changed.fhsBillingRestore', scheduleRestore);
		}
	})();
	</script>
	<?php
endif;
?>
