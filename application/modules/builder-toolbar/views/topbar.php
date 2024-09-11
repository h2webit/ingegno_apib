<?php $this->layout->addModuleStylesheet('builder-toolbar', 'css/topbar.css');
$dev_mode = $this->session->userdata('dev_mode');


$users = $this->apilib->search("users", ["users_active = " . DB_BOOL_TRUE], 'users_type_value');
$this->load->model('entities');
if ($this->entities->entity_exists('users_manager_configurations')) {
    $configurations = $this->apilib->searchFirst('users_manager_configurations');
    $salt = $configurations['users_manager_configurations_salt'];
} else {
    $salt = 'Salt configuration missing!';
}


// Value id
if (!empty($value_id)) {
    $get_value_id = $value_id;
} else {
    $get_value_id = '';

}
// debug($users);
?>


<!-- Vertical toolbar -->
<div id="builder_toolbar" style="display:none">
    <?php if ($this->auth->is_admin() || $_SERVER['REMOTE_ADDR'] == '62.196.41.184'): ?>
        <div class="material-switch">
            <span style="color:#fefefe">Maintenance mode &nbsp;</span>
            <input id="js_toolbar_maintenance" type="checkbox" <?php if (is_maintenance()): ?>checked="checked" <?php
            endif; ?> value="1">
            <label for="js_toolbar_maintenance" class="label-success" data-toggle="tooltip" data-placement="bottom"
                data-container="body" title="Maintenance mode"></label>
        </div>


        <!-- <div class="material-switch">
            <input id="js_toolbar_devtheme" type="checkbox" <?php if (!empty($dev_mode) && $dev_mode == true): ?>checked="checked" <?php
            endif; ?> value="1">
            <label for="js_toolbar_devtheme" class="label-success" data-toggle="tooltip" data-placement="bottom"
                data-container="body" title="Dev Template switch"></label>
        </div> -->

        <div class="btn-toolbar">
            <div class="btn-group-horizontal" role="group" aria-label="...">

                <button id="js_toolbar_vblink" class="btn btn-default" data-toggle="tooltip" data-placement="bottom"
                    data-container="body" title="Open layout on Visual Builder">
                    <span class="fas fa-external-link-alt"></span></button>

                <button id="js_toolbar_highlighter" class="btn btn-default" data-toggle="tooltip" data-placement="bottom"
                    data-container="body" title="Highlight elements"><span class="fas fa-highlighter"></span></button>

                <!-- Dev Console -->
                <a href="<?php echo base_url("get_ajax/layout_modal/builder-toolbar-console?_mode=side_view&get_value_id=" . $get_value_id); ?>"
                    class="btn btn-default btn-spaced dropdown-toggle js_open_modal" data-toggle="tooltip"
                    data-placement="bottom" data-container="body" title="Dev Console"><i class="fas fa-terminal"></i></a>
                <!-- Profiler -->

                <?php if ($this->input->get('_profiler')): ?>
                    <a href="#" onclick="ci_profiler_bar.open(); return false;" style="color: rgb(255, 0, 0);"
                        id="ci-profiler-menu-open" class="btn btn-default btn-spaced dropdown-toggle blink_me"
                        data-toggle="tooltip" data-placement="bottom" data-container="body" title="Profiler"><i
                            class="fas fa-bug"></i></a>
                <?php else: ?>
                    <a href="#" onclick="location.href+=location.href.includes('?')?'&_profiler=1':'?_profiler=1'"
                        class="btn btn-default btn-spaced dropdown-toggle" data-toggle="tooltip" data-placement="bottom"
                        data-container="body" title="Reload with profiler"><i class="fas fa-bug"></i></a>
                <?php endif; ?>
                <!-- Live/Debug -->


                <!-- Users Login -->

                <div class="btn-group btn-spaced" style=" width:auto" data-toggle="tooltip" data-placement="bottom"
                    data-container="body" title="All users">

                    <button type="button" class="btn btn-default dropdown-toggle " data-toggle="dropdown"
                        aria-expanded="true">
                        <span class="fas fa-key"></span>
                    </button>

                    <ul class="dropdown-menu " role="menu" style="z-index: 9999;">

                        <?php foreach ($users as $user): ?>

                            <li><a target="_blank"
                                    href="<?php echo base_url('user-extender/accessextender/login_force/' . $user['users_id'] . '/' . md5($salt . $user['users_id'])); ?>">
                                    <?php echo "<strong>" . $user['users_first_name'] . " " . $user['users_last_name'] . "</strong> " . $user['users_type_value']; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Permissions -->

                <div class="js_button_user_permissions btn-group btn-spaced" style=" width:auto" data-toggle="tooltip"
                    data-placement="bottom" data-container="body" title="Check users permissions not available...">

                    <button type="button" class="btn btn-default dropdown-toggle js_check_users_permissions"
                        data-toggle="dropdown" aria-expanded="true">
                        <span class="fas fa-exclamation"></span>
                    </button>

                    <ul class="dropdown-menu js_users_can_view" role="menu" style="z-index: 9999;">
                        <li class="divider"></li>
                        <li><a target="_blank" href="<?php echo base_url('main/permissions'); ?>">Go to permissions</a>
                        </li>
                    </ul>
                </div>

                <!-- PHP Errors Log -->
                <a href="<?php echo base_url("main/layout/builder-toolbar-logs"); ?>"
                    class="btn btn-default btn-spaced dropdown-toggle" data-toggle="tooltip" data-placement="bottom"
                    data-container="body" title="PHP Logs Viewer"><i class="fas fa-bomb"></i></a>




                <!-- Changelog -->
                <a href="<?php echo base_url("get_ajax/modal_form/changelog-form"); ?>"
                    class="btn btn-default btn-spaced dropdown-toggle js_open_modal" data-toggle="tooltip"
                    data-placement="bottom" data-container="body" title="Changelog and notification"><i
                        class="fas fa-paper-plane"></i></a>


                <!-- TOOLS -->

                <div class="btn-group btn-spaced" style=" width:auto" data-toggle="tooltip" data-placement="bottom"
                    data-container="body" title="Extra tools">

                    <button type="button" class="btn btn-default dropdown-toggle " data-toggle="dropdown"
                        aria-expanded="true">
                        <span class="fas fa-download"></span>
                    </button>

                    <ul class="dropdown-menu " role="menu" style="z-index: 9999;">

                        <li><button id="js_toolbar_download_dump" class="btn btn-default btn-spaced" data-toggle="tooltip"
                                data-placement="bottom" data-container="body" title="Download Dump"><span
                                    class="fas fa-download"></span> Download Dump</button>

                        </li>
                        <li>
                            <button id="js_toolbar_download_zip" class="btn btn-default" data-toggle="tooltip"
                                data-placement="bottom" data-container="body" title="Download Full Zip"><span
                                    class="fas fa-cloud-download-alt"></span> Download Full Zip </button>
                        </li>
                    </ul>
                </div>


                <!--
                        <button id="js_toolbar_backup" class="btn btn-default" data-toggle="tooltip" data-placement="left" data-container="body" title="Backup & Restore"><span class="fas fa-download"></span></button>
                        <button id="js_toolbar_query" class="btn btn-default" data-toggle="tooltip" data-placement="left" data-container="body" title="Query"><span class="fas fa-pen"></span></button>
                        -->




                <button id="js_toolbar_exit" class="btn btn-default btn-spaced" data-toggle="tooltip"
                    data-placement="bottom" data-container="body" title="Close Toolbar"><span
                        class="fas fa-sign-out-alt"></span></button>
            </div>


            <!--<div class="btn-group open">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                        <span class="fas fa-cogs"></span>
                        <span class="sr-only">Toggle Dropdown</span>
                    </button>
                    <ul class="dropdown-menu" role="menu">
                        <li><a href="#">Download Dump</a></li>
                        <li><a href="#">Download Full Zip</a></li>

                        <li class="divider"></li>
                        <li><a href="#">Separated link</a></li>
                    </ul>
                </div>-->
        </div>


    <?php endif; ?>
</div>


<?php $this->layout->addModuleJavascript('builder-toolbar', 'js/topbar.js'); ?>