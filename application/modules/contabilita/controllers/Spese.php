<?php

class Spese extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->settings = $this->db->get('settings')->row_array();

        $this->load->model('contabilita/docs');
    }

    private function stripP7MData($string)
    {
        //$newString=preg_replace('/[\x00\x04\x82\x03\xE8\x81]/', '', $string);
        $newString = preg_replace('/[[:^print:]]/', '', $string);

        // skip everything before the XML content
        $startXml = substr($newString, strpos($newString, '<?xml '));

        // skip everything after the XML content
        preg_match_all('/<\/.+?>/', $startXml, $matches, PREG_OFFSET_CAPTURE);
        $lastMatch = end($matches[0]);
        $str = substr($startXml, 0, $lastMatch[1]) . $lastMatch[0];
        $startAll = strpos($str, "<Allegati");
        if ($startAll !== false) {
            $endAll = strpos($str, "</Allegati>");
            $str = substr($str, 0, $startAll) . substr($str, ($endAll + 11));
        }

        $chiusura = '</p:FatturaElettronica>';
        $str = substr($str, 0, stripos($str, $chiusura) + strlen($chiusura));
        return $str;
    }

    private function verifyP7MData($file_content)
    {
        // Creo un file temporaneo da verificare con openssl bash
        $tempfile = "tmp_p7m_ver_" . time();
        $temp_output_file = "tmp_p7m_ver_output_" . time();
        $physicalDir = FCPATH . "uploads/modules_files/contabilita/";

        if (!file_exists($physicalDir)) {
            mkdir($physicalDir, 0755, true);
        }

        file_put_contents($physicalDir . $tempfile, $file_content);

        // Verifico il file
        echo exec("openssl smime -verify -inform DER -in " . $physicalDir . $tempfile . " -noverify -out " . $physicalDir . $temp_output_file);

        // Ritorno il contenuto del file
        if (file_exists($physicalDir . $temp_output_file)) {
            $content = file_get_contents($physicalDir . $temp_output_file);
            unlink($physicalDir . $temp_output_file);
            unlink($physicalDir . $tempfile);
            return $content;
        } else {
            return false;
        }
    }

    /*
     * Check elaborazione ferma dei documenti da SDI
     *
     * 1. Controllo che non ci siano elaborazioni SDI da fare piu vecchie di 4 ore.
     * Invio mail di segnalazione all'admin
     *
     */
    public function check_xml_fermi_sdi()
    {
        /*
         * 1. Controllo che non ci siano elaborazioni SDI da fare piu vecchie di 3 ore.
         */

        // Recupero i file zip da elaborare
        $files = $this->apilib->search('documenti_contabilita_ricezione_sdi', [
            "documenti_contabilita_ricezione_sdi_stato_elaborazione = '1'",
            "HOUR(TIMEDIFF(NOW(), documenti_contabilita_ricezione_sdi_creation_date)) > 3",
        ], 100, 0, 'documenti_contabilita_ricezione_sdi_nome_file_zip', 'ASC');

        if (count($files) < 1) {
            echo "Non ci sono file da elaborare.";
            exit();
        } else {
            $mail_data = [];
            $settings = $this->db->get('settings')->row_array();

            if ($settings['settings_company_email']) {
                echo "Inviata mail di notifica";
                $check = $this->mail_model->send($settings['settings_company_email'], 'notifica_esiti_sdi_mancanti', 'it', $mail_data);

                if (!$check) {
                    log_message('error', "Invio mail check_documenti_fermi SDI fallito: {$check}");
                }
            } else {
                echo "Mail non configurata nei settings, impossibile inviare il messaggio";
            }
        }
    }

    /*
     * Check status SDI
     *
     * 2. Controllo che non ci siano documenti fermi in da piu di 3 ore con lo stato Accettata, Elaborazione in corso etc.. Con lo stesso criterio del box rosso in pagina Fatture
     * Invio mail di segnalazione all'admin
     *
     */
    public function check_status_sdi($file_id = null)
    {
        $aziende = $this->apilib->search('documenti_contabilita_settings');

        foreach ($aziende as $azienda) {
            // Se l'azienda ha l'invio SDI disattivato, viene skippata
            if ($azienda['documenti_contabilita_settings_invio_sdi_attivo'] != DB_BOOL_TRUE) {
                continue;
            }
            $azienda_id = $azienda['documenti_contabilita_settings_id'];
            /*
             * 2. Controllo che non ci siano documenti fermi in da piu di 3 ore con lo stato Accettata, Elaborazione in corso etc.. Con lo stesso criterio del box rosso in pagina Fatture
             */
            // Solo fatture o note di credito
            // Solo fatture elettroniche
            $where = [];
            $where[] = "documenti_contabilita_azienda = '{$azienda_id}'";
            $where[] = 'documenti_contabilita_tipo IN (1,4,11,12)';
            $where[] = "documenti_contabilita_formato_elettronico = '" . DB_BOOL_TRUE . "'";

            if ($this->db->dbdriver != 'postgre') {
                $filtro_data_2g = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE DATEDIFF(NOW(), documenti_contabilita_cambi_stato_creation_date) < 2)";
                $filtro_data_10h = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE HOUR(TIMEDIFF(NOW(), documenti_contabilita_cambi_stato_creation_date)) < 10)";
            } else {
                $filtro_data_2g = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE documenti_contabilita_cambi_stato_creation_date > now() - '2 days'::interval)";
                $filtro_data_10h = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE documenti_contabilita_cambi_stato_creation_date > now() - '10 hours'::interval)";

                $where[] = "DATE_PART('YEAR', documenti_contabilita_data_emissione) >= 2019 ";
            }
            //inviata al server centralizzato 8, invio in corso 2, elaborazione in corso 3 (in carico a noi)
            //in attesa risposta dal sdi 5, accettata da sdi 7, in attesa di consegna 9
            $where[] = "((documenti_contabilita_stato_invio_sdi IN (8,2,3) AND $filtro_data_10h)" // Prendo tutte le fatture che non ricevono un aggiornamento di stato da noi da più di 3h
                . ' OR '
                . "(documenti_contabilita_stato_invio_sdi IN (5,7,9, 4,6) AND $filtro_data_2g))"; // Oppure tutte le fatture che non ricevono un aggiornamento di stato da SDI da più di 2g;

            $fatture_non_valide = $this->apilib->search('documenti_contabilita', $where, 10, 0, 'documenti_contabilita_data_emissione', 'ASC');

            $conteggio = $this->db->where(implode(' AND ', $where), null, false)->count_all_results('documenti_contabilita');

            $fatture_numero = array_map(function ($item) {
                if (empty($item['documenti_contabilita_stato_invio_sdi_value'])) {
                    $item['documenti_contabilita_stato_invio_sdi_value'] = 'Non inviato';
                }
                $item['documenti_contabilita_data_emissione'] = dateFormat($item['documenti_contabilita_data_emissione']);
                return "<li><a target=\"_blank\" href=\"" . base_url("main/layout/contabilita_dettaglio_documento/{$item['documenti_contabilita_id']}") . "\">{$item['documenti_contabilita_tipo_value']}: {$item['documenti_contabilita_numero']}/{$item['documenti_contabilita_serie']}</a> ({$item['documenti_contabilita_data_emissione']}): {$item['documenti_contabilita_stato_invio_sdi_value']}</li>";
            }, $fatture_non_valide);

            if ($conteggio) {
                echo "Trovati documenti con stato anomalo";

                $mail_data = [];
                $mail_data['documenti'] = implode(' ', $fatture_numero);
                $mail_data['link'] = "<a href='" . base_url("main/layout/elenco_documenti") . "'>Clicca qui</a>";

                $check = $this->mail_model->send($azienda['documenti_contabilita_settings_smtp_mail_from'], 'notifica_documenti_fermi_sdi', 'it', $mail_data);
                echo "Inviata mail di notifica";

                if (!$check) {
                    log_message('error', "Invio mail status SDI fallito: {$check}");
                }
            } else {
                echo "Non ci sono documenti non elaborati.";
            }
        }
    }
    /*
     * Elaborazione di file zip ricevuto dal sistema di interscambio
     *
     * Uno zip può contenere una fattura di spesa e quindi viene registrata
     *
     * Oppure può contenere una notifica di scarto/esito di consegna che aggiornerà quindi la fattura in oggetto con l'eventuale errore.
     *
     */
    public function elaborazione_documenti_da_sdi($file_id = null)
    {
        $general_settings = $this->apilib->searchFirst('documenti_contabilita_general_settings');
        $drop_existing = $this->input->get('drop');
        // Recupero i file zip da elaborare
        if ($file_id) {
            $files = $this->apilib->search('documenti_contabilita_ricezione_sdi', [
                'documenti_contabilita_ricezione_sdi_stato_elaborazione' => '1',
                'documenti_contabilita_ricezione_sdi_id' => $file_id,
            ]);
        } else {
            $files = $this->apilib->search('documenti_contabilita_ricezione_sdi', [
                "documenti_contabilita_ricezione_sdi_stato_elaborazione = '1'",
                "documenti_contabilita_ricezione_sdi_creation_date >= '2019-08-04 00:00:00'",
            ], 50, 0, 'documenti_contabilita_ricezione_sdi_nome_file_zip', 'ASC');
        }

        if (count($files) < 1) {
            echo "Nothing to do";
            exit();
        }
        echo_log('debug', 'Elaboro ' . count($files) . ' documenti');
        //Ciclo le righe su db ed elaboro file per file
        echo "<pre>";
        $i = 1;
        $count = count($files);
        foreach ($files as $file) {
            progress($i++, $count);
            $spesa = [];
            $fattura = [];

            $filename = $file['documenti_contabilita_ricezione_sdi_nome_file'];

            echo_log('debug', 'Elaboro il documento: ' . $filename);

            // Se uploaded ce file fisisco altrimenti il contenuto e in base 64
            if ($file['documenti_contabilita_ricezione_sdi_source'] == 2) {
                $b64 = false;
                if (file_exists($file['documenti_contabilita_ricezione_sdi_file_verificato'])) {
                    $file_content = file_get_contents($file['documenti_contabilita_ricezione_sdi_file_verificato']);
                } else {
                    log_message('error', "File not found:" . $file['documenti_contabilita_ricezione_sdi_file_verificato']);
                    // Errore
                    $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                        'documenti_contabilita_ricezione_sdi_stato_elaborazione' => 3,
                        'documenti_contabilita_ricezione_sdi_log_errori' => "File non trovato.",
                    ]);
                    continue;
                }
            } else {
                $b64 = true;
                $file_content = base64_decode($file['documenti_contabilita_ricezione_sdi_file_verificato']);
            }

            //Verifico se il file è un p7m (se si tento di pulirlo con la fiunzione ad hoc
            if (strpos($filename, '.p7m') !== false) {
                $content = $this->verifyP7MData($file_content);
            } else {
                $content = $file_content;
            }

            try {
                $xml = @new SimpleXMLElement($content);
                //debug($xml);
            } catch (Exception $e) {
                $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                    'documenti_contabilita_ricezione_sdi_stato_elaborazione' => 3,
                    'documenti_contabilita_ricezione_sdi_log_errori' => "Contenuto file xml non valido.",
                ]);
                print "<br /><br />*************** IMPOSSIBILE CONVERTIRE IL CONTENUTO IN XML VALIDO  ************************** <br /><br />";
                echo $e->getMessage();
                print "<br />";
                foreach (libxml_get_errors() as $error) {
                    echo "\t", $error->message;
                }
                print "<br /><br />*************** CONTINUO...  ************************** <br /><br />";
                continue;
            }

            $physicalDir = FCPATH . "uploads/modules_files/contabilita/";
            if (!is_dir($physicalDir)) {
                mkdir($physicalDir, 0755, true);
            }

            // Download e storicizzazione del file XSL per elaborare il file xml. In questo momento usiamo solo l'xsl della fattura ordinaria
            $xsl_url_ordinaria = $general_settings['documenti_contabilita_general_settings_xsl_fattura_ordinaria'];
            $xsl_url = $xsl_url_ordinaria;
            $tmpXsl = $physicalDir . basename($xsl_url_ordinaria);

            // Se non ce foglio xsl salvarlo.. Attenzione che se la SOGEI lo aggiorna bisogna modificarlo qui.
            if (!file_exists($tmpXsl)) {
                log_message('debug', "XSL Foglio di stile fattura elettronica non trovato, lo scarico e lo salvo.");
                file_put_contents($tmpXsl, file_get_contents($xsl_url));
            } else {
                log_message('debug', "XSL Foglio di stile fattura elettronica trovato correttamente: " . $tmpXsl);
            }


            // --------- Verifico se è una fattura di uscita (import), un esito oppure una spesa (import)

            if (isset($xml->IdentificativoSdI)) { // ESITO DA SDI
                echo "Il documento " . $file['documenti_contabilita_ricezione_sdi_nome_file_zip'] . " è un esito SDI <br />";
                $this->elabora_esito($xml, $file, $filename);
                continue;
            }

            // Vedo se riesco a prendere la partita iva del cedente
            $fattura_in_uscita = 0;
            if (isset($xml->FatturaElettronicaHeader)) {
                $cedente_pi = (string) $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->IdFiscaleIVA->IdCodice;
                $settings_result = $this->db->query("SELECT * FROM documenti_contabilita_settings WHERE documenti_contabilita_settings_company_vat_number = '$cedente_pi'");
                if ($settings_result->num_rows() > 0) {

                    //Non basta per le fatture infra-gruppo (vedi sa.ve.). In questo caso devo assicurarmi che il destinatario non sia una delle altre aziende che usano lo stesso gestionale
                    //Se il destinatario è tra le aziende allora questa è contemporaneamente una fattura e una spesa! Importo la spesa...
                    $cessionario_pi = (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->IdFiscaleIVA->IdCodice;

                    $settings_result_cessionario = $this->db->query("SELECT * FROM documenti_contabilita_settings WHERE documenti_contabilita_settings_company_vat_number = '$cessionario_pi'");
                    if ($settings_result_cessionario->num_rows() == 0) {
                        $fattura_in_uscita = 1;
                        $settings = $settings_result->row_array();
                    } else {
                        $fattura_in_uscita = 0;
                    }
                }
            }
            // ----------- FATTURA IN USCITA O SPESA? Nel primo caso è fattura in uscita! -----------------------
            if ($fattura_in_uscita == 1) {
                echo "Il documento è una <strong>fattura</strong><br />";

                if (isset($xml->FatturaElettronicaHeader)) {
                    $fattura['numero_documento'] = (string) $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Numero;
                    $fattura['spese_totale'] = (string) $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->ImportoTotaleDocumento;
                    $fattura['data_documento'] = (string) $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Data;
                    //$fattura['spese_totale'] = str_replace(' ', '', $fattura['spese_totale']);
                    $fattura['spese_totale'] = preg_replace("/[^0-9.]/", "", $fattura['spese_totale']);
                    $fattura['spese_totale'] = number_format((float) $fattura['spese_totale'], 9, '.', '');
                    //se ho una serie
                    if (str_contains($fattura['numero_documento'], '/')) {
                        $numero = preg_split("#/#", $fattura['numero_documento']);
                        $fattura['numero_documento'] = $numero[0];
                        //$fattura['serie_documento'] = $numero[1];
                        $fattura['serie_documento'] = str_replace(' ', '', $numero[1]);
                    } else {
                        //$fattura['numero_documento'] = $fattura['numero_documento'];
                        $fattura['serie_documento'] = "";
                    }
                    if ($xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->ScontoMaggiorazione) {
                        $fattura['spese_totale'] = (string) $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->ScontoMaggiorazione->Importo;
                    }
                    // Tipologia di documento
                    $tipologia = (string) $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->TipoDocumento;

                    $tipologie = $this->db->query("SELECT * FROM documenti_contabilita_tipologie_fatturazione WHERE documenti_contabilita_tipologie_fatturazione_codice = '$tipologia'");
                    if ($tipologie->num_rows() > 0) {
                        $vendita['documenti_contabilita_tipologia_fatturazione'] = $tipologie->row()->documenti_contabilita_tipologie_fatturazione_id;
                        $vendita['tipo_documento'] = $tipologie->row()->documenti_contabilita_tipologie_fatturazione_tipo_genitore;
                    }

                    $fattura_esistente = $this->apilib->searchFirst('documenti_contabilita', [
                        'documenti_contabilita_numero' => $fattura['numero_documento'],
                        'documenti_contabilita_serie' => $fattura['serie_documento'],
                        //'documenti_contabilita_data_emissione' => $fattura['data_documento'],
                        //'documenti_contabilita_totale' => $fattura['spese_totale'],
                    ]);

                    $exists = null;
                    $exists = $this->db->query("SELECT documenti_contabilita_id FROM documenti_contabilita WHERE DATE(documenti_contabilita_data_emissione) = DATE('{$fattura['data_documento']}') AND documenti_contabilita_serie = '" . $fattura['serie_documento'] . "' AND documenti_contabilita_numero = '" . $fattura['numero_documento'] . "' AND documenti_contabilita_tipo = '" . $vendita['tipo_documento'] . "'");



                    if ($drop_existing && $exists->num_rows()) {
                        echo_log('debug', 'Documento trovato, cancello.');
                        //COMMENTATO! PERICOLOSO: exists non ha filtro anno quindi cancella documenti non importati ma creati realmente (es.: importo 2022 mi cancella documenti con stesso numero 2023!!!)
                        //$this->apilib->delete('documenti_contabilita', $exists->row_array()['documenti_contabilita_id']);
                    }
                    if (!$drop_existing && $exists->num_rows()) {
                        echo "documento già esistente";
                        $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                            'documenti_contabilita_ricezione_sdi_stato_elaborazione' => 3,
                            'documenti_contabilita_ricezione_sdi_log_errori' => "Il documento risulta già presente a sistema",
                        ]);
                        continue;
                    } else {
                        //documento non trovato, inserisco i dati
                        // ANAGRAFICA
                        $mittente['ragione_sociale'] = ($xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->Anagrafica->Denominazione) ? (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->Anagrafica->Denominazione : (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->Anagrafica->Nome . " " . (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->Anagrafica->Cognome;
                        $mittente['nome'] = ($xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->Anagrafica->Nome) ? (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->Anagrafica->Nome : '';
                        $mittente['cognome'] = ($xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->Anagrafica->Cognome) ? (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->Anagrafica->Cognome : '';

                        $mittente['indirizzo'] = (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->Sede->Indirizzo;
                        $mittente['citta'] = (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->Sede->Comune;
                        $mittente['cap'] = (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->Sede->CAP;

                        $mittente['partita_iva'] = (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->IdFiscaleIVA->IdCodice;
                        $mittente['codice_fiscale'] = ($xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->CodiceFiscale) ? (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->CodiceFiscale : '';
                        $mittente['provincia'] = (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->Sede->Provincia;
                        $mittente['country'] = (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->Sede->Nazione;
                        $mittente['sdi'] = (string) $xml->FatturaElettronicaHeader->DatiTrasmissione->CodiceDestinatario;

                        $nazione_iso = $mittente['country'];
                        $nazioni = $this->db->query("SELECT * FROM countries WHERE countries_iso = '$nazione_iso'");
                        if ($nazioni->num_rows() > 0) {
                            $mittente['customers_country_id'] = $nazioni->row()->countries_id;
                            $mittente['nazione'] = $nazioni->row()->countries_iso;
                        }

                        //fare debug qua.
                        $vendita['documenti_contabilita_destinatario'] = json_encode($mittente);
                        // DATI DOCUMENTO
                        $vendita['documenti_contabilita_azienda'] = $settings['documenti_contabilita_settings_id'];
                        $vendita['documenti_contabilita_numero'] = $fattura['numero_documento'];
                        $vendita['documenti_contabilita_serie'] = $fattura['serie_documento'];
                        $vendita['documenti_contabilita_data_emissione'] = $fattura['data_documento'];
                        $vendita['documenti_contabilita_sconto_su_imponibile'] = DB_BOOL_FALSE;
                        $vendita['documenti_contabilita_importata_da_xml'] = DB_BOOL_TRUE;

                        // IMPORTI
                        //$spesa['spese_imponibile'] = $xml->FatturaElettronicaBody->DatiBeniServizi->DatiRiepilogo->ImponibileImporto;
                        $vendita['documenti_contabilita_valuta'] = (string) $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Divisa;
                        $vendita['documenti_contabilita_totale'] = (string) $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->ImportoTotaleDocumento;

                        if ($xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->ScontoMaggiorazione) {
                            $vendita['sconto_percentuale'] = (string) $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->ScontoMaggiorazione->Percentuale;
                            $vendita['importo_scontato'] = (string) $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->ScontoMaggiorazione->Importo;
                        }

                        // Sommo le imposte
                        $totale_imponibile = 0;
                        $totale_imposta = 0;
                        foreach ($xml->FatturaElettronicaBody->DatiBeniServizi->DatiRiepilogo as $riepilogo) {
                            $totale_imponibile = $totale_imponibile + $riepilogo->ImponibileImporto;
                            $totale_imposta = $totale_imposta + $riepilogo->Imposta;
                        }
                        $vendita['documenti_contabilita_iva'] = $totale_imposta;
                        $vendita['documenti_contabilita_imponibile'] = $totale_imponibile;
                        $vendita['documenti_contabilita_extra_param'] = ''; //$filename;
                        $vendita['documenti_contabilita_nome_file_xml'] = $filename;

                        $mappature = $this->docs->getMappature();
                        extract($mappature);
                        $customer = [
                            $clienti_ragione_sociale => $mittente['ragione_sociale'],
                        ];
                        $customer[$clienti_codice_sdi] = $mittente['sdi'];
                        $customer[$clienti_indirizzo] = $mittente['indirizzo'];
                        $customer[$clienti_citta] = $mittente['citta'];
                        $customer[$clienti_provincia] = $mittente['provincia'];

                        $customer[$clienti_cap] = $mittente['cap'];
                        // $customer[$clienti_nazione] = $mittente['customers_country_id'];
                        $customer['customers_country_id'] = $mittente['customers_country_id'];

                        $customer[$clienti_partita_iva] = $mittente['partita_iva'];
                        $customer[$clienti_codice_fiscale] = $mittente['codice_fiscale'];
                        $customer['customers_type'] = 1; //Cliente

                        //Alcuni mittenti non hanno partita iva ma solo codice fiscale
                        if ($mittente['partita_iva']) {
                            $fornitore = $this->apilib->searchFirst($entita_clienti, ['customers_type' => 1, $clienti_partita_iva => $mittente['partita_iva']]);
                            $customer['customers_group'] = 2;

                            // //vedo se esiste un'anagrafica con questa partita iva, aggiorno anche quella
                            // $customer_exists = $this->apilib->searchFirst('customers', [$clienti_partita_iva => $mittente['partita_iva'], 'customers_type' => 2]);
                            // if ($customer_exists) {
                            //     $customer_fornitore = $customer;
                            //     $customer_fornitore['customers_type'] = 2;
                            //     $this->apilib->edit('customers', $customer_exists['customers_id'], $customer_fornitore);
                            // }

                        } elseif ($mittente['codice_fiscale']) {
                            $fornitore = $this->apilib->searchFirst($entita_clienti, ['customers_type' => 1, $clienti_codice_fiscale => $mittente['codice_fiscale']]);
                            $customer['customers_group'] = 1;

                            // //vedo se esiste un'anagrafica con questa partita iva, aggiorno anche quella
                            // $customer_exists = $this->apilib->searchFirst('customers', [$clienti_codice_fiscale => $mittente['codice_fiscale'], 'customers_type' => 2]);
                            // if ($customer_exists) {
                            //     $customer_fornitore = $customer;
                            //     $customer_fornitore['customers_type'] = 2;
                            //     $this->apilib->edit('customers', $customer_exists['customers_id'], $customer_fornitore);
                            // }
                        } else {
                            die('Mittente privo di partita_iva e/o codice_fiscale');
                        }
                        
                        if (empty($fornitore)) {

                            //Lo creo
                            $supplier_id = $this->apilib->create($entita_clienti, $customer, false);
                        } else {
                            //Lo modifico
                            $supplier_id = $fornitore['customers_id'];
                            /*echo $entita_clienti;
                            echo $fornitore['customers_id'];
                            var_dump($customer);*/

                            try {
                                $this->apilib->edit($entita_clienti, $fornitore['customers_id'], $customer);
                            } catch (Exception $e) {
                                $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                                    'documenti_contabilita_ricezione_sdi_stato_elaborazione' => 3,
                                    'documenti_contabilita_ricezione_sdi_log_errori' => "Errore nella creazione del cliente",
                                ]);
                                continue;
                                log_message('error', "Errore modificando l'anagrafica del cliente " . $e->getMessage());
                            }

                            /* Non trovo categoria
                            if (!empty($fornitore['customers_expense_category'])) {
                            $vendita['spese_categoria'] = $fornitore['customers_expense_category'];
                            }*/
                        }




                        //Questa riga addirittura non servirebbe perchè dopo gli passiamo già supplier_id nel campo cliente_id
                        $vendita['documenti_contabilita_customer_id'] = $supplier_id; //In realtà la variabile dovrebbe chiamarsi "customer_id"... ci siamo portati dietro la logica dalle spese, ma qui siamo in fatture!

                        $vendita['documenti_contabilita_json'] = json_encode($xml);
                        //$vendita_id = $this->apilib->create('documenti_contabilita', $vendita, false);
                        //Lego questo file dello sdi alla spesa creata
                        /*
                        $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                        'documenti_contabilita_ricezione_sdi_rif_spesa' => $vendita_id,
                        ]);*/

                        $count = 0;
                        $articoli_det = [];
                        // Tento di inserire gli articoli
                        foreach ($xml->FatturaElettronicaBody->DatiBeniServizi->DettaglioLinee as $linea) {
                            $articolo_nome = (strlen($linea->Descrizione) > 200) ? substr($linea->Descrizione, 0, 200) : $linea->Descrizione;
                            $articolo = array(
                                'documenti_contabilita_articoli_codice' => (string) $linea->CodiceArticolo->CodiceValore,
                                'documenti_contabilita_articoli_name' => (string) $articolo_nome,
                                'documenti_contabilita_articoli_prezzo' => (string) $linea->PrezzoUnitario,
                                'documenti_contabilita_articoli_quantita' => (string) $linea->Quantita,
                                'documenti_contabilita_articoli_iva_perc' => (string) $linea->AliquotaIVA,
                                'documenti_contabilita_articoli_importo_totale' => (string) $linea->PrezzoTotale,
                                'documenti_contabilita_articoli_iva_id' => (string) $linea->Natura,
                                'documenti_contabilita_articoli_sconto' => (!empty($linea->ScontoMaggiorazione->Percentuale) ? ((string) $linea->ScontoMaggiorazione->Percentuale) : 0),
                                //'documenti_contabilita_articoli_documento' => $vendita_id, //dovrebbe essere il riferimento all'id del documento
                                'documenti_contabilita_articoli_extra_data' => json_encode($linea),
                                'documenti_contabilita_articoli_applica_sconto' => DB_BOOL_TRUE,
                                'documenti_contabilita_articoli_applica_ritenute' => DB_BOOL_TRUE,
                            );
                            $articoli_det[$count] = $articolo;
                            $count++;
                        }

                        $vendita['tipo_destinatario'] = 1;
                        $vendita['fornitore_id'] = '';
                        //Anche qui il fatto di aver creato la variabile supplier_id ha poi ovviamente generato questo errore! Era stata messa su fornitore e non su cliente...
                        $vendita['cliente_id'] = $supplier_id;
                        $vendita['articoli_data'] = $articoli_det;
                        $vendita['formato_elettronico'] = DB_BOOL_TRUE;
                        //$vendita['stato_invio_sdi'] = 11;

                        $partita_iva_azienda = $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->CodiceFiscale;
                        $azienda = $this->db->get_where('documenti_contabilita_settings', ['documenti_contabilita_settings_company_vat_number' => $partita_iva_azienda])->row_array();

                        $vendita['azienda'] = $azienda['documenti_contabilita_settings_id'] ?? null;

                        // Sposto il file dalla temp di importazione a quella definitiva delle fatture emesse
                        $nome_xml = basename($file['documenti_contabilita_ricezione_sdi_file_verificato']);

                        // Creo la cartella qualora fosse la prima importazione e non esiste ancora
                        if (!is_dir(FCPATH . "uploads/modules_files/contabilita/xml_importati")) {
                            mkdir(FCPATH . "uploads/modules_files/contabilita/xml_importati", 0755, true);
                        }

                        $newfile_xml = FCPATH . "uploads/modules_files/contabilita/xml_importati/" . $nome_xml;
                        if (file_exists($newfile_xml)) {
                            //debug("File $newfile_xml esistente, cancello!",true);
                            unlink($newfile_xml);
                        }
                        file_put_contents($newfile_xml, $file_content);

                        $vendita['documenti_contabilita_file_xml'] = "modules_files/contabilita/xml_importati/" . $nome_xml;

                        // Verifico se nell'xml che sto importando c'è un file base64 come allegato che al 99% è la copia cortesia della fattura stesas e quindi lo importo come pdf di cortesia
                        if (!empty($xml->FatturaElettronicaBody->Allegati)) {
                            foreach ($xml->FatturaElettronicaBody->Allegati as $allegato) {
                                // Cerco il primo PDF

                                if ($allegato->FormatoAttachment == "PDF") {
                                    $nome_file_pdf = (string) $allegato->NomeAttachment;
                                    $content_pdf = base64_decode($allegato->Attachment);
                                    if (!empty($content)) {
                                        $folder = "modules_files/contabilita/pdf_cortesia_importati";

                                        $filepath = $this->docs->salva_file_fisico($nome_file_pdf, $folder, $content_pdf);
                                        $vendita['documenti_contabilita_file_pdf'] = $filepath;
                                        break; // Mi fermo al primo eventuale allegato in quanto non gestiamo per ora allegati multipli
                                    }
                                }
                            }
                        }

                        $vendita_id = $this->docs->doc_express_save_import($vendita);

                        // Aggiorno lo stato del file zip ricevuto come elaborato
                        $this->apilib->edit("documenti_contabilita_ricezione_sdi", $file['documenti_contabilita_ricezione_sdi_id'], ['documenti_contabilita_ricezione_sdi_stato_elaborazione' => 2, 'documenti_contabilita_ricezione_sdi_documento' => $vendita_id]);

                        echo "File elaborato, passo al successivo. <br />";
                        echo "<br />";
                    }
                } else {
                    // Errore
                    $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                        'documenti_contabilita_ricezione_sdi_stato_elaborazione' => 3,
                        'documenti_contabilita_ricezione_sdi_log_errori' => "Il documento sembra essere una fattura, ma c'è il nodo FatturaElettronicaHeader che non dovrebbe esistere negli esiti!!!",
                    ]);
                    throw new Exception("Il documento sembra essere una fattura, ma c'è il nodo FatturaElettronicaHeader che non dovrebbe esistere negli esiti!!!");
                    continue;
                }
            } else { //E' una spesa da registrare
                echo "Il documento è una <strong>spesa</strong><br />";



                if (isset($xml->FatturaElettronicaHeader)) {
                    $tmpXmlFile = "{$physicalDir}/spese/{$filename}";

                    if (!is_dir($physicalDir . "/spese")) {
                        mkdir($physicalDir . "/spese", 0755, true);
                    }

                    // Derivo l'azienda al quale attribuire questa spesa dalla piva del committente

                    if (isset($xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->IdFiscaleIVA->IdCodice)) {
                        $cessionario_pi = (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->IdFiscaleIVA->IdCodice;
                        $settings_result = $this->db->query("SELECT * FROM documenti_contabilita_settings WHERE documenti_contabilita_settings_company_vat_number = '$cessionario_pi'");
                        if ($settings_result->num_rows() > 0) {
                            $settings = $settings_result->row_array();
                        } else {
                            echo "Rilevazione errata spesa azienda";
                            log_message("error", "Rilevazione errata spesa azienda");
                        }
                    } elseif (isset($xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->CodiceFiscale)) {
                        $cessionario_pi = (string) $xml->FatturaElettronicaHeader->CessionarioCommittente->DatiAnagrafici->CodiceFiscale;
                        $settings_result = $this->db->query("SELECT * FROM documenti_contabilita_settings WHERE documenti_contabilita_settings_company_vat_number = '$cessionario_pi'");
                        if ($settings_result->num_rows() > 0) {
                            $settings = $settings_result->row_array();
                        } else {
                            echo "Rilevazione errata spesa azienda";
                            log_message("error", "Rilevazione errata spesa azienda");
                        }
                    }
                    // ANAGRAFICA
                    $mittente['ragione_sociale'] = ($xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Denominazione) ? (string) $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Denominazione : (string) $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Nome . " " . (string) $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Cognome;
                    $mittente['nome'] = ($xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Nome) ? (string) $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Nome : '';
                    $mittente['cognome'] = ($xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Cognome) ? (string) $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Cognome : '';
                    /*if (empty($mittente['ragione_sociale'])) {
                    $mittente['ragione_sociale'] = (string) $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Nome && (string) $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->Anagrafica->Cognome;
                    }*/
                    $mittente['indirizzo'] = (string) $xml->FatturaElettronicaHeader->CedentePrestatore->Sede->Indirizzo;
                    $mittente['citta'] = (string) $xml->FatturaElettronicaHeader->CedentePrestatore->Sede->Comune;
                    $mittente['cap'] = (string) $xml->FatturaElettronicaHeader->CedentePrestatore->Sede->CAP;
                    $mittente['partita_iva'] = (string) $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->IdFiscaleIVA->IdCodice;
                    $mittente['codice_fiscale'] = ($xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->CodiceFiscale) ? (string) $xml->FatturaElettronicaHeader->CedentePrestatore->DatiAnagrafici->CodiceFiscale : '';
                    $mittente['provincia'] = (string) $xml->FatturaElettronicaHeader->CedentePrestatore->Sede->Provincia;
                    $mittente['nazione'] = (string) $xml->FatturaElettronicaHeader->CedentePrestatore->Sede->Nazione;

                    $spesa['spese_fornitore'] = json_encode($mittente);

                    // DATI DOCUMENTO
                    $spesa['spese_numero'] = $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Numero;
                    $spesa['spese_data_emissione'] = $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Data;

                    // IMPORTI
                    //$spesa['spese_imponibile'] = $xml->FatturaElettronicaBody->DatiBeniServizi->DatiRiepilogo->ImponibileImporto;
                    $spesa['spese_valuta'] = $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Divisa;
                    $spesa['spese_totale'] = $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->ImportoTotaleDocumento;

                    // Tipologia di documento
                    $tipologia = (string) $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->TipoDocumento;
                    $tipologie = $this->db->query("SELECT * FROM documenti_contabilita_tipologie_fatturazione WHERE documenti_contabilita_tipologie_fatturazione_codice = '$tipologia'");
                    if ($tipologie->num_rows() > 0) {
                        $spesa['spese_tipologia_fatturazione'] = $tipologie->row()->documenti_contabilita_tipologie_fatturazione_id;
                    }
                    //controllo che il file non esista già

                    $spesa_totale = preg_replace("/[^0-9.]/", "", $spesa['spese_totale']);
                    $spesa_totale = number_format((float) $spesa_totale, 9, '.', '');
                    $fattura_esistente = $this->apilib->searchFirst('spese', [
                        'spese_numero' => (string) $spesa['spese_numero'],
                        //'spese_data_emissione' => $spesa['spese_data_emissione'],
                        'spese_tipologia_fatturazione' => $spesa['spese_tipologia_fatturazione'],
                        //'documenti_contabilita_data_emissione' => $fattura['data_documento'],
                        'spese_totale' => $spesa_totale,
                    ]);
                    if (!empty($fattura_esistente) and $spesa['spese_data_emissione'] == date("Y-m-d", strtotime($fattura_esistente['spese_data_emissione']))) {
                        //documento già presente, metto in errore
                        echo "fattura già presente, proseguo al prossimo cron!";
                        log_message('debug', "fattura già presente, proseguo al prossimo cron!");
                        $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                            'documenti_contabilita_ricezione_sdi_stato_elaborazione' => 3,
                            'documenti_contabilita_ricezione_sdi_log_errori' => "Il documento risulta già presente a sistema",
                        ]);
                        continue;
                    }

                    // Sommo le imposte
                    $totale_imponibile = 0;
                    $totale_imposta = 0;
                    foreach ($xml->FatturaElettronicaBody->DatiBeniServizi->DatiRiepilogo as $riepilogo) {
                        $totale_imponibile = $totale_imponibile + $riepilogo->ImponibileImporto;
                        $totale_imposta = $totale_imposta + $riepilogo->Imposta;
                    }
                    $spesa['spese_iva'] = $totale_imposta;
                    $spesa['spese_imponibile'] = $totale_imponibile;

                    $spesa['spese_extra'] = ''; //$filename;
                    $spesa['spese_file_da_sdi'] = $file['documenti_contabilita_ricezione_sdi_id'];

                    $mappature = $this->docs->getMappature();
                    extract($mappature);
                    $customer = [
                        $clienti_ragione_sociale => $mittente['ragione_sociale'],
                    ];

                    $customer[$clienti_indirizzo] = $mittente['indirizzo'];
                    $customer[$clienti_citta] = $mittente['citta'];
                    $customer[$clienti_provincia] = $mittente['provincia'];

                    $customer[$clienti_cap] = $mittente['cap'];

                    $customer[$clienti_partita_iva] = $mittente['partita_iva'];
                    $customer[$clienti_codice_fiscale] = $mittente['codice_fiscale'];
                    $customer['customers_type'] = 2; //Fornitore
                    $customer['customers_group'] = ($mittente['partita_iva'] ? 2 : 1);

                    $nazione_iso = $mittente['nazione'];
                    $nazioni = $this->db->query("SELECT * FROM countries WHERE countries_iso = '$nazione_iso'");
                    if ($nazioni->num_rows() > 0) {
                        $customer['customers_country_id'] = $nazioni->row()->countries_id;
                    }

                    $fornitore = $this->apilib->searchFirst($entita_clienti, [
                        "
                        customers_type = 2 AND (
                                $clienti_partita_iva = '{$mittente['partita_iva']}' 
                                OR ($clienti_codice_fiscale = '{$customer[$clienti_codice_fiscale]}' AND '{$customer[$clienti_codice_fiscale]}' <> '')
                        ) 
                        
                        "
                    ]);

                    //TODO: potrebbe aver senso sbloccare questo codice che andrebbe a correggere le angrafiche dei clienti con la stessa partita iva
                    //vedo se esiste un'anagrafica con questa partita iva, aggiorno anche quella (quella del cliente)
                    // $customer_exists = $this->apilib->searchFirst('customers', [$clienti_partita_iva => $mittente['partita_iva'], 'customers_type' => 1]);
                    // if ($customer_exists) {
                    //     $customer_cliente = $customer;
                    //     $customer_cliente['customers_type'] = 1;
                    //     $this->apilib->edit('customers', $customer_exists['customers_id'], $customer_cliente);
                    // }



                    if (empty($fornitore)) {
                        //Lo creo
                        try {
                            $supplier_id = $this->apilib->create($entita_clienti, $customer, false);
                        } catch (Exception $exp) {
                            echo_log('error', 'Salvataggio anagrafica fallito: ' . $exp);
                            continue;
                        }

                    } else {
                        //Lo modifico
                        $supplier_id = $fornitore['customers_id'];

                        // Se c'è un errore in edit non posso procedere con questo documento
                        try {
                            $this->apilib->edit($entita_clienti, $fornitore['customers_id'], $customer);
                        } catch (Exception $e) {
                            log_message('error', "Errore update fornitore durante import spese: " . $e->getMessage() . "\n");
                            $this->apilib->edit("documenti_contabilita_ricezione_sdi", $file['documenti_contabilita_ricezione_sdi_id'], ['documenti_contabilita_ricezione_sdi_stato_elaborazione' => 3, 'documenti_contabilita_ricezione_sdi_log_errori' => 'Errore nel salvataggio anagrafica fornitore. Probabile check anagrafiche duplicate']);
                        }

                        if (!empty($fornitore['customers_expense_category'])) {
                            $spesa['spese_categoria'] = $fornitore['customers_expense_category'];
                        }
                    }

                    $spesa['spese_customer_id'] = $supplier_id;
                    $spesa['spese_importata_da_xml'] = DB_BOOL_TRUE;
                    $spesa['spese_azienda'] = $settings['documenti_contabilita_settings_id'];
                    $spesa['spese_json'] = json_encode($xml);

                    $spesa_id = $this->apilib->create('spese', $spesa, false);

                    //Lego questo file dello sdi alla spesa creata
                    $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                        'documenti_contabilita_ricezione_sdi_rif_spesa' => $spesa_id,
                    ]);

                    if ($spesa_id) {
                        // Tento di inserire gli articoli
                        foreach ($xml->FatturaElettronicaBody->DatiBeniServizi->DettaglioLinee as $linea) {
                            $articolo_nome = (strlen($linea->Descrizione) > 200) ? substr($linea->Descrizione, 0, 200) : $linea->Descrizione;
                            $articolo = array(
                                'spese_articoli_codice' => $linea->CodiceArticolo->CodiceValore,
                                'spese_articoli_name' => $articolo_nome,
                                'spese_articoli_prezzo' => $linea->PrezzoUnitario,
                                'spese_articoli_quantita' => $linea->Quantita,
                                'spese_articoli_iva_perc' => $linea->AliquotaIVA,
                                'spese_articoli_importo_totale' => $linea->PrezzoTotale,
                                'spese_articoli_spesa' => $spesa_id,
                                'spese_articoli_extra_data' => json_encode($linea),
                            );
                            // Check sconti e maggiorazioni
                            if (!empty($linea->ScontoMaggiorazione)) {
                                $sconti_maggiorazioni = array();
                                foreach ($linea->ScontoMaggiorazione as $sconto_magg) {
                                    $sconti_maggiorazioni[] = array("tipo" => (string) $sconto_magg->Tipo, "percentuale" => (string) $sconto_magg->Percentuale);
                                }
                                $articolo['spese_articoli_sconti_json'] = json_encode($sconti_maggiorazioni);
                            }

                            // Check codici multipli
                            if (!empty($linea->CodiceArticolo)) {
                                $codici_articolo = array();
                                foreach ($linea->CodiceArticolo as $codice_articolo) {
                                    $codici_articolo[] = array("tipo" => (string) $codice_articolo->CodiceTipo, "valore" => (string) $codice_articolo->CodiceValore);
                                }
                                $articolo['spese_articoli_codici_json'] = json_encode($codici_articolo);
                            }

                            $this->apilib->create('spese_articoli', $articolo);
                        }

                        // Inserisco l'allegato così da poterlo scaricare leggibile
                        file_put_contents($tmpXmlFile, $content);
                        $allegato = array('spese_allegati_file' => $filename, 'spese_allegati_spesa' => $spesa_id);
                        $this->apilib->create('spese_allegati', $allegato);

                        // Inserisco l'xml parsato dal nostro convertitore per avere qualcosa di leggibile

                        $tmpHtml = "{$physicalDir}/spese/{$filename}.html";

                        exec("xsltproc -o {$tmpHtml} {$tmpXsl} {$tmpXmlFile}");
                        if (file_exists($tmpHtml)) {
                            $allegato = array('spese_allegati_file' => $filename . '.html', 'spese_allegati_spesa' => $spesa_id);
                            $this->apilib->create('spese_allegati', $allegato);
                        }

                        $_metodi_pagamento = $this->apilib->search('documenti_contabilita_metodi_pagamento');
                        $metodi_pagamento = array_key_value_map($_metodi_pagamento, 'documenti_contabilita_metodi_pagamento_codice', 'documenti_contabilita_metodi_pagamento_id');

                        // Inserisco le scadenze di pagamento
                        if (!empty($xml->FatturaElettronicaBody->DatiPagamento)) {
                            foreach ($xml->FatturaElettronicaBody->DatiPagamento->DettaglioPagamento as $scadenza) {
                                $spesa_scadenza = [
                                    'spese_scadenze_ammontare' => $scadenza->ImportoPagamento,
                                    'spese_scadenze_scadenza' => ($scadenza->DataScadenzaPagamento) ? $scadenza->DataScadenzaPagamento : $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Data,
                                    'spese_scadenze_saldato_con' => ($scadenza->ModalitaPagamento) ? $metodi_pagamento[(string) $scadenza->ModalitaPagamento] : null,
                                    'spese_scadenze_spesa' => $spesa_id,
                                ];

                                // Se metodo di pagamento contanti o carta imposto data di pagamento come quella del documento oppure come data scadenza pagamento (se impostata)
                                // Forte dubbio su questo array ma non vedo altre soluzioni
                                $metodi_auto_saldo = array("MP01", "MP02", "MP03", "MP04", "MP08", "MP22");
                                if (in_array((string) $scadenza->ModalitaPagamento, $metodi_auto_saldo)) {
                                    $spesa_scadenza['spese_scadenze_data_saldo'] = ($scadenza->DataScadenzaPagamento) ? $scadenza->DataScadenzaPagamento : $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Data;
                                    $spese_scadenza['spese_scadenze_saldata'] = DB_BOOL_TRUE;
                                }

                                $this->apilib->create('spese_scadenze', $spesa_scadenza);
                            }
                        } else {
                            // Se non ci sono mi baso su importo del documento e inserisco una sola scadenza

                            $spesa_scadenza = [
                                'spese_scadenze_ammontare' => $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->ImportoTotaleDocumento,
                                'spese_scadenze_scadenza' => $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Data,
                                'spese_scadenze_saldato_con' => $metodi_pagamento["MP08"],
                                // Da capire come gestire
                                'spese_scadenze_spesa' => $spesa_id,
                            ];
                            $spesa_scadenza['spese_scadenze_data_saldo'] = $xml->FatturaElettronicaBody->DatiGenerali->DatiGeneraliDocumento->Data;

                            $this->apilib->create('spese_scadenze', $spesa_scadenza);
                        }
                        // Aggiorno lo stato del file zip ricevuto come elaborato
                        $this->apilib->edit("documenti_contabilita_ricezione_sdi", $file['documenti_contabilita_ricezione_sdi_id'], ['documenti_contabilita_ricezione_sdi_stato_elaborazione' => 2, 'documenti_contabilita_ricezione_sdi_rif_spesa' => $spesa_id]);
                    } else {
                        $this->apilib->edit("documenti_contabilita_ricezione_sdi", $file['documenti_contabilita_ricezione_sdi_id'], ['documenti_contabilita_ricezione_sdi_stato_elaborazione' => 3, 'documenti_contabilita_ricezione_sdi_log_errori' => 'Spesa id non trovata. Errore.']);
                    }

                    echo "File elaborato, passo al successivo. <br />";
                    echo "<br />";
                } else {
                    $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                        'documenti_contabilita_ricezione_sdi_stato_elaborazione' => 3,
                        'documenti_contabilita_ricezione_sdi_log_errori' => "Il documento sembra essere una spesa, ma non è impostato FatturaElettronicaHeader nell'xml!",
                    ]);
                    throw new Exception("Il documento sembra essere una spesa, ma non è impostato FatturaElettronicaHeader nell'xml!!!");
                    exit;
                }
            }
        }
    }

    private function elabora_esito($xml, $file, $filename)
    {
        $tipi_esito_map = [];
        foreach ($this->apilib->search('documenti_contabilita_ricezione_sdi_tipi_esiti') as $tipo_esito) {
            $tipi_esito_map[$tipo_esito['documenti_contabilita_ricezione_sdi_tipi_esiti_valore']] = [$tipo_esito['documenti_contabilita_ricezione_sdi_tipi_esiti_id'], $tipo_esito['documenti_contabilita_ricezione_sdi_tipi_esiti_descrizione']];
        }

        //Come prima cosa individuo se è una spesa o un esito di una fattura emessa
        $exploded_filename = explode('_', $filename);
        $countrypiva = $exploded_filename[0];

        //Mi recupero il giorno gregoriano
        $nome_file_zip = $file['documenti_contabilita_ricezione_sdi_nome_file_zip'];
        $nome_file_zip_exploded = explode('.', $nome_file_zip);
        $giorno_gregoriano = substr($nome_file_zip_exploded[2], 4);
        $anno = substr($nome_file_zip_exploded[2], 0, 4);
        $oreminuti = $nome_file_zip_exploded[3];
        $oreminuti_separati = substr($oreminuti, 0, 2) . ':' . substr($oreminuti, 2) . ':00';
        $primo_gennaio = date("$anno-01-01 $oreminuti_separati");
        $data_gregoriana_riconvertita = date('Y-m-d H:i:s', strtotime($primo_gennaio . "+ $giorno_gregoriano days"));

        //Col giorno gregoriano riconvertito, aggiorno questa riga in modo da sapere sempre il giorno in cui è stato caricato
        $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
            'documenti_contabilita_ricezione_sdi_data_gregoriana' => $data_gregoriana_riconvertita,
        ]);


        //Come prima cosa mi prendo il nome del file xml della fattura in uscita originale, così da avere tutte le info per aggiornare lo stato dopo...
        $nomefilexml = (string) $xml->NomeFile;
        $documento = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_nome_file_xml' => $nomefilexml], 0, 'documenti_contabilita_id', 'DESC');
        if (empty($documento)) {
            $nomefilexml = str_replace(".p7m", "", $nomefilexml);
            $documento = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_nome_file_xml' => $nomefilexml], 0, 'documenti_contabilita_id', 'DESC');
        }
        if (empty($documento)) {
            $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                'documenti_contabilita_ricezione_sdi_stato_elaborazione' => 3,
                //Se arrivo qua vuol dire che l'ho processato, con o senza errori del relativo documento
                'documenti_contabilita_ricezione_sdi_nome_file_emesso' => $nomefilexml,
                'documenti_contabilita_ricezione_sdi_log_errori' => "Documento con nome file '$nomefilexml' non trovato!",
            ]);
            echo "Documento con nome file '$nomefilexml' non trovato!!!";
            throw new Exception("Documento con nome file '$nomefilexml' non trovato!!!");
        }
        $documento_id = $documento['documenti_contabilita_id'];
        //Se ho trovato il documento, la prima cosa che faccio è aggiornare questa riga/file legandolo al documento_id
        $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
            'documenti_contabilita_ricezione_sdi_documento' => $documento_id,
            //Se arrivo qua vuol dire che l'ho processato, con o senza errori del relativo documento
            'documenti_contabilita_ricezione_sdi_nome_file_emesso' => $nomefilexml,
        ]);

        //A questo punto, dato che sono sicuramente in un esito, mi estraggo il codice dell'esito
        $codiceesito = $exploded_filename[2];

        //debug($tipi_esito_map,true);

        //E in base al codice, gestisco il file in maniera diversa (potrebbe contenere nodi diversi o struttura diversa in generale).
        //Anche le azioni da fare cambiano in base al codice quindi va bene lo switch...
        switch ($codiceesito) {

            case 'DT': // Notifica di esito solo per fatture PA potrebbe essere accettata o meno

                $descrizione_aggiuntiva = 'Attestazione di decorrenza termini';
                $this->apilib->edit('documenti_contabilita', $documento['documenti_contabilita_id'], [
                    'documenti_contabilita_stato_invio_sdi' => 15, // Decorrenza termini
                ]);

                $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                    'documenti_contabilita_ricezione_sdi_descrizione_errore' => $descrizione_aggiuntiva,
                    'documenti_contabilita_ricezione_sdi_tipo_esito' => $tipi_esito_map[$codiceesito][0],
                    'documenti_contabilita_ricezione_sdi_data_ora_ricezione' => '',
                ]);
                break;

            case 'AT': // Notifica di esito solo per fatture PA potrebbe essere accettata o meno

                $descrizione_aggiuntiva = 'Attestazione di avvenuta trasmissione con impossibilità di recapito';
                $this->apilib->edit('documenti_contabilita', $documento['documenti_contabilita_id'], [
                    'documenti_contabilita_stato_invio_sdi' => 14, // Impossibilita di recapito
                ]);

                $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                    'documenti_contabilita_ricezione_sdi_descrizione_errore' => $descrizione_aggiuntiva,
                    'documenti_contabilita_ricezione_sdi_tipo_esito' => $tipi_esito_map[$codiceesito][0],
                    'documenti_contabilita_ricezione_sdi_data_ora_ricezione' => '',
                ]);
                break;

            case 'NE': // Notifica di esito solo per fatture PA potrebbe essere accettata o meno

                $descrizione_aggiuntiva = '';

                if (isset($xml->EsitoCommittente)) {
                    if ((string) $xml->EsitoCommittente->Esito == 'EC01') {
                        $descrizione_aggiuntiva = 'Accettata dalla PA';
                        $this->apilib->edit('documenti_contabilita', $documento['documenti_contabilita_id'], [
                            'documenti_contabilita_stato_invio_sdi' => 12, // Accetata dalla PA
                        ]);
                    } else if ((string) $xml->EsitoCommittente->Esito == 'EC02') {
                        $descrizione_aggiuntiva = 'Rifiutata dalla PA';
                        $this->apilib->edit('documenti_contabilita', $documento['documenti_contabilita_id'], [
                            'documenti_contabilita_stato_invio_sdi' => 13, // Rifiutata dalla PA
                        ]);
                    }
                }

                $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                    'documenti_contabilita_ricezione_sdi_descrizione_errore' => $descrizione_aggiuntiva,
                    'documenti_contabilita_ricezione_sdi_tipo_esito' => $tipi_esito_map[$codiceesito][0],
                    'documenti_contabilita_ricezione_sdi_data_ora_ricezione' => '',
                ]);
                break;


            case 'RC': //Ricevuta di consegna
                //Aggiorno sia lo stato (errore), sia l'errore (tenendo lo storico degli altri errori precedentemente segnalati)
                $this->apilib->edit('documenti_contabilita', $documento['documenti_contabilita_id'], [
                    'documenti_contabilita_stato_invio_sdi' => 11, //Consegnata
                    //'documenti_contabilita_stato_invio_sdi_errore_gestito' => date('m/d/Y, h:i:s')." - Esito: $codiceesito - {$tipi_esito_map[$codiceesito][1]} - {$descrizione_aggiuntiva}<br />".$documento['documenti_contabilita_stato_invio_sdi_errore_gestito']
                ]);
                $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                    'documenti_contabilita_ricezione_sdi_descrizione_errore' => 'Ricevuta di consegna',
                    'documenti_contabilita_ricezione_sdi_tipo_esito' => $tipi_esito_map[$codiceesito][0],
                    'documenti_contabilita_ricezione_sdi_data_ora_ricezione' => (string) $xml->DataOraRicezione,
                    'documenti_contabilita_ricezione_sdi_data_ora_consegna' => (string) $xml->DataOraConsegna,
                ]);

                break;


            case 'MC': //Notifica di mancata consegna (per le fatture destinate alle PA) e ricevuta di impossibilità di recapito (per le fatture B2B), ognuna con il proprio schema
                $descrizione_aggiuntiva = (string) $xml->Descrizione;

                //Aggiorno sia lo stato (errore), sia l'errore (tenendo lo storico degli altri errori precedentemente segnalati)
                $this->apilib->edit('documenti_contabilita', $documento['documenti_contabilita_id'], [
                    'documenti_contabilita_stato_invio_sdi' => 10, //Mancata consegna
                    //'documenti_contabilita_stato_invio_sdi_errore_gestito' => date('m/d/Y, h:i:s')." - Esito: $codiceesito - {$tipi_esito_map[$codiceesito][1]} - {$descrizione_aggiuntiva}<br />".$documento['documenti_contabilita_stato_invio_sdi_errore_gestito']
                ]);
                $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                    'documenti_contabilita_ricezione_sdi_descrizione_errore' => $descrizione_aggiuntiva,
                    'documenti_contabilita_ricezione_sdi_tipo_esito' => $tipi_esito_map[$codiceesito][0],
                    'documenti_contabilita_ricezione_sdi_data_ora_ricezione' => (string) $xml->DataOraRicezione,
                    'documenti_contabilita_ricezione_sdi_data_messa_a_disposizione' => (string) $xml->DataMessaADisposizione,
                ]);

                break;

            case 'NS': //Notifica di scarto (per le fatture destinate alle PA) e ricevuta di scarto (per le fatture B2B), ognuna con il proprio schema

                //Aggiorno sia lo stato (errore), sia l'errore (tenendo lo storico degli altri errori precedentemente segnalati)
                $this->apilib->edit('documenti_contabilita', $documento['documenti_contabilita_id'], [
                    'documenti_contabilita_stato_invio_sdi' => 6, //Notifica di scarto / Scartata dal sdi
                    //'documenti_contabilita_stato_invio_sdi_errore_gestito' => date('m/d/Y, h:i:s')." - Esito: $codiceesito - {$tipi_esito_map[$codiceesito][1]} - {$descrizione_aggiuntiva}<br />".$documento['documenti_contabilita_stato_invio_sdi_errore_gestito']
                ]);

                $descrizione_aggiuntiva = '';
                if (isset($xml->ListaErrori)) {
                    foreach ($xml->ListaErrori as $errore) {
                        $descrizione_aggiuntiva .= "Errore: " . (string) $errore->Errore->Codice . " " . (string) $errore->Errore->Descrizione . " " . (string) $errore->Errore->Suggerimento . " <br />";
                        if (!empty((string) $errore->Suggerimento)) {
                            $descrizione_aggiuntiva .= "<br />" . (string) $errore->Suggerimento;
                        }
                    }
                } else {
                    $descrizione_aggiuntiva = 'Lista errori mancante.';
                }

                $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
                    'documenti_contabilita_ricezione_sdi_descrizione_errore' => $descrizione_aggiuntiva,
                    'documenti_contabilita_ricezione_sdi_tipo_esito' => $tipi_esito_map[$codiceesito][0],
                    'documenti_contabilita_ricezione_sdi_data_ora_ricezione' => (string) $xml->DataOraRicezione,
                ]);

                // invio mail all'admin che includa queste informazioni:
                // $descrizione_aggiuntiva
                // $documento['documenti_contabilita_numero'] / $documento['documenti_contabilita_serie']
                // $documento['documenti_contabilita_id'] (per il link al dettaglio documento)
                // Template da usare:     notifica_scarto_sdi

                try {
                    if (!empty($settings['documenti_contabilita_settings_smtp_mail_from'])) {
                        $mail_data = [];

                        $mail_data['numero_documento'] = $documento['documenti_contabilita_numero'];

                        if (!empty($documento['documenti_contabilita_serie'])) {
                            $mail_data['numero_documento'] = $mail_data['numero_documento'] . " / {$documento['documenti_contabilita_serie']}";
                        }

                        if (!empty($descrizione_aggiuntiva)) {
                            $mail_data['errore'] = $descrizione_aggiuntiva;
                        } else {
                            $mail_data['errore'] = '';
                        }

                        $mail_data['link_documento'] = "<a href='" . base_url("main/layout/contabilita_dettaglio_documento/{$documento['documenti_contabilita_id']}") . "'>Clicca qui</a>";

                        $check = $this->mail_model->send($settings['documenti_contabilita_settings_smtp_mail_from'], 'notifica_scarto_sdi', 'it', $mail_data);

                        if (!$check) {
                            log_message('error', "Invio mail notifica di scarto fallito id documento: {$documento['documenti_contabilita_id']}");
                        }
                    }
                } catch (Exception $e) {
                    log_message('error', "Error while sending email " . $e->getMessage());
                }

                break;
            default:
                log_message('error', "Non so come gestire l'esito $codiceesito");
                throw new Exception("Non so come gestire l'esito '$codiceesito'!!!");
                break;
        }

        //Aggiorno ovviamente anche il record dello stato elaborazione sdi
        $this->apilib->edit('documenti_contabilita_ricezione_sdi', $file['documenti_contabilita_ricezione_sdi_id'], [
            'documenti_contabilita_ricezione_sdi_stato_elaborazione' => 2, //Se arrivo qua vuol dire che l'ho processato, con o senza errori, ma l'ho comunque processato
        ]);
    }

    // Metodo per creare almeno una scadenza sulle spese che non hanno neanche una scadenza
    public function fix_scadenze()
    {
        $spese = $this->apilib->search("spese");

        foreach ($spese as $spesa) {
            $check = $this->db->query("SELECT * FROM spese_scadenze WHERE spese_scadenze_spesa = '{$spesa['spese_id']}'")->row_array();
            if (empty($check)) {
                echo "Scadenza non trovata per spesa: " . $spesa['spese_id'] . "<br />";

                $this->apilib->create('spese_scadenze', [
                    'spese_scadenze_ammontare' => $spesa['spese_totale'],
                    'spese_scadenze_scadenza' => $spesa['spese_data_emissione'],
                    'spese_scadenze_saldato_con' => 'contanti',
                    'spese_scadenze_data_saldo' => $spesa['spese_data_emissione'],
                    'spese_scadenze_movimento_id' => null,
                    'spese_scadenze_spesa' => $spesa['spese_id'],
                ]);
            }
        }
    }

    public function create_spesa()
    {
        $input = $this->input->post();

        //debug($input,true);

        if (!empty($input['spesa_id'])) {
            //die('NON GESTITO SALVATAGGIO IN MODIFICA.... Da completare!');
        }

        //Questa variabile mi serve per far si che non vada a creare nuovi flussi cassa (visto che già esistono, ma li associ semplicemente a quelli passati)
        // $from_flussi_cassa = false;
        // if (!empty($input['_flussi_cassa_ids'])) {
        //     $from_flussi_cassa = true;
        // }

        $this->load->library('form_validation');

        $this->form_validation->set_rules('spese_numero', 'numero documento', 'required');
        $this->form_validation->set_rules('spese_data_emissione', 'data emissione', 'required');
        $this->form_validation->set_rules('ragione_sociale', 'ragione sociale', 'required');
        $full_enabled = $this->apilib->searchFirst('documenti_contabilita_general_settings', ['documenti_contabilita_general_settings_contabilita_full' => DB_BOOL_TRUE]);

        if ($full_enabled) {
            $this->form_validation->set_rules('spese_modello_prima_nota', 'modello prima nota', 'required');
        }

        //$this->form_validation->set_rules('indirizzo', 'indirizzo', 'required');
        /*$this->form_validation->set_rules('citta', 'città', 'required');
        $this->form_validation->set_rules('provincia', 'provincia', 'required');
        $this->form_validation->set_rules('codice_fiscale', 'codice fiscale', 'required');
        $this->form_validation->set_rules('partita_iva', 'partita iva', 'required');
        $this->form_validation->set_rules('cap', 'CAP', 'required');*/

        $spesa = [];
        if ($this->form_validation->run() == false) {
            echo json_encode(
                array(
                    'status' => 0,
                    'txt' => validation_errors(),
                    'data' => '',
                )
            );
        } else {
            //debug($input, true);
            $dest_entity_name = $input['dest_entity_name'];

            // **************** DESTINATARIO ****************** //

            $dest_fields = array("ragione_sociale", "indirizzo", "citta", "provincia", "nazione", "codice_fiscale", "partita_iva", 'cap');
            foreach ($input as $key => $value) {
                if (in_array($key, $dest_fields)) {
                    $destinatario_json[$key] = $value;
                    $destinatario_entity[$dest_entity_name . "_" . $key] = $value;
                }
            }

            $spesa['spese_azienda'] = $input['spese_azienda'];

            // Serialize
            $spesa['spese_fornitore'] = json_encode($destinatario_json);

            $supplier = [
                'customers_company' => $input['ragione_sociale'],
            ];
            if (!empty($destinatario_json['nazione'])) {
                $nazione = $this->db->get_where('countries', ['countries_iso' => $destinatario_json['nazione']])->row_array();
            } else {
                $nazione['countries_id'] = 105;
            }
            

            $supplier['customers_address'] = $input['indirizzo'];
            $supplier['customers_city'] = $input['citta'];
            $supplier['customers_province'] = $input['provincia'];
            $supplier['customers_country_id'] = $nazione['countries_id'];
            //$supplier['suppliers_country'] = $input['nazione'];
            $supplier['customers_zip_code'] = $input['cap'];
            //$supplier['suppliers_pec'] = $input['pec'];

            $supplier['customers_vat_number'] = $input['partita_iva'];
            $supplier['customers_cf'] = $input['codice_fiscale'];
            //$supplier['suppliers_sdi'] = $input['codice_sdi'];

            //Fornitore

            // Se già censito lo collego altrimenti lo salvo se richiesto
            if ($input['dest_id']) {
                $spesa['spese_customer_id'] = $input['dest_id'];

                //Se ho comunque richiesto la sovrascrittura dei dati
                if (isset($input['save_dest']) && $input['save_dest'] == "true") {
                    $this->apilib->edit('customers', $input['dest_id'], $supplier);
                }
            } elseif (isset($input['save_dest']) && $input['save_dest'] == "true") {
                $supplier['customers_type'] = 2;
                $dest_id = $this->apilib->create('customers', $supplier, false);
                $spesa['spese_customer_id'] = $dest_id;
            }

            // **************** DOCUMENTO ****************** //
            $spesa['spese_note'] = $input['spese_note'];

            $spesa['spese_numero'] = $input['spese_numero'];

            $spesa['spese_valuta'] = $input['spese_valuta'];
            $spesa['spese_imponibile'] = $input['spese_imponibile'];

            $spesa['spese_totale'] = $input['spese_totale'];

            $spesa['spese_deduc_tasse'] = $input['spese_deduc_tasse'];
            $spesa['spese_rit_acconto'] = $input['spese_rit_acconto'];
            $spesa['spese_deduc_iva'] = $input['spese_deduc_iva'];
            $spesa['spese_utente_id'] = (!empty($spesa['spese_utente_id'])) ? $spesa['spese_utente_id'] : '';

            //debug($input,true);

            $spesa['spese_anni_ammortamento'] = $input['spese_anni_ammortamento'];

            $spesa['spese_iva'] = $input['spese_iva'];
            //            $spesa['documenti_metodo_pagamento'] = $input['documenti_metodo_pagamento'];
            //            $spesa['documenti_conto_corrente'] = $input['documenti_conto_corrente'];
            $spesa['spese_data_emissione'] = $input['spese_data_emissione'];
            $spesa['spese_categoria'] = $input['spese_categoria'];
            $spesa['spese_centro_di_costo'] = (!empty($input['spese_centro_di_costo'])) ? $input['spese_centro_di_costo'] : null;
            $spesa['spese_tipologia_fatturazione'] = (!empty($input['spese_tipologia_fatturazione'])) ? $input['spese_tipologia_fatturazione'] : '';



            $spesa['spese_tipologia_autofattura'] = (!empty($input['spese_tipologia_autofattura'])) ? $input['spese_tipologia_autofattura'] : null;
            if ($full_enabled) {
                $spesa['spese_modello_prima_nota'] = $input['spese_modello_prima_nota'];
            }

            if (!empty($input['spesa_id'])) {
                $spesa_id = $input['spesa_id'];
                $this->apilib->edit('spese', $input['spesa_id'], $spesa);
            } else {
                $spesa_id = $this->apilib->create('spese', $spesa, false);
            }

            // **************** SCADENZE ******************* //
            // if (!empty($input['spesa_id'])) {
            //     $this->db->delete('spese_scadenze', ['spese_scadenze_spesa' => $spesa_id]);
            // }
            $_metodi_pagamento = $this->apilib->search('documenti_contabilita_metodi_pagamento');
            $metodi_pagamento = array_key_value_map($_metodi_pagamento, 'documenti_contabilita_metodi_pagamento_valore', 'documenti_contabilita_metodi_pagamento_id');
            //debug($metodi_pagamento, true);
            $scadenze_ids = [-1];
            foreach ($input['scadenze'] as $scadenza) {

                

                if ($scadenza['spese_scadenze_ammontare'] > 0) {
                    if (!empty($scadenza['spese_scadenze_id'])) {
                        $scadenze_ids[] = $scadenza['spese_scadenze_id'];
                        //Modifico
                        if (!empty($scadenza['_flussi_cassa_id'])) {

                            //Faccio tutto manualmente altrimenti i pp creano duplicati
                            $this->db->where('spese_scadenze_id', $scadenza['spese_scadenze_id'])->update('spese_scadenze', [
                                'spese_scadenze_ammontare' => $scadenza['spese_scadenze_ammontare'],
                                'spese_scadenze_scadenza' => date_toDbFormat($scadenza['spese_scadenze_scadenza']),
                                'spese_scadenze_saldato_da' => $scadenza['spese_scadenze_saldato_da'],
                                'spese_scadenze_saldato_con' => ($scadenza['spese_scadenze_saldato_con']) ? $metodi_pagamento[$scadenza['spese_scadenze_saldato_con']] : null,
                                'spese_scadenze_data_saldo' => ($scadenza['spese_scadenze_data_saldo']) ? date_toDbFormat($scadenza['spese_scadenze_data_saldo']) : null,
                                'spese_scadenze_movimento_id' => (!empty($scadenza['spese_scadenze_movimento_id'])) ? $scadenza['spese_scadenze_movimento_id'] : null,
                                'spese_scadenze_spesa' => $spesa_id,
                                'spese_scadenze_saldata' => DB_BOOL_TRUE,
                            ]);
                            
                            $this->apilib->edit('flussi_cassa', $scadenza['_flussi_cassa_id'], ['flussi_cassa_spese_scadenze_collegate' => [$scadenza['spese_scadenze_id']]]);
                        } else {
                            $this->apilib->edit('spese_scadenze', $scadenza['spese_scadenze_id'], [
                                'spese_scadenze_ammontare' => $scadenza['spese_scadenze_ammontare'],
                                'spese_scadenze_scadenza' => $scadenza['spese_scadenze_scadenza'],
                                'spese_scadenze_saldato_da' => $scadenza['spese_scadenze_saldato_da'],
                                'spese_scadenze_saldato_con' => ($scadenza['spese_scadenze_saldato_con']) ? $metodi_pagamento[$scadenza['spese_scadenze_saldato_con']] : null,
                                'spese_scadenze_data_saldo' => ($scadenza['spese_scadenze_data_saldo']) ?: null,
                                'spese_scadenze_movimento_id' => (!empty($scadenza['spese_scadenze_movimento_id'])) ? $scadenza['spese_scadenze_movimento_id'] : null,
                                'spese_scadenze_spesa' => $spesa_id,
                            ]);
                        }
                    } else {
                       //Creo
                        if (!empty($scadenza['_flussi_cassa_id'])) {

                            //debug(date_toDbFormat($scadenza['spese_scadenze_scadenza']),true);

                            //Faccio tutto manualmente altrimenti i pp creano duplicati
                            $this->db->insert('spese_scadenze', [
                                'spese_scadenze_ammontare' => $scadenza['spese_scadenze_ammontare'],
                                'spese_scadenze_scadenza' => date_toDbFormat($scadenza['spese_scadenze_scadenza']),
                                'spese_scadenze_saldato_da' => $scadenza['spese_scadenze_saldato_da'],
                                'spese_scadenze_saldato_con' => ($scadenza['spese_scadenze_saldato_con']) ? $metodi_pagamento[$scadenza['spese_scadenze_saldato_con']] : null,
                                'spese_scadenze_data_saldo' => ($scadenza['spese_scadenze_data_saldo']) ? date_toDbFormat($scadenza['spese_scadenze_data_saldo']) : null,
                                'spese_scadenze_movimento_id' => (!empty($scadenza['spese_scadenze_movimento_id'])) ? $scadenza['spese_scadenze_movimento_id'] : null,
                                'spese_scadenze_spesa' => $spesa_id,
                                'spese_scadenze_saldata' => DB_BOOL_TRUE,
                            ]);
                            $spese_scadenze_id = $this->db->insert_id();
                            
                            $this->apilib->edit('flussi_cassa', $scadenza['_flussi_cassa_id'], ['flussi_cassa_spese_scadenze_collegate' => [$spese_scadenze_id]]);
                        } else {
                            $spese_scadenze_id = $this->apilib->create('spese_scadenze', [
                                'spese_scadenze_ammontare' => $scadenza['spese_scadenze_ammontare'],
                                'spese_scadenze_scadenza' => $scadenza['spese_scadenze_scadenza'],
                                'spese_scadenze_saldato_da' => $scadenza['spese_scadenze_saldato_da'],
                                'spese_scadenze_saldato_con' => ($scadenza['spese_scadenze_saldato_con']) ? $metodi_pagamento[$scadenza['spese_scadenze_saldato_con']] : null,
                                'spese_scadenze_data_saldo' => ($scadenza['spese_scadenze_data_saldo']) ?: null,
                                'spese_scadenze_movimento_id' => (!empty($scadenza['spese_scadenze_movimento_id'])) ? $scadenza['spese_scadenze_movimento_id'] : null,
                                'spese_scadenze_spesa' => $spesa_id,
                            ], false);
                        }
                        $scadenze_ids[] = $spese_scadenze_id;
                    }

                    
                }
            }
            //TODO: sostituire con foreach e apilib delete
            $this->db->query("DELETE FROM spese_scadenze where spese_scadenze_spesa = $spesa_id AND spese_scadenze_id NOT IN (" . implode(',', $scadenze_ids) . ")");
            $this->mycache->clearCacheTags(['spese_scadenze', 'spese']);

            // **************** PRODOTTI ****************** //
            if (!empty($input['spesa_id'])) {
                $this->db->delete('spese_articoli', ['spese_articoli_spesa' => $input['spesa_id']]);
            }
            if (isset($input['products']) && !empty($input['products'])) {
                foreach ($input['products'] as $prodotto) {
                    if (!empty($prodotto['spese_articoli_name'])) {
                        $prodotto['spese_articoli_spesa'] = $spesa_id;
                        $this->apilib->create("spese_articoli", $prodotto);

                        if (!empty($input['censisci']) && $input['censisci']) {
                            //TODO...
                        }

                        if (!empty($input['movimenti']) && $input['movimenti']) {
                            //TODO...
                        }
                    }
                }
            }

            $session_files = (array) ($this->session->userdata('files'));

            //debug($session_files,true);

            if (!empty($session_files)) {
                foreach ($session_files as $key => $file) {
                    if (!empty($file)) {
                        $this->apilib->create('spese_allegati', [
                            'spese_allegati_spesa' => $spesa_id,
                            'spese_allegati_file' => $file['path_local'],

                        ]);
                    }
                }
            }
            //debug($session_files,true);
            $this->session->set_userdata('files', []);
            //debug($spesa, true);
            //Se è di tipo TD17, TD18, TD19, chiedo se si vuole procedere al salvataggio dell'autofattura
            if ($spesa['spese_tipologia_autofattura']) {
                $tipologia_autofatturazione = $this->apilib->view('documenti_contabilita_tipologie_fatturazione', $spesa['spese_tipologia_autofattura']);
            } else {
                $tipologia_autofatturazione['documenti_contabilita_tipologie_fatturazione_codice'] = '';
            }

            if (in_array($tipologia_autofatturazione['documenti_contabilita_tipologie_fatturazione_codice'], ['TD17', 'TD18', 'TD19'])) {
                $document_type = 'Fattura+Reverse';

                if (!empty($spesa['spese_modello_prima_nota']) && empty($input['spesa_id'])) {
                    if ($spesa['spese_tipologia_fatturazione']) {
                        $tipologia_fatturazione = $this->apilib->view('documenti_contabilita_tipologie_fatturazione', $spesa['spese_tipologia_fatturazione']);
                    } else {
                        $tipologia_fatturazione['documenti_contabilita_tipologie_fatturazione_codice'] = '';
                    }

                    if ($tipologia_fatturazione['documenti_contabilita_tipologie_fatturazione_codice'] == 'TD04') {
                        $document_type = 'Nota+di+credito+Reverse';
                    }

                    //Se entro qua vuol dire che ho assegnato un modello... chiedo se si vuole andare in prima nota o tornare all'elenco spese
                    echo json_encode(
                        array(
                            'status' => 9,
                            'txt' => "

                    if (confirm('Hai selezionato un " . $tipologia_autofatturazione['documenti_contabilita_tipologie_fatturazione_codice'] . " come tipologia di fatturazione. Vuoi procedere alla registrazione dell\'autofattura (Fattura reverse)?') == true) {
                        location.href='" . base_url("main/layout/nuovo_documento?doc_type=" . $document_type . "&serie=R&spesa_id={$spesa_id}") . "';
                    } else {
                        if (confirm('Vuoi procedere anche con la registrazione in prima nota?') == true) {
                            location.href='" . base_url("main/layout/prima-nota?modello={$spesa['spese_modello_prima_nota']}&spesa_id={$spesa_id}") . "';
                        } else {
                            location.href='" . base_url('main/layout/elenco_spese') . "';
                        }
                    }
                ",
                        )
                    );
                } else {
                    echo json_encode(
                        array(
                            'status' => 9,
                            'txt' => "
            if (confirm('Hai selezionato un " . $tipologia_autofatturazione['documenti_contabilita_tipologie_fatturazione_codice'] . " come tipologia di fatturazione. Vuoi procedere alla registrazione dell\'autofattura?') == true) {
                location.href='" . base_url("main/layout/nuovo_documento?doc_type=" . $document_type . "&serie=R&spesa_id={$spesa_id}") . "';
            } else {
                location.href='" . base_url('main/layout/elenco_spese') . "';
            }
        ",
                        )
                    );
                }
            } else {
                if ($full_enabled && $spesa['spese_modello_prima_nota'] && empty($input['spesa_id'])) {
                    //Se entro qua vuol dire che ho assegnato un modello... chiedo se si vuole andare in prima nota o tornare all'elenco spese
                    echo json_encode(
                        array(
                            'status' => 9,
                            'txt' => "

                    if (confirm('Vuoi procedere anche con la registrazione in prima nota?') == true) {
                        location.href='" . base_url("main/layout/prima-nota?modello={$spesa['spese_modello_prima_nota']}&spesa_id={$spesa_id}") . "';
                    } else {
                        location.href='" . base_url('main/layout/elenco_spese') . "';
                    }
                ",
                        )
                    );
                } else {
                    echo json_encode(array('status' => 1, 'txt' => base_url('main/layout/elenco_spese')));
                }
            }
        }
    }

    public function autocomplete($entity)
    {
        $input = $this->input->get_post('search');

        $count_total = 0;

        $input = trim($input);
        if (empty($input) or strlen($input) < 3) {
            echo json_encode(['count_total' => -1]);
            return;
        }

        $results = [];

        $input = strtolower($input);

        if ($entity == 'fw_products') {
            $res = $this->apilib->search('fw_products', ["(LOWER(fw_products_name) LIKE '%{$input}%' OR fw_products_sku LIKE '{$input}%' OR CAST(fw_products_ean AS CHAR) = '{$input}')"]);
            //die("(LOWER(fw_products_name) LIKE '%{$input}%' OR fw_products_sku LIKE '{$input}%' OR CAST(fw_products_ean AS CHAR) = '{$input}')");
        } elseif ($entity == 'clienti') {
            $res = $this->apilib->search('clienti', ["(LOWER(clienti_ragione_sociale) LIKE '%{$input}%')"]);
        } elseif ($entity == 'suppliers') {
            $res = $this->apilib->search('suppliers', ["(LOWER(suppliers_business_name) LIKE '%{$input}%')"]);
        }

        if ($res) {
            $count_total = count($res);
            $results = [
                'data' => $res,
            ];
        }

        echo json_encode(['count_total' => $count_total, 'results' => $results]);
    }

    public function numeroSucessivo($tipo, $serie)
    {
        $next = $this->db->query("SELECT count(*) + 1 as numero FROM documenti WHERE documenti_tipo = '$tipo' AND documenti_serie = '$serie'")->row()->numero;
        echo $next;
    }

    public function addFile()
    {
        //debug($_FILES, true);
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $filename = md5($_FILES['file']['name']) . '.' . $ext;
        $uploadDepthLevel = defined('UPLOAD_DEPTH_LEVEL') ? (int) UPLOAD_DEPTH_LEVEL : 0;

        if ($uploadDepthLevel > 0) {
            // Voglio comporre il nome locale in modo che se il nome del file fosse
            // pippo.jpg la cartella finale sarà: ./uploads/p/i/p/pippo.jpg
            $localFolder = '';
            for ($i = 0; $i < $uploadDepthLevel; $i++) {
                // Assumo che le lettere siano tutte alfanumeriche,
                // alla fine le immagini sono tutte delle hash md5
                $localFolder .= strtolower(isset($filename[$i]) ? $filename[$i] . DIRECTORY_SEPARATOR : '');
            }

            if (!is_dir(FCPATH . 'uploads/' . $localFolder)) {
                mkdir(FCPATH . 'uploads/' . $localFolder, DIR_WRITE_MODE, true);
            }
        }

        $this->load->library(
            'upload',
            array(
                'upload_path' => FCPATH . 'uploads/' . $localFolder,
                'allowed_types' => '*',
                'max_size' => '50000',
                'encrypt_name' => false,
                'file_name' => $filename,
            )
        );

        $uploaded = $this->upload->do_upload('file');
        if (!$uploaded) {
            debug($this->upload->display_errors());
            die();
        }

        $up_data = $this->upload->data();
        $up_data['path_local'] = $localFolder . $filename;
        $session = (array) ($this->session->userdata('files'));
        $session[] = $up_data;
        $this->session->set_userdata('files', $session);
        usleep(100);
        echo json_encode(['status' => 1, 'file' => $up_data]);
    }

    /* Dropzone per import fatture massivo */

    public function importFatture()
    {
        //debug($_FILES, true);
        $ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $filename = $_FILES['file']['name'];
        // $filename = md5($_FILES['file']['name']) . '.' . $ext;
        // $uploadDepthLevel = defined('UPLOAD_DEPTH_LEVEL') ? (int) UPLOAD_DEPTH_LEVEL : 0;

        $localFolder = 'modules_files/contabilita/import_ricezione_sdi';

        if (!is_dir(FCPATH . 'uploads/' . $localFolder)) {
            mkdir(FCPATH . 'uploads/' . $localFolder, DIR_WRITE_MODE, true);
        }
        $localFolder = 'modules_files/contabilita/import_ricezione_sdi/';

        if (file_exists(FCPATH . 'uploads/' . $localFolder . $filename)) {
            unlink(FCPATH . 'uploads/' . $localFolder . $filename);
        }
        $this->load->library(
            'upload',
            array(
                'upload_path' => FCPATH . 'uploads/' . $localFolder,
                'allowed_types' => '*',
                'max_size' => '50000',
                'encrypt_name' => false,
                'file_name' => $filename,
            )
        );

        $uploaded = $this->upload->do_upload('file');
        if (!$uploaded) {
            debug($this->upload->display_errors());
            die();
        }

        $up_data = $this->upload->data();
        //debug($up_data, true);

        // Inserisco su DB
        $file_data = array();
        $file_data['documenti_contabilita_ricezione_sdi_nome_file'] = $up_data['client_name'];
        $file_data['documenti_contabilita_ricezione_sdi_file_verificato'] = $up_data['full_path'];
        $file_data['documenti_contabilita_ricezione_sdi_source'] = 2; // Upload
        $file_data['documenti_contabilita_ricezione_sdi_stato_elaborazione'] = 1; // Da elaborare
        $file_data['documenti_contabilita_ricezione_sdi_creation_date'] = date('Y-m-d H:i');
        $this->db->insert('documenti_contabilita_ricezione_sdi', $file_data);

        $up_data['path_local'] = $localFolder . $filename;
        $session = (array) ($this->session->userdata('files'));

        $session[] = $up_data;

        $this->session->set_userdata('files', $session);
        usleep(100);
        unset($_POST);
        unset($_FILES);
        //$trigger_cron = $this->elaborazione_documenti_da_sdi();

        echo json_encode(['status' => 1, 'file' => $up_data]);
    }

    public function removeFile($id)
    {
        $this->apilib->delete('spese_allegati', $id);
    }

    public function downloadZip()
    {
        $ids = json_decode($this->input->post('ids'));

        // debug($ids);

        $spese = $this->apilib->search('spese_allegati', ['spese_allegati_spesa IN (' . implode(',', $ids) . ')']);

        // dd($spese);

        //debug($spese,true);
        $this->load->helper('download');
        $this->load->library('zip');
        $dest_folder = FCPATH . "uploads/modules_files/contabilita/spese";

        $destination_file = "{$dest_folder}/spese.zip";

        //die('test');
        //Ci aggiungo il json e la versione, poi rizippo il pacchetto...
        $zip = new ZipArchive();

        if ($zip->open($destination_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            exit("cannot open <$destination_file>\n");
        }

        foreach ($spese as $spesa) {
            //debug($spesa,true);
            if (
                !empty($spesa['spese_allegati_file'])
                && file_exists($dest_folder . '/' . $spesa['spese_allegati_file'])
            ) {
                $file_content = file_get_contents($dest_folder . '/' . $spesa['spese_allegati_file']);
                $zip->addFromString($spesa['spese_allegati_file'], $file_content);
            }


        }

        $zip->close();

        force_download('spese.zip', file_get_contents($destination_file));
    }

    public function visualizza_formato_compatto($spesa_id, $pdf = false)
    {
        $file_xml = $this->apilib->searchFirst('spese_allegati', [
            'spese_allegati_spesa' => $spesa_id,
            "spese_allegati_file like '%.xml%'",
        ]);

        if ($file_xml) {
            //debug($file_xml,true);
            $pagina = file_get_contents('./uploads/modules_files/contabilita/spese/' . $file_xml['spese_allegati_file']);

            /*$pagina = str_ireplace('<?xml version="1.0" encoding="UTF-8" ?>', '', $pagina);
            $pagina = str_ireplace('<?xml version="1.0" encoding="utf-8"?>', '', $pagina);
            $pagina = str_ireplace('<?xml version="1.0" encoding="utf-8" standalone="no"?>', '', $pagina);
            $pagina = str_ireplace('<?xml version="1.0" encoding="utf-8" standalone="no" ?>', '', $pagina);
            $pagina = str_ireplace('<?xml version="1.0" encoding="utf-8" standalone="yes"?>', '', $pagina);
            $pagina = str_ireplace('<?xml version="1.0" encoding="utf-8" standalone="yes" ?>', '', $pagina);
            */
            $pagina = $this->replace_all_text_between($pagina, '<?xml', '?>', '');

            $pagina = str_ireplace('<?xml?>', '', $pagina);

            $regex = <<<'END'
                /
                (
                    (?: [\x00-\x7F]                 # single-byte sequences   0xxxxxxx
                    |   [\xC0-\xDF][\x80-\xBF]      # double-byte sequences   110xxxxx 10xxxxxx
                    |   [\xE0-\xEF][\x80-\xBF]{2}   # triple-byte sequences   1110xxxx 10xxxxxx * 2
                    |   [\xF0-\xF7][\x80-\xBF]{3}   # quadruple-byte sequence 11110xxx 10xxxxxx * 3
                    ){1,100}                        # ...one or more times
                )
                | .                                 # anything else
                /x
                END;

            $pagina = $this->remove_bs($pagina);


            //$pagina = mb_convert_encoding($pagina, 'UTF-8', 'UTF-8');
            //
            $view_content = $this->load->view('contabilita/pdf/visualizzazione_compatta', ['xml' => $pagina], true);

            if ($pdf) { //TODO


                // URL del file XSL
                $xslUrl = 'http://localhost/firegui_crm/module_bridge/contabilita/fattura-compatta.xsl';

                // XML da una variabile
                $xmlString = $view_content;

                // Carica il file XSL
                $xsl = new DOMDocument();
                $xsl->load($xslUrl);

                // Carica il file XML dalla variabile
                $xml = new DOMDocument();
                $xml->loadXML($xmlString);

                // Crea il trasformatore XSLT
                $processor = new XSLTProcessor();
                $processor->importStylesheet($xsl);

                // Esegui la trasformazione XSLT in HTML
                $html = $processor->transformToXML($xml);

                // Stampa l'HTML risultante
                //echo $html;
                //die();





                $orientation = $this->input->get('orientation') ? $this->input->get('orientation') : 'portrait';
                $pdfFile = $this->layout->generate_pdf($html, $orientation, "", [], false, true);

                $contents = file_get_contents($pdfFile, true);
                $pdf_b64 = base64_encode($contents);

                $file_name = 'spesa_' . $spesa_id . '_formato_compatto';

                header('Content-Type: application/pdf');
                header('Content-disposition: inline; filename="' . $file_name . time() . '.pdf"');

                echo base64_decode($pdf_b64);
            } else {
                header("Content-Type:text/xml");
                echo $view_content;
            }
        }
    }

    //TODO: portare native queste funzioni sul general helper! possono tornare utili un domani
    public function remove_bs($Str)
    {
        $StrArr = str_split($Str);
        $NewStr = '';
        foreach ($StrArr as $Char) {
            $CharNo = ord($Char);
            if ($CharNo == 163) {
                $NewStr .= $Char;
                continue;
            } // keep £
            if ($CharNo > 31 && $CharNo < 127) {
                $NewStr .= $Char;
            } elseif ($Char === "\n" || $Char === "\r\n") {
                $NewStr .= ' ';
            }
        }
        return $NewStr;
    }
    public function replace_all_text_between($str, $start, $end, $replacement)
    {
        $replacement = $start . $replacement . $end;

        $start = preg_quote($start, '/');
        $end = preg_quote($end, '/');
        $regex = "/({$start})(.*?)({$end})/";

        return preg_replace($regex, $replacement, $str);
    }

    public function imposta_data_ricezione_sdi($limit = 10)
    {
        $spese = $this->apilib->search('spese', [
            'spese_importata_da_xml' => DB_BOOL_TRUE,
            'spese_data_ricezione_sdi IS NULL',
            "spese_file_da_sdi IS NOT NULL AND spese_file_da_sdi <> ''"
        ], $limit, null, 'spese_id', 'DESC');
        $c = 0;
        $total = count($spese);
        $this->load->model('contabilita/docs');
        foreach ($spese as $spesa) {
            $c++;
            progress($c, $total);
            //debug($spesa, true);
            if (!empty($spesa['spese_file_da_sdi'])) {

                $sdi = $this->apilib->view('documenti_contabilita_ricezione_sdi', $spesa['spese_file_da_sdi']);


                if (!empty($sdi['documenti_contabilita_ricezione_sdi_nome_file_zip'])) {

                    $this->apilib->edit('spese', $spesa['spese_id'], [
                        'spese_data_ricezione_sdi' => $this->docs->formatDateFromGregorianFileName($sdi['documenti_contabilita_ricezione_sdi_nome_file_zip'], 'Y-m-d'),
                    ]);

                }
            }

        }


    }
    public function edit_scadenze()
    {
        $input = $this->input->post();
        $spesa_id = $input['spesa_id'];

        //$this->db->delete('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $documento_id]);
        $scadenze_ids = [-1];
        foreach ($input['scadenze'] as $key => $scadenza) {
            if ($scadenza['spese_scadenze_ammontare'] > 0) {
                if (!empty($scadenza['spese_scadenze_id'])) {
                    $scadenze_ids[] = $scadenza['spese_scadenze_id'];
                    $this->apilib->edit('spese_scadenze', $scadenza['spese_scadenze_id'], [
                        'spese_scadenze_ammontare' => $scadenza['spese_scadenze_ammontare'],
                        'spese_scadenze_scadenza' => $scadenza['spese_scadenze_scadenza'],
                        'spese_scadenze_saldato_con' => $scadenza['spese_scadenze_saldato_con'],
                        'spese_scadenze_saldato_da' => (empty($scadenza['spese_scadenze_saldato_da']))?null: $scadenza['spese_scadenze_saldato_da'],
                        'spese_scadenze_data_saldo' => ($scadenza['spese_scadenze_data_saldo']) ?: null,
                        'spese_scadenze_spesa' => $spesa_id,
                    ]);
                } else {
                    $scadenze_ids[] = $this->apilib->create('spese_scadenze', [
                        'spese_scadenze_ammontare' => $scadenza['spese_scadenze_ammontare'],
                        'spese_scadenze_scadenza' => $scadenza['spese_scadenze_scadenza'],
                        'spese_scadenze_saldato_con' => $scadenza['spese_scadenze_saldato_con'],
                        'spese_scadenze_saldato_da' => (empty($scadenza['spese_scadenze_saldato_da'])) ? null : $scadenza['spese_scadenze_saldato_da'],
                        'spese_scadenze_data_saldo' => ($scadenza['spese_scadenze_data_saldo']) ?: null,
                        'spese_scadenze_spesa' => $spesa_id,
                    ], false);
                }
            } else {
                unset($input['scadenze'][$key]);
            }
        }

        $this->db->query("DELETE FROM spese_scadenze where spese_scadenze_spesa = $spesa_id AND spese_scadenze_id NOT IN (" . implode(',', $scadenze_ids) . ")");
        $this->mycache->clearCache();

        //echo json_encode(array('status' => 2));
        $this->load->view('layout/json_return', ['json' => json_encode(array('status' => 2))]);
    }
}
