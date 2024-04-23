                            <?php $this->layout->addModuleStylesheet('modulo-hr', 'vendor/jsuites/jsuites.css'); ?>
                            <?php $this->layout->addModuleStylesheet('modulo-hr', 'vendor/jexcel/jexcel.css'); ?>

                            <?php $this->layout->addModuleJavascript('modulo-hr', 'vendor/jsuites/jsuites.js'); ?>
                            <?php $this->layout->addModuleJavascript('modulo-hr', 'vendor/jexcel/index.js'); ?>
                            <!-- Per salvataggio tabella !-->
                            <?php $this->layout->addModuleJavascript('modulo-hr', 'js/FileSaver.min.js'); ?>
                            <?php $this->layout->addModuleJavascript('modulo-hr', 'js/xlsx.full.min.js'); ?>
                            <?php
$giorni_settimana = array(
    "Domenica",
    "Lunedì",
    "Martedì",
    "Mercoledì",
    "Giovedì",
    "Venerdì",
    "Sabato"
);
// prendo i reparti
$reparti = $this->db->get('reparti')->result_array();
//prendi i turni di default
$turni_template = $this->db->get('turni_di_lavoro_template')->result_array();
$result = array();
foreach ($turni_template as $turno) {
    $entry = array(
        "name" => $turno["turni_di_lavoro_template_nome"],
        "id" => $turno["turni_di_lavoro_template_id"]
    );
    $result[] = $entry;
}

$turni_template_json = json_encode($result);
//$r = $reparti[0]['reparti_id'];

if (!$m = $this->input->get('m')) {
    $m = date('m');
}
if (!$y = $this->input->get('y')) {
    $y = date('Y');
}
$m = str_pad($m, 2, '0', STR_PAD_LEFT);
$days_in_month = date('t', strtotime("{$y}-{$m}-15"));

$filtro_data = $y.'-'.$m;
// imposto i where per la grid dei permessi:
$where = "DATE_FORMAT(richieste_dal, '%Y-%m') = '".$y."-".$m."' AND dipendenti_lavoro_a_turni = 1";
$incrementale_reparto = 1; //questo è l'incrementale dovuto al reparto, se ho reparto, è 0.
if ($this->input->get('r') && $this->input->get('r') != 0) {
    $incrementale_reparto = 0;
    $r = $this->input->get('r');
    $where .= " AND (richieste_user_id IS NULL OR richieste_user_id IN (
        SELECT dipendenti_id
        FROM rel_reparto_dipendenti
        WHERE reparti_id = $r
    ))";
}
//if (!$u = $this->input->get('u')) {
if ($this->input->get('u') && $this->input->get('u') != 0) {

    //$u = $this->auth->get('users_id');
    $u = $this->input->get('u');
    $where .= " AND richieste_user_id = $u";
}



//prendo i dipendenti che sono nel reparto e filtro per dipendente, se c'è
if(!empty($u)) {
    $this->db->where('dipendenti.dipendenti_id', $u);
}
$this->db->where('dipendenti_lavoro_a_turni',1);
$this->db->where('dipendenti_attivo',1);
if(!empty($r)) {
    $this->db->join('rel_reparto_dipendenti', 'rel_reparto_dipendenti.dipendenti_id = dipendenti.dipendenti_id');
    $this->db->where('rel_reparto_dipendenti.reparti_id', $r);
}
$this->db->order_by('dipendenti_nome', 'asc');
$dipendenti = $this->db->get('dipendenti')->result_array();

if(!empty($u)) {
    $this->db->where('turni_di_lavoro_dipendente', $u);
}

$this->db->where("DATE_FORMAT(turni_di_lavoro_data_inizio, '%Y-%m') = '{$filtro_data}'", null, false);
$this->db->join('dipendenti', 'dipendenti_id = turni_di_lavoro_dipendente', 'LEFT');
$this->db->where('dipendenti.dipendenti_lavoro_a_turni',1);
$turni = $this->db->get('turni_di_lavoro')->result_array();
/*
debug($dipendenti);*/
$data = $data_xls = $meta = [];
$row = 0;
$autoincrementalKey = 0; // Inizializza la variabile per l'autoincremento

