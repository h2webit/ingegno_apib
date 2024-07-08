<?php


$sede = $this->apilib->view('projects', $value_id);
$cliente = $this->apilib->view('customers', $sede['projects_customer_id']);

/*
$holidayDays = [
    '12-25', //Natale
    '01-01', //Capodanno
    '01-06', //Epifania
    '04-25', //Liberazione
    '05-01', //Festa dei lavoratori
    '06-02', //Festa della repubblica
    '08-15', //Ferragosto
    '11-01', //Tutti i santi
    '12-08', //Immacolata
    '12-26', //Santo stefano
    //Da cambiare ogni anno
    '03-31', //Pasqua
    '04-01', //Pasquetta
    '10-04', //San Petronio
];
*/

$workingDays = [1, 2, 3, 4, 5, 6]; # date format = N (1 = Monday, for more information see )
$festivita = $this->apilib->search('festivita');
$holidayDays = [];
foreach ($festivita as $fest) {
    $holidayDays[] = date('m-d', strtotime($fest['festivita_data']));
}


//$months = [
//    1 => 'Gennaio',
//    2 => 'Febbraio',
//    3 => 'Marzo',
//    4 => 'Aprile',
//    5 => 'Maggio',
//    6 => 'Giugno',
//    7 => 'Luglio',
//    8 => 'Agosto',
//    9 => 'Settembre',
//    10 => 'Ottobre',
//    11 => 'Novembre',
//    12 => 'Dicembre',
//];
$year = ($this->input->get('Y')) ? $this->input->get('Y') : date('Y');
$month = ($this->input->get('m')) ? $this->input->get('m') : date('m');
$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$associati = $this->apilib->search(
    'dipendenti',
    //Prendo solo dipendenti attivi, associati a questo progetto/sede o dipendenti che hanno turni assegnati in questo mese per questo progetto
    [
        "dipendenti_user_id IN (
    	    SELECT 
                users_id 
            FROM rel_appuntamenti_persone 
            LEFT JOIN appuntamenti ON (appuntamenti.appuntamenti_id = rel_appuntamenti_persone.appuntamenti_id) 
            WHERE 
                appuntamenti_impianto = '$value_id' 
                AND DATE(appuntamenti_giorno) >= '$year-$month-01' 
                AND DATE(appuntamenti_giorno) <= '$year-$month-$days'
        ) OR 
            dipendenti_user_id IN (
    	        SELECT users_id FROM project_members WHERE projects_id = '$value_id'
            ) 
        AND dipendenti_attivo = '1'",

    ],
    0,
    null,
    'dipendenti_cognome'
);

$ore = [];

$_fascie_orarie = $this->db->where(
    "
        (
            projects_orari_project = '$value_id' AND (projects_orari_cancellato = '0' OR projects_orari_cancellato IS NULL) 
        ) 
        OR projects_orari_id IN (
            SELECT appuntamenti_fascia 
            FROM appuntamenti 
            WHERE
                appuntamenti_impianto = '$value_id' 
                AND DATE(appuntamenti_giorno) >= '$year-$month-01' 
                AND DATE(appuntamenti_giorno) <= '$year-$month-$days'
        )   
    ",
    null,
    false
)->order_by('projects_orari_dalle')->get('projects_orari')->result_array();


$fascie_orarie = [];
foreach ($_fascie_orarie as $fascia) {
    $fascie_orarie[$fascia['projects_orari_id']] = $fascia['projects_orari_sigla'];
}
$_appuntamenti = $this->db
    
    ->join('appuntamenti', 'rel_appuntamenti_persone.appuntamenti_id = appuntamenti.appuntamenti_id')
    ->join('dipendenti', 'dipendenti_user_id = rel_appuntamenti_persone.users_id')
    ->join('projects_orari', 'projects_orari_id = appuntamenti_fascia')
    ->join('projects', 'projects_id = appuntamenti_impianto')
    ->where('appuntamenti_impianto', $value_id)
    //->where("projects_orari_cancellato <> '1'")
    ->where("MONTH(appuntamenti_giorno) = '$month'", null, false)
    ->get('rel_appuntamenti_persone')->result_array();
