<?php
$this->load->model('modulo-hr/timbrature');

if (!$m = $this->input->get('m')) {
    $m = date('m');
}
if (!$y = $this->input->get('y')) {
    $y = date('Y');
}
if (!$u = $this->input->get('u')) {
    //$u = $this->auth->get('users_id');
    //$u = $this->auth->get('dipendenti_id');
}
if (!$c = $this->input->get('c')) {
    //$u = $this->auth->get('users_id');
    $c = $value_id;
}


//Recupero solo gli i dipendenti associati a user che hanno compilato rapportini per questa commessa
$dipendenti_rapportini_commessa = $this->db->query("SELECT *
FROM dipendenti 
WHERE dipendenti_user_id IN (
    SELECT DISTINCT users_id 
    FROM rel_rapportini_users 
    WHERE rapportini_id IN (
        SELECT rapportini_id 
        FROM rapportini 
        WHERE rapportini_commessa = '{$c}'
    )
)")->result_array();
//dump($dipendenti_rapportini_commessa);

$m = str_pad($m, 2, '0', STR_PAD_LEFT);
$days_in_month = date('t', strtotime("{$y}-{$m}-15"));

$filtro_data = $y . '-' . $m;



/**
 * ! Giorni lavorativi (esclusi sabato e domenica) mensili
 */

//Prendo le festività se se ce ne sono in questo mese non le conto tra i giorni lavorativi
$festivita = $this->apilib->search('festivita');

// Crea un nuovo array con le date delle festività nel formato 'yyyy-mm-dd'
$dateFestivita = [];
if (!empty($festivita)) {
    foreach ($festivita as $festivita_item) {
        $dateFestivita[] = dateFormat($festivita_item['festivita_data'], 'Y-m-d');
    }
}


$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $m, $y);
$workDays = 0;

for ($day = 1; $day <= $daysInMonth; $day++) {
    $date = strtotime("$y-$m-$day");
    $weekday = date('N', $date);
    //Se sono in una festività (e non è sabato o domenica) diminusco giorni lavorativi
    $giorno = $day < 10 ? "0{$day}" : $day;
    $date_formatted = "$y-$m-$giorno";
    //Escludo sabato e domenica
    if ($weekday < 6) {
        //Se NON sono in una festività allora aumento i giorni lavorativi
        if (!empty($dateFestivita) && !in_array($date_formatted, $dateFestivita)) {
            $workDays++;
        }
    }
}

/**
 * ! Giorni settimana per header tabella
 */
$giorni_settimana = [
    "Domenica",
    "Lunedì",
    "Martedì",
    "Mercoledì",
    "Giovedì",
    "Venerdì",
    "Sabato"
];



if (!empty($u)) {
    $this->db->where('presenze_dipendente', $u);
}
$this->db->where('presenze_commessa', $c);
$this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = '{$filtro_data}'", null, false);
$this->db->where('presenze_cliente IS NOT NULL');
$this->db->join('dipendenti', 'dipendenti_id = presenze_dipendente', 'LEFT');
$this->db->join('customers', 'customers_id = presenze_cliente', 'LEFT');
$this->db->join('projects', 'projects_id = presenze_commessa', 'LEFT');

$presenze = $this->db->get('presenze')->result_array();

$dipendentiUnici = [];

