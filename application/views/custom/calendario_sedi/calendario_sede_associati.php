<?php
//debug($value_id);

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
$dipendenti = $this->apilib->search(
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
            SELECT appuntamenti_fascia_oraria 
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
    ->join('projects_orari', 'projects_orari_id = appuntamenti_fascia_oraria')
    ->join('projects', 'projects_id = appuntamenti_impianto')
    ->where('appuntamenti_impianto', $value_id)
    //->where("projects_orari_cancellato <> '1'")
    ->where("MONTH(appuntamenti_giorno) = '$month'", null, false)
    ->get('rel_appuntamenti_persone')->result_array();
//debug($_appuntamenti,true);

$_richieste = $this->apilib->search('appuntamenti', [
    'appuntamenti_impianto' => $value_id,
    '(appuntamenti_id NOT IN (SELECT appuntamenti_id FROM rel_appuntamenti_persone WHERE appuntamenti_id IS NOT NULL))',
    "YEAR(appuntamenti_giorno) = '{$year}'",
    "MONTH(appuntamenti_giorno) = '{$month}'",
]);

$richieste = [];
foreach ($_richieste as $richiesta) {
    //debug($richiesta,true);
    for ($i = 1; $i <= 4; $i++) {
        if ($richiesta['appuntamenti_affiancamento'] == '1') {
            if (!@in_array($richiesta['appuntamenti_fascia_oraria'] . '*', $richieste[substr($richiesta['appuntamenti_giorno'], 0, 10)][$i], true)) {
                $richieste[substr($richiesta['appuntamenti_giorno'], 0, 10)][$i][] = $richiesta['appuntamenti_fascia_oraria'] . '*';
                break;
            }
        } elseif ($richiesta['appuntamenti_studente'] == '1') {
            if (!@in_array($richiesta['appuntamenti_fascia_oraria'] . '**', $richieste[substr($richiesta['appuntamenti_giorno'], 0, 10)][$i], true)) {
                $richieste[substr($richiesta['appuntamenti_giorno'], 0, 10)][$i][] = $richiesta['appuntamenti_fascia_oraria'] . '**';
                break;
            }
        } else {
            if (!@in_array($richiesta['appuntamenti_fascia_oraria'], $richieste[substr($richiesta['appuntamenti_giorno'], 0, 10)][$i])) {
                $richieste[substr($richiesta['appuntamenti_giorno'], 0, 10)][$i][] = $richiesta['appuntamenti_fascia_oraria'];
                break;
            }
        }
    }
}


$appuntamenti = $appuntamenti_dati = [];
foreach ($_appuntamenti as $sede_professionista) {
    //debug($sede_professionista, true);
    @$appuntamenti_dati[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['dipendenti_id']][] = $sede_professionista;
    if ($sede_professionista['projects_orari_cancellato'] == '1') {
        @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['dipendenti_id']][] = '!' . $sede_professionista['appuntamenti_fascia_oraria'] . '!';
    } else {
        if (!empty($sede_professionista['appuntamenti_affiancamento']) && $sede_professionista['appuntamenti_affiancamento'] == '1') {
            @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['dipendenti_id']][] = $sede_professionista['appuntamenti_fascia_oraria'] . '*';
        } elseif (!empty($sede_professionista['appuntamenti_studente'] ) && $sede_professionista['appuntamenti_studente'] == '1') {
            @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['dipendenti_id']][] = $sede_professionista['appuntamenti_fascia_oraria'] . '**';
        } else {
            //debug($sede_professionista,true);
            @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['dipendenti_id']][] = (string) $sede_professionista['appuntamenti_fascia_oraria'];
        }
    }
}
//debug($appuntamenti,true);
$non_disponibilita_associati = $this->apilib->search(
    'richieste',
    [
        "richieste_user_id IN (
           SELECT dipendenti_id FROM project_members WHERE projects_id = '$value_id'
       )",
        'richieste_tipologia' => [6, 7]
    ]
);
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

    table .select2-container--default .select2-selection--multiple {
        border-color: transparent!important;
        border: 0px!important;
        outline-color: transparent!important;
    }

    table .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: transparent!important;
        border: 0px!important;
        outline-color: transparent!important;
    }

    table .select2-selection .select2-selection--multiple {
        border-color: transparent!important;
        border: 0px!important;
        outline-color: transparent!important;
    }

    .custom_form span.select2-selection__clear {
        display: none;
    }
