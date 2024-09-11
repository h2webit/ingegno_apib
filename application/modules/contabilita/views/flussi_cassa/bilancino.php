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
$where = $where_not_saldo = ["(flussi_cassa_azienda = '$value_id')"];

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
                $data_da_mysql = implode('-', array_reverse(explode('/', $data_da)));
                //debug($data_a_mysql);
                $where[] = "date(flussi_cassa_data) <= '$data_a_mysql'";
                $where_not_saldo[] = "date(flussi_cassa_data) >= '$data_da_mysql'";

                break;
            case $flussi_cassa_risorsa: //Risorsa
                if ($value == -2) {
                    
                    $where[] = "(flussi_cassa_risorsa IS NULL OR flussi_cassa_risorsa = '')";
                } else {
                    $where[] = "flussi_cassa_risorsa = '$value'";
                }
                

                break;
            case $flussi_cassa_tipo: //Tipo
                if ($value == -2) {
                    $where[] = "(flussi_cassa_tipo IS NULL OR flussi_cassa_tipo = '')";
                } else {
                    $where[] = "flussi_cassa_tipo = '$value'";
                }
                

                break;
            case $flussi_cassa_metodo: //Metodo
                if ($value == -2) {
                    $where[] = "(flussi_cassa_metodo IS NULL OR flussi_cassa_metodo = '')";
                } else {
                    $where[] = "flussi_cassa_metodo = '$value'";
                }
                

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
//debug($where,true);
$where_str = implode(' AND ', array_merge($where, $where_not_saldo));
//debug($where_str);

$grid_id = $this->datab->get_grid_id_by_identifier('flussi_cassa_movimenti');
// $where = $this->datab->generate_where("grids", $grid_id, $value_id);

// debug($where);


$_entrate = $this->db->query("
    SELECT 
        SUM(CASE WHEN flussi_cassa_confermato = 1 THEN flussi_cassa_importo ELSE 0 END) as s_confermate, 
        SUM(CASE WHEN flussi_cassa_confermato = 0 THEN flussi_cassa_importo ELSE 0 END) as s_previste, 
        CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) as d 
    FROM flussi_cassa 
    WHERE flussi_cassa_tipo = 1 AND $where_str 
    GROUP BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) 
    ORDER BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0))
")->result_array();
$_uscite = $this->db->query("
    SELECT 
        SUM(CASE WHEN flussi_cassa_confermato = 1 THEN flussi_cassa_importo ELSE 0 END) as s_confermate, 
        SUM(CASE WHEN flussi_cassa_confermato = 0 THEN flussi_cassa_importo ELSE 0 END) as s_previste, 
        CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) as d 
    FROM flussi_cassa 
    WHERE flussi_cassa_tipo = 2 AND $where_str 
    GROUP BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) 
    ORDER BY CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0))
")->result_array();

//die($this->db->last_query());
$righe = $entrate_confermate = $entrate_previste = $uscite_confermate = $uscite_previste = [];
$somma_entrate_confermate = $somma_entrate_previste = $somma_uscite_confermate = $somma_uscite_previste = 0;



foreach ($_entrate as $entrata) {
    $mese = $entrata['d'];
    
    $righe[$entrata['d']] = $entrata['d'];

    // Popoliamo i nuovi array per entrate confermate e previste
    $entrate_confermate[$mese] = $entrata['s_confermate'];
    $entrate_previste[$mese] = $entrata['s_previste'];

    // Inizializziamo l'array $uscite per il mese se non esiste già
    if (!isset($uscite_confermate[$mese])) {
        $uscite_confermate[$mese] = 0;
    }
    if (!isset($uscite_previste[$mese])) {
        $uscite_confermate[$mese] = 0;
    }
    if (!isset($entrate_previste[$mese])) {
        $entrate_previste[$mese] = 0;
    }
    $somma_entrate_confermate += $entrata['s_confermate'];
    $somma_entrate_previste += $entrata['s_previste'];
}

foreach ($_uscite as $uscita) {
    $mese = $uscita['d'];
    $righe[$mese] = $mese;
    $uscite_confermate[$mese] = $uscita['s_confermate'];
    $uscite_previste[$mese] = $uscita['s_previste'];
    if (!array_key_exists($uscita['d'], $entrate_confermate)) {
        $entrate_confermate[$uscita['d']] = 0;
    }
    if (!array_key_exists($uscita['d'], $entrate_previste)) {
        $entrate_previste[$uscita['d']] = 0;
    }

    if (!array_key_exists($uscita['d'], $entrate_previste)) {
        $entrate_previste[$uscita['d']] = 0;
    }
    $somma_uscite_confermate += $uscita['s_confermate'];
    $somma_uscite_previste += $uscita['s_previste'];
}

sort($righe);

$somma_differenza = 0;



