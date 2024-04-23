<style>
    .jexcel>tbody>tr>td.readonly {
        color: black !important;
    }
</style>
<?php $this->layout->addModuleStylesheet('modulo-hr', 'vendor/jsuites/jsuites.css'); ?>
<?php $this->layout->addModuleStylesheet('modulo-hr', 'vendor/jexcel/jexcel.css'); ?>

<?php $this->layout->addModuleJavascript('modulo-hr', 'vendor/jsuites/jsuites.js'); ?>
<?php $this->layout->addModuleJavascript('modulo-hr', 'vendor/jexcel/index.js'); ?>
<!-- Per salvataggio tabella !-->
<?php $this->layout->addModuleJavascript('modulo-hr', 'js/FileSaver.min.js'); ?>
<?php $this->layout->addModuleJavascript('modulo-hr', 'js/xlsx.full.min.js'); ?>
<?php
$giorni_settimana = $this->db->get('orari_di_lavoro_giorno')->result_array();

if (!$m = $this->input->get('m')) {
    $m = date('m');
}
if (!$y = $this->input->get('y')) {
    $y = date('Y');
}
$m = str_pad($m, 2, '0', STR_PAD_LEFT);
$days_in_month = date('t', strtotime("{$y}-{$m}-15"));

$filtro_data = $y . '-' . $m;
// imposto i where per la grid dei permessi:
$where = "DATE_FORMAT(richieste_dal, '%Y-%m') = '" . $y . "-" . $m . "' AND dipendenti_lavoro_a_turni = 0";

if ($this->input->get('r') && $this->input->get('r') != 0) {
    $r = $this->input->get('r');
    $where .= " AND (richieste_user_id IS NULL OR richieste_user_id IN (
        SELECT dipendenti_id
        FROM rel_reparto_dipendenti
        WHERE reparti_id = $r
    ))";
}

if (($this->input->get('u') && $this->input->get('u') != 0) || isset($value_id)) {

    $u = isset($value_id) ? $value_id : $this->input->get('u');

    $where .= " AND richieste_user_id = $u";
}





//prendo i dipendenti che sono nel reparto e filtro per dipendente, se c'è
if (!empty($u) || isset($value_id)) {
    $this->db->where('dipendenti.dipendenti_id', $u);
}
$this->db->where('dipendenti_lavoro_a_turni', 0);
$this->db->where('dipendenti_attivo', 1);
if (!empty($r)) {

    $this->db->join('rel_reparto_dipendenti', 'rel_reparto_dipendenti.dipendenti_id = dipendenti.dipendenti_id');
    $this->db->where('rel_reparto_dipendenti.reparti_id', $r);
}
$dipendenti = $this->db->get('dipendenti')->result_array();

if (!empty($u)) {
    $this->db->where('turni_di_lavoro_dipendente', $u);
}
$weekStartDate = date('Y-m-d', strtotime('sunday this week'));

//$this->db->where("DATE_FORMAT(turni_di_lavoro_data_inizio, '%Y-%m') = '{$filtro_data}'", null, false);
$this->db->where("turni_di_lavoro_data_inizio <= '{$weekStartDate}'", null, false);

$this->db->join('dipendenti', 'dipendenti_id = turni_di_lavoro_dipendente', 'LEFT');
$this->db->where('dipendenti.dipendenti_lavoro_a_turni', 0);
$turni = $this->db->get('turni_di_lavoro')->result_array();
/*
debug($dipendenti);*/
$data = $data_xls = [];
$row = 0;
foreach ($dipendenti as $dipendente) {
    $dip = $dipendente['dipendenti_id'];

    if (empty($data[$dip])) {
        $data[$dip] = [];
    }

    /* PRENDO I TURNI DEL DIPENDENTE */
    $righeDipendente = array_filter($turni, function ($row) use ($dip) {
        return isset($row['turni_di_lavoro_dipendente']) && $row['turni_di_lavoro_dipendente'] == $dip;
    });
    foreach ($righeDipendente as $rigaDipendente) {
        //debug($rigaDipendente['turni_di_lavoro_template']);
        $day = $rigaDipendente['turni_di_lavoro_giorno'];
        //$orario = $rigaDipendente['turni_di_lavoro_ora_inizio'] . " - " .$rigaDipendente['turni_di_lavoro_ora_fine'];
        if (!isset($data[$dip][$day])) {
            $data[$dip][$day] = $rigaDipendente['turni_di_lavoro_id'];
        } else {
            $data[$dip][$day] .= ';' . $rigaDipendente['turni_di_lavoro_id'];
        }
    }
};

