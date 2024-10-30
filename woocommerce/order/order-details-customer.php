<?php
/**
 * Order details - customer template
 *
 * Save as: woocommerce/order/order-details-customer.php
 */

defined('ABSPATH') || exit;
 

if (!$order) {
    return;
}

$entity_type = $order->get_meta('entity_type');
?>

<section class="woocommerce-customer-details">
    <h2 class="woocommerce-column__title"><?php esc_html_e('Информация за клиента', 'woocommerce'); ?></h2>

    <table class="woocommerce-table woocommerce-table--customer-details shop_table">
        <tbody>
            <tr>
                <th><?php esc_html_e('Тип клиент:', 'woocommerce'); ?></th>
                <td><?php echo esc_html($entity_type === 'person' ? 'Физическо лице' : 'Юридическо лице'); ?></td>
            </tr>

            <?php if ($entity_type === 'person') : ?>
                <tr>
                    <th><?php esc_html_e('Име:', 'woocommerce'); ?></th>
                    <td><?php echo esc_html($order->get_meta('first_name')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Фамилия:', 'woocommerce'); ?></th>
                    <td><?php echo esc_html($order->get_meta('last_name')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('ЕГН:', 'woocommerce'); ?></th>
                    <td><?php echo esc_html($order->get_meta('egn_eik')); ?></td>
                </tr>
            <?php else : ?>
                <tr>
                    <th><?php esc_html_e('Фирма:', 'woocommerce'); ?></th>
                    <td><?php echo esc_html($order->get_meta('company_name')); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('ЕИК:', 'woocommerce'); ?></th>
                    <td><?php echo esc_html($order->get_meta('egn_eik')); ?></td>
                </tr>
            <?php endif; ?>

            <tr>
                <th><?php esc_html_e('Телефон:', 'woocommerce'); ?></th>
                <td><?php echo esc_html($order->get_meta('phone')); ?></td>
            </tr>

            <tr>
                <th><?php esc_html_e('Имейл:', 'woocommerce'); ?></th>
                <td><?php echo esc_html($order->get_billing_email()); ?></td>
            </tr>

            <tr>
                <th><?php esc_html_e('Адрес:', 'woocommerce'); ?></th>
                <td>
                    <?php 
                    echo esc_html($order->get_billing_address_1()) . '<br>';
                    echo esc_html($order->get_billing_city());
                    if ($order->get_billing_postcode()) {
                        echo ', ' . esc_html($order->get_billing_postcode());
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <th><?php esc_html_e('Съгласия:', 'woocommerce'); ?></th>
                <td>
                    <?php
                    $email_consent = $order->get_meta('email_consent');
                    $phone_consent = $order->get_meta('phone_consent');
                    ?>
                    <div><?php echo $email_consent ? '✓' : '✗'; ?><?php esc_html_e('Имейл маркетинг:', 'woocommerce'); ?> </div>
                    <div> <?php echo $phone_consent ? '✓' : '✗'; ?><?php esc_html_e('Телефонен маркетинг:', 'woocommerce'); ?></div>
                </td>
            </tr>
        </tbody>
    </table>

    <?php do_action('woocommerce_order_details_after_customer_details', $order); ?>
</section>
 