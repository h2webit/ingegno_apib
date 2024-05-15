<?php
$filters = $this->session->userdata(SESS_WHERE_DATA);

$filtro_testo = '';
$dipendente_non_impostato = DB_BOOL_FALSE;

if (!empty($filters['filter_partecipazioni_sociali'])) {
    $filtri = $filters['filter_partecipazioni_sociali'];
    $part_soc_dipendente_id = $this->datab->get_field_by_name('partecipazioni_sociali_dipendente')['fields_id'];
    $part_soc_data_field_id = $this->datab->get_field_by_name('partecipazioni_sociali_data')['fields_id'];

    if (!empty($filtri[$part_soc_dipendente_id]['value']) && $filtri[$part_soc_dipendente_id]['value'] !== '-1') {
        $filtro_dipendente_id = $filtri[$part_soc_dipendente_id]['value'];

        $dipendente = $this->apilib->view('dipendenti', $filtri[$part_soc_dipendente_id]['value']);

        $this->db->where('partecipazioni_sociali_dipendente', $filtri[$part_soc_dipendente_id]['value']);
        
        $filtro_testo .= "Dipendente: <b>{$dipendente['dipendenti_nome']} {$dipendente['dipendenti_cognome']}</b>, ";
    } else {
        $dipendente_non_impostato = DB_BOOL_TRUE;
    }
    
    if (!empty($filtri[$part_soc_data_field_id]['value'])) {
        $data_explode = explode(" - ", $filtri[$part_soc_data_field_id]['value']);

        $dataInizio = (DateTime::createFromFormat('d/m/Y', $data_explode[0]))->format('Y-m-d');
        $dataFine = (DateTime::createFromFormat('d/m/Y', $data_explode[1]))->format('Y-m-d');
        
        $presenze_data_ita = (DateTime::createFromFormat('d/m/Y', $data_explode[0]))->format('m/Y');

        $this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
        $this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);

        $filtro_testo .= "Periodo: <b>{$filtri[$part_soc_data_field_id]['value']}</b>";
    }
    else {
        //Filtro data non impostato, prendo mese corrente
        $dataInizio = date('Y-m-01');
        $dataFine = date('Y-m-t');        
        $this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
        $this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);
        
        $filtro_testo .= "Periodo: <b>{$filtri[$part_soc_data_field_id]['value']}</b>";
    }

} else {
    //Filtri non impostati, prendo mese corrente
    $dataInizio = date('Y-m-01');
    $dataFine = date('Y-m-t');
    $this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
    $this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);

    //$filtro_testo .= "Periodo: <b>{$filtri[$part_soc_data_field_id]['value']}</b>";
}

//Sottoscrizioni
$sottoscrizione = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "1")
->get("partecipazioni_sociali")->row();
$sottoscrizione_valore = $sottoscrizione->partecipazioni_sociali_valore_nominale;
$this->db->select_sum('partecipazioni_sociali_azioni');
$this->db->select_sum('partecipazioni_sociali_valore_nominale');
$this->db->where("partecipazioni_sociali_tipo_operazione", "1");
if($dipendente_non_impostato == DB_BOOL_FALSE) {
    $this->db->where('partecipazioni_sociali_dipendente', $filtri[$part_soc_dipendente_id]['value']);
}
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);
$sottoscrizione = $this->db->get("partecipazioni_sociali")->row();
$sottoscrizione_valore = $sottoscrizione->partecipazioni_sociali_valore_nominale;


//Versamento
$versamento = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "2")
->get("partecipazioni_sociali")->row();
$versamento_valore = $versamento->partecipazioni_sociali_valore_nominale; 
$this->db->select_sum('partecipazioni_sociali_azioni');
$this->db->select_sum('partecipazioni_sociali_valore_nominale');
$this->db->where("partecipazioni_sociali_tipo_operazione", "2");
if($dipendente_non_impostato == DB_BOOL_FALSE) {
    $this->db->where('partecipazioni_sociali_dipendente', $filtri[$part_soc_dipendente_id]['value']);
}
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);
$versamento = $this->db->get("partecipazioni_sociali")->row();
$versamento_valore = $versamento->partecipazioni_sociali_valore_nominale;


//Aumento storno utile
$aumento_storno = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "3")
->get("partecipazioni_sociali")->row();
$aumento_storno_utile_valore = $aumento_storno->partecipazioni_sociali_valore_nominale; 
$this->db->select_sum('partecipazioni_sociali_azioni');
$this->db->select_sum('partecipazioni_sociali_valore_nominale');
$this->db->where("partecipazioni_sociali_tipo_operazione", "3");
if($dipendente_non_impostato == DB_BOOL_FALSE) {
    $this->db->where('partecipazioni_sociali_dipendente', $filtri[$part_soc_dipendente_id]['value']);
}
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);
$aumento_storno = $this->db->get("partecipazioni_sociali")->row();
$aumento_storno_utile_valore = $aumento_storno->partecipazioni_sociali_valore_nominale;

