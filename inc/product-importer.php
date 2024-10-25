<?php
class ProductImporter {
    
    public $import_stats;

    public function __construct() {
        $this->import_stats = [
            'products_created' => 0,
            'products_updated' => 0,
            'variations_created' => 0,
            'variations_updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'debug' => []
        ];
    }

    /**
     * Main import function
     */
    public function upload_products_from_csv($products_group) {
        foreach ($products_group as $product_type => $variations) {
            try {
                $product = $this->process_variable_product($product_type, $variations);
       
                if (!is_wp_error($product)) {
                    $this->process_variations($product, $variations);
                }
            } catch (Exception $e) {
                $this->handle_import_error($product_type, $e);
            }
        }
        return $this->import_stats;
    }

    /**
     * Process variable product
     */
    private function process_variable_product($product_type, $variations) {
        try {
            // Check if product exists by slug
            $slug = sanitize_title($product_type);
            
            // Get existing product
            $existing_product = get_page_by_path($slug, OBJECT, 'product');
            
            if ($existing_product) {
                $product = wc_get_product($existing_product->ID);
                
                // Delete variations but keep product data
                $deleted_count = $this->delete_existing_variations($existing_product->ID);
                $this->import_stats['debug'][] = "Updated product {$existing_product->ID}, deleted {$deleted_count} variations";
                
                // Keep existing product data but update necessary fields
                $product->set_name($product_type); // Update name in case it changed
                $product->set_status('publish');    // Ensure it's published
                
                $this->import_stats['products_updated']++;
            } else {
                // Create new product
                $product = new WC_Product_Variable();
                $product->set_name($product_type);
                $product->set_status('publish');
                $product->set_slug($slug);
                $this->import_stats['products_created']++;
            }

            // Always update these fields
            $product->set_sku('');
            $product->set_price(0);

            // Update categories only if provided
            if (!empty($variations[0][0])) {
                $category = sanitize_text_field($variations[0][0]);
                $this->set_product_category($product, $category);
            }
            if (!empty($variations[0][1])) {
                $subcategory = sanitize_text_field($variations[0][1]);
                $this->set_product_category($product, $subcategory, true);
            }

            // Update attributes
            $models = array_map(function ($variation) {
                return sanitize_text_field($variation[4]);
            }, $variations);

            $attribute = new WC_Product_Attribute();
            $attribute->set_id(0);
            $attribute->set_name('Model');
            $attribute->set_options($models);
            $attribute->set_visible(true);
            $attribute->set_variation(true);

            $product->set_attributes([$attribute]);

            // Save all changes
            $product_id = $product->save();
            
            $this->import_stats['debug'][] = sprintf(
                "Product %s (ID: %d) %s with %d model variations",
                $product_type,
                $product_id,
                $existing_product ? 'updated' : 'created',
                count($models)
            );

            return $product;

        } catch (Exception $e) {
            $this->import_stats['errors'][] = "Error processing product {$product_type}: " . $e->getMessage();
            return new WP_Error('product_creation_failed', $e->getMessage());
        }
    }

    /**
     * Set product category
     */
    private function set_product_category($product, $category_name, $append = false) {
        $term = get_term_by('name', $category_name, 'product_cat');
        if (!$term) {
            $term = wp_insert_term($category_name, 'product_cat');
            if (!is_wp_error($term)) {
                $term_id = $term['term_id'];
            } else {
                $this->import_stats['debug'][] = "Error creating category {$category_name}: " . $term->get_error_message();
                return;
            }
        } else {
            $term_id = $term->term_id;
        }

        $current_categories = $product->get_category_ids();
        if ($append) {
            $current_categories[] = $term_id;
        } else {
            $current_categories = [$term_id];
        }
        $product->set_category_ids($current_categories);
    }

    /**
     * Delete existing variations
     */
    private function delete_existing_variations($product_id) {
        if (!$product_id) return 0;
        
        // Get all variation ids
        $variations = get_posts(array(
            'post_parent' => $product_id,
            'post_type' => 'product_variation',
            'fields' => 'ids',
            'posts_per_page' => -1,
            'post_status' => 'any'
        ));

        $count = 0;
        if ($variations) {
            foreach ($variations as $variation_id) {
                if (wp_delete_post($variation_id, true)) {
                    $count++;
                    $this->import_stats['debug'][] = "Deleted variation: $variation_id";
                }
            }
        }

        // Clear all caches
        wc_delete_product_transients($product_id);
        clean_post_cache($product_id);
        wp_cache_delete($product_id, 'posts');
        
        // Clear product lookup tables
        if (function_exists('wc_delete_product_sync_transients')) {
            wc_delete_product_sync_transients($product_id);
        }

        return $count;
    }

    /**
     * Process variations
     */
    private function process_variations($product, $variations_data) {
        $product_id = $product->get_id();
        if (!$product || !$product->is_type('variable')) {
            return false;
        }

        $this->import_stats['debug'][] = "Starting variation processing for product $product_id";

        // Get all product attributes first
        $models = array_map(function ($variation) {
            return sanitize_text_field($variation[4]); // Model column
        }, $variations_data);

        // Create variations
        foreach ($models as $index => $model) {
            // Create variation
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_status('publish');
            $variation->set_price(0);
            $variation->set_regular_price(0);
            
            // Set SKU if provided
            if (!empty($variations_data[$index][6])) {
                $variation->set_sku($variations_data[$index][6]);
            }
            
            // Set description if provided
            if (!empty($variations_data[$index][3])) {
                $variation->set_description(sanitize_text_field($variations_data[$index][3]));
            }
            
            // Set attributes
            $variation->set_attributes(array('model' => $model));
            
            $variation_id = $variation->save();

            if ($variation_id) {
                $this->import_stats['variations_created']++;
                $this->import_stats['debug'][] = "Created variation $variation_id for model $model";

                // Set model attribute
                update_post_meta($variation_id, 'attribute_model', $model);
            }
        }

        // Clear product cache after all variations are created
        wc_delete_product_transients($product_id);
        
        return true;
    }

    /**
     * Handle import errors
     */
    private function handle_import_error($product_type, $exception) {
        $this->import_stats['errors'][] = "Error processing product {$product_type}: " . $exception->getMessage();
        $this->import_stats['skipped']++;
    }
}

/**
 * Main function to use the importer
 */
function upload_products_from_csv($products_group) {
    $importer = new ProductImporter();
    return $importer->upload_products_from_csv($products_group);
}