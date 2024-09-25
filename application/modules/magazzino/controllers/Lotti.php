<?php
    class Lotti extends MY_Controller {
        public function __construct() {
            parent::__construct();
            
            if (!$this->auth->check()) {
                redirect('login');
            }
        }
        
        public function stampa_etichetta($articolo_id) {
            $dimensione = $this->input->get('dimensione') ?? 'piccola';
            
            if (empty($dimensione) || !in_array($dimensione, array('piccola', 'grande'))) {
                $dimensione = 'piccola';
            }
            
            $articolo = $this->apilib->view('movimenti_articoli', $articolo_id);
            
            if (empty($articolo)) {
                echo 'Lotto non trovato';
                return;
            }
            
            $html = $this->load->view('magazzino/lotto_etichetta_' . strtolower($dimensione), ['articolo' => $articolo, 'qta_um' => $this->input->get('qta_um')], true);
            
            echo $html;
        }
    }
