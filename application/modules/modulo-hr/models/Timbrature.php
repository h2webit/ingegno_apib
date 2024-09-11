<?php

class Timbrature extends CI_Model
{

    public $scope = 'CRM';
    public $turni_dipendenti = [];
    public $orari_di_lavoro_ore_pausa = [];
    public $pause_support_table = [];


    public function __construct() {
        $this->impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
        $this->pause_support_table = array_key_map_data($this->apilib->search('presenze_pausa'), 'presenze_pausa_id');

        $_orari_di_lavoro_ore_pausa = $this->apilib->search('orari_di_lavoro_ore_pausa');
        foreach ($_orari_di_lavoro_ore_pausa as $turno) {
            $this->orari_di_lavoro_ore_pausa[$turno['orari_di_lavoro_ore_pausa_id']] = $turno;

        }
        parent::__construct();
    }

    public function scope($tipo)
    {
        $this->scope = $tipo;
    }

    public function timbraEntrata($dipendente_id, $ora_entrata = null, $data_entrata = null, $scope = null, $reparto = null, $cliente = null, $commessa = null, $latitude = null, $longitude = null)
    {       
        if ($scope === null) {
            $scope = 'CRM';
        }
        if ($ora_entrata === null) {
            $ora_entrata = date('H:i');
        }
        if (empty($dipendente_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Dipendente e/o ora di entrata mancanti']));
        }

        $today = date('N');
        if ($data_entrata === null) {
            $data_entrata = date('Y-m-d');
        }

        if(!empty($reparto)){
            $reparto_dettagli = $this->apilib->searchFirst('reparti', [
                "reparti_id" => $reparto
            ]);
        }

        $dipendente = $this->apilib->view('dipendenti', $dipendente_id);
		$dichiara_reparto = $dipendente['dipendenti_dichiara_reparto'] ?? DB_BOOL_FALSE;
        $consenti_straordinario = $dipendente['dipendenti_consenti_straordinari'] ?? DB_BOOL_FALSE;
        $consenti_timbratura_senza_turno = $dipendente['dipendenti_consenti_timbratura_senza_turno'] ?? DB_BOOL_FALSE;
        
        //Se sono in un solo reparto lo salvo a prescindere da altre configurazioni
        $reparti_base_dipendente = $this->apilib->search('reparti', [
            "reparti_id in (select reparti_id from rel_reparto_dipendenti where dipendenti_id = '{$dipendente_id}')"
        ]);
        if(!empty($reparti_base_dipendente) && count($reparti_base_dipendente) == 1) {
            $reparto = $reparti_base_dipendente[0]['reparti_id'];
        }

        // Salvo l'ora effettiva inviata dal dipendnete
        $inizio_effettivo = $ora_entrata;

        // Flag presenza anomala

        //Orario per testo anomalia
        $ora_attuale = date('H:i');
        // Flag per anomalia
        $presenza_anomala = DB_BOOL_FALSE;
        $testo_anomalia = '';

        $ignora_orari_lavoro = $dipendente['dipendenti_ignora_orari_lavoro'] ?? DB_BOOL_FALSE;

        
        //Recupero giorno corrispondente alla settimana in cui sto entrando nel caso di simulatore
        if($scope === 'SIMULATOR'){
            $n_giorno_settimana_entrata = date('N', strtotime($data_entrata));
            $today = $n_giorno_settimana_entrata;
        }
        $this->db->where("turni_di_lavoro_data_inizio <= '{$data_entrata}'", null, false);
        $this->db->where("(turni_di_lavoro_data_fine >= '{$data_entrata}' OR turni_di_lavoro_data_fine IS NULL)", null, false); //aggiungo anche il vuoto, se uno non imposta la data di fine.
        $this->db->where('turni_di_lavoro_dipendente', $dipendente_id);
        $this->db->where('turni_di_lavoro_giorno', $today);


        $orari_lavoro = $this->db->get('turni_di_lavoro')->result_array();

        if (empty($orari_lavoro)) {
            /**
             * ! Se non ho orari di lavoro devo valutare se ho dato la reperibilita e in questo caso devo considerare tutto come straordinari (ci pensa il post process dopo)
             */
            $reperibilita = $this->apilib->searchFirst('reperibilita', [
                'reperibilita_dipendente' => $dipendente_id, 
                "DATE(reperibilita_data) = '$data_entrata'"
            ]);
            if ($reperibilita) {
                //Consento...
            } else {
                if($ignora_orari_lavoro == DB_BOOL_TRUE) {
                    //Posso timbrare senza orari
                } else {
                    if($consenti_straordinario == DB_BOOL_TRUE AND $consenti_timbratura_senza_turno == DB_BOOL_TRUE){
                        //permetto la timbratura anche se non ho orari oggi
                    } else {
                        //die(json_encode(['status' => 0, 'txt' => 'Nessun orario di lavoro registrato per la giornata odierna, contatta il tuo responsabile per configurarli correttamente']));
                        // 15/02/2024 - Non blocco l'entrata se non ho orari configurati ma segnalo l'anomalia
                        $presenza_anomala = DB_BOOL_TRUE;
                        //imposto testo anomalia
                        $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata senza orario di lavoro registrato per la giornata odierna";
                    }
                }
            }
        } elseif(!empty($reparto)){
          	//17/10/2023 - Fix temporaneo visto che tutti i dipendenti hanno dichiara reparto false e quindi i loro orari creati dalla migaration NON HANNO POTUTO IMPOSTARE IL REPARTO CORRISPONDETE AD UN ORARIO
            //verifico se la mia entrata corrisponde a quel reparto
            $suggerimentoTurno = $this->suggerisciTurno($ora_entrata, $orari_lavoro, 'entrata');
            if($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_reparto'] != $reparto && $dichiara_reparto == DB_BOOL_TRUE && !empty($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_reparto'])){
                die(json_encode(['status' => 0, 'txt' => 'Il tuo orario di lavoro prevede un altro reparto.']));

            }

        }

        /**
         * ! Se ho già una presenza aperta non devo bloccare ma le chiudo
         */
        $presenze_aperte = $this->apilib->search('presenze', ['presenze_dipendente' => $dipendente_id, 'presenze_data_fine IS NULL or presenze_data_fine = ""']);
        if (!empty($presenze_aperte)) {
            foreach($presenze_aperte as $presenza_aperte){
                $this->timbraUscita($dipendente_id, '23:59', $presenza_aperte['presenze_data_inizio'] , $presenza_aperte['presenze_id'],"APP");
            }
        }

        /**
         * ! Se devo ignorari gli orari ma non ho impostato un n° di ore standard torno errore
         */
        if($ignora_orari_lavoro == DB_BOOL_TRUE && (empty($dipendente['dipendenti_ore_standard']) || $dipendente['dipendenti_ore_standard'] <= 0)) {
            die(json_encode(['status' => 0, 'txt' => 'Presenza non consentita, non è stato impostato il numero di ore standard giornaliero. Contatta il tuo responsabile per configurarli correttamente']));
        }


        /**
         * ! SE HO UN BLOCCO SULLE PRESENZE ATTIVO NON DEVO FAR FARE L'INSERIMENTO se non ci sono dipendenti inseriti o sono tra quelli inseriti 
         */
        $sql_blocco = "
        SELECT * FROM blocchi_hr
        WHERE blocchi_hr_blocca_inserimento = '1' AND blocchi_hr_dal <= '{$data_entrata}' and blocchi_hr_al >= '{$data_entrata}'
        ORDER BY blocchi_hr_id ASC
        ";
        $blocchi = $this->db->query($sql_blocco)->result_array();

        if(!empty($blocchi)) {
            foreach ($blocchi as $blocco) {
                $dipendenti_da_bloccare = $this->apilib->search('rel_blocchi_hr_dipendenti', [
                    'blocchi_hr_id' => $blocco['blocchi_hr_id'],
                ]);
            
                //Se vuoto o sono tra i dipendenti, blocco e torno errore
                if (empty($dipendenti_da_bloccare) || (array_search($dipendente_id, array_column($dipendenti_da_bloccare, 'dipendenti_id')) !== false)) {
                    $data_bloccata = dateFormat($data_entrata, 'd/m/Y');
                    die(json_encode(['status' => 0, 'txt' => "L'inserimento delle presenze per la data {$data_bloccata} è bloccato."]));
                } else {
                    //dump('Proseguo, ci sono dipendenti da bloccare ed io non sono tra quelli');
                }
            }
        }

        /**
         * ! CONTROLLO COORDINATE CON REPARTO
         */
        if ($dipendente['dipendenti_geolocalizzazione'] == DB_BOOL_TRUE && !in_array($scope, array('CRON', 'BADGE', 'QRCODE', 'QRCODE_PRINT', 'NFC', 'SIMULATOR'))) {
            if(empty($latitude) || empty($longitude)) {
                die(json_encode(['status' => 0, 'txt' => "Coordinate non rilevate dal dispositivo."]));
                exit;
            }

            if(!empty($reparto_dettagli['reparti_coordinate']) && !empty($reparto_dettagli['reparti_raggio'])) {        
                //query geografica
                $res = $this->db->query("SELECT * FROM reparti WHERE reparti_id = {$reparto_dettagli['reparti_id']} AND reparti_coordinate IS NOT NULL AND reparti_coordinate <> '' AND ST_Distance_Sphere(POINT({$latitude}, {$longitude}), POINT(SUBSTRING_INDEX(reparti_coordinate, ';', 1),SUBSTRING_INDEX(reparti_coordinate, ';', -1))) <= reparti_raggio")->result_array();
                if(empty($res)) {
                    die(json_encode(['status' => 0, 'txt' => "Attenzione: sei fuori dal raggio massimo di timbratura per il reparto scelto."]));
                    exit;
                }
            }
        }



        $ora_inizio = new Datetime($ora_entrata);


            
        /**
         * ! Se ho una reperibilita (e non quindi un orario di lavoro) o devo ignorare orari forzo ora_inizio e fine uguali a 00:00
         */
        if ((!empty($reperibilita) || $ignora_orari_lavoro == DB_BOOL_TRUE) || (empty($orari_lavoro) AND ($consenti_straordinario == DB_BOOL_TRUE || $consenti_timbratura_senza_turno == DB_BOOL_TRUE))) {
            $ora_inizio_profilo = new DateTime('23:59');
            $ora_fine_profilo = new DateTime('00:00');
        } else {
            $suggerimentoTurno = $this->suggerisciTurno($ora_entrata, $orari_lavoro,'entrata');
            
            if((date('Y-m-d',strtotime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_data_inizio'])) != date('Y-m-d',strtotime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_data_fine'])) && !empty($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_data_fine']))){
                //qua vuol dire che sono in un notturno
                $ora_fine_profilo = new DateTime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine']);
                $ora_fine_profilo->modify('+1 day');
            } else {
                $ora_fine_profilo = new DateTime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine']);
            }
            $ora_inizio_profilo = new DateTime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio']);
            //$ora_fine_profilo = new DateTime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine']);
        }

        $diff_orari_entrata = $ora_inizio->diff($ora_inizio_profilo);
        $diff_entrata_minuti = $diff_orari_entrata->i;
        $diff_entrata_ore = $diff_orari_entrata->h;
        $invert = $diff_orari_entrata->invert; // 1 se ora inizio > ora_inizio_profilo, 0 se ora inizio < ora_inizio_profilo

        
        $range_entrata = $this->impostazioni_modulo['impostazioni_hr_range_minuti_entrata'] ?? 30;
        //Se timbro entro questo range DOPO l'entrata prevista devo sovrascrivere l'orario con quello del profilo
        $tolleranza_entrata = $this->impostazioni_modulo['impostazioni_hr_tolleranza_entrata'] ?? 0;

        /**
         * ! Prendo il reparto e vedo se gli straordinari sono consentiti
         */
        $consenti_straordinario_reparto = $reparto_dettagli['reparti_straordinari']?? DB_BOOL_FALSE;


