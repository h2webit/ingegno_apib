<?php
    class Ajax extends MY_Controller {
        public function __construct() {
            parent::__construct();
        }
    
        public function buildData() {
            $post = $this->security->xss_clean($this->input->post());
            
            if (empty($post) || empty($post['payments_ids'])) {
                die(e_json(['status' => 0, 'txt' => 'No data received']));
            }
        
            $payments = $this->apilib->search('payments', ['payments_id IN ('.implode(',', $post['payments_ids']).')']);
        
            $customers_ids = array_unique(array_key_map($payments, 'payments_customer'));
            
            if (count($customers_ids) > 1) {
                die(e_json(['status' => 0, 'txt' => 'Non puoi creare una fattura per clienti diversi.']));
            }
    
            $articoli = [];
            $cliente_id = null;
            foreach ($payments as $payment) {
                $cliente_id = $payment['payments_customer'];

                $articolo = [
                    'codice' => '',
                    'nome' => $payment['payments_note'],
                    'descrizione' => '',
                    'prezzo' => $payment['payments_amount'],
                    'rif_pagamento' => $payment['payments_id'],
                ];
        
                $articoli[] = $articolo;
            }
    
            e_json(['status' => 1, 'txt' => 'ok', 'cliente' => $cliente_id, 'data' => $articoli]);
        }

        public function save_payments() {
            $payments = $this->input->post('payments');
            $customer = $this->input->post('customer');
            //debug($payments,true);
            foreach ($payments as $payment) {
                if (empty($payment['payments_paid'])) {
                    $payment['payments_paid'] = 0;
                }
                if (empty($payment['payments_invoice_sent'])) {
                    $payment['payments_invoice_sent'] = 0;
                }
                if (empty($payment['payments_approved'])) {
                    $payment['payments_approved'] = 0;
                }
                if (!empty($payment['payments_id'])) {
                    $dati = $payment;
                    unset($dati['payments_id']);
                    $this->apilib->edit('payments', $payment['payments_id'], $payment);
                } else {
                    $payment['payments_customer'] = $customer;
                    $this->apilib->create('payments', $payment);
                }
            }
            e_json(['status' => 7, 'close_modals'=>1, 'txt' => 'ok']);
        }
    }
