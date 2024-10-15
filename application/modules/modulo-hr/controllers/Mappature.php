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
            $row_import_cedolini = $this->apilib->view('import_cedolini', $id_import_cedolini);
            
            if (empty($row_import_cedolini)) {
                echo 'Cedolino non trovato';
                return;
            }
            
            if ($row_import_cedolini['import_cedolini_imported'] == DB_BOOL_TRUE) {
                echo 'Cedolini già importati';
                return;
            }
            
            $this->load->model('modulo-hr/hrutility');
            
            $files = json_decode($row_import_cedolini['import_cedolini_files'], true);
            
            foreach ($files as $file) {
                $saldi_estratti = $this->hrutility->estrai_saldi_da_cedolino($file, null);

                /** SALDI ESTRATTI
                 * ^ array:3 [▼
                 * "dipendenti_saldo_ferie" => 209.34
                 * "dipendenti_saldo_permessi" => 7.51
                 * "dipendenti_saldo_rol" => 0
                 * ]
                 */
                
                /** TABELLA ratei_ferie_permessi
                 * ratei_ferie_permessi_id    bigint(20) unsigned Auto Increment
                 * ratei_ferie_permessi_creation_date    datetime NULL
                 * ratei_ferie_permessi_modified_date    datetime NULL
                 * ratei_ferie_permessi_created_by    int(11) NULL
                 * ratei_ferie_permessi_edited_by    int(11) NULL
                 * ratei_ferie_permessi_insert_scope    varchar(250) NULL
                 * ratei_ferie_permessi_edit_scope    varchar(250) NULL
                 * ratei_ferie_permessi_dipendente    int(11)
                 * ratei_ferie_permessi_mese    int(11)
                 * ratei_ferie_permessi_anno    int(11)
                 * ratei_ferie_permessi_saldo_ferie    double(18,9) NULL
                 * ratei_ferie_permessi_saldo_rol    double(18,9) NULL
                 * ratei_ferie_permessi_saldo_permessi    double(18,9) NULL
                 * ratei_ferie_permessi_saldo_ferie_precedente    double(18,9) NULL
                 * ratei_ferie_permessi_saldo_rol_precedente    double(18,9) NULL
                 * ratei_ferie_permessi_saldo_permessi_precedente    double(18,9) NULL
                 * ratei_ferie_permessi_saldo_banca_ore    double(18,9) NULL
                 * ratei_ferie_permessi_saldo_banca_ore_precedente    double(18,9) NULL
                 * ratei_ferie_permessi_aggiorna_saldi    tinyint(1) NULL [0]
                 */
                
                $riga_saldi = [
                    'ratei_ferie_permessi_dipendente' => $file['dipendenti_id'],
                    'ratei_ferie_permessi_mese' => $row_import_cedolini['import_cedolini_mese_competenza'],
                    'ratei_ferie_permessi_anno' => $row_import_cedolini['import_cedolini_anno_competenza'],
                    'ratei_ferie_permessi_saldo_ferie' => $saldi_estratti['dipendenti_saldo_ferie'],
                    'ratei_ferie_permessi_saldo_rol' => $saldi_estratti['dipendenti_saldo_rol'],
                    'ratei_ferie_permessi_saldo_permessi' => $saldi_estratti['dipendenti_saldo_permessi'],
                    'ratei_ferie_permessi_aggiorna_saldi' => 1,
                ];
                
                // check if already exists for month and year and dipendente
                $rateo_fp_db = $this->apilib->searchFirst('ratei_ferie_permessi', [
                    'ratei_ferie_permessi_dipendente' => $riga_saldi['ratei_ferie_permessi_dipendente'],
                    'ratei_ferie_permessi_mese' => $riga_saldi['ratei_ferie_permessi_mese'],
                    'ratei_ferie_permessi_anno' => $riga_saldi['ratei_ferie_permessi_anno'],
                ]);
                
                if (empty($rateo_fp_db)) {
                    $this->apilib->create('ratei_ferie_permessi', $riga_saldi);
                    echo 'creato nuovo per ' . $file['dipendenti_id'] . ' ' . $row_import_cedolini['import_cedolini_mese_competenza'] . '/' . $row_import_cedolini['import_cedolini_anno_competenza'] . '<br>';
                } else {
                    // already exists
                }
            }

            // debug($saldi,true);
        }
    }