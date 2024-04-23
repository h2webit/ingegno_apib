<?php
//Se ho richiesta attiva che comprende giornata posso dover bloccare l'inserimento
$this->load->model('modulo-hr/timbrature');

$richieste = $this->apilib->search('richieste', [
    'richieste_user_id' => $data['post']['presenze_dipendente'],
    'richieste_stato' => 2, //solo richieste approvate
]);
$impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
//$range_calcolo = (isset($impostazioni_modulo['impostazioni_hr_range_calcolo']) ? $impostazioni_modulo['impostazioni_hr_range_calcolo'] : 1);
$range_calcolo = (isset($impostazioni_modulo['impostazioni_hr_range_calcolo']) ? $impostazioni_modulo['impostazioni_hr_range_calcolo'] == 0 ? 1 : $impostazioni_modulo['impostazioni_hr_range_calcolo'] : 1);

if (!empty($richieste)) {
    foreach ($richieste as $richiesta) {
        debug($richiesta);
        //se la richiesta comprende oggi
        //if (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= dateFormat($data['post']['presenze_data_inizio'], 'Y-m-d') && dateFormat($richiesta['richieste_al'], 'Y-m-d') >= dateFormat($data['post']['presenze_data_fine'], 'Y-m-d')) {
        if (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= dateFormat($data['post']['presenze_data_inizio'], 'Y-m-d') && dateFormat($richiesta['richieste_al'], 'Y-m-d') >= dateFormat($data['post']['presenze_data_inizio'], 'Y-m-d')) {
            if ($richiesta['richieste_tipologia'] == 1) { //PERMESSO
                //Caso in cui la presenza ricade all'interno del permesso
                //Aggiunto controllo !empty($data['post']['presenze_ora_fine']) perchè altrimenti non fa inserire presenze con inizio non compreso nelll'intervallo del permesso e senza data ed ora di fine
                // 30/01/2024 - Non blocco inserimento ma segnalo anomalia
                $iniziaDopoInizioRichiesta = $data['post']['presenze_ora_inizio'] < $richiesta['richieste_ora_fine'];
                $finiscePrimaFineRichiesta = $data['post']['presenze_ora_fine'] > $richiesta['richieste_ora_inizio'];
		debug($iniziaDopoInizioRichiesta);
                debug($finiscePrimaFineRichiesta);
                if ($iniziaDopoInizioRichiesta && $finiscePrimaFineRichiesta && !empty($data['post']['presenze_ora_fine'])) {
                    $note_vecchie = ($data['post']['presenze_note_anomalie']) ? "{$data['post']['presenze_note_anomalie']}\n\r" : '';
                    $data['post']['presenze_note_anomalie'] = "{$note_vecchie}(".date('d/m/Y H:i').") TIMBRATURA CON PERMESSO IN CORSO";
                }
            } elseif ($richiesta['richieste_tipologia'] == 2) { //FERIE
                /*throw new ApiException("Non puoi creare la presenza avendo una richiesta di ferie in corso.");
                exit; */
                // 30/01/2024 - Non si blocca l'inserimento ma si segnala l'anomalia                
                $note_vecchie = ($data['post']['presenze_note_anomalie'])?"{$data['post']['presenze_note_anomalie']}\n\r":'';
                $data['post']['presenze_note_anomalie'] = "{$note_vecchie}(".date('d/m/Y H:i').") TIMBRATURA CON FERIE IN CORSO";
            } elseif ($richiesta['richieste_tipologia'] == 3) { //MALATTIA
                /*throw new ApiException("Non puoi creare la presenza avendo una richiesta di malattia in corso.");
                exit; */
                // 30/01/2024 - Non si blocca l'inserimento ma si segnala l'anomalia                
                $note_vecchie = ($data['post']['presenze_note_anomalie'])?"{$data['post']['presenze_note_anomalie']}\n\r":'';
                $data['post']['presenze_note_anomalie'] = "{$note_vecchie}(".date('d/m/Y H:i').") TIMBRATURA CON MALATTIA IN CORSO";
            }
        } elseif (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= dateFormat($data['post']['presenze_data_inizio'], 'Y-m-d') && empty($richiesta['richieste_al']) && $richiesta['richieste_tipologia'] == 3) {
            //se la richiesta è senza data fine ed è MALATTIA
            /*throw new ApiException("Non puoi creare la presenza avendo una richiesta di malattia in corso.");
            exit; */
            // 30/01/2024 - Non si blocca l'inserimento ma si segnala l'anomalia                
            $note_vecchie = ($data['post']['presenze_note_anomalie'])?"{$data['post']['presenze_note_anomalie']}\n\r":'';
            $data['post']['presenze_note_anomalie'] = "{$note_vecchie}(".date('d/m/Y H:i').") TIMBRATURA CON MALATTIA SENZA DATA DI FINE IN CORSO";
        } elseif ((strtotime($richiesta['richieste_dal']) == strtotime($data['post']['presenze_data_inizio'])) || (strtotime($richiesta['richieste_al']) == strtotime($data['post']['presenze_data_fine']))) {
            //Se la richiesta ha una giornata in comune con la presenza che sto cercando di inserire
            /*throw new ApiException("Non puoi creare la presenza avendo una richiesta per una delle due date selezionate.");
            exit;*/
            // 30/01/2024 - Non si blocca l'inserimento ma si segnala l'anomalia                
            $note_vecchie = ($data['post']['presenze_note_anomalie'])?"{$data['post']['presenze_note_anomalie']}\n\r":'';
            $data['post']['presenze_note_anomalie'] = "{$note_vecchie}(".date('d/m/Y H:i').") TIMBRATURA CON RICHIESTA IN UNA DELLE DATE SCELTE";
        }
    }
}

