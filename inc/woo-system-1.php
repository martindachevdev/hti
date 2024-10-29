<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register inquiry order status
 */
function theme_register_inquiry_order_status() {
    register_post_status('wc-inquiry', array(
        'label' => _x('Inquiry', 'Order status', 'your-theme-textdomain'),
        'public' => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list' => true,
        'exclude_from_search' => false,
        'label_count' => _n_noop(
            'Inquiry <span class="count">(%s)</span>',
            'Inquiries <span class="count">(%s)</span>',
            'your-theme-textdomain'
        )
    ));
}

/**
 * Add inquiry status to order statuses
 */
function theme_add_inquiry_to_order_statuses($order_statuses) {
    $order_statuses['wc-inquiry'] = _x('Inquiry', 'Order status', 'your-theme-textdomain');
    return $order_statuses;
}

/**
 * Initialize WooCommerce modifications
 */
function theme_init_woocommerce_mods() {
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Price removal hooks
    add_filter('woocommerce_get_price_html', '__return_empty_string', 100);
    add_filter('woocommerce_cart_item_price', '__return_false');
    add_filter('woocommerce_cart_item_subtotal', '__return_false');
    add_filter('woocommerce_cart_subtotal', '__return_false');
    add_filter('woocommerce_cart_total', '__return_false');

    // Redirect checkout to cart
    add_action('template_redirect', 'theme_redirect_checkout_to_cart');
    
    // Frontend assets
    add_action('wp_enqueue_scripts', 'theme_enqueue_inquiry_cart_assets');
    
    // Cart display and toggle
    add_action('wp_footer', 'theme_render_inquiry_cart');
    add_action('wp_body_open', 'theme_add_cart_toggle_button');
    
    // Add to cart modifications
    add_filter('woocommerce_add_to_cart_fragments', 'theme_cart_button_fragment');
    add_filter('woocommerce_loop_add_to_cart_link', 'theme_add_to_cart_button', 10, 2);
    
    // Variable product support
    add_filter('woocommerce_available_variation', 'theme_modify_variation', 10, 3);
    
    // AJAX handlers
    add_action('wp_ajax_update_cart_item', 'theme_handle_update_cart_item');
    add_action('wp_ajax_remove_cart_item', 'theme_handle_remove_cart_item');
    add_action('wp_ajax_submit_inquiry', 'theme_handle_submit_inquiry');
    add_action('wp_ajax_nopriv_update_cart_item', 'theme_handle_update_cart_item');
    add_action('wp_ajax_nopriv_remove_cart_item', 'theme_handle_remove_cart_item');
    add_action('wp_ajax_nopriv_submit_inquiry', 'theme_handle_submit_inquiry');
    add_action('wp_ajax_woocommerce_add_to_cart_variable_rc', 'handle_add_to_cart_variable_rc');
    add_action('wp_ajax_nopriv_woocommerce_add_to_cart_variable_rc', 'handle_add_to_cart_variable_rc');
}

/**
 * Enqueue assets and setup WooCommerce support
 */
function theme_enqueue_inquiry_cart_assets() {
    $theme_version = wp_get_theme()->get('Version');
    
    // Enqueue main assets
    wp_enqueue_style(
        'theme-inquiry-cart',
        get_theme_file_uri('assets/css/inquiry-cart.css'),
        array(),
        $theme_version
    );

    wp_enqueue_script(
        'theme-inquiry-cart',
        get_theme_file_uri('assets/js/inquiry-cart.js'),
        array('jquery'),
        $theme_version,
        true
    );

    // Localize script
    wp_localize_script('theme-inquiry-cart', 'themeInquiryCart', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('theme-inquiry-cart'),
        'i18n' => array(
            'successMessage' => __('Вашето запитване е изпратено успешно!', 'your-theme-textdomain'),
            'errorMessage' => __('Грешка при изпращане на запитването. Моля, опитайте отново.', 'your-theme-textdomain'),
            'removeItemConfirm' => __('Сигурни ли сте, че искате да премахнете този продукт?', 'your-theme-textdomain'),
            'confirmSubmit' => __('Сигурни ли сте, че искате да изпратите това запитване?', 'your-theme-textdomain')
        )
    ));

    // WooCommerce support
    if (is_product()) {
        wp_enqueue_script('wc-add-to-cart-variation');
    }

    // WooCommerce AJAX parameters
    wp_localize_script('theme-inquiry-cart', 'wc_add_to_cart_params', array(
        'ajax_url' => WC()->ajax_url(),
        'wc_ajax_url' => \WC_AJAX::get_endpoint('%%endpoint%%'),
        'i18n_view_cart' => esc_attr__('View inquiry cart', 'your-theme-textdomain'),
        'cart_url' => apply_filters('woocommerce_add_to_cart_redirect', wc_get_cart_url()),
        'is_cart' => false,
        'cart_redirect_after_add' => false
    ));
}

/**
 * Modify variation data
 */
