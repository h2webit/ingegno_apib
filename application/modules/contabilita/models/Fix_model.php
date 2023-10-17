<?php
    class Fix_model extends CI_Model {
        public function rif_docs() {
            $sql = <<<EOT
            SELECT * FROM documenti_contabilita
            WHERE (documenti_contabilita_rif_documento_id IS NOT NULL AND documenti_contabilita_rif_documento_id <> '')
            AND documenti_contabilita_rif_documento_id IN (SELECT documenti_contabilita_id FROM documenti_contabilita)
            AND documenti_contabilita_id NOT IN (SELECT documenti_contabilita_id FROM rel_doc_contabilita_rif_documenti)
            AND documenti_contabilita_id NOT IN (SELECT rel_doc_contabilita_rif_documenti_padre FROM rel_doc_contabilita_rif_documenti)
            EOT;

            $documenti_da_migrare = $this->db->query($sql)->result_array();

            if (empty($documenti_da_migrare)) return "Nessun documento da migrare.";

            $t = count($documenti_da_migrare);
            $c = 0;
            foreach ($documenti_da_migrare as $doc) {
                $rif_doc_id = $doc['documenti_contabilita_rif_documento_id'];

                $rif_doc = [
                    'documenti_contabilita_id' => $rif_doc_id,
                    'rel_doc_contabilita_rif_documenti_padre' => $doc['documenti_contabilita_id'],
                ];

                try {
                    $this->db->insert('rel_doc_contabilita_rif_documenti', $rif_doc);
                } catch (Exception $e) {
                    return $e->getMessage();
                }

                $c++;
                progress($c, $t);
            }
        }
    }
