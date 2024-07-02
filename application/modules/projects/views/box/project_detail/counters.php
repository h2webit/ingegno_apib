<?php

// DEPRECATED ***********************************************

$project = $this->apilib->view('projects', $value_id);
$default_currency = $this->db->where('currencies_default', DB_BOOL_TRUE)->get('currencies')->row()->currencies_symbol;

$this->load->model('projects/projects');

$expenses = $this->db->query("SELECT SUM(CASE  WHEN expenses_type = 1 THEN expenses_amount ELSE expenses_amount*(-1) END) as s FROM expenses WHERE expenses_project_id = '{$value_id}'")->row()->s;

$worked = $this->projects->get_project_worked_hours($value_id);
$worked_hours = $worked['worked_hours'];
$hours_last_30_days = $worked['hours_last_30_days'];
$worked_hours_cost = $worked['worked_hours_cost'];


//aggiungo evenutali ore di tickets-report
if ($this->datab->module_installed('tickets-report')) {
    $worked_hours_tickets_reports = $this->db->query("SELECT SUM(tickets_reports_billable_hours) as s FROM tickets_reports WHERE (tickets_reports_billable_hours IS NOT NULL OR tickets_reports_billable_hours != 0) AND tickets_reports_project_id = '{$value_id}'")->row()->s;
} else {
    $worked_hours_tickets_reports = 0;
}
$hours_perc_30_days = ($worked_hours > 0) ? ($hours_last_30_days / $worked_hours) * 100 : 0;

$total_balance = $project['projects_sold_price'] - $expenses - $worked_hours_cost;
$margin_balance = ($project['projects_sold_price'] > 0) ? (100 * $total_balance) / $project['projects_sold_price'] : 0;

if ($this->datab->module_installed('billable-hours') || $this->datab->module_installed('firecrm')) {
    $hours_balance = $this->db->query("SELECT SUM(billable_hours_hours) as s FROM billable_hours WHERE billable_hours_project_id = '{$value_id}'")->row()->s;
}
$hours_balance = (empty($hours_balance)) ? 0.00 : $hours_balance;
$tickets_last_30_days = 0;
if ($this->datab->module_installed('tickets') || $this->datab->module_installed('firecrm')) {
    $tickets = $this->db->query("SELECT COUNT(*) as s FROM tickets WHERE tickets_project_id = '{$value_id}'")->row()->s;
    $tickets_last_30_days = $this->db->query("SELECT COUNT(*) as s FROM tickets WHERE tickets_project_id = '{$value_id}' AND tickets_creation_date > (NOW() - INTERVAL 30 day)")->row()->s;
} else {
    $tickets = 0;
}
$tickets_perc_30_days = ($worked_hours > 0) ? ($tickets_last_30_days / $worked_hours) * 100 : 0;

// Billable hours
if ($this->datab->module_installed('billable-hours') || $this->datab->module_installed('firecrm')) {
    $debited_billable_hours = $this->db->query("SELECT SUM(billable_hours_hours) as total FROM billable_hours WHERE billable_hours_type = 2 AND billable_hours_project_id = '{$value_id}'")->row()->total;
    $paid_billable_hours = $this->db->query("SELECT SUM(billable_hours_hours) as total FROM billable_hours WHERE billable_hours_type = 1 AND billable_hours_project_id = '{$value_id}'")->row()->total;
    $free_billable_hours = $this->db->query("SELECT SUM(billable_hours_hours) as total FROM billable_hours WHERE billable_hours_type = 4 AND billable_hours_project_id = '{$value_id}'")->row()->total;
} else {
    $debited_billable_hours = 0;
    $paid_billable_hours = 0;
    $free_billable_hours = 0;
}
// Force positive
$debited_billable_hours = -$debited_billable_hours;

$total_billable_hours = $paid_billable_hours - $debited_billable_hours;

$paid_billable_hours_cost = $paid_billable_hours * $project['projects_hourly_rate'];
$free_billable_hours_cost = $free_billable_hours * $project['projects_hourly_rate'];

$unbilled_billable_hours = abs(($paid_billable_hours - $debited_billable_hours) + $free_billable_hours);
$unbilled_billable_hours_cost = abs($unbilled_billable_hours * $project['projects_hourly_rate']);
if (($this->datab->module_installed('timesheet') and $this->datab->module_installed('billable-hours')) || $this->datab->module_installed('firecrm')) {
    $worked_billable_hours = $this->db->query("SELECT SUM(timesheet_total_hours) as total, SUM(timesheet_total_cost) as cost FROM timesheet WHERE timesheet_task IN (SELECT billable_hours_task_id FROM billable_hours) AND timesheet_project = '{$value_id}'")->row_array();
} else {
    $worked_billable_hours['cost'] = 0;
    $worked_billable_hours['total'] = 0;
}
$debited_billable_hours_cost = $debited_billable_hours * $project['projects_hourly_rate'];

