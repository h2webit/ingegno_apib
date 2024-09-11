<?php

class Hr extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('timbrature');
    }

    public function createCedoliniFromImport($import_id)
    {

        if (empty($import_id)) {
            return false;
        }

        $import = $this->db->where('import_cedolini_id', $import_id)->get('import_cedolini')->row_array();

        if ($import['import_cedolini_imported'] != DB_BOOL_FALSE) {
            return false;
        }

        $cedolini = json_decode($import['import_cedolini_files'], true);

        foreach ($cedolini as $cedolino) {

            if (!empty($cedolino['dipendenti_id'])) {

                $documento['documenti_dipendenti_nome_documento'] = "Cedolino caricato il " . date('d/m/Y');
                $documento['documenti_dipendenti_dipendente'] = $cedolino['dipendenti_id'];
                $documento['documenti_dipendenti_categoria'] = 2;
                $documento['documenti_dipendenti_file'] = $cedolino['path_local'];
                $this->apilib->create('documenti_dipendenti', $documento);
            }
        }

        $this->apilib->edit('import_cedolini', $import['import_cedolini_id'], ['import_cedolini_imported' => DB_BOOL_TRUE]);

        echo json_encode(['status' => 4, 'txt' => 'Operazione completata.']);
    }

    public function createDipendenti()
    {
        $users = $this->apilib->search('users', ['users_id NOT IN (SELECT dipendenti_user_id FROM dipendenti)']);

        if (!empty($users)) {
            foreach ($users as $user) {
                try {
                    $this->db->insert('dipendenti', [
                        'dipendenti_existing_user' => DB_BOOL_TRUE,
                        'dipendenti_user_id' => $user['users_id'],
                        'dipendenti_email' => $user['users_email'],
                        'dipendenti_password' => $user['users_password'],
                        'dipendenti_tipologia' => $user['users_type'],
                        'dipendenti_nome' => $user['users_first_name'] ?? '',
                        'dipendenti_cognome' => $user['users_last_name'] ?? '',
                        'dipendenti_foto' => $user['users_avatar'] ?? null,
                    ]);
                } catch (Exception $e) {
                    log_message('error', $e->getMessage());
                    throw new Exception('Impossibile importare i dipendenti.');
                    exit;
                }
            }
            redirect(base_url('main/layout/dipendenti'));
        } else {
            throw new Exception('Necessario creare gli utenti prima di associarvi i dipendenti');
            exit;
        }
    }

    public function loadHours($id)
    {
        $dipendente = $this->apilib->view('dipendenti', $id);

        if (!empty($dipendente)) {

            for ($i = 1; $i <= 5; $i++) {
                $orario = $this->apilib->searchFirst('turni_di_lavoro', ['turni_di_lavoro_dipendente' => $dipendente['dipendenti_id'], 'turni_di_lavoro_giorno' => $i]);
                $today = date('Y-m-d');
                if (!empty($orario)) {
                    //edit
                    try {
                        $this->apilib->edit('turni_di_lavoro', $orario['orari_di_lavoro_id'], [
                            'turni_di_lavoro_ora_inizio' => '09:00',
                            'turni_di_lavoro_ora_fine' => '18:00',
                            'turni_di_lavoro_pausa' => '1',
                        ]);
                    } catch (Exception $e) {
                        throw new Exception('Impossibile modificare gli orari per il dipendente selezionato');
                        exit;
                    }
                } else {
                    //create
                    try {
                        $this->apilib->create('turni_di_lavoro', [
                            'turni_di_lavoro_dipendente' => $id,
                            'turni_di_lavoro_giorno' => $i,
                            'turni_di_lavoro_ora_inizio' => '09:00',
                            'turni_di_lavoro_ora_fine' => '18:00',
                            'turni_di_lavoro_pausa' => '1',
                            'turni_di_lavoro_data_inizio' => $today
                        ]);
                    } catch (Exception $e) {
                        throw new Exception('Impossibile creare gli orari per il dipendente selezionato');
                        exit;
                    }
                }
            }
            redirect(base_url('main/layout/dettaglio-dipendente/' . $id));
        } else {
            throw new Exception('Dipendente non trovato.');
            exit;
        }
    }



    //Viene eseguito ogni minuto e gestisce le chiusure automatiche (sia a cavallo della mezzanotte che al superamento dell'orario lavorativo (con straordinari non consentiti))
    public function cronTimbrature()
    {
        $this->timbrature->scope('CRON');
        $scope = 'CRON';

        $impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
        //Matteo: DUBBIONE SU QUESTA RIGA: se fosse impostato 0 perchè non si vuole tolleranza? Il sistema forzerebbe 30... o sbaglio?
        $range_uscita = $impostazioni_modulo['impostazioni_hr_range_minuti_uscita'] ?? 30;
        // Flag attivazione chiusura automatica
        $chiusura_automatica = $impostazioni_modulo['impostazioni_hr_attiva_chiusura_automatica'] ?? DB_BOOL_FALSE;
        // Orario in cui devo provare a chiudere le presenze
        $ora_chiusura_automatica = $impostazioni_modulo['impostazioni_hr_ora_chiusura_automatica'] ?? null;
        // Flag per chiudere anche chi fa straordinario
        $chiusura_dipendenti_straordinario = $impostazioni_modulo['impostazioni_hr_chiusura_dipendenti_straordinario'] ?? DB_BOOL_FALSE;


        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('yesterday'));
        $today_hour = date('H:i');

        $this->db->where("presenze_data_inizio IS NULL
        OR presenze_data_inizio = ''
        OR presenze_ora_inizio IS NULL
        OR presenze_ora_inizio = ''")->delete('presenze');

        $this->mycache->clearEntityCache('presenze');
        //prendo tutte le presenze aperte
        $presenze_aperte = $this->apilib->search('presenze', [
            "presenze_data_fine IS NULL
        OR presenze_data_fine = ''
        OR presenze_ora_fine IS NULL
        OR presenze_ora_fine = ''",
        ]);

        $c = 0;
        $total = count($presenze_aperte);
        progress($c, $total);

        foreach ($presenze_aperte as $presenza) {
            $scope = 'CRON';

            $c++;
            progress($c, $total);

            $consenti_straordinario = $presenza['dipendenti_consenti_straordinari'];
            //debug($presenza);



            $orari_lavoro = $this->timbrature->getTurnoLavoro($presenza['presenze_data_inizio'], $presenza['presenze_dipendente']);
            $suggerimentoTurno = $this->timbrature->suggerisciTurno($today_hour, $orari_lavoro, 'uscita');
            $orario_lavoro = $orari_lavoro[$suggerimentoTurno];

            if (!$orario_lavoro) {
                $orario_lavoro['turni_di_lavoro_ora_inizio'] = '09:00';
                $orario_lavoro['turni_di_lavoro_ora_fine'] = '18:00';
            }

            // Cambiato da 23:59 a 00:00 perchè saltava nel caso di presenze che devono essere tutte straordinarie
            // Visto che il metodo trovato è impostare come orario 00:00 - 23:59
            if ($orario_lavoro['turni_di_lavoro_ora_fine'] == '00:00') {
                debug($presenza);
                debug($orario_lavoro);
                debug('Blocco! Da capire come gestire un orario di fine a cavallo della mezzanotte!');
                log_message('debug', "Da capire come gestire un orario di fine a cavallo della mezzanotte!");
                continue;
            }

            $richieste = $this->apilib->search('richieste', [
                'richieste_user_id' => $presenza['presenze_dipendente'],
                'richieste_stato' => 2, //solo richieste approvate
                //TODO: aggiungere filtro che questi permessi siano nel periodo della timbratura
            ]);

            $ora_fine_profilo = new DateTime($orario_lavoro['turni_di_lavoro_ora_fine']);
            $presenza_data_inizio = substr($presenza['presenze_data_inizio'], 0, 10);
            //$presenza_chiusa = false;

            foreach ($richieste as $richiesta) {
                //se la richiesta comprende oggi
                if (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= $today && dateFormat($richiesta['richieste_al'], 'Y-m-d') >= $today) {
                    if ($richiesta['richieste_tipologia'] == 1) { //PERMESSO
                        if ($today_hour >= $richiesta['richieste_ora_inizio'] && $today_hour <= $richiesta['richieste_ora_fine']) {
                            log_message('debug', "CRON - Trovata presenza {$presenza['presenze_id']} che si sovrappone con richiesta permesso");
                            // $this->timbrature->timbraUscita($presenza['presenze_dipendente'], $richiesta['richieste_ora_inizio'], $today, $presenza['presenze_id'], $scope);
                            // $presenza_chiusa = true;
                            $note_vecchie = ($presenza['presenze_note_anomalie']) ? "{$presenza['presenze_note_anomalie']}\n\r" : '';
                            $presenza['presenze_note_anomalie'] = "{$note_vecchie}[CRON] (" . date('H:i') . ") La presenza si sovrappone con una richiesta di permesso";
                        }
                    } elseif ($richiesta['richieste_tipologia'] == 2) { //FERIE
                        log_message('debug', "CRON - Trovata presenza {$presenza['presenze_id']} che si sovrappone con richiesta ferie");
                        // $this->timbrature->timbraUscita($presenza['presenze_dipendente'], '23:59', date('Y-m-d', strtotime($today . ' -1 day')), $presenza['presenze_id'], $scope);
                        // $presenza_chiusa = true;
                        $note_vecchie = ($presenza['presenze_note_anomalie']) ? "{$presenza['presenze_note_anomalie']}\n\r" : '';
                        $presenza['presenze_note_anomalie'] = "{$note_vecchie}[CRON] (" . date('H:i') . ") La presenza si sovrappone con una richiesta di ferie";
                    } elseif ($richiesta['richieste_tipologia'] == 3) { //MALATTIA
                        log_message('debug', "CRON - Trovata presenza {$presenza['presenze_id']} che si sovrappone con richiesta malattia");
                        // $this->timbrature->timbraUscita($presenza['presenze_dipendente'], $today_hour, '', $presenza['presenze_id'], $scope);
                        // $presenza_chiusa = true;
                        $note_vecchie = ($presenza['presenze_note_anomalie']) ? "{$presenza['presenze_note_anomalie']}\n\r" : '';
                        $presenza['presenze_note_anomalie'] = "{$note_vecchie}[CRON] (" . date('H:i') . ") La presenza si sovrappone con una richiesta di malattia";
                    }
                } elseif (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= $today && empty($richiesta['richieste_al']) && $richiesta['richieste_tipologia'] == 3) {
                    log_message('debug', "CRON - Trovata presenza {$presenza['presenze_id']} che si sovrappone con richiesta malattia senza data fine");
                    //se la richiesta è senza data fine ed è MALATTIA
                    // $this->timbrature->timbraUscita($presenza['presenze_dipendente'], $today_hour, '', $presenza['presenze_id'], $scope);
                    // $presenza_chiusa = true;
                    $note_vecchie = ($presenza['presenze_note_anomalie']) ? "{$presenza['presenze_note_anomalie']}\n\r" : '';
                    $presenza['presenze_note_anomalie'] = "{$note_vecchie}[CRON] (" . date('H:i') . ") La presenza si sovrappone con una richiesta di malattia senza data di fine";
                }
            }


            //if (!$presenza_chiusa) {
            $dipendente = $this->apilib->view('dipendenti', $presenza['presenze_dipendente']);
            $ignora_orari_lavoro = $dipendente['dipendenti_ignora_orari_lavoro'] ?? DB_BOOL_FALSE;

            /**
             * !  Se entro in questo if vuol dire che è passata la mezzanote e ho timbrature aperte del giorno prima
             **/
            if (($presenza_data_inizio < $today) and $dipendente['dipendenti_consenti_straordinari'] == DB_BOOL_FALSE) {
                //Se devo ingorare orari chiudo con ora fine = ora inizio + ore standard
                if ($ignora_orari_lavoro == DB_BOOL_TRUE && (!empty($dipendente['dipendenti_ore_standard']) || $dipendente['dipendenti_ore_standard'] >= 0)) {
                    $entrata = str_ireplace(' 00:00:00', '', $presenza['presenze_data_inizio']) . ' ' . $presenza['presenze_ora_inizio'];
                    $ore_standard = $dipendente['dipendenti_ore_standard'];
                    $inizio = new DateTime($entrata);
                    $ora_uscita = $inizio->modify("+{$ore_standard} hours");

                    //Se ha timbrato come straordinario e ora non lo è più
                    log_message('debug', "Chiudo presenza {$presenza['presenze_id']} perchè superata la mezzanotte e superate le ore giornaliere");
                    //Aggiunto controllo perchè può capitare che il sistema cerchi di chiudere una presenza con un'orario < dell'entrata se sono dopo la mezzanotte
                    $ora_fine_temp = $ora_uscita->format('H:i') < $presenza['presenze_ora_inizio'] ? '23:59' : $ora_uscita->format('H:i');
                    $this->timbrature->timbraUscita($presenza['presenze_dipendente'], $ora_fine_temp, $presenza_data_inizio, $presenza['presenze_id'], $scope);
                } else {
                    // Chiudo arbitrariamente alle 23:59
                    $this->timbrature->timbraUscita($presenza['presenze_dipendente'], '23:59', $presenza_data_inizio, $presenza['presenze_id'], $scope);
                    log_message('debug', "Chiudo presenza {$presenza['presenze_id']} perchè superata la mezzanotte");
                }
            } else if ($chiusura_automatica) { //Se è attiva la chiusura automatica e sono nello stesso giorno dell'apertura presenza
                //Se devo ingorare orari chiudo con ora fine = ora inizio + ore standard solo se ho superato le ore totali
                if ($ignora_orari_lavoro == DB_BOOL_TRUE && (!empty($dipendente['dipendenti_ore_standard']) && $dipendente['dipendenti_ore_standard'] >= 0)) {
                    $ore_standard = $dipendente['dipendenti_ore_standard'];

                    //Calcolo ora di fine che dovrei avere
                    $entrata = str_ireplace(' 00:00:00', '', $presenza['presenze_data_inizio']) . ' ' . $presenza['presenze_ora_inizio'];
                    $inizio = new DateTime($entrata);
                    $temp_inizio = new DateTime($entrata);
                    $ora_uscita = $temp_inizio->modify("+{$ore_standard} hours");
                    //Calcolo differenza tra entrata ed uscita
                    $uscita = str_ireplace(' 00:00:00', '', $presenza['presenze_data_inizio']) . ' ' . $today_hour;
                    $fine = new DateTime($uscita);
                    $diff_date = $fine->diff($inizio);
                    $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);

                    //Se la differenza tra ora inizio e ora attuale è > delle ore standard devo chiudere
                    if ($hours > $ore_standard) {
                        if ($consenti_straordinario == DB_BOOL_FALSE) {
                            //Se ha timbrato come straordinario e ora non lo è più
                            log_message('debug', "Chiudo presenza {$presenza['presenze_id']} perchè superate le ore giornaliere standard");
                            $this->timbrature->timbraUscita($presenza['presenze_dipendente'], $ora_uscita->format('H:i'), $presenza_data_inizio, $presenza['presenze_id'], $scope);
                        } else {
                            //Non devo fare nulla, posso fare straordinari
                        }
                    } else {
                        //Non devo fare nulla, sono ancora entro le ore giornaliere
                    }
                } elseif ($orario_lavoro['turni_di_lavoro_ora_fine'] < $today_hour && $dipendente['dipendenti_consenti_straordinari'] == DB_BOOL_FALSE) {
                    //Chiudo solo se non è stato aperto uno straordinario
                    log_message('debug', "Chiusura presenza {$presenza['presenze_id']} perchè superato orario lavorativo e dipendente senza straordinario");
                    $this->timbrature->timbraUscita($presenza['presenze_dipendente'], $orario_lavoro['turni_di_lavoro_ora_fine'], $today, $presenza['presenze_id'], $scope);
                } elseif ($chiusura_dipendenti_straordinario == DB_BOOL_TRUE && !empty($ora_chiusura_automatica) && $dipendente['dipendenti_consenti_straordinari'] == DB_BOOL_TRUE) {
                    /**
                     * ! 25/01/2024 - CHIUSURA PER STRAORDINARIO
                     * ! Se il sistema DEVE chiudere la presenza anche per chi fa straordinario, l'ora in cui deve farlo è impostata
                     * ! ed il dipendente in questione puo fare straordinari devo chiudere la presenza con l'ora delle impostazioni
                     */
                    $scope = 'CRON - CHIUSURA PER STRAORDINARIO';

                    

                    if ($orario_lavoro['turni_di_lavoro_ora_fine'] < $today_hour && $orario_lavoro['turni_di_lavoro_ora_fine'] < $ora_chiusura_automatica && ($today_hour >= '23:48' && $today_hour <= '23:59')) {
                        //if($orario_lavoro['turni_di_lavoro_ora_fine'] < $today_hour && $orario_lavoro['turni_di_lavoro_ora_fine'] < $ora_chiusura_automatica) {
                        log_message('debug', "Chiusura presenza {$presenza['presenze_id']} con orari nei settings perchè superato orario lavorativo, posso fare straordinari ma sistema chiude anche per chi fa straordinari");
                        //$this->timbrature->timbraUscita($presenza['presenze_dipendente'], $ora_chiusura_automatica, $today, $presenza['presenze_id'], $scope);
                        $this->timbrature->timbraUscita($presenza['presenze_dipendente'], $ora_chiusura_automatica, $presenza['presenze_data_inizio'], $presenza['presenze_id'], $scope);
                        
                    } else {
                        // La fine del turno è prima dell'orario di chiusura automatica
                    }
                } elseif ($dipendente['dipendenti_consenti_straordinari'] == DB_BOOL_TRUE && ($today_hour >= '23:48' && $today_hour <= '23:59')) {
                    //Aggiunto 26/01 - In maniera tale che se posso fare straordinari male che vada la mia presenza verrà chiusa alle 23:59
                    log_message('debug', "Chiusura presenza {$presenza['presenze_id']} per dipendente con straordinario per non andare in una nuova giornata");
                    $this->timbrature->timbraUscita($presenza['presenze_dipendente'], '23:59', $presenza['presenze_data_inizio'], $presenza['presenze_id'], $scope);
                } else {
                    //Va bene così
                }
            } else { //Se arrivo qui non è mezzanotte e non è attiva la chiusura automatica quindi non faccio niente
                debug("Chiusura automatica non attiva!");
            }
            //}
        }
        //die('FINITO.');
    }

    public function getOrari()
    {
        $dipendente = $this->input->post('dipendente');
        $data_selezionata = $this->input->post('data_entrata');

        if (empty($dipendente) || empty($data_selezionata)) {
            die(json_encode(['status' => 1, 'txt' => 'Dipendente e/o data non riconosciuti']));
            exit;
        }

        $data_entrata = DateTime::createFromFormat('d/m/Y', $data_selezionata)->format('Y-m-d');

        $this->db->select('*');
        $this->db->from('turni_di_lavoro');
        $this->db->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', 'LEFT');
        $this->db->where('turni_di_lavoro_data_inizio <=', $data_entrata);
        $this->db->where('turni_di_lavoro_dipendente', $dipendente);

        //se oggi vedo anche l'inizio
        if ($data_entrata == date('Y-m-d')) {
            $this->db->where('turni_di_lavoro_ora_inizio <', date('H:i'));

        }
        $this->db->where('(turni_di_lavoro_data_fine >= ' . $this->db->escape($data_entrata) . ' OR turni_di_lavoro_data_fine IS NULL)');
        $this->db->where('turni_di_lavoro_giorno', date('N', strtotime($data_entrata)));
        $turni = $this->db->get()->result_array();

        // Popolo a priori con il primo risultato che trovo
        if (!empty($turni)) {
            $turno = $turni[0];
            die(json_encode(['status' => 0, 'txt' => 'Turno trovato', 'data' => $turno]));
            exit;
        } else {
            die(json_encode(['status' => 1, 'txt' => 'Nessun turno trovato']));
            exit;
        }
    }


    /**
     * ! Cron per la creazione delle presenze da richieste
     * ! Crea presenze per le richieste approvate che non sono ancora associate ad una presenza, in base ad orari permesso o turni se la richiesta è di altro tipo
     * ! Esclude a priori richieste di disponibilità e indisponibilità
     */
    public function cronPresenzeRichieste()
    {
        $impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
        $crea_presenze = $impostazioni_modulo['impostazioni_hr_crea_presenze_da_richieste'] ?? DB_BOOL_FALSE;

        if ($crea_presenze == DB_BOOL_TRUE) {
            $today = date('N');

            $richieste_approvate = $this->db->query("SELECT * FROM richieste LEFT JOIN presenze ON richieste_id = presenze_richiesta WHERE richieste_tipologia NOT IN (6,7) AND richieste_stato = '2' AND presenze_richiesta IS NULL ORDER BY richieste_id ASC")->result_array();

            if (!empty($richieste_approvate)) {
                $c = 0;
                $total = count($richieste_approvate);
                progress($c, $total);

                foreach ($richieste_approvate as $richiesta) {
                    $c++;
                    progress($c, $total);

                    $this->timbrature->creaPresenzaDaRichiesta($richiesta);
                }
            }
            //die('FINITO.');
        } else {
            echo ('Creazione presenze da richiesta non abilitata');
        }
    }

    //Imposta le ore ordinarie su tutte quante le presenze come differenza tra totale e straordinario
