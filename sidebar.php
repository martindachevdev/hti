<?php
/**
 * The sidebar containing the main widget area.
 *
 * @package storefront
 */

?>

<div id="secondary" class="widget-area" role="complementary">
	
	<?php 
	    if (is_woocommerce() || is_shop() || is_product_category() || is_product_tag() || is_product() || is_cart() || is_checkout() || is_account_page()) {
			dynamic_sidebar( 'sidebar-1' );
		} else {
			get_sidebar( 'blog' );
		}
	
	 ?>
</div><!-- #secondary -->
