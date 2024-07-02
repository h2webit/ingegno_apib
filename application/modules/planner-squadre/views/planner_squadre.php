<script src="<?php echo base_url(); ?>script/global/plugins/sortable/Sortable.min.js?v=2.2.2"></script>

<?php

$numOfDays = 5;

/*
 * Input
 * ---------------------
 *  - week: numero settimana
 *  - year: anno
 */
$year = (int) $this->input->get('year') ?: date('Y');
$week = (int) $this->input->get('week') ?: date('W');
$weekMap = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];

// Scopri il range di date per la coppia settimana/anno passata in input
$dt = new DateTime;
$dt->setISODate($year, $week);
$from = $dt->format('Y-m-d');
$dt->modify("+{$numOfDays} days");
$to = $dt->format('Y-m-d');

// Imposta prossima settimana per paginator
$dt->setISODate($year, $week + 1);
$nextYear = $dt->format('Y');
$nextWeek = $dt->format('W');

// Imposta prossima settimana per paginator
$dt->setISODate($year, $week - 1);
$prevYear = $dt->format('Y');
$prevWeek = $dt->format('W');

$days = [$from];
for ($i = 1; $i < $numOfDays; $i++) {
    $days[] = date('Y-m-d', strtotime(sprintf('%s +%s days', $from, $i)));
}
$days[] = $to;

$today = (new DateTime)->format('Y-m-d');

$permission = $this->datab->getPermission($this->auth->get('users_id'));


$settings_modulo = $this->apilib->searchFirst('impostazioni_planner_squadre');
$mostra_cliente = $settings_modulo['impostazioni_planner_squadre_mostra_cliente'] ?? DB_BOOL_FALSE;
$mostra_impianto = $settings_modulo['impostazioni_planner_squadre_mostra_impianto'] ?? DB_BOOL_FALSE;
$where_persone = $settings_modulo['impostazioni_planner_squadre_where_custom_persone'] ?? null;
$where_automezzi = $settings_modulo['impostazioni_planner_squadre_where_custom_automezzi'] ?? null;

$utenti_permessi = $this->apilib->search('rel_impostazioni_planner_squadre_users');
if(!empty($utenti_permessi)) {
    $userIds = array_column($utenti_permessi, 'users_id');
    $userIdsString = implode(',', $userIds);
    $query = "users_id IN ($userIdsString)";

    $where_persone[] = $query;
}

//Filtro a monte gli appuntamenti
$_where_appuntamenti = $where_appuntamenti ? base64_decode($where_appuntamenti) : '';


$users = array_key_map_data($this->apilib->search('users'), 'users_id');

$tecnici = array_key_map_data($this->apilib->search('users', $where_persone), 'users_id');
$automezzi = array_key_map_data($this->apilib->search('automezzi', $where_automezzi), 'automezzi_id'); // $_appuntamenti = $this->apilib->search('appuntamenti', [
//     'appuntamenti_giorno >= ' => $days[0],
//     'appuntamenti_giorno <= ' => $days[$numOfDays],
//     $_where_appuntamenti,
// ]);

$_appuntamenti = $this->db
    ->join('customers', 'customers_id = appuntamenti_cliente', 'LEFT')
    ->join('projects', 'projects_id = appuntamenti_impianto', 'LEFT')
    ->get_where('appuntamenti', [
        'appuntamenti_giorno >= ' => $days[0],
        'appuntamenti_giorno <= ' => $days[$numOfDays],
        $_where_appuntamenti,
    ])->result_array();
// dump($_appuntamenti);

$appuntamenti = [];
$squadre = [];
$mezzi = [];

foreach ($_appuntamenti as $appuntamento) {
    $day = substr($appuntamento['appuntamenti_giorno'], 0, 10);
    if ($appuntamento['appuntamenti_cliente']) {
        $appuntamenti[$day][$appuntamento['appuntamenti_riga']][] = $appuntamento;
    }

    if ($day >= date('Y-m-d') && !array_key_exists($appuntamento['appuntamenti_riga'], $squadre)) {
        // SEZIONE PERSONE
        $appuntamento['appuntamenti_persone'] = (array)$appuntamento['appuntamenti_persone'];

        //Come squadra viene sempre presa quella "da oggi in poi". Le vecchie sono comunque storicizzate nella tabella appuntamneti
        foreach ($appuntamento['appuntamenti_persone'] as $users_id => $nomecognome) {
            if (!array_key_exists($users_id, $users)) {
                // debug($appuntamento);
                // debug($tecnici);
                unset($appuntamento['appuntamenti_persone'][$users_id]);
            }
        }
        if (!empty($appuntamento['appuntamenti_persone'])) {
            //$squadre[$appuntamento['appuntamenti_riga']] = array_keys($appuntamento['appuntamenti_persone']);
        }

        // SEZIONE AUTOMEZZI
        $appuntamento['appuntamenti_automezzi'] = (array)$appuntamento['appuntamenti_automezzi'];

        foreach ($appuntamento['appuntamenti_automezzi'] as $automezzo_id => $targa) {
            if (!array_key_exists($automezzo_id, $automezzi)) {
                // debug($appuntamento);
                // debug($tecnici);
                unset($appuntamento['appuntamenti_automezzi'][$automezzo_id]);
            }
        }
        if (!empty($appuntamento['appuntamenti_automezzi'])) {
            //$mezzi[$appuntamento['appuntamenti_riga']] = array_keys($appuntamento['appuntamenti_automezzi']);
        }
    }
}

$settings_module = $this->apilib->searchFirst('appuntamenti_squadre_impostazioni');
$planner_squadre_mode = $settings_module['appuntamenti_squadre_impostazioni_usa_drag'] ?? 0;

/* $tecnici_in_squadre = [];
foreach ($squadre as $squadra) {
    foreach ($squadra as $index => $tecnico) {
        $tecnici_in_squadre[$tecnico] = true;
    }
}

$mezzi_in_squadre = [];
foreach ($mezzi as $mezzo) {
    foreach ($mezzo as $index => $mezzo_id) {
        $mezzi_in_squadre[$mezzo_id] = true;
    }
} */
?>

