<?php
/**
 * Review order table
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/review-order.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 5.2.0
 */

defined( 'ABSPATH' ) || exit;

// Theme/plugins often add a second #place_order via these hooks — remove before payment.php runs.
remove_all_actions( 'woocommerce_review_order_before_submit' );
remove_all_actions( 'woocommerce_review_order_after_submit' );
remove_all_actions( 'woocommerce_review_order_after_order_total' );

// Coupon forms POST on full page load only — never during update_order_review AJAX.
if ( ! wp_doing_ajax() ) {
	if ( isset( $_POST['apply_coupon'] ) && ! empty( $_POST['coupon_code'] ) ) {
		$coupon_code = sanitize_text_field( wp_unslash( $_POST['coupon_code'] ) );
		if ( WC()->cart->has_discount( $coupon_code ) ) {
			wc_add_notice( 'Coupon applied.', 'notice' );
		} else {
			WC()->cart->apply_coupon( $coupon_code );
		}
	}

	if ( isset( $_POST['remove_coupon'] ) ) {
		foreach ( WC()->cart->get_applied_coupons() as $code ) {
			WC()->cart->remove_coupon( $code );
		}
		wc_add_notice( 'Coupon removed.', 'notice' );
	}
}
?>
<div class="woocommerce-checkout-review-order-grid woocommerce-checkout-review-order-table" style="display: grid; gap: 1rem;">
    <!-- Cart Items -->
    <div class="cart-items" style="display: grid; gap: 3rem;">
        <?php
        do_action('woocommerce_review_order_before_cart_contents');

        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);

            if ($_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters('woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key)) {
        ?>
                <div class="cart-item" style="display: grid; grid-template-columns: 100px 1fr auto; gap: 2rem; align-items: start;">
                    <div class="product-image">
                        <?php echo '<div>' . $_product->get_image() . '</div>'; ?>
                        <?php echo apply_filters('woocommerce_checkout_cart_item_quantity', '<span style="font-weight:500; font-size:1.4rem;" class="product-quantity">' . sprintf('× %s', $cart_item['quantity']) . '</strong>', $cart_item, $cart_item_key); ?>
                    </div>
                <div class="product-name">
                    <?php
                    // Product name
                    echo wp_kses_post(
                        apply_filters(
                            'woocommerce_cart_item_name',
                            $_product->get_name(),
                            $cart_item,
                            $cart_item_key
                        )
                    );
                
                    // SKU only (no variations/meta)
                    $sku = $_product->get_sku();
                    if ( $sku ) {
                        echo '<p class="product-sku" style="color:#8f8f8f; font-weight:400;">SKU: ' . esc_html( $sku ) . '</p>';
                    }
                    ?>
                </div>
                                    <div class="product-total" style="display:flex; flex-direction:column;">
                        <?php echo apply_filters('woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal($_product, $cart_item['quantity']), $cart_item, $cart_item_key); ?>
                        <span class="gst-message">(Ex GST)</span>
                    </div>
                </div>
        <?php
            }
        }

        do_action('woocommerce_review_order_after_cart_contents');
        ?>
    </div>

    <!-- Order Summary -->
    <div class="order-summary" style="display: grid; gap: 0.5rem;">
        <!-- Subtotal -->
  <div class="cart-summary" style="display: grid; gap: 10px;">

    <div class="cart-subtotal" style="display: grid; grid-template-columns: 1fr auto;">
        <?php $item_count = WC()->cart->get_cart_contents_count(); ?>
        <p>
          <span style="font-weight:500; font-size:1.4rem;">
            Subtotal (<?php echo $item_count . ' ' . ($item_count === 1 ? 'item' : 'items'); ?>):
          </span>
        </p>

        <p>
            <?php echo wc_price(WC()->cart->get_subtotal()); ?>
            <span class="gst-message">(Ex GST)</span>
        </p>
        
    </div>
    
      <?php if ( WC()->cart->get_applied_coupons() ) : ?>
      <div>
        <div class="remove-form" style="display: flex; justify-content: start;">
                   <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
            <div class="cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>" style="display: grid; grid-template-columns: 1fr auto 1fr; align-items:center; gap:5px;">
               <span style="font-weight:500; font-size:1.4rem;" style="font-size:1.4rem;">Coupon: </strong>
               <span><?php echo esc_html($coupon->get_code()); ?></span>
                 <button class="remove-coupon-btn" type="button" name="remove_coupon" value="<?php echo esc_attr( $coupon->get_code() ); ?>" style="padding: 0; background: none; color: red; width:fit-content; ">
                <span class="icofont icofont-bin"></span> 
            </button>
            </div>
        <?php endforeach; ?>

        
        </div>
        </div>
    <?php else : ?>
    <div>
        <div class="coupon-form">
            <input type="text" name="coupon_code" placeholder="Discount Code" required>
            <button type="button" name="apply_coupon">Apply</button>
        </div>
        </div>
    <?php endif; ?>

    <?php if ( WC()->cart->get_discount_total() > 0 ) : ?>
 
        <div class="cart-discount-total" style="display: grid; grid-template-columns: 1fr auto;">
            <p><span style="font-weight:500; font-size:1.4rem;">Coupon Discount:</strong></p>
            <p class="discount-amount-text">-<?php echo wc_price(WC()->cart->get_discount_total()); ?></p>
        </div>
    <?php endif; ?>

  



</div>

<script>
(function ($) {
	if (!$ || typeof wc_checkout_params === 'undefined') {
		return;
	}

	function getWcAjaxUrl(endpoint) {
		return wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%', endpoint);
	}

	function refreshCheckoutAfterCoupon(response) {
		if (response) {
			$('.woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message').remove();
			$('.woocommerce-notices-wrapper:first').html(response);
		}

		$(document.body).trigger('update_checkout');
	}

	$(document.body)
		.off('click.fhsCheckoutCouponApply')
		.on('click.fhsCheckoutCouponApply', '.coupon-form button[name="apply_coupon"]', function (event) {
			event.preventDefault();

			var $button = $(this);
			var $coupon = $button.closest('.coupon-form').find('input[name="coupon_code"]');
			var couponCode = $.trim($coupon.val() || '');

			if (!couponCode) {
				return;
			}

			$button.prop('disabled', true);

			$.ajax({
				type: 'POST',
				url: getWcAjaxUrl('apply_coupon'),
				data: {
					security: wc_checkout_params.apply_coupon_nonce,
					coupon_code: couponCode
				},
				complete: function () {
					$button.prop('disabled', false);
				},
				success: refreshCheckoutAfterCoupon
			});
		})
		.off('click.fhsCheckoutCouponRemove')
		.on('click.fhsCheckoutCouponRemove', '.remove-coupon-btn[name="remove_coupon"]', function (event) {
			event.preventDefault();

			var $button = $(this);
			var couponCode = $button.val();

			if (!couponCode) {
				return;
			}

			$button.prop('disabled', true);

			$.ajax({
				type: 'POST',
				url: getWcAjaxUrl('remove_coupon'),
				data: {
					security: wc_checkout_params.remove_coupon_nonce,
					coupon: couponCode
				},
				complete: function () {
					$button.prop('disabled', false);
				},
				success: refreshCheckoutAfterCoupon
			});
		});
})(window.jQuery);
</script>

        
        

        <!-- Coupons -->
        
        
                <!-- Taxes -->
        <?php if (wc_tax_enabled() && !WC()->cart->display_prices_including_tax()) : ?>
            <?php if ('itemized' === get_option('woocommerce_tax_total_display')) : ?>
                <?php foreach (WC()->cart->get_tax_totals() as $code => $tax) : ?>
                    <div class="tax-rate tax-rate-<?php echo esc_attr(sanitize_title($code)); ?>" style="display: grid; grid-template-columns: 1fr auto; padding-left: 30px !important; padding-right: 30px !important; padding-top: 20px !important;">
                        <span>GST</span>
                        <span><?php echo wp_kses_post($tax->formatted_amount); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="tax-total" style="display: grid; grid-template-columns: 1fr auto;">
                    <span><?php echo esc_html(WC()->countries->tax_or_vat()); ?></span>
                    <span><?php wc_cart_totals_taxes_total_html(); ?></span>
                </div>
            <?php endif; ?>
        <?php endif; ?>


        <!-- Shipping -->
       <div style="padding-left: 30px !important;
    padding-right: 30px !important;">
<?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>

    <?php
    $packages       = WC()->shipping()->get_packages();
    $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
    ?>

    <?php foreach ( $packages as $i => $package ) : ?>
        <?php
        $chosen_rate_id = isset( $chosen_methods[ $i ] ) ? $chosen_methods[ $i ] : '';
        if ( ! $chosen_rate_id || empty( $package['rates'][ $chosen_rate_id ] ) ) {
            $chosen_rate_id = ! empty( $package['rates'] ) ? key( $package['rates'] ) : '';
        }

        if ( ! $chosen_rate_id || empty( $package['rates'][ $chosen_rate_id ] ) ) {
            continue;
        }
        $chosen_method = $package['rates'][ $chosen_rate_id ];
        ?>

            <div class="shipping-total" style="display:grid; grid-template-columns:1fr auto; padding:10px 0 0;">
                
                <span style="font-weight:500; font-size:1.4rem;">
                    Shipping
                </span>

                <div>
                    <?php echo wc_price( $chosen_method->cost ); ?>
                    <span class="gst-message">(Inc GST)</span>
                </div>
                
            </div>

            <div class="shipping-method-label machship-message-container" style="padding: 0; font-size: 1.2rem; color: #666; text-align: right; width: 55%; margin-left: auto;">
                <span class="machship-message-text"><?php echo esc_html( $chosen_method->label ); ?></span>
            </div>

    <?php endforeach; ?>

    <?php
    // Keep shipping method inputs in the AJAX fragment (required by checkout.js).
    // Visually hidden — custom labels above remain the visible UI.
    ?>
    <div class="fhs-shipping-methods-source" aria-hidden="true" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">
        <?php do_action( 'woocommerce_review_order_before_shipping' ); ?>
        <?php wc_cart_totals_shipping_html(); ?>
        <?php do_action( 'woocommerce_review_order_after_shipping' ); ?>
    </div>

<?php endif; ?>
		</div>

        <!-- Fees -->
        <?php foreach (WC()->cart->get_fees() as $fee) : ?>
            <div class="fee" style="display: grid; grid-template-columns: 1fr auto;">
                <span><?php echo esc_html($fee->name); ?></span>
                <span><?php wc_cart_totals_fee_html($fee); ?></span>
            </div>
        <?php endforeach; ?>



        <?php do_action('woocommerce_review_order_before_order_total'); ?>
        
            <div class="cart-grandtotal" style="display: grid; grid-template-columns: 1fr auto;">
        <p><span style="font-weight:500; font-size:1.6rem;">Grand Total:</span></p>
        <div style="font-weight:700;">
            <?php wc_cart_totals_order_total_html(); ?>
            <span class="gst-message">(Inc GST)</span>
        </div>
        
    </div>

        <div class="secure-cart" style="padding:20px 30px 30px;">
			<i class="icofont-safety"></i>
			<p class="secure-payment">Safe and Secure Payments.<br>Trusted Australian Industry Supplier.</p>
		</div>

    </div>
</div>
