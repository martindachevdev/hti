<?php
// Remove prices throughout the site
add_filter('woocommerce_get_price_html', 'remove_product_prices', 10, 2);
add_filter('woocommerce_cart_item_price', '__return_false');
add_filter('woocommerce_cart_item_subtotal', '__return_false');
add_filter('woocommerce_cart_subtotal', '__return_false');
add_filter('woocommerce_cart_total', '__return_false');

function remove_product_prices($price, $product) {
    return '';
}

// Redirect checkout page to cart
add_action('template_redirect', 'redirect_checkout_to_cart');
function redirect_checkout_to_cart() {
    if (is_checkout()) {
        wp_redirect(wc_get_cart_url());
        exit;
    }
}

// Add floating cart HTML
add_action('wp_footer', 'add_floating_inquiry_cart');
function add_floating_inquiry_cart() {
    ?>
    <div id="floating-inquiry-cart" class="floating-cart">
        <div class="cart-header">
            <h3>Inquiry List</h3>
            <span class="cart-count"><?php echo WC()->cart->get_cart_contents_count(); ?></span>
            <button class="toggle-cart">×</button>
        </div>
        <div class="cart-items">
            <?php
            foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
                $product = $cart_item['data'];
                $quantity = $cart_item['quantity'];
                ?>
                <div class="cart-item" data-key="<?php echo $cart_item_key; ?>">
                    <img src="<?php echo get_the_post_thumbnail_url($product->get_id(), 'thumbnail'); ?>" alt="">
                    <div class="item-details">
                        <h4><?php echo $product->get_name(); ?></h4>
                        <div class="quantity">
                            <button class="quantity-btn minus">-</button>
                            <input type="number" value="<?php echo $quantity; ?>" min="1">
                            <button class="quantity-btn plus">+</button>
                        </div>
                    </div>
                    <button class="remove-item">×</button>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="cart-footer">
            <button id="submit-inquiry" class="button">Submit Inquiry</button>
        </div>
    </div>
    <?php
}

// Add floating cart styles
add_action('wp_head', 'add_floating_cart_styles');
function add_floating_cart_styles() {
    ?>
    <style>
        .floating-cart {
            position: fixed;
            right: -400px;
            top: 0;
            width: 400px;
            height: 100vh;
            background: #fff;
            box-shadow: -2px 0 5px rgba(0,0,0,0.2);
            transition: right 0.3s;
            z-index: 999;
            display: flex;
            flex-direction: column;
        }
        .floating-cart.active {
            right: 0;
        }
        .cart-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 15px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .cart-item img {
            width: 50px;
            height: 50px;
            margin-right: 10px;
        }
        .quantity {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .quantity input {
            width: 50px;
            text-align: center;
        }
        .cart-footer {
            padding: 15px;
            border-top: 1px solid #eee;
        }
        #submit-inquiry {
            width: 100%;
            padding: 10px;
        }
    </style>
    <?php
}

// Add floating cart JavaScript
add_action('wp_footer', 'add_floating_cart_scripts');
function add_floating_cart_scripts() {
    ?>
    <script>
        jQuery(document).ready(function($) {
    $('.single_add_to_cart_button').click(function(e) {
        e.preventDefault();
        var form = $(this).closest('form.variations_form');
        var productID = form.find('input[name="product_id"]').val();
        var variationID = form.find('input.variation_id').val();
        var quantity = form.find('input[name="quantity"]').val();

        $.ajax({
            url: ajax_data.ajax_url,
            type: 'POST',
            data: {
                action: 'ajax_add_to_inquiry_list',
                product_id: productID,
                variation_id: variationID,
                quantity: quantity,
                nonce: ajax_data.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Product added to Inquiry List!');
                    $('#floating-inquiry-cart .cart-items').append(response.data.html);
                    $('.cart-count').text(response.data.inquiry_count);
                } else {
                    alert('Failed to add product to Inquiry List.');
                }
            }
        });
    });
});
    jQuery(document).ready(function($) {
        // Toggle cart
        $('.toggle-cart').click(function() {
            $('#floating-inquiry-cart').toggleClass('active');
        });

        // Update quantity
        $('.quantity-btn').click(function() {
            var input = $(this).siblings('input');
            var currentVal = parseInt(input.val());
            
            if ($(this).hasClass('minus') && currentVal > 1) {
                input.val(currentVal - 1);
            } else if ($(this).hasClass('plus')) {
                input.val(currentVal + 1);
            }
            
            updateCartItem($(this).closest('.cart-item').data('key'), input.val());
        });

        // Remove item
        $('.remove-item').click(function() {
            var key = $(this).closest('.cart-item').data('key');
            removeCartItem(key);
        });

        function updateCartItem(key, qty) {
            $.ajax({
                url: wc_add_to_cart_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_cart_item',
                    key: key,
                    qty: qty
                },
                success: function(response) {
                    updateFloatingCart();
                }
            });
        }

        function removeCartItem(key) {
            $.ajax({
                url: wc_add_to_cart_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'remove_cart_item',
                    key: key
                },
                success: function(response) {
                    updateFloatingCart();
                }
            });
        }

        function updateFloatingCart() {
            location.reload();
        }

        // Submit inquiry
        $('#submit-inquiry').click(function() {
            submitInquiry();
        });

        function submitInquiry() {
            $.ajax({
                url: wc_add_to_cart_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'submit_inquiry'
                },
                success: function(response) {
                    if (response.success) {
                        alert('Your inquiry has been submitted successfully!');
                        location.reload();
                    }
                }
            });
        }
    });
    </script>
    <?php
}

