<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Configuratore extends CI_Model
{
    public $presenza = null;
    public $rules = [];

    public $rules_mapping = [
        //Mappature dirette col valore su db
        'data_in' => 'presenze_data_inizio',
        'data_out' => 'presenze_data_fine',
        'dipendente' => 'presenze_dipendente',
        'ora_inizio' => 'presenze_ora_inizio',
        'ora_fine' => 'presenze_ora_fine',
        'ignora_pausa' => 'dipendenti_ignora_pausa',

        //Mappature con ricalcolo valori on-the-fly
        'giorno_in' => '_giorno_in',
        'differenza_entrata_orario_previsto' => '_differenza_entrata_orario_previsto',
        'differenza_uscita_orario_previsto' => '_differenza_uscita_orario_previsto',
        'ore_giornaliere_lavorate' => '_ore_giornaliere_lavorate',
        'ora_entrata_turno' => '_ora_entrata_turno',
        'ora_uscita_turno' => '_ora_uscita_turno',
        'ore_giornaliere_previste' => '_ore_giornaliere_previste',
        'turni_giornalieri_previsti' => '_turni_giornalieri_previsti',
        'ore_straordinarie' => '_ore_straordinarie',
        'ore_previste' => '_ore_previste'

    ];

    public $values_mapping = [
        //Rimappo dopo così posso utilizzare tutti i placeholder già presenti nelle rules_mapping usate dagli IF delle condizioni
        // '{ora_entrata_turno}' => '_ora_entrata_turno',
        // '{ora_uscita_turno}' => '_ora_uscita_turno'
    ];

    public function __construct()
    {
        $this->load->model('modulo-hr/timbrature');
        foreach ($this->rules_mapping as $key => $map) {
            $this->values_mapping['{' . $key . '}'] = $map;
        }
        parent::__construct();
    }


    public function getRules($where = [])
    {

        $_rules = $this->apilib->search('hr_rules', array_merge([
            'hr_rules_active' => 1,

        ], $where));


        $this->rules = array_map(function ($rule) {
            $rule['hr_rules_json'] = json_decode($rule['hr_rules_json'], true);
            return $rule;
        }, $_rules);
    }


    public function preProcessRules($data)
    {
        $data['post'] = $this->additionalData($data['post']);
        $this->getRules(['hr_rules_scope' => 2]);
        //Prendo tutte le rules di tipo pre
        foreach ($this->rules as $rule) {
            
            if ($this->isApplicableRule($rule['hr_rules_json'], $data['post'])) {

                $this->executeAction($rule, $data['post']);
            }

        }
        //debug($data,true);
        //Arrivato qua ripulisco l'array $data dalle variabili interne con underscore (altrimenti apilib fallisce perchè quei campi non esistono)
        foreach ($data['post'] as $key => $val) {
            if (stripos($key, '_') === 0 || stripos($key, 'dipendenti') === 0) {
                unset($data['post'][$key]);
            }
        }
        //debug($data);
        return $data;
    }
    public function postProcessRules($data)
    {
        
        
        $data = $this->additionalData($data);

        
        $this->getRules(['hr_rules_scope' => 1]);
        foreach ($this->rules as $rule) {

            

            if ($this->isApplicableRule($rule['hr_rules_json'], $data)) {
                $this->executeAction($rule, $data);
            }

        }

        
        
        // foreach($data as $key => $val) {
        //     if(stripos($key, '_') === 0) { //Se inizia con _
        //         unset($data[$key]);
        //     }
        // }
        
        return $data;
    }

    private function additionalData($presenza)
    {
        //Ognuna di queste funzioni, per comodità, non ritorna l'array, ma lo modifica per riferimento (occhio all'& nel parametro della funzione)
        $this->calcoloGiorniSettimana($presenza);

        $this->calcoloDifferenzeOrariPrevisti($presenza);

        
        $this->calcoloOreGiornaliereLavorate($presenza);


        //Valori dinamici
        $this->calcoloOrarioEntrataUscitaPrevisto($presenza);

        $this->calcoloOreGiornalierePreviste($presenza);
    
        $this->calcoloTurniGiornalieriPrevisti($presenza);

        $this->calcoloOreStraordinarie($presenza);

        $this->calcoloOrePreviste($presenza);

        $this->datiDipendente($presenza);



        return $presenza;
    }

    private function calcoloOreStraordinarie(&$presenza)
    {
        $dipendente_id = $presenza['presenze_dipendente'];
        $Ymd = substr($presenza['presenze_data_inizio'], 0, 10);
        //$ore_straordinarie = $this->timbrature->calcolaOreStraordinarieBase($presenza);
        $ore_straordinarie = $this->timbrature->calcolaOreStraordinarie($Ymd, $dipendente_id);
        //debug($ore_straordinarie);
        if ($ore_straordinarie < 0) {
            $ore_straordinarie = 0;

        }
        $presenza['_ore_straordinarie'] = $ore_straordinarie;

        
    }

    private function calcoloOrePreviste(&$presenza)
    {
        $dipendente_id = $presenza['presenze_dipendente'];
        $Ymd = substr($presenza['presenze_data_inizio'], 0, 10);
        $ore_previste = $this->timbrature->calcolaOreGiornalierePreviste($Ymd, $dipendente_id);

        //debug($ore_previste);

        $presenza['_ore_previste'] = $ore_previste;
    }

    private function calcoloTurniGiornalieriPrevisti(&$presenza)
    {
        $orari_lavoro = $this->timbrature->getTurnoLavoro($presenza['presenze_data_inizio'], $presenza['presenze_dipendente']);

        

        $presenza['_turni_giornalieri_previsti'] = count($orari_lavoro);
    }
    private function calcoloDifferenzeOrariPrevisti(&$presenza)
    {
        if ($presenza['presenze_data_inizio']) {
            $presenza['presenze_data_inizio'] = $this->timbrature->stringToDate($presenza['presenze_data_inizio'])->format('Y-m-d');
        }
        if (!empty($presenza['presenze_data_fine'])) {

            $presenza['presenze_data_fine'] = $this->timbrature->stringToDate($presenza['presenze_data_fine'])->format('Y-m-d');

        }
        $orari_lavoro = $this->timbrature->getTurnoLavoro($presenza['presenze_data_inizio'], $presenza['presenze_dipendente']);

        $suggerimentoTurno = $this->timbrature->suggerisciTurno($presenza['presenze_ora_inizio'], $orari_lavoro, 'entrata');
        if (!empty($suggerimentoTurno) || $suggerimentoTurno === 0) {
            $orario_di_lavoro = $orari_lavoro[$suggerimentoTurno];
        } else {
            $orario_di_lavoro['turni_di_lavoro_ora_inizio'] = $presenza['presenze_ora_inizio'];
            // null in quanto se timbro entrata non ho l'ora di fine
            $orario_di_lavoro['turni_di_lavoro_ora_fine'] = $presenza['presenze_ora_fine'] ?? null;
        }

        if (empty($presenza['presenze_ora_inizio'])|| empty($orario_di_lavoro['turni_di_lavoro_ora_inizio'])) {
            $presenza['_differenza_uscita_orario_previsto'] = 0;

        } else {
            $orarioIngresso = DateTime::createFromFormat('H:i', $presenza['presenze_ora_inizio']);
            $ora_confronto = DateTime::createFromFormat('H:i', $orario_di_lavoro['turni_di_lavoro_ora_inizio']);
            $differenza = $orarioIngresso->diff($ora_confronto);
            $differenzaInMinuti = $differenza->h * 60 + $differenza->i;

            // Usa la proprietà 'invert' per determinare il segno
            if ($differenza->invert == 0) {
                $differenzaInMinuti = -$differenzaInMinuti;
            }

            $presenza['_differenza_entrata_orario_previsto'] = $differenzaInMinuti;
            if (!empty($presenza['presenze_ora_fine'])) {
                $orarioUscita = DateTime::createFromFormat('H:i', $presenza['presenze_ora_fine']);
                $ora_confronto = DateTime::createFromFormat('H:i', $orario_di_lavoro['turni_di_lavoro_ora_fine']);
                $differenza = $orarioUscita->diff($ora_confronto);
                $differenzaInMinuti = $differenza->h * 60 + $differenza->i;

                // Usa la proprietà 'invert' per determinare il segno
                if ($differenza->invert == 0) {
                    $differenzaInMinuti = -$differenzaInMinuti;
                }

                $presenza['_differenza_uscita_orario_previsto'] = $differenzaInMinuti;
            } else {
                $presenza['_differenza_uscita_orario_previsto'] = 0;
            }
        }

        


    }

    /**
     * Calcola le ore giornaliere lavorate da un dipendente.
     *
     * @param array $presenza Identificativo della presenza.
     * @param string $day Data per la quale calcolare le ore lavorate (default: data corrente).
     * @param string $now_time Ora per la quale calcolare le ore lavorate (default: ora corrente).
     * @return float Numero di minuti giornalieri lavorati.
     */
    private function calcoloOreGiornaliereLavorate(&$presenza, $day = null, $now_time = null)
    {
        //TODO: Sostituire usando il model Timbrature

        if (empty($presenza)) {
            throw new ApiException('errore, presenza non trovata');
            exit;
        }
        if ($day === null) {
            $day = date('Y-m-d', strtotime($presenza['presenze_data_inizio']));
        }
        if ($now_time === null) {
            $now_time = date('H:i');
        }
        $minuti_totali_lavorati = 0;
        $this->mycache->clearEntityCache('presenze');
        $presenze = $this->apilib->search('presenze', [
            'presenze_dipendente' => $presenza['presenze_dipendente'],
            "DATE_FORMAT(presenze_data_inizio, '%Y-%m-%d') = '{$day}'"
        ]);
        //debug($presenze);
        foreach ($presenze as $_presenza) {

            $day_number = date('w', strtotime($_presenza['presenze_data_inizio']));

            $this->db->where("turni_di_lavoro_data_inizio <= '{$_presenza['presenze_data_inizio']}'", null, false);
            $this->db->where("(turni_di_lavoro_data_fine >= '{$_presenza['presenze_data_inizio']}' OR turni_di_lavoro_data_fine IS NULL)", null, false); //aggiungo anche il vuoto, se uno non imposta la data di fine.
            $this->db->where('turni_di_lavoro_dipendente', $_presenza['presenze_dipendente']);
            $this->db->join('orari_di_lavoro_ore_pausa', 'turni_di_lavoro_pausa = orari_di_lavoro_ore_pausa_id', "left");
            $this->db->where('turni_di_lavoro_giorno', $day_number);
            $orari_di_lavoro = $this->db->get('turni_di_lavoro')->result_array();

            $suggerimentoTurno = $this->timbrature->suggerisciTurno($_presenza['presenze_ora_inizio'], $orari_di_lavoro, 'entrata');

            if (!empty($presenze['presenze_pausa'])) {
                $pausa = $presenze['presenze_pausa'];
            } else {
                $pausa = $orari_di_lavoro[$suggerimentoTurno]['orari_di_lavoro_ore_pausa_value'] ?? 0;
            }
            //debug($_presenza);
            //se ho timbrato l'uscita, allora vado a prendere il totale direttamente dalla presenza e tolgo la pausa
            if (!empty($_presenza['presenze_data_fine'])) {
                // debug($_presenza['presenze_ore_totali']);
                // debug($pausa);
                $ore_ordinarie = ($_presenza['presenze_ore_totali'] - $pausa < 0) ? 0 : $_presenza['presenze_ore_totali'] - $pausa;
                // debug($ore_ordinarie);
                // debug($pausa);
                $minuti_totali_lavorati += $ore_ordinarie * 60;

            } else {
                //non ho ancora timbrato l'uscita, vado a prendermi le ore lavorate fino ad ora, ma non tolgo la pausa perchè non posso sapere se è prima o dopo l'ora attuale.
                $ora_inizio = new DateTime("{$_presenza['presenze_data_inizio_calendar']}");
                $ora_fine = new DateTime("{$day} {$now_time}");
                $differenza = $ora_inizio->diff($ora_fine);
                $minuti = $differenza->i;
                $ore = $differenza->h;
                $giorni = $differenza->d;


                $minuti_totali_lavorati += $giorni * 24 * 60 + $ore * 60 + $minuti;
            }
        }

        $presenza['_ore_giornaliere_lavorate'] = $minuti_totali_lavorati / 60;

        
    }

    private function calcoloGiorniSettimana(&$presenza)
    {
        //debug($presenza,true);
        if ($presenza['presenze_data_inizio']) {
            $presenza['_giorno_in'] = $this->timbrature->getDayOfWeek($presenza['presenze_data_inizio']);
        } else {
            $presenza['_giorno_in'] = null;
        }
        if (!empty($presenza['presenze_data_fine'])) {
            $presenza['_giorno_out'] = $this->timbrature->getDayOfWeek($presenza['presenze_data_fine']);
        } else {
            $presenza['_giorno_out'] = null;
        }
    }

    public function calcoloOreGiornalierePreviste(&$presenza)
    {
        // Data della presenza
        $dataPresenza = $presenza['presenze_data_inizio'];
        $dipendente_id = $presenza['presenze_dipendente'];
        
        $presenza['_ore_giornaliere_previste'] = $this->timbrature->calcolaOreGiornalierePreviste($dataPresenza,$dipendente_id);

        
    }

    public function datiDipendente(&$presenza)
    {
        // Data della presenza
        $dipendente = $this->apilib->view('dipendenti', $presenza['presenze_dipendente'], 1);

        if (!empty($dipendente)) {
            $presenza['dipendenti_ignora_pausa'] = $dipendente['dipendenti_ignora_pausa'];
        }
    }



    private function calcoloOrarioEntrataUscitaPrevisto(&$presenza)
    {
        $orari_lavoro = $this->timbrature->getTurnoLavoro($presenza['presenze_data_inizio'], $presenza['presenze_dipendente']);
        // debug($orari_lavoro);
        $suggerimentoTurnoEntrata = $this->timbrature->suggerisciTurno($presenza['presenze_ora_inizio'], $orari_lavoro, 'entrata');
        if (!empty($presenza['presenze_ora_fine'])) {
            $suggerimentoTurnoUscita = $this->timbrature->suggerisciTurno($presenza['presenze_ora_fine'], $orari_lavoro, 'entrata');
        } else {
            $suggerimentoTurnoUscita = $suggerimentoTurnoEntrata;
        }
        

        if (!empty($suggerimentoTurnoEntrata) || $suggerimentoTurnoEntrata === 0) {
            $orario_di_lavoro = $orari_lavoro[$suggerimentoTurnoEntrata];
            if ($suggerimentoTurnoUscita != $suggerimentoTurnoEntrata) {
                $orario_di_lavoro['turni_di_lavoro_ora_fine'] = $orari_lavoro[$suggerimentoTurnoUscita]['turni_di_lavoro_ora_fine'];
            }
        } else {
            $orario_di_lavoro['turni_di_lavoro_ora_inizio'] = $presenza['presenze_ora_inizio'];
            // null in quanto se timbro entrata non ho l'ora di fine
            $orario_di_lavoro['turni_di_lavoro_ora_fine'] = $presenza['presenze_ora_fine'] ?? null;
        }
        $presenza['_ora_entrata_turno'] = $orario_di_lavoro['turni_di_lavoro_ora_inizio'];
        
        $presenza['_ora_uscita_turno'] = $orario_di_lavoro['turni_di_lavoro_ora_fine'];
    }

    

    

    /**
     * Controlla se questa regola è applicabile
     *
     * @param array $rule
     * @param array $prenoData
     * @return boolean
     */
    private function isApplicableRule(array $rule, array $presenza)
    {
        //debug($rule, true);
        $contains_rules = (isset($rule['condition']) && isset($rule['rules']));
        $is_rule_definition = (isset($rule['id']) && isset($rule['type']) && isset($rule['value']) && isset($rule['operator']));

        if ($contains_rules) {
            /*
             * Abbiamo un contenitore di regole
             */
            $is_and = strtoupper($rule['condition']) === 'AND';
            foreach ($rule['rules'] as $sub_rule) {
                $is_applicable = $this->isApplicableRule($sub_rule, $presenza);

                // Se devono essere tutte vere e la mia regola corrente è falsa,
                // allora non proseguo e ritorno false
                // Al contrario, se ne basta una vera e la trovo ora, allora
                // posso interrompere l'algoritmo proseguo e ritornare true
                // ===
                // Le precedenti affermazioni si traducono in
                // if     (IS_AND && !IS_APPLICABLE) return false;
                // elseif (!IS_AND && IS_APPLICABLE) return true;
                // che ottimizzato diventa...
                if ($is_and != $is_applicable) {
                    return $is_applicable;
                }
            }

            // Se le ho ciclate tutte, allora significa che: se la condizione
            // era AND, allora sono tutte vere (quindi ritorno true), altrimenti
            // se la condizione era OR, allora sono tutte false (quindi ritorno
            // false)
            return $is_and;
        } elseif ($is_rule_definition) {
            /*
             * Abbiamo una definizione di regola
             */
            //Creo uno switch per le condizioni speciali, ovvero che non sono semplici operatori di confronto ma serve un codice ad hoc per questa verifica
            switch ($rule['id']) {

                case 'etichetta_timbratura_precedente':

                    return $this->doSpecialOperation($rule['id'], $rule['operator'], $rule['value'], $presenza);
                    break;

                default:
                    if (!array_key_exists($rule['id'], $this->rules_mapping)) {
                        debug("Campo '{$rule['id']}' non gestito.");

                        debug($presenza, true);

                        return true;
                    }
                    
                    if (!array_key_exists($this->rules_mapping[$rule['id']], $presenza)) {
                        debug($rule['id']);
                        debug($this->rules_mapping[$rule['id']]);
                        debug($presenza, true);
                    }
                    if (array_key_exists($rule['value'], $this->values_mapping)) {
                        //Se il valore di confronto è un campo dinamico, lo prendo dalla presenza (deve essere preimpostato dalla funzione additionalData, come per i valori usati nelle condizionie "IF")
                        $rule['value'] = $presenza[$this->values_mapping[$rule['value']]];
                    }

                    if ($rule['value'] === '0') {
                        $rule['value'] = 0;
                    }
                    
                    $return = $this->doOperation($presenza[$this->rules_mapping[$rule['id']]], $rule['operator'], $rule['value']);
                    
                    
                    return $return;
                    break;
            }
        } else {
            /*
             * Situazione anomala
             */
            return false; // throw exception [?]
        }
    }
    public function doSpecialOperation($id, $ruleOperator, $ruleValue, $presenza)
    {
        switch ($id) {
            case 'etichetta_timbratura_precedente':
                if (!empty($presenza['presenze_id'])) { //Sono in edit
                    $presenza_precedente = $this->apilib->searchFirst('presenze', [
                        'presenze_dipendente' => $presenza['presenze_dipendente'],
                        "presenze_id <> '{$presenza['presenze_id']}'",
                        'presenze_data_inizio =' => $presenza['presenze_data_inizio'],
                        'presenze_ora_inizio <' => $presenza['presenze_ora_inizio'],
                    ], 0, 'presenze_data_inizio desc,presenze_ora_inizio desc');

                                    

                } else { //Sono in inserimento
                    $presenza_precedente = $this->apilib->searchFirst('presenze', [
                        'presenze_dipendente' => $presenza['presenze_dipendente'],
                        'presenze_data_inizio <=' => $presenza['presenze_data_inizio'],
                    ], 0, 'presenze_data_inizio desc,presenze_ora_inizio desc');
                }
                
                if ($presenza_precedente) {
                    //debug($presenza_precedente,true);
                    if (!$presenza_precedente['presenze_etichette']) {
                        $presenze_etichette = [];
                    } else {
                        $presenze_etichette = json_decode($presenza_precedente['presenze_etichette'], true);
                    }
                    

                    if ($ruleOperator == 'equal') {
                        $return = in_array($ruleValue, $presenze_etichette);
                    } elseif ($ruleOperator == 'not_equal'){

                        $return = !in_array($ruleValue, $presenze_etichette);

                    }

                    if (substr($presenza['presenze_data_inizio'], 0, 10) == '2024-02-13') {
                        
                        // debug($presenze_etichette);
                        // debug($ruleValue);

                        // debug($return);
                        // debug($presenza_precedente, true);
                    }
                    return $return;

                }

                return false;
                break;

            default:
                debug("Controllo '{$id}' non riconosciuto!");
                break;
        }
    }
    /**
     * Esegue un'operazione booleana avendo i due operandi e il codice operatore
     *
     * @param mixed $value
     * @param string $ruleOperator
     * @param mixed $ruleValue
     * @return bool
     */
    private function doOperation($value, $ruleOperator, $ruleValue)
    {
        // debug($value);
        // debug($ruleOperator);
        // debug($ruleValue);
        switch ($ruleOperator) {
            case 'greater':
                return $value > $ruleValue;

            case 'greater_or_equal':
                return $value >= $ruleValue;

            case 'equal':
                return is_array($ruleValue) ? in_array($value, $ruleValue) : ($value == $ruleValue);

            case 'not_equal':

                return is_array($ruleValue) ? !in_array($value, $ruleValue) : ($value != $ruleValue);

            case 'less':
                if (is_numeric($value) && is_numeric($ruleValue)) {
                    $epsilon = 0.01;

                    return abs($value - $ruleValue) >= $epsilon && $value < $ruleValue;
                } else {
                    return $value < $ruleValue;
                }
                
                
                

            case 'less_or_equal':
                return $value <= $ruleValue;

            case 'between':
                return ($ruleValue[0] <= $value && $value <= $ruleValue[1]);

            case 'not_between':
                return !($ruleValue[0] <= $value && $value <= $ruleValue[1]);

            case 'in':
                return is_array($ruleValue) ? in_array($value, $ruleValue) : ($value == $ruleValue);
        }

        return false;
    }
    //Attenzione che anche qui $presenza viene passata per riferimento
    private function executeAction($rule, &$presenza)
    {
        $override_values = [];
        switch ($rule['hr_rules_actions_value']) {
            case 'Recupero orario':
                //Non capisco bene pre_validation con sebba fare questo... agiugnere una spunta "recupero orario" sulla presenza (che dovrebbe essere come field boolean?...)
                //debug('TODO: vedi commento qui sopra...',true);
                $override_values['xxx'] = 'TODO';
                break;
            case 'Anomalia':
                

                $override_values['presenze_anomalia'] = DB_BOOL_TRUE;

                if (!empty($rule['hr_rules_parameter']) && !stripos($presenza['presenze_note_anomalie'], $rule['hr_rules_parameter'])) {
                    $note_vecchie = ($presenza['presenze_note_anomalie']) ? "{$presenza['presenze_note_anomalie']}\n\r" : '';
                    $override_values['presenze_note_anomalie'] = "{$note_vecchie}(" . date('H:i') . ") Anomalia - {$rule['hr_rules_parameter']}"; //$rule['hr_rules_parameter'];
                }
                //debug($override_values);
                break;
            case 'Rimuovi anomalia':


                $override_values['presenze_anomalia'] = DB_BOOL_FALSE;
                $override_values['presenze_note_anomalie'] = '';

                break;
            case 'Blocco entrata':
                if ($rule['hr_rules_parameter']) {
                    $msg = $rule['hr_rules_parameter'];
                } else {
                    $msg = "Timbratura bloccata in base alle regole impostate.";
                }
                throw new ApiException($msg);
                // e_json(['status' => 0,'msg'=> $msg]);
                // exit;
                break;
            case 'Assegna etichetta':

                

                if (empty($presenza['presenze_etichette'])) {
                    $old_etichette = [];
                } else {
                    if (is_array($presenza['presenze_etichette'])) {
                        $old_etichette = $presenza['presenze_etichette'];
                    } else {
                        $old_etichette = json_decode($presenza['presenze_etichette'], true);
                    }

                }
                if (!in_array($rule['hr_rules_parameter'], $old_etichette)) {
                    $old_etichette[] = $rule['hr_rules_parameter'];
                }
                $old_etichette = array_filter($old_etichette);
                $override_values['presenze_etichette'] = json_encode($old_etichette);
                //debug($override_values,true);

                break;
            case 'Ignora straordinario entrata':
                $ore_straordinarie = $this->timbrature->calcolaOreStraordinarieBase($presenza, [
                    'presenze_ora_inizio' => ($presenza['presenze_ora_inizio'] < $presenza[$this->rules_mapping['ora_entrata_turno']])?$presenza[$this->rules_mapping['ora_entrata_turno']]: $presenza['presenze_ora_inizio'] //Calcolo gli straordinari come se fosse entrato nell'ora giusta
                ]);
                
                $override_values['presenze_straordinario'] = $ore_straordinarie>0?$ore_straordinarie:0;
                //debug($override_values['presenze_straordinario']);
                //$override_values['presenze_straordinario'] = 0;
                //debug(($presenza['presenze_ora_inizio'] < $presenza[$this->rules_mapping['ora_entrata_turno']]) ? $presenza[$this->rules_mapping['ora_entrata_turno']] : $presenza['presenze_ora_inizio'], true);

                break;
            case 'Ignora straordinario uscita':
                $ore_straordinarie = $this->timbrature->calcolaOreStraordinarieBase($presenza, [
                    'presenze_ora_fine' => $presenza[$this->rules_mapping['ora_uscita_turno']] //Calcolo gli straordinari come se fosse entrato nell'ora giusta
                ]);
                
                $override_values['presenze_straordinario'] = $ore_straordinarie > 0 ? $ore_straordinarie : 0;
                //debug($override_values['presenze_straordinario']);

                break;
            case 'Ignora straordinari': //Tutti
                // if ($presenza['presenze_id'] == 1862) {
                //     debug($presenza, true);
                // }
                /*$override_values['presenze_straordinario'] = $this->timbrature->calcolaOreStraordinarieBase($presenza, [
                    'presenze_ora_inizio' => $presenza[$this->rules_mapping['ora_entrata_turno']], //Calcolo gli straordinari come se fosse entrato nell'ora giusta
                    'presenze_ora_fine' => $presenza[$this->rules_mapping['ora_uscita_turno']] //Calcolo gli straordinari come se fosse entrato nell'ora giusta
                ]);*/

                //debug($presenza,true);

                $override_values['presenze_straordinario'] = 0;
                break;
            case 'Considera come straordinari': //Tutti
                if ($presenza['presenze_id'] == 1862) {
                    //debug($presenza, true);
                }
                $override_values['presenze_straordinario'] = $presenza['presenze_ore_totali'];
                $override_values['presenze_ore_totali'] = $override_values['presenze_straordinario'];

                break;
            case 'Banca ore': //Tutti
                //debug($presenza, true);
                if (!$presenza['presenze_straordinario']) {

                    $presenza['presenze_straordinario'] = $this->timbrature->calcolaOreStraordinarieBase($presenza);
                    //debug($presenza['presenze_straordinario']);

                }

                $this->timbrature->inserisciInBancaOre($presenza);

                $override_values['presenze_straordinario'] = 0;
                break;
            default:
                //debug($rule);
                debug("Azione regola '{$rule['hr_rules_actions_value']}' non gestita");
                return false;


        }
        if ($rule['hr_rules_scope_type'] == 'Dopo') {

            if ($override_values != []) {
                //Se è di tipo "dopo" allora devo eseguire una edit perchè non basta sovrascrivere l'array.
                //Non posso fare con apilib altrimenti entra in ricorsione, faccio con db
                $this->db->where('presenze_id', $presenza['presenze_id'])->update('presenze', $override_values);
                $this->mycache->clearEntityCache('presenze');
            }
        } else {


        }
        $presenza = array_merge($presenza, $override_values);
    }


    //Ritorna una stringa che descrive lo sconto
    public function rulesHumanReadableCondition($rule_db, $rules_json = null)
    {
        //debug($rule_db);
        if ($rules_json === null) {
            $rules_json = $rule_db['hr_rules_json'];
        }
        if (@json_decode($rules_json)) {
            $rules_json = json_decode($rules_json);
        }

        //debug($rule_db);

        $str = '';
        //Se ho regole da elaborare
        if (!empty($rules_json->rules) && !empty($rules_json->condition)) {
            $rules = [];
            foreach ($rules_json->rules as $rule) {
                $rules[] = $this->rulesHumanReadableCondition($rule_db, $rule);
            }
            return implode($rules_json->condition == 'AND' ? ' e ' : ' o ', $rules);
        } else {
            //$rules_json->value = implode(', ',(array)($rules_json->value));
            switch ($rules_json->field) {
                case 'dipendente':
                    $label = 'il dipendente';
                    $dipendente = $this->apilib->view('dipendenti', $rules_json->value);
                    $rules_json->value = "{$dipendente['dipendenti_nome']} {$dipendente['dipendenti_cognome']}";
                    break;
                case 'giorni':
                    $label = 'il giorno della settimana';
                    $giorni = ["domenica", "lunedì", "martedì", "mercoledì", "giovedì", "venerdì", "sabato"];

                    $rules_json->value = $giorni[$rules_json->value];
                    break;
                default:
                    $label = $rules_json->field;
                    break;
            }
            switch ($rules_json->operator) {
                case 'greater':
                    $operator = 'maggiore di';
                    break;
                case 'greater_or_equal':
                    $operator = 'maggiore o uguale a';
                    break;
                case 'less':
                    $operator = 'minore di';
                    break;
                case 'less_or_equal':
                    $operator = 'minore o uguale di';
                    break;
                case 'equal':
                    $operator = '';
                    break;
                case 'not_equal':
                    $operator = 'diverso da';
                    break;
                case 'between':
                    $operator = 'compreso tra';
                    break;
                case 'not_between':
                    $operator = 'non compreso tra';
                    break;
                default:
                    $operator = $rules_json->operator;
                    break;
            }

            $val = (is_array($rules_json->value)) ? implode(' e ', $rules_json->value) : $rules_json->value;
            
            $val = $this->replacePlaceholders($val);
            //debug($this->replacePlaceholders(0));
            return "se <strong>$label</strong> è $operator <strong>$val</strong>";
        }
    }

    public function rulesHumanReadableAction($rule_db)
    {
        return "allora <strong>" . strtolower($rule_db['hr_rules_actions_value']) . "</strong>" . (($rule_db['hr_rules_parameter'] ? " con <strong>{$rule_db['hr_rules_parameter']}</strong>" : ""));
    }

    public function rulesHumanReadable($rule_db)
    {

        return $this->rulesHumanReadableCondition($rule_db) . ', ' . $this->rulesHumanReadableAction($rule_db);
    }
    public function replacePlaceholders($val)
    {
        if (stripos($val, '}')) {
            //debug($val,true);
        }
        if ($val === '{ora_entrata_turno}') {
            return 'ora entrata prevista';
        }
        if ($val === '{ora_uscita_turno}') {
            return 'ora uscita prevista';
        }
        return $val;
    }

}