//Aggiunto controllo iniziale in maniera tale che admin possa anche solo inserire data ed ora entrata,
//Successivamente inserirà data ed ora fine e verranno effettuati i controlli (quindi anche sovrascrivere ora entrata, calcolo straordinari ecc ecc)
//La fortuna vuole che empty torna true quando inseriamo "0" nelle ore. Questo ci permette di forzare un ricalcolo automatico....
if(empty($data['post']['presenze_ore_totali']) && empty($data['post']['presenze_straordinario'])
   && !empty($data['post']['presenze_data_inizio']) && !empty($data['post']['presenze_ora_inizio'])
   && !empty($data['post']['presenze_data_fine']) && !empty($data['post']['presenze_ora_fine'])
  ) {

    //Controllo date ed orari
    if(empty($data['post']['presenze_data_inizio']) || empty($data['post']['presenze_data_fine'])) {
        throw new ApiException("Le date di inizio e fine sono obbligatorie");
        exit;
    }
    if(empty($data['post']['presenze_ora_inizio']) || empty($data['post']['presenze_ora_fine'])) {
        throw new ApiException("Le ore di inizio e fine sono obbligatorie");
        exit;
    }
    /*if($data['post']['presenze_ora_inizio'] > $data['post']['presenze_ora_fine']) {
        throw new ApiException("Non puoi inserire una presenza che cade su due giornate. Fai un primo inserimento dall'ora di inizio fino alle ore 23:59 ed un altro inserimento dalle 00:00 all'ora di fine");
        exit;
    }*/

    $check_entrata = new DateTime($data['post']['presenze_data_inizio']);
    $check_oggi = new DateTime();
    if($check_entrata > $check_oggi) {
        throw new ApiException("Non puoi inserire una presenza per una data futura");
        exit;
    }

    //controllo banca ore
    $impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
    $banca_ore = $impostazioni_modulo['impostazioni_hr_banca_ore'];

    //Recupero dati dipendente
    $dipendente = $this->apilib->view('dipendenti', $data['post']['presenze_dipendente']);
    $timbratura_diretta = $dipendente['dipendenti_timbratura_diretta'] ?? DB_BOOL_FALSE;

    //Creazione campi per controlli
    $data_entrata = dateFormat($data['post']['presenze_data_inizio'], 'Y-m-d');
    $data_ora_entrata = $data_entrata.' '.$data['post']['presenze_ora_inizio'];
    $data_uscita = dateFormat($data['post']['presenze_data_fine'], 'Y-m-d');
    $data_ora_uscita = $data_uscita.' '.$data['post']['presenze_ora_fine'];
    //Imposto campi calendario   
    $data['post']['presenze_data_inizio_calendar'] = $data_ora_entrata.':00';
    $data['post']['presenze_data_fine_calendar'] = $data_ora_uscita.':00';
    
    
    
    //Calcolo base (dopo potrebbe essere sovrascritto da "condizioni speciali")
    $inizio = new DateTime($data_ora_entrata);
    $fine = new DateTime($data_ora_uscita);
    $diff_date = $fine->diff($inizio);
    $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);     
	$data['post']['presenze_ore_totali'] = $hours;
    
    $this->db->where('turni_di_lavoro_dipendente', $data['post']['presenze_dipendente']);
    $orari_lavoro = $this->db->get('turni_di_lavoro')->result_array();

    //$hours = 0;
    $trasferta = 0;//imposto 0 e poi imposto 1 se entro quando non ha un orario, così da non rifare il conto alla fine.

    if(!empty($orari_lavoro)) {
        //Recupero orario dipendente per la giornata inserita
        $today = date('N', strtotime($data['post']['presenze_data_inizio']));     

        $this->db->select('turni_di_lavoro.*'); // Seleziona tutte le colonne da entrambe le tabelle
        $this->db->from('turni_di_lavoro');
        $this->db->where("turni_di_lavoro_data_inizio <= '{$data['post']['presenze_data_fine']}'", null, false);
        $this->db->where("(turni_di_lavoro_data_fine >= '{$data['post']['presenze_data_fine']}' OR turni_di_lavoro_data_fine IS NULL)", null, false); //aggiungo anche il vuoto, se uno non imposta la data di fine.
        $this->db->where('turni_di_lavoro_dipendente', $data['post']['presenze_dipendente']);
        $this->db->where('turni_di_lavoro_giorno', $today);
        $orario_lavoro = $this->db->get()->result_array();


        //Ho orario per la giornata, controllo ingresso ed uscita per gli straordinari
        if(!empty($orario_lavoro)) {
            
            $suggerimentoTurno = $this->timbrature->suggerisciTurno($data['post']['presenze_ora_inizio'], $orario_lavoro,'entrata');

            $impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
            $range_entrata = $impostazioni_modulo['impostazioni_hr_range_minuti_entrata'] ?? 30;
            $range_uscita = $impostazioni_modulo['impostazioni_hr_range_minuti_uscita'] ?? 30;
            $consenti_straordinario = $dipendente['dipendenti_consenti_straordinari'] ?? DB_BOOL_FALSE;
            $reparto_dettagli = $this->apilib->searchFirst('reparti', [
                "reparti_id" => $data['post']['presenze_reparto']
            ]);
            $consenti_straordinario_reparto = $reparto_dettagli['reparti_straordinari'] ?? DB_BOOL_FALSE;

            /*
    		* CONTROLLI ENTRATA
    		*/    
            //IMPOSTO ORARIO CORRETTO IN ENTRATA IN BASE AL CONTROLLO DEL RANGE
            $ora_entrata = $data['post']['presenze_ora_inizio']; 
            $ora_inizio = new Datetime($data['post']['presenze_ora_inizio']);
            $ora_inizio_profilo = new DateTime($orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio']);
            $ora_fine_profilo = new DateTime($orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine']);

            $diff_orari_entrata = $ora_inizio->diff($ora_inizio_profilo);
            //debug($diff_orari_entrata);
            $diff_entrata_minuti = $diff_orari_entrata->i; //0
            $diff_entrata_ore = $diff_orari_entrata->h; //5
            $invert = $diff_orari_entrata->invert; // 1 se ora inizio > ora_inizio_profilo, 0 se ora inizio < ora_inizio_profilo
            //differenza tra entrata e fine profilo
            $diff_orari_uscita = $ora_inizio->diff($ora_fine_profilo);
            $diff_uscita_minuti = $diff_orari_uscita->i;
            $diff_uscita_ore = $diff_orari_uscita->h;
            $invert_uscita = $diff_orari_uscita->invert; // 1 se ora inizio > $ora_fine_profilo, 0 se ora inizio < $ora_fine_profilo

            //Se timbro prima dell'inizio e sono entro il range di tollerenza salvo l'ora inizio del profilo
            if (($diff_entrata_minuti < $range_entrata && $diff_entrata_ore < 1 && $invert == 0)) {
                $ora_entrata = $orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio'];
            } elseif ($diff_entrata_minuti > $range_entrata && $diff_entrata_ore < 1 && $invert == 0 && ($consenti_straordinario == DB_BOOL_FALSE || $consenti_straordinario_reparto == DB_BOOL_FALSE))  {
                //Se sono oltre il range definito nelle impostazioni ma gli straordinario NON sono consentiti salvo l'ora inizio del profilo
                $ora_entrata = $orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio'];
            }elseif ($diff_entrata_ore > 1 && $invert == 0 && ($consenti_straordinario == DB_BOOL_FALSE || $consenti_straordinario_reparto == DB_BOOL_FALSE))  {
                //Se sono oltre il range definito nelle impostazioni ma gli straordinario NON sono consentiti e sto timbrando almeno 1 ora prima
                /*throw new ApiException("Straordinari non consentiti, stai timbrando l'entrata prima dell'inizio stabilito nel tuo profilo.");
                exit;*/
                // 30/01/2024 - Non si blocca l'inserimento ma si segnala l'anomalia                
                $note_vecchie = ($data['post']['presenze_note_anomalie'])?"{$data['post']['presenze_note_anomalie']}\n\r":'';
                $data['post']['presenze_note_anomalie'] = "{$note_vecchie}(".date('d/m/Y H:i').") ENTRATA PRIMA DELL'INIZIO REGISTRATO E STRAORDINARI NON CONSENTITI";
            }elseif ($invert_uscita == 1 && ($consenti_straordinario == DB_BOOL_FALSE || $consenti_straordinario_reparto == DB_BOOL_FALSE)) {
                //Sto timbrando in ritardo e fuori dall'orario lavorativo (entrata > fine profilo) e straordinario disabilitato
                /*throw new ApiException("Straordinari non consentiti, stai timbrando entrata dopo la fine registrata nel profilo.");
                exit;*/
                // 30/01/2024 - Non si blocca l'inserimento ma si segnala l'anomalia                
                $note_vecchie = ($data['post']['presenze_note_anomalie'])?"{$data['post']['presenze_note_anomalie']}\n\r":'';
                $data['post']['presenze_note_anomalie'] = "{$note_vecchie}(".date('d/m/Y H:i').") ENTRATA PRIMA DELL'INIZIO REGISTRATO E STRAORDINARI NON CONSENTITI";
            }    
            /*
    		* CONTROLLI USCITA
    		*/
            //IMPOSTO ORARIO CORRETTO IN USCITA IN BASE AL CONTROLLO DEL RANGE
            $ora_uscita = $data['post']['presenze_ora_fine'];
            $ora_fine = new Datetime($ora_uscita);
            $ora_fine_profilo = new DateTime($orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine']);

            $diff_orari_uscita = $ora_fine->diff($ora_fine_profilo);
            $diff_uscita_minuti = $diff_orari_uscita->i;
            $diff_uscita_ore = $diff_orari_uscita->h;
            $invert = $diff_orari_uscita->invert; // 1 se ora fine > ora_fine_profilo, 0 se ora fine < ora_fine_profilo
            //Se timbro dopo la fine e sono entro il range di tollerenza salvo l'ora fine del profilo
            // Aggiunto presenza inizio < ora fine profilo per fare in modo che se ho timbrato entrata dopo la fine del profilo e adesso sto uscendo di nuovo deve mettere l'ora che invio io e non quella del profilo
            if ($diff_uscita_minuti < $range_uscita && $diff_uscita_ore < 1 && $invert == 1 && (new DateTime($presenza['presenze_ora_inizio']) < $ora_fine_profilo)) { //
                $ora_uscita = $orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine'];
            } elseif ($diff_uscita_minuti >= $range_uscita && $diff_uscita_ore >= 0 && $invert == 1 && ($consenti_straordinario == DB_BOOL_FALSE || $consenti_straordinario_reparto == DB_BOOL_FALSE)) {
                //Se sono oltre il range definito nelle impostazioni ma gli straordinario NON sono consentiti salvo l'ora fine del profilo
                $ora_uscita = $orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine'];
            }

            //Calcolo straordinario totale (sia in entrata che in uscita)
            $ore_straordinari = 0;

            $presenza['presenze_data_inizio'] = date('Y-m-d', strtotime($data['post']['presenze_data_inizio']));
            //$ora_inizio_profilo = new DateTime("{$data['post']['presenze_data_inizio']} {$orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio']}");
            $start = str_ireplace(' 00:00:00', '', $data['post']['presenze_data_inizio']);
            $ora_inizio_profilo = new DateTime("{$start} {$orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_inizio']}");
            //$inizio = new Datetime("{$data['post']['presenze_data_inizio']} {$data['post']['presenze_ora_inizio']}");
            $inizio = new Datetime("{$start} {$ora_entrata}");
            $diff_inizio = $ora_inizio_profilo->diff($inizio);
            if ($diff_inizio->invert == 1) {
                $ore_straordinari += ($diff_inizio->h + ($diff_inizio->i / 60));
            }

            //$ora_fine_profilo = new DateTime("{$data['post']['presenze_data_fine']} {$orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine']}");
            $end = str_ireplace(' 00:00:00', '', $data['post']['presenze_data_fine']);
            $ora_fine_profilo = new DateTime("{$end} {$orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_ora_fine']}");
            //$uscita = new Datetime("{$data['post']['presenze_data_fine']} {$data['post']['presenze_ora_fine']}");
            $uscita = new Datetime("{$end} {$ora_uscita}");
            $diff_fine = $ora_fine_profilo->diff($uscita);   
            if ($diff_fine->invert == 0) {
                $ore_straordinari += ($diff_fine->h + ($diff_fine->i / 60));
            }

            //CALCOLO ORE TOTALI           
            //$inizio = new DateTime($data_ora_entrata);
            $inizio = new DateTime(dateFormat($data['post']['presenze_data_inizio'], 'Y-m-d').' '.$ora_entrata);
            //$fine = new DateTime($data_ora_uscita);
            $fine = new DateTime(dateFormat($data['post']['presenze_data_fine'], 'Y-m-d').' '.$ora_uscita);
            $diff_date = $fine->diff($inizio);
            $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);
            //tolgo la pausa che ho nel profilo
            /*$day_number = date('w', strtotime($data['post']['presenze_data_inizio']));
            $orari_di_lavoro = $this->apilib->searchFirst('orari_di_lavoro', ["orari_di_lavoro_dipendente = {$data['post']['presenze_dipendente']}", 'orari_di_lavoro_giorno_numero' => $day_number]);
            $pausa = $orari_di_lavoro['orari_di_lavoro_ore_pausa_value'] ?? 1;
            $hours -= $pausa;
            */
            $range_calcolo_orario = $range_calcolo/60;
            $ore_straordinari = floor($ore_straordinari/$range_calcolo_orario)*$range_calcolo_orario;
            //IMPOSTO I CAMPI CORRETTI
            $data['post']['presenze_ora_inizio'] = $ora_entrata;
            $data['post']['presenze_ora_fine'] = $ora_uscita;
            $data['post']['presenze_ore_totali'] = $hours; 
            $data['post']['presenze_straordinario'] = $ore_straordinari;
            
            
            
        } else {
            
            //sono in una giornata in cui NON avrei dovuto lavorare, quindi la accetto solo se c'è la trasferta ed imposto metto come ore lavorate quelle impostato nel reparto
            if($data['post']['presenze_reparto']){
                $trasferta = 1;
                $reparto_dettagli = $this->apilib->searchFirst('reparti', [
                    "reparti_id" => $data['post']['presenze_reparto']
                ]);
                if($reparto_dettagli['reparti_trasferta_italia'] == 1 || $reparto_dettagli['reparti_trasferta_estero'] == 1){
                    $inizio = new DateTime($data_ora_entrata);
                    $fine = new DateTime($data_ora_uscita);
                    $diff_date = $fine->diff($inizio);

                    $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);       
                    $ore_pausa = $reparto_dettagli['reparti_ore_pausa'];

                    $data['post']['presenze_ore_totali'] = $hours;
                    if(($hours - $ore_pausa) > $reparto_dettagli['reparti_ore_base']) {
                        //Se ho sforato le ore ma non posso fare straordinario e gli straordinari non sono impostati per l'utente blocco inserimento presenza
                        $consenti_straordinario = $dipendente['dipendenti_consenti_straordinari'] ?? DB_BOOL_FALSE;
                        $consenti_straordinario_reparto = $reparto_dettagli['reparti_straordinari'] ?? DB_BOOL_FALSE;
                        if($consenti_straordinario == DB_BOOL_FALSE || $consenti_straordinario_reparto == DB_BOOL_FALSE ) {
                            throw new ApiException("Gli straordinari non sono autorizzati, per assistenza contattare l'amministratore.");
                            exit;
                        }
                        $data['post']['presenze_ore_totali'] = $hours+$data['post']['presenze_straordinario']; 
                        $data['post']['presenze_straordinario'] = ($hours - $ore_pausa) - $reparto_dettagli['reparti_ore_base'];
                    } else {
                        $data['post']['presenze_ore_totali'] = $hours; 
                        $data['post']['presenze_straordinario'] = 0;
                    }
                } else {
                    /*throw new ApiException('Impossibile inserire la presenza, non hai un orario configurato per la giornata selezionata');
                    exit;*/
                    $note_vecchie = ($data['post']['presenze_note_anomalie'])?"{$data['post']['presenze_note_anomalie']}\n\r":'';
                    $data['post']['presenze_note_anomalie'] = "{$note_vecchie}(".date('d/m/Y H:i').") TIMBRATURA SENZA ORARIO PER GIORNATA CORRENTE";
                }
            }
            else {
                /*throw new ApiException('Impossibile inserire la presenza, non hai un orario configurato per la giornata selezionata');
                exit;*/
                $note_vecchie = ($data['post']['presenze_note_anomalie'])?"{$data['post']['presenze_note_anomalie']}\n\r":'';
                $data['post']['presenze_note_anomalie'] = "{$note_vecchie}(".date('d/m/Y H:i').") TIMBRATURA SENZA ORARIO PER GIORNATA CORRENTE";
            }
            if (!empty($data['post']['presenze_pausa'])) {
                $pausa = $this->apilib->view('presenze_pausa',$data['post']['presenze_pausa'])['presenze_pausa_value'];
            } else {
                $pausa = 0;
            }
            $data['post']['presenze_straordinario'] = $hours - $pausa;
        }
    } else {
        
        //Non ho proprio orari configurati, considero 8 ore con 1 fissa di pausa e se superate il resto va in straordinario
        /* COMMENTATO PER CARICARE SU CONF COOP (tanto fa riferimento ai reparti... @alesandro)
        $inizio = new DateTime($data_ora_entrata);
        $fine = new DateTime($data_ora_uscita);
        $diff_date = $fine->diff($inizio);
        $reparto_dettagli = $this->apilib->searchFirst('reparti', [
            "reparti_id" => $data['post']['presenze_reparto']
        ]);
        $consenti_straordinario_reparto = $reparto_dettagli['reparti_straordinari'] ?? DB_BOOL_FALSE;
        $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);       
        if ($data['post']['presenze_pausa']) {
            	$ore_pausa = $this->apilib->view('presenze_pausa', $data['post']['presenze_pausa'])['presenze_pausa_value'];
        } else {
            $ore_pausa =  0;
        }
        

        $data['post']['presenze_ore_totali'] = $hours;
        if(($hours - $ore_pausa) > 8) {
            //Se ho sforato le 8 ore ma non posso fare straordinario blocco inserimento presenza
            $consenti_straordinario = $dipendente['dipendenti_consenti_straordinari'] ?? DB_BOOL_FALSE;
            if($consenti_straordinario == DB_BOOL_FALSE || $consenti_straordinario_reparto == DB_BOOL_FALSE) {
                throw new ApiException("Gli straordinari non sono autorizzati, per assistenza contattare l'amministratore.");
                exit;
            }
            $data['post']['presenze_straordinario'] = ($hours - $ore_pausa) - 8;
        } else {
            $data['post']['presenze_straordinario'] = 0;
        }*/
        $data['post']['presenze_straordinario'] = $hours;
        
    }
    //verifico se è una trasferta, in quel caso modifico i calcoli:
    if($data['post']['presenze_reparto'] && $trasferta == 0) {
        $reparto_dettagli = $this->apilib->searchFirst('reparti', [
            "reparti_id" => $data['post']['presenze_reparto']
        ]);
        if($reparto_dettagli['reparti_trasferta_italia'] == 1 || $reparto_dettagli['reparti_trasferta_estero'] == 1){
            $inizio = new DateTime($data_ora_entrata);
            $fine = new DateTime($data_ora_uscita);
            $diff_date = $fine->diff($inizio);

            $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);       
            $ore_pausa = $reparto_dettagli['reparti_ore_pausa'];
            if(($hours - $ore_pausa) > $reparto_dettagli['reparti_ore_base']) {
                //Se ho sforato le ore ma non posso fare straordinario e gli straordinari non sono impostati per l'utente blocco inserimento presenza
                $consenti_straordinario = $dipendente['dipendenti_consenti_straordinari'] ?? DB_BOOL_FALSE;
                $consenti_straordinario_reparto = $reparto_dettagli['reparti_straordinari'] ?? DB_BOOL_FALSE;
                if($consenti_straordinario == DB_BOOL_FALSE || $consenti_straordinario_reparto == DB_BOOL_FALSE ) {
                    throw new ApiException("Gli straordinari non sono autorizzati, per assistenza contattare l'amministratore.");
                    exit;
                }
                $data['post']['presenze_straordinario'] = ($data['post']['presenze_ore_totali'] - $ore_pausa) - $reparto_dettagli['reparti_ore_base'];
            } else {
                $data['post']['presenze_straordinario'] = 0;
            }
        }
    }



    //SE DEVO IGNORARE ORARI E HO IMPOSTATO ORARIO STANDARD, CALCOLO LO STRAORDINARIO DIVERSAMENTE
    //TUTTO QUELLO CHE VA OLTRE LE ORE STANDARD E STRAORDINARIO
    $ignora_orari_lavoro = $dipendente['dipendenti_ignora_orari_lavoro'];

    if($ignora_orari_lavoro == DB_BOOL_TRUE && !empty($dipendente['dipendenti_ore_standard']) && $dipendente['dipendenti_ore_standard'] > 0) {
        $ore_standard = $dipendente['dipendenti_ore_standard'];
        if($hours > $ore_standard) {
            if($consenti_straordinario == DB_BOOL_FALSE) {
                /* 09/02/2023 Andrea - Non blocco più l'inserimento perchè il timbraUscita tornava errore entrando qui.
                * Salvo come ora di fine presenza  l'ora di entrata + n° di ore standard. Quindi devo sovrascrivere anche le ore totali con le ore standard e straord. a zero
                */
                if(!empty($ore_standard) && $ore_standard >= 0) {
                    //Calcolo ora di fine che dovrei avere in base alle ore standard
                    $entrata = str_ireplace(' 00:00:00', '', $data['post']['presenze_data_inizio']).' '.$data['post']['presenze_ora_inizio'];
                    $inizio = new DateTime($entrata);
                    $temp_inizio = new DateTime($entrata);
                    $ora_uscita = $temp_inizio->modify("+{$ore_standard} hours");
                    //Imposto ora di fine, ora fine calendario, ore totali e straordinario
                    $data['post']['presenze_ora_fine'] = $ora_uscita->format('H:i');
                    $data['post']['presenze_data_fine_calendar'] = str_ireplace(' 00:00:00', '', $data['post']['presenze_data_fine']).' '.$data['post']['presenze_ora_fine'];
                    $hours = $ore_standard;
                    $data['post']['presenze_straordinario'] = 0;
                }
            } else {
                //Posso fare straordinari, li calcolo
                $data['post']['presenze_straordinario'] = $hours - $ore_standard;   
            }
        } else {
            //Non ho fatto straordinari (sono entro le ore massime di lavoro)
            $data['post']['presenze_straordinario'] = 0;
        }      
    }

    
    /*dump($hours);
    exit;*/

    $ore_notturne = 0;
    if(!empty($suggerimentoTurno) && $timbratura_diretta == DB_BOOL_FALSE && $ignora_orari_lavoro == DB_BOOL_FALSE) {
        // verifico se ho impostato o l'orario notturno sul turno oppure il template del turno ha il notturno
        if (!empty($orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_notturno_inizio']) && !empty($orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_notturno_fine'])){

            $ore_notturne = $this->timbrature->calculateNightHours($data['post']['presenze_ora_inizio'], $data['post']['presenze_ora_fine'], $orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_notturno_inizio'], $orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_notturno_fine']);
        }
    }
    $data['post']['presenze_notturno'] = $ore_notturne;

    //Storicizzo il costo e valore per la giornata e calcolo costo e valore in base alle ore lavorate
    $costo_orario = $dipendente['dipendenti_costo_orario'];
    $valore_orario = $dipendente['dipendenti_valore_orario'];
    $costo_totale = $costo_orario * $hours;
    $valore_totale = $valore_orario * $hours;

    $data['post']['presenze_costo_orario'] = $costo_orario;
    $data['post']['presenze_valore_orario'] = $valore_orario;
    $data['post']['presenze_costo_giornaliero'] = $costo_totale;
    $data['post']['presenze_valore_giornaliero'] = $valore_totale;
}



