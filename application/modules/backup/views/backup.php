<?php

// Check authorized user
$check_user = $this->db->query("SELECT * FROM backups_settings WHERE backups_settings_authorized_user LIMIT 1")->row_array();

// Check if password exists or create it
$check_password = $this->db->query("SELECT * FROM backups_settings LIMIT 1")->row_array();

// Db Size
$db_size = $this->db->query("SELECT table_schema 'database', ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) 'size' FROM information_schema.tables")->row_array();

if (!empty($db_size)) {
    $db_size = $db_size['size'];
} else {
    $db_size = "Error";
}

?>
<?php if (!$this->datab->is_admin() && (!empty($check_user) && ($check_user['backups_settings_authorized_user'] != $this->auth->get('users_id')))) : ?>

    <div class="row">
        <div class="col-md-12">
            <h5><?php e('Oh no! you have no permission to open this page. Please contact your system administrator.'); ?></h5>
        </div>
    </div>

<?php else : ?>
    <p><strong><?php e('Keep your data safe'); ?></strong>, <?php e('generate a database dump or files backup and keep them safe'); ?>.</p>

    <div class="row">
        <div class="col-md-12">
            <ul>
                <li><strong><?php e('Database size'); ?></strong>: <?php echo $db_size; ?>M</li>
                <li><strong><?php e('Dimensione file caricati'); ?></strong>: <span class="js_upload_size"></span> <a href="#" OnClick="getUploadSize()"><?php e('Calculate now'); ?> </a>
                </li>
            </ul>

            <h5><?php e('Only admin can download backup files.'); ?></h5>
        </div>
    </div>


    <div class="row" style="margin-top:30px">
        <div class="col-md-12">
            <a href="#" OnClick="downloadDump();" class=" btn  btn-primary menu-806 mr-10 br-4">
                <i class="fas fa-download"></i> <?php e('Download database'); ?> </a>

            <a href="#" OnClick="downloadFiles();" class=" btn  btn-primary  menu-806 mr-10 br-4">
                <i class="fas fa-download"></i> <?php e('Download uploaded files'); ?></a>

            <?php if ($this->datab->is_admin()) : ?>
                <a class="js-action_button btn btn-grid-action-s btn-success js_open_modal" href="<?php echo base_url('get_ajax/modal_form/backups-superadmin-settings'); ?>">
                    <i class="fas fa-cogs"></i> Superadmin Settings</i>
                </a>
            <?php endif; ?>

            <h5><?php e('The download operation may take a long time, the system may stop responding.'); ?></h5>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <br />
            <h4><?php e('Latest backup downloads'); ?>:</h4><br />
            <?php
            $grid_id = $this->datab->get_grid_id_by_identifier('backups-downloads');
            $grid = $this->datab->get_grid($grid_id);
            $grid_layout = $grid['grids']['grids_layout'];
            $grid_data['data'] = $this->datab->get_grid_data($grid, ['value_id' => null]);
            $grid_html = $this->load->view("pages/layouts/grids/{$grid_layout}", array('grid' => $grid, 'sub_grid' => null, 'grid_data' => $grid_data, 'value_id' => null, 'layout_data_detail' => []), true);
            echo $grid_html;
            ?>

            <h4><?php e('The latest backup download list does not guarantee that users have actually downloaded a full copy of the backup and that it is intact. Verification must be done manually, after downloading the file, opening the archive as a zip and checking that it is valid.'); ?></h4>
        </div>
    </div>





    <script>
        function downloadDump() {
            var sys_password = prompt("Please enter system password");
            if (sys_password != null) {
                window.location.href = base_url + 'backup/download_dump/' + sys_password;
            }
        }

        function downloadFiles() {
            var sys_password = prompt("Please enter system password");
            if (sys_password != null) {
                window.location.href = base_url + 'backup/download_uploads/' + sys_password;
            }
        }

        function generatePwd() {
            $.ajax({
                url: base_url + "backup/generatePassword",
                success: function(data) {
                    alert(data);
                    loading(0);
                    window.location.reload();
                },
                error: function() {
                    alert("There was an error on getting directory size. Contact administrator");
                    loading(0);
                }
            });
        }

        function getUploadSize() {
            loading(1);
            $('.js_upload_size').text("Wait... ");

            $.ajax({
                url: base_url + "backup/getFolderSize/uploads",
                success: function(data) {
                    $('.js_upload_size').text(data);
                    loading(0);
                },
                error: function() {
                    alert("There was an error on getting directory size. Contact administrator");
                    loading(0);
                }
            });
        }

        function getProjectSize() {
            loading(1);
            $.ajax({
                url: base_url + "backup/getFolderSize/",
                success: function(data) {
                    $('.js_project_size').text(data + "M");
                    loading(0);
                },
                error: function() {
                    alert("There was an error on getting directory size. Contact administrator");
                    loading(0);
                }
            });
        }
    </script>
<?php endif; ?>