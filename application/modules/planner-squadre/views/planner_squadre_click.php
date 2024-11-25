<script src="<?php echo base_url(); ?>script/global/plugins/sortable/Sortable.min.js?v=2.2.2"></script>
<?php $this->layout->addModuleStylesheet('planner-squadre', 'main.css'); ?>
<?php $this->layout->addModuleFooterJavascript('planner-squadre', 'jquery.ui.touch-punch.min.js'); ?>


<style>
    .label i {
        font-size: 13px;
        color: #086fa3;
    }

    .info_actions a:hover {
        color: #086fa3;
    }
</style>
<?php

$numOfDays = 6;

/*
 * Input
 * ---------------------
 *  - week: numero settimana
 *  - year: anno
 */
$year = (int) $this->input->get('year') ?: date('Y');
$week = (int) $this->input->get('week') ?: date('W');
$weekMap = ['Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato', 'Domenica'];

// Scopri il range di date per la coppia settimana/anno passata in input
$dt = new DateTime;
$dt->setISODate($year, $week);
$from = $dt->format('Y-m-d');
$dt->modify("+{$numOfDays} days");
$to = $dt->format('Y-m-d');

// Trova il numero di settimane dell'anno corrente
$totalWeeksInYear = (new DateTime())->setISODate($year, 53)->format("W") === "53" ? 53 : 52;
// Imposta prossima settimana per paginator
if ($week < $totalWeeksInYear) {
    $nextYear = $year;
    $nextWeek = $week + 1;
} else {
    // Cambia anno e reimposta alla settimana 1
    $nextYear = $year + 1;
    $nextWeek = 1;
}

// Imposta settimana precedente per paginator
if ($week > 1) {
    $prevYear = $year;
    $prevWeek = $week - 1;
} else {
    // Cambia anno e imposta l'ultima settimana dell'anno precedente
    $prevYear = $year - 1;
    $prevWeek = (new DateTime())->setISODate($prevYear, 53)->format("W") === "53" ? 53 : 52;
}

/* // Imposta prossima settimana per paginator
$dt->setISODate($year, $week + 1);
$nextYear = $dt->format('Y');
$nextWeek = $dt->format('W');

// Imposta prossima settimana per paginator
$dt->setISODate($year, $week - 1);
$prevYear = $dt->format('Y');
$prevWeek = $dt->format('W'); */

$days = [$from];
for ($i = 1; $i < $numOfDays; $i++) {
    $days[] = date('Y-m-d', strtotime(sprintf('%s +%s days', $from, $i)));
}
$days[] = $to;

$today = (new DateTime)->format('Y-m-d');

$permission = $this->datab->getPermission($this->auth->get('users_id'));


/**
 * 
 * ! Impostazioni modulo
 * 
 */
$settings_modulo = $this->apilib->searchFirst('impostazioni_planner_squadre');

$mostra_cliente = $settings_modulo['impostazioni_planner_squadre_mostra_cliente'] ?? DB_BOOL_FALSE;
$link_cliente_nuova_scheda = $settings_modulo['impostazioni_planner_squadre_cliente_nuova_scheda'] ?? DB_BOOL_FALSE;
$link_cliente_settings = $settings_modulo['impostazioni_planner_squadre_link_cliente'] ?? null;

$mostra_impianto = $settings_modulo['impostazioni_planner_squadre_mostra_impianto'] ?? DB_BOOL_FALSE;
$link_impianto_nuova_scheda = $settings_modulo['impostazioni_planner_squadre_impianto_nuova_scheda'] ?? DB_BOOL_FALSE;
$link_impianto_settings = $settings_modulo['impostazioni_planner_squadre_link_impianto'] ?? null;

$mostra_modifica = $settings_modulo['impostazioni_planner_squadre_mostra_modifica'] ?? DB_BOOL_FALSE;
$mostra_clona = $settings_modulo['impostazioni_planner_squadre_mostra_clona'] ?? DB_BOOL_FALSE;
$mostra_controlli = $settings_modulo['impostazioni_planner_squadre_mostra_controlli'] ?? DB_BOOL_FALSE;
$usa_id_appuntamento = $settings_modulo['impostazioni_planner_squadre_usa_id_appuntamento'] ?? DB_BOOL_FALSE;

$mostra_sab = $impostazioni['impostazioni_planner_squadre_mostra_sab'] ?? DB_BOOL_FALSE;
$mostra_dom = $impostazioni['impostazioni_planner_squadre_mostra_dom'] ?? DB_BOOL_FALSE;
$card_border = $settings_modulo['impostazioni_planner_squadre_bordo_appuntamento'] ?? 8;
$max_persone = $settings_modulo['impostazioni_planner_squadre_max_n_persone'] ?? 3;
$background_card = $settings_modulo['impostazioni_planner_squadre_background_card'] ?? DB_BOOL_FALSE;
$testo_bianco = $settings_modulo['impostazioni_planner_squadre_testo_bianco'] ?? DB_BOOL_FALSE;
$evidenzia_confermati = $settings_modulo['impostazioni_planner_squadre_evidenzia_confermati'] ?? DB_BOOL_FALSE;

$where_persone = $settings_modulo['impostazioni_planner_squadre_where_custom_persone'] ?? null;
$where_automezzi = $settings_modulo['impostazioni_planner_squadre_where_custom_automezzi'] ?? null;

$ordinamento_campi = $settings_modulo['impostazioni_planner_squadre_ordinamento_campi'] ?? 'appuntamenti_ora_inizio,projects_category_order'; 


$utenti_permessi = $this->apilib->search('rel_impostazioni_planner_squadre_users');
if (!empty($utenti_permessi)) {
    $userIds = array_column($utenti_permessi, 'users_id');
    $userIdsString = implode(',', $userIds);
    $query = "users_id IN ($userIdsString)";

    $where_persone[] = $query;
}


/**
 * 
 * ! Filtro a monte gli appuntamenti tramite i parametri GET
 * 
 */
// Recupera i parametri GET
$where_get = $this->input->get() ?? [];
$appuntamenti_tipologia = $this->input->get('appuntamenti_tipologia'); // Per IN clause
$appuntamenti_comparison = $this->input->get('appuntamenti_comparison'); // Per comparison (es: ne)
// Rimuovi year e week dai parametri di filtro
unset($where_get['year'], $where_get['week']);

