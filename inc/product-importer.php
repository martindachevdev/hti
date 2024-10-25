<?php
class ProductImporter
{

    public $import_stats;

    public function __construct()
    {
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
    public function upload_products_from_csv($products_group)
    {


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
    private function process_variable_product($product_type, $variations)
    {
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
            $product->set_sku(''); // Blank SKU for parent
            $product->set_price(0); // Blank SKU for parent

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
            $models = array_map(function ($variation) {
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
    private function set_product_category($product, $category_name, $append = false)
    {
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
     * Handle import errors
     */
    private function handle_import_error($product_type, $exception)
    {
        $this->import_stats['errors'][] = "Error processing product {$product_type}: " . $exception->getMessage();
        $this->import_stats['skipped']++;
    }

    private function process_variations($product, $variations_data)
    {
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
        foreach ($models as $index =>  $model) {
            // Create variation
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_status('publish');
            $variation->set_price(0);
            $variation->set_sku($variations_data[$index][6]);
            $variation->set_description($variations_data[$index][3]);
            $variation->set_attributes(array('model' => $model));
            $variation_id = $variation->save();

            if ($variation_id) {
                $this->import_stats['variations_created']++;
                $this->import_stats['debug'][] = "Created variation $variation_id for model $model";

                // Set properties
                update_post_meta($variation_id, 'attribute_model', $model);
            }
        }
    }
}

/**
 * Main function to use the importer
 */
function upload_products_from_csv($products_group)
{
    $importer = new ProductImporter();
    return $importer->upload_products_from_csv($products_group);
}