function getExcelColumnLabel($index) {
    $label = '';
    $dividend = $index + 1;

    while ($dividend > 0) {
        $modulo = ($dividend - 1) % 26;
        $letter = chr(65 + $modulo); // ASCII code for 'A' is 65
        $label = $letter . $label;
        $dividend = intval(($dividend - $modulo) / 26);
    }

    return $label;
}
foreach ($dipendenti as $dipendente) {
    //stampo i singoli reparti
    $dip = $dipendente['dipendenti_id'];

    $this->db->select('*');
    $this->db->where('dipendenti_id', $dipendente['dipendenti_id']);
    if ($this->input->get('r') && $this->input->get('r') != 0) {
        $this->db->where('reparti.reparti_id', $this->input->get('r'));
    }
    $this->db->join('reparti', 'rel_reparto_dipendenti.reparti_id = reparti.reparti_id', 'LEFT');
    $reparti = $this->db->get('rel_reparto_dipendenti')->result_array();
    
    foreach($reparti as $reparto){
        $reparto_nome = $reparto['reparti_nome'];

        //$dip = $dipendente['dipendenti_nome'].' '.substr($dipendente['dipendenti_cognome'], 0, 1) . '.';
        $reparto = $reparto['reparti_id'];

        if (empty($data[$autoincrementalKey])) {
            $data[$autoincrementalKey] = [
                '0' => "<a class='js_open_modal' style='color:black;text-decoration: none;' href='" . base_url("get_ajax/modal_form/modifica-dipendente/".$dip) . "?_size=large' title='Modifica dipendente'>".$dipendente['dipendenti_cognome'] . ' ' . substr($dipendente['dipendenti_nome'], 0, 1)."</a>",
                '1' => $reparto_nome,
                // Aggiungi qui ulteriori valori se necessario
            ];
            
        }
        $righeDipendente = array_filter($turni, function ($row) use ($dip, $reparto) {
            return isset($row['turni_di_lavoro_dipendente']) && $row['turni_di_lavoro_dipendente'] == $dip && isset($row['turni_di_lavoro_reparto']) && $row['turni_di_lavoro_reparto'] == $reparto;
        });

        foreach ($righeDipendente as $rigaDipendente) {
            $day = ltrim(date("d", strtotime($rigaDipendente['turni_di_lavoro_data_inizio'])), '0')+ $incrementale_reparto;
            $orario = $rigaDipendente['turni_di_lavoro_template'];
            
            if (!isset($data[$autoincrementalKey][$day])) {
                $data[$autoincrementalKey][$day] = $orario;
            } else {
                $data[$autoincrementalKey][$day] .= ';' . $orario;
            }
        }
        for ($i = 2; $i <= $days_in_month+$incrementale_reparto; $i++) {
            if (!isset($data[$autoincrementalKey][$i])) {
                $data[$autoincrementalKey][$i] = '';
            }
        }
        ksort($data[$autoincrementalKey]);
        
        $colonne = array_combine(array_keys($data[$autoincrementalKey]), array_map('getExcelColumnLabel', array_keys($data[$autoincrementalKey])));
        $incrementale = 0;
        if (!$this->input->get('r') || $this->input->get('r') == 0) {
            $incrementale = 1;
        }  

        foreach($colonne as $chiave => $colonna){
            
            $meta[$colonna . ($row + 1)] = [
                'entity_name' => 'turni_di_lavoro',
                'giorno' => $chiave - $incrementale,
                'id' => $dip,
                'reparto' => $reparto
            ];
            

        }
        // Assegna l'array temporaneo all'array principale

        /* PRENDO I PERMESSI APPROVATI DEL DIPENDENTE */
        $this->db->where('richieste_user_id',$dipendente['dipendenti_id']);
        $this->db->where('richieste_stato',2);
        $this->db->where("DATE_FORMAT(richieste_dal, '%Y-%m') = '{$filtro_data}'", null, false);
        $permessi = $this->db->get('richieste')->result_array();
        foreach($permessi as $permesso){
            $currentDate = strtotime($permesso['richieste_dal']);
            $endDate = strtotime($permesso['richieste_al']);

            while ($currentDate <= $endDate) {

                $day = ltrim(date("d", $currentDate), '0')+ $incrementale_reparto;
                if($permesso['richieste_tipologia'] == '1') { //Permesso
                    // per ora commento la richiesta di permesso.
                    /*$inizio = new DateTime($permesso['richieste_data_ora_inizio_calendar']);
                    $fine = new DateTime($permesso['richieste_data_ora_fine_calendar']);
                    $diff_date = $fine->diff($inizio);
                    $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h, 2);

                    $data[$dip][$reparto][$day] = 'Permesso';*/
                    //$data[$progetto][$nome_cliente][$i] = 'P';     
                } elseif ($permesso['richieste_tipologia'] == '2') { //Ferie
                    // Aggiungi il valore delle ferie all'array principale
                    $data[$autoincrementalKey][$day] = 'F';
        
                    if (!empty($permesso['richieste_sottotipologia'])) {
                        if ($permesso['richieste_sottotipologia'] == '1') { //Assenza ingiustificata
                            $data[$autoincrementalKey][$day] = 'aing';
                        }
                        if ($permesso['richieste_sottotipologia'] == '4') { //Infortunio
                            $data[$autoincrementalKey][$day] = 'inf';
                        }
                        if ($permesso['richieste_sottotipologia'] == '7') { //L. 104
                            $data[$autoincrementalKey][$day] = 'l104';
                        }
                    }
                } else { //Malattia
                    // Aggiungi il valore della malattia all'array principale
                    $data[$autoincrementalKey][$day] = 'M';
                }
                
                $currentDate = strtotime('+1 day', $currentDate);
            }
        }
        $autoincrementalKey++; // Incrementa la chiave autoincrementale
        $row++;
   }
}
//Riempio i buchi
$data_xls = $data;

