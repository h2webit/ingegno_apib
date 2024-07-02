<?php
$dipendenti_presenti = $this->db->query("SELECT *
FROM dipendenti
JOIN presenze ON dipendenti_id = presenze_dipendente
WHERE DATE(presenze_data_inizio) = CURDATE();
")->result_array(); 
//dump($dipendenti_presenti);

$dipendenti_richieste = $this->db->query("SELECT *
FROM dipendenti
JOIN richieste ON dipendenti_id = richieste_user_id
JOIN richieste_tipologia on richieste.richieste_tipologia = richieste_tipologia.richieste_tipologia_id
JOIN richieste_sottotipologia on richieste.richieste_sottotipologia = richieste_sottotipologia.richieste_sottotipologia_id
WHERE DATE(richieste_dal) >= CURDATE() AND (DATE(richieste_al) >= CURDATE() OR richieste_al IS NULL OR richieste_al = '') AND richieste_tipologia IN (1, 2) AND richieste_stato = 2;
")->result_array(); 
//dump($dipendenti_richieste);

$dip_assenti = $this->db->query("SELECT *
FROM appuntamenti
WHERE appuntamenti_id NOT IN (SELECT rapportini_appuntamento_id FROM rapportini WHERE rapportini_appuntamento_id IS NOT NULL AND rapportini_appuntamento_id <> '') AND DATE(appuntamenti_giorno) = CURDATE()
")->result_array();
//dump($dip_assenti);
$actual_time = date('H:i');

$dipendenti_assenti = $this->apilib->search("appuntamenti", [
	"appuntamenti_id NOT IN (SELECT rapportini_appuntamento_id FROM rapportini WHERE rapportini_appuntamento_id IS NOT NULL AND rapportini_appuntamento_id <> '') AND DATE(appuntamenti_giorno) = CURDATE() AND (appuntamenti_ora_fine IS NOT NULL AND appuntamenti_ora_fine <> '' AND appuntamenti_ora_fine <= '{$actual_time}')"
]);
?>

<style>
.single_record {
    margin-bottom: 8px;
    /* border: 1px solid #282828;
    border-radius: 8px;
    padding: 4px 8px; */
    display: flex;
    justify-content: flex-start;
    flex-direction: column;
    gap: 2px;
    text-decoration: none;
    color: unset;
}

.single_record p {
    font-weight: bold;
    margin: 0;
}
</style>


<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12 col-md-4">

            <!-- Dipendenti che hanno timbrato presenza oggi -->
            <h4>Dipendenti presenti</h4>
            <?php
				if(!empty($dipendenti_presenti)) :
					foreach($dipendenti_presenti as $presenza) :
					$orari = $presenza['presenze_ora_inizio'];
					$orari .= !empty($presenza['presenze_ora_fine']) ? ' - '.$presenza['presenze_ora_fine'] : '';
			?>
            <a href="<?php echo base_url("get_ajax/layout_modal/dettaglio-presenza/{$presenza['presenze_id']}");?>" class="js_open_modal single_record">
                <p><?php echo "{$presenza['dipendenti_nome']} {$presenza['dipendenti_cognome']}, {$orari}";?></p>
            </a>
            <?php
				endforeach;
				else :
			?>
            <h5 class="text-red">Non ci sono dipendenti che hanno timbrato l'entrata.</h5>
            <?php endif; ?>
        </div>

        <!-- Dipendenti con appuntamneti per oggi passato ma senza rapportino compilato -->
        <div class="col-sm-12 col-md-4">
            <h4>Dipendenti assenti</h4>
            <?php
				if(!empty($dipendenti_assenti)) :
					foreach($dipendenti_assenti as $assenti) :
					$commessa = $assenti['appuntamenti_impianto'] ?  $assenti['projects_name'] : '';
			?>
            <div class="single_record">
                <!-- <img src="<?php //echo $assenti['dipendenti_foto']; ?>" alt="<?php //echo $assenti['dipendenti_foto']; ?>" /> -->
                <p>
                    <?php
						if(!empty($assenti['appuntamenti_persone'])) {
							foreach($assenti['appuntamenti_persone'] as $key => $value) {
								echo $value;
							}
						}
					?>
                </p>
                <small><a href='<?php echo base_url("main/layout/dettaglio_commessa/{$assenti['appuntamenti_impianto']}"); ?>' target="_blank"><?php echo "{$commessa}";?></a></small>
            </div>
            <?php
				endforeach;
				else :
			?>
            <h5 class="text-red">Nessun appuntamento per oggi senza rapportino da visualizzare</h5>
            <?php endif; ?>
        </div>

        <!-- Dipendenti con permesso o ferie comprendente oggi approvato -->
        <div class="col-sm-12 col-md-4">
            <h4>Dipendenti con richieste</h4>
            <?php
				if(!empty($dipendenti_richieste)) :
					foreach($dipendenti_richieste as $richiesta) :
					$info = $richiesta['richieste_tipologia_value'];
					$info .= !empty($richiesta['richieste_sottotipologia']) ? ' ('.$richiesta['richieste_sottotipologia_value'].')' : '';
			?>
            <a href="<?php echo base_url("get_ajax/layout_modal/dettaglio-richiesta/{$richiesta['richieste_id']}");?>" class="js_open_modal single_record">
                <p><?php echo "{$richiesta['dipendenti_nome']} {$richiesta['dipendenti_cognome']}"; ?></p>
                <small><?php echo "{$info}";?></small>
            </a>
            <?php
				endforeach;
				else :
			?>
            <h5 class="text-red">Nessuna richiesta approvata da visualizzare.</h5>
            <?php endif; ?>
        </div>
    </div>
</div>