<style>
@media print {
    * {
        visibility: hidden;
    }

    /* Show element to print, and any children he has. */
    .table-week-plan,
    .table-week-plan * {
        visibility: initial;
        font-size: 24px;
    }
}
</style>

<style>
/*
 * ========================================================
 * Planner 1
 * ========================================================
 */
#planner-container {
    overflow-x: auto;
    position: relative;
}

#planner-container .nav-tabs,
#planner-container .nav-pills {
    margin-bottom: 0;
}

#planner-container .column {
    width: 320px;
    display: table-cell;
    border-right: 1px solid #999;
    padding-left: 10px;
    padding-right: 10px;
}

#planner-container .column:first-child {
    border-left: 1px solid #999;
}

#planner-container .planner-header {
    display: table;
    table-layout: fixed;
    width: 100%;
}

#planner-container .planner-body {
    display: table;
    table-layout: fixed;
    width: 100%;
}

#planner-container .planner-header .column {
    color: #e4e4e4;
    text-align: center;
    background: #1f1f1f;
    border-top: 1px solid #999;
}

#planner-container .planner-header .column.blue {
    background: #4B8DF8;
    color: #fff
}

#planner-container .planner-header .column.purple {
    background: #852B99;
}

#planner-container .planner-header .column.yellow {
    background: #FFB848;
    color: #fff
}

#planner-container .planner-header .column.green {
    background: #35AA47;
    color: #fff
}

#planner-container .planner-header .column * {
    margin: 5px 0;
    color: inherit
}

#planner-container .planner-body .column {
    background: #efefef;
    padding-top: 10px;
    padding-bottom: 150px;
    border-bottom: 1px solid #999;
    vertical-align: top
}

#planner-container .sortable-box-placeholder.round-all {
    border-color: #000;
}

#planner-container .sortable .task-box {
    cursor: move
}




/** Grafica task-box **/
.task-box {
    margin-bottom: 10px;
    border: 1px solid #e4e4e4;
}

.task-box.blue {
    background: #4B8DF8
}

.task-box.purple {
    background: #852B99
}

.task-box.yellow {
    background: #FFB848
}

.task-box.green {
    background: #35AA47
}

.task-box.grey {
    background: #555555
}

.task-box.red {
    background: #FF0000
}

.task-box>.task-inner {
    padding: 6px 8px;
    margin-left: 10px;
    background: #fff;
    overflow: hidden;
    font-size: 11px;
}

.task-box>.task-inner .left {
    width: 85%
}

.task-box>.task-inner .right {
    width: 15%
}

.task-box .task-head,
.task-box .task-body {
    margin-bottom: 10px
}

.task-box .task-head .task-title {
    font-size: 15px
}

.task-box.blue .task-head .task-title {
    color: #4B8DF8
}

.task-box.purple .task-head .task-title {
    color: #852B99
}

.task-box.yellow .task-head .task-title {
    color: #FFB848
}

.task-box.green .task-head .task-title {
    color: #35AA47
}

.task-box.grey .task-head .task-title {
    color: #555555
}

.task-box.red .task-head .task-title {
    color: #FF0000
}

.task-box .task-head .task-title * {
    display: block;
    color: inherit;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.task-box .task-head .project {
    color: #777;
    text-decoration: none
}

.task-box .task-head .project:hover {
    color: #1f1f1f
}

.task-box .task-body .text {
    overflow: hidden;
    height: 33px;
    cursor: pointer;
    font-size: 10px
}

.task-box .task-foot {
    overflow: hidden
}

.task-box .task-foot .dates .expired {
    color: #E43538
}

.task-box .task-foot .dates {
    float: left
}

.task-box .task-foot .actions {
    float: right;
    width: 92px
}

.task-box .task-foot .actions .btn {
    padding: 3px 5px;
    font-size: 5px;
}

.task-box .photos {
    float: right;
    width: 40px;
    text-align: right
}


.task-box .photos img {
    margin-bottom: 5px
}

.warning-icon {
    width: 15px;
    margin-top: -3px;
    margin-left: 10px;
}




/** Show all */
#planner-container.limited {
    max-height: 1500px;
    overflow-y: hidden;
}

#planner-container .show-all {
    display: none;
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    text-align: center;
    font-size: 20px;
    padding: 55px 0 55px;
    border-top: 1px solid #e4e4e4;
    border-bottom: 1px solid #e4e4e4;
    background: linear-gradient(to bottom, rgba(255, 255, 255, 0.5), #FFF)
}

#planner-container .show-all:hover {
    color: #4B8DF8;
    cursor: pointer;
    border: 1px solid #e4e4e4;
    background: rgba(255, 255, 255, 0.89);
}

#planner-container.limited .show-all {
    display: block;
}




/** Grafica a portlet **/
#planner-container .portlet-title {
    padding: 5px 10px;
}

#planner-container .portlet-title .caption {
    margin: 0;
    font-size: 15px;
}

#planner-container .portlet-title .caption small {
    font-size: 70%;
}

#planner-container .portlet-title .actions {
    margin-top: 0;
}

#planner-container .portlet.solid {
    padding: 0;
}

#planner-container .portlet.solid .portlet-title {
    margin-bottom: 1px;
}

#planner-container .portlet.solid .portlet-body {
    padding: 10px;
}

#planner-container .portlet .portlet-body {
    cursor: pointer
}

#planner-container .portlet .portlet-body:hover {
    background: #EEEEEE
}


/*
 * ========================================================
 * Planner settimanale
 * ========================================================
 */

/* Table structure */
.table-week-plan th {
    background-color: #1f1f1f;
    color: #fff;
    font-size: 13px !important;
    font-weight: normal !important;
    text-align: center
}

