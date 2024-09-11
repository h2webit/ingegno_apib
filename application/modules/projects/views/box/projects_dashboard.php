<?php
$this->load->model('tasks/tasks');

$tasks_limit = 1000;
$max_tasks_per_project = 50;
$interval_done_task = 5;

$grid = $this->db->where('grids_append_class', 'tasks_dashboard_commesse')->get('grids')->row_array();
$where_tasks = [$this->datab->generate_where("grids", $grid['grids_id'], null)];


$users = $this->apilib->search('users', ['users_show_in_kanban' => DB_BOOL_TRUE, 'users_active' => DB_BOOL_TRUE]);
$users_map_id = array_key_map_data($users, 'users_id');

$edit_task_form_id = $this->datab->get_form_id_by_identifier('task_form');


$data = [];
$tasks = $this->apilib->search('tasks', array_merge([
    "(
        tasks_status IN (
            SELECT tasks_status_id FROM tasks_status WHERE tasks_status_done_status <> '" . DB_BOOL_TRUE . "'
        )  OR (tasks_status = '1' AND tasks_done_date >= DATE(NOW()) - INTERVAL $interval_done_task DAY)
    )",
    //"tasks_due_date <= DATE(NOW()) + INTERVAL 30 DAY",
    "(tasks_hidden = 0 OR tasks_hidden IS NULL)",
    //'tasks_status' => 4,
], $where_tasks), $tasks_limit, 0, 'tasks_priority DESC, tasks_due_date ASC, -tasks_delivery_date DESC');

$_working_tasks = $this->apilib->search('timesheet', [
    "(timesheet_end_time IS NULL OR timesheet_end_time = '')"
]);
$working_tasks = [];
foreach ($_working_tasks as $task) {
    $working_tasks[$task['timesheet_task']] = $task['timesheet_member'];
}
//Prendere anche i ticket