// Costruisci la condizione WHERE dinamica
$_where_appuntamenti = isset($where_appuntamenti) && !empty($where_appuntamenti) ? (is_array($where_appuntamenti) ? $where_appuntamenti : [base64_decode($where_appuntamenti)]) : [];

// Gestione della clausola IN/NOT IN
if (!empty($appuntamenti_tipologia)) {
    if (is_array($appuntamenti_tipologia)) {
        // Converte l'array in una stringa di valori separati da virgole
        $appuntamenti_tipologia_string = implode(',', array_map('intval', $appuntamenti_tipologia));

        if ($appuntamenti_comparison === 'ne') {
            // NOT IN clause
            $_where_appuntamenti[] = "appuntamenti_tipologia NOT IN ($appuntamenti_tipologia_string)";
        } else {
            // IN clause
            $_where_appuntamenti[] = "appuntamenti_tipologia IN ($appuntamenti_tipologia_string)";
        }
    } else {
        // Gestione di un singolo valore
        if ($appuntamenti_comparison === 'ne') {
            // Se è un singolo valore e confronto 'ne', usa il confronto <>
            $_where_appuntamenti['appuntamenti_tipologia <>'] = (int) $appuntamenti_tipologia;
        } else {
            // Confronto semplice
            $_where_appuntamenti['appuntamenti_tipologia'] = (int) $appuntamenti_tipologia;
        }
    }
}
unset($where_get['appuntamenti_comparison']);

// Eventuale WHERE caricato dalla view
/* if(!empty($where_appuntamenti)) {
	$_where_appuntamenti[] = base64_decode($where_appuntamenti);
} */


// Se hai altre condizioni nel WHERE, gestiscile come stringhe se necessarie
$where_custom = implode(' AND ', $_where_appuntamenti);


$users = array_key_map_data($this->apilib->search('users'), 'users_id');
$tecnici = array_key_map_data($this->apilib->search('users', $where_persone), 'users_id');
$automezzi = array_key_map_data($this->apilib->search('automezzi', $where_automezzi), 'automezzi_id');

$_appuntamenti = $this->apilib->search('appuntamenti', [
    'appuntamenti_giorno >= ' => $days[0],
    'appuntamenti_giorno <= ' => $days[$numOfDays],
    $where_custom,
], 0, null, 'appuntamenti_ora_inizio ASC, appuntamenti_ora_fine ASC');

//dump($_appuntamenti);

// Controllo rapportini agganciati ad appuntamenti
foreach ($_appuntamenti as $key => $appuntamento) {
    $_appuntamenti[$key]['appuntamenti_rapportino'] = DB_BOOL_FALSE;

    if($this->datab->module_installed('rapportini') && !empty($_appuntamenti)) {
        
        $rapportino = $this->apilib->searchFirst('rapportini', [
            'rapportini_appuntamento_id' => $appuntamento['appuntamenti_id']
        ]);
        if(!empty($rapportino)) {
            $_appuntamenti[$key]['appuntamenti_rapportino'] = DB_BOOL_TRUE;
        }
    }
}

// Definiamo la stringa di ordinamento (esempio: può essere caricata dalle impostazioni)
$sort_fields = $ordinamento_campi;

// Funzione di ordinamento personalizzata per `_appuntamenti` con priorità configurabile
usort($_appuntamenti, function ($a, $b) use ($sort_fields) {
    // Divide la stringa dei campi in un array
    $fields = explode(',', $sort_fields);
    
    // Per ogni campo nella sequenza di ordinamento
    foreach ($fields as $field) {
        $field = trim($field);
        
        // Caso speciale solo per appuntamenti_persone che è un array
        if (is_array($a[$field])) {
            //debug(reset($a[$field]),true);
            $valueA = reset($a[$field]) ?: '';
            $valueB = reset($b[$field]) ?: '';
        } else {
            $valueA = $a[$field] ?? '';
            $valueB = $b[$field] ?? '';
        }
        
        // Se i valori sono diversi, ritorna il confronto
        if ($valueA !== $valueB) {
            return is_numeric($valueA) && is_numeric($valueB) 
                ? $valueA - $valueB 
                : strcmp($valueA, $valueB);
        }
    }
    
    // Se tutti i campi sono uguali, mantieni l'ordine originale
    return 0;
});


$appuntamenti = [];
$squadre = [];
$mezzi = [];

foreach ($_appuntamenti as $appuntamento) {
    $day = substr($appuntamento['appuntamenti_giorno'], 0, 10);

    /* if ($appuntamento['appuntamenti_cliente']) {
        $appuntamenti[$day][$appuntamento['appuntamenti_riga']][] = $appuntamento;
    } */
    if ($appuntamento['appuntamenti_cliente']) {
        $appuntamenti[$day][] = $appuntamento;
    }

    if ($day >= date('Y-m-d') && !array_key_exists($appuntamento['appuntamenti_riga'], $squadre)) {
        // SEZIONE PERSONE
        $appuntamento['appuntamenti_persone'] = (array) $appuntamento['appuntamenti_persone'];
        //Come squadra viene sempre presa quella "da oggi in poi". Le vecchie sono comunque storicizzate nella tabella appuntamneti
        foreach ($appuntamento['appuntamenti_persone'] as $users_id => $nomecognome) {
            if (!array_key_exists($users_id, $users)) {
                unset($appuntamento['appuntamenti_persone'][$users_id]);
            }
        }

        // SEZIONE AUTOMEZZI
        $appuntamento['appuntamenti_automezzi'] = (array) $appuntamento['appuntamenti_automezzi'];
        foreach ($appuntamento['appuntamenti_automezzi'] as $automezzo_id => $targa) {
            if (!array_key_exists($automezzo_id, $automezzi)) {
                unset($appuntamento['appuntamenti_automezzi'][$automezzo_id]);
            }
        }
    }
}

$settings_module = $this->apilib->searchFirst('appuntamenti_squadre_impostazioni');
$planner_squadre_mode = $settings_module['appuntamenti_squadre_impostazioni_usa_drag'] ?? 0;

?>