        /**
         * ! Calcolo differenza tra entrata e fine profilo
         */
        $diff_orari_uscita = $ora_inizio->diff($ora_fine_profilo);
        $diff_uscita_minuti = $diff_orari_uscita->i;
        $diff_uscita_ore = $diff_orari_uscita->h;
        $invert_uscita = $diff_orari_uscita->invert; // 1 se ora inizio > $ora_fine_profilo, 0 se ora inizio < $ora_fine_profilo

        /**
         * ! Se timbro prima dell'inizio e sono entro il range di tollerenza salvo l'ora inizio del profilo
         * ! Controlli da fare solo se reperibilità non comunicata e orari non da ignorare
         */
        if (empty($reperibilita) && $ignora_orari_lavoro == DB_BOOL_FALSE) {
            if (($diff_entrata_minuti < $range_entrata && $diff_entrata_ore < 1 && $invert == 0)) {
                $ora_entrata = $orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio'];
            } elseif ($diff_entrata_minuti > $range_entrata && $diff_entrata_ore < 1 && $invert == 0 && $consenti_straordinario == DB_BOOL_FALSE) {
                /**
                 * ! Se sono oltre il range definito nelle impostazioni ma gli straordinario NON sono consentiti salvo l'ora inizio del profilo
                 */
                $ora_entrata = $orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio'];
                // Segno la presenza come anomala
                $presenza_anomala = DB_BOOL_TRUE;
                //imposto testo anomalia
                $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata oltre il range definito nelle impostazioni e straordinari non consentiti";
            } elseif ($diff_entrata_minuti > $range_entrata && $diff_entrata_ore < 1 && $invert == 0 && $consenti_straordinario_reparto == DB_BOOL_FALSE && !empty($reparto_dettagli)) {
                /**
                 * ! Se sono oltre il range definito nelle impostazioni ma gli straordinario NON sono consentiti nel reparto salvo l'ora inizio del profilo
                 */
                $ora_entrata = $orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio'];
                // Segno la presenza come anomala
                $presenza_anomala = DB_BOOL_TRUE;
                //imposto testo anomalia
                $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata oltre il range definito nelle impostazioni e straordinari non consentiti nel reparto scelto";
            } elseif ($diff_entrata_ore > 1 && $invert == 0 && $consenti_straordinario == DB_BOOL_FALSE)  {
                /**
                 * ! Se sono oltre il range definito nelle impostazioni ma gli straordinario NON sono consentiti e sto timbrando almeno 1 ora prima
                 * ! Es. sto entrando alle 9.10, sul profilo ho 10.30 e non posso fare straordinari
                 */
                $ora_entrata = $orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio'];
                // Segno la presenza come anomala
                $presenza_anomala = DB_BOOL_TRUE;
                //imposto testo anomalia
                $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata oltre il range definito nelle impostazioni, timbratura almeno 1 ora prima e straordinari non consentiti";
            } elseif ($diff_entrata_ore > 1 && $invert == 0 && !empty($reparto_dettagli) && $consenti_straordinario_reparto == DB_BOOL_FALSE)  {
                /**
                 * ! Se sono oltre il range definito nelle impostazioni ma gli straordinario NON sono consentiti nel reparto e sto timbrando almeno 1 ora prima
                 * ! sto entrando alle 9.10 e sul profilo ho 10.30 e non posso fare straordinari nel reparto
                 */
                $ora_entrata = $orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio'];
                // Segno la presenza come anomala
                $presenza_anomala = DB_BOOL_TRUE;
                //imposto testo anomalia
                $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata oltre il range definito nelle impostazioni, timbratura almeno 1 ora prima e straordinari non consentiti nel reparto scelto";
            }/* elseif ($invert_uscita == 1 && $consenti_straordinario == DB_BOOL_FALSE) {
                //Sto timbrando in ritardo e fuori dall'orario lavorativo (entrata > fine profilo) e straordinario disabilitato
                die(json_encode(['status' => 0, 'txt' => 'Straordinari non consentiti, stai timbrando entrata dopo la fine registrata nel profilo.']));
            } */ elseif ($invert_uscita == 1 && $consenti_straordinario_reparto == DB_BOOL_FALSE && !empty($reparto_dettagli)) {
                /**
                 * ! Sto timbrando in ritardo e fuori dall'orario lavorativo (entrata > fine profilo) e straordinario nel reparto disabilitato
                 */
                //die(json_encode(['status' => 0, 'txt' => 'Straordinari non consentiti, stai timbrando entrata dopo la fine registrata nel profilo.']));
                 // Segno la presenza come anomala
                 $presenza_anomala = DB_BOOL_TRUE;
                 //imposto testo anomalia
                 $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Straordinari non consentiti, timbratura entrata dopo la fine registrata nel profilo";
            //} elseif ($invert_uscita == 1 && $consenti_timbratura_senza_turno == DB_BOOL_FALSE) {
            } elseif ($invert_uscita == 1 && $consenti_straordinario == DB_BOOL_FALSE) {
                /**
                 * ! Sto timbrando in ritardo e fuori dall'orario lavorativo (entrata > fine profilo), blocco sempre l'entrata. Al limite posso uscire dopo la fine
                 * ! 24/01/2024 - Permetto timbratura se posso fare straordinario
                 */
                //die(json_encode(['status' => 0, 'txt' => 'Presenza non consentita, stai timbrando entrata dopo la fine registrata nel profilo.']));
                // Segno la presenza come anomala
                $presenza_anomala = DB_BOOL_TRUE;
                //imposto testo anomalia
                $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Straordinari non consentiti, timbratura entrata dopo la fine registrata nel profilo";
            }
            
            /**
             * ! Se sto timbrando in ritardo ma sono entro la tolleranza devo sovrascrivere l'ora entrata che invio con quella del profilo
             */
            if(!empty($tolleranza_entrata)) {
                //Converto differenza in ore in minuti e la aggiungo alla differenza in minnuti
                $diff_entrata_min = $diff_entrata_ore * 60 + $diff_entrata_minuti;

                //Sono entrato dopo l'orario stabilito e la differenza in minuti è minore della tolleranza in entrata --> sovrascrivo ora entrata inviata
                if($invert === 1 && ($diff_entrata_min < $tolleranza_entrata)) {
                    $ora_entrata = $orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio'];
                } else if($invert === 1 && ($diff_entrata_min > $tolleranza_entrata)) {
                    // Segno la presenza come anomala perchè sono entrato più tardi
                    $presenza_anomala = DB_BOOL_TRUE;
                    //imposto testo anomalia
                    $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata in ritardo ed oltre i minuti di tolleranza impostata";
                }
            } else {
            /**
             * ! Se entro in ritardo e basta (no tolleranza impostata) devo segnarla come anomala
             */
                if($invert == 1) {
                    $presenza_anomala = DB_BOOL_TRUE;
                    //imposto testo anomalia
                    $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata in ritardo";
                }
            }
        }

