<?php

class Cron extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('contabilita/docs');

        $this->settings = $this->db->get('settings')->row_array();
        
    }
    
    public function cron_reminder_scadenze_fatture() {
        /**
         * 1. prendo tutte le aziende che hanno il cron attivo
         * 2. prendo le scadenze filtrato con:
         *
         * - azienda
         * - saldata 0, null, ''
         * - data saldo null, ''
         * - data scadenza - X giorno = oggi
         * - promemoria inviato 0, null, ''
         * - tipo documento = 1 (fatture)
         */
        
        set_log_scope('cron-reminder-scadenze-fatture');
        
        my_log('debug', "avvio cron invio reminder scadenza");
        
        $aziende = $this->apilib->search("documenti_contabilita_settings", ['documenti_contabilita_settings_cron_solleciti_attivo' => DB_BOOL_TRUE]);
        
        if (empty($aziende)) {
            my_log('error', "Nessuna azienda abilitata all'invio dei solleciti");
            
            return false;
        }
        
        $mappature = $this->docs->getMappature();
        extract($mappature);
        
//        debug($aziende, true);
        
        foreach ($aziende as $azienda) {
            if (empty($azienda['documenti_contabilita_settings_cron_solleciti_giorni_prima']) || !is_numeric($azienda['documenti_contabilita_settings_cron_solleciti_giorni_prima'])) {
                my_log('error', "L'azienda {$azienda['documenti_contabilita_settings_company_name']} non ha i giorni configurati correttamente");
                continue;
            }
            
            if (empty($azienda['documenti_contabilita_settings_cron_solleciti_template'])) {
                my_log('error', "L'azienda {$azienda['documenti_contabilita_settings_company_name']} non ha template mail configurato");
                continue;
            }
            
            $giorni_prima = $azienda['documenti_contabilita_settings_cron_solleciti_giorni_prima'];
            
            $scadenze = $this->apilib->search('documenti_contabilita_scadenze', [
                "(DATE(documenti_contabilita_scadenze_scadenza) <= (CURRENT_DATE + INTERVAL $giorni_prima DAY))",
                "(documenti_contabilita_scadenze_data_saldo IS NULL OR documenti_contabilita_scadenze_data_saldo = '')",
                "(documenti_contabilita_scadenze_saldata = '0' OR documenti_contabilita_scadenze_saldata = '' OR documenti_contabilita_scadenze_saldata IS NULL)",
                "(documenti_contabilita_scadenze_promemoria_inviato = '0' OR documenti_contabilita_scadenze_promemoria_inviato = '' OR documenti_contabilita_scadenze_promemoria_inviato IS NULL)",
                'documenti_contabilita_azienda' => $azienda['documenti_contabilita_settings_id'],
                'documenti_contabilita_tipo' => 1, //Solo fatture
            ], null, 0, null, 'ASC', 3);

            if (empty($scadenze)) {
                my_log('debug', "Nessuna scadenza oggi (".date('d/m/Y').") con intervallo $giorni_prima giorni per l'azienda {$azienda['documenti_contabilita_settings_company_name']}");
                continue;
            }
            
            $c = 0;
            $t = count($scadenze);
            foreach ($scadenze as $scadenza) {
                $last_used = $this->db->query("SELECT documenti_contabilita_mail_destinatario as email FROM documenti_contabilita_mail WHERE (
                    (documenti_contabilita_mail_destinatario <> '' AND documenti_contabilita_mail_destinatario IS NOT NULL)
                    AND documenti_contabilita_mail_documento_id IS NOT NULL
                    AND documenti_contabilita_mail_documento_id IN (
                        SELECT documenti_contabilita_id FROM documenti_contabilita WHERE documenti_contabilita_customer_id = '{$scadenza['documenti_contabilita_customer_id']}'
                    )
                ) ORDER BY documenti_contabilita_mail_id DESC");
                if ($last_used->num_rows() >= 1) {
                    $destinatario_email = $last_used->row()->email;
                } else {
                    $destinatario_email = $scadenza[$clienti_email];
                }
                if (empty($destinatario_email)) {
                    my_log('error', "Nessuna email trovata per la fattura '{$scadenza['documenti_contabilita_numero']}'");
                    continue;
                }
                
                if (!filter_var($destinatario_email, FILTER_VALIDATE_EMAIL)) {
                    my_log('error', "Formato email destinatario '{$destinatario_email}' non valido per la fattura '{$scadenza['documenti_contabilita_numero']}'");
                    continue;
                }
                
                $this->apilib->create('documenti_contabilita_mail', [
                    'documenti_contabilita_mail_mittente_nome' => $azienda['documenti_contabilita_mail_template_mittente_nome'],
                    'documenti_contabilita_mail_mittente' => $azienda['documenti_contabilita_mail_template_mittente'],
                    'documenti_contabilita_mail_oggetto' => $azienda['documenti_contabilita_mail_template_oggetto'],
                    'documenti_contabilita_mail_testo' => $azienda['documenti_contabilita_mail_template_testo'],
                    'documenti_contabilita_mail_documento_id' => $scadenza['documenti_contabilita_scadenze_documento'],
                    'documenti_contabilita_mail_template' => $azienda['documenti_contabilita_settings_cron_solleciti_template'],
                    'documenti_contabilita_mail_destinatario' => $destinatario_email
                ]);
                
                $this->apilib->edit('documenti_contabilita_scadenze', $scadenza['documenti_contabilita_scadenze_id'], [
                    'documenti_contabilita_scadenze_promemoria_inviato' => DB_BOOL_TRUE
                ]);
                
                progress($c++, $t);
            }
        }
    }
    
    public function cron_reminder_scadenze_spese() {
        /**
         * 1. prendo tutte le aziende che hanno il cron attivo
         * 2. prendo le scadenze filtrato con:
         *
         * - azienda
         * - saldata 0, null, ''
         * - data saldo null, ''
         * - data scadenza - X giorno = oggi
         * - promemoria inviato 0, null, ''
         */
        
        set_log_scope('cron-reminder-scadenze-spese');
        
        my_log('debug', "avvio cron invio reminder scadenza");
        
        $aziende = $this->apilib->search("documenti_contabilita_settings", ['documenti_contabilita_settings_cron_solleciti_spese_attivo' => DB_BOOL_TRUE]);
        
        if (empty($aziende)) {
            my_log('error', "Nessuna azienda abilitata all'invio dei solleciti");
            
            return false;
        }
        
        $mappature = $this->docs->getMappature();
        extract($mappature);
        
        $this->load->library('table');
        
        $this->table->set_template([
            'table_open' => '<table style="width: 100%; text-align: left">',
        ]);
        
        $this->table->set_heading(['Fornitore', 'N°', 'Importo', 'Data Scadenza']);
        
        foreach ($aziende as $azienda) {
            if (empty($azienda['documenti_contabilita_settings_cron_solleciti_spese_giorni_prima']) || !is_numeric($azienda['documenti_contabilita_settings_cron_solleciti_spese_giorni_prima'])) {
                my_log('error', "L'azienda {$azienda['documenti_contabilita_settings_company_name']} non ha i giorni configurati correttamente");
                continue;
            }
            
            if (empty($azienda['documenti_contabilita_settings_cron_solleciti_spese_mail'])) {
                my_log('error', "L'azienda {$azienda['documenti_contabilita_settings_company_name']} non ha la mail destinatario configurata");
                continue;
            }
            
            $destinatario_email = $azienda['documenti_contabilita_settings_cron_solleciti_spese_mail'];
            
            if (!filter_var($destinatario_email, FILTER_VALIDATE_EMAIL)) {
                my_log('error', "Formato email destinatario '{$destinatario_email}' non valido per l'azienda '{$azienda['documenti_contabilita_settings_company_name']}'");
                continue;
            }
            
            $giorni_prima = $azienda['documenti_contabilita_settings_cron_solleciti_spese_giorni_prima'];
            
            $scadenze = $this->apilib->search('spese_scadenze', [
                "(DATE(spese_scadenze_scadenza) <= (CURRENT_DATE + INTERVAL $giorni_prima DAY))",
                "(spese_scadenze_data_saldo IS NULL OR spese_scadenze_data_saldo = '')",
                "(spese_scadenze_saldata = '0' OR spese_scadenze_saldata = '' OR spese_scadenze_saldata IS NULL)",
                "(spese_scadenze_promemoria_inviato = '0' OR spese_scadenze_promemoria_inviato = '' OR spese_scadenze_promemoria_inviato IS NULL)",
                'spese_azienda' => $azienda['documenti_contabilita_settings_id'],
            ]);
            
            $c = 0;
            $t = count($scadenze);

            if (!empty($scadenze)) {
                foreach ($scadenze as $scadenza) {
                    $fornitore = json_decode($scadenza['spese_fornitore'], true);
                    
                    $scadenza['spese_scadenze_scadenza'] = dateFormat($scadenza['spese_scadenze_scadenza']);
                    $scadenza['spese_data_emissione'] = dateFormat($scadenza['spese_data_emissione']);
                    
                    $this->table->add_row([
                        $fornitore['ragione_sociale'],
                        $scadenza['spese_numero'],
                        ['data' => number_format($scadenza['spese_scadenze_ammontare'], 2, ',', '.'), 'style' => 'text-align: right'],
                        $scadenza['spese_scadenze_scadenza'],
                    ]);
                    
                    $this->apilib->edit('spese_scadenze', $scadenza['spese_scadenze_id'], [
                        'spese_scadenze_promemoria_inviato' => DB_BOOL_TRUE
                    ]);
    
                    progress(++$c, $t);
                }
                
                $mail_data = [
                    'data_scadenze' => (new DateTime)->modify("+{$giorni_prima} day")->format('d/m/Y'),
                    'nome_azienda' => $azienda['documenti_contabilita_settings_company_name'],
                    'elenco_scadenze' => $this->table->generate(),
                ];
                
                $this->mail_model->send($destinatario_email, 'spese_in_scadenza', '', $mail_data);
                my_log('debug', "Mail inviata per l'azienda {$azienda['documenti_contabilita_settings_company_name']}");
            } else {
                my_log('debug', "Nessuna scadenza da inviare per l'azienda {$azienda['documenti_contabilita_settings_company_name']}");
            }
        }
    }

    // Cron da processare ogni 5 minuti. Non farlo prima di  minuti per sicurezza in quanto il nome file contiene il minuto di creazione
    public function cron_documenti_da_processare_sdi()
    {
        // Viene usato un search limit 1 per fare in modo che venga elaborato un documento alla volta, ad ogni passaggio cron. Potenzialmente funziona anche con il ->search normale ma alcune volte, inviando piu fatture nello stesso supporto FI. non riceviamo correttamente gli esiti da Sogei.
        $documenti = $this->apilib->search("documenti_contabilita", [
            "documenti_contabilita_stato_invio_sdi" => 2,
            "documenti_contabilita_formato_elettronico" => DB_BOOL_TRUE,
        ], 1);

        echo "Trovati: " . count($documenti) . " da processare \n";

        //debug($documenti);

        if (!empty($documenti)) {
            foreach ($documenti as $documento) {
                echo "Processo documento:  " . $documento['documenti_contabilita_id'] . "\n";
                if (!$this->send_to_sdiftp($documento['documenti_contabilita_id'])) {
                    echo "Invio fallito! Controllare file di log!";
                } else {
                    echo "File processato";
                }
            }
        }
    }

    // Funzione richiamabile anche dall'esterno per cambiare lo stato ad
    private function change_sdi_status($documento, $status, $extra = null)
    {
        //debug($this->input->post());
        $data = (!empty($this->input->post())) ? $this->input->post() : array("documenti_contabilita_stato_invio_sdi" => $status, "documenti_contabilita_stato_invio_sdi_errore_gestito" => $extra);

        if (is_numeric($documento)) {
            //Pare ci sia un bug... arriva un this->input->post sbagliato e soprattutto con nome file vuoto. Forzo il valore eventualmente già ssalvato su db
            $documenti_contabilita_nome_file_xml = $this->db->query("SELECT documenti_contabilita_nome_file_xml FROM documenti_contabilita WHERE documenti_contabilita_id = '$documento'")->row()->documenti_contabilita_nome_file_xml;
            $data['documenti_contabilita_nome_file_xml'] = $documenti_contabilita_nome_file_xml;
            $this->apilib->edit("documenti_contabilita", $documento, $data);
            //debug($this->db->query("SELECT documenti_contabilita_nome_file_xml FROM documenti_contabilita WHERE documenti_contabilita_id = '$documento'")->row()->documenti_contabilita_nome_file_xml);
        } else {
            $zipname = $documento;
            $documento = $this->apilib->searchFirst("documenti_contabilita", ["documenti_contabilita_nome_zip_sdi" => $zipname]);

            $this->apilib->edit("documenti_contabilita", $documento['documenti_contabilita_id'], $data);
        }
    }

    // Funzione per inviare manualmente un documento al nostro server centralizzato
    private function send_to_sdiftp($documento_id)
    {
        $this->load->library('ftp');

        // Estrazione documento
        $documento = $this->apilib->view('documenti_contabilita', $documento_id);

        if (empty($documento)) {
            log_message('error', "send_to_sdiftp: Documento '$documento_id' non esistente!");
            return false;
            // die('Documento id non valido');
        }

        // Verifico se è stato caricato un file firmato uso quello altrimenti prendo quello non firmato
        if (!empty($documento['documenti_contabilita_file_xml_firmato'])) {
            if (!file_exists(FCPATH . "uploads/" . $documento['documenti_contabilita_file_xml_firmato'])) {
                log_message('error', "send_to_sdiftp: Documento '$documento_id' ha un file xml firmato caricato ma non esiste il file fisico " . FCPATH . "uploads/" . $documento['documenti_contabilita_file_xml_firmato'] . ". Non posso inviare!");
                $this->change_sdi_status($documento_id, '4', "Il file XML firmato è anomalo. Verificare e rimandare.");
                return false;
            }

            $xmlfilename = basename($documento['documenti_contabilita_file_xml_firmato']);
            $file = file_get_contents(FCPATH . "uploads/" . $documento['documenti_contabilita_file_xml_firmato']);
        } else {
            // Situazione anomala, non dovrei arrivare qui senza un file XML, quindi non proseguo.
            if (empty($documento['documenti_contabilita_file_xml']) || !file_exists(FCPATH . "uploads/" . $documento['documenti_contabilita_file_xml'])) {
                log_message('error', "send_to_sdiftp: Documento '$documento_id' non ha un file XML firmato o non ha un file xml fisico normale. Forse il file semplicemente non esiste. Non posso inviarlo allo sdi!");
                $this->change_sdi_status($documento_id, '4', "File xml mancante. Verificare e rimandare.");
                return false;
            }

            $xmlfilename = $documento['documenti_contabilita_nome_file_xml'];
            $file = file_get_contents(FCPATH . "uploads/" . $documento['documenti_contabilita_file_xml']);
        }


        // Generazione nome file zip
        $partita_iva = '02675040303'; // Partita iva dell'intermediario.
        $data_gregoriana = (date('Y')) . (str_pad(date('z') + 1, 3, '0', STR_PAD_LEFT));
        $ora = date('Hi');
        $incrementale = "001";

        $xmlquadname = "FI.$partita_iva.$data_gregoriana.$ora.$incrementale.xml";
        $zipname = "FI.$partita_iva.$data_gregoriana.$ora.$incrementale.zip";

        //20220928 - Controllo che lo zipname non esista già. Questo serve perchè se chiamiamo a mano il cron nello stesso minuto, evitiamo che vada a generare uno zip con più file dentro. La logica invece deve esseere "1 FILE ALLA VOLTA" (o meglio: uno zip al minuto)!
        $check_zipname_exists = $this->db->query("SELECT * FROM documenti_contabilita WHERE documenti_contabilita_nome_zip_sdi = '$zipname' LIMIT 1");
        if ($check_zipname_exists->num_rows() > 0) {
            log_message('error', "Nome '$zipname' già presente su documenti_contabilità (documento: '$documento_id')");
            return false;
        }

        // Creazione file di quadratura xml temporaneo TODO: Da rifare con generazione xml fatta bene
        // Per scarti ET02 ricevuti senza motivo abbiamo cambiato la dataoracreazione da date('c') a gmdate('Y-m-d\TH:i:s\Z') cosi da portare tutto in UTC
        $file_quadratura = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                <ns2:FileQuadraturaFTP xmlns:ns2="http://www.fatturapa.it/sdi/ftp/v2.0" versione="2.0">
                <IdentificativoNodo>' . $partita_iva . '</IdentificativoNodo>
                <DataOraCreazione>' . gmdate('Y-m-d\TH:i:s\Z') . '</DataOraCreazione>
                <NomeSupporto>' . $zipname . '</NomeSupporto>
                <NumeroFile>
                        <File>
                                <Tipo>FA</Tipo>
                                <Numero>1</Numero>
                        </File>
                </NumeroFile>
                </ns2:FileQuadraturaFTP>';
        // Creo lo zip

        // Dir temporeanea
        $physicalDir = FCPATH . "uploads/modules_files/contabilita/zip_inviati";
        if (!is_dir($physicalDir)) {
            mkdir($physicalDir, 0755, true);
        }

        $tmpZipFile = "{$physicalDir}/{$zipname}";
        $zip = new ZipArchive();
        if ($zip->open($tmpZipFile, ZipArchive::CREATE) !== true) {
            $this->change_sdi_status($documento_id, '4', "Zip file creation failed. Cannot open zip file");
            exit("cannot open <$tmpZipFile>\n");
        }
        $zip->addFromString($xmlquadname, $file_quadratura);
        $zip->addFromString($xmlfilename, $file);
        $zip->close();


        // Mando al server SDI il mio file zip, ma potrebbe rifiutarlo in quanto qualcun altro potrebbe aver già inviato un file zip con lo stesso minutaggio.
        $base64_content = base64_encode(file_get_contents($tmpZipFile));

        // TODO: Inserire nei settings di contabilita
        $serversdi = "https://serversdi.h2web.it/rest/v1/create/log_files";
        $token = "4f075894402fe39edcfcd7c0d5a6475a";
        $post_data = array('log_files_filename' => $xmlfilename, 'log_files_zipname' => $zipname, 'log_files_filezip_b64' => $base64_content, 'log_files_direction' => 1, 'log_files_type' => 1, 'log_files_partita_iva' => $documento['documenti_contabilita_settings_company_vat_number']);
        $output = my_api($serversdi, $token, $post_data);

        if (empty($output['data'][0]['log_files_id'])) {

            // Cancello il file che avevo creato
            unlink($tmpZipFile);
            // Non cambio stato al file altrimenti non riproverebbe al prossimo giro
            echo_log('error', 'Errore invio file FI. al server SDI, output non conforme oppure non puo accettare il file in questo momento.');
            echo_log('error', $output);
            return false;
        } else {

            // Aggiorno il documento indicando il nome zip utilizzato per l'invio del documento e anche il nome del file xml per fare match piu facilmente con le notifiche di scarto
            $this->apilib->edit("documenti_contabilita", $documento_id, ["documenti_contabilita_nome_zip_sdi" => $zipname]);

            $this->change_sdi_status($documento_id, '8');

            return true;
        }


        // DEPRECATO 7 Febbraio 2023, non si trasferisce piu via FTP ma inviando alle API di serverSDI poi sara lui a depositarlo allo SDI.
        // Configurazioni FTP TODO: Portare in costanti
        // $config['hostname'] = '195.201.22.125';
        // $config['username'] = 'docftpuser';
        // $config['password'] = 'nB@NGs9u292';
        // $config['debug'] = true;

        // // Connession ed upload ... TODO Verificare eventuali errori di upload
        // $this->ftp->connect($config);
        // if ($this->ftp->upload($tmpZipFile, "./ftp_temp_dir/" . $zipname, FTP_BINARY, 0775)) {
        //     $this->change_sdi_status($documento_id, '8');
        //     $this->ftp->close();

        //     return true;
        // } else {
        //     log_message('error', "send_to_sdiftp: Invio FTP al server centralizzato non riuscito (documento '$documento_id')");

        //     $this->change_sdi_status($documento_id, '4', "Invio FTP al server centralizzato non riuscito.");
        //     $this->ftp->close();
        //     return false;
        // }
    }
}