//debug($_appuntamenti,true);

$_richieste = $this->apilib->search('richieste_disponibilita', [
    'richieste_disponibilita_sede_operativa' => $value_id,
    'richieste_disponibilita_turno_assegnato IS NULL',
    "date_part('year', richieste_disponibilita_giorno) = '{$year}'",
    "date_part('month', richieste_disponibilita_giorno) = '{$month}'",
]);

$richieste = [];
foreach ($_richieste as $richiesta) {
    //debug($richiesta,true);
    for ($i = 1; $i <= 4; $i++) {
        if ($richiesta['richieste_disponibilita_affiancamento'] == '1') {
            if (!@in_array($richiesta['richieste_disponibilita_fascia'] . '*', $richieste[substr($richiesta['richieste_disponibilita_giorno'], 0, 10)][$i], true)) {
                $richieste[substr($richiesta['richieste_disponibilita_giorno'], 0, 10)][$i][] = $richiesta['richieste_disponibilita_fascia'] . '*';
                break;
            }
        } elseif ($richiesta['richieste_disponibilita_studente'] == '1') {
            if (!@in_array($richiesta['richieste_disponibilita_fascia'] . '**', $richieste[substr($richiesta['richieste_disponibilita_giorno'], 0, 10)][$i], true)) {
                $richieste[substr($richiesta['richieste_disponibilita_giorno'], 0, 10)][$i][] = $richiesta['richieste_disponibilita_fascia'] . '**';
                break;
            }
        } else {
            if (!@in_array($richiesta['richieste_disponibilita_fascia'], $richieste[substr($richiesta['richieste_disponibilita_giorno'], 0, 10)][$i])) {
                $richieste[substr($richiesta['richieste_disponibilita_giorno'], 0, 10)][$i][] = $richiesta['richieste_disponibilita_fascia'];
                break;
            }
        }
    }
}



$appuntamenti = $appuntamenti_dati = [];
foreach ($_appuntamenti as $sede_professionista) {
    //debug($sede_professionista, true);
    @$appuntamenti_dati[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['appuntamenti_associato']][] = $sede_professionista;
    if ($sede_professionista['projects_orari_cancellato'] == '1') {
        @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['appuntamenti_associato']][] = '!' . $sede_professionista['appuntamenti_fascia'] . '!';
    } else {
        if ($sede_professionista['appuntamenti_affiancamento'] == '1') {
            @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['appuntamenti_associato']][] = $sede_professionista['appuntamenti_fascia'] . '*';
        } elseif ($sede_professionista['appuntamenti_studente'] == '1') {
            @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['appuntamenti_associato']][] = $sede_professionista['appuntamenti_fascia'] . '**';
        } else {
            @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['appuntamenti_associato']][] = (string) $sede_professionista['appuntamenti_fascia'];
        }
    }
}

//$non_disponibilita_associati = $this->apilib->search('disponibilita_associati', [
//        "associati_id IN (
//            SELECT associati_id FROM projects_associati WHERE projects_id = '$value_id'
//        )",
//    ]
//);
//debug($non_disponibilita_associati,true);

$totali_giorni = $totali_associati = [];

$totalone = $totalone_affiancamenti = $totalone_costo_differenziato = 0;
?>