$total_worked_billable_hours = $debited_billable_hours_cost - $worked_billable_hours['cost'] - $free_billable_hours_cost;

$margin_bill_hours = ($debited_billable_hours > 0) ? 100 - ((100 * $worked_billable_hours['total']) / $debited_billable_hours) : 0;

$margin_bill_hours_cost = ($debited_billable_hours_cost > 0) ? 100 - ((100 * $worked_billable_hours['cost']) / $debited_billable_hours_cost) : 0;

/* Worked / Estimated Hours */
$worked_margin = ($project['projects_estimated_hours'] > 0) ? (100 * $worked_hours) / $project['projects_estimated_hours'] : 0;

/* Progress Project*/
if ($this->datab->module_installed('tasks') || $this->datab->module_installed('firecrm')) {
    $total_tasks_hours = $this->db->query("SELECT SUM(CASE WHEN (tasks_estimated_hours>0) THEN tasks_estimated_hours ELSE 1 END) as s FROM tasks WHERE tasks_project_id = '{$value_id}' AND tasks_status IN (SELECT tasks_status_id FROM tasks_status WHERE tasks_status_done_status = '" . DB_BOOL_TRUE . "' OR tasks_status_todo_status = '" . DB_BOOL_TRUE . "')")->row()->s;
    $closed_tasks_hours = $this->db->query("SELECT SUM(CASE WHEN (tasks_estimated_hours>0) THEN tasks_estimated_hours ELSE 1 END) as s FROM tasks WHERE tasks_project_id = '{$value_id}' AND tasks_status IN (SELECT tasks_status_id FROM tasks_status WHERE tasks_status_done_status = '" . DB_BOOL_TRUE . "')")->row()->s;
} else {
    $total_tasks_hours = 0;
    $closed_tasks_hours = 0;
}
if ($total_tasks_hours == 0) {
    $total_tasks_hours = 1;
}
$project_progress = (100 * $closed_tasks_hours) / $total_tasks_hours;