.table-week-plan td {
    background-color: #EFEFEF;
    border-right: 1px solid #999;
    border-left: 1px solid #a4a4a4;
    border-bottom: 1px solid #999;
    border-top: 1px solid #a4a4a4;
    width: 290px
}

.avatar {
    border-radius: 50%;
    width: 45px;
}

.table-week-plan .avatar {
    width: 67px;
}

.table-week-plan .tasks-column {
    width: 275px;
    margin: 0 auto;
    min-height: 100px;
}


/* Task Box */
.table-week-plan .task-item {
    background-color: #fff;
    border: 1px solid #e4e4e4;
    margin-bottom: 8px;
}

.table-week-plan .task-item.blue {
    background-color: #4B8DF8
}

.table-week-plan .task-item.purple {
    background-color: #852B99
}

.table-week-plan .task-item.yellow {
    background-color: #FFB848
}

.table-week-plan .task-item.green {
    background-color: #35AA47
}

.table-week-plan .task-item.grey {
    background-color: #555555
}

.table-week-plan .task-item.red {
    background-color: #FF0000
}

.table-week-plan .task-item>.task-inner {
    padding: 5px 8px;
    margin-left: 5px;
    background: #fff;
    overflow: hidden;
}


.table-week-plan .task-item .task-title {
    font-size: 120%;
    float: left;
}

.table-week-plan .task-item.blue .task-title {
    color: #4B8DF8
}

.table-week-plan .task-item.purple .task-title {
    color: #852B99
}

.table-week-plan .task-item.yellow .task-title {
    color: #FFB848
}

.table-week-plan .task-item.green .task-title {
    color: #35AA47
}

.table-week-plan .task-item.grey .task-title {
    color: #555555
}

.table-week-plan .task-item .task-title a {
    display: block;
    color: inherit;
    text-decoration: none;
}

.table-week-plan .task-item .task-title a:hover {
    text-decoration: underline
}


.table-week-plan .task-item .project {
    font-size: 105%;
    color: #777;
    text-decoration: none
}

.table-week-plan .task-item .project:hover {
    color: #1f1f1f
}

.table-week-plan .task-item .actions {
    float: left;
    width: 20px;
}

.table-week-plan .task-item .actions a {
    font-size: 85%;
    text-decoration: none;
    width: 21px;
    height: 19px;
    margin: 1px 0;
}

.table-week-plan .task-item .actions a.purple {
    background: rgb(156, 39, 176);
    color: #ffffff;
}


/* Sortable */
.portlet-sortable-placeholder {
    border-color: #bbb;
    width: 70px;
    height: 70px;

    background-color: #e3e3e3;
    z-index: 999999999999;
    opacity: 0.7;
}

.table-week-plan .portlet-sortable-placeholder.round-all {
    border-color: #000;
    border-radius: 50%;
}

.table-week-plan .ui-sortable .task-item {
    cursor: move
}


.container-persone,
.container-mezzi {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    border: 1px solid #d2d2d2;
    border-radius: 5px;
    padding: 5px;
    min-height: 100px;
}

/*** circle ***/
.automezzo,
.persona {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    margin-bottom: 5px;
    margin-right: 10px;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    color: #ffffff;
    font-weight: 500;
    font-size: 9px;
    cursor: grab;
    overflow: hidden;
}

.automezzosmall,
.personasmall {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    margin-bottom: 0px;
    margin-right: 0px;
    display: flex;
    justify-content: center;
    align-items: center;
    flex-direction: column;
    color: #ffffff;
    font-weight: 500;
    font-size: 12px;
}

.customer_container {
    width: 100%;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    /*border: 2px dashed #d6d6d6;*/
    position: absolute;
    /*top: 15px;*/
    top: 5px;
    left: 0;
}

.box-persone {
    /*border-bottom: 1px dashed #999999;*/
    border-botton: 1px solid #d9d9d9
}

.box-persone,
.box-automezzi {
    /*width: 250px;*/
    width: 100%;
    height: 50%;
    min-height: 50px;
    min-width: 200px;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 5px;
}

td.relative {
    position: relative;
}

.selected_customer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-radius: 5px;
    padding: 5px 10px;
    margin-bottom: 10px;
    background-color: #fefefe;
    box-shadow: 1px 3px 12px 0px rgb(0 0 0 / 15%);
    flex-direction: column;
    cursor: pointer;
}

.selected_customer div.info {
    width: 100%;
    display: flex;
    justify-content: flex-start;
    flex-direction: column;
    margin: 0;
}

.selected_customer div.info>strong {
    margin-bottom: 6px;
    display: flex;
    justify-content: flex-start;
    flex-direction: column;
}

.selected_customer a.edit_appuntamento {
    margin-left: 5px;
}

.selected_customer_customer {
    width: 100%;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    flex-direction: column;
}

.selected_customer_info {
    width: 100%;
    display: flex;
    justify-content: space-around;
    align-items: center;
    margin-top: 10px;
}

.customer_detail,
.customer_detail:hover {
    color: inherit;
}

span.day_reference {
    font-size: 14px;
    font-weight: 500;
}

.appuntamento {
    cursor: grab;
}

.fixed_container {
    /* position: fixed;
        z-index: 999;
        width: 100%; */
    z-index: 999;
    padding-bottom: 20px;
    background: #f5f5f5;
}

.sticky {
    position: fixed;
    top: 0;
    left: 50px;
    width: 100%;
    padding-top: 20px;
    box-shadow: 0px 3px 3px 0px rgba(0, 0, 0, .25);
}

.sticky+.calendar_section {
    padding-top: 200px;
}

.selected_customer_composizione_squadra {
    width: 100%;
}

.container_appuntamenti .selected_customer {
    margin-top: 35px;
}

.table-week-plan tbody tr td {
    height: 140px;
}

.copia_appuntamento {
    cursor: pointer;
}

.copia_appuntamento span {
    color: #3c8dbc;
    font-size: 18px;
}

.custom_label_tecnici {
    margin-left: 10px;
}