$data = $data_xls = [];
$row = 0;
foreach ($presenze as $p) {
    $dip = $p['dipendenti_nome'] . ' ' . $p['dipendenti_cognome'];
    if (empty($data[$dip])) {
        $data[$dip] = [];
    }

    //Aggiungo i dipendenti con chiave l'id per poterli usare nelle richieste anche quando non è selezionato alcun dipendente nella select
    if (!array_key_exists($p['presenze_dipendente'], $dipendentiUnici)) {
        // Se l'id del dipendente non esiste nell'array, lo aggiungi
        $dipendentiUnici[$p['presenze_dipendente']] = [$dip];
        
    }

    $day = date("d", strtotime($p['presenze_data_inizio']));
    //Prendo pausa della giornata di oggi
    $ignora_pausa = $p['dipendenti_ignora_pausa'] ?? DB_BOOL_FALSE;
    $weekday = date('w', strtotime($p['presenze_data_inizio']));

    $this->db->select('*');
    $this->db->from('turni_di_lavoro');
    $this->db->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', "left");
    $this->db->where("turni_di_lavoro_data_inizio <= '{$p['presenze_data_inizio']}'", null, false);
    $this->db->where("(turni_di_lavoro_data_fine >= '{$p['presenze_data_inizio']}' OR turni_di_lavoro_data_fine IS NULL)", null, false);
    $this->db->where('turni_di_lavoro_dipendente', $p['presenze_dipendente']);
    $this->db->where('turni_di_lavoro_giorno', $weekday);
    $orario_lavoro = $this->db->get()->result_array();

    $suggerimentoTurno = $this->timbrature->suggerisciTurno($p['presenze_ora_inizio'], $orario_lavoro, 'entrata');

    if ($ignora_pausa == DB_BOOL_FALSE) {
        //if(!empty($orario_lavoro) && !empty($orario_lavoro['turni_di_lavoro_pausa'])) {
        if (!empty($orario_lavoro[$suggerimentoTurno]) && !empty($orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_pausa'])) {
            $pausa = $orario_lavoro[$suggerimentoTurno]['orari_di_lavoro_ore_pausa_value'] ?? 1;
        } else {
            $pausa = 1;
        }
    } else {
        $pausa = 0;
    }

    $ore_lavorate = ($p['presenze_ore_totali'] - $pausa) > 0 ? ($p['presenze_ore_totali'] - $pausa) : 0;
    //$ore_lavorate = ($p['presenze_ore_totali'] - $p['presenze_straordinario']) > 0 ? ($p['presenze_ore_totali'] - $p['presenze_straordinario']) : 0;

    if (empty($data[$dip][$day])) {
        $data[$dip][$day] = $ore_lavorate;
    } else {
        $data[$dip][$day] += $ore_lavorate;
    }
}


//Riempio i buchi e vedo se ho assenze per le giornate
for ($i = 1; $i <= $days_in_month; $i++) {
    $i = str_pad($i, 2, '0', STR_PAD_LEFT);
    $current_date = $y . '-' . $m . '-' . $i;

    foreach ($data as $dipendente => $giorno) {
        if (!array_key_exists($i, $giorno)) {
            $data[$dipendente][$i] = '';
        }
        /*dump($data);
        dump($dipendente);*/

        if (!empty($u) || in_array($dipendente, array_column($dipendentiUnici, 0))) {
            foreach ($dipendentiUnici as $key => $value) {
                if ($value[0] === $dipendente) {
                    $u = $key;
                    break;  // Puoi interrompere il ciclo una volta trovato il valore
                }
            }

            //Se ho assenza nella giornata inserisco sigla per poter cambiare colore dopo
            $assenza = $this->db
                ->where("DATE_FORMAT(richieste_dal, '%Y-%m-%d') <= '{$current_date}'", null, false)
                ->where("DATE_FORMAT(richieste_al, '%Y-%m-%d') >= '{$current_date}'", null, false)
                ->where('richieste_user_id', $u)
                ->where('richieste_stato', '2')
                ->get('richieste')->row_array();

                
            if (!empty($assenza)) {
                $day_start = str_replace('"', "", dateFormat($assenza['richieste_dal'], 'Y-m-d'));
                $day_end = str_replace('"', "", dateFormat($assenza['richieste_al'], 'Y-m-d'));

                if (!isset($days_hours[$i]) && ($day_start <= $current_date && $current_date <= $day_end)) {
                    if ($assenza['richieste_tipologia'] == '1') { //Permesso
                        $inizio = new DateTime($assenza['richieste_data_ora_inizio_calendar']);
                        $fine = new DateTime($assenza['richieste_data_ora_fine_calendar']);
                        $diff_date = $fine->diff($inizio);
                        $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h, 2);

                        $data[$dipendente][$i] = 'Permesso - ' . $hours;
                        if ($assenza['richieste_sottotipologia'] == '7') { //L. 104
                            $data[$dipendente][$i] = 'l104 - ' . $hours;
                        }
                        //$data[$dipendente][$i] = 'P';     
                    } elseif ($assenza['richieste_tipologia'] == '2') { //Ferie
                        if (!empty($assenza['richieste_sottotipologia'])) {
                            if ($assenza['richieste_sottotipologia'] == '1') { //Assenza ingiustificata
                                $data[$dipendente][$i] = 'aing';
                            }
                            if ($assenza['richieste_sottotipologia'] == '4') { //Infortunio
                                $data[$dipendente][$i] = 'inf';
                            }
                            if ($assenza['richieste_sottotipologia'] == '7') { //L. 104
                                $data[$dipendente][$i] = 'l104';
                            }
                        } else {
                            $data[$dipendente][$i] = 'F';
                        }
                    } else { //Malattia
                        $data[$dipendente][$i] = 'M';
                        if ($assenza['richieste_sottotipologia'] == '1') { //Assenza ingiustificata
                            $$data[$dipendente][$i] = 'aing';
                        }
                        if ($assenza['richieste_sottotipologia'] == '4') { //Infortunio
                            $$data[$dipendente][$i] = 'inf';
                        }
                        if ($assenza['richieste_sottotipologia'] == '7') { //L. 104
                            $$data[$dipendente][$i] = 'l104';
                        }
                    }
                }
            }
        }
    }
}

