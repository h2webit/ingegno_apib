<?php
if (!$m = $this->input->get('m')) {
    $m = date('m');
}
if (!$u = $this->input->get('u')) {
    $u = $this->auth->get('users_id');
}
$m = str_pad($m, 2, '0', STR_PAD_LEFT);
$days_in_month = date('t', strtotime("2021-{$m}-15"));
$timesheets = $this->apilib->search('timesheet', [
    "EXTRACT(MONTH FROM timesheet_start_time) = '$m'",
    'EXTRACT(YEAR FROM timesheet_start_time) = EXTRACT(YEAR FROM CURRENT_TIMESTAMP)',
    'timesheet_member' => $u
]);
$data = $data_xls = [];
$row = 0;
foreach ($timesheets as $timesheet) {
    if (empty($data[$timesheet['projects_name']])) {
        $data[$timesheet['projects_name']] = [];
    }
    $day =    date("d", strtotime($timesheet['timesheet_start_time']));
    if (empty($data[$timesheet['projects_name']][$timesheet['tasks_title']][$day])) {
        $data[$timesheet['projects_name']][$timesheet['tasks_title']][$day] = $timesheet['timesheet_total_hours'];
    } else {
        $data[$timesheet['projects_name']][$timesheet['tasks_title']][$day] += $timesheet['timesheet_total_hours'];
    }
}

for ($i = 1; $i <= $days_in_month; $i++) { //Riempio i buchi
    $i = str_pad($i, 2, '0', STR_PAD_LEFT);
    foreach ($data as $project_name => $tasks) {
        foreach ($tasks as $task_title => $days_hours) {
            if (!array_key_exists($i, $days_hours)) {
                $data[$project_name][$task_title][$i] = '';
            }
        }
    }
}
foreach ($data as $project_name => $tasks) {
    foreach ($tasks as $task_title => $days_hours) {
        ksort($data[$project_name][$task_title]);
    }
}
//debug($data);
$tot_mensile = 0;
foreach ($data as $project_name => $tasks) {

    foreach ($tasks as $task_title => $days_hours) {
        $data_xls[$row][] = $project_name;
        $data_xls[$row][] = $task_title;
        foreach ($days_hours as $day => $hours) {
            if (!empty($hours)) {
                $tot_mensile += $hours;
                $hours = number_format((float)$hours, 2, ".", "");
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
//debug($data_xls);

?>


<script src="https://bossanova.uk/jspreadsheet/v4/jexcel.js"></script>
<link rel="stylesheet" href="https://bossanova.uk/jspreadsheet/v4/jexcel.css" type="text/css" />

<script src="https://jsuites.net/v4/jsuites.js"></script>
<link rel="stylesheet" href="https://jsuites.net/v4/jsuites.css" type="text/css" />

<div class="container-fluid">
    <div class="form-group row">
        <?php if (@$this->datab->getPermission($this->auth->get('users_id'))['permissions_group'] == 'Capo filiale' || $this->auth->is_admin()) : ?>
            <?php //debug($this->datab->getPermission($this->auth->get('users_id'))); 
            ?>
            <div class="col-sm-3">
                <label for=" prop_template">Operatore <span class="text-danger"></span></label>
                <select class="form-control js_select2 select_operator" name="timesheet_month" id="timesheet_month" data-placeholder="<?php e('Choose template') ?>">
                    <?php foreach ($this->apilib->search('users') as $user) : ?>
                        <option value="<?php echo $user['users_id']; ?>" <?php if ($user['users_id'] == $u) : ?>selected="selected" <?php endif; ?>><?php echo $user['users_last_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-sm-3">
            <label for=" prop_template">Mese <span class="text-danger"></span></label>
            <select class="form-control js_select2 select_month" name="timesheet_month" id="timesheet_month" data-placeholder="<?php e('Choose template') ?>">
                <?php for ($i = 1; $i <= 12; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php if ($i == $m) : ?>selected="selected" <?php endif; ?>><?php echo mese_testuale($i); ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <div id="spreadsheet2"></div>
        </div>
    </div>

</div>

<style>
    .jexcel>tbody>tr>td.readonly {
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

    var table2 = jspreadsheet(document.getElementById('spreadsheet2'), {
        onload: function(el, instance) {
            //header background
            var x = 1 // column A
            $(instance.thead).find("tr td").css({
                'font-weight': 'bold',
                'font-size': '16px'
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
                width: 100,
            },
            {
                type: 'text',
                title: 'Descrizione attività',
                width: 100,
            },
            <?php for ($i = 1; $i <= $days_in_month; $i++) : ?> {
                    type: 'numeric',
                    title: '<?php echo $i; ?>',
                    width: 15,
                    readOnly: true,
                    align: 'center'
                },
            <?php endfor; ?> {
                type: 'numeric',
                title: 'TOT',
                width: 25,
                readOnly: true,
                align: 'center'
            },
        ],
    });

    //hide row number column
    table2.hideIndex();




    $(function() {
        var select = $('[name="timesheet_month"]');
        var operator_id, month_id = '';
        var baseURL = '<?php echo base_url('main/layout/tabella-operatore'); ?>';

        select.on('change', function() {
            operator_id = $('.select_operator').find(':selected').val();
            month_id = $('.select_month').find(':selected').val();
            if (operator_id == undefined) { //caso in cui non ho accesso alla select degli operatori
                window.location.replace(baseURL + "?m=" + month_id);
            } else {
                window.location.replace(baseURL + "?u=" + operator_id + "&m=" + month_id);
            }
        });

    })
</script>