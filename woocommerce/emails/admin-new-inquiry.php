
<?php 
if (!defined('ABSPATH')) {
    exit;
}

do_action('woocommerce_email_header', $email_heading, $email);
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

<?php do_action('woocommerce_email_footer', $email);