foreach ($data as $dipendente => $giorno) {
    ksort($data[$dipendente]);
}


$tot_mensile = 0;

/*
03/04/2024 - Chiesto di non mostrare le ore permesso nei calcoli totali delle righe
foreach ($data as $dipendente => $giorno) {
    $data_xls[$row][] = $dipendente;
    foreach ($giorno as $day => $hours) {
        if (!empty($hours) && (is_numeric($hours) || strpos($hours, "Permesso - ") !== false)) { //Aggiunto is_numeric
            //Se è permesso pulisco la stringa
            if(strpos($hours, "Permesso - ") !== false) {
                $calcolo = str_replace("Permesso - ", "", $hours);  
            } else {
                $calcolo = $hours;
            }
            $tot_mensile += $calcolo;
            $hours = is_numeric($hours)  ? str_replace(".00", "", (string)number_format($hours, 2, ".", "")) : $hours;
        }
        $data_xls[$row][] = $hours;
    }
    $data_xls[$row][] = round($tot_mensile, 2);
    $tot_mensile = 0;
    $row++;
} */
foreach ($data as $dipendente => $giorno) {
    $data_xls[$row][] = $dipendente;
    foreach ($giorno as $day => $hours) {
        if (!empty($hours) && is_numeric($hours)) { //Aggiunto is_numeric
            $tot_mensile += $hours;
            $hours = is_numeric($hours)  ? str_replace(".00", "", (string)number_format($hours, 2, ".", "")) : $hours;
        }
        $data_xls[$row][] = $hours;
    }
    $data_xls[$row][] = round($tot_mensile, 2);
    $tot_mensile = 0;
    $row++;
}


/**
 * EXCEL ADDITIONAL DATA
 */
$footer = ['Total'];
for ($i = 0; $i <= $days_in_month; $i++) {
    $footer[] = '=SUMCOL(TABLE(), COLUMN())';
}

?>

<?php $this->layout->addModuleStylesheet('modulo-hr', 'vendor/jsuites/jsuites.css'); ?>
<?php $this->layout->addModuleStylesheet('modulo-hr', 'vendor/jexcel/jexcel.css'); ?>

<?php $this->layout->addModuleJavascript('modulo-hr', 'vendor/jsuites/jsuites.js'); ?>
<?php $this->layout->addModuleJavascript('modulo-hr', 'vendor/jexcel/index.js'); ?>

<style>
.jexcel tbody tr:nth-child(even) {
    background-color: rgba(0, 0, 0, .05) !important;
}

.filtri_cartellino_ore {
    margin-top: 16px;
}

.legenda_container {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 20px;
    margin-bottom: 20px;
}

.legenda_item {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    gap: 4px;
}

.legenda_square {
    width: 16px;
    height: 16px;
    border-radius: 2px;
}

.legenda_square.permesso {
    background-color: rgb(253 224 71);
}

.legenda_square.malattia {
    background-color: rgb(4 120 87);
}

.legenda_square.ferie {
    background-color: rgb(249 115 22);
}

.legenda_square.ass_ing {
    background-color: rgb(124 45 18);
}

.legenda_square.infortunio {
    background-color: rgb(15 23 42);

}

.legenda_square.l104 {
    background-color: rgb(8, 181, 234);
}
</style>


