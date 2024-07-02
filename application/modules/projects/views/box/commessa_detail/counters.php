<?php

// DEPRECATED ***********************************************



//conto i timesheets 
if ($this->datab->module_installed('timesheet')) {
    $worked_hours = $this->db->query("SELECT SUM(timesheet_total_hours) as s FROM timesheet WHERE timesheet_project = '{$value_id}'")->row()->s;
}

//conto le spese 
$expenses = $this->db->query("SELECT SUM(CASE  WHEN expenses_type = 1 THEN expenses_amount ELSE expenses_amount*(-1) END) as s FROM expenses WHERE expenses_project_id = '{$value_id}'")->row()->s;

//calcolo i tickets
if ($this->datab->module_installed('tickets')) {
    $tickets = $this->db->query("SELECT COUNT(*) as s FROM tickets WHERE tickets_project_id = '{$value_id}'")->row()->s;
    $tickets_last_30_days = $this->db->query("SELECT COUNT(*) as s FROM tickets WHERE tickets_project_id = '{$value_id}' AND tickets_creation_date > (NOW() - INTERVAL 30 day)")->row()->s;
    $tickets_perc_30_days = ($worked_hours > 0) ? ($tickets_last_30_days / $worked_hours) * 100 : 0;
}

//calcolo le billable hours per il saldo ore
$billable = 0;
if ($this->datab->module_installed('billable-hours')) {
    $billable = $this->db->query("SELECT SUM(billable_hours_hours) as s FROM billable_hours WHERE billable_hours_project_id = '{$value_id}'")->row()->s;
}

//calcolo i tickets report
if ($this->datab->module_installed('tickets-report')) {
    //calcolo ore lavorate
    $hours_lavorate = 0;
    $hours_fatturato = 0;
    $tickets_report_detail = $this->db->query("SELECT * FROM tickets_reports WHERE tickets_reports_project_id = '{$value_id}'")->result_array();

    foreach ($tickets_report_detail as $ticket_report) {
        //$tecnici = $this->db->query("SELECT * FROM tickets_reports_tecnici WHERE tickets_reports_id = '{$ticket_report['tickets_reports_id']}'")->result_array();
        $tecnici = $this->db->query("SELECT COUNT(*) AS num_tecnici FROM tickets_reports_tecnici WHERE tickets_reports_id = '{$ticket_report['tickets_reports_id']}'")->row_array()['num_tecnici'];

        $query = "SELECT COUNT(*) as total_rows FROM tickets_reports_tecnici WHERE tickets_reports_id = '{$ticket_report['tickets_reports_id']}'";
        /*$count_tecnici_result = $this->db->query($query)->row_array();

        $count_tecnici = $count_tecnici_result['total_rows'];
        if ($count_tecnici === 0) {
            $count_tecnici = 1;
        }*/

        $start_time = new DateTime($ticket_report['tickets_reports_start_time']);
        $end_time = new DateTime($ticket_report['tickets_reports_end_time']);
        $diff = $end_time->diff($start_time);
        $ore_lavorate = round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
        $hours_lavorate += $ore_lavorate * $tecnici;        //vedo le ore da fatturare
        //$hours_fatturato += $ticket_report['tickets_reports_billable_hours'] * $count_tecnici; 
        $hours_fatturato += $ticket_report['tickets_reports_billable_hours']; //rimuovo i tecnici, nelle billable_hours si tiene conto già dei tecnici.
    }

    //$hours_fatturato = $this->db->query("SELECT SUM(tickets_reports_billable_hours) as s FROM tickets_reports WHERE tickets_reports_project_id = '{$value_id}'")->row()->s;     
    //qua deve prendere dalle ore degli interventi             
}

//calcolo ore rapportini
if ($this->datab->module_installed('rapportini')) {
    //calcolo ore lavorate
    $ore_rapportini = 0;
    $rapportini_commessa = $this->db->query("SELECT * FROM rapportini WHERE rapportini_commessa = '{$value_id}'")->result_array();

    foreach ($rapportini_commessa as $rapportino) {
        $start_time = new DateTime($rapportino['rapportini_ora_inizio']);
        $end_time = new DateTime($rapportino['rapportini_ora_fine']);
        $diff = $end_time->diff($start_time);
        $ore_rapportini += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
    }
}

$default_currency = $this->db->where('currencies_default', DB_BOOL_TRUE)->get('currencies')->row()->currencies_symbol;
?>

<?php if ($this->datab->module_installed('timesheet')): ?>
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="info-box bg-yellow">
                <span class="info-box-icon"><i class="fas fa-stopwatch"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text"><?php e('Worked hours'); ?> da Timesheets</span>
                    <span class="info-box-number"><?php echo number_format($worked_hours, 2, '.', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
<!-- Rapportini -->
<?php if ($this->datab->module_installed('rapportini')): ?>
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="info-box bg-yellow">
                <span class="info-box-icon"><i class="fas fa-business-time"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text"><?php e('Worked hours'); ?></span>
                    <span class="info-box-number"><?php echo number_format($ore_rapportini, 2, '.', '.'); ?></span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Tickets reports -->
<?php if ($this->datab->module_installed('tickets-report')): ?>
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="fas fa-business-time"></i></span>

                <div class="info-box-content">
                    <?php if ($this->datab->module_installed('billable-hours')): ?>
                        <span class="progress-description">
                            Saldo ore: <?php echo number_format($billable, 2, '.', '.'); ?>
                        </span>
                    <?php endif; ?>
                    <span class="progress-description">
                        Ore interventi fatturate: <?php echo number_format($hours_fatturato, 2, '.', '.'); ?>
                    </span>
                    <span class="progress-description">
                        Ore interventi lavorate: <?php echo number_format($hours_lavorate, 2, '.', '.'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Spese -->
<div class="row">
    <!-- /.col -->
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="info-box bg-red">
            <span class="info-box-icon"><i class="fas fa-search-dollar"></i></span>

            <div class="info-box-content">
                <span class="info-box-text"><?php e('Expenses'); ?></span>
                <span
                    class="info-box-number"><?php echo $default_currency . " " . number_format($expenses, 2, '.', ','); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Tickets -->
<?php if ($this->datab->module_installed('tickets')): ?>
    <div class="row">
        <div class="col-md-12 col-sm-12 col-xs-12">
            <div class="info-box bg-aqua">
                <span class="info-box-icon"><i class="fas fa-ticket-alt"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text"><?php e('Tickets'); ?></span>
                    <span class="info-box-number"><?php echo $tickets; ?></span>

                    <div class="progress">
                        <div class="progress-bar"
                            style="width: <?php echo number_format($tickets_perc_30_days, 2, '.', '.'); ?>%"></div>
                    </div>
                    <span class="progress-description">
                        <?php echo number_format($tickets_perc_30_days, 2, '.', '.'); ?>% <?php e('Increase in 30 Days'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>