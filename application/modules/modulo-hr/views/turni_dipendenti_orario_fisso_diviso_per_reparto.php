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

$filtro_data = $y.'-'.$m;
// imposto i where per la grid dei permessi:
$where = "DATE_FORMAT(richieste_dal, '%Y-%m') = '".$y."-".$m."' AND dipendenti_lavoro_a_turni = 0";
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

if (($this->input->get('u') && $this->input->get('u') != 0) || isset($value_id)) {
    
    $u = isset($value_id) ? $value_id : $this->input->get('u');

    $where .= " AND richieste_user_id = $u";

}





//prendo i dipendenti che sono nel reparto e filtro per dipendente, se c'è
if(!empty($u) || isset($value_id)) {
    $this->db->where('dipendenti.dipendenti_id', $u);
}
$this->db->where('dipendenti_lavoro_a_turni',0);
$this->db->where('dipendenti_attivo',1);
if(!empty($r)) {
    $this->db->join('rel_reparto_dipendenti', 'rel_reparto_dipendenti.dipendenti_id = dipendenti.dipendenti_id');
    $this->db->where('rel_reparto_dipendenti.reparti_id', $r);
}
$dipendenti = $this->db->get('dipendenti')->result_array();

if(!empty($u)) {
    $this->db->where('turni_di_lavoro_dipendente', $u);
}
$weekStartDate = date('Y-m-d', strtotime('sunday this week'));

//$this->db->where("DATE_FORMAT(turni_di_lavoro_data_inizio, '%Y-%m') = '{$filtro_data}'", null, false);
$this->db->where("turni_di_lavoro_data_inizio <= '{$weekStartDate}'", null, false);

