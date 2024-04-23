<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

class Turni extends MY_Controller
{
    
    public function __construct()
    {
        parent::__construct();

        header('Access-Control-Allow-Origin: *');
        @header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}"); //X-Requested-With
        
        $this->load->model('timbrature');
    }
    
    public function modifico_turni()
    {
        $turni_creati = array();
        $post = $this->input->post('turni');
        $oggetti = json_decode($post, true);
        $dipendenti_array = array();
        $templates = $this->db->query("SELECT * FROM turni_di_lavoro_template")->result_array();
        // mi creo un array con le singole date, così da cancellare i valori vecchi
        foreach ($oggetti as $oggetto) {

            $dipendente = $oggetto["dipendente"];
            $data_giorno = $oggetto["data"] . "-" . $oggetto["giorno"];
            $valore = $oggetto["valore"];
            $reparto = $oggetto["reparto"];
            
            $turni_creati[] = $this->createTurno($dipendente, $data_giorno, $valore, $reparto, $templates, $turni_creati);
        }
        /*debug($dipendenti_array);
        // per ogni dipendente cancello i turni vecchi e creo i nuovi
        foreach ($dipendenti_array as $dipendente => $array) {
            $valori_stampati = array();

            foreach ($array as $elemento) {
                $data_giorno = $elemento["data_giorno"];

                if (!in_array($data_giorno, $valori_stampati)) {
                    $valori_stampati[] = $data_giorno;
                    debug("elimino");
                    //$this->deleteTurni($dipendente, $data_giorno);
                }

                if (!empty($elemento['valore'])) {
                    $this->createTurno($dipendente, $data_giorno, $elemento['valore'], $elemento['reparto'], $templates);
                }
            }
        }*/

        echo json_encode(array('status' => 4, 'txt' => 'Turni salvati correttamente.'));
    }
    public function salva_csv()
    {

        if (isset($_POST['csvContent'])) {
        $csvContent = $_POST['csvContent'];
        $destination_folder = FCPATH . "uploads/modulo-hr";
        if (!file_exists($destination_folder)) {
            mkdir($destination_folder, 0777, true);
        }

        file_put_contents($destination_folder."/turni.csv", $csvContent);
            echo 'File salvato con successo!';
        } else {
            echo 'Dati CSV non presenti nella richiesta.';
        }
    }
    public function esporta_turni()
    {
        $csvFilePath = FCPATH . "uploads/modulo-hr/turni.csv"; // Specifica il percorso del file CSV
        $outputFilePath = FCPATH . "uploads/modulo-hr/turni.xls"; // Specifica il percorso in cui salvare il file XLS

        /// Carica il file CSV
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();
        //mi prendo i template di lavoro
        $templates = $this->db->query("SELECT * FROM turni_di_lavoro_template")->result_array();
        // Creo un array associativo usando l'id del turno come chiave
        $turni_di_lavoro_templates = array();
        foreach ($templates as $template) {
            $turni_di_lavoro_templates[$template['turni_di_lavoro_template_id']] = $template;
        }

        $row = 1;
        if (($handle = fopen($csvFilePath, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $col = 1;
                foreach ($data as $cellData) {
                    $cellData = strip_tags($cellData); //tolgo il tag a

                    $nome = '';
                    if (preg_match('/^(\d+)(;\d+)*$/', $cellData)) {
                        $numbers = explode(';', $cellData);
                        $names = [];
                    
                        foreach ($numbers as $number) {
                            if (is_numeric($number)) {
                                // Esegui la chiamata  per ottenere l'orario corrispondente
                                $template = isset($turni_di_lavoro_templates[$number]) ? $turni_di_lavoro_templates[$number] : null;
                                $nome = $this->getTurnoDaTemplate($template);
                                $names[] = $nome;
                            } else {
                                $names[] = $number;
                            }
                        }
                    
                        $combinedNames = implode('; ', $names);
                    
                        // Imposta i nomi combinati nella cella corrispondente nel foglio di lavoro XLS
                        $worksheet->setCellValueByColumnAndRow($col, $row, $combinedNames);
                    }else if ($worksheet->getCellByColumnAndRow($col, 1)->getValue() === 'Reparto') {
                        $names = explode(';', $cellData);
                        $combinedNames = implode("\n", $names);
        
                        // Imposta i nomi combinati nella cella corrispondente nel foglio di lavoro XLS
                        $cell = $worksheet->getCellByColumnAndRow($col, $row);
                        $cell->setValue($combinedNames);
                        $cell->getStyle()->getAlignment()->setWrapText(true);
                    } 
                    else {
                        $worksheet->setCellValueByColumnAndRow($col, $row, $cellData);

                    }
                    $col++;
                }
                $row++;
            }
            fclose($handle);
        }
        // Imposta lo stile per le celle dell'intestazione che contengono la parola "(D)"
        $headerStyle = [
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FF0000']],
        ];
        $headerRowCount = 1;

        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        // Trova l'ultima riga con un valore nella colonna 1
        $lastRowWithValue = $worksheet->getHighestDataRow();


        // Applica lo stile alle colonne corrispondenti all'intestazione che contiene "(D)"
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $headerValue = $worksheet->getCellByColumnAndRow($col, $headerRowCount)->getValue();
            if (strpos($headerValue, '(D)') !== false) {
                $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $columnRange = $columnLetter . '1:' . $columnLetter . $lastRowWithValue;
                $worksheet->getStyle($columnRange)->applyFromArray($headerStyle);
            }
        }

        // Salva il file XLS
        $writer = new Xls($spreadsheet);
        $writer->save($outputFilePath);

        // Imposta le intestazioni HTTP per il download del file
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="turni.xls"');
        header('Cache-Control: max-age=0');

        // Invia il file XLS al browser
        readfile($outputFilePath);
        if (file_exists($outputFilePath)) {
            unlink($outputFilePath); // Cancella il file xls
        }
        if (file_exists($csvFilePath)) {
            unlink($csvFilePath); // Cancella il file CSV
        }
        exit;
    }

    /*private function deleteTurni($dipendente, $data_giorno)
    {
        $this->db->where("turni_di_lavoro_dipendente", $dipendente);
        $this->db->where("DATE(turni_di_lavoro_data_inizio)", $data_giorno);
        $turni = $this->db->get('turni_di_lavoro')->result_array();

        if (!empty($turni)) {
            foreach ($turni as $turno) {
                $this->apilib->delete('turni_di_lavoro', $turno['turni_di_lavoro_id']);
            }
        }
    }*/

    private function createTurno($dipendente, $data_giorno, $valore, $reparto, $templates, $turni_creati)
    {
        $this->db->where("turni_di_lavoro_dipendente", $dipendente);
        $this->db->where("turni_di_lavoro_reparto", $reparto);
        $this->db->where("DATE(turni_di_lavoro_data_inizio)", $data_giorno);
        $turni = $this->db->get('turni_di_lavoro')->result_array();

        if (!empty($turni)) {
            foreach ($turni as $turno) {
                if (!in_array($turno['turni_di_lavoro_id'], $turni_creati)) {
                    $this->apilib->delete('turni_di_lavoro', $turno['turni_di_lavoro_id']);
                }
            }
        }

        $insert['turni_di_lavoro_data_inizio'] = $data_giorno;

        //vedo se il turno finisce il giorno dopo, metto la data fine come gg dopo
        $indice = array_search($valore, array_column($templates, 'turni_di_lavoro_template_id'));
        $template = $templates[$indice];
        if(strtotime($template['turni_di_lavoro_template_alle']) > strtotime($template['turni_di_lavoro_template_dalle'])){
            $insert['turni_di_lavoro_data_fine'] = $data_giorno;
        } else {
            $insert['turni_di_lavoro_data_fine'] = (new DateTime($data_giorno))->add(new DateInterval('P1D'))->format('Y-m-d');
        }
        $insert['turni_di_lavoro_dipendente'] = $dipendente;
        $insert['turni_di_lavoro_template'] = $valore;
        $insert['turni_di_lavoro_ora_inizio'] = $template['turni_di_lavoro_template_dalle'];
        $insert['turni_di_lavoro_ora_fine'] = $template['turni_di_lavoro_template_alle'];
        $insert['turni_di_lavoro_reparto'] = $reparto;

        $insert['turni_di_lavoro_giorno'] = date('N', strtotime($insert['turni_di_lavoro_data_inizio'])); // salvo per il timbra entrata

        return $this->apilib->create('turni_di_lavoro', $insert, false);
    }
    private function getTurnoDaTemplate($template) {

        if (!empty($template)) {
            $dalle = explode(':', $template['turni_di_lavoro_template_dalle']);
            $alle = explode(':', $template['turni_di_lavoro_template_alle']);
    
            $ora_inizio = intval($dalle[0]);
            $minuti_inizio = intval($dalle[1]);
    
            $ora_fine = intval($alle[0]);
            $minuti_fine = intval($alle[1]);
    
            $output = $ora_inizio . ($minuti_inizio !== 0 ? ':' . sprintf('%02d', $minuti_inizio) : '') . '-' . $ora_fine . ($minuti_fine !== 0 ? ':' . sprintf('%02d', $minuti_fine) : '');
    
            return $output;
        }
        return "nd";

    }
    public function creazione_massiva()
    {
        $dipendenti = $this->input->post('dipendenti');
        $giorni = $this->input->post('giorni');
        $oraInizio = $this->input->post('turni_di_lavoro_ora_inizio');
        $oraFine = $this->input->post('turni_di_lavoro_ora_fine');
        $pausa = $this->input->post('pausa');
        $oraInizioNotturno = $this->input->post('turni_di_lavoro_ora_inizio_notturno');
        $oraFineNotturno = $this->input->post('turni_di_lavoro_ora_fine_notturno');

        try {
            // Se non ho dipendenti li creo per tutti quanti
            if(empty($dipendenti)) {
                $allDipendenti = $this->apilib->search('dipendenti', ['dipendenti_attivo' => DB_BOOL_TRUE]);

                if(!empty($allDipendenti)) {
                    foreach ($allDipendenti as $dip) {
                        foreach ($giorni as $giorno) {
                            // Inserisci i dati nel database per ciascun dipendente e giorno
                            $data = array(
                                'turni_di_lavoro_dipendente' => $dip['dipendenti_id'],
                                'turni_di_lavoro_giorno' => $giorno,
                                'turni_di_lavoro_ora_inizio' => $oraInizio,
                                'turni_di_lavoro_ora_fine' => $oraFine,
                                'turni_di_lavoro_notturno_inizio' => $oraInizioNotturno,
                                'turni_di_lavoro_notturno_fine' => $oraFineNotturno,
                                'turni_di_lavoro_data_inizio' => date('Y-m-d 00:00:00', strtotime('-1 day')),
                                'turni_di_lavoro_pausa' => $pausa
                            );
        
                            // Esegui l'inserimento nel database
                            $this->apilib->create('turni_di_lavoro', $data);
                        }
                    }
                }

            } else {
                // Li creo solo per i dipendenti selezionati
                foreach ($dipendenti as $dipendente) {
                    foreach ($giorni as $giorno) {
                        // Inserisci i dati nel database per ciascun dipendente e giorno
                        $data = array(
                            'turni_di_lavoro_dipendente' => $dipendente,
                            'turni_di_lavoro_giorno' => $giorno,
                            'turni_di_lavoro_ora_inizio' => $oraInizio,
                            'turni_di_lavoro_ora_fine' => $oraFine,
                            'turni_di_lavoro_notturno_inizio' => $oraInizioNotturno,
                            'turni_di_lavoro_notturno_fine' => $oraFineNotturno,
                            'turni_di_lavoro_data_inizio' => date('Y-m-d 00:00:00', strtotime('-1 day')),
                            'turni_di_lavoro_pausa' => $pausa
                        );
    
                        // Esegui l'inserimento nel database
                        $this->apilib->create('turni_di_lavoro', $data);
                    }
                }
            }

            echo json_encode(array('success' => true, 'txt' => 'Turni salvati correttamente.'));
        } catch (Exception $e) {
            // Gestisci l'errore e restituisci una risposta JSON di errore
            echo json_encode(array('success' => false, 'txt' => 'Si è verificato un errore durante il salvataggio dei turni.'));
        }
    }


    
}