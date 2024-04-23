<?php
if ($this->datab->module_installed('projects') AND $this->datab->module_installed('customers')):

if (!$m = $this->input->get('m')) {
    $m = date('m');
}
if (!$y = $this->input->get('y')) {
    $y = date('Y');
}
if (!$u = $this->input->get('u')) {
    //$u = $this->auth->get('users_id');
    $u = $this->auth->get('dipendenti_id');
}


// Recupero filtri per dipendente selezionato nei filtri
$filters = $this->session->userdata(SESS_WHERE_DATA);

if (!empty($filters['filter-presenze'])) {
    $filtri = $filters['filter-presenze'];

    $presenze_dipendente_field_id = $this->datab->get_field_by_name('presenze_dipendente')['fields_id'];

    if (!empty($filtri[$presenze_dipendente_field_id]['value']) && $filtri[$presenze_dipendente_field_id]['value'] !== '-1') {
        $filtro_dipendente_id = $filtri[$presenze_dipendente_field_id]['value'];
        $u = $filtro_dipendente_id;
    }
}



$m = str_pad($m, 2, '0', STR_PAD_LEFT);
$days_in_month = date('t', strtotime("{$y}-{$m}-15"));

$filtro_data = $y.'-'.$m;

$this->db->where('presenze_dipendente', $u);
//$this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')", null, false);
$this->db->where("DATE_FORMAT(presenze_data_inizio, '%Y-%m') = '{$filtro_data}'", null, false);
$this->db->where('presenze_cliente IS NOT NULL');
$this->db->join('dipendenti', 'dipendenti_id = presenze_dipendente', 'LEFT');
$this->db->join('customers', 'customers_id = presenze_cliente', 'LEFT');
$this->db->join('projects', 'projects_id = presenze_commessa', 'LEFT');

$presenze = $this->db->get('presenze')->result_array();
//dump($presenze);

$data = $data_xls = [];
$row = 0;
foreach ($presenze as $presenza) {
    if (empty($data[$presenza['projects_name']])) {
        $data[$presenza['projects_name']] = [];
    }
    
    $day = date("d", strtotime($presenza['presenze_data_inizio']));

    //Prendo pausa della giornata di oggi
    $ignora_pausa = $presenza['dipendenti_ignora_pausa'] ?? DB_BOOL_FALSE;
    $weekday = date('N', strtotime($presenza['presenze_data_inizio']));

    $orario_lavoro = $this->db
    ->join("orari_di_lavoro_ore_pausa", "orari_di_lavoro_ore_pausa = orari_di_lavoro_ore_pausa_id", "left")
    ->join("orari_di_lavoro_giorno", "orari_di_lavoro_giorno_numero = '".$weekday."'", "left")
    ->where("orari_di_lavoro_dipendente", $presenza['presenze_dipendente'])
    ->get("orari_di_lavoro")->row_array();

    if($ignora_pausa == DB_BOOL_FALSE) {
        if(!empty($orario_lavoro) && !empty($orario_lavoro['orari_di_lavoro_ore_pausa'])) {
            $pausa = $orario_lavoro['orari_di_lavoro_ore_pausa_value'] ?? 1;
        } else {
            $pausa = 1;
        }
    } else {
        $pausa = 0;
    }

    $ore_lavorate = ($presenza['presenze_ore_totali'] - $pausa) > 0 ? ($presenza['presenze_ore_totali'] - $pausa) : 0;
    //$ore_lavorate = ($presenza['presenze_ore_totali'] - $presenza['presenze_straordinario']) > 0 ? ($presenza['presenze_ore_totali'] - $presenza['presenze_straordinario']) : 0;
    
    if (empty($data[$presenza['projects_name']][$presenza['customers_full_name']][$day])) {
        $data[$presenza['projects_name']][$presenza['customers_full_name']][$day] = $ore_lavorate;
    } else {
        $data[$presenza['projects_name']][$presenza['customers_full_name']][$day] += $ore_lavorate;
    }
}


//Riempio i buchi
for ($i = 1; $i <= $days_in_month; $i++) {
    $i = str_pad($i, 2, '0', STR_PAD_LEFT);
    foreach ($data as $progetto => $cliente) {
        foreach ($cliente as $nome_cliente => $days_hours) {
            if (!array_key_exists($i, $days_hours)) {
                $data[$progetto][$nome_cliente][$i] = '';
            }
        }
    }
}

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
            if (!empty($hours)) {
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



$footer = ['Total', ''];
for ($i = 1; $i <= $days_in_month; $i++) {
    $footer[] = '';
}
$footer[] = '=SUMCOL(TABLE(), COLUMN())';

?>

<?php $this->layout->addModuleStylesheet('modulo-hr', 'vendor/jsuites/jsuites.css'); ?>
<?php $this->layout->addModuleStylesheet('modulo-hr', 'vendor/jexcel/jexcel.css'); ?>

<?php $this->layout->addModuleJavascript('modulo-hr', 'vendor/jsuites/jsuites.js'); ?>
<?php $this->layout->addModuleJavascript('modulo-hr', 'vendor/jexcel/index.js'); ?>

<div class="container-fluid">
    <div class="form-group row">
        <?php //debug($this->datab->getPermission($this->auth->get('users_id'))); ?>
        <div class="col-sm-3 hidden">
            <label for="presenze_month">Dipendente</label>
            <select class="form-control select2_standard js_select2 select_dipendente" name="presenze_dipendente" id="presenze_dipendente" data-placeholder="<?php e('Choose template') ?>">
                <?php foreach ($this->apilib->search('dipendenti') as $dipendente) : ?>
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
#spreadsheet_presenze .jexcel>tbody>tr>td.readonly {
    color: #000000 !important;
}
</style>

<script>
var data = <?php echo json_encode($data_xls); ?>;

// A custom method to SUM all the cells in the current column
var SUMCOL = function(instance, columnId) {
    var total = 0;
    for (var j = 0; j < instance.options.data.length; j++) {
        if (Number(instance.records[j][columnId - 1].innerHTML)) {
            total += Number(instance.records[j][columnId - 1].innerHTML);
        }
    }
    return total.toFixed(2);
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
            type: 'numeric',
            title: '<?php echo $i; ?>',
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
    var select = $('[name="presenze_dipendente"], [name="presenze_month"], [name="presenze_year"]');
    var operator_id, month_id = '';
    var baseURL = '<?php echo base_url('main/layout/presenze-recap'); ?>';

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
})
</script>
<?php
endif;
?>