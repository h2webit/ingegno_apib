<?php
if (!function_exists('calcola_tariffa_accesso_sede')) {

    function calcola_tariffa_accesso_sede($sede_id, $data, $affiancamento, $costo_differenziato, $associato_id = false)
    {
        return costo_accesso($sede_id, $data, $affiancamento, $costo_differenziato, $associato_id);
    }
}
if (!function_exists('costo_accesso')) {

    function costo_accesso($sede_id, $data, $affiancamento, $costo_differenziato, $associato_id = false)
    {
        $CI = get_instance();

        //Cerco eventuali variazioni
        if ($associato_id) {

            $data_primo_del_mese = new DateTime($data);
            $data_primo_del_mese->modify('first day of this month');
            $data_primo_del_mese = $data_primo_del_mese->format('Y-m-d');

            $data_ultimo_del_mese = new DateTime($data);
            $data_ultimo_del_mese->modify('last day of this month');
            $data_ultimo_del_mese = $data_ultimo_del_mese->format('Y-m-d');

            $variazione = $CI->db->query("SELECT * FROM compensi_variazioni WHERE 
                    compensi_variazioni_associato = '$associato_id' 
                    AND 
                    (
                        (
                            (compensi_variazioni_data IS NULL OR compensi_variazioni_data <= '$data_primo_del_mese') AND 
                            (compensi_variazioni_valida_al IS NULL OR compensi_variazioni_valida_al >= '$data_ultimo_del_mese')
                        ) 
                        AND 
                        compensi_variazioni_data <= '$data_primo_del_mese'::timestamp
                        AND
                        compensi_variazioni_data >= 
                        (
                            '$data_primo_del_mese'::timestamp -  
                            (
                                CASE 
                                    WHEN compensi_variazioni_ripetizione = 1
                                    THEN interval '0 months'
                                    WHEN compensi_variazioni_ripetizione = 2
                                    THEN interval '1 month'
                                    WHEN compensi_variazioni_ripetizione = 3
                                    THEN interval '2 months'
                                    WHEN compensi_variazioni_ripetizione = 4
                                    THEN interval '3 months'
                                    WHEN compensi_variazioni_ripetizione = 5
                                    THEN interval '4 months'
                                    WHEN compensi_variazioni_ripetizione = 6
                                    THEN interval '5 months'
                                    WHEN compensi_variazioni_ripetizione = 7
                                    THEN interval '0 year'
                                    WHEN compensi_variazioni_ripetizione = 9
                                    THEN interval '1 year'
                                    WHEN compensi_variazioni_ripetizione = 10
                                    THEN interval '2 years'
                                    WHEN compensi_variazioni_ripetizione = 11
                                    THEN interval '3 years'
                                    WHEN compensi_variazioni_ripetizione = 12
                                    THEN interval '4 years'
                                    ELSE 
                                        interval '-99 years'
                                END
                            )
                        )
                    )
                    AND compensi_variazioni_tipo = '5' 
                    AND (compensi_variazioni_sede = '$sede_id' OR compensi_variazioni_sede IS NULL)
                    AND compensi_variazioni_categoria = '6'
                    
                    ORDER BY compensi_variazioni_data DESC
                    ");



            if ($variazione->num_rows() >= 1) {
                $importo = $variazione->row()->compensi_variazioni_importo;
                //debug($importo, true);
                //                $associato = $CI->apilib->view('associati', $associato_id);
                //                //Verifico se devo prendere dallo storico
                //                $storico = $CI->db->query("SELECT * FROM storico_associati WHERE storico_associati_associato = '$associato_id' AND storico_associati_data_creazione > '$data' ORDER BY storico_associati_id ASC")->row();
                //                if (!empty($storico) && $storico->storico_dipendenti_percentuale_sedi != 0) {
                //                    //debug($associato['dipendenti_percentuale_sedi'],true);
                //                    $associato['dipendenti_percentuale_sedi'] = $storico->storico_dipendenti_percentuale_sedi;
                //                }
                //                $importo = 100 * $importo / $associato['dipendenti_percentuale_sedi'];
                return floatval($importo);
            }
        }

        $query = "SELECT * FROM tariffe WHERE tariffe_sede = '$sede_id' AND tariffe_categoria = '6' AND tariffe_creation_date<= '$data' ORDER BY tariffe_id DESC LIMIT 1";

        $row = $CI->db->query($query)->row();





        //Se non ho trovato una tariffa valida il giorno del report, prendo la prima immediatamente dopo quella data
        if (empty($row)) {
            $query = "SELECT * FROM tariffe WHERE tariffe_sede = '$sede_id' AND tariffe_categoria = '6' AND tariffe_creation_date > '$data' ORDER BY tariffe_id ASC LIMIT 1";

            $row = $CI->db->query($query)->row();
        }
        if (!empty($row)) {
            if ($affiancamento) {
                return $row->tariffe_costo_affiancamento;
            } elseif ($costo_differenziato) {
                return $row->tariffe_costo_extra;
            } else {
                return $row->tariffe_costo_accesso;
            }
        } else {
            //Se comunque non ho trovato la tariffa genero eccezione
            //throw new Exception("Non è stato possibile recuperare la tariffa accesso per la sede '$sede_id',  valida per il giorno '$data'.");
            return 0;
        }
    }
}
if (!function_exists('calcola_tariffa_totale_oraria_sede')) {

    function calcola_tariffa_totale_oraria_sede($report, $variazioni = true)
    {
        $sede_id = $report['rapportini_commessa'];
        $dalle  = $report['rapportini_ora_inizio'];
        $alle  = $report['rapportini_ora_fine'];
        $categoria  = $report['projects_orari_categoria'];
        $affiancamento  = ($report['rapportini_affiancamento'] == '1');
        $costo_differenziato  = ($report['rapportini_costo_differenziato'] == '1');
        $festivo  = ($report['rapportini_festivo'] == '1');

        if ($festivo) {
            $categoria = 3;
        }

        $associato  = $report['rapportini_operatori'];

        if (!$categoria) {
            throw new Exception("Fascia assente nel report ore per l'associato <a href='" . base_url('main/layout/34/' . $associato) . "'>$associato</a>!");
        }

        // Call the format method on the DateInterval-object
        $ore = differenza_in_ore_float($dalle, $alle);
        //        $minuti = differenza_in_minuti($dalle, $alle);
        //        //die($dalle.' - '.$alle.' - '.(($ore*60)+$minuti));
        //        $ore_con_frazioni = (($ore*60)+$minuti)/60;
        $costo = costo_orario($sede_id, $dalle, $categoria, $affiancamento, $costo_differenziato, ($variazioni) ? $associato : null, $variazioni, $report);
        //debug($costo);
        $costo = number_format($costo, 2);
        //        debug($costo);
        //        debug($ore);

        return $costo * $ore;
    }
}

if (!function_exists('costo_orario')) {

    function costo_orario($sede_id, $data, $categoria, $affiancamento, $costo_differenziato, $associato_id = null, $variazioni = true, $report = null)
    {
        $CI = get_instance();

        if ($affiancamento) {
            $where = " AND compensi_variazioni_affiancamento = '1'";
        } elseif ($costo_differenziato) {
            $where = " AND compensi_variazioni_tariffa_differenziata = '1'";
        } else {
            $where = '';
        }

        if ($associato_id !== null) {
            $associato = $CI->apilib->view('dipendenti', $associato_id);
            //Verifico se devo prendere dallo storico
            
            $storico = [];
            if (false) {
                $storico = $CI->db->query("SELECT * FROM storico_associati WHERE storico_associati_associato = '$associato_id' AND storico_associati_data_creazione > '$data' ORDER BY storico_associati_id ASC")->row();
                if (!empty($storico) && $storico->storico_dipendenti_percentuale_sedi != 0) {
                    //debug($associato['dipendenti_percentuale_sedi'],true);
                    $associato['dipendenti_percentuale_sedi'] = $storico->storico_dipendenti_percentuale_sedi;
                }
            }
            
            //debug($associato,true);
        }



        if ($variazioni && $associato_id !== null) {



            $data_primo_del_mese = new DateTime($data);
            $data_primo_del_mese->modify('first day of this month');
            $data_primo_del_mese = $data_primo_del_mese->format('Y-m-d');

            $data_ultimo_del_mese = new DateTime($data);
            $data_ultimo_del_mese->modify('last day of this month');
            $data_ultimo_del_mese = $data_ultimo_del_mese->format('Y-m-d');

            $ultimo_giorno_dellanno = new DateTime($data);
            $ultimo_giorno_dellanno->modify('last day of this year');
            $ultimo_giorno_dellanno = $ultimo_giorno_dellanno->format('Y-m-d');



            //Prima di tutto verifico che non ci sia una variazione specifica della tariffa
            $variazione = $CI->db->query("SELECT * FROM compensi_variazioni WHERE 
                compensi_variazioni_associato = '$associato_id'
                AND
                (
                    (
                        (compensi_variazioni_data IS NULL OR DATE(compensi_variazioni_data) <= '$data_primo_del_mese') AND
                        (compensi_variazioni_valida_al IS NULL OR DATE(compensi_variazioni_valida_al) >= '$data_ultimo_del_mese')
                    ) AND
        
                    compensi_variazioni_data <= CAST('$data_primo_del_mese' AS DATETIME)
                    AND
                    compensi_variazioni_data >=
                    (
                        CASE compensi_variazioni_ripetizione
                            WHEN 1 THEN DATE_SUB(CAST('$data_primo_del_mese' AS DATETIME), INTERVAL 0 MONTH)
                            WHEN 2 THEN DATE_SUB(CAST('$data_primo_del_mese' AS DATETIME), INTERVAL 1 MONTH)
                            WHEN 3 THEN DATE_SUB(CAST('$data_primo_del_mese' AS DATETIME), INTERVAL 2 MONTH)
                            WHEN 4 THEN DATE_SUB(CAST('$data_primo_del_mese' AS DATETIME), INTERVAL 3 MONTH)
                            WHEN 5 THEN DATE_SUB(CAST('$data_primo_del_mese' AS DATETIME), INTERVAL 4 MONTH)
                            WHEN 6 THEN DATE_SUB(CAST('$data_primo_del_mese' AS DATETIME), INTERVAL 5 MONTH)
                            WHEN 7 THEN DATE_SUB(CAST('$ultimo_giorno_dellanno' AS DATETIME), INTERVAL 1 YEAR)
                            WHEN 9 THEN DATE_SUB(CAST('$ultimo_giorno_dellanno' AS DATETIME), INTERVAL 2 YEAR)
                            WHEN 10 THEN DATE_SUB(CAST('$ultimo_giorno_dellanno' AS DATETIME), INTERVAL 3 YEAR)
                            WHEN 11 THEN DATE_SUB(CAST('$ultimo_giorno_dellanno' AS DATETIME), INTERVAL 4 YEAR)
                            WHEN 12 THEN DATE_SUB(CAST('$ultimo_giorno_dellanno' AS DATETIME), INTERVAL 5 YEAR)
                            ELSE DATE_SUB(CAST('$data_primo_del_mese' AS DATETIME), INTERVAL 99 YEAR)
                        END
                    )
                )
                AND compensi_variazioni_tipo = '5'
                AND (compensi_variazioni_sede = '$sede_id' OR compensi_variazioni_sede IS NULL)
                AND compensi_variazioni_categoria = '$categoria'
                $where
                ORDER BY compensi_variazioni_data DESC");
            
            //            debug($variazione->num_rows());

            if ($variazione->num_rows() >= 1) {





                return floatval($variazione->row()->compensi_variazioni_importo);
            }
        }
        $query = "SELECT * FROM tariffe WHERE tariffe_sede = '$sede_id' AND tariffe_categoria = '$categoria' AND tariffe_creation_date <= '$data' ORDER BY tariffe_id DESC LIMIT 1";

        if ($sede_id == 79) {
            //die($query);
        }

        $row = $CI->db->query($query)->row();

        //Se non ho trovato una tariffa valida il giorno del report, prendo la prima immediatamente dopo quella data
        if (empty($row)) {
            $query = "SELECT * FROM tariffe WHERE tariffe_sede = '$sede_id' AND tariffe_categoria = '$categoria' AND tariffe_creation_date > '$data' ORDER BY tariffe_id ASC LIMIT 1";

            $row = $CI->db->query($query)->row();

            //debug($row,true);
            //debug($CI->db->last_query());
        }

        if (!empty($row)) {
            if ($associato_id === null) {
                $associato = [];
                $associato['dipendenti_percentuale_sedi'] = 100; //Fake per mantenere la tariffa al 100% nel caso non venga passato un associato_id alla funzione
            }
            if ($affiancamento) {
                return floatval($row->tariffe_costo_affiancamento * $associato['dipendenti_percentuale_sedi'] / 100);
            } elseif ($costo_differenziato) {
                return floatval($row->tariffe_costo_extra * $associato['dipendenti_percentuale_sedi'] / 100);
            } else {
                return floatval($row->tariffe_costo_orario * $associato['dipendenti_percentuale_sedi'] / 100);
            }
        } else {
            $sede = $CI->apilib->view('sedi_operative', $sede_id);
            //throw new Exception("Non è stato possibile recuperare la tariffa accesso per la sede '{$sede['sedi_operative_reparto']}',  valida per il giorno '$data'.");
            //debug($associato_id);

            echo ("Non è stato possibile recuperare la tariffa accesso per la sede '{$sede['sedi_operative_reparto']}',  valida per il giorno '$data'. ");
            if ($report) {
                echo $report['dipendenti_cognome'];
            }
            debug($report, true);
            die();
        }
    }
}
//SEMBRA NON ESSERE PIU USATA!!! DISMESSA....
/*if (!function_exists('differenza_in_ore')) {
    
    function differenza_in_ore($dal, $al)
    {
        $date1 = new DateTime($dal);
        $date2 = new DateTime($al);

        // The diff-methods returns a new DateInterval-object...
        $diff = $date2->diff($date1);

        // Call the format method on the DateInterval-object
        return $diff->format('%h');
    }
}*/
if (!function_exists('differenza_in_ore_float')) {

    function differenza_in_ore_float($dal, $al)
    {
        $date1 = new DateTime($dal);
        $date2 = new DateTime($al);

        // The diff-methods returns a new DateInterval-object...
        $diff = $date2->diff($date1);

        //        if ('2018-03-03 20:00:00' == $dal) {
        //            debug($dal);
        //            debug($al);
        //            debug($diff,true);
        //        }

        // Call the format method on the DateInterval-object
        return number_format((($diff->format('%d') * 24 * 60) + ($diff->format('%h') * 60) +  differenza_in_minuti($dal, $al)) / 60, 2);
    }
}
if (!function_exists('differenza_in_minuti')) {

    function differenza_in_minuti($dal, $al)
    {
        $date1 = new DateTime($dal);
        $date2 = new DateTime($al);

        // The diff-methods returns a new DateInterval-object...
        $diff = $date2->diff($date1);

        // Call the format method on the DateInterval-object
        return $diff->format('%i');
    }
}
if (!function_exists('mese_testuale')) {

    function mese_testuale($data_o_mese)
    {
        if (is_numeric($data_o_mese)) {
            $month = $data_o_mese;
        } else {
            $month = date("n", strtotime($data_o_mese));
        }
        $month = (int) $month;
        $months = [
            1 => 'Gennaio',
            2 => 'Febbraio',
            3 => 'Marzo',
            4 => 'Aprile',
            5 => 'Maggio',
            6 => 'Giugno',
            7 => 'Luglio',
            8 => 'Agosto',
            9 => 'Settembre',
            10 => 'Ottobre',
            11 => 'Novembre',
            12 => 'Dicembre',
        ];
        // Call the format method on the DateInterval-object
        return $months[$month];
    }
}
