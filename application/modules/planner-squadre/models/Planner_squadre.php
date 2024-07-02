<?php

class Planner_squadre extends CI_Model
{
    public function generaRicorrenze($pianificazione)
    {

        $giorni_remap = [
            'Lunedì' => 1,
            'Martedì' => 2,
            'Mercoledì' => 3,
            'Giovedì' => 4,
            'Venerdì' => 5,
            'Sabato' => 6,
            'Domenica' => 7,
        ];
        
        $giorni_selezionati = [];
        if(!empty($pianificazione['pianificazione_lavori_giorni_settimana'])) {
            foreach ($pianificazione['pianificazione_lavori_giorni_settimana'] as $id => $giorno_testuale) {
                $giorni_selezionati[] = $giorni_remap[$giorno_testuale];
            }
        }

        $this->db->query("DELETE FROM appuntamenti WHERE appuntamenti_pianificazione IS NOT NULL AND appuntamenti_pianificazione <> '' AND appuntamenti_pianificazione = '{$pianificazione['pianificazione_lavori_id']}' AND DATE(appuntamenti_giorno) >= DATE(NOW())");
        
        //Se è un appuntamento ricorrente, creo le ricorrenze
        if (!empty($pianificazione['pianificazione_lavori_intervallo_ripetizione']) && $pianificazione['pianificazione_lavori_intervallo_ripetizione'] != 1) { // 1 è nessuna ripetizione
            //debug($this->input->post(),true);
            $operatori = $this->input->post('pianificazione_lavori_operatori');
            $automezzi = $this->input->post('pianificazione_lavori_automezzi');
            
            $event_title = '';
            if(!empty($pianificazione['pianificazione_lavori_operatori'])) {
                foreach ($pianificazione['pianificazione_lavori_operatori'] as $key => $value) {
                    $event_title .= $value.' ';
                }
            }
            
            if(!empty($pianificazione['pianificazione_lavori_automezzi'])) {
                $event_title .= '<br/>';
                foreach ($pianificazione['pianificazione_lavori_automezzi'] as $key => $value) {
                    $event_title .= $value.' ';
                }
            }

            $appuntamento = [
                'appuntamenti_titolo' => $event_title,
                'appuntamenti_cliente' => $pianificazione['pianificazione_lavori_cliente'],
                'appuntamenti_pianificazione' => $pianificazione['pianificazione_lavori_id'],
                'appuntamenti_impianto' => $pianificazione['pianificazione_lavori_commessa'],
                'appuntamenti_ora_inizio' => $pianificazione['pianificazione_lavori_ora_inizio'],
                'appuntamenti_ora_fine' => $pianificazione['pianificazione_lavori_ora_fine'],
                //'appuntamenti_persone' => $operatori,
                //'appuntamenti_automezzi' => $automezzi,
                'appuntamenti_note' => $pianificazione['pianificazione_lavori_note'],
                'appuntamenti_all_day' => DB_BOOL_FALSE,
                'appuntamenti_ora_inizio' => $pianificazione['pianificazione_lavori_ora_inizio'],
                'appuntamenti_ora_fine' => $pianificazione['pianificazione_lavori_ora_fine'],
                'appuntamenti_da_confermare' => $pianificazione['pianificazione_lavori_da_confermare'] ?? DB_BOOL_FALSE,
                'appuntamenti_checklist' => $pianificazione['pianificazione_lavori_checklist'] ?? null,
            ];

            //Ciclo dalla data dell'appuntamneto fino a domiciliari_turni_ripetizione_fino_al
            if (array_key_exists('old', $pianificazione)) {
                debug('TODO!', true);
                //Vuol dire che sono in modifica: lascio invariate i vecchi pagamenti e rigenero solo i futuri
                $data_da = date('Y-m-d H:i:s');
                //TODO: cambiare con un while data < oggi, partendo da data inizio, aumentare in base alla ripetizione e appena esce dal while quella sarà la data corretta di inizio.
            } else {
                $data_da = $pianificazione['pianificazione_lavori_data_inizio'];
            }

            $data_a = ($pianificazione['pianificazione_lavori_data_fine']) ?: date('2030-12-31 23:59:59');
            $begin = new DateTime($data_da);
            $end = new DateTime($data_a);
            $orig_end = new DateTime($data_a);

            switch ($pianificazione['pianificazione_lavori_intervallo_ripetizione']) {
                case '2': //Ogni giorno
                    $step = '1 day';
                    break;
                case '3': //Ogni settimana
                    $step = '1 week';
                    break;
                case '4': //Ogni 2 settimane
                    $step = '2 weeks';
                    break;
                case '5': //Ogni mese
                    $step = '1 month';
                    break;
                case '6': //Ogni 2 mesi
                    $step = '2 months';
                    break;
                case '7': //Ogni 3 mesi
                    $step = '3 months';
                    break;
                case '8': //Ogni 4 mesi
                    $step = '4 months';
                    break;
                case '9': //Ogni 6 mesi
                    $step = '6 months';
                    break;
                case '10': //Ogni anno
                    $step = '1 year';
                    break;
                case '11': //Ogni 2 anni
                    $step = '2 years';
                    break;
                case '12': //Ogni 5 settimane
                    $step = '5 weeks';
                    break;
                case '13': //Ogni 3 settimane
                        $step = '3 weeks';
                        break;
                default:
                    throw new ApiException("Ripetizione pianificazione non riconosciuta. Si prega di contattare l'assistenza.");
                    break;
            }

            $end = $end->modify("+{$step}");
            $interval = DateInterval::createFromDateString($step);
            $period = new DatePeriod($begin, $interval, $end);

            foreach ($period as $key => $dt) {
                if ($dt->format('Y-m-d') < date('Y-m-d')) {
                    //debug($dt,true);
                    continue;
                }
                if ($dt->format('Y-m-d') > $orig_end->format('Y-m-d')) {
                    break;
                } else {
                    //debug($orig_end->format('Y-m-d'));
                    //debug($dt,true);
                }

                //Se è impostato un giorno della settimana, cerco il primo giorno >= a $dt che coincida con quel giorno della settimana
                if ($giorni_selezionati) {
                    //Nel caso di ripetizione giornaliera deve semplicemente skippare se il giorno non coincide.
                    if ($pianificazione['pianificazione_lavori_intervallo_ripetizione'] == 2) { //Giornaliero
                        if (!in_array($dt->format('N'), $giorni_selezionati)) {
                            continue;
                        }
                    } else {
                        while (!in_array($dt->format('N'), $giorni_selezionati)) {
                            $dt->modify('+1 day');
                        }
                    }
                    //In tutti gli altri casi vale la regola del maggiore/uguale...
                }

                if($dt->format('Y-m-d') > $end->format('Y-m-d')) {
                    break;
                }

                $appuntamento['appuntamenti_giorno'] = $dt->format('Y-m-d H:i:s');

                //Calcolo la riga del planner (la prima libera)
                $riga = 1;
                while ($this->db->query("SELECT COUNT(appuntamenti_id) as c FROM appuntamenti WHERE appuntamenti_riga = '$riga' AND appuntamenti_giorno = '{$appuntamento['appuntamenti_giorno']}'")->row()->c > 0 && $riga < 20) {
                    $riga++;
                }
                $appuntamento['appuntamenti_riga'] = $riga;

                //$this->apilib->create('appuntamenti', $appuntamento);
                try {
                    $this->db->insert('appuntamenti', $appuntamento);
                    $new_appuntamento_id = $this->db->insert_id();

                    //inserisco persone in relazione
                    if(!empty($operatori)) {
                        foreach($operatori as $operatore) {
                            $this->db->insert('rel_appuntamenti_persone', [
                                'appuntamenti_id' => $new_appuntamento_id,
                                'users_id' => $operatore,
                            ]);
                        }
                    }
                    //inserisco automezzi in relazione
                    if(!empty($automezzi)) {
                        foreach($automezzi as $automezzo) {
                            $this->db->insert('rel_appuntamenti_automezzi', [
                                'appuntamenti_id' => $new_appuntamento_id,
                                'automezzi_id' => $automezzo,
                            ]);
                        }
                    }

                    $this->mycache->clearCache();
                } catch (Exception $e) {
                    log_message('error', "Impossibile creare la pianificazione. Errore: ". $e->getMessage());
                    throw new ApiException('Si è verificato un errore durante la creazione della pianificazione');
                    exit;
                }
                
            }
        }

    }
}