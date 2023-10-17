<?php
return;
    $ordinamento_documenti_tipo = array_key_map($this->apilib->search('ordinamento_documenti_tipo', [], null, 0, 'ordinamento_documenti_tipo_order'), 'documenti_contabilita_tipo_value');
    $remap_label = [
        'Fatture' => 'Fattura',
        'Preventivi' => 'Preventivo',
        'Pro forma' => 'Pro forma',
        'Note di credito' => 'Nota di credito',
        'Ordini cliente' => 'Ordine cliente',
        'Ordini fornitore' => 'Ordine fornitore',
        'Elenco DDT' => 'DDT',
        'Ordini interni' => 'Ordine interno',

    ];
?>

<script>
var ordinamento_documenti_tipo = <?php echo json_encode($ordinamento_documenti_tipo); ?>;
var remap_label = <?php echo json_encode($remap_label); ?>;
//console.log(remap_label);

setTimeout(() => {
    $(() => {
        $('.nav-tabs li a[data-toggle="tab"]').each(function() {
            var label = $(this).html();
            var li_to_move = $(this).parent();
        });

        var listItems = $('ul.nav-tabs li').detach().sort(function(a, b) {

            var label_a = remap_label[$('a', a).text()];
            var label_b = remap_label[$('a', b).text()];
            // console.log(label_a);
            // console.log(label_b);
            return ordinamento_documenti_tipo.indexOf(label_a) - ordinamento_documenti_tipo.indexOf(label_b);
        });

        // Aggiungi i tag <li> ordinati nuovamente all'HTML in base all'ordine corretto
        $('ul.nav-tabs').append(listItems);
    });
}, 1500);
</script>