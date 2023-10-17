<?php $this->layout->addModuleStylesheet('ticket-planner', 'css/customer_tickets.css'); ?>
<?php $this->layout->addModuleJavascript('ticket-planner', 'js/customer_tickets.js'); ?>

<style>

    .icona_chat {
            position: absolute;
        right: 15px;
        top: 15px;
        font-size: 20px!important;
    }

   @keyframes shake {
  0% { transform: translate(0, 0) rotate(0deg); }
  10% { transform: translate(-10px, 0) rotate(-20deg); }
  20% { transform: translate(10px, 0) rotate(20deg); }
  30% { transform: translate(-10px, 0) rotate(-20deg); }
  40% { transform: translate(10px, 0) rotate(20deg); }
  50% { transform: translate(0, 0) rotate(0); }
  100% { transform: translate(0, 0) rotate(0); }
}

.shake-icon {
  animation: shake 2s;
  animation-iteration-count: infinite;
  animation-delay: 2s;
  display: inline-block;
}


    .bg-aqua {
        background-color: #00c0ef !important;
    }

    .bg-green {
        background-color: #00a65a !important;
    }

    .bg-yellow {
        background-color: #f39c12 !important;
    }

    .bg-red {
        background-color: #dd4b39 !important;
    }

    .bg-red,
    .bg-yellow,
    .bg-aqua,
    .bg-blue,
    .bg-light-blue,
    .bg-green,
    .bg-navy,
    .bg-teal,
    .bg-olive,
    .bg-lime,
    .bg-orange,
    .bg-fuchsia,
    .bg-purple,
    .bg-maroon,
    .bg-black,
    .bg-red-active,
    .bg-yellow-active,
    .bg-aqua-active,
    .bg-blue-active,
    .bg-light-blue-active,
    .bg-green-active,
    .bg-navy-active,
    .bg-teal-active,
    .bg-olive-active,
    .bg-lime-active,
    .bg-orange-active,
    .bg-fuchsia-active,
    .bg-purple-active,
    .bg-maroon-active,
    .bg-black-active {
        color: #fff !important;
    }
   
    .info-box {
        display: block;
        min-height: 90px;
        background: #fff;
        width: 100%;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
        border-radius: 2px;
        margin-bottom: 15px;
    }

    .info-box-icon {
        border-top-left-radius: 2px;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
        border-bottom-left-radius: 2px;
        display: block;
        float: left;
        height: 90px;
        width: 90px;
        text-align: center;
        /* font-size: 45px !important; */
        line-height: 90px;
        background: rgba(0, 0, 0, 0.2);
    }

    .info-box-icon>i {
        font-size: 40px;
    }

    .info-box-content {
        padding: 5px 10px;
        margin-left: 90px;
    }

    .info-box-text {
        text-transform: uppercase;
    }

    .progress-description,
    .info-box-text {
        display: block;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .info-box-number {
        display: block;
        font-weight: bold;
        font-size: 18px;
    }

    .info-box .progress,
    .info-box .progress .progress-bar {
        border-radius: 0;
    }

    .info-box .progress {
        background: rgba(0, 0, 0, 0.2);
        margin: 5px -10px 5px -10px;
        height: 2px;
    }

    .progress,
    .progress>.progress-bar,
    .progress .progress-bar,
    .progress>.progress-bar .progress-bar {
        border-radius: 1px;
    }

    .progress,
    .progress>.progress-bar {
        -webkit-box-shadow: none;
        box-shadow: none;
    }

    .progress {
        height: 20px;
        margin-bottom: 20px;
        overflow: hidden;
        /* background-color: #f5f5f5; */
        border-radius: 4px;
        -webkit-box-shadow: inset 0 1px 2px rgba(0, 0, 0, .1);
        box-shadow: inset 0 1px 2px rgba(0, 0, 0, .1);
    }

    .progress-description {
        margin: 0;
    }

    .progress-description,
    .info-box-text {
        display: block;
        font-size: 14px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    a:hover,
    /* OPTIONAL*/
    a:visited,
    a:focus {
        text-decoration: none !important;
    }

    .small-box {
        border-radius: 2px;
        position: relative;
        display: block;
        margin-bottom: 20px;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
    }

    .small-box>.inner {
        padding: 10px;
    }

    .small-box h3,
    .small-box p {
        z-index: 5;
    }

    .small-box h3 {
        font-size: 38px;
        font-weight: bold;
        margin: 0 0 10px 0;
        white-space: nowrap;
        padding: 0;
    }

    .small-box h3,
    .small-box p {
        z-index: 5;
        margin: 0 0 10px;
    }

    .small-box p {
        font-size: 15px;
    }

    .small-box .icon {
        -webkit-transition: all .3s linear;
        -o-transition: all .3s linear;
        transition: all .3s linear;
        position: absolute;
        top: -10px;
        right: 10px;
        z-index: 0;
        font-size: 90px;
        color: rgba(0, 0, 0, 0.15);
    }

    .small-box>.small-box-footer {
        position: relative;
        text-align: center;
        padding: 3px 0;
        color: #fff;
        color: rgba(255, 255, 255, 0.8);
        display: block;
        z-index: 10;
        background: rgba(0, 0, 0, 0.1);
        text-decoration: none;
    }

    [class^="fa-"]:not(.fa-stack),
    [class^="glyphicon-"],
    [class^="icon-"],
    [class*=" fa-"]:not(.fa-stack),
    [class*=" glyphicon-"],
    [class*=" icon-"] {
        display: inline-block;
        font-size: inherit;
        margin-right: inherit;
        line-height: 14px;
        -webkit-font-smoothing: antialiased;
    }
</style>

<?php if ($value_id): ?>
    <?php

    $this->load->model('ticket-planner/ticket');

    $response = $this->ticket->apiRequest('custom/h2/ticket_planner', '', [], $value_id, false);

    if ($response['status'] == '0' && !empty($response['message']) && empty($response['data'])) {
        echo '<div class="col-sm-6 col-sm-offset-3"><div class="alert alert-danger">' . $response['message'] . '</div></div>';
        return;
    }


    $project = $response['data']['project'];
    $tickets = $response['data']['tickets'] ?? [];
    $billing_tasks = $response['data']['billable_tasks'] ?? [];
    $billing_hours = $response['data']['billable_hours'] ?? [];

    $closed_tickets = $opened_tickets = 0;

    $total_tickets = count($tickets);

    foreach ($tickets as $ticket) {
        if ($ticket['tickets_status'] == '1') { // ticket open
            $opened_tickets++;
        }

        if ($ticket['tickets_status'] == '5') { // ticket closed
            $closed_tickets++;
        }
    }

    $perc_progress = ($closed_tickets > 0 || $total_tickets > 0) ? (100 * $closed_tickets) / $total_tickets : 0;

    $ore_assistenza = 0;
    if (!empty($billing_hours)) {
        $ore_assistenza = array_reduce($billing_hours, function ($sum, $item) {
            return number_format($sum + $item['billable_hours_hours'], 2, '.', '');
        }, 0);
    }

    ?>

    <div class="col-md-10 col-md-offset-1">
        <header>
            <div class="col-sm-2"
                style="text-align:center; background-color:#096fa3 !important; !important; min-height: 100% !important; padding: 16px 0px;">
                <img src="<?php echo $this->layout->moduleAssets('ticket-planner', '/images/ticket-details/ingegno.png'); ?>"
                    class="img-responsive" alt="logo">
            </div>
            <div class="header_bar ">

                <div class="row">
                    <div class="col-sm-9">
                        <div class="project_details">
                            <div class="row">
                                <div class="col-sm-9">
                                    <h1>
                                        <?php echo $project['customers_company'] . ' - ' . $project['projects_name']; ?>
                                    </h1>
                                </div>

                         <div class="col-sm-3 text-right">
                        <h4>Saldo ore assistenza</h4>
                        <span style="font-size:22px;color:#f7ee67"><strong><?php echo $ore_assistenza;?></strong></span>
                    </div>
                            </div>
                        </div>
                    </div>

           
                </div>

                
            </div>
        </header>

        <div>
            <div class="filter">
                <div class="row">
                    <div class="col-xs-11">
                        <div class="row">
                            <div class="col-lg-3 col-md-4">
                                <h4 style="margin-top: 3px;">
                                    <?php e('Legenda colori'); ?>
                                </h4>
                            </div>

                            <div class="col-lg-9 col-md-8">
                                <ul class="legenda_stati">
                                    <li class="status-blue">
                                        <?php e('Aperto'); ?>
                                    </li>
                                    <li class="status-yellow">
                                        <?php e('Assegnato'); ?>
                                    </li>
                                    <li class="status-purple">
                                        <?php e('In attesa di risposta'); ?>
                                    </li>
                                    <li class="status-green">
                                        <?php e('Chiuso'); ?>
                                    </li>
                                    <li class="status-black">
                                        <?php e('Annullato'); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-xs-1">
                        <button class="btn btn-sm btn-danger pull-right" data-toggle="modal"
                            data-target="#new_ticket_modal"><i class="fas fa-plus"></i>
                            <?php e('Nuovo Ticket'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="main">

            <?php if (!empty($this->session->flashdata('alert'))): ?>
                <div class="row">
                    <div class="col-sm-6 col-sm-offset-3">
                        <div
                            class="alert <?php echo ($this->session->flashdata('alert')['type'] == 'error') ? 'alert-danger' : 'alert-success'; ?>">
                            <?php echo $this->session->flashdata('alert')['message'] ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($ore_assistenza <= -2): ?>
                <div class="row">
                    <div class="col-sm-12">
                        <div class="alert alert-danger blink" style="/*! padding-top: 10px; */">
                            <h3 style="text-shadow: 0 1px black;padding-top: 0px;margin-top: 0px;margin-bottom: 0px;">Le ore
                                assistenza sono esaurite, contattare H2 WEB al numero 0432 1841408 per il rinnovo.</h3>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div>
                <div class="panel-group panel-group-ticket" id="accordion-ticket" role="tablist">
                    <div class="panel panel-default">
                        <div class="panel-heading" role="tab" id="heading-tickets">
                            <div class="row">
                                <div class="col-xs-5">
                                    <h2 class="panel-title">
                                        <?php e('Tickets project'); ?>
                                    </h2>
                                </div>
                                <div class="col-xs-6">
                                    <div class="progress js_progress" style="display:none" >
                                       <div style="width: 100%; background-color: #f3f3f3; border-radius: 5px;">
                                        <div style="height: 20px; width: 0; background-color: #4CAF50; animation: progress 2s linear infinite; border-radius: 5px;"></div>
                                        </div>

                                        <style>
                                        @keyframes progress {
                                        0% { width: 0; }
                                        100% { width: 100%; }
                                        }
                                        </style>

                                    </div>
                                </div>
                                <div class="col-xs-1 text-right">
                                    <!-- <span class="step_percentage ">
                                        <?php //echo number_format($perc_progress, 2, '.', '') ?>%
                                    </span>
                                    <a data-toggle="collapse" data-parent="#accordion" href="#collapse-tickets"
                                        aria-expanded="true" aria-controls="collapse-tickets">
                                        <i class="glyphicon glyphicon-chevron-down"></i>
                                    </a> -->
                                </div>
                            </div>
                        </div>

                        <div id="collapse-tickets" class="panel-collapse collapse in" role="tabpanel"
                            aria-labelledby="heading-tickets">
                            <div class="_panel-body">
                                <div role="tabpanel" class="tabcontainer" style="background: #e0e0e0">
                                    <div class="row">
                                        <div class="col-xs-12 col-sm-6 col-md-4" style="max-height: 560px;overflow-y: auto">

                                            <ul class="nav nav-pills nav-stacked" role="tablist">
                                                <?php foreach ($tickets as $ticket): ?>
                                                    <?php
                                                    switch ($ticket['tickets_status']) {
                                                        case 5:
                                                            $class = 'green';
                                                            break;
                                                        case 1: //Open
                                                            $class = 'blu';
                                                            break;
                                                        case 2: //In progress
                                                            $class = 'yellow';
                                                            break;
                                                        case 3: // Waiting answer
                                                            $class = 'purple';
                                                            break;
                                                        case 4: //In progress
                                                            $class = 'black';
                                                            break;
                                                        default:
                                                            $class = 'black';
                                                            // debug($ticket);
                                                            break;
                                                    }

                                                    ?>
                                                    <li role="presentation" class="<?php echo $class; ?> js_ticket "
                                                        data-ticket_id="<?php echo $ticket['tickets_id']; ?>">
                                                        <a href="#ticket_tab_<?php echo $ticket['tickets_id']; ?>"
                                                            aria-controls="home" role="tab" data-toggle="pill">
                                                            <?php echo $ticket['tickets_subject']; ?>
                                                            <span class="button-holder pull-right">
                                                                <span data-container="body" data-toggle="popover"
                                                                    data-placement="bottom" data-content="" title="">
                                                                    <img ng-src="img/icona-messaggi-off.png" alt=""
                                                                        src="img/icona-messaggi-off.png">
                                                                </span>
                                                            </span>
                                                            <small class="ng-binding">
                                                                <strong style="font-size: 85%" class="ng-binding">#
                                                                    <?php echo $ticket['tickets_id']; ?>
                                                                </strong> -
                                                                <?php echo dateFormat($ticket['tickets_creation_date']); ?>
                                                                &nbsp;
                                                                <small style="color:#189ACA; ">
                                                                    <?php if ($ticket['tickets_attachments']): ?>
                                                                        <!-- <i class="glyphicon glyphicon-paperclip" style="margin-right:8px"></i> -->
                                                                    <?php endif; ?>
                                                                    <i class="glyphicon " style="margin-right:18px"></i>
                                                                    <?php echo $ticket['tickets_category_value']; ?>
                                                                </small>

                                                            </small>
                                                        </a>
                                                            <?php if ($ticket['tickets_status'] == 3):?>
                                                                <i class="shake-icon icona_chat fas fa-envelope"></i>
                                                                <?php endif;?>
                                                    </li>
                                                <?php endforeach; ?>

                                            </ul>
                                        </div>

                                        <div class="col-xs-12 col-sm-6 col-md-8">
                                            <!-- Tab panes -->
                                            <div class="tab-content_ticket" style="background: none;">
                                                <div class="js-ticket_details">
                                                    <?php e('Mostra il ticket scegliendo dalla lista a sinistra'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-sm-9">
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#billing_tickets_closed" data-toggle="tab">Ticket chiusi</a></li>
                        <li><a href="#billing_tickets" data-toggle="tab">Ticket a preventivo</a></li>
                        <li ><a href="#billing_hours" data-toggle="tab">Carico/Scarico ore</a></li>
                    </ul>
                    <div class="tab-content">

                        <div class="tab-pane active" id="billing_tickets_closed">
                            <table class="table table-sm table-striped js_datatable billing_tickets_closed">
                                <thead>
                                    <tr>
                                        <th>Aperto il</th>
                                        <th>Chiuso il</th>
                                        <th>Titolo</th>
                                        <th>Ore stimate</th>
                                        <th>Ore effettive</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    if (!empty($tickets)):
                                        foreach ($tickets as $ticket):
                                            if ($ticket['tickets_status'] != 5) continue;
                                            ?>
                                            <tr>
                                                <td>
                                                    <?php echo dateFormat($ticket['tickets_creation_date']); ?>
                                                </td>
                                                <td>
                                                    <?php echo dateFormat($ticket['tickets_close_date']); ?>
                                                </td>
                                                <td>
                                                    <a href="#" class="js_ticket_link" data-ticket_id="<?php echo $ticket['tickets_id']; ?>">
                                                        <?php echo "#" . $ticket['tickets_id'] . " " . $ticket['tickets_subject']; ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <?php echo ($ticket['tickets_estimated_billable'] > 0) ? $ticket['tickets_estimated_billable'] : ''; ?>
                                                </td>
                                                <td>
                                                    <?php echo (!empty($ticket['tasks'][0]['tasks_billable_hours']) && $ticket['tasks'][0]['tasks_billable_hours'] > 0) ? "-" . $ticket['tasks'][0]['tasks_billable_hours'] : ''; ?>
                                                </td>
                                                
                                            </tr>
                                        <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>

                

                        <div class="tab-pane " id="billing_hours">
                            <table class="table table-sm table-striped js_datatable billing_hours">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Titolo</th>
                                        <th>Note</th>
                                        <th>Tipo</th>
                                        <th>Ore</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php $saldo = 0;
                                    if (!empty($billing_hours)):
                                        foreach ($billing_hours as $billing_hour):
                                            $saldo += $billing_hour['billable_hours_hours']; ?>
                                            <tr>
                                                <td>
                                                    <?php echo (!empty($billing_hour['tasks_creation_date'])) ? dateFormat($billing_hour['tasks_creation_date']) : ''; ?>
                                                </td>
                                                <td>
                                                    <?php echo $billing_hour['tasks_title']; ?>
                                                    <?php if ($billing_hour['tasks_ticket_id']): ?>
                                                        (ticket #
                                                        <?php echo $billing_hour['tasks_ticket_id']; ?>)
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $billing_hour['billable_hours_note'] ?>
                                                </td>
                                                <th>
                                                    <?php echo $billing_hour['billable_hours_type_value'] ?>
                                                </th>
                                                <td class="js_billing_hours_amount"
                                                    data-raw-value="<?php echo $billing_hour['billable_hours_hours']; ?>"><?php echo $billing_hour['billable_hours_hours']; ?></td>
                                            </tr>
                                        <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="tab-pane" id="billing_tickets">
                            <table class="table table-sm table-striped js_datatable billing_tickets">
                                <thead>
                                    <tr>
                                        <th>Scadenza</th>
                                        <th>Titolo</th>
                                        <th>Ammontare</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php
                                    if (!empty($billing_tasks)):
                                        foreach ($billing_tasks as $billing_task):
                                            if ($billing_task['tasks_bill_amount'] <= 0) {
                                                continue;
                                            } ?>
                                            <tr>
                                                <td>
                                                    <?php echo (!empty($billing_task['tasks_due_date'])) ? dateFormat($billing_task['tasks_due_date']) : ''; ?>
                                                </td>
                                                <td>
                                                    <?php echo $billing_task['tasks_title']; ?>
                                                    <?php if ($billing_task['tasks_ticket_id']): ?>
                                                        (ticket #
                                                        <?php echo $billing_task['tasks_ticket_id']; ?>)
                                                    <?php endif; ?>
                                                </td>
                                                <td class="js_billing_task_amount"
                                                    data-raw-value="<?php echo $billing_task['tasks_bill_amount']; ?>">€ <?php echo $billing_task['tasks_bill_amount']; ?></td>
                                            </tr>
                                        <?php endforeach;
                                    endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-sm-3">
                <div class="small-box bg-aqua">
                    <div class="inner">
                        <h3>
                            <?php echo number_format($saldo, 2, '.', '') ?>
                        </h3>

                        <p>Saldo Ore</p>
                    </div>
                    <div class="icon">
                        <i class="fas fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>

        <?php echo $this->load->module_view('ticket-planner', 'views/new_ticket_modal', ['project' => $project, 'saldo' => $saldo], true); ?>
    </div>

            <script>
            $(document).ready(function() {

            initTables();
            });
            </script>
<?php endif; ?>