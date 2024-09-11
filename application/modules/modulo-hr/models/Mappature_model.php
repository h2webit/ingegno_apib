<?php

use setasign\Fpdi\Fpdi;
use Spatie\PdfToText\Pdf;

class Mappature_model extends CI_Model
{
    public function __construct ()
    {
        require_once APPPATH . 'modules/modulo-hr/third_party/vendor/autoload.php';
        
        
    }
    
    public $scope = 'CRM';
    
    public function scope ($tipo)
    {
        $this->scope = $tipo;
    }
    
    public function estrai_saldi_da_cedolino ($pdf_path, $mappatura)
    {
        if (!file_exists($pdf_path)) {
            return false;
        }
        
        if (empty($mappatura)) {
            return false;
        }
        
        if (!is_array($mappatura)) {
            $mappatura = $this->apilib->searchFirst('dipendenti_mappature_pdf', ['dipendenti_mappature_pdf_id' => $mappatura]);
        }
        
        if (empty($mappatura['dipendenti_mappature_pdf_json'])) {
            return false;
        }

        $coords = json_decode($mappatura['dipendenti_mappature_pdf_json'], true);
        
        // Get the actual PDF dimensions
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($pdf_path);
        $pageId = $pdf->importPage(1);
        $pageSize = $pdf->getTemplateSize($pageId);

        // Define the scale factors based on actual PDF dimensions
        $scale_x = $pageSize['width'] / 595;
        $scale_y = $pageSize['height'] / 842;

        $texts = [];

        foreach ($coords as $coord) {
            $coords_inizio = explode(',', $coord['coord_inizio']);
            $coords_fine = explode(',', $coord['coord_fine']);

            // Scale the coordinates
            $x1 = $coords_inizio[0] * $scale_x;
            $y1 = $coords_inizio[1] * $scale_y;
            $x2 = $coords_fine[0] * $scale_x;
            $y2 = $coords_fine[1] * $scale_y;

            // We're not flipping Y coordinates here anymore

            // Ensure y2 is always greater than y1 (bottom is greater than top in original coordinates)
            if ($y2 < $y1) {
                $temp = $y1;
                $y1 = $y2;
                $y2 = $temp;
            }

            //echo "x1: $x1, y1: $y1, x2: $x2, y2: $y2\n";

            // Extract the text from the specified rectangular area
            $text = $this->extractTextFromRectangle($pdf_path, $x1, $y1, $x2, $y2);

            //debug($text, true);
            $texts[] = $text;
        }
        return $texts;
    }

    public function extractTextFromRectangle($pdfPath, $x1, $y1, $x2, $y2)
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($pdfPath);

        $pageId = $pdf->importPage(1);
        $pageSize = $pdf->getTemplateSize($pageId);

        // echo "Larghezza pagina: " . $pageSize['width'] . "\n";
        // echo "Altezza pagina: " . $pageSize['height'] . "\n";

        // Calculate the width and height of the rectangle
        $width = $x2 - $x1;
        $height = $y2 - $y1;  // Note: y2 is now greater than y1

        // echo "Larghezza: " . $width . "\n";
        // echo "Altezza: " . $height . "\n";

        // Create a new PDF with only the rectangle area
        $pdf->AddPage('L', array($width, $height));

        // Use the imported page inside the specified rectangle
        // We need to adjust the y-coordinate here
        $pdf->useTemplate($pageId, -$x1, -$y1, $pageSize['width'], $pageSize['height']);

        // Save the temporary PDF
        $tempPdfPath = 'temp.pdf';
        $pdf->Output($tempPdfPath, 'F');

        // Extract text from the temporary PDF
        $extractedText = Pdf::getText($tempPdfPath);

        // Clean up temporary file
        // unlink('tempbck.pdf');
        // copy($tempPdfPath, 'tempbck.pdf');
        unlink($tempPdfPath);

        return $extractedText;
    }
}