<?php
class Pdf extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }


    public function stampa($view = 'pdf_presenze')
    {
        //pdf custom o base del modulo
        if (file_exists(APPPATH . 'modules/modulo-hr/views/pdf/custom/pdf_presenze.php')) {
            $view_content = $this->load->view("modulo-hr/pdf/custom/{$view}", [], true);
        } else {
            $view_content = $this->load->view("modulo-hr/pdf/{$view}", [], true);
        }

        $pdf_content = $this->load->view("modulo-hr/pdf/pdf_plain_structure", ['html' => $view_content], true);

        $pdfFile = $this->layout->generate_pdf($pdf_content, "landscape", "", [], false, true, [
            'useMpdf' => true,
            'mpdfInit' => [
                'mode' => 'utf-8',
                'margin_left' => 5,
                'margin_right' => 5,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_header' => 20,
                'margin_footer' => 0,
                'orientation' => 'L'
            ]
        ]);

        $contents = file_get_contents($pdfFile, true);
        $pdf_b64 = base64_encode($contents);

        header('Content-Type: application/pdf');
        header('Content-disposition: inline; filename="Stampa_presenze_' . time() . '.pdf"');

        echo base64_decode($pdf_b64);
    }

    public function stampa_note_spese($view = 'pdf_note_spese')
    {
        //pdf custom o base del modulo
        if (file_exists(APPPATH . 'modules/modulo-hr/views/pdf/custom/pdf_note_spese.php')) {
            $view_content = $this->load->view("modulo-hr/pdf/custom/{$view}", [], true);
        } else {
            $view_content = $this->load->view("modulo-hr/pdf/{$view}", [], true);
        }

        $pdf_content = $this->load->view("modulo-hr/pdf/pdf_plain_structure", ['html' => $view_content], true);

        $pdfFile = $this->layout->generate_pdf($pdf_content, "landscape", "", [], false, true, [
            'useMpdf' => true,
            'mpdfInit' => [
                'mode' => 'utf-8',
                'margin_left' => 5,
                'margin_right' => 5,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_header' => 20,
                'margin_footer' => 0,
                'orientation' => 'L'
            ]
        ]);

        $contents = file_get_contents($pdfFile, true);
        $pdf_b64 = base64_encode($contents);

        header('Content-Type: application/pdf');
        header('Content-disposition: inline; filename="Stampa_note_spese_' . time() . '.pdf"');

        echo base64_decode($pdf_b64);
    }
}