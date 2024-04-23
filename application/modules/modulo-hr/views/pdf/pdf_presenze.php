<?php
    ini_set("pcre.backtrack_limit", "50000000");
    $this->load->model('modulo-hr/timbrature');
    $giorni_map = ['D', 'L', 'M', 'M', 'G', 'V', 'S'];

    $giorni_mese = date('t');

    $dati = [];

    // Dati filtri impostati
    $filters = $this->session->userdata(SESS_WHERE_DATA);

    $impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
    
    $arrotondamento = 2;
    if(isset($impostazioni_modulo['impostazioni_hr_numeri'])) {
        if($impostazioni_modulo['impostazioni_hr_numeri'] == 0) {
            $arrotondamento = 0;
        }
    }

    $mesi = [
        'Gennaio',
        'Febbraio',
        'Marzo',
        'Aprile',
        'Maggio',
        'Giugno',
        'Luglio',
        'Agosto',
        'Settembre',
        'Ottobre',
        'Novembre',
        'Dicembre'
    ];
    

    $filtro_testo = '';
    $filtro_dipendente_id = null;
    $filtro_azienda_id = null;
    $filtro_agenzia_somministrazione_id = null;

    if (!empty($filters['filter-presenze'])) {
        $filtri = $filters['filter-presenze'];

        $presenze_dipendente_field_id = $this->datab->get_field_by_name('presenze_dipendente')['fields_id'];
        $presenze_data_field_id = $this->datab->get_field_by_name('presenze_data_inizio')['fields_id'];
        $dipendenti_azienda_field_id = $this->datab->get_field_by_name('dipendenti_azienda')['fields_id'];
        $dipendenti_agenzia_somministrazione_field_id = $this->datab->get_field_by_name('dipendenti_agenzia_somministrazione')['fields_id'];

        //Filtro azienda
        if (!empty($filtri[$dipendenti_azienda_field_id]['value']) && $filtri[$dipendenti_azienda_field_id]['value'] !== '-1') {
            $filtro_azienda_id = $filtri[$dipendenti_azienda_field_id]['value'];

            $azienda = $this->apilib->view('aziende', $filtri[$dipendenti_azienda_field_id]['value']);
            
            $filtro_testo .= "Azienda: <b>{$azienda['aziende_ragione_sociale']}</b>,  ";

            $this->db->join('dipendenti', 'presenze_dipendente = dipendenti_id', "left");
            $this->db->where('dipendenti_azienda', $filtri[$dipendenti_azienda_field_id]['value']);
        }

        //Filtro agenzia soomministrazione
        if (!empty($filtri[$dipendenti_agenzia_somministrazione_field_id]['value']) && $filtri[$dipendenti_agenzia_somministrazione_field_id]['value'] !== '-1') {
            $filtro_agenzia_somministrazione_id = $filtri[$dipendenti_agenzia_somministrazione_field_id]['value'];

            $agenzia_somministrazione = $this->apilib->view('dipendenti_agenzia_somministrazione', $filtri[$dipendenti_agenzia_somministrazione_field_id]['value']);
            
            $filtro_testo .= "Agenzia: <b>{$agenzia_somministrazione['dipendenti_agenzia_somministrazione_value']}</b>,  ";

            $this->db->join('dipendenti', 'presenze_dipendente = dipendenti_id', "left");
            $this->db->where('dipendenti_agenzia_somministrazione', $filtri[$dipendenti_agenzia_somministrazione_field_id]['value']);
        }

        //Filtro dipendente
        if (!empty($filtri[$presenze_dipendente_field_id]['value']) && $filtri[$presenze_dipendente_field_id]['value'] !== '-1') {
            $filtro_dipendente_id = $filtri[$presenze_dipendente_field_id]['value'];

            $dipendente = $this->db->where('dipendenti_id', $filtri[$presenze_dipendente_field_id]['value'])->get('dipendenti')->row_array();

            $filtro_testo .= "Dipendente: <b>{$dipendente['dipendenti_nome']} {$dipendente['dipendenti_cognome']}</b>, ";

            $this->db->where('presenze_dipendente', $filtri[$presenze_dipendente_field_id]['value']);
        }
        
        //Filtro data
        if (!empty($filtri[$presenze_data_field_id]['value'])) {
            $presenze_data_ex = explode(' - ', $filtri[$presenze_data_field_id]['value']);
            $presenze_data = (DateTime::createFromFormat('d/m/Y', $presenze_data_ex[0]))->format('Y-m');
            $presenze_data_ita = (DateTime::createFromFormat('d/m/Y', $presenze_data_ex[0]))->format('m/Y');

            $this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = '{$presenze_data}'", null, false);
            $anno = (DateTime::createFromFormat('d/m/Y', $presenze_data_ex[0]))->format('Y');
            $mese = (DateTime::createFromFormat('d/m/Y', $presenze_data_ex[0]))->format('m');

            $data_tmp = DateTime::createFromFormat('!m', $mese)->format('Y-m-d');
            //$nome_mese = strftime('%B', strtotime($data_tmp));
            $nome_mese = $mesi[$mese - 1];

            $filtro_testo .= "<b>{$nome_mese} {$anno}</b>";
            
            $giorni_mese = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);
        } else {
            //Filtro data non impostato
            $this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')", null, false);
            $anno = date('Y');
            $mese = date('m');
            
            $data_tmp = DateTime::createFromFormat('!m', $mese)->format('Y-m-d');
            //$nome_mese = strftime('%B', strtotime($data_tmp));
            $nome_mese = $mesi[$mese - 1];

            $filtro_testo .= "<b>{$nome_mese} {$anno}</b>";
        }

    } else {
        $this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')", null, false);
        $anno = date('Y');
        $mese = date('m');

        $data_tmp = DateTime::createFromFormat('!m', $mese)->format('Y-m-d');
        //$nome_mese = strftime('%B', strtotime($data_tmp));
        $nome_mese = $mesi[$mese - 1];

        $filtro_testo .= "<b>{$nome_mese} {$anno}</b>";
    }

    // Solo presenza NON legate a richieste, il conteggio delle ore richieste viene visualizzato sotto
    $this->db->where("presenze_richiesta IS NULL", null, false);
    
    $_presenze = $this->db->get('presenze')->result_array();

    $presenze = [];
    foreach ($_presenze as $_presenza) {
        $presenze[$_presenza['presenze_dipendente']][] = $_presenza;
    }

    $where_dipendenti = [];
    $where_dipendenti['dipendenti_attivo'] = DB_BOOL_TRUE;

    if ($filtro_dipendente_id) {
        $where_dipendenti['dipendenti_id'] = $filtro_dipendente_id;
    }
    
    if ($filtro_azienda_id) {
        $where_dipendenti['dipendenti_azienda'] = $filtro_azienda_id;
    }

    if ($filtro_agenzia_somministrazione_id) {
        $where_dipendenti['dipendenti_agenzia_somministrazione'] = $filtro_agenzia_somministrazione_id;
    }

    //$dipendenti = $this->apilib->search('dipendenti', $where_dipendenti);
    $dipendenti = $this->apilib->search('dipendenti', $where_dipendenti, null, 0, 'dipendenti_cognome', 'ASC');

    foreach ($dipendenti as $dipendente) {
        $ignora_pausa = $dipendente['dipendenti_ignora_pausa'] ?? DB_BOOL_FALSE;

        $ordinarie = $straordinarie = 0;

        $dati_dip = [];

        $dati_dip['dipendente'] = $dipendente;
        
        $conteggi = [];
        for ($giorno = 1; $giorno <= $giorni_mese; $giorno++) {
            if (empty($dati_dip[$giorno])) {
                $dati_dip[$giorno]['ordinarie'] = 0;
                $dati_dip[$giorno]['notturno'] = 0;
                $dati_dip[$giorno]['straordinarie'] = 0;
                $dati_dip[$giorno]['reperibile'] = '';
                $dati_dip[$giorno]['trasferte'] = '';
                $dati_dip[$giorno]['banca_ore'] = 0;
                $dati_dip[$giorno]['permesso'] = 0;
                $dati_dip[$giorno]['ferie'] = 0;
                $dati_dip[$giorno]['malattia'] = 0;
                $dati_dip[$giorno]['tipo_assenza'] = '';
                $dati_dip[$giorno]['mensa'] = '';
                $dati_dip[$giorno]['trasferta'] = '';
                $dati_dip[$giorno]['smartworking'] = '';
                $dati_dip[$giorno]['anomalia'] = '';
            }

            if (empty($conteggi)) {
                $conteggi['giorni_ordinari'] = 0;
                $conteggi['giorni_straord'] = 0;
                $conteggi['giorni_notturni'] = 0;
                $conteggi['ore_ordinarie'] = 0;
                $conteggi['ore_notturno'] = 0;
                $conteggi['ore_straord'] = 0;

                $conteggi['giorni_reperibili'] = 0;
                $conteggi['trasferte'] = 0;
                $conteggi['banca'] = 0;
                $conteggi['permesso'] = 0;
                $conteggi['ferie'] = 0;
                $conteggi['malattia'] = 0;
            }

            $day = $giorno < 10 ? '0'.$giorno : $giorno;
            /*$month = date('m');
            $year = date('Y');
            $current_date = $year.'-'.$month.'-'.$day;*/
            $current_date = $anno.'-'.$mese.'-'.$day;
            
            //RICHIESTE (permesso, ferie, malattia, legge 104)
            $assenza = $this->db
            ->where("DATE_FORMAT(richieste_dal, '%Y-%m-%d') <= '{$current_date}'", null, false)
            ->where("DATE_FORMAT(richieste_al, '%Y-%m-%d') >= '{$current_date}'", null, false)
            ->where('richieste_user_id', $dipendente['dipendenti_id'])
            ->where('richieste_stato', '2')
            ->get('richieste')->row_array();

            if(!empty($assenza)) {
                $data_ora_inizio = str_ireplace(' 00:00:00', '', $assenza['richieste_dal']).' '.$assenza['richieste_ora_inizio'];
                $data_ora_fine = str_ireplace(' 00:00:00', '', $assenza['richieste_al']).' '.$assenza['richieste_ora_fine'];
                $inizio = new DateTime($data_ora_inizio);
                $fine = new DateTime($data_ora_fine);
                $diff_date = $fine->diff($inizio);
                $hours = 0;

                // Cerco orario lavorativo per il giorno della richiesta per poter calcolare le ore richieste in tutti i casi tranne per il permesso
                $weekday = date('N', strtotime($current_date));
                $this->db->select('*');
                $this->db->from('turni_di_lavoro');
                $this->db->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', "left");
                $this->db->where("turni_di_lavoro_data_inizio <= '{$current_date}'", null, false);
                $this->db->where("(turni_di_lavoro_data_fine >= '{$current_date}' OR turni_di_lavoro_data_fine IS NULL)", null, false);
                $this->db->where('turni_di_lavoro_dipendente', $dipendente['dipendenti_id']);
                $this->db->where('turni_di_lavoro_giorno', $weekday);
                $orari_lavoro = $this->db->get()->result_array();

                if(!empty($orari_lavoro) && $assenza['richieste_tipologia'] != 1 ) {
                    foreach ($orari_lavoro as $orario) {
                        $inizio_richiesta = DateTime::createFromFormat('H:i', $orario['turni_di_lavoro_ora_inizio']);
                        $fine_richiesta = DateTime::createFromFormat('H:i', $orario['turni_di_lavoro_ora_fine']);
                        $diff_richiesta = $inizio_richiesta->diff($fine_richiesta);
                        //echo "La differenza tra l'orario di inizio e fine lavoro è: " . $diff_richiesta->format('%H ore %i minuti');
                        //$hours += round(($diff_richiesta->s / 3600) + ($diff_richiesta->i / 60) + $diff_richiesta->h + ($diff_richiesta->days * 24), 2);
                        $hours += round(($diff_richiesta->s / 3600) + ($diff_richiesta->i / 60) + $diff_richiesta->h + ($diff_richiesta->days * 24), 2) - $orario['orari_di_lavoro_ore_pausa_value'];
                    }
                }


                if($assenza['richieste_tipologia'] == 1) { // PERMESSO
                    $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);
                    //vedo la sottotipologia
                    if(empty($assenza['richieste_sottotipologia'])){
                        $tipologia_assenza = 'P';
                    } else {
                        $tipologia = $this->db->where('richieste_sottotipologia_id', $assenza['richieste_sottotipologia'])->get('richieste_sottotipologia')->row_array();
                        if(isset($tipologia['richieste_sottotipologia_codice'])){
                            $tipologia_assenza = $tipologia['richieste_sottotipologia_codice'];
                        } else {
                            $tipologia_assenza = 'P';
                        }
                    }
                } elseif($assenza['richieste_tipologia'] == 2) { // FERIE
                        $tipologia = $this->db->where('richieste_sottotipologia_id', $assenza['richieste_sottotipologia'])->get('richieste_sottotipologia')->row_array();
                        if(isset($tipologia['richieste_sottotipologia_codice'])){
                            $tipologia_assenza = $tipologia['richieste_sottotipologia_codice'];
                        } else {
                            $tipologia_assenza = 'F';
                        }
                } elseif($assenza['richieste_tipologia'] == 3) { // MALATTIA
                    $tipologia = $this->db->where('richieste_sottotipologia_id', $assenza['richieste_sottotipologia'])->get('richieste_sottotipologia')->row_array();
                    if(isset($tipologia['richieste_sottotipologia_codice'])){
                        $tipologia_assenza = $tipologia['richieste_sottotipologia_codice'];
                    } else {
                        $tipologia_assenza = 'M';
                    }
                } elseif($assenza['richieste_tipologia'] == 4) { // SMART WORKING
                    $tipologia_assenza = 'SW';
                    $dati_dip[$giorno]['smartworking'] = 'X';
                } elseif($assenza['richieste_tipologia'] == 5 ) { // TRASFERTA
                    $tipologia_assenza = 'TR';
                    $dati_dip[$giorno]['trasferta'] = 'X';
                }
                
                $dati_dip[$giorno]['tipo_assenza'] = $tipologia_assenza;
                $dati_dip[$giorno]['permesso'] += number_format($hours, $arrotondamento);
                $conteggi['permesso'] += number_format($hours, $arrotondamento);
            }

            if (isset($presenze[$dipendente['dipendenti_id']]) && !empty($presenze[$dipendente['dipendenti_id']])) {
                $pres = $presenze[$dipendente['dipendenti_id']];

                foreach ($pres as $p) {
                    if (date('d', strtotime($p['presenze_data_inizio'])) == $giorno) {
                        //Cerco orario lavorativo per il giorno che sto ciclando e prendo la pausa
                        $weekday = date('N', strtotime($p['presenze_data_inizio']));

                        $this->db->select('*');
                        $this->db->from('turni_di_lavoro');
                        $this->db->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', "left");
                        $this->db->where("turni_di_lavoro_data_inizio <= '{$p['presenze_data_inizio']}'", null, false);
                        $this->db->where("(turni_di_lavoro_data_fine >= '{$p['presenze_data_inizio']}' OR turni_di_lavoro_data_fine IS NULL)", null, false);
                        $this->db->where('turni_di_lavoro_dipendente', $p['presenze_dipendente']);
                        $this->db->where('turni_di_lavoro_giorno', $weekday);

                        $orario_lavoro = $this->db->get()->result_array();
                        $suggerimentoTurno = $this->timbrature->suggerisciTurno($p['presenze_ora_inizio'], $orario_lavoro,'entrata');
                        
                        if($ignora_pausa == DB_BOOL_FALSE) {
                            $pausa = 0;

                            if(!empty($orario_lavoro[$suggerimentoTurno]) && !empty($orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_pausa'])) {
                                $pausa = $orario_lavoro[$suggerimentoTurno]['orari_di_lavoro_ore_pausa_value'];
                            }
                        } else {
                            $pausa = 0;
                        }

                        $presenza_data_inizio = dateFormat($p['presenze_data_inizio'], 'Y-m-d');
                        $reperibilita = $this->db->where("DATE_FORMAT(reperibilita_data, '%Y-%m-%d') = '{$presenza_data_inizio}'", null, false)->where('reperibilita_dipendente', $p['presenze_dipendente'])->get('reperibilita')->row_array();
                        $ore_banca_giornaliere = $this->db->where("DATE_FORMAT(banca_ore_data, '%Y-%m-%d') = '{$presenza_data_inizio}'", null, false)->where('banca_ore_dipendente', $p['presenze_dipendente'])->where('banca_ore_creato_da_presenza', $p['presenze_id'])->get('banca_ore')->row_array();

                        if (!empty($reperibilita)) {
                            $conteggi['giorni_reperibili']++;
                            $dati_dip[$giorno]['reperibile'] = 'x';
                        }
                        if (!empty($p['presenze_ore_totali']) && (float) $p['presenze_ore_totali'] > 0 && (float) is_numeric($p['presenze_ore_totali'])) {
                            if($dati_dip[$giorno]['ordinarie'] == 0) {
                                $conteggi['giorni_ordinari'] += 1;
                            }
                            
                            $dati_dip[$giorno]['ordinarie'] += number_format($p['presenze_ore_totali'] - $pausa, $arrotondamento);
                            $conteggi['ore_ordinarie'] += number_format($p['presenze_ore_totali'] - $pausa, $arrotondamento);
                        }



                        //CONTROLLO DIRITTO ALL'INDENNITA MENSA
                        // 22/01/2024 Non si controlla più se ho lavorato almeno le ore che dovevo fare ma se 
                        // almeno una mia presenza di oggi ha storicizzato ill buon pasto (maturato se ho fatto almeno N ore)
                        //dump($p);
                        if(!empty($p['presenze_buono_pasto']) && $p['presenze_buono_pasto'] == DB_BOOL_TRUE) {
                            $dati_dip[$giorno]['mensa'] = 'X';
                        }
                        /*$ore_tot_orari = 0;
                        if(!empty($orario_lavoro)) {
                            //Calcolo le ore di tutti i turni che ho per la giornata corrente come differenza ORA FINE - ORA INIZIO
                              foreach ($orario_lavoro as $orario) {
                                $inizio_turno = new DateTime($orario['turni_di_lavoro_ora_inizio']);
                                $fine_turno = new DateTime($orario['turni_di_lavoro_ora_fine']);
                                $ore_lavorate = $fine_turno->diff($inizio_turno)->h;

                                // Se è presente una pausa, sottraggila dalle ore lavorate
                                if (!empty($orario['turni_di_lavoro_pausa'])) {
                                    $ore_tot_orari -= $orario['orari_di_lavoro_ore_pausa_value'];
                                }
                                $ore_tot_orari += $ore_lavorate;
                            }
                        }
                        //Se le ore lavorate sono >= delle ore di tutti i turni per la giornata corrente ho diritto all'indennità mensa
                        if($dati_dip[$giorno]['ordinarie'] >= $ore_tot_orari) {
                            $dati_dip[$giorno]['mensa'] = 'X';
                        }*/

                        //CONTROLLO SMARTWORKING PRESENZA
                        $smartworking = $p['presenze_smartworking'] ?? DB_BOOL_FALSE;
                        if($smartworking == DB_BOOL_TRUE ) {
                            $dati_dip[$giorno]['smartworking'] = 'X';
                        }



                        if (!empty($p['presenze_notturno']) && (float) $p['presenze_notturno'] > 0 && (float) is_numeric($p['presenze_notturno'])) {
                            if($dati_dip[$giorno]['notturno'] == 0) {
                                $conteggi['giorni_notturni'] += 1;
                            }
                            //dump($p['presenze_notturno']);

                            $dati_dip[$giorno]['notturno'] += number_format($p['presenze_notturno'], $arrotondamento);
                            $conteggi['ore_notturno'] += number_format($p['presenze_notturno'], $arrotondamento);
                            // tolgo il notturno da quelle ordinarie
                            $conteggi['ore_ordinarie'] -= number_format($p['presenze_notturno'], $arrotondamento);
                            $dati_dip[$giorno]['ordinarie'] -= number_format($p['presenze_notturno'], $arrotondamento);
                        }

                        if (!empty($p['presenze_straordinario']) && (float) $p['presenze_straordinario'] > 0 && (float) is_numeric($p['presenze_straordinario'])) {
                            if($dati_dip[$giorno]['straordinarie'] == 0) {
                                $conteggi['giorni_straord'] += 1;
                            }
                            $dati_dip[$giorno]['straordinarie'] += number_format($p['presenze_straordinario'], $arrotondamento);
                            $conteggi['ore_straord'] += number_format($p['presenze_straordinario'], $arrotondamento);
                        }

                        if (!empty($ore_banca_giornaliere)) {
                            $dati_dip[$giorno]['banca_ore'] += number_format($ore_banca_giornaliere['banca_ore_hours'], 2);
                            $conteggi['banca'] += number_format($ore_banca_giornaliere['banca_ore_hours'], 2);
                        }
                        
                        //verifico se è una trasferta
                        if(!empty($p['presenze_reparto'])) {
                            $reparto = $this->db->where("reparti_id = '{$p["presenze_reparto"]}'", null, false)->get('reparti')->row_array();

                            if(isset($reparto['reparti_trasferta_italia'])) {
                                if($reparto['reparti_trasferta_italia'] == DB_BOOL_TRUE) {
                                    $dati_dip[$giorno]['tipo_assenza'] = 'TI';
                                }
                            }

                            if(isset($reparto['reparti_trasferta_estero'])) {
                                if($reparto['reparti_trasferta_estero'] == DB_BOOL_TRUE) {
                                    $dati_dip[$giorno]['tipo_assenza'] = 'TE';
                                }
                            }
                        }
                       
                        //Se presenza è anomala lo segnalo
                        if(!empty($p['presenze_anomalia']) && $p['presenze_anomalia'] == DB_BOOL_TRUE) {
                            $dati_dip[$giorno]['anomalia'] = 'X';
                        }
                    }
                }
            }
        }

        $dati_dip['conteggi'] = $conteggi;
        $dati["{$dipendente['dipendenti_cognome']} {$dipendente['dipendenti_nome']}"] = $dati_dip;
    }

    $voci_busta = [
        ['key' => 'ordinarie', 'name' => 'Ore Ordinarie'],
        ['key' => 'notturno', 'name' => 'Ore Notturne'],
        ['key' => 'tipo_assenza', 'name' => 'Richiesta'],
        ['key' => 'permesso', 'name' => 'Ore richiesta'], 
        ['key' => 'trasferta', 'name' => 'Trasferte'], 
        ['key' => 'mensa', 'name' => 'Indennità mensa'], 
        ['key' => 'smartworking', 'name' => 'Smartworking'],
        ['key' => 'anomalia', 'name' => 'Anomalia'],
    ];
    
    //Aggiungo reperibilità se attivo
    if($impostazioni_modulo['impostazioni_hr_reperibilita']==1){
        $elemento_aggiungere =  [
            ['key' => 'reperibile', 'name' => 'Reperibilità']
        ];
        array_splice( $voci_busta, 2, 0, $elemento_aggiungere ); // splice in at position 3
    }

    if($impostazioni_modulo['impostazioni_hr_banca_ore'] == 0) {
        $elemento_aggiungere =  [
            ['key' => 'straordinarie', 'name' => 'Ore Starord.']
        ];
    } else {
        $elemento_aggiungere =  [
            ['key' => 'banca_ore', 'name' => 'Banca ore']
        ];
    }
    array_splice( $voci_busta, 2, 0, $elemento_aggiungere ); // splice in at position 3

