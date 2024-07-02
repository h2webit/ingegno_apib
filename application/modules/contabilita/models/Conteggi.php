<?php

class Conteggi extends CI_Model
{
    private function baseQueryDocumentiContabilita($options = []) {
        $append_select_imponibile = array_get($options, 'append_select_imponibile', '');
        $append_select_iva = array_get($options, 'append_select_imponibile', '');
        $select = array_get($options, 'select', "
            SUM(CASE WHEN documenti_contabilita_tipo <> 4 THEN documenti_contabilita_imponibile ELSE -ABS(documenti_contabilita_imponibile) END) $append_select_imponibile as imponibile,
            SUM(CASE WHEN documenti_contabilita_tipo <> 4 THEN documenti_contabilita_iva ELSE -ABS(documenti_contabilita_iva) END) $append_select_iva as iva 
        ");

        $order_by = array_get($options, 'order_by', '');
        $group_by = array_get($options, 'group_by', '');
        $limit = array_get($options, 'limit', '');

        $custom_from = array_get($options, 'custom_from', 'documenti_contabilita');
        

        $where = array_get($options, 'where', [
            "documenti_contabilita_tipo IN (1, 4)"
        ]);
        $where_append = array_get($options, 'where_append', [
            
        ]);

        $multi_rows = array_get($options, 'multi_rows', false);

        $this->db->select($select);
        $this->db->from($custom_from);
        $this->db->order_by($order_by);
        $this->db->group_by($group_by);
        $this->db->limit($limit);
        foreach (array_filter($where) as $w) {
            $this->db->where($w, null, false);
        }
        foreach (array_filter($where_append) as $w) {
            $this->db->where($w, null, false);
        }

        if ($group_by || $multi_rows) {
            return $this->db->get()->result_array();
        } else {
            return $this->db->get()->row_array();
        }
        
    }

    /************************** CONTEGGI AGENTI (UTENTI) ***********************************/
    public function getMeseFatturatoAgente($user_id, $mese, $anno)
    {
        $options = [
            'where_append' => [
                "documenti_contabilita_agente = '$user_id'",
                "EXTRACT(MONTH FROM documenti_contabilita_data_emissione) = '$mese'",
                "EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = '$anno'"
            ],
            'multi_rows' => false
        ];

        return $this->baseQueryDocumentiContabilita($options);
    }

    public function getMesePreventiviAgente($user_id, $mese, $anno)
    {
        $options = [
            'where' => [
                "documenti_contabilita_tipo = 7",
                "documenti_contabilita_agente = '$user_id'",
                "EXTRACT(MONTH FROM documenti_contabilita_data_emissione) = '$mese'",
                "EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = '$anno'"
            ],
            'multi_rows' => false
        ];

        return $this->baseQueryDocumentiContabilita($options);
    }

    public function getMeseOrdiniAgente($user_id, $mese, $anno)
    {
        $options = [
            'where' => [
                "documenti_contabilita_tipo = 5",
                "documenti_contabilita_agente = '$user_id'",
                "EXTRACT(MONTH FROM documenti_contabilita_data_emissione) = '$mese'",
                "EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = '$anno'"
            ],
            'multi_rows' => false
        ];

        return $this->baseQueryDocumentiContabilita($options);
    }


    /************************** CONTEGGI PER SINGOLO CLIENTE ***********************************/

    // Dato il customer id ritorna il fatturato per ogni anno
    public function getFatturatoCustomer($customer_id)
    {
        $options = [
            'where_append' => [
                "documenti_contabilita_customer_id = '$customer_id'"
            ],
            'multi_rows' => false
        ];

        return $this->baseQueryDocumentiContabilita($options);
    }


    public function getFatturatoAnnoCustomer($customer_id)
    {
        $options = [
            'where_append' => [
                "documenti_contabilita_customer_id = '$customer_id'",
                "EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = EXTRACT(YEAR FROM CURRENT_TIMESTAMP)"
            ],
            'multi_rows' => false
        ];

        return $this->baseQueryDocumentiContabilita($options);
    }


    public function getInsolvenzeCustomer($customer_id)
    {
        $options = [
            'where_append' => [
                "documenti_contabilita_customer_id = '$customer_id'",
                "documenti_contabilita_stato_pagamenti = '1'"
            ],
            'multi_rows' => false
        ];

        return $this->baseQueryDocumentiContabilita($options);
    }

    public function getTempoMedioSaldoCustomer($customer_id, $human_readable = true)
    {
        $this->db->select("AVG(DATEDIFF(documenti_contabilita_scadenze.documenti_contabilita_scadenze_data_saldo, documenti_contabilita.documenti_contabilita_data_emissione)) as tempo_medio_saldo_giorni");
        $this->db->from("documenti_contabilita_scadenze");
        $this->db->join("documenti_contabilita", "documenti_contabilita.documenti_contabilita_id = documenti_contabilita_scadenze.documenti_contabilita_scadenze_documento");
        $this->db->where("documenti_contabilita.documenti_contabilita_customer_id", $customer_id);
        $this->db->where("documenti_contabilita_scadenze.documenti_contabilita_scadenze_data_saldo IS NOT NULL", null, false);

        $query = $this->db->get();
        $result = $query->row_array();

        if (!isset($result['tempo_medio_saldo_giorni'])) {
            if ($human_readable) {
                return 'N/A';
            } else {
                return false;
            }
            
        }

        if ($human_readable) {
            return $this->convertDaysToYearsMonthsDays($result['tempo_medio_saldo_giorni']);
        } else {
            return $result['tempo_medio_saldo_giorni'];
        }

        
    }

    public function getTotaleOrdiniVenditaCustomer($customer_id)
    {
        $options = [
            'select' => "COUNT(*) as totale_ordini",
            'where' => [
                "documenti_contabilita_tipo = 5", // Assumendo che il tipo 5 sia per gli ordini di vendita
                "documenti_contabilita_customer_id = '$customer_id'"
            ],
            'multi_rows' => false
        ];

        return $this->baseQueryDocumentiContabilita($options);
    }

    public function getTotaleOrdiniAcquistoCustomer($customer_id) {
        $options = [
            'select' => "COUNT(*) as totale_ordini",
            'where' => [
                "documenti_contabilita_tipo = 6", // Assumendo che il tipo 6 sia per gli ordini di acquisto
                "documenti_contabilita_customer_id = '$customer_id'"
            ],
            'multi_rows' => false
        ];

        return $this->baseQueryDocumentiContabilita($options);
    }


    private function convertDaysToYearsMonthsDays($days)
    {
        $years = floor($days / 365);
        $remainingDays = $days % 365;
        $months = floor($remainingDays / 30);
        $days = $remainingDays % 30;

        $result = [];
        if ($years > 0) {
            if ($years == 1) {
                $result[] = $years . ' anno';
            } else {
                $result[] = $years . ' anni';
            }
            
        }
        if ($months > 0) {
            if ($months == 1) {
                $result[] = $months . ' mese';
            } else {
                $result[] = $months . ' mesi';
            }
            
        }
        if ($days > 0) {
            if ($days == 1) {
                $result[] = $days . ' giorno';
            } else {
                $result[] = $days . ' giorni';
            }
            
        }

        return implode(', ', $result);
    }


    /************************** CONTEGGI GLOBALI ***********************************/

    public function getFatturatoAnno($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $options = [
            'where_append' => [
                "EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = '$anno'"
            ],
            'multi_rows' => false
        ];

        return $this->baseQueryDocumentiContabilita($options);
    }


    private function baseQuerySpese($options = [])
    {
        $append_select_imponibile = array_get($options, 'append_select_imponibile', '');
        $append_select_iva = array_get($options, 'append_select_iva', '');
        $select = array_get($options, 'select', "
            SUM(CASE WHEN spese_tipologia_fatturazione <> 4 THEN spese_imponibile ELSE -ABS(spese_imponibile) END) $append_select_imponibile as imponibile,
            SUM(CASE WHEN spese_tipologia_fatturazione <> 4 THEN (
                CASE WHEN spese_tipologia_fatturazione IN (9,10,11) THEN 0 ELSE spese_iva END
            ) ELSE -ABS(spese_iva) END) $append_select_iva as iva 
        ");

        $order_by = array_get($options, 'order_by', '');
        $group_by = array_get($options, 'group_by', '');
        $limit = array_get($options, 'limit', '');
        $custom_from = array_get($options, 'custom_from', 'spese');
        
        $where = array_get($options, 'where', [
            //"spese_tipologia_fatturazione IN (1, 4)"
        ]);
        $where_append = array_get($options, 'where_append', [
            //"spese_tipologia_fatturazione IN (1, 4)"
        ]);

        $multi_rows = array_get($options, 'multi_rows', false);

        $this->db->select($select);
        $this->db->from($custom_from);
        $this->db->order_by($order_by);
        $this->db->group_by($group_by);
        $this->db->limit($limit);
        foreach (array_filter($where) as $w) {
            $this->db->where($w, null, false);
        }
        foreach (array_filter($where_append) as $w) {
            $this->db->where($w, null, false);
        }
        

        if ($group_by || $multi_rows) {
            
            return $this->db->get()->result_array();
        } else {
            $row = $this->db->get()->row_array();
            //debug($this->db->last_query(),true);
            return $row;
        }
    }





    public function getSpeseAnno($anno = null, $where_append = '')
    {
        if (!$anno) {
            $filtro_anno = '(1=1)';
        } else {
            $filtro_anno = "EXTRACT(YEAR FROM spese_data_emissione) = '$anno'";
        }

        $options = [
            'where' => [
                $filtro_anno,
                $where_append
            ],
            'multi_rows' => false
        ];

        return $this->baseQuerySpese($options);
    }
    public function getCostiDeducibiliAnno($anno = null, $where_append = '')
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $options = [
            'select' => "
            SUM((CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_imponibile ELSE -ABS(spese_imponibile) END) / 100 * COALESCE(spese_deduc_tasse, 0)) as costi_deducibili
        ",
            'where' => [
                "EXTRACT(YEAR FROM spese_data_emissione) = '$anno'",
                $where_append
            ],
            'multi_rows' => false
        ];

        return $this->baseQuerySpese($options);
    }




    public function getSpeseCategorieAnno($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $options = [
            'select' => "
            spese_categorie_nome, spese_categoria, 
            SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_imponibile ELSE -spese_imponibile END) as imponibile, 
            SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_iva ELSE -spese_iva END) as iva
        ",
            'where' => [
                "EXTRACT(YEAR FROM spese_data_emissione) = '$anno'"
            ],
            'group_by' => 'spese_categoria',
            'order_by' => 'imponibile ASC',
            'multi_rows' => true
        ];

