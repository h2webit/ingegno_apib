<?php
$this->load->model('modulo-hr/timbrature');

$dipendenti = $this->apilib->search('dipendenti');

$current_dip = $this->apilib->searchFirst('dipendenti', [
    'dipendenti_user_id' => $value_id ?? $this->input->get('u')
]);

if (!$m = $this->input->get('m')) {
    $m = date('m');
}
if (!$y = $this->input->get('y')) {
    $y = date('Y');
}
if (!$u = $this->input->get('u')) {
    $u = $current_dip['dipendenti_id'];
}

$m = str_pad($m, 2, '0', STR_PAD_LEFT);
$days_in_month = date('t', strtotime("{$y}-{$m}-15"));

$filtro_data = $y.'-'.$m;


$this->db->where('dipendenti_id', $u);
$dati_dipendente = $this->db->get('dipendenti')->row_array();
$user_id = $dati_dipendente['dipendenti_user_id'];


/**
 * ! Giorni lavorativi (esclusi sabato e domenica) mensili
 */

//Prendo le festività se se ce ne sono in questo mese non le conto tra i giorni lavorativi
$festivita = $this->apilib->search('festivita');

// Crea un nuovo array con le date delle festività nel formato 'yyyy-mm-dd'
$dateFestivita = [];
if(!empty($festivita)) {
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
        if(!empty($dateFestivita) && !in_array($date_formatted, $dateFestivita)) {
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




/**
 * ! Ore da contratto (giorni totali mese esclusi sabato e domenica e devo togliere eventuali richieste approvate)
 */
$richieste = $this->db
->where("DATE_FORMAT(richieste_dal, '%Y-%m') = '{$filtro_data}'")
->where("richieste_user_id = '{$dati_dipendente['dipendenti_id']}'")
->where("richieste_stato = '2'")
->get('richieste')->result_array();

$tot_giorni_richieste = 0;
if(!empty($richieste)) {
    foreach ($richieste as $richiesta) {
        $inizio_richiesta = new DateTime($richiesta['richieste_dal']);
        $fine_richiesta = new DateTime($richiesta['richieste_al']);
        $diff_giorni = $inizio_richiesta->diff($fine_richiesta)->days + 1; // Calcola la differenza in giorni
        $tot_giorni_richieste += $diff_giorni;
    }
}

//$ore_contratto = number_format($dati_dipendente['dipendenti_media_ore_gg'] * $workDays, 2, '.', ',');
$ore_contratto = number_format($dati_dipendente['dipendenti_media_ore_gg'] * ($workDays - $tot_giorni_richieste), 2, '.', ',');


/**
 * ! Ore che dovrebbero svoglere questo mese (somma ore previste appuntamenti)
 */
$appuntamenti = $this->db
->where("DATE_FORMAT(appuntamenti_giorno, '%Y-%m') = '{$filtro_data}'", null, false)
->where("appuntamenti_id IN (SELECT appuntamenti_id FROM rel_appuntamenti_persone WHERE users_id = '$user_id')", null, false)
->get('appuntamenti')->result_array();

$ore_teoriche = 0;
if(!empty($appuntamenti)) {
    foreach($appuntamenti as $appuntamento) {
        $inizio_appuntamento = new DateTime(str_ireplace(' 00:00:00', '', $appuntamento['appuntamenti_giorno']).' '.$appuntamento['appuntamenti_ora_inizio']);
        $fine_appuntamento = new DateTime(str_ireplace(' 00:00:00', '', $appuntamento['appuntamenti_giorno']).' '.$appuntamento['appuntamenti_ora_fine']);
        $diff_appuntamento = $fine_appuntamento->diff($inizio_appuntamento);
        $ore_teoriche += round(($diff_appuntamento->s / 3600) + ($diff_appuntamento->i / 60) + $diff_appuntamento->h + ($diff_appuntamento->days * 24), 2);
    }
}

/**
 * ! Ore lavorate (inserite nei rapportini --> presenze)
 */
$ore_lavorate_mese = 0;

/**
 * ! PRESENZE DEL MESE
 */
$this->db->where('presenze_dipendente', $u);
$this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = '{$filtro_data}'", null, false);
$this->db->where('presenze_cliente IS NOT NULL');
$this->db->where('presenze_rapportino_id IS NOT NULL');
$this->db->join('dipendenti', 'dipendenti_id = presenze_dipendente', 'LEFT');
$this->db->join('customers', 'customers_id = presenze_cliente', 'LEFT');
$this->db->join('projects', 'projects_id = presenze_commessa', 'LEFT');

$presenze = $this->db->get('presenze')->result_array();



$data = $data_xls = [];
$row = 0;
foreach ($presenze as $p) {   
    if (empty($data[$p['projects_name']])) {
        $data[$p['projects_name']] = [];
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

    $suggerimentoTurno = $this->timbrature->suggerisciTurno($p['presenze_ora_inizio'], $orario_lavoro,'entrata');

    if($ignora_pausa == DB_BOOL_FALSE) {
        //if(!empty($orario_lavoro) && !empty($orario_lavoro['turni_di_lavoro_pausa'])) {
        if(!empty($orario_lavoro[$suggerimentoTurno]) && !empty($orario_lavoro[$suggerimentoTurno]['turni_di_lavoro_pausa'])) {
            $pausa = $orario_lavoro[$suggerimentoTurno]['orari_di_lavoro_ore_pausa_value']?? 1;
        } else {
            $pausa = 1;
        }
    } else {
        $pausa = 0;
    }

    $ore_lavorate = ($p['presenze_ore_totali'] - $pausa) > 0 ? ($p['presenze_ore_totali'] - $pausa) : 0;

    if (empty($data[$p['projects_name']][$p['customers_company']][$day])) {
        $data[$p['projects_name']][$p['customers_company']][$day] = $ore_lavorate;
    } else {
        $data[$p['projects_name']][$p['customers_company']][$day] += $ore_lavorate;
    }
    //Ore totali lavorate questo mese

    

    $ore_lavorate_mese += $ore_lavorate;
}

$dipendente = $this->db->get_where('dipendenti', ['dipendenti_id' => $u])->row_array();
$rapportini = $this->db->query("
    SELECT * FROM rapportini
    LEFT JOIN projects ON projects_id = rapportini_commessa
    LEFT JOIN customers ON customers_id = projects_customer_id
    WHERE
        rapportini_da_validare = '0'
        AND rapportini_id IN (SELECT rapportini_id FROM rel_rapportini_users WHERE users_id = '{$dipendente['dipendenti_user_id']}')  -- Sostituisci con l'ID del dipendente
        AND DATE_FORMAT(rapportini_data, '%Y-%m') = '{$filtro_data}'
        AND rapportini_id NOT IN (SELECT presenze_rapportino_id FROM presenze WHERE presenze_dipendente = '{$dipendente['dipendenti_id']}' AND presenze_rapportino_id IS NOT NULL)
")->result_array();
foreach ($rapportini as $r) {
    if (empty($data[$r['projects_name']])) {
        $data[$r['projects_name']] = [];
    }
    $day = date("d", strtotime($r['rapportini_data']));

    $ora_inizio = DateTime::createFromFormat('H:i', $r['rapportini_ora_inizio']);
    $ora_fine = DateTime::createFromFormat('H:i', $r['rapportini_ora_fine']);
    if ($ora_inizio && $ora_fine) {
        $diff = $ora_inizio->diff($ora_fine);
        $ore_lavorate = $diff->h + ($diff->i / 60); // Converti minuti in ore
    } else {
        $ore_lavorate = 0; // Se l'ora di inizio o di fine non sono valide, setta a 0
    }

    if (empty($data[$r['projects_name']][$r['customers_company']][$day])) {
        $data[$r['projects_name']][$r['customers_company']][$day] = $ore_lavorate;
    } else {
        $data[$r['projects_name']][$r['customers_company']][$day] += $ore_lavorate;
    }
    $ore_lavorate_mese += $ore_lavorate;

}


//Riempio i buchi e vedo se ho assenze per le giornate
for ($i = 1; $i <= $days_in_month; $i++) {
    $i = str_pad($i, 2, '0', STR_PAD_LEFT);
    $current_date = $y.'-'.$m.'-'.$i;

    foreach ($data as $progetto => $cliente) {
        foreach ($cliente as $nome_cliente => $days_hours) {                   
            if (!array_key_exists($i, $days_hours)) {
                $data[$progetto][$nome_cliente][$i] = '';
            }
            //Se ho assenza nella giornata inserisco sigla per poter cambiare colore dopo
            $assenza = $this->db
            ->where("DATE_FORMAT(richieste_dal, '%Y-%m-%d') <= '{$current_date}'", null, false)
            ->where("DATE_FORMAT(richieste_al, '%Y-%m-%d') >= '{$current_date}'", null, false)
            ->where('richieste_user_id', $u)
            ->where('richieste_stato', '2')
            ->get('richieste')->row_array();
            
            if(!empty($assenza)) {
                $day_start = str_replace('"', "", dateFormat($assenza['richieste_dal'], 'Y-m-d'));
                $day_end = str_replace('"', "", dateFormat($assenza['richieste_al'], 'Y-m-d'));
                
                if(!isset($days_hours[$i]) && ($day_start <= $current_date && $current_date <= $day_end)) {
                    if($assenza['richieste_tipologia'] == '1') { //Permesso
                        $inizio = new DateTime($assenza['richieste_data_ora_inizio_calendar']);
                        $fine = new DateTime($assenza['richieste_data_ora_fine_calendar']);
                        $diff_date = $fine->diff($inizio);
                        $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h, 2);

                        $data[$progetto][$nome_cliente][$i] = 'Permesso - '.$hours;
                        //$data[$progetto][$nome_cliente][$i] = 'P';     
                    } elseif($assenza['richieste_tipologia'] == '2') { //Ferie
                        if(!empty($assenza['richieste_sottotipologia'])) {
                            if($assenza['richieste_sottotipologia'] == '1') { //Assenza ingiustificata
                                $data[$progetto][$nome_cliente][$i] = 'aing';
                            }
                            if($assenza['richieste_sottotipologia'] == '4') { //Infortunio
                                $data[$progetto][$nome_cliente][$i] = 'inf';
                            }
                            if($assenza['richieste_sottotipologia'] == '7') { //L. 104
                                $data[$progetto][$nome_cliente][$i] = 'l104';
                            }
                        } else {
                            $data[$progetto][$nome_cliente][$i] = 'F';
                        }
                    } else { //Malattia
                        $data[$progetto][$nome_cliente][$i] = 'M';
                        if ($assenza['richieste_sottotipologia'] == '1') { //Assenza ingiustificata
                            $data[$progetto][$nome_cliente][$i] = 'aing';
                        }
                        if ($assenza['richieste_sottotipologia'] == '4') { //Infortunio
                            $data[$progetto][$nome_cliente][$i] = 'inf';
                        }
                        if ($assenza['richieste_sottotipologia'] == '7') { //L. 104
                            $data[$progetto][$nome_cliente][$i] = 'l104';
                        }
                    }
                }
            }

            //Se è domenica colora di rosso
            /* $datatime_current_date = new DateTime($current_date);
            $day_week = $datatime_current_date->format("w");
            if($day_week == 0) {
                $data[$progetto][$nome_cliente][$i] = 'dom';
            } */
        }
    }
}


//Ordino le giornate
foreach ($data as $progetto => $cliente) {
    foreach ($cliente as $nome_cliente => $days_hours) {
        ksort($data[$progetto][$nome_cliente]);
        
    }
}


$tot_mensile = 0;
foreach ($data as $progetto => $cliente) {
    foreach ($cliente as $nome_cliente => $days_hours) {
        $data_xls[$row][] = $progetto;
        $data_xls[$row][] = $nome_cliente;
        foreach ($days_hours as $day => $hours) {
            if (!empty($hours) && is_numeric($hours)) { //Aggiunto is_numeric
                $tot_mensile += $hours;
                $hours = str_replace(".00", "", (string)number_format($hours, 2, ".", ""));
            }
            $data_xls[$row][] = $hours;
        }
        $data_xls[$row][] = round($tot_mensile, 2);
        $tot_mensile = 0;
        $row++;
    }

}

/**
 * EXCEL ADDITIONAL DATA
 */

$footer = ['Total', ''];
for ($i = 2; $i <= $days_in_month; $i++) {
    $idx = $i+1;
    $footer[] = "=SUMCOL(TABLE(), {$idx})";
}
$footer[] = '=SUMCOL(TABLE(), COLUMN())';

$styles = $meta = [];

/*$styles['F' . ($row + 1)] = 'color: #d92307;font-weight: bold';
$styles['A3'] = 'color: #d92307;font-weight: bold';
$styles['AB3'] = 'color: #d92307;font-weight: bold'; */

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
    display: flex;
    justify-content: flex-start;
    align-items: baseline;
    gap: 20px;
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
    <div class="row">
        <div class="col-sm-4">
            <div>
                <div class="text-uppercase">
                    <strong>ore teoriche</strong>
                    <?php echo number_format($ore_teoriche, 2, '.', ','); ?>
                </div>
            </div>
            <div>
                <div class="text-uppercase">
                    <strong>ore contratto</strong>
                    <?php echo $ore_contratto; ?>
                </div>
            </div>
            <div>
                <div class="text-uppercase">
                    <strong>ore lavorate</strong>
                    <?php echo number_format($ore_lavorate_mese, 2, '.', ','); ?>
                </div>
            </div>
            <div>
                <div class="text-uppercase">
                    <strong>ore (LAV. - CONTR.)</strong>
                    <?php echo number_format($ore_lavorate_mese-$ore_contratto, 2, '.', ','); ?>
                </div>
            </div>
            <div>
                <div class="text-uppercase">
                    <strong>Ore settimanali</strong>
                    <?php echo number_format($dati_dipendente['dipendenti_ore_settimanali'] ?? 0, 2, '.', ','); ?>
                </div>
            </div>
            <div>
                <div class="text-uppercase">
                    <strong>Giorni settimanali</strong>
                    <?php echo number_format($dati_dipendente['dipendenti_gg_settimana'] ?? 0, 2, '.', ','); ?>
                </div>
            </div>
            <div>
                <div class="text-uppercase">
                    <strong>Media ore giornaliere</strong>
                    <?php echo number_format($dati_dipendente['dipendenti_media_ore_gg'] ?? 0, 2, '.', ','); ?>
                </div>
            </div>
        </div>
        <div class="col-sm-8">
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

    <div class="form-group row filtri_cartellino_ore">
        <div class="col-sm-3 hidden">
            <label for="presenze_month">Dipendente</label>
            <select class="form-control select2_standard js_select2 select_dipendente" name="presenze_dipendente" id="presenze_dipendente" data-placeholder="<?php e('Choose template') ?>">
                <option value="" selected="selected">Seleziona dipendente</option>
                <?php foreach ($dipendenti as $dipendente) : ?>
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
            <div id="spreadsheet_presenze"></div>
        </div>
    </div>

</div>

<style>
/* .jexcel>tbody>tr>td.readonly {
    color: #000000 !important;
} */
table.jexcel {
    font-size: 13px;
}
</style>

<script>
var data = <?php echo json_encode($data_xls); ?>;
var styles = <?php echo json_encode($styles); ?>;


// A custom method to SUM all the cells in the current column
var SUMCOL = function(instance, columnId) {
    var total = 0;
    for (var j = 0; j < instance.options.data.length; j++) {
        if (!isNaN(parseFloat(instance.records[j][columnId - 1].innerHTML))) {
            if (instance.records[j][columnId - 1].style.getPropertyValue("background-color") === 'rgb(253, 224, 71)') {
                total = 0;
            } else {
                total += Number(instance.records[j][columnId - 1].innerHTML);
            }
        }
    }
    return total.toFixed(2);
}

var table_cartellino = jspreadsheet(document.getElementById('spreadsheet_presenze'), {
    onload: function(el, instance) {
        //header background
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

        //$(instance.tbody).find("tr td").popover();
        //console.log($(instance.tbody).find("tr td"));
        /*setTimeout(() => {
            $(instance.tbody).find("tr td.readonly").each(function(index) {
                console.log($(this).popover());
                console.log($(this));
                //$(this).popover();
            });
        }, 1000);*/

        //$(cell).popover();
    },
    updateTable: function(instance, cell, col, row, val, label, cellName) {
        //console.log(`cell: ${cell}, col: ${col}, row: ${row}, val: ${val}, label: ${label}, cellName: ${cellName}`);
        /*console.log(instance);
        console.log(cell);*/
        //Data attributes
        /*cell.dataset.html = 'true';
        cell.dataset.placement = 'auto';
        cell.dataset.trigger = 'hover';
        cell.dataset.container = 'body';
        cell.dataset.originalTitle = 'Dettaglio ore';
        cell.dataset.content = '<strong>Test</strong><br/>descrizione';*/

        //Coloro sfondo e testo per le ore lavorate
        if (col != '0' && col != '1') {
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
            title: 'Commessa',
            width: 90,
        },
        {
            type: 'text',
            title: 'Cliente',
            width: 90,
        },
        <?php for ($i = 1; $i <= $days_in_month; $i++) : ?> {
            <?php
              $giorno_completo = sprintf("%s-%02d-%02d", substr($filtro_data, 0, 4), substr($filtro_data, 5), $i);
              $giorno_settimana = strftime("%w", strtotime($giorno_completo));
              $iniziale_giorno = substr($giorni_settimana[$giorno_settimana], 0, 1);
              $headers[] = $i."($iniziale_giorno)";
            ?>
            type: 'text',
                title: '<?php echo $i." ($iniziale_giorno)"; ?>',
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
    style: styles,
});

//hide row number column
table_cartellino.hideIndex();

table_cartellino.getHeaders();




$(function() {
    var select = $('[name="presenze_dipendente"], [name="presenze_month"], [name="presenze_year"]');
    var operator_id, month_id = '';
    var baseURL = '<?php echo base_url('main/layout/piano-lavoro-dipendente/'.$value_id); ?>';

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