$footer = ['Total', ''];
for ($i = 1; $i <= $days_in_month; $i++) {
    $footer[] = '';
}
$footer[] = '=SUMCOL(TABLE(), COLUMN())';
 ?>

                            <div class="btn-group sortableMenu">
                                <a href="<?php echo base_url_uploads("get_ajax/layout_modal/fasce-orarie?_size=large"); ?>" class="menu_item btn  btn-primary js_open_modal mr-10 br-4">
                                    <i class="far fa-clock"></i>
                                    Fasce orarie
                                </a>
                                <a class="menu_item btn  btn-primary mr-10" onclick="salvaTurni()"><i class="fas fa-save"></i> Salva modifiche </a>
                                <a class="menu_item btn  btn-primary" id="exportButton"><i class="fas fa-download"></i> Esporta tabella </a>

                            </div>
                            <br><br>

                            <div class="pad no-print">
                                <div class="callout callout-info" style="margin-bottom: 0!important; background-color: #3c8dbc!important;
                                         border-left: 5px solid #eee;
                                         border-left-width: 5px;
                                         border-left-style: solid;
                                         border-left-color: #357ca5!important;">
                                    <h4><i class="fa fa-info"></i> Informazioni:</h4>
                                    In questa pagina è possibile gestire i dipendenti che lavorano a turni. Per abilitare la funzione, del dettaglio del dipendente assicurati di aver selezionato "Si" alla voce "Lavoro a turni" presente sotto "Opzioni timbratura".
                                </div>
                            </div>
                            <div class="container-fluid turni">
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="legenda_container">
                                            <div class="text-uppercase legenda_item">
                                                <strong>Ferie</strong> <span class="legenda_square ferie"></span>
                                            </div>
                                            <div class="text-uppercase legenda_item">
                                                <strong>Domenica</strong> <span class="legenda_square dom"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <?php //debug($this->datab->getPermission($this->auth->get('users_id'))); ?>
                                    <div class="col-sm-3">
                                        <label for="presenze_month">Reparto</label>
                                        <select class="form-control select2_standard js_select2 select_reparto" name="reparto" id="reparto">
                                            <option value="0" selected="selected">Seleziona reparto</option>
                                            <?php foreach ($this->apilib->search('reparti') as $reparto) : ?>
                                            <option value="<?php echo $reparto['reparti_id']; ?>" <?php if (isset($r) && $reparto['reparti_id'] == $r) : ?>selected="selected" <?php endif; ?>><?php echo $reparto['reparti_nome']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-3">
                                        <label for="presenze_month">Dipendente</label>
                                        <select class="form-control select2_standard js_select2 select_dipendente" name="presenze_dipendente" id="presenze_dipendente" data-placeholder="<?php e('Choose template') ?>">
                                            <option value="" selected="selected">Seleziona dipendente</option>
                                            <?php foreach ($this->apilib->search('dipendenti',['dipendenti_lavoro_a_turni' => DB_BOOL_TRUE]) as $dipendente) : ?>
                                            <option value="<?php echo $dipendente['dipendenti_id']; ?>" <?php if (isset($u) && $dipendente['dipendenti_id'] == $u) : ?>selected="selected" <?php endif; ?>><?php echo $dipendente['dipendenti_cognome'] . ' ' . $dipendente['dipendenti_nome']; ?></option>

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
                                        <div class="xls_container">
                                            <div id="spreadsheet_presenze"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="permessi_container">
                                            <div id="permessi"></div>
                                            <h3>Permessi richiesti:</h3>
                                            <?php
                $grid_id = $this->datab->get_grid_id_by_identifier('richieste_permessi');
                $grid = $this->datab->get_grid($grid_id);
                
                $grid_layout = $grid['grids']['grids_layout'] ?: DEFAULT_LAYOUT_GRID;
                $this->load->view("pages/layouts/grids/{$grid_layout}", array(
                    'grid' => $grid,
                    'sub_grid' => null,
                    'layout_data_detail' => null,
                    'where' => $where,
                ));
                ?>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <style>
