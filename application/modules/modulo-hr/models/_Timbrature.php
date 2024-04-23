<?php

class Timbrature extends CI_Model
{

    public $scope = 'CRM';
    public function scope($tipo)
    {
        $this->scope = $tipo;
    }
    public function timbraEntrata($dipendente_id, $ora_entrata = null, $data_entrata = null, $scope = null)
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

        //Orario salvato nel dipendente per la giornata odierna
        $today = date('w');
        if ($data_entrata === null) {
            $data_entrata = date('Y-m-d');
        }
        $dipendente = $this->apilib->view('dipendenti', $dipendente_id);
        
        $orario_lavoro = $this->apilib->searchFirst('orari_di_lavoro', ['orari_di_lavoro_dipendente' => $dipendente_id, 'orari_di_lavoro_giorno_numero' => $today]);
        /**
         * ! CONTROLLI SOLO SE HO UN ORARIO IMPOSTATO
         * */
        if (!empty($orario_lavoro)) {
            //Imposto l'orario corretto in base al controllo del range
            $ora_inizio = new Datetime($ora_entrata);
            $ora_inizio_profilo = new DateTime($orario_lavoro['orari_di_lavoro_ora_inizio']);
            $ora_fine_profilo = new DateTime($orario_lavoro['orari_di_lavoro_ora_fine']);

            $diff_orari_entrata = $ora_inizio->diff($ora_inizio_profilo);
            $diff_entrata_minuti = $diff_orari_entrata->i;
            $diff_entrata_ore = $diff_orari_entrata->h;
            $invert = $diff_orari_entrata->invert; // 1 se ora inizio > ora_inizio_profilo, 0 se ora inizio < ora_inizio_profilo

            //Inserisco orario definito nel profilo altrimenti lascio i dati come sono
            $impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
            $range_entrata = $impostazioni_modulo['impostazioni_hr_range_minuti_entrata'] ?? 30;
            $consenti_straordinario = $dipendente['dipendenti_consenti_straordinari'] ?? DB_BOOL_FALSE;

            //Differenza tra entrata e fine profilo
            $diff_orari_uscita = $ora_inizio->diff($ora_fine_profilo);
            $diff_uscita_minuti = $diff_orari_uscita->i;
            $diff_uscita_ore = $diff_orari_uscita->h;
            $invert_uscita = $diff_orari_uscita->invert; // 1 se ora inizio > $ora_fine_profilo, 0 se ora inizio < $ora_fine_profilo

            //Se timbro prima dell'inizio e sono entro il range di tollerenza salvo l'ora inizio del profilo
            if (($diff_entrata_minuti < $range_entrata && $diff_entrata_ore < 1 && $invert == 0)) {
                $ora_entrata = $orario_lavoro['orari_di_lavoro_ora_inizio'];
            } elseif ($diff_entrata_minuti > $range_entrata && $diff_entrata_ore < 1 && $invert == 0 && $consenti_straordinario == DB_BOOL_FALSE) {
                //Se sono oltre il range definito nelle impostazioni ma gli straordinario NON sono consentiti salvo l'ora inizio del profilo
                $ora_entrata = $orario_lavoro['orari_di_lavoro_ora_inizio'];
            } elseif ($invert_uscita == 1 && $consenti_straordinario == DB_BOOL_FALSE) {
                //Sto timbrando in ritardo e fuori dall'orario lavorativo (entrata > fine profilo) e straordinario disabilitato
                die(json_encode(['status' => 0, 'txt' => 'Straordinari non consentiti, stai timbrando entrata dopo la fine registrata nel profilo.']));
            }
        } else {
            //die(json_encode(['status' => 0, 'txt' => 'Nessun orario di lavoro registrato per la giornata odierna']));
            //NON DEVO FARE NULLA PERCHE DATA E ORA ENTRATA SARANNO QUELLI CHE HO INIVATO IO, SENZA CONTROLLI
        }

