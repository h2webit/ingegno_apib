<!DOCTYPE HTML>
<html>
    <head>

        <link href='http://fonts.googleapis.com/css?family=Open+Sans' rel='stylesheet' type='text/css'>
        <link rel="stylesheet" href="<?php echo base_url_template('template/crm-v2/assets/global/plugins/bootstrap/css/bootstrap.min.css'); ?>">
        <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
        <style>
            html, body, p, * {
                font-family: 'Open Sans', sans-serif;
            }
        </style>
    </head>
    <body>
        <?php
$workingDays = [1, 2, 3, 4, 5, 6]; # date format = N (1 = Monday, for more information see )
$holidayDays = HOLIDAYS; /*[
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
    '04-02', //Pasqua
]; # variable and fixed holidays*/

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
$year = ($this->input->get('Y')) ? $this->input->get('Y') : date('Y');
$month = ($this->input->get('m')) ? $this->input->get('m') : date('m');
$days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
$associati = $this->apilib->search('associati', [
    "associati_id IN (
    	SELECT domiciliari_turni_associato FROM domiciliari_turni WHERE domiciliari_turni_domiciliare = '$value_id'
    )",
    //"associati_non_attivo = 'f'"
    ]
);

$legenda = [];

$lettere = range('A', 'Z');

foreach ($turni as $turno) {
    $dalle = substr($turno['domiciliari_turni_inizio'], 11, 5);
    $alle = substr($turno['domiciliari_turni_fine'], 11, 5);
    if (!array_key_exists($dalle.'-'.$alle, $legenda)) {
        $legenda[$dalle.'-'.$alle] = array_shift($lettere);
    }    
}

//debug($legenda,true);

$totalone = 0;

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

    .select2-search-choice-close:hover{
        cursor:not-allowed;
    }

    .year{
        position:relative;
        left: 37%
    }

    .month{
        position:relative;
        left: 40%
    }

    .custom_form{
        padding-bottom: 1.5em; 
    }

    .festività > ul.select2-choices{
        background-color: #F3565E;
    }

    .festività > ul.select2-choices > li.select2-search-choice {
        background-color: transparent;
    }
    hr {
        display:none;
    }
    * {
        font-size: 10px;
    }
    .table > tbody > tr > td, .table > tbody > tr > th, .table > tfoot > tr > td, .table > tfoot > tr > th, .table > thead > tr > td, .table > thead > tr > th {
        text-align: center;
        padding: 4px;
        width:10px;
    }
    td.occupato {
        background-color: #edd968 !important;
    }
</style>

<div class="row">
    <div class="col-md-4" style="font-size:1.2em;">
    <strong>A.P.I.B. S.R.L. S.T.P.</strong><br />
    Via Paolo Fabbri 1/2 - 40138 Bologna<br />
    Tel. 051/272603 / Cell. 335383498 / Fax. 051/9911961<br />
    <br />
    C.F. e P.I. 04120871209<br />
    Email - info@apibstp.com
    <br />
    Referente:
</div>
    <div class="col-md-4" >
        <center style="font-size:3em!important;"><?php echo $months[$month]; ?> <?php echo $year; ?></center>
    </div>
    <div class="col-md-4">
        <?php
            $layoutEntityData = [];
            $grid = $this->datab->get_grid(103);
            $grid_layout = $grid['grids']['grids_layout'];
            $grid_data['data'] = $this->datab->get_grid_data($grid, ['value_id' => $value_id, 'additional_data' => $layoutEntityData]);
            echo $this->load->view("pages/layouts/grids/{$grid_layout}", array('grid' => $grid, 'sub_grid' => null, 'grid_data' => $grid_data, 'value_id' => $value_id, 'layout_data_detail' => $layoutEntityData), true);

        ?>
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

        <?php foreach ($associati as $associato) : ?>
            <tr>
                <td class="td-associato text-center"><?php echo "{$associato['associati_nome']} {$associato['associati_cognome']}"; ?></td>
                <?php for ($day = 1; $day <= $days; $day++) : ?>
                    <?php
                    $dateString = date('Y-m-d', strtotime($year . '/' . $month . '/' . $day));
                    ?>
                    <td class="<?php echo @$td_class;?>">
                        <strong>
                        <?php foreach($turni as $turno): ?>
                        
                            <?php if ($associato['associati_id'] == $turno['domiciliari_turni_associato'] && stripos($turno['domiciliari_turni_inizio'], $dateString) !== false) : ?>
                                <?php 
                                    $dalle = substr($turno['domiciliari_turni_inizio'], 11, 5);
                                    $alle = substr($turno['domiciliari_turni_fine'], 11, 5);
                                    
                                    echo $legenda[$dalle.'-'.$alle];
                                ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </strong>
                    </td>
                <?php endfor; ?>
                <td class="text-center">
                    <strong>
                        <?php echo (empty($totali_associati[$associato['associati_id']])) ? 0 : $totali_associati[$associato['associati_id']]; ?>
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
                <th class="text-center <?php echo $bad_days; ?>"><?php echo (empty($totali_giorni[$day])) ? 0 : $totali_giorni[$day]; ?></th>
            <?php endfor; ?>
            <th class="text-center"><?php echo $totalone; ?></th>
        </tr>

        <tr>
            <td class="td-associato text-center" colspan="<?php echo 2 + $days; ?>">Richieste</td>
        </tr>
        
    </tfoot>
</table>


<div class="row">
    
    <div class="col-md-4">
        <table class="table">
            <?php foreach ($legenda as $ora => $fascia): ?>
            <tr>
                <td><strong><?php echo $fascia; ?></strong></td>
                <td><?php echo $ora; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="col-md-4">
        <strong>*&nbsp; Affiancamento</strong><br />
        <strong>** Costo orario differenziato</strong>
    </div>
</div>




    </body>
</html>