//Dati per grafico
$entrate_confermate_data = array_fill_keys(range(1, 12), 0);
$entrate_previste_data = array_fill_keys(range(1, 12), 0);
$uscite_confermate_data = array_fill_keys(range(1, 12), 0);
$uscite_previste_data = array_fill_keys(range(1, 12), 0);

foreach ($entrate_confermate as $mese => $somma) {
    // Estrai il numero del mese dalla chiave
    $numero_mese = (int)substr($mese, -2); // Prende gli ultimi 2 caratteri e li converte in intero
    $entrate_confermate_data[$numero_mese] = $somma;
}
foreach($entrate_previste as $mese => $somma) {
    $numero_mese = (int)substr($mese, -2);
    $entrate_previste_data[$numero_mese] = $somma;
}
foreach($uscite_confermate as $mese => $somma) {
    $numero_mese = (int)substr($mese, -2);
    $uscite_confermate_data[$numero_mese] = $somma;
}
foreach($uscite_previste as $mese => $somma) {
    $numero_mese = (int)substr($mese, -2);
    $uscite_previste_data[$numero_mese] = $somma;
}


$series = [
    [
        "name" => "Entrate saldate",
        "group" => "Entrate",
        
        "data" => array_values($entrate_confermate_data) // Questo dovrebbe essere un array dei valori
    ],
    
    
    [
        "name" => "Entrate non saldate",
        "group" => "Entrate",

        "data" => array_values($entrate_previste_data) // Questo dovrebbe essere un array dei valori
    ],
    [
        "name" => "Uscite saldate",
        "group" => "Uscite",

        "data" => array_values($uscite_confermate_data) // Questo dovrebbe essere un array dei valori
    ],
    [
        "name" => "Uscite non saldate",
        "group" => "Uscite",
        
        "data" => array_values($uscite_previste_data) // Questo dovrebbe essere un array dei valori
    ]
];

