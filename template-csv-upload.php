<?php
/*
Template Name: CSV Product Import
*/

if (!current_user_can('manage_options')) {
    wp_die('Неоторизиран достъп');
}

get_header();

delete_all_products();

// Process form submission
if (isset($_POST['submit']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];
    $import_stats = array(
        'products_created' => 0,
        'variations_created' => 0,
        'errors' => array(),
        'actions' => array()
    );
    
    if (pathinfo($file['name'], PATHINFO_EXTENSION) != 'csv') {
        display_message('error', 'Моля, качете CSV файл.');
    } else {
		$handle = fopen($file['tmp_name'], 'r');
        fgetcsv($handle, 0, ';'); // Skip header
        
        // Group products by type
        $products_group = array();
        while (($data = fgetcsv($handle, 0, ';')) !== FALSE) {
            $product_type = $data[2];
            $products_group[$product_type][] = $data;
        }
        
		foreach ($products_group as $product_type => $variations_data) {
			try {
				// Set categories
				$category = $variations_data[0][0];
				$subcategory = $variations_data[0][1];
				$term_ids = array();
				
				// Main category
				$main_cat = term_exists($category, 'product_cat') ?: wp_insert_term($category, 'product_cat');
				$term_ids[] = is_wp_error($main_cat) ? $main_cat->get_error_data()['term_id'] : $main_cat['term_id'];
				
				// Subcategory
				if (!empty($subcategory)) {
					$sub_cat = term_exists($subcategory, 'product_cat') ?: 
							  wp_insert_term($subcategory, 'product_cat', array('parent' => $term_ids[0]));
					if (!is_wp_error($sub_cat)) {
						$term_ids[] = $sub_cat['term_id'];
					}
				}
				
				// Get unique models for the 'model' attribute
				$models = array_unique(array_map(function($data) {
					return trim($data[4]); // Model column
				}, $variations_data));
				
				// Prepare attributes array
				$attributes = [
					[
						'name' => 'Модел',
						'options' => $models
					]
				];
				
				// Get description from first variation (or you could combine all descriptions)
				$product_description = !empty($variations_data[0][3]) ? trim($variations_data[0][3]) : '';
				
				// Prepare variations array
				$variations = array();
				foreach ($variations_data as $data) {
					$variations[] = array(
						'attributes' => array(
							'model' => trim($data[4])  // Model column
						),
						'sku' => $data[6],       // SKU column
						'price' => '0',                // Add your price logic here
						'description' => trim($data[3]) // Description column
					);
				}
		
				// Create the variable product
				$product = create_variable_product(
					$product_type,           // product name
					$product_description,    // product description
					$attributes,            // attributes array
					$variations            // variations array
				);
				
				// Update categories after product creation
				if (!is_wp_error($product)) {
					wp_set_object_terms($product->get_id(), $term_ids, 'product_cat');
					
					$import_stats['products_created']++;
					$import_stats['variations_created'] += count($variations);
					$import_stats['actions'][] = sprintf(
						'Created variable product "%s" with %d variations',
						$product_type,
						count($variations)
					);
				} else {
					$import_stats['errors'][] = sprintf(
						'Failed to create product "%s": %s',
						$product_type,
						$product->get_error_message()
					);
				}
				
			} catch (Exception $e) {
				$import_stats['errors'][] = "Error processing $product_type: " . $e->getMessage();
			}
		}
        
        fclose($handle);
        
        // Display results
        echo '<div class="wrap">';
        echo '<h2>Import Results</h2>';
        
        printf(
            '<div class="notice updated"><p>Import completed:<br>
            Products created: %d<br>
            Variations created: %d</p></div>',
            $import_stats['products_created'],
            $import_stats['variations_created']
        );
        
        if (!empty($import_stats['actions'])) {
            echo '<div class="card"><h3>Details:</h3><pre>';
            echo implode("\n", $import_stats['actions']);
            echo '</pre></div>';
        }
        
        if (!empty($import_stats['errors'])) {
            echo '<div class="notice error"><h3>Errors:</h3><pre>';
            echo implode("\n", $import_stats['errors']);
            echo '</pre></div>';
        }
        
        echo '</div>';
    }
}
?>

<div class="wrap">
    <h1>CSV Product Import</h1>
    
    <div class="card">
        <h2>Instructions</h2>
        <p>Upload a CSV file with the following columns:</p>
        <ul>
            <li>Category</li>
            <li>Subcategory</li>
            <li>Product Type</li>
            <li>Description</li>
            <li>Model</li>
            <li>Unit</li>
            <li>SKU</li>
        </ul>
        <p>Use semicolon (;) as delimiter.</p>
    </div>
    
    <form method="post" enctype="multipart/form-data">
        <table class="form-table">
            <tr>
                <th><label for="csv_file">Select CSV File</label></th>
                <td><input type="file" name="csv_file" id="csv_file" accept=".csv" required></td>
            </tr>
        </table>
        <?php wp_nonce_field('import_products_csv', 'import_products_nonce'); ?>
        <input type="submit" name="submit" class="button button-primary" value="Import">
    </form>
</div>

<?php get_footer(); ?>