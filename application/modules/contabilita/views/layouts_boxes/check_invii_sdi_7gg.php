<?php

/*
documenti_contabilita_stato_invio_sdi
1	Non inviato X
2	Invio in corso... X
3	Elaborazione in corso... X
4	Errore nel processo di invio X
5	In attesa risposta dal SDI X
6	Scartata dal sdi X
7	Accettata dal sdi X
8	Inviata al server centralizzato X
9	In attesa di consegna X
10	Mancata consegna
11	Consegnata
12	Accettata dalla PA
13	Rifiutata dalla PA
14	Attestazione avvenuta trasmissione
15	Decorrenza termini
*/

//debug($this->apilib->view('documenti_contabilita', 16122),true);
// Where
$where_base = "(documenti_contabilita_formato_elettronico = 1 AND (documenti_contabilita_importata_da_xml <> 1 OR documenti_contabilita_importata_da_xml IS NULL)) AND documenti_contabilita_tipo IN (1,4,11,12)";
$filtro_data_3g = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE DATEDIFF(NOW(), documenti_contabilita_cambi_stato_creation_date) < 3)";
$filtro_data_4g = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE DATEDIFF(NOW(), documenti_contabilita_cambi_stato_creation_date) < 4)";
$filtro_data_1g = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE DATEDIFF(NOW(), documenti_contabilita_cambi_stato_creation_date) < 1)";
$filtro_data_2g = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE DATEDIFF(NOW(), documenti_contabilita_cambi_stato_creation_date) < 2)";
$filtro_data_3h = "documenti_contabilita_id NOT IN (SELECT documenti_contabilita_cambi_stato_documento_id FROM documenti_contabilita_cambi_stato WHERE HOUR(TIMEDIFF(NOW(), documenti_contabilita_cambi_stato_creation_date)) < 1)";

// SE Invio SDI Attivo
// Documenti Scartati
$scarti = $this->db->query("SELECT COUNT(*) AS c FROM documenti_contabilita WHERE $where_base AND documenti_contabilita_stato_invio_sdi IN (6,13)")->row()->c;

// Documenti elettronici non inviati > 5 gg
$non_inviati = $this->db->query("SELECT COUNT(*) AS c FROM documenti_contabilita WHERE $where_base AND (documenti_contabilita_stato_invio_sdi IN (1) OR documenti_contabilita_stato_invio_sdi IS NULL)")->row()->c;
//debug($this->db->query("SELECT * FROM documenti_contabilita WHERE $where_base AND (documenti_contabilita_stato_invio_sdi IN (1) OR documenti_contabilita_stato_invio_sdi IS NULL)")->result_array(), true);
// > 1 gg in  Invio in corso o Elaborazione in corso 
$anomali = $this->db->query("SELECT COUNT(*) AS c FROM documenti_contabilita WHERE $where_base AND documenti_contabilita_stato_invio_sdi IN (2,3,8) AND $filtro_data_1g")->row()->c;

// > 3 giorni in In attesa da SDI, Accettata da sdi, In attesa di consegna
$attesa = $this->db->query("SELECT COUNT(*) AS c FROM documenti_contabilita WHERE $where_base AND documenti_contabilita_stato_invio_sdi IN (5,7,9) AND $filtro_data_3g")->row()->c;

// Elaborazione con errore
$errore = $this->db->query("SELECT COUNT(*) AS c FROM documenti_contabilita WHERE $where_base AND documenti_contabilita_stato_invio_sdi IN (4)")->row()->c;

$positive_ultimi1gg = $this->db->query("SELECT COUNT(*) AS c FROM documenti_contabilita WHERE NOT ($filtro_data_2g) AND documenti_contabilita_stato_invio_sdi IN (10,11,12,14)")->row()->c;

// Imposto il filtro "Stato invio SDI" in sessione per filtrare le tabelle native,
//verificando che il parametro get filtro_sdi non sia vuoto e che sia un array (perchè il filtro è una multiselect)
if (!empty($this->input->get('filtro_sdi')) && is_array($this->input->get('filtro_sdi'))) {
    $filtro_sdi = $this->input->get('filtro_sdi');
    $filtro_sdi = array_values($filtro_sdi); // per sicurezza, mi prendo i valori dell'array, così da rimuovere eventuali buchi nelle chiavi e potenziali hackings

    $filtro_fatture = $_SESSION[SESS_WHERE_DATA]['filtro_elenchi_documenti_contabilita'];

    // ottengo il field id del campo sul filtro
    $filtro_sdi_field_id = $this->db->query("SELECT * FROM fields WHERE fields_name = 'documenti_contabilita_stato_invio_sdi'")->row()->fields_id;


    // sostituisco il valore
    $filtro_fatture[$filtro_sdi_field_id] = [
        'field_id' => $filtro_sdi_field_id,
        'operator' => 'in',
        'value' => $filtro_sdi
    ];

    // risetto l'intero array di sessione del filtro (così facendo tengo comunque gli altri filtri identici a prima)
    $_SESSION[SESS_WHERE_DATA]['filtro_elenchi_documenti_contabilita'] = $filtro_fatture;

    // refresho la pagina togliendo i parametri in get
    redirect(base_url('main/layout/elenco_documenti'), 'refresh');
    exit;
}

