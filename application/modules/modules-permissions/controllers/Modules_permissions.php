<?php

class Modules_permissions extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('modules-permissions/mod_perm');
        $this->load->model('module');
        $this->modules_permissions_settings = $this->apilib->searchFirst('modules_permissions_settings');
    }

    public function modules_permissions_save($modules_permissions_id)
    {
        $module = $this->apilib->view('modules_permissions', $modules_permissions_id);
        $post = $this->input->post();
        $inputPostView = $post['view'];
        $viewsAccessWithGroups = $inputPostView;
        $viewsAccess = [];

        $layouts = $this->db->where_in('layouts_id', array_keys($inputPostView))->get('layouts')->result_array();
        $layouts_by_id = array_key_map_data($layouts, 'layouts_id');
        //debug($layouts_by_id,true);

        foreach ($viewsAccessWithGroups as $layout_id => $groups) {
            $layout_module_identifier = $layouts_by_id[$layout_id]['layouts_module_key'];
            if (empty($layout_module_identifier)) {
                $layout_module_identifier = $this->module->generate_key($module['modules_permissions_module'], 'layout', $layout_id);
                $layouts_by_id[$layout_id]['layouts_module_key'] = $layout_module_identifier;
                $this->db->where('layouts_id', $layout_id)->update('layouts', ['layouts_module_key' => $layout_module_identifier]);
            }
            if (empty($viewsAccess[$layout_module_identifier])) {
                $viewsAccess[$layout_module_identifier] = [];
            }
            
            foreach ($groups as $group) {
                
                    $viewsAccess[$layout_module_identifier][] = $group;
                
            }
        }

        //A questo punto, so chi accede ai vari layouts. Tutti gli altri li metto unallowed (devo fare così perchè nel progetto destinazione poi non saprei quali sono gruppi effettivamente unalloed rispetto a gruppi "ad hoc" per quel crm)
        $unallowed = [];
        $groups = $this->db->query("SELECT DISTINCT(permissions_group) as permissions_group FROM permissions WHERE permissions_group IS NOT NULL AND permissions_group <> ''")->result_array();
        foreach ($layouts_by_id as $layout_id => $layout) {
            foreach ($groups as $group) {
                $groupname = $group['permissions_group'];
                if (empty($viewsAccess[$layout['layouts_module_key']]) || !in_array($groupname, $viewsAccess[$layout['layouts_module_key']])) {
                    $unallowed[$layout['layouts_module_key']][] = $groupname;
                } else {
                    // debug($groupname);
                    // debug($layout,true);
                }
            }
            
        }
        $json = [
            'viewAccess' => $viewsAccess,
            'unallowed_layouts' => $unallowed
        ];
        $this->apilib->edit('modules_permissions', $modules_permissions_id, ['modules_permissions_json' => json_encode($json)]);
        e_json(array('status' => 4, 'txt' => t('Module permissions exported!')));
    }

    public function modules_permissions_import($modules_permissions_id)
    {
       $return =  $this->mod_perm->modules_permissions_import($modules_permissions_id, true);
       if ($return) {
            if (isset($_SERVER['HTTP_REFERER'])) {
                //redirect($_SERVER['HTTP_REFERER']);
                // Headers already sent, use JavaScript for the redirect
                $uri = $_SERVER['HTTP_REFERER'];
            } else {
                // Se la variabile referer non è definita, fai un redirect a una pagina predefinita
                //redirect();
                $uri = base_url();
            }
            //debug("fatto",true);
            echo '<script>window.location.href="' . $uri . '";</script>';

        }  else {
            echo_log('debug', 'Module permissions empty!');
       }
       
        
    }
    public function massive_import() {
        $ids = json_decode($this->input->post('ids'),true);
        
        foreach ($ids as $modules_permissions_id) {
            $this->mod_perm->modules_permissions_import($modules_permissions_id, true);
        }
        if (isset($_SERVER['HTTP_REFERER'])) {
            //redirect($_SERVER['HTTP_REFERER']);
            // Headers already sent, use JavaScript for the redirect
            $uri = $_SERVER['HTTP_REFERER'];
        } else {
            // Se la variabile referer non è definita, fai un redirect a una pagina predefinita
            //redirect();
            $uri = base_url();
        }
        echo '<script>window.location.href="' . $uri . '";</script>';
        
    }

    public function automatic_massive_import()
    {
        if ($this->modules_permissions_settings['modules_permissions_settings_auto_update'] == DB_BOOL_TRUE) {
            foreach ($this->apilib->search('modules_permissions') as $modules_permissions) {
                $modules_permissions_id = $modules_permissions['modules_permissions_id'];

                if (!$this->mod_perm->modules_permissions_import($modules_permissions_id, true)) {
                    log_message('debug', "Module '{$modules_permissions['modules_permissions_module']}' permissions empty");
                }
            }
        } else {
            echo_log('debug', "Update automatico disattivato.");
        }

        
        
    }

    public function import_modules() {
        $this->db->query("
            INSERT INTO modules_permissions (modules_permissions_module)
            SELECT modules_identifier
            FROM modules
            WHERE modules_identifier NOT IN (SELECT modules_permissions_module FROM modules_permissions);
        ");
        $this->mycache->clearCache();
        if (isset($_SERVER['HTTP_REFERER'])) {
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            // Se la variabile referer non è definita, fai un redirect a una pagina predefinita
            redirect();
        }
    }

    
    
}