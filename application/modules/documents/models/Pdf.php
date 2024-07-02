<?php
    class Pdf extends CI_Model {
        public function generate($data, $dst_file = null) {
            require_once APPPATH . 'modules/documents/vendor/autoload.php';
            
            $tpl_protocollo = $this->apilib->view('documents_templates', $data['documents_template_protocollo']);
            
            $pdf = new mikehaertl\pdftk\Pdf('uploads/' . $tpl_protocollo['documents_templates_file']);
            
            foreach ($data as $field_name => $value) {
                if (!is_array($value)) {
                    if (!empty($value)) {
                        $value = html_entity_decode(strip_tags($value));
                        
                        if ($this->checkmydate($value)) {
                            $data[$field_name] = dateFormat($value);
                        } else {
                            $data[$field_name] = $value;
                        }
                    } else {
                        $data[$field_name] = '';
                    }
                }
            }
            
            $pdf->fillForm($data)->flatten()->needAppearances();
            
            if ($dst_file) {
                $pdf->saveAs($dst_file);
                $dst_file_path = $dst_file;
            } else {
                $pdf->execute();
                $dst_file_path = (string) $pdf->getTmpFile();
            }
            
            return $dst_file_path;
        }
        
        private function checkmydate($date)
        {
            if (!is_array($date)) {
                $tempDate = explode('-', (string) $date);
                if (isset($tempDate[1]) && isset($tempDate[2])) {
                    return checkdate((int) $tempDate[1], (int) $tempDate[2], (int) $tempDate[0]);
                } else {
                    return false;
                }
            } else {
                return false;
            }
        }
    }