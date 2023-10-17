<?php

class Conteggi extends CI_Model
{


    /************************** CONTEGGI AGENTI (UTENTI) ***********************************/
    public function getMeseFatturatoAgente($user_id, $mese, $anno)
    {
        $fatturato = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_imponibile ELSE -documenti_contabilita_imponibile END) as fatturato,SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND documenti_contabilita_agente = '$user_id' AND EXTRACT(MONTH FROM documenti_contabilita_data_emissione) = '$mese' AND EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = '$anno'")->row_array();
        return $fatturato;
    }
    public function getMesePreventiviAgente($user_id, $mese, $anno)
    {
        $fatturato = $this->db->query("SELECT SUM(documenti_contabilita_imponibile) as fatturato, SUM(documenti_contabilita_iva) as iva FROM documenti_contabilita WHERE documenti_contabilita_tipo = 7 AND documenti_contabilita_agente = '$user_id' AND EXTRACT(MONTH FROM documenti_contabilita_data_emissione) = '$mese' AND EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = '$anno'")->row_array();
        return $fatturato;
    }
    public function getMeseOrdiniAgente($user_id, $mese, $anno)
    {
        $fatturato = $this->db->query("SELECT SUM(documenti_contabilita_imponibile) as fatturato, SUM(documenti_contabilita_iva) as iva FROM documenti_contabilita WHERE documenti_contabilita_tipo = 5 AND documenti_contabilita_agente = '$user_id' AND EXTRACT(MONTH FROM documenti_contabilita_data_emissione) = '$mese' AND EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = '$anno'")->row_array();
        return $fatturato;
    }

    /************************** CONTEGGI PER SINGOLO CLIENTE ***********************************/

    // Dato il customer id ritorna il fatturato per ogni anno
    public function getFatturatoCustomer($customer_id)
    {
        $fatturato = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_imponibile ELSE -documenti_contabilita_imponibile END) as fatturato,SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND documenti_contabilita_customer_id = '$customer_id'")->row_array();
        return $fatturato;
    }

    public function getFatturatoAnnoCustomer($customer_id)
    {
        $fatturato = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_imponibile ELSE -documenti_contabilita_imponibile END) as fatturato,SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND documenti_contabilita_customer_id = '$customer_id' AND EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = EXTRACT(YEAR FROM CURRENT_TIMESTAMP)")->row_array();
        return $fatturato;
    }

    public function getInsolvenzeCustomer($customer_id)
    {
        $insolvenze = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_imponibile ELSE -documenti_contabilita_imponibile END) as fatturato,SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND documenti_contabilita_customer_id = '$customer_id' AND documenti_contabilita_stato_pagamenti = '1'")->row_array();
        return $insolvenze;
    }

    /************************** CONTEGGI GLOBALI ***********************************/

    public function getFatturatoAnno($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $fatturato = $this->db->query("SELECT SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_imponibile ELSE -documenti_contabilita_imponibile END) as imponibile,SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END) as iva FROM documenti_contabilita WHERE (documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4) AND EXTRACT(YEAR FROM documenti_contabilita_data_emissione) = '$anno'")->row_array();
        return $fatturato;
    }

    public function getSpeseAnno($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $spese = $this->db->query("SELECT SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_imponibile ELSE -spese_imponibile END) as imponibile,SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_iva ELSE -spese_iva END) as iva FROM spese WHERE (spese_tipologia_fatturazione = 1 OR spese_tipologia_fatturazione = 4) AND EXTRACT(YEAR FROM spese_data_emissione) = '$anno'")->row_array();
        return $spese;
    }

    public function getCostiDeducibiliAnno($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $costi_deducibili = $this->db->query("
            SELECT SUM((CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_imponibile ELSE -spese_imponibile END) / 100 * COALESCE(spese_deduc_tasse, 0)) as costi_deducibili FROM spese
            WHERE (spese_tipologia_fatturazione = 1 OR spese_tipologia_fatturazione = 4) AND EXTRACT(YEAR FROM spese_data_emissione) = '$anno'")->row_array();

        return $costi_deducibili;
    }

    public function getSpeseCategorieAnno($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $spese = $this->db->query("SELECT spese_categorie_nome,spese_categoria,SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_imponibile ELSE -spese_imponibile END) as imponibile,SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_iva ELSE -spese_iva END) as iva FROM spese LEFT JOIN spese_categorie ON spese_categorie_id = spese_categoria WHERE (spese_tipologia_fatturazione = 1 OR spese_tipologia_fatturazione = 4) AND EXTRACT(YEAR FROM spese_data_emissione) = '$anno' GROUP BY spese_categoria ORDER BY imponibile ASC")->result_array();
        return $spese;
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

        $debiti = $this->db->query("SELECT SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_scadenze_ammontare ELSE -spese_scadenze_ammontare END) as debiti FROM spese_scadenze LEFT JOIN spese ON (spese_scadenze_spesa = spese_id) WHERE spese_scadenze_saldata = 0 AND (spese_tipologia_fatturazione = 1 OR spese_tipologia_fatturazione = 4) AND EXTRACT(YEAR FROM spese_scadenze_scadenza) = '$anno'")->row()->debiti;
        return $debiti;
    }

    /********************* CONTEGGI GLOBALI PER MESE *****************************/

    public function getFatturatoAnnoMensile($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $fatturato = $this->db->query("SELECT idMonth, MONTHNAME(STR_TO_DATE(idMonth, '%m')) as m,
            coalesce(SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_imponibile ELSE -documenti_contabilita_imponibile END),0) as imponibile,
            coalesce(SUM(CASE WHEN documenti_contabilita_tipo = 1 THEN documenti_contabilita_iva ELSE -documenti_contabilita_iva END),0) as iva
            FROM (
                    SELECT 1 as idMonth
                    UNION SELECT 2 as idMonth
                    UNION SELECT 3 as idMonth
                    UNION SELECT 4 as idMonth
                    UNION SELECT 5 as idMonth
                    UNION SELECT 6 as idMonth
                    UNION SELECT 7 as idMonth
                    UNION SELECT 8 as idMonth
                    UNION SELECT 9 as idMonth
                    UNION SELECT 10 as idMonth
                    UNION SELECT 11 as idMonth
                    UNION SELECT 12 as idMonth
                ) as Month
            LEFT JOIN documenti_contabilita ON ((documenti_contabilita_tipo = 1 OR documenti_contabilita_tipo = 4)
            AND DATE_FORMAT(documenti_contabilita_data_emissione, '%Y') = '$anno'
            AND Month.idMonth = month(`documenti_contabilita_data_emissione`))
            GROUP BY Month.idMonth")->result_array();

        return $fatturato;
    }

    public function getSpeseAnnoMensile($anno = null)
    {
        if (!$anno) {
            $anno = date("Y");
        }

        $spese = $this->db->query("SELECT idMonth, MONTHNAME(STR_TO_DATE(idMonth, '%m')) as m,
            coalesce(SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_imponibile ELSE -spese_imponibile END),0) as imponibile,
            coalesce(SUM(CASE WHEN spese_tipologia_fatturazione = 1 THEN spese_iva ELSE -spese_iva END),0) as iva
            FROM (
                    SELECT 1 as idMonth
                    UNION SELECT 2 as idMonth
                    UNION SELECT 3 as idMonth
                    UNION SELECT 4 as idMonth
                    UNION SELECT 5 as idMonth
                    UNION SELECT 6 as idMonth
                    UNION SELECT 7 as idMonth
                    UNION SELECT 8 as idMonth
                    UNION SELECT 9 as idMonth
                    UNION SELECT 10 as idMonth
                    UNION SELECT 11 as idMonth
                    UNION SELECT 12 as idMonth
                ) as Month
            LEFT JOIN spese ON ((spese_tipologia_fatturazione = 1 OR spese_tipologia_fatturazione = 4)
            AND DATE_FORMAT(spese_data_emissione, '%Y') = '$anno'
            AND Month.idMonth = month(`spese_data_emissione`))
            GROUP BY Month.idMonth")->result_array();

        return $spese;
    }
}