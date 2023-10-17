<?php

// Integrato il campo Template default nei template della contabilita. Questa migration setta il primo template (in teoria il generico base) come default
$template = $this->db->query("SELECT * FROM documenti_contabilita_template_pdf ORDER BY documenti_contabilita_template_pdf_id ASC LIMIT 1")->row_array();

if (count($template) > 0) {
    $this->db->query("UPDATE documenti_contabilita_template_pdf SET documenti_contabilita_template_pdf_default = '".DB_BOOL_TRUE."' WHERE documenti_contabilita_template_pdf_id = '{$template['documenti_contabilita_template_pdf_id']}'");
}

$this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_rif_documento_id = NULL WHERE documenti_contabilita_rif_documento_id = ''");