<section class="labels_container">
    <div>
        <button onclick="location.reload();" class="label_settings" data-toggle="tooltip" title="Aggiorna" data-placement="left">
            <i class="fas fa-sync"></i>
        </button>
    </div>
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
                        <a href="#">
                            <span aria-hidden="true">&larr;</span>
                            Dal <strong><?php echo (new DateTime)->setISODate($prevYear, $prevWeek)->format('d-m'); ?></strong>
                            al <strong><?php echo (new DateTime)->setISODate($prevYear, $prevWeek)->modify("+{$numOfDays} days")->format('d-m'); ?></strong>
                        </a>
                    </li>
                    <li style="font-size: 22px;">Dal <strong><?php echo (new DateTime($from))->format('d-m'); ?></strong> al <strong><?php echo (new DateTime($to))->format('d-m'); ?></strong></li>
                    <li class="next">
                        <a href="#">
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
                            // Salta il sabato e/o domenica
                            if ($weekMap[$k % 6] === 'Sabato' && !$mostra_sab) {
                                continue;
                            }
                            if ($weekMap[$k % 7] === 'Domenica' && !$mostra_dom) {
                                continue;
                            }


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
                                    <span class="day_reference"><?php echo substr($weekMap[$k % 7], 0, 3) . '&nbsp;' . dateFormat($day, 'd'); ?></span>
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
                        <tr>
                            <?php
                                foreach ($days as $k => $day):
                                    // Salta il sabato e/o domenica
                                    if ($weekMap[$k % 6] === 'Sabato' && !$mostra_sab) {
                                        continue;
                                    }
                                    if ($weekMap[$k % 7] === 'Domenica' && !$mostra_dom) {
                                        continue;
                                    }
                            ?>
                                <td class="relative container_appuntamenti" data-day="<?php echo $day; ?>" style="min-width: 250px;">
                                    <?php if (!empty($appuntamenti[$day])): ?>
                                        <?php
                                        foreach ($appuntamenti[$day] as $appuntamento):
                                            //debug($appuntamento, true);
                                            $dayBefore = date('Y-m-d', strtotime($day . ' -1 day'));
                                            $dayAfter = date('Y-m-d', strtotime($day . ' +1 day'));

                                            //Border or full background
                                            $style_bg = '';
                                            if($background_card == DB_BOOL_FALSE && !empty($appuntamento['projects_category_color'])) {
                                                $style_bg = "border-left: {$card_border}px solid {$appuntamento['projects_category_color']}";
                                            } elseif($background_card == DB_BOOL_TRUE && !empty($appuntamento['projects_category_color'])) {
                                                $style_bg = "background-color: {$appuntamento['projects_category_color']}";
                                            }

                                            $style_text = ($testo_bianco && $style_bg != '') ? '#FFFFFF' :  '#000000';
                                            ?>
                                            <div class="selected_customer appuntamento" data-appuntamento_id="<?php echo $appuntamento['appuntamenti_id']; ?>" data-persone="<?php echo !empty($appuntamento['appuntamenti_persone']) ? implode(',', array_keys($appuntamento['appuntamenti_persone'])) : ''; ?>" data-automezzi="<?php echo !empty($appuntamento['appuntamenti_automezzi']) ? implode(',', array_keys($appuntamento['appuntamenti_automezzi'])) : '0'; ?>" style="<?php echo $style_bg; ?>">
                                                <div class="selected_customer_customer">
                                                    <div class="js_card_clicked selected_customer_composizione_squadra">
                                                        <!-- Squadra - persone -->
                                                        <div class="box-persone ui-sortable-handle">
                                                            <?php
                                                            if (!empty($appuntamento['appuntamenti_persone'])):
                                                                $total_persons = count($appuntamento['appuntamenti_persone']);
                                                                $j = 0; // Iniziamo da 0 per contare correttamente le persone mostrate

                                                                foreach ($appuntamento['appuntamenti_persone'] as $users_id => $name):
                                                                    if ($j < $max_persone): // Mostra solo fino al massimo consentito
                                                                        $avatar_src = !empty($users[$users_id]['users_avatar'])
                                                                            ? base_url("uploads/" . $users[$users_id]['users_avatar'])
                                                                            : base_url("images/user.png");

                                                                        $user_name = $users[$users_id]['users_first_name'] . " " . $users[$users_id]['users_last_name'];
                                                                        ?>
                                                                        <div class="persona bg-primary ui-sortable-handle" data-users_id="<?php echo $users_id; ?>" style="opacity: 1;" data-appuntamento_id="<?php echo $appuntamento['appuntamenti_id']; ?>">
                                                                            <img class="avatar" src="<?php echo $avatar_src; ?>" <?php echo $planner_squadre_mode == 0 ? 'draggable="false"' : ''; ?> title="<?php echo $user_name; ?>" data-toggle="tooltip" data-placement="right" />
                                                                        </div>
                                                                        <?php
                                                                        $j++; // Incrementa solo quando la persona viene mostrata
                                                                    endif;
                                                                endforeach;

                                                                // Mostra il +X solo se ci sono più persone del massimo consentito
                                                                if ($total_persons > $max_persone):
                                                                    ?>
                                                                    <div class="persona rimanenti avatar-rimanenti">
                                                                        +<?php echo ($total_persons - $max_persone); ?>
                                                                    </div>
                                                                    <?php
                                                                endif;
                                                            endif;
                                                            ?>
                                                        </div>

                                                        <!-- Squadra - mezzi -->
                                                        <div class="box-automezzi ui-sortable-handle">
                                                            <?php if (!empty($appuntamento['appuntamenti_automezzi'])): ?>
                                                                <?php foreach ($appuntamento['appuntamenti_automezzi'] as $mezzo_id => $mezzo): ?>
                                                                    <div class="automezzo bg-automezzo ui-sortable-handle" data-automezzi_id="<?php echo $mezzo_id; ?>" style="opacity: 1;" data-appuntamento_id="<?php echo $appuntamento['appuntamenti_id']; ?>">
                                                                        <div class="text-center"><?php echo $automezzi[$mezzo_id]['automezzi_modello']; ?></div>
                                                                        <div class="text-center"><?php echo $automezzi[$mezzo_id]['automezzi_targa']; ?></div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <div class="info">
                                                        <strong>
                                                            <?php
                                                            if (!empty($appuntamento['appuntamenti_impianto']) && $mostra_impianto == DB_BOOL_TRUE):
                                                                $detail_id = ($usa_id_appuntamento == DB_BOOL_TRUE) ? $appuntamento['appuntamenti_id'] : $appuntamento['appuntamenti_impianto'];
                                                                $link_impianto = !empty($link_impianto_settings) ? base_url($link_impianto_settings . $detail_id) : '#';
                                                            ?>
                                                                <a <?php echo (!empty($link_impianto_nuova_scheda) && $link_impianto_nuova_scheda == DB_BOOL_TRUE) ? 'target="_blank"' : ''; ?> href="<?php echo $link_impianto; ?>" class="text-black card_link card_link_impianto <?php echo (!empty($link_impianto_settings) && $link_impianto_nuova_scheda == DB_BOOL_FALSE) ? 'js_open_modal' : '';?>" style='<?php echo "color: {$style_text}!important"; ?>'>
                                                                <?php
                                                                    $appuntamento_codice = !empty($appuntamento['appuntamenti_codice']) ? "{$appuntamento['appuntamenti_codice']} - " : '';
                                                                    echo "{$appuntamento_codice}{$appuntamento['projects_name']}";
                                                                ?>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php
                                                            $link_cliente = '#';
                                                            if (!empty($appuntamento['appuntamenti_cliente']) && $mostra_cliente == DB_BOOL_TRUE):
                                                                $detail_id = ($usa_id_appuntamento == DB_BOOL_TRUE) ? $appuntamento['appuntamenti_id'] : $appuntamento['appuntamenti_cliente'];
                                                                $link_cliente = !empty($link_cliente_settings) ? base_url($link_cliente_settings . $detail_id) : '#';
                                                            ?>
                                                                <a <?php echo (!empty($link_cliente_settings) && $link_cliente_nuova_scheda == DB_BOOL_TRUE) ? 'target="_blank"' : ''; ?> href="<?php echo $link_cliente; ?>" class="text-black card_link card_link_customer <?php echo (!empty($link_cliente_settings) && $link_cliente_nuova_scheda == DB_BOOL_FALSE) ? 'js_open_modal' : '';?>" style='<?php echo "color: {$style_text}!important"; ?>'>
                                                                    <?php
                                                                    $customer_name = $appuntamento['customers_full_name'] ?? $appuntamento['customers_name'] . ' ' . $appuntamento['customers_last_name'];
                                                                    echo (strlen($customer_name) > 25) ? "<span>{$appuntamento['appuntamenti_ora_inizio']} - {$appuntamento['appuntamenti_ora_fine']} " . substr($customer_name, 0, 25) . '...</span>' : "<span>{$appuntamento['appuntamenti_ora_inizio']} - {$appuntamento['appuntamenti_ora_fine']} " . $customer_name . "</span>";

                                                                    if($evidenzia_confermati == DB_BOOL_TRUE && $appuntamento['appuntamenti_da_confermare'] == DB_BOOL_FALSE) {
                                                                        echo "<span class='bg-green appuntamento_confermato' data-toggle='tooltip' data-placement='top' title='Confermato'></span>";
                                                                    }
                                                                    ?>
                                                                </a>
                                                            <?php endif; ?>

                                                            <?php if (!empty($appuntamento['appuntamenti_note']) && strlen($appuntamento['appuntamenti_note']) < 20): ?>
                                                                <p title="<?php echo strip_tags($appuntamento['appuntamenti_note']); ?>" data-toggle="tooltip" data-placement="bottom" style="margin-bottom: 5px; font-weight: 600; font-size: 12px;">
                                                                    <?php echo strlen($appuntamento['appuntamenti_note']) > 31 ? substr($appuntamento['appuntamenti_note'], 0, 30) . '...' : $appuntamento['appuntamenti_note']; ?>
                                                                </p>
                                                            <?php endif; ?>
                                                        </strong>

                                                        <div class="info_actions">
                                                            <?php if($mostra_modifica == DB_BOOL_TRUE && $appuntamento['appuntamenti_rapportino'] == DB_BOOL_FALSE) : ?>
                                                            <a class="js_open_modal label" href="<?php echo base_url("get_ajax/modal_form/edit-appuntamento-cliente/{$appuntamento['appuntamenti_id']}"); ?>">
                                                                <i class="fas fa-edit" style='<?php echo "color: {$style_text}!important"; ?>'></i>
                                                            </a>
                                                            <?php endif; ?>
                                                            <?php if($mostra_controlli == DB_BOOL_TRUE) : ?>
                                                            <div class="controls">
                                                                <a href="<?php echo base_url("planner-squadre/planner/spostaGiornoAppuntamento/{$appuntamento['appuntamenti_id']}/{$dayBefore}"); ?>" class="js_sposta_appuntamento sposta_appuntamento label">
                                                                    <i class="fas fa-arrow-left" style='<?php echo "color: {$style_text}!important"; ?>'></i>
                                                                </a>
                                                                <a href="<?php echo base_url("planner-squadre/planner/spostaGiornoAppuntamento/{$appuntamento['appuntamenti_id']}/{$dayAfter}"); ?>" class="js_sposta_appuntamento sposta_appuntamento label">
                                                                    <i class="fas fa-arrow-right" style='<?php echo "color: {$style_text}!important"; ?>'></i>
                                                                </a>
                                                            </div>
                                                            <?php endif; ?>
                                                            <?php if($mostra_clona == DB_BOOL_TRUE) : ?>
                                                            <a href="<?php echo base_url('planner-squadre/planner/duplicaAppuntamento/' . $appuntamento['appuntamenti_id']); ?>" class="js_copia_appuntamento copia_appuntamento label">
                                                                <i class="fas fa-copy" style='<?php echo "color: {$style_text}!important"; ?>'></i>
                                                            </a>
                                                            <?php endif; ?>
                                                            <?php if($mostra_clona == DB_BOOL_FALSE && $mostra_modifica == DB_BOOL_FALSE && !empty($appuntamento['appuntamenti_note3'])) : ?>
                                                            <div class="appuntamento-note" style='<?php echo "color: {$style_text}!important"; ?>'>
                                                                <?php
                                                                    $max_length = 100;
                                                                    $note_text = $appuntamento['appuntamenti_note3'];
                                                                    $note_preview = (strlen($note_text) > $max_length) ? substr($note_text, 0, $max_length) . "..." : $note_text;
                                                                    echo "<span class='note-preview'>{$note_preview}</span>";
                                                                    echo "<span class='note-full' style='display: none;'>{$note_text}</span>";
                                                                ?>
                                                            </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php //endfor; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>



