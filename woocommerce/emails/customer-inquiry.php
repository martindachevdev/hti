<?php 
if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p>Здравейте<?php echo $order->get_meta('entity_type') === 'person' ? ' ' . $order->get_meta('first_name') : ''; ?>,</p>

<p>Благодарим ви за вашето запитване. Ще се свържем с вас възможно най-скоро.</p>

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

<p>
    <a class="button button-primary" href="<?php echo esc_url($order->get_view_order_url()); ?>">
        Преглед на заявката
    </a>
</p>

<?php do_action('woocommerce_email_footer', $email); ?>