.jexcel_container {
    margin-bottom: 100px;
}

.jexcel>tbody>tr>td.readonly {
    color: #000000 !important;
}

.xls_container {
    width: 100%;
    overflow-x: scroll;
}

.legenda_container {
    display: flex;
    justify-content: flex-start;
    align-items: baseline;
    gap: 20px;
    float: right;
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
    background-color: rgb(234 179 8);
}

.legenda_square.dom {
    background-color: rgb(239 68 68);
}
                            </style>
                            <?php
$headers = array();//mi serve per l'export
?>
                            <script>
var meta = <?php echo json_encode($meta); ?>;

var data = <?php echo json_encode($data_xls); ?>;
var array_creato = [];

var changed = function(instance, cell, x, y, value) {
    var cellName = jexcel.getColumnNameFromId([x, y]);
    /*console.log('New change on cell ' + cellName + ' to: ' + value + '');
    console.log(x);
    console.log(y);*/

    // console.log(cell);
    //console.log(cell.getMeta());
    var meta = table2.getMeta(cellName);
    if (meta.id && meta.entity_name) {
        var id = meta.id;
        var giorno = meta.giorno;
        var reparto = meta.reparto;
        var values = value.trim().split(";");
        //values = values.slice(0, -1);
        if (values.length > 1 && values[values.length - 1] === "") {
            values = values.slice(0, -1);
        }
        if (values.length === 1 && values[0] !== "") {

            array_creato.push({
                'dipendente': id,
                'giorno': giorno,
                'reparto': reparto,
                'data': <?php echo json_encode($filtro_data); ?>,
                'valore': values[0]
            });
        } else {

            if (values.length === 0) {

                array_creato.push({
                    'dipendente': id,
                    'giorno': giorno,
                    'reparto': reparto,
                    'data': <?php echo json_encode($filtro_data); ?>,
                    'valore': "" // Imposta il valore vuoto
                });
            } else {
                values.forEach(function(val) {
                    array_creato.push({
                        'dipendente': id,
                        'giorno': giorno,
                        'reparto': reparto,
                        'data': <?php echo json_encode($filtro_data); ?>,
                        'valore': val
                    });
                });
            }
        }
    } else {
        alert("Questo campo non è modificabile tramite la tabella.");
    }


}