//Riempio i buchi
for ($i = 1; $i <= count($giorni_settimana); $i++) {
    //$i = str_pad($i, 2, '0', STR_PAD_LEFT);

    foreach ($data as $dipendente => $giorno) {
        if (!array_key_exists($i, $giorno)) {
            $data[$dipendente][$i] = '';
        }
    }
}
foreach ($data as $dipendente => $giorno) {
    /*foreach($giorno as $giorno_numerico => $giorno_dettaglio){

        $giorno_completo = sprintf("%s-%02d-%02d", substr($filtro_data, 0, 4), substr($filtro_data, 5), $giorno_numerico);
        $giorno_settimana = strftime("%w", strtotime($giorno_completo));

        if($giorno_settimana == 0){
            //$data[$dipendente][$giorno_numerico] = 'dom - '.$data[$dipendente][$giorno_numerico];
        }
    }*/

    ksort($data[$dipendente]);
}
//$tot_mensile = 0;
$meta = [];
//debug($data);
/*
foreach ($data as $dipendente => $giorno) {
    //debug($giorno);
    $colonne = array_combine(array_keys($giorno), array_map('getExcelColumnLabel', array_keys($giorno)));
    foreach($colonne as $chiave => $colonna){
        $meta[$colonna . ($row +1)] = [
            'field_name' => 'tasks_status',
            'entity_name' => 'tasks',
            'giorno' => $chiave,
            'id' => $dipendente
        ];
        

    }
    $row++;
}*/
foreach ($data as $dipendente => $giorno) {
    $tot_mensile = 0;
    /* $giorno = array_combine(array_keys($giorno), array_map('getExcelColumnLabel', array_keys($giorno)));

    foreach ($giorno as $key => &$value) {
        $giorno = array_combine(array_keys($giorno), array_map('getExcelColumnLabel', array_keys($giorno)));

    }*/
    // debug($giorno);
    //$colonne = array_combine(array_keys($giorno), array_map('getExcelColumnLabel', array_keys($giorno)));
    /*foreach($colonne as $colonna){
        $meta[$colonna . ($row +1)] = [
            'field_name' => 'tasks_status',
            'entity_name' => 'tasks',
            'giorno' => $giorno[$row],
            'id' => $dipendente
        ];
    }*/
    /*$meta['D' . ($row + 1)] = [
        'field_name' => 'tasks_start_date',
        'entity_name' => 'tasks',
        'id' => $task['tasks_id']
    ];
    $meta['E' . ($row + 1)] = [
        'field_name' => 'tasks_due_date',
        'entity_name' => 'tasks',
        'id' => $task['tasks_id']
    ];
    $meta['F' . ($row + 1)] = [
        'field_name' => 'tasks_delivery_date',
        'entity_name' => 'tasks',
        'id' => $task['tasks_id']
    ];
    $meta['G' . ($row + 1)] = [
        'field_name' => 'tasks_priority',
        'entity_name' => 'tasks',
        'id' => $task['tasks_id']
    ];
    $meta['M' . ($row + 1)] = [
        'field_name' => 'tasks_hidden',
        'entity_name' => 'tasks',
        'id' => $task['tasks_id']
    ];*/
    /* Scrivo il dipendente nome e iniziale cognome */
    $risultati = array_filter($dipendenti, function ($item) use ($dipendente) {
        return $item['dipendenti_id'] == $dipendente;
    });

    if (!empty($risultati)) {
        foreach ($risultati as $risultato) {
            //debug($risultato);
            //if ($this->input->get('r') == null) {
            if (!$this->input->get('r') || $this->input->get('r') == 0) {
                // prendo i reparti al quale è associato il dipendente
                $this->db->where("dipendenti_id = '{$risultato['dipendenti_id']}'");
                $this->db->join('reparti', 'reparti.reparti_id = rel_reparto_dipendenti.reparti_id');
                if (!empty($r)) {
                    $this->db->where('rel_reparto_dipendenti.reparti_id', $r);
                }
                $reparti = $this->db->get('rel_reparto_dipendenti')->result_array();
            }

            //$dip = $risultato['dipendenti_nome'] . ' ' . substr($risultato['dipendenti_cognome'], 0, 1).".";
            $dip = "<a class='js_open_modal' style='color:black;text-decoration: none;' href='" . base_url("get_ajax/modal_form/modifica-dipendente/" . $risultato['dipendenti_id']) . "?_size=large' title='Modifica dipendente'>" . $risultato['dipendenti_cognome'] . ' ' . substr($risultato['dipendenti_nome'], 0, 1) . "</a>";


            //if ($this->input->get('r') == null) {
            if (!$this->input->get('r') || $this->input->get('r') == 0) {
                $dipendente_reparti = [];
                foreach ($reparti as $reparto) {
                    $dipendente_reparti[] = $reparto['reparti_nome'];
                }
            }
        }
    }

    $data_xls[$row][] = $dip;
    //if ($this->input->get('r') == null) {
    if (!$this->input->get('r') || $this->input->get('r') == 0) {
        sort($dipendente_reparti);

        $data_xls[$row][] = implode("\n", $dipendente_reparti);
    }
    foreach ($giorno as $day => $hours) {

        if (empty($hours)) {
            $data_xls[$row][] = "<a class='js_open_modal' style='color:white;' href='" . base_url("get_ajax/modal_form/nuovo_turno_lavoro_no_reparto/?turni_di_lavoro_giorno=" . $day . "&turni_di_lavoro_dipendente=" . $dipendente) . "' title='Aggiungi orario'>testo nascosto</a>";
        } else {
            // Assicuriamoci che $hours sia una stringa e sia formattato correttamente per la query
            $hours_str = (string) $hours;

            // Dividiamo $hours_str in un array di valori separati
            $hours_array = explode(';', $hours_str);

            // Array per contenere i risultati delle query
            $turni_lavoro = [];
            // Eseguiamo una query separata per ciascun valore di $hours_array
            foreach ($hours_array as $hour) {
                $this->db->where("turni_di_lavoro_id", $hour, true);
                $turno = $this->db->get('turni_di_lavoro')->row_array();
                $turni_lavoro[] = $turno;
            }

            // Concateniamo i risultati nella stessa riga dell'array $data_xls
            $row_data = "";
            foreach ($turni_lavoro as $index => $turno) {
                // Aggiungiamo il punto e virgola solo se non è il primo elemento
                if ($index > 0) {
                    $row_data .= " ; ";
                }
                /* per calcolare il totale settimanale */
                $this->db->where('orari_di_lavoro_ore_pausa_id', $turno['turni_di_lavoro_pausa']);
                $orari_di_lavoro = $this->db->get('orari_di_lavoro_ore_pausa')->row_array();
                $ore_pausa = 0;
                if (!empty($orari_di_lavoro)) {
                    $ore_pausa = $orari_di_lavoro['orari_di_lavoro_ore_pausa_value'];
                }
                // Converti le stringhe in oggetti DateTime

                $inizio = new DateTime($turno['turni_di_lavoro_ora_inizio']);
                $fine = new DateTime($turno['turni_di_lavoro_ora_fine']);
                // Verifica se l'ora di fine è successiva all'ora di inizio
                if ($fine < $inizio) {
                    // Aggiungi un giorno all'ora di fine per gestire il passaggio alla giornata successiva
                    $fine->modify('+1 day');
                }
                $diff_date = $fine->diff($inizio);
                $hours = round(($diff_date->s / 3600) + ($diff_date->i / 60) + $diff_date->h + ($diff_date->days * 24), 2);
                // Aggiungi la durata della pausa
                $hours -= $ore_pausa;
                $tot_mensile += $hours;
                $row_data .= "<a class='js_open_modal' style='color:black;text-decoration: none;' href='" . base_url("get_ajax/modal_form/nuovo_turno_lavoro_no_reparto/" . htmlspecialchars($turno['turni_di_lavoro_id'])) . "' title='Modifica orario'>" . $turno['turni_di_lavoro_ora_inizio'] . " - " . $turno['turni_di_lavoro_ora_fine'] . "</a>";
            }


            // Aggiungiamo il risultato nella riga dell'array $data_xls
            $data_xls[$row][] = $row_data . "<a class='js_open_modal' style='color:white;' href='" . base_url("get_ajax/modal_form/nuovo_turno_lavoro_no_reparto/?turni_di_lavoro_giorno=" . $day . "&turni_di_lavoro_dipendente=" . htmlspecialchars($dipendente)) . "' title='Aggiungi orario'>testo nascosto</a>";
        }
        /*
        if (empty($hours)) {
            $data_xls[$row][] = "<a class='js_open_modal' style='color:white;' href='" . base_url("get_ajax/modal_form/nuovo_turno_lavoro_no_reparto/?turni_di_lavoro_giorno=".$day."&turni_di_lavoro_dipendente=" . $dipendente) . "' title='Aggiungi orario'>testo nascosto</a>";
        } else {
            debug($hours);
            $this->db->where("turni_di_lavoro_id = '{$hours}'", null, false);
            $turno = $this->db->get('turni_di_lavoro')->row_array();
            $data_xls[$row][] = "<a class='js_open_modal' style='color:black;text-decoration: none;' href='" . base_url("get_ajax/modal_form/nuovo_turno_lavoro_no_reparto/" . $hours) . "' title='Modifica orario'>".$turno['turni_di_lavoro_ora_inizio'] . " - " .$turno['turni_di_lavoro_ora_fine']."</a><a class='js_open_modal' style='color:white;' href='" . base_url("get_ajax/modal_form/nuovo_turno_lavoro_no_reparto/?turni_di_lavoro_giorno=".$day."&turni_di_lavoro_dipendente=" . $dipendente) . "' title='Aggiungi orario'>testo nascosto</a>";
        }*/
    }
    $data_xls[$row][] = round($tot_mensile, 2);

    //$data_xls[$row][] = round($tot_mensile, 2);
    $row++;
}
$footer = ['Total', ''];
for ($i = 1; $i <= $days_in_month; $i++) {
    $footer[] = '';
}
$footer[] = '=SUMCOL(TABLE(), COLUMN())';
?>
<?php
if (!isset($value_id)) :
?>
    <div class="btn-group sortableMenu">
        <a href="<?php echo base_url("get_ajax/modal_form/nuovo_turno_lavoro_no_reparto"); ?>" class="menu_item btn btn-primary js_open_modal mr-10 br-4"><i class="fas fa-plus"></i> Assegna turni</a>

        <a href="<?php echo base_url("get_ajax/layout_modal/orari-massivi"); ?>" class="menu_item btn btn-primary js_open_modal mr-10"><i class="fas fa-plus"></i> Orari massivi</a>
    </div>
    <br><br>
    <div class="pad no-print">
        <div class="callout callout-info" style="margin-bottom: 0!important; background-color: #3c8dbc!important;
                                            border-left: 5px solid #eee;
                                            border-left-width: 5px;
                                            border-left-style: solid;
                                            border-left-color: #357ca5!important;">
            <h4><i class="fa fa-info"></i> Informazioni:</h4>
            In questa pagina è possibile gestire i dipendenti che lavorano a orari fissi. Per abilitare la funzione, del dettaglio del dipendente assicurati di aver selezionato "No" alla voce "Lavoro a turni" presente sotto "Opzioni timbratura".
        </div>
    </div>
