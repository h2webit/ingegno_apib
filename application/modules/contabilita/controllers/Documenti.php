<?php

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Documenti extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Se non sono loggato allora semplicemente uccido la richiesta
        if ($this->auth->guest()) {
            set_status_header(401); // Unauthorized
            die('Non sei loggato nel sistema');
        }

        $this->load->model('contabilita/docs');

        $this->settings = $this->db->get('settings')->row_array();
        $this->contabilita_settings = $this->apilib->searchFirst('documenti_contabilita_settings');

        require APPPATH . "modules/contabilita/third_party/vendor/autoload.php";
    }

    public function create_document()
    {
        $input = $this->input->post();

        if (!empty($input['spesa_id'])) {
            $spesa_id = $input['spesa_id'];
            unset($input['spesa_id']);
        }

        if (!empty($input['documento_id'])) {
            //die('NON GESTITO SALVATAGGIO IN MODIFICA.... Da completare!');
        }

        $this->load->library('form_validation');

        $this->form_validation->set_rules('documenti_contabilita_tipo', 'Tipo documento', 'required');
        $this->form_validation->set_rules('documenti_contabilita_numero', 'Numero documento', 'required');
        $this->form_validation->set_rules('documenti_contabilita_data_emissione', 'data emissione', 'required');

        //Barbatrucco matteo: non è detto che sia 1 nel caso di riga eliminata (può partire da 2, da 3 o altro...)
        $chiave = 1;


        foreach (@$input['products'] as $key => $p) {
            if (count($p) < 3) {
                unset($input['products'][$key]);
                continue;
            }

            if (isset($p['documenti_contabilita_articoli_riga_desc']) && $p['documenti_contabilita_articoli_riga_desc'] == DB_BOOL_TRUE)
                continue;
            $chiave = $key;
            break;
        }

        //Verifico che il numero di fattura e la serie rispettino le regole di fatturazione
        $numero = $this->input->post('documenti_contabilita_numero');
        $serie = $this->input->post('documenti_contabilita_serie');
        $azienda = $this->input->post('documenti_contabilita_azienda');
        $tipo = $this->input->post('documenti_contabilita_tipo');

        $this->form_validation->set_rules('products[' . $chiave . '][documenti_contabilita_articoli_name]', 'nome prodotto', 'required');
        $this->form_validation->set_rules('ragione_sociale', 'ragione sociale', 'required');
        $this->form_validation->set_rules('indirizzo', 'indirizzo', 'required');
        $this->form_validation->set_rules('citta', 'città', 'required');
        $this->form_validation->set_rules('nazione', 'nazione', 'required');

        if ($this->input->post('nazione') == 'IT') {
            $this->form_validation->set_rules('provincia', 'provincia', 'required|max_length[2]');
            $this->form_validation->set_rules('cap', 'CAP', 'required');
        }

        if ($this->input->post('documenti_contabilita_formato_elettronico') == DB_BOOL_TRUE) {
            if (in_array($tipo, ['1', '4'])) {
                if ($this->input->post('nazione') == 'IT') {
                    if (empty($input['partita_iva'])) {
                        $this->form_validation->set_rules('codice_fiscale', 'codice fiscale', 'required');
                    }
                }
            }
            $this->form_validation->set_rules('documenti_contabilita_tipologia_fatturazione', 'Tipologia di fatturazione', 'required');
        } else {
        }


        // DATA EMISSIONE
        if ($this->db->dbdriver != 'postgre') {
            //debug($input['documenti_contabilita_data_emissione'],true);

            $date = DateTime::createFromFormat("d/m/Y", $input['documenti_contabilita_data_emissione']);
            $data_emissione = $date->format('Y-m-d H:i:s');
            $year = $date->format('Y');
            $filtro_anno = "AND YEAR(documenti_contabilita_data_emissione) = $year";
        } else {
            //$data_emissione = $input['documenti_contabilita_data_emissione'];
            $date = DateTime::createFromFormat("d/m/Y", $input['documenti_contabilita_data_emissione']);
            $data_emissione = $date->format('Y-m-d H:i:s');
            $year = $date->format('Y');
            $filtro_anno = "AND date_part('year', documenti_contabilita_data_emissione) = '$year'";
        }

        //Controllo che il numero del documento sia numerico
        if (!empty($numero) && !is_numeric($numero)) {
            echo json_encode(
                array(
                    'status' => 0,
                    'txt' => "Il numero del documento deve essere un intero, non deve contenere lettere o caratteri speciali.",
                    'data' => '',
                )
            );
            exit;
        }

        //Controllo se esiste lo stesso documento con stesso numero. Per gli altri tipi di documento, ignoro il controllo

        if (!empty($input['documento_id'])) {
            $exists = $this->db->query("SELECT documenti_contabilita_id FROM documenti_contabilita WHERE documenti_contabilita_serie = '$serie' AND documenti_contabilita_azienda = '$azienda' AND documenti_contabilita_numero = '$numero' AND documenti_contabilita_id <> '{$input['documento_id']}' AND documenti_contabilita_tipo = '{$tipo}' $filtro_anno")->num_rows();
            if ($exists) {
                echo json_encode(
                    array(
                        'status' => 0,
                        'txt' => "Esiste già un documento con questo numero!",
                        'data' => '',
                    )
                );
                exit;
            }
        } else {
            $exists = $this->db->query("SELECT documenti_contabilita_id FROM documenti_contabilita WHERE documenti_contabilita_serie = '$serie' AND documenti_contabilita_azienda = '$azienda' AND documenti_contabilita_numero = '$numero' AND documenti_contabilita_tipo = '{$tipo}' $filtro_anno")->num_rows();
            if ($exists) {
                echo json_encode(
                    array(
                        'status' => 0,
                        'txt' => "Esiste già un documento con questo numero!",
                        'data' => '',
                    )
                );
                exit;
            }
        }

        if ($this->db->dbdriver != 'postgre') {
            $filtro_data = "AND date(documenti_contabilita_data_emissione) < date('{$data_emissione}')";
        } else {
            $filtro_data = "AND documenti_contabilita_data_emissione::date < '{$data_emissione}'::date";
        }
        $exists_with_number_next = $this->db->query("SELECT documenti_contabilita_id,documenti_contabilita_numero,documenti_contabilita_data_emissione FROM documenti_contabilita WHERE documenti_contabilita_serie = '$serie' AND documenti_contabilita_azienda = '$azienda' AND documenti_contabilita_numero > '$numero' AND documenti_contabilita_tipo = '{$tipo}' $filtro_data $filtro_anno");

        if ($exists_with_number_next->num_rows()) {
            $fattura = $exists_with_number_next->row();
            echo json_encode(
                array(
                    'status' => 0,
                    'txt' => "Esiste un documento (numero '{$fattura->documenti_contabilita_numero}' del '{$fattura->documenti_contabilita_data_emissione}') con numero maggiore ma data inferiore!",
                    'data' => '',
                )
            );
            exit;
        }

        if ($this->db->dbdriver != 'postgre') {
            $filtro_data = "AND date(documenti_contabilita_data_emissione) > date('{$data_emissione}')";
        } else {
            $filtro_data = "AND documenti_contabilita_data_emissione::date > '{$data_emissione}'::date";
        }

        if (!empty($input['documento_id'])) {
            $exists_with_date_next = $this->db->query("SELECT documenti_contabilita_id,documenti_contabilita_numero,documenti_contabilita_data_emissione FROM documenti_contabilita WHERE
                    documenti_contabilita_serie = '$serie'
                    AND documenti_contabilita_azienda = '$azienda'
                    AND documenti_contabilita_numero < $numero
                    AND documenti_contabilita_tipo = '{$tipo}'
                    $filtro_data
                    $filtro_anno
                    AND documenti_contabilita_id <> '{$input['documento_id']}'");
        } else {
            $exists_with_date_next = $this->db->query("SELECT documenti_contabilita_id,documenti_contabilita_numero,documenti_contabilita_data_emissione FROM documenti_contabilita WHERE
                    documenti_contabilita_serie = '$serie'
                    AND documenti_contabilita_azienda = '$azienda'
                    AND documenti_contabilita_numero < $numero
                    AND documenti_contabilita_tipo = '{$tipo}'
                    $filtro_data
                    $filtro_anno");
        }
        if ($exists_with_date_next->num_rows()) {
            $fattura = $exists_with_date_next->row();
            echo json_encode(
                array(
                    'status' => 0,
                    'txt' => "Esiste una fattura (la numero '{$fattura->documenti_contabilita_numero}' del '{$fattura->documenti_contabilita_data_emissione}') con numero minore ma data superiore!",
                    'data' => '',
                )
            );
            exit;
        }
        if ($input['documenti_contabilita_cassa_professionisti_valore'] > 0 && empty($input['documenti_contabilita_cassa_professionisti_tipo'])) {
            echo json_encode(
                array(
                    'status' => 0,
                    'txt' => "Cassa professionisti impostata senza tipo. Quando impostata una cassa professionisti è obbligatorio specificarne il tipo dalla tendina.",
                    'data' => '',
                )
            );
            exit;
        }

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

            //TODO: tutto sbagliato qua! Vanno prese le mappature dai settings!
            $dest_fields = array("codice", "ragione_sociale", "indirizzo", "citta", "provincia", "nazione", "codice_fiscale", "partita_iva", 'cap', 'pec', 'codice_sdi');
            foreach ($input as $key => $value) {
                if (in_array($key, $dest_fields)) {
                    $destinatario_json[$key] = trim($value);
                    //$destinatario_entity[$dest_entity_name . "_" . $key] = trim($value);
                }
            }
            if ($input['documenti_contabilita_tipo'] != 11 && $input['documenti_contabilita_tipo'] != 12 && !empty($input['documenti_contabilita_formato_elettronico']) && $input['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
                if ($destinatario_json['codice_sdi'] !== '0000000' || !empty($this->contabilita_settings['partita_iva'])) {
                    if (empty($destinatario_json['codice_sdi']) && empty($destinatario_json['pec'])) {
                        echo json_encode(
                            array(
                                'status' => 0,
                                'txt' => "Per le aziende la PEC o il Codice destinatario SDI devono essere compilati",
                                'data' => '',
                            )
                        );
                        exit;
                    }
                }
            }

            // Serialize
            $documents['documenti_contabilita_destinatario'] = json_encode($destinatario_json);

            $mappature = $this->docs->getMappature();
            extract($mappature);
            if ($this->input->post('documenti_contabilita_tipo_destinatario') == 2) { //Privato
                $customer = [
                    $clienti_nome => $input['ragione_sociale'],
                    
                ];
            } else {
                $customer = [
                    $clienti_ragione_sociale => $input['ragione_sociale'],
                ];
            }

            $nazione = $this->db->get_where('countries', ['countries_iso' => $input['nazione']])->row_array();

            $customer[$clienti_indirizzo] = $input['indirizzo'];
            $customer[$clienti_citta] = $input['citta'];
            $customer[$clienti_provincia] = $input['provincia'];
            $customer[$clienti_nazione] = $nazione['countries_id'];
            $customer[$clienti_cap] = $input['cap'];
            $customer[$clienti_pec] = $input['pec'];

            $customer[$clienti_partita_iva] = $input['partita_iva'];
            $customer[$clienti_codice_fiscale] = $input['codice_fiscale'];
            $customer[$clienti_codice_sdi] = $input['codice_sdi'];

            $customer[$clienti_tipo] = 1;

            // Se già censito lo collego altrimenti lo salvo se richiesto
            try {
                if ($input['dest_id']) {
                    //TODO: salvare su clienti id o su suppliers id a seconda dell'entità dest
                    if ($dest_entity_name == 'suppliers') {
                        $documents['documenti_contabilita_supplier_id'] = $input['dest_id'];
                    } else {
                        $documents['documenti_contabilita_customer_id'] = $input['dest_id'];
                    }

                    //Se ho comunque richiesto la sovrascrittura dei dati
                    if (isset($input['save_dest']) && isset($input['save_dest']) && $input['save_dest'] == 'true') {
                        $this->apilib->edit($entita_clienti, $input['dest_id'], $customer);
                    }
                } elseif (isset($input['save_dest']) && $input['save_dest'] == 'true') {
                    if ($dest_entity_name == 'suppliers') {
                        $supplier = [
                            'customers_company' => $input['ragione_sociale'],
                        ];

                        // 2021-07-01 - michael e. - commento in quanto suppliers è stato unificato dentro customers con type 2
                        // $supplier['suppliers_address'] = $input['indirizzo'];
                        // $supplier['suppliers_city'] = $input['citta'];
                        // $supplier['suppliers_province'] = $input['provincia'];
                        // $supplier['suppliers_country'] = $input['nazione'];
                        // $supplier['suppliers_zip_code'] = $input['cap'];
                        // $supplier['suppliers_pec'] = $input['pec'];

                        // $supplier['suppliers_vat_number'] = $input['partita_iva'];
                        // $supplier['suppliers_cf'] = $input['codice_fiscale'];
                        // $supplier['suppliers_sdi'] = $input['codice_sdi'];

                        $supplier[$clienti_indirizzo] = $input['indirizzo'];
                        $supplier[$clienti_citta] = $input['citta'];
                        $supplier[$clienti_provincia] = $input['provincia'];
                        $supplier[$clienti_nazione] = $nazione['countries_id'];
                        $supplier[$clienti_cap] = $input['cap'];
                        $supplier[$clienti_pec] = $input['pec'];

                        $supplier[$clienti_partita_iva] = $input['partita_iva'];
                        $supplier[$clienti_codice_fiscale] = $input['codice_fiscale'];
                        $supplier[$clienti_codice_sdi] = $input['codice_sdi'];

                        $dest_id = $this->apilib->create('suppliers', $supplier, false);
                        $documents['documenti_contabilita_supplier_id'] = $dest_id;
                    } else {
                        //debug($entita_clienti, true);
                        $dest_id = $this->apilib->create($entita_clienti, $customer, false);
                        $documents['documenti_contabilita_customer_id'] = $dest_id;
                    }
                }
            } catch (Exception $e) {
                e_json(['status' => 0, 'txt' => 'Si è verificato un errore durante il salvataggio del cliente: ' . $e->getMessage()]);
                exit;
            }

            //debug($customer, true);

            // **************** DOCUMENTO ****************** //
            // debug($input, true);

            $documents['documenti_contabilita_agente'] = $input['documenti_contabilita_agente'];
            $documents['documenti_contabilita_note_interne'] = $input['documenti_contabilita_note'];
            $documents['documenti_contabilita_note_generiche'] = $input['documenti_contabilita_note_generiche'];
            $documents['documenti_contabilita_tipo'] = $input['documenti_contabilita_tipo'];
            $documents['documenti_contabilita_numero'] = $input['documenti_contabilita_numero'];
            $documents['documenti_contabilita_serie'] = isset($input['documenti_contabilita_serie']) ? $input['documenti_contabilita_serie'] : null;
            $documents['documenti_contabilita_valuta'] = $input['documenti_contabilita_valuta'];
            $documents['documenti_contabilita_tasso_di_cambio'] = $input['documenti_contabilita_tasso_di_cambio'];
            //$documents['documenti_contabilita_metodo_pagamento'] = $input['documenti_contabilita_metodo_pagamento'];
            $documents['documenti_contabilita_template_pagamento'] = $input['documenti_contabilita_template_pagamento'];
            $documents['documenti_contabilita_conto_corrente'] = ($input['documenti_contabilita_conto_corrente']) ?: null;
            $documents['documenti_contabilita_data_emissione'] = $data_emissione;
            $documents['documenti_contabilita_formato_elettronico'] = (!empty($input['documenti_contabilita_formato_elettronico']) ? $input['documenti_contabilita_formato_elettronico'] : DB_BOOL_FALSE);
            $documents['documenti_contabilita_extra_param'] = ($input['documenti_contabilita_extra_param']) ?: null;
            $documents['documenti_contabilita_rif_documento_id'] = ($input['documenti_contabilita_rif_documento_id']) ?: null;
//            $documents['documenti_contabilita_rif_documenti'] = ($input['documenti_contabilita_rif_documenti']) ?explode(',', $input['documenti_contabilita_rif_documenti']): []; // disattivato per incompatibilità della relazione con la stessa entità referenziata
            $documents['documenti_contabilita_da_sollecitare'] = (!empty($input['documenti_contabilita_da_sollecitare']) ? $input['documenti_contabilita_da_sollecitare'] : DB_BOOL_FALSE);
            $documents['documenti_contabilita_tipologia_fatturazione'] = (!empty($input['documenti_contabilita_tipologia_fatturazione'])) ? $input['documenti_contabilita_tipologia_fatturazione'] : null;
            $documents['documenti_contabilita_oggetto'] = (!empty($input['documenti_contabilita_oggetto'])) ? $input['documenti_contabilita_oggetto'] : null;


            //debug($input['documenti_contabilita_competenze'],true);

            $documents['documenti_contabilita_rivalsa_inps_perc'] = $input['documenti_contabilita_rivalsa_inps_perc'];

            $documents['documenti_contabilita_cassa_professionisti_perc'] = $input['documenti_contabilita_cassa_professionisti_perc'];

            //Accompagnatoria/DDT
            $documents['documenti_contabilita_fattura_accompagnatoria'] = (!empty($input['documenti_contabilita_fattura_accompagnatoria']) ? $input['documenti_contabilita_fattura_accompagnatoria'] : DB_BOOL_FALSE);
            $documents['documenti_contabilita_n_colli'] = ($input['documenti_contabilita_n_colli'] ?: null);
            $documents['documenti_contabilita_peso'] = ($input['documenti_contabilita_peso'] ?: null);
            $documents['documenti_contabilita_peso_netto'] = ($input['documenti_contabilita_peso_netto'] ?: null);
            $documents['documenti_contabilita_volume'] = ($input['documenti_contabilita_volume'] ?: null);
            $documents['documenti_contabilita_targhe'] = ($input['documenti_contabilita_targhe'] ?: null);
            $documents['documenti_contabilita_descrizione_colli'] = ($input['documenti_contabilita_descrizione_colli'] ?: null);
            $documents['documenti_contabilita_luogo_destinazione'] = $input['documenti_contabilita_luogo_destinazione'];
            $documents['documenti_contabilita_luogo_destinazione_id'] = $input['documenti_contabilita_luogo_destinazione_id'];
            $documents['documenti_contabilita_trasporto_a_cura_di'] = $input['documenti_contabilita_trasporto_a_cura_di'];
            $documents['documenti_contabilita_causale_trasporto'] = $input['documenti_contabilita_causale_trasporto'];
            $documents['documenti_contabilita_annotazioni_trasporto'] = $input['documenti_contabilita_annotazioni_trasporto'];
            $documents['documenti_contabilita_ritenuta_acconto_perc'] = $input['documenti_contabilita_ritenuta_acconto_perc'];
            $documents['documenti_contabilita_ritenuta_acconto_perc_imponibile'] = $input['documenti_contabilita_ritenuta_acconto_perc_imponibile'];
            $documents['documenti_contabilita_porto'] = $input['documenti_contabilita_porto'];
            $documents['documenti_contabilita_vettori_residenza_domicilio'] = $input['documenti_contabilita_vettori_residenza_domicilio'];
            $documents['documenti_contabilita_data_ritiro_merce'] = $input['documenti_contabilita_data_ritiro_merce'];
            $documents['documenti_contabilita_tipo_destinatario'] = (!empty($input['documenti_contabilita_tipo_destinatario'])) ? $input['documenti_contabilita_tipo_destinatario'] : null;
            $documents['documenti_contabilita_utente_id'] = (!empty($input['documenti_contabilita_utente_id'])) ? $input['documenti_contabilita_utente_id'] : null;
            $documents['documenti_contabilita_rif_ddt'] = (!empty($input['documenti_contabilita_rif_ddt'])) ? $input['documenti_contabilita_rif_ddt'] : null;

            $documents['documenti_contabilita_tracking_code'] = ($input['documenti_contabilita_tracking_code'] ?: null);

            // Attributi avanzati Fattura Elettronica
            $documents['documenti_contabilita_fe_attributi_avanzati'] = (!empty($input['documenti_contabilita_fe_attributi_avanzati']) ? $input['documenti_contabilita_fe_attributi_avanzati'] : DB_BOOL_FALSE);

            $documents['documenti_contabilita_sconto_su_imponibile'] = (!empty($input['documenti_contabilita_sconto_su_imponibile']) ? $input['documenti_contabilita_sconto_su_imponibile'] : DB_BOOL_FALSE);

            $json = [];
            if (!empty($input['documenti_contabilita_fe_rif_n_linea'])) {
                $json['RiferimentoNumeroLinea'] = $input['documenti_contabilita_fe_rif_n_linea'];
            }

            if (!empty($input['documenti_contabilita_fe_id_documento'])) {
                $json['IdDocumento'] = $input['documenti_contabilita_fe_id_documento'];
            }

            $documents['documenti_contabilita_fe_attributi_avanzati_json'] = (!empty($json)) ? json_encode($json) : '';
            $documents['documenti_contabilita_fe_dati_contratto'] = json_encode($input['documenti_contabilita_fe_dati_contratto']);
            $documents['documenti_contabilita_fe_ordineacquisto'] = json_encode($input['documenti_contabilita_fe_ordineacquisto']);

            //Pagamento
            $documents['documenti_contabilita_accetta_paypal'] = (!empty($input['documenti_contabilita_accetta_paypal']) ? $input['documenti_contabilita_accetta_paypal'] : DB_BOOL_FALSE);
            $documents['documenti_contabilita_split_payment'] = (!empty($input['documenti_contabilita_split_payment']) ? $input['documenti_contabilita_split_payment'] : DB_BOOL_FALSE);

            $documents['documenti_contabilita_centro_di_ricavo'] = (!empty($input['documenti_contabilita_centro_di_ricavo']) ? $input['documenti_contabilita_centro_di_ricavo'] : null);
            $documents['documenti_contabilita_template_pdf'] = (!empty($input['documenti_contabilita_template_pdf']) ? $input['documenti_contabilita_template_pdf'] : null);
            $documents['documenti_contabilita_max_num_articoli'] = (!empty($input['documenti_contabilita_max_num_articoli']) ? $input['documenti_contabilita_max_num_articoli'] : 24);
            $documents['documenti_contabilita_font'] = (!empty($input['documenti_contabilita_font']) ? $input['documenti_contabilita_font'] : "'Inter', sans-serif");
            $documents['documenti_contabilita_font_size'] = (!empty($input['documenti_contabilita_font_size']) ? $input['documenti_contabilita_font_size'] : 14);

            //Importi
            $documents['documenti_contabilita_totale'] = $input['documenti_contabilita_totale'];
            $documents['documenti_contabilita_iva'] = $input['documenti_contabilita_iva'];
            $documents['documenti_contabilita_competenze'] = ($input['documenti_contabilita_competenze']) ?: 0;
            $documents['documenti_contabilita_rivalsa_inps_valore'] = $input['documenti_contabilita_rivalsa_inps_valore'];
            $documents['documenti_contabilita_competenze_lordo_rivalsa'] = $input['documenti_contabilita_competenze_lordo_rivalsa'];
            $documents['documenti_contabilita_cassa_professionisti_valore'] = $input['documenti_contabilita_cassa_professionisti_valore'];
            
            $documents['documenti_contabilita_cassa_professionisti_tipo'] =  (!empty($input['documenti_contabilita_cassa_professionisti_tipo']) ? $input['documenti_contabilita_cassa_professionisti_tipo'] : null);
            $documents['documenti_contabilita_imponibile'] = $input['documenti_contabilita_imponibile'];
            $documents['documenti_contabilita_imponibile_scontato'] = $input['documenti_contabilita_imponibile_scontato'];
            $documents['documenti_contabilita_ritenuta_acconto_valore'] = $input['documenti_contabilita_ritenuta_acconto_valore'];
            $documents['documenti_contabilita_ritenuta_acconto_imponibile_valore'] = $input['documenti_contabilita_ritenuta_acconto_imponibile_valore'];
            $documents['documenti_contabilita_importo_bollo'] = $input['documenti_contabilita_importo_bollo'];
            $documents['documenti_contabilita_applica_bollo'] = (!empty($input['documenti_contabilita_applica_bollo'])) ? $input['documenti_contabilita_applica_bollo'] : DB_BOOL_FALSE;
            $documents['documenti_contabilita_bollo_virtuale'] = (!empty($input['documenti_contabilita_bollo_virtuale'])) ? $input['documenti_contabilita_bollo_virtuale'] : DB_BOOL_FALSE;

            // Michael - 14/02/2023 - Va fatto un json_decode e poi json_encode, in quanto in questo punto si stava ciclando un json (per cui ovvimaente dava errore sul foreach stesso)
            $input['documenti_contabilita_iva_json'] = json_decode($input['documenti_contabilita_iva_json'], true);
            foreach ($input['documenti_contabilita_iva_json'] as $iva_id => $iva) {
                if ($iva_id == 0) {
                    unset($input['documenti_contabilita_iva_json'][$iva_id]);
                }
            }
            $input['documenti_contabilita_iva_json'] = json_encode($input['documenti_contabilita_iva_json']);

            $documents['documenti_contabilita_iva_json'] = $input['documenti_contabilita_iva_json'];
            $documents['documenti_contabilita_imponibile_iva_json'] = $input['documenti_contabilita_imponibile_iva_json'];
            $documents['documenti_contabilita_sconto_percentuale'] = $input['documenti_contabilita_sconto_percentuale'];

            $documents['documenti_contabilita_azienda'] = $input['documenti_contabilita_azienda'];

            $documents['documenti_contabilita_causale_pagamento_ritenuta'] = $input['documenti_contabilita_causale_pagamento_ritenuta'];
            $documents['documenti_contabilita_tipo_ritenuta'] = $input['documenti_contabilita_tipo_ritenuta'];

            if (!empty($input['documento_id'])) {
                $documento_id = $input['documento_id'];

                $documento = $this->apilib->view('documenti_contabilita', $documento_id);
                $this->apilib->edit("documenti_contabilita", $input['documento_id'], $documents); // Come mai è stato commentato e ora si usa update su db diretto? non vanno cosi i post process
            } else {
                $documents['documenti_contabilita_stato_invio_sdi'] = 1;
                $documents['documenti_contabilita_stato'] = 1;

                if (!empty($input['payment_id'])) {
                    $this->session->set_userdata('_payment_id', $input['payment_id']);
                }

                //debug($documents, true);
                $documento = $this->apilib->create('documenti_contabilita', $documents);
                $documento_id = $documento['documenti_contabilita_id'];
                //se ho un lead collegato, lo imposto come 
                if ($documents['documenti_contabilita_extra_param'] != null) {
                    $this->apilib->edit('leads', $documents['documenti_contabilita_extra_param'], [
                        'leads_price' => $documents['documenti_contabilita_totale']
                    ]);
                }


                //MP - 20230608 - Rimosso perchè fa tutto già apilib passandogli sopra l'array con gli id dei documenti... | ME - 202230609 - Ripristinato in quanto la relazione punta con entrambi i campi a documenti_contabilita ed è una cosa che non viene gestita correttamente da APILIB. Di conseguenza si ripristina il vecchio metodo
                // 08-05-2023 rif_doc diventa multplo
                // Imposto documento padre nella relazione
                $rif_docs = array_filter(explode(',', $input['documenti_contabilita_rif_documenti'])) ?? [];
                if (!empty($rif_docs)) {
                    $this->docs->associaDocumenti($documento_id, $rif_docs);
                }
            }
            
            //20230323 - MP - Sopra io sovrascrivo il documento in caso di edit, quindi $documento contiene ancora i dati "ante" modifica. Devo forzare un reload!
            $documento = $this->apilib->view('documenti_contabilita', $documento_id);

            // Genero nome file xml e salvo sul db
            if ($documents['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE && empty($documento['documenti_contabilita_nome_file_xml'])) {
                $settings = $this->contabilita_settings;
                $prefisso = "IT" . $settings['documenti_contabilita_settings_company_vat_number'];
                $xmlfilename = $this->docs->generateXmlFilename($prefisso, $documento_id);
                $documents['documenti_contabilita_nome_file_xml'] = $xmlfilename;
            } else {
                $xmlfilename = null;
            }

            //Imposto lo stato pagamento a non pagato per poi modificarlo in caso nel foreach scadenze

            $scadenze_ids = [-1];

            foreach ($input['scadenze'] as $key => $scadenza) {
                if (abs($scadenza['documenti_contabilita_scadenze_ammontare']) > 0) {
                    if (!empty($scadenza['documenti_contabilita_scadenze_scadenza'])) {
                        if ($this->db->dbdriver != 'postgre') {
                            $date = DateTime::createFromFormat("d/m/Y", $scadenza['documenti_contabilita_scadenze_scadenza']);
                            $scadenza['documenti_contabilita_scadenze_scadenza'] = $date->format('Y-m-d H:i:s');
                        } else {
                            //$data_emissione = $input['documenti_contabilita_data_emissione'];
                            $date = DateTime::createFromFormat("d/m/Y", $scadenza['documenti_contabilita_scadenze_scadenza']);
                            $scadenza['documenti_contabilita_scadenze_scadenza'] = $date->format('Y-m-d H:i:s');
                        }
                    }

                    if (!empty($scadenza['documenti_contabilita_scadenze_id'])) {
                        $scadenze_ids[] = $scadenza['documenti_contabilita_scadenze_id'];
                        $this->apilib->edit('documenti_contabilita_scadenze', $scadenza['documenti_contabilita_scadenze_id'], [
                            'documenti_contabilita_scadenze_ammontare' => $scadenza['documenti_contabilita_scadenze_ammontare'],
                            'documenti_contabilita_scadenze_scadenza' => $scadenza['documenti_contabilita_scadenze_scadenza'],
                            'documenti_contabilita_scadenze_saldato_con' => $scadenza['documenti_contabilita_scadenze_saldato_con'] ?? null,
                            'documenti_contabilita_scadenze_data_saldo' => ($scadenza['documenti_contabilita_scadenze_data_saldo']) ?: null,
                            'documenti_contabilita_scadenze_documento' => $documento_id,
                        ]);
                    } else {
                        $scadenze_ids[] = $this->apilib->create('documenti_contabilita_scadenze', [
                            'documenti_contabilita_scadenze_ammontare' => $scadenza['documenti_contabilita_scadenze_ammontare'],
                            'documenti_contabilita_scadenze_scadenza' => $scadenza['documenti_contabilita_scadenze_scadenza'],
                            'documenti_contabilita_scadenze_saldato_con' => $scadenza['documenti_contabilita_scadenze_saldato_con'] ?? null,
                            'documenti_contabilita_scadenze_data_saldo' => ($scadenza['documenti_contabilita_scadenze_data_saldo']) ?: null,
                            'documenti_contabilita_scadenze_documento' => $documento_id,
                        ], false);
                    }
                } else {
                    unset($input['scadenze'][$key]);
                }
            }

            $this->db->query("DELETE FROM documenti_contabilita_scadenze where documenti_contabilita_scadenze_documento = $documento_id AND documenti_contabilita_scadenze_id NOT IN (" . implode(',', $scadenze_ids) . ")");
            $this->mycache->clearCacheTags(['documenti_contabilita_scadenze', 'documenti_contabilita']);

            // **************** PRODOTTI ****************** //
            if (!empty($input['documento_id'])) {
                //Rimosso perchè ora fa update se siamo in edit anche di ogni riga articolo come per le scadenze
                //$this->db->delete('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $input['documento_id']]);
            }

            $raw_iva = $this->db->get('iva')->result_array();
            $iva = array_combine(
                array_map(function ($_iva) {
                    return $_iva['iva_id'];
                }, $raw_iva),
                array_map(function ($_iva) {
                    return $_iva['iva_valore'];
                }, $raw_iva)
            );
            //debug($iva,true);
            $articoli_ids = ['-1'];
            $campi_personalizzati = $this->apilib->search('campi_righe_articoli');


            //Arrivato qui, riordino i prodotti in base al campo position
            usort($input['products'], function ($p1, $p2) {
                $a = $p1['documenti_contabilita_articoli_position'];
                $b = $p2['documenti_contabilita_articoli_position'];
                if ($a == $b) {
                    return 0;
                }
                return ($a < $b) ? -1 : 1;
            });
            $prodotti_movimentabili = false;
            $padri_da_aggiornare = [];
            foreach ($input['products'] as $prodotto) {
                if ($prodotto['documenti_contabilita_articoli_prodotto_id'] && $ext_prodotto = $this->apilib->view($entita_prodotti, $prodotto['documenti_contabilita_articoli_prodotto_id'])) {
                    if (!empty($ext_prodotto['fw_products_stock_management'])) {
                        $prodotti_movimentabili = true;
                    }
                }
                foreach ($campi_personalizzati as $campo) {
                    $prodotto[$campo['campi_righe_articoli_map_to']] = $prodotto[$campo['campi_righe_articoli_campo']];
                    unset($prodotto[$campo['campi_righe_articoli_campo']]);
                }
                //unset($prodotto['documenti_contabilita_articoli_id']);
                $prodotto['documenti_contabilita_articoli_documento'] = $documento_id;
                //Mi arriva l'id dell'iva, quindi recupero il valore
                //                debug($iva);
                //                debug($prodotto);
                if (!empty($prodotto['documenti_contabilita_articoli_iva_id'])) {
                    $prodotto['documenti_contabilita_articoli_iva_perc'] = $iva[$prodotto['documenti_contabilita_articoli_iva_id']];
                }

                $prodotto['documenti_contabilita_articoli_applica_sconto'] = (empty($prodotto['documenti_contabilita_articoli_applica_sconto'])) ? DB_BOOL_FALSE : $prodotto['documenti_contabilita_articoli_applica_sconto'];
                $prodotto['documenti_contabilita_articoli_applica_ritenute'] = (empty($prodotto['documenti_contabilita_articoli_applica_ritenute'])) ? DB_BOOL_FALSE : $prodotto['documenti_contabilita_articoli_applica_ritenute'];
                $prodotto['documenti_contabilita_articoli_riga_desc'] = (empty($prodotto['documenti_contabilita_articoli_riga_desc'])) ? DB_BOOL_FALSE : $prodotto['documenti_contabilita_articoli_riga_desc'];
                if (!empty($prodotto['documenti_contabilita_articoli_attributi_sdi'])) {
                    $prodotto['documenti_contabilita_articoli_attributi_sdi'] = base64_decode($prodotto['documenti_contabilita_articoli_attributi_sdi']);
                }

                if (!empty($input['documenti_contabilita_aggiungi_articoli']) && $input['documenti_contabilita_aggiungi_articoli'] == DB_BOOL_TRUE && empty($prodotto['documenti_contabilita_articoli_id'])) {
                    //SENZA mappature cerco l'unità di misura corrispondente
                    $misura = $this->apilib->searchFirst('fw_products_unita_misura', ['fw_products_unita_misura_value' => $prodotto['documenti_contabilita_articoli_unita_misura']]);
                    if ($misura) {
                        $misura_articolo = $misura['fw_products_unita_misura_id'];
                    }
                    //calcolo prezzo ivato
                    $iva_articolo = $prodotto['documenti_contabilita_articoli_prezzo'] / 100 * $prodotto['documenti_contabilita_articoli_iva_perc'];
                    $nuovo_prodotto = [
                        $campo_codice_prodotto => $prodotto['documenti_contabilita_articoli_codice'],

                        $campo_descrizione_prodotto => $prodotto['documenti_contabilita_articoli_descrizione'],
                        $campo_prezzo_prodotto => ($prodotto['documenti_contabilita_articoli_prezzo']) ?: '0.0',
                        $campo_preview_prodotto => $prodotto['documenti_contabilita_articoli_name'],
                        $campo_iva_prodotto => $prodotto['documenti_contabilita_articoli_iva_id'],
                        $campo_tipo_prodotto => '1',
                        'fw_products_unita_misura' => $misura_articolo,
                        'fw_products_sell_price_tax_included' => ($prodotto['documenti_contabilita_articoli_prezzo'] + $iva_articolo),
                    ];
                    try {
                        $prodotto_id = $this->apilib->create($entita_prodotti, $nuovo_prodotto, false);
                        $prodotto['documenti_contabilita_articoli_prodotto_id'] = $prodotto_id;
                    } catch (Exception $e) {
                        log_message("error", "errore creazione prodotto da articolo contabilita -> {$e->getMessage()} | " . json_encode($nuovo_prodotto));
                    }
                }

                if (!empty($prodotto['documenti_contabilita_articoli_id'])) {
                    $articoli_ids[] = $prodotto['documenti_contabilita_articoli_id'];
                    $this->apilib->edit('documenti_contabilita_articoli', $prodotto['documenti_contabilita_articoli_id'], $prodotto);
                } else {
                    if (!empty($prodotto['documenti_contabilita_articoli_codice']) || !empty($prodotto['documenti_contabilita_articoli_name']) || !empty($prodotto['documenti_contabilita_articoli_descrizione'])) {
                        //Almeno il name, codice o descrizione ci deve essere, altrimenti devo considerarla come riga vuota.
                        $articoli_ids[] = $this->apilib->create("documenti_contabilita_articoli", $prodotto, false);
                    }
                }


                if ($prodotto['documenti_contabilita_articoli_rif_riga_articolo'] && $riga_padre = $this->db->get_where('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $prodotto['documenti_contabilita_articoli_rif_riga_articolo']])->row_array()) {
                    if (!in_array($riga_padre['documenti_contabilita_articoli_documento'], $padri_da_aggiornare)) {
                        $padri_da_aggiornare[] = $riga_padre['documenti_contabilita_articoli_documento'];

                    }


                }

                if ($documents['documenti_contabilita_tipo'] == 1 && $prodotto['documenti_contabilita_articoli_rif_riga_articolo']) {
                    $this->db->where('documenti_contabilita_articoli_id', $prodotto['documenti_contabilita_articoli_rif_riga_articolo'])->update('documenti_contabilita_articoli', [
                        'documenti_contabilita_articoli_evaso_in_fattura' => DB_BOOL_TRUE,
                    ]);
                }
            }
            //debug($padri_da_aggiornare,true);
            foreach ($padri_da_aggiornare as $padre) {
                
                $this->docs->aggiornaStatoDocumento($padre, $documents['documenti_contabilita_tipo']);
            }
            //@TODO gestire eventuali ordini multi commessa ed eventuali aggiunte/modifiche al documento
            if (!empty($input['documenti_contabilita_commessa']) && empty($input['documento_id'])) {
                //if ($this->session->userdata('commessa_id') && empty($input['documento_id'])) {
                if ($documents['documenti_contabilita_tipo'] == 5) {
                    //se è un ordine cliente, associo la commessa al documento
                    $this->apilib->create("documenti_contabilita_commesse", [
                        'documenti_contabilita_commesse_projects_id' => $input['documenti_contabilita_commessa'],
                        'documenti_contabilita_commesse_documenti_contabilita_id' => $documento_id,
                    ]);
                } elseif ($documents['documenti_contabilita_tipo'] == 6) {

                    //se ho la commessa, imposto per ogni articolo il collegamento con l'ordine fornitore
                    $articoli_ordine = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id]);
                    foreach ($articoli_ordine as $articolo_ordine) {
                        $this->apilib->create("projects_items", [
                            'projects_items_product' => $articolo_ordine['documenti_contabilita_articoli_id'],
                            'projects_items_project' => $input['documenti_contabilita_commessa'],
                        ]);
                    }
                } elseif ($documents['documenti_contabilita_tipo'] == 14) {

                    //se ho la commessa, imposto per ogni articolo il collegamento con l'ordine interno
                    $articoli_ordine = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id]);
                    foreach ($articoli_ordine as $articolo_ordine) {
                        $this->apilib->create("projects_items", [
                            'projects_items_product' => $articolo_ordine['documenti_contabilita_articoli_id'],
                            'projects_items_project' => $input['documenti_contabilita_commessa'],
                        ]);
                    }
                }

            }

            //debug($articoli_ids, true);
            $this->db->query("DELETE FROM documenti_contabilita_articoli where documenti_contabilita_articoli_documento = $documento_id AND documenti_contabilita_articoli_id NOT IN (" . implode(',', $articoli_ids) . ")");
            $this->mycache->clearCacheTags(['documenti_contabilita_articoli', 'documenti_contabilita']);

            //$this->docs->calcolaQuantitaEvase($documento_id);

            if ($documents['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
                $this->docs->generate_xml($documento, $xmlfilename);
                //die('test');
            } else {
                // Storicizzo PDF
                if ($documents['documenti_contabilita_template_pdf']) {
                    $template = $this->apilib->view('documenti_contabilita_template_pdf', $documents['documenti_contabilita_template_pdf']);

                    // Se caricato un file che contiene un html da priorità a quello
                    if (!empty($template['documenti_contabilita_template_pdf_file_html']) && file_exists(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html'])) {
                        $content_html = file_get_contents(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html']);
                    } else {
                        $content_html = $template['documenti_contabilita_template_pdf_html'];
                    }

                    $pdfFile = $this->layout->generate_pdf($content_html, "portrait", "", ['documento_id' => $documento_id], 'contabilita', true);
                } else {
                    $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $documento_id], 'contabilita');
                    //debug($input,true);
                }


                // Storicizzo la copia di cortesia del PDF su file
                if (file_exists($pdfFile)) {
                    $pdf_file_name = $this->docs->generate_beautify_name($documento);

                    $content = file_get_contents($pdfFile, true);
                    $folder = "modules_files/contabilita/pdf_cortesia";
                    $filepath = $this->docs->salva_file_fisico($pdf_file_name, $folder, $content);
                    $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file_pdf' => $filepath]);
                }

                // Deprecato
                // if (file_exists($pdfFile)) {
                //     $contents = file_get_contents($pdfFile, true);
                //     $pdf_b64 = base64_encode($contents);
                //     $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file' => $pdf_b64]);
                // }
            }

            $this->mycache->clearCache();
            $return = array('status' => 1, 'txt' => base_url('main/layout/contabilita_dettaglio_documento/' . $documento_id . '?first_save=1'));
            if (empty($input['documento_id']) && !empty($spesa_id)) {
                $spesa = $this->apilib->view('spese', $spesa_id);
                if ($spesa['spese_modello_prima_nota']) {
                    //Se entro qua vuol dire che ho assegnato un modello... chiedo se si vuole andare in prima nota o tornare all'elenco spese
                    $return =
                        array(
                            'status' => 9,
                            'txt' => "

                    if (confirm('Vuoi procedere anche con la registrazione in prima nota?') == true) {
                        location.href='" . base_url("main/layout/prima-nota?modello={$spesa['spese_modello_prima_nota']}&spesa_id={$spesa_id}") . "';
                    } else {
                        location.href='" . base_url('main/layout/contabilita_dettaglio_documento/' . $documento_id . '?first_save=1') . "';
                    }
                "
                        );
                }
            } elseif ($prodotti_movimentabili && $this->datab->module_installed('magazzino')) {
                $magazzino_settings = $this->apilib->searchFirst('magazzino_settings');

                if (!empty($magazzino_settings['magazzino_settings_movimenta_per']) && in_array($input['documenti_contabilita_tipo'], array_keys($magazzino_settings['magazzino_settings_movimenta_per']))) {
                    $movimentato = false;
                    if (!empty($rif_docs)) {
                        foreach ($rif_docs as $doc_ref_id) {
                            $movimentato = $this->db->get_where('movimenti', ['movimenti_documento_id' => $doc_ref_id])->num_rows() > 0;
                            if ($movimentato) {
                                break;
                            }
                        }
                    }

                    if (!$movimentato) {
                        //Se è un documento che può essere movimentato, chiedo se si vuole procedere a movimentare la merce   
                        $return = [
                            'status' => 9,
                            'txt' => "if (confirm('Vuoi creare un movimento di magazzino per questo ordine?') == true) {
                                location.href='" . base_url("main/layout/nuovo_movimento?documenti_id={$documento_id}") . "';
                            } else {
                                location.href='" . base_url('main/layout/contabilita_dettaglio_documento/' . $documento_id . '?first_save=1') . "';
                            }"
                        ];
                    }
                }
            }

            echo json_encode($return);
        }
    }

    public function edit_scadenze()
    {
        $input = $this->input->post();
        $documento_id = $input['documento_id'];

        //$this->db->delete('documenti_contabilita_scadenze', ['documenti_contabilita_scadenze_documento' => $documento_id]);
        $scadenze_ids = [-1];
        foreach ($input['scadenze'] as $key => $scadenza) {
            if ($scadenza['documenti_contabilita_scadenze_ammontare'] > 0) {
                if (!empty($scadenza['documenti_contabilita_scadenze_id'])) {
                    $scadenze_ids[] = $scadenza['documenti_contabilita_scadenze_id'];
                    $this->apilib->edit('documenti_contabilita_scadenze', $scadenza['documenti_contabilita_scadenze_id'], [
                        'documenti_contabilita_scadenze_ammontare' => $scadenza['documenti_contabilita_scadenze_ammontare'],
                        'documenti_contabilita_scadenze_scadenza' => $scadenza['documenti_contabilita_scadenze_scadenza'],
                        'documenti_contabilita_scadenze_saldato_con' => $scadenza['documenti_contabilita_scadenze_saldato_con'],
                        'documenti_contabilita_scadenze_saldato_su' => $scadenza['documenti_contabilita_scadenze_saldato_su'],
                        'documenti_contabilita_scadenze_data_saldo' => ($scadenza['documenti_contabilita_scadenze_data_saldo']) ?: null,
                        'documenti_contabilita_scadenze_documento' => $documento_id,
                    ]);
                } else {
                    $scadenze_ids[] = $this->apilib->create('documenti_contabilita_scadenze', [
                        'documenti_contabilita_scadenze_ammontare' => $scadenza['documenti_contabilita_scadenze_ammontare'],
                        'documenti_contabilita_scadenze_scadenza' => $scadenza['documenti_contabilita_scadenze_scadenza'],
                        'documenti_contabilita_scadenze_saldato_con' => $scadenza['documenti_contabilita_scadenze_saldato_con'],
                        'documenti_contabilita_scadenze_saldato_su' => $scadenza['documenti_contabilita_scadenze_saldato_su'],
                        'documenti_contabilita_scadenze_data_saldo' => ($scadenza['documenti_contabilita_scadenze_data_saldo']) ?: null,
                        'documenti_contabilita_scadenze_documento' => $documento_id,
                    ], false);
                }
            } else {
                unset($input['scadenze'][$key]);
            }
        }

        $this->db->query("DELETE FROM documenti_contabilita_scadenze where documenti_contabilita_scadenze_documento = $documento_id AND documenti_contabilita_scadenze_id NOT IN (" . implode(',', $scadenze_ids) . ")");
        $this->mycache->clearCache();

        //echo json_encode(array('status' => 2));
        $this->load->view('layout/json_return', ['json' => json_encode(array('status' => 2))]);
    }

    public function autocomplete($entity = null)
    {
        if (!$entity) {
            echo json_encode(['count_total' => 0, 'results' => []]);
            die();
        }
        $input = $this->input->get_post('search');
        $type = $this->input->get_post('type');
        //debug($type,true);
        $count_total = 0;

        $input = trim($input);
        if (empty($input) or strlen($input) < 2) {
            echo json_encode(['count_total' => -1]);
            return;
        }

        $results = [];

        $input = strtolower($input);

        $input = str_ireplace("'", "''", $input);

        $res = "";

        //L'entità clienti è configurale, come anche i vari campi di preview...
        $mappature = $this->docs->getMappatureAutocomplete();
        extract($mappature);

        $entita_fornitori = 'suppliers';

        if ($entity == $entita_prodotti) {
            $campo_codice = $campo_codice_prodotto;
            $campo_preview = $campo_preview_prodotto;
            $where = ["( (LOWER($campo_preview) LIKE '%{$input}%' OR $campo_preview LIKE '{$input}%') OR (LOWER($campo_codice) LIKE '%{$input}%' OR $campo_codice LIKE '{$input}%') )"];

            if (!empty($campo_gestione_giacenza_prodotto)) {
                $where[] = "$campo_gestione_giacenza_prodotto => '1'";
            }

            $res = $this->apilib->search($entity, $where, 20);

            // if ($entita_prodotti == 'fw_products') {
            //     $products_ids = array_key_map($res, 'fw_products_id');
            //     $price_lists = $this->apilib->search('price_list', ['price_list_product' => $products_ids]);
            //     foreach ($price_lists as $price_list) {
            //         foreach ($res as $key => $product) {
            //             if ($product['fw_products_id'] == $price_list['price_list_product']) {
            //                 $res[$key]['price_list'][$price_list['price_list_label']] = $price_list['price_list_price'];
            //             }
            //         }
            //     }
            //     debug($res,true);
            // }

            //die("(LOWER(fw_products_name) LIKE '%{$input}%' OR fw_products_sku LIKE '{$input}%' OR CAST(fw_products_ean AS CHAR) = '{$input}')");
        } elseif ($entity == $entita_clienti) {
            if ($clienti_tipo && $type) {
                $where = ["(LOWER({$clienti_codice}) LIKE '%{$input}%' OR LOWER({$clienti_ragione_sociale}) LIKE '%{$input}%' OR LOWER({$clienti_nome}) LIKE '%{$input}%' OR LOWER({$clienti_cognome}) LIKE '%{$input}%') AND ({$clienti_tipo} IN ($type))"];
                //debug($where, true);
                $res = $this->apilib->search($entita_clienti, $where, 10, 0, "{$clienti_codice},{$clienti_ragione_sociale},{$clienti_cognome},{$clienti_nome}");
            } else {
                $res = $this->apilib->search($entita_clienti, ["(LOWER({$clienti_codice}) LIKE '%{$input}%' OR LOWER({$clienti_ragione_sociale}) LIKE '%{$input}%' OR LOWER({$clienti_nome}) LIKE '%{$input}%' OR LOWER({$clienti_cognome}) LIKE '%{$input}%')"], 10, 0, "{$clienti_codice},{$clienti_ragione_sociale},{$clienti_cognome},{$clienti_nome}");
            }

            if (!empty($res) && !empty($this->db->table_exists('customers_shipping_address'))) {
                // michael - 24/03/2023 - metto manualmente customers_shipping_address come suggerito da matteo
                foreach ($res as $index => $row) {
                    if (!empty($row['customers_id'])) {
                        $sedi = $this->apilib->search('customers_shipping_address', ['customers_shipping_address_customer_id' => $row['customers_id']]);

                        $res[$index]['sedi'] = $sedi;
                    }
                }
            }
        } elseif ($entity == $entita_fornitori) {
            $res = $this->apilib->search($entita_clienti, ["(LOWER({$clienti_codice}) LIKE '%{$input}%' OR LOWER({$clienti_ragione_sociale}) LIKE '%{$input}%' OR LOWER({$clienti_nome}) LIKE '%{$input}%' OR LOWER({$clienti_cognome}) LIKE '%{$input}%') AND ({$clienti_tipo} = '2' OR  {$clienti_tipo} = '3' )"], 10, 0, "{$clienti_codice},{$clienti_ragione_sociale},{$clienti_cognome},{$clienti_nome}");
        } elseif ($entity == $entita_vettori) {
            $where = ["(LOWER(vettori_ragione_sociale) LIKE '%{$input}%') ORDER BY vettori_ragione_sociale ASC"];
            if ($type == 'match') {
                $where = ["(LOWER(vettori_ragione_sociale) = '{$input}') ORDER BY vettori_ragione_sociale ASC"];
            }

            $res = $this->apilib->search($entita_vettori, $where);
        }

        if ($res) {
            $count_total = count($res);
            $results = [
                'data' => $res,
            ];
        }

        echo json_encode(['count_total' => $count_total, 'results' => $results]);
    }

    public function getTemplatePdf($azienda)
    {
        echo json_encode($this->apilib->search('documenti_contabilita_template_pdf', ["documenti_contabilita_template_pdf_azienda = '$azienda' OR documenti_contabilita_template_pdf_azienda IS NULL OR documenti_contabilita_template_pdf_azienda = ''"]));
    }

    public function numeroSucessivo($azienda, $tipo, $serie = null)
    {
        $data = $this->input->post('data_emissione');
        echo $this->docs->numero_sucessivo($azienda, $tipo, $serie, $data);
    }

    public function uploadImage($prodotto)
    {
        if (!isset($_FILES['prodotti_immagini_immagine']) && isset($_FILES['file'])) {
            $_FILES['prodotti_immagini_immagine'] = $_FILES['file'];
        }

        if (!isset($_FILES['prodotti_immagini_immagine']['type']) or !in_array($_FILES['prodotti_immagini_immagine']['type'], ['image/jpeg', 'image/png'])) {
            die('Tipo file non supportato');
        }

        unset($_FILES['file']);

        try {
            $newMedia = $this->apilib->create('prodotti_immagini', ['prodotti_immagini_prodotto' => $prodotto]);
        } catch (Exception $ex) {
            set_status_header(500);
            die($ex->getMessage());
        }

        echo json_encode($newMedia);
    }

    public function ajax_get_templates($template_id, $documento_id = null)
    {
        $result = $this->apilib->view('documenti_contabilita_mail_template', $template_id);

        if (!empty($documento_id)) {
            $documento = $this->apilib->view('documenti_contabilita', $documento_id);

            if ($documento['documenti_contabilita_customer_id']) {
                $mappature = $this->docs->getMappature();
                extract($mappature);

                $destinatario_email = '';
                $destinatario = $this->apilib->view($entita_clienti, $documento['documenti_contabilita_customer_id']);
                $last_used = $this->db->query("SELECT documenti_contabilita_mail_destinatario as email FROM documenti_contabilita_mail WHERE (
                    documenti_contabilita_mail_documento_id IN (
                        SELECT documenti_contabilita_id FROM documenti_contabilita WHERE documenti_contabilita_customer_id = '{$documento['documenti_contabilita_customer_id']}'
                        )
                    ) ORDER BY documenti_contabilita_mail_id DESC");
                if ($last_used->num_rows() >= 1) {
                    $destinatario[$clienti_email] = $last_used->row()->email;
                }


                if ($destinatario[$clienti_email]) {
                    $destinatario_email = $destinatario[$clienti_email];
                }

                // michael - 07/07/2022 - commentato perchè è un po' problematico per la fatturazione
                // if ($this->module->moduleExists('customers') && $entita_clienti === 'customers' && $this->db->table_exists('customers_contacts') && $this->db->field_exists('customers_contacts_refer_to', 'customers_contacts')) {
                //     $contatto = $this->apilib->searchFirst('customers_contacts', ['customers_contacts_customer_id' => $documento['documenti_contabilita_customer_id'], 'customers_contacts_refer_to' => '1']);

                //     if (!empty($contatto) && !empty($contatto['customers_contacts_email'])) {
                //         $destinatario_email = $contatto['customers_contacts_email'];
                //     }
                // }

                if ($destinatario_email) {
                    $result['email_destinatario'] = $destinatario_email;
                }
            }
        }

        echo json_encode($result);
    }

    public function tassoDiCambio($valuta_id)
    {
        $settings = $this->apilib->searchFirst('documenti_contabilita_settings');
        $tasso = $this->apilib->searchFirst('tassi_di_cambio', [
            'tassi_di_cambio_valuta_2' => $valuta_id,
            'tassi_di_cambio_valuta_1' => $settings['documenti_contabilita_settings_valuta_base'],
        ], 0, 'tassi_di_cambio_creation_date', 'DESC');

        if (empty($tasso)) {
            echo json_encode([]);
        } else {
            echo json_encode($tasso);
        }
    }

    public function print_pdf($documento_id, $field_name = 'documenti_contabilita_file_pdf')
    {
        $documento = $this->apilib->view('documenti_contabilita', $documento_id);

        if ($documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
            $field_name = 'documenti_contabilita_file_pdf';
        }

        //TODO: non deve generare documento_pdf ma valutare se il documento ha un template associato e usare quello (occhio al controllo tra html e file di template caricato)
        if (!empty($documento_id)) {
            $html = '';
            if (!empty($this->input->get('view'))) {
                $html = $this->load->module_view('contabilita/views', $this->input->get('view'), ['documento_id' => $documento_id], true);
            } elseif (!empty($this->input->get('tpl'))) {
                $tpl_id = $this->input->get('tpl');
                $tpl = $this->db->get_where('documenti_contabilita_template_pdf', ['documenti_contabilita_template_pdf_id' => $tpl_id])->row_array();

                if (!empty($tpl['documenti_contabilita_template_pdf_file_html'])) {
                    $html = file_get_contents(FCPATH . 'uploads/' . $tpl['documenti_contabilita_template_pdf_file_html']);
                } elseif (!empty($tpl['documenti_contabilita_template_pdf_html'])) {
                    $html = $tpl['documenti_contabilita_template_pdf_html'];
                }
            }
            $html = trim($html);

            if (empty($html)) {
                $html = $this->load->module_view('contabilita/views', 'documento_pdf', ['documento_id' => $documento_id], true);
            }

            $pdfFile = $this->layout->generate_pdf($html, 'portrait', '', [
                'documento_id' => $documento_id,
            ], 'contabilita', true);

            $contents = file_get_contents($pdfFile, true);

            if ($this->input->get('html')) {
                die($contents);
            }

            header('Content-Type: application/pdf');
            header('Content-disposition: inline; filename="' . $documento['documenti_contabilita_tipo_value'] . '_' . $documento['documenti_contabilita_numero'] . $documento['documenti_contabilita_serie'] . '.pdf"');

            echo $contents;
        } else {
            echo 'Errore, documenti non esistente';
        }
    }

    // IN TEORIA DEPRECATO --
    // public function xml_fattura_elettronica($id, $reverse = false)
    // {
    //     //Ho dovuto centralizzare questo metodo perchè utilizzato anche da altre parti...
    //     $pagina = $this->docs->get_content_fattura_elettronica($id, $reverse);
    //     header("Content-Type:text/xml");
    //     echo $pagina;
    // }
    public function visualizza_formato_compatto($id)
    {
        $documento = $this->apilib->view('documenti_contabilita', $id);
        $file_xml = "uploads/" . $documento['documenti_contabilita_file_xml'];

        if (empty($documento['documenti_contabilita_file_xml']) || !file_exists($file_xml)) {
            log_message('error', "Formato compatto non visualizzabile perchè il file xml non è salvato o non esiste il file fisico.");
            return false;
        }

        $reverse = in_array($documento['documenti_contabilita_tipologie_fatturazione_codice'], ['TD17', 'TD18', 'TD19']);
        $content_xml = file_get_contents($file_xml);
        $content_xml = preg_replace('/<\?xml(.*?)\?>/', '', $content_xml);

        if ($documento['documenti_contabilita_importata_da_xml']) {
            $pos = strpos($content_xml, '<FatturaElettronica xmlns:');
            if ($pos !== false) {
                $content_xml = substr_replace($content_xml, '<p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" versione="FPR12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 https://www.fatturapa.gov.it/export/documenti/fatturapa/v1.2.1/Schema_del_file_xml_FatturaPA_versione_1.2.1.xsd">', 0, $pos + strlen('<FatturaElettronica xmlns:'));
                $content_xml = str_ireplace('</FatturaElettronica>', '</p:FatturaElettronica>', $content_xml);
            }
        }

        header("Content-Type:text/xml");
        $this->load->view('contabilita/pdf/visualizzazione_compatta', ['xml' => $content_xml]);
    }


    public function visualizza_formato_completo($id)
    {
        $documento = $this->apilib->view('documenti_contabilita', $id);
        $reverse = in_array($documento['documenti_contabilita_tipologie_fatturazione_codice'], ['TD17', 'TD18', 'TD19']);
        $this->load->helper('download');
        $general_settings = $this->apilib->searchFirst('documenti_contabilita_general_settings');
        $xsl_url_ordinaria = $general_settings['documenti_contabilita_general_settings_xsl_fattura_ordinaria'];
        $xsl_url_pa = $general_settings['documenti_contabilita_general_settings_xsl_fattura_pa'];

        $physicalDir = FCPATH . "application/modules/contabilita/assets/uploads/";

        // Verifico se è per soggetti privati (e aziende) oppure pubblica amministrazione perche hanno due xslt diversi
        if ($documento['documenti_contabilita_tipo_destinatario'] == 3) {
            $xsl_url = $xsl_url_pa;
            $file_xsl = 'fattura_completa_pa.xsl';
            $tmpXsl = $physicalDir . $file_xsl;
        } else {
            $xsl_url = $xsl_url_ordinaria;
            $file_xsl = 'fattura_completa_ordinaria.xsl';
            $tmpXsl = $physicalDir . $file_xsl;
        }

        // Se non ce foglio xsl salvarlo.. Per modificare il file caricarlo sui general settings.
        if (!file_exists($tmpXsl)) {
            log_message('debug', "XSL Foglio di stile fattura elettronica non trovato, lo scarico e lo salvo.");
            file_put_contents($tmpXsl, file_get_contents($xsl_url));
        } else {
            log_message('debug', "XSL Foglio di stile fattura elettronica trovato correttamente: " . $tmpXsl);
        }


        $file_xml = "uploads/" . $documento['documenti_contabilita_file_xml'];

        $content_xml = file_get_contents($file_xml);
        $content_xml = preg_replace('/<\?xml(.*?)\?>/', '', $content_xml);

        if ($documento['documenti_contabilita_importata_da_xml']) {
            $pos = strpos($content_xml, '<FatturaElettronica xmlns:');
            if ($pos !== false) {
                $content_xml = substr_replace($content_xml, '<p:FatturaElettronica xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:p="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" versione="FPR12" xsi:schemaLocation="http://ivaservizi.agenziaentrate.gov.it/docs/xsd/fatture/v1.2 https://www.fatturapa.gov.it/export/documenti/fatturapa/v1.2.1/Schema_del_file_xml_FatturaPA_versione_1.2.1.xsd">', 0, $pos + strlen('<FatturaElettronica xmlns:'));
                $content_xml = str_ireplace('</FatturaElettronica>', '</p:FatturaElettronica>', $content_xml);
            }
        }

        header("Content-Type:text/xml");
        $this->load->view('contabilita/pdf/visualizzazione_completa', ['xml' => $content_xml, 'file_xsl' => $file_xsl]);
    }

    //20220316 - MP - Deprecato a favore del nuovo metodo con generazione a runtime da xsl
    // public function _____visualizza_fattura_elettronica($id)
    // {
    // die('DEPRECATO');

    // $this->load->helper('download');

    // $dati['fattura'] = $this->apilib->view('documenti_contabilita', $id);

    // $settings = $this->apilib->searchFirst('documenti_contabilita_settings');
    // $general_settings = $this->apilib->searchFirst('documenti_contabilita_general_settings');
    // $xsl_url_ordinaria = $general_settings['documenti_contabilita_general_settings_xsl_fattura_ordinaria'];
    // $xsl_url_pa = $general_settings['documenti_contabilita_general_settings_xsl_fattura_pa'];

    // $filename = date('Ymd-H-i-s');
    // $physicalDir = FCPATH . "uploads/";

    // // Create a temporary file with the view html
    // $tmpHtml = "{$physicalDir}/{$filename}.html";
    // $tmpXml = "{$physicalDir}/{$filename}.xml";

    // // Verifico se è per soggetti privati (e aziende) oppure pubblica amministrazione perche hanno due xslt diversi
    // if ($dati['fattura']['documenti_contabilita_tipo_destinatario'] == 3) {
    // $xsl_url = $xsl_url_pa;
    // $tmpXsl = $physicalDir . basename($xsl_url_pa);
    // } else {
    // $xsl_url = $xsl_url_ordinaria;
    // $tmpXsl = $physicalDir . basename($xsl_url_ordinaria);
    // }

    // if (!is_dir($physicalDir)) {
    // mkdir($physicalDir, 0755, true);
    // }

    // // Se non ce foglio xsl salvarlo.. Per modificare il file caricarlo sui general settings.
    // if (!file_exists($tmpXsl)) {
    // log_message('debug', "XSL Foglio di stile fattura elettronica non trovato, lo scarico e lo salvo.");
    // file_put_contents($tmpXsl, file_get_contents($xsl_url));
    // } else {
    // log_message('debug', "XSL Foglio di stile fattura elettronica trovato correttamente: " . $tmpXsl);
    // }
    // // Scarico sempre il file di stile
    // $arrContextOptions = array(
    // "ssl" => array(
    // "verify_peer" => false,
    // "verify_peer_name" => false,
    // ),
    // );

    // //file_put_contents($tmpXsl, file_get_contents("https://www.fatturapa.gov.it/export/documenti/fatturapa/v1.2/fatturaordinaria_v1.2.xsl", false, stream_context_create($arrContextOptions)));

    // // Creo xml temporaneo
    // file_put_contents($tmpXml, base64_decode($dati['fattura']['documenti_contabilita_file']), LOCK_EX);

    // // Tento di generarlo
    // //echo "xsltproc -o {$tmpHtml} {$tmpXsl} {$tmpXml}"; // Per debug comando decommentare
    // exec("xsltproc -o {$tmpHtml} {$tmpXsl} {$tmpXml}");

    // // Check se html generato correttamente lo mostro altrimenti carico xml perchè potrebbe avere dei die con gli errori
    // if (file_exists($tmpHtml)) {
    // // Storicizzo la preview
    // $pdfFile = $this->layout->generate_pdf(file_get_contents($tmpHtml), "portrait", "", [], null, true);
    // $this->apilib->edit("documenti_contabilita", $id, ['documenti_contabilita_file_preview_xml' => base64_encode(file_get_contents($pdfFile))]);

    // header('Content-Type: application/pdf');
    // echo file_get_contents($pdfFile);
    // } else {
    // echo file_get_contents($tmpXml);
    // }
    // }

    // Deprecato
    // public function download_fattura_elettronica($id)
    // {
    // $this->load->helper('download');

    // $dati['fattura'] = $this->apilib->view('documenti_contabilita', $id);
    // $settings = $this->contabilita_settings;

    // $file = base64_decode($dati['fattura']['documenti_contabilita_file']);

    // $prefisso = "IT" . $settings['documenti_contabilita_settings_company_vat_number'];

    // if (empty($dati['fattura']['documenti_contabilita_nome_file_xml'])) {
    // $xmlfilename = $this->docs->generateXmlFilename($prefisso, $dati['fattura']['documenti_contabilita_id']);
    // } else {
    // $xmlfilename = $dati['fattura']['documenti_contabilita_nome_file_xml'];
    // }
    // force_download($xmlfilename, $file);
    // }

    public function generaRiba()
    {
        $ids = json_decode($this->input->post('ids'));

        $documenti = $this->apilib->search('documenti_contabilita_scadenze', "documenti_contabilita_scadenze_id IN (" . implode(',', $ids) . ")");
        $conto = $this->apilib->view('conti_correnti', $this->input->post('conto_riba'));

        $accorpata = $this->input->post('accorpata');

        $data = date('Y-m-d');
        //debug($conto,true);

        $this->load->model('contabilita/ribaabicbi');
        $file_content = $this->ribaabicbi->creaFileFromDocumenti($this->apilib->searchFirst('documenti_contabilita_settings'), $documenti, $accorpata);

        header("Content-type: text/plain");
        header("Content-Disposition: attachment; filename={$conto['conti_correnti_nome_istituto']}_{$data}.dat");
        echo $file_content;
        die();
    }

    public function generaSdd($override_data = [])
    {
        $ids = json_decode($this->input->post('ids') ?? $this->input->post('ids_b2b'));
        $conto_id = $this->input->post('conto_sdd') ?? $this->input->post('conto_sdd_b2b');
        $conto = $this->apilib->view('conti_correnti', $conto_id);
        //debug($conto, true);
        $documenti = $this->apilib->search('documenti_contabilita_scadenze', "documenti_contabilita_scadenze_id IN (" . implode(',', $ids) . ")");

        foreach ($documenti as $index => $documento) {
            $documenti[$index]['cliente'] = $this->apilib->searchFirst('customers_bank_accounts', ['customers_bank_accounts_customer_id' => $documento['documenti_contabilita_customer_id']]);
        }

        $settings = $this->apilib->searchFirst('documenti_contabilita_settings', [], 0, 'documenti_contabilita_settings_creation_date', 'ASC');

        // dump($settings);
        // dump($documenti);

        $sdd = [];

        $tot_docs = array_reduce($documenti, function ($sum, $doc) {
            if (empty($doc['documenti_contabilita_scadenze_ammontare'])) {
                $doc['documenti_contabilita_scadenze_ammontare'] = 0;
            }

            return $sum + $doc['documenti_contabilita_scadenze_ammontare'];
        }, 0);

        $sdd['total'] = number_format($tot_docs, 2, '.', '');
        $sdd['sdd_id'] = 'SDDXml-' . date('dmY-Hi');
        $sdd['creation_datetime'] = (new DateTime())->format(DateTime::ATOM);
        $sdd['number_of_transactions'] = (int)count($ids);
        $sdd['azienda'] = $settings;
        $sdd['documenti'] = $documenti;

        $sdd['conto'] = $conto;

        // dd($sdd);

        $file_content = $this->load->view('contabilita/xml_sdd', ['sdd' => $sdd, 'override_data' => $override_data], true);

        header("Content-type: text/xml");
        header("Content-Disposition: attachment; filename=sdd.xml");

        die($file_content);
    }

    public function generaSddB2b()
    {
        $this->generaSdd(['Cd' => 'B2B']);
    }

    public function downloadZip()
    {
        $ids = json_decode($this->input->post('ids'));

        //debug($ids);

        $fatture = $this->apilib->search('documenti_contabilita', ['documenti_contabilita_id IN (' . implode(',', $ids) . ')']);

        //debug($fatture,true);
        $this->load->helper('download');
        $this->load->library('zip');
        $dest_folder = FCPATH . "uploads";

        $destination_file = "{$dest_folder}/fatture.zip";

        //die('test');
        //Ci aggiungo il json e la versione, poi rizippo il pacchetto...
        $zip = new ZipArchive();

        if ($zip->open($destination_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            exit("cannot open <$destination_file>\n");
        }

        foreach ($fatture as $fattura) {
            //debug($fattura,true);
            $xml_content = file_get_contents(FCPATH . "uploads/" . $fattura['documenti_contabilita_file_xml']);
            $pdf_content = file_get_contents(FCPATH . "uploads/" . $fattura['documenti_contabilita_file_pdf']);

            // Todo andrebbe usato il metodo generaxml che salva il nome nuovo del file
            if (!empty($fattura['documenti_contabilita_nome_file_xml'])) {
                $zip->addFromString("xml/{$fattura['documenti_contabilita_nome_file_xml']}", $xml_content);
            } else {
                $zip->addFromString("xml/{$fattura['documenti_contabilita_numero']}{$fattura['documenti_contabilita_serie']}.xml", $xml_content);
            }
            $pdf_file_name = $this->docs->generate_beautify_name($fattura);
            $zip->addFromString("pdf/" . $pdf_file_name, $pdf_content);
        }

        $zip->close();

        force_download('fatture.zip', file_get_contents($destination_file));
    }

    public function print_all()
    {
        if (!command_exists('pdfunite')) {
            //throw new ApiException('Errore generico durante la generazione del pdf.');
            //echo json_encode(['status' => 0, 'txt' => 'Errore generico durante la generazione del pdf.']);
            echo "<alert>Errore generico durante la generazione del pdf (pdfunite non installato).</alert>";
            exit;
        }

        $ids = json_decode($this->input->post('ids'));
        $tpl = $this->input->post('tpl');
        $documenti = $this->apilib->search('documenti_contabilita', ["documenti_contabilita_id IN (" . implode(',', $ids) . ")"]);
        
        $template_pdf = null;
        if (is_numeric($tpl)) {
            $template_pdf = $this->apilib->searchFirst('documenti_contabilita_template_pdf', ['documenti_contabilita_template_pdf_id' => $tpl]);
        }
        
        $files = [];
        foreach ($documenti as $key => $documento) {
            //var_dump(base64_decode($documento[$field_name]));
            if ($tpl == 'formato_compatto') {
                //Se formato compatto uso il model per salvarmi il file
                $pdf_file = $this->docs->getPdfFormatoCompatto($documento);
            } elseif (!empty($template_pdf)) {
                $pdf_file = $this->docs->getPdfTemplate($documento, $template_pdf);
            } else {
                $pdf_file = FCPATH . $key . '.pdf';
                file_put_contents(FCPATH . $key . '.pdf', file_get_contents(FCPATH . "uploads/" . $documento['documenti_contabilita_file_pdf']));
            }
            
            $files[] = $pdf_file;
        }
        $output = '';
        //echo "pdfunite ".implode(' ', $files)." ".FCPATH."merge.pdf";
        exec("pdfunite " . implode(' ', $files) . " " . FCPATH . "documenti.pdf", $output);

        foreach ($documenti as $key => $documento) {
            unlink(FCPATH . $key . '.pdf');
        }
        $fp = fopen(FCPATH . "documenti.pdf", 'rb');
        header("Content-Type: application/force-download");
        header("Content-Length: " . filesize(FCPATH . "documenti.pdf"));
        header("Content-Disposition: attachment; filename=documenti.pdf");
        fpassthru($fp);
        unlink(FCPATH . "documenti.pdf");
        exit;
    }

    public function genera_fatture_distinte()
    {
        $ddt_ids = json_decode($this->input->post('ddt_ids'), true);
        //debug($ddt_ids, true);
        foreach ($ddt_ids as $ddt_id) {
            $documento_old = $this->db->where('documenti_contabilita_id', $ddt_id)->get('documenti_contabilita')->row_array();
            $documento_new = $documento_old;

            //debug($documento_new);

            unset($documento_new['documenti_contabilita_id']);

            // Automaticamente elettronica
            $documento_new['documenti_contabilita_formato_elettronico'] = DB_BOOL_TRUE;
            //Cambio il tipo documento
            $documento_new['documenti_contabilita_tipo'] = 1;
            //Calcolo il nuovo numero
            $numero_sucessivo = $this->docs->numero_sucessivo($documento_new['documenti_contabilita_azienda'], $documento_new['documenti_contabilita_tipo'], $documento_new['documenti_contabilita_serie'], date("d/m/Y"));
            $documento_new['documenti_contabilita_numero'] = $numero_sucessivo;
            //Associo il documento al ddt
            // $documento_new['documenti_contabilita_rif_documento_id'] = $ddt_id;

            //Cambio la data emissione
            $documento_new['documenti_contabilita_data_emissione'] = date("Y-m-d H:i:s");

            //Creo il nuovo documento
            //$new_documento_id = $this->apilib->create('documenti_contabilita', $documento_new, false);
            $this->db->insert('documenti_contabilita', $documento_new);
            $new_documento_id = $this->db->insert_id();

            // 08-05-2023 rif_doc diventa multplo
            // Imposto documento padre nella relazione
            if (!empty($ddt_id)) {
                $this->docs->associaDocumenti($ddt_id, [$new_documento_id]);
            }

            //Copio gli articoli nel nuovo documento
            $articoli = $this->db->where('documenti_contabilita_articoli_documento', $ddt_id)->get('documenti_contabilita_articoli')->result_array();
            foreach ($articoli as $articolo) {
                unset($articolo['documenti_contabilita_articoli_id']);
                $articolo['documenti_contabilita_articoli_documento'] = $new_documento_id;

                $this->db->insert('documenti_contabilita_articoli', $articolo);
            }

            //Creo una scadenza
            $this->db->insert('documenti_contabilita_scadenze', [
                'documenti_contabilita_scadenze_documento' => $new_documento_id,
                'documenti_contabilita_scadenze_ammontare' => $documento_old['documenti_contabilita_totale'],
                'documenti_contabilita_scadenze_scadenza' => date("Y-m-d"),
            ]);


            // Cerco se c'è un template di default uso quello altrimenti il primo che creato che si presume il generico
            $tpl_pdf_db = $this->apilib->searchFirst('documenti_contabilita_template_pdf', ["documenti_contabilita_template_pdf_default" => DB_BOOL_TRUE], 0, 'documenti_contabilita_template_pdf_id', 'ASC');
            if (empty($tpl_pdf_db)) {
                $tpl_pdf_db = $this->apilib->searchFirst('documenti_contabilita_template_pdf', [], 0, 'documenti_contabilita_template_pdf_id', 'ASC');
            }
            $documento_new['documenti_contabilita_template_pdf'] = $tpl_pdf_db['documenti_contabilita_template_pdf_id'];

            $this->apilib->edit('documenti_contabilita', $new_documento_id, ['documenti_contabilita_template_pdf' => $documento_new['documenti_contabilita_template_pdf']]);

            // TODO: Qua sarà da cambiare perche lavorando multiazienda si dovrebbe indicare l'azienda id che genera il documento.
            $prefisso = "IT" . $settings['documenti_contabilita_settings_company_vat_number'];
            $this->docs->generateXmlFilename($prefisso, $new_documento_id);
            $documento = $this->apilib->view('documenti_contabilita', $new_documento_id);

            $this->docs->generate_xml($documento, $nomefilexml);
        }

        redirect('main/layout/elenco_documenti');
    }

    public function genera_fatture_accorpate_cliente()
    {
        $ddt_ids = json_decode($this->input->post('ddt_ids'), true);
        $data_emissione = (!empty($this->input->post('data_emissione'))) ? $this->input->post('data_emissione') : date('Y-m-d');
        
        $fatture_da_generare = [];
        //debug($ddt_ids, true);
        $ordinamento = 1;
        foreach ($ddt_ids as $ddt_id) {
            $documento_old = $this->apilib->view('documenti_contabilita', $ddt_id);
            $articoli_old = $this->db->where('documenti_contabilita_articoli_documento', $ddt_id)->get('documenti_contabilita_articoli')->result_array();
            array_unshift($articoli_old, [
                'documenti_contabilita_articoli_riga_desc' => DB_BOOL_TRUE,
                'documenti_contabilita_articoli_name' => "Rif. {$documento_old['documenti_contabilita_tipo_value']} {$documento_old['documenti_contabilita_numero']}" . (($documento_old['documenti_contabilita_serie']) ? "/{$documento_old['documenti_contabilita_serie']}" : ''),
                'documenti_contabilita_articoli_descrizione' => "del " . dateFormat($documento_old['documenti_contabilita_data_emissione']),
                'documenti_contabilita_articoli_position' => $ordinamento++,

            ]);
            foreach ($articoli_old as $key_articolo => $articolo) {
                unset($articoli_old[$key_articolo]['documenti_contabilita_articoli_id']);
                $articoli_old[$key_articolo]['documenti_contabilita_articoli_position'] = $ordinamento++;
            }

            $customer_id = $documento_old['documenti_contabilita_customer_id'];
            $customer = $this->apilib->view('customers', $customer_id);
            if (empty($fatture_da_generare[$customer_id])) {
                $fatture_da_generare[$customer_id] = [
                    'tipo_documento' => 1,
                    'tipo_destinatario' => 1,
                    'formato_elettronico' => DB_BOOL_TRUE,
                    'debug' => $customer['customers_company'],
                    'rif_documenti' => [$ddt_id],
                    'cliente_id' => $customer_id,
                    'articoli_data' => $articoli_old,
                    'data_emissione' => $data_emissione,
                ];
            } else {
                $fatture_da_generare[$customer_id]['articoli_data'] = array_merge($fatture_da_generare[$customer_id]['articoli_data'], $articoli_old);
            }


        }
        //debug($fatture_da_generare,true);
        foreach ($fatture_da_generare as $fattura_da_generare) {
            $documento_id = $this->docs->doc_express_save($fattura_da_generare);
        }
        //Creo ordine per questo fornitore


        redirect('main/layout/elenco_documenti');
    }

    public function genera_ordini_fornitori()
    {
        $commessa_id = null;
        if ($this->input->post('commessa_id')) {
            $commessa_id = $this->input->post('commessa_id');
        }
        $mappature = $this->docs->getMappature();
        extract($mappature);
        if (!$this->input->post('ddt_ids')) {
            $righe_articoli_ids = $this->input->post('articoli_ids');
            $articoli = $this->db
                ->where_in('documenti_contabilita_articoli_id', $righe_articoli_ids)
                ->join($entita_prodotti, "documenti_contabilita_articoli_prodotto_id = {$campo_id_prodotto}", 'LEFT')
                ->join('customers', "fw_products_supplier = customers_id", 'LEFT')
                ->get('documenti_contabilita_articoli')
                ->result_array();
        } else {
            $ddt_ids = json_decode($this->input->post('ddt_ids'), true);
            $articoli = $this->db
                ->where_in('documenti_contabilita_articoli_documento', $ddt_ids)
                ->join($entita_prodotti, "documenti_contabilita_articoli_prodotto_id = {$campo_id_prodotto}", 'LEFT')
                ->join('customers', "fw_products_supplier = customers_id", 'LEFT')
                ->get('documenti_contabilita_articoli')
                ->result_array();
        }
        $ordini_fornitori = [];
        $conto_articolo_no_fornitore = 0;
        foreach ($articoli as $articolo) {
            if ($articolo['fw_products_supplier']) {

                
                if (!array_key_exists($articolo['fw_products_supplier'], $ordini_fornitori)) {
                    $ordini_fornitori[$articolo['fw_products_supplier']] = []; 
                    //Inserisco riferimento all'ordine se sto generando un ordine fornitore da un solo ordine cliente (di fatto è come se avessi fatto "clona")
                    if (count($this->input->post('ddt_ids')) == 1) {
                        $documento = $this->apilib->view('documenti_contabilita', $articolo['documenti_contabilita_articoli_documento']);
                        $riga_desc_rif_doc = [
                            'documenti_contabilita_articoli_id' => null,
                            'documenti_contabilita_articoli_rif_riga_articolo' => null,
                            'documenti_contabilita_articoli_riga_desc' => DB_BOOL_TRUE,
                            'documenti_contabilita_articoli_codice' => null,
                            'documenti_contabilita_articoli_name' => 'Rif. ' . $documento['documenti_contabilita_tipo_value'] . ' n. ' . $documento['documenti_contabilita_numero'] . '' . (!empty($documento['documenti_contabilita_serie']) ? '/' . $documento['documenti_contabilita_serie'] : ''),
                            'documenti_contabilita_articoli_descrizione' => 'del ' . dateFormat($documento['documenti_contabilita_data_emissione']),
                            'documenti_contabilita_articoli_prezzo' => 0,
                            'documenti_contabilita_articoli_imponibile' => 0,
                            'documenti_contabilita_articoli_iva' => '',
                            'documenti_contabilita_articoli_prodotto_id' => '',
                            'documenti_contabilita_articoli_codice_asin' => '',
                            'documenti_contabilita_articoli_codice_ean' => '',
                            'documenti_contabilita_articoli_unita_misura' => '',
                            'documenti_contabilita_articoli_quantita' => 1,
                            'documenti_contabilita_articoli_sconto' => '',
                            'documenti_contabilita_articoli_applica_ritenute' => '',
                            'documenti_contabilita_articoli_applica_sconto' => '',
                            'documenti_contabilita_articoli_importo_totale' => '',
                            'documenti_contabilita_articoli_iva_id' => 1,
                        ];
                        $ordini_fornitori[$articolo['fw_products_supplier']][] = $riga_desc_rif_doc;
                    }
                }
                $ordini_fornitori[$articolo['fw_products_supplier']][] = $articolo;
            } else {
                $conto_articolo_no_fornitore++;
                /*
                $documento = $articoli[0]['documenti_contabilita_articoli_documento'];
                //$ddt_ids = json_decode($this->input->post('ddt_ids'), true);
                redirect('main/layout/nuovo_documento/'.$documento."?clone=1");
                die("Il prodotto '{$articolo['documenti_contabilita_articoli_name']}' (id: {$articolo[$campo_id_prodotto]}) non ha fornitore associato!");*/
            }
        }

        

        if (!empty($this->input->post('_from_commessa')) && $this->input->post('_from_commessa') == '1' && $conto_articolo_no_fornitore != 0) {
            //caso in cui qualche prodotto non ha il fornitore di default.
            $articoli_post = $this->input->post('articoli_ids');

            $articoli = [];
            $fornitore_id = 0;
            foreach ($articoli_post as $articolo_id) {
                $articoli = $this->db
                    ->where_in('documenti_contabilita_articoli_id', $articolo_id)
                    ->join($entita_prodotti, "documenti_contabilita_articoli_prodotto_id = {$campo_id_prodotto}", 'LEFT')
                    ->join('customers', 'fw_products_supplier = customers_id', 'LEFT')
                    ->get('documenti_contabilita_articoli')
                    ->row_array();
                if (empty($articoli)) {
                    die(e_json(['status' => 0, 'txt' => 'Articolo non trovato.']));
                }
                if (!empty($articoli['fw_products_supplier'])) {
                    $fornitore_id = $articoli['fw_products_supplier'];
                }
                $articolo = [
                    'id_riga' => $articolo_id,
                    'codice' => $articoli['documenti_contabilita_articoli_codice'],
                    'nome' => $articoli['documenti_contabilita_articoli_name'],
                    'descrizione' => $articoli['documenti_contabilita_articoli_descrizione'],
                    'prezzo' => $articoli['documenti_contabilita_articoli_prezzo'],
                    'quantita' => $articoli['documenti_contabilita_articoli_quantita'],

                ];
                $articoli_data[] = $articolo;
            }
            $this->session->set_userdata('commessa_id', $commessa_id);
            e_json(['status' => 1, 'txt' => 'ok', 'fornitore' => $fornitore_id, 'data' => $articoli_data]);
            exit;
        }

        

        foreach ($ordini_fornitori as $supplier_id => $articoli) {
            //debug($articoli);
            $articoli_qty = [];
            foreach ($articoli as $articolo) {
                if (empty($articoli_qty[$articolo['documenti_contabilita_articoli_prodotto_id']])) {
                    $articoli_qty[$articolo['documenti_contabilita_articoli_prodotto_id']] = [
                        'qty' => 0,
                        'documenti_contabilita_articoli_rif_riga_articolo' => $articolo['documenti_contabilita_articoli_id'],
                    ];
                }
                $articoli_qty[$articolo['documenti_contabilita_articoli_prodotto_id']]['qty'] += $articolo['documenti_contabilita_articoli_quantita'];
                //
            }
            //Creo ordine per questo fornitore
            $documento_id = $this->docs->doc_express_save([
                'tipo_documento' => 6,
                'tipo_destinatario' => 1,
                'fornitore_id' => $supplier_id,
                'articoli' => $articoli_qty,
                //TODO: aggiungere qui il riferimento all'ordine da cui provengono gli articoli
            ]);
            if ($commessa_id) {
                //se ho la commessa, imposto per ogni articolo il collegamento
                $articoli_ordine = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_id]);
                foreach ($articoli_ordine as $articolo_ordine) {
                    $this->apilib->create("projects_items", [
                        'projects_items_product' => $articolo_ordine['documenti_contabilita_articoli_id'],
                        'projects_items_project' => $commessa_id,
                    ]);
                }
            }
        }
        if ($this->input->post('ddt_ids')) {
            //A questo punto marco "in attesa" gli ordini cliente
            foreach ($ddt_ids as $id) {
                $this->apilib->edit('documenti_contabilita', $id, ['documenti_contabilita_stato' => 5]);
            }
        }

        if ($this->input->is_ajax_request()) {
            die(json_encode([
                'status' => 2,
                'txt' => t('Ordini creati con successo'),
            ]));
        } else {
            redirect(base_url('main/layout/elenco_documenti'), 'refresh');
        }
    }

    public function download_prima_nota()
    {
        require_once APPPATH . 'third_party/PHPExcel.php';

        $filtro_fatture = @$this->session->userdata(SESS_WHERE_DATA)['filtro_elenchi_documenti_contabilita'];

        $where_documenti = ['documenti_contabilita_tipo IN (1,4,11,12)'];
        $where_spese = ["1=1"];

        if (!empty($filtro_fatture)) {
            foreach ($filtro_fatture as $field => $filtro) {
                $value = $filtro['value'];
                switch ($field) {
                    case '778': //Data emissione
                        $data_expl = explode(' - ', $value);
                        $data_da = $data_expl[0];
                        $data_a = $data_expl[1];
                        $where_documenti[] = "documenti_contabilita_data_emissione <= '$data_a' AND documenti_contabilita_data_emissione>= '$data_da'";
                        $where_spese[] = "spese_data_emissione <= '$data_a' AND spese_data_emissione>= '$data_da'";
                        break;
                    default:
                        debug("Campo filtro non gestito (custom view iva).");
                        debug($filtro);
                        break;
                }
            }
        }

        $where_documenti_str = implode(' AND ', $where_documenti);
        $where_spese_str = implode(' AND ', $where_spese);

        //die($where_documenti_str);

        $fatture = $this->db->query("SELECT * FROM documenti_contabilita LEFT JOIN documenti_contabilita_tipo ON (documenti_contabilita_tipo = documenti_contabilita_tipo_id) LEFT JOIN conti_correnti ON (conti_correnti_id = documenti_contabilita_conto_corrente) WHERE $where_documenti_str")->result_array();
        $spese = $this->db->query("SELECT * FROM spese WHERE $where_spese_str ")->result_array();

        $out = [];
        $saldo_progressivo = 0;
        foreach ($fatture as $fattura) {
            $out[] = [
                'Data' => $fattura['documenti_contabilita_data_emissione'],
                'Conto' => $fattura['conti_correnti_nome_istituto'],
                'Descrizione' => "{$fattura['documenti_contabilita_tipo_value']} n. {$fattura['documenti_contabilita_numero']}" . (($fattura['documenti_contabilita_serie']) ? "/{$fattura['documenti_contabilita_serie']}" : ''),
                'Cliente/fornitore' => json_decode($fattura['documenti_contabilita_destinatario'], true)['ragione_sociale'],
                'Entrate' => ($fattura['documenti_contabilita_tipo'] == 1) ? $fattura['documenti_contabilita_totale'] : 0,
                'Uscite' => ($fattura['documenti_contabilita_tipo'] == 4) ? $fattura['documenti_contabilita_totale'] : 0,
                //'Saldo progressivo' => $saldo_progressivo
            ];
        }
        foreach ($spese as $spesa) {
            //debug($spesa,true);

            $out[] = [
                'Data' => $spesa['spese_data_emissione'],
                'Conto' => '',
                'Descrizione' => "{$spesa['spese_numero']}",
                'Cliente/fornitore' => json_decode($spesa['spese_fornitore'], true)['ragione_sociale'],
                'Entrate' => 0,
                'Uscite' => $spesa['spese_totale'],
                //'Saldo progressivo' => $saldo_progressivo
            ];
        }

        usort($out, function ($a, $b) {
            if ($a['Data'] == $b['Data']) {
                return 0;
            }
            return ($a['Data'] < $b['Data']) ? -1 : 1;
        });
        setlocale(LC_MONETARY, 'it_IT');
        foreach ($out as $key => $dato) {
            if ($dato['Uscite']) {
                $out[$key]['Uscite'] = $dato['Uscite'];
                $saldo_progressivo -= $dato['Uscite'];
            } else {
                $out[$key]['Entrate'] = $dato['Entrate'];
                $saldo_progressivo += $dato['Entrate'];
            }

            $out[$key]['Data'] = date('d-m-Y', strtotime($dato['Data']));
            $out[$key]['Saldo progressivo'] = number_format($saldo_progressivo, 2, ',', '');
            $out[$key]['Uscite'] = number_format($dato['Uscite'], 2, ',', '');
            $out[$key]['Entrate'] = number_format($dato['Entrate'], 2, ',', '');
        }

        //debug($out);

        $objPHPExcel = new PHPExcel();

        $objPHPExcel->getActiveSheet()->fromArray(array_keys($out[0]), '', 'A1');

        $objPHPExcel->getActiveSheet()->fromArray($out, '', 'A2');

        // $objPHPExcel->getActiveSheet()
        // ->getStyle('G2')
        // ->getNumberFormat()
        // ->setFormatCode(
        // PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE
        // );
        // $objPHPExcel->getActiveSheet()
        // ->getStyle('E2')
        // ->getNumberFormat()
        // ->setFormatCode(
        // PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE
        // );

        // Stile
        /*$objPHPExcel->getActiveSheet()->getStyle('E2')->applyFromArray(
        array(
        'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color' => array('rgb' => '00ff00')
        )
        )
        );
        $objPHPExcel->getActiveSheet()->getStyle('F2')->applyFromArray(
        array(
        'fill' => array(
        'type' => PHPExcel_Style_Fill::FILL_SOLID,
        'color' => array('rgb' => 'FF0000')
        )
        )
        );*/
        // Setto le larghezze
        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(12);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(35);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(15);
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth(15);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"prima_nota.xlsx\"");
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->setPreCalculateFormulas(true);

        $objWriter->save('php://output');
    }

    public function regeneratePdfAll()
    {
        $fatture = $this->apilib->search('documenti_contabilita', [
            'documenti_contabilita_tipo' => 1,
            "documenti_contabilita_data_emissione > '2023-08-27'"
        ]);

        $c = count($fatture);
        debug($c);
        $i = 0;
        foreach ($fatture as $fattura) {
            $i++;
            $this->regeneratePdf($fattura['documenti_contabilita_id']);
            progress($i, $c);
        }
    }

    public function regeneratePdf($documento_id)
    {
        $documento = $this->apilib->view('documenti_contabilita', $documento_id);
        if ($this->input->get('file_tpl')) {
            $pdfFile = $this->layout->generate_pdf($this->input->get('file_tpl'), "portrait", "", ['documento_id' => $documento_id], 'contabilita');
            //debug($pdfFile, true);
        } else {
            if ($documento['documenti_contabilita_template_pdf']) {
                $template = $this->apilib->view('documenti_contabilita_template_pdf', $documento['documenti_contabilita_template_pdf']);

                // Se caricato un file che contiene un html da priorità a quello
                if (!empty($template['documenti_contabilita_template_pdf_file_html']) && file_exists(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html'])) {
                    $content_html = file_get_contents(FCPATH . "uploads/" . $template['documenti_contabilita_template_pdf_file_html']);
                } else {
                    $content_html = $template['documenti_contabilita_template_pdf_html'];
                }
                $pdfFile = $this->layout->generate_pdf($content_html, "portrait", "", ['documento_id' => $documento_id], 'contabilita', true);
            } else {
                $pdfFile = $this->layout->generate_pdf("documento_pdf", "portrait", "", ['documento_id' => $documento_id], 'contabilita');
            }
        }

        // Storicizzo la copia di cortesia del PDF su file
        if (file_exists($pdfFile)) {
            $pdf_file_name = $this->docs->generate_beautify_name($documento);

            $content = file_get_contents($pdfFile, true);
            $folder = "modules_files/contabilita/pdf_cortesia";
            $filepath = $this->docs->salva_file_fisico($pdf_file_name, $folder, $content);
            $this->apilib->edit("documenti_contabilita", $documento_id, ['documenti_contabilita_file_pdf' => $filepath]);
        }
    }
    public function regenerateXmlAll()
    {
        $fatture = $this->apilib->search('documenti_contabilita', [
            'documenti_contabilita_tipo' => 1,
            "documenti_contabilita_data_emissione > '2023-08-27'"
        ]);

        $c = count($fatture);

        $i = 0;
        foreach ($fatture as $fattura) {
            $i++;
            $this->regenerateXml($fattura['documenti_contabilita_id']);
            progress($i, $c);
        }
    }
    public function regenerateXml($documento_id)
    {
        $documento = $this->apilib->view('documenti_contabilita', $documento_id);
        $this->docs->generate_xml($documento);
    }

    public function genera_cmr($documento_id = null)
    {
        if (empty($documento_id)) {
            die('Documento non esistente');
        }

        $documento = $this->apilib->view('documenti_contabilita', $documento_id);

        unset($documento['documenti_contabilita_template_pdf_html']);

        $pdfFile = $this->layout->generate_pdf("documento_cmr", "portrait", "", ['doc' => $documento], 'contabilita');

        $contents = file_get_contents($pdfFile, true);
        $pdf_b64 = base64_encode($contents);

        header('Content-Type: application/pdf');
        header('Content-disposition: inline; filename="documento_cmr_' . time() . '.pdf"');

        echo base64_decode($pdf_b64);
    }

    public function imposta_filtro_anno($anno)
    {
        $field_id = $this->db->query("SELECT * FROM fields WHERE fields_name = 'documenti_contabilita_data_emissione'")->row()->fields_id;

        $filtro_fatture = (array)@$this->session->userdata(SESS_WHERE_DATA)['filtro_elenchi_documenti_contabilita'];

        $filtro_fatture[$field_id] = [
            'value' => '01/01/' . $anno . ' - 31/12/' . $anno,
            'field_id' => $field_id,
            'operator' => 'eq',
        ];

        if (array_key_exists('0', $filtro_fatture)) {
            unset($filtro_fatture[0]);
        }

        $filtro = $this->session->userdata(SESS_WHERE_DATA);
        $filtro['filtro_elenchi_documenti_contabilita'] = $filtro_fatture;
        $this->session->set_userdata(SESS_WHERE_DATA, $filtro);

        redirect('main/layout/elenco_documenti');

        //debug($anno, true);
    }

    public function export_dhl()
    {
        $this->load->config('geography');
        $nazioni_map = array_flip($this->config->item('nazioni'));

        $ids = json_decode($this->input->post('ids_dhl'));

        $fatture = $this->apilib->search('documenti_contabilita', ['documenti_contabilita_id IN (' . implode(',', $ids) . ')']);

        //debug($fatture, true);

        $this->load->helper('download');

        $dest_folder = FCPATH . "uploads";

        $filename = "SpedizioniFattDHL_del_" . date('dm') . ".csv";

        $destfile = "$dest_folder/$filename";

        if (file_exists($destfile)) {
            unlink($destfile);
        }

        $file = fopen($destfile, 'w');

        $settings = $this->apilib->searchFirst('documenti_contabilita_settings');

        $ha_ragione_sociale = $settings['documenti_contabilita_settings_company_name'];
        $ha_indirizzo = $settings['documenti_contabilita_settings_company_address'];
        $ha_cap = $settings['documenti_contabilita_settings_company_zipcode'];
        $ha_citta = $settings['documenti_contabilita_settings_company_city'];
        $ha_nazione = $settings['documenti_contabilita_settings_company_country'];
        $ha_nazione = (strlen($ha_nazione) > 2) ? $nazioni_map[$ha_nazione] : $ha_nazione;
        $ha_telefono = "";

        $csv = [];

        $csv_head = [
            'sender_reference',
            'sender_company',
            'sender_address1',
            'sender_zip',
            'sender_city',
            'sender_country_cd',
            'sender_cd',
            'sender_account_num',
            'receiver_company',
            'receiver_attention',
            'receiver_address_1',
            'receiver_zip',
            'receiver_city',
            'receiver_country_cd',
            'Local_product_cd',
            'shipment_pieces',
            'shipment_weight',
            'contents1',
            'pre_alert_email',
            'rcvr_always_send_prealert_flag',
            'Advisory_Attached_flag',
            'receiver_cd',
            'Services',
            // 0 o 1 se contrassegno
            'KB',
            'COD',
            '',
            '',
            '',
            '',
            // valori fissi
            'COD_value',
            'COD_currency',
            'COD_payment_type',
            'receiver_phone',
            "\r\n",
        ];

        $csv_head_str = implode(';', $csv_head);

        fwrite($file, $csv_head_str);

        foreach ($fatture as $key => $fattura) {
            $sped = $this->apilib->getById('clienti_indirizzi_spedizione', $fattura['documenti_contabilita_extra_param']);

            $dest = json_decode($fattura['documenti_contabilita_destinatario'], true);

            $ragione_sociale = $dest['ragione_sociale'];
            $email = (!empty($fattura['clienti_email'])) ? $fattura['clienti_email'] : '';
            $telefono = (!empty($fattura['clienti_telefono'])) ? $fattura['clienti_telefono'] : '';
            $codice = (!empty($fattura['clienti_codice'])) ? $fattura['clienti_codice'] : '';

            $indirizzo = (!empty($sped)) ? $sped['clienti_indirizzi_spedizione_indirizzo'] : $dest['indirizzo'];
            $citta = (!empty($sped)) ? $sped['clienti_indirizzi_spedizione_citta'] : $dest['citta'];
            $cap = (!empty($sped)) ? $sped['clienti_indirizzi_spedizione_cap'] : $dest['cap'];
            $nazione = (!empty($sped)) ? ucfirst(strtolower($sped['clienti_indirizzi_spedizione_nazione'])) : ucfirst(strtolower($dest['nazione']));

            $nazione = (strlen($nazione) > 2) ? $nazioni_map[$nazione] : $nazione;

            $numdoc = $fattura['documenti_contabilita_numero'];
            $totale = number_format($fattura['documenti_contabilita_totale'], 2, ',', '');
            $valuta = $fattura['documenti_contabilita_valuta'];

            $n_colli = (!empty($fattura['documenti_contabilita_n_colli'])) ? $fattura['documenti_contabilita_n_colli'] : '';
            $peso = (!empty($fattura['documenti_contabilita_peso'])) ? $fattura['documenti_contabilita_peso'] : '0.01';
            $contrassegno = ($fattura['documenti_contabilita_metodo_pagamento'] == 'contrassegno') ? '1' : '0';
            $contrassegno_kb = ($fattura['documenti_contabilita_metodo_pagamento'] == 'contrassegno') ? 'KB' : '0';
            //$csv[$key]

            $csv_arr = [
                $numdoc,
                //A numero documento
                $ha_ragione_sociale,
                //B ragione sociale
                $ha_indirizzo,
                //C indirizzo mittente
                $ha_cap,
                //D cap mittente
                $ha_citta,
                //E citta mittente
                $ha_nazione, //F nazione mittente
                $settings['documenti_contabilita_settings_dhl_code'], //G codice dhl
                $settings['documenti_contabilita_settings_dhl_code'],
                //H codice dhl
                $ragione_sociale,
                //I ragione sociale destinatario
                $ragione_sociale,
                //J attenzione destinatario
                $indirizzo,
                //K indirizzo destinatario
                $cap,
                //L cap destinatario
                $citta,
                //M citta destinatario
                $nazione,
                //N nazione destinatario
                "N",
                //O codice prodotto dhl
                number_format($n_colli, 0),
                //P numero colli
                number_format($peso, 2, ',', ''),
                //Q peso spedizione
                "Generico",
                //R descrizione contenuto
                $email,
                "1",
                //S preavviso mail
                "1",
                //T attivazione preavviso
                $codice,
                //U codice cliente
                $contrassegno,
                //V se contrassegno, 1, altrimenti 0
                $contrassegno_kb,
                'COD',
                '',
                '',
                '',
                '',
                $totale,
                //W valore spedizione
                $valuta,
                //X valuta spedizione
                'K',
                //Y tipo di pagamento contrassegno (?)
                preg_replace("/[^0-9]/", "", $telefono),
                //Z telefono destinatario
                "\r\n",
            ];
            $csv_str = implode(';', $csv_arr);
            fwrite($file, $csv_str);
        }

        /*$csv = $this->array_to_csv($csv ,';', '"');
        fwrite($file, $csv);*/
        fclose($file);

        force_download($filename, file_get_contents($destfile));
        unlink($destfile);
    }

    public function getProducts($doc_id)
    {
        $articoli = $this->apilib->search('documenti_contabilita_articoli', [
            'documenti_contabilita_articoli_documento' => $doc_id,
        ]);

        e_json($articoli);
    }

    public function listDocumenti($options_html = false)
    {
        $mail_data = $this->input->post();
        $where = [];
        if ($tipo = $mail_data['tipo']) {
            $where['documenti_contabilita_tipo'] = $tipo;
        }

        $documenti = $this->apilib->search('documenti_contabilita', $where, 100, null, 'documenti_contabilita_data_emissione DESC');
        if ($options_html) {
            foreach ($documenti as $documento) {
                ?>
                <option data-tipo_documento="<?php echo $documento['documenti_contabilita_tipo']; ?>" data-data_documento="<?php echo dateFormat($documento['documenti_contabilita_data_emissione'], 'd/m/Y'); ?>" data-rif="<?php echo $documento['documenti_contabilita_numero']; ?><?php if ($documento['documenti_contabilita_serie']): ?>/<?php echo $documento['documenti_contabilita_serie']; ?><?php endif; ?>" value="<?php echo $documento['documenti_contabilita_id']; ?>" <?php if (!empty($movimento['movimenti_documento_id']) && $movimento['movimenti_documento_id'] == $documento['documenti_contabilita_id']): ?> selected="selected" <?php endif; ?>>
                    <?php echo $documento['documenti_contabilita_numero']; ?> <?php if ($documento['documenti_contabilita_serie']): ?>/<?php echo $documento['documenti_contabilita_serie']; ?><?php endif; ?> - <?php echo json_decode($documento['documenti_contabilita_destinatario'], true)['ragione_sociale']; ?>
                </option>
                <?php
            }
        } else {
            e_json($documenti);
        }
    }

    public function exportOrdiniFornitori()
    {
        $ids = json_decode($this->input->post('ids'));

        //debug($ids, true);

        $_articoli = $this->apilib->search('documenti_contabilita_articoli', [
            'documenti_contabilita_id IN (' . implode(',', $ids) . ')',
        ], 'documenti_contabilita_numero');
        $ordini_fornitori = [];
        foreach ($_articoli as $articolo) {
            if (!$articolo['documenti_contabilita_supplier_id']) {
                die("Ordine fornitore n. '{$articolo['documenti_contabilita_numero']}' non ha il fornitore associato.");
            }

            $supplier = $this->apilib->view('suppliers', $articolo['documenti_contabilita_supplier_id']);
            $ordini_fornitori[$articolo['documenti_contabilita_supplier_id']][$articolo['documenti_contabilita_articoli_documento']][] = $articolo + $supplier;
        }

        //debug($ordini_fornitori, true);
        $this->load->helper('download');
        $this->load->library('zip');
        $dest_folder = FCPATH . "uploads";

        $destination_file = "{$dest_folder}/ordini.zip";

        //die('test');
        //Ci aggiungo il json e la versione, poi rizippo il pacchetto...
        $zip = new ZipArchive();

        if ($zip->open($destination_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            exit("cannot open <$destination_file>\n");
        }
        $columns = [
            'customers_company' => 'Fornitore',
            'documenti_contabilita_numero' => 'Num. doc.',
            'documenti_contabilita_data_emissione' => 'Data',
            'documenti_contabilita_totale' => 'Totale doc.',
            'documenti_contabilita_imponibile' => 'Imponibile doc.',
            'documenti_contabilita_competenze' => 'Competenze doc.',
            'documenti_contabilita_iva' => 'Iva doc.',
            'documenti_contabilita_articoli_name' => 'Nome art.',
            'documenti_contabilita_articoli_codice' => 'Codice',
            'documenti_contabilita_articoli_codice_ean' => 'Ean',
            'documenti_contabilita_articoli_codice_asin' => 'Asin',
            'documenti_contabilita_articoli_descrizione' => 'Descrizione',
            'documenti_contabilita_articoli_prezzo' => 'Prezzo unit.',
            'documenti_contabilita_articoli_sconto' => 'Sconto',
            'documenti_contabilita_articoli_iva' => 'Iva art.',
            'documenti_contabilita_articoli_importo_totale' => 'Totale art.',
            'documenti_contabilita_articoli_imponibile' => 'Imponibile art.',
            'documenti_contabilita_articoli_quantita' => 'Qty',
            'documenti_contabilita_note_interne' => 'Note ordine',
        ];
        foreach ($ordini_fornitori as $supplier_id => $ordini) {
            $supplier = $this->apilib->view('suppliers', $supplier_id);
            $supplier_code = $supplier['customers_code'] ?: $supplier_id;
            $objPHPExcel = new Spreadsheet();
            $objPHPExcel->getActiveSheet()->fromArray($columns, '', 'A1');

            $xls_rows = [];
            foreach ($ordini as $ordine_id => $articoli) {
                foreach ($articoli as $key => $articolo) {
                    //debug($articolo, true);
                    foreach ($columns as $column => $label) {
                        $xls_rows[$key][$column] = $articolo[$column];
                    }
                }
            }

            $objPHPExcel->getActiveSheet()->fromArray($xls_rows, '', 'A2');

            $objWriter = IOFactory::createWriter($objPHPExcel, 'Xlsx');
            $objWriter->setPreCalculateFormulas(true);
            $objWriter->save("{$supplier_code}.xlsx");
            $zip->addFromString("{$supplier_code}.xlsx", file_get_contents("{$supplier_code}.xlsx"));

            unlink("{$supplier_code}.xlsx");
        }

        $zip->close();

        force_download('ordini_fornitore.zip', file_get_contents($destination_file));

        unlink($destination_file);
    }

    public function bulk_invio_mail()
    {
        $post = $this->input->post();

        $ids = explode(',', $post['documenti_contabilita_mail_documento_id']);

        $this->load->model('contabilita/docs');
        $this->load->library('email');

        // invio email
        $config['charset'] = 'utf-8';
        $config['wordwrap'] = TRUE;
        $config['mailtype'] = 'html';
        $config['smtp_auto_tls'] = true;
        $config['newline'] = "\r\n";

        $settings = $this->apilib->searchFirst('documenti_contabilita_settings');
        // Michael E. - 2022
        $smtp_host = (!empty($settings['documenti_contabilita_settings_smtp_host'])) ? trim($settings['documenti_contabilita_settings_smtp_host']) : null;
        $smtp_user = (!empty($settings['documenti_contabilita_settings_smtp_login'])) ? trim($settings['documenti_contabilita_settings_smtp_login']) : null;
        $smtp_pass = (!empty($settings['documenti_contabilita_settings_smtp_password'])) ? trim($settings['documenti_contabilita_settings_smtp_password']) : null;
        $smtp_port = (!empty($settings['documenti_contabilita_settings_smtp_port'])) ? trim($settings['documenti_contabilita_settings_smtp_port']) : null;

        if ($smtp_host && $smtp_user && $smtp_pass) {

            switch ($smtp_port) {
                case '587':
                    $smtp_crypt = 'TLS';
                    break;
                case '465':
                    $smtp_crypt = 'SSL';
                    break;
                case '25':
                default:
                    $smtp_crypt = '';
                    break;
            }

            $config['smtp_host'] = $smtp_host;
            $config['smtp_user'] = $smtp_user;
            $config['smtp_pass'] = $smtp_pass;
            $config['smtp_crypto'] = $smtp_crypt;
            $config['smtp_port'] = $smtp_port;
            $config['protocol'] = 'smtp';
            $config['smtp_debug'] = 0;
        }

        $this->email->initialize($config);

        foreach ($ids as $documento_id) {
            $this->email->clear(true);

            $mail_data = $post;

            $documento = $this->apilib->view('documenti_contabilita', $documento_id);

            $this->email->from($mail_data['documenti_contabilita_mail_mittente'], $mail_data['documenti_contabilita_mail_mittente_nome']);

            $documento_numero = $documento['documenti_contabilita_numero'];

            // SE fattura elettronica allego anche la preview xml
            // if ($documento['documenti_contabilita_formato_elettronico'] == DB_BOOL_TRUE) {
            // if ($mail_data['documenti_contabilita_mail_template']) {
            //     $allegato = base64_decode($documento['documenti_contabilita_file_preview_xml']);
            //     $this->email->attach($allegato, 'attachment', 'Fattura_elettronica_' . $documento_numero . '.pdf', 'application/pdf');
            // }

            // Allegato il pdf standard
            $allegato = file_get_contents(FCPATH . "uploads/" . $documento['documenti_contabilita_file_pdf']);
            $this->email->attach($allegato, 'attachment', 'Documento_' . $documento_numero . '.pdf', 'application/pdf');

            // Allego l'xml
            /*$allegato = base64_decode($documento['documenti_contabilita_file']);
            $nomefile = "IT" . $settings['documenti_contabilita_settings_company_vat_number'] . "_00001.xml";
            $this->email->attach($allegato, 'attachment', $nomefile, 'application/xml');*/
            // } else {
            //     // Allegato il pdf standard
            //     $allegato = file_get_contents(FCPATH."uploads/".$documento['documenti_contabilita_file_pdf']);
            //     $this->email->attach($allegato, 'attachment', 'Documento_' . $documento_numero . '.pdf', 'application/pdf');
            // }

            if ($documento['documenti_contabilita_serie']) {
                $documento_numero = $documento_numero . '/' . $documento['documenti_contabilita_serie'];
            }

            // Replace
            $mail_data['documenti_contabilita_mail_oggetto'] = str_replace('{numero_fattura}', $documento_numero, $mail_data['documenti_contabilita_mail_oggetto']);
            $mail_data['documenti_contabilita_mail_oggetto'] = str_replace('{data_emissione}', date('d-m-Y', strtotime($documento['documenti_contabilita_data_emissione'])), $mail_data['documenti_contabilita_mail_oggetto']);

            $mail_data['documenti_contabilita_mail_testo'] = str_replace('{numero_fattura}', $documento_numero, $mail_data['documenti_contabilita_mail_testo']);
            $mail_data['documenti_contabilita_mail_testo'] = str_replace('{data_emissione}', date('d-m-Y', strtotime($documento['documenti_contabilita_data_emissione'])), $mail_data['documenti_contabilita_mail_testo']);

            $mail_data['documenti_contabilita_mail_documento_id'] = $documento_id;

            if (empty($mail_data['documenti_contabilita_mail_destinatario'])) {
                if ($documento['documenti_contabilita_customer_id']) {
                    $mappature = $this->docs->getMappature();
                    extract($mappature);

                    $destinatario = $this->apilib->view($entita_clienti, $documento['documenti_contabilita_customer_id']);

                    if (!empty($destinatario[$clienti_email])) {
                        $mail_data['documenti_contabilita_mail_destinatario'] = $destinatario[$clienti_email];
                    }
                }
            }

            if (!empty($mail_data['documenti_contabilita_mail_destinatario'])) {
                $this->email->to($mail_data['documenti_contabilita_mail_destinatario']);

                $this->email->subject($mail_data['documenti_contabilita_mail_oggetto']);

                $this->email->message($mail_data['documenti_contabilita_mail_testo'] ?: ' ');

                $mail_data['documenti_contabilita_mail_allegati'] = json_encode(array(base64_encode($allegato)));

                $mail_data['documenti_contabilita_mail_creation_date'] = date('Y-m-d H:i:s');

                // ho usato $this->db perchè sennò con apilib entrava nel post-process pre-insert documenti_contabilita_mail "Invio email contabilita" e avrebbe fatto casini
                $this->db->insert('documenti_contabilita_mail', $mail_data);

                // Send and return the result
                $return = $this->email->send();
                if (empty($return)) {
                    throw new ApiException("Invio mail fallito: " . $this->email->print_debugger());
                    //    exit();
                }

                //die('esito: ' . $return);
            }
        }
        echo json_encode(['status' => 2, 'txt' => 'ok']);
    }

    public function associa_movimento_scadenza($flusso_cassa_id)
    {
        $ids = json_decode($this->input->post('scadenze_ids'));

        $flusso_cassa = $this->apilib->view('flussi_cassa', $flusso_cassa_id);
        $data = $this->apilib->edit('flussi_cassa', $flusso_cassa_id, [
            'flussi_cassa_scadenze_collegate' => $ids,
        ]);
        //debug($this->apilib->view('flussi_cassa', $flusso_cassa_id), true);
        redirect('main/layout/dettaglio-flusso-cassa/' . $data['flussi_cassa_azienda']);
    }

    public function associa_movimento_scadenza_uscita($flusso_cassa_id)
    {
        $ids = json_decode($this->input->post('scadenze_uscita_ids'));

        //debug($ids, true);

        $flusso_cassa = $this->apilib->view('flussi_cassa', $flusso_cassa_id);
        $data = $this->apilib->edit('flussi_cassa', $flusso_cassa_id, [
            'flussi_cassa_spese_scadenze_collegate' => $ids,
        ]);
        //debug($this->apilib->view('flussi_cassa', $flusso_cassa_id), true);
        redirect('main/layout/dettaglio-flusso-cassa/' . $data['flussi_cassa_azienda']);
    }

    public function testSign()
    {


        $file_to_be_signed = APPPATH . 'test.xml';
        $passphrase = '12345';
        $private_key_pem = APPPATH . 'private_key.pem';
        $certificate = APPPATH . 'certificate.pem';
        $file_signed = APPPATH . 'output.xml';

        // Load the XML to be signed
        $doc = new DOMDocument();
        $doc->load($file_to_be_signed);

        // Create a new Security object
        $objDSig = new RobRichards\XMLSecLibs\XMLSecurityDSig();
        // Use the c14n exclusive canonicalization
        $objDSig->setCanonicalMethod(RobRichards\XMLSecLibs\XMLSecurityDSig::EXC_C14N);
        // Sign using SHA-256
        $objDSig->addReference(
            $doc,
            RobRichards\XMLSecLibs\XMLSecurityDSig::SHA256,
            array('http://www.w3.org/2000/09/xmldsig#enveloped-signature')
        );

        // Create a new (private) Security key
        $objKey = new RobRichards\XMLSecLibs\XMLSecurityKey(RobRichards\XMLSecLibs\XMLSecurityKey::RSA_SHA256, array('type' => 'private'));
        /*
        If key has a passphrase, set it using
        $objKey->passphrase = '<passphrase>';
        */
        // Load the private key
        $objKey->passphrase = $passphrase;

        $objKey->loadKey($private_key_pem, true);

        // Sign the XML file
        $objDSig->sign($objKey);

        // Add the associated public key to the signature
        $objDSig->add509Cert(file_get_contents($certificate));

        // Append the signature to the XML
        $objDSig->appendSignature($doc->documentElement);
        // Save the signed XML
        $doc->save($file_signed);
    }

    public function associaLead()
    {
        $documento['documenti_contabilita_extra_param'] = $_POST['lead'];
        if (!$_POST['lead']) {
            die(e_json(['status' => 0, 'txt' => 'Lead non trovato']));
        }
        if (!$_POST['documento']) {
            die(e_json(['status' => 0, 'txt' => 'Documento non trovato']));
        }
        try {
            $this->apilib->edit('documenti_contabilita', $_POST['documento'], ['documenti_contabilita_extra_param' => $_POST['lead']]);
            $documento_contabilita = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_id' => $_POST['documento']]);
            //ora modifico il valore del lead con il prezzo totale del documento
            $lead['leads_price'] = $documento_contabilita['documenti_contabilita_totale'];
            $this->apilib->edit('leads', $_POST['lead'], ['leads_price' => $documento_contabilita['documenti_contabilita_totale']]);

            die(e_json(['status' => 1, 'txt' => 'Documento associato']));
        } catch (Exception $e) {
            die(e_json(['status' => 0, 'txt' => 'Si è verificato un errore interno']));
        }
    }

    public function DisassociaLead()
    {
        if (!$_POST['documento']) {
            die(e_json(['status' => 0, 'txt' => 'Documento non trovato']));
        }
        try {
            $this->apilib->edit('documenti_contabilita', $_POST['documento'], ['documenti_contabilita_extra_param' => '']);
            die(e_json(['status' => 1, 'txt' => 'Documento disassociato']));
        } catch (Exception $e) {
            die(e_json(['status' => 0, 'txt' => 'Si è verificato un errore interno']));
        }
    }

    public function buildArticoliData()
    {
        $post = $this->security->xss_clean($this->input->post());
        $type = $this->security->xss_clean($this->input->get('type'));
        $mappature = $this->docs->getMappature();
        extract($mappature);

        if (empty($post) || empty($post['articoli_ids']) || !is_array($post['articoli_ids'])) {
            die(e_json(['status' => 0, 'txt' => 'Errore invio dati']));
        }
        $articoli = [];
        $fornitore_id = 0;
        foreach ($post['articoli_ids'] as $articolo_id) {
            $articoli = $this->db
                ->where_in('documenti_contabilita_articoli_id', $articolo_id)
                ->join($entita_prodotti, "documenti_contabilita_articoli_prodotto_id = {$campo_id_prodotto}", 'LEFT')
                ->join('customers', "fw_products_supplier = customers_id", 'LEFT')
                ->get('documenti_contabilita_articoli')
                ->row_array();
            if (empty($articoli)) {
                die(e_json(['status' => 0, 'txt' => "Articolo non trovato."]));
            }
            if (!empty($articoli['fw_products_supplier'])) {
                $fornitore_id = $articoli['fw_products_supplier'];
            }
            $articolo = [
                'id_riga' => $articolo_id,
                'codice' => $articoli['documenti_contabilita_articoli_codice'],
                'nome' => $articoli['documenti_contabilita_articoli_name'],
                'descrizione' => $articoli['documenti_contabilita_articoli_descrizione'],
                'prezzo' => $articoli['documenti_contabilita_articoli_prezzo'],
                'quantita' => $articoli['documenti_contabilita_articoli_quantita'],

            ];
            $articoli_data[] = $articolo;
        }

        e_json(['status' => 1, 'txt' => 'ok', 'fornitore' => $fornitore_id, 'data' => $articoli_data]);
    }

    public function ConfermaPreventivo($documento)
    {
        if (!$documento) {
            die(e_json(['status' => 0, 'txt' => 'Documento non trovato']));
        }
        try {
            $this->apilib->edit('documenti_contabilita', $documento, ['documenti_contabilita_stato' => 3]);
        } catch (Exception $e) {
            die(e_json(['status' => 0, 'txt' => 'Si è verificato un errore interno']));
        }
        $mappature = $this->docs->getMappature();
        extract($mappature);
        $articoli = $this->db
            ->where_in('documenti_contabilita_articoli_documento', $documento)
            // ->join($entita_prodotti, "documenti_contabilita_articoli_prodotto_id = {$campo_id_prodotto}", 'LEFT')
            // ->join('customers', "fw_products_supplier = customers_id", 'LEFT')
            ->get('documenti_contabilita_articoli')
            ->result_array();

        // debug($articoli);
        // $articoli_qty = [];
        // foreach ($articoli as $articolo) {
        //     if (empty($articolo['documenti_contabilita_articoli_prodotto_id'])) {
        //         $articoli_qty[] = $articolo;
        //     } else {
        //         if (empty($articoli_qty[$articolo['documenti_contabilita_articoli_prodotto_id']])) {
        //             $articoli_qty[$articolo['documenti_contabilita_articoli_prodotto_id']] = [
        //                 'qty' => 0,
        //                 'documenti_contabilita_articoli_rif_riga_articolo' => $articolo['documenti_contabilita_articoli_id'],
        //             ];
        //         }
        //         $articoli_qty[$articolo['documenti_contabilita_articoli_prodotto_id']]['qty'] += $articolo['documenti_contabilita_articoli_quantita'];
        //         //
        //     }
        // }
        // debug($articoli_qty, true);
        //trovo cliente
        $documento_contabilita = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_id' => $documento]);
        $cliente = $documento_contabilita['documenti_contabilita_customer_id'];
        $lead_id = $documento_contabilita['documenti_contabilita_extra_param'];
        //Creo ordine per questo fornitore
        $documento_id = $this->docs->doc_express_save([
            'tipo_documento' => 5,
            'tipo_destinatario' => 1,
            'cliente_id' => $cliente,
            'documenti_contabilita_extra_param' => $lead_id,
            //'articoli' => $articoli_qty,
            'articoli_data' => $articoli,
            //TODO: aggiungere qui il riferimento all'ordine da cui provengono gli articoli
        ]);
        redirect('main/layout/dashboard-flow');
    }

    /**
     * Passando in $_POST uno o più 'documenti_contabilita_id' (ed eventualmente una data di emissione personalizzata),
     * utilizzando il model Docs -> doc_express_save, si possono duplicare delle fatture in bulk.
     *
     * @return void
     * @throws Exception
     * @version 2.6.6
     * @author Michael E.
     */
    public function bulk_clone()
    {
        $this->load->model('contabilita/docs');

        $post = $this->security->xss_clean($this->input->post());

        if (empty($post['ids'])) {
            die('ANOMALIA: Dati non passati');
        }

        if (empty($post['data_emissione']) || empty($post['data_scadenza']) || empty($post['tipo_documento'])) {
            die('ANOMALIA: Data emissione e/o scadenza e/o tipo documento non definiti');
        }

        if (in_array($post['tipo_documento'], [1, 4, 11, 12]) && empty($post['tipologia'])) {
            die('ANOMALIA: Data emissione e/o scadenza e/o tipo documento non definiti');
        }

        $ids_array = json_decode($post['ids'], true);

        $documenti = $this->db->where_in('documenti_contabilita_id', $ids_array)->order_by('documenti_contabilita_numero', 'ASC')->get('documenti_contabilita')->result_array();
        if (empty($documenti)) {
            die('ANOMALIA: Nessun documento trovato');
        }

        $template_pdf = $this->db->where('documenti_contabilita_template_pdf_tipo', $post['tipo_documento'])->get('documenti_contabilita_template_pdf')->row_array();

        if (empty($template_pdf)) {
            $template_pdf = $this->db->order_by('documenti_contabilita_template_pdf_default', 'DESC')->get('documenti_contabilita_template_pdf')->row_array();
        }

        foreach ($documenti as $doc) {
            $clone = [
                'documenti_contabilita_destinatario' => $doc['documenti_contabilita_destinatario'],
                'serie' => $doc['documenti_contabilita_serie'],
                'azienda' => $doc['documenti_contabilita_azienda'],
                'tipo_destinatario' => $doc['documenti_contabilita_tipo_destinatario'],
                'cliente_id' => $doc['documenti_contabilita_customer_id'],
                //'metodo_pagamento' => $doc['documenti_contabilita_metodo_pagamento'],
                'template_pagamento' => $doc['documenti_contabilita_template_pagamento'],
                'template' => $template_pdf['documenti_contabilita_template_pdf_id'] ?? $doc['documenti_contabilita_template_pdf'],
                'codice_esterno' => $doc['documenti_contabilita_codice_esterno'],
                'conto_corrente' => $doc['documenti_contabilita_conto_corrente'],
                'formato_elettronico' => $doc['documenti_contabilita_formato_elettronico'],
                'centro_di_costo' => $doc['documenti_contabilita_centro_di_ricavo'],
                'imponibile' => $doc['documenti_contabilita_imponibile'],
                'iva' => $doc['documenti_contabilita_iva'],
                'competenze' => $doc['documenti_contabilita_competenze'],
                'iva_json' => $doc['documenti_contabilita_iva_json'],
                'imponibile_iva_json' => $doc['documenti_contabilita_imponibile_iva_json'],
                'documenti_contabilita_totale' => $doc['documenti_contabilita_totale'],
//                'documenti_contabilita_rif_documento_id' => $doc['documenti_contabilita_id'], // 08-06 - DISATTIVATO IN QUANTO SOSTITUITO DALLA RELAZIONE rif_documenti
                'rif_documenti' => [$doc['documenti_contabilita_id']],
                'ritenuta_acconto_perc' => $doc['documenti_contabilita_ritenuta_acconto_perc'],
                'ritenuta_acconto_valore' => $doc['documenti_contabilita_ritenuta_acconto_valore'],
                'ritenuta_acconto_imponibile_valore' => $doc['documenti_contabilita_ritenuta_acconto_imponibile_valore'],
                'ritenuta_acconto_perc_imponibile' => $doc['documenti_contabilita_ritenuta_acconto_perc_imponibile'],
                'tipo_ritenuta' => $doc['documenti_contabilita_tipo_ritenuta'],
                'causale_pagamento_ritenuta' => $doc['documenti_contabilita_causale_pagamento_ritenuta'],
                'oggetto' => $doc['documenti_contabilita_oggetto'],
                'tipo_fatturazione' => $post['tipologia'],
                'tipo_documento' => $post['tipo_documento'],
                'data_emissione' => $post['data_emissione']
            ];

            $articoli = $this->db->where('documenti_contabilita_articoli_documento', $doc['documenti_contabilita_id'])->get('documenti_contabilita_articoli')->result_array();

            if (!empty($articoli)) {
                foreach ($articoli as $key => $art) {
                    $articoli[$key]['documenti_contabilita_articoli_descrizione'] = ((!empty($post['periodo_competenza']) ? 'periodo di competenza: ' . $post['periodo_competenza'] : ''));
                }

                if ($post['tipologia'] == 4) {
                    $articoli_aggiornati = []; // Crea un nuovo array vuoto per i valori aggiornati

                    foreach ($articoli as $articolo) {
                        $test_add = "Nota d'accredito a storno totale della fatt. " . $doc['documenti_contabilita_numero'] . "/" . $doc['documenti_contabilita_anno'] . " dd. " . date('d/m/Y', strtotime($doc['documenti_contabilita_data_emissione'])) . " per incompleta descrizione fattura";
                        $articolo['documenti_contabilita_articoli_name'] = $test_add . " " . $articolo['documenti_contabilita_articoli_name'];
                        $articoli_aggiornati[] = $articolo; // Aggiungi il valore aggiornato al nuovo array
                    }

                    $clone['articoli_data'] = $articoli_aggiornati;
                    //nota di credo, aggiungo ad ogni articolo la dicitura di riferimento
                } else {
                    $clone['articoli_data'] = $articoli;
                }
            }
            $scadenza = $this->db->where('documenti_contabilita_scadenze_documento', $doc['documenti_contabilita_id'])->get('documenti_contabilita_scadenze')->row_array();
            if (!empty($scadenza)) {
                $scadenza['documenti_contabilita_scadenze_saldata'] = DB_BOOL_FALSE;
                $scadenza['documenti_contabilita_scadenze_data_saldo'] = null;
                $scadenza['documenti_contabilita_scadenze_scadenza'] = $post['data_scadenza'];
                $clone['scadenza'] = $scadenza;
            }
            try {
                $this->docs->doc_express_save($clone);
            } catch (Exception $e) {
                die($e->getMessage());
            }
        }

        echo 'documenti duplicati con successo. reindirizzamento in corso...';
        sleep(2);
        redirect(base_url('main/layout/elenco_documenti'), 'refresh');
    }

    public function genera_fatture_da_pagamenti()
    {
        $this->load->model('contabilita/docs');

        $post = $this->security->xss_clean($this->input->post());

        if (empty($post['ids'])) {
            die(json_encode(['status' => 0, 'txt' => 'ANOMALIA: Dati non passati']));
        }

        $pagamenti = $this->db->where_in('payments_id', $post['ids'])->order_by('payments_date', 'DESC')->get('payments')->result_array();

        if (empty($pagamenti)) {
            die(json_encode(['status' => 0, 'txt' => 'ANOMALIA: Nessun pagamento trovato']));
        }

        if (count($pagamenti) !== count($post['ids'])) {
            die(json_encode(['status' => 0, 'txt' => 'ANOMALIA: Il numero di pagamenti trovato in database non corrisponde al numero di pagamenti selezionati']));
        }

        if (empty($post['serie']) || $post['serie'] == '---') {
            $post['serie'] = '';
        }

        $check_data = $this->db->query("SELECT documenti_contabilita_data_emissione AS data_emissione FROM documenti_contabilita WHERE documenti_contabilita_tipo = '1' AND documenti_contabilita_serie = '{$post['serie']}' ORDER BY documenti_contabilita_data_emissione DESC LIMIT 1")->row();

        if ($post['data_emissione'] < $check_data->data_emissione) {
            die(e_json(['status' => 0, 'txt' => "Inserire una data maggiore o uguale all'ultima data di emissione per questa serie!"]));
        }

        $azienda = $this->apilib->searchFirst('documenti_contabilita_settings');

        foreach ($pagamenti as $pagamento) {
            // @todo: valutare se fare controllo fattura già emessa per questo pagamento

            $fattura = [
                'cliente_id' => $pagamento['payments_customer'],
                'azienda' => $azienda['documenti_contabilita_settings_id'],
                'tipo_documento' => 1,
                'data_emissione' => (!empty($post['data_emissione'])) ? $post['data_emissione'] : dateFormat($pagamento['payments_date'], 'Y-m-d'),
                'totale' => $pagamento['payments_amount'],
                'stato' => 1,
                'formato_elettronico' => 1,
                'articoli_data' => [
                    [
                        'documenti_contabilita_articoli_name' => $pagamento['payments_note'],
                        'documenti_contabilita_articoli_prezzo' => $pagamento['payments_amount'],
                        'documenti_contabilita_articoli_quantita' => 1,
                        'documenti_contabilita_articoli_iva_id' => 1,
                        'documenti_contabilita_articoli_iva_perc' => 22,
                        'documenti_contabilita_articoli_descrizione' => (!empty($post['periodo_competenza']) ? 'periodo di competenza: ' . $post['periodo_competenza'] : ''),
                    ]
                ],
            ];

            if (!empty($post['data_scadenza'])) {
                $fattura['data_scadenza'] = $post['data_scadenza'];
            }

            if (!empty($post['serie'])) {
                $fattura['serie'] = $post['serie'];
            }

            try {
                $documento_id = $this->docs->doc_express_save($fattura);

                $this->apilib->edit('payments', $pagamento['payments_id'], ['payments_accounting_document_id' => $documento_id, 'payments_invoice_sent' => DB_BOOL_TRUE]);
            } catch (Exception $e) {
                die(e_json(['status' => 0, 'txt' => $e->getMessage()]));
            }
        }

        die(e_json(['status' => 3, 'txt' => 'Fatture generate con successo!']));
    }

    public function estratto_conto($customer_id = null)
    {
        $customer = $this->apilib->view('customers', $customer_id);
        //debug($customer,true);
        if ($customer['customers_type'] == 2) { //E' un fornitore
            $content = $this->load->view('pdf/stampa_estratto_conto_fornitore', [
                'customer_id' => $customer_id,
                'customer' => $customer
            ], true);
        } else {
            $content = $this->load->view('pdf/stampa_estratto_conto', [
                'customer_id' => $customer_id,
                'customer' => $customer
            ], true);
        }


        $pdf = $this->layout->generate_pdf($content, "portrait", "", 'contabilita', false, true);
        $fp = fopen(
            $pdf,
            'rb'
        );
        //die(file_get_contents($pdf));
        header("Content-Type: application/pdf");
        header("Content-Length: " . filesize($pdf));
        fpassthru($fp);
        unlink($pdf);
    }

    public function genera_fatture_da_pagamenti_cliente()
    {
        $this->load->model('contabilita/docs');

        $post = $this->security->xss_clean($this->input->post());

        $pagamenti_ids = json_decode($post['pagamenti_ids'], true);

        if (empty($pagamenti_ids)) {
            die(json_encode(['status' => 0, 'txt' => 'ANOMALIA: Dati non passati']));
        }

        $pagamenti_db = $this->db->where_in('payments_id', $pagamenti_ids)->order_by('payments_date', 'DESC')->get('payments')->result_array();

        if (empty($pagamenti_db)) {
            die(json_encode(['status' => 0, 'txt' => 'ANOMALIA: Nessun pagamento trovato']));
        }

        if (count($pagamenti_db) !== count($pagamenti_ids)) {
            die(json_encode(['status' => 0, 'txt' => 'ANOMALIA: Il numero di pagamenti trovato in database non corrisponde al numero di pagamenti selezionati']));
        }

        $azienda = $this->apilib->searchFirst('documenti_contabilita_settings');

        $pagamenti_clienti = [];
        foreach ($pagamenti_db as $pagamento_db) {
            $pagamenti_clienti[$pagamento_db['payments_customer']][] = $pagamento_db;
        }

        foreach ($pagamenti_clienti as $cliente_id => $pagamenti) {
            $fattura = [
                'cliente_id' => $cliente_id,
                'azienda' => $azienda['documenti_contabilita_settings_id'],
                'tipo_documento' => 1,
                'data_emissione' => date('Y-m-d'),
                'stato' => 1,
                'formato_elettronico' => 1,
            ];

            $articoli_data = [];
            $totale = 0;
            foreach ($pagamenti as $pagamento) {
                $articoli_data[] = [
                    'documenti_contabilita_articoli_name' => $pagamento['payments_note'],
                    'documenti_contabilita_articoli_descrizione' => (!empty($post['periodo_competenza']) ? 'periodo di competenza: ' . $post['periodo_competenza'] : ''),
                    'documenti_contabilita_articoli_prezzo' => number_format($pagamento['payments_amount'], 2, '.', ''),
                    'documenti_contabilita_articoli_quantita' => 1,
                    'documenti_contabilita_articoli_iva_id' => 1,
                    'documenti_contabilita_articoli_iva_perc' => 22
                ];

                $totale += $pagamento['payments_amount'];
            }

            $fattura['articoli_data'] = $articoli_data;
            $fattura['totale'] = number_format($totale, 2, '.', '');

            try {
                $documento_id = $this->docs->doc_express_save($fattura);

                foreach ($pagamenti as $pagamento) {
                    $this->apilib->edit('payments', $pagamento['payments_id'], ['payments_accounting_document_id' => $documento_id, 'payments_invoice_sent' => DB_BOOL_TRUE]);
                }
            } catch (Exception $e) {
                die(e_json(['status' => 0, 'txt' => $e->getMessage()]));
            }
        }

        die(e_json(['status' => 4, 'txt' => 'Fatture generate con successo!']));
    }

    public function get_azienda($azienda_id)
    {
        try {
            $azienda = $this->apilib->view('documenti_contabilita_settings', $azienda_id);

            die(e_json(['status' => 1, 'txt' => $azienda]));
        } catch (Exception $e) {
            die(e_json(['status' => 0, 'txt' => $e->getMessage()]));
        }
    }

    public function xml_liquidazione_periodica()
    {
        $azienda = $this->apilib->searchFirst('documenti_contabilita_settings');

        $view_data = [];

        $view_data['azienda'] = $azienda;

        $xml = $this->load->module_view('contabilita/views', 'xml_liquidazione_periodica', $view_data, true);

        $this->output->set_content_type('text/xml')->set_output($xml);
    }
}