<div class="container-fluid">
    <div class="form-group row">
        <?php //debug($this->datab->getPermission($this->auth->get('users_id'))); 
        ?>
        <div class="col-sm-3">
            <label for="presenze_month">Dipendente</label>
            <select class="form-control select2_standard js_select2 select_dipendente" name="presenze_dipendente" id="presenze_dipendente" data-placeholder="<?php e('Choose template') ?>">
                <option value="" selected="selected">Seleziona dipendente</option>
                <?php foreach ($dipendenti_rapportini_commessa as $dipendente) : ?>
                <option value="<?php echo $dipendente['dipendenti_id']; ?>" <?php if ($dipendente['dipendenti_id'] == $u) : ?>selected="selected" <?php endif; ?>><?php echo $dipendente['dipendenti_nome'] . ' ' . $dipendente['dipendenti_cognome']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-sm-3">
            <label for="presenze_month">Mese</label>
            <select class="form-control select2_standard js_select2 select_month" name="presenze_month" id="presenze_month" data-placeholder="<?php e('Choose template') ?>">
                <?php for ($i = 1; $i <= 12; $i++) : ?>
                <option value="<?php echo $i; ?>" <?php if ($i == $m) : ?>selected="selected" <?php endif; ?>><?php echo mese_testuale($i); ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="col-sm-3">
            <label for="presenze_year">Anno</label>
            <select name="presenze_year" id="presenze_year" class="form-control select2_standard js_select2 select_year">
                <?php for ($i = 2022; $i <= date('Y'); $i++) : ?>
                <option value="<?php echo $i; ?>" <?php if ($i == $y) : ?>selected="selected" <?php endif; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div class="legenda_container">
                <div class="text-uppercase legenda_item">
                    <strong>Permesso</strong> <span class="legenda_square permesso"></span>
                </div>
                <div class="text-uppercase legenda_item">
                    <strong>Malattia</strong> <span class="legenda_square malattia"></span>
                </div>
                <div class="text-uppercase legenda_item">
                    <strong>Ferie</strong> <span class="legenda_square ferie"></span>
                </div>
                <div class="text-uppercase legenda_item">
                    <strong>Assenza ingius.</strong> <span class="legenda_square ass_ing"></span>
                </div>
                <div class="text-uppercase legenda_item">
                    <strong>Infortunio</strong> <span class="legenda_square infortunio"></span>
                </div>
                <div class="text-uppercase legenda_item">
                    <strong>L. 104</strong> <span class="legenda_square l104"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div id="spreadsheet_presenze"></div>
        </div>
    </div>

</div>

<style>
/* .jexcel>tbody>tr>td.readonly {
    color: #000000 !important;
} */
</style>

<script>
var data = <?php echo json_encode($data_xls); ?>;

// A custom method to SUM all the cells in the current column
var SUMCOL = function(instance, columnId) {
    var total = 0;
    for (var j = 0; j < instance.options.data.length; j++) {
        if (!isNaN(parseFloat(instance.records[j][columnId - 1].innerHTML))) {
            if (instance.records[j][columnId - 1].style.getPropertyValue("background-color") === 'rgb(253, 224, 71)') {
                //total = 0;
            } else {
                total += Number(instance.records[j][columnId - 1].innerHTML);
            }
            //total += Number(instance.records[j][columnId - 1].innerHTML);
        }
    }
    return total.toFixed(2);
}