function theme_modify_variation($variation_data, $product, $variation) {
    $variation_data['add_to_cart_url'] = $product->get_permalink();
    $variation_data['add_to_cart_text'] = __('Add to Inquiry', 'your-theme-textdomain');
    return $variation_data;
}

/**
 * Handle variable product add to cart
 */
function handle_add_to_cart_variable_rc() {
    ob_start();

    $product_id = apply_filters('woocommerce_add_to_cart_product_id', absint($_POST['product_id']));
    $quantity = empty($_POST['quantity']) ? 1 : wc_stock_amount($_POST['quantity']);
    $variation_id = isset($_POST['variation_id']) ? absint($_POST['variation_id']) : '';
    $variations = isset($_POST['variation']) ? (array) $_POST['variation'] : array();

    $passed_validation = apply_filters('woocommerce_add_to_cart_validation', true, $product_id, $quantity, $variation_id, $variations);

    if ($passed_validation && WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variations)) {
        do_action('woocommerce_ajax_added_to_cart', $product_id);
        WC_AJAX::get_refreshed_fragments();
    } else {
        wp_send_json(array(
            'error' => true,
            'product_url' => apply_filters('woocommerce_cart_redirect_after_error', get_permalink($product_id), $product_id)
        ));
    }

    wp_die();
}

// Initialize
add_action('init', 'theme_register_inquiry_order_status');
add_filter('wc_order_statuses', 'theme_add_inquiry_to_order_statuses');
add_action('after_setup_theme', 'theme_init_woocommerce_mods');

// ... rest of your existing functions (theme_redirect_checkout_to_cart, theme_add_cart_toggle_button, etc.) ...

 

/**
 * Redirect checkout to cart
 */
function theme_redirect_checkout_to_cart() {
    if (is_checkout()) {
        wp_safe_redirect(wc_get_cart_url());
        exit;
    }
}

/**
 * Modify add to cart button text
 */
function theme_modify_add_to_cart_text() {
    return __('Add to Inquiry', 'your-theme-textdomain');
}

/**
 * Add cart fragments
 */
function theme_cart_button_fragment($fragments) {
    ob_start();
    theme_add_cart_toggle_button();
    $fragments['.header-cart-toggle'] = ob_get_clean();
    
    ob_start();
    theme_render_inquiry_cart();
    $fragments['#floating-inquiry-cart'] = ob_get_clean();
    
    return $fragments;
}

/**
 * Modify add to cart button
 */
function theme_add_to_cart_button($button, $product) {
    $button_text = __('Add to Inquiry', 'your-theme-textdomain');
    $button_classes = array(
        'button',
        'product_type_' . $product->get_type(),
        'add_to_cart_button'
    );
    
    if (!$product->is_type('variable')) {
        $button_classes[] = 'ajax_add_to_cart';
    }
    
    return sprintf(
        '<a href="%s" data-quantity="1" class="%s" %s data-product_id="%d" data-product_sku="%s" aria-label="%s" rel="nofollow">%s</a>',
        esc_url($product->add_to_cart_url()),
        esc_attr(implode(' ', $button_classes)),
        $product->is_type('variable') ? '' : 'data-variation_id=""',
        esc_attr($product->get_id()),
        esc_attr($product->get_sku()),
        esc_attr(sprintf(__('Add "%s" to inquiry', 'your-theme-textdomain'), $product->get_name())),
        esc_html($button_text)
    );
}





// Add this to your PHP file
add_action('wp_ajax_woocommerce_add_to_cart_variable_rc', 'handle_add_to_cart_variable_rc');
add_action('wp_ajax_nopriv_woocommerce_add_to_cart_variable_rc', 'handle_add_to_cart_variable_rc');

 
/**
 * Handle AJAX request to update cart display
 */
function theme_handle_update_cart_display() {
    check_ajax_referer('theme-inquiry-cart', 'nonce');

    // Ensure WC is loaded
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(['message' => __('WooCommerce cart is not available', 'your-theme-textdomain')]);
        return;
    }

    // Get cart information
    $cart_count = WC()->cart->get_cart_contents_count();
    $is_empty = ($cart_count === 0);

    // Get updated fragments
    ob_start();
    theme_add_cart_toggle_button();
    $cart_button_fragment = ob_get_clean();

    ob_start();
    theme_render_inquiry_cart();
    $cart_content_fragment = ob_get_clean();

    // Compile fragments
    $fragments = [
        '.header-cart-toggle' => $cart_button_fragment,
        '#floating-inquiry-cart' => $cart_content_fragment
    ];

    // Apply WooCommerce fragment filters
    $fragments = apply_filters('woocommerce_add_to_cart_fragments', $fragments);

    // Send response
    wp_send_json_success([
        'fragments' => $fragments,
        'cart_count' => $cart_count,
        'is_empty' => $is_empty
    ]);
}

// Register AJAX handlers for cart display update
add_action('wp_ajax_update_cart_display', 'theme_handle_update_cart_display');
add_action('wp_ajax_nopriv_update_cart_display', 'theme_handle_update_cart_display');