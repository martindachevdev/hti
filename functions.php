<?php

/**
 * Storefront automatically loads the core CSS even if using a child theme as it is more efficient
 * than @importing it in the child theme style.css file.
 *
 * Uncomment the line below if you'd like to disable the Storefront Core CSS.
 *
 * If you don't plan to dequeue the Storefront Core CSS you can remove the subsequent line and as well
 * as the sf_child_theme_dequeue_style() function declaration.
 */
//add_action( 'wp_enqueue_scripts', 'sf_child_theme_dequeue_style', 999 );

/**
 * Dequeue the Storefront Parent theme core CSS
 */

require 'inc/product-importer.php';
require 'inc/woo-system-1.php';
require 'inc/woo-system-2.php';

function sf_child_theme_dequeue_style()
{
    wp_dequeue_style('storefront-style');
    wp_dequeue_style('storefront-woocommerce-style');
}




/**
 * Note: DO NOT! alter or remove the code above this text and only add your custom PHP functions below this text.
 */


function remove_storefront_header_cart()
{
    remove_action('storefront_header', 'storefront_product_search', 40);
    remove_action('storefront_header', 'storefront_header_cart', 60);
    // $font_script = '<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">';
    // echo $font_script;

}
add_action('wp_head', 'remove_storefront_header_cart');


function get_my_search_form($args = array())
{
    /**
     * Fires before the search form is retrieved, at the start of get_search_form().
     *
     * @since 2.7.0 as 'get_search_form' action.
     * @since 3.6.0
     * @since 5.5.0 The `$args` parameter was added.
     *
     * @link https://core.trac.wordpress.org/ticket/19321
     *
     * @param array $args The array of arguments for building the search form.
     *                    See get_search_form() for information on accepted arguments.
     */
    do_action('pre_get_search_form', $args);

    $echo = true;

    if (!is_array($args)) {
        /*
         * Back compat: to ensure previous uses of get_search_form() continue to
         * function as expected, we handle a value for the boolean $echo param removed
         * in 5.2.0. Then we deal with the $args array and cast its defaults.
         */
        $echo = (bool) $args;

        // Set an empty array and allow default arguments to take over.
        $args = array();
    }

    // Defaults are to echo and to output no custom label on the form.
    $defaults = array(
        'echo' => $echo,
        'aria_label' => '',
    );

    $args = wp_parse_args($args, $defaults);

    /**
     * Filters the array of arguments used when generating the search form.
     *
     * @since 5.2.0
     *
     * @param array $args The array of arguments for building the search form.
     *                    See get_search_form() for information on accepted arguments.
     */
    $args = apply_filters('search_form_args', $args);

    // Ensure that the filtered arguments contain all required default values.
    $args = array_merge($defaults, $args);

    $format = current_theme_supports('html5', 'search-form') ? 'html5' : 'xhtml';

    /**
     * Filters the HTML format of the search form.
     *
     * @since 3.6.0
     * @since 5.5.0 The `$args` parameter was added.
     *
     * @param string $format The type of markup to use in the search form.
     *                       Accepts 'html5', 'xhtml'.
     * @param array  $args   The array of arguments for building the search form.
     *                       See get_search_form() for information on accepted arguments.
     */
    $format = apply_filters('search_form_format', $format, $args);

    $search_form_template = locate_template('searchform.php');

    if ('' !== $search_form_template) {
        ob_start();
        require $search_form_template;
        $form = ob_get_clean();
    } else {
        // Build a string containing an aria-label to use for the search form.
        if ($args['aria_label']) {
            $aria_label = 'aria-label="' . esc_attr($args['aria_label']) . '" ';
        } else {
            /*
             * If there's no custom aria-label, we can set a default here. At the
             * moment it's empty as there's uncertainty about what the default should be.
             */
            $aria_label = '';
        }

        if ('html5' === $format) {
            $form = '<form role="search" ' . $aria_label . 'method="get" class="search-form" style="display:none;" action="' . esc_url(home_url('/')) . '">
				<label>
					<span class="screen-reader-text">' .
                /* translators: Hidden accessibility text. */
                _x('Search for:', 'label') .
                '</span>
					<input type="search" class="search-field" placeholder="' . esc_attr_x('Search &hellip;', 'placeholder') . '" value="' . get_search_query() . '" name="s" />
				</label>
				<button type="submit" class="search-submit" value="' . esc_attr_x('Search', 'submit button') . '" ><svg viewBox="0 0 24 24">
                   <g>
	<path d="M11.996,0.195c-6.51,0-11.807,5.297-11.807,11.807c0,6.51,5.297,11.807,11.807,11.807c6.51,0,11.807-5.297,11.807-11.807
		C23.803,5.491,18.506,0.195,11.996,0.195z M11.996,22.808c-5.959,0-10.807-4.848-10.807-10.807S6.037,1.195,11.996,1.195
		s10.807,4.848,10.807,10.807S17.955,22.808,11.996,22.808z"></path>
	<path d="M18.145,11.809c-0.025-0.061-0.062-0.116-0.108-0.162l-4.745-4.744c-0.195-0.195-0.512-0.195-0.707,0s-0.195,0.512,0,0.707
		l3.892,3.891H6.201c-0.276,0-0.5,0.224-0.5,0.5s0.224,0.5,0.5,0.5h10.275l-3.892,3.892c-0.195,0.195-0.195,0.512,0,0.707
		c0.098,0.098,0.226,0.146,0.354,0.146s0.256-0.049,0.354-0.146l4.745-4.745c0.046-0.046,0.083-0.101,0.108-0.162
		c0.025-0.06,0.039-0.126,0.039-0.192S18.17,11.87,18.145,11.809z"></path>
</g>
                </svg></button>
			</form>';
        } else {
            $form = '<form role="search" ' . $aria_label . 'method="get" id="searchform" class="searchform" action="' . esc_url(home_url('/')) . '">
				<div>
					<label class="screen-reader-text" for="s">' .
                /* translators: Hidden accessibility text. */
                _x('Search for:', 'label') .
                '</label>
					<input type="text" value="' . get_search_query() . '" name="s" id="s" />
					<input type="submit" id="searchsubmit" value="' . esc_attr_x('Search', 'submit button') . '" />
				</div>
			</form>';
        }
    }

    /**
     * Filters the HTML output of the search form.
     *
     * @since 2.7.0
     * @since 5.5.0 The `$args` parameter was added.
     *
     * @param string $form The search form HTML output.
     * @param array  $args The array of arguments for building the search form.
     *                     See get_search_form() for information on accepted arguments.
     */
    $result = apply_filters('get_search_form', $form, $args);

    if (null === $result) {
        $result = $form;
    }

    if ($args['echo']) {
        echo $result;
    } else {
        return $result;
    }
}

