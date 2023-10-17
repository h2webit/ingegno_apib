<?php

$this->load->model('ticket-planner/ticket');

$this->ticket->ticket_planner_settings = $this->apilib->searchFirst('ticket_planner_settings');

if (
    empty($this->ticket->ticket_planner_settings)
    || empty($this->ticket->ticket_planner_settings['ticket_planner_settings_auth_bearer'])
    || empty($this->ticket->ticket_planner_settings['ticket_planner_settings_customer_id'])
    || empty($this->ticket->base_endpoint)
) {
    echo '<div class="col-sm-6 col-sm-offset-3"><div class="alert alert-danger">Ticket Planner non configurato.</div></div>';
    return;
} else {
    $projects_search_where = ['where[projects_customer_id]' => $this->ticket->ticket_planner_settings['ticket_planner_settings_customer_id'], 'where[projects_status]' => '2'];

    if (!empty($this->ticket->ticket_planner_settings['ticket_planner_settings_project_id'])) {
        $projects_search_where['where[]'] = "projects_id IN ({$this->ticket->ticket_planner_settings['ticket_planner_settings_project_id']})";
    }

    $response = $this->ticket->apiRequest('projects', 'search', $projects_search_where);

    if ($response['status'] == '1' && !empty($response['message']) && empty($response['data'])) {
        echo '<div class="col-sm-6 col-sm-offset-3"><div class="alert alert-danger">'.$response['message'].'</div></div>';
        return;
    }
    
    $projects = $response['data'];
    
    $colors = ['bg-aqua', 'bg-yellow', 'bg-green', 'bg-red']; ?>

<style>
.info-box .progress .progress-bar {
    background: #fff;
}

.info-box .progress,
.info-box .progress .progress-bar {
    border-radius: 0;
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

.progress-bar {
    float: left;
    width: 0%;
    height: 100%;
    font-size: 12px;
    line-height: 20px;
    color: #fff;
    text-align: center;
    background-color: #337ab7;
    -webkit-box-shadow: inset 0 -1px 0 rgba(0, 0, 0, .15);
    box-shadow: inset 0 -1px 0 rgba(0, 0, 0, .15);
    -webkit-transition: width .6s ease;
    -o-transition: width .6s ease;
    transition: width .6s ease;
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
    font-size: 45px !important;
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

<div class="row">
    <?php if (!empty($projects)) : ?>
        <div class="col-sm-12">
            <h2 class="page-header"><i class="fas fa-info text-info"></i> <?php e('Clicca sul box del progetto di cui vui vedere i relativi ticket'); ?></h2>
        </div>
        <?php foreach ($projects as $key => $project) : ?>
            <?php if (count($projects) > 0 && count($projects) <= 1): ?>
                <script>
                location.href = '<?php echo base_url("main/layout/ticket-planner-elenco/{$project['projects_id']}"); ?>';
                </script>
            <?php else: ?>
                <?php
                    $response = $this->ticket->apiRequest('tickets', 'search', ['where[tickets_project_id]' => $project['projects_id']]);

                    $tickets = $response['data'] ?? [];

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
                ?>
        
                <div class="col-sm-4">
                    <a href="<?php echo base_url("main/layout/ticket-planner-elenco/{$project['projects_id']}"); ?>">
                        <div class="info-box <?php echo $colors[$key % 4]; ?>">
                            <span class="info-box-icon">
                                <i class="far fa-bookmark"></i>
                            </span>
            
                            <div class="info-box-content">
                                <span class="info-box-text"><?php echo $project['projects_name']; ?></span>
                                <span class="info-box-number">
                                    <span><?php e('Ticket Aperti'); ?>:</span>
                                    <?php echo $opened_tickets; ?>
                                </span>
            
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?php echo $perc_progress; ?>%"></div>
                                </div>
                                <span class="progress-description">
                                    <?php e('Ticket Totali'); ?>:
                                    <span><strong><?php echo $total_tickets; ?></strong></span>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else : ?>
        <div class="col-sm-6 col-sm-offset-3">
            <div class="alert alert-info">
                <h2>Nessun progetto trovato.</h2>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php
} ?>
