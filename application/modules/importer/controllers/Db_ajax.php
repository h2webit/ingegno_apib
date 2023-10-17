<?php

class Db_ajax extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        if ($this->auth->guest()) {
            redirect('access');
        }

        /* if (!$this->datab->module_installed(MODULE_NAME)) {
            die('Module not installed');
        }

        if (!$this->datab->module_access(MODULE_NAME)) {
            die('Access forbidden');
        }*/
    }

    public function import_1()
    {
        $data = $this->input->post();

        if (empty($data) || !$data['entity_id']) {
            die(json_encode(array('status' => 0, 'txt' => 'Entity not declared')));
        }

        if (!is_dir('./uploads/import/')) {
            mkdir('./uploads/import/', DIR_WRITE_MODE, true);
        }

        $config['upload_path'] = './uploads/import/';
        $config['allowed_types'] = 'csv';
        $config['max_size'] = '20000';
        $this->load->library('upload');

        $this->upload->initialize($config);


        if (!$this->upload->do_upload('csv_file')) {
            die(json_encode(array('status' => 0, 'txt' => $this->upload->display_errors())));
        }

        $data['csv_file'] = $this->upload->data();

        $this->session->set_userdata(SESS_IMPORT_DATA, $data);

        // If use an existing mapping
        if ($data['importer_mappings_id']) {
            //Attention!!! $_FILES is already filled. Unset to avoid apilib getting error
            $_FILES = null;
            $this->import_2(0, $data['importer_mappings_id']);
        } else {
            echo json_encode(array('status' => 1, 'txt' => base_url('importer/import/import_map')));
        }
    }



    public function import_2($test = 0, $mapping_id = 0)
    {
        $data = $this->input->post();
        $import_data = $this->session->userdata(SESS_IMPORT_DATA);

        if ($mapping_id != 0) {
            $mapping = $this->db->query("SELECT * FROM importer_mappings WHERE importer_mappings_id = '{$mapping_id}'")->row_array();
            $map_json = json_decode($mapping['importer_mappings_json'], true);
            $data['ref_fields'] = (!empty($map_json['ref_fields'])) ? $map_json['ref_fields'] : array();
            $data['csv_fields'] = $map_json['csv_fields'];
            $campo_chiave = $map_json['field_key'];
        }

        if (empty($data) || empty($import_data)) {
            die(json_encode(array('status' => 0, 'txt' => "No session or file!")));
        }

        //Read csv first line
        if (($handle = fopen($import_data['csv_file']['full_path'], "r")) !== FALSE) {

            /*
             * Info preliminari
             */
            $csv_fields = array_filter($data['csv_fields']);
            $count = 0;

            $this->db->trans_start();

            $entity = $this->datab->get_entity($import_data['entity_id']);

            if ($import_data['action_on_data_present'] == 1) { //Metodo di importazione: DELETE INSERT
                $this->db->empty_table($entity['entity_name']);
            }

            //Estraggo tutte le info sui campi (mi tornano utili dopo)
            $field_data_map = array();
            foreach ($csv_fields as $k => $field) {
                foreach ($entity['fields'] as $_field) {
                    if ($_field['fields_name'] == $field) {
                        $field_data_map[$field] = $_field;
                        continue;
                    }
                }
            }

            $head = fgetcsv($handle, 0, "{$import_data['field_separator']}");
            $body = array();
            $riga_id = 1;

            $errors = $warnings = array();
            $already_found = [];
            while (($row = fgetcsv($handle, 0, "{$import_data['field_separator']}")) !== FALSE) {

                //
                // Remap campi riga su campi entità e metto in array insert
                //
                $insert = array();

                foreach ($csv_fields as $k => $field) {
                    if (array_key_exists('ref_fields', $data) && array_key_exists($k, $data['ref_fields'])) {
                        //TODO: esiste un field_ref impostato (prendo l'id dall'altra entità)
                        if ($data['ref_fields'][$k]) {
                            //Cerco il record con quella chiave
                            if ($row[$k]) {
                                //Sfrutto la cache dei vari ref che ho trovato per non dover rifare ogni volta la query
                                if (empty($already_found[$data['ref_fields'][$k]][$row[$k]])) {
                                    $ref_record = $this->db->get_where($field_data_map[$field]['fields_ref'], array($data['ref_fields'][$k] => $row[$k]));
                                    if ($ref_record->num_rows() >= 1 && $row[$k]) { //Fix
                                        //Giusto per avvisare l'utente, segnalo come warning il fatto che ho trovato più corrispondenze
                                        if ($ref_record->num_rows() > 1) {
                                            $warn = "{$ref_record->num_rows()} records found with {$data['ref_fields'][$k]}='{$row[$k]}', first one used.";
                                            $warnings[$warn] = $warn;
                                        }

                                        $already_found[$data['ref_fields'][$k]][$row[$k]] = $ref_record->row_array();
                                        $insert[$field] = $ref_record->row_array()[$field_data_map[$field]['fields_ref'] . '_id'];
                                    } else {
                                        //Se il campo può essere null e non ho trovato corrispondenza, lo setto a null
                                        if ($field_data_map[$field]['fields_required'] != DB_BOOL_TRUE) {
                                            $already_found[$data['ref_fields'][$k]][$row[$k]] = null;
                                            $insert[$field] = null;
                                            $warn = "I cannot find record in {$field_data_map[$field]['fields_ref']} with {$data['ref_fields'][$k]}='{$row[$k]}'.";
                                            $warnings[$warn] = $warn;
                                        } else { //Altrimenti errore
                                            $err = "I cannot find record in {$field_data_map[$field]['fields_ref']} with {$data['ref_fields'][$k]}='{$row[$k]}'.";
                                            $errors[$err] = $err;
                                        }
                                    }
                                } else {
                                    $insert[$field] = $already_found[$data['ref_fields'][$k]][$row[$k]][$field_data_map[$field]['fields_ref'] . '_id'];
                                }
                            } else {
                                //Se il campo può essere null e non ho trovato corrispondenza, lo setto a null
                                if ($field_data_map[$field]['fields_required'] != DB_BOOL_TRUE) {
                                    $insert[$field] = null;
                                    $warn = "I cannot find record in {$field_data_map[$field]['fields_ref']} with {$data['ref_fields'][$k]}='{$row[$k]}'.";
                                    $warnings[$warn] = $warn;
                                } else { //Altrimenti errore
                                    $err = "I cannot find record in {$field_data_map[$field]['fields_ref']} with {$data['ref_fields'][$k]}='{$row[$k]}'.";
                                    $errors[$err] = $err;
                                }
                            }


                            continue;
                        } else {
                            //Se è stato lasciato vuoto va bene andare avanti e prendere l'id
                        }
                    }
                    if (array_key_exists($field, $field_data_map)) {
                        switch (strtoupper($field_data_map[$field]['fields_type'])) {
                            case 'DOUBLE':
                            case 'FLOAT':
                                $insert[$field] = str_replace(',', '.', str_replace('.', '', $row[$k]));
                                break;
                            case DB_INTEGER_IDENTIFIER:
                            case 'INT':
                                $insert[$field] = (int) ($row[$k]);
                                break;
                            case 'TIMESTAMP WITHOUT TIME ZONE':
                                if ($row[$k]) {
                                    //Verifico se inizia con 4 cifre (allora è formato standard postgres/americano Y-m-d). In caso contrario dò per scontato d-m-Y o d/m/Y
                                    if ((string) ((int) substr($row[$k], 0, 4)) === (string) substr($row[$k], 0, 4)) { //Please DO NOT COMMENT ON THIS!
                                        //debug(substr($row[$k], 0, 4));
                                        $time = strtotime($row[$k]);
                                    } else {
                                        $time = strtotime(str_replace('/', '-', $row[$k]));
                                    }

                                    if ($time) {

                                        $insert[$field] = date('Y-m-d h:m:s', $time);
                                    } else { //Se è fallito, probabilmente sono in formato italiano d m y ma con / come separatore.

                                        throw new Exception("Date format unknown: '{$row[$k]}'");
                                    }
                                    //debug($insert, true);
                                } else {
                                    $insert[$field] = null;
                                }
                                break;
                            case 'BOOL':
                                if (is_numeric($row[$k])) {
                                    $insert[$field] = ($row[$k] != 0) ? DB_BOOL_TRUE : DB_BOOL_FALSE;
                                } else {
                                    $insert[$field] = ($row[$k]) ? DB_BOOL_TRUE : DB_BOOL_FALSE;
                                }
                                break;
                            default:
                                $insert[$field] = $row[$k];
                                break;
                        }
                        //Fix: se è multilingua mi aspetto un json_encode nella colonna del csv
                        if ($field_data_map[$field]['fields_multilingual'] == DB_BOOL_TRUE) {
                            $value = @json_decode($insert[$field], true);
                            if (is_array($value)) {
                                $insert[$field] = $value;
                            } else {
                                //Lascio com'era e importo nella lingua di default
                            }
                        }

                        //Se il campo può essere null e non ho trovato corrispondenza, lo setto a null
                        if ($field_data_map[$field]['fields_required'] == DB_BOOL_TRUE && $insert[$field] == '') {
                            if ($field_data_map[$field]['fields_default']) {
                                unset($insert[$field]);
                            } else {
                                $warn = "I cannot insert row with $field empty (row id: $riga_id)";
                                $warnings[$warn] = $warn;
                                $insert = array();
                                continue 2;
                            }
                        }
                    } else {
                        $insert[$field] = $row[$k];
                    }
                }

                //
                //Se ho qualcosa da inserire e non ho riscontrato errori
                //

                if (!empty($insert) && empty($errors)) {
                    if ($import_data['action_on_data_present'] == 2 || $import_data['action_on_data_present'] == 4) {
                        //Metodo di importazione: UPDATE

                        $campo_chiave = (!empty($campo_chiave)) ? $campo_chiave : $data['csv_fields'][$data['unique_key']];
                        $riga = $this->db->where($campo_chiave, $insert[$campo_chiave])->get($entity['entity_name']);
                        if ($riga->num_rows() == 1) {
                            if ($import_data['use_apilib'] == 1) {
                                $this->apilib->edit($entity['entity_name'], $riga->row()->{$entity['entity_name'] . '_id'}, $insert);
                            } else {
                                $this->db->where($entity['entity_name'] . '_id', $riga->row()->{$entity['entity_name'] . '_id'})->update($entity['entity_name'], $insert);
                            }
                        } elseif ($riga->num_rows() > 1) {
                            //TODO: foreach e aggiorno tutti? Valutare...
                            if ($import_data['use_apilib'] == 1) {
                                $this->apilib->edit($entity['entity_name'], $riga->row()->{$entity['entity_name'] . '_id'}, $insert);
                            } else {
                                $this->db->where($entity['entity_name'] . '_id', $riga->row()->{$entity['entity_name'] . '_id'})->update($entity['entity_name'], $insert);
                            }
                            $warn = "I've found {$riga->num_rows()} records in {$entity['entity_name']} with $campo_chiave='{$insert[$campo_chiave]}'.";
                            $warnings[$warn] = $warn;
                        } else {
                            $warn = "I cannot find record in {$entity['entity_name']} with $campo_chiave='{$insert[$campo_chiave]}'.";
                            $warnings[$warn] = $warn;
                            if ($import_data['action_on_data_present'] == 4) {
                                if ($import_data['use_apilib'] == 1) {
                                    $this->apilib->create($entity['entity_name'], $insert);
                                } else {
                                    $this->db->insert($entity['entity_name'], $insert);
                                }
                            }
                        }
                        //$this->apilib->edit($entity['entity_name'], $insert);
                        //$updated = $this->db->affected_rows();
                        $count += 1;
                    } else { //Metodo di importazione: INSERT
                        if ($import_data['use_apilib'] == 1) {
                            if ($this->apilib->create($entity['entity_name'], $insert)) {
                                $count++;
                            }
                        } else {
                            $this->db->insert($entity['entity_name'], $insert);
                            //debug($this->db->last_query(), true);
                            $count++;
                        }
                    }
                }

                $riga_id++;
            }

            fclose($handle);

            //debug($already_found, true);

            if (!empty($errors)) {
                echo json_encode(array('status' => 0, 'txt' => implode('<br />', $errors)));
                die();
            }

            if ($test) {
                if ($this->db->trans_status() === FALSE) {
                    echo json_encode(array('status' => 0, 'txt' => 'Import operation cannot be executed without errors.'));
                } else {

                    if (!empty($warnings)) {
                        echo json_encode(array('status' => 1, 'txt' => 'Import operation can be executed without errors. But...<br /><br />' . implode('<br />', $warnings)));
                    } else {
                        echo json_encode(array('status' => 1, 'txt' => 'Import operation can be executed without errors.'));
                    }
                }
                $this->db->trans_rollback();
            } else {
                $this->db->trans_complete();
                $this->session->set_flashdata(SESS_IMPORT_COUNT, $count);
                $this->session->set_flashdata(SESS_IMPORT_WARNINGS, $warnings);

                // Save mappings
                if (!empty($data['save_mapping']) && $data['save_mapping'] == 1) {
                    $mapping['importer_mappings_name'] = $data['importer_mappings_name'];
                    $json['ref_fields'] = $data['ref_fields'];
                    $json['csv_fields'] = $data['csv_fields'];
                    $json['field_key'] = $data['csv_fields'][$data['unique_key']];
                    $mapping['importer_mappings_json'] = json_encode($json);
                    $mapping['importer_mappings_entity_id'] = $import_data['entity_id'];
                    $this->db->insert('importer_mappings', $mapping);
                }

                echo json_encode(array('status' => 1, 'txt' => base_url('importer/import/import_return')));
            }
        } else {
            die('Cannot open the CSV file.');
        }
    }
    public function get_fields_by_entity_name($entity_name)
    {
        echo json_encode($this->datab->get_entity_by_name($entity_name)['fields']);
    }
}