?>

<style>
.td-middle {
    vertical-align: middle !important;
}

th,
td {
    white-space: nowrap;
    padding: 10px;
}
</style>

<?php echo (!empty($filtro_testo)) ? "<h4>{$filtro_testo}</h4>" : ''; ?>

<table class="table-bordered">
    <tbody>
        <thead>
            <tr>
                <th colspan="3"></th>
                <?php
                $giorni_italiano = array(
                    'Mon' => 'L',
                    'Tue' => 'M',
                    'Wed' => 'M',
                    'Thu' => 'G',
                    'Fri' => 'V',
                    'Sat' => 'S',
                    'Sun' => 'D'
                );
                
                for ($giorno = 1; $giorno <= $giorni_mese; $giorno++) {
                    $giorno_sql = str_pad($giorno, 2, '0', STR_PAD_LEFT);
                    $current_date = $anno.'-'.$mese.'-'.$giorno_sql;
                    $dayOfWeek = date('D', strtotime($current_date));
                    
                    echo "<th>{$giorni_italiano[$dayOfWeek]}</th>";
                }
                ?>
                <th colspan="10" class="text-center"></th>
            </tr>
            <tr>
                <th colspan="3"></th>
                <?php for ($giorno = 1; $giorno <= $giorni_mese; $giorno++): ?>
                <?php
                    $giorno_sql = str_pad($giorno, 2, '0', STR_PAD_LEFT);

                    $current_date = $anno.'-'.$mese.'-'.$giorno_sql;
                    $festivo = $this->db->where("DATE_FORMAT(festivita_data, '%Y-%m-%d') = '{$current_date}'", null, false)->get('festivita')->row_array();
                    $datatime_current_date = new DateTime($current_date);
                    $day =  $datatime_current_date->format("w");
                    if($day==6 || $day == 0 || !empty($festivo)){
                        echo "<th class='bg-danger'><?php echo $giorno; ?></th>";
                }else {
                echo "<th><?php echo $giorno; ?></th>";
                }
                ?>
                <?php endfor; ?>
                <th colspan="10" class="text-center">Totali</th>
            </tr>
            <tr>
                <th>Matr.</th>
                <th>Dipendente</th>
                <th>Voci Busta</th>

                <?php for ($giorno = 1; $giorno <= $giorni_mese; $giorno++): $dayofweek = date('N', strtotime(date("Y-m-").str_pad($giorno, 8, '0', STR_PAD_LEFT))); ?>
                <th></th>
                <?php endfor; ?>
                <th>Gg ordinari</th>
                <th>Ore ordinarie</th>
                <th>Gg notturni</th>
                <th>Ore notturne</th>
                <?php if($impostazioni_modulo['impostazioni_hr_banca_ore']==0): ?>
                <th>Gg Straord.</th>
                <th>Ore Straord.</th>
                <?php else: ?>
                <th>Banca Ore</th>
                <?php endif; ?>
                <?php if($impostazioni_modulo['impostazioni_hr_reperibilita']==1): ?>
                <th>GG reperibili</th>
                <?php endif; ?>
                <th>Permessi</th>
                <th>Spese fisse</th>
                <th>Spese Mensili</th>
                <th>Spese da inserire in busta</th>
            </tr>
        </thead>

    <tbody>
        <tr>
            <td colspan="<?php echo $giorni_mese+14 ?>"></td>
        </tr>

        <?php foreach ($dati as $dipendente => $presenze): ?>,
        <?php foreach ($voci_busta as $key => $voce_busta): ?>
        <tr>
            <?php if ($key == 0) : ?>
            <td class="td-middle" rowspan="<?php echo count($voci_busta) ?>"><?php echo $presenze['dipendente']['dipendenti_id']; ?></td>
            <td class="td-middle" rowspan="<?php echo count($voci_busta) ?>"><?php echo $dipendente; ?></td>
            <?php endif; ?>

            <td><?php echo $voce_busta['name']; ?></td>

            <?php for ($giorno = 1; $giorno <= $giorni_mese; $giorno++): ?>
            <td>
                <!-- <td class="<?php //echo $presenze[$giorno]['sfondo']; ?>">-->
                <?php 
                if($voce_busta['name']=='Richiesta'){
                    echo "<b>".$presenze[$giorno][$voce_busta['key']]."</b>";
                } else {
                    echo $presenze[$giorno][$voce_busta['key']];
                }
                ?>
            </td>
            <?php endfor; ?>

            <!-- Totale Giorni ordinari -->
            <td><?php echo ($voce_busta['key'] == 'ordinarie') ? $presenze['conteggi']['giorni_ordinari'] : null; ?></td>
            <!-- Totale Ore ordinarie -->
            <td><?php echo ($voce_busta['key'] == 'ordinarie') ? $presenze['conteggi']['ore_ordinarie'] : null; ?></td>
            <!-- Totale Ore notturne -->
            <td><?php echo ($voce_busta['key'] == 'notturno') ? $presenze['conteggi']['giorni_notturni'] : null; ?></td>
            <!-- Totale Ore notturne -->
            <td><?php echo ($voce_busta['key'] == 'notturno') ? $presenze['conteggi']['ore_notturno'] : null; ?></td>
            <?php if($impostazioni_modulo['impostazioni_hr_banca_ore']==0): ?>
            <!-- Totale Giorni Straord. -->
            <td><?php echo ($voce_busta['key'] == 'straordinarie') ? $presenze['conteggi']['giorni_straord'] : null; ?></td>
            <!-- Totale Ore Straord. -->
            <td><?php echo ($voce_busta['key'] == 'straordinarie') ? $presenze['conteggi']['ore_straord'] : null; ?></td>
            <?php else: ?>
            <!-- Giorni di banca -->
            <td><?php echo ($voce_busta['key'] == 'banca_ore') ? $presenze['conteggi']['banca'] : null; ?></td>
            <?php endif; ?>
            <!-- Giorni reperibili -->
            <?php if($impostazioni_modulo['impostazioni_hr_reperibilita']==1): ?>
            <td><?php echo ($voce_busta['key'] == 'reperibile') ? $conteggi['giorni_reperibili'] : null; ?></td>
            <?php endif; ?>
            <!-- Permessi -->
            <td><?php echo ($voce_busta['key'] == 'permesso') ? $presenze['conteggi']['permesso'] : null; ?></td>
            <!-- Spese fisse -->
            <td></td>
            <!-- Spese Mensili -->
            <td></td>
            <!-- Spese da inserire in busta -->
            <td></td>

            <?php /*if ($key == 0) : ?>
            <td class="td-middle" rowspan="<?php echo count($voci_busta) ?>"></td>
            <?php endif;*/ ?>
        </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>

    </tbody>

    </tbody>
