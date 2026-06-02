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
			'billing_phone'      => 30,
			'billing_email'      => 40,
			'billing_company'    => 50,
			'billing_address_1'  => 60,
			'billing_city'       => 70,
			'billing_state'      => 80,
			'billing_postcode'   => 90,
			'billing_country'    => 100,
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
?>
<script>
	(function () {
		var billingDefaults = <?php echo wp_json_encode( $fhs_billing_defaults ); ?>;
		var restoreTimer = null;
		var stateRefreshTimer = null;

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

		function getFieldValue(fieldId) {
			var field = document.getElementById(fieldId);
			if (!field) {
				return '';
			}

			return window.jQuery ? window.jQuery(field).val() || '' : field.value || '';
		}

		function restoreBillingStateControl() {
			var country = document.getElementById('billing_country');
			var state = document.getElementById('billing_state');

			if (!country || !state || !getFieldValue('billing_country')) {
				return;
			}

			var stateValue = getFieldValue('billing_state') || billingDefaults.billing_state || '';

			if (window.jQuery) {
				var $state = window.jQuery(state);

				if ($state.is('select')) {
					$state.find('option[value!=""]').prop('disabled', false);
					$state.prop('disabled', false);
				} else if (!$state.is('select')) {
					$state.prop('disabled', false);
				}

				if (stateValue) {
					$state.val(stateValue);
				}

				if ($state.hasClass('select2-hidden-accessible')) {
					$state.trigger('change');
				}
			} else {
				state.disabled = false;
				if (stateValue) {
					state.value = stateValue;
				}
			}
		}

		function refreshBillingStateControl() {
			if (stateRefreshTimer) {
				window.clearTimeout(stateRefreshTimer);
			}

			stateRefreshTimer = window.setTimeout(function () {
				stateRefreshTimer = null;
				var state = document.getElementById('billing_state');
				var hasDisabledStateOptions = false;

				if (window.jQuery && state) {
					hasDisabledStateOptions = window.jQuery(state).find('option[value!=""]:disabled').length > 0;
				}

				if (window.jQuery && document.getElementById('billing_country') && state && (state.disabled || hasDisabledStateOptions)) {
					window.jQuery('#billing_country').trigger('change');
					window.setTimeout(restoreBillingStateControl, 50);
					return;
				}

				restoreBillingStateControl();
			}, 50);
		}

		function restoreBillingIfCopiedFromShipping() {
			if (window.fhsSyncingShippingFromBilling) {
				return;
			}
			var sameAsCb = document.getElementById('fhs-use-same-as-billing-address-checkbox');
			if (sameAsCb && sameAsCb.checked) {
				return;
			}

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
			refreshBillingStateControl();
			scheduleRestore();
			window.setTimeout(restoreBillingIfCopiedFromShipping, 0);
			window.setTimeout(scheduleRestore, 200);
			window.setTimeout(scheduleRestore, 600);
		});

		if (window.jQuery) {
			window.jQuery(document.body).on('updated_checkout.fhsBillingRestore', scheduleRestore);
			window.jQuery(document.body).on('country_to_state_changed.fhsBillingRestore', scheduleRestore);
			window.jQuery(document.body).on('checkout_error.fhsBillingState updated_checkout.fhsBillingState', refreshBillingStateControl);
			window.jQuery(document.body).on('country_to_state_changed.fhsBillingState', restoreBillingStateControl);
		}
	})();
</script>
