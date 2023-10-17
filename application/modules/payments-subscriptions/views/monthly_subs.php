<?php


// Monthly subs
$subs = $this->apilib->search('subscriptions', ['subscriptions_recurrence' => 5, '(subscriptions_end_date IS NULL OR subscriptions_end_date = "" OR subscriptions_end_date > NOW())'], null, 0, "subscriptions_price ASC");

$total_monthly = 0;
foreach ($subs as $sub):
    $total_monthly = $total_monthly + $sub['subscriptions_price'];
    $prices[] = number_format($sub['subscriptions_price'], 1, ".", "");
endforeach;

$data_prices = implode(",", $prices);

// debug($subs);
$total_target = $total_monthly * 1.3;

?>

<div id="chart"></div>

<script>
    var options = {
        series: [
            {
                name: "Total (€)",
                data: [<?php echo $data_prices; ?>],
            },
        ],
        chart: {
            type: 'bar',
            height: 700,
        },
        plotOptions: {
            bar: {
                borderRadius: 0,
                horizontal: true,
                distributed: true,
                barHeight: '80%',
                isFunnel: true,
            },
        },
        colors: [
            '#F44F5E',
            '#E55A89',
            '#D863B1',
            '#CA6CD8',
            '#B57BED',
            '#8D95EB',
            '#62ACEA',
            '#4BC3E6',
        ],
        dataLabels: {
            enabled: false,
            formatter: function (val, opt) {
                return opt.w.globals.labels[opt.dataPointIndex]
            },
            dropShadow: {
                enabled: true,
            },
        },
        title: {
            text: 'Monthly subscriptions: € <?php e_money($total_monthly); ?>',
            align: 'middle',
        },
        xaxis: {
            categories: [<?php foreach ($subs as $sub): ?>'<?php echo addslashes($sub['customers_full_name']); ?>', <?php endforeach; ?>],
        },
        legend: {
            show: false,
        },
    };

    var chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();
</script>