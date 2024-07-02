<?php
    class Rapportini extends MY_Controller {
        public function __construct() {
            parent::__construct();

            header('Access-Control-Allow-Origin: *');
            @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}"); //X-Requested-With

            set_log_scope("rapportini-app"); // loggo tutto su log specifico, così da avere un debug rapido
        }

        /**
         * Imposta all'intervento l'ora di inizio e fine dell'appuntamento corrispondente.
         * Crea la presenza con gli stessi orari dell'appuntamento
         * ! 2023-06-20
         * ! Se è stata timbrata entrata/uscita e poi creato il rapportino ho già una presenza, devo cercarla, cancellarla e crearne
         * ! una nuova con i dati dell'appuntamento (impostando da quella vecchia il cliente, commessa e reparto)
         */
        public function normalizza($rapportino_id) {
            
            $rapportino =  $this->apilib->view('rapportini', $rapportino_id);

            if(!empty($rapportino['rapportini_appuntamento_id'])) {
                $appuntamento = $this->apilib->view('appuntamenti', $rapportino['rapportini_appuntamento_id']);

                if(!empty($appuntamento)) {                   

                    //Creo la presenza per ogni operatore del rapportino
                    foreach ($rapportino['rapportini_operatori'] as $user_id => $user) {
                        $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $user_id]); 
                
                        if(!empty($dipendente)) {
                            try {
                                //Ore totali presenza
                                $inizio_rapportino = new DateTime($rapportino['rapportini_data'].' '.$appuntamento['appuntamenti_ora_inizio']);
                                $fine_rapportino = new DateTime($rapportino['rapportini_data'].' '.$appuntamento['appuntamenti_ora_fine']);
                                $diff_rapportino = $fine_rapportino->diff($inizio_rapportino);
                                $ore_totali = round(($diff_rapportino->s / 3600) + ($diff_rapportino->i / 60) + $diff_rapportino->h + ($diff_rapportino->days * 24), 2);
                                //Nessuno straordinario
                                $ore_straordinario = 0;
                                //Ore per calendario
                                $ora_inizio_calendar = $rapportino['rapportini_data'].' '.$appuntamento['appuntamenti_ora_inizio'].':00';
                                $ora_fine_calendar = $rapportino['rapportini_data'].' '.$appuntamento['appuntamenti_ora_fine'].':00';

                                //Se era già stata creata la presenza da timbratura prima di compilare il rapportino la cancello
                                // perchè normalizzando vengono modificati gli orari
                                $presenza_da_timbratura = $this->apilib->searchFirst('presenze', [
                                    'presenze_dipendente' => $dipendente['dipendenti_id'],
                                    'presenze_data_inizio' => $rapportino['rapportini_data'],
                                    'presenze_data_fine' => $rapportino['rapportini_data'],
                                    'presenze_ora_inizio' => $rapportino['rapportini_ora_inizio'],
                                    'presenze_ora_fine' => $rapportino['rapportini_ora_fine'],
                                    'presenze_cliente' => $rapportino['rapportini_cliente'],
                                    'presenze_commessa' => $rapportino['rapportini_commessa'],
                                ]);

                                //Sulla nuova presenza devo salvare il reparto di quella che ho appena cancellato
                                $reparto_presenza_da_cancellare = null;
                                if(!empty($presenza_da_timbratura)) {
                                    $reparto_presenza_da_cancellare = $presenza_da_timbratura['presenze_reparto'];
                                    try {
                                        $this->apilib->delete('presenze', $presenza_da_timbratura['presenze_id']);
                                        $this->apilib->clearCache();
                                    } catch (Exception $e) {
                                        log_message('error', "Impossibile cancellare la presenza #{$presenza_da_timbratura['presenze_id']} precedente al rapportino: Error: {$e->getMessage()}");
                                        throw new Exception('Impossibile cancellare la presenza precedente al rapportino');
                                        exit;
                                    }
                                }
                
                                $this->db->insert('presenze', [
                                    'presenze_dipendente' => $dipendente['dipendenti_id'],
                                    'presenze_data_inizio' => $rapportino['rapportini_data'],
                                    'presenze_data_fine' => $rapportino['rapportini_data'],
                                    'presenze_ora_inizio' => $appuntamento['appuntamenti_ora_inizio'],
                                    'presenze_ora_fine' => $appuntamento['appuntamenti_ora_fine'],
                                    'presenze_ore_totali' => $ore_totali,
                                    'presenze_straordinario' => $ore_straordinario,
                                    'presenze_cliente' => $rapportino['rapportini_cliente'] ?? '',
                                    'presenze_commessa' => $rapportino['rapportini_commessa'],
                                    'presenze_rapportino_id' => $rapportino['rapportini_id'],
                                    'presenze_data_inizio_calendar' => $ora_inizio_calendar,
                                    'presenze_data_fine_calendar' => $ora_fine_calendar,
                                    'presenze_reparto' => $reparto_presenza_da_cancellare
                                ]);

                                //Modifico il rapportino con le ore dell'appuntamento e lo marco come NON da validare
                                $this->db->where('rapportini_id', $rapportino_id)->update('rapportini', ['rapportini_ora_inizio' => $appuntamento['appuntamenti_ora_inizio'], 'rapportini_ora_fine' => $appuntamento['appuntamenti_ora_fine'], 'rapportini_da_validare' => DB_BOOL_FALSE]);
                                
                            } catch (Exception $e) {
                                log_message('error', "Impossibile creare presenza associata a rapportino tramite normalizza: Error: {$e->getMessage()}");
                                die(json_encode(['status' => 3, 'msg' => "Errore durante la normalizzazione del rapportino, contattare l'assietenza"]));
                            }
                        }
                    }
                    
                    die(json_encode(['status' => 2]));
                }
            }
        }

        /****************************************************************************
         * 
         * ! Caricamento foto intervento su relazione con categoria e coordinate
         * 
         ****************************************************************************/
        public function savePhotos() {
            $post = $this->input->post();

            $rapportino_id = $post['rapportino_id'];
            $immagine = json_decode($post['immagini'], true);

            if (empty($rapportino_id) || empty($immagine)) {
                die(json_encode(['status' => 0, 'txt' => 'Immagini e/o rapportino non riconosciuti.']));
            }

            //Foto suddivise in folder per rapportino (eventuale export)
            if (!file_exists(FCPATH . "uploads/app/rapportini")) {
                mkdir(FCPATH . "uploads/app/rapportini");
            }

//            $rapportino = $this->apilib->view('rapportini', $rapportino_id);
//            $crm_settings = $this->db->get('settings')->row_array();

            //Array immagini appena aggiunte
            $imgs = [];

            
            $blob = base64_decode($immagine['data']);
    
            $db_path = "app/rapportini";
            $folder = FCPATH . 'uploads/' . $db_path;
            $filename = $immagine['name'];
            
            $filename_without_extension = pathinfo($filename, PATHINFO_BASENAME);
            $file_extension = pathinfo($filename, PATHINFO_EXTENSION);
            
            $new_filename = md5('Plan' . date('Y') . $filename_without_extension . hrtime(true)) . '.' . $file_extension;
            
            $fullpath = "{$folder}/{$new_filename}";
            $fulldb_path = "{$db_path}/{$new_filename}";

            file_put_contents($fullpath, $blob);

            // Verifico che sulla commessa sia attivo il flag per impostare il watermark GPS
//            if (!empty($rapportino['projects_applica_watermark_gps']) && $rapportino['projects_applica_watermark_gps'] == DB_BOOL_TRUE && $this->datab->module_installed('image-manipulation')) {
//                $this->load->model('image-manipulation/watermark_model', 'wm');
//
//                // verifico che il rapportino abbia latitudine e longitudine
//                if (!empty($immagine['latitude']) && !empty($immagine['longitude'])) {
//                    $exif = exif_read_data($fullpath, 'EXIF', true); // mi estrapolo gli exif dalla foto
//
//                    // verifico se negli exif c'è la data di scatto originale
//                    if (!empty($exif['EXIF']['DateTimeOriginal'])) {
//                        $image_date = (DateTime::createFromFormat('Y:m:d H:i:s', $exif['EXIF']['DateTimeOriginal']))->format('d/m/Y H:i');
//                    } else { // altrimenti prendo data e ora attuali
//                        $image_date = date('d/m/Y H:i');
//                    }
//
//                    $wm_text_applied = $this->wm->apply_text($fullpath, "{$immagine['latitude']}, {$immagine['longitude']}\n" . $image_date);
//
//                    if (!$wm_text_applied) {
//                        my_log("error", "Errore applicazione watermark testuale per file {$fulldb_path} -> " . $this->image_lib->display_errors());
//                    }
//                } else {
//                    // se non li ha, allora l'app non li sta passando (probabilmente l'utente non ha il gps attivo o non ha dato i permessi)
//                }
//            }
//
//            if (!empty($crm_settings['settings_company_logo']) && !empty($rapportino['projects_applica_watermark_logo']) && $rapportino['projects_applica_watermark_logo'] == DB_BOOL_TRUE && $this->datab->module_installed('image-manipulation')) {
//                $this->load->model('image-manipulation/watermark_model', 'wm');
//
//                // applico il watermark
//                $wm_overlay_applied = $this->wm->apply_overlay($fullpath,  FCPATH . 'uploads/' . $crm_settings['settings_company_logo'], [
//                    'wm_vrt_offset' => 100,
//                    'wm_type' => 'overlay'
//                ]);
//
//                if (!$wm_overlay_applied) {
//                    my_log("error", "Errore applicazione watermark logo per file {$fulldb_path}");
//                }
//            }

            try {
                //Inserisco immagini nella loro entità e mi torno id e path                
                $int_imm = $this->apilib->create('rapportini_immagini', [
                    'rapportini_immagini_file' => $fulldb_path,
                    'rapportini_immagini_rapportino_id' => $rapportino_id,
                    'rapportini_immagini_categoria' => $immagine['category'] ?? null,
                    'rapportini_immagini_latitudine' => $immagine['latitude'] ?? null,
                    'rapportini_immagini_longitudine' => $immagine['longitude'] ?? null,
                    'rapportini_immagini_operatore' => $immagine['dipendente'] ?? null,
                    'rapportini_immagini_watermarked' => DB_BOOL_FALSE
                ]);

                if(!empty($int_imm)) {
                    $imgs[] = ['path' => "{$int_imm['rapportini_immagini_file']}", 'id' => "{$int_imm['rapportini_immagini_id']}"];

                    //Inserisco id rapportino, id immagine e categoria nella relazione
                    $insert_data = ['rapportini_id' => $rapportino_id, 'rapportini_immagini_id' => $int_imm['rapportini_immagini_id']];
                    if (!empty($immagine['category'])) {
                        $insert_data['rel_rapportini_foto_categoria'] = $immagine['category'];
                    }

                    $this->db->insert('rel_rapportini_foto', $insert_data);
                    $this->apilib->clearCache();
                }
            } catch (Exception $e) {
                log_message('error', $e->getMessage());
                die(json_encode(['status' => 0, 'txt' => 'Errore durante il salvataggio delle immagini']));
            }
            die(json_encode(['status' => 1, 'txt' => 'Immagini salvate con successo', 'imgs' => json_encode($imgs)]));
        }


    }