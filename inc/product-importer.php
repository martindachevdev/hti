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
            $existing_product_id = wc_get_product_id_by_sku($slug);
            
            if ($existing_product_id) {
                $product = wc_get_product($existing_product_id);
                $this->import_stats['products_updated']++;
            } else {
                $product = new WC_Product_Variable();
                $this->import_stats['products_created']++;
            }

            // Set basic product data
            $product->set_name($product_type);
            $product->set_status('publish');
            $product->set_stock_status('instock');
            $product->set_sku(''); // Blank SKU for parent

            // Set categories
            if (!empty($variations[0][0])) { // Category
                $category = sanitize_text_field($variations[0][0]);
                $this->set_product_category($product, $category);
            }
            if (!empty($variations[0][1])) { // Subcategory
                $subcategory = sanitize_text_field($variations[0][1]);
                $this->set_product_category($product, $subcategory, true);
            }

            // Setup attributes
            $models = array_map(function($variation) {
                return sanitize_text_field($variation[4]); // Model column
            }, $variations);

            $attribute = new WC_Product_Attribute();
            $attribute->set_id(0);
            $attribute->set_name('Model');
            $attribute->set_options($models);
            $attribute->set_visible(true);
            $attribute->set_variation(true);

            $product->set_attributes([$attribute]);
            $product->save();

            return $product;

        } catch (Exception $e) {
            $this->import_stats['errors'][] = "Error creating product {$product_type}: " . $e->getMessage();
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
     * Process variations
     */
    private function process_variations($product, $variations) {

        $this->import_stats['debug'][] = "Starting variation process for product: " . $product->get_id();
    
        // First verify product is variable
        if (!$product->is_type('variable')) {
            $this->import_stats['errors'][] = "Product is not variable type: " . $product->get_id();
            return;
        }
    
        // Log attributes before creation
        $this->import_stats['debug'][] = "Product attributes: " . print_r($product->get_attributes(), true);
    
        // Create variations
        $data_store = $product->get_data_store();
        $data_store->create_all_product_variations($product, 100);
        
        // Get variations through WP_Query to debug
        $variation_query = new WP_Query([
            'post_type'      => 'product_variation',
            'post_status'    => ['publish', 'private', 'draft'],
            'posts_per_page' => -1,
            'post_parent'    => $product->get_id(),
        ]);
    
        $this->import_stats['debug'][] = "Found variations through query: " . $variation_query->found_posts;
    
        if ($variation_query->have_posts()) {
            $variation_index = 0;
            
            while ($variation_query->have_posts()) {
                $variation_query->the_post();
                $variation_id = get_the_ID();
                
                if (isset($variations[$variation_index])) {
                    $variation_data = $variations[$variation_index];
                    $this->import_stats['debug'][] = "Updating variation {$variation_id} with data index {$variation_index}";
                    
                    $this->update_variation($variation_id, $variation_data);
                    $variation_index++;
                }
            }
            wp_reset_postdata();
        } else {
            $this->import_stats['errors'][] = "No variations found after creation for product: " . $product->get_id();
        }
    
        // Final check of variations
        $final_query = new WP_Query([
            'post_type'      => 'product_variation',
            'post_status'    => ['publish', 'private', 'draft'],
            'posts_per_page' => -1,
            'post_parent'    => $product->get_id(),
        ]);
        
        $this->import_stats['debug'][] = "Final variation count: " . $final_query->found_posts;
    
        // Save product
        $product->save();
        
        // Clear caches
        wp_cache_delete($product->get_id(), 'post_meta');
        clean_post_cache($product->get_id());
        wc_delete_product_transients($product->get_id());
    }

    /**
     * Create all possible variations
     */
    private function create_all_variations($product) {
        if (!$product || !$product->is_type('variable')) {
            return false;
        }

        $data_store = $product->get_data_store();
        $data_store->create_all_product_variations($product, 30);
        $data_store->update_product_variation_pricing($product);

        return true;
    }

    /**
     * Update variation with data
     */
    private function update_variation($variation_id, $variation_data) {
        try {
            $variation = wc_get_product($variation_id);
            
            if (!$variation) {
                throw new Exception("Variation not found: {$variation_id}");
            }

            // Set variation data
            $model = sanitize_text_field($variation_data[4]); // Model column
            $sku = $variation_data[6]; // SKU column
            $description = sanitize_text_field($variation_data[3]); // Description column

            // Set basic variation data
            $variation->set_sku($sku);

            $variation->set_status('publish');
            $variation->set_stock_status('instock');
            $variation->set_manage_stock(true);
            $variation->set_stock_quantity(0); // Set initial stock
            
            // Set description if exists
            if (!empty($description)) {
                $variation->set_description($description);
            }

            // Set variation attributes
            $variation_attributes = [
                'model' => sanitize_title($model)
            ];
            $variation->set_attributes($variation_attributes);

            $variation->save();
            
            $this->import_stats['variations_updated']++;

        } catch (Exception $e) {

            $this->import_stats['errors'][] = "Error updating variation: " . $e->getMessage();
        }
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