.note_appuntamento {
    color: #3c8dbc;
}

.info .info_actions {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}


.settings_planner {
    width: 100%;
    margin-bottom: 16px;
}

.intestazione_planner {
    display: flex;
    justify-content: flex-start;
    flex-direction: column;
}

.intestazione_planner label,
.intestazione_planner div {
    width: 100%;
}

.settings_planner div.help-block {
    margin-left: 4px;
}

.opacity_clicked {
    opacity: 0.4;
}

.card_link {
    transition: all .3s ease-in;
}

.card_link:hover {
    color: #0073b7 !important;
}

.toggleUsers {
    margin-left: 16px;
    cursor: pointer;
}

.container-persone {
    max-height: 155px;
    overflow-y: hidden;
}

.container-persone_visible {
    max-height: unset;
    overflow-y: visible;
}

.container_intestazione {
    display: flex;
    justify-content: center;
    align-items: center;
}

.icon_intestazione {
    margin-right: 8px;
    font-size: 18px;
}
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-6">
            <div class="settings_planner">
                <div class="form-group">
                    <div class="intestazione_planner">
                        <label class="control-label">
                            Utilizza drag & drop
                        </label>
                        <div class="help-block">
                            Se disabilitato, il planner squadre funzionerà tramite click. Seleziona una persona/automezzo e poi clicca su un appuntamento, in questo modo l'elemento verrà inserito e salvato.
                        </div>
                    </div>
                    <div>
                        <?php if ($settings_module['appuntamenti_squadre_impostazioni_usa_drag'] == 0) : ?>
                        <a href="<?php echo base_url("db_ajax/change_value/appuntamenti_squadre_impostazioni/1/appuntamenti_squadre_impostazioni_usa_drag/1"); ?>" class="btn btn-xs btn-success js_link_ajax">
                            Abilita
                        </a>
                        <?php else : ?>
                        <a href="<?php echo base_url("db_ajax/change_value/appuntamenti_squadre_impostazioni/1/appuntamenti_squadre_impostazioni_usa_drag/0"); ?>" class="btn btn-xs btn-danger js_link_ajax">
                            Disabilita
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-12 col-md-6">
            <div class="settings_planner">
                <div class="form-group">
                    <div class="intestazione_planner">
                        <label class="control-label">
                            Imposta utenti da visualizzare
                        </label>
                        <div class="help-block">
                            Qui si possono specificare gli utenti da visualizzare nella selezione del planner
                        </div>
                    </div>
                    <div>
                        <a href="<?php echo base_url("get_ajax/modal_form/imposta-utenti-planner?_size=large"); ?>" class="btn btn-xs btn-primary js_open_modal">Imposta utenti</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="fixed_container" id="myHeader">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-7">
                <h3 class="text-center container_intestazione">
                    <span class="fas fa-users icon_intestazione"></span>Utenti
                    <span class="btn btn-xs btn-primary toggleUsers">Mostra tutti</span>
                </h3>
                <div class="container-persone">
                    <?php if (empty($tecnici)) : ?>
                    <div class="text-red">Nessun utente da visualizzare</div>
                    <?php else : ?>
                    <div style="display:flex;flex-direction: row;flex-wrap: wrap;" id="tecnici">
                        <?php foreach ($tecnici as $persona_id => $persona) : ?>
                        <div class="persona bg-primary js_selected_persona" data-users_id="<?php echo $persona['users_id']; ?>">
                            <?php if ($persona['users_avatar']) : ?>
                            <img class="avatar" src="<?php echo base_url("uploads/" . $persona['users_avatar']); ?>" <?php echo $planner_squadre_mode == 0 ? 'draggable="false"' : ''; ?> title="<?php echo $persona['users_first_name'] . " " . $persona['users_last_name']; ?>" data-toggle="tooltip" data-placement="right" />
                            <?php else : ?>
                            <img class="avatar" src="<?php echo base_url("images/user.png"); ?>" <?php echo $planner_squadre_mode == 0 ? 'draggable="false"' : ''; ?> title="<?php echo $persona['users_first_name'] . " " . $persona['users_last_name']; ?>" data-toggle="tooltip" data-placement="right" />
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <script>
                    $(function() {
                        $('.btn-showall').on('click', function() {
                            var this_btn = $(this);
                            var impiegati = $('#impiegati');

                            if (impiegati.is(':visible')) {
                                impiegati.css('display', 'none')
                            } else {
                                impiegati.css('display', 'flex')
                            }
                        })
                    })
                    </script>
                </div>
            </div>
            <div class="col-md-5">
                <h3 class="text-center">Automezzi</h3>
                <div class="container-mezzi">
                    <?php if (empty($automezzi)) : ?>
                    <div class="text-red">Nessun automezzo da visualizzare</div>
                    <?php else : ?>
                    <?php foreach ($automezzi as $automezzo_id => $automezzo) : ?>
                    <div class="automezzo bg-red js_selected_automezzo" data-automezzi_id="<?php echo $automezzo['automezzi_id']; ?>">
                        <div class="text-center"><?php echo $automezzo['automezzi_modello']; ?></div>
                        <div class="text-center automezzo_targa"><?php echo $automezzo['automezzi_targa']; ?></div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="calendar_section" data-get_pars="<?php echo $_SERVER['QUERY_STRING']; ?>">
    <div class="row">
        <div class="col-md-12">
            <nav>
                <ul class="pager">
                    <li class="previous">
                        <a href="?year=<?php echo $prevYear ?>&amp;week=<?php echo $prevWeek ?>">
                            <span aria-hidden="true">&larr;</span>
                            Dal <strong><?php echo (new DateTime)->setISODate($prevYear, $prevWeek)->format('d-m'); ?></strong>
                            al <strong><?php echo (new DateTime)->setISODate($prevYear, $prevWeek)->modify("+{$numOfDays} days")->format('d-m'); ?></strong>
                        </a>
                    </li>
                    <li style="font-size: 22px;">Dal <strong><?php echo (new DateTime($from))->format('d-m'); ?></strong> al <strong><?php echo (new DateTime($to))->format('d-m'); ?></strong></li>
                    <li class="next">
                        <a href="?year=<?php echo $nextYear ?>&amp;week=<?php echo $nextWeek ?>">
                            Dal <strong><?php echo (new DateTime)->setISODate($nextYear, $nextWeek)->format('d-m'); ?></strong>
                            al <strong><?php echo (new DateTime)->setISODate($nextYear, $nextWeek)->modify("+{$numOfDays} days")->format('d-m'); ?></strong>
                            <span aria-hidden="true">&rarr;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12 table-scrollable">
            <table id="week-planner" class="table table-condensed table-week-plan">
                <thead>
                    <tr>
                        <?php foreach ($days as $k => $day) : ?>
                        <?php
                            $tecnici_impegnati = [];

                            if (!empty($appuntamenti[$day])) {
                                foreach ($appuntamenti[$day] as $key => $_appuntamenti) {
                                    foreach ($_appuntamenti as $key => $appuntamento) {
                                        if (!empty($appuntamento['appuntamenti_persone'])) {
                                            foreach ($appuntamento['appuntamenti_persone'] as $tecnico_id => $tecnico) {
                                                $tecnici_impegnati[$tecnico_id] = $tecnico;
                                            }
                                        }
                                    }
                                }
                            }
                            //debug($tecnici_impegnati);
                            ?>
                        <th><span class="day_reference"><?php echo $weekMap[$k % 6] . ', &nbsp; ' . dateFormat($day, 'd.m'); ?></span> <span class="label label-primary custom_label_tecnici" data-toggle="tooltip" title="Tecnici impeganti"><?php echo count($tecnici_impegnati); ?></span></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 1; $i <= 10; $i++) : ?>
                    <tr data-riga="<?php echo $i; ?>">
                        <?php foreach ($days as $k => $day) : ?>

                        <td class="relative container_appuntamenti" data-day="<?php echo $day; ?>" data-riga="<?php echo $i; ?>" style="min-width: 250px;">
                            <div class="customer_container">
                                <a href="javascript:void(0);" data-url="<?php echo base_url("get_ajax/modal_form/new-appuntamenti-cliente?appuntamenti_giorno=$day"); ?>" data-toggle="tooltip" title="Aggiungi appuntamento" class="js_aggiungi_cliente btn btn-sm">
                                    <i class="fas fa-plus"></i>
                                </a>
                            </div>
                            <?php //debug($appuntamenti, true); ?>
                            <?php if (!empty($appuntamenti[$day][$i])) : ?>
                            <?php foreach ($appuntamenti[$day][$i] as $appuntamento) : ?>

                            <?php //debug($appuntamento, true);  ?>

                            <div class="js_card_clicked selected_customer appuntamento" data-appuntamento_id="<?php echo $appuntamento['appuntamenti_id']; ?>" data-previous_riga="<?php echo $i; ?>">
                                <div class="selected_customer_customer">

                                    <div class="selected_customer_composizione_squadra">
                                        <!-- Squadra - persone -->
                                        <div class="box-persone">
                                            <?php if (!empty($appuntamento['appuntamenti_persone'])) : ?>
                                            <?php foreach ($appuntamento['appuntamenti_persone'] as $users_id => $name) : ?>

                                            <div class="persona bg-primary ui-sortable-handle" data-users_id="<?php echo $users_id; ?>" style="opacity: 1;" data-previous_riga="<?php echo $i; ?>" data-appuntamento_id="<?php echo $appuntamento['appuntamenti_id']; ?>">
                                                <?php if ($users[$users_id]['users_avatar']) : ?>
                                                <img class="avatar" src="<?php echo base_url("uploads/" . $users[$users_id]['users_avatar']); ?>" <?php echo $planner_squadre_mode == 0 ? 'draggable="false"' : ''; ?> title="<?php echo $users[$users_id]['users_first_name'] . " " . $users[$users_id]['users_last_name']; ?>" data-toggle="tooltip" data-placement="right" />
                                                <?php else : ?>
                                                <img class="avatar" src="<?php echo base_url("images/user.png"); ?>" <?php echo $planner_squadre_mode == 0 ? 'draggable="false"' : ''; ?> title="<?php echo @$users[$users_id]['users_first_name'] . " " . @$users[$users_id]['users_last_name']; ?>" data-toggle="tooltip" data-placement="right" />
                                                <?php endif; ?>

                                            </div>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                        <!-- Squadra - mezzi -->
                                        <div class="box-automezzi">
                                            <?php if (!empty($appuntamento['appuntamenti_automezzi'])) : ?>
                                            <?php foreach ($appuntamento['appuntamenti_automezzi'] as $mezzo_id => $mezzo) : ?>
                                            <div class="automezzo bg-red ui-sortable-handle" data-automezzi_id="<?php echo $mezzo_id; ?>" style="opacity: 1;" data-previous_riga="<?php echo $i; ?>" data-appuntamento_id="<?php echo $appuntamento['appuntamenti_id']; ?>">
                                                <div class="text-center"><?php echo $automezzi[$mezzo_id]['automezzi_modello']; ?></div>
                                                <div class="text-center"><?php echo $automezzi[$mezzo_id]['automezzi_targa']; ?></div>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="info">
                                        <strong>
                                            <?php if($mostra_cliente == DB_BOOL_TRUE): ?>
                                            <a target="blank" href="<?php echo base_url("main/layout/customer-detail/" . $appuntamento['appuntamenti_cliente']); ?>" class="text-black card_link">
                                                <?php echo ($appuntamento['customers_company'] ?? $appuntamento['customers_name'] . ' ' . $appuntamento['customers_last_name']); ?>
                                            </a>
                                            <?php endif; ?>

                                            <?php if (!empty($appuntamento['appuntamenti_impianto']) && $mostra_cliente == DB_BOOL_TRUE) : ?>
                                            <a target="_blank" href="<?php echo base_url("main/layout/dettaglio_progetto_commessa/" . $appuntamento['appuntamenti_impianto']); ?>" class="text-black card_link">
                                                <?php echo $appuntamento['projects_name']; ?>
                                            </a>
                                            <?php endif; ?>

                                            <?php if (!empty($appuntamento['appuntamenti_note'])) : ?>
                                            <p title="<?php echo strip_tags($appuntamento['appuntamenti_note']); ?>" data-toggle="tooltip" data-placement="bottom" style="margin-bottom: 5px; font-weight: 600; font-size: 12px;">
                                                <?php echo strlen($appuntamento['appuntamenti_note']) > 31 ? substr($appuntamento['appuntamenti_note'], 0, 30).'...' : $appuntamento['appuntamenti_note']; ?>
                                            </p>
                                            <?php endif; ?>
                                        </strong>

                                        <div class="info_actions">
                                            <a class="js_open_modal label bg-blue" href="<?php echo base_url("get_ajax/modal_form/edit-appuntamento-cliente/{$appuntamento['appuntamenti_id']}"); ?>">
                                                Modifica
                                            </a>

                                            <a href="<?php echo base_url('planner-squadre/planner/duplicaAppuntamento/' . $appuntamento['appuntamenti_id']); ?>" class="js_copia_appuntamento copia_appuntamento label bg-blue">
                                                Copia
                                            </a>
                                        </div>
                                    </div>

                                </div>
                                <div class="selected_customer_info" style="display:none;">
                                    <?php foreach ((array)$appuntamento['appuntamenti_persone'] as $id => $persona) :   $expl = explode(' ', $persona);                                         ?>
                                    <div class="personasmall bg-primary" data-users_id="<?php echo $id; ?>" data-toggle="tooltip" data-placement="top" title="<?php echo $persona; ?>">
                                        <div class="text-center"><?php echo substr($expl[0], 0, 1); ?>.<?php echo substr($expl[1], 0, 1); ?>.</div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php foreach ((array)$appuntamento['appuntamenti_automezzi'] as $id => $automezzo) :                                          ?>
                                    <div class="automezzosmall bg-red" data-automezzi_id="<?php echo $id; ?>" data-toggle="tooltip" data-placement="top" title="<?php echo $automezzo; ?>">
                                        <div class="text-center"><?php echo explode(' ', $automezzo)[0]; ?></div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <?php endforeach; ?>
                            <?php endif; ?>

                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endfor; ?>


                </tbody>
            </table>
        </div>
    </div>