function salvaTurni() {
    $.ajax({
        url: base_url + 'modulo-hr/turni/modifico_turni/',
        type: "POST",
        data: {
            [token_name]: token_hash,
            turni: JSON.stringify(array_creato)
        },
        async: true,
        success: function(response, textStatus, jqXHR) {
            var responseData = JSON.parse(response); // Converti la risposta JSON in un oggetto JavaScript
            alert(responseData.txt); // Stampa il valore del campo "txt" dalla risposta JSON
            location.reload();
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log(jqXHR);
            console.log(textStatus);
            console.log(errorThrown);
        }
    });
}

var table2 = jspreadsheet(document.getElementById('spreadsheet_presenze'), {
    onload: function(el, instance) {
        //header background
        var x = 1 // column A
        $(instance.thead).find("tr td").css({
            'font-weight': 'bold',
            'font-size': '15px'
        });
    },
    updateTable: function(instance, cell, col, row, val, label, cellName) {
        //console.log(`cell: ${cell}, col: ${col}, row: ${row}, val: ${val}, label: ${label}, cellName: ${cellName}`);
        /*console.log(instance);
        console.log(cell);*/
        //Data attributes
        cell.dataset.html = 'true';
        cell.dataset.placement = 'auto';
        cell.dataset.trigger = 'hover';
        cell.dataset.container = 'body';
        cell.dataset.originalTitle = 'Dettaglio ore';

        //Coloro sfondo e testo per le ore lavorate
        if (col != '0') {
            /*if (parseFloat(label)) {*/
            cell.style.color = 'rgb(0 0 0)';
            /*cell.style.background = 'rgb(128 255 0)';*/
            cell.style.fontWeight = 'bold';
            /*}*/
        }
        if (col === 1 && cell.textContent.length > 8) {
            cell.style.fontWeight = 'normal';

        }

        //Colore sfondo e testo per permesso
        if (cell.textContent.includes('Permesso')) {
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
            cell.style.color = 'rgb(234 179 8)';
            cell.style.background = 'rgb(234 179 8)';
        }
        //Colore sfondo e testo per malattia
        if (val === 'M') {
            cell.style.color = 'rgb(4 120 87)';
            cell.style.background = 'rgb(4 120 87)';
        }
        //Colore sfondo e testo per domenica
        if (val.tipologia === 'dom') {
            cell.style.color = 'rgb(239 68 68)';
            cell.style.background = 'rgb(239 68 68)';
        }
    },
    data: data,
    contextMenu: false,
    defaultColAlign: 'left',
    meta: meta,
    /*footers: [
         //json_encode($footer);
    ],*/
    onchange: changed,
    columns: [{
            <?php $headers[] = 'Dipendente'; //mi serve per l'export ?>
            type: 'html',
            title: 'Dipendente',
            width: 90,
            readOnly: true,
        }, <?php
             if (!$this->input->get('r') || $this->input->get('r') == 0):
                $headers[] = 'Reparto'; //mi serve per l'export

            ?> {
            type: 'text',
            title: 'Reparto',
            width: 100,
            readOnly: true,

        },
        <?php endif; ?>
        <?php 
        for ($i = 1; $i <= $days_in_month; $i++) : ?> {
            <?php
              $giorno_completo = sprintf("%s-%02d-%02d", substr($filtro_data, 0, 4), substr($filtro_data, 5), $i);
              $giorno_settimana = strftime("%w", strtotime($giorno_completo));
              $iniziale_giorno = substr($giorni_settimana[$giorno_settimana], 0, 1);
              $headers[] = $i."($iniziale_giorno)"; //mi serve per l'export

            ?>
            type: 'dropdown',

                title: '<?php echo $i."($iniziale_giorno)"; ?>',
                source: <?php echo $turni_template_json; ?>,
                autocomplete: true,
                multiple: true
        },
        <?php endfor; 
        $headers_json = json_encode($headers);//mi serve per l'export
        ?>
    ],
});