foreach ($tasks as $key => $task) {
    $task['tasks_worked_hours'] = $this->db->query("
        SELECT SUM(timesheet_total_hours) AS total 
        FROM timesheet 
        WHERE timesheet_task = '{$task['tasks_id']}'
    ")->row()->total;

    if (empty($task['projects_name'])) {
        $task['projects_name'] = "NO NAME (ID #{$task['tasks_project_id']})";
    }
    //Limito a 8 le task per progetto (sopra sono ordinate per due date)
    if (empty($data[$task['projects_name']]) || count($data[$task['projects_name']]) < $max_tasks_per_project) {
        $data[$task['projects_name']][] = $task;
    } else {
        continue;
    }
}

$xls_data = [];
$colspanned_rows = [];
$row = 0;
$columns = [
    //TODO: 
];



$styles = $meta = [];

$_status = $this->apilib->search('tasks_status');
$task_status = [];
foreach ($_status as $status) {
    $task_status[] = [
        'id' => $status['tasks_status_id'],
        'name' => $status['tasks_status_status'],
    ];
}

$_priority = $this->apilib->search('tasks_priority');
$task_priority = [];
foreach ($_priority as $priority) {
    $task_priority[] = [
        'id' => $priority['tasks_priority_id'],
        'name' => $priority['tasks_priority_value'],
    ];
}



foreach ($data as $project_name => $tasks) {

    $project_name = strtoupper($project_name);

    $hours_balance = $this->db->query("SELECT SUM(billable_hours_hours) as s FROM billable_hours WHERE billable_hours_project_id = '{$tasks[0]['tasks_project_id']}'")->row()->s;
    $hours_balance = (empty($hours_balance)) ? 0.00 : $hours_balance;
    $hours_balance = number_format($hours_balance, 1, '.', '');
    if ($hours_balance <= 0) {
        $xls_data[$row] = [
            '<a style="color:#000000;font-weight: bold;" style="margin-right:10px;" target="_blank" href="' . base_url("main/layout/project-detail/" . $tasks[0]['tasks_project_id']) . '">' . $project_name . '</a> | <a style="margin-left:10px;margin-right:10px" class="js_open_modal" href="' . base_url("get_ajax/modal_form/236?tasks_project_id=" . $tasks[0]['tasks_project_id']) . '"><span class="badge" style="color:#ffffff; background-color:#46b352;"><i class="fas fa-plus"></i></span></a> | <span style="color:red; margin-left:10px">' . $hours_balance . ' ore</span>',
            '', '', '', '', '', '', '', '', '', '', '', ''
            //TODO: Pallino rosso da usare per insolvenze cliente
            //<i class="fas fa-circle" style="margin-left: 10px; color:#d92307"></i>
        ];
    } else {
        $xls_data[$row] = [
            '<a style="color:#000000;font-weight: bold;" style="margin-right:10px;" target="_blank" href="' . base_url("main/layout/project-detail/" . $tasks[0]['tasks_project_id']) . '">' . $project_name . '</a> | <a style="margin-left:10px;margin-right:10px" class="js_open_modal" href="' . base_url("get_ajax/modal_form/236?tasks_project_id=" . $tasks[0]['tasks_project_id']) . '"><span class="badge" style="color:#ffffff; background-color:#46b352;"><i class="fas fa-plus"></i></span></a> | <span style="color:green; margin-left:10px">' . $hours_balance . ' ore</span>',
            '', '', '', '', '', '', '', '', '', '', '', ''
        ];
    }


    $colspanned_rows[] = $row + 1;
    $row++;

    //$users = $this->apilib->search('users');
    $users_map = $users_preview = [];
    foreach ($users as $user) {
        $users_map[$user['users_first_name'] . ' ' . $user['users_last_name']] = $user;
    }


    foreach ($tasks as $key => $task) {
        //debug($task, true);
        $users_preview = [];
        if (!empty($task['tasks_members'])) {
            foreach ($task['tasks_members'] as $member) {
                $expl = explode(' ', $member);

                $iniziali = implode(array_map(function ($n) {
                    return substr($n, 0, 1);
                }, $expl));
                if (!empty($users_map[$member])) {
                    $user = $users_map[$member];

                    $link = '<span style="display:none>' . $user['users_first_name'] . ' ' . $user['users_last_name'] . '</span><a target="_blank" href="' . base_url("main/layout/user-detail/" . $user['users_id']) . '"><img class="img-circle pull-left bacheca-avatar" src="' . base_url('thumb/25/25/1/uploads/' . $user['users_avatar']) . '" width="25" height="25" /></a>&nbsp;';
                    $users_preview[] = $link;
                }
            }
        }

        //'<a target="_blank" href="' . base_url("main/layout/user-detail/" . $tasks[0]['tasks_project_id']) . '" style="color:#000000;font-weight: bold;">' . $project_name . '</a>';

        $perc = '';
        if ($task['tasks_items']) {
            $items = json_decode($task['tasks_items'], true);
            $done = 0;
            $total = count($items);
            if ($total) {
                foreach ($items as $item) {
                    if ($item['checked']) {
                        $done++;
                    }
                }
                $perc = ((int)(100 * $done / $total)) . '%';
            }
        }
        $pulsante_play_pausa = '';
        if ($task['tasks_members'] && in_array($this->auth->get('users_id'), array_keys($task['tasks_members']))) {
            if (array_key_exists($task['tasks_id'], $working_tasks) && $working_tasks[$task['tasks_id']] == $this->auth->get('users_id')) {
                //Ci sto lavorando

                $pulsante_play_pausa = '<a href="' . base_url() . 'tasks/main/task_working_on/2/' . $task['tasks_id'] . '" class="btn red js_link_ajax firecrm_pause_button" data-toggle="tooltip" title="" data-original-title="Stop time tracker">
                    <i class="fas fa-pause"></i>
                </a>';
            } else {
                $pulsante_play_pausa = '<a href="' . base_url() . 'tasks/main/task_working_on/1/' . $task['tasks_id'] . '" class="btn green js_link_ajax firecrm_play_button" data-toggle="tooltip" title="" data-original-title="Start time tracker">
                    <i class="fas fa-play"></i>
                </a>';
                //debug($task, true);
            }
        }


        $xls_data[$row] = [
            implode(' ', $users_preview),
            '<a class="js_open_modal" href="' . base_url("get_ajax/modal_form/{$edit_task_form_id}/" . $task['tasks_id']) . '?_size=large">' . $task['tasks_title'] . '</a>' . $pulsante_play_pausa,
            $task['tasks_status'],
            $task['tasks_start_date'],
            $task['tasks_due_date'],
            $task['tasks_delivery_date'],
            $task['tasks_priority'],
            ($task['tasks_estimated_hours'] == 0) ? $task['tasks_estimated_hours'] = '' : $task['tasks_estimated_hours'],
            ($task['tasks_worked_hours'] == 0) ? $task['tasks_worked_hours'] = '' : number_format($task['tasks_worked_hours'], 2, '.', '.'),
            ($task['tasks_bill_amount'] == 0) ? $task['tasks_bill_amount'] = '' : number_format($task['tasks_bill_amount'], 0),
            ($task['tasks_billable_hours'] == 0) ? $task['tasks_billable_hours'] = '' : $task['tasks_billable_hours'],
            '<a href="' . base_url('get_ajax/layout_modal/task-detail/' . $task['tasks_id']) . '?_size=large" class="js_open_modal">' . $perc . '</a>',
            $task['tasks_hidden'],
            ''
        ];
        /*
        {
            A1: {
                myMeta: 'this is just a test',
                otherMetaInformation: 'other test'
            },
            A2: {
                info: 'test'
            }
        } 
*/


        $meta['C' . ($row + 1)] = [
            'field_name' => 'tasks_status',
            'entity_name' => 'tasks',
            'id' => $task['tasks_id']
        ];
        $meta['D' . ($row + 1)] = [
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
        ];

        //Task due date expired and status TODO
        if (strtotime($task['tasks_due_date']) < time() && ($task['tasks_status_status'] == 'To do' || $task['tasks_status_status'] == 'Da fare')) {
            $styles['E' . ($row + 1)] = 'color: #d92307;font-weight: bold';
        }
        //Task delivery date expired
        if (strtotime($task['tasks_delivery_date']) < time()) {
            $styles['F' . ($row + 1)] = 'color: #d92307;font-weight: bold';
        }

        if (array_key_exists($task['tasks_id'], $working_tasks)) {
            $styles['A' . ($row + 1)] = 'background-color: #e6b24c;';
        }
        
        if ($task['tasks_status_status'] == 'To do' || $task['tasks_status_status'] == 'Da fare') {
            $styles['C' . ($row + 1)] = 'color: #ffffff; text-transform: uppercase; background-color: #1ed0eb';
        } elseif ($task['tasks_status_status'] == 'Waiting reply...' || $task['tasks_status_status'] == 'Attesa di risposta') {
            $styles['C' . ($row + 1)] = 'color: #ffffff; text-transform: uppercase; background-color: #df75eb';
        } elseif ($task['tasks_status_status'] == 'To be scheduled' || $task['tasks_status_status'] == 'Da pianificare') {
            $styles['C' . ($row + 1)] = 'color: #ffffff; text-transform: uppercase; background-color: #b3b3b3';
        } elseif ($task['tasks_status_status'] == 'Done' || $task['tasks_status_status'] == 'Chiusa') {
            $styles['C' . ($row + 1)] = 'color: #ffffff; text-transform: uppercase; background-color: #46b352';
        } elseif ($task['tasks_status_status'] == 'Ready' || $task['tasks_status_status'] == 'In consegna') {
            $styles['C' . ($row + 1)] = 'color: #ffffff; text-transform: uppercase; background-color: #db5540';
        } else {
            $styles['C' . ($row + 1)] = 'color: #ffffff; text-transform: uppercase; background-color: #000000';
        }


        //Color priority based on value
        if ($task['tasks_priority_value'] == 'Low' || $task['tasks_priority_value'] == 'Bassa') {
            $styles['G' . ($row + 1)] = 'color:#ffffff;background-color: #46b352; font-weight: bold; text-transform: uppercase;';
        } elseif ($task['tasks_priority_value'] == 'Medium' || $task['tasks_priority_value'] == 'Media') {
            $styles['G' . ($row + 1)] = 'color:#ffffff;background-color: #e6b24c; font-weight: bold; text-transform: uppercase;';
        } elseif ($task['tasks_priority_value'] == 'High' || $task['tasks_priority_value'] == 'Alta') {
            $styles['G' . ($row + 1)] = 'color:#ffffff;background-color: #db5540; font-weight: bold; text-transform: uppercase;';
        } else {
            $styles['G' . ($row + 1)] = 'color:#000000;; font-weight: bold;';
        }

        //Check bill amount
        if (!empty($task['tasks_bill_amount']) && $task['tasks_bill_amount'] > 0) {
            $styles['J' . ($row + 1)] = 'color:#46b352; font-weight: bold;';
        }

        //Estimates hours < Worked hours --> worked hours in red
        if (!empty($task['tasks_worked_hours']) && $task['tasks_estimated_hours'] < number_format($task['tasks_worked_hours'], 2, '.', '.')) {
            $styles['I' . ($row + 1)] = 'color: #d92307;font-weight: bold;';
        }
        //Bill hours > 0 e bill hours < worked hours --> bill hours in red
        if (!empty($task['tasks_worked_hours']) && $task['tasks_billable_hours'] > 0 && $task['tasks_billable_hours'] < number_format($task['tasks_worked_hours'], 2, '.', '.')) {
            $styles['K' . ($row + 1)] = 'color: #d92307;font-weight: bold';
        } elseif ($task['tasks_billable_hours'] > 0) {
            $styles['K' . ($row + 1)] = 'color: #46b352;font-weight: bold';
        }



        $row++;
    }

    // $colspanned_rows[] = $row;

}

//sommo le ore per ogni utente per capire il carico di lavoro
$ore_previste = $this->db->query("
    SELECT
        SUM(tasks_estimated_hours) as hours,
        users_id
    FROM
        tasks_members
        NATURAL LEFT JOIN users
        NATURAL LEFT JOIN tasks
        LEFT JOIN tasks_status ON (tasks_status = tasks_status_id)
    WHERE
        tasks_status_todo_status = 1
        AND users_show_in_kanban = 1
        AND users_active = 1
        AND tasks_estimated_hours < 500

    GROUP BY users_id
")->result_array();



//debug($users_map);
$ore_previste = array_key_map_data($ore_previste, 'users_id', null);


?>


<div class="row">
    <div class="col-sm-12 col-md-6">
        <div class="box box-primary collapsed-box">
            <div class="box-header with-border js_title_collapse">
                <div class="box-title">
                    <i class="fas fa-edit"></i>
                    <span class="">Filtri</span>
                </div>
                <div class="box-tools">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <div class="box-body form display-hide">

                <?php
                $form_id = $this->datab->get_form_id_by_identifier('dashboard_commesse');
                $form = $this->datab->get_form($form_id, null);
                $this->load->view(
                    "pages/layouts/forms/form_{$form['forms']['forms_layout']}",
                    array(
                        'form' => $form,
                        'ref_id' => $form_id,
                        'value_id' => null,
                    ),
                    false
                );
                ?>



            </div>
        </div>
    </div>

    <div class="col-sm-12 col-md-6">
        <div class="box box-warning collapsed-box">
            <div class="box-header with-border js_title_collapse">
                <div class="box-title">
                    <i class="fas fa-calendar-alt"></i>
                    <span class="">Date disponibili e monte ore</span>
                </div>
                <div class="box-tools">
                    <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fas fa-plus"></i></button>
                </div>
            </div>
            <div class="box-body form display-hide">
                <div class="box-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>PRIMA DATA DISPONIBILE</th>
                                    <th>ORE OCCUPATE</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ore_previste as $user_id => $data) : ?>


                                    <tr>
                                        <th scope="row"><?php echo $users_map_id[$user_id]['users_first_name']; ?> <?php echo $users_map_id[$user_id]['users_last_name']; ?></th>
                                        <td>
                                            <center><?php echo $this->tasks->calcolaPrimaDataUtile($data['hours']); ?></center>
                                        </td>
                                        <td><?php echo (int)$data['hours']; ?></td>
                                    </tr>

                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <?php $this->layout->addModuleJavascript('projects', 'js/jexcel.js'); ?>
    <?php $this->layout->addModuleStylesheet('projects', 'js/jexcel.css'); ?>
    <?php $this->layout->addModuleJavascript('projects', 'js/jsuites.js'); ?>
    <?php $this->layout->addModuleStylesheet('projects', 'js/jsuites.css'); ?>

    <div id="spreadsheet"></div>
    <script>
        var data = <?php echo json_encode($xls_data); ?>;
        var colspanned_rows = <?php echo json_encode($colspanned_rows); ?>;
        var tasks_status = <?php echo json_encode($task_status); ?>;
        var tasks_priority = <?php echo json_encode($task_priority); ?>;
        var styles = <?php echo json_encode($styles); ?>;
        var meta = <?php echo json_encode($meta); ?>;
    </script>
    <?php $this->layout->addModuleJavascript('projects', 'js/projects_dashboard.js'); ?>

    <style>
        /*.jexcel>tbody>tr>td.readonly {
        color: #000000 !important;
    }

    .jexcel tbody tr td:nth-child(12) {
        color: #00ba16 !important;
    }*/
        .jexcel tbody tr:nth-child(even) {
            background-color: rgba(0, 0, 0, .05) !important;
        }
    </style>