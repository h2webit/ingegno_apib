<style>
.styled-table {
    border-collapse: collapse;
    /* margin: 25px 0; */
    font-size: 14px;
    font-family: sans-serif;
    min-width: 400px;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.15);
}

.styled-table thead tr {
    background-color: #3c8dbc;
    color: #ffffff;
    text-align: left;
}

.styled-table th,
.styled-table tr,
.styled-table td {
    padding: 12px 15px;
}

.styled-table tbody tr {
    border-bottom: 1px solid #dddddd;
}

.styled-table tbody tr:last-of-type {
    border-bottom: 2px solid #3c8dbc;
}

.styled-table tbody tr.active-row {
    font-weight: bold;
    color: #3c8dbc;
}
</style>
<div id="timbrature">
    <table class="styled-table">
        <thead>
            <tr>
                <th>
                    Dipendente
                </th>
                <th>
                    Data
                </th>
                <th>
                    Turno atteso
                </th>
                <th>Orario registrato</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php
        $anomalie = array();
        $current_date_format = date('Y-m-d');

        $this->db->select('t.*,d.dipendenti_nome,d.dipendenti_cognome');
        $this->db->from('turni_di_lavoro t');
        $this->db->join('dipendenti d', 't.turni_di_lavoro_dipendente = d.dipendenti_id');
        $this->db->where('t.turni_di_lavoro_data_inizio <=', $current_date_format);
        $this->db->where('d.dipendenti_attivo', 1);
        $this->db->where('(d.dipendenti_presenza_automatica = 0 OR d.dipendenti_presenza_automatica IS NULL)');
        //se oggi vedo anche l'inizio
        $this->db->where('t.turni_di_lavoro_ora_inizio <', date('H:i'));
        $this->db->where('(t.turni_di_lavoro_data_fine >= ' . $this->db->escape($current_date_format) . ' OR t.turni_di_lavoro_data_fine IS NULL)');
        $this->db->where('t.turni_di_lavoro_giorno', date('N', strtotime($current_date_format)));
        $turni = $this->db->get()->result_array();
    
        //$subquery = "(SELECT COUNT(*) FROM presenze p WHERE p.presenze_dipendente = t.turni_di_lavoro_dipendente AND DATE(p.presenze_data_inizio) = '{$current_date_format}') = 0";

        //debug($this->db->last_query());
        
        $date_ita = date('d-m-Y');
        if(!empty($turni))
        {
            foreach($turni as $turno){
                $data_calendar_turno = $current_date_format." ".$turno['turni_di_lavoro_ora_inizio'].":00";
                //vedo se ha timbrato correttamente
                $this->db->select('*');
                $this->db->from('presenze');
                $this->db->where('presenze_dipendente', $turno['turni_di_lavoro_dipendente']);
                $this->db->where('presenze_data_inizio', $current_date_format);
                $presenze = $this->db->get()->result_array();

                if(!empty($presenze)){
                    foreach($presenze as $presenza){
                        /* @TODO gestire anche le uscite anticipate */
                        if($presenza['presenze_data_inizio_calendar'] <= $data_calendar_turno){
                            //ok, ho timbrato prima, quindi vado avanti.
                            continue;
                        } else{
                            $anomalie[] = [
                                "dipendente" => $turno['turni_di_lavoro_dipendente'],
                                "turno" => $turno,
                                "presenza" => $presenza,
                                "data" => $current_date_format
                            ];
                            //qua vuol dire che non ho timbrato!
                        }
                    }
                } else{
                    $anomalie[] = [
                        "dipendente" => $turno['turni_di_lavoro_dipendente'],
                        "turno" => $turno,
                        "presenza" => '',
                        "data" => $current_date_format
                    ];
                }
            }
        }

    $anomalie_continue = false; // Variabile flag per il loop delle anomalie
    foreach($anomalie as $anomalia){

            $data_calendar_turno = $anomalia['data']." ".$anomalia['turno']['turni_di_lavoro_ora_inizio'].":00";
            //verifico se aveva un permesso
            $this->db->select('*');
            $this->db->from('richieste');
            $this->db->where('richieste_user_id', $anomalia['dipendente']);
            $this->db->where('richieste_stato', 2);
            $richieste = $this->db->get()->result_array();
            if (!empty($richieste)) {
                foreach ($richieste as $richiesta) {
                    // se la richiesta comprende oggi
                    if (dateFormat($richiesta['richieste_data_ora_inizio_calendar'], 'Y-m-d H:i') <= date('Y-m-d H:i', strtotime($data_calendar_turno)) && dateFormat($richiesta['richieste_al'], 'Y-m-d') >= date('Y-m-d H:i', strtotime($data_calendar_turno))) {
                        $anomalie_continue = true; // Imposta il flag a true
                        break; // Esci dal loop delle richieste
                    } elseif (dateFormat($richiesta['richieste_data_ora_inizio_calendar'], 'Y-m-d') <= $data_calendar_turno && empty($richiesta['richieste_al']) && $richiesta['richieste_tipologia'] == 3) {
                        $anomalie_continue = true; // Imposta il flag a true
                        break; // Esci dal loop delle richieste
                    } elseif ((strtotime($richiesta['richieste_data_ora_inizio_calendar']) == date('Y-m-d H:i', strtotime($data_calendar_turno)))) {
                        $anomalie_continue = true; // Imposta il flag a true
                        break; // Esci dal loop delle richieste
                    }
                }
            }
                
            if ($anomalie_continue) {
                continue; // Se il flag è true, continua il loop delle anomalie
            }

            echo "<tr>";
            echo "<td><a target='_blank' href='".base_url('main/layout/dettaglio-dipendente/'.$anomalia['dipendente'])."'>" . $anomalia['turno']['dipendenti_nome']." ".$anomalia['turno']['dipendenti_cognome'] . "</td></a>";
            echo "<td>" . dateFormat($anomalia['data']) . "</td>";
            echo "<td>" . $anomalia['turno']['turni_di_lavoro_ora_inizio']." - " . $anomalia['turno']['turni_di_lavoro_ora_fine']."</td>";
            if(!empty($anomalia['presenza'])){
                echo "<td>" . $anomalia['presenza']['presenze_ora_inizio']." - " . $anomalia['presenza']['presenze_ora_fine']."</td>";
            } else {
                echo "<td></td>";
            }
            // Colonna azioni per inserire se manca completamente la presenza o modificarla se anomala
            if(empty($anomalia['presenza'])){
                echo "<td style='text-align: center;'><a href=".base_url('get_ajax/modal_form/form-presenze?presenze_dipendente='.$anomalia['dipendente'])." class='btn btn-success btn-xs js_open_modal'>Crea presenza</a></td>";
            } else {
                echo "<td style='text-align: center;'><a href=".base_url('get_ajax/modal_form/form-presenze/'.$anomalia['presenza']['presenze_id'])." class='btn bg-purple btn-xs js_open_modal'>Modifca</a></td>";
            }
            echo "</tr>";

    }

   
    ?>
        </tbody>
    </table>

</div>