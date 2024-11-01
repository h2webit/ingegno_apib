<?php
// Dati filtri impostati
$filters = $this->session->userdata(SESS_WHERE_DATA);

$filtro_testo = '';
$filtro_dipendente_id = null;
$filtro_azienda_id = null;
$filtro_agenzia_somministrazione_id = null;

$mesi = [
    'Gennaio',
    'Febbraio',
    'Marzo',
    'Aprile',
    'Maggio',
    'Giugno',
    'Luglio',
    'Agosto',
    'Settembre',
    'Ottobre',
    'Novembre',
    'Dicembre'
];

//dump($filters);
$projects_installed = $this->datab->module_installed('projects');

if (!empty($filters['filter_nota_spese_dipendenti'])) {
    $filtri = $filters['filter_nota_spese_dipendenti'];
    $nota_spese_dipendente_field_id = $this->datab->get_field_by_name('nota_spese_dipendente')['fields_id'];
    $nota_spese_data_field_id = $this->datab->get_field_by_name('nota_spese_data')['fields_id'];
    $nota_spese_stato_field_id = $this->datab->get_field_by_name('nota_spese_stato')['fields_id'];


    // FILTRO DIPENDENTE
    if (!empty($filtri[$nota_spese_dipendente_field_id]['value']) && $filtri[$nota_spese_dipendente_field_id]['value'] !== '-1') {
        $filtro_azienda_id = $filtri[$nota_spese_dipendente_field_id]['value'];
        $dipendente = $this->db->where('dipendenti_id', $filtri[$nota_spese_dipendente_field_id]['value'])->get('dipendenti')->row_array();
        //$this->db->join('dipendenti', 'nota_spese_dipendente = dipendenti_id', "left");
        $this->db->where('nota_spese_dipendente', $filtri[$nota_spese_dipendente_field_id]['value']);

        $filtro_testo .= "Dipendente: <b>{$dipendente['dipendenti_nome']} {$dipendente['dipendenti_cognome']}</b>, ";
    }

    // FILTRO DATA
    if (!empty($filtri[$nota_spese_data_field_id]['value']) && $filtri[$nota_spese_data_field_id]['value'] !== '-1') {
        $nota_spese_data_ex = explode(' - ', $filtri[$nota_spese_data_field_id]['value']);
        $nota_spese_data = (DateTime::createFromFormat('d/m/Y', $nota_spese_data_ex[0]))->format('Y-m');
        $nota_spese_data_ita = (DateTime::createFromFormat('d/m/Y', $nota_spese_data_ex[0]))->format('m/Y');

        // Applica il filtro con il range di date
        $nota_spese_data_start = (DateTime::createFromFormat('d/m/Y', $nota_spese_data_ex[0]))->format('Y-m-d');
        $nota_spese_data_end = (DateTime::createFromFormat('d/m/Y', $nota_spese_data_ex[1]))->format('Y-m-d');
        $this->db->where("nota_spese_data >=", $nota_spese_data_start, null, false);
        $this->db->where("nota_spese_data <=", $nota_spese_data_end, null, false);

        $anno = (DateTime::createFromFormat('d/m/Y', $nota_spese_data_ex[0]))->format('Y');
        $mese = (DateTime::createFromFormat('d/m/Y', $nota_spese_data_ex[0]))->format('m');
        $data_tmp = DateTime::createFromFormat('!m', $mese)->format('Y-m-d');
        //$nome_mese = strftime('%B', strtotime($data_tmp));
        $nome_mese = $mesi[$mese - 1];

        $filtro_testo .= "<b>{$nome_mese} {$anno}</b>";
    }

    // FILTRO STATO
    if (!empty($filtri[$nota_spese_stato_field_id]['value']) && $filtri[$nota_spese_stato_field_id]['value'] !== '-1') {
        $this->db->where('nota_spese_stato', $filtri[$nota_spese_stato_field_id]['value']);
    }
} else {
    // FILTRO DATA NON IMPOSTATO
    /* $this->db->where("DATE_FORMAT(nota_spese_data, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')", null, false);
    $anno = date('Y');
    $mese = date('m');

    $data_tmp = DateTime::createFromFormat('!m', $mese)->format('Y-m-d');
    //$nome_mese = strftime('%B', strtotime($data_tmp));
    $nome_mese = $mesi[$mese - 1];

    $filtro_testo .= "<b>{$nome_mese} {$anno}</b>"; */
}

$this->db->join('dipendenti', 'nota_spese_dipendente = dipendenti_id', "left");
if ($projects_installed === DB_BOOL_TRUE) {
    $this->db->join('projects', 'nota_spese_commessa = projects_id', "left");
}
$this->db->order_by('nota_spese_data', 'ASC');
$this->db->join('nota_spese_stato', 'nota_spese.nota_spese_stato = nota_spese_stato.nota_spese_stato_id', "left");
$nota_spese = $this->db->get('nota_spese')->result_array();

/*dump($this->db->last_query() );
dump($nota_spese);*/
?>

<style>
    .td-middle {
        vertical-align: middle !important;
    }

    th,
    td {
        white-space: nowrap;
        padding: 8px;
    }

    table {
        font-size: 10px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <h3><?php echo $filtro_testo; ?></h3>
            <table class="table table-bordered table-striped table-condensed">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Dipendente</th>
                        <th>Importo</th>
                        <th>Commessa</th>
                        <th>Stato</th>
                        <th>Descrizione</th>
                        <th>Ricevuta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($nota_spese)):
                        $totale_spese = 0;

                        foreach ($nota_spese as $spesa):
                            $totale_spese += $spesa['nota_spese_importo'] ?? 0; // Somma l'importo al totale
                            ?>
                            <tr>
                                <td><?php echo dateFormat($spesa['nota_spese_data']); ?></td>
                                <td><?php echo "{$spesa['dipendenti_cognome']} {$spesa['dipendenti_nome']}"; ?></td>
                                <td><?php e_money($spesa['nota_spese_importo'], '€ {number}'); ?></td>
                                <td><?php echo $spesa['nota_spese_commessa'] ?? '-'; ?></td>
                                <td><?php echo $spesa['nota_spese_stato_value']; ?></td>
                                <td><?php echo $spesa['nota_spese_descrizione'] ?? '-'; ?></td>
                                <td class="text-align: center;">
                                    <?php
                                    if (!empty($spesa['nota_spese_foto_ricevuta'])):
                                        $link = base_url("thumb/80/80/1/uploads/{$spesa['nota_spese_foto_ricevuta']}");
                                        ?>
                                        <img src="<?php echo $link; ?>" alt="">
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        endforeach;
                    endif; ?>
                    <?php if (empty($nota_spese)): ?>
                        <tr>
                            <td colspan="7" class="text-center">Nessuna nota spese presente</td>
                        </tr>
                    <?php else: ?>
                        <tr>
                            <td colspan="2"><strong>Totale</b></td>
                            <td><strong><?php e_money($totale_spese, '€ {number}'); ?></b></td>
                            <td colspan="4"></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>