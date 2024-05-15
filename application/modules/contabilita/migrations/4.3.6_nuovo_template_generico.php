<?php

//Verifico se esiste il template generico
$exists = $this->db->query("SELECT * FROM documenti_contabilita_template_pdf WHERE documenti_contabilita_template_pdf_nome = 'Generico'")->row_array();

$contenuto_tpl = file_get_contents(APPPATH . 'modules/contabilita/assets/uploads/generico.php');

if ($exists) {//Se si, lo modifico col nuovo html
    $template_id = $exists['documenti_contabilita_template_pdf_id'];
    $this->apilib->edit('documenti_contabilita_template_pdf', $template_id, [
        'documenti_contabilita_template_pdf_html' => $contenuto_tpl,
    ]);
} else {
    //Se no, lo inserisco e lo marco come default
    $this->apilib->create('documenti_contabilita_template_pdf', [
        'documenti_contabilita_template_pdf_nome' => 'Generico',
        'documenti_contabilita_template_pdf_html' => $contenuto_tpl,
        'documenti_contabilita_template_pdf_default' => 1,
    ]);
}


