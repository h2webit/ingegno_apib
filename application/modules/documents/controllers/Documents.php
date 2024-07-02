<?php
    require_once __DIR__.'/../vendor/autoload.php';
    
    use PhpOffice\PhpSpreadsheet\IOFactory;
    use PhpOffice\PhpSpreadsheet\PhpSpreadsheet;
    use PhpOffice\PhpWord\TemplateProcessor;
    
    class Documents extends MY_Controller {
        public function __construct() {
            parent::__construct();
        
            if (!$this->auth->check()) show_404();
        }
        
        public function download_file($document_id = null){
            if (!$document_id) die('No data');
            
            $document = $this->apilib->searchFirst('documents', ['documents_id' => $document_id]);
            
            if (empty($document)) die('No document found');
    
            $file = $document['documents_file'];
            $filename = $document['documents_filename'];
            $mime_type = get_mime_by_extension($file);
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
    
            $full_file_path = FCPATH . 'uploads/' . $file;
            
            header('Content-Type: '.$mime_type);
            header('Content-Length: ' . filesize($full_file_path));
            header('Content-disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: public, must-revalidate, max-age=0');
    
            readfile($full_file_path);
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
        
        public function download_template($document_id = null) {
            if (!$document_id) die('No data');
    
            $document = $this->apilib->searchFirst('documents', ['documents_id' => $document_id]);
    
            if (empty($document)) die('No document found');
            
            if (!empty($document['documents_template_protocollo'])) {
                $tpl_protocollo = $this->apilib->view('documents_templates', $document['documents_template_protocollo']);
                
                $document = array_merge($document, $tpl_protocollo);
            }
            
            $file = $document['documents_templates_file'];
            
            $filename = $document['documents_templates_filename'];
//            $word_to_pdf = $document['documents_templates_doc_to_pdf'];
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            $mime_type = get_mime_by_extension($file);
            
            $full_file_path = FCPATH . 'uploads/' . $file;
            
            $data = $document;
            
            if (!empty($document['documents_customer'])) {
                $customer = $this->apilib->view('customers', $document['documents_customer']);
        
                $data = array_merge($data, $customer);
            }
    
            if (!empty($document['documents_project'])) {
                $project = $this->apilib->view('projects', $document['documents_project']);
        
                $data = array_merge($data, $project);
            }
            
            if ($document['documents_seleziona_anagrafica'] == DB_BOOL_TRUE && !empty($customer)) {
                $data['documents_mittente_destinatario'] = <<<EOT
                {$customer['customers_full_name']}
                
                {$customer['customers_address']}
                {$customer['customers_city']}
                {$customer['customers_zip_code']} {$customer['customers_province']}
                EOT;
            }
    
            if ( ($document['documents_templates_auto_compilation'] == DB_BOOL_TRUE || $document['documents_protocollo'] == DB_BOOL_TRUE) && !empty($data) && file_exists($full_file_path)) {
                if ($ext == 'pdf') {
                    $pdf = new mikehaertl\pdftk\Pdf('uploads/' . $file);
                    
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
                    
                    $pdf->fillForm($data)->flatten()->needAppearances()->execute();
            
                    $full_file_path = (string) $pdf->getTmpFile();
                } else if (stripos($ext, 'xls') !== false) {
                    $tmp_file = stream_get_meta_data(tmpfile())['uri'];
    
                    $inputFileType = IOFactory::identify($full_file_path);
                    $reader = IOFactory::createReader($inputFileType);
                    $spreadsheet = $reader->load($full_file_path);
            
                    $sheet = $spreadsheet->setActiveSheetIndex(0);
            
                    $new_data = [];
                    foreach ($data as $field_name => $value) {
                        if ($this->checkmydate($value)) {
                            $new_data['{' . $field_name . '}'] = dateFormat($value);
                        } else {
                            $new_data['{' . $field_name . '}'] = $value;
                        }
                    }
    
                    foreach ($sheet->getRowIterator() as $row) {
                        $cellIterator = $row->getCellIterator();
                        $cellIterator->setIterateOnlyExistingCells(FALSE);
        
                        foreach ($cellIterator as $cell) {
                            $valore_cella_orig = $cell->getValue();
            
                            $valore_cella = strtr($valore_cella_orig, $new_data);
            
                            $coordinate = $cell->getCoordinate();
                            
                            $sheet->setCellValue($coordinate, $valore_cella);
                        }
                    }
    
                    $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
                    $writer->setPreCalculateFormulas(true);
                    $writer->save($tmp_file);
                    
                    $full_file_path = $tmp_file;
                } else if (stripos($ext, 'doc') !== false) {
                    $tmp_file = stream_get_meta_data(tmpfile())['uri'];
                    $templateProcessor = new TemplateProcessor($full_file_path);
                    
                    foreach ($data as $field_name => $value) {
                        if (!is_array($value)) {
                            if (!empty($value)) {
                                $value = html_entity_decode(strip_tags($value));
                                
                                if ($this->checkmydate($value)) {
                                    $templateProcessor->setValue($field_name, dateFormat($value));
                                } else {
                                    $templateProcessor->setValue($field_name, $value);
                                }
                            } else {
                                $templateProcessor->setValue($field_name, '');
                            }
                        }
                    }
                    
                    $templateProcessor->saveAs($tmp_file);
                    $full_file_path = $tmp_file;
    
//                    if ($word_to_pdf == DB_BOOL_TRUE) {
//                        $pdf_tmp_file = stream_get_meta_data(tmpfile())['uri'];
//
//                        \PhpOffice\PhpWord\Settings::setPdfRendererName('MPDF');
//                        \PhpOffice\PhpWord\Settings::setPdfRendererPath(FCPATH . '/vendor/mpdf/mpdf');
//                        $phpWord = \PhpOffice\PhpWord\IOFactory::load($tmp_file);
//                        $xmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
//                        $xmlWriter->save($pdf_tmp_file);
//
//                        $filename = str_ireplace(['docx', 'doc'], 'pdf', $filename);
//                        $mime_type = 'application/pdf';
//                        $full_file_path = $pdf_tmp_file;
//                    }
                }
            }
            
            header('Content-Type: '.$mime_type);
            header('Content-Length: ' . filesize($full_file_path));
            header('Content-disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: public, must-revalidate, max-age=0');
    
            readfile($full_file_path);
        }
        
        public function invio_mail_protocollo ($documento_id = null)
        {
            $post = $this->input->post();
            
            if (empty($documento_id) && empty($post['ids'])) {
                e_json(['status' => 0, 'txt' => 'Documento Id non passato']);
                return false;
            }
            
            $ids = implode(',', json_decode($post['ids'], true) ?? [$documento_id]);
            
            $documenti = $this->apilib->search('documents', ["documents_id IN ({$ids})", 'documents_protocollo' => DB_BOOL_TRUE]);
            
            if (empty($documenti)) {
                e_json(['status' => 0, 'txt' => 'Nessun documento trovato']);
                return false;
            }
            
            $in_coda = [];
            foreach ($documenti as $documento) {
                if (empty($documento['customers_email'])) {
                    $in_coda[] = "mail non impostata su cliente {$documento['customers_full_name']} per il documento {$documento['documents_code']}";
                    continue;
                } else {
                    if (!filter_var($documento['customers_email'], FILTER_VALIDATE_EMAIL)) {
                        $in_coda[] = "formato email non conforme del cliente {$documento['customers_full_name']} per il documento {$documento['documents_code']}";
                        continue;
                    }
                }
                
                if ($documento['documents_mail_sent'] == DB_BOOL_TRUE) {
                    $in_coda[] = "Mail già inviata a {$documento['customers_full_name']} per il documento {$documento['documents_code']}";
                    continue;
                }
                
                $email = $documento['customers_email'];
                if ($this->auth->get('users_id') == '1') {
                    $email = 'michael@h2web.it';
                }
                
                $attachments = [];
                if (!empty($documento['documents_file']) && file_exists(FCPATH . 'uploads/' . $documento['documents_file'])) {
                    $fileinfo = pathinfo($documento['documents_file']);
                    
                    $nice_filename = ucwords(str_ireplace('_', ' ', $fileinfo['filename']));
                    
                    $attachments = [['file_name' => $nice_filename, 'file' => FCPATH . 'uploads/' . $documento['documents_file']]];
                }
                
                $mail_data = array_merge($documento, [
                    'documents_date' => dateFormat($documento['documents_date']),
                ]);
                
                $this->mail_model->send($email, 'invio_mail_protocollo', 'it', $mail_data, [], $attachments);
                
                $this->db->where('documents_id', $documento['documents_id'])->update('documents', ['documents_mail_sent' => DB_BOOL_TRUE]);
                
                $this->apilib->clearCache();
                
                $in_coda[] = "Protocollo {$documento['documents_code']} messo in coda per l'invio a {$documento['customers_full_name']}";
            }
            
            e_json(['status' => 4, 'txt' => implode(PHP_EOL, $in_coda)]);
        }
    }