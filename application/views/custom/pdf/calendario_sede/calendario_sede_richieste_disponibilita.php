<?php
$workingDays = [1, 2, 3, 4, 5, 6]; # date format = N (1 = Monday, for more information see )
$festivita = $this->apilib->search('festivita');
$holidayDays = [];
foreach ($festivita as $fest) {
    $holidayDays[] = date('m-d', strtotime($fest['festivita_data']));
}

$months = [
    1 => 'Gennaio',
    2 => 'Febbraio',
    3 => 'Marzo',
    4 => 'Aprile',
    5 => 'Maggio',
    6 => 'Giugno',
    7 => 'Luglio',
    8 => 'Agosto',
    9 => 'Settembre',
    10 => 'Ottobre',
    11 => 'Novembre',
    12 => 'Dicembre',
];

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

// $_richieste = $this->apilib->search('richieste_disponibilita', [
//     'richieste_disponibilita_sede_operativa' => $value_id,
//     'richieste_disponibilita_turno_assegnato IS NULL',
//     "date_part('year', richieste_disponibilita_giorno) = '{$year}'",
//     "date_part('month', richieste_disponibilita_giorno) = '{$month}'",
// ]);
$_richieste = [];
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
    @$appuntamenti_dati[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['dipendenti_id']][] = $sede_professionista;
    if ($sede_professionista['projects_orari_cancellato'] == '1') {
        @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['dipendenti_id']][] = '!' . $sede_professionista['appuntamenti_fascia_oraria'] . '!';
    } else {
        if (!empty($sede_professionista['appuntamenti_affiancamento']) && $sede_professionista['appuntamenti_affiancamento'] == '1') {
            @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['dipendenti_id']][] = $sede_professionista['appuntamenti_fascia_oraria'] . '*';
        } elseif (!empty($sede_professionista['appuntamenti_studente']) && $sede_professionista['appuntamenti_studente'] == '1') {
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
//debug($sede,true);
?>

<style>
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

    hr {
        display: none;
    }

    * {
        font-size: 10px;
    }

    .table>tbody>tr>td,
    .table>tbody>tr>th,
    .table>tfoot>tr>td,
    .table>tfoot>tr>th,
    .table>thead>tr>td,
    .table>thead>tr>th {
        text-align: center;
        padding: 4px;
        width: 10px;
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

    .table-bordered>tbody>tr>td,
    .table-bordered>tbody>tr>th,
    .table-bordered>tfoot>tr>td,
    .table-bordered>tfoot>tr>th,
    .table-bordered>thead>tr>td,
    .table-bordered>thead>tr>th {
        border: 1px solid #000;
    }

    .table>caption+thead>tr:first-child>td,
    .table>caption+thead>tr:first-child>th,
    .table>colgroup+thead>tr:first-child>td,
    .table>colgroup+thead>tr:first-child>th,
    .table>thead:first-child>tr:first-child>td,
    .table>thead:first-child>tr:first-child>th {
        border-top: 1px solid #000;
    }

    .calendario_sede td {
        font-size: 12px;
        color: #000;
    }

    * {
        color: #000;
    }

    dd.js-grid-field-182,
    dd.js-grid-field-224 {
        font-weight: bolder;
        font-size: 1.2em;
        background-color: #EEE;
    }

    td.td-associato {
        font-size: 0.8em !important;
    }
</style>

<div class="row">
    <div class="col-md-4" style="font-size:1em;">
        <strong>A.P.I.B. S.R.L. S.T.P.</strong><br />
        Via Paolo Fabbri 1/2 - 40138 Bologna<br />
        Tel. 051/272603 / Cell. 335383498 / Fax. 051/9911961<br />
        <br />
        C.F. e P.I. 04120871209<br />
        Email - info@apibstp.com
        <br />
        Referente: <?php echo $sede['projects_name']; ?>
    </div>
    <div class="col-md-3">
        <?php //var_dump(((int)$month)); 
        ?>
        <center style="font-size:3em!important;"><?php echo $months[((int) $month)]; ?> <?php echo $year; ?></center>
    </div>
    <div class="col-md-5">
        <?php
        $customer = $this->apilib->view('customers', $sede['projects_customer_id']);
        //debug($grid_data,true);


        //die($this->load->view("pages/layouts/grids/{$grid_layout}", array('grid' => $grid, 'sub_grid' => null, 'grid_data' => $grid_data, 'value_id' => $value_id, 'layout_data_detail' => $layoutEntityData), true));

        //echo $this->load->view("pages/layouts/grids/{$grid_layout}", array('grid' => $grid, 'sub_grid' => null, 'grid_data' => $grid_data, 'value_id' => $value_id, 'layout_data_detail' => $layoutEntityData), true);
        ?>
        <dl id='grid_95' data-id="176" class="dl-horizontal dl-horizontal-compact static-vertical-grid ">
            <dt class="js-grid-field-182">Ragione sociale:</dt>
            <dd class="js-grid-field-182"><?php echo $customer['customers_full_name']; ?></dd>
            <hr>
            <dt class="js-grid-field-193">Indirizzo:</dt>
            <dd class="js-grid-field-193"><?php echo $customer['customers_address']; ?></dd>
            <hr>
            <dt class="js-grid-field-194">Citta:</dt>
            <dd class="js-grid-field-194"><?php echo $customer['customers_city']; ?></dd>
            <hr>
            <dt class="js-grid-field-224">Reparto:</dt>
            <dd class="js-grid-field-224"><?php echo $sede['projects_name']; ?></dd>
            <hr>
            <dt class="js-grid-field-599">Note:</dt>
            <dd class="js-grid-field-599">
                <div class="text_4c3d7bfb3b8a8abef9010e43ee79e78a" style="white-space: pre-line"><?php echo $customer['customers_notes']; ?></div>
            </dd>
            <hr>
        </dl>
    </div>
</div>

<table class="table table-striped table-bordered calendario_sede">
    <thead>
        <tr>
            <th>Associato</th>

            <?php for ($day = 1; $day <= $days; $day++) : ?>
                <?php
                $fullDate = $year . '-' . $month . '-' . $day;
                if (!in_array(date('w', strtotime($fullDate)), $workingDays) || in_array(date('m-d', strtotime($fullDate)), $holidayDays)) {
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
            <tr>
                <td class="td-associato text-center">
                    <?php echo $dipendente['dipendenti_cognome'] . ' ' . substr($dipendente['dipendenti_nome'], 0, 1); ?>.
                </td>
                <?php for ($day = 1; $day <= $days; $day++) : ?>
                    <?php
                    $dateString = date('Y-m-d', strtotime($year . '/' . $month . '/' . $day));

                    foreach (@(array) ($sedi_professionisti_dati[$dateString][$dipendente['dipendenti_id']]) as $dati) {

                        if (str_ireplace(':', '', $dati['sedi_operative_orari_alle']) < str_ireplace(':', '', $dati['sedi_operative_orari_dalle'])) {
                            $dati['sedi_operative_orari_dalle'] = '2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00';
                            $dati['sedi_operative_orari_alle'] = '2016-01-02 ' . $dati['sedi_operative_orari_alle'] . ':00';
                        } else {
                            $dati['sedi_operative_orari_dalle'] = '2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00';
                            $dati['sedi_operative_orari_alle'] = '2016-01-01 ' . $dati['sedi_operative_orari_alle'] . ':00';
                        }
                        $ore = differenza_in_ore_float($dati['sedi_operative_orari_dalle'], $dati['sedi_operative_orari_alle']);
                        @$totali_giorni[$day] += $ore;
                        @$totali_associati[$dipendente['dipendenti_id']] += $ore;

                        $totalone += $ore;

                        if ($dati['sedi_professionisti_affiancamento'] == 't') {
                            $totalone_affiancamenti += $ore;
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
                    <td class="<?php echo $td_class; ?>">
                        <div class="pallino1"></div>
                        <div class="pallino2"></div>
                        <?php foreach (@(array) $sedi_professionisti[$dateString][$dipendente['dipendenti_id']] as $fascia_id) : ?>
                            <?php echo @$fascie_orarie[$fascia_id]; ?>
                        <?php endforeach; ?>
                    </td>
                <?php endfor; ?>
                <td class="text-center">
                    <strong>
                        <?php echo (empty($totali_associati[$dipendente['dipendenti_id']])) ? 0 : number_format($totali_associati[$dipendente['dipendenti_id']], 2); ?>
                    </strong>
                </td>
            </tr>

        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <th class="text-center">Totali (ore)</th>
            <?php
            for ($day = 1; $day <= $days; $day++) :
                $fullDate = $year . '-' . $month . '-' . $day;
                if (!in_array(date('w', strtotime($fullDate)), $workingDays) || in_array(date('m-d', strtotime($fullDate)), $holidayDays)) {
                    $bad_days = 'btn-danger festività';
                } else {
                    $bad_days = '';
                }
            ?>
                <th class="text-center <?php echo $bad_days; ?>"><?php echo (empty($totali_giorni[$day])) ? 0 : number_format($totali_giorni[$day], 2); ?></th>
            <?php endfor; ?>
            <th class="text-center">
                <?php echo number_format($totalone, 2); ?>
                di cui <?php echo number_format($totalone_affiancamenti, 2); ?>*
            </th>
        </tr>

        <tr>
            <td class="td-associato text-center" colspan="<?php echo 2 + $days; ?>">Richieste</td>
        </tr>
        <?php for ($i = 1; $i <= 4; $i++) : ?>
            <tr class="js-turni_scoperti">
                <td class="td-associato text-center">Riga <?php echo $i; ?></td>
                <?php for ($day = 1; $day <= $days; $day++) : ?>
                    <?php
                    $dateString = date('Y-m-d', strtotime($year . '/' . $month . '/' . $day));

                    foreach (@(array) ($sedi_professionisti_dati[$dateString][$dipendente['dipendenti_id']]) as $dati) {
                        if (str_ireplace(':', '', $dati['sedi_operative_orari_alle']) < str_ireplace(':', '', $dati['sedi_operative_orari_dalle'])) {
                            @$totali_giorni[$day] += (strtotime('2016-01-02 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            @$totali_associati[$dipendente['dipendenti_id']] += (strtotime('2016-01-02 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            $totalone += (strtotime('2016-01-02 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                        } else {
                            @$totali_giorni[$day] += (strtotime('2016-01-01 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            @$totali_associati[$dipendente['dipendenti_id']] += (strtotime('2016-01-01 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            $totalone += (strtotime('2016-01-01 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                        }
                    }
                    ?>
                    <td class="<?php //echo $bad_days; 
                                ?>" data-giorno="<?php echo $dateString; ?>">
                        <?php
                        $labels = [];
                        foreach (@(array) $richieste[$dateString][$i] as $rich) {
                            $labels[] = @$fascie_orarie[$rich];
                        }
                        ?>
                        <?php echo implode(',', $labels); ?>

                    </td>
                <?php endfor; ?>
                <td class="text-center">
                    <strong>
                        &nbsp;
                    </strong>
                </td>
            </tr>
        <?php endfor; ?>
    </tfoot>
</table>


<div class="row">

    <div class="col-md-4">
        <?php
        $layoutEntityData = [];
        $grid = $this->datab->get_grid(96);
        $grid_layout = $grid['grids']['grids_layout'];
        $grid_data['data'] = $this->datab->get_grid_data($grid, empty($layoutEntityData) ? $sede['projects_id'] : ['value_id' => $value_id, 'additional_data' => $layoutEntityData]);
        echo $this->load->view("pages/layouts/grids/{$grid_layout}", array('grid' => $grid, 'sub_grid' => null, 'grid_data' => $grid_data, 'value_id' => $value_id, 'layout_data_detail' => $layoutEntityData), true);
        ?>
    </div>
    <div class="col-md-4">
        <strong>*&nbsp; Affiancamento</strong><br />
        <strong>** Costo orario differenziato</strong>
    </div>
</div>


<script>
    function change_date(container) {
        var month = $('select.js-month', container).val();
        var year = $('select.js-year', container).val();
        <?php if ($this->auth->get('utenti_tipo') == 17) : ?>
            location.href = '<?php echo base_url("main/layout/52"); ?>?Y=' + year + '&m=' + month;
        <?php else : ?>
            location.href = '<?php echo base_url("main/dashboard"); ?>?Y=' + year + '&m=' + month;
        <?php endif; ?>
    }

    //cambi anno/mese
    $('.js-change_month').on('click', function() {
        change_date($(this).parent());

    });
    $('.js-month,.js-year').on('change', function() {
        change_date($(this).parent());
    });

    var table_richieste = $('.js-turni_scoperti');

    table_richieste.find('select').on('change', function(e) {

        var select = $(this);
        var giorno = select.parent().data('giorno');
        var fascie = [];
        fascie.push($('select[name="richiesta_cella[' + giorno + '][1]"]').val());
        fascie.push($('select[name="richiesta_cella[' + giorno + '][2]"]').val());
        fascie.push($('select[name="richiesta_cella[' + giorno + '][3]"]').val());
        fascie.push($('select[name="richiesta_cella[' + giorno + '][4]"]').val());


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
</script>