<?php
if(!empty($value_id)) {
    $dipendente = $this->apilib->view('dipendenti', $value_id);
}

//Sottoscrizioni
$sottoscrizione = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "1")
->where("partecipazioni_sociali_dipendente", "{$value_id}")
->get("partecipazioni_sociali")->row();
$sottoscrizione_valore = $sottoscrizione->partecipazioni_sociali_valore_nominale;


//Versamento
$versamento = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "2")
->where("partecipazioni_sociali_dipendente", "{$value_id}")
->get("partecipazioni_sociali")->row();
$versamento_valore = $versamento->partecipazioni_sociali_valore_nominale;


//Aumento storno utile
$aumento_storno = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "3")
->where("partecipazioni_sociali_dipendente", "{$value_id}")
->get("partecipazioni_sociali")->row();
$aumento_storno_utile_valore = $aumento_storno->partecipazioni_sociali_valore_nominale;


//Recesso
$recesso = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "4")
->where("partecipazioni_sociali_dipendente", "{$value_id}")
->get("partecipazioni_sociali")->row();
$recesso_valore = $recesso->partecipazioni_sociali_valore_nominale;


//Recesso storno utile
$recesso_storno = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "5")
->where("partecipazioni_sociali_dipendente", "{$value_id}")
->get("partecipazioni_sociali")->row();
$recesso_storno_utile_valore = $recesso_storno->partecipazioni_sociali_valore_nominale;


//Rimborso
$rimborso = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "6")
->where("partecipazioni_sociali_dipendente", "{$value_id}")
->get("partecipazioni_sociali")->row();
$rimborso_valore = $rimborso->partecipazioni_sociali_valore_nominale;


//Rimborso storno
$rimborso_storno = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "7")
->where("partecipazioni_sociali_dipendente", "{$value_id}")
->get("partecipazioni_sociali")->row();
$rimborso_storno_valore = $rimborso_storno->partecipazioni_sociali_valore_nominale;

//Rivalutazione quote sociali
$rivalutazione_sociale = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "8")
->where("partecipazioni_sociali_dipendente", "{$value_id}")
->get("partecipazioni_sociali")->row();
$rivalutazione_sociale_valore = $rivalutazione_sociale->partecipazioni_sociali_valore_nominale;


//Capitale sottoscritto
$capitale_sottoscritto = $sottoscrizione_valore - $recesso_valore + $aumento_storno_utile_valore - $recesso_storno_utile_valore;
//Capitale versato
$capitale_versato = $versamento_valore - $rimborso_valore + $aumento_storno_utile_valore - $recesso_storno_utile_valore;


//Partecipazioni
$partecipazioni = $this->db
->where("partecipazioni_sociali_dipendente", "{$value_id}")
->join('partecipazioni_sociali_tipo_operazione', 'partecipazioni_sociali_tipo_operazione_id = partecipazioni_sociali_tipo_operazione', 'LEFT')
->get("partecipazioni_sociali")->result_array();
?>

<div>
    <?php if(!empty($value_id)) : ?>
    <div class="row">
        <div class="col-sm-12">
            <h5 class="text-center"><span class="text-capitalize"><?php echo $dipendente['dipendenti_nome'].' '.$dipendente['dipendenti_cognome'].' - '.$dipendente['dipendenti_indirizzo'].', '.$dipendente['dipendenti_citta'];?></span></h5>
            <h5 class="text-center"><span class="text-capitalize"><?php echo $dipendente['dipendenti_codice_fiscale']; ?></span> - <?php echo $dipendente['dipendenti_email'];?></h5>
            <h5 class="text-center">Aggiornato al <?php echo date('d/m/Y H:m');?></h5>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-sm-12">
            <table class="table table-striped table-condensed">
                <thead>
                    <tr>
                        <th>#</th>
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
                        <td colspan="7" class="text-center">Nessuna operazione registrata</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <h4 class="text-uppercase text-center">Riepilogo operazioni socio</h4>
        </div>
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
                        <td><?php echo number_format($sottoscrizione->partecipazioni_sociali_valore_nominale, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>VERSAMENTO</td>
                        <td><?php echo $versamento->partecipazioni_sociali_azioni; ?></td>
                        <td><?php echo number_format($versamento->partecipazioni_sociali_valore_nominale, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>RIMBORSO</td>
                        <td><?php echo $rimborso->partecipazioni_sociali_azioni; ?></td>
                        <td><?php echo number_format($rimborso->partecipazioni_sociali_valore_nominale, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>RECESSO</td>
                        <td><?php echo $recesso->partecipazioni_sociali_azioni; ?></td>
                        <td> - <?php echo number_format($recesso->partecipazioni_sociali_valore_nominale, 2, ',', '.'); ?></td>
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
                        <td>AUMENTO STORNO UTILE</td>
                        <td><?php echo $aumento_storno->partecipazioni_sociali_azioni; ?></td>
                        <td><?php echo number_format($aumento_storno->partecipazioni_sociali_valore_nominale, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>RECESSO STORNO UTILE</td>
                        <td><?php echo $recesso_storno->partecipazioni_sociali_azioni; ?></td>
                        <td> - <?php echo number_format($recesso_storno->partecipazioni_sociali_valore_nominale, 2, ',', '.'); ?></td>
                    </tr>
                    <tr>
                        <td>RIMBORSO STORNO</td>
                        <td><?php echo $rimborso_storno->partecipazioni_sociali_azioni; ?></td>
                        <td><?php echo number_format($rimborso_storno->partecipazioni_sociali_valore_nominale, 2, ',', '.'); ?></td>
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