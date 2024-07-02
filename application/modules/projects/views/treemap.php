<?php
$where = "projects_status = 2";
$projects = $this->apilib->search('projects', $where, 200);


foreach ($projects as $project) {
    $_json['x'] = $project['projects_name'];

    switch ($project['projects_priority']):
        case '1': // Low
            $_json['y'] = 6;
            break;
        case '2':
            $_json['y'] = 12;
            break;
        case '3':
            $_json['y'] = 20;
            break;
    endswitch;
    $_json['color'] = "#ff0000";
    $json[] = $_json;
}

?>


<div class="col-md-12">
    <div id="js_treemap_projects_chart"></div>
</div>


<script>
    var options = {
        series: [{
            data: <?php echo json_encode($json); ?>
        }],
        legend: {
            show: false
        },
        chart: {
            height: 950,
            type: 'treemap',
            events: {
                dataPointSelection: function(event, chartContext, config) {
                    alert('click');
                    console.log(event);
                    console.log(chartContext);
                    console.log(config);

                }
            },
        },
        title: {
            text: 'Treemap with Color scale'
        },
        dataLabels: {
            enabled: true,
            style: {
                fontSize: '12px',
            },
            formatter: function(text, op) {
                return [text, op.value]
            },
            offsetY: -4
        },

        plotOptions: {
            treemap: {
                enableShades: true,
                shadeIntensity: 0.5,
                reverseNegativeShade: true,
                colorScale: {
                    ranges: [{
                            from: -6,
                            to: 0,
                            color: '#CD363A'
                        },
                        {
                            from: 0.001,
                            to: 6,
                            color: '#52B12C'
                        }
                    ]
                }
            }
        }
    };

    var chart = new ApexCharts(document.querySelector("#js_treemap_projects_chart"), options);
    chart.render();
</script>