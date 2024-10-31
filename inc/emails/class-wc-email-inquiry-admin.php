<?php

if (!defined('ABSPATH')) {
    exit;
}

class WC_Email_Inquiry_Admin extends WC_Email {
    public function __construct() {
        $this->id = 'inquiry_admin';
        $this->title = 'Ново запитване (Админ)';
        $this->description = 'Имейл известия за нови запитвания.';
        $this->heading = 'Ново запитване #{order_number}';
        $this->subject = '[{site_title}] Ново запитване #{order_number}';
        
        $this->template_html = 'emails/admin-new-inquiry.php';
        
        add_action('theme_inquiry_submitted', array($this, 'trigger'), 10, 1);
        
        parent::__construct();
        
        $this->recipient = $this->get_option('recipient', get_option('admin_email'));
    }
    
    public function trigger($order_id) {
        $this->object = wc_get_order($order_id);
        
        if ($this->object && $this->is_enabled() && $this->get_recipient()) {
            // Replace placeholders in subject and heading
            $this->subject = $this->format_string($this->get_option('subject', $this->subject));
            $this->heading = $this->format_string($this->get_option('heading', $this->heading));
            
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }
    }
    
    public function format_string($string) {
        if ($this->object) {
            $string = str_replace(
                array('{order_number}', '{site_title}'),
                array($this->object->get_order_number(), wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)),
                $string
            );
        }
        return $string;
    }
    
    public function get_content_html() {
        ob_start();
        wc_get_template(
            $this->template_html,
            array(
                'order' => $this->object,
                'email_heading' => $this->get_heading(),
                'sent_to_admin' => true,
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
            'recipient' => array(
                'title' => 'Получател(и)',
                'type' => 'text',
                'description' => 'Въведете получателите (разделени със запетая)',
                'placeholder' => get_option('admin_email'),
                'default' => get_option('admin_email')
            ),
            'subject' => array(
                'title' => 'Заглавие',
                'type' => 'text',
                'description' => 'Това контролира заглавието на имейла. Използвайте {site_title} и {order_number}',
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