//Recesso
$recesso = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "4")
->get("partecipazioni_sociali")->row();
$recesso_valore = $recesso->partecipazioni_sociali_valore_nominale; 
$this->db->select_sum('partecipazioni_sociali_azioni');
$this->db->select_sum('partecipazioni_sociali_valore_nominale');
$this->db->where("partecipazioni_sociali_tipo_operazione", "4");
if($dipendente_non_impostato == DB_BOOL_FALSE) {
    $this->db->where('partecipazioni_sociali_dipendente', $filtri[$part_soc_dipendente_id]['value']);
}
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);
$recesso = $this->db->get("partecipazioni_sociali")->row();
$recesso_valore = $recesso->partecipazioni_sociali_valore_nominale;


//Recesso storno utile
$recesso_storno = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "5")
->get("partecipazioni_sociali")->row();
$recesso_storno_utile_valore = $recesso_storno->partecipazioni_sociali_valore_nominale; 
$this->db->select_sum('partecipazioni_sociali_azioni');
$this->db->select_sum('partecipazioni_sociali_valore_nominale');
$this->db->where("partecipazioni_sociali_tipo_operazione", "5");
if($dipendente_non_impostato == DB_BOOL_FALSE) {
    $this->db->where('partecipazioni_sociali_dipendente', $filtri[$part_soc_dipendente_id]['value']);
}
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);
$recesso_storno = $this->db->get("partecipazioni_sociali")->row();
$recesso_storno_utile_valore = $recesso_storno->partecipazioni_sociali_valore_nominale;


//Rimborso
$rimborso = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "6")
->get("partecipazioni_sociali")->row();
$rimborso_valore = $rimborso->partecipazioni_sociali_valore_nominale; 
$this->db->select_sum('partecipazioni_sociali_azioni');
$this->db->select_sum('partecipazioni_sociali_valore_nominale');
$this->db->where("partecipazioni_sociali_tipo_operazione", "6");
if($dipendente_non_impostato == DB_BOOL_FALSE) {
    $this->db->where('partecipazioni_sociali_dipendente', $filtri[$part_soc_dipendente_id]['value']);
}
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);
$rimborso = $this->db->get("partecipazioni_sociali")->row();
$rimborso_valore = $rimborso->partecipazioni_sociali_valore_nominale;


//Rimborso storno
$rimborso_storno = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "7")
->get("partecipazioni_sociali")->row();
$rimborso_storno_valore = $rimborso_storno->partecipazioni_sociali_valore_nominale; 
$this->db->select_sum('partecipazioni_sociali_azioni');
$this->db->select_sum('partecipazioni_sociali_valore_nominale');
$this->db->where("partecipazioni_sociali_tipo_operazione", "7");
if($dipendente_non_impostato == DB_BOOL_FALSE) {
    $this->db->where('partecipazioni_sociali_dipendente', $filtri[$part_soc_dipendente_id]['value']);
}
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);
$rimborso_storno = $this->db->get("partecipazioni_sociali")->row();
$rimborso_storno_valore = $rimborso_storno->partecipazioni_sociali_valore_nominale;


// Rivalutazione quote sociali
$rivalutazione_sociale = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "8")
->get("partecipazioni_sociali")->row();
$rivalutazione_sociale_valore = $rivalutazione_sociale->partecipazioni_sociali_valore_nominale; 
$this->db->select_sum('partecipazioni_sociali_azioni');
$this->db->select_sum('partecipazioni_sociali_valore_nominale');
$this->db->where("partecipazioni_sociali_tipo_operazione", "8");
if($dipendente_non_impostato == DB_BOOL_FALSE) {
    $this->db->where('partecipazioni_sociali_dipendente', $filtri[$part_soc_dipendente_id]['value']);
}
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);
$rivalutazione_sociale = $this->db->get("partecipazioni_sociali")->row();
$rivalutazione_sociale_valore = $rivalutazione_sociale->partecipazioni_sociali_valore_nominale;

// TK #11000 - rivalutazione quote sociali
//Capitale sottoscritto
//$capitale_sottoscritto = $sottoscrizione_valore - $recesso_valore + $aumento_storno_utile_valore - $recesso_storno_utile_valore;
$capitale_sottoscritto = $sottoscrizione_valore + $rivalutazione_sociale_valore + $aumento_storno_utile_valore;
//Capitale versato
//$capitale_versato = $versamento_valore - $rimborso_valore + $aumento_storno_utile_valore - $recesso_storno_utile_valore;
$capitale_versato = $versamento_valore  + $rivalutazione_sociale_valore + $aumento_storno_utile_valore;

