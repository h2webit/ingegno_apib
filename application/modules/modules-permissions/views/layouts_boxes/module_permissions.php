<?php
$module = $this->apilib->view('modules_permissions', $value_id);

//Check per evitare il bug quando max_input_data è minore del numero di checkbox stampati...
if (defined('LOGIN_ACTIVE_FIELD') && LOGIN_ACTIVE_FIELD) {
    if (defined('LOGIN_DELETED_FIELD') && LOGIN_DELETED_FIELD) {
        $this->db->where(LOGIN_DELETED_FIELD . " <> '" . DB_BOOL_TRUE . "'", null, false);
    }
    $users = $this->db->get_where(LOGIN_ENTITY, array(LOGIN_ACTIVE_FIELD => DB_BOOL_TRUE))->result_array();
} else {
    $users = $this->db->get(LOGIN_ENTITY)->result_array();
}

// Crea un array di mappatura layout_id => ucwords(n. cognome)
$userIds = array_key_map($users, LOGIN_ENTITY . '_id');
$ucwordsUserNames = array_map(function ($user) {
    $n = isset($user[LOGIN_NAME_FIELD]) ? $user[LOGIN_NAME_FIELD] : '';
    $s = isset($user[LOGIN_SURNAME_FIELD]) ? $user[LOGIN_SURNAME_FIELD] : '';
    return ($n && $s) ? ucwords($n[0] . '. ' . $s) : $n . ' ' . $s;
}, $users);
$usersLayouts = array_combine($userIds, $ucwordsUserNames);

// Crea un array di mappatura layout_id => ucfirst(layout_title)
//debug($module['modules_permissions_module']);
$dati['layouts'] = $this->db
    ->join('modules', 'layouts_module = modules_identifier', 'LEFT')
    ->where('layouts_module', $module['modules_permissions_module'])
    ->order_by('layouts_module, layouts_title')
    ->get('layouts')
    ->result_array();

//Fix per non prendere tutti gli utenti ma solo quelli che possono fare login
if (defined('LOGIN_ACTIVE_FIELD') && !empty(LOGIN_ACTIVE_FIELD)) {
    $this->db->where("unallowed_layouts_user IN (SELECT " . LOGIN_ENTITY . "_id FROM " . LOGIN_ENTITY . " WHERE " . LOGIN_ACTIVE_FIELD . " = '" . DB_BOOL_TRUE . "')", null, false);
}

$unalloweds = $this->db->get('unallowed_layouts')->result_array();
$dati['unallowed'] = array();

$dati['userGroupsStatus'] = $userGroupsStatus = $this->datab->getUserGroups(); // Un array dove per ogni utente ho il gruppo corrispondente
$dati['users_layout'] = [];
foreach ($usersLayouts as $userId => $userPreview) {
    if (isset($userGroupsStatus[$userId])) {
        $dati['users_layout'][$userGroupsStatus[$userId]] = ucwords($userGroupsStatus[$userId]);
    } else {
        $dati['users_layout'][$userId] = $userPreview;
    }
}

foreach ($unalloweds as $unallowedLayout) {
    $layout = $unallowedLayout['unallowed_layouts_layout'];
    $user = $unallowedLayout['unallowed_layouts_user'];

    if (isset($userGroupsStatus[$user]) && $userGroupsStatus[$user]) {
        $dati['unallowed'][$userGroupsStatus[$user]][] = $layout;
    } else {
        $dati['unallowed'][$user][] = $layout;
    }
}

uksort($dati['users_layout'], function ($k1, $k2) {
    $isGroupK1 = !is_numeric($k1);
    $isGroupK2 = !is_numeric($k2);

    if ($isGroupK1 == $isGroupK2) {
        return ($k1 < $k2) ? -1 : 1;
    } elseif ($isGroupK1) {
        return -1;
    } else {
        return 1;
    }
});
//Struttura [userId] => [chiave_non_so => id_layout]
//Costruisco un array identico, prendendo i dati dal json (mi serve per fare i vari confronti)
$json_allowed = [];
$json_module_data = json_decode($module['modules_permissions_json'], true);
$json_module = (empty($json_module_data['viewAccess']))?[]: $json_module_data['viewAccess'];

//debug($dati['layouts']);