<?php
endif;
?>
<br><br>
<div class="container-fluid fisso">
    <?php
    if (!isset($value_id)) :
    ?>
        <div class="form-group row">
            <?php //debug($this->datab->getPermission($this->auth->get('users_id'))); 
            ?>
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
                    <?php foreach ($this->apilib->search('dipendenti', ['dipendenti_lavoro_a_turni' => DB_BOOL_FALSE]) as $dipendente) : ?>
                        <option value="<?php echo $dipendente['dipendenti_id']; ?>" <?php if (isset($u) && $dipendente['dipendenti_id'] == $u) : ?>selected="selected" <?php endif; ?>><?php echo $dipendente['dipendenti_nome'] . ' ' . $dipendente['dipendenti_cognome']; ?></option>

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
    <?php
    endif;
    ?>
    <div class="row">
        <div class="col-sm-12">
            <div class="xls_container_fissi">
                <div id="spreadsheet_presenze_fissi"></div>
            </div>
        </div>
    </div>
    <?php
    if (!isset($value_id)) :
    ?>
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
    <?php
    endif;
    ?>


</div>

<style>
    .xls_container_fissi {
        width: 100%;
        overflow-x: scroll;
    }
</style>
<script>
    var data = <?php echo json_encode($data_xls); ?>;
    console.log(data);


    // A custom method to SUM all the cells in the current column
    /*
    var SUMCOL = function(instance, columnId) {
        var total = 0;
        for (var j = 0; j < instance.options.data.length; j++) {
            console.log(Number(instance.records[j][columnId - 1].innerHTML))
            if (Number(instance.records[j][columnId - 1].innerHTML)) {
                total += Number(instance.records[j][columnId - 1].innerHTML);
            }
        }
        return total.toFixed(2);
    }*/

    var table3 = jspreadsheet(document.getElementById('spreadsheet_presenze_fissi'), {
        onload: function(el, instance) {
            //header background
            var x = 1 // column A
            $(instance.thead).find("tr td").css({
                'font-weight': 'bold',
                'font-size': '15px'
            });
        },
        data: data,
        contextMenu: false,
        defaultColAlign: 'left',
        /*footers: [
             //json_encode($footer);
        ],*/
        columns: [{
                type: 'html',
                title: 'Dipendente',
                width: 90,
                readOnly: true,
            },

            <?php //if (!$this->input->get('r')) : 
            if (!$this->input->get('r') || $this->input->get('r') == 0) :
            ?> {
                    type: 'text',
                    title: 'Reparto',
                    width: 100,
                    readOnly: true,

                },
            <?php endif; ?>
            <?php
            foreach ($giorni_settimana as $giorno_settimana) :
            ?> {
                    type: 'html',
                    title: '<?php echo $giorno_settimana["orari_di_lavoro_giorno_value"]; ?>',
                    width: 100,
                    readOnly: true,
                },
            <?php endforeach; ?> {
                type: 'html',
                title: 'Totale',
                width: 90,
                readOnly: true,
            }
        ],
    });

    //hide row number column
    table3.hideIndex();


    $(function() {
        var select = $('.fisso [name="presenze_dipendente"], .fisso [name="presenze_month"],  .fisso [name="presenze_year"], .fisso [name="reparto"]');
        var operator_id, month_id, reparto = '';
        var currentURL = window.location.href; // Ottiene l'URL corrente

        select.on('change', function() {
            operator_id = $('.fisso .select_dipendente').find(':selected').val();
            month_id = $('.fisso .select_month').find(':selected').val();
            year_id = $('.fisso .select_year').find(':selected').val();
            reparto = $('.fisso .select_reparto').find(':selected').val();

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
</script>