//Partecipazioni
if($dipendente_non_impostato == DB_BOOL_FALSE) {
    $this->db->where('partecipazioni_sociali_dipendente', $filtri[$part_soc_dipendente_id]['value']);
}
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') >= '{$dataInizio}'", null, false);
$this->db->where("DATE_FORMAT(partecipazioni_sociali_data, '%Y-%m-%d') <= '{$dataFine}'", null, false);
$this->db->join('partecipazioni_sociali_tipo_operazione', 'partecipazioni_sociali_tipo_operazione_id = partecipazioni_sociali_tipo_operazione', 'LEFT');
$this->db->join('dipendenti', 'dipendenti_id = partecipazioni_sociali_dipendente', 'LEFT');
$this->db->order_by('partecipazioni_sociali_data', 'DESC');
$partecipazioni = $this->db->get("partecipazioni_sociali")->result_array();
?>

<div>
    <div class="row">
        <div class="col-sm-12 text-center">
            <?php echo $filtro_testo; ?>
        </div>
    </div>
</div>

<div>
    <div class="row">
        <div class="col-sm-12">
            <h4 class="text-uppercase text-center">Riepilogo operazioni azienda</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <table class="table table-striped table-condensed">
                <thead>
                    <tr>
                        <th>Tipo operazione</th>
                        <th>Quantità azioni</th>
                        <th>Valore nominale €</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>SOTTOSCRIZIONE</td>
                        <td><?php echo $sottoscrizione->partecipazioni_sociali_azioni; ?></td>
                        <td><?php echo number_format($sottoscrizione_valore, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>VERSAMENTO</td>
                        <td><?php echo $versamento->partecipazioni_sociali_azioni; ?></td>
                        <td><?php echo number_format($versamento_valore, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>RIMBORSO</td>
                        <td><?php echo $rimborso->partecipazioni_sociali_azioni; ?></td>
                        <td><?php echo number_format($rimborso_valore, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>RECESSO</td>
                        <td><?php echo $recesso->partecipazioni_sociali_azioni; ?></td>
                        <td> - <?php echo number_format($recesso_valore, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>DELIBERA</td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td>RIVALUTAZIONE QUOTE SOCIALI</td>
                        <td><?php echo $rivalutazione_sociale->partecipazioni_sociali_azioni; ?></td>
                        <td><?php echo number_format($rivalutazione_sociale_valore, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                    <tr>
                        <td>AUMENTO STORNO UTILE</td>
                        <td><?php echo $aumento_storno->partecipazioni_sociali_azioni; ?></td>
                        <td><?php echo number_format($aumento_storno_utile_valore, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>RECESSO STORNO UTILE</td>
                        <td><?php echo $recesso_storno->partecipazioni_sociali_azioni; ?></td>
                        <td> - <?php echo number_format($recesso_storno_utile_valore, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>RIMBORSO STORNO</td>
                        <td><?php echo $rimborso_storno->partecipazioni_sociali_azioni; ?></td>
                        <td><?php echo number_format($rimborso_storno_valore, 2, ',', '.'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6">
            <div>
                <span>CAPITALE SOTTOSCRITTO</span>
            </div>
            <div>
                <span>CAPITALE VERSATO</span>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="text-right">
                <span><?php echo number_format($capitale_sottoscritto, 2, ',', '.'); ?></span>
            </div>
            <div class="text-right">
                <span><?php echo number_format($capitale_versato, 2, ',', '.'); ?></span>
            </div>
        </div>
    </div>
</div>

<hr />

<div>
    <div class="row">
        <div class="col-sm-12">
            <table class="table table-striped table-condensed">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Dipendente</th>
                        <th>Data</th>
                        <th>Tipo operazione</th>
                        <th>Qtà azioni</th>
                        <th>Valore azione €</th>
                        <th>Valore nominale €</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if (!empty($partecipazioni)) : 
                    foreach($partecipazioni as $key => $partecipazione) :
                    ?>
                    <tr>
                        <td><?php echo $key+1; ?></td>
                        <td>
                            <?php
                            if($dipendente_non_impostato == DB_BOOL_TRUE) {
                                echo "{$partecipazione['dipendenti_nome']} {$partecipazione['dipendenti_cognome']}";
                            } else {
                                echo "{$dipendente['dipendenti_nome']} {$dipendente['dipendenti_cognome']}";
                            }
                            ?>
                        </td>
                        <td><?php echo dateFormat($partecipazione['partecipazioni_sociali_data'], 'd/m/Y'); ?></td>
                        <td><?php echo $partecipazione['partecipazioni_sociali_tipo_operazione_value']; ?></td>
                        <td><?php echo $partecipazione['partecipazioni_sociali_azioni']; ?></td>
                        <td><?php echo number_format($partecipazione['partecipazioni_sociali_valore_azione'], 2, ',', '.'); ?></td>
                        <td><?php echo number_format($partecipazione['partecipazioni_sociali_azioni'] * $partecipazione['partecipazioni_sociali_valore_azione'], 2, ',', '.'); ?></td>
                        <td><?php echo $partecipazione['partecipazioni_sociali_note']; ?></td>
                    </tr>
                    <?php
                        endforeach;
                        else :
                    ?>
                    <tr>
                        <td colspan="8" class="text-center">Nessuna operazione registrata</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>