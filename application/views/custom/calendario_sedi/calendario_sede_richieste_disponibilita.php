<?php
$sede = $this->apilib->view('sedi_operative', $value_id);
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
    'associati',
    [
        "associati_id IN (
    	SELECT appuntamenti_associato FROM appuntamenti WHERE appuntamenti_impianto = '$value_id' AND appuntamenti_giorno >= '$year-$month-01'::timestamp AND appuntamenti_giorno <= '$year-$month-$days'::timestamp
    ) OR associati_id IN (
    	SELECT associati_id FROM sedi_operative_associati WHERE sedi_operative_id = '$value_id'
    )",
        //"associati_non_attivo = '0'"
    ],
    0,
    null,
    'associati_cognome'
);
//Aggiungo l'associato fittizio "TURNI SCOPERTI" con id null
//$associati[] = [
//    'associati_nome' => 'TURNI',
//    'associati_cognome' => 'SCOPERTI',
//    'associati_id' => null,
//];
$ore = [];

$_fascie_orarie = $this->apilib->search('sedi_operative_orari', [
    'sedi_operative_orari_sede' => $value_id
], null, 0, 'sedi_operative_orari_dalle');
$fascie_orarie = [];
foreach ($_fascie_orarie as $fascia) {
    $fascie_orarie[$fascia['sedi_operative_orari_id']] = $fascia['sedi_operative_orari_nome'];
}
//debug($fascie_orarie,true);
//$_appuntamenti = $this->apilib->search('appuntamenti', [
//    'appuntamenti_impianto' => $value_id,
//        ]);

$_appuntamenti = $this->db
    ->join('associati', 'associati_id = appuntamenti_associato')
    ->join('sedi_operative_orari', 'sedi_operative_orari_id = appuntamenti_fascia')
    ->join('sedi_operative', 'sedi_operative_id = appuntamenti_impianto')
    ->where('appuntamenti_impianto', $value_id)
    ->where("date_part('month', appuntamenti_giorno) = '$month'", null, false)
    ->get('appuntamenti')->result_array();

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
        if (!@in_array($richiesta['richieste_disponibilita_fascia'], $richieste[substr($richiesta['richieste_disponibilita_giorno'], 0, 10)][$i])) {
            $richieste[substr($richiesta['richieste_disponibilita_giorno'], 0, 10)][$i][] = $richiesta['richieste_disponibilita_fascia'];
            break;
        }
    }
}

//debug($richieste,true);

$appuntamenti = $appuntamenti_dati = [];
foreach ($_appuntamenti as $sede_professionista) {
    @$appuntamenti_dati[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['appuntamenti_associato']][] = $sede_professionista;
    if ($sede_professionista['appuntamenti_affiancamento'] == '1') {
        @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['appuntamenti_associato']][] = $sede_professionista['appuntamenti_fascia'] . '*';
    } elseif ($sede_professionista['appuntamenti_studente'] == '1') {
        @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['appuntamenti_associato']][] = $sede_professionista['appuntamenti_fascia'] . '**';
    } else {
        @$appuntamenti[substr($sede_professionista['appuntamenti_giorno'], 0, 10)][$sede_professionista['appuntamenti_associato']][] = $sede_professionista['appuntamenti_fascia'];
    }
}

//$non_disponibilita_associati = $this->apilib->search('disponibilita_associati', [
//        "associati_id IN (
//            SELECT associati_id FROM sedi_operative_associati WHERE sedi_operative_id = '$value_id'
//        )",
//    ]
//);
//debug($non_disponibilita_associati,true);

$totali_giorni = $totali_associati = [];

$totalone = 0;
?>