function storefront_site_branding()
{
    ?>
    <div class="site-branding">
        <?php storefront_site_title_or_logo(); ?>
        <div class="site-utils">
            <div class="site-utils-cont">
                <a href="/shop" class="txt">HTI ONLINE+</button>
                    <a href="#" id="site-search-toggle" class="icon">
                        <svg viewBox="0 0 24 24" width="24" height="24">
                            <g>
                                <path d="M23.261,20.802l-6.12-6.12c1.019-1.484,1.619-3.277,1.619-5.209c0-5.087-4.139-9.226-9.225-9.226
                                c-5.087,0-9.226,4.139-9.226,9.226s4.139,9.226,9.226,9.226c1.932,0,3.726-0.6,5.209-1.619l6.12,6.12
                                c0.32,0.32,0.746,0.497,1.198,0.497c0.001,0,0.001,0,0.001-0.001c0.453,0,0.878-0.176,1.197-0.496
                                C23.922,22.539,23.922,21.464,23.261,20.802z M1.309,9.473c0-4.536,3.69-8.226,8.226-8.226s8.225,3.69,8.225,8.226
                                c0,4.535-3.689,8.226-8.225,8.226S1.309,14.009,1.309,9.473z M22.554,22.493c-0.131,0.131-0.305,0.203-0.49,0.203h-0.001
                                c-0.186,0-0.359-0.072-0.491-0.203l-6.031-6.031c0.352-0.303,0.68-0.631,0.983-0.983l6.031,6.031
                                C22.825,21.781,22.825,22.221,22.554,22.493z"></path>
                                <path
                                    d="M10.119,2.969C8.213,2.902,6.425,3.61,5.085,4.95C3.744,6.291,3.04,8.079,3.103,9.985c0.009,0.271,0.231,0.483,0.499,0.483
                                c0.006,0,0.011,0,0.017,0c0.276-0.009,0.492-0.24,0.483-0.516c-0.054-1.626,0.546-3.151,1.69-4.294
                                c1.143-1.143,2.681-1.733,4.294-1.69c0.294,0.038,0.507-0.207,0.516-0.483C10.611,3.208,10.395,2.977,10.119,2.969z">
                                </path>
                            </g>
                        </svg>
                    </a>
                    <a href="/uslugi/tehnicheska-biblioteka/" class="icon">
                        <svg version="1.1" id="FOLDERS" xmlns="http://www.w3.org/2000/svg"
                            xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="32px" viewBox="0 0 1800 1800"
                            enable-background="new 0 0 1800 1800" xml:space="preserve">
                            <g>
                                <g>
                                    <g>
                                        <path d="M404.006,1798.767H88.317c-46.674,0-84.646-37.972-84.646-84.646V85.879
                c0-46.674,37.972-84.646,84.646-84.646h315.688c46.669,0,84.637,37.972,84.637,84.646v1628.242
                C488.643,1760.795,450.675,1798.767,404.006,1798.767z M88.317,64.304c-11.896,0-21.575,9.679-21.575,21.575v1628.242
                c0,11.896,9.679,21.574,21.575,21.574h315.688c11.892,0,21.566-9.678,21.566-21.574V85.879c0-11.896-9.674-21.575-21.566-21.575
                H88.317z" />
                                    </g>
                                    <g>
                                        <path d="M246.157,1623.078c-69.556,0-126.142-56.586-126.142-126.141c0-69.557,56.586-126.143,126.142-126.143
                s126.142,56.586,126.142,126.143C372.299,1566.492,315.713,1623.078,246.157,1623.078z M246.157,1433.866
                c-34.778,0-63.07,28.293-63.07,63.071c0,34.777,28.292,63.07,63.07,63.07s63.071-28.293,63.071-63.07
                C309.228,1462.159,280.935,1433.866,246.157,1433.866z" />
                                    </g>
                                    <g>
                                        <path d="M335.149,726.573H157.165c-17.418,0-31.535-14.118-31.535-31.535c0-17.418,14.117-31.536,31.535-31.536
                h177.984c17.418,0,31.536,14.118,31.536,31.536C366.685,712.455,352.567,726.573,335.149,726.573z" />
                                    </g>
                                    <g>
                                        <path d="M335.149,591.422H157.165c-17.418,0-31.535-14.118-31.535-31.536s14.117-31.536,31.535-31.536h177.984
                c17.418,0,31.536,14.118,31.536,31.536S352.567,591.422,335.149,591.422z" />
                                    </g>
                                    <g>
                                        <path d="M335.149,456.27H157.165c-17.418,0-31.535-14.118-31.535-31.536c0-17.417,14.117-31.535,31.535-31.535
                h177.984c17.418,0,31.536,14.118,31.536,31.535C366.685,442.152,352.567,456.27,335.149,456.27z" />
                                    </g>
                                    <g>
                                        <path d="M335.149,321.119H157.165c-17.418,0-31.535-14.118-31.535-31.536c0-17.417,14.117-31.535,31.535-31.535
                h177.984c17.418,0,31.536,14.118,31.536,31.535C366.685,307.001,352.567,321.119,335.149,321.119z" />
                                    </g>
                                </g>
                                <g>
                                    <g>
                                        <path d="M1057.852,1798.767h-315.69c-46.674,0-84.646-37.972-84.646-84.646V85.879
                c0-46.674,37.972-84.646,84.646-84.646h315.69c46.67,0,84.637,37.972,84.637,84.646v1628.242
                C1142.488,1760.795,1104.521,1798.767,1057.852,1798.767z M742.162,64.304c-11.896,0-21.575,9.679-21.575,21.575v1628.242
                c0,11.896,9.679,21.574,21.575,21.574h315.69c11.893,0,21.566-9.678,21.566-21.574V85.879c0-11.896-9.674-21.575-21.566-21.575
                H742.162z" />
                                    </g>
                                    <g>
                                        <path d="M899.997,1623.078c-69.556,0-126.142-56.586-126.142-126.141c0-69.557,56.586-126.143,126.142-126.143
                c69.558,0,126.144,56.586,126.144,126.143C1026.141,1566.492,969.555,1623.078,899.997,1623.078z M899.997,1433.866
                c-34.778,0-63.071,28.293-63.071,63.071c0,34.777,28.293,63.07,63.071,63.07c34.78,0,63.073-28.293,63.073-63.07
                C963.07,1462.159,934.777,1433.866,899.997,1433.866z" />
                                    </g>
                                    <g>
                                        <path d="M988.992,726.573H811.005c-17.418,0-31.536-14.118-31.536-31.535c0-17.418,14.118-31.536,31.536-31.536
                h177.987c17.416,0,31.535,14.118,31.535,31.536C1020.527,712.455,1006.408,726.573,988.992,726.573z" />
                                    </g>
                                    <g>
                                        <path d="M988.992,591.422H811.005c-17.418,0-31.536-14.118-31.536-31.536s14.118-31.536,31.536-31.536h177.987
                c17.416,0,31.535,14.118,31.535,31.536S1006.408,591.422,988.992,591.422z" />
                                    </g>
                                    <g>
                                        <path d="M988.992,456.27H811.005c-17.418,0-31.536-14.118-31.536-31.536c0-17.417,14.118-31.535,31.536-31.535
                h177.987c17.416,0,31.535,14.118,31.535,31.535C1020.527,442.152,1006.408,456.27,988.992,456.27z" />
                                    </g>
                                    <g>
                                        <path d="M988.992,321.119H811.005c-17.418,0-31.536-14.118-31.536-31.536c0-17.417,14.118-31.535,31.536-31.535
                h177.987c17.416,0,31.535,14.118,31.535,31.535C1020.527,307.001,1006.408,321.119,988.992,321.119z" />
                                    </g>
                                </g>
                                <g>
                                    <g>
                                        <path d="M1711.691,1798.767h-315.688c-46.674,0-84.646-37.972-84.646-84.646V85.879
                c0-46.674,37.973-84.646,84.646-84.646h315.688c46.67,0,84.637,37.972,84.637,84.646v1628.242
                C1796.328,1760.795,1758.361,1798.767,1711.691,1798.767z M1396.004,64.304c-11.896,0-21.575,9.679-21.575,21.575v1628.242
                c0,11.896,9.679,21.574,21.575,21.574h315.688c11.892,0,21.566-9.678,21.566-21.574V85.879c0-11.896-9.675-21.575-21.566-21.575
                H1396.004z" />
                                    </g>
                                    <g>
                                        <path d="M1553.839,1623.078c-69.556,0-126.142-56.586-126.142-126.141c0-69.557,56.586-126.143,126.142-126.143
                s126.142,56.586,126.142,126.143C1679.98,1566.492,1623.395,1623.078,1553.839,1623.078z M1553.839,1433.866
                c-34.778,0-63.071,28.293-63.071,63.071c0,34.777,28.293,63.07,63.071,63.07s63.07-28.293,63.07-63.07
                C1616.909,1462.159,1588.617,1433.866,1553.839,1433.866z" />
                                    </g>
                                    <g>
                                        <path d="M1642.831,726.573h-177.985c-17.417,0-31.535-14.118-31.535-31.535c0-17.418,14.118-31.536,31.535-31.536
                h177.985c17.417,0,31.535,14.118,31.535,31.536C1674.366,712.455,1660.248,726.573,1642.831,726.573z" />
                                    </g>
                                    <g>
                                        <path d="M1642.831,591.422h-177.985c-17.417,0-31.535-14.118-31.535-31.536s14.118-31.536,31.535-31.536h177.985
                c17.417,0,31.535,14.118,31.535,31.536S1660.248,591.422,1642.831,591.422z" />
                                    </g>
                                    <g>
                                        <path d="M1642.831,456.27h-177.985c-17.417,0-31.535-14.118-31.535-31.536c0-17.417,14.118-31.535,31.535-31.535
                h177.985c17.417,0,31.535,14.118,31.535,31.535C1674.366,442.152,1660.248,456.27,1642.831,456.27z" />
                                    </g>
                                    <g>
                                        <path d="M1642.831,321.119h-177.985c-17.417,0-31.535-14.118-31.535-31.536c0-17.417,14.118-31.535,31.535-31.535
                h177.985c17.417,0,31.535,14.118,31.535,31.535C1674.366,307.001,1660.248,321.119,1642.831,321.119z" />
                                    </g>
                                </g>
                            </g>
                            <g>
                            </g>
                            <g>
                            </g>
                            <g>
                            </g>
                            <g>
                            </g>
                            <g>
                            </g>
                            <g>
                            </g>
                        </svg>

                    </a>
            </div>
            <?php get_my_search_form(); ?>
        </div>
    </div>
    <script>
        // Search toggle
        jQuery('#site-search-toggle').click(function ($) {
            jQuery('#site-search-toggle').toggleClass('active');
            jQuery('.search-form').slideToggle('fast', function () {
                if (jQuery('.search-form').is(':visible')) {
                    jQuery('.search-field').val("");
                    jQuery('.search-field').focus();
                }
            });
        }); 
    </script>
    <?php
}

