/**
 * Save as: assets/js/inquiry-cart.js
 */

(function ($) {
    'use strict';
    var $variationForm = $(".variations_form");
            
    $variationForm.on("found_variation", function(event, variation) {
        var measureUnit = variation.measure_unit;
        if (measureUnit) {
            $(".variation-measure-unit").text(measureUnit);
            $(".quantity-wrapper").addClass("has-unit");
        } else {
            $(".variation-measure-unit").text("");
            $(".quantity-wrapper").removeClass("has-unit");
        }
    });
    
    $variationForm.on("reset_data", function() {
        $(".variation-measure-unit").text("");
        $(".quantity-wrapper").removeClass("has-unit");
    });

    $('.single_add_to_cart_button').removeClass('alt');
        
    // For variations form
    $('.variations_form').on('found_variation', function() {
        $('.single_add_to_cart_button').removeClass('alt');
    });
    
    // When variations are reset
    $('.variations_form').on('reset_data', function() {
        $('.single_add_to_cart_button').removeClass('alt');
    });
    const InquiryCart = {
        selectors: {
            cart: '#floating-inquiry-cart',
            toggleBtn: '.toggle-cart',
            quantityBtn: '.quantity-btn',
            removeBtn: '.remove-item',
            submitBtn: '#submit-inquiry',
            quantityInput: '.quantity input',
            cartItem: '.cart-item',
            addToCartBtn: '.add_to_cart_button',
            variationForm: '.variations_form',
            singleAddToCartBtn: '.single_add_to_cart_button'
        },

        isProcessing: false,
        isPageLoad: true,

        init() {
            this.bindEvents();
            this.initWooCommerce();
            this.initializeQuantityInputs();
             // Reset page load flag after init
             setTimeout(() => {
                this.isPageLoad = false;
            }, 2000);
        },

        bindEvents() {
            $(document.body).on('wc_fragments_refreshed', function(e) {
                console.log(e);
                self.openCart();
            });
            $('body').on('click', '.close-cart', function() {
               self.toggleCart();
            });
            const self = this;
            
             // Toggle cart button click
             $(document).on('click', this.selectors.toggleBtn, function (e) {
                const $target = $(e.target);
                const $toggleBtn = $(this);
                
                // Don't handle toggle if clicking on elements inside the cart
                if ($target.closest(self.selectors.cart).length) {
                    return;
                }
                
                // Don't handle if clicking on quantity buttons inside toggle button
                if ($target.closest(self.selectors.quantityBtn).length) {
                    return;
                }
                
                e.preventDefault();
                e.stopPropagation();
                self.toggleCart();
            });

            // Quantity buttons
            $(document).on('click', `${this.selectors.cart} ${this.selectors.quantityBtn}`, function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $btn = $(this);
                const $input = $btn.siblings('input');
                let qty = parseInt($input.val(), 10);

                if ($btn.hasClass('minus') && qty > 1) {
                    qty--;
                } else if ($btn.hasClass('plus')) {
                    qty++;
                }

                $input.val(qty).trigger('change');
            });

            // Quantity input change
            $(document).on('change', `${this.selectors.cart} ${this.selectors.quantityInput}`, function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $input = $(this);
                const qty = Math.max(1, parseInt($input.val(), 10) || 1);
                const $item = $input.closest(self.selectors.cartItem);

                $input.val(qty);
                if ($item.length) {
                    self.updateCartItem($item.data('key'), qty);
                }
            });

            // Remove item
            $(document).on('click', this.selectors.removeBtn, function (e) {
                e.preventDefault();
                e.stopPropagation();
                const $item = $(this).closest(self.selectors.cartItem);
                if (confirm(themeInquiryCart.i18n.removeItemConfirm)) {
                    self.removeCartItem($item.data('key'));
                }
            });

            // Submit inquiry
            $(document).on('click', this.selectors.submitBtn, function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!self.isProcessing && confirm(themeInquiryCart.i18n.confirmSubmit)) {
                    self.submitInquiry();
                }
            });

            // Close cart when clicking outside
            $(document).on('click', function (e) {
                const $target = $(e.target);
                
                // Don't close if:
                // 1. Cart is not active
                // 2. Clicking inside the cart
                // 3. Clicking on a toggle button
                // 4. Clicking on a quantity button
                // 5. Clicking on any interactive elements
                if (!$(self.selectors.cart).hasClass('active') ||
                    $target.closest(self.selectors.cart).length ||
                    $target.closest(self.selectors.toggleBtn).length ||
                    $target.closest(self.selectors.quantityBtn).length ||
                    $target.closest('button, input, select, a').length) {
                    return;
                }
                
                self.closeCart();
            });

            // Close cart with escape key
            $(document).on('keyup', function (e) {
                if (e.key === 'Escape' && $(self.selectors.cart).hasClass('active')) {
                    self.closeCart();
                }
            });

            // Prevent cart closing when clicking inside
            $(this.selectors.cart).on('click', function (e) {
                e.stopPropagation();
            });
        },

        initWooCommerce() {
            const self = this;
        
            // Handle WooCommerce events
            $(document.body).on('added_to_cart removed_from_cart updated_cart_totals', (e, fragments) => {
                self.updateCartDisplay();
                if (fragments) {
                    self.updateFragments(fragments);
                }
                self.openCart();
                self.isProcessing = false;
            });
        
            // Handle variable products
            $(document).on('show_variation', this.selectors.variationForm, function(event, variation) {
                if (variation && variation.variation_id) {
                    const $form = $(this);
                    const $button = $form.find(self.selectors.singleAddToCartBtn);
                    
                    $button
                        .removeClass('disabled')
                        .attr('data-variation_id', variation.variation_id)
                        .attr('data-product_id', $form.find('input[name="product_id"]').val());
                }
            });
        
            // Handle variable product add to cart
            $(document).on('click', this.selectors.singleAddToCartBtn, function(e) {
                const $button = $(this);
                const $form = $button.closest('form');
                
                if ($form.hasClass('variations_form') && !self.isProcessing) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if ($button.hasClass('disabled')) {
                        return;
                    }
            
                    self.isProcessing = true;
                    self.showLoader();
                    
                    // Using form serialization consistently
                    const formData = $form.serialize();
                    
                    $.ajax({
                        url: wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart'),
                        data: formData + '&action=woocommerce_add_to_cart_variable_rc',
                        type: 'POST',
                        success: function(response) {
                            if (!response) {
                                return;
                            }
                            
                            if (response.error && response.product_url) {
                                window.location = response.product_url;
                                return;
                            }
                            
                            // Trigger event
                            $(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash, $button]);
                        },
                        complete: function() {
                            self.hideLoader();
                            self.isProcessing = false;
                        }
                    });
                }
            });
        
            // Handle AJAX add to cart loading states
            $(document.body).on('adding_to_cart', () => {
                self.showLoader();
            }).on('added_to_cart', () => {
                self.hideLoader();
            });
        },

        initializeQuantityInputs() {
            $(this.selectors.quantityInput).each(function () {
                const $input = $(this);
                $input.attr('min', '1')
                    .on('input', function () {
                        this.value = this.value.replace(/[^\d]/g, '');
                        if (this.value < 1) this.value = 1;
                    })
                    .on('blur', function () {
                        if (!this.value) this.value = 1;
                    });
            });
        },

        toggleCart() {
            const $cart = $(this.selectors.cart);
            $cart.toggleClass('active');
            $('body').toggleClass('has-floating-cart');
        },

        openCart() {
            if(this.isPageLoad) return;
            const $cart = $(this.selectors.cart);
            $cart.addClass('active');
            $('body').addClass('has-floating-cart');
        },

        closeCart() {
            const $cart = $(this.selectors.cart);
            $cart.removeClass('active');
            $('body').removeClass('has-floating-cart');
        },

        showLoader() {
            $(this.selectors.cart).addClass('loading');
        },

        hideLoader() {
            $(this.selectors.cart).removeClass('loading');
        },

        updateCartItem(key, qty) {
            if (!key || this.isProcessing) return;

            this.isProcessing = true;
            this.showLoader();

            $.ajax({
                url: themeInquiryCart.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'update_cart_item',
                    nonce: themeInquiryCart.nonce,
                    key: key,
                    qty: qty
                },
                success: (response) => {
                    if (response.success && response.data.fragments) {
                        this.updateFragments(response.data.fragments);
                    }
                },
                error: () => {
                    alert(themeInquiryCart.i18n.errorMessage);
                },
                complete: () => {
                    this.hideLoader();
                    this.isProcessing = false;
                }
            });
        },

        removeCartItem(key) {
            if (!key || this.isProcessing) return;

            this.isProcessing = true;
            this.showLoader();

            $.ajax({
                url: themeInquiryCart.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'remove_cart_item',
                    nonce: themeInquiryCart.nonce,
                    key: key
                },
                success: (response) => {
                    if (response.success && response.data.fragments) {
                        this.updateFragments(response.data.fragments);
                    }
                },
                error: () => {
                    alert(themeInquiryCart.i18n.errorMessage);
                },
                complete: () => {
                    this.hideLoader();
                    this.isProcessing = false;
                }
            });
        },

        submitInquiry() {
            if (this.isProcessing) return;

            this.isProcessing = true;
            this.showLoader();

            $.ajax({
                url: themeInquiryCart.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'submit_inquiry',
                    nonce: themeInquiryCart.nonce
                },
                success: (response) => {
                        if(response.data && response.success) {
                            alert(themeInquiryCart.i18n.successMessage);
                        } else{
                            alert(themeInquiryCart.i18n.errorMessage);
                        }
                        window.location.href = response.data.redirect;
                },
                error: () => {
                    alert(themeInquiryCart.i18n.errorMessage);
                },
                complete: () => {
                    this.hideLoader();
                    this.isProcessing = false;
                }
            });
        },

        updateFragments(fragments) {
            if (!fragments) return;

            // Store cart state before fragment update
            const wasActive = $(this.selectors.cart).hasClass('active');
            const wasLoading = $(this.selectors.cart).hasClass('loading');

            $.each(fragments, (selector, content) => {
                // Special handling for cart fragment to preserve state
                if (selector === this.selectors.cart) {
                    const $newContent = $(content);
                    if (wasActive) {
                        $newContent.addClass('active');
                    }
                    if (wasLoading) {
                        $newContent.addClass('loading');
                    }
                    content = $newContent.prop('outerHTML');
                }
                $(selector).replaceWith(content);
            });

            // Reinitialize inputs after fragment update
            this.initializeQuantityInputs();

            // Restore body class if cart was active
            if (wasActive) {
                $('body').addClass('has-floating-cart');
            }

            // Trigger WooCommerce event
            $(document.body).trigger('wc_fragments_refreshed');
        },


        updateCartDisplay() {
            // Store cart state
            const $cart = $(this.selectors.cart);
            const wasActive = $cart.hasClass('active');
            
            // Don't update if already processing
            // if (this.isProcessing) return;

            this.isProcessing = true;
            this.showLoader();

            $.ajax({
                url: themeInquiryCart.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'update_cart_display',
                    nonce: themeInquiryCart.nonce
                },
                success: (response) => {
                    if (response.success) {
                        if (response.data.fragments) {
                            this.updateFragments(response.data.fragments);
                        }
                        
                        // Handle empty cart state
                        if (response.data.is_empty) {
                            this.closeCart();
                        } 

                        // Update cart counter if available
                        if (response.data.cart_count !== undefined) {
                            const $counter = $('.cart-counter');
                            if ($counter.length) {
                                $counter.text(response.data.cart_count);
                                $counter.toggleClass('hidden', response.data.cart_count === 0);
                            }
                        }
                    }
                },
                error: () => {
                    console.error('Failed to update cart display');
                },
                complete: () => {
                    this.hideLoader();
                    this.isProcessing = false;

                    // Final state check to ensure cart state is correct
                    const $updatedCart = $(this.selectors.cart);
             
                }
            });
        },
    };

    // Initialize on document ready
    $(document).ready(() => InquiryCart.init());

})(jQuery);


