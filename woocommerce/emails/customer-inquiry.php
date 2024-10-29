<?php 
if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email);
?>

<p>Здравейте<?php echo $order->get_meta('entity_type') === 'person' ? ' ' . $order->get_meta('first_name') : ''; ?>,</p>

<p>Благодарим ви за вашето запитване. Ще се свържем с вас възможно най-скоро.</p>

<h2>Заявени продукти</h2>
<ul>
    <?php foreach ($order->get_items() as $item): ?>
        <li>
            <?php echo esc_html($item->get_name()); ?> 
            (Количество: <?php echo esc_html($item->get_quantity()); ?>)
        </li>
    <?php endforeach; ?>
</ul>

<p>
    <a class="button button-primary" href="<?php echo esc_url($order->get_view_order_url()); ?>">
        Преглед на заявката
    </a>
</p>

<?php do_action('woocommerce_email_footer', $email); ?>