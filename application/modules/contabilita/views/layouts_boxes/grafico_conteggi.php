<?php
$where_fatture = ["documenti_contabilita_tipo IN (1,4,11,12)"]; //Prendo solo fatture e note di credito
$where_spese = ["1=1"]; //Prendo solo fatture e note di credito

//Verifico eventuali filtri impostati nel modulo contabilità:

$filtro_fatture = (array) @$this->session->userdata(SESS_WHERE_DATA)['filtro_elenchi_documenti_contabilita'];
$data_da = date('Y-01-01');
$data_a = date('Y-12-31');
foreach ($filtro_fatture as $field_id => $filtro) {
    $field_id = $filtro['field_id'];
    $value = $filtro['value'];

    if ($value !== '' && $value != -1) {
        $field_data = $this->db->query("SELECT * FROM fields WHERE fields_id = '$field_id'")->row_array();
        $field_name = $field_data['fields_name'];
        switch ($field_name) {
            case 'documenti_contabilita_data_emissione': //Filtro data

                $value_expl = explode(' - ', $value);
                if (DateTime::createFromFormat('d/m/Y', $value_expl[0]) && DateTime::createFromFormat('d/m/Y', $value_expl[1])) {
                    $data_da = DateTime::createFromFormat('d/m/Y', $value_expl[0])->format('Y-m-d');
                    $data_a = DateTime::createFromFormat('d/m/Y', $value_expl[1])->format('Y-m-d');

                    $where_fatture[] = "(DATE(documenti_contabilita_data_emissione) >= '$data_da' AND DATE(documenti_contabilita_data_emissione) <= '$data_a')";
                    $where_spese[] = "(DATE(spese_data_emissione) >= '$data_da' AND DATE(spese_data_emissione) <= '$data_a')";
                }


                break;
            case 'documenti_contabilita_serie':
                $where_fatture[] = "documenti_contabilita_serie IN ('" . implode("','", $value) . "')";
                $where_spese[] = "(spese_data_emissione >= '$data_da' AND spese_data_emissione <= '$data_a')";
                break;
            case 'documenti_contabilita_centro_di_ricavo':
                $where_fatture[] = "documenti_contabilita_centro_di_ricavo = '$value'";
                $where_spese[] = "spese_centro_di_costo = '$value'";
                break;
            case 'documenti_contabilita_stato_pagamenti':
                $where_fatture[] = "documenti_contabilita_stato_pagamenti = '$value'";
                //$where_spese[] = "(spese_data_emissione >= '$data_da' AND spese_data_emissione <= '$data_a')";
                break;
            case 'documenti_contabilita_customer_id':
                $where_fatture[] = "documenti_contabilita_customer_id = '$value'";
                $where_spese[] = "spese_customer_id = '$value'";
                break;
            case 'documenti_contabilita_azienda':
                $where_fatture[] = "documenti_contabilita_azienda = '$value'";
                $where_spese[] = "spese_azienda = '$value'";
                break;
            default:

                debug("Campo filtro non gestito {$field_name}(custom view iva).");
                debug($filtro);
                break;
        }
    }
}



//Mi preocostruisco la tabella fake di supporto per avere tutte le date
$query_all_days = "with recursive all_dates(dt) as (\r\n\r\n
    select '$data_da' dt\r\n
        union all \r\n
    select dt + interval 1 day from all_dates where dt + interval 1 day <= '$data_a'\r\n
) \r\n

";
//$this->db->query($query_all_days)->result_array();

$where_fatture_str = implode(' AND ', $where_fatture);
$where_spese_str = implode(' AND ', $where_spese);
$query_fatturato = $query_all_days . " SELECT
    coalesce(SUM(CASE WHEN documenti_contabilita_tipo IN (1,11,12) THEN documenti_contabilita_totale-documenti_contabilita_iva ELSE -(documenti_contabilita_totale-documenti_contabilita_iva) END),0) as x,
    
    extract(month FROM dt) as y,
    extract(year FROM dt) as anno
    FROM
        all_dates d
        left join documenti_contabilita t on CAST(t.documenti_contabilita_data_emissione as DATE) = d.dt
    WHERE $where_fatture_str
    GROUP BY extract(month FROM dt),extract(year FROM dt)
    ORDER BY extract(year FROM dt), extract(month from dt)";
$fatturato_mensile = $this->db->query($query_fatturato)->result_array();

$query_spese = $query_all_days . " SELECT
    coalesce(SUM(spese_totale-spese_deduc_iva),0) as x,
    extract(month FROM dt) as y,
    extract(year FROM dt) as anno
    FROM
        all_dates d
        left join spese t on CAST(t.spese_data_emissione AS DATE) = d.dt


    WHERE $where_spese_str
    GROUP BY extract(month FROM dt),extract(year FROM dt)
    ORDER BY extract(year FROM dt), extract(month from dt)";
$spese_mensile = $this->db->query($query_spese)->result_array();

$values_fatturato = $values_spese = $categories = [];
foreach ($fatturato_mensile as $data) {
    $values_fatturato[] = number_format($data['x'], 2, '.', '');
    $meset = mese_testuale($data['y']);
    $categories[] = "{$meset} {$data['anno']}";
}
foreach ($spese_mensile as $data) {
    $values_spese[] = number_format($data['x'], 2, '.', '');
}

$series = [];
$series[] = [
    'name' => 'Fatturato',
    'data' => $values_fatturato,
];
$series[] = [
    'name' => 'Spese',
    'data' => $values_spese,
];

//debug($fatturato_mensile);
//
?>



<div id="container_chartjs_14"></div>
<script>
    var seriescontainer_chartjs_14 = JSON.parse('<?php echo json_encode($series); ?>');
    console.log(seriescontainer_chartjs_14);
    var optionscontainer_chartjs_14 = {
        chart: {
            type: 'bar',
            zoom: {
                type: 'x',
                enabled: true,
                autoScaleYaxis: true
            },
        },

        dataLabels: {
            enabled: false,
            enabledOnSeries: true,
            position: 'center',
            maxItems: 100,
            hideOverflowingLabels: true,
            orientation: 'vertical'
        },

        legend: {
            show: true
        },
        series: seriescontainer_chartjs_14,
        xaxis: {
            categories: JSON.parse('<?php echo json_encode($categories); ?>')

        },

        tooltip: {
            shared: true,
            intersect: false,
            y: {
                formatter: function (y) {

                    return y;

                }
            }
        }



    }

    var chartcontainer_chartjs_14 = new ApexCharts(document.querySelector("#container_chartjs_14"), optionscontainer_chartjs_14);

    chartcontainer_chartjs_14.render();
</script>