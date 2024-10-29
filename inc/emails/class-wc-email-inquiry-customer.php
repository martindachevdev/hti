<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Email_Inquiry_Customer extends WC_Email {
    public function __construct() {
        $this->id = 'inquiry_customer';
        $this->title = 'Потвърждение за запитване';
        $this->description = 'Имейл потвърждение до клиента за получено запитване.';
        $this->heading = 'Запитване #{order_number}';
        $this->subject = 'Вашето запитване #{order_number}';
        
        $this->template_html = 'emails/customer-inquiry.php';
        
        $this->customer_email = true;
        
        add_action('theme_inquiry_submitted', array($this, 'trigger'), 10, 1);
        
        parent::__construct();
    }
    
    public function trigger($order_id) {
        $this->object = wc_get_order($order_id);
        
        if ($this->object && $this->is_enabled()) {
            $this->recipient = $this->object->get_billing_email();
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }
    }
    
    public function get_content_html() {
        ob_start();
        wc_get_template(
            $this->template_html,
            array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => false,
                'plain_text' => false,
                'email' => $this
            ),
            '',
            get_template_directory() . '/woocommerce/'
        );
        return ob_get_clean();
    }
    
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Активиране',
                'type' => 'checkbox',
                'label' => 'Активиране на този имейл',
                'default' => 'yes'
            ),
            'subject' => array(
                'title' => 'Заглавие',
                'type' => 'text',
                'description' => 'Това контролира заглавието на имейла. Използвайте {order_number}',
                'placeholder' => $this->subject,
                'default' => $this->subject
            ),
            'heading' => array(
                'title' => 'Заглавие в имейла',
                'type' => 'text',
                'description' => 'Това контролира заглавието в самия имейл. Използвайте {order_number}',
                'placeholder' => $this->heading,
                'default' => $this->heading
            ),
            'email_type' => array(
                'title' => 'Формат на имейла',
                'type' => 'select',
                'description' => 'Изберете формат за имейла',
                'default' => 'html',
                'class' => 'email_type',
                'options' => array(
                    'html' => 'HTML'
                )
            )
        );
    }
}