</section>



<script>
window.onscroll = function() {
    //myFunction()
};

var header = document.getElementById("myHeader");
var sticky = header.offsetTop;

function myFunction() {
    if (window.pageYOffset > sticky) {
        header.classList.add("sticky");
    } else {
        header.classList.remove("sticky");
    }
}
</script>

<script>
var getRowUsers = function(riga) {
    var $tr = $('tr[data-riga="' + riga + '"]');
    var $box_persone = $('.box-persone', $tr);
    var users = [];
    $('.persona', $box_persone).each(function(i, persona) {
        var $persona = $(persona);
        users.push($persona.data('users_id'));
    });
    return users;
}
var getRowAutomezzi = function(riga) {
    var $tr = $('tr[data-riga="' + riga + '"]');
    var $box_automezzi = $('.box-automezzi', $tr);
    var automezzi = [];
    $('.automezzo', $box_automezzi).each(function(i, automezzo) {
        var $automezzo = $(automezzo);
        automezzi.push($automezzo.data('automezzi_id'));
    });
    return automezzi;
}
var aggiornaAppuntamento = function(id, day, riga) {

    $.ajax({
        method: 'get',
        url: base_url + "planner-squadre/planner/aggiornaAppuntamento/" + id + "/" + day + '/' + riga,

        success: function(ajax_response) {
            console.log('Salvato!');
            //salvaRiga(riga);

        }
    });
}
var aggiungiMezzoAppuntamento = function(id, mezzo_id) {
    $.ajax({
        method: 'get',
        url: base_url + "planner-squadre/planner/aggiungiMezzoAppuntamento/" + id + "/" + mezzo_id,

        success: function(ajax_response) {
            console.log('Salvato!');

        }
    });
};
var aggiungiPersonaAppuntamento = function(id, persona_id) {
    $.ajax({
        method: 'get',
        url: base_url + "planner-squadre/planner/aggiungiPersonaAppuntamento/" + id + "/" + persona_id,

        success: function(ajax_response) {
            console.log('Salvato!');

        }
    });
};
var rimuoviMezzoAppuntamento = function(id, mezzo_id, dom_automezzo) {
    $.ajax({
        method: 'get',
        url: base_url + "planner-squadre/planner/rimuoviMezzoAppuntamento/" + id + "/" + mezzo_id,

        success: function(ajax_response) {
            console.log('Salvato!');
            dom_automezzo && dom_automezzo.remove();
        }
    });
};
var rimuoviPersonaAppuntamento = function(id, persona_id, dom_persona) {
    $.ajax({
        method: 'get',
        url: base_url + "planner-squadre/planner/rimuoviPersonaAppuntamento/" + id + "/" + persona_id,

        success: function(ajax_response) {
            console.log('Salvato!');
            dom_persona && dom_persona.remove();
        }
    });
};
var salvaRiga = function(riga) {
    var $tr = $('tr[data-riga="' + riga + '"]');

    var users = getRowUsers(riga);
    var automezzi = getRowAutomezzi(riga);

    console.log(users);
    console.log(automezzi);

    var data = {};
    data.users = users;
    data.automezzi = automezzi;
    data.riga = riga;

    //TODO:
    data.giorni = <?php echo json_encode($days); ?>;

    //TODO: ajax per salvare la riga passando data
    console.log('TODO: ajax per salvare la riga passando data');
    /*         $.ajax({
                method: 'post',
                url: base_url + "planner-squadre/planner/salvaRigaCalendarioLavoriSquadre",
                data: data,
                success: function(ajax_response) {
                    if (ajax_response.status == '0') {
                        alert(ajax_response.txt);
                    } else {
                        // refreshare ajax
                    }
                    //alert('Salvato!');
                    console.log('Salvato!');
                }
            }); */
};



