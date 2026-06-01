<?php
defined('ABSPATH') || exit;

$user_shipping_addresses = [];
$fulfilment_mode = '';

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
	$raw = get_user_meta(get_current_user_id(), 'thwma_custom_address', true);
	$data = maybe_unserialize($raw);

	if (!empty($data['shipping']) && is_array($data['shipping'])) {
		$user_shipping_addresses = $data['shipping'];
	}
}
?>

<div class="woocommerce-shipping-fields fhs-checkout-flow"
	data-current-mode="<?php echo esc_attr($fulfilment_mode); ?>">
	<div class="fhs-fulfilment-toggle-wrap">
		<button type="button" class="fhs-fulfilment-btn" data-mode="delivery" aria-pressed="false">
			<!-- 			<i class="icofont-delivery-time"></i> -->
			<img src="https://fhs.com.au/wp-content/uploads/2026/04/Delivery-Icon.png" style="height: 20px;">

			<span><?php esc_html_e('Delivery', 'woocommerce'); ?></span>
		</button>
		<span class="fhs-fulfilment-or"><?php esc_html_e('or', 'woocommerce'); ?></span>
		<button type="button" class="fhs-fulfilment-btn" data-mode="pickup" aria-pressed="false">
			<!-- 			<i class="icofont-worker"></i> -->
			<img src="https://fhs.com.au/wp-content/uploads/2026/04/Pickup-Own-Freight-Icon.png" style="height: 20px;">
			<span><?php esc_html_e('Pickup / Own Freight', 'woocommerce'); ?></span>
		</button>
	</div>

	<input type="hidden" id="fhs_fulfilment_method" name="fhs_fulfilment_method"
		value="<?php echo esc_attr($fulfilment_mode); ?>" />

	<?php if (WC()->cart->needs_shipping_address()): ?>
		<div id="fhs-delivery-panel" class="fhs-mode-panel">
			<div class="shipping_address">
				<input type="hidden" id="ship_to_different_address" name="ship_to_different_address" value="1" />

				<?php do_action('woocommerce_before_checkout_shipping_form', $checkout); ?>

				<h3><?php esc_html_e('Shipping details', 'woocommerce'); ?></h3>

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
					<span><?php esc_html_e('Use same as billing address', 'woocommerce'); ?></span>
				</label>

				<div class="shipping-options-row">
					<div class="residential-delivery-group">
						<span
							class="residential-delivery-label"><?php esc_html_e('Is this a Residential delivery?', 'woocommerce'); ?></span>
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
						'shipping_first_name' => 10,
						'shipping_last_name' => 20,
						'shipping_address_1' => 30,
						'shipping_city' => 40,
						'shipping_state' => 50,
						'shipping_postcode' => 60,
						'shipping_country' => 70,
					];

					foreach ($fields as $key => $field) {
						$field['placeholder'] = '';
						$field['input_placeholder'] = '';

						if (!empty($field['custom_attributes']['data-placeholder'])) {
							$field['custom_attributes']['data-placeholder'] = '';
						}

						if (isset($shipping_field_order[$key])) {
							$field['priority'] = $shipping_field_order[$key];
						}

						if ('shipping_country' === $key) {
							$field['label'] = esc_html__('Country', 'woocommerce');
						}

						if ('shipping_address_1' === $key) {
							$field['label'] = esc_html__('Address', 'woocommerce');
						}

						$value = $checkout->get_value($key);
						woocommerce_form_field($key, $field, $value);
					}
					?>
				</div>

				<?php if (is_user_logged_in()): ?>
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
		<!-- 		<p><strong><?php esc_html_e('Standard Opening Hours', 'woocommerce'); ?></strong> <?php esc_html_e('are Monday-Thursday 7:30am-4:30pm, Friday 7:30am-3pm. Public Holidays Forklift loading available (only until 1pm Fridays)', 'woocommerce'); ?></p> -->
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

	<?php if (apply_filters('woocommerce_enable_order_notes_field', 'yes' === get_option('woocommerce_enable_order_comments', 'yes'))): ?>
		<?php if (!WC()->cart->needs_shipping() || wc_ship_to_billing_address_only()): ?>
			<h3><?php esc_html_e('Additional information', 'woocommerce'); ?></h3>
		<?php endif; ?>

		<div class="woocommerce-additional-fields__field-wrapper">
			<?php foreach ($checkout->get_checkout_fields('order') as $key => $field): ?>
				<?php woocommerce_form_field($key, $field, $checkout->get_value($key)); ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php do_action('woocommerce_after_order_notes', $checkout); ?>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		const STORAGE_KEY = 'checkout_state';
		const billingStateElement = document.getElementById('billing_state');

		if (billingStateElement) {

			// Store initial prefilled value from user profile
			localStorage.setItem(
				STORAGE_KEY,
				billingStateElement.value || ''
			);

			// Update when user changes state
			jQuery(billingStateElement).on('select2:select change', function (e) {
				const value = e.target.value || '';
				localStorage.setItem(STORAGE_KEY, value);
			});
		}

		const root = document.querySelector('.fhs-checkout-flow');
		if (!root) return;

		const modeInput = document.getElementById('fhs_fulfilment_method');
		const buttons = root.querySelectorAll('.fhs-fulfilment-btn');
		const deliveryPanel = document.getElementById('fhs-delivery-panel');
		const pickupPanel = document.getElementById('fhs-pickup-panel');
		const sameAsBilling = document.getElementById('fhs-use-same-as-billing-address-checkbox');
		const savedShipping = document.getElementById('thwma_saved_shipping');
		const shipToDifferent = document.getElementById('ship_to_different_address');
		const billingFieldIds = [
			'billing_first_name',
			'billing_last_name',
			'billing_country',
			'billing_address_1',
			'billing_city',
			// 		'billing_state',
			'billing_postcode'
		];
		const billingUserState = {};
		let syncGuardTimer = null;

		const savedData = <?php echo wp_json_encode($user_shipping_addresses); ?>;

		const getSelectedShippingMethodInput = function (mode) {
			const methods = Array.from(document.querySelectorAll('input[name^="shipping_method"]'));
			if (!methods.length) return null;

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

		const setShippingMethodForMode = function (mode) {
			if (mode !== 'delivery' && mode !== 'pickup') return;
			const target = getSelectedShippingMethodInput(mode);
			if (!target || target.checked) return;

			target.checked = true;
			target.dispatchEvent(new Event('change', { bubbles: true }));
		};

		const queueCheckoutUpdate = function () {
			if (!window.jQuery) return;
			if (syncGuardTimer) {
				window.clearTimeout(syncGuardTimer);
			}
			syncGuardTimer = window.setTimeout(function () {
				window.fhsAllowBillingTriggeredCheckoutUntil = Date.now() + 1200;
				window.jQuery(document.body).trigger('update_checkout');
			}, 120);
		};

		const ensureBillingAddressEditable = function () {
			billingFieldIds.forEach(function (fieldId) {
				const field = document.getElementById(fieldId);
				if (!field) return;
				field.disabled = false;
				field.readOnly = false;
			});
		};

		const captureBillingState = function () {
			billingFieldIds.forEach(function (fieldId) {
				const field = document.getElementById(fieldId);
				if (!field) return;
				billingUserState[fieldId] = field.value || '';
			});
		};

		const enforceBillingState = function () {
			billingFieldIds.forEach(function (fieldId) {
				const field = document.getElementById(fieldId);
				if (!field) return;
				const expected = Object.prototype.hasOwnProperty.call(billingUserState, fieldId) ? billingUserState[fieldId] : (field.value || '');
				if (field.value !== expected) {
					field.value = expected;
				}
			});
		};

		const toggleShippingFieldState = function (enabled) {
			if (!deliveryPanel) return;

			const shippingFields = deliveryPanel.querySelectorAll(
				'input[name^="shipping_"], select[name^="shipping_"], textarea[name^="shipping_"], input[name="residential_delivery"], input[name="save_shipping_to_address_book"], input[name="fhs_use_same_as_billing_address"], #thwma_saved_shipping'
			);

			shippingFields.forEach(function (field) {
				field.disabled = !enabled;
			});
		};

		const setMode = function (mode) {
			if (!modeInput) return;
			modeInput.value = mode;

			buttons.forEach(function (button) {
				const isActive = button.getAttribute('data-mode') === mode;
				button.classList.toggle('is-active', isActive);
				button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
			});

			if (deliveryPanel) {
				deliveryPanel.style.display = mode === 'delivery' ? 'block' : 'none';
			}

			if (pickupPanel) {
				pickupPanel.style.display = mode === 'pickup' ? 'block' : 'none';
			}

			toggleShippingFieldState(mode === 'delivery');
			setShippingMethodForMode(mode);
		};


		const syncShippingStateWithBilling = function () {

			const shippingState = document.getElementById('shipping_state');

			if (!shippingState) return;


			const storedValue = localStorage.getItem(STORAGE_KEY) || '';


			if (shippingState.value !== storedValue) {
				shippingState.value = storedValue;

				shippingState.dispatchEvent(new Event('change', { bubbles: true }));
			}
		};

		const syncShippingWithBilling = function (options) {
			const opts = options || {};
			const queueUpdate = opts.queueUpdate !== false;
			const pairs = [
				['billing_first_name', 'shipping_first_name'],
				['billing_last_name', 'shipping_last_name'],
				['billing_country', 'shipping_country'],
				['billing_address_1', 'shipping_address_1'],
				['billing_city', 'shipping_city'],
				// 			['billing_state', 'shipping_state'],
				['billing_postcode', 'shipping_postcode']
			];

			pairs.forEach(function (pair) {
				const billing = document.getElementById(pair[0]);
				const shipping = document.getElementById(pair[1]);
				if (!billing || !shipping) return;

				shipping.value = billing.value || '';
			});

			syncShippingStateWithBilling();

			if (queueUpdate) {
				queueCheckoutUpdate();
			}
			enforceBillingState();
			ensureBillingAddressEditable();
		};

		const normalizeCountryLabels = function () {
			['billing_country', 'shipping_country'].forEach(function (fieldId) {
				const label = document.querySelector('label[for="' + fieldId + '"]');
				if (!label) return;

				label.childNodes.forEach(function (node) {
					if (node.nodeType === Node.TEXT_NODE && node.nodeValue.indexOf('Country / Region') !== -1) {
						node.nodeValue = node.nodeValue.replace('Country / Region', 'Country');
					}
				});
			});
		};

		buttons.forEach(function (button) {
			button.addEventListener('click', function () {
				setMode(button.getAttribute('data-mode'));
				if (window.jQuery) {
					window.jQuery(document.body).trigger('update_checkout');
				}
			});
		});

		if (savedShipping) {
			savedShipping.addEventListener('change', function () {
				const key = this.value;


				if (key === 'same_as_billing') {
					if (sameAsBilling) {
						sameAsBilling.checked = true;
						syncShippingWithBilling();
					}
					ensureBillingAddressEditable();
					return;
				}

				if (!key || !savedData[key]) return;
				const selected = savedData[key];

				if (sameAsBilling) {
					sameAsBilling.checked = false;
				}

				['first_name', 'last_name', 'address_1', 'city', 'postcode', 'country', 'state'].forEach(function (field) {
					const input = document.getElementById('shipping_' + field);
					const value = selected['shipping_' + field] || '';
					if (!input) return;
					input.value = value;
					input.dispatchEvent(new Event('change', { bubbles: true }));
				});

				if (window.jQuery) {
					window.jQuery(document.body).trigger('update_checkout');
				}

				ensureBillingAddressEditable();
			});
		}

		if (sameAsBilling) {
			sameAsBilling.addEventListener('change', function () {
				if (this.checked) {
					syncShippingWithBilling();
				}
				ensureBillingAddressEditable();
			});
		}

		document.addEventListener('input', function (event) {
			if (!event.target) return;
			const targetId = String(event.target.id || '');
			if (targetId.startsWith('billing_')) {
				billingUserState[targetId] = event.target.value || '';
			}
			if (!sameAsBilling || !sameAsBilling.checked) return;
			if (!targetId.startsWith('billing_')) return;
			// Avoid per-keystroke checkout AJAX; sync values only.
			syncShippingWithBilling({ queueUpdate: false });
			ensureBillingAddressEditable();
		});

		document.addEventListener('change', function (event) {
			if (!event.target) return;
			const targetId = String(event.target.id || '');
			if (targetId.startsWith('billing_')) {
				billingUserState[targetId] = event.target.value || '';
			}
			if (!sameAsBilling || !sameAsBilling.checked) return;
			if (targetId.startsWith('billing_')) {
				syncShippingWithBilling();
				ensureBillingAddressEditable();
				return;
			}
			if (targetId.startsWith('shipping_')) {
				window.setTimeout(function () {
					enforceBillingState();
					ensureBillingAddressEditable();
				}, 0);
			}
		});

		if (shipToDifferent) {
			shipToDifferent.value = '1';
		}

		normalizeCountryLabels();
		captureBillingState();

		if (window.jQuery) {
			window.jQuery(document.body).on('updated_checkout', function () {
				normalizeCountryLabels();
				setShippingMethodForMode(modeInput ? modeInput.value : '');
				if (sameAsBilling && sameAsBilling.checked) {
					syncShippingStateWithBilling();
				}
				ensureBillingAddressEditable();
			});
		}

		setMode(modeInput && modeInput.value ? modeInput.value : '');
		captureBillingState();
		ensureBillingAddressEditable();
	});
</script>