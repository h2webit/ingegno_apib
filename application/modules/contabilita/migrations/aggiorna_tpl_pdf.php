<?php
echo_log('debug', 'cerco i file tpl nella cartella contabilita/templates<br/>');
$files = glob(APPPATH . 'modules/contabilita/templates/*.tpl');

if (!empty($files)) {
    foreach ($files as $file) {
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $content = file_get_contents($file);
        
        $filename_exp = explode('_', $filename);
        
        $nome_tpl = $filename_exp[1];
        
        echo_log('debug', 'cerco tpl su db: ' . $nome_tpl . '<br/>');
        $tpl_db = $this->db->get_where('documenti_contabilita_template_pdf', [
            'documenti_contabilita_template_pdf_nome' => $nome_tpl,
            'documenti_contabilita_template_pdf_master' => DB_BOOL_TRUE,
        ])->row_array();
        
        if (empty($tpl_db)) {
            echo_log('debug', 'tpl non trovato, creo: ' . $nome_tpl . '<br/>');
            $this->apilib->create('documenti_contabilita_template_pdf', [
                'documenti_contabilita_template_pdf_nome' => $nome_tpl,
                'documenti_contabilita_template_pdf_master' => DB_BOOL_TRUE,
                'documenti_contabilita_template_pdf_html' => $content
            ]);
        } else {
            echo_log('debug', 'aggiorno tpl: ' . $nome_tpl . '<br/>');
            $this->apilib->edit('documenti_contabilita_template_pdf', $tpl_db['documenti_contabilita_template_pdf_id'], [
                'documenti_contabilita_template_pdf_html' => $content
            ]);
        }
    }
    
    echo_log('debug', 'template(s) aggiornati<br/>');
} else {
    echo_log('debug', 'nessun template da aggiornare trovato<br/>');
}