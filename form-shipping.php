<?php
defined('ABSPATH') || exit;

$user_shipping_addresses = [];
$fulfilment_mode         = '';

if (WC()->session) {
	$fulfilment_mode = sanitize_key((string) WC()->session->get('fhs_fulfilment_method', ''));
}

if (isset($_POST['fhs_fulfilment_method'])) {
	$fulfilment_mode = sanitize_key(wp_unslash($_POST['fhs_fulfilment_method']));
}

if (!in_array($fulfilment_mode, ['delivery', 'pickup'], true)) {
	$fulfilment_mode = '';
}

if (is_user_logged_in()) {
	$raw  = get_user_meta(get_current_user_id(), 'thwma_custom_address', true);
	$data = maybe_unserialize($raw);

	if (is_array($data) && !empty($data['shipping']) && is_array($data['shipping'])) {
		$user_shipping_addresses = $data['shipping'];
	}
}
?>

<div class="woocommerce-shipping-fields fhs-checkout-flow"
	data-current-mode="<?php echo esc_attr($fulfilment_mode); ?>">
	<div class="fhs-fulfilment-toggle-wrap">
		<button type="button" class="fhs-fulfilment-btn" data-mode="delivery" aria-pressed="false">
			<img src="https://fhs.com.au/wp-content/uploads/2026/04/Delivery-Icon.png" style="height: 20px;" alt="">
			<span><?php esc_html_e('Delivery', 'woocommerce'); ?></span>
		</button>
		<span class="fhs-fulfilment-or"><?php esc_html_e('or', 'woocommerce'); ?></span>
		<button type="button" class="fhs-fulfilment-btn" data-mode="pickup" aria-pressed="false">
			<img src="https://fhs.com.au/wp-content/uploads/2026/04/Pickup-Own-Freight-Icon.png" style="height: 20px;" alt="">
			<span><?php esc_html_e('Pickup / Own Freight', 'woocommerce'); ?></span>
		</button>
	</div>

	<input type="hidden" id="fhs_fulfilment_method" name="fhs_fulfilment_method"
		value="<?php echo esc_attr($fulfilment_mode); ?>" />

	<?php if (WC()->cart->needs_shipping_address()) : ?>
		<div id="fhs-delivery-panel" class="fhs-mode-panel">
			<div class="shipping_address">
				<input type="hidden" id="ship_to_different_address" name="ship_to_different_address" value="1" />

				<?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

				<h3><?php esc_html_e('Shipping details', 'woocommerce'); ?></h3>

				<?php if (!empty($user_shipping_addresses)) : ?>
					<p class="form-row form-row-wide" id="thwma_saved_shipping_field">
						<label for="thwma_saved_shipping"><?php esc_html_e('Address Book', 'woocommerce'); ?></label>
						<span class="woocommerce-input-wrapper">
							<select id="thwma_saved_shipping" class="select" style="width:100%;" disabled
								title="<?php esc_attr_e('Temporarily disabled while checkout address sync is rebuilt.', 'woocommerce'); ?>">
								<option value=""><?php esc_html_e('Select an address', 'woocommerce'); ?></option>
								<?php foreach ($user_shipping_addresses as $key => $address) : ?>
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
						name="fhs_use_same_as_billing_address" value="1" disabled
						title="<?php esc_attr_e('Temporarily disabled while checkout address sync is rebuilt.', 'woocommerce'); ?>" />
					<span><?php esc_html_e('Use same as billing address', 'woocommerce'); ?></span>
				</label>

				<div class="shipping-options-row">
					<div class="residential-delivery-group">
						<span class="residential-delivery-label"><?php esc_html_e('Is this a Residential delivery?', 'woocommerce'); ?></span>
						<label>
							<input type="radio" name="residential_delivery" value="yes">
							<?php esc_html_e('Yes', 'woocommerce'); ?>
						</label>
						<label>
							<input type="radio" name="residential_delivery" value="no" checked>
							<?php esc_html_e('No', 'woocommerce'); ?>
						</label>
					</div>
				</div>

				<div class="woocommerce-shipping-fields__field-wrapper">
					<?php
					$fields = $checkout->get_checkout_fields('shipping');

					unset($fields['shipping_address_2'], $fields['shipping_company']);

					$shipping_field_order = [
						'shipping_first_name'  => 10,
						'shipping_last_name'   => 20,
						'shipping_address_1'   => 30,
						'shipping_city'        => 40,
						'shipping_state'       => 50,
						'shipping_postcode'    => 60,
						'shipping_country'     => 70,
					];

					foreach ($fields as $key => $field) {
						$field['placeholder']       = '';
						$field['input_placeholder'] = '';

						if (!empty($field['custom_attributes']['data-placeholder'])) {
							$field['custom_attributes']['data-placeholder'] = '';
						}

						if (isset($shipping_field_order[ $key ])) {
							$field['priority'] = $shipping_field_order[ $key ];
						}

						if ('shipping_country' === $key) {
							$field['label'] = esc_html__('Country', 'woocommerce');
						}

						if ('shipping_address_1' === $key) {
							$field['label'] = esc_html__('Address', 'woocommerce');
						}

						woocommerce_form_field($key, $field, $checkout->get_value($key));
					}
					?>
				</div>

				<?php if (is_user_logged_in()) : ?>
					<p class="form-row form-row-wide fhs-save-address-book-row">
						<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
							<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
								type="checkbox" name="save_shipping_to_address_book" value="1" />
							<span><?php esc_html_e('Save this Address in my Address Book', 'woocommerce'); ?></span>
						</label>
					</p>
				<?php endif; ?>

				<?php do_action('woocommerce_after_checkout_shipping_form', $checkout); ?>
			</div>
		</div>
	<?php endif; ?>

	<div id="fhs-pickup-panel" class="fhs-mode-panel fhs-pickup-panel">
		<p><strong><?php esc_html_e('Pickup from FHS Poly:', 'woocommerce'); ?></strong> 11-15 Martha Street, Seaford,
			Victoria Australia 3198</p>
		<p><strong><?php esc_html_e('Standard Opening Hours', 'woocommerce'); ?></strong>
			<?php esc_html_e('are Monday-Thursday 7.30am-4.30pm, Friday 7.30am-3pm. Closed Public Holidays.', 'woocommerce'); ?>
		</p>
		<p><strong><?php esc_html_e('Forklift', 'woocommerce'); ?></strong>
			<?php esc_html_e('loading available (only until 1pm Fridays)', 'woocommerce'); ?></p><br>
		<p><?php esc_html_e('Once your order is packed and ready, we will get in contact via the information you have provided above for pick up or to provide package dimensions.', 'woocommerce'); ?>
		</p><br>
		<p><?php esc_html_e('If you have any queries, call us on 03 8770 5770.', 'woocommerce'); ?></p>
	</div>