<script>
    var selectedPersona = null;
    var selectedMezzo = null;

    function updateUrlParams(year, week) {
        // Ottieni i parametri GET attuali dall'URL
        var currentParams = new URLSearchParams(window.location.search);
        // Imposta o sovrascrivi i parametri year e week
        currentParams.set('year', year);
        currentParams.set('week', week);
        // Ritorna il nuovo URL
        return window.location.pathname + '?' + currentParams.toString();
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Seleziona i pulsanti "Avanti" e "Indietro"
        var previousLink = document.querySelector('.previous a');
        var nextLink = document.querySelector('.next a');

        // Ottieni i valori per le settimane precedenti e successive
        var prevYear = <?php echo json_encode($prevYear); ?>;
        var prevWeek = <?php echo json_encode($prevWeek); ?>;
        var nextYear = <?php echo json_encode($nextYear); ?>;
        var nextWeek = <?php echo json_encode($nextWeek); ?>;
        // Aggiorna il link "Indietro" mantenendo i parametri GET esistenti
        previousLink.href = updateUrlParams(prevYear, prevWeek);
        // Aggiorna il link "Avanti" mantenendo i parametri GET esistenti
        nextLink.href = updateUrlParams(nextYear, nextWeek);
    });


    var plannerMode = <?php echo $planner_squadre_mode ?>;


    window.onscroll = function () {
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



    /**
     * ! UTILITIES
     */
    var aggiornaAppuntamento = function (id, day, riga = null) {
        $.ajax({
            method: 'get',
            //url: base_url + "planner-squadre/planner/aggiornaAppuntamento/" + id + "/" + day + '/' + riga,
            url: base_url + "planner-squadre/planner/aggiornaAppuntamento/" + id + "/" + day,
            success: function (ajax_response) {
                toast('', 'success', 'Appuntamento aggiornato', 'toastr', false);
            }
        });
    }

    var aggiungiMezzoAppuntamento = function (id, mezzo_id, ui) {
        $.ajax({
            method: 'get',
            url: base_url + "planner-squadre/planner/aggiungiMezzoAppuntamento/" + id + "/" + mezzo_id,
            success: function (ajax_response) {
                toast('', 'success', 'Mezzo aggiunto', 'toastr', false);
                // così lo rimuove da dove l'ho tolto, se sono in un'altra card
                // se invece lo sto trascinando dal container principale lo rimuove subito
                selectedMezzo = null;
            }
        });
    };

    var aggiungiPersonaAppuntamento = function (id, persona_id) {
        $.ajax({
            method: 'get',
            url: base_url + "planner-squadre/planner/aggiungiPersonaAppuntamento/" + id + "/" + persona_id,
            success: function (ajax_response) {
                toast('', 'success', 'Operatore aggiunto', 'toastr', false);
                // Rimuovo persona selezionata
                //ui.item.remove();
                selectedPersona = null;
            }
        });
    };

    var rimuoviMezzoAppuntamento = function (id, mezzo_id, dom_automezzo) {
        $.ajax({
            method: 'get',
            url: base_url + "planner-squadre/planner/rimuoviMezzoAppuntamento/" + id + "/" + mezzo_id,
            success: function (ajax_response) {
                dom_automezzo && dom_automezzo.remove();
                toast('', 'success', 'Mezzo rimosso', 'toastr', false);
            }
        });
    };

    var rimuoviPersonaAppuntamento = function (id, persona_id, dom_persona) {
        $.ajax({
            method: 'get',
            url: base_url + "planner-squadre/planner/rimuoviPersonaAppuntamento/" + id + "/" + persona_id,
            success: function (ajax_response) {
                dom_persona && dom_persona.remove();
                toast('', 'success', 'Persona rimossa', 'toastr', false);
            }
        });
    };

    function handleUserToggle() {
        $(".toggleUsers").on("click", function () {
            const toggler = $(this);
            const togglerContainer = toggler.parent();
            const target = togglerContainer.siblings(".container-persone");

            target.toggleClass("container-persone_visible");
            toggler.text(target.hasClass("container-persone_visible") ? "Mostra di meno" : "Mostra tutti");
        });
    }



    // Gestione corretta degli eventi touchstart, touchend, e click per i dispositivi touch per le actions appuntamento.
    $(document).ready(function() {
        $(document).on('click touchend touchstart', '.info_actions a', function(event) {
            console.log($(this));
            
            if ($(this).hasClass('js_open_modal')) {
                // Lascia che il normale comportamento della modale avvenga
                return; // Non blocca la modale
            } else {
                event.preventDefault(); // Previene il comportamento predefinito per i link normali
                window.location.href = $(this).attr('href'); // Esegue il reindirizzamento solo per i link normali
            }
        });
    });


    $(function () {

        $('.appuntamento-note').on('click', function() {
            const preview = $(this).find('.note-preview');
            const fullText = $(this).find('.note-full');

            if (fullText.is(':visible')) {
                preview.show();
                fullText.hide();
            } else {
                preview.hide();
                fullText.show();
            }
        });


        $(".content-header.page-title").hide();
        $('a.sidebar-toggle:visible').click();

        handleUserToggle();

        $(".btn-showall").on("click", function () {
            var this_btn = $(this);
            var impiegati = $("#impiegati");

            if (impiegati.is(":visible")) {
                impiegati.css("display", "none");
            } else {
                impiegati.css("display", "flex");
            }
        });

        // Se il click non è su una persona o automezzo, apri la sidebar
        $(".js_open_selection").on("click", function (event) {
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

        // Funzione per evidenziare le persone e i mezzi associati a un appuntamento
        function highlightAssociatedPersonsAndVehicles(appuntamento) {
            // Rimuove l'evidenziazione precedente
            $('.container_single_persona').removeClass('highlight');
            $('.container_single_automezzo').removeClass('highlight');

            // Ottieni gli ID delle persone e dei mezzi associati all'appuntamento
            var personeAssociati = $(appuntamento).data('persone');
            // Converte il valore in stringa per gestire correttamente sia i numeri che le stringhe
            personeAssociati = personeAssociati.toString();
            // Verifica se l'attributo data-persone esiste e contiene valori
            if (personeAssociati.includes(',')) {
                personeAssociati = personeAssociati.split(','); // Se è una stringa con più persone, esegui lo split
            } else if (personeAssociati.length > 0) {
                personeAssociati = [personeAssociati]; // Se è una singola persona, converti in array
            } else {
                personeAssociati = []; // Se non è un valore valido, imposta un array vuoto
            }            

            var mezziAssociati = $(appuntamento).find('.automezzo').map(function () {
                return $(this).data('automezzi_id');
            }).get();

            // Evidenzia le persone corrispondenti nella sidebar
            personeAssociati.forEach(function (users_id) {            
                $('.container_single_persona[data-users_id="' + users_id + '"]').addClass('highlight');
            });

            // Evidenzia i mezzi corrispondenti nella sidebar
            mezziAssociati.forEach(function (automezzi_id) {
                $('.container_single_automezzo[data-automezzi_id="' + automezzi_id + '"]').addClass('highlight');
            });
        }


        // Funzione per aprire e posizionare la sidebar accanto all'appuntamento cliccato
        var activeAppointment = null;
        var sidebar = $('.fixed_container'); // Usa la sidebar globale
        var appointmentTopOffset = 0; // Variabile per memorizzare l'offset dell'appuntamento in alto
        var sidebarLeftPosition = 0; // Variabile per memorizzare la posizione orizzontale della sidebar
        var touchTimeout;
        var isDragging = false; // Variabile per tracciare il drag
        var startX, startY; // Variabili per memorizzare la posizione iniziale del tocco/click
        var threshold = 10; // Soglia di movimento in pixel per considerare un drag

        // Gestisci l'inizio del tocco o click
        $('.js_card_clicked').on('touchstart mousedown', function (event) {
            // Controlla se il click è avvenuto su una persona o su un automezzo
            if ($(event.target).closest('.persona, .automezzo').length) {
                return; // Evita di aprire la sidebar se il click è su una persona o su un automezzo
            }

            isDragging = false; // Inizialmente, non siamo in modalità drag

            // Memorizza la posizione iniziale del tocco/click
            var touch = event.type === 'touchstart' ? event.originalEvent.touches[0] : event;
            startX = touch.pageX;
            startY = touch.pageY;

            // Se è un evento touchstart, previeni il click successivo
            if (event.type === 'touchstart') {
                clearTimeout(touchTimeout);
                touchTimeout = setTimeout(function () {
                    touchTimeout = null; // Resetta dopo un breve periodo per evitare conflitti con click
                }, 500); // Delay to avoid double triggering
            }
        });

        // Rileva il movimento (drag) durante il tocco o click
        $('.js_card_clicked').on('touchmove mousemove', function (event) {
            var touch = event.type === 'touchmove' ? event.originalEvent.touches[0] : event;
            var diffX = Math.abs(touch.pageX - startX);
            var diffY = Math.abs(touch.pageY - startY);

            // Se il movimento supera la soglia, consideriamo l'azione come un drag
            if (diffX > threshold || diffY > threshold) {
                isDragging = true;
            }
        });

        // Gestisci il rilascio del tocco o click
        $('.js_card_clicked').on('touchend mouseup', function (event) {
            // Controlla se il click è avvenuto su una persona o su un automezzo
            if ($(event.target).closest('.persona, .automezzo').length) {
                return; // Evita di aprire la sidebar se il click è su una persona o su un automezzo
            }
            // Se non c'è stato un drag, allora gestisci l'evento come un normale click/touch
            if (!isDragging) {
                // Verifica che l'evento non sia un click dopo un touchstart
                if (event.type === 'touchend') {
                    handleAppointmentClick(event, $(event.currentTarget).closest('.appuntamento'));
                }

                if (event.type === 'mouseup' && !touchTimeout) {
                    handleAppointmentClick(event, $(event.currentTarget).closest('.appuntamento'));
                }
            }
        });

        function handleAppointmentClick(event, appointmentRef) {
            var target = $(event.target);
            // Se il click è su un link, non aprire la sidebar
            if (target.is('a') || target.closest('a').length) {
                event.stopPropagation();
                return;
            }

            //activeAppointment = $(this).closest('.appuntamento'); // Memorizza l'appuntamento attivo
            activeAppointment = appointmentRef;
            // Ottieni la posizione dell'appuntamento rispetto al viewport
            var rect = activeAppointment[0].getBoundingClientRect();
            var appointmentTop = rect.top; // Posizione dell'appuntamento rispetto al viewport
            var appointmentLeft = rect.left;
            // Ottieni la larghezza dell'appuntamento e della sidebar
            var appointmentWidth = activeAppointment.outerWidth();
            var sidebarWidth = sidebar.outerWidth();
            var windowWidth = $(window).width();
            // Definisci il margine desiderato
            var margin = 20;

            // Calcolo della posizione orizzontale della sidebar
            if (windowWidth - (appointmentLeft + appointmentWidth + margin) >= sidebarWidth) {
                // Posiziona la sidebar a destra dell'appuntamento
                sidebarLeftPosition = appointmentLeft + appointmentWidth + margin;
            } else {
                // Posiziona la sidebar a sinistra dell'appuntamento
                sidebarLeftPosition = appointmentLeft - sidebarWidth - margin;
            }
            // Memorizza la posizione verticale iniziale rispetto al documento
            appointmentTopOffset = rect.top + $(window).scrollTop();

            // Posiziona la sidebar inizialmente con `position: fixed`
            sidebar.css({
                top: appointmentTop + 'px',
                left: sidebarLeftPosition + 'px',
                position: 'fixed'
            }).removeClass('hidden');
            // Mostra la sidebar
            sidebar.show(500, "easeInOutQuad");

            // Evidenzia le persone e i mezzi associati all'appuntamento cliccato            
            highlightAssociatedPersonsAndVehicles(activeAppointment);
        }

        // Listener per aggiornare la posizione della sidebar durante lo scroll
        $(window).on('scroll', function () {
            if (activeAppointment) {
                // Calcola la nuova posizione verticale basata sullo scroll
                var scrollTop = $(window).scrollTop();
                var newSidebarTop = appointmentTopOffset - scrollTop;

                // Aggiorna la posizione della sidebar in base allo scroll
                sidebar.css({
                    top: newSidebarTop + 'px', // Mantiene la stessa posizione orizzontale
                    left: sidebarLeftPosition + 'px',
                    position: 'fixed'
                });
            }
        });



        /**
         * ! 29/10/2024 - Commentato in quando non permette di aprire cliente e commessa in modale
         */
        // Blocca la propagazione del click sugli elementi con link
        /* $('[data-stop-propagation]').on('click', function (event) {
            event.stopPropagation();
        }); */

        // Chiudi la sidebar quando si clicca sulla X e rimuovi l'evidenziazione oltre che l'appuntamento corrente selezionato
        $(".close_persone_mezzi").on("click", function () {
            selectedPersona = null;
            selectedMezzo = null;
            activeAppointment = null; // rimuove appuntamento corrente
            $(".fixed_container").hide(500, "easeInOutQuad");
            // Rimuove l'evidenziazione quando si chiude la sidebar
            $('.container_single_persona').removeClass('highlight');
            $('.container_single_automezzo').removeClass('highlight');
        });

        /***************************************************************
         * 
         * ! FULLSCREEN
         * 
         ****************************************************************/
        $(".toggle_fullscreen").on("click", function () {
            var elem = document.documentElement;
            // Add fullscreen
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
                // Remove fullscreen
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

        // Mostra o nasconde elementi (navbar, footer, sidebar) in base al fullscreen
        function handleFullscreenChange() {
            if (document.fullscreenElement) {
                // Chiude la sidebar se necessario
                $('nav.navbar, footer.main-footer').hide();
                $('a.sidebar-toggle:visible').click();
            } else {
                // Riapre la sidebar se necessario
                $('nav.navbar, footer.main-footer').show();
                $('a.sidebar-toggle:visible').click();
            }
        }

        document.addEventListener("fullscreenchange", handleFullscreenChange);
        document.addEventListener("webkitfullscreenchange", handleFullscreenChange);
        document.addEventListener("mozfullscreenchange", handleFullscreenChange);
        document.addEventListener("MSFullscreenChange", handleFullscreenChange);





        /***************************************************
         *
         * ! GESTIONE PLANNER
         * 
         ****************************************************/
        function initializePlanner() {
            initializeDragAndDrop();
            initializeClickMode();
        }

        // Aggiungi il supporto per i Pointer Events solo per pointerup
        if (window.PointerEvent) {
            $('.container_appuntamenti').on('pointerup', function (e) {
                const mouseEvent = new MouseEvent('mouseup', {
                    bubbles: true,
                    cancelable: true,
                    view: window
                });
                //console.log(e);
                e.target.dispatchEvent(mouseEvent);
            });
        }

        // Funzione per gestire il drag & drop solo per gli appuntamenti
        function initializeDragAndDrop() {
            // Mantieni il drag & drop solo per gli appuntamenti
            $('.container_appuntamenti').sortable({
                connectWith: ['.container_appuntamenti'],
                items: '.appuntamento',
                opacity: 0.7,
                forceHelperSize: true,
                placeholder: 'portlet-sortable-placeholder',
                forcePlaceholderSize: true,
                tolerance: "pointer",
                delayOnTouchOnly: true,
                delay: 500,
                // Aggiungi la gestione degli eventi touch
                touchStartThreshold: 15, // Riduce il ritardo per i dispositivi touch, px prima di cancellare evento
                fallbackTolerance: 5, // Tolleranza per il fallback del mouse in caso di problemi di drag su touch
                fallbackOnBody: true, // Usa l'elemento body per il fallback
                fallbackClass: 'sortable-fallback', // Aggiungi una classe per lo stile durante il drag fallback
                cancel: ".info_actions a", // Esclude i link dentro .info_actions dal drag
            }).on('sortupdate', function (event, ui) {
                if (this === ui.item.parent()[0]) {
                    var day = $(event.currentTarget).data('day');
                    //var riga = $(event.currentTarget).data('riga');
                    var appuntamentoId = ui.item.data('appuntamento_id');
                    //aggiornaAppuntamento(appuntamentoId, day, riga);
                    aggiornaAppuntamento(appuntamentoId, day);
                }
            });
        }

        function initializeClickMode() {
            // Selezione persona
            $('.js_selected_persona').on('click', function () {
                selectedPersona = handleSelection($(this), selectedPersona, 'persona');
                console.log(selectedPersona);
            });

            // Selezione automezzo
            $('.js_selected_automezzo').on('click', function () {
                selectedMezzo = handleSelection($(this), selectedMezzo, 'automezzo');
            });

            // Click su card per aggiungere persona
            $('.js_card_clicked').on('click', function () {
                if (selectedPersona) {
                    attachPersonaToAppointment($(this), selectedPersona);
                    //selectedPersona = null;
                }

                if (selectedMezzo) {
                    attachMezzoToAppointment($(this), selectedMezzo);
                    //selectedMezzo = null;
                }
            });

            // Rimozione persona dall'appuntamento
            $(document).on('click touchend', '.selected_customer_composizione_squadra .persona', function (event) {
                event.stopPropagation();

                if (!selectedPersona && !$(this).hasClass("rimanenti")) {
                    var appuntamentoId = $(this).closest('.appuntamento').data('appuntamento_id');
                    var personaId = $(this).data('users_id');

                    var personaNome = $(this).find('img').attr('data-original-title');
                    if (confirm("Vuoi rimuovere " + personaNome + " dall'appuntamento?")) {
                        rimuoviPersonaAppuntamento(appuntamentoId, personaId, $(this));

                        // Aggiorno l'attributo data-persone della card
                        var card = $(this).closest('.appuntamento');
                        var personeId = card.data('persone').toString();
                        var personeArray = personeId.split(",");
                        // Rimuovo l'ID della persona dall'array
                        var index = personeArray.indexOf(personaId.toString());
                        if (index !== -1) {
                            personeArray.splice(index, 1);
                        }
                        // Aggiorno l'attributo data-persone con la nuova lista
                        card.data('persone', personeArray.join(","));
                    } else {
                        // Impedisci ulteriori eventi e la propagazione se l'utente clicca "Annulla"
                        event.stopImmediatePropagation();
                        return false; // Blocca l'eventuale apertura della sidebar
                    }
                }
            });

            // Rimozione mezzo dall'appuntamento
            $(document).on('click touchend', '.selected_customer_composizione_squadra .automezzo', function (event) {
                event.stopPropagation();

                if (!selectedMezzo && !$(this).hasClass("rimanenti")) {
                    var appuntamentoId = $(this).closest('.appuntamento').data('appuntamento_id');
                    var mezzoId = $(this).data('automezzi_id');

                    if (confirm("Vuoi rimuovere questo automezzo dall'appuntamento?")) {
                        rimuoviMezzoAppuntamento(appuntamentoId, mezzoId, $(this));

                        // Aggiorno l'attributo data-automezzi della card
                        var card = $(this).closest('.appuntamento');
                        var automezziId = card.data('automezzi').toString();
                        var automezziArray = automezziId.split(",");
                        // Rimuovo l'ID dell'automezzo dall'array
                        var index = automezziArray.indexOf(mezzoId.toString());
                        if (index !== -1) {
                            automezziArray.splice(index, 1);
                        }
                        // Aggiorno l'attributo data-automezzi con la nuova lista
                        card.data('automezzi', personeArray.join(","));
                    } else {
                        // Impedisci ulteriori eventi e la propagazione se l'utente clicca "Annulla"
                        event.stopImmediatePropagation();
                        return false; // Blocca l'eventuale apertura della sidebar
                    }
                }
            });
        }

        function handleSelection(element, previousSelection, typeClicked) {
            console.log('handleSelection');
            console.log(activeAppointment);
            console.log(element);

            if(typeClicked === 'persona') {
                var personaId = element.data("users_id");
                var personeId = activeAppointment.closest(".appuntamento").data("persone").toString();
                console.log(personaId);
                console.log(personeId);

                // Se la persona è già nell'appuntamento, blocca l'aggiunta
                var personeArray = personeId.split(",");
                if (personeArray.includes(personaId.toString())) {
                    // Impedisce qualsiasi ulteriore evento su questo elemento
                    event.preventDefault();
                    event.stopImmediatePropagation(); 
                    alert("La persona scelta è già presente nell'appuntamento.");
                    return false; // Interrompe la selezione
                }
            } else {
                var automezzoId = element.data("automezzi_id");
                var automezziId = activeAppointment.closest(".appuntamento").data("automezzi").toString();
                console.log(automezzoId);
                console.log(automezziId);

                // Se automezzo è già nell'appuntamento, blocca l'aggiunta
                var automezziArray = automezziId.split(",");
                if (automezziArray.includes(automezzoId.toString())) {
                    // Impedisce qualsiasi ulteriore evento su questo elemento
                    event.preventDefault();
                    event.stopImmediatePropagation(); 
                    alert("L'automezzo scelto è già presente nell'appuntamento.");
                    return false; // Interrompe la selezione
                }
            }

/*             var personaId = element.data("users_id");
            var personeId = activeAppointment.closest(".appuntamento").data("persone").toString();
            console.log(personaId);
            console.log(personeId);

            // Se la persona è già nell'appuntamento, blocca l'aggiunta
            var personeArray = personeId.split(",");
            if (personeArray.includes(personaId.toString())) {
                // Impedisce qualsiasi ulteriore evento su questo elemento
                event.preventDefault();
                event.stopImmediatePropagation(); 
                alert("La persona scelta è già presente nell'appuntamento.");
                return false; // Interrompe la selezione
            } */

            if (previousSelection) {
                previousSelection.removeClass("opacity_clicked");
            }
            element.addClass("opacity_clicked");

            // Dopo la selezione, chiamiamo attachPersonaToAppointment se c'è un appuntamento attivo
            if (activeAppointment) {
                if(typeClicked === 'persona') {
                    attachPersonaToAppointment(activeAppointment, element);
                }  else {
                    attachMezzoToAppointment(activeAppointment, element);
                }
            }

            return element;
        }

        function attachPersonaToAppointment(card, persona) {
            //var rigaId = card.closest("tr").data("riga");
            var personaId = persona.data("users_id");
            var appuntamentoId = card.closest(".appuntamento").data("appuntamento_id");

            aggiungiPersonaAppuntamento(appuntamentoId, personaId);

            var clonedPersona = persona.clone().attr({
                "data-appuntamento_id": appuntamentoId,
               // "data-previous_riga": rigaId,
            }).removeClass("opacity_clicked container_single_persona");
            persona.removeClass("opacity_clicked");

            card.find(".box-persone").append(clonedPersona);

            // Aggiorno l'attributo data-persone aggiungendo la nuova persona
            var personeId = card.closest(".appuntamento").data("persone").toString();
            var personeArray = personeId.split(",");
            if (!personeArray.includes(personaId.toString())) {
                personeArray.push(personaId.toString());
            }
            // Aggiorno l'attributo data-persone con la nuova lista
            card.closest(".appuntamento").data("persone", personeArray.join(","));

            
            // Rimuovo solo se sto spostando tra appuntamenti, non se sto spostando dalla sidebar
            if (!persona.hasClass("container_single_persona")) {
                persona.remove(); // Remove from original position
            }
            selectedPersona = null;
        }

        function attachMezzoToAppointment(card, mezzo) {
            //var rigaId = card.closest("tr").data("riga");
            var mezzoId = mezzo.data("automezzi_id");
            var appuntamentoId = card.closest(".appuntamento").data("appuntamento_id");

            aggiungiMezzoAppuntamento(appuntamentoId, mezzoId);

            var clonedMezzo = mezzo.clone().attr({
                "data-appuntamento_id": appuntamentoId,
                //"data-previous_riga": rigaId,
            }).removeClass("opacity_clicked container_single_persona");
            mezzo.removeClass("opacity_clicked");

            card.find(".box-automezzi").append(clonedMezzo);

            // Aggiorno l'attributo data-automezzi aggiungendo la nuova persona
            var automezziId = card.closest(".appuntamento").data("automezzi").toString();
            var automezziArray = automezziId.split(",");
            
           /*  console.log(automezziId);
            console.log(automezziArray); */
            
            if (!automezziArray.includes(mezzoId.toString())) {
                automezziArray.push(mezzoId.toString());
            }
            /* console.log(automezziArray);
            console.log(card.closest(".appuntamento"))
            console.log(card.closest(".appuntamento").data("automezzi")); */
            
            // Aggiorno l'attributo data-automezzi con la nuova lista
            card.closest(".appuntamento").data("automezzi", automezziArray.join(","));
            //console.log(card.closest(".appuntamento").data("automezzi"));            

            // Rimuovo solo se sto spostando tra appuntamenti, non se sto spostando dalla sidebar
            if (!mezzo.hasClass("container_single_automezzo")) {
                mezzo.remove(); // Remove from original position
            }
            selectedMezzo = null;
        }

        /* function handlePersonaRemoval(persona) {
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
            } */

        initializePlanner();
        handleUserToggle();

        // Handle modal open/close and other UI interactions here...





        /* $('.js_aggiungi_cliente').on('click', function () {
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
        }); */

        //Copia appuntamento per il giorno successivo
        $('.js_copia_appuntamento').on('click', function () {
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

        $('body').on('click', '.btn-danger.js_confirm_button.js_link_ajax', function (e) {
            $('.modal').modal('toggle');
        });



        /* Show/hide more users */
        $('.toggleUsers').on('click', function () {
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