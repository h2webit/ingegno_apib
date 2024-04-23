<?php
    $filtri_attivi = '';
    
    $filters = $this->session->userdata(SESS_WHERE_DATA);

    if (!empty($filters) && array_key_exists('filter-presenze', $filters)) {
        $filtro_presenze = $filters['filter-presenze'];

        $presenze_data_inizio_field = $this->db->query("SELECT * FROM fields WHERE fields_name = 'presenze_data_inizio'")->row()->fields_id;
        $presenze_data_fine_field = $this->db->query("SELECT * FROM fields WHERE fields_name = 'presenze_data_fine'")->row()->fields_id;
        $presenze_dipendente_field = $this->db->query("SELECT * FROM fields WHERE fields_name = 'presenze_dipendente'")->row()->fields_id;

        if (!empty($filtro_presenze[$presenze_dipendente_field]['value']) && $filtro_presenze[$presenze_dipendente_field]['value'] !== '-1') {
            $dipendente = $this->db->where('dipendenti_id', $filtro_presenze[$presenze_dipendente_field]['value'])->get('dipendenti')->row_array();
            
            $this->db->where('presenze_dipendente', $filtro_presenze[$presenze_dipendente_field]['value']);


            $filtri_attivi .= "<b>Dipendente:</b> {$dipendente['dipendenti_nome']} {$dipendente['dipendenti_cognome']}<br/>";
        }

        if (!empty($filtro_presenze[$presenze_data_inizio_field]['value'])) {
            $presenze_data_inizio_exp = explode(' - ', $filtro_presenze[$presenze_data_inizio_field]['value']);

            $presenze_where_data_inizio_year = (DateTime::createFromFormat('d/m/Y', $presenze_data_inizio_exp[0]))->format('Y');
            $presenze_where_data_inizio_month = (DateTime::createFromFormat('d/m/Y', $presenze_data_inizio_exp[0]))->format('m');
            
            $this->db->where("(YEAR(presenze_data_inizio) = '{$presenze_where_data_inizio_year}' AND MONTH(presenze_data_inizio = '{$presenze_where_data_inizio_month}'))");

            $filtri_attivi .= "<b>Data inizio:</b> {$presenze_where_data_inizio_month}/{$presenze_where_data_inizio_year}<br/>";
        }

        // if (!empty($filtro_presenze[$presenze_data_fine_field]['value'])) {
        //     $presenze_data_fine_exp = explode(' - ', $filtro_presenze[$presenze_data_fine_field]['value']);
            
        //     $presenze_where_data_fine_from = (DateTime::createFromFormat('d/m/Y', $presenze_data_fine_exp[0]))->format('Y-m');
        //     // $presenze_where_data_fine_to = (DateTime::createFromFormat('d/m/Y',$presenze_data_fine_exp[1]))->format('Y-m');
            
        //     // $this->db->where("(DATE_FORMAT(presenze_data_fine, '%Y-%m-%d') BETWEEN '{$presenze_where_data_fine_from}' AND '{$presenze_where_data_fine_to}')");
        //     $this->db->where("(DATE_FORMAT(presenze_data_fine, '%Y-%m') = '{$presenze_where_data_fine_from}')");

        //     $filtri_attivi .= "<b>Data fine:</b> {$filtro_presenze[$presenze_data_fine_field]['value']}<br/>";
        // }
    }

    $presenze = $this->db
    ->join('dipendenti', 'dipendenti_id = presenze_dipendente', 'left')
    ->where("(presenze_data_fine IS NOT NULL AND presenze_data_fine <> '')")
    ->order_by('presenze_data_inizio,presenze_ora_inizio', 'asc')
    ->get('presenze')->result_array();
    // ->get_compiled_select('presenze');

    // debug($presenze, true);

    $dati = [];
    foreach ($presenze as $presenza) {
        $dati["{$presenza['dipendenti_cognome']} {$presenza['dipendenti_nome']}"][] = $presenza;
    }
?>

<?php if(!empty($filtri_attivi)): ?>

<div class="col-sm-12">
    <div class="well well-sm">
        <h3>Filtri Attivi:</h3>
        <h4><?php echo $filtri_attivi ?></h4>
    </div>
</div>

<?php endif; ?>

<?php if(empty($dati)): ?>
<div class="col-sm-6 col-sm-offset-3">
    <div class="alert alert-info text-center"><b>Nessun dato rilevato.</b><br />Controlla che i filtri impostati siano corretti</div>
</div>
<?php endif; ?>

<?php foreach($dati as $dipendente => $presenze): ?>
<div class="col-sm-12">
    <div style="page-break-after: always !important;"></div>
    <div class="panel panel-default">
        <div class="panel-heading">
            <h3 class="panel-title text-uppercase" style="font-weight: bold;"><?php echo $dipendente ?></h3>
        </div>

        <table class="table table-condensed table-striped table-bordered">
            <thead>
                <tr>
                    <th class="text-center">Entrata</th>
                    <th class="text-center">Uscita</th>
                    <th class="text-right">Ore Ordinarie</th>
                    <th class="text-right">Ore Straordinarie</th>
                    <th class="text-right">Ore Totali</th>
                </tr>
            </thead>

            <tbody>
                <?php
                $tot_ore_ordinarie = 0;
                $tot_ore_straordinarie = 0;
                $tot_ore = 0;
                foreach($presenze as $presenza):
            
                $entrata = $presenza['presenze_data_inizio_calendar'];
                $uscita = $presenza['presenze_data_fine_calendar'];

                $presenza_ore_ord = (!empty($presenza['presenze_ore_totali']) && $presenza['presenze_ore_totali'] > 0) ? number_format($presenza['presenze_ore_totali'], 2, '.', '') : 0;
                $presenza_ore_straord = (!empty($presenza['presenze_straordinario']) && $presenza['presenze_straordinario'] > 0) ? number_format($presenza['presenze_straordinario'], 2, '.', '') : 0;
                $presenza_ore_tot = number_format($presenza_ore_ord + $presenza_ore_straord, 2, '.', '');

                $tot_ore_ordinarie += $presenza_ore_ord;
                $tot_ore_straordinarie += $presenza_ore_straord;
                $tot_ore += $presenza_ore_tot
            ?>
                <tr>
                    <td class="text-center"><?php echo dateFormat($presenza['presenze_data_inizio'], 'd/m/Y'); ?> alle <b><?php echo $presenza['presenze_ora_inizio']; ?></b></td>
                    <td class="text-center"><?php echo dateFormat($presenza['presenze_data_fine'], 'd/m/Y'); ?> alle <b><?php echo $presenza['presenze_ora_fine']; ?></b></td>
                    <td class="text-right"><?php echo $presenza_ore_ord; ?></td>
                    <td class="text-right"><?php echo $presenza_ore_straord; ?></td>
                    <td class="text-right"><?php echo $presenza_ore_tot; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-right" style="font-weight: bold;">Totali ore:</td>
                    <td class="text-right">Ord. <span style="font-weight: bold;"><?php echo $tot_ore_ordinarie ?></span></td>
                    <td class="text-right">Straord. <span style="font-weight: bold;"><?php echo $tot_ore_straordinarie ?></span></td>
                    <td class="text-right">Tot. <span style="font-weight: bold;"><?php echo $tot_ore ?></span></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php endforeach; ?>