?>
<table class="table bilancino" data-totalable="1">
    <thead>
        <tr>
            <th>Mese</th>
            <th style="text-align: right;">Entrate saldate</th>
            <th style="text-align: right;">Entrate non saldate</th>
            <th style="text-align: right;">Uscite saldate</th>
            <th style="text-align: right;">Uscite non saldate</th>
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
                        echo "<span style='display:none' class='entrata'>" . number_format($entrate_confermate[$data], 2, ',', '.') . "</span>";
                        echo number_format($entrate_confermate[$data], 2, ',', '.');
                    ?>
                </td>
                <td style="text-align: right; color: grey;">&euro;
                    <?php
                        echo "<span style='display:none' class='entrata_prevista'>" . number_format($entrate_previste[$data], 2, ',', '.') . "</span>";
                        echo number_format($entrate_previste[$data], 2, ',', '.');
                    ?>
                </td>

                <td style="text-align: right; color: red;">&euro;
    <?php
                        echo "<span style='display:none' class='uscita_saldato'>" . number_format($uscite_confermate[$data], 2, ',', '.') . "</span>";
                        echo number_format($uscite_confermate[$data], 2, ',', '.');
                        ?>
                </td>
                <td style="text-align: right; color: grey;">&euro;
                    <?php
                    echo "<span style='display:none' class='uscita_prevista'>" . number_format($uscite_previste[$data], 2, ',', '.') . "</span>";
                    echo number_format($uscite_previste[$data], 2, ',', '.');
                    ?>
                </td>
                <td style="text-align: right;">&euro;
                    <?php
                    $differenza = number_format($entrate_confermate[$data]+$entrate_previste[$data] - ($uscite_confermate[$data]+$uscite_previste[$data]), 2, ',', '.');
                    
                    $somma_differenza += $entrate_confermate[$data]+$entrate_previste[$data] - ($uscite_confermate[$data] + $uscite_previste[$data]);
                    
                    echo "<span style='display:none' class='differenza'>{$differenza}</span>";
                    echo (substr($differenza, 0, 1) == '-') ? "<span style='color: red'>{$differenza}</span>" : "<span style='color: green;'>{$differenza}</span>";

                    ?>
                </td>
                <td style="text-align: right;">
                    <?php
                    $where_str_saldo = implode(' AND ', $where);
                    $somma = $this->db->query("
                        SELECT 
                            SUM(CASE flussi_cassa_tipo WHEN '1' THEN flussi_cassa_importo WHEN '2' THEN -1*flussi_cassa_importo ELSE 0 END) as s 
                        FROM flussi_cassa 
                        WHERE 
                            $where_str_saldo
                            AND 
                            CONCAT(YEAR(flussi_cassa_data), '-', LPAD(MONTH(flussi_cassa_data),2,0)) <= '$data'
                        ")->row()->s;

                    $saldo_iniziale = $this->db->query("SELECT SUM(COALESCE(conti_correnti_saldo_iniziale,0)) AS saldo_iniziale FROM conti_correnti WHERE conti_correnti_id IN (SELECT flussi_cassa_risorsa FROM flussi_cassa WHERE $where_str)")->row()->saldo_iniziale;
                    //$saldo_iniziale = 0;
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
            <td class="tot_entrate" style="font-weight: bold;color: green;text-align:right">&euro;
                <?php echo number_format($somma_entrate_confermate, 2, ',', '.'); ?>
            </td>
            <td class="tot_entrate" style="font-weight: bold;color: grey;text-align:right">&euro;
                <?php echo number_format($somma_entrate_previste, 2, ',', '.'); ?>
            </td>
            <td class="tot_uscite" style="font-weight: bold;color: red;text-align:right">&euro; 
                <?php echo number_format($somma_uscite_confermate, 2, ',', '.'); ?>
            </td>
            <td class="tot_uscite" style="font-weight: bold;color: grey;text-align:right">&euro; 
                <?php echo number_format($somma_uscite_previste, 2, ',', '.'); ?>
            </td>
            <td class="tot_differenza" style="font-weight: bold;text-align:right">
                &euro;
                <?php
                $tot_differenza = number_format($somma_differenza, 2, ',', '.');
                echo (substr($tot_differenza, 0, 1) == '-') ? "<span style='color: red'>{$tot_differenza}</span>" : "<span style='color: green;'>{$tot_differenza}</span>";
                ?>
            </td>
            <td class="tot_saldo_prog" style="text-align:right"></td>
        </tr>
    </tfoot>
</table>


<div class="js_grafico_bilancino"></div>


<script>
function adjustRectanglesHeight(minHeight) {
    document.querySelectorAll('.apexcharts-bar-area').forEach((bar) => {
        var dPath = bar.getAttribute('d');
        let segments = dPath.split('L');

        // Estrae le coordinate Y dal segmento superiore e inferiore
        let upperY = parseFloat(segments[1].split(' ')[1]);
        let lowerY = parseFloat(segments[3].split(' ')[1]);

        // Calcola l'attuale altezza
        let currentHeight = lowerY - upperY;

        if (currentHeight < minHeight) {
            console.log(dPath);
            let heightDiff = minHeight - currentHeight;
            upperY -= heightDiff; // Sposta il lato superiore verso l'alto

            // Aggiorna il path con le nuove coordinate Y
            segments[1] = segments[1].split(' ')[0] + ' ' + upperY.toString();
            segments[2] = segments[2].split(' ')[0] + ' ' + upperY.toString();
            var newDPath = segments.join('L');
            console.log(newDPath);

            bar.setAttribute('d', newDPath);

            // Riposiziona l'etichetta
            // Calcolare la media delle coordinate X per trovare l'etichetta corrispondente
            let midX = (parseFloat(segments[0].split('M')[1].split(' ')[0]) + parseFloat(segments[2].split(' ')[0])) / 2;

            document.querySelectorAll('.apexcharts-datalabel').forEach((label) => {
                let labelX = parseFloat(label.getAttribute('x'));
                // Considera un margine di errore nella posizione X per associare etichetta e barra
                if (Math.abs(labelX - midX) < 5) {
                    let labelY = parseFloat(label.getAttribute('y'));
                    label.setAttribute('y', labelY - heightDiff.toString());
                }
            });
        }
    });
}






    $(function () {
        $('.bilancino').dataTable({
            stateSave: true,
            "paging": false,
            "lengthChange": false,
            "length": 12,
            "searching": false,
            "info": false,
            
        });

        var options = {
          series: <?php e_json($series); ?>,
          chart: {
            type: 'bar',
            height: 500,
            stacked: true,
            events: {
                
                animationEnd: function(chartContext, options) {
                    
                    adjustRectanglesHeight(12);
                },
                
            }
        },
        stroke: {
          width: 1,
          colors: ['#fff']
        },
        dataLabels: {
          formatter: (val) => {
            
            // Per valori di 1000 o superiori, dividi per 1000 e aggiungi 'K' alla fine, arrotondato a due cifre decimali
            return (val / 1000).toFixed(0) + 'K';
            
          }
        },
        plotOptions: {
          bar: {
            horizontal: false
          }
        },
        xaxis: {
          categories: <?php e_json(array_values($righe)); ?>
        },
        fill: {
          opacity: 1
        },
        colors: ['#28a745', '#a9dfbf', '#dc3545', '#f5b7b1'],
        yaxis: {
          labels: {
            formatter: (val) => {
                return '€ ' + val.toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
          }
        },
        legend: {
          position: 'top',
          horizontalAlign: 'left'
        }
        
        };

        var chart = new ApexCharts(document.querySelector(".js_grafico_bilancino"), options);
        chart.render();
        




    });
</script>