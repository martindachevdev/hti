/**
 * Save as: assets/js/inquiry-cart.js
 */

(function ($) {
    'use strict';

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

        init() {
            this.bindEvents();
            this.initWooCommerce();
            this.initializeQuantityInputs();
        },

        bindEvents() {
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
                    
                    // Get form data
                    const formData = new FormData($form[0]);
                    formData.append('action', 'woocommerce_add_to_cart_variable_rc');
                    
                    self.showLoader();
                    
                    $.ajax({
                        url: wc_add_to_cart_params.wc_ajax_url.replace('%%endpoint%%', 'add_to_cart'),
                        data: $form.serialize() + '&action=woocommerce_add_to_cart_variable_rc',
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
                            
                            self.openCart();
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
                    if (response.success) {
                        alert(themeInquiryCart.i18n.successMessage);
                        location.reload();
                    } else if (response.data && response.data.message) {
                        alert(response.data.message);
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
                        } else if (wasActive) {
                            // If cart wasn't empty and was active, ensure it stays open
                            this.openCart();
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
                    if (wasActive && !$updatedCart.hasClass('active') && !response?.data?.is_empty) {
                        this.openCart();
                    }
                }
            });
        },
    };

    // Initialize on document ready
    $(document).ready(() => InquiryCart.init());

})(jQuery);