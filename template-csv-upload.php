<?php
/*
Template Name: CSV Product Import
*/

if (!current_user_can('manage_options')) {
	wp_die('Неоторизиран достъп');
}

get_header();
delete_all_products();
/**
 * Display message helper function
 */

 
/**
 * Display import results
 */

 
// Process form submission
if (isset($_POST['submit']) && isset($_FILES['csv_file'])) {
	// Verify nonce
	if (!isset($_POST['import_products_nonce']) || !wp_verify_nonce($_POST['import_products_nonce'], 'import_products_csv')) {
		display_message('error', 'Invalid security token.');
		return;
	}

	$file = $_FILES['csv_file'];

	if ($file['error'] !== UPLOAD_ERR_OK) {
		display_message('error', 'Error uploading file: ' . $file['error']);
		return;
	}

	if (pathinfo($file['name'], PATHINFO_EXTENSION) != 'csv') {
		display_message('error', 'Моля, качете CSV файл.');
		return;
	}

	try {
		$handle = fopen($file['tmp_name'], 'r');
		if ($handle === false) {
			throw new Exception('Could not open file.');
		}

		// Skip header
		fgetcsv($handle, 0, ';');

		// Group products by type
		$products_group = array();
		while (($data = fgetcsv($handle, 0, ';')) !== FALSE) {
			if (isset($data[2])) { // Check if product type exists
				$product_type = $data[2];
				$products_group[$product_type][] = $data;
			}
		}

		fclose($handle);



		// Perform import
		if (!empty($products_group)) {
			$import_stats = upload_products_from_csv($products_group);
			// print_r($import_stats);
			display_import_results($import_stats);
		} else {
			display_message('error', 'No valid products found in CSV file.');
		}

	} catch (Exception $e) {
		display_message('error', 'Error processing file: ' . $e->getMessage());
	}
}
?>

<div class="wrap">
	<h1>Импорт на продукти от CSV</h1>

	<div class="card">
		<h2>Инструкции</h2>
		<p>Качете CSV файл със следните колони:</p>
		<ul>
			<li>Категория (Category)</li>
			<li>Подкатегория (Subcategory)</li>
			<li>Вид артикул (Product Type)</li>
			<li>Описание на артикула (Description)</li>
			<li>Модел (Model)</li>
			<li>Ед.мярка (Unit)</li>
			<li>Артикулен код (SKU)</li>
		</ul>
		<p>Използвайте точка и запетая (;) като разделител.</p>
	</div>

	<form method="post" enctype="multipart/form-data">
		<table class="form-table">
			<tr>
				<th><label for="csv_file">Изберете CSV файл</label></th>
				<td>
					<input type="file" name="csv_file" id="csv_file" accept=".csv" required>
					<?php wp_nonce_field('import_products_csv', 'import_products_nonce'); ?>
				</td>
			</tr>
		</table>
		<input type="submit" name="submit" class="button button-primary" value="Импорт">
	</form>
</div>

<?php get_footer(); ?>