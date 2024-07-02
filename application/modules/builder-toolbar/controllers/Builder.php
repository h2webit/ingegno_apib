<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');


/*
 *
 * TODO: Move this controller to the new Builder module!
 *
 */
class Builder extends MY_Controller
{

    function __construct()
    {

        

        parent::__construct();


        // Super admin protection
        if (!$this->auth->is_admin()) {
            die("Oh no! Only super-admin can use this module.");
        }
    }


    // Execute eval code for php shell
    public function execute_eval()
    {
        if (!$this->auth->is_admin()) {
            die("Oh no!");
        }

        $code = $this->input->post('code');
        if ($code) {
            try {
                ob_start();
                eval($code);
                $output = ob_get_contents();
                ob_end_clean();
            } catch (Exception $e) {
                $output = $e->getMessage();
            }
            echo json_encode(['output' => $output]);
        } else {
            echo json_encode(['output' => 'Nessun codice inserito.']);
        }
    }




    public function git_pull()
    {
        $output = shell_exec("git pull");
        echo "<p>Executing GIT pull: </p><pre>$output</pre>";
    }
    public function git_push()
    {
        $output = shell_exec("git add . && git commit -m 'Auto commit from builder toolbar' && git push");
        echo "<p>Executing GIT push: </p><pre>$output</pre>";
    }
    public function check_user_permissions($layout_id)
    {
        if (empty($layout_id)) {
            return false;
        }
        //$unallowed_users = $this->db->query("SELECT * FROM unallowed_layouts WHERE unallowed_layouts_layout = '{$layout_id}'")->result_array();

        // Normal user can see this layout
        $users_can_view = $this->db->query("SELECT permissions_group, permissions_admin FROM users 
                                            LEFT JOIN permissions ON users_id = permissions_user_id 
                                            WHERE (users_deleted = '" . DB_BOOL_FALSE . "' OR users_deleted IS NULL OR users_deleted = '') AND users_active = '" . DB_BOOL_TRUE . "'
                                            AND users_id NOT IN (SELECT unallowed_layouts_user FROM unallowed_layouts WHERE unallowed_layouts_layout = '{$layout_id}') 
                                            GROUP BY permissions_group ORDER BY permissions_group ASC 
                                             ")->result_array();

        $all_groups = $this->db->query("SELECT * FROM permissions 
                                        GROUP BY permissions_group ORDER BY permissions_group DESC")->result_array();

        if (!empty($users_can_view)) {
            $only_super_admin = false;
        } else {
            $only_super_admin = true;
        }

        //Detect if layout is refering to a module
        $layout = $this->db->get_where('layouts', ['layouts_id' => $layout_id])->row_array();

        // $only_super_admin = true;
        echo json_encode(array('only_super_admin' => $only_super_admin, 'all_groups' => $all_groups, 'users_can_view' => $users_can_view,'layout' => $layout));
    }

    public function add_group_permission($layout_id, $group = false, $checked = false, $recursion_call = false)
    {
        //debug($this->input->post(),true);
        if (empty($layout_id) || !$this->auth->is_admin()) {
            if (!$recursion_call) {
                e_json(['status' => 0, 'msg' => t('Error occurred!')]);
            }
            return false;
        }
        if (!$group) {
            $group = $this->input->post('group');
            $checked = $this->input->post('checked')=='true';
        }
        
        if ($checked) {
            $this->db->query("DELETE FROM unallowed_layouts WHERE unallowed_layouts_layout = $layout_id AND unallowed_layouts_user IN (SELECT permissions_user_id FROM permissions WHERE permissions_group = '$group')");
        } else {
            $query = "INSERT INTO unallowed_layouts (unallowed_layouts_layout,unallowed_layouts_user) SELECT $layout_id, permissions_user_id FROM permissions WHERE permissions_group = '$group' AND permissions_group IS NOT NULL AND permissions_user_id IS NOT NULL";
            
            $this->db->query($query);
        }

        $module = $this->input->post('module');
        
        if ($module) {
            if ($recursion_call) {
                $_children_layouts = [];
            } else {
                $_children_layouts = $this->db->select('layouts_id as layout_id')->get_where('layouts', ['layouts_module' => $module])->result_array();
            }
            
        } else {
            $_children_layouts = $this->db->query("SELECT layouts_boxes_content_ref as layout_id FROM layouts_boxes WHERE layouts_boxes_content_type = 'layout' AND layouts_boxes_layout = '$layout_id' AND layouts_boxes_content_ref IS NOT NULL AND layouts_boxes_content_ref IN (SELECT layouts_id FROM layouts)")->result_array();
            
        }
        
        
        foreach ($_children_layouts as $lay) {
            if ($lay['layout_id']) {
                //debug($lay);
                    $this->add_group_permission($lay['layout_id'], $group, $checked, true);
                
                
            }
            
        }
        
        if (!$recursion_call) {
            $this->mycache->clearCache();
            e_json(['status' => 1, 'msg' => t('Permissions saved!')]);
        }
    }

    // Drag and Drop layout boxes
    public function update_layout_box_position($layout_id, $last_box_moved)
    {
        $rows = $this->input->post();
        if (!$rows) {
            return;
        }

        foreach ($rows as $row_id => $row_array) {
            if (is_array($row_array)) {
                // Security reset
                //$this->db->where('layouts_boxes_layout', $layout_id)->where('layouts_boxes_row', $row_id)->update("layouts_boxes", array("layouts_boxes_position" => 0));
                foreach ($row_array as $position => $layout_box_id) {

                    if ($layout_box_id == $last_box_moved) {
                        $this->db->where("layouts_boxes_id = '$layout_box_id'")->update("layouts_boxes", array("layouts_boxes_layout" => $layout_id, "layouts_boxes_position" => $position, "layouts_boxes_row" => $row_id));
                    } else {
                        $this->db->where("layouts_boxes_id = '$layout_box_id'")->update("layouts_boxes", array("layouts_boxes_position" => $position, "layouts_boxes_row" => $row_id));
                    }
                }
            }
        }
    }



    // Switch maintenance 
    public function switch_devtheme()
    {
        $settings = $this->db->get('settings')->row();

        // Search dev template
        $template = $this->db->where('settings_template_folder', 'builder')->limit(1)->get('settings_template')->row_array();


        if (!empty($template)) {
            if ($settings->settings_template == $template['settings_template_id']) {
                // Return to prev template
                $this->session->set_userdata('dev_mode', false);
                $original_template = $this->session->userdata('original_template');
                $this->db->where('settings_id', $settings->settings_id)->update('settings', array('settings_template' => $original_template));
            } else {
                $this->session->set_userdata('dev_mode', true);
                $this->session->set_userdata('original_template', $settings->settings_template);
                // Set dev template
                $this->db->where('settings_id', $settings->settings_id)->update('settings', array('settings_template' => $template['settings_template_id']));
                // Set maintenance
                $this->db->where('settings_id', $settings->settings_id)->update('settings', array('settings_maintenance_mode' => '1'));
            }
        }
    }

    // Switch maintenance 
    public function switch_maintenance()
    {
        $settings = $this->db->get('settings')->row();

        if ($settings->settings_maintenance_mode == 0) {
            $this->db->where('settings_id', $settings->settings_id)->update('settings', array('settings_maintenance_mode' => '1'));
        } else {
            $this->db->where('settings_id', $settings->settings_id)->update('settings', array('settings_maintenance_mode' => '0'));
        }

        $this->mycache->clearCache();
    }

    /*
     *   ----- FORMS ------
     */
    // Form position
    public function update_form_fields_position($form_id)
    {

        $data = $this->input->post('undefined');

        foreach ($data as $pos => $form_field_id) {
            $this->db
                ->where('forms_fields_forms_id', $form_id)
                ->where('forms_fields_fields_id', $form_field_id)
                ->update('forms_fields', [
                    'forms_fields_order' => $pos,
                ]);
        }
    }
    // Form remove position
    public function remove_form_field($field_id, $form_id)
    {
        $this->db->where('forms_fields_forms_id', $form_id)->where('forms_fields_fields_id', $field_id)->delete('forms_fields');
    }

    // Resize form fields
    public function update_field_cols($field_id, $cols)
    {
        $this->db->where('forms_fields_fields_id', $field_id)->update("forms_fields", array("forms_fields_override_colsize" => $cols));
    }

    /*
     *   ----- LAYOUT BOXES ------
     */

    // Resize layout boxes
    public function update_layout_box_cols($layouts_boxes_id, $cols)
    {
        $this->db->where('layouts_boxes_id', $layouts_boxes_id)->update("layouts_boxes", array("layouts_boxes_cols" => $cols));
    }

    // Delete layout boxes
    public function delete_layout_box($layout_box_id)
    {
        $this->db->where("layouts_boxes_id = '$layout_box_id'")->delete("layouts_boxes");
    }
    // Delete layout boxes
    public function move_layout_box($layout_box_id, $new_layout_id)
    {
        $this->db->where('layouts_boxes_id', $layout_box_id)->update("layouts_boxes", array("layouts_boxes_layout" => $new_layout_id));
    }

    // Update layout title
    public function update_layout_box_title($layout_box_id)
    {
        $title = $this->input->get('title');
        if ($title) {
            $this->db->where('layouts_boxes_id', $layout_box_id)->update("layouts_boxes", array("layouts_boxes_title" => $title));
        }
    }

    /*
     *   ----- SIDEBAR MENU ------
     */

    public function update_menu_item_position()
    {

        $data = $this->input->post('undefined');

        foreach ($data as $pos => $menu_id) {
            $this->db
                ->where('menu_id', $menu_id)
                ->update('menu', [
                    'menu_order' => $pos,
                ]);
        }
    }



    /*
     * Backup Methods
     */
    function download_dump($system_password)
    {
        // TODO: Cambiare con gestione di un token di sicurezza o altro controllo
        if (md5($system_password) != 'e96afee91143296068580d92da1ea097') {
            die("Hacking attempt detected. Communication in progress to the system administrator.");
        }

        $DBUSER = $this->db->username;
        $DBPASSWD = $this->db->password;
        $DATABASE = $this->db->database;
        $DBHOST = $this->db->hostname;

        $filename = "backup-db-" . date("d-m-Y") . ".sql.gz";
        $mime = "application/x-gzip";

        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $cmd = "mysqldump -h $DBHOST -u $DBUSER --password=$DBPASSWD $DATABASE | gzip --best";
        passthru($cmd);
    }

    function download_zip($system_password)
    {
        // TODO: Cambiare con gestione di un token di sicurezza o altro controllo
        if (md5($system_password) != 'e96afee91143296068580d92da1ea097') {
            die("Hacking attempt detected. Communication in progress to the system administrator.");
        }

        $filename = "backup-uploads-" . date("d-m-Y") . ".tar";

        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachement; filename=" . $filename);
        passthru("tar -cz " . FCPATH);
    }

    function getFolderSize($dir = FCPATH)
    {
        passthru("cd $dir && du -hs");
    }
}