var table2 = jspreadsheet(document.getElementById('spreadsheet_presenze'), {
    onload: function(el, instance) {
        //header background
        var x = 1 // column A
        $(instance.thead).find("tr td").css({
            'background-color': '#086fa3',
            'color': '#ffffff',
            'font-weight': 'bold',
            'font-size': '12px'
        });
        $(instance.tfoot).find("tr td").css({
            'color': '#086fa3',
            'font-weight': 'bold',
        });
    },
    updateTable: function(instance, cell, col, row, val, label, cellName) {
        //console.log(`cell: ${cell}, col: ${col}, row: ${row}, val: ${val}, label: ${label}, cellName: ${cellName}`);

        //Coloro sfondo e testo per le ore lavorate
        if (col != '0') {
            cell.style.color = 'rgb(0 0 0)';
            cell.style.fontWeight = 'bold';
            /*if (parseFloat(label)) {
                cell.style.color = 'rgb(220 38 38)';
                cell.style.background = 'rgb(254 226 226)';
                cell.style.fontWeight = 'bold';
            }*/
        }
        //Colore sfondo e testo per permesso
        if (cell.textContent.includes('Permesso - ')) {
            //console.log(cell.textContent);
            cell.textContent = cell.textContent.substring(11, 20);
            cell.style.color = 'rgb(0 0 0)';
            cell.style.background = 'rgb(253 224 71)';
            cell.style.fontWeight = 'bold';
        }
        if (cell.textContent.includes('l104 - ')) {
            /* console.log(cell.textContent);
            console.log(cell.textContent.length); */
            cell.textContent = cell.textContent.substring(7, 12);
            cell.style.color = 'rgb(0 0 0)';
            cell.style.background = 'rgb(8, 181, 234)';
            cell.style.fontWeight = 'bold';
        }
        //Colore sfondo e testo per ferie
        if (val === 'F') {
            cell.style.color = 'rgb(249 115 22)';
            cell.style.background = 'rgb(249 115 22)';
        }
        //Colore sfondo e testo per assenza ingiustificata (Ferie)
        if (val === 'aing') {
            cell.style.color = 'rgb(124 45 18)';
            cell.style.background = 'rgb(124 45 18)';
        }
        //Colore sfondo e testo per infortunio (Ferie)
        if (val === 'inf') {
            cell.style.color = 'rgb(15 23 42)';
            cell.style.background = 'rgb(15 23 42)';
        }
        //Colore sfondo e testo per L. 104 (Ferie)
        if (val === 'l104') {
            cell.style.color = 'rgb(8, 181, 234)';
            cell.style.background = 'rgb(8, 181, 234)';
        }
        //Colore sfondo e testo per malattia
        if (val === 'M') {
            cell.style.color = 'rgb(4 120 87)';
            cell.style.background = 'rgb(4 120 87)';
        }
        //Colore sfondo e testo per domenica
        if (val === 'dom') {
            cell.style.color = 'rgb(239 68 68)';
            //cell.style.background = 'rgb(239 68 68)';
        }
    },
    data: data,
    contextMenu: false,
    defaultColAlign: 'left',
    footers: [
        <?php echo json_encode($footer); ?>
    ],
    columns: [{
            type: 'text',
            title: 'Dipendente',
            width: 90,
        },
        <?php for ($i = 1; $i <= $days_in_month; $i++) : ?> {
            <?php
                    $giorno_completo = sprintf("%s-%02d-%02d", substr($filtro_data, 0, 4), substr($filtro_data, 5), $i);
                    $giorno_settimana = strftime("%w", strtotime($giorno_completo));
                    $iniziale_giorno = substr($giorni_settimana[$giorno_settimana], 0, 1);
                    $headers[] = $i . "($iniziale_giorno)";
                    ?>
            type: 'text',
                title: '<?php echo $i . " ($iniziale_giorno)"; ?>',
                width: 20,
                readOnly: true,
                align: 'center'
        },
        <?php endfor; ?> {
            type: 'numeric',
            title: 'TOT',
            width: 20,
            readOnly: true,
            align: 'center'
        },
    ],
});

//hide row number column
table2.hideIndex();




$(function() {
    const commessa_id = '<?php echo $c; ?>';
    var select = $('[name="presenze_dipendente"], [name="presenze_month"], [name="presenze_year"]');
    var operator_id, month_id = '';
    var baseURL = '<?php echo base_url("main/layout/dettaglio_commessa/{$c}"); ?>';

    select.on('change', function() {
        operator_id = $('.select_dipendente').find(':selected').val();
        month_id = $('.select_month').find(':selected').val();
        year_id = $('.select_year').find(':selected').val();

        var url = baseURL + "?m=" + month_id;

        if (operator_id) {
            url += "&u=" + operator_id;
        }

        if (year_id) {
            url += "&y=" + year_id;
        }


        if (commessa_id) {
            url += "&c=" + commessa_id;
        }

        window.location.replace(url);
    });

    // Seleziona tutte le <td> principali con la parola 'D' nel titolo
    var mainTds = $('#spreadsheet_presenze td[title*="(D)"]');
    // Itera su ciascuna <td> principale
    mainTds.each(function() {
        var mainTd = $(this);
        var dataX = mainTd.data('x');

        // Seleziona tutte le <td> con lo stesso valore di data-x
        var relatedTds = $('#spreadsheet_presenze td[data-x="' + dataX + '"]');

        // Applica lo stile alle <td> corrispondenti
        relatedTds.css({
            color: 'white',
            background: 'rgb(239 68 68)'
        });
    });
})
</script>