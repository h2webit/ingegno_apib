<?php

class Mod_perm extends CI_Model
{
    public function modules_permissions_import($modules_permissions_id, $progress = false)
    {
        $module = $this->apilib->view('modules_permissions', $modules_permissions_id);
        $json = json_decode($module['modules_permissions_json'], true);

        if (!$json) {
            return false;
        }

        $json_permission = $json['viewAccess'];
        $unallowed = $json['unallowed_layouts'];
        //debug($unallowed,true);
        $module_name = $module['modules_permissions_module'];
        //Prendo tutti i layout del modulo
        $layouts = $this->db->get_where('layouts', ['layouts_module' => $module_name])->result_array();

        $count = count($layouts);
        $c = 0;
        
        foreach ($layouts as $layout) {
            $c++;
            if ($progress) {
                progress($c, $count, $module_name);
            }
            
            //Se non c'è tra i layout json_permission, lo metto unallowed
            if (empty($json_permission[$layout['layouts_module_key']])) {
                //Blocco a tutti (layout non più presente sul modulo o a cui nessuno deve avere più accesso)
                $this->db->query("INSERT INTO unallowed_layouts (unallowed_layouts_layout,unallowed_layouts_user) SELECT {$layout['layouts_id']},users_id FROM users WHERE users_id NOT IN (SELECT permissions_user_id FROM permissions WHERE permissions_admin = 1 AND permissions_user_id IS NOT NULL)");
            } else {
                $gruppi_where = "'" . implode("','", $json_permission[$layout['layouts_module_key']]) . "'";
                //Rimuovo prima tutti gli unallowed
                $this->db->query("
                    DELETE FROM unallowed_layouts 
                    WHERE 
                        unallowed_layouts_layout = '{$layout['layouts_id']}' 
                        AND unallowed_layouts_user IN (
                            SELECT permissions_user_id FROM permissions WHERE permissions_group IS NOT NULL AND permissions_group <> '' AND permissions_group IN ($gruppi_where)
                        )
                ");
                //A questo punto inserisco unallowed tutti gli users presenti nel json unallowed
                foreach ($unallowed[$layout['layouts_module_key']] as $groupname) {
                    $this->db->query("INSERT INTO unallowed_layouts (unallowed_layouts_layout,unallowed_layouts_user) SELECT {$layout['layouts_id']},permissions_user_id FROM permissions WHERE permissions_group = '{$groupname}'");
                }

                //debug($json_permission[$layout['layouts_module_key']], true);
            }
        }
        return true;

    }
}