<style>
    .td-associato {

        overflow: hidden;
        white-space: nowrap;

        padding: 0px;
    }

    #calendario_sede td {
        padding: 0px;
    }

    .select2-container-multi .select2-choices .select2-search-field input {
        padding: 0px;
    }

    .select2-container-multi .select2-choices {
        padding: 0px;
        border: 0px;
    }

    .select2-container-multi .select2-choices .select2-search-choice {
        margin: 0px;
        padding: 0px;
        border: 0px;
    }

    .select2-container-multi .select2-choices .select2-search-choice .btn-danger {
        background: red !important;
    }

    .select2-search-choice-close {

        left: 1px !important;
        opacity: 0;
        position: relative;
        top: -12px;
        width: 18px;
    }

    .select2-container-multi .select2-choices .select2-search-choice div {
        margin: 0px 2px;
    }

    .select2-search-choice-close:hover {
        cursor: not-allowed;
    }

    .year {
        position: relative;
        left: 37%
    }

    .month {
        position: relative;
        left: 40%
    }

    .custom_form {
        padding-bottom: 1.5em;
    }

    .festività>ul.select2-choices {
        background-color: #F3565E;
    }

    .festività>ul.select2-choices>li.select2-search-choice {
        background-color: transparent;
    }

    .js_hover_multiselect {
        height: 30px !important;
        opacity: 0;
    }

    /*td.occupato, td.occupato * {
        background-color: #edd968 !important;
    }
    td.disponibile, td.disponibile * {
        background-color: #edd968 !important;
    }*/

    td.occupato .pallino1 {
        margin-left: 2px;
        height: 12px;
        width: 12px;
        background-color: #F00;
        border-radius: 50%;
        display: inline-block;

    }

    td.disponibile .pallino2 {
        margin-left: 2px;
        height: 12px;
        width: 12px;
        background-color: #0F0;
        border-radius: 50%;
        display: inline-block;
    }
</style>


<div class="col-lg-8 text-center form-inline custom_form">
    <select class="form-control select2me col-lg-4 year js-year ">
        <?php for ($i = date('Y') - 2; $i <= date('Y') + 10; $i++) : ?>
            <option value="<?php echo $i; ?>" <?php if ($i == $year) : ?> selected="selected" <?php endif; ?>><?php echo $i; ?></option>
        <?php endfor; ?>
    </select>
    <select class="js-month form-control select2me col-lg-4 month ">
        <?php
        if ($this->auth->get('utenti_tipo') == '16') {
            $m_start = date('m');
            $m_end = date('m') + 1;
        } else {
            $m_start = 1;
            $m_end = 12;
        }
        ?>
        <?php for ($m = $m_start; $m <= $m_end; $m++) : ?>
            <option value="<?php echo $m; ?>" <?php if ($m == $month) : ?> selected="selected" <?php endif; ?>><?php echo mese_testuale($m); ?></option>
        <?php endfor; ?>
    </select>

    <button style="margin-left:300px" class="btn js_refresh">Aggiorna</button>
    <button class="btn js-stampa">Stampa</button>
    <?php if ($this->auth->is_admin() || in_array($this->auth->get('utenti_tipo'), [7, 8])) : ?>
        <button class="btn red js-notifica">NOTIFICA A TUTTI</button>
    <?php endif; ?>


</div>
<?php if ($this->auth->get('utenti_tipo') != 17) : ?>
    <div class="col-lg-4">
        <input type="checkbox" class="form-control js-notify_change" /> Notifica ogni variazione all'associato
    </div>