//Creazione campi per controlli
if (!empty($data['post']['presenze_data_inizio']) && !empty($data['post']['presenze_data_fine'])
    && !empty($data['post']['presenze_ora_inizio']) && !empty($data['post']['presenze_ora_fine'])) {
    $data_entrata = dateFormat($data['post']['presenze_data_inizio'], 'Y-m-d');
    $data_ora_entrata = $data_entrata.' '.$data['post']['presenze_ora_inizio'];
    $data_uscita = dateFormat($data['post']['presenze_data_fine'], 'Y-m-d');
    $data_ora_uscita = $data_uscita.' '.$data['post']['presenze_ora_fine'];
    //Imposto campi calendario  
    if (empty($data['post']['presenze_data_inizio_calendar'])) {
        $data['post']['presenze_data_inizio_calendar'] = $data_ora_entrata.':00';    
    }
    if (empty($data['post']['presenze_data_fine_calendar'])) {
        $data['post']['presenze_data_fine_calendar'] = $data_ora_uscita.':00';
    }
}


//SE HO UN BLOCCO SULLE PRESENZE ATTIVO NON DEVO FAR FARE L'INSERIMENTO se non ci sono dipendenti inseriti o sono tra quelli inseriti
$sql_blocco = "
SELECT * FROM blocchi_hr
WHERE blocchi_hr_blocca_inserimento = '1' AND blocchi_hr_dal <= '{$data['post']['presenze_data_inizio']}' and blocchi_hr_al >= '{$data['post']['presenze_data_inizio']}'
ORDER BY blocchi_hr_id ASC
";
$blocchi = $this->db->query($sql_blocco)->result_array();

if(!empty($blocchi)) {
    foreach ($blocchi as $blocco) {
        $dipendenti_da_bloccare = $this->apilib->search('rel_blocchi_hr_dipendenti', [
            'blocchi_hr_id' => $blocco['blocchi_hr_id'],
        ]);

        //Se vuoto o sono tra i dipendenti, blocco e torno errore
        if (empty($dipendenti_da_bloccare) || (array_search( $dipendente['dipendenti_id'], array_column($dipendenti_da_bloccare, 'dipendenti_id')) !== false)) {
            $data_bloccata = dateFormat($data_entrata, 'd/m/Y');
            throw new ApiException("L'inserimento delle presenze per la data {$data_bloccata} è bloccato.");
            exit;
        } else {
            //dump('Proseguo, ci sono dipendenti da bloccare ed io non sono tra quelli');
        }
    }
}