// Handle AJAX actions
add_action('wp_ajax_update_cart_item', 'handle_update_cart_item');
add_action('wp_ajax_remove_cart_item', 'handle_remove_cart_item');
add_action('wp_ajax_submit_inquiry', 'handle_submit_inquiry');

function handle_update_cart_item() {
    if (isset($_POST['key']) && isset($_POST['qty'])) {
        WC()->cart->set_quantity($_POST['key'], $_POST['qty']);
    }
    wp_die();
}

function handle_remove_cart_item() {
    if (isset($_POST['key'])) {
        WC()->cart->remove_cart_item($_POST['key']);
    }
    wp_die();
}

function handle_submit_inquiry() {
    // Create order
    $order = wc_create_order();
    
    // Add items from cart
    foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
        $order->add_product(
            $cart_item['data'],
            $cart_item['quantity']
        );
    }

    // Add customer details from user meta
    $customer_id = get_current_user_id();
    $fields = array(
        'entity_type',
        'first_name',
        'last_name',
        'company_name',
        'egn_eik',
        'phone',
        'email_consent',
        'phone_consent'
    );

    foreach ($fields as $field) {
        $value = get_user_meta($customer_id, $field, true);
        $order->update_meta_data($field, $value);
    }

    // Set order status to inquiry
    $order->update_status('inquiry');
    
    // Clear cart
    WC()->cart->empty_cart();
    
    wp_send_json_success();
}

// Register new order status
add_action('init', 'register_inquiry_order_status');
function register_inquiry_order_status() {
    register_post_status('wc-inquiry', array(
        'label' => 'Inquiry',
        'public' => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list' => true,
        'exclude_from_search' => false,
        'label_count' => _n_noop('Inquiry <span class="count">(%s)</span>', 'Inquiries <span class="count">(%s)</span>')
    ));
}

add_filter('wc_order_statuses', 'add_inquiry_to_order_statuses');
function add_inquiry_to_order_statuses($order_statuses) {
    $order_statuses['wc-inquiry'] = 'Inquiry';
    return $order_statuses;
}

add_action('wp_ajax_ajax_add_to_inquiry_list', 'ajax_add_to_inquiry_list_handler');
add_action('wp_ajax_nopriv_ajax_add_to_inquiry_list', 'ajax_add_to_inquiry_list_handler');

function ajax_add_to_inquiry_list_handler() {
    check_ajax_referer('ajax-inquiry-nonce', 'nonce');
    $product_id = intval($_POST['product_id']);
    $variation_id = intval($_POST['variation_id']);
    $quantity = intval($_POST['quantity']);

    // Using session to store inquiry list if not yet initialized
    if (!isset($_SESSION['inquiry_list'])) {
        $_SESSION['inquiry_list'] = array();
    }

    // Add item to the inquiry list session
    $inquiry_item = array('product_id' => $product_id, 'variation_id' => $variation_id, 'quantity' => $quantity);
    $_SESSION['inquiry_list'][] = $inquiry_item;

    // Generate HTML output for the added item
    $product = wc_get_product($product_id);
    $item_html = '<div class="cart-item" data-key="' . $product_id . '">';
    $item_html .= '<img src="' . get_the_post_thumbnail_url($product_id, 'thumbnail') . '" alt="">';
    $item_html .= '<div class="item-details"><h4>' . $product->get_name() . '</h4>';
    $item_html .= '<div class="quantity"><span>' . $quantity . '</span></div></div></div>';

    wp_send_json_success(array(
        'html' => $item_html,
        'inquiry_count' => count($_SESSION['inquiry_list'])
    ));
}