/*public function setOreOrdinarie() {
    $sql = "
    SELECT * FROM presenze
    WHERE (presenze_data_fine IS NOT NULL OR presenze_data_fine != '')
    AND (presenze_ora_fine IS NOT NULL OR presenze_ora_fine != '')
    ORDER BY presenze_id ASC
    ";
    $presenze = $this->db->query($sql)->result_array();

    if(!empty($presenze)) {
        foreach($presenze as $presenza) {
            $ore_tot = $presenza['presenze_ore_totali'] ?? 0;
            $ore_straordinarie = $presenza['presenze_straordinario'] ?? 0;
            $ore_ordinarie = round($ore_tot - $ore_straordinarie, 2);
            
            $this->db->where('presenze_id', $presenza['presenze_id'])->update('presenze', [
                'presenze_ore_ordinarie' => $ore_ordinarie
            ]);
        }
        $this->mycache->clearEntityCache('presenze');
    }
}*/
    public function imposta_filtro_presenze($anno, $mese)
    {

        $field_id = $this->db->query("SELECT * FROM fields WHERE fields_name = 'presenze_data_inizio'")->row()->fields_id;

        $filtro_presenze = (array) @$this->session->userdata(SESS_WHERE_DATA)['filter-presenze'];

        if ($mese !== null) {
            $filtro_presenze[$field_id] = [
                'value' => '01/' . $mese . '/' . $anno . ' - ' . date('t', strtotime($mese . '/01/' . $anno)) . '/' . $mese . '/' . $anno,
                'field_id' => $field_id,
                'operator' => 'eq',
            ];
        } else {
            $filtro_presenze[$field_id] = [
                'value' => '01/01/' . $anno . ' - 31/12/' . $anno,
                'field_id' => $field_id,
                'operator' => 'eq',
            ];
        }

        if (array_key_exists('0', $filtro_presenze)) {
            unset($filtro_presenze[0]);
        }

        $filtro = $this->session->userdata(SESS_WHERE_DATA);
        $filtro['filter-presenze'] = $filtro_presenze;
        $this->session->set_userdata(SESS_WHERE_DATA, $filtro);
        echo json_encode(['success' => true]);

    }
    public function sposto_straordinario($presenza, $funzione)
    {
        $response = ['success' => false, 'message' => 'Operazione non riuscita'];

        if ($funzione == '1') {
            //sposto verso la banca ore
            $presenza_dettaglio = $this->apilib->searchFirst('presenze', ['presenze_id' => $presenza]);
            if (!empty($presenza_dettaglio)) {
                if (!empty($presenza_dettaglio['presenze_straordinario'])) {
                    try {
                        $this->db->insert('banca_ore', [
                            'banca_ore_dipendente' => $presenza_dettaglio['presenze_dipendente'],
                            'banca_ore_creato_da_presenza' => $presenza,
                            'banca_ore_movimento' => 1, //aggiunta
                            'banca_ore_hours' => $presenza_dettaglio['presenze_straordinario'],
                            'banca_ore_data' => $presenza_dettaglio['presenze_data_inizio']
                        ]);

                        $this->db->where('presenze_id', $presenza)->update('presenze', [
                            'presenze_straordinario' => 0
                        ]);
                        $response = ['success' => true, 'message' => 'Operazione completata con successo'];
                    } catch (Exception $e) {
                        log_message('error', $e->getMessage());
                        throw new Exception('Impossibile aggiungere in banca ore.');
                        exit;
                    }

                }
            }

        } elseif ($funzione == '2') {
            //sposto verso gli straordinari
            $banca_ore = $this->apilib->searchFirst('banca_ore', ['banca_ore_creato_da_presenza' => $presenza, 'banca_ore_movimento' => 1]);
            if (!empty($banca_ore)) {
                try {
                    $this->db->where('presenze_id', $presenza)->update('presenze', [
                        'presenze_straordinario' => $banca_ore['banca_ore_hours']
                    ]);

                    $this->db->where('banca_ore_id', $banca_ore['banca_ore_id'])->update('banca_ore', [
                        'banca_ore_movimento' => 2
                    ]);
                    $response = ['success' => true, 'message' => 'Operazione completata con successo'];

                } catch (Exception $e) {
                    log_message('error', $e->getMessage());
                    throw new Exception('Impossibile aggiungere in banca ore.');
                    exit;
                }
            }

        }
        $this->mycache->clearEntityCache('presenze');
        $this->mycache->clearEntityCache('banca_ore');
        // Risposta JSON
        header('Content-Type: application/json');
        echo json_encode($response);
    }
    public function normalizzagiornata($dipendente, $giorno)
    {
        $skipDipendente = false; // Aggiunta variabile di controllo
        $scope = 'NORMALIZZAZIONE GIORNATA';

        /* PRENDO IL DIPENDENTE */
        $dipendente_dettaglio = $this->apilib->searchFirst('dipendenti', [
            'dipendenti_id' => $dipendente,
        ]);
        /* PRENDO TUTTE LE PRESENZE */
        $presenze = $this->apilib->search('presenze', [
            'presenze_data_inizio >=' => "$giorno 00:00:00",
            'presenze_data_inizio <=' => "$giorno 23:59:59",
            'presenze_dipendente' => $dipendente,
            'presenze_richiesta IS NULL'
        ]);
        if (!empty($presenze)) {
            foreach ($presenze as $presenza) {
                $this->apilib->delete('presenze', $presenza['presenze_id']);
            }
        }

        /* PRENDO I TURNI */
        $this->db->select('*');
        $this->db->from('turni_di_lavoro');
        $this->db->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', 'LEFT');
        $this->db->where('turni_di_lavoro_data_inizio <=', $giorno);
        $this->db->where('turni_di_lavoro_dipendente', $dipendente);
        $this->db->where('(turni_di_lavoro_data_fine >= ' . $this->db->escape($giorno) . ' OR turni_di_lavoro_data_fine IS NULL)');
        $this->db->where('turni_di_lavoro_giorno', date('N', strtotime($giorno)));
        $orari_lavoro = $this->db->get()->result_array();

        if (!empty($orari_lavoro)) {
            //Cancello prima le presenze NON legate ad una richiesta
            foreach ($orari_lavoro as $orario_lavoro) {
                $ora_entrata = $orario_lavoro['turni_di_lavoro_ora_inizio'];
                $ora_uscita = $orario_lavoro['turni_di_lavoro_ora_fine'];

                /* CERCO EVENTUALI RICHIESTE */
                $richieste = $this->apilib->search('richieste', [
                    'richieste_user_id' => $dipendente,
                    'richieste_stato' => 2, //solo richieste approvate
                ]);

                if (!empty($richieste)) {
                    foreach ($richieste as $richiesta) {
                        if (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= $giorno && dateFormat($richiesta['richieste_al'], 'Y-m-d') >= $giorno) {
                            if ($richiesta['richieste_tipologia'] == 1) { //PERMESSO
                                if ($ora_entrata >= $richiesta['richieste_ora_inizio'] && $ora_entrata <= $richiesta['richieste_ora_fine']) {
                                    $ora_entrata = $richiesta['richieste_ora_fine'];
                                }
                            } elseif ($richiesta['richieste_tipologia'] == 2) { //FERIE
                                $skipDipendente = true; // Imposta la variabile di controllo a true
                                break; // Esci dal loop delle richieste
                            } elseif ($richiesta['richieste_tipologia'] == 3) { //MALATTIA
                                $skipDipendente = true; // Imposta la variabile di controllo a true
                                break; // Esci dal loop delle richieste
                            }
                        } elseif (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= $giorno && empty($richiesta['richieste_al']) && $richiesta['richieste_tipologia'] == 3) {
                            $skipDipendente = true; // Imposta la variabile di controllo a true
                            break; // Esci dal loop delle richieste
                        }
                    }
                }
                if ($skipDipendente) {
                    break;
                }
                /* CALCOLO INIZIO E FINE CALENDAR */
                $data_inizio_calendar = $giorno;
                $ora_inizio_calendar = $ora_entrata;
                $dal = (new DateTime($data_inizio_calendar))->format('Y-m-d');
                $inizio_calendar = $dal . ' ' . $ora_inizio_calendar;

                $data_fine_calendar = $giorno;
                $ora_fine_calendar = $ora_uscita;
                $al = (new DateTime($data_fine_calendar))->format('Y-m-d');
                $fine_calendar = $al . ' ' . $ora_fine_calendar;

                /* CALCOLO ORE TOTALI */
                $inizio = new DateTime($giorno . ' ' . $ora_entrata . ":00");
                $fine = new DateTime($giorno . ' ' . $ora_uscita . ":00");
                $diff_date = $fine->diff($inizio);
                $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);
                /*$pausa = $orario_lavoro['orari_di_lavoro_ore_pausa_value'] ?? 0;
                $hours -= $pausa;*/

                /* TROVO COSTI ORARI */
                $costo_orario = $dipendente_dettaglio['dipendenti_costo_orario'];
                $valore_orario = $dipendente_dettaglio['dipendenti_valore_orario'];
                $costo_totale = $costo_orario * $hours;
                $valore_totale = $valore_orario * $hours;

                /* IMPOSTA REPARTO, SE ASSOCIATO */
                // Prendo reparti dipendente, se uno solo lo imposto in automatico su presenza
                $reparto = null;
                $reparti = $this->db->query("SELECT * FROM reparti WHERE reparti_id IN (SELECT reparti_id FROM rel_reparto_dipendenti WHERE dipendenti_id = '{$dipendente}')")->result_array();
                if (!empty($reparti) && count($reparti) == 1) {
                    $reparto = $reparti[0]['reparti_id'];
                }

                $data = array(
                    'presenze_dipendente' => $dipendente,
                    'presenze_data_inizio' => $giorno,
                    'presenze_ora_inizio' => $ora_entrata,
                    'presenze_data_inizio_calendar' => $inizio_calendar,
                    'presenze_scope_create' => $scope,
                    'presenze_ora_inizio_effettivo' => $ora_entrata,
                    'presenze_data_fine' => $giorno,
                    'presenze_ora_fine' => $ora_uscita,
                    'presenze_data_fine_calendar' => $fine_calendar,
                    'presenze_ora_fine_effettiva' => $ora_uscita,
                    'presenze_ore_totali' => $hours,
                    'presenze_straordinario' => 0,
                    'presenze_costo_orario' => $costo_orario,
                    'presenze_valore_orario' => $valore_orario,
                    'presenze_costo_giornaliero' => $costo_totale,
                    'presenze_valore_giornaliero' => $valore_totale,
                    'presenze_reparto' => $reparto
                );

                $this->db->insert('presenze', $data);

            }
        }
        $this->mycache->clearEntityCache('presenze');

        die(json_encode(['status' => 2, 'related_entity' => 'presenze', 'refresh_grids' => 1]));

    }

    public function riprocessa_presenze()
    {
        $post = $this->input->post();

        $anno = !empty($post['anno']) ? $post['anno'] : date('Y');
        $mese = !empty($post['mese']) ? $post['mese'] : '01';
        $dipendente = !empty($post['dipendente']) ? $post['dipendente'] : null;

        $ultimo_giorno = date('t', strtotime("$anno-$mese-01"));


        //Svuoto la banca_ore in base ai filtri
        $this->db
            ->where('banca_ore_data >=', "$anno-$mese-01")
            ->where('banca_ore_data <=', "$anno-$mese-$ultimo_giorno");
        if (!empty($dipendente)) {
            $this->db->where('banca_ore_dipendente', $dipendente);
        }
        $this->db->delete('banca_ore');
        $this->mycache->clearEntityCache('banca_ore');

        // $presenze = $this->apilib->search('presenze', [
        //     'DATE(presenze_data_inizio) >=' => "$anno-$mese-01",
        //     'DATE(presenze_data_inizio) <=' => "$anno-$mese-$ultimo_giorno",
        //    (!empty($dipendente) ? "presenze_dipendente = $dipendente" : null)
        // ]);
        $this->db
            ->where('DATE(presenze_data_inizio) >=', "$anno-$mese-01")
            ->where('DATE(presenze_data_inizio) <=', "$anno-$mese-$ultimo_giorno");
        if (!empty($dipendente)) {
            $this->db->where('presenze_dipendente', $dipendente);
        }
        
        $this->db->order_by('presenze_data_inizio,presenze_ora_inizio_effettivo', 'ASC');
        $presenze = $this->db->get('presenze')->result_array();
        $count = count($presenze);
        $i = 0;
        $olddate = '';

        if ($this->datab->module_installed('long-operations')) {
            $this->load->model('long-operations/longoperations', 'long_operations');
            $use_long_operations_system = true;
        } else {
            $use_long_operations_system = false;
        }

        foreach ($presenze as $key => $presenza) {
            progress(++$i, $count);
            if ($olddate != substr($presenza['presenze_data_inizio'], 0, 10)) {
                $olddate = substr($presenza['presenze_data_inizio'], 0, 10);
                debug($olddate);
            }
            //Forzo ignora_configuratore a false
            $presenza['presenze_ignora_configuratore'] = false;

            //debug($this->datab->module_installed('long-operations'),true);

            

            if ($use_long_operations_system) {
               
                $this->long_operations->longOperation('riprocessa_presenza', $presenza, 'modulo-hr/timbrature');
                

            } else {
                $presenza = $this->timbrature->riprocessa_presenza($presenza);
            }
            //
            

            // if ('2024-01-07' == substr($presenza['presenze_data_inizio'], 0, 10)) {
            //     debug($presenza,true);
            // }


        }

        //Dopo 5 secondi chiudo la tab
        echo 'La pagina si chiuderà tra 5 secondi...<script>setTimeout(function(){window.close();}, 5000);</script>';
    }

    /**
     * 
     * ! Assegna buono pasto sull'ultima presenza della giornata, quella con inizio effettivo più alto
     * 
     */
    public function assegnaBuonoPasto($data, $dipendente)
    {
        //debug($data,true);
        $presenze_giornata = $this->apilib->search('presenze', [
            'presenze_data_inizio' => $data,
            'presenze_dipendente' => $dipendente,
            'presenze_data_fine IS NOT NULL AND presenze_data_fine <> ""'
        ], null, 0, 'presenze_ora_inizio_effettivo', 'DESC');

        if (!empty($presenze_giornata)) {
            $presenza_giornata = $presenze_giornata[0];

            try {
                $this->db->where('presenze_id', $presenza_giornata['presenze_id'])->update('presenze', [
                    'presenze_buono_pasto' => DB_BOOL_TRUE
                ]);

                //dump('Buono pasto assegnato a presenza :'.$presenza_giornata['presenze_id']);

                $this->mycache->clearEntityCache('presenze');

            } catch (Exception $e) {
                log_message('error', "Impossibile impostare buono pasto manuale su presenza {$presenza_giornata}. Errore: " . $e->getMessage());
                throw new Exception('Impossibile impostare il buono pasto sulle presenze');
                exit;
            }

            die(json_encode(['status' => 7, 'related_entity' => 'presenze', ' refresh_grids' => 1]));
        }

    }

    /**
     * 
     * ! Rimuove buono pasto da tutte le presenze della giornata
     * 
     */
    public function rimuoviBuonoPasto($data, $dipendente)
    {
        $presenze_giornata = $this->apilib->search('presenze', [
            'presenze_data_inizio' => $data,
            'presenze_dipendente' => $dipendente,
            'presenze_data_fine IS NOT NULL AND presenze_data_fine <> ""'
        ]);

        if (!empty($presenze_giornata)) {

            foreach ($presenze_giornata as $presenza) {
                try {
                    $this->db->where('presenze_id', $presenza['presenze_id'])->update('presenze', [
                        'presenze_buono_pasto' => DB_BOOL_FALSE
                    ]);

                } catch (Exception $e) {
                    log_message('error', "Impossibile rimuovere buono pasto manuale su presenza {$presenza['presenze_id']}. Errore: " . $e->getMessage());
                    throw new Exception('Impossibile rimuovere il buono pasto sulle presenze');
                    exit;
                }
            }

            $this->mycache->clearEntityCache('presenze');

            die(json_encode(['status' => 7, 'related_entity' => 'presenze', ' refresh_grids' => 1]));
        }
    }

    /**
     * 
     * ! Imposta record banca ore legato ad una presenza come cancellato
     * 
     */
    public function annullaBancaOre($presenza_id) {
        if(!empty($presenza_id)) {
            //Risalgo al record collegato alla presenza
            $banca_ore = $this->apilib->search('banca_ore', ['banca_ore_creato_da_presenza' => $presenza_id]);

            if(!empty($banca_ore)) {
                try {
                    foreach($banca_ore as $record) {
                        $this->db->where('banca_ore_id', $record['banca_ore_id'])->update('banca_ore', [
                            'banca_ore_movimento' => 2
                        ]);
                    }
                    $this->mycache->clearEntityCache('banca_ore');
                    
                    die(json_encode(['status' => 7, 'related_entity' => 'banca_ore', ' refresh_grids' => 1]));
                } catch (Exception $e) {
                    log_message('error', "Impossibile cancellare record banca ore {$banca_ore['banca_ore_id']} dalla presenza {$presenza_id}. Errore: " . $e->getMessage());
                    throw new Exception('Impossibile cancellare il record banca ore');
                    exit;
                }
            }
        }
    }
}