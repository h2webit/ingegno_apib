<?php

class Cron extends MY_Controller
{
    public function __construct ()
    {
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
    
    public function get_notifiche_sdi ()
    {
        if (!$this->datab->module_installed('contabilita')) {
            my_log('error', "Modulo INGEGNO OFFICE non installato");
            throw new ApiException("Modulo INGEGNO OFFICE non installato");
        }
        
        my_log('debug', 'inizio cron ricezione notifiche sdi');
        
        $documenti = $this->apilib->search('documenti_contabilita', [
            "(documenti_contabilita_uuid_openapi IS NOT NULL AND documenti_contabilita_uuid_openapi <> '')", // solo i documenti che hanno un UUID OpenApi
            
            /**
             * Prendo solo i documenti che o hanno lo stato "In attesa da SDI",
             * OPPURE i documenti PA che NON hanno uno dei seguenti stati SDI:
             * - Accettata dalla PA (12)
             * - Rifiutata dalla PA (13)
             * - Decorrenza termini (15)
             * - Scartata dal SDI (6)
             * - Mancata consegna (10)
             * - Attestazione avvenuta trasmissione (14)
             */
            "( (documenti_contabilita_stato_invio_sdi = '5') OR (documenti_contabilita_tipo_destinatario = '3' AND documenti_contabilita_stato_invio_sdi NOT IN (12, 13, 15, 6, 10, 14) ) )",
        ], null, 0, 'documenti_contabilita_id', 'ASC');
        
        if (empty($documenti)) {
            my_log('debug', 'nessun documento con uuid verso PA o con stato "in attesa da sdi" trovato');
            echo 'nessun documento con uuid verso PA o con stato "in attesa da sdi" trovato';
            return false;
        }
        
        $ricezione_sdi_openapi = $this->db->where("(documenti_contabilita_ricezione_sdi_uuid_notifica_openapi IS NOT NULL AND documenti_contabilita_ricezione_sdi_uuid_notifica_openapi <> '')", null, false)->get('documenti_contabilita_ricezione_sdi')->result_array();
        
        $ricezione_sdi_openapi = array_key_map($ricezione_sdi_openapi, 'documenti_contabilita_ricezione_sdi_uuid_notifica_openapi');
        $cartella_file_sdi = FCPATH . 'uploads/modules_files/contabilita/import_ricezione_sdi';
        
        if (!is_dir($cartella_file_sdi)) {
            mkdir($cartella_file_sdi, DIR_WRITE_MODE, true);
        }
        
        $t_docs = count($documenti);
        $c_docs = 0;
        foreach ($documenti as $documento) {
            $uuid = $documento['documenti_contabilita_uuid_openapi'];
            
            my_log("debug", "processo documento {$documento['documenti_contabilita_numero']}/{$documento['documenti_contabilita_serie']} (#{$documento['documenti_contabilita_id']}) con uuid $uuid");
            
            $oa_notification = $this->openapi->get_invoice_notifications($uuid, $documento, 'json');
            
            if (empty($oa_notification['data'])) {
                my_log('debug', "nessuna notifica da openapi per il documento {$documento['documenti_contabilita_numero']}/{$documento['documenti_contabilita_serie']} (#{$documento['documenti_contabilita_id']})");
                echo "nessuna notifica da openapi per il documento {$documento['documenti_contabilita_numero']}/{$documento['documenti_contabilita_serie']} (#{$documento['documenti_contabilita_id']})<br/>";
                progress(++$c_docs, $t_docs, 'documenti');
                continue;
            }
            
            /**
             * Michael, 15/01/2024
             *
             *
             * Faccio 2 chiamate per documento, una JSON e l'altra XML, in quanto:
             * - Il JSON ha più informazioni riguardante le notifiche SDI, come ad esempio il nome del file con i vari codici di stato (NE, DT, etc)
             * - L'XML ha il contenuto XML dello SDI che viene parsato poi dal cron "elaborazione_spese_da_sdi" del modulo INGEGNO OFFICE.
             *
             * Ho fatto entrambe le chiamate perchè nonostante il JSON abbia il nome del file, non ho trovato nessun modo per poterci accedere in modo diretto, se non appunto facendo la seconda chiamata.
             *
             *
             * Sia il JSON che l'XML hanno (potenzialmente) più notifiche nella stessa chiamata (nel JSON è un array multidimensionale in 'data', nell'XML è tutto nel primo livello "item").
             * Sapendo questa cosa, quindi, ho fatto un match usando l'UUID della notifica e quindi ottenendo il corretto contenuto XML della singola notifica.
             *
             *
             * Tutto questo andava a risolvere un problema piuttosto grave che faceva tante, troppe chiamate verso Openapi, andando in sovracosto.
             * Questo perchè per ogni notifica, veniva fatta una chiamata all'XML ed essa parsata erroneamente.
             * */
            
            $oa_notification_xml = $this->openapi->get_invoice_notifications($uuid, $documento, 'xml');
            
            $xmlObj = simplexml_load_string($oa_notification_xml);
            
            $notifiche_sdi = $oa_notification['data'];
            
            $t_notifiche = count($notifiche_sdi);
            $c_notifiche = 0;
            foreach ($notifiche_sdi as $notifica) {
                if (in_array($notifica['uuid'], $ricezione_sdi_openapi)) {
                    my_log('debug', "notifica già presente a sistema per il documento {$documento['documenti_contabilita_numero']}/{$documento['documenti_contabilita_serie']} (#{$documento['documenti_contabilita_id']})");
                    echo "notifica già presente a sistema per il documento {$documento['documenti_contabilita_numero']}/{$documento['documenti_contabilita_serie']} (#{$documento['documenti_contabilita_id']})<br/>";
                    continue;
                }
                
                $xml_content = null;
                
                foreach ($xmlObj->item as $notifica_xml) {
                    if ((string) $notifica_xml['key'] === $notifica['uuid']) {
                        $xml_content = (string) $notifica_xml;
                    }
                }
                
                if (!$xml_content)
                    continue;
                
                $full_file_path = $cartella_file_sdi . '/' . $notifica['file_name'];
                
                file_put_contents($full_file_path, trim($xml_content));
                
                // Faccio questo accrocchio perchè OPENAPI mi ritorna il file in questo modo: 20230830_131138_982658_IT10442360961_8NM0T_NS_001.xml
                // A me serve invece solamente la parte da IT in poi, in quanto lo script che processa le elaborazioni sdi, fa un'explode sul nome del file per ottenere lo stato (NS)
                // Quindi converto il nome del file in OPENAPI-IT10442360961_8NM0T_NS_001.xml
                $oa_filename_ex = explode('_', $notifica['file_name'], 4);
                $oa_filename = '';
                
                foreach ($oa_filename_ex as $filename_chunk) {
                    if (substr($filename_chunk, 0, 2) == 'IT' && substr($filename_chunk, -4) == '.xml') {
                        $oa_filename = 'OPENAPI-' . $filename_chunk;
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
                echo "notifica importata per {$documento['documenti_contabilita_numero']}/{$documento['documenti_contabilita_serie']} (#{$documento['documenti_contabilita_id']})<br/>";
                progress(++$c_notifiche, $t_notifiche, "notifiche");
            }
            
            progress(++$c_docs, $t_docs, 'documenti');
        }
    }
}