<?php endif; ?>
<table class="table table-striped table-bordered " id="calendario_sede" sede_id="<?php echo $sede['projects_id']; ?>">
    <thead>
        <tr>
            <th>Associato</th>

            <?php for ($day = 1; $day <= $days; $day++) : ?>
                <?php
                $fullDate = $year . '-' . $month . '-' . $day;
                if (!in_array(date('w', strtotime($fullDate)), $workingDays) || in_array(date('m-d', strtotime($fullDate)), $holidayDays)) {
                    //debug($fullDate);
                    $bad_days = 'btn-danger festività';
                } else {
                    $bad_days = '';
                }
                ?>
                <th class="text-center <?php echo $bad_days; ?>"><?php echo $day; ?></th>

            <?php endfor; ?>
            <th class="text-center">Tot.</th>
        </tr>
    </thead>
    <tbody>

        <?php foreach ($associati as $associato) : ?>
            <tr class="js-calendario_sede">
                <td class="td-associato text-center"><?php echo $associato['associati_cognome'] . ' ' . substr($associato['associati_nome'], 0, 1); ?>.</td>
                <?php for ($day = 1; $day <= $days; $day++) : ?>
                    <?php
                    $dateString = date('Y-m-d', strtotime($year . '/' . $month . '/' . $day));

                    foreach (@(array) ($appuntamenti_dati[$dateString][$associato['associati_id']]) as $dati) {
                        if (str_ireplace(':', '', $dati['projects_orari_alle']) < str_ireplace(':', '', $dati['projects_orari_dalle'])) {
                            @$totali_giorni[$day] += (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                            @$totali_associati[$associato['associati_id']] += (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;

                            $ore = (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;

                            $totalone += $ore;
                            if ($dati['appuntamenti_affiancamento'] == '1') {
                                $totalone_affiancamenti += $ore;
                            }
                        } else {
                            @$totali_giorni[$day] += (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                            @$totali_associati[$associato['associati_id']] += (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;

                            $ore = (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;

                            $totalone += $ore;
                            if ($dati['appuntamenti_affiancamento'] == '1') {
                                $totalone_affiancamenti += $ore;
                            }
                        }
                    }
                    $tooltip = $title = $td_class = '';
                    //Verifico eventuali NON disponibilità per questo associato in questo giorno
                    if (!empty($associato['associati_id'])) {
                        $non_disponibile = $this->db->query("
                                    SELECT 
                                        * 
                                    FROM 
                                        disponibilita_associati 
                                    WHERE 
                                        disponibilita_associati_associato = '{$associato['associati_id']}'
                                        AND disponibilita_associati_tipo = '1'
                                        AND (disponibilita_associati_dal::date <= '$dateString' AND (
                                                disponibilita_associati_al::date >= '$dateString' 
                                                AND (
                                                    to_char(disponibilita_associati_al, 'HH24:MI:SS') <> '00:00:00'
                                                    OR disponibilita_associati_al::date <> '$dateString'
                                                )
                                            )
                                        )");

                        $query = $this->db->last_query();
                        $gia_occupato = $this->db->query("
                                    SELECT 
                                        * 
                                    FROM 
                                        appuntamenti LEFT JOIN projects_orari ON projects_orari_id = appuntamenti_fascia
                                    WHERE 
                                        appuntamenti_associato = '{$associato['associati_id']}'
                                        AND appuntamenti_impianto <> '{$value_id}'
                                        AND appuntamenti_giorno::date = '$dateString'");
                        if ($non_disponibile->num_rows() != 0 || $gia_occupato->num_rows() != 0) {
                            $non_disponibilita = [];
                            foreach ($non_disponibile->result_array() as $disp) {
                                $non_disponibilita[] = "{$disp['disponibilita_associati_dal']} - {$disp['disponibilita_associati_al']}";
                            }
                            foreach ($gia_occupato->result_array() as $occupato) {
                                //debug($occupato,true);
                                $non_disponibilita[] = "{$occupato['projects_orari_dalle']} - {$occupato['projects_orari_alle']}";
                            }
                            $implode = implode(', ', $non_disponibilita);
                            $title .= 'Non disponibile: ' . $implode;
                            if ($non_disponibile->num_rows() != 0) {
                                $td_class = ' occupato';
                            } else {
                                $td_class = ' ';
                            }
                        }


                        //Metto anche le DISPONIBILITA
                        $disponibile = $this->db->query("
                                    SELECT 
                                        * 
                                    FROM 
                                        disponibilita_associati 
                                    WHERE 
                                        disponibilita_associati_associato = '{$associato['associati_id']}'
                                        AND disponibilita_associati_tipo = '2'
                                        AND (disponibilita_associati_dal::date <= '$dateString' AND (
                                                disponibilita_associati_al::date >= '$dateString' 
                                                AND (
                                                    to_char(disponibilita_associati_al, 'HH24:MI:SS') <> '00:00:00'
                                                    OR disponibilita_associati_al::date <> '$dateString'
                                                )
                                            )
                                        )");

                        $query = $this->db->last_query();

                        if ($disponibile->num_rows() != 0) {
                            $disponibilita = [];
                            foreach ($disponibile->result_array() as $disp) {
                                $disponibilita[] = "{$disp['disponibilita_associati_dal']} - {$disp['disponibilita_associati_al']}";
                            }

                            $implode = implode(', ', $disponibilita);
                            $title .= ' **** Disponibile: ' . $implode;

                            $td_class .= ' disponibile';
                        }
                        $tooltip = 'data-toggle="tooltip" title="' . $title . '" ';
                    }
                    ?>

                    <td <?php echo $tooltip; ?>data-associato="<?php echo $associato['associati_id']; ?>" data-giorno="<?php echo $dateString; ?>" class="<?php //echo $bad_days;
                                                                                                                                                            ?><?php echo $td_class; ?>">
                        <div class="pallino1"></div>
                        <div class="pallino2"></div>
                        <?php if (!empty($ore[$day][$associato['associati_id']])) : ?>
                            <?php $ora = $ore[$day][$associato['associati_id']]; ?>

                            <input type="text" data-record="<?php echo $ora['ore_account_id']; ?>" value="<?php echo $ora['ore_account_ore']; ?>" class="form-control input-xs <?php echo $bad_days; ?>" />

                        <?php else : ?>
                            <?php //debug(@$appuntamenti[$dateString][$associato['associati_id']]); 
                            ?>
                            <!--<input type="text" data-record='' class="form-control input-xs"/>-->

                            <?php
                            if (!empty(@$appuntamenti[$dateString][$associato['associati_id']])) {
                                $fascie_impostate = [];
                                foreach ($appuntamenti[$dateString][$associato['associati_id']] as $fascia_id) {
                                    //debug($fascia_id);
                                    if (stripos($fascia_id, '!') !== false) {
                                        $fascia_id = str_ireplace('!', '', $fascia_id);
                                        $fascie_impostate[] = '!' . $fascie_orarie[(int) $fascia_id] . '!';
                                    } elseif (stripos($fascia_id, '**')) {
                                        $fascie_impostate[] = $fascie_orarie[(int) $fascia_id] . '**';
                                    } elseif (stripos($fascia_id, '*')) {
                                        $fascie_impostate[] = $fascie_orarie[(int) $fascia_id] . '*';
                                    } else {
                                        $fascie_impostate[] = $fascie_orarie[$fascia_id];
                                    }
                                }

                                echo '<div class="js-fascie_impostate">' . implode(' ', $fascie_impostate) . '</div>';
                            }
                            ?>

                            <select multiple class="form-control <?php if (empty(@$appuntamenti[$dateString][$associato['associati_id']])) : ?>js_hover_multiselect<?php else : ?>js_hover_multiselect <?php endif; ?> field_293" name="cella[<?php echo $dateString; ?>][<?php echo $associato['associati_id']; ?>]" data-val="<?php echo @implode(',', @$appuntamenti[$dateString][$associato['associati_id']]); ?>" data-ref="appuntamenti" data-source-field="" data-minimum-input-length="0">

                                <?php foreach ($fascie_orarie as $fascia_id => $fascia) : ?>
                                    <?php $fascia_id = (string) $fascia_id; ?>
                                    <option value="<?php echo $fascia_id; ?>" <?php if (@in_array($fascia_id, @$appuntamenti[$dateString][$associato['associati_id']], true)) : ?> selected<?php endif; ?>><?php echo $fascia; ?></option>
                                <?php endforeach; ?>

                                <?php foreach ($fascie_orarie as $fascia_id => $fascia) : ?>
                                    <option value="<?php echo $fascia_id; ?>*" <?php if (@in_array($fascia_id . '*', @$appuntamenti[$dateString][$associato['associati_id']], true)) : ?> selected<?php endif; ?>><?php echo $fascia; ?>*</option>
                                <?php endforeach; ?>

                                <?php foreach ($fascie_orarie as $fascia_id => $fascia) : ?>
                                    <option value="<?php echo $fascia_id; ?>**" <?php if (@in_array($fascia_id . '**', @$appuntamenti[$dateString][$associato['associati_id']], true)) : ?> selected<?php endif; ?>><?php echo $fascia; ?>**</option>
                                <?php endforeach; ?>

                                <?php foreach ($fascie_orarie as $fascia_id => $fascia) : ?>
                                    <?php if (@in_array('!' . $fascia_id . '!', @$appuntamenti[$dateString][$associato['associati_id']], true)) : ?><option value="!<?php echo $fascia_id; ?>!" selected>!<?php echo $fascia; ?>!</option><?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>

                    </td>
                <?php endfor; ?>
                <td class="text-center">
                    <strong>
                        <?php echo number_format((empty($totali_associati[$associato['associati_id']])) ? 0 : $totali_associati[$associato['associati_id']], 2); ?>
                    </strong>
                </td>
            </tr>

        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th class="text-center">Totali (ore)</th>
            <?php for ($day = 1; $day <= $days; $day++) :
                $fullDate = $year . '-' . $month . '-' . $day;
                if (!in_array(date('w', strtotime($fullDate)), $workingDays) || in_array(date('m-d', strtotime($fullDate)), $holidayDays)) {
                    $bad_days = 'btn-danger festività';
                } else {
                    $bad_days = '';
                } ?>

                <th class="text-center <?php echo $bad_days; ?>"><?php echo number_format((empty($totali_giorni[$day])) ? 0 : $totali_giorni[$day], 2); ?></th>
            <?php endfor; ?>
            <th class="text-center"><?php echo number_format($totalone, 2); ?><br />
                di cui <?php echo number_format($totalone_affiancamenti, 2); ?>*
            </th>
        </tr>
        <?php //if ($cliente['clienti_modalita_calendario'] == 2 || in_array($this->auth->get('utenti'), [7,8])) : 
        ?>
        <?php //if ($this->auth->get('utenti_tipo') != 15) : //amministrativo con calendario sola lettura 
        ?>
        <tr>
            <td class="td-associato text-center" colspan="<?php echo 2 + $days; ?>">Richieste</td>
        </tr>
        <?php for ($i = 1; $i <= 4; $i++) : ?>
            <tr class="js-turni_scoperti">
                <td class="td-associato text-center">Riga <?php echo $i; ?></td>
                <?php for ($day = 1; $day <= $days; $day++) : ?>
                    <?php
                    $dateString = date('Y-m-d', strtotime($year . '/' . $month . '/' . $day));

                    foreach (@(array) ($appuntamenti_dati[$dateString][$associato['associati_id']]) as $dati) {
                        if (str_ireplace(':', '', $dati['projects_orari_alle']) < str_ireplace(':', '', $dati['projects_orari_dalle'])) {
                            @$totali_giorni[$day] += (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                            @$totali_associati[$associato['associati_id']] += (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                            $totalone += (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                        } else {
                            @$totali_giorni[$day] += (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                            @$totali_associati[$associato['associati_id']] += (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                            $totalone += (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                        }
                    }
                    ?>
                    <td class="<?php //echo $bad_days; 
                                ?>" data-giorno="<?php echo $dateString; ?>">

                        <select multiple class="form-control <?php if (empty(@$richieste[$dateString][$i])) : ?>js_hover_multiselect<?php else : ?>js_multiselect<?php endif; ?> field_293" name="richiesta_cella[<?php echo $dateString; ?>][<?php echo $i; ?>]" data-val="<?php echo @implode(',', @(array) $richieste[$dateString][$i]); ?>" data-ref="richieste_disponibilita" data-source-field="" data-minimum-input-length="0">

                            <?php foreach ($fascie_orarie as $fascia_id => $fascia) : ?>
                                <?php $fascia_id = (string) $fascia_id; ?>
                                <option value="<?php echo $fascia_id; ?>" <?php if (@in_array($fascia_id, @$richieste[$dateString][$i], true)) : ?> selected<?php endif; ?>><?php echo $fascia; ?></option>
                            <?php endforeach; ?>

                            <?php foreach ($fascie_orarie as $fascia_id => $fascia) : ?>
                                <option value="<?php echo $fascia_id; ?>*" <?php if (@in_array($fascia_id . '*', @$richieste[$dateString][$i], true)) : ?> selected<?php endif; ?>><?php echo $fascia; ?>*</option>
                            <?php endforeach; ?>

                            <?php foreach ($fascie_orarie as $fascia_id => $fascia) : ?>
                                <option value="<?php echo $fascia_id; ?>**" <?php if (@in_array($fascia_id . '**', @$richieste[$dateString][$i], true)) : ?> selected<?php endif; ?>><?php echo $fascia; ?>**</option>
                            <?php endforeach; ?>
                        </select>

                    </td>
                <?php endfor; ?>
                <td class="text-center">
                    <strong>
                        &nbsp;
                    </strong>
                </td>
            </tr>
        <?php endfor; ?>
        <?php //endif; 
        ?>
        <?php //endif; 
        ?>
    </tfoot>
</table>



<div class="row">
    <div class="col-md-4">
        <strong>*&nbsp;&nbsp; Affiancamento</strong><br />
        <strong>** Costo orario differenziato</strong><br />
        <strong>! Fascia oraria cancellata</strong>
    </div>
    <div class="col-md-8">
        <strong>Fascie cancellate</strong>
        <?php
        $fascie_cancellate = $this->db->query("SELECT * FROM projects_orari WHERE 
            projects_orari_cancellato = '1'
            AND projects_orari_id IN (
                SELECT appuntamenti_fascia 
                FROM appuntamenti 
                WHERE 
                    DATE_PART('month', appuntamenti_giorno) = '0{$this->input->get('m')}' 
                    AND DATE_PART('year', appuntamenti_giorno) = '0{$this->input->get('Y')}' 
                    AND (
                        projects_orari_sede = '0{$this->input->get('projects_orari_sede')}' OR 
                        projects_orari_sede = '0{$value_id}' 
                    )
            )")->result_array();
        ?>
        <?php foreach ($fascie_cancellate as $fascia) : ?>
            - <?php echo ($fascia['projects_orari_nome']); ?> (<?php echo ($fascia['projects_orari_dalle']); ?> - <?php echo ($fascia['projects_orari_alle']); ?>)
        <?php endforeach; ?>
    </div>
</div>



<script>
    var table_calendario = $('.js-calendario_sede');



    var table_richieste = $('.js-turni_scoperti');

    <?php if ($this->auth->get('utenti_tipo') == 15) : ?>
        //console.log('TEEEEEEEEEEST');
        table_richieste.find('select').on('click', function(e) {
            e.preventDefault();
            alert('Non sei autorizzato a salvare questa richiesta');
            location.reload();
            return false;
        });
        table_calendario.find('select').on('click', function(e) {
            e.preventDefault();
            alert('Non sei autorizzato a salvare questa richiesta');
            location.reload();
            return false;
        });
    <?php else : ?>
        table_calendario.find('select').on('change', function(e) {
            var select = $(this);
            //console.log(select);
            var fascie = select.val();
            var associato = select.parent().data('associato');
            var giorno = select.parent().data('giorno');
            var sede = <?php echo $value_id; ?>;

            var notity_check = 0;
            if ($('.js-notify_change').is(':checked')) {
                notity_check = 1;
            } else {
                notity_check = 0;
            }

            var data = {
                'appuntamenti_associato': associato,
                'appuntamenti_impianto': sede,
                'appuntamenti_giorno': giorno,
                'appuntamenti_fascie': fascie
            };

            console.log(data);

            var url = base_url + "custom/apib/editSediProfessionisti/" + notity_check;

            $.ajax(url, {
                data,
                success: function(output) {
                    console.log(output)
                },
                dataType: 'json',
                method: 'post',
            });
        });
        table_richieste.find('select').on('change', function(e) {

            var select = $(this);
            var giorno = select.parent().data('giorno');
            var fascie = [];
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][1]"]').val());
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][2]"]').val());
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][3]"]').val());
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][4]"]').val());

            console.log(fascie);
            var sede = <?php echo $value_id; ?>;

            var data = {
                'sede': sede,
                'giorno': giorno,
                'fascie': fascie
            };

            console.log(data);

            var url = base_url + "custom/apib/editRichiesteDisponibilita";

            $.ajax(url, {
                data,
                success: function(output) {
                    console.log(output)
                },
                dataType: 'json',
                method: 'post',
            });
        });
    <?php endif; ?>

    function change_date(container) {
        //alert($('select.js-month', container).val());
        var month = $('select.js-month', container).val();
        var year = $('select.js-year', container).val();

        <?php if ($this->auth->get('utenti_tipo') == 17) : ?>
            location.href = '<?php echo base_url("main/layout/52"); ?>?Y=' + year + '&m=' + month;

        <?php elseif (empty($this->auth->get('clienti_id')) && empty($this->auth->get('projects_id'))) : ?>
            location.href = '<?php echo base_url("main/layout/55/$value_id"); ?>?Y=' + year + '&m=' + month;
        <?php else : ?>
            location.href = '<?php echo base_url("main/dashboard"); ?>?Y=' + year + '&m=' + month;
        <?php endif; ?>
    }
    //cambi anno/mese
    $('.js-change_month').on('click', function() {
        change_date($(this).parent());

    });
    $('.js-stampa').on('click', function() {
        var month = $('select.js-month', $(this).parent()).val();
        var year = $('select.js-year', $(this).parent()).val();

        window.open('<?php echo base_url("custom/apib/stampaCalendarioSede/$value_id"); ?>?Y=' + year + '&m=' + month + '&_regen=1', '_blank');

    });

    $('.js-notifica').on('click', function() {
        var month = $('select.js-month', $(this).parent()).val();
        var year = $('select.js-year', $(this).parent()).val();

        var r = confirm("Sei sicuro di voler notificare a tutti?");
        if (r == true) {
            window.open('<?php echo base_url("custom/apib/notificaCambioCalendarioSede/$value_id"); ?>?Y=' + year + '&m=' + month, '_blank');
        } else {}

    });

    $('.js-month,.js-year').on('change', function() {

        change_date($(this).parent());
    });

    $('.js_refresh').on('click', function() {
        location.reload();
    });

    $('.js-fascie_impostate').on('hover', function() {
        $('.js_hover_multiselect', $(this).parent()).removeClass('js_hover_multiselect').addClass('js_multiselect');
        var that = $('.js_hover_multiselect', $(this).parent());
        var minInput = that.data('minimum-input-length');
        that.select2({
            allowClear: true,
            minimumInputLength: minInput ? minInput : 0
        });
        $(this).hide();

    });

    $('.js_hover_multiselect').on('hover', function() {
        $(this).removeClass('js_hover_multiselect').addClass('js_multiselect');
        var that = $(this);
        var minInput = that.data('minimum-input-length');
        that.select2({
            allowClear: true,
            minimumInputLength: minInput ? minInput : 0
        });
        $('.js-fascie_impostate', that.parent()).hide();

    });
    var container = $('div[container_sede_id=<?php echo $value_id; ?>]');
    $('.select2me', container).select2({
        allowClear: true
    });
    console.log('modalità calendario: <?php echo $this->auth->get('projects_modalita_calendario'); ?>');
    //    $(document).ready(function () {
    //        
    //        try {
    //            $('.js_multiselect:not(.select2-offscreen):not(.select2-container)', container).each(function () {
    //                var that = $(this);
    //                var minInput = that.data('minimum-input-length');
    //                that.select2({allowClear: true, minimumInputLength: minInput ? minInput : 0});
    //            });
    //            $('.select2me', container).select2({allowClear: true});
    //        } catch (e) {
    //        }
    //        
    //        
    //    });
</script>