<style>
    .td-associato {

        overflow: hidden;
        white-space: nowrap;

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

<div class="col-lg-12 text-center form-inline custom_form">
    <select class="form-control select2me col-lg-4 year js-year">
        <?php for ($i = date('Y') - 2; $i <= date('Y') + 10; $i++) : ?>
            <option value="<?php echo $i; ?>" <?php if ($i == $year) : ?> selected="selected" <?php endif; ?>><?php echo $i; ?></option>
        <?php endfor; ?>
    </select>
    <select class="form-control select2me col-lg-4 month js-month">
        <?php
        if ($this->auth->get('utenti_tipo') == 17) { //Associato responsabile sede
            $modalita = $this->apilib->searchFirst('responsabili_sedi', [
                'responsabili_sedi_associato' => $this->auth->get('associati_id'),
                'responsabili_sedi_sede' => $value_id
            ])['responsabili_sedi_modalita'];
        } else {
            $modalita = $this->auth->get('sedi_operative_modalita_calendario');
        }
        if ($modalita == 3) {
            $m_start = date('m');
            $m_end = date('m') + 1;
        } else {
            $m_start = 1;
            $m_end = 12;
        }


        ?>
        <?php for ($m = $m_start; $m <= $m_end; $m++) : ?>
            <option value="<?php echo ($m == 13) ? 1 : $m; ?>" <?php if ((($m == 13) ? 1 : $m) == $month) : ?> selected="selected" <?php endif; ?>><?php echo mese_testuale(($m == 13) ? 1 : $m); ?></option>
        <?php endfor; ?>
    </select>

    <button style="margin-left:300px" class="btn js_refresh">Aggiorna</button>
    <button class="btn js-stampa">Stampa</button>
    <?php if ($this->auth->is_admin() || in_array($this->auth->get('utenti_tipo'), [7, 8])) : ?>
        <button class="btn red js-notifica">NOTIFICA A TUTTI</button>
    <?php endif; ?>

</div>

<table class="table table-striped table-bordered calendario_sede" sede_id="<?php echo $sede['sedi_operative_id']; ?>">
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
                <td class="td-associato text-center"><?php echo $associato['associati_cognome'] . ' ' . substr($associato['associati_nome'], 0, 1); ?>.</td>
                <?php for ($day = 1; $day <= $days; $day++) : ?>
                    <?php
                    $dateString = date('Y-m-d', strtotime($year . '/' . $month . '/' . $day));

                    foreach (@(array) ($appuntamenti_dati[$dateString][$associato['associati_id']]) as $dati) {
                        if (str_ireplace(':', '', $dati['sedi_operative_orari_alle']) < str_ireplace(':', '', $dati['sedi_operative_orari_dalle'])) {
                            @$totali_giorni[$day] += (strtotime('2016-01-02 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            @$totali_associati[$associato['associati_id']] += (strtotime('2016-01-02 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            $totalone += (strtotime('2016-01-02 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                        } else {
                            @$totali_giorni[$day] += (strtotime('2016-01-01 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            @$totali_associati[$associato['associati_id']] += (strtotime('2016-01-01 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            $totalone += (strtotime('2016-01-01 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                        }
                    }

                    $tooltip = $td_class = '';

                    ?>

                    <td class="<?php //echo $bad_days; 
                                ?>">
                        <?php foreach (@(array) $appuntamenti[$dateString][$associato['associati_id']] as $fascia_id) : ?>
                            <?php echo @$fascie_orarie[$fascia_id]; ?>
                        <?php endforeach; ?>
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
                        if (str_ireplace(':', '', $dati['sedi_operative_orari_alle']) < str_ireplace(':', '', $dati['sedi_operative_orari_dalle'])) {
                            @$totali_giorni[$day] += (strtotime('2016-01-02 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            @$totali_associati[$associato['associati_id']] += (strtotime('2016-01-02 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            $totalone += (strtotime('2016-01-02 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                        } else {
                            @$totali_giorni[$day] += (strtotime('2016-01-01 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            @$totali_associati[$associato['associati_id']] += (strtotime('2016-01-01 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                            $totalone += (strtotime('2016-01-01 ' . $dati['sedi_operative_orari_alle'] . ':00') - strtotime('2016-01-01 ' . $dati['sedi_operative_orari_dalle'] . ':00')) / 3600;
                        }
                    }
                    ?>
                    <td class="<?php //echo $bad_days; 
                                ?>" data-giorno="<?php echo $dateString; ?>">

                        <select multiple class="form-control js_multiselect field_293" name="richiesta_cella[<?php echo $dateString; ?>][<?php echo $i; ?>]" data-val="<?php echo @implode(',', @(array) $richieste[$dateString][$i]); ?>" data-ref="richieste_disponibilita" data-source-field="" data-minimum-input-length="0">

                            <?php foreach ($fascie_orarie as $fascia_id => $fascia) : ?>
                                <option value="<?php echo $fascia_id; ?>" <?php if (@in_array($fascia_id, @$richieste[$dateString][$i])) : ?> selected<?php endif; ?>><?php echo $fascia; ?></option>
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
    </tfoot>
</table>




<strong>*&nbsp; Affiancamento</strong><br />
<strong>** Costo orario differenziato</strong>

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
    $('.js-stampa').on('click', function() {
        var month = $('select.js-month', $(this).parent()).val();
        var year = $('select.js-year', $(this).parent()).val();

        window.open('<?php echo base_url("custom/apib/stampaCalendarioSede/$value_id"); ?>?Y=' + year + '&m=' + month + '&_regen=1', '_blank');

    });

    $('.js-notifica').on('click', function() {
        var month = $('select.js-month', $(this).parent()).val();
        var year = $('select.js-year', $(this).parent()).val();

        window.open('<?php echo base_url("custom/apib/notificaCambioCalendarioSede/$value_id"); ?>?Y=' + year + '&m=' + month, '_blank');

    });

    var table_richieste = $('[sede_id="<?php echo $value_id; ?>"] .js-turni_scoperti');


    <?php if ($this->auth->get('utenti_tipo') == 15) : ?>
        //console.log('TEEEEEEEEEEST');
        table_richieste.find('select').on('click', function(e) {
            e.preventDefault();
            alert('Non sei autorizzato a salvare questa richiesta');
            location.reload();
            return false;
        });

    <?php else : ?>

        table_richieste.find('select').on('change', function(e) {

            var select = $(this);
            var giorno = select.parent().data('giorno');
            var fascie = [];
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][1]"]').val());
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][2]"]').val());
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][3]"]').val());
            fascie.push($('[sede_id="<?php echo $value_id; ?>"] select[name="richiesta_cella[' + giorno + '][4]"]').val());


            console.log(select);
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

            $('.js_refresh').on('click', function() {
                location.reload();
            });
        });

    <?php endif; ?>


    console.log('modalità calendario: <?php echo $modalita; ?>');

    <?php if ($modalita == 2) : ?>
        $('.js-stampa').hide();
    <?php elseif ($modalita == 3) : ?>
        $('<iframe width="100%" height="1024px" style="border: black 1px solid;" src="<?php echo base_url("custom/apib/stampaCalendarioSede/$value_id?Y=$year&m=$month&_regen=1"); ?>" />').insertAfter($('.calendario_sede').hide());
        $('#grid_91').parent().hide();
        $('.js_open_modal').hide();
    <?php endif; ?>
</script>