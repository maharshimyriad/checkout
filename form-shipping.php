<?php
/**
 * Checkout shipping form — default address from address book via fhs-address-defaults.php.
 *
 * @package WooCommerce\Templates
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/fhs-address-defaults.php';

$fulfilment_mode = '';

if ( WC()->session ) {
	$fulfilment_mode = sanitize_key( (string) WC()->session->get( 'fhs_fulfilment_method', '' ) );
}

if ( isset( $_POST['fhs_fulfilment_method'] ) ) {
	$fulfilment_mode = sanitize_key( wp_unslash( $_POST['fhs_fulfilment_method'] ) );
}

if ( ! in_array( $fulfilment_mode, array( 'delivery', 'pickup' ), true ) ) {
	$fulfilment_mode = 'delivery';
}
?>

<div class="woocommerce-shipping-fields fhs-checkout-flow" data-current-mode="<?php echo esc_attr( $fulfilment_mode ); ?>">
	<div class="fhs-fulfilment-toggle-wrap">
		<button type="button" class="fhs-fulfilment-btn" data-mode="delivery" aria-pressed="false">
			<img src="https://fhs.com.au/wp-content/uploads/2026/04/Delivery-Icon.png" style="height: 20px;" alt="">
			<span><?php esc_html_e( 'Delivery', 'woocommerce' ); ?></span>
		</button>
		<span class="fhs-fulfilment-or"><?php esc_html_e( 'or', 'woocommerce' ); ?></span>
		<button type="button" class="fhs-fulfilment-btn" data-mode="pickup" aria-pressed="false">
			<img src="https://fhs.com.au/wp-content/uploads/2026/04/Pickup-Own-Freight-Icon.png" style="height: 20px;" alt="">
			<span><?php esc_html_e( 'Pickup / Own Freight', 'woocommerce' ); ?></span>
		</button>
	</div>

	<input type="hidden" id="fhs_fulfilment_method" name="fhs_fulfilment_method" value="<?php echo esc_attr( $fulfilment_mode ); ?>" />

	<?php if ( WC()->cart->needs_shipping_address() ) : ?>
		<div id="fhs-delivery-panel" class="fhs-mode-panel">
			<div class="shipping_address">
				<input type="hidden" id="ship_to_different_address" name="ship_to_different_address" value="1" />

				<?php do_action( 'woocommerce_before_checkout_shipping_form', $checkout ); ?>

				<h3><?php esc_html_e( 'Shipping details', 'woocommerce' ); ?></h3>
								<?php if (!empty($user_shipping_addresses)): ?>
					<p class="form-row form-row-wide" id="thwma_saved_shipping_field">
						<label for="thwma_saved_shipping"><?php esc_html_e('Address Book', 'woocommerce'); ?></label>
						<span class="woocommerce-input-wrapper">
							<select id="thwma_saved_shipping" class="select" style="width:100%;">
								<option value=""><?php esc_html_e('Select an address', 'woocommerce'); ?></option>
								<option value="same_as_billing"><?php esc_html_e('Same as billing address', 'woocommerce'); ?>
								</option>
								<?php foreach ($user_shipping_addresses as $key => $address): ?>
									<?php
									$label = !empty($address['shipping_heading'])
										? $address['shipping_heading']
										: ucfirst(str_replace('_', ' ', (string) $key));
									?>
									<option value="<?php echo esc_attr($key); ?>">
										<?php echo esc_html($label); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</span>
					</p>
				<?php endif; ?>

				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox same-as-billing-toggle">
					<input id="fhs-use-same-as-billing-address-checkbox"
						class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" type="checkbox"
						name="fhs_use_same_as_billing_address" value="1" />
					<span><?php esc_html_e( 'Use same as billing address', 'woocommerce' ); ?></span>
				</label>

				<div class="shipping-options-row">
					<div class="residential-delivery-group">
						<span class="residential-delivery-label"><?php esc_html_e( 'Is this a Residential delivery?', 'woocommerce' ); ?></span>
						<label>
							<input type="radio" name="residential_delivery" value="yes">
							<?php esc_html_e( 'Yes', 'woocommerce' ); ?>
						</label>
						<label>
							<input type="radio" name="residential_delivery" value="no" checked>
							<?php esc_html_e( 'No', 'woocommerce' ); ?>
						</label>
					</div>
				</div>

				<div class="woocommerce-shipping-fields__field-wrapper">
					<?php
					$fields = $checkout->get_checkout_fields( 'shipping' );

					unset( $fields['shipping_address_2'], $fields['shipping_company'] );

					$shipping_field_order = array(
						'shipping_first_name' => 10,
						'shipping_last_name'  => 20,
						'shipping_address_1'  => 30,
						'shipping_city'       => 40,
						'shipping_state'      => 50,
						'shipping_postcode'   => 60,
						'shipping_country'    => 70,
					);

					foreach ( $fields as $key => $field ) {
						if ( isset( $shipping_field_order[ $key ] ) ) {
							$field['priority'] = $shipping_field_order[ $key ];
						}

						if ( 'shipping_country' === $key ) {
							$field['label'] = esc_html__( 'Country', 'woocommerce' );
						}

						if ( 'shipping_address_1' === $key ) {
							$field['label'] = esc_html__( 'Address', 'woocommerce' );
						}

						woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
					}
					?>
				</div>

				<?php do_action( 'woocommerce_after_checkout_shipping_form', $checkout ); ?>
			</div>
		</div>
	<?php endif; ?>

	<div id="fhs-pickup-panel" class="fhs-mode-panel fhs-pickup-panel">
		<p><strong><?php esc_html_e( 'Pickup from FHS Poly:', 'woocommerce' ); ?></strong> 11-15 Martha Street, Seaford, Victoria Australia 3198</p>
		<p><strong><?php esc_html_e( 'Standard Opening Hours', 'woocommerce' ); ?></strong>
			<?php esc_html_e( 'are Monday-Thursday 7.30am-4.30pm, Friday 7.30am-3pm. Closed Public Holidays.', 'woocommerce' ); ?>
		</p>
		<p><strong><?php esc_html_e( 'Forklift', 'woocommerce' ); ?></strong>
			<?php esc_html_e( 'loading available (only until 1pm Fridays)', 'woocommerce' ); ?></p><br>
		<p><?php esc_html_e( 'Once your order is packed and ready, we will get in contact via the information you have provided above for pick up or to provide package dimensions.', 'woocommerce' ); ?></p><br>
		<p><?php esc_html_e( 'If you have any queries, call us on 03 8770 5770.', 'woocommerce' ); ?></p>
	</div>
</div>

<div class="woocommerce-additional-fields">
	<?php do_action( 'woocommerce_before_order_notes', $checkout ); ?>

	<?php if ( apply_filters( 'woocommerce_enable_order_notes_field', 'yes' === get_option( 'woocommerce_enable_order_comments', 'yes' ) ) ) : ?>
		<?php if ( ! WC()->cart->needs_shipping() || wc_ship_to_billing_address_only() ) : ?>
			<h3><?php esc_html_e( 'Additional information', 'woocommerce' ); ?></h3>
		<?php endif; ?>

		<div class="woocommerce-additional-fields__field-wrapper">
			<?php foreach ( $checkout->get_checkout_fields( 'order' ) as $key => $field ) : ?>
				<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_order_notes', $checkout ); ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	var root = document.querySelector('.fhs-checkout-flow');
	if (!root) return;

	var modeInput = document.getElementById('fhs_fulfilment_method');
	var deliveryPanel = document.getElementById('fhs-delivery-panel');
	var pickupPanel = document.getElementById('fhs-pickup-panel');
	var sameAsBilling = document.getElementById('fhs-use-same-as-billing-address-checkbox');
	var checkoutUpdateTimer = null;

	var billingShippingPairs = [
		['billing_first_name', 'shipping_first_name'],
		['billing_last_name', 'shipping_last_name'],
		['billing_address_1', 'shipping_address_1'],
		['billing_city', 'shipping_city'],
		['billing_postcode', 'shipping_postcode'],
		['billing_country', 'shipping_country'],
		['billing_state', 'shipping_state']
	];

	window.fhsSyncingShippingFromBilling = false;
	window.fhsBlockCheckoutUpdate = false;

	function isSameAsBillingActive() {
		return !!(sameAsBilling && sameAsBilling.checked);
	}

	function getFieldValue(fieldId) {
		var el = document.getElementById(fieldId);
		if (!el) return '';
		return window.jQuery ? window.jQuery(el).val() || '' : el.value || '';
	}

	function setFieldValue(fieldId, value, triggerChange) {
		var el = document.getElementById(fieldId);
		if (!el) return;
		var val = value != null ? String(value) : '';
		if (window.jQuery) {
			var $el = window.jQuery(el);
			if ($el.val() !== val) {
				$el.val(val);
			}
			if (triggerChange) {
				$el.trigger('change');
			}
		} else if (el.value !== val) {
			el.value = val;
			if (triggerChange) {
				el.dispatchEvent(new Event('change', { bubbles: true }));
			}
		}
	}

	function shippingAlreadyMatchesBilling() {
		return billingShippingPairs.every(function (pair) {
			return getFieldValue(pair[1]) === getFieldValue(pair[0]);
		});
	}

	function refreshSelect2Display(fieldId) {
		if (!window.jQuery) return;
		var $field = window.jQuery('#' + fieldId);
		if ($field.length && $field.hasClass('select2-hidden-accessible')) {
			$field.trigger('change.select2');
		}
	}

	function queueCheckoutUpdate() {
		if (!window.jQuery || window.fhsBlockCheckoutUpdate) {
			return;
		}
		if (checkoutUpdateTimer) {
			window.clearTimeout(checkoutUpdateTimer);
		}
		checkoutUpdateTimer = window.setTimeout(function () {
			checkoutUpdateTimer = null;
			if (window.fhsBlockCheckoutUpdate) {
				return;
			}
			if (isSameAsBillingActive()) {
				syncShippingFromBilling({ triggerCheckout: false });
			}
			window.fhsBlockCheckoutUpdate = true;
			window.jQuery(document.body).one('updated_checkout.fhsSameAsBillingGuard', function () {
				window.fhsBlockCheckoutUpdate = false;
				if (isSameAsBillingActive()) {
					syncShippingFromBilling({ triggerCheckout: false });
				}
			});
			window.jQuery(document.body).trigger('update_checkout');
		}, 400);
	}

	/**
	 * Copy billing → shipping without triggering change (avoids update_order_review loop).
	 */
	function syncShippingFromBilling(options) {
		if (!isSameAsBillingActive()) {
			return;
		}

		var opts = options || {};

		if (shippingAlreadyMatchesBilling()) {
			if (opts.triggerCheckout) {
				queueCheckoutUpdate();
			}
			return;
		}

		window.fhsSyncingShippingFromBilling = true;
		window.fhsBlockCheckoutUpdate = true;

		billingShippingPairs.forEach(function (pair) {
			setFieldValue(pair[1], getFieldValue(pair[0]), false);
		});

		refreshSelect2Display('shipping_country');
		refreshSelect2Display('shipping_state');

		window.fhsSyncingShippingFromBilling = false;
		window.fhsBlockCheckoutUpdate = false;

		if (opts.triggerCheckout) {
			queueCheckoutUpdate();
		}
	}

	function setMode(mode) {
		var active = mode === 'pickup' ? 'pickup' : 'delivery';
		if (modeInput) modeInput.value = active;
		root.querySelectorAll('.fhs-fulfilment-btn').forEach(function (btn) {
			var on = btn.getAttribute('data-mode') === active;
			btn.classList.toggle('is-active', on);
			btn.setAttribute('aria-pressed', on ? 'true' : 'false');
		});
		if (deliveryPanel) deliveryPanel.style.display = active === 'delivery' ? 'block' : 'none';
		if (pickupPanel) pickupPanel.style.display = active === 'pickup' ? 'block' : 'none';
	}

	root.querySelectorAll('.fhs-fulfilment-btn').forEach(function (btn) {
		btn.addEventListener('click', function () {
			setMode(btn.getAttribute('data-mode'));
			if (window.jQuery) window.jQuery(document.body).trigger('update_checkout');
		});
	});

	if (sameAsBilling) {
		sameAsBilling.addEventListener('change', function () {
			if (this.checked) {
				syncShippingFromBilling({ triggerCheckout: true });
			}
		});
	}

	document.addEventListener('change', function (event) {
		if (!event.target || !isSameAsBillingActive() || window.fhsBlockCheckoutUpdate) {
			return;
		}
		var id = String(event.target.id || '');
		if (id.indexOf('billing_') !== 0) {
			return;
		}
		syncShippingFromBilling({ triggerCheckout: true });
	});

	setMode(modeInput ? modeInput.value : 'delivery');
});
</script>
