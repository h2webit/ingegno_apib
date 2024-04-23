<?php
    ini_set("pcre.backtrack_limit", "50000000");

    $giorni_map = ['D', 'L', 'M', 'M', 'G', 'V', 'S'];

    $giorni_mese = date('t');

    $dati = [];

    // Dati filtri impostati
    $filters = $this->session->userdata(SESS_WHERE_DATA);

    $impostazioni_modulo = $this->apilib->searchFirst('impostazioni_hr');
    $arrotondamento = 2;
    if(isset($impostazioni_modulo['impostazioni_hr_numeri'])){
        if($impostazioni_modulo['impostazioni_hr_numeri']==0){
            $arrotondamento = 0;
        }
    }

    $filtro_testo = '';
    $filtro_dipendente_id = null;

    if (!empty($filters['filter-presenze'])) {
        $filtri = $filters['filter-presenze'];

        $presenze_dipendente_field_id = $this->datab->get_field_by_name('presenze_dipendente')['fields_id'];
        $presenze_data_field_id = $this->datab->get_field_by_name('presenze_data_inizio')['fields_id'];

        if (!empty($filtri[$presenze_dipendente_field_id]['value']) && $filtri[$presenze_dipendente_field_id]['value'] !== '-1') {
            $filtro_dipendente_id = $filtri[$presenze_dipendente_field_id]['value'];

            $dipendente = $this->db->where('dipendenti_id', $filtri[$presenze_dipendente_field_id]['value'])->get('dipendenti')->row_array();

            $filtro_testo .= "Dipendente: <b>{$dipendente['dipendenti_nome']} {$dipendente['dipendenti_cognome']}</b>, ";

            $this->db->where('presenze_dipendente', $filtri[$presenze_dipendente_field_id]['value']);
        }

        if (!empty($filtri[$presenze_data_field_id]['value'])) {
            $presenze_data_ex = explode(' - ', $filtri[$presenze_data_field_id]['value']);
            $presenze_data = (DateTime::createFromFormat('d/m/Y', $presenze_data_ex[0]))->format('Y-m');
            $filtro_testo .= "Mese e anno: <b>{$presenze_data}</b>";

            $this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = '{$presenze_data}'", null, false);
            $anno = (DateTime::createFromFormat('d/m/Y', $presenze_data_ex[0]))->format('Y');
            $mese = (DateTime::createFromFormat('d/m/Y', $presenze_data_ex[0]))->format('m');
            $giorni_mese = cal_days_in_month(CAL_GREGORIAN, $mese, $anno);
        } else {
            $this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')", null, false);
            $anno = date('Y');
            $mese = date('m');
        }

    } else {
        $this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')", null, false);
        $anno = date('Y');
        $mese = date('m');
    }
    
    $this->db->join("presenze_pausa", "presenze_pausa = presenze_pausa_id", "left");
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

    $dipendenti = $this->apilib->search('dipendenti', $where_dipendenti);

    foreach ($dipendenti as $dipendente) {
        $ordinarie = $straordinarie = 0;

        $dati_dip = [];

        $dati_dip['dipendente'] = $dipendente;
        
        $conteggi = [];
        for ($giorno = 1; $giorno <= $giorni_mese; $giorno++) {
            if (empty($dati_dip[$giorno])) {
                $dati_dip[$giorno]['ordinarie'] = 0;
                $dati_dip[$giorno]['straordinarie'] = 0;
                $dati_dip[$giorno]['reperibile'] = '';
                $dati_dip[$giorno]['trasferte'] = '';
                $dati_dip[$giorno]['banca_ore'] = 0;
                $dati_dip[$giorno]['permesso'] = 0;
                $dati_dip[$giorno]['ferie'] = 0;
                $dati_dip[$giorno]['malattia'] = 0;
                $dati_dip[$giorno]['tipo_assenza'] = '';
                $dati_dip[$giorno]['giorno_ferie'] = '';
                $dati_dip[$giorno]['giorno_malattia'] = '';
            }

            if (empty($conteggi)) {
                $conteggi['giorni_ordinari'] = 0;
                $conteggi['giorni_straord'] = 0;
                $conteggi['ore_ordinarie'] = 0;
                $conteggi['ore_straord'] = 0;

                $conteggi['giorni_reperibili'] = 0;
                $conteggi['trasferte'] = 0;
                $conteggi['banca'] = 0;
                $conteggi['permesso'] = 0;
                $conteggi['ferie'] = 0;
                $conteggi['malattia'] = 0;
            }

            $day = $giorno < 10 ? '0'.$giorno : $giorno;
            /* $month = date('m');
            $year = date('Y'); */
            $current_date = $anno.'-'.$mese.'-'.$day;
            //PERMESSI
            //$assenza = $this->db->where("DATE_FORMAT(richieste_dal, '%Y-%m-%d') = '{$current_date}'", null, false)->where("DATE_FORMAT(richieste_al, '%Y-%m-%d') = '{$current_date}'", null, false)->where('richieste_user_id', $dipendente['dipendenti_id'])->where('richieste_stato', '2')->get('richieste')->row_array();
            //$assenza = $this->db->where("DATE_FORMAT(richieste_dal, '%Y-%m-%d') = '{$current_date}'", null, false)->where("DATE_FORMAT(richieste_al, '%Y-%m-%d') = '{$current_date}'", null, false)->where('richieste_user_id', $dipendente['dipendenti_id'])->where('richieste_stato', '2')->get('richieste')->row_array();
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

                //se è permesso
                if($assenza['richieste_tipologia'] == 1 ) {
                    //$hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);
                    $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h, 2);
                    /**
                     * ? SE NON HO ORARI DI LAVORO:
                     * ?    Con i nuovi parametri (ore giornaliere standard, ecc...) usare questa logica:
                     * ?        Se $diff_date->days == 0 => calcolo diff in ore semplice come sopra
                     * ?        Altrimenti $hours = ore standard dipendente
                     * ? ELSE:
                     * ?    Il primo giorno calcolo da $data_ora_inizio a ora fine lavoro configurata. L'ultimo è il diff di ora inizio lavoro configura e $fine.
                     * ?    Nei giorni "in mezzo" hours = alle ore che il dipendente deve fare quel giorno
                     */
                    
                    /**
                    * ! CALCOLO IN BASE ALLE ORE STANDARD
                    **/
                    if($dipendente['dipendenti_ignora_orari_lavoro'] == DB_BOOL_TRUE && !empty($dipendente['dipendenti_ore_standard']) && $dipendente['dipendenti_ore_standard'] > 0) {
                        //Se data inizio = data fine calcolo semplicemente la differenza
                        if($diff_date->days == 0) {
                            $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h, 2);
                        } else {
                            //Imposto ore uguale alle ore standard di lavoro
                            $hours = $dipendente['dipendenti_ore_standard'];
                        }
                    } else {
                        /**
                         * ! CALCOLO IN BASE AGLI ORARI DI LAVORO
                         */
                        //Giorni inizio e fine richiesta
                        $inizio_richiesta = $inizio->format('j');
                        $fine_richiesta = $fine->format('j');

                        //Devo prendermi il corrispondente orario vedendo a quale giorno della settimana corrisponde il giorno che sto ciclando
                        $weekday = date('w', strtotime($current_date));
                        $orario_lavoro = $this->db
                        ->join("orari_di_lavoro_ore_pausa", "orari_di_lavoro_ore_pausa = orari_di_lavoro_ore_pausa_id", "left")
                        ->join("orari_di_lavoro_giorno", "orari_di_lavoro_giorno_numero = '".$weekday."'", "left")
                        ->where("orari_di_lavoro_dipendente", $dipendente['dipendenti_id'])
                        ->get("orari_di_lavoro")->row_array();

                        //PRIMO GIORNO
                        if($inizio_richiesta == $giorno) {
                            //Calcolo differenza tra data_ora_inizio_richiesta e data_ora_fine_lavoro
                            $data_ora_fine_lavoro = str_ireplace(' 00:00:00', '', $assenza['richieste_al']).' '.$orario_lavoro['orari_di_lavoro_ora_fine'];
                            $inizio = new DateTime($data_ora_inizio);
                            $fine = new DateTime($data_ora_fine_lavoro);
                            $diff_date = $fine->diff($inizio);
                            
                            $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h, 2);
                        } elseif($inizio_richiesta < $giorno && $fine_richiesta > $giorno) {
                            //GIORNATE INTERMEDIE
                            $hours = $orario_lavoro['orari_di_lavoro_totale_ore'] + 1;
                        } elseif($fine_richiesta == $giorno) {
                            //ULTIMO GIORNO, calcolo differenza tra data_ora_inizio_lavoro e data_ora_fine_richiesta
                            $data_ora_inizio_lavoro = str_ireplace(' 00:00:00', '', $assenza['richieste_al']).' '.$orario_lavoro['orari_di_lavoro_ora_inizio'];
                            $data_ora_fine = str_ireplace(' 00:00:00', '', $assenza['richieste_al']).' '.$assenza['richieste_ora_fine'];
                            $inizio = new DateTime($data_ora_inizio_lavoro);
                            $fine = new DateTime($data_ora_fine);
                            $diff_date = $fine->diff($inizio);

                            $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h, 2);
                        }
                    }



                    //vedo la sottotipologia
                    if(empty($assenza['richieste_sottotipologia'])) {
                        $tipologia_assenza = 'P';
                    } else {
                        $tipologia = $this->db->where('richieste_sottotipologia_id', $assenza['richieste_sottotipologia'])->get('richieste_sottotipologia')->row_array();
                        if(isset($tipologia['richieste_sottotipologia_codice'])){
                            $tipologia_assenza = $tipologia['richieste_sottotipologia_codice'];
                        } else {
                            $tipologia_assenza = 'P';
                        }
                    }
                } elseif($assenza['richieste_tipologia'] == 2 ) {
                    $conteggi['ferie'] += 1;
                    $dati_dip[$giorno]['giorno_ferie'] = 'X';
                    //$tipologia_assenza = 'F';
                    $tipologia = $this->db->where('richieste_sottotipologia_id', $assenza['richieste_sottotipologia'])->get('richieste_sottotipologia')->row_array();
                    if(isset($tipologia['richieste_sottotipologia_codice'])){
                        $tipologia_assenza = $tipologia['richieste_sottotipologia_codice'];
                    } else {
                        $tipologia_assenza = 'F';
                    }
                } elseif($assenza['richieste_tipologia'] == 3 ) {
                    $conteggi['malattia'] += 1;
                    $dati_dip[$giorno]['giorno_malattia'] = 'X';
                    //$tipologia_assenza = 'M';
                    $tipologia = $this->db->where('richieste_sottotipologia_id', $assenza['richieste_sottotipologia'])->get('richieste_sottotipologia')->row_array();
                    if(isset($tipologia['richieste_sottotipologia_codice'])){
                        $tipologia_assenza = $tipologia['richieste_sottotipologia_codice'];
                    } else {
                        $tipologia_assenza = 'M';
                    }
                }

                $dati_dip[$giorno]['tipo_assenza'] = $tipologia_assenza;
                $dati_dip[$giorno]['permesso'] += number_format($hours, $arrotondamento);
                $conteggi['permesso'] += number_format($hours, $arrotondamento);
            }

            if (isset($presenze[$dipendente['dipendenti_id']]) && !empty($presenze[$dipendente['dipendenti_id']])) {
                $pres = $presenze[$dipendente['dipendenti_id']];

                //Flag per contare presenza una sola volta
                $presenze_multiple_ordinario = false;
                $presenze_multiple_straord = false;
                
                foreach ($pres as $p) {
                    if (date('d', strtotime($p['presenze_data_inizio'])) == $giorno) {
                        //Cerco orario lavorativo per il giorno che sto ciclando e prendo la pausa
                        $weekday = date('w', strtotime($p['presenze_data_inizio']));

                        $orario_lavoro = $this->db
                        ->join("orari_di_lavoro_ore_pausa", "orari_di_lavoro_ore_pausa = orari_di_lavoro_ore_pausa_id", "left")
                        ->join("orari_di_lavoro_giorno", "orari_di_lavoro_giorno_numero = '".$weekday."'", "left")
                        ->where("orari_di_lavoro_dipendente", $p['presenze_dipendente'])
                        ->get("orari_di_lavoro")->row_array();
                    
                        $pausa = 1;
                        if(!empty($orario_lavoro) && !empty($orario_lavoro['orari_di_lavoro_ore_pausa'])) {
                            $pausa = $orario_lavoro['orari_di_lavoro_ore_pausa_value'];
                        } elseif(empty($orario_lavoro)) {
                            if(!empty($dipendente['dipendenti_ignora_pausa']) && $dipendente['dipendenti_ignora_pausa'] == DB_BOOL_TRUE) {
                                $pausa = 0;
                            } else {
                                $pausa = $p['presenze_pausa_value'] ?? 0;
                            }
                        }

                        $presenza_data_inizio = dateFormat($p['presenze_data_inizio'], 'Y-m-d');
                        $reperibilita = $this->db->where("DATE_FORMAT(reperibilita_data, '%Y-%m-%d') = '{$presenza_data_inizio}'", null, false)->where('reperibilita_dipendente', $p['presenze_dipendente'])->get('reperibilita')->row_array();
                        $ore_banca_giornaliere = $this->db->where("DATE_FORMAT(banca_ore_data, '%Y-%m-%d') = '{$presenza_data_inizio}'", null, false)->where('banca_ore_dipendente', $p['presenze_dipendente'])->where('banca_ore_creato_da_presenza', $p['presenze_id'])->get('banca_ore')->row_array();

                        if (!empty($reperibilita)) {
                            $conteggi['giorni_reperibili']++;
                            $dati_dip[$giorno]['reperibile'] = 'x';
                        }

                        if (!empty($p['presenze_ore_totali']) && (float) $p['presenze_ore_totali'] > 0 && (float) is_numeric($p['presenze_ore_totali'])) {
                            $dati_dip[$giorno]['ordinarie'] += number_format($p['presenze_ore_totali'] - $p['presenze_straordinario'] - $pausa, $arrotondamento);
                            $conteggi['ore_ordinarie'] += number_format($p['presenze_ore_totali'] - $p['presenze_straordinario'] - $pausa, $arrotondamento);
                            //CALCOLO TOT GIORNI ORDINARI
                            if($presenze_multiple_ordinario == false) {
                                $conteggi['giorni_ordinari'] += 1;
                            }
                            $presenze_multiple_ordinario = true;
                        }
                        
                        if (!empty($p['presenze_straordinario']) && (float) $p['presenze_straordinario'] > 0 && (float) is_numeric($p['presenze_straordinario'])) {
                            $dati_dip[$giorno]['straordinarie'] += number_format($p['presenze_straordinario'], $arrotondamento);
                            $conteggi['ore_straord'] += number_format($p['presenze_straordinario'], $arrotondamento);
                            //CALCOLO TOT GIORNI STRAORDINARI
                            if($presenze_multiple_straord == false) {
                                $conteggi['giorni_straord'] += 1;
                            }
                            $presenze_multiple_straord = true;
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
                       
                    }
                }
            }
        }

        /*$conteggi['giorni_ordinari'] = round(($conteggi['ore_ordinarie'] / 24), 0);
        $conteggi['giorni_straord'] = round(($conteggi['ore_straord'] / 24), 0);*/
        $dati_dip['conteggi'] = $conteggi;
    
        $dati["{$dipendente['dipendenti_cognome']} {$dipendente['dipendenti_nome']}"] = $dati_dip;
    }
    /*
    $voci_busta = [
        ['key' => 'ordinarie', 'name' => 'Ore Ordinarie'],
        ['key' => 'straordinarie', 'name' => 'Ore Starord.'],
        ['key' => 'reperibile', 'name' => 'Reperibilità'],
        ['key' => 'trasferte', 'name' => 'Trasferte'],
        ['key' => 'banca_ore', 'name' => 'Banca ore'],
        ['key' => 'permesso', 'name' => 'Permessi'],
        ['key' => 'ferie', 'name' => 'Ferie'],
        ['key' => 'malattia', 'name' => 'Malattia']
    ];*/
    $voci_busta = [
        ['key' => 'ordinarie', 'name' => 'Ore Ordinarie'],
        ['key' => 'tipo_assenza', 'name' => 'Tipologia'],
        ['key' => 'permesso', 'name' => 'Ore permesso'], 
        ['key' => 'giorno_ferie', 'name' => 'Ferie'],
        ['key' => 'giorno_malattia', 'name' => 'Malattia'],
    ];
    //Aggiungo reperibilità se attivo
    if($impostazioni_modulo['impostazioni_hr_reperibilita'] == 1) {
        $elemento_aggiungere =  [
            ['key' => 'reperibile', 'name' => 'Reperibilità']
        ];
        array_splice( $voci_busta, 1, 0, $elemento_aggiungere ); // splice in at position 2
    }

    if($impostazioni_modulo['impostazioni_hr_banca_ore'] == 0) {
        $elemento_aggiungere =  [
            ['key' => 'straordinarie', 'name' => 'Ore Starord.']
        ];
    }
    else {
        $elemento_aggiungere =  [
            ['key' => 'banca_ore', 'name' => 'Banca ore']
        ];
    }

    array_splice( $voci_busta, 1, 0, $elemento_aggiungere ); // splice in at position 2

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

