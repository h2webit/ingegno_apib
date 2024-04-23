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
    /************************** CONTEGGI GLOBALI ***********************************/

    
}