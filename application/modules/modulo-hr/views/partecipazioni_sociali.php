<?php
//Sottoscrizioni
$sottoscrizione = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "1")
->get("partecipazioni_sociali")->row();
$sottoscrizione_valore = $sottoscrizione->partecipazioni_sociali_valore_nominale;


//Versamento
$versamento = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "2")
->get("partecipazioni_sociali")->row();
$versamento_valore = $versamento->partecipazioni_sociali_valore_nominale;


//Aumento storno utile
$aumento_storno = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "3")
->get("partecipazioni_sociali")->row();
$aumento_storno_utile_valore = $aumento_storno->partecipazioni_sociali_valore_nominale;

//Recesso
$recesso = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "4")
->get("partecipazioni_sociali")->row();
$recesso_valore = $recesso->partecipazioni_sociali_valore_nominale;


//Recesso storno utile
$recesso_storno = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "5")
->get("partecipazioni_sociali")->row();
$recesso_storno_utile_valore = $recesso_storno->partecipazioni_sociali_valore_nominale;


//Rimborso
$rimborso = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "6")
->get("partecipazioni_sociali")->row();
$rimborso_valore = $rimborso->partecipazioni_sociali_valore_nominale;

//Rimborso storno
$rimborso_storno = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "7")
->get("partecipazioni_sociali")->row();
$rimborso_storno_valore = $rimborso_storno->partecipazioni_sociali_valore_nominale;

//Rivalutazione quote sociali
$rivalutazione_sociale = $this->db
->select_sum('partecipazioni_sociali_azioni')
->select_sum('partecipazioni_sociali_valore_nominale')
->where("partecipazioni_sociali_tipo_operazione", "8")
->get("partecipazioni_sociali")->row();
$rivalutazione_sociale_valore = $rivalutazione_sociale->partecipazioni_sociali_valore_nominale;


//Capitale sottoscritto
$capitale_sottoscritto = $sottoscrizione_valore - $recesso_valore + $aumento_storno_utile_valore - $recesso_storno_utile_valore;
//Capitale versato
$capitale_versato = $versamento_valore - $rimborso_valore + $aumento_storno_utile_valore - $recesso_storno_utile_valore;
?>

<div>
    <div class="row">
        <div class="col-sm-12">
            <h4 class="text-uppercase text-center">Riepilogo operazioni azienda</h4>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            <table class="table table-striped">
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