<?php

class Hrutility extends CI_Model
{

    public $scope = 'CRM';

    public function scope($tipo)
    {
        $this->scope = $tipo;
    }


    public function check_cf_in_pdf($cf, $file_full_path)
    {

        require_once APPPATH . 'modules/modulo-hr/third_party/vendor/autoload.php';

        $parser = new Smalot\PdfParser\Parser();

        try {
            $pdf = $parser->parseFile($file_full_path);
            $text = $pdf->getText();

            $stringToFind = trim($cf); // La stringa da cercare

            if (stripos($text, $stringToFind) !== false) {
                return true;
            } else {
                return false;
            }

        } catch (Exception $e) {
            echo "Si è verificato un errore: " . $e->getMessage();
        }


    }
    
    public function estrai_saldi_da_cedolino($file_cedolino, $id_mappatura = null) {
        $this->load->model('modulo-hr/mappature_model');
        
        $full_file_path = FCPATH . 'uploads/' . $file_cedolino['path_local'];
        
        // dump($full_file_path);
        
        if (!$id_mappatura && !empty($file_cedolino['dipendenti_id'])) {
            $dipendente = $this->apilib->view('dipendenti', $file_cedolino['dipendenti_id']);
        }
        
        $mappatura_where = [];
        
        if (!empty($dipendente['dipendenti_mappatura_pdf_cedolino'])) {
            $mappatura_where['dipendenti_mappature_pdf_id'] = $dipendente['dipendenti_mappatura_pdf_cedolino'];
        } else {
            $mappatura_where['dipendenti_mappature_pdf_default'] = 1;
        }
        
        $mappatura = $this->apilib->searchFirst('dipendenti_mappature_pdf', $mappatura_where);
        
        $dati_estratti = $this->mappature_model->estrai_dati_da_cedolino($full_file_path, $mappatura);
        
        $saldi_estratti = [];
        
        $coords = json_decode($mappatura['dipendenti_mappature_pdf_json'], true);
        
        foreach ($dati_estratti as $dato) {
            foreach ($coords as $mappatura) {
                $saldo = trim($dato);
                $label_mappatura = trim($mappatura['label']);
                if (stripos($saldo, $label_mappatura) !== false) {
                    $valore_saldo = trim(str_ireplace([$label_mappatura, PHP_EOL, ','], ['', '', '.'], $saldo));
        
                    $key = $mappatura['campo'] ?: $label_mappatura;
                    
                    $saldi_estratti[$key] = (float) $valore_saldo ?: 0;
                }
            }
        }
    
        return $saldi_estratti;
    }
    /************************** CONTEGGI GLOBALI ***********************************/

    
}