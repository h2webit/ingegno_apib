<?php
//Prendo il filtro della grid movimenti

$campi_filtro = $this->db->where_in('fields_name', ['flussi_cassa_confermato', 'flussi_cassa_risorsa', 'flussi_cassa_data', 'flussi_cassa_metodo', 'flussi_cassa_tipo', 'flussi_cassa_saldato'])->get('fields')->result_array();

foreach ($campi_filtro as $campo) {
    //Trick (pay attention to the double $$ sign...)
    //debug($campo);
    $field_name = $campo['fields_name'];
    $$field_name = $campo['fields_id'];
}


$filtro_movimenti = @$this->session->userdata(SESS_WHERE_DATA)['filtro_flussi_movimenti'];
//debug($filtro_movimenti);
$where = ['1=1'];

if (!empty($filtro_movimenti)) {
    foreach ($filtro_movimenti as $field => $filtro) {
        $value = $filtro['value'];
        if ($value == -1) {
            continue;
        }
        switch ($field) {
            case $flussi_cassa_data: //Data
                $data_expl = explode(' - ', $value);
                $data_da = $data_expl[0];
                $data_a = @$data_expl[1];
                $data_a_mysql = implode('-', array_reverse(explode('/', $data_a)));
                //debug($data_a_mysql);
                $where[] = "date(flussi_cassa_data) <= '$data_a_mysql'";

                break;
            case $flussi_cassa_risorsa: //Risorsa

                $where[] = "flussi_cassa_risorsa = '$value'";

                break;
            case $flussi_cassa_tipo: //Tipo

                $where[] = "flussi_cassa_tipo = '$value'";

                break;
            case $flussi_cassa_metodo: //Metodo

                $where[] = "flussi_cassa_metodo = '$value'";

                break;

            case $flussi_cassa_confermato: //Contatto

                $where[] = "flussi_cassa_confermato = '$value'";

                break;
            default:
                debug("Campo filtro non gestito");
                debug($filtro);
                break;
        }
    }
}

$where_str = implode(' AND ', $where);
//debug($where_str);

$grid_id = $this->datab->get_grid_id_by_identifier('flussi_cassa_movimenti');
// $where = $this->datab->generate_where("grids", $grid_id, $value_id);

// debug($where);


$_entrate = $this->db->query("SELECT SUM(flussi_cassa_importo) as s, CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) as d FROM flussi_cassa WHERE flussi_cassa_tipo = 1 AND $where_str GROUP BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) ORDER BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0))")->result_array();
$_uscite = $this->db->query("SELECT SUM(flussi_cassa_importo) as s, CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) as d FROM flussi_cassa WHERE flussi_cassa_tipo = 2 AND $where_str GROUP BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) ORDER BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0))")->result_array();
//die($this->db->last_query());
$righe = $entrate = $uscite = [];
$somma_entrate = $somma_uscite = 0;
foreach ($_entrate as $entrata) {
    $righe[$entrata['d']] = $entrata['d'];
    $entrate[$entrata['d']] = $entrata['s'];
    $uscite[$entrata['d']] = 0;
    $somma_entrate += $entrata['s'];
}
foreach ($_uscite as $uscita) {
    $righe[$uscita['d']] = $uscita['d'];
    $uscite[$uscita['d']] = $uscita['s'];
    if (!array_key_exists($uscita['d'], $entrate)) {
        $entrate[$uscita['d']] = 0;
    }
    $somma_uscite += $uscita['s'];
}

sort($righe);
?>
<table class="table bilancino" data-totalable="1">
    <thead>
        <tr>
            <th>Mese</th>
            <th style="text-align: right;">Entrate</th>
            <th style="text-align: right;">Uscite</th>
            <th style="text-align: right;">Differenza</th>
            <th style="text-align: right;">Saldo</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($righe as $data): ?>
            <tr>
                <td>
                    <?php echo $data; ?>
                </td>
                <td style="text-align: right; color: green;">&euro;
                    <?php
                    echo "<span style='display:none' class='entrata'>" . number_format($entrate[$data], 2, ',', '.') . "</span>";
                    echo number_format($entrate[$data], 2, ',', '.');
                    ?>
                </td>

                <td style="text-align: right; color: red">&euro; -
                    <?php
                    echo "<span style='display:none' class='uscita'>" . number_format($uscite[$data], 2, ',', '.') . "</span>";
                    echo number_format($uscite[$data], 2, ',', '.');
                    ?>
                </td>
                <td style="text-align: right;">&euro;
                    <?php
                    $differenza = number_format($entrate[$data] - $uscite[$data], 2, ',', '.');
                    echo "<span style='display:none' class='differenza'>{$differenza}</span>";
                    echo (substr($differenza, 0, 1) == '-') ? "<span style='color: red'>{$differenza}</span>" : "<span style='color: green;'>{$differenza}</span>";
                    ?>
                </td>
                <td style="text-align: right;">
                    <?php
                    $somma = $this->db->query("SELECT SUM(CASE flussi_cassa_tipo WHEN '1' THEN flussi_cassa_importo WHEN '2' THEN -1*flussi_cassa_importo ELSE 0 END) as s FROM flussi_cassa WHERE $where_str")->row()->s;

                    $saldo_iniziale = $this->db->query("SELECT SUM(COALESCE(conti_correnti_saldo_iniziale,0)) AS saldo_iniziale FROM conti_correnti WHERE conti_correnti_id IN (SELECT flussi_cassa_risorsa FROM flussi_cassa WHERE $where_str)")->row()->saldo_iniziale;
                    //debug($saldo_iniziale);
                    ?>
                    &euro;
                    <?php
                    $saldo = number_format($somma + $saldo_iniziale, 2, ',', '.');
                    echo "<span style='display:none' class='saldo_progressivo'>{$saldo}</span>";
                    echo (substr($saldo, 0, 1) == '-') ? "<span style='color: red'>{$saldo}</span>" : "<span style='color: green;'>{$saldo}</span>";
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td style="font-weight: bold;">Totali:</td>
            <td class="tot_entrate" style="color: green;text-align:right">&euro;
                <?php echo number_format($somma_entrate, 2, ',', '.'); ?>
            </td>
            <td class="tot_uscite" style="color: red;text-align:right">&euro; -
                <?php echo number_format($somma_uscite, 2, ',', '.'); ?>
            </td>
            <td class="tot_differenza" style="text-align:right"></td>
            <td class="tot_saldo_prog" style="text-align:right"></td>
        </tr>
    </tfoot>
</table>

<script>
    $(function () {
        $('.bilancino').dataTable({
            stateSave: true
        });
    });
</script>