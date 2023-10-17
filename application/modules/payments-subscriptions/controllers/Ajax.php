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
                ];
        
                $articoli[] = $articolo;
            }
    
            e_json(['status' => 1, 'txt' => 'ok', 'cliente' => $cliente_id, 'data' => $articoli]);
        }
    }
