<?php
    class Cron extends MY_Controller {
        public function __construct () {
            parent::__construct();
            
            $this->load->model('openapi-integration/openapi');
            
            set_log_scope("cron-openapi");
        
            if (empty($this->openapi->openapi_settings['openapi_settings_enable_crons']) || $this->openapi->openapi_settings['openapi_settings_enable_crons'] == DB_BOOL_FALSE) {
                my_log('error', "Cron non attivo nelle impostazioni del modulo openapi");
                throw new ApiException("Cron non attivo nelle impostazioni del modulo openapi");
            }
        }
        
        /**
         * @return false|void
         * @throws ApiException
         *
         * @todo dividere in due questo cron facendo una volta al giorno il where solo per le fatture che hanno già ricevuto un RC o NS e tutte le altre ogni 4 ore
         */
        
        public function get_notifiche_sdi() {
            if (!$this->datab->module_installed('contabilita')) {
                my_log('error', "Modulo INGEGNO OFFICE non installato");
                throw new ApiException("Modulo INGEGNO OFFICE non installato");
            }
            
            my_log('debug', 'inizio cron ricezione notifiche sdi');
            
            $documenti = $this->apilib->search('documenti_contabilita', [
                "(documenti_contabilita_uuid_openapi IS NOT NULL AND documenti_contabilita_uuid_openapi <> '')", // solo i documenti che hanno un UUID OpenApi
                "( (documenti_contabilita_stato_invio_sdi = '5') OR (documenti_contabilita_tipo_destinatario = '3' AND documenti_contabilita_stato_invio_sdi NOT IN (12, 13, 15)) )" // prendo i documenti che hanno lo stato sdi "in attesa da sdi" oppure i documenti inviati alla PA e non è con stato: Accettata dalla PA, Rifiutata dalla PA, Decorrenza termini
            ]);
            
            if (empty($documenti)) {
                my_log('debug', 'nessun documento con uuid verso PA o con stato "in attesa da sdi" trovato');
                echo 'nessun documento con uuid verso PA o con stato "in attesa da sdi" trovato';
                return false;
            }
            
            $ricezione_sdi_openapi = $this->db
                ->where("(documenti_contabilita_ricezione_sdi_uuid_notifica_openapi IS NOT NULL AND documenti_contabilita_ricezione_sdi_uuid_notifica_openapi <> '')", null, false)
                ->get('documenti_contabilita_ricezione_sdi')->result_array();
            
            $ricezione_sdi_openapi = array_key_map($ricezione_sdi_openapi, 'documenti_contabilita_ricezione_sdi_uuid_notifica_openapi');
            
            $cartella_file_sdi = FCPATH . 'uploads/modules_files/contabilita/import_ricezione_sdi';
            
            if (!is_dir($cartella_file_sdi)) {
                mkdir($cartella_file_sdi, DIR_WRITE_MODE, true);
            }
            
            foreach ($documenti as $documento) {
                $uuid = $documento['documenti_contabilita_uuid_openapi'];
                
                my_log("debug", "processo documento #{$documento['documenti_contabilita_id']} con uuid $uuid");
                
                $oa_notification = $this->openapi->get_invoice_notifications($uuid, $documento, 'json');
                
                if (empty($oa_notification['data'])) {
                    my_log('debug', "nessuna notifica da openapi per il documento #{$documento['documenti_contabilita_id']}");
                    echo "nessuna notifica da openapi per il documento #{$documento['documenti_contabilita_id']}";
                    continue;
                }
                
                $notifica = $oa_notification['data'][0];
                
                if (in_array($notifica['uuid'], $ricezione_sdi_openapi)) {
                    my_log('debug', "notifica già presente a sistema per il documento #{$documento['documenti_contabilita_id']}");
                    echo "notifica già presente a sistema per il documento #{$documento['documenti_contabilita_id']}";
                    continue;
                }
                
                $oa_notification_xml = $this->openapi->get_invoice_notifications($uuid, $documento, 'xml');
                
                $xml = simplexml_load_string($oa_notification_xml);
                
                $textContent = (string) $xml->item;
                
                $full_file_path = $cartella_file_sdi . '/' . $notifica['file_name'];
                
                file_put_contents($full_file_path, trim($textContent));
                
                // Faccio questo accrocchio perchè OPENAPI mi ritorna il file in questo modo: 20230830_131138_982658_IT10442360961_8NM0T_NS_001.xml
                // A me serve invece solamente la parte da IT in poi, in quanto lo script che processa le elaborazioni sdi, fa un'explode sul nome del file per ottenere lo stato (NS)
                // Quindi converto il nome del file in OPENAPI-IT10442360961_8NM0T_NS_001.xml
                $oa_filename_ex = explode('_', $notifica['file_name'], 4);
                $oa_filename = '';
                
                foreach ($oa_filename_ex as $filename_chunk) {
                    if (substr($filename_chunk, 0, 2) == 'IT' && substr($filename_chunk, -4) == '.xml') {
                        $oa_filename = 'OPENAPI-'. $filename_chunk;
                    }
                }
                
                $file_data = [];
                $file_data['documenti_contabilita_ricezione_sdi_nome_file'] = $oa_filename;
                $file_data['documenti_contabilita_ricezione_sdi_file_verificato'] = $full_file_path;
                $file_data['documenti_contabilita_ricezione_sdi_source'] = 2; // UPLOAD
                $file_data['documenti_contabilita_ricezione_sdi_stato_elaborazione'] = 1; // Da elaborare
                $file_data['documenti_contabilita_ricezione_sdi_creation_date'] = date('Y-m-d H:i');
                $file_data['documenti_contabilita_ricezione_sdi_uuid_notifica_openapi'] = $notifica['uuid'];

                $this->db->insert('documenti_contabilita_ricezione_sdi', $file_data);

                $this->mycache->clearCache();

                my_log('debug', "notifica importata per #{$documento['documenti_contabilita_id']}");
                echo "notifica importata per #{$documento['documenti_contabilita_id']}";
            }
        }
    }