/*
* jquery-match-height 0.7.2 by @liabru
* http://brm.io/jquery-match-height/
* License MIT
*/
!function(t){"use strict";"function"==typeof define&&define.amd?define(["jquery"],t):"undefined"!=typeof module&&module.exports?module.exports=t(require("jquery")):t(jQuery)}(function(t){var e=-1,o=-1,n=function(t){return parseFloat(t)||0},a=function(e){var o=1,a=t(e),i=null,r=[];return a.each(function(){var e=t(this),a=e.offset().top-n(e.css("margin-top")),s=r.length>0?r[r.length-1]:null;null===s?r.push(e):Math.floor(Math.abs(i-a))<=o?r[r.length-1]=s.add(e):r.push(e),i=a}),r},i=function(e){var o={
byRow:!0,property:"height",target:null,remove:!1};return"object"==typeof e?t.extend(o,e):("boolean"==typeof e?o.byRow=e:"remove"===e&&(o.remove=!0),o)},r=t.fn.matchHeight=function(e){var o=i(e);if(o.remove){var n=this;return this.css(o.property,""),t.each(r._groups,function(t,e){e.elements=e.elements.not(n)}),this}return this.length<=1&&!o.target?this:(r._groups.push({elements:this,options:o}),r._apply(this,o),this)};r.version="0.7.2",r._groups=[],r._throttle=80,r._maintainScroll=!1,r._beforeUpdate=null,
r._afterUpdate=null,r._rows=a,r._parse=n,r._parseOptions=i,r._apply=function(e,o){var s=i(o),h=t(e),l=[h],c=t(window).scrollTop(),p=t("html").outerHeight(!0),u=h.parents().filter(":hidden");return u.each(function(){var e=t(this);e.data("style-cache",e.attr("style"))}),u.css("display","block"),s.byRow&&!s.target&&(h.each(function(){var e=t(this),o=e.css("display");"inline-block"!==o&&"flex"!==o&&"inline-flex"!==o&&(o="block"),e.data("style-cache",e.attr("style")),e.css({display:o,"padding-top":"0",
"padding-bottom":"0","margin-top":"0","margin-bottom":"0","border-top-width":"0","border-bottom-width":"0",height:"100px",overflow:"hidden"})}),l=a(h),h.each(function(){var e=t(this);e.attr("style",e.data("style-cache")||"")})),t.each(l,function(e,o){var a=t(o),i=0;if(s.target)i=s.target.outerHeight(!1);else{if(s.byRow&&a.length<=1)return void a.css(s.property,"");a.each(function(){var e=t(this),o=e.attr("style"),n=e.css("display");"inline-block"!==n&&"flex"!==n&&"inline-flex"!==n&&(n="block");var a={
display:n};a[s.property]="",e.css(a),e.outerHeight(!1)>i&&(i=e.outerHeight(!1)),o?e.attr("style",o):e.css("display","")})}a.each(function(){var e=t(this),o=0;s.target&&e.is(s.target)||("border-box"!==e.css("box-sizing")&&(o+=n(e.css("border-top-width"))+n(e.css("border-bottom-width")),o+=n(e.css("padding-top"))+n(e.css("padding-bottom"))),e.css(s.property,i-o+"px"))})}),u.each(function(){var e=t(this);e.attr("style",e.data("style-cache")||null)}),r._maintainScroll&&t(window).scrollTop(c/p*t("html").outerHeight(!0)),
this},r._applyDataApi=function(){var e={};t("[data-match-height], [data-mh]").each(function(){var o=t(this),n=o.attr("data-mh")||o.attr("data-match-height");n in e?e[n]=e[n].add(o):e[n]=o}),t.each(e,function(){this.matchHeight(!0)})};var s=function(e){r._beforeUpdate&&r._beforeUpdate(e,r._groups),t.each(r._groups,function(){r._apply(this.elements,this.options)}),r._afterUpdate&&r._afterUpdate(e,r._groups)};r._update=function(n,a){if(a&&"resize"===a.type){var i=t(window).width();if(i===e)return;e=i;
}n?o===-1&&(o=setTimeout(function(){s(a),o=-1},r._throttle)):s(a)},t(r._applyDataApi);var h=t.fn.on?"on":"bind";t(window)[h]("load",function(t){r._update(!1,t)}),t(window)[h]("resize orientationchange",function(t){r._update(!0,t)})});



jQuery('.product h2').matchHeight();