<?php
    class Crons extends MY_Controller {
        public function __construct()
        {
            parent::__construct();
            
            $this->module_settings = $this->apilib->searchFirst('payments_subscriptions_settings');
        }
        
        public function runAll() {
            $this->paymentsExpiringToday();
        }
        
        public function paymentsExpiringToday()
        {
            if (empty($this->module_settings) || empty($this->module_settings['payments_subscriptions_settings_email_notifications'])) {
                echo 'Invalid module settings';
                return;
            }
            
            $payments_expire_today = $this->apilib->search('payments', ['DATE(payments_date) = DATE(now()) AND payments_invoice_sent = ' . DB_BOOL_FALSE . ' AND payments_canceled = ' . DB_BOOL_FALSE]);
        
            if (!empty($payments_expire_today)) {
                $mail_text = '<ul>';
            
                foreach ($payments_expire_today as $payment) {
                    $customer_name = (!empty($payment['customers_company'])) ? $payment['customers_company'] : $payment['customers_name'] . ' ' . $payment['customers_last_name'];
                
                    $mail_text .= '<li>Customer: ' . $customer_name . '<br/>Amount: ' . $payment['payments_amount'] . '<br/>Note: ' . $payment['payments_note'] . '</li>';
                }
            
                $mail_text .= '</ul>';
            
                $mail_data['payments'] = $mail_text;
                $mail_data['today'] = date('d/m/Y');
                $mail_data['link_payments_crm'] = base_url('main/layout/payments');
            
                $this->mail_model->send($this->module_settings['payments_subscriptions_settings_email_notifications'], 'payments_expiring_today', 'en', $mail_data);
                echo 'mail sent';
            }
        }
    }