</table>

<br />
<p>Legenda richieste:</p>
<ul style="font-size:8px;">
    <li>F = Ferie</li>
    <li>P = Permesso</li>
    <li>M = Malattia</li>
    <li>SW = Smart Working</li>
    <li>TR = Trasferta</li>
    <?php
    //$richieste_sottotipologia = $this->db->get('richieste_sottotipologia')->row_array();
    $richieste_sottotipologia = $this->apilib->search('richieste_sottotipologia');
    foreach($richieste_sottotipologia as $richiesta_sottotipologia) {
        if(!empty($richiesta_sottotipologia['richieste_sottotipologia_value'])){
            echo "<li>".$richiesta_sottotipologia['richieste_sottotipologia_codice']." = ".$richiesta_sottotipologia['richieste_sottotipologia_value']."</li>";
        }
    }
  ?>
    <?php
  //TODO migliorare il codice.
  $ti = 0;
  $te = 0;
  $reparti = $this->apilib->search('reparti');
  foreach($reparti as $reparto){
    if($reparto['reparti_trasferta_italia'] == DB_BOOL_TRUE && $ti == 0){
        echo "<li>TI = Trasferta Italia</li>";
        $ti++;
    }
    if($reparto['reparti_trasferta_estero'] == DB_BOOL_TRUE && $te == 0){
        echo "<li>TE = Trasferta estera</li>";
        $te++;
    }
  }
  ?>
</ul>