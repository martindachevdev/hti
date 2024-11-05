<?php
 /**
 * The template for displaying the homepage.
 *
 * This page template will display any functions hooked into the `homepage` action.
 * By default this includes a variety of product displays and the page content itself. To change the order or toggle these components
 * use the Homepage Control plugin.
 * https://wordpress.org/plugins/homepage-control/
 *
 * Template name: Test
 *
 * @package storefront
 */
// Get the latest order
$orders = wc_get_orders(array(
    'limit' => 1,
    'orderby' => 'date',
    'order' => 'DESC',
));

if (empty($orders)) {
    wp_die(__('No orders found. Please create at least one order to test the email template.'));
}

$order = $orders[0];

?>
<p>Получена е ново запитване.</p>

<h2>Информация за клиента</h2>
<ul>
    <li><strong>Тип потребител:</strong> 
        <?php echo $order->get_meta('entity_type') === 'person' ? 'Физическо лице' : 'Юридическо лице'; ?>
    </li>
    
    <?php if ($order->get_meta('entity_type') === 'person'): ?>
        <li><strong>Име и фамилия:</strong> 
            <?php echo esc_html($order->get_meta('first_name') . ' ' . $order->get_meta('last_name')); ?>
        </li>
        <li><strong>ЕГН:</strong> <?php echo esc_html($order->get_meta('egn_eik')); ?></li>
    <?php else: ?>
        <li><strong>Име на фирма:</strong> <?php echo esc_html($order->get_meta('company_name')); ?></li>
        <li><strong>ЕИК:</strong> <?php echo esc_html($order->get_meta('egn_eik')); ?></li>
    <?php endif; ?>
    
    <li><strong>Телефон:</strong> <?php echo esc_html($order->get_meta('phone')); ?></li>
    <li><strong>Имейл:</strong> <?php echo esc_html($order->get_billing_email()); ?></li>
    <li><strong>Адрес:</strong> <?php echo esc_html($order->get_billing_address_1()); ?></li>
    <li><strong>Град:</strong> <?php echo esc_html($order->get_billing_city()); ?></li>
    <li><strong>Пощенски код:</strong> <?php echo esc_html($order->get_billing_postcode()); ?></li>
</ul>

<h3>Съгласия</h3>
<ul>
    <li><strong>Съгласие за имейл:</strong> <?php echo $order->get_meta('email_consent') ? 'Да' : 'Не'; ?></li>
    <li><strong>Съгласие за телефон:</strong> <?php echo $order->get_meta('phone_consent') ? 'Да' : 'Не'; ?></li>
</ul>

<h2>Заявени продукти</h2>
<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 30px;">
    <thead>
        <tr>
            <th class="td" scope="col" style="text-align: left;"><?php esc_html_e('Product', 'woocommerce'); ?></th>
            <th class="td" scope="col" style="text-align: left;"><?php esc_html_e('Quantity', 'woocommerce'); ?></th>
            <th class="td" scope="col" style="text-align: left;">М. ед-ца</th>
            <th class="td" scope="col" style="text-align: left;"><?php esc_html_e('SKU', 'woocommerce'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $unit = get_post_meta( $item->get_variation_id(), 'attribute_measure_unit', true );
            ?>
            <tr>
                <td class="td" style="text-align: left;">
                    <?php echo wp_kses_post(apply_filters('woocommerce_order_item_name', $item->get_name(), $item, false)); ?>
                </td>
                <td class="td" style="text-align: left;"><?php echo esc_html($item->get_quantity()); ?></td>
                <td class="td" style="text-align: left;"><?php echo esc_html($unit); ?></td>
                <td class="td" style="text-align: left;"><?php echo esc_html($product->get_sku()); ?></td>
            </tr>
            <?php
        }
        ?>
    </tbody>
</table>
<ul>
    <?php foreach ($order->get_items() as $item): ?>
        <li>
            <?php echo esc_html($item->get_name()); ?> 
            (Количество: <?php echo esc_html($item->get_quantity()); ?>)
        </li>
    <?php endforeach; ?>
</ul>

<p>
    <a class="button button-primary" href="<?php echo admin_url('post.php?post=' . $order->get_id() . '&action=edit'); ?>">
        Преглед на заявката
    </a>
</p>