$(function() {
    const plannerMode = <?php echo $planner_squadre_mode; ?>;

    //MODALITA DRAG AND DROP
    if (plannerMode) {
        /************ CONTAINER PERSONE ****************/
        $('.container-persone').sortable({
            connectWith: '.box-persone',
            items: '.persona',
            opacity: 0.8,
            forceHelperSize: true,
            placeholder: 'portlet-sortable-placeholder round-all',
            forcePlaceholderSize: true,
            tolerance: "pointer",
        }).on('sortupdate', function(event, ui) {
            if (this === ui.item.parent().parent()[0]) {
                var riga_precedente = ui.item.data('previous_riga');

                var riga_id = ui.item.closest('tr').data('riga');
                ui.item.data('previous_riga', riga_id);
                // salvaRiga(riga_precedente);
                // salvaRiga(riga_id);
                var persona_id = ui.item.data('users_id');
                var appuntamento_id = ui.item.data('appuntamento_id');

                rimuoviPersonaAppuntamento(appuntamento_id, persona_id);
            }
        });
        $('.container-mezzi').sortable({
            connectWith: '.box-automezzi',
            items: '.automezzo',
            opacity: 0.8,
            forceHelperSize: true,
            placeholder: 'portlet-sortable-placeholder round-all',
            forcePlaceholderSize: true,
            tolerance: "pointer",
        }).on('sortupdate', function(event, ui) {
            if (this === ui.item.parent()[0]) {
                var riga_precedente = ui.item.data('previous_riga');

                var riga_id = ui.item.closest('tr').data('riga');
                ui.item.data('previous_riga', riga_id);
                console.log(ui.item.parent());
                // salvaRiga(riga_precedente);
                // salvaRiga(riga_id);
                var mezzo_id = ui.item.data('automezzi_id');
                var appuntamento_id = ui.item.data('appuntamento_id');

                rimuoviMezzoAppuntamento(appuntamento_id, mezzo_id);
            }
        });


        /************ CONTAINER MEZZI ****************/
        $('.box-persone').sortable({
            connectWith: ['.box-persone', '.container-persone'],
            items: '.persona',
            opacity: 0.8,
            forceHelperSize: true,
            placeholder: 'portlet-sortable-placeholder round-all',
            forcePlaceholderSize: true,
            tolerance: "pointer",
        }).on('sortupdate', function(event, ui) {
            if (this === ui.item.parent()[0]) {
                var riga_precedente = ui.item.data('previous_riga');

                var riga_id = ui.item.closest('tr').data('riga');
                ui.item.data('previous_riga', riga_id);
                // salvaRiga(riga_precedente);
                // salvaRiga(riga_id);
                var appuntamento_id = $(event.currentTarget.closest('.appuntamento')).data('appuntamento_id');
                ui.item.data('appuntamento_id', appuntamento_id);
                var persona_id = ui.item.data('users_id');

                aggiungiPersonaAppuntamento(appuntamento_id, persona_id);
                $('#tecnici').prepend(ui.item.clone());
            }
        });

        $('.box-automezzi').sortable({
            connectWith: ['.box-automezzi', '.container-mezzi'],
            items: '.automezzo',
            opacity: 0.8,
            forceHelperSize: true,
            placeholder: 'portlet-sortable-placeholder round-all',
            forcePlaceholderSize: true,
            tolerance: "pointer",
        }).on('sortupdate', function(event, ui) {
            if (this === ui.item.parent()[0]) {
                var riga_precedente = ui.item.data('previous_riga');

                var riga_id = ui.item.closest('tr').data('riga');
                ui.item.data('previous_riga', riga_id);

                var appuntamento_id = $(event.currentTarget.closest('.appuntamento')).data('appuntamento_id');
                ui.item.data('appuntamento_id', appuntamento_id);

                var mezzo_id = ui.item.data('automezzi_id');

                aggiungiMezzoAppuntamento(appuntamento_id, mezzo_id);
                $('.container-mezzi').prepend(ui.item.clone());
            }
        });

        //Rendo draggabili anche gli appuntamenti dentro il planner
        $('.container_appuntamenti').sortable({
            connectWith: ['.container_appuntamenti'],
            items: '.appuntamento',
            opacity: 0.8,
            forceHelperSize: true,
            placeholder: 'portlet-sortable-placeholder',
            forcePlaceholderSize: true,
            tolerance: "pointer",
            delay: 500
        }).on('sortupdate', function(event, ui) {
            if (this === ui.item.parent()[0]) {
                //console.log('sortupdate appuntamento');
                //console.log(event.currentTarget);
                var day = $(event.currentTarget).data('day');
                var riga = $(event.currentTarget).data('riga');

                var appuntamento_id = ui.item.data('appuntamento_id');

                //alert('sposto appuntamento ' + appuntamento_id);

                aggiornaAppuntamento(appuntamento_id, day, riga);
            }
        });

    } else {
        //MODALITA CLICK

        /**
         * ! Selezione persona
         */
        const container_persone = $('.container-persone');
        var selected_persona = null;
        $('.js_selected_persona', container_persone).on('click', function() {
            //console.log($(this));
            selected_persona = $(this);
            selected_persona.addClass('opacity_clicked');
        });
        /**
         * ! Click su card, devo incollare persona 
         */
        var card_clicked = null;
        $('.js_card_clicked').on('click', function() {
            card_clicked = $(this);
            if (selected_persona) {
                var riga_id = card_clicked.closest('tr').data('riga');
                var persona_id = selected_persona.data('users_id');
                var appuntamento_id = card_clicked.data('appuntamento_id');

                aggiungiPersonaAppuntamento(appuntamento_id, persona_id);

                var cloned_persona = selected_persona.clone(false, true);
                cloned_persona.attr('data-appuntamento_id', appuntamento_id);
                cloned_persona.attr('data-previous_riga', riga_id);
                cloned_persona.removeClass('opacity_clicked');

                var card_container_automezzi = card_clicked.find('.box-persone');
                card_container_automezzi.append(cloned_persona);

                selected_persona.removeClass('opacity_clicked');
                selected_persona = null;
                card_clicked = null;
            }
        });
        /**
         * ! Rimozione persona dall'appuntamento
         */
        const js_card_clicked = $('.js_card_clicked');
        //        $('.persona', '.js_card_clicked').on('click', function() {
        $('.js_card_clicked').on('click', '.persona', function() {
            //console.log($(this))
            var persona_clicked = $(this);
            card_clicked = persona_clicked.closest('.js_card_clicked');

            if (persona_clicked) {
                var riga_id = card_clicked.closest('tr').data('riga');
                card_clicked.data('previous_riga', riga_id);

                var persona_id = persona_clicked.data('users_id');
                var appuntamento_id = card_clicked.data('appuntamento_id');

                var riferimento_persona = persona_clicked.find('img.avatar').data('original-title');

                x = confirm('Vuoi rimuovere ' + riferimento_persona + ' dall\'appuntamento?');
                if (x) {
                    rimuoviPersonaAppuntamento(appuntamento_id, persona_id, persona_clicked);
                }

                riga_id = null;
                persona_clicked = null;
                card_clicked = null;
                riferimento_persona = null;
            }
        });


        /**
         * ! Selezione automezzo
         */
        const container_mezzi = $('.container-mezzi');
        var selected_automezzo = null;
        $('.js_selected_automezzo', container_mezzi).on('click', function() {
            //console.log($(this));
            selected_automezzo = $(this);
            selected_automezzo.addClass('opacity_clicked');
        });
        /**
         * ! Click su card, devo incollare automezzo 
         */
        var card_clicked = null;
        $('.js_card_clicked').on('click', function() {
            card_clicked = $(this);
            if (selected_automezzo) {
                var riga_id = card_clicked.closest('tr').data('riga');
                var mezzo_id = selected_automezzo.data('automezzi_id');
                var appuntamento_id = card_clicked.data('appuntamento_id');

                aggiungiMezzoAppuntamento(appuntamento_id, mezzo_id);

                var cloned_automezzo = selected_automezzo.clone(false, true);
                cloned_automezzo.attr('data-appuntamento_id', appuntamento_id);
                cloned_automezzo.attr('data-previous_riga', riga_id);
                cloned_automezzo.removeClass('opacity_clicked');
                cloned_automezzo.removeClass('js_selected_automezzo');

                var card_container_automezzi = card_clicked.find('.box-automezzi');
                card_container_automezzi.append(cloned_automezzo);

                selected_automezzo.removeClass('opacity_clicked');
                selected_automezzo = null;
                card_clicked = null;
            }
        });
        /**
         * ! Rimozione automezzo dall'appuntamento
         */
        //        $('.automezzo', '.js_card_clicked').on('click', function() {
        $('.js_card_clicked').on('click', '.automezzo', function() {
            var automezzo_clicked = $(this);
            card_clicked = automezzo_clicked.closest('.js_card_clicked');

            if (automezzo_clicked) {
                var riga_id = card_clicked.closest('tr').data('riga');
                card_clicked.data('previous_riga', riga_id);

                var mezzo_id = automezzo_clicked.data('automezzi_id');
                var appuntamento_id = card_clicked.data('appuntamento_id');

                x = confirm('Vuoi rimuovere questo automezzo dall\'appuntamento?');
                if (x) {
                    rimuoviMezzoAppuntamento(appuntamento_id, mezzo_id, automezzo_clicked);
                }

                riga_id = null;
                automezzo_clicked = null;
                card_clicked = null;
            }
        });
    }




    $('.js_aggiungi_cliente').on('click', function() {
        var url = $(this).data('url');
        var riga_id = $(this).closest('tr').data('riga');
        var users = getRowUsers(riga_id);
        var automezzi = getRowAutomezzi(riga_id);
        //console.log(users);
        var append_pars = '';
        // for (var i in users) {
        //     console.log(users[i]);
        //     append_pars += '&appuntamenti_persone[' + users[i] + '] = ' + users[i];
        // }
        // for (var i in automezzi) {
        //     append_pars += '&appuntamenti_automezzi[' + automezzi[i] + ']=' + automezzi[i];
        // }
        append_pars += '&appuntamenti_riga=' + riga_id;
        url = url + append_pars;

        var data_post = [];
        data_post.push({
            name: token_name,
            value: token_hash
        });

        loadModal(url, data_post);
    });

    //Copia appuntamento per il giorno successivo
    $('.js_copia_appuntamento').on('click', function() {
        //console.log('copia appuntamento...');
        /*$.ajax({
            method: 'get',
            url: base_url + "planner-squadre/planner/aggiungiMezzoAppuntamento/" + id + "/" + mezzo_id,
            success: function(ajax_response) {
                window.location.reload(true);
                console.log('Appuntamento copiato per il giorno successivo');
            }
        });*/
    });

    $('body').on('click', '.btn-danger.js_confirm_button.js_link_ajax', function(e) {

        $('.modal').modal('toggle');
    });



    /* Show/hide more users */
    $('.toggleUsers').on('click', function() {
        const toggler = $(this);
        const toggler_container = $(this).parent();
        const target = toggler_container.siblings('.container-persone');

        target.toggleClass('container-persone_visible');

        if (target.hasClass('container-persone_visible')) {
            toggler.text('Mostra di meno');
        } else {
            toggler.text('Mostra tutti');
        }
    })
});
</script>