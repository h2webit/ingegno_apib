<?php


class Fix extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();

        // Se non sono loggato allora semplicemente uccido la richiesta
        if ($this->auth->guest()) {
            set_status_header(401); // Unauthorized
            die('Non sei loggato nel sistema');
        }

        $this->load->model('contabilita/docs');

        $this->settings = $this->db->get('settings')->row_array();
        
    }

    public function migrate_spese_file() {
        $documenti = $this->db->query("SELECT * FROM documenti_contabilita WHERE documenti_contabilita_nome_zip_sdi IS NOT NULL AND documenti_contabilita_nome_zip_sdi <> ''")->result_array();
        $count = count($documenti);
        $c = 0;
        foreach ($documenti as $documento) {
            $c++;
            progress($c, $count, 'documenti');
            log_message('debug', "Trovato zip: " . $documento['documenti_contabilita_nome_zip_sdi'] . "<br />");
            if (file_exists(FCPATH . "uploads/" . $documento['documenti_contabilita_nome_zip_sdi'])) {
                log_message('debug', "... sposto nella nuova directory<br />");

                $source_file = FCPATH . "uploads/" . $documento['documenti_contabilita_nome_zip_sdi'];

                $physicalDir = FCPATH . "uploads/modules_files/contabilita/zip_inviati";
                if (!is_dir($physicalDir)) {
                    mkdir($physicalDir, 0755, true);
                }
                $destination_file = $physicalDir . "/" . $documento['documenti_contabilita_nome_zip_sdi'];
                 $path = pathinfo($destination_file);
                    if (!file_exists($path['dirname'])) {
                        mkdir($path['dirname'], 0777, true);
                    }

                rename($source_file, $destination_file);
                
            }
        }


        // Spese

        $spese_allegati = $this->db->query("SELECT * FROM spese_allegati")->result_array();
 $count = count($spese_allegati);
        $c = 0;
        foreach ($spese_allegati as $allegato) {
            $c++;
            progress($c, $count, 'spese');
            if (file_exists(FCPATH . "uploads/" . $allegato['spese_allegati_file'])) {
                log_message('debug', "... sposto nella nuova directory<br />");

                $source_file = FCPATH . "uploads/" . $allegato['spese_allegati_file'];

                $physicalDir = FCPATH . "uploads/modules_files/contabilita/spese";
                if (!is_dir($physicalDir)) {
                    mkdir($physicalDir, 0755, true);
                }
                $destination_file = $physicalDir . "/" . $allegato['spese_allegati_file'];
                 $path = pathinfo($destination_file);
                    if (!file_exists($path['dirname'])) {
                        mkdir($path['dirname'], 0777, true);
                    }

                rename($source_file, $destination_file);
            }
        }
    }


    // Migrazione da base64 a file fisici

    // documenti_contabilita_file_xml deve contenere il percorso del file xml fisico.
    // documenti_contabilita_file_pdf deve contenere il percorso del file pdf fisico.

    // Deprecati quindi i campi documenti_contabilita_file_preview e documenti_contabilita_file. Prima l'xml veniva salvato in b64 nel _file in caso di doc elettronico, mentre b64 del pdf sempre su _file se non lo era.
    public function migrate_b64($limit=200)
    {
        $documenti = $this->db->query("SELECT * FROM documenti_contabilita LEFT JOIN documenti_contabilita_tipo ON documenti_contabilita_tipo = documenti_contabilita_tipo_id WHERE CHAR_LENGTH(documenti_contabilita_file_preview) > 100 OR CHAR_LENGTH(documenti_contabilita_file) > 100  ORDER BY documenti_contabilita_id ASC LIMIT $limit")->result_array();

        foreach ($documenti as $documento) {
            echo "Elaboro il documento: ".$documento['documenti_contabilita_id']." tipo: ".$documento['documenti_contabilita_tipo']."<br />";

            // Questo in teoria è un documento elettronico, non mi baso sul booleano ma sulla combinazione.
            if (!empty($documento['documenti_contabilita_nome_file_xml']) && !empty($documento['documenti_contabilita_file_preview']) && !empty($documento['documenti_contabilita_file'])) {
                // Salvo xml
                if (base64_decode($documento['documenti_contabilita_file'], true)) {
                    echo "XML di: ".$documento['documenti_contabilita_id']." tipo: ".$documento['documenti_contabilita_tipo']."<br />";

                    $b64_xml_content = base64_decode($documento['documenti_contabilita_file']);
                    $xml_file_name = $documento['documenti_contabilita_nome_file_xml'];
                    $folder = "modules_files/contabilita/xml_generati";
                    $file_xml_path = $this->docs->salva_file_fisico($xml_file_name, $folder, $b64_xml_content);

                    if (file_exists(FCPATH."uploads/".$file_xml_path)) {
                        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_file_xml = '$file_xml_path' WHERE documenti_contabilita_id = '{$documento['documenti_contabilita_id']}'");
                        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_file = 'migrato su file $file_xml_path' WHERE documenti_contabilita_id = '{$documento['documenti_contabilita_id']}'");
                    }
                }

                // Salvo pdf cortesia
                if (base64_decode($documento['documenti_contabilita_file_preview'], true)) {
                    echo "PDF di: ".$documento['documenti_contabilita_id']." tipo: ".$documento['documenti_contabilita_tipo']."<br />";

                    $b64_pdf_content = base64_decode($documento['documenti_contabilita_file_preview']);
                    $pdf_file_name = $this->docs->generate_beautify_name($documento);

                    $folder = "modules_files/contabilita/pdf_cortesia";
                    $file_pdf_path = $this->docs->salva_file_fisico($pdf_file_name, $folder, $b64_pdf_content);

                    if (file_exists(FCPATH."uploads/".$file_pdf_path)) {
                        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_file_pdf = '$file_pdf_path' WHERE documenti_contabilita_id = '{$documento['documenti_contabilita_id']}'");
                        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_file_preview = 'migrato su file $file_pdf_path' WHERE documenti_contabilita_id = '{$documento['documenti_contabilita_id']}'");
                    }
                }
            }


            // In questa casistica non è un documento elettronico
            if (!empty($documento['documenti_contabilita_file_preview']) && empty($documento['documenti_contabilita_file'])) {
                // Salvo pdf cortesia
                if (base64_decode($documento['documenti_contabilita_file_preview'], true)) {
                    echo "PDF di: ".$documento['documenti_contabilita_id']." tipo: ".$documento['documenti_contabilita_tipo']."<br />";

                    $b64_pdf_content = base64_decode($documento['documenti_contabilita_file_preview']);
                    $pdf_file_name = $this->docs->generate_beautify_name($documento);

                    $folder = "modules_files/contabilita/pdf_cortesia";
                    $file_pdf_path = $this->docs->salva_file_fisico($pdf_file_name, $folder, $b64_pdf_content);

                    if (file_exists(FCPATH."uploads/".$file_pdf_path)) {
                        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_file_pdf = '$file_pdf_path' WHERE documenti_contabilita_id = '{$documento['documenti_contabilita_id']}'");
                        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_file_preview = 'migrato su file $file_pdf_path' WHERE documenti_contabilita_id = '{$documento['documenti_contabilita_id']}'");
                    }
                }
            }

            // Questo dovrebbe sistemare solo vecchi documenti
            if ($documento['documenti_contabilita_formato_elettronico'] == 0 && empty($documento['documenti_contabilita_nome_file_xml']) && !empty($documento['documenti_contabilita_file'])) {
                // Salvo pdf cortesiae
                if (base64_decode($documento['documenti_contabilita_file'], true)) {
                    echo "PDF di: ".$documento['documenti_contabilita_id']." tipo: ".$documento['documenti_contabilita_tipo']."<br />";

                    $b64_pdf_content = base64_decode($documento['documenti_contabilita_file']);
                    $pdf_file_name = $this->docs->generate_beautify_name($documento);

                    $folder = "modules_files/contabilita/pdf_cortesia";
                    $file_pdf_path = $this->docs->salva_file_fisico($pdf_file_name, $folder, $b64_pdf_content);

                    if (file_exists(FCPATH."uploads/".$file_pdf_path)) {
                        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_file_pdf = '$file_pdf_path' WHERE documenti_contabilita_id = '{$documento['documenti_contabilita_id']}'");
                        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_file = 'migrato su file $file_pdf_path' WHERE documenti_contabilita_id = '{$documento['documenti_contabilita_id']}'");
                    }
                }


                // Salvo pdf cortesia che spesso era anche nel file_preview
                if (base64_decode($documento['documenti_contabilita_file_preview'], true)) {
                    echo "PDF copia di: ".$documento['documenti_contabilita_id']." tipo: ".$documento['documenti_contabilita_tipo']."<br />";

                    $b64_pdf_content = base64_decode($documento['documenti_contabilita_file_preview']);
                    $pdf_file_name = "copia_".$this->docs->generate_beautify_name($documento);

                    $folder = "modules_files/contabilita/pdf_cortesia";
                    $file_pdf_path = $this->docs->salva_file_fisico($pdf_file_name, $folder, $b64_pdf_content);

                    if (file_exists(FCPATH."uploads/".$file_pdf_path)) {
                        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_file_pdf = '$file_pdf_path' WHERE documenti_contabilita_id = '{$documento['documenti_contabilita_id']}'");
                        $this->db->query("UPDATE documenti_contabilita SET documenti_contabilita_file_preview = 'migrato su file $file_pdf_path' WHERE documenti_contabilita_id = '{$documento['documenti_contabilita_id']}'");
                    }
                }
            }
        }
    }

    public function rif_docs() {
        if (empty($this->input->get('confirm')) || $this->input->get('confirm') !== 'yes') die("NOT ALLOWED");

        $this->load->model('contabilita/fix_model');

        echo $this->fix_model->rif_docs();
    }

    public function previsionale_flussi_cassa($year = null) {
        
        //Genero i flussi cassa per tutte le scadenze non saldate, sia fatture che spese
        $spese_scadenze = $this->apilib->search('spese_scadenze', [
            //'spese_scadenze_saldata' => DB_BOOL_FALSE,
            ($year==null)?'1=1':'YEAR(spese_scadenze_scadenza) = '.$year,
            // 'MONTH(spese_scadenze_scadenza) = 3'
            "spese_scadenze_id NOT IN (SELECT spese_scadenze_id FROM flussi_cassa_spese_scadenze_collegate)"
        ]);
        $documenti_contabilita_scadenze = $this->apilib->search('documenti_contabilita_scadenze', [
            //'documenti_contabilita_scadenze_saldata' => DB_BOOL_FALSE,
            ($year==null)?'1=1':'YEAR(documenti_contabilita_data_emissione) = '.$year,
            // 'MONTH(documenti_contabilita_data_emissione) = 3',
            'documenti_contabilita_tipo' => [1,4,11,12],
            "documenti_contabilita_scadenze_id NOT IN (SELECT documenti_contabilita_scadenze_id FROM flussi_cassa_scadenze_collegate)"
        ]);
        
        $s = $f = 0;
        
        foreach ($spese_scadenze as $spesa_scadenza) {
            progress(++$s, count($spese_scadenze), 'spese_scadenze');
            log_message('debug', "Elaboro spesa: {$spesa_scadenza['spese_numero']}");
            //Triggero banalmente il save così da scatenare il post-process che genera il flusso cassa automaticamente
            $this->apilib->edit('spese_scadenze', $spesa_scadenza['spese_scadenze_id'], [
                'spese_scadenze_note' => $spesa_scadenza['spese_scadenze_note'],
            ]);
        }

        //Faccio lo stesso per le fatture di vendita   
        
        foreach ($documenti_contabilita_scadenze as $documenti_contabilita_scadenza) {
            progress(++$f, count($documenti_contabilita_scadenze), 'documenti_contabilita_scadenze');
            log_message('debug', "Elaboro fattura: {$documenti_contabilita_scadenza['documenti_contabilita_numero']}");
            //Triggero banalmente il save così da scatenare il post-process che genera il flusso cassa automaticamente
            $this->apilib->edit('documenti_contabilita_scadenze', $documenti_contabilita_scadenza['documenti_contabilita_scadenze_id'], [
                'documenti_contabilita_scadenze_note' => $documenti_contabilita_scadenza['documenti_contabilita_scadenze_note'],
            ]);
        }
    }
}
