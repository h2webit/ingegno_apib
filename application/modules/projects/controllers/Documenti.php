<?php
    class Documenti extends MY_Controller {
        public function __construct() {
            parent::__construct();

            header('Access-Control-Allow-Origin: *');
            @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
    
        public function express_save() {
            if (!$this->datab->module_installed('contabilita')) die(show_404());
        
            $post = $this->security->xss_clean($this->input->post());
        
            if (!$post) die(e_json(['status' => 0, 'txt' => 'No data']));
        
            $this->load->library('form_validation');
            $this->load->model('contabilita/docs');
        
            $this->form_validation->set_rules('commessa', 'Commessa', 'required');
            $this->form_validation->set_rules('cliente', 'Cliente', 'required');
            $this->form_validation->set_rules('utente', 'Utente', 'required');
            $this->form_validation->set_rules('tipo', 'Tipo', 'required');
            $this->form_validation->set_rules('prodotti', 'Prodotti', 'required');
        
            if (!$this->form_validation->run()) die(e_json(['status' => 0, 'txt' => validation_errors()]));
        
            $utente = $post['utente'];
            $commessa = $post['commessa'];
            $cliente = $post['cliente'];
            $tipo = $post['tipo'];
            $prodotti = json_decode($post['prodotti'], true);
            $note = $post['note_generiche'] ?? null;
        
            $ordine = [
                'cliente_id' => $cliente,
                'utente' => $utente,
                'tipo_documento' => $tipo,
                'serie' => '',
                'articoli' => [],
                'note_generiche' => $note,
            ];
        
            $articoli_ordine = [];
            foreach ($prodotti as $prodotto) {
                if (!isset($articoli_ordine[$prodotto['prodotto_id']]['qty'])) $articoli_ordine[$prodotto['prodotto_id']]['qty'] = 0;
            
                $articoli_ordine[$prodotto['prodotto_id']]['qty'] += $prodotto['qty'];
            }
            $ordine['articoli'] = $articoli_ordine;
        
            $this->load->model('contabilita/docs');
        
            $ordine_id = $this->docs->doc_express_save($ordine);
        
            if (!$ordine_id) die(e_json(['status' => 0, 'txt' => 'Errore durante la creazione ordine']));
        
            try {
                $this->apilib->create("documenti_contabilita_commesse", ['documenti_contabilita_commesse_documenti_contabilita_id' => $ordine_id, 'documenti_contabilita_commesse_projects_id' => $commessa]);
                $documento = $this->apilib->view('documenti_contabilita', $ordine_id);
            
                e_json(['status' => 1, 'txt' => 'Ordine salvato correttamente', 'documento' => $documento]);
                //e_json(['status' => 1, 'txt' => 'Ordine salvato correttamente']);
            } catch (Exception $e) {
                log_message('error', $e->getMessage());
                e_json(['status' => 0, 'txt' => 'Si è verificato un errore inatteso.']);
            }
        }
    }