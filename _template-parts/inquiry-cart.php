<?php
/**
 * Template part for displaying the inquiry cart
 *
 * @package YourTheme
 * 
 * Save as: template-parts/inquiry-cart.php
 */

defined('ABSPATH') || exit;

$cart = WC()->cart;
?>

<div id="floating-inquiry-cart" class="floating-cart">
    <div class="cart-header">
        <h3><?php esc_html_e('Inquiry Cart', 'your-theme-textdomain'); ?></h3>
        <button class="toggle-cart" aria-label="<?php esc_attr_e('Toggle cart', 'your-theme-textdomain'); ?>">&times;</button>
    </div>

    <?php if ($cart->is_empty()) : ?>
        <div class="cart-empty">
            <?php
            printf(
                /* translators: %s: shop catalog URL */
                esc_html__('Browse our %scatalog%s to add items for inquiry.', 'your-theme-textdomain'),
                '<a href="' . esc_url(get_permalink(wc_get_page_id('shop'))) . '">',
                '</a>'
            );
            ?>
        </div>
    <?php else : ?>
        <div class="cart-items">
            <?php
            foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
                $_product = apply_filters('woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key);
                $product_id = apply_filters('woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key);

                if ($_product && $_product->exists() && $cart_item['quantity'] > 0) {
                    $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                    ?>
                    <div class="cart-item" data-key="<?php echo esc_attr($cart_item_key); ?>">
                        <?php
                        $thumbnail = apply_filters(
                            'woocommerce_cart_item_thumbnail',
                            $_product->get_image('thumbnail'),
                            $cart_item,
                            $cart_item_key
                        );

                        if ($product_permalink) {
                            printf('<a href="%s">%s</a>', esc_url($product_permalink), $thumbnail);
                        } else {
                            echo $thumbnail;
                        }
                        ?>
                        
                        <div class="item-details">
                            <h4>
                                <?php
                                if ($product_permalink) {
                                    printf('<a href="%s">%s</a>', esc_url($product_permalink), 
                                        esc_html($_product->get_name())
                                    );
                                } else {
                                    echo esc_html($_product->get_name());
                                }
                                ?>
                            </h4>
                            
                            <div class="quantity">
                                <button type="button" class="quantity-btn minus">-</button>
                                <input type="number" 
                                    value="<?php echo esc_attr($cart_item['quantity']); ?>" 
                                    min="1" 
                                    aria-label="<?php esc_attr_e('Product quantity', 'your-theme-textdomain'); ?>"
                                >
                                <button type="button" class="quantity-btn plus">+</button>
                            </div>
                        </div>
                        
                        <button class="remove-item" aria-label="<?php esc_attr_e('Remove item', 'your-theme-textdomain'); ?>">&times;</button>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
        
        <div class="cart-footer">
            <button id="submit-inquiry" class="button alt">
                <?php esc_html_e('Submit Inquiry', 'your-theme-textdomain'); ?>
            </button>
        </div>
    <?php endif; ?>
</div>