<script src="<?php echo base_url(); ?>script/global/plugins/sortable/Sortable.min.js?v=2.2.2"></script>

<?php //$this->layout->addModuleJavascript('planner-squadre', 'main.js'); 
?>
<?php $this->layout->addModuleStylesheet('planner-squadre', 'main.css'); ?>

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
$mostra_sab_dom = $impostazioni['impostazioni_planner_squadre_mostra_sab_dom'] ?? DB_BOOL_FALSE;

$utenti_permessi = $this->apilib->search('rel_impostazioni_planner_squadre_users');
if (!empty($utenti_permessi)) {
    $userIds = array_column($utenti_permessi, 'users_id');
    $userIdsString = implode(',', $userIds);
    $query = "users_id IN ($userIdsString)";

    $where_persone[] = $query;
}

//Filtro a monte gli appuntamenti
//$_where_appuntamenti = !empty($where_appuntamenti) ? base64_decode($where_appuntamenti) : '';
$_where_appuntamenti = isset($where_appuntamenti) && !empty($where_appuntamenti) ? base64_decode($where_appuntamenti) : [];

$users = array_key_map_data($this->apilib->search('users'), 'users_id');

$tecnici = array_key_map_data($this->apilib->search('users', $where_persone), 'users_id');
$automezzi = array_key_map_data($this->apilib->search('automezzi', $where_automezzi), 'automezzi_id');

$_appuntamenti = $this->apilib->search('appuntamenti', [
    'appuntamenti_giorno >= ' => $days[0],
    'appuntamenti_giorno <= ' => $days[$numOfDays],
    $_where_appuntamenti,
], 0, null, 'appuntamenti_ora_inizio ASC, appuntamenti_ora_fine ASC');
;
/* $_appuntamenti = $this->db
    ->join('customers', 'customers_id = appuntamenti_cliente', 'LEFT')
    ->join('projects', 'projects_id = appuntamenti_impianto', 'LEFT')
    ->get_where('appuntamenti', [
        'appuntamenti_giorno >= ' => $days[0],
        'appuntamenti_giorno <= ' => $days[$numOfDays],
        $_where_appuntamenti,
    ])->result_array(); */

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
        $appuntamento['appuntamenti_persone'] = (array) $appuntamento['appuntamenti_persone'];

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
        $appuntamento['appuntamenti_automezzi'] = (array) $appuntamento['appuntamenti_automezzi'];

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


<?php /*
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
*/ ?>


<section class="labels_container">
    <div>
        <a href="javascript:void(0);" class="label_settings toggle_fullscreen" data-toggle="tooltip" title="Fullscreen" data-placement="left">
            <i class="fas fa-compress"></i>
        </a>
    </div>
    <div>
        <a href="<?php echo base_url("get_ajax/layout_modal/personalizza_planner_squadre?_size=large"); ?>" class="js_open_modal label_settings" data-toggle="tooltip" title="Impostazioni" data-placement="left">
            <i class="fas fa-cogs"></i>
        </a>
    </div>
    <div>
        <a class="js_open_selection label_settings" data-toggle="tooltip" title="Persone e mezzi" data-placement="left">
            <i class="fas fa-users"></i>
        </a>
    </div>
</section>

