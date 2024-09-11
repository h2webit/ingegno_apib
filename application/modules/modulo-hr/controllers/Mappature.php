<?php
    class Mappature extends MY_Controller {
        public function __construct() {
            parent::__construct();
        }
        
        public function salva() {
            $post = $this->input->post();
            
            if (empty($post['nome_mappatura'])) {
                e_json(['status' => 0, 'txt' => "Inserire un nome della mappatura"]);
                return;
            }
            
            if (empty($post['mappature'])) {
                e_json(['status' => 0, 'txt' => "Mappature mancanti"]);
                return;
            }
            
            $errori = [];
            $map_row = 1;
            foreach ($post['mappature'] as $map) {
                ++$map_row;
                
                if (empty($map['campo'])) {
                    $errori[] = "Nome campo mappatura mancante alla riga $map_row";
                }
            }
            
            if (!empty($errori)) {
                e_json(['status' => 0, 'txt' => implode('<br>', $errori)]);
                return;
            }
            
            $mappature = json_encode($post['mappature']);
            
            $data = [
                'dipendenti_mappature_pdf_label' => $post['nome_mappatura'],
                'dipendenti_mappature_pdf_json' => $mappature,
                'dipendenti_mappature_pdf_default' => (isset($post['mappatura_default']) && $post['mappatura_default'] == 1) ? 1 : 0,
            ];
            
            try {
                $this->apilib->create('dipendenti_mappature_pdf', $data);
            
                e_json(['status' => 1, 'txt' => base_url('main/layout/mappature-pdf-dipendenti')]);
            } catch (Exception $e) {
                e_json(['status' => 0, 'txt' => 'Errore DB: ' . $e->getMessage()]);
            }
        }
        
        public function estrai_saldi_da_cedolino($id_import_cedolini) {
            $cedolini = $this->apilib->view('import_cedolini', $id_import_cedolini);
            
            if (empty($cedolini)) {
                e_json(['status' => 0, 'txt' => 'Cedolino non trovato']);
                return;
            }
            
            $this->load->model('modulo-hr/mappature_model');
            
            $files = json_decode($cedolini['import_cedolini_files'], true);

            $mappatura = $this->apilib->searchFirst('dipendenti_mappature_pdf', ['dipendenti_mappature_pdf_default' => 1]);
            
            foreach ($files as $file) {
                $saldi = $this->mappature_model->estrai_saldi_da_cedolino(FCPATH . 'uploads/' . $file['path_local'], $mappatura);
            }

            debug($saldi,true);
        }
    }