?>
<style>
.bignum {
    display: block;
    width: 100%;
    font-size: 55px;
    margin: 0px;
    margin: 0 auto;
    text-align: center;
    font-weight: bold;
}

.bigtext {
    display: block;
    width: 100%;
    font-size: 25px;
    margin: 0px;
    margin-bottom: 5px;
    text-align: center;
}
</style>

<div class="row">
    <!-- Positive -->
    <?php /*if ($positive_ultimi1gg > 0): ?>
    <div class="col-md-2 callout callout-success" style="padding:2px">
        <span class="bignum">
            <?php echo $positive_ultimi1gg; ?>
        </span>
        <span class="bigtext">Consegnate<br /></span>
        <small style="width: 100%; display: block; text-align: center;">Ultime 24h</small>

    </div>
    <?php endif; */?>

    <!-- Non inviati -->
    <?php if ($non_inviati > 0): ?>
    <div class="col-md-2 callout callout-warning" style="padding:2px">
        <span class="bignum">
            <?php echo $non_inviati; ?>
        </span>
        <span class="bigtext">Non inviati</span>

        <small style="width: 100%; display: block; text-align: center;">
            <a href="<?php echo base_url('main/layout/elenco_documenti?filtro_sdi[]=1'); ?>">Filtra</a>
            |
            <a class=" js_open_modal" href="<?php echo base_url(); ?>get_ajax/layout_modal/legenda-stati-sdi?_size=large">Legenda</a>
        </small>
    </div>
    <?php endif; ?>


    <!-- Scarti -->
    <?php if ($scarti > 0): ?>
    <div class="col-md-2 callout callout-danger" style="padding:2px">
        <span class="bignum">
            <?php echo $scarti; ?>
        </span>
        <span class="bigtext">Scarti</span>

        <small style="width: 100%;
                                                                                                                                                    display: block;
                                                                                                                                                    text-align: center;">
            <a href="<?php echo base_url('main/layout/elenco_documenti?filtro_sdi[]=6&filtro_sdi[]=13'); ?>">Filtra</a>
            |
            <a class=" js_open_modal" href="<?php echo base_url(); ?>get_ajax/layout_modal/legenda-stati-sdi?_size=large">Legenda</a>
        </small>
    </div>
    <?php endif; ?>

    <!-- Anomali -->
    <?php if ($anomali > 0): ?>
    <div class="col-md-2 callout callout-danger" style="padding:2px">
        <span class="bignum">
            <?php echo $anomali; ?>
        </span>
        <span class="bigtext">Anomali</span>

        <small style="width: 100%;
                                                                                                                                                    display: block;
                                                                                                                                                    text-align: center;">
            <a href="<?php echo base_url('main/layout/elenco_documenti?filtro_sdi[]=2&filtro_sdi[]=3&filtro_sdi[]=8'); ?>">Filtra</a>
            |
            <a class=" js_open_modal" href="<?php echo base_url(); ?>get_ajax/layout_modal/legenda-stati-sdi?_size=large">Legenda</a>
        </small>
    </div>
    <?php endif; ?>

    <!-- Attesa -->
    <?php if ($attesa > 0): ?>
    <div class="col-md-2 callout callout-danger " style="padding:2px">
        <span class="bignum">
            <?php echo $attesa; ?>
        </span>
        <span class="bigtext">Attesa</span>

        <small style="width: 100%;
                                                                                                                                                    display: block;
                                                                                                                                                    text-align: center;">
            <a href="<?php echo base_url('main/layout/elenco_documenti?filtro_sdi[]=5&filtro_sdi[]=7&filtro_sdi[]=9'); ?>">Filtra</a>
            |
            <a class=" js_open_modal" href="<?php echo base_url(); ?>get_ajax/layout_modal/legenda-stati-sdi?_size=large">Legenda</a>
        </small>
    </div>
    <?php endif; ?>

    <!-- Errore -->
    <?php if ($errore > 0): ?>
    <div class="col-md-2 callout callout-danger" style="padding:2px">
        <span class="bignum">
            <?php echo $errore; ?>
        </span>
        <span class="bigtext">Errore</span>

        <small style="width: 100%;
                                                                                                                                                    display: block;
                                                                                                                                                    text-align: center;">
            <a href="<?php echo base_url('main/layout/elenco_documenti?filtro_sdi[]=4'); ?>">Filtra</a>
            |
            <a class=" js_open_modal" href="<?php echo base_url(); ?>get_ajax/layout_modal/legenda-stati-sdi?_size=large">Legenda</a>
        </small>
    </div>
    <?php endif; ?>
</div>