<?php echo (!empty($filtro_testo)) ? "<h3>{$filtro_testo}</h3>" : ''; ?>

<table class="table-bordered">
    <tbody>
        <thead>
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

                <?php for ($giorno = 1; $giorno <= $giorni_mese; $giorno++): $dayofweek = date('w', strtotime(date("Y-m-").str_pad($giorno, 8, '0', STR_PAD_LEFT))); ?>
                <th></th>
                <?php endfor; ?>
                <th>Tot. gg ordinari</th>
                <th>Tot. Ore ordinarie</th>
                <?php if($impostazioni_modulo['impostazioni_hr_banca_ore']==0): ?>
                <th>Tot. gg Straord.</th>
                <th>Tot. Ore Straord.</th>
                <?php else: ?>
                <th>Banca Ore</th>
                <?php endif; ?>
                <?php if($impostazioni_modulo['impostazioni_hr_reperibilita']==1): ?>
                <th>GG reperibili</th>
                <?php endif; ?>
                <th>Ore Permessi</th>
                <th>Giorni Ferie</th>
                <th>Giorni Malattia</th>
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
                if($voce_busta['name']=='Tipologia'){
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

            <?php if($impostazioni_modulo['impostazioni_hr_banca_ore']==0): ?>
            <!-- Totale Ore Straord. -->
            <td><?php echo ($voce_busta['key'] == 'straordinarie') ? $presenze['conteggi']['giorni_straord'] : null; ?></td>
            <!-- Totale Giorni Straord. -->
            <td><?php echo ($voce_busta['key'] == 'straordinarie') ? $presenze['conteggi']['ore_straord'] : null; ?></td>
            <?php else: ?>
            <!-- Giorni di banca -->
            <td><?php echo ($voce_busta['key'] == 'banca_ore') ? $presenze['conteggi']['banca'] : null; ?></td>
            <?php endif; ?>
            <!-- Giorni reperibili -->
            <?php if($impostazioni_modulo['impostazioni_hr_reperibilita']==1): ?>
            <td><?php echo ($voce_busta['key'] == 'reperibile') ? $conteggi['giorni_reperibili'] : null; ?></td>
            <?php endif; ?>
            <!-- Permessi - Ore -->
            <td><?php echo ($voce_busta['key'] == 'permesso') ? $presenze['conteggi']['permesso'] : null; ?></td>
            <!-- Ferie - Giorni -->
            <td><?php echo ($voce_busta['key'] == 'giorno_ferie') ? $presenze['conteggi']['ferie'] : null; ?></td>
            <!-- Malattie - Giorni -->
            <td><?php echo ($voce_busta['key'] == 'giorno_malattia') ? $presenze['conteggi']['malattia'] : null; ?></td>

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
<p>Legenda:</p>
<ul style="font-size:8px;">
    <li>F = Ferie</li>
    <li>P = Permesso</li>
    <li>M = Malattia</li>
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