function extra_data($layout, $sidebar_layouts)
{
    $extra="";


    if (in_array($layout['layouts_id'], $sidebar_layouts)) {
        $extra .= '<i title="Linked in sidebar" class="extra_icon fas fa-link"></i> ';
    }


    if ($layout['layouts_is_entity_detail']) {
        $extra .= '<i title="Layout Detail" class="extra_icon fas fa-eye"></i> ';
    }

    if ($layout['layouts_is_public']) {
        $extra .= '<i title="Public layout" class="extra_icon fas fa-user"></i> ';
    }


    if ($layout['layouts_settings']) {
        $extra .= '<i title="Settings layout" class="extra_icon fas fa-cogs"></i> ';
    }


    if ($layout['layouts_dashboardable']) {
        $extra .= '<i title="Dashboard" class="extra_icon fas fa-home"></i> ';
    }


    if ($layout['layouts_pdf']) {
        $extra .= '<i title="PDF layout" class="extra_icon fas fa-print"></i> ';
    }



    return $extra;
}


$sidebar = $this->db->query("SELECT menu_layout FROM menu WHERE menu_layout <> '' AND menu_layout IS NOT NULL AND menu_position = 'sidebar'")->result_array();
$sidebar_layouts = array();
foreach ($sidebar as $menu_layout) {
    $sidebar_layouts[] = $menu_layout['menu_layout'];
}
?>
<style>
    a {
        cursor:pointer!important;
    }
    .extra_icon {
        color:#666666;
    }
    .modules_permissions_buttons .btn:not(.selected) {
        background-color: gray; /* Cambia il colore di sfondo per i bottoni non selezionati */
        color: white; /* Cambia il colore del testo per i bottoni non selezionati */
    }
    .checkbox_green  {
        
        accent-color: green;
    }
   
    .checkbox_red {
        
        accent-color: red;
    }
   
    </style>


<section class="content">
    <div class="row modules_permissions_buttons">
        <div class="col-md-4">
            <button class="btn btn-success btn-block js-switch_view_permissions selected" data-what="current">Current permissions</button>
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary btn-block js-switch_view_permissions" data-what="module">Module permissions</button>
        </div>
        <div class="col-md-4">
            <button class="btn btn-danger btn-block js-switch_view_permissions" data-what="differences">Differences</button>
        </div>
    </div>
