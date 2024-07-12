<?php

class Zucchetti extends MY_Controller
{
    public function __construct ()
    {
        parent::__construct();
        $this->load->model('modulo-hr/timbrature');
    }
    
    public function genera_xml ($view = 0)
    {
        $this->mycache->clearCache();
        
        // ottengo i filtri delle presenze con chiave "filter-presenze"
        $filtri_presenze = $this->session->userdata(SESS_WHERE_DATA)['filter-presenze'] ?? [];
        
        // ottengo il singolo filtro data inizio, null se non trova niente
        $filtro_data_inizio_field_id = $this->db->where('fields_name', 'presenze_data_inizio')->get('fields')->row()->fields_id;
        $filtro_data_inizio = $filtri_presenze[$filtro_data_inizio_field_id]['value'] ?? null;
        
        // ottengo il singolo filtro dipendente, null se non trova niente
        $filtro_dipendente_field_id = $this->db->where('fields_name', 'presenze_dipendente')->get('fields')->row()->fields_id;
        $filtro_dipendente = $filtri_presenze[$filtro_dipendente_field_id]['value'] ?? null;
        
        // ottengo il singolo filtro azienda, null se non trova niente
        $filtro_azienda_field_id = $this->db->where('fields_name', 'dipendenti_azienda')->get('fields')->row()->fields_id;
        $filtro_azienda = $filtri_presenze[$filtro_azienda_field_id]['value'] ?? null;
        
        // ottengo l'elenco dipendenti
        $where_dipendenti = ["dipendenti_codice_esterno <> '' AND dipendenti_codice_esterno IS NOT NULL AND dipendenti_attivo = 1"];
        if (!empty($filtro_dipendente) && $filtro_dipendente !== '-1') {
            $where_dipendenti[] = "(dipendenti_id = '{$filtro_dipendente}')";
        }
        if (!empty($filtro_azienda) && $filtro_azienda !== '-1') {
            $where_dipendenti[] = "(dipendenti_azienda = '{$filtro_azienda}')";
        }
        
        $dipendenti = $this->apilib->search('dipendenti', $where_dipendenti);
        
        if (empty($dipendenti)) {
            throw new ApiException("ATTENZIONE: Nessun dipendente trovato. Verifica di aver impostato correttamente i filtri e riprova");
            return;
        }

        //$where_presenze = ['presenze_dipendente' => $dipendente['dipendenti_id']];
        $where_presenze = ['presenze_dipendente IN (' . implode(',', array_column($dipendenti, 'dipendenti_id')) . ')'];
        if (!empty ($filtro_data_inizio)) {
            $filtro_data_ex = explode(' - ', $filtro_data_inizio);

            $filtro_data_da = (DateTime::createFromFormat('d/m/Y', $filtro_data_ex[0]))->format('Y-m-d');
            $filtro_data_a = (DateTime::createFromFormat('d/m/Y', $filtro_data_ex[1]))->format('Y-m-d');

            $where_presenze[] = "( DATE_FORMAT(presenze_data_inizio, '%Y-%m-%d') BETWEEN '{$filtro_data_da}' AND '{$filtro_data_a}' )";
        }

        $fine_mese = (new DateTime($filtro_data_da ?? ''))->format('Y-m-t');

        //$presenze_dipendente = $this->apilib->search('presenze', $where_presenze, null, 0, 'presenze_data_inizio', 'ASC', 2);
        $_presenze_dipendenti = $this->db->where(implode(' AND ', $where_presenze), null, false)->
            join('richieste', 'presenze_richiesta = richieste_id', 'left')->
            join('dipendenti', 'presenze_dipendente = dipendenti_id', 'left')->
            join('aziende', 'dipendenti_azienda = aziende_id', 'left')->
            join('richieste_tipologia', 'richieste_tipologia = richieste_tipologia_id', 'left')->
            join('richieste_sottotipologia', 'richieste_sottotipologia = richieste_sottotipologia_id', 'left')
            ->order_by('presenze_data_inizio', 'ASC')
            ->get('presenze')
            ->result_array();
        $presenze_dipendenti = [];
        foreach ($_presenze_dipendenti as $presenza) {
            $presenze_dipendenti[$presenza['presenze_dipendente']][] = $presenza;
        }
        
        // debug($presenze_dipendenti,true);
        
        $array = ['Dipendente' => []];
        
        foreach ($dipendenti as $dipendente) {
            // debug($dipendente['dipendenti_nome'] . ' ' . $dipendente['dipendenti_cognome']);
            
            $array_dipendente_movimenti = [
                'Movimento' => [],
                
                '@attributes' => [
                    'GenerazioneAutomaticaDaTeorico' => 'N',
                ],
            ];
            $array_dipendente_voci_retributive = [];
            $array_dipendente_forzatura_giustificativi = [];
            
            
            
            $presenze_dipendente = $presenze_dipendenti[$dipendente['dipendenti_id']] ?? [];
            //debug($presenze_dipendente,true);
            if (empty($presenze_dipendente)) {
                continue;
            }
            
            $buoni_pasto_dipendente = [];
            $presenze_giornaliere_dipendente = [];
            foreach ($presenze_dipendente as $presenza) {
                $data_presenza = dateFormat($presenza['presenze_data_inizio'], 'Y-m-d');
                
                if (!isset($presenze_giornaliere_dipendente[$data_presenza]['timbrature'])) {
                    $presenze_giornaliere_dipendente[$data_presenza]['timbrature'] = [];
                }
                
                if (!isset($presenze_giornaliere_dipendente[$data_presenza]['straordinari'])) {
                    $presenze_giornaliere_dipendente[$data_presenza]['straordinari'] = [];
                }
                
                if (!isset($presenze_giornaliere_dipendente[$data_presenza]['richieste_fp'])) {
                    $presenze_giornaliere_dipendente[$data_presenza]['richieste_fp'] = [];
                }
                
                if (!empty($presenza['presenze_richiesta'])) {
                    // 2024-03-04 - michael - forzo un codice fittizio per la trasferta se non ha un codice suo, per gestire successivamente il calcolo delle ore
                    if ($presenza['richieste_tipologia'] === '5' && empty($presenza['richieste_sottotipologia_codice'])) { // tipologia = Trasferta
                        $presenza['richieste_sottotipologia_codice'] = '_TRASFERTA';
                    }
                    
                    if (in_array($presenza['richieste_tipologia'], [1,2]) && $presenza['richieste_utilizzo_banca_ore'] == DB_BOOL_TRUE) { // tipologia = banca ore
                        $presenza['richieste_sottotipologia_codice'] = '_BANCA_ORE';
                    }

                    $presenze_giornaliere_dipendente[$data_presenza]['richieste_fp'][$presenza['richieste_sottotipologia_codice']][] = $presenza;
                } else {
                    $presenze_giornaliere_dipendente[$data_presenza]['timbrature'][] = $presenza;
                    
                    if ($presenza['presenze_straordinario'] > 0) {
                        $presenze_giornaliere_dipendente[$data_presenza]['straordinari'][] = $presenza;
                    }
                }
                
                if ($presenza['presenze_buono_pasto'] == DB_BOOL_TRUE) {
                    $buoni_pasto_dipendente[$data_presenza] = 1;
                }
            }
            
            foreach ($presenze_giornaliere_dipendente as $giorno => $tipi_presenze) {
                $ore_previste = $this->timbrature->calcolaOreGiornalierePreviste($giorno, $dipendente['dipendenti_id'], true);
                $giorno_settimanale = date('w', strtotime($giorno));
                
                // debug('giorno: ' . $giorno);
                // debug('ore previste: ' . $ore_previste);
                // debug('giorno settimanale: ' . $giorno_settimanale);
                
                $ore_giornaliere = 0;
                $cod_turno = null;
                $cod_giustificativo_ril_pres = null;
                foreach ($tipi_presenze as $tipo => $presenze) {
                    if (!empty($presenze)) {
                        if ($tipo === 'richieste_fp') {
                            // debug($presenze, true);
                            foreach ($presenze as $codice_richiesta => $presenze_richiesta) {
                                $is_trasferta = $is_banca_ore = false;
                                if ($codice_richiesta === '_TRASFERTA') {
                                    $is_trasferta = true;
                                    $codice_richiesta = '';
                                }
                                if ($codice_richiesta === '_BANCA_ORE') {
                                    $is_banca_ore = true;
                                    $codice_richiesta = '';
                                }
                                
                                if (!$is_trasferta && !$is_banca_ore) {
                                    $ore_richieste_fp = 0;
                                    foreach ($presenze_richiesta as $presenza) {
                                        if ($presenza['richieste_tipologia'] === '3') { // malattia
                                            if (!in_array($giorno_settimanale, [0,6])) { // se non è sabato o domenica
                                                $ore_richieste_fp += $presenza['presenze_ore_totali'];
                                            } else { // se lo è, non conta
                                            
                                            }
                                        } else {
                                            $ore_richieste_fp += $presenza['presenze_ore_totali'];
                                        }
                                    }
                                    
                                    // Converte le ore decimali in ore e minuti
                                    $ore = floor($ore_richieste_fp); // Calcola il numero intero di ore
                                    $minuti = round(($ore_richieste_fp - $ore) * 60); // Calcola i minuti
                                    
                                    $array_dipendente_movimento = [
                                        'CodGiustificativoUfficiale' => $codice_richiesta,
                                        'Data' => $giorno,
                                        'NumOre' => $ore,
                                        'NumMinuti' => $minuti,
                                    ];
                                    
                                    $ore_giornaliere += $ore_richieste_fp;
                                    
                                    $array_dipendente_movimenti['Movimento'][] = $array_dipendente_movimento;
                                }
                            }
                        }
                        if ($tipo === 'straordinari') {
                            foreach ($presenze as $presenza) {
                                $ore_straordinario = $presenza['presenze_straordinario'];
                                
                                // Converte le ore decimali in ore e minuti
                                $ore = floor($ore_straordinario); // Calcola il numero intero di ore
                                $minuti = round(($ore_straordinario - $ore) * 60); // Calcola i minuti
                                
                                $array_dipendente_forzatura_giustificativo = [
                                    'CodTipoGiustificativo' => 'S',
                                    'DataMovimento' => dateFormat($presenza['presenze_data_inizio'], 'Y-m-d'),
                                    // 'NumMinutiCentesimi' => $presenza['presenze_straordinario'] * 60,
                                    'NumOre' => $ore,
                                    'NumMinuti' => $minuti,
                                ];
                                
                                $ore_giornaliere += $presenza['presenze_straordinario'];
                                
                                $array_dipendente_forzatura_giustificativi['Giustificativo'][] = $array_dipendente_forzatura_giustificativo;
                            }
                        } else {
                            $array_dipendente_forzatura_giustificativo = [
                                'DataMovimento' => $giorno,
                                'CodTipoGiustificativo' => 'S',
                            ];
                            
                            $array_dipendente_forzatura_giustificativi['Giustificativo'][] = $array_dipendente_forzatura_giustificativo;
                        }
                        
                        // if ($tipo === 'timbrature') {
                        //     $ore_giornaliere = 0;
                        //
                        //     foreach ($presenze as $presenza) {
                        //         $ore_giornaliere += ($presenza['presenze_ore_totali'] - $presenza['presenze_straordinario']);
                        //     }
                        //
                        //     $pause = $this->timbrature->calcolaOrePausaPranzo($giorno, $dipendente['dipendenti_id']);
                        //     $ore_giornaliere -= $pause;
                        //
                        //     if ($ore_giornaliere <= 0) {
                        //         $ore_giornaliere = 0;
                        //     }
                        //
                        //     $array_dipendente_movimento = [
                        //         'Data' => $giorno,
                        //         'NumMinutiInCentesimi' => $ore_giornaliere * 60,
                        //     ];
                        //
                        //     $array_dipendente_movimenti['Movimento'][] = $array_dipendente_movimento;
                        // }
                        
                        foreach ($presenze as $presenza) {
                            // verifico se il campo presenze_codice_turno_zucchetti è popolato e se il valore è un numero da 1 a 9, se si allora lo assegno alla variabile $cod_turno
                            
                            // michael, 28/06/2024 - se leggi questo, vuol dire che sei ricapitato su questo punto perchè non è stato messo un codice NUMERICO da 1 a 9.
                            // Sappi che questo controllo NON è a caso, bensì è dato dalla DOCUMENTAZIONE UFFICIALE DI ZUCCHETTI. (cerca CodTurno nel pdf).
                            if (!empty($presenza['presenze_codice_turno_zucchetti']) && is_numeric($presenza['presenze_codice_turno_zucchetti']) && $presenza['presenze_codice_turno_zucchetti'] >= 1 && $presenza['presenze_codice_turno_zucchetti'] <= 9) {
                                $cod_turno = $presenza['presenze_codice_turno_zucchetti'];
                            }
                            
                            //------------------------------------//
                            // michael, 02/07/2024 - questo codice va impostato sul campo "codice giustificativo (zucchetti)" nel nuovo/modifica presenza. Per info, cerca sul pdf della documentazione zucchetti, il campo CodGiustificativoRilPres.
                            if (!empty($presenza['presenze_codice_giustificativo_presenza'])) {
                                $cod_giustificativo_ril_pres = $presenza['presenze_codice_giustificativo_presenza'];
                            }
                        }
                    }
                }
                
                // debug('ore giornaliere: ' . $ore_giornaliere);
                
                $ore_ordinarie = $ore_previste - $ore_giornaliere;
                
                if ($ore_ordinarie <= 0) {
                    $ore_ordinarie = 0;
                }
                
                // Converte le ore decimali in ore e minuti
                $ore = floor($ore_ordinarie); // Calcola il numero intero di ore
                $minuti = round(($ore_ordinarie - $ore) * 60); // Calcola i minuti
                
                $array_dipendente_movimento = [
                    'CodGiustificativoUfficiale' => '01',
                    'Data' => $giorno,
                    // 'NumMinutiInCentesimi' => $ore_ordinarie * 60,
                    'NumOre' => $ore,
                    'NumMinuti' => $minuti,
                ];
                
                if (!empty($cod_turno)) {
                    $array_dipendente_movimento['CodTurno'] = (string) $cod_turno;
                }
                
                if (!empty($cod_giustificativo_ril_pres)) {
                    $array_dipendente_movimento['CodGiustificativoRilPres'] = (string) $cod_giustificativo_ril_pres;
                }
                
                $array_dipendente_movimenti['Movimento'][] = $array_dipendente_movimento;
                
                $array_dipendente_forzatura_giustificativo = [
                    'DataMovimento' => $giorno,
                    'CodTipoGiustificativo' => 'O',
                ];
                
                $array_dipendente_forzatura_giustificativi['Giustificativo'][] = $array_dipendente_forzatura_giustificativo;
                
                // debug('ore ordinarie: ' . $ore_ordinarie);
                // debug('------');
            }
            
            if (!empty($buoni_pasto_dipendente)) {
                $voce_retributiva = [
                    'CodVoceRilPres' => '0134',
                    'DataElaborazione' => $fine_mese,
                    'CodTipoCedolino' => 50,
                    // 50 = Cedolino normale, "31-38" cedolini aggiuntivi, 40 = cedolino aggiuntivo automatico
                    'CodTipoVoce' => 'G',
                    // H = Ore, I = Importo, G = Giorni, M = Mese
                    'Quantita' => count($buoni_pasto_dipendente),
                ];
                
                $array_dipendente_voci_retributive['Voce'][] = $voce_retributiva;
            }
            
            $where_note_spese_dipendente = ['nota_spese_dipendente' => $dipendente['dipendenti_id']];
            
            if (!empty($filtro_data_da) && !empty($filtro_data_a)) {
                $where_note_spese_dipendente[] = "( DATE_FORMAT(nota_spese_data, '%Y-%m-%d') BETWEEN '{$filtro_data_da}' AND '{$filtro_data_a}' )";
            }
            
            $note_spese_dipendente = $this->apilib->search('nota_spese', $where_note_spese_dipendente);
            
            if (!empty($note_spese_dipendente)) {
                $importo_note_spese = 0;
                foreach ($note_spese_dipendente as $nota_spese) {
                    $importo_note_spese += $nota_spese['nota_spese_importo'];
                }
                
                // Se l'importo delle note spese è maggiore di 0, aggiungi la voce retributiva
                if ($importo_note_spese > 0) {
                    $voce_retributiva = [
                        'CodVoceRilPres' => '0152',
                        // note spese / rimborsi piè di lista
                        'DataElaborazione' => $fine_mese,
                        'CodTipoCedolino' => 50,
                        // 50 = Cedolino normale, "31-38" cedolini aggiuntivi, 40 = cedolino aggiuntivo automatico
                        'CodTipoVoce' => 'I',
                        // H = Ore, I = Importo, G = Giorni, M = Mese
                        'ImpVoce' => number_format($importo_note_spese, 2, '.', ''),
                    ];
                    
                    $array_dipendente_voci_retributive['Voce'][] = $voce_retributiva;
                }
            }
            
            if (empty($array_dipendente_movimenti['Movimento'])) {
                continue;
            }
            
            $array_dipendente = [
                'Movimenti' => $array_dipendente_movimenti,
            ];
            
            if (!empty($array_dipendente_voci_retributive)) {
                $array_dipendente['VociRetributive'] = $array_dipendente_voci_retributive;
            }
            
            if (!empty($array_dipendente_forzatura_giustificativi)) {
                $array_dipendente['ForzatureGiustificativi'] = $array_dipendente_forzatura_giustificativi;
            }
            
            $attributi = [
                '@attributes' => [
                    'CodAziendaUfficiale' => (!empty($dipendente['aziende_codice'])) ? str_pad($dipendente['aziende_codice'], 6, '0', STR_PAD_LEFT) : '',
                    'CodDipendenteUfficiale' => str_pad($dipendente['dipendenti_codice_esterno'], 7, '0', STR_PAD_LEFT),
                ]
            ];
            
            $array_dipendente = array_merge($array_dipendente, $attributi);
            
            if (empty($array_dipendente['Movimenti'])) {
                continue;
            }
            
            $array['Dipendente'][] = $array_dipendente;
            
            // die('x');
        }
        
        $xmlData = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8" ?><Fornitura/>');
        
        // debug($array, true);
        
        $this->array_to_xml($array, $xmlData);
        
        // display XML to screen
        header("Content-type: text/xml");
        
        if (!$view)
            header('Content-Disposition: attachment; filename="presenze.xml"');
        
        $xmlDocument = new DOMDocument('1.0');
        $xmlDocument->preserveWhiteSpace = false;
        $xmlDocument->formatOutput = true;
        $xmlDocument->loadXML($xmlData->asXML());
        
        echo $xmlDocument->saveXML();
    }
    
    private function array_to_xml ($data, &$xml_data = null, $first_element = '')
    {
        $node_counter = 0;
        
        foreach ($data as $key => $value) {
            if ($key === '@attributes') {
                foreach ($value as $attr_key => $attr_value) {
                    $xml_data->addAttribute($attr_key, $attr_value);
                }
            } else if (is_array($value)) {
                if (array_key_exists(0, $value)) {
                    // È un array di elementi
                    foreach ($value as $item) {
                        $subnode = $xml_data->addChild($key);
                        $this->array_to_xml($item, $subnode);
                        $node_counter++;
                    }
                } else {
                    // È un singolo elemento
                    $subnode = $xml_data->addChild($key);
                    $this->array_to_xml($value, $subnode);
                    $node_counter++;
                }
            } else {
                $xml_data->addChild($key, htmlspecialchars($value));
            }
        }
    }
}