        //Se ho già una presenza aperta non devo fare timbrare
        $presenze_odierna = $this->apilib->searchFirst('presenze', ['presenze_dipendente' => $dipendente_id, 'presenze_data_inizio' => date('Y-m-d'), 'presenze_data_fine IS NULL or presenze_data_fine = ""']);
        if (!empty($presenze_odierna)) {
            die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare nuovamente l\'entrata senza chiudere la presenza precedente.']));
        }


        //Campi per il calendario
        $data_inizio_calendar = $data_entrata;
        $ora_inizio_calendar = $ora_entrata;
        $dal = (new DateTime($data_inizio_calendar))->format('Y-m-d');
        $inizio_calendar = $dal . ' ' . $ora_inizio_calendar;

        //Creo record
        try {
            //Se ho richiesta attiva che comprende giornata posso dover bloccare l'inserimento
            $richieste = $this->apilib->search('richieste', [
                'richieste_user_id' => $dipendente_id,
                'richieste_stato' => 2, //solo richieste approvate
            ]);
            if (!empty($richieste)) {
                foreach ($richieste as $richiesta) {
                    //se la richiesta comprende oggi
                    if (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= $data_entrata && dateFormat($richiesta['richieste_al'], 'Y-m-d') >= $data_entrata) {
                        if ($richiesta['richieste_tipologia'] == 1) { //PERMESSO
                            if ($ora_entrata >= $richiesta['richieste_ora_inizio'] && $ora_entrata <= $richiesta['richieste_ora_fine']) {
                                die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare avendo una richiesta di permesso in corso.']));
                            }
                        } elseif ($richiesta['richieste_tipologia'] == 2) { //FERIE
                            die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare avendo una richiesta di ferie in corso.']));
                        } elseif ($richiesta['richieste_tipologia'] == 3) { //MALATTIA
                            die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare avendo una richiesta di malattia in corso.']));
                        } elseif ($richiesta['richieste_tipologia'] == 4) { //LEGGE 104
                            die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare avendo una richiesta legge 104 in corso.']));
                        }
                    } elseif (dateFormat($richiesta['richieste_dal'], 'Y-m-d') <= $data_entrata && empty($richiesta['richieste_al']) && $richiesta['richieste_tipologia'] == 3) {
                        //se la richiesta è senza data fine ed è MALATTIA
                        die(json_encode(['status' => 0, 'txt' => 'Non puoi timbrare avendo una richiesta di malattia in corso.']));
                    }
                }
            }
            $entrata = $this->apilib->create('presenze', [
                'presenze_dipendente' => $dipendente_id,
                'presenze_data_inizio' => $data_entrata,
                'presenze_ora_inizio' => $ora_entrata,
                'presenze_data_inizio_calendar' => $inizio_calendar,
                'presenze_scope_create' => $scope,
            ]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            die(json_encode(['status' => 0, 'txt' => 'Errore durante il salvataggio dell\'entrata']));
        }
        return $entrata;
    }

    /**
     * ! Timbra uscita presenza
     * 
     * * Se non ha in input data ed ora li imposta a quelli attuali
     * * Se il dipendente ha un orario lavorativo per la presenza attuale calcola lo straordinario in entrata ed uscita
     * * Se il dipendente non ha un orario lavorativo per la presenza attuale e se le ore lavorate lavorato > 8 ore tutto quello che avanza va in straordinario   
     * * Storicizza costo e valore orario del dipendente per la giornata in questione
     * * Calcola il costo e valore totale della presenza per la giornata in questione
     */
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
            $today = date('w');
        } else {
            $today = date('w', strtotime($data_uscita));
        }

        //Cerca la presenza di oggi senza data e ora uscita, se non la trovo mostro errore altrimenti salvo data e ora uscita + data calendario
        $dipendente = $this->apilib->view('dipendenti', $dipendente_id);
        if (!$dipendente) {
            die(json_encode(['status' => 0, 'txt' => "Dipendente '$dipendente_id' non trovato!"]));
        }

        $orario_lavoro = $this->apilib->searchFirst('orari_di_lavoro', ['orari_di_lavoro_dipendente' => $dipendente_id, 'orari_di_lavoro_giorno_numero' => $today]);

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
        $data_entrata = substr($presenza['presenze_data_inizio'], 0, 10);

        /**
         * ! CONTROLLI SOLO SE HO UN ORARIO IMPOSTATO
         * */
        if(!empty($orario_lavoro)) {
            //Imposto l'orario corretto in base al controllo del range
            $ora_fine = new Datetime($ora_uscita);
            $ora_fine_profilo = new DateTime($orario_lavoro['orari_di_lavoro_ora_fine']);

            $diff_orari_uscita = $ora_fine->diff($ora_fine_profilo);
            $diff_uscita_minuti = $diff_orari_uscita->i;
            $diff_uscita_ore = $diff_orari_uscita->h;
            $invert = $diff_orari_uscita->invert; // 1 se ora fine > ora_fine_profilo, 0 se ora fine < ora_fine_profilo

            //Inserisco orario definito nel profilo altrimenti lascio i dati come sono
            $impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
            $range_uscita = $impostazioni_modulo['impostazioni_hr_range_minuti_uscita'] ?? 30;
            $consenti_straordinario = $dipendente['dipendenti_consenti_straordinari'];
            //Se timbro dopo la fine e sono entro il range di tollerenza salvo l'ora fine del profilo
            //Aggiunto presenza inizio < ora fine profilo per fare in modo che se ho timbrato entrata dopo la fine del profilo e adesso sto uscendo di nuovo deve mettere l'ora che invio io e non quella del profilo
            if ($diff_uscita_minuti < $range_uscita && $diff_uscita_ore < 1 && $invert == 1 && (new DateTime($presenza['presenze_ora_inizio']) < $ora_fine_profilo)) { //
                $ora_uscita = $orario_lavoro['orari_di_lavoro_ora_fine'];
            } elseif ($diff_uscita_minuti > $range_uscita && $invert == 1 && $consenti_straordinario == DB_BOOL_FALSE) {
                //Se sono oltre il range definito nelle impostazioni ma gli straordinario NON sono consentiti salvo l'ora fine del profilo
                $ora_uscita = $orario_lavoro['orari_di_lavoro_ora_fine'];
            }
        }


        //Imposto campi per il calendario
        $data_fine_calendar = $data_uscita;
        $ora_fine_calendar = $ora_uscita;
        $al = (new DateTime($data_fine_calendar))->format('Y-m-d');
        $fine_calendar = $al . ' ' . $ora_fine_calendar;

        try {
            //Permesso che comprende giornata in corso, se timbro dopo inizio permesso devo salvare come ora fine presenza quella di inizio permesso
            $richieste_permesso = $this->apilib->search('richieste', [
                'richieste_user_id' => $dipendente_id,
                'richieste_stato' => 2, //solo richieste approvate
                'richieste_tipologia' => 1,
                //TODO: aggiungere filtro per prendere solo richieste nel periodo della presenza...
            ]);
            if (!empty($richieste_permesso)) {
                foreach ($richieste_permesso as $permesso) {
                    if (dateFormat($permesso['richieste_dal'], 'Y-m-d') <= $data_entrata && dateFormat($permesso['richieste_al'], 'Y-m-d') >= $data_entrata) {
                        if ($ora_uscita >= $permesso['richieste_ora_inizio'] && $ora_uscita <= $permesso['richieste_ora_fine']) {
                            $ora_uscita = $permesso['richieste_ora_inizio'];
                        }
                    }
                }
            }
            //TODO: spostare in un pp (INIZIATO LAVORO MA RAGIONARE)...
            //Se sto timbrando un'uscita, calcolo in automatico le ore straordinario
            // if ($consenti_straordinario == DB_BOOL_TRUE) { //Per prima cosa devono essere consentiti gli straordinari
            //     $ore_straordinari = 0;
            //     $inizio = new Datetime($presenza['presenze_ora_inizio']);
            //     $ora_inizio_profilo = new DateTime($orario_lavoro['orari_di_lavoro_ora_inizio']);
            //     $diff_inizio = $ora_inizio_profilo->diff($inizio);
            //     if ($diff_inizio->invert == 1) {
            //         $ore_straordinari += ($diff_inizio->h + ($diff_inizio->i / 60)); 
            //     }

            //     $uscita = new Datetime($ora_uscita);
            //     $diff_fine = $ora_fine_profilo->diff($uscita);
            //     if ($diff_fine->invert == 0) {
            //         $ore_straordinari += ($diff_fine->h + ($diff_fine->i / 60));
            //     }
            // } else {
            //     $ore_straordinari = 0;
            // }

            /**
             * ! CALCOLO LE ORE TOTALI DELLA PRESENZA
             */
            $data_inizio = dateFormat($presenza['presenze_data_inizio'], 'Y-m-d');
            $data_ora_inizio = $data_inizio.' '.$presenza['presenze_ora_inizio'];
            $data_ora_fine = $data_uscita.' '.$ora_uscita;

            $inizio = new DateTime($data_ora_inizio);
            $fine = new DateTime($data_ora_fine);
            $diff_date = $fine->diff($inizio);
            
            $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);


            /**
             * ! CALCOLO STRAORDINARIO TOTALE (SIA PER L'ENTRATA CHE PER L'USCITA)
             */
            $ore_straordinari = 0;
            $consenti_straordinario = $dipendente['dipendenti_consenti_straordinari'];

            $presenza['presenze_data_inizio'] = date('Y-m-d', strtotime($presenza['presenze_data_inizio']));
            $inizio = new Datetime("{$presenza['presenze_data_inizio']} {$presenza['presenze_ora_inizio']}");
            $uscita = new DateTime("{$data_uscita} {$ora_uscita}");
            //Per prima cosa devono essere consentiti gli straordinari e devo avere orario lavorativo
            if ($consenti_straordinario == DB_BOOL_TRUE && !empty($orario_lavoro)) { 
                //Confronto entrata con inizio profilo
                $ora_inizio_profilo = new DateTime("{$presenza['presenze_data_inizio']} {$orario_lavoro['orari_di_lavoro_ora_inizio']}");
                $diff_inizio = $ora_inizio_profilo->diff($inizio);
                if ($diff_inizio->invert == 1) {
                    $ore_straordinari += ($diff_inizio->h + ($diff_inizio->i / 60));
                }
                //Confronto uscita con fine profilo
                $ora_fine_profilo = new DateTime("{$data_uscita} {$orario_lavoro['orari_di_lavoro_ora_fine']}");
                $diff_fine = $ora_fine_profilo->diff($uscita);
                if ($diff_fine->invert == 0) {
                    $ore_straordinari += ($diff_fine->h + ($diff_fine->i / 60));
                }
            } else if($consenti_straordinario == DB_BOOL_TRUE && empty($orario_lavoro)) {
                //Posso fare straordinario ma non ho orario salvato, se ho lavorato più di 8 ore tutto quello che avanza va in straordinario      
                $ore_pausa = 1;
                if(($hours - $ore_pausa) > 8) {
                    $ore_straordinari = ($hours - $ore_pausa) - 8;
                    $hours = 8 + $ore_straordinari;
                } else {
                    $ore_straordinari = 0;
                }
            } else {
                //Non posso fare straordinario e non ho orario salvato
                $ore_straordinari = 0;
            }

            /**
             * ! CALCOLO COSTO E VALORE GIORNALIERO
             */
            $costo_orario = $dipendente['dipendenti_costo_orario'];
            $valore_orario = $dipendente['dipendenti_valore_orario'];
            $costo_totale = $costo_orario * $hours;
            $valore_totale = $valore_orario * $hours;

        
            /**
            * ! AGGIONRNO LA PRESENZA CON TUTTI I DATI
            */
            $uscita = $this->apilib->edit('presenze', $presenza['presenze_id'], [
                'presenze_data_fine' => $data_uscita,
                'presenze_ora_fine' => $ora_uscita,
                'presenze_data_fine_calendar' => $fine_calendar,
                'presenze_straordinario' => $ore_straordinari,
                'presenze_ore_totali' => $hours,
                //Portare calcoli dei costi e valori su PP
                'presenze_costo_orario' => $costo_orario,
                'presenze_valore_orario' => $valore_orario,
                'presenze_costo_giornaliero' => $costo_totale,
                'presenze_valore_giornaliero' => $valore_totale,
                'presenze_scope_edit' => $scope,
            ]);
        } catch (Exception $e) {
            log_message('error', $e->getMessage());
            die(json_encode(['status' => 0, 'txt' => 'Errore durante il salvataggio dell\'uscita']));
        }
        return $uscita;

    }

    public function getFatturatoAnnoCustomer($customer_id)
    {
        $fatturato = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo IN (1,11,12) THEN documenti_contabilita_totale ELSE -documenti_contabilita_totale END) as fatturato,SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND documenti_contabilita_customer_id = '$customer_id' AND EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = EXTRACT(YEAR FROM CURRENT_TIMESTAMP)")->row_array();
        return $fatturato;
    }

    public function getInsolvenzeCustomer($customer_id)
    {
        $insolvenze = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo IN (1,11,12) THEN documenti_contabilita_totale ELSE -documenti_contabilita_totale END) as fatturato,SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND documenti_contabilita_customer_id = '$customer_id' AND documenti_contabilita_stato_pagamenti = '1'")->row_array();
        return $insolvenze;
    }

    public function isStraordinario($presenza, $data_ora = null)
    {
        //Considero straordinarie tutte le presenza la cui ora inizio è fuori da un orario di lavoro....
        $weekday = date('w', strtotime($presenza['presenze_data_inizio']));
        $orario_lavoro = $this->apilib->searchFirst('orari_di_lavoro', [
            'orari_di_lavoro_dipendente' => $presenza['presenze_dipendente'],
            'orari_di_lavoro_giorno_numero' => $weekday,
        ]);
        $consenti_straordinario = $presenza['dipendenti_consenti_straordinari'];

        if ($data_ora == null) {
            $data_ora = $presenza['presenze_data_inizio'];
        }

        $inizio_profilo = new Datetime($orario_lavoro['orari_di_lavoro_ora_inizio']);
        $fine_profilo = new Datetime($orario_lavoro['orari_di_lavoro_ora_fine']);
        $inizio_presenza = new Datetime($presenza['presenze_ora_inizio']);
        //Ho orario per oggi, posso fare straordinari e entrata presenza <= entrata profilo o > uscita profilo
        if ($orario_lavoro && $consenti_straordinario && ($inizio_presenza <= $inizio_profilo || $inizio_presenza > $fine_profilo)) {
            //se data ora è fuori dal mio orario lavorativo torno true perchè è straordinario
            debug($data_ora);
            debug($orario_lavoro);
            debug('Straordinario in corso, presenza da non chiudere');
            return true;
        } else {
            return false;
        }
    }

    /************************** CONTEGGI GLOBALI ***********************************/
}