<div class="row">
        
        <div class="col-md-12">

            <div class="box box-primary">
                
                <div class="portlet-body form">
                    
                    <form id="views_form" role="form" method="post" action="<?php echo base_url('modules-permissions/modules_permissions/modules_permissions_save/'. $value_id); ?>" class="formAjax">
                        
                    <div class="form-actions fluid row">
            <div class="col-md-12">
                <div class='pull-right'>
                    <a class="btn btn-primary js_confirm_button" href="<?php echo base_url('modules-permissions/modules_permissions/modules_permissions_import/' . $value_id); ?>" data-confirm-text="Sei sicuro di voler procedere? In questo modo verranno importati i permessi dal modulo permissions, sovrascrivendo quelli correnti (la procedura cambierà solo i permessi dei gruppi, lasciando inalterati eventuali users con permessi ad hoc.">
                                    <?php e('Import'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                        <?php add_csrf(); ?>
                        <div class="form-body">
                            <div class="table-scrollable table-scrollable-borderless">
                                <table id="views-permissions-datatable" class="table table-bordered table-condensed table-hover">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <?php foreach ($dati['users_layout'] as $userID => $username):
                                                if (is_numeric($userID)) {
                                                    continue;
                                                } 
                                                ?>
                                                <th>
                                                    <label>
                                                        <input type="checkbox" data-toggle="tooltip" title="<?php e('Enable/Disable all'); ?>" class="js-toggle-all toggle" data-user="<?php echo $userID; ?>" />
                                                        <strong><?php echo(is_numeric($userID) ? '' : '<small class="text-muted fw-normal">Group</small> ') . $username; ?></strong>
                                                    </label>
                                                </th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php $module = "";?>
                                        <?php foreach ($dati['layouts'] as $layout) : ?>

                                            

                                            <!-- Single layout row -->
                                            <tr data-module="<?php echo $layout['layouts_module'];?>" class="tr-module" <?php if (in_array($layout['layouts_id'], $sidebar_layouts)): ?>data-sidebar="true"<?php endif;?>>
                                                <th>
                                                    <label class="permissions-layout-label" title="<?php echo $layout['layouts_title']; ?> ">
                                                        <input type="checkbox" data-toggle="tooltip" title="<?php e('Enable/Disable all'); ?>" class="js-toggle-all-horizontal toggle" data-layout="<?php echo $layout['layouts_id']; ?>" />
                                                        <small class="text-muted"><?php echo $layout['layouts_id']; ?> - </small> <a target="_blank" href="<?php echo base_url(); ?>main/layout/<?php echo $layout['layouts_id']; ?>"><?php echo $layout['layouts_title']; ?></a> <?php echo extra_data($layout, $sidebar_layouts);?> <small><?php echo $layout['layouts_identifier']; ?></small>
                                                    </label>
                                                </th>
                                                <?php foreach ($dati['users_layout'] as $userID => $username) :
                                                        if (is_numeric($userID)) {
                                                            continue;
                                                        }

                                                    $checked_current = (!isset($dati['unallowed'][$userID]) || !in_array($layout['layouts_id'], $dati['unallowed'][$userID]));
                                                    $checked_module = (in_array($layout['layouts_module_key'], array_keys($json_module)) && in_array($username, $json_module[$layout['layouts_module_key']]));
                                                    $difference = ($checked_current != $checked_module);
                                                    if ($difference) {
                                                        if (!$checked_current) {
                                                            $difference_green_red = 'green';
                                                        } else {
                                                            $difference_green_red = 'red';
                                                        }
                                                    } else {
                                                        $difference_green_red = '';
                                                    }
                                                    
                                                        ?>
                                                    <td>
                                                        <label>
                                                            <input type="checkbox" class="js-toggle-view toggle js-checkbox_module_permission <?php echo "checkbox_{$difference_green_red}"; ?>" data-single_checkbox="1" data-module="<?php echo $layout['layouts_module'];?>" data-user="<?php echo $userID; ?>" value="<?php echo $userID; ?>" name="view[<?php echo $layout['layouts_id']; ?>][]" <?php if ($checked_current) {
                                                                echo 'checked';
                                                            } ?> data-checked_current="<?php echo (int)$checked_current; ?>" data-checked_module="<?php echo (int) $checked_module; ?>" data-difference="<?php echo $difference_green_red; ?>"/>
                                                                            <small class="text-muted"><?php echo $username; ?></small>
                                                            
                                                        </label>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                            
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="form-group">
                                <div id="msg_views_form" class="alert"></div>
                            </div>

                        </div>

                        <div class="form-actions fluid">
                            <div class="col-md-12">
                                <div class='pull-right'>
                                    
                                    <button type="submit" class="btn btn-primary"><?php e('Export/Save'); ?></button>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions fluid">
                            <div class="col-md-12">
                                <div class='pull-right'>
                                    &nbsp;
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
            <!-- END SAMPLE FORM PORTLET-->
        </div>

    </div>
</section>


<script>
    var token = JSON.parse(atob($('body').data('csrf')));
    var token_name = token.name;
    var token_hash = token.hash;
    var only_sidebar = false;
    $(document).ready(function() {
        'use strict';
        $('.js-switch_view_permissions').on('click', function () {
            var btn = $(this);
            $('.js-checkbox_module_permission').prop('checked', false); // Rimuovi le spunte da tutti i checkbox
            $(this).removeClass('checkbox_green');
                        $(this).removeClass('checkbox_red');
            switch (btn.data('what')) {
                case 'current':
                    $('.js-checkbox_module_permission[data-checked_current="1"]').prop('checked', true);
                    break;
                case 'module':
                    $('.js-checkbox_module_permission[data-checked_module="1"]').prop('checked', true);
                    break;
                case 'differences':
                    $('.js-checkbox_module_permission').not('[data-difference=""]').prop('checked', true); // Rimuovi le spunte dai checkbox senza data-difference
                    
                    break;
            }
            $('.js-checkbox_module_permission').not('[data-difference=""]').each(function () {
                        
                        $(this).addClass('checkbox_'+$(this).data('difference'));
                    });
            $('.js-switch_view_permissions').removeClass('selected');
            btn.addClass('selected');
        });
    });
    
</script>