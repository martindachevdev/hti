<?php
/**
 * Cart toggle button
 */
function theme_add_cart_toggle_button() {
    if (!function_exists('WC') || !WC()->cart) {
        return;
    }
    
    $count = WC()->cart->get_cart_contents_count();
    ?>
    <button class="header-cart-toggle toggle-cart">
        <span class="cart-icon">
        <svg width="30px" height="auto" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
<path d="M22 2L2 8.66667L11.5833 12.4167M22 2L15.3333 22L11.5833 12.4167M22 2L11.5833 12.4167" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>
        </span>
        <?php if ($count > 0) : ?>
            <span class="cart-count"><?php echo esc_html($count); ?></span>
        <?php endif; ?>
    </button>
    <?php
}

/**
 * Render inquiry cart
 */
function theme_render_inquiry_cart() {
    if (!function_exists('WC') || !WC()->cart) {
        return;
    }
    
    $cart = WC()->cart;
    ?>
    <div id="floating-inquiry-cart" class="floating-cart">
        <div class="cart-header">
            <h3><?php esc_html_e('Моето запитване', 'storefront'); ?></h3>
            <button class="toggle-cart close-cart" aria-label="<?php esc_attr_e('Затвори', 'storefront'); ?>">&times;</button>
        </div>

        <?php if ($cart->is_empty()) : ?>
            <div class="cart-empty">
                <?php
                printf(
                    /* translators: %s: shop catalog URL */
                    esc_html__('Разгледайте нашия %sкаталог%s, за да добавите продукти за запитване.', 'storefront'),
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
                    if ($_product && $_product->exists() && $cart_item['quantity'] > 0) {
                        $product_permalink = apply_filters('woocommerce_cart_item_permalink', $_product->is_visible() ? $_product->get_permalink($cart_item) : '', $cart_item, $cart_item_key);
                        ?>
                        <div class="cart-item" data-key="<?php echo esc_attr($cart_item_key); ?>">
                            <?php
                            $thumbnail = apply_filters(
                                'woocommerce_cart_item_thumbnail',
                                $_product->get_image(),
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
                                        printf('<a href="%s">%s</a>', 
                                            esc_url($product_permalink),
                                            esc_html($_product->get_name())
                                        );
                                    } else {
                                        echo esc_html($_product->get_name());
                                    }

                                    // Display variation attributes
                                    if ($cart_item['variation_id']) {
                                        echo '<div class="variation">';
                                        foreach ($cart_item['variation'] as $key => $value) {
                                            $taxonomy = wc_attribute_taxonomy_name(str_replace('attribute_pa_', '', $key));
                                            $term = get_term_by('slug', $value, $taxonomy);
                                            $label = wc_attribute_label(str_replace('attribute_', '', $key), $_product);
                                            echo '<span>' . esc_html($label) . ': ' . esc_html($term ? $term->name : $value) . '</span>';
                                        }
                                        echo '</div>';
                                    }
                                    ?>
                                </h4>
                                
                                <div class="quantity">
                                    <button type="button" class="quantity-btn minus" aria-label="<?php esc_attr_e('Намали количеството', 'storefront'); ?>">-</button>
                                    <input type="number" 
                                        value="<?php echo esc_attr($cart_item['quantity']); ?>" 
                                        min="1" 
                                        aria-label="<?php esc_attr_e('Количество на продукта', 'storefront'); ?>"
                                    >
                                    <button type="button" class="quantity-btn plus" aria-label="<?php esc_attr_e('Увеличи количеството', 'storefront'); ?>">+</button>
                                </div>
                            </div>
                            
                            <button type="button" class="remove-item" aria-label="<?php esc_attr_e('Премахни продукта', 'storefront'); ?>">&times;</button>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            
            <div class="cart-footer">
                <button type="button" id="submit-inquiry" class="button alt">
                    <?php esc_html_e('Изпрати запитване', 'storefront'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * AJAX Handlers
 */
function theme_handle_update_cart_item() {
    check_ajax_referer('theme-inquiry-cart', 'nonce');

    if (!isset($_POST['key']) || !isset($_POST['qty'])) {
        wp_send_json_error();
    }

    WC()->cart->set_quantity(
        sanitize_text_field($_POST['key']),
        absint($_POST['qty'])
    );

    WC()->cart->calculate_totals();

    wp_send_json_success(array(
        'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array(
            '.header-cart-toggle' => theme_get_cart_button_html(),
            '#floating-inquiry-cart' => theme_get_cart_html()
        ))
    ));
}

function theme_handle_remove_cart_item() {
    check_ajax_referer('theme-inquiry-cart', 'nonce');

    if (!isset($_POST['key'])) {
        wp_send_json_error();
    }

    WC()->cart->remove_cart_item(sanitize_text_field($_POST['key']));
    
    wp_send_json_success(array(
        'fragments' => apply_filters('woocommerce_add_to_cart_fragments', array(
            '.header-cart-toggle' => theme_get_cart_button_html(),
            '#floating-inquiry-cart' => theme_get_cart_html()
        ))
    ));
}

function theme_handle_submit_inquiry() {
    check_ajax_referer('theme-inquiry-cart', 'nonce');
    if(!is_user_logged_in()){
        wp_send_json_error( 
            array(
                'redirect' => get_permalink( get_option('woocommerce_myaccount_page_id') )
            )
            );
        return;
    }
    try {
        $order = wc_create_order();
        $customer_id = get_current_user_id();

        // Set customer ID for the order
        $order->set_customer_id($customer_id);

        // Add products from cart
        foreach (WC()->cart->get_cart() as $cart_item) {
            $order->add_product(
                $cart_item['data'],
                $cart_item['quantity'],
                [
                    'variation' => $cart_item['variation'] ?? [],
                    'variation_id' => $cart_item['variation_id'] ?? 0
                ]
            );
        }

        // Get user data
        $user_data = get_userdata($customer_id);
        
        // Set billing information
        $billing_address = array(
            'first_name' => get_user_meta($customer_id, 'first_name', true) ?: $user_data->first_name,
            'last_name'  => get_user_meta($customer_id, 'last_name', true) ?: $user_data->last_name,
            'company'    => get_user_meta($customer_id, 'company_name', true),
            'email'      => $user_data->user_email,
            'phone'      => get_user_meta($customer_id, 'phone', true),
            'address_1'  => get_user_meta($customer_id, 'billing_address_1', true),
            'address_2'  => get_user_meta($customer_id, 'billing_address_2', true),
            'city'       => get_user_meta($customer_id, 'billing_city', true),
            'state'      => get_user_meta($customer_id, 'billing_state', true),
            'postcode'   => get_user_meta($customer_id, 'billing_postcode', true),
            'country'    => get_user_meta($customer_id, 'billing_country', true)
        );

        // Set billing data
        $order->set_address($billing_address, 'billing');
        
        // Set additional customer fields
        $customer_fields = array(
            'entity_type',
            'first_name',
            'last_name',
            'company_name',
            'egn_eik',
            'phone',
            'email_consent',
            'phone_consent'
        );

        foreach ($customer_fields as $field) {
            $value = get_user_meta($customer_id, $field, true);
            if ($value) {
                $order->update_meta_data($field, sanitize_text_field($value));
            }
        }

        // Set order status and save
        $order->set_status('inquiry');
        $order->save();

        // Empty cart
        WC()->cart->empty_cart();
 
        // Send customer email
        WC()->mailer()->get_emails()['WC_Email_Inquiry_Customer']->trigger($order->get_id());

        // Send admin new order email
        WC()->mailer()->get_emails()['WC_Email_Inquiry_Admin']->trigger($order->get_id());

        wp_send_json_success(array(
            'order_id' => $order->get_id(),
            'redirect' => $order->get_view_order_url()
        ));

    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => $e->getMessage()
        ));
    }
}

/**
 * Helper functions
 */
function theme_get_cart_button_html() {
    ob_start();
    theme_add_cart_toggle_button();
    return ob_get_clean();
}

function theme_get_cart_html() {
    ob_start();
    theme_render_inquiry_cart();
    return ob_get_clean();
}

/**
 * Add to cart script
 */
add_action('wp_footer', function() {
    if (!function_exists('WC') || !WC()->cart) return;
    ?>
    <script type="text/javascript">
    (function($) {
        // Variable products support
        $('.variations_form').on('show_variation', function(event, variation, purchasable) {
            if (variation.is_purchasable) {
                $(this).find('.single_add_to_cart_button')
                    .removeClass('disabled')
                    .attr('data-product_id', variation.variation_id);
            }
        });

        // Handle add to cart success
        $(document.body).on('added_to_cart', function(e, fragments, cart_hash) {
            if (fragments) {
                $.each(fragments, function(key, value) {
                    $(key).replaceWith(value);
                });
            }
            $('#floating-inquiry-cart').addClass('active');
            $('body').addClass('has-floating-cart');
        });
    })(jQuery);
    </script>
    <?php
}, 20);