// Add custom fields to the WooCommerce registration form
function custom_woocommerce_register_form_bg()
{
    ?>
    <p class="form-row form-row-wide">
        <label>
            <input type="radio" name="entity_type" value="person" checked>
            <?php _e('Физическо лице', 'woocommerce'); ?>
        </label>
        <label>
            <input type="radio" name="entity_type" value="company">
            <?php _e('Юридическо лице', 'woocommerce'); ?>
        </label>
    </p>

    <div id="person_fields" class="entity-fields">
        <p class="form-row form-row-first">
            <label for="reg_first_name"><?php _e('Име:', 'woocommerce'); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="first_name" id="reg_first_name"
                value="<?php if (!empty($_POST['first_name']))
                    echo esc_attr($_POST['first_name']); ?>" />
        </p>

        <p class="form-row form-row-last">
            <label for="reg_last_name"><?php _e('Фамилия:', 'woocommerce'); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="last_name" id="reg_last_name"
                value="<?php if (!empty($_POST['last_name']))
                    echo esc_attr($_POST['last_name']); ?>" />
        </p>
    </div>

    <div id="company_fields" class="entity-fields" style="display: none;">
        <p class="form-row form-row-wide">
            <label for="reg_company_name"><?php _e('Име на фирма:', 'woocommerce'); ?> <span
                    class="required">*</span></label>
            <input type="text" class="input-text" name="company_name" id="reg_company_name"
                value="<?php if (!empty($_POST['company_name']))
                    echo esc_attr($_POST['company_name']); ?>" />
        </p>
    </div>

    <p class="form-row form-row-wide" id="egn_eik_field">
        <label for="reg_egn_eik" id="egn_eik_label"><?php _e('ЕГН:', 'woocommerce'); ?> <span
                class="required">*</span></label>
        <input type="text" class="input-text" name="egn_eik" id="reg_egn_eik"
            value="<?php if (!empty($_POST['egn_eik']))
                echo esc_attr($_POST['egn_eik']); ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="billing_address_1"><?php _e('Адрес:', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_address_1" id="billing_address_1"
            placeholder="<?php _e('ул./бул., №, бл., вх., ап.', 'woocommerce'); ?>"
            value="<?php if (!empty($_POST['billing_address_1']))
                echo esc_attr($_POST['billing_address_1']); ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="billing_city"><?php _e('Град:', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_city" id="billing_city"
            value="<?php if (!empty($_POST['billing_city']))
                echo esc_attr($_POST['billing_city']); ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="billing_postcode"><?php _e('Пощенски код:', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="billing_postcode" id="billing_postcode"
            value="<?php if (!empty($_POST['billing_postcode']))
                echo esc_attr($_POST['billing_postcode']); ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="reg_phone"><?php _e('Телефон:', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="tel" class="input-text" name="phone" id="reg_phone"
            value="<?php if (!empty($_POST['phone']))
                echo esc_attr($_POST['phone']); ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="reg_email_consent">
            <input type="checkbox" name="email_consent" id="reg_email_consent" value="1" checked />
            <?php _e('Искам да получавам информация за обучение, промоции и новини относно офертата на HTI, чрез имейл адреса предоставен във формуляра. Можете да оттеглите съгласието си по всяко време.', 'woocommerce'); ?>
        </label>
    </p>

    <p class="form-row form-row-wide">
        <label for="reg_phone_consent">
            <input type="checkbox" name="phone_consent" id="reg_phone_consent" value="1" checked />
            <?php _e('Искам да получавам информация за обучения, промоции и новини относно офертата на HTI на посочения във формата телефон. Можете да оттеглите съгласието си по всяко време.', 'woocommerce'); ?>
        </label>
    </p>

    <script>
        jQuery(document).ready(function ($) {
            function updateEgnEikLabel(entityType) {
                var label = entityType === 'person' ? 'ЕГН:' : 'ЕИК:';
                $('#egn_eik_label').html(label + ' <span class="required">*</span>');

                var placeholder = entityType === 'person' ?
                    '<?php _e("Въведете 10 цифри", "woocommerce"); ?>' :
                    '<?php _e("Въведете 9 или 13 цифри", "woocommerce"); ?>';
                $('#reg_egn_eik').attr('placeholder', placeholder);
            }

            $('input[name="entity_type"]').change(function () {
                $('.entity-fields').hide();
                if ($(this).val() === 'person') {
                    $('#person_fields').show();
                } else {
                    $('#company_fields').show();
                }
                updateEgnEikLabel($(this).val());
            });

            // Set initial state
            updateEgnEikLabel('person');
        });
    </script>
    <?php
}
add_action('woocommerce_register_form', 'custom_woocommerce_register_form_bg');

// Validate the custom fields 
function custom_woocommerce_registration_errors_bg($errors, $username, $email)
{
    $entity_type = isset($_POST['entity_type']) ? $_POST['entity_type'] : 'person';

    if ($entity_type === 'person') {
        if (empty($_POST['first_name'])) {
            $errors->add('first_name_error', __('Моля, въведете вашето име.', 'woocommerce'));
        }
        if (empty($_POST['last_name'])) {
            $errors->add('last_name_error', __('Моля, въведете вашата фамилия.', 'woocommerce'));
        }
    } else {
        if (empty($_POST['company_name'])) {
            $errors->add('company_name_error', __('Моля, въведете име на фирмата.', 'woocommerce'));
        }
    }

    if (empty($_POST['billing_address_1'])) {
        $errors->add('address_error', __('Моля, въведете адрес.', 'woocommerce'));
    }

    if (empty($_POST['billing_city'])) {
        $errors->add('city_error', __('Моля, въведете град.', 'woocommerce'));
    }

    if (empty($_POST['billing_postcode'])) {
        $errors->add('postcode_error', __('Моля, въведете пощенски код.', 'woocommerce'));
    }

    if (empty($_POST['egn_eik'])) {
        $errors->add(
            'egn_eik_error',
            $entity_type === 'person' ?
            __('Моля, въведете ЕГН.', 'woocommerce') :
            __('Моля, въведете ЕИК.', 'woocommerce')
        );
    } else {
        $egn_eik = sanitize_text_field($_POST['egn_eik']);
        if ($entity_type === 'person') {
            if (!preg_match('/^[0-9]{10}$/', $egn_eik)) {
                $errors->add('egn_eik_format_error', __('Моля, въведете валидно ЕГН (10 цифри).', 'woocommerce'));
            }
        } else {
            if (!preg_match('/^[0-9]{9}$|^[0-9]{13}$/', $egn_eik)) {
                $errors->add('egn_eik_format_error', __('Моля, въведете валиден ЕИК (9 или 13 цифри).', 'woocommerce'));
            }
        }
    }

    if (empty($_POST['phone'])) {
        $errors->add('phone_error', __('Моля, въведете телефон.', 'woocommerce'));
    } else {
        $phone = sanitize_text_field($_POST['phone']);
        // Remove spaces, dashes, and parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

        // Simple validation - just check if it's a number with reasonable length
        if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
            $errors->add('phone_format_error', __('Моля, въведете валиден телефонен номер.', 'woocommerce'));
        }
    }

    return $errors;
}
add_filter('woocommerce_registration_errors', 'custom_woocommerce_registration_errors_bg', 10, 3);

// Save the custom fields
function custom_woocommerce_save_account_details_bg($customer_id)
{
    $fields = array(
        'entity_type' => 'text',
        'first_name' => 'text',
        'last_name' => 'text',
        'company_name' => 'text',
        'egn_eik' => 'text',
        'phone' => 'text',
        'email_consent' => 'checkbox',
        'phone_consent' => 'checkbox'
    );

    foreach ($fields as $field => $type) {
        if ($type === 'checkbox') {
            update_user_meta($customer_id, $field, isset($_POST[$field]) ? '1' : '0');
        } else {
            if (isset($_POST[$field])) {
                // Clean phone number before saving
                if ($field === 'phone') {
                    $phone = preg_replace('/[\s\-\(\)]/', '', $_POST[$field]);
                    update_user_meta($customer_id, $field, sanitize_text_field($phone));
                } else {
                    update_user_meta($customer_id, $field, sanitize_text_field($_POST[$field]));
                }
            }
        }
    }

    // Save billing address fields
    $billing_fields = array(
        'billing_address_1',
        'billing_city',
        'billing_postcode',
        'billing_country' => 'BG' // Set default country to Bulgaria
    );

    foreach ($billing_fields as $key => $value) {
        if (is_numeric($key)) {
            if (isset($_POST[$value])) {
                update_user_meta($customer_id, $value, sanitize_text_field($_POST[$value]));
            }
        } else {
            update_user_meta($customer_id, $key, $value);
        }
    }
}
add_action('woocommerce_created_customer', 'custom_woocommerce_save_account_details_bg');

// Display the custom fields on the account edit page
function custom_woocommerce_edit_account_form_bg() {
    $user_id = get_current_user_id();
    
    // Get user data
    $user_meta = array(
        'entity_type' => get_user_meta($user_id, 'entity_type', true),
        'first_name' => get_user_meta($user_id, 'first_name', true),
        'last_name' => get_user_meta($user_id, 'last_name', true),
        'company_name' => get_user_meta($user_id, 'company_name', true),
        'egn_eik' => get_user_meta($user_id, 'egn_eik', true),
        'phone' => get_user_meta($user_id, 'phone', true),
        'email_consent' => get_user_meta($user_id, 'email_consent', true),
        'phone_consent' => get_user_meta($user_id, 'phone_consent', true),
        'billing_address_1' => get_user_meta($user_id, 'billing_address_1', true),
        'billing_city' => get_user_meta($user_id, 'billing_city', true),
        'billing_postcode' => get_user_meta($user_id, 'billing_postcode', true)
    );
    ?>
    
    <p class="form-row form-row-wide">
        <label><?php _e('Тип потребител:', 'woocommerce'); ?></label>
        <label style="margin-right: 15px;">
            <input type="radio" name="account_entity_type" value="person" <?php checked($user_meta['entity_type'], 'person'); ?>>
            <?php _e('Физическо лице', 'woocommerce'); ?>
        </label>
        <label>
            <input type="radio" name="account_entity_type" value="company" <?php checked($user_meta['entity_type'], 'company'); ?>>
            <?php _e('Юридическо лице', 'woocommerce'); ?>
        </label>
    </p>

    <div id="account_person_fields" class="entity-fields" <?php echo $user_meta['entity_type'] !== 'person' ? 'style="display:none;"' : ''; ?>>
        <p class="form-row form-row-first">
            <label for="account_first_name"><?php _e('Име:', 'woocommerce'); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="account_first_name" id="account_first_name" value="<?php echo esc_attr($user_meta['first_name']); ?>" />
        </p>
        <p class="form-row form-row-last">
            <label for="account_last_name"><?php _e('Фамилия:', 'woocommerce'); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="account_last_name" id="account_last_name" value="<?php echo esc_attr($user_meta['last_name']); ?>" />
        </p>
    </div>

    <div id="account_company_fields" class="entity-fields" <?php echo $user_meta['entity_type'] !== 'company' ? 'style="display:none;"' : ''; ?>>
        <p class="form-row form-row-wide">
            <label for="account_company_name"><?php _e('Име на фирма:', 'woocommerce'); ?> <span class="required">*</span></label>
            <input type="text" class="input-text" name="account_company_name" id="account_company_name" value="<?php echo esc_attr($user_meta['company_name']); ?>" />
        </p>
    </div>

    <p class="form-row form-row-wide">
        <label for="account_egn_eik" id="account_egn_eik_label">
            <?php echo $user_meta['entity_type'] === 'person' ? __('ЕГН:', 'woocommerce') : __('ЕИК:', 'woocommerce'); ?>
            <span class="required">*</span>
        </label>
        <input type="text" class="input-text" name="account_egn_eik" id="account_egn_eik" value="<?php echo esc_attr($user_meta['egn_eik']); ?>" />
    </p>

    <!-- Add missing billing fields -->
    <p class="form-row form-row-wide">
        <label for="account_billing_address_1"><?php _e('Адрес:', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="account_billing_address_1" id="account_billing_address_1" 
               placeholder="<?php _e('ул./бул., №, бл., вх., ап.', 'woocommerce'); ?>"
               value="<?php echo esc_attr($user_meta['billing_address_1']); ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="account_billing_city"><?php _e('Град:', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="account_billing_city" id="account_billing_city" 
               value="<?php echo esc_attr($user_meta['billing_city']); ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="account_billing_postcode"><?php _e('Пощенски код:', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="text" class="input-text" name="account_billing_postcode" id="account_billing_postcode" 
               value="<?php echo esc_attr($user_meta['billing_postcode']); ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="account_phone"><?php _e('Телефон:', 'woocommerce'); ?> <span class="required">*</span></label>
        <input type="tel" class="input-text" name="account_phone" id="account_phone" value="<?php echo esc_attr($user_meta['phone']); ?>" />
    </p>

    <p class="form-row form-row-wide">
        <label for="account_email_consent">
            <input type="checkbox" name="account_email_consent" id="account_email_consent" value="1" <?php checked($user_meta['email_consent'], '1'); ?> />
            <?php _e('Искам да получавам информация за обучение, промоции и новини относно офертата на HTI, чрез имейл адреса предоставен във формуляра. Можете да оттеглите съгласието си по всяко време.', 'woocommerce'); ?>
        </label>
    </p>

    <p class="form-row form-row-wide">
        <label for="account_phone_consent">
            <input type="checkbox" name="account_phone_consent" id="account_phone_consent" value="1" <?php checked($user_meta['phone_consent'], '1'); ?> />
            <?php _e('Искам да получавам информация за обучения, промоции и новини относно офертата на HTI на посочения във формата телефон. Можете да оттеглите съгласието си по всяко време.', 'woocommerce'); ?>
        </label>
    </p>

    <script>
        jQuery(document).ready(function($) {
            function updateAccountEgnEikLabel(entityType) {
                var label = entityType === 'person' ? 'ЕГН:' : 'ЕИК:';
                $('#account_egn_eik_label').html(label + ' <span class="required">*</span>');

                var placeholder = entityType === 'person' ? 
                    '<?php _e("Въведете 10 цифри", "woocommerce"); ?>' : 
                    '<?php _e("Въведете 9 или 13 цифри", "woocommerce"); ?>';
                $('#account_egn_eik').attr('placeholder', placeholder);
            }

            $('input[name="account_entity_type"]').change(function() {
                $('.entity-fields').hide();
                if ($(this).val() === 'person') {
                    $('#account_person_fields').show();
                } else {
                    $('#account_company_fields').show();
                }
                updateAccountEgnEikLabel($(this).val());
            });

            // Set initial state
            updateAccountEgnEikLabel($('input[name="account_entity_type"]:checked').val());
        });
    </script>
    <?php
}
add_action('woocommerce_edit_account_form', 'custom_woocommerce_edit_account_form_bg');

 
// Validate all fields in account details form
add_filter('woocommerce_save_account_details_errors', function($errors, $user) {
    $entity_type = isset($_POST['account_entity_type']) ? sanitize_text_field($_POST['account_entity_type']) : '';

    // Entity Type Validation
    if (empty($entity_type) || !in_array($entity_type, array('person', 'company'))) {
        $errors->add('entity_type_error', __('Моля, изберете тип потребител.', 'woocommerce'));
    }

    // Person Fields Validation
    if ($entity_type === 'person') {
        if (empty($_POST['account_first_name'])) {
            $errors->add('first_name_error', __('Моля, въведете вашето име.', 'woocommerce'));
        }
        if (empty($_POST['account_last_name'])) {
            $errors->add('last_name_error', __('Моля, въведете вашата фамилия.', 'woocommerce'));
        }
    }

    // Company Fields Validation
    if ($entity_type === 'company') {
        if (empty($_POST['account_company_name'])) {
            $errors->add('company_name_error', __('Моля, въведете име на фирмата.', 'woocommerce'));
        }
    }

    // EGN/EIK Validation
    if (empty($_POST['account_egn_eik'])) {
        $errors->add(
            'egn_eik_error',
            $entity_type === 'person' ? 
                __('Моля, въведете ЕГН.', 'woocommerce') : 
                __('Моля, въведете ЕИК.', 'woocommerce')
        );
    } else {
        $egn_eik = sanitize_text_field($_POST['account_egn_eik']);
        if ($entity_type === 'person') {
            if (!preg_match('/^[0-9]{10}$/', $egn_eik)) {
                $errors->add('egn_eik_format_error', __('Моля, въведете валидно ЕГН (10 цифри).', 'woocommerce'));
            }
        } else {
            if (!preg_match('/^[0-9]{9}$|^[0-9]{13}$/', $egn_eik)) {
                $errors->add('egn_eik_format_error', __('Моля, въведете валиден ЕИК (9 или 13 цифри).', 'woocommerce'));
            }
        }
    }

    // Address Validation
    if (empty($_POST['account_billing_address_1'])) {
        $errors->add('billing_address_error', __('Моля, въведете адрес.', 'woocommerce'));
    }

    // City Validation
    if (empty($_POST['account_billing_city'])) {
        $errors->add('billing_city_error', __('Моля, въведете град.', 'woocommerce'));
    }

    // Postcode Validation
    if (empty($_POST['account_billing_postcode'])) {
        $errors->add('billing_postcode_error', __('Моля, въведете пощенски код.', 'woocommerce'));
    } else {
        $postcode = sanitize_text_field($_POST['account_billing_postcode']);
        if (!preg_match('/^[0-9]{4}$/', $postcode)) {
            $errors->add('billing_postcode_format_error', __('Моля, въведете валиден пощенски код (4 цифри).', 'woocommerce'));
        }
    }

    // Phone Validation
    if (empty($_POST['account_phone'])) {
        $errors->add('phone_error', __('Моля, въведете телефон.', 'woocommerce'));
    } else {
        $phone = sanitize_text_field($_POST['account_phone']);
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        if (!preg_match('/^\+?[0-9]{7,15}$/', $phone)) {
            $errors->add('phone_format_error', __('Моля, въведете валиден телефонен номер.', 'woocommerce'));
        }
    }

    return $errors;
}, 10, 2);

// Save account details with proper validation and sanitization
function custom_save_account_details($user_id) {
    // Verify nonce
    if (!isset($_POST['save-account-details-nonce']) || 
        !wp_verify_nonce($_POST['save-account-details-nonce'], 'save_account_details')) {
        return;
    }

    $entity_type = isset($_POST['account_entity_type']) ? 
        sanitize_text_field($_POST['account_entity_type']) : '';

    // Basic Fields
    $fields = array(
        'account_entity_type' => 'entity_type',
        'account_egn_eik' => 'egn_eik',
        'account_phone' => 'phone'
    );

    // Entity-specific fields
    if ($entity_type === 'person') {
        $fields['account_first_name'] = 'first_name';
        $fields['account_last_name'] = 'last_name';
    } else {
        $fields['account_company_name'] = 'company_name';
    }

    // Billing Fields
    $billing_fields = array(
        'account_billing_address_1' => 'billing_address_1',
        'account_billing_city' => 'billing_city',
        'account_billing_postcode' => 'billing_postcode'
    );

    // Consent Fields
    $consent_fields = array(
        'account_email_consent' => 'email_consent',
        'account_phone_consent' => 'phone_consent'
    );

    // Save Basic Fields
    foreach ($fields as $post_field => $meta_field) {
        if (isset($_POST[$post_field])) {
            $value = sanitize_text_field($_POST[$post_field]);
            if ($post_field === 'account_phone') {
                $value = preg_replace('/[\s\-\(\)]/', '', $value);
            }
            update_user_meta($user_id, $meta_field, $value);
        }
    }

    // Save Billing Fields
    foreach ($billing_fields as $post_field => $meta_field) {
        if (isset($_POST[$post_field])) {
            update_user_meta($user_id, $meta_field, sanitize_text_field($_POST[$post_field]));
        }
    }

    // Save Consent Fields
    foreach ($consent_fields as $post_field => $meta_field) {
        update_user_meta($user_id, $meta_field, isset($_POST[$post_field]) ? '1' : '0');
    }

    // Clear any cached user meta
    wp_cache_delete($user_id, 'user_meta');
}
remove_action('woocommerce_save_account_details', 'woocommerce_save_account_details');
add_action('woocommerce_save_account_details', 'custom_save_account_details');
 
// Save account details
function custom_woocommerce_save_account_details_bg_update($user_id)
{
    $fields = array(
        'account_entity_type' => 'entity_type',
        'account_first_name' => 'first_name',
        'account_last_name' => 'last_name',
        'account_company_name' => 'company_name',
        'account_egn_eik' => 'egn_eik',
        'account_phone' => 'phone',
        'account_email_consent' => 'email_consent',
        'account_phone_consent' => 'phone_consent'
    );

    foreach ($fields as $post_field => $meta_field) {
        if (strpos($post_field, 'consent') !== false) {
            update_user_meta($user_id, $meta_field, isset($_POST[$post_field]) ? '1' : '0');
        } else {
            if (isset($_POST[$post_field])) {
                if ($post_field === 'account_phone') {
                    $phone = preg_replace('/[\s\-\(\)]/', '', $_POST[$post_field]);
                    update_user_meta($user_id, $meta_field, sanitize_text_field($phone));
                } else {
                    update_user_meta($user_id, $meta_field, sanitize_text_field($_POST[$post_field]));
                }
            }
        }
    }
}
add_action('woocommerce_save_account_details', 'custom_woocommerce_save_account_details_bg_update');

// Add custom columns to admin users list
function custom_add_user_columns($columns)
{
    $columns['entity_type'] = __('Тип', 'woocommerce');
    $columns['egn_eik'] = __('ЕГН/ЕИК', 'woocommerce');
    $columns['phone'] = __('Телефон', 'woocommerce');
    return $columns;
}
add_filter('manage_users_columns', 'custom_add_user_columns');

// Fill custom columns in admin users list
function custom_show_user_columns($value, $column_name, $user_id)
{
    switch ($column_name) {
        case 'entity_type':
            $entity_type = get_user_meta($user_id, 'entity_type', true);
            return $entity_type === 'company' ? __('Фирма', 'woocommerce') : __('Физическо лице', 'woocommerce');
        case 'egn_eik':
            return get_user_meta($user_id, 'egn_eik', true);
        case 'phone':
            return get_user_meta($user_id, 'phone', true);
        default:
            return $value;
    }
}
add_filter('manage_users_custom_column', 'custom_show_user_columns', 10, 3);


add_filter('gettext', 'translate_registration_password_text', 20, 3);

function translate_registration_password_text($translated_text, $text, $domain)
{
    if ($domain === 'woocommerce') {
        if ($text === 'A link to set a new password will be sent to your email address.') {
            return 'След успешна регистрация, връзка за задаване на парола ще бъде изпратена на посочения имейл адрес.';
        }
    }
    return $translated_text;
}

function delete_all_products()
{
    $args = array(
        'post_type' => array('product', 'product_variation'),
        'posts_per_page' => -1,
        'post_status' => 'any'
    );

    $products = get_posts($args);

    foreach ($products as $product) {
        // Force delete (skip trash)
        wp_delete_post($product->ID, true);
    }

    // Clear product attributes
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}wc_product_meta_lookup");
    $wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_attribute_taxonomies");
    $wpdb->query("DELETE FROM {$wpdb->prefix}termmeta WHERE term_id IN (SELECT term_id FROM {$wpdb->prefix}terms WHERE term_id IN (SELECT term_id FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy LIKE 'pa_%'))");
    $wpdb->query("DELETE FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy LIKE 'pa_%'");
    $wpdb->query("DELETE FROM {$wpdb->prefix}terms WHERE term_id IN (SELECT term_id FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy LIKE 'pa_%')");

    // Clear transients
    wc_delete_product_transients();
    delete_transient('wc_attribute_taxonomies');
}

function create_all_variations($product_id)
{
    // Load WC utilities if not loaded
    if (!function_exists('wc_get_product')) {
        include_once(WP_PLUGIN_DIR . '/woocommerce/includes/wc-product-functions.php');
    }

    $product = wc_get_product($product_id);

    if (!$product || !$product->is_type('variable')) {
        return false;
    }

    // Using WC built-in AJAX function
    $data_store = $product->get_data_store();

    // This creates all possible variations
    $data_store->create_all_product_variations($product, 30); // 30 is max variations per run

    // This sets variation prices
    $data_store->update_product_variation_pricing($product);

    return true;
}


function create_variable_product($product_name, $product_description, $attributes, $variations)
{

    try {
        // Create the variable product
        $product = new WC_Product_Variable();
        $product->set_name($product_name);
        $product->set_description($product_description);
        $product->set_status('publish');
        $product->set_stock_status('instock');
        // Explicitly set blank SKU for parent product to prevent inheritance
        $product->set_sku('');
        // Create and set attributes
        $product_attributes = [];
        foreach ($attributes as $attr) {
            $attribute = new WC_Product_Attribute();
            $attribute->set_id(0);
            $attribute->set_name($attr['name']);
            $attribute->set_options($attr['options']);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            $product_attributes[] = $attribute;
        }
        $product->set_attributes($product_attributes);

        // Save the product to get an ID
        $product->save();
        $product_id = $product->get_id();

        // Create variations
        create_all_variations($product_id);
        // foreach ($variations as $variation_data) {
        //     $variation = new WC_Product_Variation();
        //     $variation->set_parent_id($product_id);

        //     // Convert model name to term slug for the attribute
        //     $variation_attributes = array();
        //     foreach ($variation_data['attributes'] as $attr_name => $value) {
        //         $taxonomy = 'pa_' . wc_sanitize_taxonomy_name($attr_name);
        //         $term_slug = sanitize_title($value);
        //         $variation_attributes[$taxonomy] = $term_slug;
        //     }

        //     $variation->set_attributes($variation_attributes);
        //     $variation->set_status('publish');
        //     $variation->set_sku($variation_data['sku']);
        //     $variation->set_price($variation_data['price']);
        //     $variation->set_regular_price($variation_data['price']);
        //     $variation->set_stock_status('instock');
        //     $variation->save();
        // }
        $av_variations = $product->get_available_variations();
        $i = 0;
        foreach ($av_variations as $variation) {

            $variation_obj = wc_get_product($variation['variation_id']);



            // Create SKU: PRODUCTID-VALUE1-VALUE2
            $sku = $variations[$i]['sku'];

            // Set and save SKU
            $variation_obj->set_sku($sku);
            $variation_obj->save();
            $i++;
        }
        // Final save of the parent product
        $product->save();

        return $product;
    } catch (Exception $e) {
        return new WP_Error('product_creation_failed', $e->getMessage());
    }
}
function generate_parent_sku($product_type)
{
    $transliteration = array(
        'а' => 'a',
        'б' => 'b',
        'в' => 'v',
        'г' => 'g',
        'д' => 'd',
        'е' => 'e',
        'ё' => 'e',
        'ж' => 'zh',
        'з' => 'z',
        'и' => 'i',
        'й' => 'y',
        'к' => 'k',
        'л' => 'l',
        'м' => 'm',
        'н' => 'n',
        'о' => 'o',
        'п' => 'p',
        'р' => 'r',
        'с' => 's',
        'т' => 't',
        'у' => 'u',
        'ф' => 'f',
        'х' => 'h',
        'ц' => 'ts',
        'ч' => 'ch',
        'ш' => 'sh',
        'щ' => 'sht',
        'ъ' => 'a',
        'ы' => 'i',
        'ь' => 'y',
        'э' => 'e',
        'ю' => 'yu',
        'я' => 'ya'
    );

    $latin = str_replace(
        array_keys($transliteration),
        array_values($transliteration),
        mb_strtolower($product_type)
    );

    $clean = preg_replace('/[^a-z0-9]+/', '-', $latin);
    $clean = trim($clean, '-');

    return $clean . '-parent';
}

function display_message($type, $message)
{
    $class = ($type === 'error') ? 'notice error' : 'notice';
    echo "<div class='$class'><p>$message</p></div>";
}

function display_import_results($import_stats) {
    echo '<div class="wrap">';
    echo '<h2>Резултати от импорта</h2>';

    // Show stats
    echo '<div class="notice notice-success"><p>';
    echo '<strong>Импортът е завършен:</strong><br>';
    echo '<ul style="list-style-type: disc; margin-left: 20px;">';
    echo '<li>Създадени продукти: ' . $import_stats['products_created'] . '</li>';
    echo '<li>Обновени продукти: ' . $import_stats['products_updated'] . '</li>';
    echo '<li>Създадени вариации: ' . $import_stats['variations_created'] . '</li>';
    echo '<li>Обновени вариации: ' . $import_stats['variations_updated'] . '</li>';
    echo '<li>Пропуснати: ' . $import_stats['skipped'] . '</li>';
    echo '</ul></p></div>';

    // Show errors if any
    if (!empty($import_stats['errors'])) {
        echo '<div class="notice notice-error">';
        echo '<h3>Грешки:</h3>';
        echo '<pre style="background: #fff; padding: 10px; overflow: auto; max-height: 300px;">';
        foreach ($import_stats['errors'] as $error) {
            echo htmlspecialchars($error) . "\n";
        }
        echo '</pre></div>';
    }

    // Show debug info if any
    // if (!empty($import_stats['debug'])) {
    //     echo '<div class="notice notice-info is-dismissible">';
    //     echo '<h3>Debug информация:</h3>';
    //     echo '<pre style="background: #fff; padding: 10px; overflow: auto; max-height: 300px;">';
    //     foreach ($import_stats['debug'] as $debug) {
    //         echo htmlspecialchars($debug) . "\n";
    //     }
    //     echo '</pre></div>';
    // }

    echo '</div>';

    // Add some basic styling
    echo '<style>
        .wrap .notice { margin: 10px 0; padding: 10px; }
        .wrap .notice h3 { margin-top: 0; }
        .wrap .notice pre { margin: 0; }
        .wrap ul { margin-bottom: 0; }
    </style>';
}


// Helper function to return 'instock'
function return_instock() {
    return 'instock';
}

// Force all products to be in stock and purchasable
add_filter('woocommerce_product_is_in_stock', '__return_true');
add_filter('woocommerce_product_backorders_allowed', '__return_true');
add_filter('woocommerce_product_is_purchasable', '__return_true');
add_filter('woocommerce_variation_is_purchasable', '__return_true');

// Hide stock management fields
add_filter('woocommerce_product_data_tabs', function($tabs) {
    unset($tabs['inventory']);
    return $tabs;
});

// Disable stock management globally
add_filter('pre_option_woocommerce_manage_stock', function() {
    return 'no';
});

// Force stock status to be "in stock"
add_filter('woocommerce_product_get_stock_status', 'return_instock', 100);
add_filter('woocommerce_product_variation_get_stock_status', 'return_instock', 100);
add_filter('woocommerce_variation_get_stock_status', 'return_instock', 100);

// Override availability text
add_filter('woocommerce_get_availability', function($availability, $product) {
    return array(
        'availability' => '',
        'class'        => 'in-stock',
    );
}, 100, 2);

// Remove "out of stock" message
add_filter('woocommerce_get_availability_text', '__return_empty_string', 100);

// Always allow adding to cart
add_filter('woocommerce_variation_is_active', '__return_true');
add_filter('woocommerce_variation_is_visible', '__return_true');

// Remove stock validation
add_filter('woocommerce_update_cart_validation', '__return_true', 10, 4);

// Force variations to be purchasable
add_filter('woocommerce_available_variation', function($variation_data, $product, $variation) {
    $variation_data['is_in_stock'] = true;
    $variation_data['is_purchasable'] = true;
    $variation_data['max_qty'] = '';
    $variation_data['stock_status'] = 'instock';
    unset($variation_data['availability_html']);
    return $variation_data;
}, 10, 3);

// Make sure all variations are shown as in stock in admin
add_action('woocommerce_product_after_variable_attributes', function($loop, $variation_data, $variation) {
    if ($variation) {
        update_post_meta($variation->ID, '_stock_status', 'instock');
    }
}, 10, 3);


add_action('init', 'add_editor_woo_caps');

function add_editor_woo_caps() {
    $role = get_role('editor');
    $role->add_cap('edit_products');
    $role->add_cap('edit_published_products');
    $role->add_cap('edit_others_products');
    $role->add_cap('read_products');

    // Order capabilities
    $role->add_cap('edit_shop_orders');
    $role->add_cap('read_shop_orders');
    $role->add_cap('edit_shop_order');
    $role->add_cap('edit_others_shop_orders');
    $role->add_cap('publish_shop_orders');
    $role->add_cap('read_private_shop_orders');
    $role->add_cap('edit_private_shop_orders');
    
    // General WooCommerce
    $role->add_cap('read_private_products');
    $role->add_cap('view_woocommerce_reports');
    // $role->add_cap('manage_woocommerce'); 
}


  // Register custom order status translations
  add_filter('woocommerce_register_shop_order_post_statuses', function($statuses) {
    $statuses['wc-inquiry'] = [
        'label' => _x('Запитване', 'Order status', 'woocommerce'),
        'public' => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list' => true,
        'exclude_from_search' => false,
        'label_count' => _n_noop(
            'Запитване <span class="count">(%s)</span>',
            'Запитвания <span class="count">(%s)</span>'
        )
    ];
    return $statuses;
});
 
 
// Add custom fields to admin order details
add_action('woocommerce_admin_order_data_after_billing_address', 'display_custom_fields_in_admin_order', 10, 1);
function display_custom_fields_in_admin_order($order) {
    $user_id = $order->get_user_id();
    if (!$user_id) return;

    $entity_type = get_user_meta($user_id, 'entity_type', true);
    $egn_eik = get_user_meta($user_id, 'egn_eik', true);
    $phone = get_user_meta($user_id, 'phone', true);
    $email_consent = get_user_meta($user_id, 'email_consent', true);
    $phone_consent = get_user_meta($user_id, 'phone_consent', true);

    echo '<div class="custom-field-section" style="margin-top: 20px;">';
    echo '<h3>Допълнителна информация</h3>';
    
    // Display entity type and corresponding fields
    echo '<p><strong>Тип потребител:</strong> ' . 
        ($entity_type === 'person' ? 'Физическо лице' : 'Юридическо лице') . '</p>';
    
    if ($entity_type === 'person') {
        $first_name = get_user_meta($user_id, 'first_name', true);
        $last_name = get_user_meta($user_id, 'last_name', true);
        echo '<p><strong>Име и фамилия:</strong> ' . 
            esc_html($first_name . ' ' . $last_name) . '</p>';
        echo '<p><strong>ЕГН:</strong> ' . esc_html($egn_eik) . '</p>';
    } else {
        $company_name = get_user_meta($user_id, 'company_name', true);
        echo '<p><strong>Име на фирма:</strong> ' . 
            esc_html($company_name) . '</p>';
        echo '<p><strong>ЕИК:</strong> ' . esc_html($egn_eik) . '</p>';
    }

    echo '<p><strong>Телефон:</strong> ' . esc_html($phone) . '</p>';

    // Display consent information
    echo '<h4 style="margin-top: 15px;">Съгласия</h4>';
    echo '<p><strong>Съгласие за имейл:</strong> ' . 
        ($email_consent ? 'Да' : 'Не') . '</p>';
    echo '<p><strong>Съгласие за телефон:</strong> ' . 
        ($phone_consent ? 'Да' : 'Не') . '</p>';
    echo '</div>';
}


if (!defined('ABSPATH')) {
    exit;
}



 
// Register new email classes
add_filter('woocommerce_email_classes', 'add_inquiry_email_classes');
function add_inquiry_email_classes($email_classes) {
    $theme_path = get_stylesheet_directory(); // Child theme path, falls back to parent if no child theme
 
    
    // Try child theme first, then parent theme
 
        include_once $theme_path . '/inc/emails/class-wc-email-inquiry-admin.php';
        include_once $theme_path . '/inc/emails/class-wc-email-inquiry-customer.php';
    
    
    $email_classes['WC_Email_Inquiry_Admin'] = new WC_Email_Inquiry_Admin();
    $email_classes['WC_Email_Inquiry_Customer'] = new WC_Email_Inquiry_Customer();
    
    return $email_classes;
}

// Also update template path handling in email classes
add_filter('woocommerce_template_directory', 'add_inquiry_template_directory', 10, 2);
function add_inquiry_template_directory($directory, $template) {
    if (strpos($template, 'inquiry') !== false) {
        return 'woocommerce';
    }
    return $directory;
}
 
add_action('storefront_sidebar', 'custom_storefront_sidebar', 5);
function custom_storefront_sidebar() {
    // Remove default sidebar
    remove_action('storefront_sidebar', 'storefront_get_sidebar', 10);
    
    // Only show sidebar on WooCommerce pages
    if (is_woocommerce() || is_shop() || is_product_category() || is_product_tag() || is_product() || is_cart() || is_checkout() || is_account_page()) {
        add_action('storefront_sidebar', 'storefront_get_sidebar', 10);
    }
}

 