</div>

<div class="woocommerce-additional-fields">
	<?php do_action('woocommerce_before_order_notes', $checkout); ?>

	<?php if (apply_filters('woocommerce_enable_order_notes_field', 'yes' === get_option('woocommerce_enable_order_comments', 'yes'))) : ?>
		<?php if (!WC()->cart->needs_shipping() || wc_ship_to_billing_address_only()) : ?>
			<h3><?php esc_html_e('Additional information', 'woocommerce'); ?></h3>
		<?php endif; ?>

		<div class="woocommerce-additional-fields__field-wrapper">
			<?php foreach ($checkout->get_checkout_fields('order') as $key => $field) : ?>
				<?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php do_action('woocommerce_after_order_notes', $checkout); ?>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		const root = document.querySelector('.fhs-checkout-flow');
		if (!root) {
			return;
		}

		const modeInput = document.getElementById('fhs_fulfilment_method');
		const buttons = root.querySelectorAll('.fhs-fulfilment-btn');
		const deliveryPanel = document.getElementById('fhs-delivery-panel');
		const pickupPanel = document.getElementById('fhs-pickup-panel');
		const shipToDifferent = document.getElementById('ship_to_different_address');

		const getSelectedShippingMethodInput = function (mode) {
			const methods = Array.from(document.querySelectorAll('input[name^="shipping_method"]'));
			if (!methods.length) {
				return null;
			}

			const pickupMethod = methods.find(function (input) {
				return /local_pickup|pickup/i.test(String(input.value || ''));
			});

			if (mode === 'pickup') {
				return pickupMethod || null;
			}

			return methods.find(function (input) {
				return input !== pickupMethod;
			}) || methods[0];
		};

		const setShippingMethodForMode = function (mode, silent) {
			if (mode !== 'delivery' && mode !== 'pickup') {
				return;
			}
			const target = getSelectedShippingMethodInput(mode);
			if (!target || target.checked) {
				return;
			}
			target.checked = true;
			if (!silent) {
				target.dispatchEvent(new Event('change', { bubbles: true }));
			}
		};

		const toggleShippingFieldState = function (enabled) {
			if (!deliveryPanel) {
				return;
			}

			const shippingFields = deliveryPanel.querySelectorAll(
				'input[name^="shipping_"], select[name^="shipping_"], textarea[name^="shipping_"], input[name="residential_delivery"], input[name="save_shipping_to_address_book"]'
			);

			shippingFields.forEach(function (field) {
				field.disabled = !enabled;
			});
		};

		const setMode = function (mode) {
			if (!modeInput) {
				return;
			}

			const activeMode = mode === 'pickup' ? 'pickup' : 'delivery';
			modeInput.value = activeMode;

			buttons.forEach(function (button) {
				const isActive = button.getAttribute('data-mode') === activeMode;
				button.classList.toggle('is-active', isActive);
				button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
			});

			if (deliveryPanel) {
				deliveryPanel.style.display = activeMode === 'delivery' ? 'block' : 'none';
			}

			if (pickupPanel) {
				pickupPanel.style.display = activeMode === 'pickup' ? 'block' : 'none';
			}

			toggleShippingFieldState(activeMode === 'delivery');
			setShippingMethodForMode(activeMode);
		};

		buttons.forEach(function (button) {
			button.addEventListener('click', function () {
				setMode(button.getAttribute('data-mode'));
				if (window.jQuery) {
					window.jQuery(document.body).trigger('update_checkout');
				}
			});
		});

		if (shipToDifferent) {
			shipToDifferent.value = '1';
		}

		if (window.jQuery) {
			window.jQuery(document.body).on('updated_checkout.fhsFulfilment', function () {
				setShippingMethodForMode(modeInput ? modeInput.value : 'delivery', true);
			});
		}

		setMode(modeInput && modeInput.value ? modeInput.value : 'delivery');
	});
</script>
