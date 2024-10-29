<?php
/**
 * Orders
 *
 * Shows orders on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/orders.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.2.0
 */

defined( 'ABSPATH' ) || exit;
 
// Get inquiries
$customer_inquiries = wc_get_orders(array(
    'customer' => get_current_user_id(),
    'status' => array('inquiry', 'processing'),
    'paginate' => true,
    'limit' => 10,
    'page' => get_query_var('paged') ? get_query_var('paged') : 1,
));

do_action('before_account_inquiries');
?>

<h2>Моите запитвания</h2>

<?php if ($customer_inquiries->orders) : ?>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
        <thead>
            <tr>
                <th class="inquiry-number">Номер</th>
                <th class="inquiry-date">Дата</th>
                <th class="inquiry-products">Продукти</th>
                <th class="inquiry-status">Статус</th>
                <th class="inquiry-actions">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($customer_inquiries->orders as $inquiry): ?>
                <tr>
                    <td data-title="Номер">
                        <a href="<?php echo esc_url($inquiry->get_view_order_url()); ?>">
                            #<?php echo $inquiry->get_order_number(); ?>
                        </a>
                    </td>
                    <td data-title="Дата">
                        <?php echo wp_date('d.m.Y H:i', $inquiry->get_date_created()->getTimestamp()); ?>
                    </td>
                    <td data-title="Продукти">
                        <?php
                        $items = $inquiry->get_items();
                        foreach ($items as $item):
                            $product = $item->get_product();
                            if (!$product) continue;
                        ?>
                            <div class="inquiry-product">
                                <div class="product-image">
                                    <?php echo $product->get_image('thumbnail'); ?>
                                </div>
                                <div class="product-info">
                                    <span class="product-name">
                                        <?php echo esc_html($item->get_name()); ?>
                                    </span>
                                    <span class="product-quantity">
                                        Количество: <?php echo esc_html($item->get_quantity()); ?>
                                    </span>
                                    <?php if ($item->get_variation_id()): ?>
                                        <div class="product-variation">
                                            <?php
                                            foreach ($item->get_formatted_meta_data() as $meta) {
                                                echo '<span>' . wp_strip_all_tags($meta->display_key) . ': ' 
                                                     . wp_strip_all_tags($meta->display_value) . '</span>';
                                            }
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </td>
                    <td data-title="Статус">
                        <span class="inquiry-status">Запитване</span>
                    </td>
                    <td data-title="Действия">
                        <a href="<?php echo esc_url($inquiry->get_view_order_url()); ?>" class="woocommerce-button button">
                            Преглед
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($customer_inquiries->max_num_pages > 1): ?>
        <div class="woocommerce-pagination">
            <?php
            echo paginate_links(array(
                'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                'format' => '?paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $customer_inquiries->max_num_pages
            ));
            ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <div class="woocommerce-message woocommerce-message--info">
        <p>Нямате направени запитвания.</p>
        <a class="woocommerce-Button button" href="<?php echo esc_url(get_permalink(wc_get_page_id('shop'))); ?>">
            Разгледайте продукти
        </a>
    </div>
<?php endif; ?>

<?php do_action('after_account_inquiries'); ?>
 