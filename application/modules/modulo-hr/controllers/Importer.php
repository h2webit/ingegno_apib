<?php
    class Importer extends MY_Controller {
        public function __construct () {
            parent::__construct();
            
            set_log_scope('importer');
        }
        
        private function csv_to_array($file, $sep = ';') {
            $array = [];
            $handle = fopen($file, 'r');
            if ($handle) {
                //fgets($handle);
                // Read the first line of the CSV file to get the keys
                $keys = fgetcsv($handle, null, $sep);
                // Loop through the keys and modify them using the regular expression
                $keys = array_map(function ($key) use ($sep) {
                    $_key = strtolower(preg_replace('/[^a-zA-Z0-9' . $sep . '#]/', '_', $key));
                    
                    if (trim($_key, '_') !== '') {
                        $_key = trim($_key, '_');
                    }
                    
                    // Replace consecutive underscores with a single underscore
                    $_key = preg_replace('/_+/', '_', $_key);
                    
                    return $_key;
                }, $keys);
                
                $c = 0;
                // Loop through the remaining lines of the CSV file
                while (($row = fgetcsv($handle, null, $sep)) !== false) {
                    $c++;
                    // Create an associative array with the modified keys and values from the current line
                    $array[] = array_combine($keys, $row);
                }
                if (!feof($handle)) {
                    return false;
                }
                fclose($handle);
            }
            
            return $array;
        }
        
        public function saldi_ferie_permessi() {
            if (empty($_FILES['csv_saldi_ferie_permessi'])) throw new ApiException('Errore: file non caricato');
            
            $file = $_FILES['csv_saldi_ferie_permessi'];
            $_FILES = [];
            
            @file_put_contents(FCPATH . 'uploads/' . date('YmdHis') . '_lista_saldi_ferie_permessi_' . $file['name'], file_get_contents($file['tmp_name']));
            
            $rows = $this->csv_to_array($file['tmp_name']);
            
            $saldi_ferie_permessi = [];
            foreach ($rows as $row) {
                $cf_dipendente = strtoupper(trim($row['cf_dipendente']));
                
                $saldi_ferie_permessi[$cf_dipendente][] = $row;
            }
            
            $ratei_ferie_permessi_anno_map = array_key_value_map($this->db->get('ratei_ferie_permessi_anno')->result_array(), 'ratei_ferie_permessi_anno_value', 'ratei_ferie_permessi_anno_id');
            
            $t_saldi = count($saldi_ferie_permessi);
            $c_saldi = 0;
            foreach ($saldi_ferie_permessi as $cf_dipendente => $rows) {
                $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_codice_fiscale' => $cf_dipendente]);
                
                if (empty($dipendente)) {
                    echo_log('error', "dipendente con cf {$cf_dipendente} non trovato<br/>");
                    progress(++$c_saldi, $t_saldi, "IMPORT");
                    continue;
                }
                
                $saldo_ferie = $saldo_rol = $saldo_permessi = null;
                
                $mese = array_unique(array_key_map($rows, 'mese'))[0];
                $anno = array_unique(array_key_map($rows, 'anno'))[0];
                
                foreach ($rows as $row) {
                    switch($row['descrizione_rateo']) {
                        case 'Ferie':
                            $saldo_ferie = number_format(str_ireplace(',', '.', $row['totale_residuo']), 2, '.', '');
                            breaK;
                        case 'R.O.L.':
                            $saldo_rol = number_format(str_ireplace(',', '.', $row['totale_residuo']), 2, '.', '');
                            break;
                        case 'Ex-fest.':
                            $saldo_permessi = number_format(str_ireplace(',', '.', $row['totale_residuo']), 2, '.', '');
                            break;
                        default:
                            break;
                    }
                }
                
                $saldo_dipendente = $this->apilib->searchFirst('ratei_ferie_permessi', [
                    'ratei_ferie_permessi_dipendente' => $dipendente['dipendenti_id'],
                    'ratei_ferie_permessi_mese' => $mese,
                    'ratei_ferie_permessi_anno' => $ratei_ferie_permessi_anno_map[$anno],
                ]);
                
                if (empty($saldo_dipendente)) {
                    $this->apilib->create('ratei_ferie_permessi', [
                        'ratei_ferie_permessi_dipendente' => $dipendente['dipendenti_id'],
                        'ratei_ferie_permessi_mese' => $mese,
                        'ratei_ferie_permessi_anno' => $ratei_ferie_permessi_anno_map[$anno],
                        
                        'ratei_ferie_permessi_saldo_ferie' => $saldo_ferie,
                        'ratei_ferie_permessi_saldo_rol' => $saldo_rol,
                        'ratei_ferie_permessi_saldo_permessi' => $saldo_permessi,
                        
                        'ratei_ferie_permessi_aggiorna_saldi' => 1,
                    ]);
                    
                    echo_flush("inseriti saldi per {$cf_dipendente}: ferie: {$saldo_ferie}, rol: {$saldo_rol}, permessi: {$saldo_permessi}<br/>", '<br/>');
                } else {
                    $this->apilib->edit('ratei_ferie_permessi', $saldo_dipendente['ratei_ferie_permessi_id'], [
                        'ratei_ferie_permessi_saldo_ferie' => $saldo_ferie,
                        'ratei_ferie_permessi_saldo_rol' => $saldo_rol,
                        'ratei_ferie_permessi_saldo_permessi' => $saldo_permessi,
                        
                        'ratei_ferie_permessi_aggiorna_saldi' => 1,
                    ]);
                    
                    echo_flush("aggiornati saldi per {$cf_dipendente}: ferie: {$saldo_ferie}, rol: {$saldo_rol}, permessi: {$saldo_permessi}<br/>", '<br/>');
                }
                
                progress(++$c_saldi, $t_saldi, "IMPORT");
            }
        }
    }
