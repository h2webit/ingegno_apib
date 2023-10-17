<?php

// Spostamento file fisici nelle nuove directory della contabilita

// ZIP FI Creati per invio allo sdi

$documenti = $this->db->query("SELECT * FROM documenti_contabilita WHERE documenti_contabilita_nome_zip_sdi IS NOT NULL AND documenti_contabilita_nome_zip_sdi <> ''")->result_array();

foreach ($documenti as $documento) {
    log_message('debug', "Trovato zip: " . $documento['documenti_contabilita_nome_zip_sdi'] . "<br />");
    if (file_exists(FCPATH . "uploads/" . $documento['documenti_contabilita_nome_zip_sdi'])) {
        log_message('debug', "... sposto nella nuova directory<br />");

        $source_file = FCPATH . "uploads/" . $documento['documenti_contabilita_nome_zip_sdi'];

        $physicalDir = FCPATH . "uploads/modules_files/contabilita/zip_inviati";
        if (!is_dir($physicalDir)) {
            mkdir($physicalDir, 0755, true);
        }

        rename($source_file, $physicalDir . "/" . $documento['documenti_contabilita_nome_zip_sdi']);
    }
}


// Spese

$spese_allegati = $this->db->query("SELECT * FROM spese_allegati")->result_array();

foreach ($spese_allegati as $allegato) {

    if (file_exists(FCPATH . "uploads/" . $allegato['spese_allegati_file'])) {
        log_message('debug', "... sposto nella nuova directory<br />");

        $source_file = FCPATH . "uploads/" . $allegato['spese_allegati_file'];

        $physicalDir = FCPATH . "uploads/modules_files/contabilita/spese";
        if (!is_dir($physicalDir)) {
            mkdir($physicalDir, 0755, true);
        }

        rename($source_file, $physicalDir . "/" . $allegato['spese_allegati_file']);
    }
}