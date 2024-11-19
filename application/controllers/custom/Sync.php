<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sync extends MY_Controller
{
    private $apib_db;
    function __construct ()
    {
        parent::__construct();
        
        if (!$this->auth->check()) {
            //die("Unauthorized");
        }
        
        $this->apib_db_connect();
        
        
    }
    
    private function apib_db_connect() {
        //Mi connetto al db postgres
        // verifico se sono sul dominio apib.ingegnosuite.it uso localhost, altrimenti uso il dominio
        
        if (strpos($_SERVER['HTTP_HOST'], 'apib.ingegnosuite.it') !== false || is_cli()) {
            $db['crm_postgres']['hostname'] = 'localhost';
        } else {
            $db['crm_postgres']['hostname'] = 'crm.apibinfermieribologna.com';
        }
        
        // $db['crm_postgres']['hostname'] = 'crm.apibinfermieribologna.com'; // Cambiare per testare in linea
        //$db['crm_postgres']['hostname'] = 'localhost';
        $db['crm_postgres']['database'] = 'mastercrm_apib';
        $db['crm_postgres']['username'] = 'mastercrm_apib';
        $db['crm_postgres']['password'] = 'Djf93MN@VZZF215';
        
        $db['crm_postgres']['dbdriver'] = 'postgre';
        $db['crm_postgres']['dbprefix'] = '';
        $db['crm_postgres']['pconnect'] = false;
        $db['crm_postgres']['db_debug'] = true;
        $db['crm_postgres']['cache_on'] = false;
        $db['crm_postgres']['cachedir'] = '';
        $db['crm_postgres']['char_set'] = 'utf8';
        $db['crm_postgres']['dbcollat'] = 'utf8_general_ci';
        $db['crm_postgres']['swap_pre'] = '';
        $db['crm_postgres']['autoinit'] = true;
        $db['crm_postgres']['stricton'] = false;
        
        $this->apib_db = $this->load->database($db['crm_postgres'], true);
    }
    
    public function import_pagamenti()
    {
        set_log_scope('sync-pagamenti');
        $associati = $this->db
            ->where("documenti_contabilita_settings_company_codice_fiscale IS NOT NULL AND documenti_contabilita_settings_company_codice_fiscale <> ''")
            ->get('documenti_contabilita_settings')->result_array();
        
        $associati_cf = array_key_value_map($associati, 'documenti_contabilita_settings_company_codice_fiscale', 'documenti_contabilita_settings_id');
        
        //Tolgo eventuali spazi (trim) dal codice fiscale (che è la chiave nel nostro array)
        $associati_cf = array_combine(
            array_map('trim', array_keys($associati_cf)),
            array_values($associati_cf)
        );

        $pagamenti_id_importati = array_key_map($this->db->get('pagamenti')->result_array(), 'pagamenti_id_esterno');
        $pagamenti_id_importati[] = '-1';
        
        $pagamenti = $this->apib_db
            ->where_not_in('pagamenti_id', $pagamenti_id_importati)
            ->where('pagamenti_approvato', 't')
            ->where('pagamenti_anno >', '2022')
            ->where('pagamenti_acconto > 0', null, false)
            ->get('pagamenti')->result_array();
        
        if (empty($pagamenti)) {
            echo 'nessun pagamento da importare';
            return false;
        }
        //debug($pagamenti,true);
        $t = count($pagamenti);
        $c = 0;
        foreach ($pagamenti as $pagamento) {
            //debug($pagamento,true);
            $associato_old = $this->apib_db->where('associati_id', $pagamento['pagamenti_associato'])->get('associati')->row_array();
            
            $pagamento['pagamenti_id_esterno'] = $pagamento['pagamenti_id'];
            
            unset($pagamento['pagamenti_data_creazione']);
            unset($pagamento['pagamenti_data_modifica']);
            
            $pagamento['pagamenti_pagato'] = ($pagamento['pagamenti_pagato'] == 't') ? DB_BOOL_TRUE : DB_BOOL_FALSE;
            $pagamento['pagamenti_approvato'] = ($pagamento['pagamenti_approvato'] == 't') ? DB_BOOL_TRUE : DB_BOOL_FALSE;
            if (5470.95 == $pagamento['pagamenti_acconto']) {
                // debug($pagamento);
                // debug($associato_old);
                // debug($associati_cf,true);
                

            }
            if (!empty($associati_cf[$associato_old['associati_cf']])) {
                $pagamento['pagamenti_associato'] = $associati_cf[$associato_old['associati_cf']];
            } else {
                
                
                $pagamento['pagamenti_associato'] = null;
            }
            
            try {
                $this->apilib->create('pagamenti', $pagamento);
                
                progress(++$c, $t);
            } catch (Exception $e) {
                echo "errore inserimento pagamento";
                my_log('error', "errore inserimento pagamento: {$e->getMessage()}");
            }
        }
    }

    public function test() {
        $this->mail_model->sendMessage('matteopuppis@gmail.com', 'test', 'test');
    }
    public function migrate_dati() {
        $this->import_associati();
        $this->import_clienti();
        $this->import_sedi();
        $this->import_orari();
        $this->import_pagamenti();
        $this->import_sedi_operative_associati();
        $this->import_disponibilita_associati();
        $this->import_sedi_professionisti();
        $this->import_domiciliari();

        $this->import_listino_prezzi();
        $this->import_report_orari();

        $this->import_variazioni();

        $this->inport_tariffe();
    }
    
    public function import_associati() {
        set_log_scope('sync-associati');
        $associati = $this->apib_db->join('utenti', 'utenti_id = associati_utente', 'LEFT')->where("associati_deleted <> 't' OR associati_deleted IS NULL ", null, false)->get('associati')->result_array();
        //debug($associati,true);
        $t = count($associati);
        $c = 0;
        foreach ($associati as $associato) {
            
            progress(++$c, $t, 'import associati vs dipendenti');
            if ($associato['associati_email'] == '') {
                continue;
            }
            if ($associato['associati_foto']) {
                $folder = explode('/', $associato['associati_foto']);
                $folder = "{$folder[0]}/{$folder[1]}/{$folder[2]}";
                @mkdir(FCPATH . 'uploads/' . $folder, DIR_WRITE_MODE, true);
                copy("https://crm.apibinfermieribologna.com/uploads/{$associato['associati_foto']}", FCPATH."uploads/{$associato['associati_foto']}");
            }
            $dipendente = [
                'dipendenti_id' => $associato['associati_id'],
                //'dipendenti_user_id' => $associato['associati_utente'],
                'dipendenti_tipologia' => 7, //Empolyee
                
                'dipendenti_posizione' => 2, //P.iva
                'dipendenti_azienda' => 1, //Apib
                
                'dipendenti_nome' => $associato['associati_nome'],
                'dipendenti_cognome' => $associato['associati_cognome'] ?? 'senza cognome',
                'dipendenti_codice_fiscale' => $associato['associati_cf'],
                'dipendenti_cellulare' => $associato['associati_cellulare'],
                'dipendenti_foto' => $associato['associati_foto']??null,
                'dipendenti_email' => trim(strtolower($associato['associati_email'])),
                'dipendenti_password' => $associato['utenti_password'],
                'dipendenti_data_nascita' => $associato['associati_data_nascita'],
                'dipendenti_luogo_nascita' => $associato['associati_luogo_nascita'],
                
                'dipendenti_consenti_straordinari' => DB_BOOL_FALSE,
                'dipendenti_automobile_personale' => $associato['associati_automobile'],
                'dipendenti_costo_chilometrico' => $associato['associati_costo_km'],
                
                'dipendenti_data_inizio' => $associato['associati_inizio_rapporto'],
                'dipendenti_data_fine' => $associato['associati_fine_rapporto'],
                
                'dipendenti_note_aggiuntive' => "{$associato['associati_note_1']}\n{$associato['associati_note_2']}\n{$associato['associati_note_3']}",
                'dipendenti_indirizzo' => $associato['associati_indirizzo_residenza'],
                'dipendenti_citta' => $associato['associati_citta_residenza'],
                'dipendenti_dichiara_reparto' => DB_BOOL_FALSE,
                'dipendenti_reperibilita' => DB_BOOL_TRUE,
                'dipendenti_attivo' => ($associato['associati_non_attivo'] == 't' || $associato['associati_deleted'] == 't') ? DB_BOOL_FALSE : DB_BOOL_TRUE,
                'dipendenti_ignora_orari_lavoro' => DB_BOOL_TRUE,
                'dipendenti_ignora_pausa' => DB_BOOL_TRUE,
                'dipendenti_timbra_da_rapportino' => DB_BOOL_TRUE,
                
                'dipendenti_send_credential_by_email' => DB_BOOL_FALSE,
                
                'dipendenti_presenza_automatica' => DB_BOOL_FALSE,
                'dipendenti_mostra_in_riepilogo' => DB_BOOL_TRUE,
                'dipendenti_consenti_timbratura_senza_turno' => DB_BOOL_FALSE,
                
                'dipendenti_iban' => $associato['associati_iban'],
                'dipendenti_creation_date' => $associato['associati_data_creazione'],
                'dipendenti_modified_date' => $associato['associati_data_modifica'],
                
                'dipendenti_percentuale_sedi' => $associato['associati_percentuale_sedi'] ?? 0,
                'dipendenti_percentuale_domiciliari' => $associato['associati_percentuale_domiciliari'] ?? 0,
            ];


            //Tutti i campi che non esistono mappati su $dipendente ma che esistono su $associato, li salvo sul json dipendenti_altri_dati
            foreach ($associato as $key => $value) {
                $key = str_replace('associati_', 'dipendenti_', $key);
                if (!array_key_exists($key, $dipendente)) {
                    // $dipendente['dipendenti_dati_apib'][$key] = $value;
                    $dipendente['dipendenti_dati_apib'][] = [
                        'key' => $key,
                        'value' => $value,
                    ];
                }

            }
            // debug($dipendente['dipendenti_dati_apib'],true);
            // $dipendente['dipendenti_dati_apib'] = json_encode($dipendente['dipendenti_dati_apib']);

            // debug($dipendente,true);
            try {
                $dipendente_exists = $this->db->get_where('dipendenti', ['dipendenti_id' => $dipendente['dipendenti_id']])->row_array();


                if ($associato['associati_id'] == 727) {
                    // debug($dipendente_exists);
                    // debug($dipendente, true);
                }

                $_POST = $dipendente;
                if ($dipendente_exists) {
                    $dipendente_creato = $this->apilib->edit('dipendenti', $dipendente['dipendenti_id'], $dipendente);
                } else {
                    $dipendente_creato = $this->apilib->create('dipendenti', $dipendente);
                    //Una volta creato il dipendente, aggiorno il campo password con la password criptata (altrimenti le apilib fanno un md5 dell'md5...)
                   
                }

                // debug($associato);
                // debug($dipendente_creato, true);
                //Il pp avrà creato anche l'utente... correggo la password così da migrare anche quella
                $this->db->where('users_id', $dipendente_creato['dipendenti_user_id'])->update('users', ['users_password' => $associato['utenti_password']]);
                $this->db->where('dipendenti_id', $dipendente_creato['dipendenti_id'])->update('dipendenti', ['dipendenti_password' => $associato['utenti_password']]);
                $_POST = [];

                
            } catch (Exception $e) {
                
                my_log('error', "errore inserimento associato: {$e->getMessage()}");
                //debug($associato);
                echo($e->getMessage());
            }
        }
    }
    public function import_clienti()
    {
        set_log_scope('sync-clienti');
        $clienti = $this->apib_db->where('clienti_id <>', 631)->get('clienti')->result_array();
        //debug($associati,true);
        $t = count($clienti);
        $c = 0;
        foreach ($clienti as $cliente) {

            progress(++$c, $t, 'import clienti vs customers');
            
            
            $customer = [
                'customers_id' => $cliente['clienti_id'],
                'customers_city' => $cliente['clienti_citta'],
                'customers_address' => $cliente['clienti_indirizzo'],
                'customers_vat_number' => $cliente['clienti_p_iva'],
                'customers_cf' => $cliente['clienti_cf'],
                'customers_zip_code' => $cliente['clienti_cap'],
                'customers_company' => $cliente['clienti_ragione_sociale'],
                'customers_full_name'=> $cliente['clienti_ragione_sociale'],
                //'customers_id' => $cliente['clienti_iban'],
                'customers_email' => $cliente['clienti_email'],
                'customers_province' => $cliente['clienti_provincia'],
                'customers_phone' => $cliente['clienti_telefono'],
                'customers_mobile' => $cliente['clienti_cellulare'],
                //'customers_id' => $cliente['clienti_codice_pa'],
                'customers_fax' => $cliente['clienti_fax'],
                'customers_description' => $cliente['clienti_note'],
                'customers_status' => ($cliente['clienti_non_attivo']?3:1),
                //'clienti_referente' => $cliente['clienti_referente'],
                //'clienti_referente' => $cliente['clienti_termini_pagamento'],
                //'clienti_referente' => $cliente['clienti_cp'],
                //'clienti_referente' => $cliente['clienti_note_cliente'],
                'customers_deleted' => $cliente['clienti_deleted'],
                'customers_code' => $cliente['clienti_id'],
                'customers_status' => 1,
                'customers_type' => 1, //customer
                'customers_group' => 2, //azienda

            ];
            //Tutti i campi che non esistono mappati su $dipendente ma che esistono su $associato, li salvo sul json dipendenti_altri_dati
            foreach ($cliente as $key => $value) {
                $key = str_replace('clienti_', 'customers_', $key);
                if (!array_key_exists($key, $customer)) {
                    $customer['customers_dati_apib'][$key] = $value;
                }

            }
            $customer['customers_dati_apib'] = json_encode($customer['customers_dati_apib']);
            
            //debug($dipendente,true);
            //debug($customer,true);
            try {
                $customer_exists = $this->db->get_where('customers', ['customers_id' => $customer['customers_id']])->row_array();
                $_POST = $customer;
                if ($customer_exists) {
                    $customer_creato = $this->apilib->edit('customers', $customer['customers_id'], $customer);
                } else {
                    $customer_creato = $this->apilib->create('customers', $customer);

                    //debug($customer_creato,true);
                }

                $_POST = [];


            } catch (Exception $e) {

                my_log('error', "errore inserimento customer: {$e->getMessage()}");
                debug($customer);
                debug($e->getMessage(), true);
            }
        }
        $this->mycache->clearCache();
    }

    

    public function import_sedi() {
        set_log_scope('sync-sedi');
        
        // $sedi_orfane = $this->apib_db->where("(sedi_operative_cliente IS NULL OR sedi_operative_cliente NOT IN (SELECT clienti_id FROM clienti))", null, false)->get('sedi_operative')->result_array();
        
        // debug($sedi_orfane,true);
        
        $all_sedi = $this->apib_db
            // ->where("(sedi_operative_cliente IS NOT NULL AND sedi_operative_cliente IN (SELECT clienti_id FROM clienti))", null, false)
            ->get('sedi_operative')->result_array();
        
        // debug($all_sedi,true);
        
        $sedi_cliente = [];
        foreach ($all_sedi as $sede_cliente) {
            $sedi_cliente[$sede_cliente['sedi_operative_cliente']][] = $sede_cliente;
        }
        
        // debug($sedi_cliente[689],true);
        
        $t_all = count($sedi_cliente);
        $c_all = 0;

        $this->db->query("DELETE FROM customers_shipping_address");
        $this->db->query("DELETE FROM projects");

        foreach ($sedi_cliente as $cliente_id => $sedi) {
            // elimino tutte le sedi di questo cliente
            progress(++$c_all, $t_all, 'import sedi operative');
            // if (!empty($cliente_id)) {
            //     $this->db->where('customers_shipping_address_customer_id', $cliente_id)->delete('customers_shipping_address');
            // }
            
            $t = count($sedi);
            $c = 0;
            foreach ($sedi as $sede) {
                $shipping_address = [
                    'customers_shipping_address_id' => $sede['sedi_operative_id'],
                    'customers_shipping_address_customer_id' => $cliente_id ?: null,
                    'customers_shipping_address_country_id' => 105, // Italia
                    'customers_shipping_address_type' => 1,
                    'customers_shipping_address_street' => $sede['sedi_operative_indirizzo'] ?: '-',
                    'customers_shipping_address_city' => $sede['sedi_operative_citta'] ?: '-',
                    'customers_shipping_address_state' => $sede['sedi_operative_provincia'],
                    'customers_shipping_address_zip_code' => null,
                    
                    'customers_shipping_address_name' => $sede['sedi_operative_reparto'],
                    'customers_shipping_address_note' => $sede['sedi_operative_note'],
                    
                    // 'customers_shipping_address_phone' => ,
                    // 'customers_shipping_address_mobile' => ,
                    // 'customers_shipping_address_email' => ,
                
                    // 'customers_shipping_address_position' => $cliente['clienti_fax'],
                    // 'customers_shipping_address_external_id' => $cliente['clienti_note'],
                    // 'customers_shipping_address_default' => ($cliente['clienti_non_attivo'] ? 3 : 1),
                ];
                
                // dump($shipping_address);
                
                try {
                    $sede_db = $this->db->get_where('customers_shipping_address', ['customers_shipping_address_id' => $sede['sedi_operative_id']])->row_array();
                    $_POST = $shipping_address;
                    if ($sede_db) {
                        $sede_creata = $this->apilib->edit('customers_shipping_address', $sede['sedi_operative_id'], $shipping_address);
                    } else {
                        $sede_creata = $this->apilib->create('customers_shipping_address', $shipping_address);
                    
                        //debug($customer_creato,true);
                    }
                
                    $_POST = [];
                } catch (Exception $e) {
                    echo_log('error', "errore inserimento customer: {$e->getMessage()}");
                    
                    // debug($customer);
                    // debug($e->getMessage(), true);
                }
                
                //progress(++$c, $t, "import sedi {$cliente_id}");
            }
            
            
        }
        
        $this->mycache->clearCache();
    }
    
    public function import_sedi_operative_associati() {
        $sedi_operative_associati = $this->apib_db->get('sedi_operative_associati')->result_array();
        
        $this->db->query('DELETE FROM project_members');
        $this->mycache->clearCache();
        
        $c = 0;
        $t = count($sedi_operative_associati);
        foreach ($sedi_operative_associati as $sede_op) {
            progress(++$c, $t);
            $project = $this->db->get_where('projects', ['projects_id' => $sede_op['sedi_operative_id']])->row_array();
            $dipendente = $this->db->get_where('dipendenti', ['dipendenti_id' => $sede_op['associati_id']])->row_array();
            
            if (!$project) {
                echo_flush("project non trovato<br/>", '<br/>');
                
                continue;
            }
            if (!$dipendente) {
                echo_flush("dipendente '{$sede_op['associati_id']}' non trovato<br/>", '<br/>');
                
                continue;
            }
            if ($dipendente['dipendenti_user_id']) {
                $this->db->insert('project_members', [
                    'projects_id' => $project['projects_id'],
                    'users_id' => $dipendente['dipendenti_user_id'],
                ]);
            } else {
                echo_flush("dipendente '{$sede_op['associati_id']}' - {$dipendente['dipendenti_nome']} {$dipendente['dipendenti_cognome']} non ha utente<br/>", '<br/>');
            }
            
            
        }
        $this->mycache->clearCache();
    }

    public function import_orari()
    {
        set_log_scope('sync-orari');
        $orari = $this->apib_db->where('sedi_operative_orari_sede IN (SELECT sedi_operative_id FROM sedi_operative WHERE sedi_operative_cliente IN (SELECT clienti_id FROM clienti))',null, false)->get('sedi_operative_orari')->result_array();
        //debug($orari,true);
        $t = count($orari);
        $c = 0;
        $categorie_map = [
            1 => 1, //Mattina
            2=> 2, //Pomeriggio
            3 => 3, //Notte/Festivo
            6 => 4 //Accesso

        ];
        foreach ($orari as $orario) {

            progress(++$c, $t, 'import sedi_operative_orari vs projects_orari');

            $project = $this->db->get_where('projects', ['projects_customer_address' => $orario['sedi_operative_orari_sede']])->row_array();
            if (!$project) {
                debug($orario,true);
            }
            $orario = [
                'projects_orari_id' => $orario['sedi_operative_orari_id'],
                'projects_orari_project' => $project['projects_id'],
                'projects_orari_categoria' => $categorie_map[$orario['sedi_operative_orari_categoria']],
                'projects_orari_giorni' => [1,2,3,4,5,6,7],
                'projects_orari_dalle' => $orario['sedi_operative_orari_dalle'],
                'projects_orari_alle' => $orario['sedi_operative_orari_alle'],
                'projects_orari_cancellato' => $orario['sedi_operative_orari_cancellato']=='t'?1:0,
                'projects_orari_sigla'=> $orario['sedi_operative_orari_nome'],
                

            ];
            
            try {
                $orario_exists = $this->db->get_where('projects_orari', ['projects_orari_id' => $orario['projects_orari_id']])->row_array();
                $_POST = $orario;
                if ($orario_exists) {
                    $orario_creato = $this->apilib->edit('projects_orari', $orario['projects_orari_id'], $orario);
                } else {
                    $orario_creato = $this->apilib->create('projects_orari', $orario);

                    //debug($customer_creato,true);
                }

                $_POST = [];


            } catch (Exception $e) {

                my_log('error', "errore inserimento customer: {$e->getMessage()}");
                debug($orario);
                debug($e->getMessage(), true);
            }
        }
        $this->mycache->clearCache();
    }

    public function import_disponibilita_associati()
    {
        set_log_scope('sync-disponibilita-associati');
        //Filtro solo dal mese corrente in poi...
        $previousMonthStart = date('Y-m-01 00:00:00', strtotime('first day of previous month'));

        // Filtra solo dal mese precedente in poi
        $disponibilita = $this->apib_db->where('disponibilita_associati_dal >=', $previousMonthStart)
            ->get('disponibilita_associati')
            ->result_array();


        $t = count($disponibilita);
        $c = 0;

        foreach ($disponibilita as $disponibilita_item) {
            progress(++$c, $t, 'import disponibilita_associati vs richieste');

            // Parse the datetime strings
            $dal_datetime = new DateTime($disponibilita_item['disponibilita_associati_dal']);
            $al_datetime = new DateTime($disponibilita_item['disponibilita_associati_al']);
            //Se è Marcario ed è il 6 luglio
            
            

            // Check if al_datetime is greater than dal_datetime
            if ($al_datetime <= $dal_datetime) {
                my_log('error', "Errore: data/ora di fine non successiva a data/ora di inizio per disponibilita_associati_id: " . $disponibilita_item['disponibilita_associati_id']);
                continue; // Skip this item and move to the next
            }
            // if ($disponibilita_item['disponibilita_associati_associato'] == 958 && $dal_datetime->format('Y-m-d') == '2024-07-01') {
            //     debug($dal_datetime->format('H:i'), true);

            // } else {
            //     continue;
            // }
            $richiesta = [
                'richieste_id' => $disponibilita_item['disponibilita_associati_id'],
                'richieste_user_id' => $disponibilita_item['disponibilita_associati_associato'],
                'richieste_stato' => 1, // Assuming default state
                'richieste_tipologia' => ($disponibilita_item['disponibilita_associati_tipo'] == 2) ? 6 : 7,
                'richieste_creation_date' => $disponibilita_item['disponibilita_associati_data_creazione'],
                'richieste_modified_date' => $disponibilita_item['disponibilita_associati_data_modifica'],
                'richieste_dal' => $dal_datetime->format('Y-m-d'),
                'richieste_al' => $al_datetime->format('Y-m-d'),
                'richieste_ora_inizio' => $dal_datetime->format('H:i'),
                'richieste_ora_fine' => $al_datetime->format('H:i'),
                'richieste_all_day_calendar' => DB_BOOL_FALSE,
                'richieste_data_ora_inizio_calendar' => $disponibilita_item['disponibilita_associati_dal'],
                'richieste_data_ora_fine_calendar' => $disponibilita_item['disponibilita_associati_al'],
            ];

            try {
                $richiesta_exists = $this->db->get_where('richieste', ['richieste_id' => $richiesta['richieste_id']])->row_array();
                $_POST = $richiesta;

                if ($richiesta_exists) {
                    $richiesta_creata = $this->apilib->edit('richieste', $richiesta['richieste_id'], $richiesta);
                } else {
                    $richiesta_creata = $this->apilib->create('richieste', $richiesta);
                }

                $_POST = [];
            } catch (Exception $e) {
                my_log('error', "errore inserimento richiesta: {$e->getMessage()}");
                debug($richiesta);
                debug($e->getMessage(), true);
            }
        }

        $this->mycache->clearCache();
    }

    public function import_sedi_professionisti()
    {
        set_log_scope('sync-sedi-professionisti');

        // Ottieni la data di inizio del mese precedente
        $previousMonthStart = date('Y-m-01 00:00:00', strtotime('first day of previous month'));

        // Recupera i dati dalla tabella sedi_professionisti
        $sedi_professionisti = $this->apib_db
            ->where('sedi_professionisti_giorno >=', $previousMonthStart)
            //->join('associati', 'associati_id = sedi_professionisti_associato', 'LEFT')
            ->get('sedi_professionisti')
            ->result_array();

        $t = count($sedi_professionisti);
        $c = 0;

        //Precarico tutte le fasce orarie
        $_orari = $this->db->get('projects_orari')->result_array();
        //Li rimappo per avere come chiave l'id
        $orari = array_key_map_data($_orari, 'projects_orari_id');

        //Mi estraggo gli associati (dipendenti) e mi rimappo associato_id => users_id
        $_dipendenti = $this->db->get('dipendenti')->result_array();
        $dipendenti = array_key_value_map($_dipendenti, 'dipendenti_id', 'dipendenti_user_id');
        
        //debug($orari,true);
        foreach ($sedi_professionisti as $sede_professionista) {
            progress(++$c, $t, 'import sedi_professionisti vs appuntamenti');
            
            if (278 != $sede_professionista['sedi_professionisti_sede']) {
                continue;
            }
            $fascia = $orari[$sede_professionista['sedi_professionisti_fascia']];
            
            if ( !$fascia) {
                my_log('error', "Errore: sede o fascia non trovata per sedi_professionisti_id: " . $sede_professionista['sedi_professionisti_id']);
                continue;
            }
            //debug($sede_professionista,true);
            $appuntamento = [
                'appuntamenti_id' => $sede_professionista['sedi_professionisti_id'],
                'appuntamenti_impianto' => $sede_professionista['sedi_professionisti_sede'],
                'appuntamenti_fascia_oraria' => $fascia['projects_orari_id'],
                'appuntamenti_persone' => [$dipendenti[$sede_professionista['sedi_professionisti_associato']]],
                'appuntamenti_giorno' => $sede_professionista['sedi_professionisti_giorno'],
                'appuntamenti_ora_inizio' => $fascia['projects_orari_dalle'],
                'appuntamenti_ora_fine' => $fascia['projects_orari_alle'],
                'appuntamenti_all_day' => DB_BOOL_FALSE,
                'appuntamenti_da_confermare' => DB_BOOL_FALSE,
                'appuntamenti_annullato' => DB_BOOL_FALSE,
                'appuntamenti_titolo' => 'Appuntamento importato',
                'appuntamenti_creation_date' => date('Y-m-d H:i:s'),
                'appuntamenti_modified_date' => date('Y-m-d H:i:s'),
                'appuntamenti_affiancamento' => $sede_professionista['sedi_professionisti_affiancamento'] == 't' ? 1 : 0,
                'appuntamenti_studente'   => $sede_professionista['sedi_professionisti_studente'] == 't' ? 1 : 0,
            ];

            
            try {
                $appuntamento_exists = $this->db->get_where('appuntamenti', ['appuntamenti_id' => $appuntamento['appuntamenti_id']])->row_array();
                $_POST = $appuntamento;

                if ($appuntamento_exists) {
                    $appuntamento_creato = $this->apilib->edit('appuntamenti', $appuntamento['appuntamenti_id'], $appuntamento);
                } else {
                    $appuntamento_creato = $this->apilib->create('appuntamenti', $appuntamento);
                }

                $_POST = [];
            } catch (Exception $e) {
                my_log('error', "errore inserimento appuntamento: {$e->getMessage()}");
                debug($appuntamento);
                debug($e->getMessage(), true);
            }
        }

        $this->mycache->clearCache();
    }

    public function import_domiciliari()
    {
        set_log_scope('sync-domiciliari');
        $domiciliari = $this->apib_db->get('domiciliari')->result_array();

        $t = count($domiciliari);
        $c = 0;
        foreach ($domiciliari as $domiciliare) {
            progress(++$c, $t, 'import domiciliari vs customers');
            //MP: Questa forzatura serve per avere id univoci tra domiciliari e clienti (su apib_db sono due tabelle diverse, mentre qua importiamo tutto su customers quindi questo è l'unico trick rapido che mi è venuto in mente)
            $domiciliare['domiciliari_id'] = '9999' . $domiciliare['domiciliari_id'];
            $customer = [
                'customers_id' => $domiciliare['domiciliari_id'],
                'customers_name' => $domiciliare['domiciliari_nome'],
                'customers_last_name' => $domiciliare['domiciliari_cognome'],
                'customers_full_name' => $domiciliare['domiciliari_nome'] . ' ' . $domiciliare['domiciliari_cognome'],
                'customers_address' => $domiciliare['domiciliari_residenza_indirizzo'],
                'customers_city' => $domiciliare['domiciliari_residenza_comune'],
                'customers_province' => $domiciliare['domiciliari_residenza_provincia'],
                'customers_cf' => $domiciliare['domiciliari_codice_fiscale'],
                'customers_phone' => $domiciliare['domiciliari_telefono'],
                'customers_fax' => $domiciliare['domiciliari_fax'],
                'customers_mobile' => $domiciliare['domiciliari_cellulare'],
                'customers_email' => $domiciliare['domiciliari_email'],
                'customers_description' => $domiciliare['domiciliari_note'],
                'customers_status' => ($domiciliare['domiciliari_non_attivo'] == 't') ? 3 : 1,
                'customers_type' => 1, // Assumendo che 1 sia il tipo customer
                'customers_group' => 1, // Assumendo che 2 sia il tipo privato
                'customers_creation_date' => $domiciliare['domiciliari_data_creazione'],
                'customers_modified_date' => $domiciliare['domiciliari_data_modifica'],
            ];

            foreach ($domiciliare as $key => $value) {
                $key = str_replace('domiciliari_', 'customers_', $key);
                if (!array_key_exists($key, $customer)) {
                    $customer['customers_dati_apib'][$key] = $value;
                }

            }
            $customer['customers_dati_apib'] = json_encode($customer['customers_dati_apib']);

            try {
                $customer_exists = $this->db->get_where('customers', ['customers_id' => $customer['customers_id']])->row_array();
                $_POST = $customer;
                if ($customer_exists) {
                    $customer_creato = $this->apilib->edit('customers', $customer['customers_id'], $customer);
                } else {
                    $customer_creato = $this->apilib->create('customers', $customer);
                }

                $_POST = [];

            } catch (Exception $e) {
                my_log('error', "errore inserimento customer (domiciliare): {$e->getMessage()}");
                debug($customer);
                debug($e->getMessage(), true);
            }
        }
        $this->mycache->clearCache();
    }

    public function import_report_orari($offset = 0) {
        echo_flush('import rapportini vs report_orari');
        set_log_scope('sync-report-orari');
        if (is_cli()) {
            $limit = 9999999999;
        } else {
            $limit = 99;
        }
        
        
        $report_orari = $this->apib_db
            ->join('sedi_operative', 'sedi_operative_id = report_orari_sede_operativa', 'LEFT')
            //->where('report_orari_inizio >= ', '2024-01-01')
            
            ->limit($limit, $offset)
            ->order_by('report_orari_id', (is_cli()?'DESC':'ASC'))
            ->get('report_orari')->result_array();
        
$count_total = $this->apib_db
            
            //->where('report_orari_inizio >= ', '2024-01-01')
            
            ->count_all_results('report_orari');

        if (empty($report_orari)) {
            echo_log('debug', 'nessun report_orari da importare<br/>');
            return;
        }
        
        $all_dipendenti = $this->db->get('dipendenti')->result_array();
        $map_dipendente_user_id = array_column($all_dipendenti, 'dipendenti_user_id', 'dipendenti_id');
        
        $all_sedi_operative = $this->db->get('projects')->result_array();
        $map_sedi_operative_cliente = array_column($all_sedi_operative, 'projects_customer_id', 'projects_id');
        $all_sedi_operative_id = array_column($all_sedi_operative, 'projects_id');
        
        // debug($report_orari);
        
        /** VECCHIO -> report_orari
         * [report_orari_id] => 9347
         * [report_orari_data_creazione] => 2018-02-13 17:24:18
         * [report_orari_data_modifica] =>
         * [report_orari_associato] => 419
         * [report_orari_sede_operativa] => 9
         * [report_orari_note] =>
         * [report_orari_inizio] => 2018-02-12 20:00:00
         * [report_orari_fine] => 2018-02-13 07:00:00
         * [report_orari_accessi] =>
         * [report_orari_prestazioni] =>
         * [report_orari_domiciliare] =>
         * [report_orari_affiancamento] => f // da convertire in 0/1
         * [report_orari_fascia] => 20
         * [report_orari_costo_differenziato] => f // da convertire in 0/1
         * [report_orari_festivo] => f // da convertire in 0/1
         */
        
        /** NUOVO -> rapportini
         * [rapportini_id] => 1
         * [rapportini_cliente] => 11 // report_orari_associato
         * [rapportini_commessa] => 3 // report_orari_sede_operativa
         * [rapportini_note] =>
         * [rapportini_operatori] => 0
         * [rapportini_appuntamento_id] =>
         * [rapportini_data] => 2024-06-10
         * [rapportini_ora_inizio] => 10:30
         * [rapportini_ora_fine] => 12:45
         * [rapportini_immagini] =>
         * [rapportini_da_validare] => 1
         * [rapportini_firma_cliente] =>
         * [rapportini_firma_operatore] =>
         * [rapportini_foto] =>
         * [rapportini_compilazione_id] =>
         * [rapportini_codice] => 1-2024
         * [rapportini_servizi] => // array id della variabile servizi -> listino_prezzi_id
         * [rapportini_fascia] =>
         * [rapportini_costo_differenziato] =>
         * [rapportini_festivo] =>
         * [rapportini_accessi] =>
         * [rapportini_affiancamento] =>
         */
        
        $t = count($report_orari);
        $c = $offset;

        $_commesse_domiciliari = $this->db->where('projects_customer_id IN (SELECT customers_id FROM customers WHERE customers_group = 1)', null, false)->get('projects')->result_array();
        $commesse_domiciliari = array_key_value_map($_commesse_domiciliari, 'projects_customer_id', 'projects_id');
        
        foreach ($report_orari as $report_orario) {
            progress(++$c, $count_total, 'import report_orari - offset ' . $offset);
            
            if ($report_orario['report_orari_id'] != '195005') {
                //continue;
            } else {
                // debug($report_orario);
                // debug($all_sedi_operative_id,true);
            }

            // michael, 19/08/2024 - metto questo controllo perchè altrimenti va in errore postprocess in quanto ne la sede operativa ne il cliente esistono.
            if (!empty($report_orario['report_orari_sede_operativa']) && (!in_array($report_orario['report_orari_sede_operativa'], $all_sedi_operative_id))) {
                echo_log('debug', "SKIP report_orario {$report_orario['report_orari_id']} con sede operativa non trovata<br/>");
                continue;
            }
            
           

            if (empty($map_dipendente_user_id[$report_orario['report_orari_associato']])) {
                echo_log('debug', "SKIP report_orario per mancanza associato<br/>");
                continue;
            }
            
            $servizi = $this->apib_db
                ->where('report_orari_id', $report_orario['report_orari_id'])
                ->get('report_orari_listino_prezzi')->result_array();
            
            $id_listino_prezzi = [];
            if (!empty($servizi)) {
                $id_listino_prezzi = array_column($servizi, 'listino_prezzi_id');
                
            }
            
            // debug($servizi);
            
            $ora_inizio = date('H:i', strtotime($report_orario['report_orari_inizio']));
            $ora_fine = date('H:i', strtotime($report_orario['report_orari_fine']));
            
            // // michael, 19/08/2024 - forzo ora fine a 23:59 se è minore dell'ora inizio (il che vuol dire che andrebbe su un altro giorno, ossia turno notturno)
            // if ($ora_fine < $ora_inizio) {
            //     echo_log('debug', "FORZATURA MEZZANOTTE - ora fine inferiore a ora inizio: {$ora_fine} < {$ora_inizio}<br/>");
            //     $ora_fine = '23:59';
            // }
            
            // creo il rapportino
            $rapportino = [
                
                
                'rapportini_note' => $report_orario['report_orari_note'],
                
                'rapportini_appuntamento_id' => null,
                'rapportini_data' => date('Y-m-d', strtotime($report_orario['report_orari_inizio'])),
                'rapportini_ora_inizio' => $ora_inizio,
                'rapportini_ora_fine' => $ora_fine,
                'rapportini_da_validare' => 0,
                'rapportini_firma_cliente' => null,
                'rapportini_firma_operatore' => null,
                'rapportini_foto' => null,
                'rapportini_compilazione_id' => null,
                'rapportini_codice' => null,
                //'rapportini_servizi' => $id_listino_prezzi,
                'rapportini_fascia' => $report_orario['report_orari_fascia'],
                'rapportini_costo_differenziato' => $report_orario['report_orari_costo_differenziato'] == 't' ? 1 : 0,
                'rapportini_festivo' => $report_orario['report_orari_festivo'] == 't' ? 1 : 0,
                'rapportini_accessi' => $report_orario['report_orari_accessi'],
                'rapportini_affiancamento' => $report_orario['report_orari_affiancamento'] == 't' ? 1 : 0,
                'rapportini_id' => $report_orario['report_orari_id'],
                'rapportini_codice' => $c,
            ];

            

            if (!empty($report_orario['report_orari_domiciliare'])) {
                $report_orario['report_orari_domiciliare'] = '9999' . $report_orario['report_orari_domiciliare'];
                $rapportino['rapportini_cliente'] = $report_orario['report_orari_domiciliare'] ?: $map_sedi_operative_cliente[$report_orario['report_orari_sede_operativa']];
                $rapportino['rapportini_commessa'] = $commesse_domiciliari[$report_orario['report_orari_domiciliare']];
                
            } else {
                $rapportino['rapportini_cliente'] = $report_orario['sedi_operative_cliente'] ?: $map_sedi_operative_cliente[$report_orario['report_orari_sede_operativa']];
                $rapportino['rapportini_commessa'] = $report_orario['report_orari_sede_operativa'];
            }

            if ($report_orario['report_orari_id'] == '195005') {
                //debug($rapportino, true);
            }
            
            $_POST = $rapportino;
            try {
                $rapportino_db = $this->db->get_where('rapportini', ['rapportini_id' => $report_orario['report_orari_id']])->row_array();
                
                if ($rapportino_db) {
                    //$this->apilib->edit('rapportini', $report_orario['report_orari_id'], $rapportino);
                    $this->db->where('rapportini_id', $report_orario['report_orari_id'])->update('rapportini', $rapportino);
                    //echo_log('debug', "rapportino {$report_orario['report_orari_id']} modificato<br/>");
                } else {
                    
                    
                    //$this->apilib->create('rapportini', $rapportino);
                    $this->db->insert('rapportini', $rapportino);
                    
                    //echo_log('debug', "rapportino {$report_orario['report_orari_id']} creato<br/>");
                }
                //Inserisco gli operatori
                $this->db->where('rapportini_id', $report_orario['report_orari_id'])->delete('rel_rapportini_users');

                $this->db->insert('rel_rapportini_users', [
                    'rapportini_id' => $report_orario['report_orari_id'],
                    'users_id' => $map_dipendente_user_id[$report_orario['report_orari_associato']],
                ]);

                //Inserisco i servizi
                $this->db->where('rapportini_id', $report_orario['report_orari_id'])->delete('rel_rapportini_servizi_prodotti');
                if ($id_listino_prezzi) {
                    foreach ($id_listino_prezzi as $id_listino)
                    $this->db->insert('rel_rapportini_servizi_prodotti', [
                        'rapportini_id' => $report_orario['report_orari_id'],
                        'fw_products_id' => $id_listino,
                    ]);
                }

            } catch (Exception $e) {
                // michael, 19/08/2024 - ho messo questa distinzione perchè in alcuni casi va in errore ("throw" voluto) il post-process #6773 alla riga 66. in questo caso il rapportino NON viene creato.
                if (stripos($e->getMessage(), 'una rapportino') !== false) {
                    echo_log('debug', "rapportino {$report_orario['report_orari_id']} con intervallo inizio-fine in overlap con altro rapportino dello stesso utente (Errore ricevuto: {$e->getMessage()})<br/>");
                } else {
                    echo_log('error', "errore inserimento rapportino: {$e->getMessage()}");
                }
                debug($rapportino);
            }
            
            $_POST = [];
        }
        //die('TEST');
        // refresh page with offset increased by limit
        if (!is_cli()) {
            echo_flush('<script>setTimeout(function(){window.location.href = "' . base_url('custom/sync/import_report_orari/' . ($offset + $limit)) . '";}, 500);</script>');
        }
    }
    
    public function import_listino_prezzi() {
        $listini = $this->apib_db->get('listino_prezzi')->result_array();
        
        $t = count($listini);
        $c = 0;

        foreach ($listini as $listino) {
            progress(++$c, $t, 'import listino_prezzi');
            
            $servizio = [
                'fw_products_name' => $listino['listino_prezzi_servizio'],
                'fw_products_sell_price' => $listino['listino_prezzi_prezzo'],
            ];
            
            try {
                $servizio_db = $this->db->get_where('fw_products', ['fw_products_id' => $listino['listino_prezzi_id']])->row_array();

                if ($servizio_db) {
                    $this->apilib->edit('fw_products', $servizio_db['fw_products_id'], $servizio);
                } else {
                    $servizio['fw_products_id'] = $listino['listino_prezzi_id'];
                    
                    $this->apilib->create('fw_products', $servizio);
                }
            } catch (Exception $e) {
                echo_log('error', "errore inserimento servizio: {$e->getMessage()}");
            }
        }
        
    }

    public function import_variazioni($associato_id = null)
    {
        set_log_scope('sync-variazioni');
        if ($associato_id) {
            $this->apib_db->where('compensi_variazioni_associato', $associato_id);
        }
        $variazioni = $this->apib_db->get('compensi_variazioni')->result_array();

       

        $t = count($variazioni);
        $c = 0;
        foreach ($variazioni as $variazione) {
            progress(++$c, $t, 'import compensi_variazioni');
            $variazione['compensi_variazioni_importo'] = number_format($variazione['compensi_variazioni_importo'],2,',','.'); //formato italiano
            $variazione['compensi_variazioni_creation_date'] = $variazione['compensi_variazioni_data_creazione'];
            unset($variazione['compensi_variazioni_data_creazione']);
            $variazione['compensi_variazioni_modified_date'] = $variazione['compensi_variazioni_data_modifica'];
            unset($variazione['compensi_variazioni_data_modifica']);
            try {
                $variazione_exists = $this->db->get_where('compensi_variazioni', ['compensi_variazioni_id' => $variazione['compensi_variazioni_id']])->row_array();
                
                
                if ($variazione_exists) {
                    $variazione_creata = $this->apilib->edit('compensi_variazioni', $variazione['compensi_variazioni_id'], $variazione);
                } else {
                    $variazione_creata = $this->apilib->create('compensi_variazioni', $variazione);
                }


            } catch (Exception $e) {
                my_log('error', "errore inserimento variazione compensi: {$e->getMessage()}");
                debug($variazione);
                debug($e->getMessage(), true);
            }
        }
        $this->mycache->clearCache();
    }
    public function import_note_compensi($associato_id = null)
    {
        set_log_scope('sync-note_compensi');
        if ($associato_id) {
            $this->apib_db->where('allegati_per_scheda_compensi_associato', $associato_id);
        }
        $note = $this->apib_db->get('allegati_per_scheda_compensi')->result_array();



        $t = count($note);
        $c = 0;
        foreach ($note as $nota) {
            progress(++$c, $t, 'import note compensi');
            
            try {
                $nota_exists = $this->db->get_where('allegati_per_scheda_compensi', ['allegati_per_scheda_compensi_id' => $nota['allegati_per_scheda_compensi_id']])->row_array();


                if ($nota_exists) {
                    $nota_creata = $this->apilib->edit('allegati_per_scheda_compensi', $nota['allegati_per_scheda_compensi_id'], $nota);
                } else {
                    $nota_creata = $this->apilib->create('allegati_per_scheda_compensi', $nota);
                }


            } catch (Exception $e) {
                my_log('error', "errore inserimento variazione compensi: {$e->getMessage()}");
                debug($nota);
                debug($e->getMessage(), true);
            }
        }
        $this->mycache->clearCache();
    }
    public function import_tariffe() {
        set_log_scope('sync-tariffe');
        $tariffe = $this->apib_db->get('tariffe')->result_array();
        
        $categorie_map = [
                    1 => 1, //Mattina
                    2=> 2, //Pomeriggio
                    3 => 3, //Notte/Festivo
                    6 => 4 //Accesso

                ];

        $t = count($tariffe);
        $c = 0;
        
        foreach ($tariffe as $tariffa) {
            //debug($tariffa,true);
            progress(++$c, $t, 'import tariffe');

            // Format the monetary values to match the new system's format
            $tariffa['tariffe_costo_orario'] = number_format($tariffa['tariffe_costo_orario'], 2, ',', '.');
            $tariffa['tariffe_costo_accesso'] = number_format($tariffa['tariffe_costo_accesso'], 2, ',', '.');
            $tariffa['tariffe_costo_affiancamento'] = number_format($tariffa['tariffe_costo_affiancamento'], 2, ',', '.');
            $tariffa['tariffe_costo_extra'] = number_format($tariffa['tariffe_costo_extra'], 2, ',', '.');

            // Map creation and modification dates
            $tariffa['tariffe_creation_date'] = $tariffa['tariffe_data_creazione'] ?? null;
            unset($tariffa['tariffe_data_creazione']);
            $tariffa['tariffe_modified_date'] = $tariffa['tariffe_data_modifica'] ?? null;
            unset($tariffa['tariffe_data_modifica']);

            $tariffa['tariffe_categoria'] = $categorie_map[$tariffa['tariffe_categoria']]??null;

            try {
                $tariffa_exists = $this->db->get_where('tariffe', ['tariffe_id' => $tariffa['tariffe_id']])->row_array();
                
                if ($tariffa_exists) {
                    $tariffa_creata = $this->apilib->edit('tariffe', $tariffa['tariffe_id'], $tariffa);
                } else {
                    $tariffa_creata = $this->apilib->create('tariffe', $tariffa);
                }

            } catch (Exception $e) {
                my_log('error', "errore inserimento tariffa: {$e->getMessage()}");
                debug($tariffa);
                debug($e->getMessage(), true);
            }
        }
        
        $this->mycache->clearCache();
    }
}