$this->db->join('dipendenti', 'dipendenti_id = turni_di_lavoro_dipendente', 'LEFT');
$this->db->where('dipendenti.dipendenti_lavoro_a_turni',0);
$turni = $this->db->get('turni_di_lavoro')->result_array();
/*
debug($dipendenti);*/
$data = $data_xls = $meta = [];
$row = 0;
$autoincrementalKey = 0; // Inizializza la variabile per l'autoincremento

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
        $tot_mensile = 0;
        $reparto_nome = $reparto['reparti_nome'];

        //$dip = $dipendente['dipendenti_nome'].' '.substr($dipendente['dipendenti_cognome'], 0, 1) . '.';
        $reparto = $reparto['reparti_id'];

        if (empty($data[$autoincrementalKey])) {
            if(!empty($r)) {
                $data[$autoincrementalKey] = [
                    '0' => "<a class='js_open_modal' style='color:black;text-decoration: none;' href='" . base_url("get_ajax/modal_form/modifica-dipendente/".$dip) . "?_size=large' title='Modifica dipendente'>".$dipendente['dipendenti_cognome'] . ' ' . substr($dipendente['dipendenti_nome'], 0, 1)."</a>",
                    // Aggiungi qui ulteriori valori se necessario
                ];
            } else{
                $data[$autoincrementalKey] = [
                    '0' => "<a class='js_open_modal' style='color:black;text-decoration: none;' href='" . base_url("get_ajax/modal_form/modifica-dipendente/".$dip) . "?_size=large' title='Modifica dipendente'>".$dipendente['dipendenti_cognome'] . ' ' . substr($dipendente['dipendenti_nome'], 0, 1)."</a>",
                    '1' => $reparto_nome,
                    // Aggiungi qui ulteriori valori se necessario
                ];
            }
            
        }
        $righeDipendente = array_filter($turni, function ($row) use ($dip, $reparto) {
            return isset($row['turni_di_lavoro_dipendente']) && $row['turni_di_lavoro_dipendente'] == $dip && isset($row['turni_di_lavoro_reparto']) && $row['turni_di_lavoro_reparto'] == $reparto;
        });

        foreach ($righeDipendente as $rigaDipendente) {
            /* CALCOLO LE ORE TOTALI DEL TURNO */
            $this->db->where('orari_di_lavoro_ore_pausa_id', $rigaDipendente['turni_di_lavoro_pausa']);
            $orari_di_lavoro = $this->db->get('orari_di_lavoro_ore_pausa')->row_array();
            $ore_pausa = 0;
            if(!empty($orari_di_lavoro)){
                $ore_pausa = $orari_di_lavoro['orari_di_lavoro_ore_pausa_value'];
            }

            $inizio = new DateTime($rigaDipendente['turni_di_lavoro_ora_inizio']);
            $fine = new DateTime($rigaDipendente['turni_di_lavoro_ora_fine']);
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
            /* FINE CALCOLO LE ORE TOTALI DEL TURNO */

            $day = $rigaDipendente['turni_di_lavoro_giorno']+$incrementale_reparto;
            
            if (!isset($data[$autoincrementalKey][$day])) {
                $data[$autoincrementalKey][$day] = "<a class='js_open_modal' style='color:black;text-decoration: none;' href='" . base_url("get_ajax/modal_form/nuovo_turno_lavoro/" . htmlspecialchars($rigaDipendente['turni_di_lavoro_id']."")) . "' title='Modifica orario'>".$rigaDipendente['turni_di_lavoro_ora_inizio'] . " - " .$rigaDipendente['turni_di_lavoro_ora_fine']."</a>";

            } else {
                $data[$autoincrementalKey][$day] .= ';' . "<a class='js_open_modal' style='color:black;text-decoration: none;' href='" . base_url("get_ajax/modal_form/nuovo_turno_lavoro/" . htmlspecialchars($rigaDipendente['turni_di_lavoro_id'])) . "' title='Modifica orario'>".$rigaDipendente['turni_di_lavoro_ora_inizio'] . " - " .$rigaDipendente['turni_di_lavoro_ora_fine']."</a>";
            }
            
        }
        
        for ($i = 2; $i <= count($giorni_settimana)+$incrementale_reparto; $i++) {
            if (!isset($data[$autoincrementalKey][$i])) {
                //$data[$autoincrementalKey][$i] = '';
                $data[$autoincrementalKey][$i] = "<a class='js_open_modal' style='color:white;' href='" . base_url("get_ajax/modal_form/nuovo_turno_lavoro/?turni_di_lavoro_reparto=".$reparto."&turni_di_lavoro_giorno=".$i."&turni_di_lavoro_dipendente=" . htmlspecialchars($dip)) . "' title='Aggiungi orario'>testo nascosto</a>";
            } else {
                $data[$autoincrementalKey][$i] = $data[$autoincrementalKey][$i]."<a class='js_open_modal' style='color:white;' href='" . base_url("get_ajax/modal_form/nuovo_turno_lavoro/?turni_di_lavoro_reparto=".$reparto."&turni_di_lavoro_giorno=".$i."&turni_di_lavoro_dipendente=" . htmlspecialchars($dip)) . "' title='Aggiungi orario'>testo nascosto</a>";
            }
        }
        ksort($data[$autoincrementalKey]);
        $data[$autoincrementalKey][] = $tot_mensile;

        $colonne = array_combine(array_keys($data[$autoincrementalKey]), array_map('getExcelColumnLabel', array_keys($data[$autoincrementalKey])));
        $incrementale = 0;
        if (!$this->input->get('r') || $this->input->get('r') == 0) {
            $incrementale = 1;
        }  
        $autoincrementalKey++; // Incrementa la chiave autoincrementale
        $row++;
   }
}
$data_xls = $data;
$footer = ['Total', ''];
for ($i = 1; $i <= $days_in_month; $i++) {
    $footer[] = '';
}
$footer[] = '=SUMCOL(TABLE(), COLUMN())';
 ?>
<?php
if(!isset($value_id)) :
?>
<div class="btn-group sortableMenu">
    <a href="<?php echo base_url("get_ajax/modal_form/nuovo_turno_lavoro"); ?>" class="menu_item btn btn-primary js_open_modal"><i class="fas fa-plus"></i> Assegna turni</a>

</div>
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
    if(!isset($value_id)) :
    ?>
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
                <?php foreach ($this->apilib->search('dipendenti',['dipendenti_lavoro_a_turni' => DB_BOOL_FALSE]) as $dipendente) : ?>
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
    if(!isset($value_id)) :
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
        <?php
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