        /**
        * ! Campi per il calendario
        */
        $data_inizio_calendar = $data_entrata;
        $ora_inizio_calendar = $ora_entrata;
        $dal = (new DateTime($data_inizio_calendar))->format('Y-m-d');
        $inizio_calendar = $dal . ' ' . $ora_inizio_calendar;
        //Creo record
        try {
            /**
             * ! Prendo tutte le richieste approvate per il dipendente che sta timbrando, potrei dovrei bloccare l'inserimento
             */
            $richieste = $this->apilib->search('richieste', [
                'richieste_user_id' => $dipendente_id,
                'richieste_stato' => 2, //solo richieste approvate
            ]);
            if (!empty($richieste)) {
                foreach ($richieste as $richiesta) {
                    /**
                     * ! Se la richiesta comprende oggi
                     */
                    if (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= $data_entrata && dateFormat($richiesta['richieste_al'], 'Y-m-d') >= $data_entrata) {
                        if ($richiesta['richieste_tipologia'] == 1) { //PERMESSO
                            if ($ora_entrata >= $richiesta['richieste_ora_inizio'] && $ora_entrata <= $richiesta['richieste_ora_fine']) {
                                //die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare avendo una richiesta di permesso in corso.']));
                                // 19/12/2023 - Non si blocca l'inserimento ma si segnala l'anomalia
                                $presenza_anomala = DB_BOOL_TRUE;
                                $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata con richiesta di permesso in corso";
                            }
                        } elseif ($richiesta['richieste_tipologia'] == 2) { //FERIE
                            //die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare avendo una richiesta di ferie in corso.']));
                            // 25/12/2024 - Non si blocca l'inserimento ma si segnala l'anomalia
                            $presenza_anomala = DB_BOOL_TRUE;
                            $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata con richiesta di permesso in corso";
                        } elseif ($richiesta['richieste_tipologia'] == 3) { //MALATTIA
                            //die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare avendo una richiesta di malattia in corso.']));
                            // 25/12/2024 - Non si blocca l'inserimento ma si segnala l'anomalia
                            $presenza_anomala = DB_BOOL_TRUE;
                            $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata con richiesta di malattia in corso";
                        }
                    } elseif (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= $data_entrata && empty($richiesta['richieste_al']) && $richiesta['richieste_tipologia'] == 3) {
                        /**
                         * ! Se la richiesta è senza data fine ed è MALATTIA
                         */
                        //die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare avendo una richiesta di malattia in corso.']));
                        // 25/12/2024 - Non si blocca l'inserimento ma si segnala l'anomalia
                        $presenza_anomala = DB_BOOL_TRUE;
                        $testo_anomalia = "[{$scope}] ({$ora_attuale}) Anomalia - Entrata con richiesta di malattia (senza data fine) in corso";
                    }
                }
            }

            $entrata = $this->apilib->create('presenze', [
                'presenze_dipendente' => $dipendente_id,
                'presenze_data_inizio' => $data_entrata,
                'presenze_ora_inizio' => $ora_entrata,
                'presenze_data_inizio_calendar' => $inizio_calendar,
                'presenze_scope_create' => $scope,
                'presenze_reparto' => $reparto,
                'presenze_cliente' => $cliente,
                'presenze_commessa' => $commessa,
                'presenze_latitude' => $latitude,
                'presenze_longitude' => $longitude,
                'presenze_ora_inizio_effettivo' => $inizio_effettivo,
                'presenze_anomalia' => $presenza_anomala,
                'presenze_note_anomalie' => $testo_anomalia
            ]);
            
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            if(!empty($e->getMessage())) {
                die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
            } 
            die(json_encode(['status' => 0, 'txt' => 'Errore durante il salvataggio dell\'entrata']));
        }
        return $entrata;
    }


    public function timbraUscita($dipendente_id, $ora_uscita = null, $data_uscita = null, $presenze_id = null, $scope = null)
    {
        
        if ($scope === null) {
            $scope = 'CRM';
        }
        if ($ora_uscita === null) {
            $ora_uscita = date('H:i');
        }
        if (empty($dipendente_id)) {
            die(json_encode(['status' => 0, 'txt' => 'Dipendente e/o ora di uscita mancanti']));
        }
        if ($data_uscita === null) {
            $data_uscita = date('Y-m-d');
            $today = date('N');
        } else {
            $today = date('N', strtotime($data_uscita));
        }
        $dipendente = $this->apilib->view('dipendenti', $dipendente_id);
        if (!$dipendente) {
            die(json_encode(['status' => 0, 'txt' => "Dipendente '$dipendente_id' non trovato!"]));
        }

        $ignora_orari_lavoro = $dipendente['dipendenti_ignora_orari_lavoro'] ?? DB_BOOL_FALSE;

        // Salvo l'ora effettiva inviata dal dipendnete
        $fine_effettiva = $ora_uscita;

        //Anomalia se esco prima della fine
        $presenza_anomala = DB_BOOL_FALSE;
        $testo_anomalia = '';

        /**
         * ! Cerco la presenza di oggi senza data e ora uscita, se non la trovo mostro errore (non ho timbrato entrata o è stata già chiusa dal sistema)
         */
        if($presenze_id === null){
            $sql = "
            SELECT * FROM presenze
            WHERE presenze_dipendente = '{$dipendente_id}'
            AND (presenze_data_fine IS NULL OR presenze_data_fine = '')
            AND (presenze_ora_fine IS NULL OR presenze_ora_fine = '')
            ORDER BY presenze_id ASC
            ";
        } else {
            $sql = "
            SELECT * FROM presenze
            WHERE presenze_id = '{$presenze_id}'
            ";
        }
        $presenza = $this->db->query($sql)->row_array();

        if (empty($presenza)) {
            die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare l\'uscita senza prima aver timbrato l\'entrata, oppure il tuo turno di lavoro è già stato chiuso in automatico dal sistema.']));
        }
        //$orario_lavoro = $this->apilib->searchFirst('orari_di_lavoro', ['orari_di_lavoro_dipendente' => $dipendente_id, 'orari_di_lavoro_giorno_numero' => $today]);
        $this->db->where("turni_di_lavoro_data_inizio <= '{$presenza['presenze_data_inizio']}'", null, false);
        $this->db->where("(turni_di_lavoro_data_fine >= '{$presenza['presenze_data_inizio']}' OR turni_di_lavoro_data_fine IS NULL)", null, false); //aggiungo anche il vuoto, se uno non imposta la data di fine.
        $this->db->where('turni_di_lavoro_dipendente', $dipendente_id);
        $this->db->where('turni_di_lavoro_giorno', date('N', strtotime($presenza['presenze_data_inizio'])));
        $orari_lavoro = $this->db->get('turni_di_lavoro')->result_array();
        
        $consenti_straordinario = $dipendente['dipendenti_consenti_straordinari'];

        /**
         * ! Se non ho orari di lavoro devo valutaare se ho dato la reperibilita e in questo caso devo considerare tutto come straordinari
         * ! (se ne occupa il post process)
         */
        if (empty($orari_lavoro)) {
            $reperibilita = $this->apilib->searchFirst('reperibilita', [
                'reperibilita_dipendente' => $dipendente_id, 
                "DATE(reperibilita_data) = '$data_uscita'"
            ]);
            if ($reperibilita) {
                //Mi baso sulla reperibilita e non sull'orario di lavoro (vedi sotto)
            }
        }

        
        /**
         * ! Se devo ignorari gli orari ma non ho impostato un n° di ore standard torno errore
         */
        if($ignora_orari_lavoro == DB_BOOL_TRUE && (empty($dipendente['dipendenti_ore_standard']) || $dipendente['dipendenti_ore_standard'] <= 0)) {
            die(json_encode(['status' => 0, 'txt' => 'Chiusura presenza non consentita, non è stato impostato il numero di ore standard giornaliero. Contatta il tuo responsabile per configurarle correttamente']));
        }

        $data_entrata = substr($presenza['presenze_data_inizio'], 0, 10);
        //Imposto l'orario corretto in base al controllo del range
        $ora_fine = new Datetime($ora_uscita);

        /**
         * ! Se ho una reperibilita (e non quindi un orario di lavoro) o devo ignorare orari forzo fine a 00:00
         */
        if (!empty($reperibilita) || $ignora_orari_lavoro == DB_BOOL_TRUE) {
            $ora_fine_profilo = new DateTime('00:00');
            // Converti l'orario iniziale in un oggetto DateTime
            $datetime_inizio = DateTime::createFromFormat('H:i', $presenza['presenze_ora_inizio']);

            // Aggiungi le ore del dipendente all'orario di inizio
            $ora_fine_profilo = clone $datetime_inizio; // Clona l'oggetto DateTime per non modificarlo direttamente
            $ora_fine_profilo->add(new DateInterval("PT{$dipendente['dipendenti_ore_standard']}H"));
        } else {
            if(empty($orari_lavoro)){
                // 17 ottobre 2023. Non si entrerà più qua, avendo i turni di lavoro che vengono controllati in entrata, ma per chiudere le presenze vecchie, faccio così.
                $ora_fine_profilo = new DateTime($ora_uscita);
            } else {
                $suggerimentoTurno = $this->suggerisciTurno($ora_uscita, $orari_lavoro,'uscita');
                $ora_fine_profilo = new DateTime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine']);    
            }
            
        }
        /* SE NON POSSO FARE STRAORDINARI MA STO CHIUDENDO UNA DEL GG PRECEDENTE -> metto dati di ieri */
        if(isset($suggerimentoTurno)){
            if($consenti_straordinario == DB_BOOL_FALSE && date('N', strtotime($presenza['presenze_data_inizio'])) != date('N')  && strtotime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine']) > strtotime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio'])) {
                $data_uscita = date('Y-m-d', strtotime($presenza['presenze_data_inizio']));
                $ora_fine_profilo->modify("-1 day");
            }
        }
        $diff_orari_uscita = $ora_fine->diff($ora_fine_profilo);
        $diff_uscita_minuti = $diff_orari_uscita->i;
        $diff_uscita_ore = $diff_orari_uscita->h;
        $invert = $diff_orari_uscita->invert; // 1 se ora fine > ora_fine_profilo, 0 se ora fine < ora_fine_profilo

        // Orario per testo anomalia
        $ora_attuale = date('H:i');

        //Se sono prima della fine metto anomalia
        if($invert == 0) {
            if ($diff_uscita_ore == 0 && $diff_uscita_minuti == 0) {
                //Gli orari sono identici quindi non devo segnare l'anomalia
            } else {
                $presenza_anomala = DB_BOOL_TRUE;
                //imposto testo anomalia
                $note_vecchie = ($presenza['presenze_note_anomalie']) ? "{$presenza['presenze_note_anomalie']}\n\r" : '';
                $testo_anomalia = "{$note_vecchie}[{$scope}] ({$ora_attuale}) Anomalia - Uscita in anticipo rispetto all'orario prestabilito";
            }
        }

        /**
         * ! Inserisco orario definito nel profilo altrimenti lascio i dati come sono, vedendo se posso fare straordinari o meno
         */
        
        $range_uscita = $this->impostazioni_modulo['impostazioni_hr_range_minuti_uscita'] ?? 30;

        /**
         * ! Se timbro dopo la fine e sono entro il range di tollerenza salvo l'ora fine del profilo
         * ! Abbiamo aggiunto presenza inizio < ora fine profilo per fare in modo che se ho timbrato entrata dopo la fine del profilo
         * ! e adesso sto uscendo di nuovo deve mettere l'ora che invio io e non quella del profilo
         */
        if (empty($reperibilita) && $ignora_orari_lavoro == DB_BOOL_FALSE) {
            if ($diff_uscita_minuti < $range_uscita && $diff_uscita_ore < 1 && $invert == 1 && (new DateTime($presenza['presenze_ora_inizio']) < $ora_fine_profilo)) {
                $ora_uscita = $orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine'];
            } elseif ($diff_uscita_minuti >= $range_uscita && $diff_uscita_ore >= 0 && $invert == 1 && $consenti_straordinario == DB_BOOL_FALSE) {
                /**
                 * ! Se sono oltre il range definito nelle impostazioni ma gli straordinario NON sono consentiti salvo l'ora fine del profilo
                 */
                $ora_uscita = $orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine'];
            }
        }

        /**
        * ! Imposto campi per il calendario
        */

        $data_fine_calendar = $data_uscita;
        $ora_fine_calendar = $ora_uscita;
        $al = (new DateTime($data_fine_calendar))->format('Y-m-d');
        $fine_calendar = $al . ' ' . $ora_fine_calendar;

        $richieste_permesso = $this->apilib->search('richieste', [
            'richieste_user_id' => $dipendente_id,
            'richieste_stato' => 2, //solo richieste approvate
            'richieste_tipologia' => 1, //richieste permesso
            //TODO: aggiungere filtro per prendere solo richieste nel periodo della presenza...
        ]);
        if (!empty($richieste_permesso)) {
            foreach ($richieste_permesso as $permesso) {

                /* @TODO provare a fare una richiesta dalle 4 di notte alle 7. Timbro entrata il gg prima alle 22 e poi timbro uscita alle 6. */
                if (dateFormat($permesso['richieste_dal'], 'Y-m-d') <= $data_uscita && dateFormat($permesso['richieste_al'], 'Y-m-d') >= $data_uscita) {
                    if ($ora_uscita >= $permesso['richieste_ora_inizio'] && $ora_uscita <= $permesso['richieste_ora_fine']) {
                        //$ora_uscita = $permesso['richieste_ora_inizio'];
                        /**
                         * ! Devo togliere in maniera arbitraria un minuto dall'ora di uscita altrimenti nel post process viene tornaro errore che
                         * ! sto timbrando avendo una richiesta in corso (vede che ora uscita è uguale ad ora inizio richiesta)
                         */
                        /*$inizio_richiesta = new DateTime($permesso['richieste_data_ora_inizio_calendar']);
                        $ora_uscita = $inizio_richiesta->modify("-1 minutes")->format('H:i');
                        $fine_calendar = $al . ' ' . $permesso['richieste_data_ora_inizio_calendar'];*/
                        /**
                         * ! 26/01/2024 - Non sovrascrivo più gli orari ma segnalo l'anomlia
                         */
                        $presenza_anomala = DB_BOOL_TRUE;
                        //imposto testo anomalia
                        $note_vecchie = ($presenza['presenze_note_anomalie']) ? "{$presenza['presenze_note_anomalie']}\n\r" : '';
                        $testo_anomalia = "{$note_vecchie}[{$scope}] ({$ora_attuale}) Anomalia - Uscita, la presenza si sovrappone con una richiesta";
                    }
                }
            }
        } 

        //EDIT RECORD
        try {
            $uscita = $this->apilib->edit('presenze', $presenza['presenze_id'], [
                'presenze_data_fine' => $data_uscita,
                'presenze_ora_fine' => $ora_uscita,
                'presenze_data_fine_calendar' => $fine_calendar,
                //'presenze_straordinario' => $ore_straordinari, //Le ore straordinario vengono calcolate dal post process in automatico all'edit e anche all'insert
                'presenze_scope_edit' => $scope,
                'presenze_ora_fine_effettiva' => $fine_effettiva,
                'presenze_anomalia' => $presenza_anomala,
                'presenze_note_anomalie' => $testo_anomalia
            ]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            if(!empty($e->getMessage())) {
                die(json_encode(['status' => 0, 'txt' => $e->getMessage()]));
            } 
            die(json_encode(['status' => 0, 'txt' => 'Errore durante il salvataggio dell\'uscita']));
        }
        
        return $uscita;
    }

    //Questa funzione, a differenza di tutto quello che fa il pp, fa un mero calcolo grezzo degli straordinari... non si mette li a valutare se il dipendente può farli, se ha delle ferie o altre cose. Serve solo per eseguire centralizzata l'operazione matematica di calcolo. Stop.
    //Bisogna anche tenere presente che questa funzione fa affidamento su parametri già tutti belli e pronti passati in ingresso (come ad esempio _ora_entrata_turno che contiene il datetime dell'ora prevista...)
    //Sarebbero da gestire questi casi, ad esempio prendendo il turno previsto ed estraendo da questo _ora_entrata_turno quando non presente questa chiave.
    public function calcolaOreStraordinarieBase($presenza, $override_data = []) {
        $presenza_overrided = array_merge($presenza, $override_data);


        
        
        if(!isset($presenza_overrided['_ora_entrata_turno'], $presenza_overrided['_ora_uscita_turno'],
            $presenza_overrided['presenze_ora_inizio'], $presenza_overrided['presenze_ora_fine'],
            $presenza_overrided['presenze_data_inizio'], $presenza_overrided['presenze_data_fine'])) {
            return 0;
        }

        

        // Estrae solo la parte data da presenze_data_inizio e presenze_data_fine
        $dataInizio = explode(" ", $presenza_overrided['presenze_data_inizio'])[0];
        $dataFine = explode(" ", $presenza_overrided['presenze_data_fine'])[0];
        if (!$dataFine || !$presenza_overrided['presenze_ora_fine']) {
            return 0;
        }
        // Crea gli oggetti DateTime
        $entrataEffettiva = DateTime::createFromFormat('Y-m-d H:i', $dataInizio.' '.$presenza_overrided['presenze_ora_inizio']);
        $uscitaEffettiva = DateTime::createFromFormat('Y-m-d H:i', $dataFine.' '.$presenza_overrided['presenze_ora_fine']);

        $entrataPrevista = DateTime::createFromFormat('Y-m-d H:i', $dataInizio.' '.$presenza_overrided['_ora_entrata_turno']);
        $uscitaPrevista = DateTime::createFromFormat('Y-m-d H:i', $dataFine.' '.$presenza_overrided['_ora_uscita_turno']);

        // Assicurati che tutte le date siano nel formato corretto
        // e che l'uscitaEffettiva sia sempre dopo l'entrataEffettiva

        //Commentato... non ha senso visto che l'uscita effettiva viene già presa con la data fine...
        // if ($uscitaEffettiva < $entrataEffettiva) {
        //     //Verifico se effettivamente ha timbrato l'uscita il giorno dopo
        //     if ($presenza['presenze_data_fine'] > $presenza['presenze_data_inizio']) {
        //         $uscitaEffettiva->modify('+1 day');
        //     }
            
        // }

        $oreStraordinarie = 0;

        // Calcola ore straordinarie prima dell'entrata prevista
        //if ($entrataEffettiva < $entrataPrevista) {
            $oreStraordinarie += ($entrataPrevista->getTimestamp() - $entrataEffettiva->getTimestamp()) / 3600;
        //}
        
        

        //debug($oreStraordinarie);
        // Calcola ore straordinarie dopo l'uscita prevista
        //if ($uscitaEffettiva > $uscitaPrevista) {
            $oreStraordinarie += ($uscitaEffettiva->getTimestamp() - $uscitaPrevista->getTimestamp()) / 3600;
        //}
        // if ($presenza['presenze_id'] == 3722) {
        //     debug($uscitaEffettiva);
        //     debug($uscitaPrevista);
        //     debug($oreStraordinarie);
        //     debug($presenza, true);

        // }
        

        //Arrotondo in base al parametro $range_calcolo
        $range_calcolo = ($this->impostazioni_modulo['impostazioni_hr_range_calcolo'] ?? 0) / 60;

        
        
        //Da queste devo togliere eventuali ore straordinarie (negative), ovvero il recupero che doveva fare
        $Ymd = substr($dataInizio,0,10);
        $dipendente_id = $presenza['presenze_dipendente'];
        //     $presenze_esclusa_questa = $this->apilib->search('presenze', [
        //         'presenze_dipendente' => $dipendente_id,
        //         'DATE(presenze_data_inizio)' => $Ymd,
        //         'presenze_id <>' => $presenza['presenze_id']
        //     ]);
       
        // $ore_recupero = -$this->calcolaOreStraordinarie($Ymd, $dipendente_id, $presenze_esclusa_questa, false);

       
        
        // $oreStraordinarie -=$ore_recupero;

        $oreStraordinarie = $this->calcolaOreStraordinarie($Ymd, $dipendente_id, $this->apilib->search('presenze', [
            'presenze_dipendente' => $dipendente_id,
            'DATE(presenze_data_inizio)' => $Ymd,
            'presenze_id <=' => $presenza['presenze_id']
        ]));
        
        if($range_calcolo) {
            $oreStraordinarie = floor($oreStraordinarie / $range_calcolo) * $range_calcolo;
        }
        
        return $oreStraordinarie;
    }
    //La regola è che viene definito come “periodo notturno” un periodo di almeno sette ore consecutive 
    //comprendenti l'intervallo tra la mezzanotte e le cinque del mattino.
    //In questo periodo rientrano quindi gli orari 22-5, 23-6, 24-7 e, ovviamente, orari di maggiore durata comprendenti i precedenti.
    function calcolaOreNotturne($giorno, $dipendente_id, $presenze = null)
    {
        if ($presenze === null) {
            // Utilizza la chiamata esistente al model apilib per recuperare le presenze
            $presenze = $this->apilib->search('presenze', [
                'presenze_dipendente' => $dipendente_id,
                'DATE(presenze_data_inizio)' => $giorno
            ]);
        }

        $oreNotturne = 0;
        // Definisci l'intervallo notturno
        $inizioNotturno = '22:00:00';
        $fineNotturno = '05:00:00';

        foreach ($presenze as $presenza) {
            $presenza['presenze_data_inizio'] = substr($presenza['presenze_data_inizio'], 0, 10);
            $presenza['presenze_data_fine'] = substr($presenza['presenze_data_fine'], 0, 10);
            $dataInizio = $presenza['presenze_data_inizio'] . ' ' . $presenza['presenze_ora_inizio'];
            $dataFine = $presenza['presenze_data_fine'] . ' ' . $presenza['presenze_ora_fine'];

            // Calcola le ore notturne per la presenza corrente
            $oreNotturne += $this->calcolaOreNotturnePresenza($dataInizio, $dataFine, $inizioNotturno, $fineNotturno);
        }

        return $oreNotturne;
    }

    private function calcolaOreNotturnePresenza($dataInizio, $dataFine, $inizioNotturno, $fineNotturno)
    {
        // Converti le stringhe di orario in oggetti DateTime
        $start = new DateTime($dataInizio);
        $end = new DateTime($dataFine);
        $nightStart = new DateTime($dataInizio);
        $nightEnd = new DateTime($dataFine);
        $nightStart->modify($inizioNotturno); // Imposta l'orario di inizio notturno per la data di inizio presenza
        $nightEnd->modify($fineNotturno); // Imposta l'orario di fine notturno per la data di fine presenza

        // Se l'orario di fine notturno è inferiore a quello di inizio, significa che attraversa la mezzanotte
        if ($nightEnd <= $nightStart) {
            $nightEnd->modify('+1 day'); // Aggiungi un giorno all'orario di fine notturno
        }

        // Calcola l'intersezione tra la presenza e l'intervallo notturno
        $interval = 0;

        // Inizio presenza prima dell'inizio notturno e fine presenza dopo l'inizio notturno
        if ($start < $nightStart && $end > $nightStart) {
            $intervalStart = $nightStart;
        } else if ($start >= $nightStart) {
            $intervalStart = $start;
        } else {
            $intervalStart = null;
        }

        // Fine presenza dopo la fine notturno e inizio presenza prima della fine notturno
        if ($end > $nightEnd && $start < $nightEnd) {
            $intervalEnd = $nightEnd;
        } else if ($end <= $nightEnd) {
            $intervalEnd = $end;
        } else {
            $intervalEnd = null;
        }

        // Se abbiamo un intervallo valido, calcoliamo le ore
        if ($intervalStart !== null && $intervalEnd !== null) {
            $interval = max(0, $intervalEnd->getTimestamp() - $intervalStart->getTimestamp()) / 3600; // Calcola in ore
        }

        return $interval; // Restituisce le ore notturne calcolate
    }


    public function calcolaOreStraordinarie($Ymd, $dipendente_id, $presenze = false, $avoid_under_zero = true)
    {
        $dipendente = $this->apilib->view('dipendenti', $dipendente_id);
        if ($dipendente['dipendenti_consenti_straordinari'] == DB_BOOL_FALSE) {
            return 0;
        }
        
        // Assicurati che l'array delle presenze sia popolato; altrimenti, recuperalo
        if ($presenze === false) {
            $presenze = $this->apilib->search('presenze', [
                'presenze_dipendente' => $dipendente_id,
                'DATE(presenze_data_inizio)' => $Ymd // Assicurati che questo formato corrisponda a quello usato nel database
            ]);
        }

        // Calcola le ore totali lavorate per la giornata
        $oreTotaliLavorate = $this->calcolaOreTotali($Ymd, $dipendente_id, $presenze, true);

        if ($Ymd == '2024-03-16') {
            //debug($oreTotaliLavorate,true);
        }

        // Calcola le ore di lavoro previste per la giornata (ordine)
        $oreLavoroPreviste = $this->calcolaOreGiornalierePreviste($Ymd, $dipendente_id);

        // Calcola le ore di pausa (se applicabile)
        $orePausa = $this->calcolaOrePausaPranzo($Ymd, $dipendente_id, $presenze);
        
            
        // Calcola le ore straordinarie
        if ($avoid_under_zero) {
            $oreStraordinarie = max(0, $oreTotaliLavorate - $oreLavoroPreviste - $orePausa);
        } else {
            // debug($oreTotaliLavorate);
            // debug($oreLavoroPreviste);
            // debug($orePausa);
            $oreStraordinarie = $oreTotaliLavorate - $oreLavoroPreviste - $orePausa;
        }
        

        
        //Arrotondo in base al parametro $range_calcolo
        $range_calcolo = ($this->impostazioni_modulo['impostazioni_hr_range_calcolo'] ?? 0)/60;
        //debug($oreStraordinarie);
        if($range_calcolo) {
            $oreStraordinarie = floor($oreStraordinarie / $range_calcolo) * $range_calcolo;
        }

        //debug(round($oreStraordinarie, 2));
        
        

        return round($oreStraordinarie,2);
    }

    public function calcolaOreOrdinarie($Ymd, $dipendente_id, $presenze = []) {
        $ore_straordinarie = $this->calcolaOreStraordinarie($Ymd, $dipendente_id, $presenze);
        $ore_totali = $this->calcolaOreTotali($Ymd, $dipendente_id, $presenze);
        $ore_pausa = $this->calcolaOrePausaPranzo($Ymd, $dipendente_id, $presenze);

        return round($ore_totali - $ore_straordinarie - $ore_pausa,2);

    }
    public function calcolaOreTotali($Ymd, $dipendente_id, $presenze = null, $esclude_malattia = false)
    {
        
        //debug($Ymd,true);
        if ($presenze === null) {
            $presenze = $this->apilib->search('presenze', [
                'DATE(presenze_data_inizio)' => $Ymd,
                //'presenze_data_fine <=' => "$Ymd 23:59:59",
                'presenze_dipendente' => $dipendente_id
            ]);
        }

        //debug(count($presenze));
        $minuti_totali_lavorati = 0;
        //$this->mycache->clearEntityCache('presenze');
       

        foreach ($presenze as $_presenza) {
            if (empty($_presenza['presenze_data_fine']) || empty($_presenza['presenze_ora_fine'])) {
                
                continue;
            }
            $day_number = date('w', strtotime($_presenza['presenze_data_inizio']));

            $this->db->where("turni_di_lavoro_data_inizio <= '{$_presenza['presenze_data_inizio']}'", null, false);
            $this->db->where("(turni_di_lavoro_data_fine >= '{$_presenza['presenze_data_inizio']}' OR turni_di_lavoro_data_fine IS NULL)", null, false); //aggiungo anche il vuoto, se uno non imposta la data di fine.
            $this->db->where('turni_di_lavoro_dipendente', $_presenza['presenze_dipendente']);
            $this->db->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', "left");
            $this->db->where('turni_di_lavoro_giorno', $day_number);
            $orari_di_lavoro = $this->db->get('turni_di_lavoro')->result_array();

            $suggerimentoTurno = $this->timbrature->suggerisciTurno($_presenza['presenze_ora_inizio'], $orari_di_lavoro, 'entrata');
            //Se la richiesta è di tipo malattia, non la considero
            if ($esclude_malattia && $_presenza['presenze_richiesta']) {
                $richiesta = $this->apilib->view('richieste', $_presenza['presenze_richiesta']);
                if (in_array($richiesta['richieste_tipologia'], [3])) {

                    continue;
                }
            }
            if (!empty($presenze['presenze_pausa'])) {
                $pausa = $presenze['presenze_pausa'];
            } else {
                $pausa = $orari_di_lavoro[$suggerimentoTurno]['orari_di_lavoro_ore_pausa_value'] ?? 0;
            }
            $data_inizio = substr($_presenza['presenze_data_inizio'], 0, 10);
            //se ho timbrato l'uscita, allora vado a prendere il totale direttamente dalla presenza e tolgo la pausa
            if (!empty($_presenza['presenze_data_fine']) && !empty($_presenza['presenze_ora_fine'])) {
                
                $data_fine = substr($_presenza['presenze_data_fine'], 0, 10);

                $ora_inizio = new DateTime("{$data_inizio} {$_presenza['presenze_ora_inizio']}");
                $ora_fine = new DateTime("{$data_fine} " . $_presenza['presenze_ora_fine']);
                if ($ora_fine < $ora_inizio) {
                    $ora_fine->modify('+1 day');
                }
                $differenza = $ora_inizio->diff($ora_fine);
                $minuti = $differenza->i;
                $ore = $differenza->h;
                $giorni = $differenza->d;
                //debug($giorni);

                $minuti_totali_lavorati += $giorni * 24 * 60 + $ore * 60 + $minuti;

            } else {
                //non ho ancora timbrato l'uscita, vado a prendermi le ore lavorate fino ad ora, ma non tolgo la pausa perchè non posso sapere se è prima o dopo l'ora attuale.
                
                $ora_inizio = new DateTime("{$data_inizio} {$_presenza['presenze_ora_inizio']}");
                $ora_fine = new DateTime("{$Ymd} ". date('H:i'));
                if ($ora_fine < $ora_inizio) {
                    $ora_fine->modify('+1 day');
                }
                $differenza = $ora_inizio->diff($ora_fine);
                $minuti = $differenza->i;
                $ore = $differenza->h;
                $giorni = $differenza->d;


                $minuti_totali_lavorati += $giorni * 24 * 60 + $ore * 60 + $minuti;
            }
        }

       // debug($minuti_totali_lavorati);

        return round($minuti_totali_lavorati / 60,2);

        
    }

    public function calcolaOrePausaPranzo($Ymd, $dipendente_id, $presenze = null)
    {
       
        if ($presenze === null) {
            // $presenze = $this->apilib->search('presenze', [
            //     'DATE(presenze_data_inizio)' => $Ymd,
            //     //'presenze_data_fine <=' => "$Ymd 23:59:59",
            //     'presenze_dipendente' => $dipendente_id
            // ]);
            $presenze = $this->db
                ->where("DATE(presenze_data_inizio) = '$Ymd'",null, false)
                ->where('presenze_dipendente', $dipendente_id)
                ->get('presenze')->result_array();
        }
        if ($Ymd == '20240210') {
            //debug($dipendente_id,true);

        }
        $orePausaTotale = $orePausaTotaleAutomatica = $orePausaDaPresenza = 0;
        $oreTotali = 0;

        foreach ($presenze as $presenza) {
            if ($presenza['presenze_ore_totali'] <= 0) {
                continue;
            }
            $oreTotali += str_replace(',', '.', $presenza['presenze_ore_totali']);
            if ($Ymd == '2024-02-09') {
               // debug($presenza,true);
            }
            if (!empty($presenza['presenze_pausa'])) {
                // Aggiungi direttamente l'ora di pausa dalla presenza
                if ($presenza['presenze_pausa']) {
                    if ($Ymd == '20240203') {
                        //debug($presenza);
                    }
                    //$orePausa = $this->apilib->view('presenze_pausa', $presenza['presenze_pausa'])['presenze_pausa_value'];
                    $orePausa = $this->pause_support_table[$presenza['presenze_pausa']]['presenze_pausa_value'];
                } else {
                    $orePausa = 0;
                }
                
                $orePausaDaPresenza += $orePausa;
                
            } else {
                // Recupera il turno di lavoro per il dipendente in quella specifica giornata
                $giornoSettimana = $this->getDayOfWeek($Ymd);
                // debug($Ymd);
                // debug($giornoSettimana);
                $turniDiLavoro = $this->getTurniDiLavoro($dipendente_id, $giornoSettimana);

                // Suggerisci il turno basato sull'orario di inizio della presenza
                $suggerimentoTurno = $this->suggerisciTurno($presenza['presenze_ora_inizio'], $turniDiLavoro, 'entrata');

                if (isset($turniDiLavoro[$suggerimentoTurno])) {
                    // Prendi il valore di pausa dal turno suggerito
                    $turno = $turniDiLavoro[$suggerimentoTurno];
                    if ($turno['turni_di_lavoro_pausa']) {
                        $orePausa = $this->orari_di_lavoro_ore_pausa[$turno['turni_di_lavoro_pausa']]['orari_di_lavoro_ore_pausa_value'];
                        //$orePausa = $this->apilib->view('orari_di_lavoro_ore_pausa', $turno['turni_di_lavoro_pausa'])['orari_di_lavoro_ore_pausa_value'];
                    } else {
                        $orePausa = 0;
                    }
                    

                    // Converti il valore di pausa in ore, se necessario
                    $orePausaTotaleAutomatica = $orePausa;
                    
                }
            }
        }
        //debug($orePausaDaPresenza);
        $orePausaTotale = $orePausaDaPresenza+ $orePausaTotaleAutomatica;
        // if ($oreTotali - $orePausaTotale > $this->calcolaOreGiornalierePreviste($Ymd, $dipendente_id)) {
        //     $orePausaTotale -= $orePausaTotaleAutomatica;
        // }
        
        return round($orePausaTotale,2);
    }
    public function calcolaOreRichieste($Ymd, $dipendente_id, $sottotipogia = null)
    {
        // Cerca tutte le richieste di permesso approvate per il dipendente in quella data
        $filtroRichieste = [
            'richieste_user_id' => $dipendente_id,
            //'richieste_tipologia' => 1, // Permesso
            'richieste_stato' => 2, // Approvato
            'DATE(richieste_dal) <=' => $Ymd,
            'DATE(richieste_al) >=' => $Ymd
        ];

        // Aggiungi il filtro per il sottotipo di richiesta, se specificato
        if ($sottotipogia !== null) {
            $filtroRichieste[] = "richieste_sottotipologia IN (SELECT richieste_sottotipologia_id FROM richieste_sottotipologia WHERE richieste_sottotipologia_codice = '$sottotipogia')";
        }

        $richieste = $this->apilib->search('richieste', $filtroRichieste);

        $orePermesso = 0;
        foreach ($richieste as $richiesta) {
            // Calcola solo se sia ora inizio che ora fine sono specificate
            if (!empty($richiesta['richieste_ora_inizio']) && !empty($richiesta['richieste_ora_fine'])) {
                $inizio = DateTime::createFromFormat('H:i', $richiesta['richieste_ora_inizio']);
                $fine = DateTime::createFromFormat('H:i', $richiesta['richieste_ora_fine']);
                if ($inizio && $fine) { // Assicurati che entrambi gli orari siano stati convertiti correttamente
                    $intervallo = $inizio->diff($fine);
                    $orePermesso += ($intervallo->h + $intervallo->i / 60);
                }
            } else {
                // Se manca l'orario, considero come ore di permesso solo quelle previste dai turni di lavoro
                $ore_previste = $this->calcolaOreGiornalierePreviste($Ymd, $dipendente_id);
                $orePermesso += $ore_previste;
            }
        }

        return round($orePermesso,2);
    }

    public function getTurniDiLavoro($idDipendente, $giornoSettimana) {
        if (!array_key_exists($idDipendente, $this->turni_dipendenti) || !array_key_exists($giornoSettimana,  $this->turni_dipendenti[$idDipendente])) {
            $this->db->where('turni_di_lavoro_dipendente', $idDipendente);
            //debug($giornoSettimana);
            $this->db->where('turni_di_lavoro_giorno', $giornoSettimana);
            $this->db->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', "left");
            $query = $this->db->get('turni_di_lavoro');
            $this->turni_dipendenti[$idDipendente][$giornoSettimana] = $query->result_array();
        
            // if ($idDipendente == '242' && $giornoSettimana == 4) {
            //     debug($this->turni_dipendenti[$idDipendente][$giornoSettimana]);

            // }

        } else {
            //debug('trovato',true);
        }
        return $this->turni_dipendenti[$idDipendente][$giornoSettimana];
    }

    public function isStraordinario($presenza, $data_ora = null)
    {
        //Considero straordinarie tutte le presenza la cui ora inizio è fuori da un orario di lavoro....
        $weekday = date('N', strtotime($presenza['presenze_data_inizio']));

        $this->db->where("turni_di_lavoro_data_inizio <= '{$presenza['presenze_data_inizio']}'", null, false);
        $this->db->where("(turni_di_lavoro_data_fine >= '{$presenza['presenze_data_inizio']}' OR turni_di_lavoro_data_fine IS NULL)", null, false); //aggiungo anche il vuoto, se uno non imposta la data di fine.
        $this->db->where('turni_di_lavoro_dipendente', $presenza['presenze_dipendente']);
        $this->db->where('turni_di_lavoro_giorno', date('N', strtotime($presenza['presenze_data_inizio'])));
        $orari_lavoro = $this->db->get('turni_di_lavoro')->result_array();
        $suggerimentoTurno = $this->suggerisciTurno(date('H:i'), $orari_lavoro,'uscita');
        //prendere l'orario corretto.

        /*$orario_lavoro = $this->apilib->searchFirst('orari_di_lavoro', [
            'orari_di_lavoro_dipendente' => $presenza['presenze_dipendente'],
            'orari_di_lavoro_giorno_numero' => $weekday,
        ]);*/
        $consenti_straordinario = $presenza['dipendenti_consenti_straordinari'];

        if ($data_ora == null) {
            $data_ora = $presenza['presenze_data_inizio'];
        }

        $inizio_profilo = new Datetime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio']);
        $fine_profilo = new Datetime($orari_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine']);
        $inizio_presenza = new Datetime($presenza['presenze_ora_inizio']);
        //Ho orario per oggi, posso fare straordinari e entrata presenza <= entrata profilo o > uscita profilo
        if ($orari_lavoro && $consenti_straordinario && ($inizio_presenza <= $inizio_profilo || $inizio_presenza > $fine_profilo)) {
            //se data ora è fuori dal mio orario lavorativo torno true perchè è straordinario
            log_message('error', "Straordinario in corso, presenza da non chiudere ".$presenza['presenze_id']);

            return true;
        } else {
            return false;
        }
    }
    public function suggerisciTurno($orarioIngresso, $turni, $tipologia) {
        if (empty($orarioIngresso)) {
            $orarioIngresso = '00:00';
        }
        // Converti l'orario di ingresso in formato DateTime
        $orarioIngresso = DateTime::createFromFormat('H:i', $orarioIngresso);
    
        $orarioSuggerito = null;
        $differenzaMinima = null;
    
        // Scansiona tutti i turni
        foreach ($turni as $indice => $turno) {
            if($tipologia == 'entrata'){
                $confronto = $turno['turni_di_lavoro_ora_inizio'];
    
            } elseif($tipologia == 'uscita'){
                $confronto = $turno['turni_di_lavoro_ora_fine'];    
    
            }

            // Converti gli orari di inizio e fine turno in formato DateTime
            $ora_confronto = DateTime::createFromFormat('H:i', $confronto);
            // Calcola la differenza di tempo tra l'orario di ingresso e l'inizio del turno corrente
            //debug($orarioIngresso);
            $differenza = $orarioIngresso->diff($ora_confronto);
            $differenzaInMinuti = $differenza->h * 60 + $differenza->i;
    
            // Verifica se la differenza di tempo è minore della differenza minima registrata
            if ($differenzaMinima === null || $differenzaInMinuti < $differenzaMinima) {
                $differenzaMinima = $differenzaInMinuti;
                $orarioSuggerito = $indice;
            }
        }
    
        return $orarioSuggerito; // Restituisci l'indice del turno suggerito
    }
    
    function calculateNightHours($start, $end, $night_start, $night_end) {
        $startDateTime = DateTime::createFromFormat('H:i', $start);
        $endDateTime = DateTime::createFromFormat('H:i', $end);
        $nightStartDateTime = DateTime::createFromFormat('H:i', $night_start);
        $nightEndDateTime = DateTime::createFromFormat('H:i', $night_end);
    
        if ($startDateTime === false || $endDateTime === false || $nightStartDateTime === false || $nightEndDateTime === false) {
            // Errore nella conversione delle date/orari, gestisci l'errore come preferisci.
            return 0;
        }
    
        // Aggiungi un giorno alla fine se l'orario di fine è precedente all'orario di inizio
        if ($endDateTime < $startDateTime) {
            $endDateTime->modify('+1 day');
        }
    
        // Controllo se l'orario di inizio è prima dell'orario di fine del turno notturno
        if ($startDateTime < $nightEndDateTime) {
            // Calcolo delle ore notturne parziali prima della fine del turno notturno
            $nightHours = min(($nightEndDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 3600, 8);
        } else {
            // Calcolo delle ore notturne parziali dopo la fine del turno notturno
            $nightHours = 0;
        }
    
        // Controllo se l'orario di fine è dopo l'orario di inizio del turno notturno
        if ($endDateTime > $nightStartDateTime) {
            // Calcolo delle ore notturne parziali dopo l'inizio del turno notturno
            $nightHours += min(($endDateTime->getTimestamp() - $nightStartDateTime->getTimestamp()) / 3600, 8);
        }
    
        // Limita le ore notturne al massimo di 8 ore
        //$nightHours = min($nightHours, 8);
    
        return $nightHours;
    }

    public function getTurnoDiLavoroFromPresenza ($presenza) {
        return $this->getTurnoLavoro($presenza['presenze_data_inizio'], $presenza['presenze_dipendente']);
    }


    public function getTurnoLavoro($data,$dipendente_id)
    {
        $this->db->where("turni_di_lavoro_data_inizio <= '{$data}'", null, false);
        $this->db->where("(turni_di_lavoro_data_fine >= '{$data}' OR turni_di_lavoro_data_fine IS NULL)", null, false); //aggiungo anche il vuoto, se uno non imposta la data di fine.
        $this->db->where('turni_di_lavoro_dipendente', $dipendente_id);
        $this->db->where('turni_di_lavoro_giorno', date('N', strtotime($data)));
        $orari_lavoro = $this->db->get('turni_di_lavoro')->result_array();

        //debug($this->db->last_query());

        return $orari_lavoro;
    }

    public function inserisciInBancaOre($presenza) {
        //debug($presenza,true);
        if(!empty($presenza['presenze_id'])) {
            $banca_ore_esistente = $this->apilib->searchFirst('banca_ore', [
                "banca_ore_creato_da_presenza" => $presenza['presenze_id'],
                "banca_ore_dipendente" => $presenza['presenze_dipendente'],
            ]);
            //debug($banca_ore_esistente,true);
            //modifico la banca ore con i nuovi dati
            if($banca_ore_esistente) {
                try {
                    $this->apilib->edit('banca_ore', $banca_ore_esistente['banca_ore_id'], [
                        "banca_ore_hours" => $presenza['presenze_straordinario'],
                        "banca_ore_data" => $presenza['presenze_data_fine'],
                    ]);
                } catch (Exception $e) {
                    throw new ApiException('Error while editing banca_ore');
                    exit;
                }
            } else {
                
                try {
                    $this->apilib->create('banca_ore', [
                        "banca_ore_creato_da_presenza" => $presenza['presenze_id'] ?? null,
                        "banca_ore_dipendente" => $presenza['presenze_dipendente'],
                        "banca_ore_movimento" => 1, //aggiunta
                        "banca_ore_hours" => $presenza['presenze_straordinario'],
                        "banca_ore_data" => $presenza['presenze_data_fine'],
                    ]);
                } catch (Exception $e) {
                    throw new ApiException('Error while creating banca_ore');
                    exit;
                }
            }
        }
    }
    public function getDayOfWeek($dateString)
    {
        $date = $this->stringToDate($dateString);

        // Verifica se la data è valida
        if ($date === false) {
            return "Data non valida";
        }

        // Ottieni il giorno della settimana (1 = domenica, 7 = sabato)
        return (int) $date->format('N');
    }
    public function stringToDate($dateString)
    {
        // Rileva il formato della data e convertila in un formato standard
        $dateString = explode(' ', $dateString)[0]; //Prendo solo la prima parte (escludo eventuali ore minuti secondi)
        //debug($dateString);
        if (strpos($dateString, '/') !== false) {
            // Se la data è nel formato gg/mm/aaaa
            $date = DateTime::createFromFormat('d/m/Y', $dateString);
        } elseif (strpos($dateString, '-') !== false) {
            // Se la data è nel formato aaaa/mm/gg
            $date = DateTime::createFromFormat('Y-m-d', $dateString);
        } else {
            $date = DateTime::createFromFormat('Ymd', $dateString);
        }
        return $date;
    }
    public function calcolaOreGiornalierePreviste($giorno, $dipendente_id, $escludi_pausa = false)
    {
        // Ottenere il giorno della settimana per la data della presenza
        $giornoSettimana = $this->getDayOfWeek($giorno);

        // Recuperare tutti i turni di lavoro per il dipendente in quel giorno
        $turni = $this->getTurniDiLavoro($dipendente_id, $giornoSettimana);

        $oreTotaliPreviste = 0;

        foreach ($turni as $turno) {
            // Calcolare la durata di ogni turno
            $inizio = DateTime::createFromFormat('H:i', $turno['turni_di_lavoro_ora_inizio']);
            $fine = DateTime::createFromFormat('H:i', $turno['turni_di_lavoro_ora_fine']);
            $durata = $inizio->diff($fine);

            

            // Convertire la durata in minuti e sommarla al totale
            $oreTotaliPreviste += $durata->h * 60 + $durata->i;

            if ($turno['turni_di_lavoro_pausa']) {
                $orePausa = $this->apilib->view('orari_di_lavoro_ore_pausa', $turno['turni_di_lavoro_pausa'])['orari_di_lavoro_ore_pausa_value'];
            } else {
                $orePausa = 0;
            }
            if ($escludi_pausa) {
                $oreTotaliPreviste -= $orePausa * 60;
            }
            
        }

        
        return round($oreTotaliPreviste / 60,2);
    }

    /******************************************************************************************************************************************
     * 
     * ! Se il dipendente ne ha diritto, in base alle ore minime da effettuare ed alle ore previste da contratto per la giornata,
     * ! imposta il flag del buono pasto sulle presenze della giornata
     *  
     ******************************************************************************************************************************************/
    public function gestisciBuonoPasto($Ymd, $dipendente_id, $presenze = null)
    {
        $dipendente = $this->apilib->view('dipendenti', $dipendente_id);
        // debug($dipendente['dipendenti_ore_min_buono_pasto']);
        // debug($dipendente['dipendenti_buoni_pasto'],true);
        if(empty($dipendente)) {
            return DB_BOOL_FALSE;
        }

        // Se non ne ho diritto torno false
        if(empty($dipendente['dipendenti_buoni_pasto']) && $dipendente['dipendenti_buoni_pasto'] == DB_BOOL_FALSE) {
            return DB_BOOL_FALSE;
        }
        // Se non sono impostate le ore da effettuare torno false
        if(empty($dipendente['dipendenti_ore_min_buono_pasto'])) {
            return DB_BOOL_FALSE;
        }

        //Se la presenza è legata ad una richiesta e la richiesta NON è di trasferta NON devo impostare buono pasto
        if(!empty($presenze) && !empty($presenze['presenze_richiesta'])) {
            $richiesta = $this->apilib->view('richieste', $presenze['presenze_richiesta']);

            if(!empty($richiesta) && $richiesta['richieste_tipologia'] != 5) {
                return DB_BOOL_FALSE;
            }
        }

        //Se non ho le presenze le recupero
        if ($presenze === null) {
            $presenze = $this->apilib->search('presenze', [
                'DATE(presenze_data_inizio)' => $Ymd,
                'presenze_dipendente' => $dipendente_id,
                "(presenze_richiesta IS NULL OR presenze_richiesta NOT IN (SELECT richieste_id FROM richieste WHERE richieste_user_id = $dipendente_id AND richieste_tipologia IN (1,2,3) AND DATE(richieste_dal) <= '$Ymd' AND DATE(richieste_al) >= '$Ymd'))"
            ]);
        }

        
        
        $ore_totali = $this->calcolaOreTotali($Ymd, $dipendente_id, $presenze);
        $pause = $this->calcolaOrePausaPranzo($Ymd, $dipendente_id, $presenze);
        $oreLavoroPreviste = $this->calcolaOreGiornalierePreviste($Ymd, $dipendente_id);
        $ore_totali -= $pause;
        // debug($ore_totali);
        // debug($oreLavoroPreviste);
        // debug($dipendente['dipendenti_ore_min_buono_pasto'],true);
        
        if($ore_totali > $dipendente['dipendenti_ore_min_buono_pasto'] && $oreLavoroPreviste > $dipendente['dipendenti_ore_min_buono_pasto']) {
            return DB_BOOL_TRUE;
        } else {
            return DB_BOOL_FALSE;
        }
    }

    /******************************************************************************************************************************************
     * 
     * ! Torna l'indice della pausa da applicare alla presenza, se non impostata manualmente
     *  
     ******************************************************************************************************************************************/
    public function setOrePausa($Ymd, $dipendente_id, $presenze = null)
    {
       
        if ($presenze === null) {
            $presenze = $this->apilib->search('presenze', [
                'DATE(presenze_data_inizio)' => $Ymd,
                'presenze_dipendente' => $dipendente_id
            ]);
        }

        $pausaIndex = 0;

        foreach ($presenze as $presenza) {
            if (!empty($presenza['presenze_pausa'])) {
                // Pausa già impostata manualmente sulla presenza
                $pausaIndex = $presenza['presenze_pausa'];
            } else {
                // Recupera il turno di lavoro per il dipendente in quella specifica giornata
                $giornoSettimana = $this->getDayOfWeek($Ymd);
                $turniDiLavoro = $this->getTurniDiLavoro($dipendente_id, $giornoSettimana);

                // Suggerisci il turno basato sull'orario di inizio della presenza
                $suggerimentoTurno = $this->suggerisciTurno($presenza['presenze_ora_inizio'], $turniDiLavoro, 'entrata');

                if (isset($turniDiLavoro[$suggerimentoTurno])) {
                    // Prendi il valore di pausa dal turno suggerito
                    $turno = $turniDiLavoro[$suggerimentoTurno];
                    if ($turno['turni_di_lavoro_pausa']) {
                        $pausaIndex = $this->apilib->view('orari_di_lavoro_ore_pausa', $turno['turni_di_lavoro_pausa'])['orari_di_lavoro_ore_pausa_id'];
                    } else {
                        $pausaIndex = 0;
                    }
                }
            }
        }

        return $pausaIndex;
    }

    
    public function normalizzaOraInizioFine(&$dati_presenza) {


        //debug('foo',true);
        /*
        Completare seguendo questi parametri

        Range minuti entrata: quanti minuti di tolleranza applicare alla timbratura di entrata rispetto all'orario nel profilo del dipendente (es. dipendente con entrata alle "09:00", specificando "30" e timbrando alle ore "08:50" verrà salvato come orario di entrata "09:00" mentre timbrando alle ore "08:25" verrà salvato l'orario effettivo). Questa regola non si applica invece a timbrature successive all'orario di inizio del dipendente. Di default è impostato a 30 minuti.
        Range minuti uscita: quanti minuti di tolleranza applicare alla timbratura di uscita rispetto all'orario nel profilo del dipendente (es. dipendente con uscita alle "18:00", specificando "30" e timbrando alle ore "18:10" verrà salvato come orario di uscita "18:00" mentre timbrando alle ore "18:35" verrà salvato l'orario effettivo). Questa regola non si applica invece a timbrature precedenti all'orario di fine del dipendente. Di default è impostato a 30 minuti.
        Tolleranza entrata: quanti minuti di tolleranza applicare alla timbratura di entrata rispetto all'orario nel profilo del dipendente (es. dipendente con entrata alle "09:00", specificando "5" e timbrando alle ore "09:04" verrà salvato come orario di entrata "09:00" mentre timbrando alle ore "09:12" verrà salvato l'orario effettivo). Questa regola non si applica invece a timbrature precedenti all'orario di inizio del dipendente. Di default è impostato a 0 minuti.
        Range calcolo: imposta il minimo di minuti entro i quali non viene considerato lo straordinario. Esempio 30 minuti: se vengono effettuati 29 minuti di straordinario NON sarà considerato nulla, se faccio 45 minuti, verranno considerati 30 minuti di straordinario 
        */

        
        $range_calcolo = $this->impostazioni_modulo['impostazioni_hr_range_calcolo'];
        $range_minuti_entrata = $this->impostazioni_modulo['impostazioni_hr_range_minuti_entrata'];
        $range_minuti_uscita = $this->impostazioni_modulo['impostazioni_hr_range_minuti_uscita'];
        $tolleranza_entrata = $this->impostazioni_modulo['impostazioni_hr_tolleranza_entrata'];

        $orari_di_lavoro_previsti = $this->getTurnoDiLavoroFromPresenza($dati_presenza);

        /*
        $orari_di_lavoro_previsto:
        Array
        (
            [0] => Array
                (
                    [turni_di_lavoro_id] => 363
                    [turni_di_lavoro_creation_date] => 2024-02-13 10:25:11
                    [turni_di_lavoro_modified_date] => 
                    [turni_di_lavoro_created_by] => 2
                    [turni_di_lavoro_edited_by] => 
                    [turni_di_lavoro_insert_scope] => form
                    [turni_di_lavoro_edit_scope] => 
                    [turni_di_lavoro_dipendente] => 6
                    [turni_di_lavoro_data_inizio] => 2020-02-13 00:00:00
                    [turni_di_lavoro_data_fine] => 
                    [turni_di_lavoro_ora_inizio] => 09:00
                    [turni_di_lavoro_ora_fine] => 13:00
                    [turni_di_lavoro_pausa] => 4
                    [turni_di_lavoro_template] => 
                    [turni_di_lavoro_giorno] => 1
                    [turni_di_lavoro_notturno_inizio] => 
                    [turni_di_lavoro_notturno_fine] => 
                    [turni_di_lavoro_reparto] => 
                )

            [1] => Array
                (
                    [turni_di_lavoro_id] => 364
                    [turni_di_lavoro_creation_date] => 2024-02-13 10:25:40
                    [turni_di_lavoro_modified_date] => 
                    [turni_di_lavoro_created_by] => 2
                    [turni_di_lavoro_edited_by] => 
                    [turni_di_lavoro_insert_scope] => form
                    [turni_di_lavoro_edit_scope] => 
                    [turni_di_lavoro_dipendente] => 6
                    [turni_di_lavoro_data_inizio] => 2020-02-13 00:00:00
                    [turni_di_lavoro_data_fine] => 
                    [turni_di_lavoro_ora_inizio] => 14:00
                    [turni_di_lavoro_ora_fine] => 18:00
                    [turni_di_lavoro_pausa] => 4
                    [turni_di_lavoro_template] => 
                    [turni_di_lavoro_giorno] => 1
                    [turni_di_lavoro_notturno_inizio] => 
                    [turni_di_lavoro_notturno_fine] => 
                    [turni_di_lavoro_reparto] => 
                )

        )
        */
        if ($dati_presenza['presenze_ora_inizio'] != $dati_presenza['presenze_ora_inizio_effettivo']) {
            $ora_inizio_normalizzata = $dati_presenza['presenze_ora_inizio'] ?? $dati_presenza['presenze_ora_inizio_effettivo'];
        } else {
            $ora_inizio_normalizzata = $dati_presenza['presenze_ora_inizio_effettivo'] ?? $dati_presenza['presenze_ora_inizio'];
        }

        /* if ('3592' == $dati_presenza['presenze_id']) {
            // debug($dati_presenza);
            // debug($ora_inizio_normalizzata, true);
        } */
        
        if ($dati_presenza['presenze_ora_fine'] != $dati_presenza['presenze_ora_fine_effettiva']) {
            $ora_fine_normalizzata = $dati_presenza['presenze_ora_fine'] ?? $dati_presenza['presenze_ora_fine_effettiva'];
        } else {
            $ora_fine_normalizzata = $dati_presenza['presenze_ora_fine_effettiva'] ?? $dati_presenza['presenze_ora_fine'];
        }

        
        $suggerimentoTurno = $this->suggerisciTurno($dati_presenza['presenze_ora_inizio_effettivo'], $orari_di_lavoro_previsti, 'entrata');
        if (array_key_exists($suggerimentoTurno, $orari_di_lavoro_previsti)) {
            $turno_di_lavoro_previsto = $orari_di_lavoro_previsti[$suggerimentoTurno];

            //Di default li imposto uguali a quelli passati nella presenza
            

            //Calcolo la differenza tra l'orario di inizio passato in dati_presenza e quello del turno di lavoro
            $ora_inizio = new DateTime($dati_presenza['presenze_data_inizio'] . ' ' . $dati_presenza['presenze_ora_inizio_effettivo']);
            $ora_inizio_turno = new DateTime($dati_presenza['presenze_data_inizio'] . ' ' . $turno_di_lavoro_previsto['turni_di_lavoro_ora_inizio']);
            $differenza_inizio = $ora_inizio->diff($ora_inizio_turno);
            $minuti_inizio = $differenza_inizio->days * 24 * 60 + $differenza_inizio->h * 60 + $differenza_inizio->i;

            //Se invert è 1 allora vuol dire che sto entrando dopo. Se è 0 vuol dire che sto entrando prima
            if ($minuti_inizio < $range_minuti_entrata) {
                //Ora valuto la tolleranza in entrata, ovvero se l'orario di inizio è minore di tolleranza_entrata, allora imposto l'orario di inizio del turno di lavoro
                if ($minuti_inizio < $tolleranza_entrata) {
                    $ora_inizio_normalizzata = $turno_di_lavoro_previsto['turni_di_lavoro_ora_inizio'];
                } else {
                    //Normalizzo solo se non sto entrando troppo in ritardo!
                    if ($differenza_inizio->invert != 1) {
                        //Se la differenza è maggiore del range minuti entrata, allora imposto l'orario di inizio del turno di lavoro
                        $ora_inizio_normalizzata = $turno_di_lavoro_previsto['turni_di_lavoro_ora_inizio'];
                    }
                    
                }
            }

            $ora_fine = new DateTime($dati_presenza['presenze_data_fine'] . ' ' . $dati_presenza['presenze_ora_fine_effettiva']);
            $ora_fine_turno = new DateTime($dati_presenza['presenze_data_inizio'] . ' ' . $turno_di_lavoro_previsto['turni_di_lavoro_ora_fine']);
            $differenza_fine = $ora_fine->diff($ora_fine_turno);
            $minuti_fine = $differenza_fine->days * 24 * 60 + $differenza_fine->h * 60 + $differenza_fine->i;

            if ($minuti_fine < $range_minuti_uscita) {
                //Se la differenza è maggiore del range minuti uscita, allora imposto l'orario di fine del turno di lavoro
                $ora_fine_normalizzata = $turno_di_lavoro_previsto['turni_di_lavoro_ora_fine'];
            }
        }

        // if ('2024-01-11' == substr($dati_presenza['presenze_data_inizio'], 0, 10)) {
        //     debug($ora_inizio_normalizzata);
        //     debug($dati_presenza,true);
        // }

            

        $dati_presenza['presenze_ora_inizio'] = $ora_inizio_normalizzata;
        $dati_presenza['presenze_ora_fine'] = $ora_fine_normalizzata;
        return;
    }

    public function riprocessa_presenza($presenza)
    {
        if (is_numeric($presenza)) {
            
            $presenza = $this->db->where('presenze_id', $presenza)->get('presenze')->row_array();
        }
        
        $this->db->where('banca_ore_creato_da_presenza', $presenza['presenze_id'])->delete('banca_ore');
        $this->mycache->clearEntityCache('banca_ore');
        //Svuoto tutto e lascio ricalcolare ai post process
        $presenza['presenze_scope_edit'] = 'RIPROCESSA PRESENZE';
        $presenza['presenze_ora_inizio'] = $presenza['presenze_ora_inizio_effettivo']??$presenza['presenze_ora_inizio'];
        $presenza['presenze_ora_fine'] = $presenza['presenze_ora_fine_effettiva']??$presenza['presenze_ora_fine'];
        unset($presenza['presenze_data_inizio_calendar']);
        unset($presenza['presenze_data_fine_calendar']);
        unset($presenza['presenze_ore_totali']);
        unset($presenza['presenze_straordinario']);

        unset($presenza['presenze_costo_orario']);
        unset($presenza['presenze_valore_orario']);
        unset($presenza['presenze_costo_giornaliero']);
        unset($presenza['presenze_valore_giornaliero']);
        //Questo non lo unsetto, altrimenti poi non sovrascrive con note vuote
        $presenza['presenze_note_anomalie'] = '';
        unset($presenza['presenze_buono_pasto']);
        unset($presenza['presenze_ore_ordinarie']);
        //Scateno una edit in modo da far rientrare in tutti i pre/post process che ricalcolano tutti i valori
        

        $presenza = $this->apilib->edit('presenze', $presenza['presenze_id'], $presenza);

        if ($presenza['presenze_id'] == 3786) {
            //debug($presenza,true);
        }

        return $presenza;
    }

    public function creaPresenzaDaRichiesta($richiesta) {       
        $impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
        $crea_presenze = $impostazioni_modulo['impostazioni_hr_crea_presenze_da_richieste'] ?? DB_BOOL_FALSE;
        $presenze_da_cancellare = $this->apilib->search('presenze', ['presenze_richiesta' => $richiesta['richieste_id']]);
        //debug($presenze_da_cancellare, true);
        foreach ($presenze_da_cancellare as $presenza) {
            $this->apilib->delete('presenze', $presenza['presenze_id']);
        }
        if($crea_presenze != DB_BOOL_TRUE || $richiesta['richieste_stato'] != '2' || in_array($richiesta['richieste_tipologia'], [6,7])) {
            //Per prima cosa verifico che la richiesta sia approvata, altrimenti non creo alcuna presenza
            // Stessa cosa per richieste di disponibilità e indisponibilità
        
            return;

        }

        //return;
        // Prendo reparti dipendente, se uno solo lo imposto in automatico su presenza
        $reparto = null;
        $reparti = $this->db->query("SELECT * FROM reparti WHERE reparti_id IN (SELECT reparti_id FROM rel_reparto_dipendenti WHERE dipendenti_id = '{$richiesta['richieste_user_id']}')")->result_array();
        if (!empty($reparti)) {
            $reparto = $reparti[0]['reparti_id'];
        }
        //debug($richiesta,true);
        // Se è permesso oppure trasferta devo crearla SOLO per gli orari del permesso
        if (
            ($richiesta['richieste_tipologia'] == 1 || $richiesta['richieste_tipologia'] == 5) || 
            ($richiesta['richieste_ora_inizio'] && $richiesta['richieste_ora_fine'])
        ) {
            
            //TODO: creare una presenza per ogni turno di lavoro, fino all'ora fine, in modo da considerare eventuali salti per pause pranzo

            // Calcolo ore totali
            $dataOraInizio = new DateTime(substr($richiesta['richieste_dal'], 0, 10) . ' ' . $richiesta['richieste_ora_inizio']);
            $dataOraFine = new DateTime(substr($richiesta['richieste_al'], 0, 10) . ' ' . $richiesta['richieste_ora_fine']);
            $diff_orari = $dataOraInizio->diff($dataOraFine);
            $ore_tot = round(($diff_orari->s / 3600) + ($diff_orari->i / 60) + $diff_orari->h + ($diff_orari->days * 24), 2);
            // Campi calendario
            if($richiesta['richieste_ora_inizio'] && $richiesta['richieste_ora_fine']) {
                $inizio_calenadario = substr($richiesta['richieste_dal'], 0, 10) . ' ' . $richiesta['richieste_ora_inizio'] . ':00';
                $fine_calendario = substr($richiesta['richieste_al'], 0, 10) . ' ' . $richiesta['richieste_ora_fine'] . ':00';
            } else {
                $inizio_calenadario = substr($richiesta['richieste_dal'], 0, 10) . ' ' . '00:00:00';
                $fine_calendario = substr($richiesta['richieste_al'], 0, 10) . ' ' . '00:00:00';
            }

            

            $inizio = new DateTime($richiesta['richieste_dal']);
            $fine = new DateTime($richiesta['richieste_al']);
            $fine->modify('+1 day'); // Includo il giorno finale nella generazione delle presenze

            $intervallo = new DateInterval('P1D');
            $periodo = new DatePeriod($inizio, $intervallo, $fine);

            foreach ($periodo as $data) {
                $giorno_richiesta = $data->format('Y-m-d');
                $turniDiLavoro = $this->getTurniDiLavoro($richiesta['richieste_user_id'], date('N', strtotime($giorno_richiesta)));
                $fine_richiesta_con_ora = new DateTime($giorno_richiesta . ' ' . $richiesta['richieste_ora_fine']);
                foreach ($turniDiLavoro as $turno) {
                    //debug($giorno_richiesta);
                    $inizio_turno = new DateTime($giorno_richiesta . ' ' . $turno['turni_di_lavoro_ora_inizio']);
                    $fine_turno = new DateTime($giorno_richiesta . ' ' . $turno['turni_di_lavoro_ora_fine']);

                    // Determina l'inizio effettivo considerando il massimo tra l'inizio del turno e l'inizio della richiesta
                    
                    
                    //Nel caso di trasferta fa comunque fede l'ora indicata nella richiesta, a prescindere dal turno (al più saranno straordinari...)
                    if ($richiesta['richieste_tipologia'] == 5) {
                        $inizio_effettivo = $dataOraInizio;
                        $fine_effettiva = $fine_richiesta_con_ora;
                    } else {
                        $inizio_effettivo = max($dataOraInizio, $inizio_turno);
                        if ($data->format('Y-m-d') == (new DateTime($richiesta['richieste_al']))->format('Y-m-d')) {
                            
                            $fine_effettiva = min($fine_richiesta_con_ora, $fine_turno);
                        } else {
                            $fine_effettiva = min(new DateTime($giorno_richiesta . ' 23:59:59'), $fine_turno);
                        }
                    }
                    
                    //debug($fine_effettiva,true);
//                    debug($fine_effettiva->format('Y-m-d H:i:s'));
                    // Assicurati che l'inizio effettivo sia prima della fine effettiva
                    if ($inizio_effettivo < $fine_effettiva) {
                        //debug($giorno_richiesta);
                        // Calcola le ore totali tra l'inizio e la fine effettivi
                        $diff = $inizio_effettivo->diff($fine_effettiva);
                        $ore_totali = $diff->h + ($diff->i / 60);

                        try {
                            $presenze_pausa = $this->db->get_where('presenze_pausa' , ['presenze_pausa_value' => $turno['orari_di_lavoro_ore_pausa_value']]);
                            if ($presenze_pausa->num_rows() == 1) {
                                $presenze_pausa = $presenze_pausa->row()->presenze_pausa_id;
                            } else {
                                $presenze_pausa = null;

                            }
                            $this->db->insert('presenze', [
                                'presenze_creation_date' => date('Y-m-d H:i:s'),
                                'presenze_dipendente' => $richiesta['richieste_user_id'],
                                'presenze_data_inizio' => $giorno_richiesta,
                                'presenze_ora_inizio' => $inizio_effettivo->format('H:i'),
                                'presenze_data_fine' => $giorno_richiesta,
                                'presenze_ora_fine' => $fine_effettiva->format('H:i'),
                                'presenze_ore_totali' => $ore_totali- $turno['orari_di_lavoro_ore_pausa_value'],
                                'presenze_reparto' => $reparto,
                                'presenze_richiesta' => $richiesta['richieste_id'],
                                'presenze_scope_create' => 'Funzione creaPresenzaDaRichiesta',
                                'presenze_pausa' => $presenze_pausa,
                            ]);

                            if ($richiesta['richieste_tipologia'] == 5) { //Se è trasferta, non credo una presenza per ogni turno di lavoro, ma una unica, visto che fanno fede gli orari inseriti nella richiesta.
                                $presenza_inserita_id = $this->db->insert_id();
                                if ($presenza_inserita_id) {
                                    $buono = $this->gestisciBuonoPasto($giorno_richiesta, $richiesta['richieste_user_id']);
                                    $this->db->where('presenze_id', $presenza_inserita_id)->update('presenze', ['presenze_buono_pasto' => $buono]);
                                }
                                break;
                            }
                        } catch (Exception $e) {
                            log_message('error', "Impossibile creare presenza automatica da richiesta #{$richiesta['richieste_id']}: " . $e->getMessage());
                        }
                    }
                }
            }


        }

        // Se è malattia/ferie/smart working o missione devo crearla in base all'orario del profilo
        elseif (in_array($richiesta['richieste_tipologia'], [2, 3, 4])) {

            // Se non ho data di fine richiesta (malattia senza fine) salto la creazione
            if (!empty($richiesta['richieste_al'])) {
                // Ricavo intervallo di date della richiesta
                $inizio = new DateTime($richiesta['richieste_dal']);
                $fine = new DateTime($richiesta['richieste_al']);
                $fine->modify('+1 day'); // Aggiungi un giorno per includere anche l'ultimo giorno
                $intervallo = new DateInterval('P1D'); // Intervallo di un giorno
                $periodo = new DatePeriod($inizio, $intervallo, $fine);

                // Creo presenza per ogni giorno della richiesta
                foreach ($periodo as $data) {
                    $giorno_richiesta = $data->format('Y-m-d');

                    //$data_inizio_richiesta = dateFormat($richiesta['richieste_dal'], 'Y-m-d');
                    $richiesta_inizio_giorno = date('N', strtotime($giorno_richiesta));

                    // Recupero turni lavoro
                    $this->db->where("turni_di_lavoro_data_inizio <= '{$giorno_richiesta}'", null, false);
                    $this->db->where("(turni_di_lavoro_data_fine >= '{$giorno_richiesta}' OR turni_di_lavoro_data_fine IS NULL)", null, false); //aggiungo anche il vuoto, se uno non imposta la data di fine.
                    $this->db->where('turni_di_lavoro_dipendente', $richiesta['richieste_user_id']);
                    $this->db->where('turni_di_lavoro_giorno', $richiesta_inizio_giorno); //Prendo il giorno della richiesta
                    $this->db->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', 'LEFT');
                    $turni_lavoro = $this->db->get('turni_di_lavoro')->result_array();
                    
                    if (!empty($turni_lavoro)) {
                        // Creo una presenza per ogni turno che trovo
                        foreach ($turni_lavoro as $turno) {
                            $inizio_turno = $turno['turni_di_lavoro_ora_inizio'] ?? '00:00';
                            $fine_turno = $turno['turni_di_lavoro_ora_fine'] ?? '23:59';
                            // Calcolo ore totali
                            $dataOraInizio = new DateTime($giorno_richiesta . ' ' . $inizio_turno);
                            $dataOraFine = new DateTime($giorno_richiesta . ' ' . $fine_turno);
                            $diff_orari = $dataOraInizio->diff($dataOraFine);
                            $ore_tot = round(($diff_orari->s / 3600) + ($diff_orari->i / 60) + $diff_orari->h + ($diff_orari->days * 24), 2);

                            try {
                                //debug($turno);
                                $presenze_pausa = $this->db->get_where('presenze_pausa', ['presenze_pausa_value' => $turno['orari_di_lavoro_ore_pausa_value']]);
                                if ($presenze_pausa->num_rows() == 1) {
                                    $presenze_pausa = $presenze_pausa->row()->presenze_pausa_id;
                                } else {
                                    $presenze_pausa = null;

                                }
                                $this->db->insert('presenze', [
                                    'presenze_creation_date' => date('Y-m-d H:i:s'),
                                    'presenze_dipendente' => $richiesta['richieste_user_id'],
                                    'presenze_data_inizio' => $giorno_richiesta,
                                    'presenze_data_fine' => $giorno_richiesta,
                                    'presenze_ora_inizio' => $inizio_turno,
                                    'presenze_ora_fine' => $fine_turno,
                                    'presenze_data_inizio_calendar' => $giorno_richiesta . ' ' . $inizio_turno . ':00',
                                    'presenze_data_fine_calendar' => $giorno_richiesta . ' ' . $fine_turno . ':00',
                                    'presenze_ore_totali' => $ore_tot,
                                    'presenze_straordinario' => 0,
                                    'presenze_reparto' => $reparto,
                                    'presenze_richiesta' => $richiesta['richieste_id'],
                                    'presenze_note' => $richiesta['richieste_note'],
                                    'presenze_smartworking' => $richiesta['richieste_tipologia'] == 4 ? DB_BOOL_TRUE : DB_BOOL_FALSE,
                                    'presenze_scope_create' => 'CRON PRESENZE RICHIESTE',
                                    'presenze_pausa' => $presenze_pausa,
                                ]);
                            } catch (Exception $e) {
                                log_message('error', "Impossibile creare presenza automatica da richiesta #{$richiesta['richieste_id']}: " . $e->getMessage());
                            }
                        }
                    } else {
                        // se la tipologia di richiesta NON è ferie, creo la presenza anomala.
                        // michael, 20/08/2024 - deciso con matteo di applicare questa correzione in quanto se un dipendente non ha un turno di lavoro, viene creata la presenza con conseguente anomalia anche su zucchetti
                        if (!in_array($richiesta['richieste_tipologia'], [2])) {
                            // Creo comunque con 09-17 e segnalo anomalia
                            $inizio_turno = '09:00';
                            $fine_turno = '17:00';
                            try {
                                $this->db->insert('presenze', [
                                    'presenze_creation_date' => date('Y-m-d H:i:s'),
                                    'presenze_dipendente' => $richiesta['richieste_user_id'],
                                    'presenze_data_inizio' => $giorno_richiesta,
                                    'presenze_data_fine' => $giorno_richiesta,
                                    'presenze_ora_inizio' => $inizio_turno,
                                    'presenze_ora_fine' => $fine_turno,
                                    'presenze_data_inizio_calendar' => $giorno_richiesta . ' 09:00:00',
                                    'presenze_data_fine_calendar' => $giorno_richiesta . ' 18:00:00',
                                    'presenze_ore_totali' => 9,
                                    'presenze_straordinario' => 0,
                                    'presenze_reparto' => $reparto,
                                    'presenze_richiesta' => $richiesta['richieste_id'],
                                    'presenze_note' => $richiesta['richieste_note'],
                                    'presenze_anomalia' => DB_BOOL_TRUE,
                                    'presenze_note_anomalie' => 'Creata dal sistema (a partire da richiesta) in giornata senza turni di lavoro registrati',
                                    'presenze_note' => $richiesta['richieste_note'],
                                    'presenze_smartworking' => $richiesta['richieste_tipologia'] == 4 ? DB_BOOL_TRUE : DB_BOOL_FALSE,
                                    'presenze_scope_create' => 'CRON PRESENZE RICHIESTE'
                                ]);
                            } catch (Exception $e) {
                                log_message('error', "Impossibile creare presenza automatica, per dipendente senza turno, da richiesta #{$richiesta['richieste_id']}: " . $e->getMessage());
                            }
                        }
                    }
                }
            } else {
                log_message('error', "Impossibile creare presenza automatica da richiesta #{$richiesta['richieste_id']}: richiesta senza data di fine");
            }
        }
        $this->mycache->deleteByTags(['richieste', 'presenze', 'buoni_pasto']);
    }
}