//hide row number column
table2.hideIndex();


$(function() {
    var select = $('.turni [name="presenze_dipendente"], .turni [name="presenze_month"], .turni [name="presenze_year"], .turni [name="reparto"]');
    var operator_id, month_id, reparto = '';
    var currentURL = window.location.href; // Ottiene l'URL corrente

    select.on('change', function() {
        operator_id = $('.turni .select_dipendente').find(':selected').val();
        month_id = $('.turni .select_month').find(':selected').val();
        year_id = $('.turni .select_year').find(':selected').val();
        reparto = $('.turni .select_reparto').find(':selected').val();

        var url = new URL(currentURL);
        url.searchParams.set('m', month_id);

        if (operator_id) {
            url.searchParams.set('u', operator_id);
        } else {
            url.searchParams.delete('u');
        }

        if (year_id) {
            url.searchParams.set('y', year_id);
        } else {
            url.searchParams.delete('y');
        }

        if (reparto) {
            url.searchParams.set('r', reparto);
        } else {
            url.searchParams.delete('r');
        }

        window.location.href = url.href; // Reindirizza alla stessa pagina con le variabili aggiunte/sostituite
    });

});
$(document).ready(function() {
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
            background: 'red'
        });
    });
    /* CANCELLO IL BOLD AL REPARTO */
    /*
    var mainTds = $('td[title*="Reparto"]');
    var relatedTds = [];

    mainTds.each(function(index) {
        var mainTd = $(this);
        var dataX = mainTd.data('x');
        relatedTds.push($('td[data-x="' + dataX + '"]'));
    });


    relatedTds.forEach(function(td) {
        td.css({
            'font-weight': 'normal'
        });
    });
    mainTds.css({
    '   font-weight': 'bold'
    });*/

});
                            </script>
                            <script>
document.getElementById('exportButton').addEventListener('click', function() {
    var data = table2.getData(); // Ottieni i dati dalla tabella
    var headers = <?php echo $headers_json; ?>; // Recupera l'array di intestazioni dei giorni da PHP

    // Aggiungi le righe di intestazione alla tabella dei dati
    data.unshift(headers);

    // Conversione dei dati in formato CSV
    var csvContent = '';
    data.forEach(function(row) {
        for (var i = 0; i < row.length; i++) {
            row[i] = row[i].replace(/\n/g, ";");
        }
        csvContent += row.join(',') + '\n';
    });
    // Invia i dati CSV al server
    $.ajax({
        url: base_url + 'modulo-hr/turni/salva_csv',
        method: 'POST',
        data: {
            [token_name]: token_hash,
            csvContent: csvContent
        },
        success: function(response) {
            window.location.href = base_url + 'modulo-hr/turni/esporta_turni';
        },
        error: function(xhr, status, error) {
            console.error('Errore durante il salvataggio del file:', error);
        }
    });
    /*
    // Download del file CSV
    var blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    saveAs(blob, 'turni.csv');*/
});
                            </script>
                            <!--
<script src="https://unpkg.com/xlsx/dist/xlsx.full.min.js"></script>

<script>
document.getElementById("sheetjsexport").addEventListener('click', function() {
  /* Create worksheet from HTML DOM TABLE */
  var wb = XLSX.utils.table_to_book(document.getElementById("spreadsheet_presenze"));
  /* Export to file (start a download) */
  XLSX.writeFile(wb, "Turni.xlsx");
});
</script>
-->