?>
<?php $this->layout->addModuleStylesheet('projects', 'css/counters.css'); ?>
<div class="row">
    <div class="nav-tabs-custom">
        <ul class="nav nav-tabs">
            <li class="active"><a href="#tab_1" data-toggle="tab">
                    <?php e('Counters'); ?>
                </a></li>
            <li><a href="#tab_2" data-toggle="tab">
                    <?php echo "Consuntivo"; ?>
                </a></li>
            <!-- <li><a href="#tab_3" data-toggle="tab"><?php e('Billable Hours'); ?></a></li>-->
            <li class="pull-right"><a href="#" class="text-muted"><i class="fa fa-gear"></i></a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane active" id="tab_1">
                <div class="row">
                    <div class="col-md-6 col-sm-6 col-xs-12">
                        <div
                            class="info-box <?php echo ($hours_balance < 0) ? 'bg-red' : 'bg-green'; ?> box_billable_hours">
                            <span class="info-box-icon"><i class="fas fa-hourglass"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text">
                                    <?php e('Billable Hours Balance'); ?>
                                </span>
                                <span class="info-box-number <?php echo ($hours_balance < 0) ? 'blink_me' : ''; ?>">
                                    <?php echo number_format($hours_balance, 2, '.', '.'); ?>
                                </span>

                                <div class="progress">
                                    <div class="progress-bar w100"></div>
                                </div>
                                <span class="progress-description">
                                </span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                        <!-- /.info-box -->
                    </div>
                    <!-- /.col -->
                    <div class="col-md-6 col-sm-6 col-xs-12">
                        <div class="info-box bg-aqua box_tickets">
                            <span class="info-box-icon"><i class="fas fa-ticket-alt"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text">
                                    <?php e('Tickets'); ?>
                                </span>
                                <span class="info-box-number">
                                    <?php echo $tickets; ?>
                                </span>

                                <div class="progress">
                                    <div class="progress-bar"
                                        style="width: <?php echo number_format($tickets_perc_30_days, 2, '.', '.'); ?>%">
                                    </div>
                                </div>
                                <span class="progress-description">
                                    <?php echo number_format($tickets_perc_30_days, 2, '.', '.'); ?>%
                                    <?php e('Increase in 30 Days'); ?>
                                </span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                        <!-- /.info-box -->
                    </div>
                    <!-- /.col -->
                    <div class="col-md-6 col-sm-6 col-xs-12">
                        <div class="info-box bg-yellow box_timesheet">
                            <span class="info-box-icon"><i class="fas fa-stopwatch"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text small-info-box-text">
                                    <?php e('Timesheet hours'); ?>
                                </span>
                                <span class="info-box-number small-info-box-number">
                                    <?php echo number_format($worked_hours, 2, '.', '.'); ?>
                                </span>
                                <span class="info-box-text small-info-box-text">
                                    <?php e('Ticket reports hours'); ?>
                                </span>
                                <span class="info-box-number small-info-box-number">
                                    <?php echo number_format($worked_hours_tickets_reports, 2, '.', '.'); ?>
                                </span>
                            </div>
                            <!-- /.info-box-content -->
                        </div>
                        <!-- /.info-box -->
                    </div>
                    <!-- /.col -->
                    <div class="col-md-6 col-sm-6 col-xs-12">
                        <div class="info-box bg-red box_expenses">
                            <span class="info-box-icon"><i class="fas fa-search-dollar"></i></span>

                            <div class="info-box-content">
                                <span class="info-box-text">
                                    <?php e('Expenses'); ?>
                                </span>
                                <span class="info-box-number">
                                    <?php echo $default_currency . " " . number_format($expenses, 2, '.', ','); ?>
                                </span>


                            </div>
                            <!-- /.info-box-content -->
                        </div>
                        <!-- /.info-box -->
                    </div>
                    <!-- /.col -->

                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="flex-wrapper">
                            <div class="single-chart">
                                <h4 class="text-center">
                                    <?php echo e('Worked on Est. Hours'); ?>
                                </h4>
                                <svg viewbox="0 0 36 36" class="circular-chart orange">
                                    <path class="circle-bg" d="M18 2.0845
        a 15.9155 15.9155 0 0 1 0 31.831
        a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <path class="circle"
                                        stroke-dasharray="<?php echo number_format($worked_margin, 0); ?>, 100" d="M18 2.0845
        a 15.9155 15.9155 0 0 1 0 31.831
        a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <text x="18" y="20.35" class="percentage">
                                        <?php echo number_format($worked_margin, 0); ?>%
                                    </text>
                                </svg>
                            </div>

                            <div class="single-chart">
                                <h4 class="text-center">
                                    <?php echo e('Project progress'); ?>
                                </h4>
                                <svg viewbox="0 0 36 36" class="circular-chart green">
                                    <path class="circle-bg" d="M18 2.0845
        a 15.9155 15.9155 0 0 1 0 31.831
        a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <path class="circle"
                                        stroke-dasharray="<?php echo number_format($project_progress, 0); ?>, 100" d="M18 2.0845
        a 15.9155 15.9155 0 0 1 0 31.831
        a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <text x="18" y="20.35" class="percentage">
                                        <?php echo number_format($project_progress, 0); ?>%
                                    </text>
                                </svg>
                            </div>

                            <div class="single-chart">
                                <h4 class="text-center">
                                    <?php echo e('Your margin'); ?>
                                </h4>
                                <svg viewbox="0 0 36 36" class="circular-chart blue">
                                    <path class="circle-bg" d="M18 2.0845
        a 15.9155 15.9155 0 0 1 0 31.831
        a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <path class="circle"
                                        stroke-dasharray="<?php echo number_format($margin_balance, 0); ?>, 100" d="M18 2.0845
        a 15.9155 15.9155 0 0 1 0 31.831
        a 15.9155 15.9155 0 0 1 0 -31.831" />
                                    <text x="18" y="20.35" class="percentage">
                                        <?php echo number_format($margin_balance, 0); ?>%
                                    </text>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            <!-- Billable hours -->
            <div class="tab-pane" id="tab_2">
                <div class="btn-group sortableMenu">
                    <a href="<?php echo base_url("main/layout/consuntivo-progetto-detail/" . $value_id); ?>"
                        target="_blank" class="btn btn-primary">
                        <i class="fas fa-print"></i> Stampa consuntivo completo</a>
                </div>
                <br><br>
                <table class="table">

                    <tr>
                        <td>
                            <?php e('Sold price'); ?>
                        </td>

                        <td class="text-green">
                            <?php echo $default_currency . " " . $project['projects_sold_price']; ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php e('Expenses'); ?>
                        </td>
                        <td class="text-red">
                            <?php echo $default_currency . " " . number_format($expenses, 2, '.', ','); ?>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <?php e('Worked hours'); ?> (
                            <?php echo number_format($worked_hours, 2, '.', '.'); ?>
                            <?php e('hours'); ?>)
                        </td>

                        <td class="text-red">
                            <?php echo $default_currency . " " . number_format($worked_hours_cost, 2, '.', '.'); ?>
                        </td>
                    </tr>

                    <tr>
                        <td><strong>
                                <?php e('Total'); ?>
                            </strong></td>

                        <td class="<?php echo ($total_balance > 0) ? 'text-green' : 'text-red' ?>">
                            <?php echo $default_currency . " " . number_format($total_balance, 2, '.', '.'); ?>
                        </td>
                    </tr>

                    <tr>
                        <td><strong>
                                <?php e('Your margin'); ?>
                            </strong></td>

                        <td class="<?php echo ($margin_balance > 0) ? 'text-green' : 'text-red' ?>">
                            <?php echo number_format($margin_balance, 2, '.', '.'); ?>%
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Billable hours -->
            <div class="tab-pane" id="tab_3">
                <table class="table">
                    <tr>
                        <th></th>
                        <th>
                            <?php e('Hours'); ?>
                        </th>
                        <th>
                            <?php e('Cost'); ?>
                        </th>
                    </tr>
                    <tr>
                        <td>
                            <?php e('Hourly Rate'); ?>
                        </td>
                        <td></td>
                        <td class="text-info">
                            <?php echo $default_currency . " " . number_format($project['projects_hourly_rate'], 2, '.', ','); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <?php e('Debited hours'); ?>
                        </td>
                        <td class="text-green">
                            <?php echo number_format($debited_billable_hours, 2, '.', ','); ?>
                        </td>
                        <td class="text-green">
                            <?php echo $default_currency . " " . number_format($debited_billable_hours_cost, 2, '.', ','); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <?php e('Free hours'); ?>
                        </td>
                        <td class="<?php echo ($free_billable_hours > 0) ? 'text-red' : 'text-green' ?>">
                            <?php echo number_format($free_billable_hours, 2, '.', ','); ?>
                        </td>
                        <td class="<?php echo ($unbilled_billable_hours_cost > 0) ? 'text-red' : 'text-green' ?>">
                            <?php echo $default_currency . " -" . number_format($free_billable_hours_cost, 2, '.', ','); ?>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <?php e('Real worked hours'); ?>
                        </td>
                        <td>
                            <?php echo number_format($worked_billable_hours['total'], 2, '.', ','); ?>
                        </td>
                        <td class="text-red">
                            <?php echo $default_currency . " -" . number_format($worked_billable_hours['cost'], 2, '.', ','); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>
                                <?php e('Estimated Total'); ?>
                            </strong></td>
                        <td></td>
                        <td class="<?php echo ($total_worked_billable_hours > 0) ? 'text-green' : 'text-red' ?>">
                            <?php echo $default_currency . " " . number_format($total_worked_billable_hours, 2, '.', '.'); ?>
                        </td>
                    </tr>


                    <?php if ($unbilled_billable_hours > 0): ?>

                        <tr>
                            <td>
                                <?php e('Unbilled hours'); ?>
                            </td>
                            <td class="<?php echo ($unbilled_billable_hours > 0) ? 'text-red' : 'text-green' ?>">
                                <?php echo number_format($unbilled_billable_hours, 2, '.', ','); ?>
                            </td>
                            <td class="<?php echo ($unbilled_billable_hours_cost > 0) ? 'text-red' : 'text-green' ?>">
                                <?php echo $default_currency . " " . number_format($unbilled_billable_hours_cost, 2, '.', ','); ?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>
                                    <?php e('Total'); ?>
                                </strong></td>
                            <td></td>
                            <?php $real_total = $total_worked_billable_hours - $unbilled_billable_hours_cost; ?>
                            <td class="<?php echo ($real_total > 0) ? 'text-green' : 'text-red' ?>">
                                <?php echo $default_currency . " " . number_format($real_total, 2, '.', '.'); ?>
                            </td>
                        </tr>

                    <?php endif; ?>

                    <tr>
                        <td><strong>
                                <?php e('Your margin'); ?>
                            </strong></td>

                        <td class="<?php echo ($margin_bill_hours > 0) ? 'text-green' : 'text-red' ?>">
                            <?php echo number_format($margin_bill_hours, 2, '.', '.'); ?>%
                        </td>
                        <td class="<?php echo ($margin_bill_hours_cost > 0) ? 'text-green' : 'text-red' ?>">
                            <?php echo number_format($margin_bill_hours_cost, 2, '.', '.'); ?>%
                        </td>
                    </tr>




                </table>
            </div>

        </div>

    </div>

</div>