</style>

<div class="box box-primary">
<div class="row ">
    <div class="col-lg-8 text-center form-inline custom_form">
        <div class="row">
            <div class="col-sm-12 col-lg-3">
                <div style="margin-bottom: 8px;">
                    <select class="form-control select2me year js-year ">
                        <?php for ($i = date('Y') - 2; $i <= date('Y') + 10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php if ($i == $year): ?> selected="selected" <?php endif; ?>>
                                <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="col-sm-12 col-lg-3">
                <div style="margin-bottom: 8px;">
                    <select class="js-month form-control select2me month ">
                        <?php
                        if ($this->auth->get('utenti_tipo') == '16') {
                            $m_start = date('m');
                            $m_end = date('m') + 1;
                        } else {
                            $m_start = 1;
                            $m_end = 12;
                        }
                        ?>
                        <?php for ($m = $m_start; $m <= $m_end; $m++): ?>
                            <option value="<?php echo $m; ?>" <?php if ($m == $month): ?> selected="selected" <?php endif; ?>><?php echo mese_testuale($m); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>
            <div class="col-sm-12 col-lg-6">
                <div style="display: flex; justify-content: center; gap: 8px; margin-bottom: 8px;">
                    <button class="btn js_refresh">Aggiorna</button>
                    <button class="btn js-stampa">Stampa</button>
                    <?php if ($this->auth->is_admin() || in_array($this->auth->get('utenti_tipo'), [7, 8])): ?>
                        <button class="btn red js-notifica">NOTIFICA A TUTTI</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($this->auth->get('utenti_tipo') != 17): ?>
        <div class="col-lg-4">
            <input type="checkbox" class="js-notify_change" />
            <span>Notifica ogni variazione all'associato</span>
        </div>
    <?php endif; ?>
</div>


<div class="row">
    <div class="col-sm-12">
        <div class="table-responsive">
            <table class="table table-striped table-bordered " id="calendario_sede"
                sede_id="<?php echo $sede['projects_id']; ?>">
                <thead>
                    <tr>
                        <th>Associato</th>
                        <?php for ($day = 1; $day <= $days; $day++): ?>
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

                    <?php foreach ($dipendenti as $dipendente): ?>
                        <tr class="js-calendario_sede">
                            <td class="td-associato text-center">
                                <?php echo $dipendente['dipendenti_cognome'] . ' ' . substr($dipendente['dipendenti_nome'], 0, 1); ?>.
                            </td>
                            <?php for ($day = 1; $day <= $days; $day++): ?>
                                <?php
                                $dateString = date('Y-m-d', strtotime($year . '/' . $month . '/' . $day));

                                foreach (@(array) ($appuntamenti_dati[$dateString][$dipendente['dipendenti_id']]) as $dati) {
                                    if (str_ireplace(':', '', $dati['projects_orari_alle']) < str_ireplace(':', '', $dati['projects_orari_dalle'])) {
                                        @$totali_giorni[$day] += (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                                        @$totali_associati[$dipendente['dipendenti_id']] += (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;

                                        $ore = (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;

                                        $totalone += $ore;
                                        if (!empty($dati['appuntamenti_affiancamento']) && $dati['appuntamenti_affiancamento'] == '1') {
                                            $totalone_affiancamenti += $ore;
                                        }
                                    } else {
                                        @$totali_giorni[$day] += (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                                        @$totali_associati[$dipendente['dipendenti_id']] += (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;

                                        $ore = (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;

                                        $totalone += $ore;
                                        if (!empty($dati['appuntamenti_affiancamento']) && $dati['appuntamenti_affiancamento'] == '1') {
                                            $totalone_affiancamenti += $ore;
                                        }
                                    }
                                }
                                $tooltip = $title = $td_class = '';
                                //Verifico eventuali NON disponibilità per questo associato in questo giorno
                                if (!empty($dipendente['dipendenti_id'])) {
                                    $non_disponibile = $this->db->query("
                                    SELECT 
                                        * 
                                    FROM 
                                        richieste 
                                    WHERE 
                                        richieste_tipologia IN (7)
                                        AND richieste_user_id = '{$dipendente['dipendenti_id']}'
                                        
                                        AND (DATE(richieste_dal) <= '$dateString' AND (
                                                DATE(richieste_al) >= '$dateString' 
                                                
                                            )
                                        )");

                                    $query = $this->db->last_query();
                                    $gia_occupato = $this->db->query("
                                    SELECT 
                                        * 
                                    FROM 
                                        appuntamenti 
                                        LEFT JOIN projects_orari ON projects_orari_id = appuntamenti_fascia_oraria
                                    WHERE 
                                        appuntamenti_id IN (SELECT appuntamenti_id FROM rel_appuntamenti_persone WHERE users_id = (SELECT dipendenti_user_id FROM dipendenti WHERE dipendenti_id = '{$dipendente['dipendenti_id']}'))
                                        
                                        AND appuntamenti_impianto <> '{$value_id}'
                                        AND DATE(appuntamenti_giorno) = '$dateString'");
                                    if ($non_disponibile->num_rows() != 0 || $gia_occupato->num_rows() != 0) {
                                        $non_disponibilita = [];
                                        foreach ($non_disponibile->result_array() as $disp) {
                                            $disp['richieste_dal'] = substr($disp['richieste_dal'], 0, 10);
                                            $disp['richieste_al'] = substr($disp['richieste_al'], 0, 10);
                                            $non_disponibilita[] = "{$disp['richieste_dal']} {$disp['richieste_ora_inizio']} - {$disp['richieste_al']} {$disp['richieste_ora_fine']}";
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
                                        richieste 
                                    WHERE 
                                        richieste_tipologia IN (6)
                                        AND richieste_user_id = '{$dipendente['dipendenti_id']}'
                                        
                                        AND (DATE(richieste_dal) <= '$dateString' AND (
                                                DATE(richieste_al) >= '$dateString' 
                                                
                                            )
                                        )");

                                    $query = $this->db->last_query();

                                    if ($disponibile->num_rows() != 0) {
                                        $disponibilita = [];
                                        foreach ($disponibile->result_array() as $disp) {
                                            $disponibilita[] = "{$disp['richieste_dal']} - {$disp['richieste_al']}";
                                        }

                                        $implode = implode(', ', $disponibilita);
                                        $title .= ' **** Disponibile: ' . $implode;

                                        $td_class .= ' disponibile';
                                    }

                                    $tooltip = 'data-toggle="tooltip" title="' . $title . '" ';
                                }
                                ?>

                                <td <?php echo $tooltip; ?>data-associato="<?php echo $dipendente['dipendenti_id']; ?>"
                                    data-giorno="<?php echo $dateString; ?>"
                                    class="<?php //echo $bad_days;
                                            ?><?php echo $td_class; ?>">
                                    <div class="pallino1"></div>
                                    <div class="pallino2"></div>
                                    <?php if (!empty($ore[$day][$dipendente['dipendenti_id']])): ?>
                                        <?php $ora = $ore[$day][$dipendente['dipendenti_id']]; ?>

                                        <input type="text" data-record="<?php echo $ora['ore_account_id']; ?>"
                                            value="<?php echo $ora['ore_account_ore']; ?>"
                                            class="form-control input-xs <?php echo $bad_days; ?>" />

                                    <?php else: ?>
                                        <?php //debug(@$appuntamenti[$dateString][$dipendente['dipendenti_id']]); 
                                                    ?>
                                        <!--<input type="text" data-record='' class="form-control input-xs"/>-->

                                        <?php
                                        if (!empty(@$appuntamenti[$dateString][$dipendente['dipendenti_id']])) {
                                            $fascie_impostate = [];
                                            foreach ($appuntamenti[$dateString][$dipendente['dipendenti_id']] as $fascia_id) {
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

                                        <select multiple
                                            class="form-control <?php if (empty(@$appuntamenti[$dateString][$dipendente['dipendenti_id']])): ?>js_hover_multiselect<?php else: ?>js_hover_multiselect <?php endif; ?> field_293 __select2me"
                                            name="cella[<?php echo $dateString; ?>][<?php echo $dipendente['dipendenti_id']; ?>]"
                                            data-val="<?php echo @implode(',', @$appuntamenti[$dateString][$dipendente['dipendenti_id']]); ?>"
                                            data-ref="appuntamenti" data-source-field="" data-minimum-input-length="0">

                                            <?php foreach ($fascie_orarie as $fascia_id => $fascia): ?>
                                                <?php $fascia_id = (string) $fascia_id; ?>
                                                <option value="<?php echo $fascia_id; ?>" <?php if (@in_array($fascia_id, @$appuntamenti[$dateString][$dipendente['dipendenti_id']], true)): ?> selected<?php endif; ?>><?php echo $fascia; ?></option>
                                            <?php endforeach; ?>

                                            <?php foreach ($fascie_orarie as $fascia_id => $fascia): ?>
                                                <option value="<?php echo $fascia_id; ?>*" <?php if (@in_array($fascia_id . '*', @$appuntamenti[$dateString][$dipendente['dipendenti_id']], true)): ?> selected<?php endif; ?>><?php echo $fascia; ?>*</option>
                                            <?php endforeach; ?>

                                            <?php foreach ($fascie_orarie as $fascia_id => $fascia): ?>
                                                <option value="<?php echo $fascia_id; ?>**" <?php if (@in_array($fascia_id . '**', @$appuntamenti[$dateString][$dipendente['dipendenti_id']], true)): ?> selected<?php endif; ?>><?php echo $fascia; ?>**</option>
                                            <?php endforeach; ?>

                                            <?php foreach ($fascie_orarie as $fascia_id => $fascia): ?>
                                                <?php if (@in_array('!' . $fascia_id . '!', @$appuntamenti[$dateString][$dipendente['dipendenti_id']], true)): ?>
                                                    <option value="!<?php echo $fascia_id; ?>!" selected>!<?php echo $fascia; ?>!</option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>

                                </td>
                            <?php endfor; ?>
                            <td class="text-center">
                                <strong>
                                    <?php echo number_format((empty($totali_associati[$dipendente['dipendenti_id']])) ? 0 : $totali_associati[$dipendente['dipendenti_id']], 2); ?>
                                </strong>
                            </td>
                        </tr>

                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th class="text-center">Totali (ore)</th>
                        <?php for ($day = 1; $day <= $days; $day++):
                            $fullDate = $year . '-' . $month . '-' . $day;
                            if (!in_array(date('w', strtotime($fullDate)), $workingDays) || in_array(date('m-d', strtotime($fullDate)), $holidayDays)) {
                                $bad_days = 'btn-danger festività';
                            } else {
                                $bad_days = '';
                            } ?>

                            <th class="text-center <?php echo $bad_days; ?>">
                                <?php echo number_format((empty($totali_giorni[$day])) ? 0 : $totali_giorni[$day], 2); ?>
                            </th>
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
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <tr class="js-turni_scoperti">
                            <td class="td-associato text-center">Riga <?php echo $i; ?></td>
                            <?php for ($day = 1; $day <= $days; $day++): ?>
                                <?php
                                $dateString = date('Y-m-d', strtotime($year . '/' . $month . '/' . $day));

                                foreach (@(array) ($appuntamenti_dati[$dateString][$dipendente['dipendenti_id']]) as $dati) {
                                    if (str_ireplace(':', '', $dati['projects_orari_alle']) < str_ireplace(':', '', $dati['projects_orari_dalle'])) {
                                        @$totali_giorni[$day] += (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                                        @$totali_associati[$dipendente['dipendenti_id']] += (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                                        $totalone += (strtotime('2016-01-02 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                                    } else {
                                        @$totali_giorni[$day] += (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                                        @$totali_associati[$dipendente['dipendenti_id']] += (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                                        $totalone += (strtotime('2016-01-01 ' . $dati['projects_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['projects_orari_dalle'] . ':00')) / 3600;
                                    }
                                }
                                ?>
                                <td class="<?php //echo $bad_days; 
                                        ?>" data-giorno="<?php echo $dateString; ?>">

                                    <select multiple
                                        class="form-control <?php if (empty(@$richieste[$dateString][$i])): ?>js_hover_multiselect<?php else: ?>js_multiselect<?php endif; ?> field_293"
                                        name="richiesta_cella[<?php echo $dateString; ?>][<?php echo $i; ?>]"
                                        data-val="<?php echo @implode(',', @(array) $richieste[$dateString][$i]); ?>"
                                        data-ref="appuntamenti" data-source-field="" data-minimum-input-length="0">

                                        <?php foreach ($fascie_orarie as $fascia_id => $fascia): ?>
                                            <?php $fascia_id = (string) $fascia_id; ?>
                                            <option value="<?php echo $fascia_id; ?>" <?php if (@in_array($fascia_id, @$richieste[$dateString][$i], true)): ?> selected<?php endif; ?>>
                                                <?php echo $fascia; ?></option>
                                        <?php endforeach; ?>

                                        <?php foreach ($fascie_orarie as $fascia_id => $fascia): ?>
                                            <option value="<?php echo $fascia_id; ?>*" <?php if (@in_array($fascia_id . '*', @$richieste[$dateString][$i], true)): ?> selected<?php endif; ?>>
                                                <?php echo $fascia; ?>*</option>
                                        <?php endforeach; ?>

                                        <?php foreach ($fascie_orarie as $fascia_id => $fascia): ?>
                                            <option value="<?php echo $fascia_id; ?>**" <?php if (@in_array($fascia_id . '**', @$richieste[$dateString][$i], true)): ?> selected<?php endif; ?>>
                                                <?php echo $fascia; ?>**</option>
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
        </div>
    </div>
</div>



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
                SELECT appuntamenti_fascia_oraria 
                FROM appuntamenti 
                WHERE 
                    MONTH(appuntamenti_giorno) = '0{$this->input->get('m')}' 
                    AND YEAR(appuntamenti_giorno) = '0{$this->input->get('Y')}' 
                    AND (
                        projects_orari_project = '0{$this->input->get('projects_orari_sede')}' OR 
                        projects_orari_project = '0{$value_id}' 
                    )
            )")->result_array();
        ?>
        <?php foreach ($fascie_cancellate as $fascia): ?>
            - <?php echo ($fascia['projects_orari_nome']); ?> (<?php echo ($fascia['projects_orari_dalle']); ?> -
            <?php echo ($fascia['projects_orari_alle']); ?>)
        <?php endforeach; ?>
    </div>
</div>

</div>

<script>
    var table_calendario = $('.js-calendario_sede');



    var table_richieste = $('.js-turni_scoperti');

    <?php if ($this->auth->get('utenti_tipo') == 15): ?>
        //console.log('TEEEEEEEEEEST');
        table_richieste.find('select').on('click', function (e) {
            e.preventDefault();
            alert('Non sei autorizzato a salvare questa richiesta');
            location.reload();
            return false;
        });
        table_calendario.find('select').on('click', function (e) {
            e.preventDefault();
            alert('Non sei autorizzato a salvare questa richiesta');
            location.reload();
            return false;
        });
    <?php else: ?>
        table_calendario.find('select').on('change', function (e) {
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
                'dipendenti_id': associato,
                'appuntamenti_impianto': sede,
                'appuntamenti_giorno': giorno,
                'appuntamenti_fascie': fascie
            };

            console.log(data);

            var url = base_url + "custom/apib/editSediProfessionisti/" + notity_check;

            $.ajax(url, {
                data,
                success: function (output) {
                    console.log(output)
                },
                dataType: 'json',
                method: 'post',
            });
        });
        table_richieste.find('select').on('change', function (e) {

            var select = $(this);
            var giorno = select.parent().data('giorno');
            var fascie = [];
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][1]"]').val());
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][2]"]').val());
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][3]"]').val());
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][4]"]').val());

            console.log(fascie);
            var sede = <?php echo $value_id; ?>;

            var postData = {
                'sede': sede,
                'giorno': giorno,
                'fascie[]': fascie
            };

            console.log(postData);

            var url = base_url + "custom/apib/editRichiesteDisponibilita";

            $.ajax({
                url: url,
                dataType: 'json',
                type: 'POST',
                data: $.param(postData),
                success: function (output) {
                    console.log(output);
                },
            })
            
            // $.ajax(url, {
            //     data,
            //     success: function (output) {
            //         console.log(output)
            //     },
            //     dataType: 'json',
            //     method: 'post',
            // });
        });
    <?php endif; ?>

    function change_date(container) {
        //alert($('select.js-month', container).val());
        var month = $('select.js-month', container).val();
        var year = $('select.js-year', container).val();
        
        // alert(month);
        // alert(year);
        // return;

        // Prendi l'URL corrente
        var currentUrl = window.location.href;
        
        // Crea un oggetto URL per manipolare facilmente i parametri
        var url = new URL(currentUrl);
        
        // Aggiorna o aggiungi i parametri
        url.searchParams.set('Y', year);
        url.searchParams.set('m', month);
        
        // Reindirizza alla nuova URL
        location.href = url.toString();
    }
    
    $('.js-stampa').on('click', function () {
        var month = $('select.js-month', $(this).parents('.row').first()).val();
        var year = $('select.js-year', $(this).parents('.row').first()).val();

        window.open('<?php echo base_url("custom/apib/stampaCalendarioSede/$value_id"); ?>?Y=' + year + '&m=' + month + '&_regen=1', '_blank');

    });

    $('.js-notifica').on('click', function () {
        var month = $('select.js-month', $(this).parents('.row').first()).val();
        var year = $('select.js-year', $(this).parents('.row').first()).val();

        var r = confirm("Sei sicuro di voler notificare a tutti?");
        if (r == true) {
            window.open('<?php echo base_url("custom/apib/notificaCambioCalendarioSede/$value_id"); ?>?Y=' + year + '&m=' + month, '_blank');
        } else { }

    });

    $('.js-month,.js-year').on('change', function () {

       change_date($(this).parents('.row').first());
    });

    $('.js_refresh').on('click', function () {
        location.reload();
    });

    $('.js-fascie_impostate').on('hover', function () {
        $('.js_hover_multiselect', $(this).parent()).removeClass('js_hover_multiselect').addClass('js_multiselect');
        var that = $('.js_hover_multiselect', $(this).parent());
        var minInput = that.data('minimum-input-length');
        alert(2);
        that.select2({
            allowClear: false,
            minimumInputLength: minInput ? minInput : 0
        });
        $(this).hide();

    });

    $('.js_hover_multiselect').on('mouseenter', function () {
        $(this).removeClass('js_hover_multiselect').addClass('js_multiselect');
        //debugger
        var that = $(this);
        var minInput = that.data('minimum-input-length');
        that.select2({
            allowClear: false,
            minimumInputLength: minInput ? minInput : 0
        });
        $('.js-fascie_impostate', that.parent()).hide();

    });
    var container = $('div[container_sede_id=<?php echo $value_id; ?>]');

    // $('.js_hover_multiselect', container).select2({
    //     allowClear: true
    // });
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