<section class="fixed_container" id="myHeader">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="pull-right close_persone_mezzi">
                    <i class="fas fa-times"></i>
                </div>
            </div>
            <div class="col-sm-12">
                <h3 class="text-center container_intestazione">
                    <span class="fas fa-users icon_intestazione"></span><span class="sidebar_section_title">Utenti</span>
                    <span class="btn btn-xs btn-primary toggleUsers">Mostra tutti</span>
                </h3>
                <div class="container-persone">
                    <?php if (empty($tecnici)): ?>
                    <div class="text-red">Nessun utente da visualizzare</div>
                    <?php else: ?>
                    <div style="display:flex;flex-direction: row;flex-wrap: wrap;" id="tecnici">
                        <?php foreach ($tecnici as $persona_id => $persona): ?>
                        <div class="persona container_single_persona bg-primary js_selected_persona ui-sortable-handle" data-users_id="<?php echo $persona['users_id']; ?>">
                            <?php if ($persona['users_avatar']): ?>
                            <img class="avatar" src="<?php echo base_url("uploads/" . $persona['users_avatar']); ?>" <?php echo $planner_squadre_mode == 0 ? 'draggable="false"' : ''; ?> title="<?php echo $persona['users_first_name'] . " " . $persona['users_last_name']; ?>" data-toggle="tooltip" data-placement="right" />
                            <?php else: ?>
                            <img class="avatar" src="<?php echo base_url("images/user.png"); ?>" <?php echo $planner_squadre_mode == 0 ? 'draggable="false"' : ''; ?> title="<?php echo $persona['users_first_name'] . " " . $persona['users_last_name']; ?>" data-toggle="tooltip" data-placement="right" />
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-sm-12">
                <h3 class="text-center">
                    <span class="fas fa-car icon_intestazione"></span><span class="sidebar_section_title">Automezzi</span>
                </h3>
                <div class="container-mezzi">
                    <?php if (empty($automezzi)): ?>
                    <div class="text-red">Nessun automezzo da visualizzare</div>
                    <?php else: ?>
                    <?php foreach ($automezzi as $automezzo_id => $automezzo): ?>
                    <div class="automezzo container_single_automezzo bg-automezzo js_selected_automezzo ui-sortable-handle" data-automezzi_id="<?php echo $automezzo['automezzi_id']; ?>">
                        <div class="text-center"><?php echo strlen($automezzo['automezzi_modello']) > 9 ? substr($automezzo['automezzi_modello'], 0, 8) . '...' : $automezzo['automezzi_modello']; ?></div>
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
                        <?php
                        foreach ($days as $k => $day):
                            $tecnici_impegnati = [];
                            $automezzi_impegnati = [];

                            if (!empty($appuntamenti[$day])) {
                                foreach ($appuntamenti[$day] as $_appuntamenti) {
                                    foreach ($_appuntamenti as $appuntamento) {
                                        // Se ci sono tecnici associati all'appuntamento, aggiungili all'array
                                        if (!empty($appuntamento['appuntamenti_persone'])) {
                                            $tecnici_impegnati = array_merge($tecnici_impegnati, $appuntamento['appuntamenti_persone']);
                                        }
                                        // Se ci sono automezzi associati all'appuntamento, aggiungili all'array
                                        if (!empty($appuntamento['appuntamenti_automezzi'])) {
                                            $automezzi_impegnati = array_merge($automezzi_impegnati, $appuntamento['appuntamenti_automezzi']);
                                        }
                                    }
                                }
                                // Rimuovi i duplicati (se un tecnico o automezzo può essere assegnato più volte)
                                $tecnici_impegnati = array_unique($tecnici_impegnati, SORT_REGULAR);
                                $automezzi_impegnati = array_unique($automezzi_impegnati, SORT_REGULAR);
                            }

                            // N° tecnici ed automezzi impegnati
                            $numero_tecnici_impegnati = count($tecnici_impegnati);
                            $numero_automezzi_impegnati = count($automezzi_impegnati);
                            ?>
                        <th>
                            <div style="display: flex; justify-content: space-around; align-items: baseline; gap: 12px;">
                                <span class="day_reference"><?php echo substr($weekMap[$k % 6], 0, 3) . '&nbsp;' . dateFormat($day, 'd'); ?></span>
                                <div>
                                    <span class="custom_label_tecnici"><i class="fas fa-users"></i><?php echo $numero_tecnici_impegnati; ?></span>
                                    <span class="custom_label_tecnici"><i class="fas fa-car"></i><?php echo $numero_automezzi_impegnati; ?></span>
                                </div>
                                <span>
                                    <a href="<?php echo base_url("get_ajax/modal_form/new-appuntamenti-cliente?appuntamenti_giorno=$day"); ?>" data-toggle="tooltip" title="Aggiungi appuntamento" class="js_aggiungi_cliente js_open_modal btn btn-sm">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                </span>
                            </div>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for ($i = 1; $i <= 20; $i++): ?>
                    <tr data-riga="<?php echo $i; ?>">
                        <?php foreach ($days as $k => $day): ?>

                        <td class="relative container_appuntamenti" data-day="<?php echo $day; ?>" data-riga="<?php echo $i; ?>" style="min-width: 250px;">
                            <?php if (!empty($appuntamenti[$day][$i])): ?>
                            <?php foreach ($appuntamenti[$day][$i] as $appuntamento): ?>
                            <?php //debug($appuntamento, true); 
                                                            ?>

                            <div class="selected_customer appuntamento" data-appuntamento_id="<?php echo $appuntamento['appuntamenti_id']; ?>" data-previous_riga="<?php echo $i; ?>">
                                <div class="selected_customer_customer">
                                    <div class="js_card_clicked selected_customer_composizione_squadra">
                                        <!-- Squadra - persone -->
                                        <div class="box-persone ui-sortable-handle">
                                            <?php
                                                            if (!empty($appuntamento['appuntamenti_persone'])) {
                                                                $total_persons = count($appuntamento['appuntamenti_persone']);
                                                                $display_limit = 2;
                                                                $j = 0;

                                                                foreach ($appuntamento['appuntamenti_persone'] as $users_id => $name) {
                                                                    if ($j < $display_limit) {
                                                                        $avatar_src = $users[$users_id]['users_avatar']
                                                                            ? base_url("uploads/" . $users[$users_id]['users_avatar'])
                                                                            : base_url("images/user.png");

                                                                        $user_name = $users[$users_id]['users_first_name'] . " " . $users[$users_id]['users_last_name'];
                                                                        ?>
                                            <div class="persona bg-primary ui-sortable-handle" data-users_id="<?php echo $users_id; ?>" style="opacity: 1;" data-previous_riga="<?php echo $i; ?>" data-appuntamento_id="<?php echo $appuntamento['appuntamenti_id']; ?>">
                                                <img class="avatar" src="<?php echo $avatar_src; ?>" <?php echo $planner_squadre_mode == 0 ? 'draggable="false"' : ''; ?> title="<?php echo $user_name; ?>" data-toggle="tooltip" data-placement="right" />
                                            </div>
                                            <?php
                                                                    }
                                                                    $j++;
                                                                }

                                                                if ($j > $display_limit) {
                                                                    ?>
                                            <div class="persona rimanenti avatar-rimanenti">
                                                +<?php echo ($j - $display_limit); ?>
                                            </div>
                                            <?php
                                                                }
                                                            }
                                                            ?>
                                        </div>

                                        <!-- Squadra - mezzi -->
                                        <div class="box-automezzi ui-sortable-handle">
                                            <?php if (!empty($appuntamento['appuntamenti_automezzi'])): ?>
                                            <?php foreach ($appuntamento['appuntamenti_automezzi'] as $mezzo_id => $mezzo): ?>
                                            <div class="automezzo bg-automezzo ui-sortable-handle" data-automezzi_id="<?php echo $mezzo_id; ?>" style="opacity: 1;" data-previous_riga="<?php echo $i; ?>" data-appuntamento_id="<?php echo $appuntamento['appuntamenti_id']; ?>">
                                                <div class="text-center"><?php echo $automezzi[$mezzo_id]['automezzi_modello']; ?></div>
                                                <div class="text-center"><?php echo $automezzi[$mezzo_id]['automezzi_targa']; ?></div>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="info">
                                        <strong>
                                            <?php if (!empty($appuntamento['appuntamenti_impianto']) && $mostra_cliente == DB_BOOL_TRUE): ?>
                                            <a target="_blank" href="<?php echo base_url("main/layout/dettaglio_progetto_commessa/" . $appuntamento['appuntamenti_impianto']); ?>" class="text-black card_link card_link_impianto">
                                                <?php echo $appuntamento['projects_name']; ?>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($mostra_cliente == DB_BOOL_TRUE): ?>
                                            <a target="blank" href="<?php echo base_url("main/layout/customer-detail/" . $appuntamento['appuntamenti_cliente']); ?>" class="text-black card_link card_link_customer">
                                                <?php
                                                                    $customer_name = $appuntamento['customers_full_name'] ?? $appuntamento['customers_name'] . ' ' . $appuntamento['customers_last_name'];
                                                                    echo (strlen($customer_name) > 25) ? "{$appuntamento['appuntamenti_ora_inizio']} - {$appuntamento['appuntamenti_ora_fine']} " . substr($customer_name, 0, 25) . '...' : "{$appuntamento['appuntamenti_ora_inizio']} - {$appuntamento['appuntamenti_ora_fine']} " . $customer_name;
                                                                    ?>
                                            </a>
                                            <?php endif; ?>


                                            <?php if (!empty($appuntamento['appuntamenti_note'])): ?>
                                            <p title="<?php echo strip_tags($appuntamento['appuntamenti_note']); ?>" data-toggle="tooltip" data-placement="bottom" style="margin-bottom: 5px; font-weight: 600; font-size: 12px;">
                                                <?php echo strlen($appuntamento['appuntamenti_note']) > 31 ? substr($appuntamento['appuntamenti_note'], 0, 30) . '...' : $appuntamento['appuntamenti_note']; ?>
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
                                    <?php foreach ((array) $appuntamento['appuntamenti_persone'] as $id => $persona):
                                                        $expl = explode(' ', $persona); ?>
                                    <div class="personasmall bg-primary" data-users_id="<?php echo $id; ?>" data-toggle="tooltip" data-placement="top" title="<?php echo $persona; ?>">
                                        <div class="text-center"><?php echo substr($expl[0], 0, 1); ?>.<?php echo substr($expl[1], 0, 1); ?>.</div>
                                    </div>
                                    <?php endforeach; ?>
                                    <?php foreach ((array) $appuntamento['appuntamenti_automezzi'] as $id => $automezzo): ?>
                                    <div class="automezzosmall bg-automezzo" data-automezzi_id="<?php echo $id; ?>" data-toggle="tooltip" data-placement="top" title="<?php echo $automezzo; ?>">
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
const plannerMode = <?php echo $planner_squadre_mode ?>;
</script>


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
            toast('', 'success', 'Appuntamento aggiornato', 'toastr', false);
        }
    });
}
var aggiungiMezzoAppuntamento = function(id, mezzo_id, ui) {
    $.ajax({
        method: 'get',
        url: base_url + "planner-squadre/planner/aggiungiMezzoAppuntamento/" + id + "/" + mezzo_id,

        success: function(ajax_response) {
            console.log('Salvato!');
            toast('', 'success', 'Mezzo aggiunto', 'toastr', false);
            //ui.item.remove();
            // così lo rimuove da dove l'ho tolto, se sono in un'altra card
            // se invece lo sto trascinando dal container principale lo rimuove subito
        }
    });
};
var aggiungiPersonaAppuntamento = function(id, persona_id) {
    $.ajax({
        method: 'get',
        url: base_url + "planner-squadre/planner/aggiungiPersonaAppuntamento/" + id + "/" + persona_id,

        success: function(ajax_response) {
            console.log('Salvato!');
            toast('', 'success', 'Operatore aggiunto', 'toastr', false);
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
            toast('', 'success', 'Mezzo rimosso', 'toastr', false);
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
            toast('', 'success', 'Persona rimossa', 'toastr', false);
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

function handleUserToggle() {
    $(".toggleUsers").on("click", function() {
        const toggler = $(this);
        const togglerContainer = toggler.parent();
        const target = togglerContainer.siblings(".container-persone");

        target.toggleClass("container-persone_visible");

        toggler.text(target.hasClass("container-persone_visible") ? "Mostra di meno" : "Mostra tutti");
    });
}



$(function() {
    $(".content-header.page-title").hide();

    /* if (plannerMode) {
        initializeDragAndDrop();
    } else {
        initializeClickMode();
    } */

    handleUserToggle();

    $(".btn-showall").on("click", function() {
        var this_btn = $(this);
        var impiegati = $("#impiegati");

        if (impiegati.is(":visible")) {
            impiegati.css("display", "none");
        } else {
            impiegati.css("display", "flex");
        }
    });

    //$(".js_open_selection, .js_card_clicked").on("click", function(event) {
    $(".js_open_selection").on("click", function(event) {
        // Se il click non è su una persona o automezzo, apri la sidebar
        selectedPersona = null;
        selectedMezzo = null;

        if (!$(event.target).closest('.persona, .automezzo').length) {
            $(".fixed_container").css({
                left: '0px', // 20px di margine
                top: '154px', // Posiziona verticalmente allineato alla tabella
                right: "auto", // Rimuovi il posizionamento a destra
                position: 'absolute' // Assicurati che sia posizionato rispetto al documento
            });

            $(".fixed_container").show(500, "easeInOutQuad");
        }
    });

    $(".close_persone_mezzi").on("click", function() {
        selectedPersona = null;
        selectedMezzo = null;
        $(".fixed_container").hide(500, "easeInOutQuad");
    });

    $(".toggle_fullscreen").on("click", function() {
        var elem = document.documentElement;

        if (!document.fullscreenElement) {
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    });



    const plannerMode = <?php echo $planner_squadre_mode; ?>;
    var selectedPersona = null;
    var selectedMezzo = null;

    function initializePlanner() {
        if (plannerMode) {
            initializeDragAndDrop();
        } else {
            initializeClickMode();
        }
    }

    function initializeDragAndDrop() {
        $('.container-persone').sortable({
            connectWith: '.box-persone',
            items: '.persona',
            opacity: 0.7,
            forceHelperSize: true,
            placeholder: 'portlet-sortable-placeholder round-all',
            forcePlaceholderSize: true,
            tolerance: "pointer",
            cancel: ".avatar-rimanenti",
        }).on('sortupdate', function(event, ui) {
            if (this === ui.item.parent().parent()[0]) {
                var rigaPrecedente = ui.item.data('previous_riga');
                var rigaId = ui.item.closest('tr').data('riga');
                ui.item.data('previous_riga', rigaId);
                var personaId = ui.item.data('users_id');
                var appuntamentoId = ui.item.data('appuntamento_id');

                rimuoviPersonaAppuntamento(appuntamentoId, personaId);
                //ui.item.remove(); // Remove the item from the previous card
            }
        });

        $('.container-mezzi').sortable({
            connectWith: '.box-automezzi',
            items: '.automezzo',
            opacity: 0.7,
            forceHelperSize: true,
            placeholder: 'portlet-sortable-placeholder round-all',
            forcePlaceholderSize: true,
            tolerance: "pointer",
            cancel: ".avatar-rimanenti",
        }).on('sortupdate', function(event, ui) {
            if (this === ui.item.parent()[0]) {
                var rigaPrecedente = ui.item.data('previous_riga');
                var rigaId = ui.item.closest('tr').data('riga');
                ui.item.data('previous_riga', rigaId);
                var mezzoId = ui.item.data('automezzi_id');
                var appuntamentoId = ui.item.data('appuntamento_id');

                rimuoviMezzoAppuntamento(appuntamentoId, mezzoId);
                //ui.item.remove(); // Remove the item from the previous card
            }
        });

        $('.box-persone').sortable({
            connectWith: ['.box-persone', '.container-persone'],
            items: '.persona',
            opacity: 0.7,
            forceHelperSize: true,
            placeholder: 'portlet-sortable-placeholder round-all',
            forcePlaceholderSize: true,
            tolerance: "pointer",
            cancel: ".avatar-rimanenti",
        }).on('sortupdate', function(event, ui) {
            if (this === ui.item.parent()[0]) {
                var rigaId = ui.item.closest('tr').data('riga');
                var appuntamentoId = $(event.currentTarget.closest('.appuntamento')).data('appuntamento_id');
                ui.item.data('appuntamento_id', appuntamentoId);
                var personaId = ui.item.data('users_id');

                const appointmentIdPrecedente = ui.item.data('appuntamento_id');
                const appointmentIdCorrente = $(event.currentTarget.closest('.appuntamento')).data('appuntamento_id');
                //console.log(appointmentIdPrecedente, appointmentIdCorrente);

                // Aggiungo solo se sto effettivamente cambiando appuntamento
                if (appointmentIdPrecedente !== appointmentIdCorrente) {
                    aggiungiPersonaAppuntamento(appuntamentoId, personaId);
                    //ui.item.remove(); // Remove the item from the previous card
                }
            }
        });

        $('.box-automezzi').sortable({
            connectWith: ['.box-automezzi', '.container-mezzi'],
            items: '.automezzo',
            opacity: 0.7,
            forceHelperSize: true,
            placeholder: 'portlet-sortable-placeholder round-all',
            forcePlaceholderSize: true,
            tolerance: "pointer",
            cancel: ".avatar-rimanenti",
        }).on('sortupdate', function(event, ui) {
            if (this === ui.item.parent()[0]) {
                var rigaId = ui.item.closest('tr').data('riga');
                var appuntamentoId = $(event.currentTarget.closest('.appuntamento')).data('appuntamento_id');
                ui.item.data('appuntamento_id', appuntamentoId);
                var mezzoId = ui.item.data('automezzi_id');

                const appointmentIdPrecedente = ui.item.data('appuntamento_id');
                const appointmentIdCorrente = $(event.currentTarget.closest('.appuntamento')).data('appuntamento_id');
                //console.log(appointmentIdPrecedente, appointmentIdCorrente);

                // Aggiungo solo se sto effettivamente cambiando appuntamento
                //if (appointmentIdPrecedente !== appointmentIdCorrente) {
                aggiungiMezzoAppuntamento(appuntamentoId, mezzoId, ui);
                //ui.item.remove(); // Remove the item from the previous card
                //}
            }
        });

        $('.container_appuntamenti').sortable({
            connectWith: ['.container_appuntamenti'],
            items: '.appuntamento',
            opacity: 0.7,
            forceHelperSize: true,
            placeholder: 'portlet-sortable-placeholder',
            forcePlaceholderSize: true,
            tolerance: "pointer",
            delay: 500
        }).on('sortupdate', function(event, ui) {
            if (this === ui.item.parent()[0]) {
                var day = $(event.currentTarget).data('day');
                var riga = $(event.currentTarget).data('riga');
                var appuntamentoId = ui.item.data('appuntamento_id');

                aggiornaAppuntamento(appuntamentoId, day, riga);
            }
        });
    }

    function initializeClickMode() {
        // Selezione persona
        $('.js_selected_persona').on('click', function() {
            selectedPersona = handleSelection($(this), selectedPersona);
        });

        // Selezione automezzo
        $('.js_selected_automezzo').on('click', function() {
            selectedMezzo = handleSelection($(this), selectedMezzo);
        });

        // Click su card per aggiungere persona
        $('.js_card_clicked').on('click', function() {
            /* console.log(selectedPersona);
            console.log(selectedMezzo); */

            if (selectedPersona) {
                attachPersonaToAppointment($(this), selectedPersona);
                selectedPersona = null;
            }

            if (selectedMezzo) {
                attachMezzoToAppointment($(this), selectedMezzo);
                selectedMezzo = null;
            }
        });
        // Click su card per aggiungere persona o mezzo
        /* $('.js_card_clicked').on('click', function() {
                return;
                const parentAppuntamento = $(this).closest('.appuntamento');
                const appointmentId = parentAppuntamento.data('appuntamento_id');
                const rigaId = parentAppuntamento.closest('tr').data('riga');
    
                console.log(selectedPersona);
                console.log(selectedMezzo);
    
    
                if (selectedPersona) {
                    // Aggiungi la persona alla card
                    const personaId = selectedPersona.data('users_id');
    
                    aggiungiPersonaAppuntamento(appointmentId, personaId);
    
                    const clonedPersona = selectedPersona.clone(false, true);
                    clonedPersona.attr('data-appuntamento_id', appointmentId);
                    clonedPersona.attr('data-previous_riga', rigaId);
                    clonedPersona.removeClass('opacity_clicked');
    
                    $(this).find('.box-persone').append(clonedPersona);
    
                    // Deseleziona la persona
                    selectedPersona = null;
                }
    
                if (selectedMezzo) {
                    // Aggiungi il mezzo alla card
                    const mezzoId = selectedMezzo.data('automezzi_id');
    
                    aggiungiMezzoAppuntamento(appointmentId, mezzoId);
    
                    const clonedMezzo = selectedMezzo.clone(false, true);
                    clonedMezzo.attr('data-appuntamento_id', appointmentId);
                    clonedMezzo.attr('data-previous_riga', rigaId);
                    clonedMezzo.removeClass('opacity_clicked');
    
                    $(this).find('.box-automezzi').append(clonedMezzo);
    
                    // Deseleziona il mezzo
                    selectedMezzo = null;
                }
            }); */

        // Rimozione persona dall'appuntamento
        $('.js_card_clicked').on('click', '.persona', function(event) {
            //event.stopPropagation(); // Impedisce che l'evento si propaghi alla card
            handlePersonaRemoval($(this));
        });

        // Rimozione mezzo dall'appuntamento
        $('.js_card_clicked').on('click', '.automezzo', function(event) {
            //event.stopPropagation(); // Impedisce che l'evento si propaghi alla card
            handleMezzoRemoval($(this));
        });
    }

    function handleSelection(element, previousSelection) {
        if (previousSelection) {
            previousSelection.removeClass("opacity_clicked");
        }
        element.addClass("opacity_clicked");
        return element;
    }

    function attachPersonaToAppointment(card, persona) {
        var rigaId = card.closest("tr").data("riga");
        var personaId = persona.data("users_id");
        var appuntamentoId = card.closest(".appuntamento").data("appuntamento_id");

        aggiungiPersonaAppuntamento(appuntamentoId, personaId);

        var clonedPersona = persona.clone().attr({
            "data-appuntamento_id": appuntamentoId,
            "data-previous_riga": rigaId,
        }).removeClass("opacity_clicked container_single_persona");
        persona.removeClass("opacity_clicked");

        card.find(".box-persone").append(clonedPersona);
        // Rimuovo solo se sto spostando tra appuntamenti, non se sto spostando dalla sidebar
        if (!persona.hasClass("container_single_persona")) {
            persona.remove(); // Remove from original position
        }
    }

    function attachMezzoToAppointment(card, mezzo) {
        var rigaId = card.closest("tr").data("riga");
        var mezzoId = mezzo.data("automezzi_id");
        var appuntamentoId = card.closest(".appuntamento").data("appuntamento_id");

        aggiungiMezzoAppuntamento(appuntamentoId, mezzoId);

        var clonedMezzo = mezzo.clone().attr({
            "data-appuntamento_id": appuntamentoId,
            "data-previous_riga": rigaId,
        }).removeClass("opacity_clicked container_single_persona");
        mezzo.removeClass("opacity_clicked");

        card.find(".box-automezzi").append(clonedMezzo);
        // Rimuovo solo se sto spostando tra appuntamenti, non se sto spostando dalla sidebar
        if (!mezzo.hasClass("container_single_automezzo")) {
            mezzo.remove(); // Remove from original position
        }
    }

    function handlePersonaRemoval(persona) {
        //Solo se non ho una persona selezionata dalla sidebar e se non è avatar rimanenti
        if (!selectedPersona && !persona.hasClass("rimanenti")) {
            var appuntamentoId = persona.closest(".appuntamento").data("appuntamento_id");
            var personaId = persona.data("users_id");

            if (confirm("Vuoi rimuovere questa persona dall'appuntamento?")) {
                rimuoviPersonaAppuntamento(appuntamentoId, personaId, persona);
            }
        }
    }

    function handleMezzoRemoval(mezzo) {
        //Solo se non ho una persona selezionata dalla sidebar e se non è avatar rimanenti
        if (!selectedMezzo && !mezzo.hasClass("rimanenti")) {
            var appuntamentoId = mezzo.closest(".js_card_clicked").data("appuntamento_id");
            var mezzoId = mezzo.data("automezzi_id");

            if (confirm("Vuoi rimuovere questo automezzo dall'appuntamento?")) {
                rimuoviMezzoAppuntamento(appuntamentoId, mezzoId, mezzo);
            }
        }
    }

    initializePlanner();
    handleUserToggle();

    // Handle modal open/close and other UI interactions here...





    $('.js_aggiungi_cliente').on('click', function() {
        var url = $(this).data('url');
        var riga_id = $(this).closest('tr').data('riga');
        var users = getRowUsers(riga_id);
        var automezzi = getRowAutomezzi(riga_id);
        //console.log(users);
        var append_pars = '';
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