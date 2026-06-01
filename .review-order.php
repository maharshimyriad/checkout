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
?>
<?php 


    if ( isset($_POST['apply_coupon']) && ! empty($_POST['coupon_code']) ) {
        $coupon_code = sanitize_text_field($_POST['coupon_code']);
        if ( WC()->cart->has_discount( $coupon_code ) ) {
            wc_add_notice( 'Coupon applied.', 'notice' );
        } else {
            WC()->cart->apply_coupon( $coupon_code );
            // wc_add_notice( 'Coupon applied!', 'success' );
        }
    }

    if ( isset($_POST['remove_coupon']) ) {
        foreach ( WC()->cart->get_applied_coupons() as $code ) {
            WC()->cart->remove_coupon( $code );
        }
        wc_add_notice( 'Coupon removed.', 'notice' );
    }

    // ✅ Force WooCommerce to recalculate totals for accuracy
    // Disable tax calculation for shipping (consistent with cart page)
    add_filter( 'woocommerce_shipping_rate_taxes', '__return_empty_array' );
    WC()->cart->calculate_totals();

    ob_start();
    wc_print_notices();
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
        <form method="post" class="remove-form" style="display: flex; justify-content: start;">
                   <?php foreach (WC()->cart->get_coupons() as $code => $coupon) : ?>
            <div class="cart-discount coupon-<?php echo esc_attr(sanitize_title($code)); ?>" style="display: grid; grid-template-columns: 1fr auto 1fr; align-items:center; gap:5px;">
               <span style="font-weight:500; font-size:1.4rem;" style="font-size:1.4rem;">Coupon: </strong>
               <span><?php echo esc_html($coupon->get_code()); ?></span>
                 <button class="remove-coupon-btn" type="submit" name="remove_coupon" style="padding: 0; background: none; color: red; width:fit-content; ">
                <span class="icofont icofont-bin"></span> 
            </button>
            </div>
        <?php endforeach; ?>

        
        </form>
        </div>
    <?php else : ?>
    <div>
        <form method="post" class="coupon-form">
            <input type="text" name="coupon_code" placeholder="Discount Code" required>
            <button type="submit" name="apply_coupon">Apply</button>
        </form>
        </div>
    <?php endif; ?>

    <?php if ( WC()->cart->get_discount_total() > 0 ) : ?>
 
        <div class="cart-discount-total" style="display: grid; grid-template-columns: 1fr auto;">
            <p><span style="font-weight:500; font-size:1.4rem;">Coupon Discount:</strong></p>
            <p class="discount-amount-text">-<?php echo wc_price(WC()->cart->get_discount_total()); ?></p>
        </div>
    <?php endif; ?>

  



</div>

        
        

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
    $packages = WC()->shipping()->get_packages();
    $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
    ?>

    <?php foreach ( $packages as $i => $package ) : ?>
        <?php if ( isset( $chosen_methods[ $i ] ) ) : ?>

            <?php
            $chosen_method = $package['rates'][ $chosen_methods[ $i ] ];
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

        <?php endif; ?>
    <?php endforeach; ?>

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
        <p><span style="font-weight:500; font-size:1.6rem;">Grand Total:</strong></p>
        <div style="font-weight:700;"><?php echo wc_price(WC()->cart->get_total('edit')); ?>
        <span class="gst-message">(Inc GST)</span>
        </div>
        
    </div>

        <div class="secure-cart" style="padding:20px 30px 30px;">
			<i class="icofont-safety"></i>
			<p class="secure-payment">Safe and Secure Payments.<br>Trusted Australian Industry Supplier.</p>
		</div>


        <!-- Place Order Button -->
        <div class="place-order" style="padding:0 30px;">
           <?php
            $total = WC()->cart->get_total('edit'); // Get raw total
            $formatted_total = strip_tags(wc_price($total)); // Get price text only (e.g., "$900.00")
            $button_class = 'button alt' . (wc_wp_theme_get_element_class_name('button') ? ' ' . esc_attr(wc_wp_theme_get_element_class_name('button')) : '');
            $button_text = esc_html__('Pay Now', 'woocommerce') . ' ' . $formatted_total;
            $button_value = esc_attr($button_text);
            
            echo apply_filters(
                'woocommerce_order_button_html',
                '<button style="width:100%;" type="submit" 
                    class="' . esc_attr($button_class) . '" 
                    name="woocommerce_checkout_place_order" 
                    id="place_order" 
                    value="' . $button_value . '" 
                    data-value="' . $button_value . '">' . $button_text . '</button>'
            );
            ?>

        </div>

        <?php do_action('woocommerce_review_order_after_order_total'); ?>
    </div>
</div>