        return $this->baseQuerySpese($options);
    }



    public function getCreditiClientiAnno($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $crediti = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_scadenze_ammontare ELSE -documenti_contabilita_scadenze_ammontare END) as crediti FROM documenti_contabilita_scadenze LEFT JOIN documenti_contabilita ON (documenti_contabilita_scadenze_documento = documenti_contabilita_id) WHERE documenti_contabilita_scadenze_saldata = 0 AND (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND EXTRACT(YEAR FROM documenti_contabilita_scadenze_scadenza) = '$anno'")->row()->crediti;
        return $crediti;
    }

    public function getDebitiFornitoriAnno($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $debiti = $this->db->query("
            SELECT 
                SUM(CASE WHEN spese_tipologia_fatturazione = 4 THEN -ABS(spese_scadenze_ammontare) ELSE spese_scadenze_ammontare END) as debiti 
            FROM 
                spese_scadenze 
                LEFT JOIN spese ON (spese_scadenze_spesa = spese_id) 
            WHERE 
                (spese_scadenze_saldata = 0 OR spese_scadenze_saldata IS NULL)
                AND EXTRACT(YEAR FROM spese_scadenze_scadenza) = '$anno'
            ")->row()->debiti;

            //debug($this->db->last_query());
        return $debiti;
    }

    /********************* CONTEGGI GLOBALI PER MESE *****************************/

    public function getFatturatoAnnoMensile($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $month_join = "
        (
            SELECT 1 as idMonth UNION SELECT 2 as idMonth UNION SELECT 3 as idMonth UNION SELECT 4 as idMonth
            UNION SELECT 5 as idMonth UNION SELECT 6 as idMonth UNION SELECT 7 as idMonth UNION SELECT 8 as idMonth
            UNION SELECT 9 as idMonth UNION SELECT 10 as idMonth UNION SELECT 11 as idMonth UNION SELECT 12 as idMonth
        ) as Month
        LEFT JOIN documenti_contabilita ON (
            documenti_contabilita_tipo IN (1, 4)
            AND DATE_FORMAT(documenti_contabilita_data_emissione, '%Y') = '$anno'
            AND Month.idMonth = MONTH(documenti_contabilita_data_emissione)
        )";

        $options = [
            'custom_from' => $month_join,
            'select' => "
                idMonth, MONTHNAME(STR_TO_DATE(idMonth, '%m')) as m, 
                coalesce(SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_imponibile ELSE -documenti_contabilita_imponibile END), 0) as imponibile, 
                coalesce(SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END), 0) as iva
            ",
            'group_by' => 'Month.idMonth',
            'multi_rows' => true
        ];

        $results = $this->baseQueryDocumentiContabilita($options);
        $formattedResults = [];
        foreach ($results as $result) {
            $formattedResults[$result['idMonth']] = $result;
        }
        //debug($formattedResults);
        for ($mese = 1; $mese <= 12; $mese++) {
            if (!array_key_exists($mese, $formattedResults)) {
                //debug($mese);

                $formattedResults[$mese] = [
                    'idMonth' => $mese,
                    'm' => mese_testuale($mese),
                    'imponibile' => 0,
                    'iva' => 0
                ];
                //debug($formattedResults);
            }

        }
        
        //Riordino per mese
        ksort($formattedResults);

        return $formattedResults;
        
        
    }


    public function getSpeseAnnoMensile($anno = null, $category = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $category_condition = $category ? "AND spese_categoria = '$category'" : "";

        $month_join = "
        (
            SELECT 1 as idMonth UNION SELECT 2 as idMonth UNION SELECT 3 as idMonth UNION SELECT 4 as idMonth
            UNION SELECT 5 as idMonth UNION SELECT 6 as idMonth UNION SELECT 7 as idMonth UNION SELECT 8 as idMonth
            UNION SELECT 9 as idMonth UNION SELECT 10 as idMonth UNION SELECT 11 as idMonth UNION SELECT 12 as idMonth
        ) as Month
        LEFT JOIN spese ON (
            DATE_FORMAT(spese_data_emissione, '%Y') = '$anno'
            AND Month.idMonth = MONTH(spese_data_emissione)
            $category_condition
        )";

        $options = [
            'custom_from' => $month_join,
            'select' => "
            idMonth, MONTHNAME(STR_TO_DATE(idMonth, '%m')) as m, 
            coalesce(SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_imponibile ELSE -ABS(spese_imponibile) END), 0) as imponibile, 
            coalesce(SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_iva ELSE -ABS(spese_iva) END), 0) as iva
        ",
            'group_by' => 'Month.idMonth',
            'multi_rows' => true
        ];
        $results = $this->baseQuerySpese($options);
        //debug($results,true);
        $formattedResults = [];

        foreach ($results as $result) {
            $formattedResults[$result['idMonth']] = $result;
        }

        for ($mese = 1; $mese <= 12; $mese++) {
            if (!array_key_exists($mese, $formattedResults)) {
             
            
                $formattedResults[$mese] = [
                    'idMonth' => $mese,
                    'm' => mese_testuale($mese),
                    'imponibile' => 0,
                    'iva' => 0
                ];
            }

        }
        //Riordino per mese
        ksort($formattedResults);
        return $formattedResults;
        
    }

}