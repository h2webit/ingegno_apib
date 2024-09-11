<?php

$where_payments = [];

// Verifico eventuali filtri impostati nel modulo payments:
$filtro_fatture = (array) @$this->session->userdata(SESS_WHERE_DATA)['payments_filters'];
//debug($filtro_fatture,true);
$data_da = date('Y-01-01');
$data_a = date('Y-12-31');
$found_cancelled = false;
foreach ($filtro_fatture as $field_id => $filtro) {
    $field_id = $filtro['field_id'];
    $value = $filtro['value'];

    if ($value !== '' && $value != -1) {
        $field_data = $this->db->query("SELECT * FROM fields WHERE fields_id = '$field_id'")->row_array();
        $field_name = $field_data['fields_name'];
        switch ($field_name) {
            case 'payments_date': // Filtro data
                $value_expl = explode(' - ', $value);
                if (DateTime::createFromFormat('d/m/Y', $value_expl[0]) && DateTime::createFromFormat('d/m/Y', $value_expl[1])) {
                    $data_da = DateTime::createFromFormat('d/m/Y', $value_expl[0])->format('Y-m-d');
                    $data_a = DateTime::createFromFormat('d/m/Y', $value_expl[1])->format('Y-m-d');

                    $where_payments[] = "(DATE(payments_date) >= '$data_da' AND DATE(payments_date) <= '$data_a')";
                }
                break;
            case 'payments_canceled':
                $found_cancelled = true;
                if ($value == 1) {
                    $where_payments[] = "($field_name = 1)";
                } else {
                    $where_payments[] = "($field_name IS NULL OR $field_name <> 1)";
                }
                break;
            default:
                //if ($value) {
                $where_payments[] = "($field_name = '$value')";
                //}
                break;
        }
    }
}
if (!$found_cancelled) {//Escludo i cancelled anche se non è stato impostato il filtro
    $where_payments[] = "(payments_canceled IS NULL OR payments_canceled <> 1)";

}

$where_payments_str = implode(' AND ', $where_payments);

// Estrai gli anni dalle date di inizio e fine
$anno_inizio = (new DateTime($data_da))->format('Y');
$anno_fine = (new DateTime($data_a))->format('Y');
$intestazione_anni = $anno_inizio == $anno_fine ? $anno_inizio : "$anno_inizio-$anno_fine";

// Genera l'array $data con tutte le date dell'ultimo giorno di ogni mese nel periodo $data_da/$data_a
$data = [];
$current_date = new DateTime($data_da);
$end_date = new DateTime($data_a);

while ($current_date <= $end_date) {
    $last_day_of_month = $current_date->format('Y-m-t');
    $data[$last_day_of_month] = ['fatturato' => 0, 'da_fatturare' => 0, 'incassato' => 0, 'da_incassare' => 0];
    $current_date->modify('first day of next month');
}

$raggruppa = isset($_GET['raggruppa']) && $_GET['raggruppa'] == 1;

// Prendo solo i clienti che hanno pagamenti in base al filtro $where_payments
$clienti_query = "
    SELECT 
        c.customers_id, 
        c.customers_full_name, 
        c.customers_notes, 
        p.payments_centro_costo_ricavo,
        
        CASE 
            WHEN p.payments_centro_costo_ricavo = '' OR p.payments_centro_costo_ricavo IS NULL THEN 'non definito' 
            ELSE cc.centri_di_costo_ricavo_nome 
        END AS centri_di_costo_ricavo_nome
    FROM customers c
    JOIN payments p ON c.customers_id = p.payments_customer
    LEFT JOIN centri_di_costo_ricavo cc ON p.payments_centro_costo_ricavo = cc.centri_di_costo_ricavo_id
    WHERE p.payments_customer IS NOT NULL AND $where_payments_str
    GROUP BY " . ($raggruppa ? "p.payments_centro_costo_ricavo, " : "") . "c.customers_id
    ORDER BY " . ($raggruppa ? "p.payments_centro_costo_ricavo, " : "") . "c.customers_full_name
";

$clienti = $this->db->query($clienti_query)->result_array();
//debug($clienti,true);

// Estraggo tutti i pagamenti filtrati e aggrego i dati con una sola query
$payments = $this->db->query("
    SELECT
        p.payments_customer,
         p.payments_centro_costo_ricavo,
        
        CASE 
            WHEN p.payments_centro_costo_ricavo = '' OR p.payments_centro_costo_ricavo IS NULL THEN 'non definito' 
            ELSE cc.centri_di_costo_ricavo_nome 
        END AS centri_di_costo_ricavo_nome,
        DATE_FORMAT(p.payments_date, '%Y-%m') as payment_month,
        SUM(p.payments_amount) as total_amount,
        SUM(CASE WHEN p.payments_invoice_sent = 1 THEN p.payments_amount ELSE 0 END) as fatturato,
        SUM(CASE WHEN p.payments_invoice_sent <> 1 THEN p.payments_amount ELSE 0 END) as da_fatturare,
        SUM(CASE WHEN p.payments_paid = 1 THEN p.payments_amount ELSE 0 END) as incassato,
        SUM(CASE WHEN p.payments_paid <> 1 THEN p.payments_amount ELSE 0 END) as da_incassare,
        MAX(p.payments_date) as last_payment_date
    FROM payments p
    LEFT JOIN centri_di_costo_ricavo cc ON p.payments_centro_costo_ricavo = cc.centri_di_costo_ricavo_id
    WHERE $where_payments_str
    GROUP BY p.payments_customer, p.payments_centro_costo_ricavo, payment_month
")->result_array();
//debug($payments,true);

// Preparo i totali per ciascun cliente e mese
$totals = [];
foreach ($payments as $payment) {
    $customer_id = $payment['payments_customer'];
    $centro_costo = $raggruppa?$payment['payments_centro_costo_ricavo']:'non definito';
    
    $payment_month = $payment['payment_month'] . '-01'; // Rende la data al primo giorno del mese per uniformità
    $total_amount = $payment['total_amount'];
    $fatturato = $payment['fatturato'];
    $da_fatturare = $payment['da_fatturare'];
    $incassato = $payment['incassato'];
    $da_incassare = $payment['da_incassare'];
    $last_payment_date = new DateTime($payment['last_payment_date']);

    // Trova l'ultimo giorno del mese per la data di pagamento
    $last_day_of_month = (new DateTime($payment_month))->format('Y-m-t');

    if (!isset($totals[$centro_costo][$customer_id])) {
        $totals[$centro_costo][$customer_id] = ['importo' => 0, 'fatturato' => 0, 'da_fatturare' => 0, 'incassato' => 0, 'da_incassare' => 0, 'da_fatturare' => 0];
    }

    if (!isset($totals[$centro_costo][$customer_id][$last_day_of_month])) {
        $totals[$centro_costo][$customer_id][$last_day_of_month] = ['total_amount' => 0, 'incassato' => 0, 'da_incassare' => 0, 'last_payment_date' => $last_payment_date, 'da_fatturare' => 0, 'fatturato' => 0];
    }

    $totals[$centro_costo][$customer_id]['importo'] += $total_amount;
    $totals[$centro_costo][$customer_id]['fatturato'] += $fatturato;
    $totals[$centro_costo][$customer_id]['da_fatturare'] += $da_fatturare;
    $totals[$centro_costo][$customer_id]['incassato'] += $incassato;
    $totals[$centro_costo][$customer_id]['da_incassare'] += $da_incassare;

    $totals[$centro_costo][$customer_id][$last_day_of_month]['total_amount'] += $total_amount;
    $totals[$centro_costo][$customer_id][$last_day_of_month]['incassato'] += $incassato;
    $totals[$centro_costo][$customer_id][$last_day_of_month]['da_incassare'] += $da_incassare;
    $totals[$centro_costo][$customer_id][$last_day_of_month]['da_fatturare'] += $da_fatturare;
    $totals[$centro_costo][$customer_id][$last_day_of_month]['fatturato'] += $fatturato;
    $totals[$centro_costo][$customer_id][$last_day_of_month]['last_payment_date'] = $last_payment_date;
}

// Mi calcolo i vari totali e li metto nell'array $clienti, divisi per periodo di riferimento (mese)
foreach ($clienti as &$cliente_ref) {
    $cliente_id = $cliente_ref['customers_id'];
    $centro_costo = $raggruppa?$cliente_ref['payments_centro_costo_ricavo']:'non definito';
    $cliente_ref['importo'] = isset($totals[$centro_costo][$cliente_id]['importo']) ? $totals[$centro_costo][$cliente_id]['importo'] : 0;
    $cliente_ref['fatturato'] = isset($totals[$centro_costo][$cliente_id]['fatturato']) ? $totals[$centro_costo][$cliente_id]['fatturato'] : 0;
    $cliente_ref['da_fatturare'] = isset($totals[$centro_costo][$cliente_id]['da_fatturare']) ? $totals[$centro_costo][$cliente_id]['da_fatturare'] : 0;
    $cliente_ref['incassato'] = isset($totals[$centro_costo][$cliente_id]['incassato']) ? $totals[$centro_costo][$cliente_id]['incassato'] : 0;
    $cliente_ref['da_incassare'] = isset($totals[$centro_costo][$cliente_id]['da_incassare']) ? $totals[$centro_costo][$cliente_id]['da_incassare'] : 0;
}
//debug($clienti,true);
// Calcola i totali complessivi per tutte le colonne
$total_importo = 0;
$total_fatturato = 0;
$total_da_fatturare = 0;
$total_incassato = 0;
$total_da_incassare = 0;
$total_monthly = array_fill_keys(array_keys($data), 0);

foreach ($clienti as $cliente) {
    $centro_costo = $raggruppa ? $cliente['payments_centro_costo_ricavo'] : 'non definito';
    $total_importo += $cliente['importo'];
    $total_fatturato += $cliente['fatturato'];
    $total_da_fatturare += $cliente['da_fatturare'];
    $total_incassato += $cliente['incassato'];
    $total_da_incassare += $cliente['da_incassare'];

    foreach (array_keys($data) as $fine_mese) {
        if (isset($totals[$centro_costo][$cliente['customers_id']][$fine_mese]['total_amount'])) {
            $total_monthly[$fine_mese] += $totals[$centro_costo][$cliente['customers_id']][$fine_mese]['total_amount'];
        }
    }
}

?>

<style>
    .incassato {
        background-color: green;
    }

    .parziale {
        background-color: #eeee65;
        color: black;
    }

    .non-fatturato-incassato {
        background: repeating-linear-gradient(45deg,
                rgba(0, 128, 0, 0.5),
                /* Verde trasparente */
                rgba(0, 128, 0, 0.5) 10px,
                rgba(240, 240, 240, 0.5) 10px,
                rgba(240, 240, 240, 0.5) 12px);
    }

    .non-fatturato-non-incassato {
        background: repeating-linear-gradient(45deg,
                #b73333,
                /* Rosso trasparente */
                #b73333 10px,
                rgba(240, 240, 240, 0.5) 10px,
                rgba(240, 240, 240, 0.5) 12px);
        color: white;
    }

    .non-incassato {
        background-color: #b73333;
        color: white;
    }

    tbody td {
        text-align: right;
    }

    .td_left {
        text-align: left;
    }

    td {
        white-space: nowrap;
    }

    .js_open_modale_pagamenti,
    .js_open_modale_nuovo_pagamento,
    .js_open_modale_pagamenti_all {
        cursor: pointer;
    }

    .js_open_modale_nuovo_pagamento:hover {
        background-color: powderblue;
    }

    .js_open_modale_nuovo_pagamento:hover:after {
        content: 'Nuovo'
    }

    .progress-bar {
        position: relative;
        height: 100%;
        width: 100px;
        line-height: 56px;
        background-color: #b73333;
    }

    .progress-bar-inner {
        position: absolute;
        height: 100%;
    }

    .progress-bar-0 {
        background-color: #b73333;
    }

    .progress-bar-partial {
        background-color: #0c7518;
    }

    .progress-bar-full {
        background-color: green;
    }

    td.no-padding {
        padding: 0 !important;
    }

    .legend {
        display: flex;
        margin-bottom: 15px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        margin-right: 20px;
    }

    .legend-color {
        display: inline-block;
        width: 20px;
        height: 20px;
        margin-right: 5px;
    }

    .legend-color.incassato {
        background-color: green;
    }

    .legend-color.non-incassato {
        background-color: #b73333;
    }

    .legend-color.futuro {
        background-color: #f0f0f0;
        border: 1px solid black;
    }

    .legend-color.non-fatturato-incassato {
        background: repeating-linear-gradient(45deg,
                rgba(0, 128, 0, 0.5),
                rgba(0, 128, 0, 0.5) 10px,
                rgba(240, 240, 240, 0.5) 10px,
                rgba(240, 240, 240, 0.5) 12px);
        border: 1px solid black;
    }

    .legend-color.non-fatturato-non-incassato {
        background: repeating-linear-gradient(45deg,
                rgba(183, 51, 51, 0.5),
                rgba(183, 51, 51, 0.5) 10px,
                rgba(240, 240, 240, 0.5) 10px,
                rgba(240, 240, 240, 0.5) 12px);
        border: 1px solid black;
    }
</style>
<?php if ($raggruppa == 1): ?>
    <button class="btn btn-warning" id="toggle-raggruppa">Rimuovi raggruppamento</button>
<?php else: ?>
    <button class="btn btn-success" id="toggle-raggruppa">Raggruppa per centro di costo</button>
<?php endif; ?>

<script>
    document.getElementById('toggle-raggruppa').addEventListener('click', function () {
        const urlParams = new URLSearchParams(window.location.search);
        const raggruppa = urlParams.get('raggruppa');
        if (raggruppa === '1') {
            urlParams.set('raggruppa', '0');
        } else {
            urlParams.set('raggruppa', '1');
        }
        window.location.search = urlParams.toString();
    });
</script>



<div style="overflow-x: auto;">
    <table class="table table-striped table-bordered table-hover nowrap table-middle">
        <thead>
            <tr>
                <th>Pagamenti <?php echo $intestazione_anni; ?></th>
                <th>Importo</th>
                <?php foreach (array_keys($data) as $fine_mese): ?>
                    <?php
                    $date_obj = new DateTime($fine_mese);
                    $year = $date_obj->format('Y');
                    $month = $date_obj->format('M'); // Abbreviazione del mese
                    ?>
                    <th class="nowrap" style="text-align:center;"><?php echo $year; ?><br><?php echo $month; ?></th>
                <?php endforeach; ?>
                <th>Fatturato</th>
                <th>Da fatturare</th>
                <th>Incassato</th>
                <th>Da incassare</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($clienti)): ?>

                <?php
                $current_centro_costo = null;
                foreach ($clienti as $cliente):
                    if ($raggruppa && $cliente['centri_di_costo_ricavo_nome'] !== $current_centro_costo) {
                        $current_centro_costo = $cliente['centri_di_costo_ricavo_nome'];
                        echo '<tr ><td class="td_left" colspan="' . (6 + count($data)) . '">Centro di costo: <strong>' . strtoupper($current_centro_costo) . '</strong></td></tr>';
                    }
                    $importo = $cliente['importo'];
                    $incassato = $cliente['incassato'];
                    $percentuale_pagato = ($importo > 0) ? ($incassato / $importo) * 100 : 0;
                    $progress_bar_class = 'progress-bar-0';
                    if ($percentuale_pagato == 100) {
                        $progress_bar_class = 'progress-bar-full';
                    } elseif ($percentuale_pagato > 0) {
                        $progress_bar_class = 'progress-bar-partial';
                    }

                    // if ($percentuale_pagato == 0) {
                    //     $percentuale_pagato = 100;
                    // }
            
                    ?>
                    <tr>
                        <td class="td_left">
                            <a href="<?php echo base_url('main/layout/customer-detail/' . $cliente['customers_id']); ?>" class="riga_cliente"
                               data-cliente="<?php echo $cliente['customers_id']; ?>" data-centro_di_costo="<?php echo $cliente['payments_centro_costo_ricavo']; ?>"
                               target="_blank"><?php echo character_limiter($cliente['customers_full_name'], 15); ?></a>
                            <?php
                            //Se ci sono note sul cliente, mostro l'icona gialla, altrimenti normale
                            //debug($cliente,true);
                            $note = $cliente['customers_notes'];

                            ?>
                            <a class="js_open_modal"
                                href="<?php echo base_url('get_ajax/modal_form/customers_notes/' . $cliente['customers_id']); ?>">
                                <i class="far fa-sticky-note"
                                    style="margin-right:0px!important<?php if (strlen($note) > 10): ?>; color:red;<?php endif; ?>"></i>
                            </a>
                        </td>
                        <td class="no-padding">
                            <div class="progress-bar js_open_modale_pagamenti_all"
                                 data-centro_di_costo="<?php echo $cliente['payments_centro_costo_ricavo']; ?>"
                                data-cliente="<?php echo $cliente['customers_id']; ?>">
                                <div class="progress-bar-inner <?php echo $progress_bar_class; ?>"
                                    style="width: <?php echo $percentuale_pagato; ?>%;"></div>
                                <strong style="position: relative;">€ <?php e_money($importo); ?></strong>
                            </div>
                        </td>
                        <?php foreach (array_keys($data) as $fine_mese): ?>
                            <?php
    $centro_costo = $raggruppa?$cliente['payments_centro_costo_ricavo']:'non definito';
                            $total_amount = isset($totals[$centro_costo][$cliente['customers_id']][$fine_mese]['total_amount']) ? $totals[$centro_costo][$cliente['customers_id']][$fine_mese]['total_amount'] : 0;
                            $incassato = isset($totals[$centro_costo][$cliente['customers_id']][$fine_mese]['incassato']) ? $totals[$centro_costo][$cliente['customers_id']][$fine_mese]['incassato'] : 0;
                            $da_incassare = isset($totals[$centro_costo][$cliente['customers_id']][$fine_mese]['da_incassare']) ? $totals[$centro_costo][$cliente['customers_id']][$fine_mese]['da_incassare'] : 0;
                            $da_fatturare = isset($totals[$centro_costo][$cliente['customers_id']][$fine_mese]['da_fatturare']) ? $totals[$centro_costo][$cliente['customers_id']][$fine_mese]['da_fatturare'] : 0;

                            $last_payment_date = isset($totals[$centro_costo][$cliente['customers_id']][$fine_mese]['last_payment_date']) ? $totals[$centro_costo][$cliente['customers_id']][$fine_mese]['last_payment_date'] : null;
                            $class = '';
                            if ($total_amount > 0) {
                                if ($total_amount == $incassato) {
                                    if ($da_fatturare) {
                                        $class = 'non-fatturato-incassato';
                                    } else {
                                        $class = 'incassato';
                                    }

                                } elseif ($incassato > 0 && $da_incassare > 0) {

                                    $class = 'parziale';

                                } elseif ($last_payment_date && $last_payment_date < new DateTime()) {
                                    if ($da_fatturare) {
                                        $class = 'non-fatturato-non-incassato';
                                    } else {
                                        $class = 'non-incassato';
                                    }

                                }


                            }

                            ?>
                            <td class="<?php echo $class . ' ' . (($total_amount > 0) ? 'js_open_modale_pagamenti' : 'js_open_modale_nuovo_pagamento') ?>"
                                data-cliente="<?php echo $cliente['customers_id']; ?>"
                                data-centro_di_costo="<?php echo $cliente['payments_centro_costo_ricavo']; ?>"
                                data-mese="<?php echo explode('-', $fine_mese)[1]; ?>"
                                data-anno="<?php echo explode('-', $fine_mese)[0]; ?>">
                                <?php if ($total_amount > 0): ?>
                                    <strong>€ <?php e_money($total_amount); ?></strong>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                        <td><strong>€ <?php e_money($cliente['fatturato']); ?></strong></td>
                        <td>
                            <strong>€ <?php e_money($cliente['da_fatturare']); ?></strong>
                        </td>
                        <td>
                            <strong>€ <?php e_money($cliente['incassato']); ?></strong>
                        </td>
                        <td>
                            <strong>€ <?php e_money($cliente['da_incassare']); ?></strong>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <!-- Aggiungi la riga dei totali -->
                <tr>
                    <td class="td_left"><strong>Totale</strong></td>
                    <td>
                        <strong>€ <?php e_money($total_importo); ?></strong>
                    </td>
                    <?php foreach (array_keys($data) as $fine_mese): ?>
                        <td>
                            <strong>€ <?php e_money($total_monthly[$fine_mese]); ?></strong>
                        </td>
                    <?php endforeach; ?>
                    <td><strong>€ <?php e_money($total_fatturato); ?></strong></td>
                    <td>
                        <strong>€ <?php e_money($total_da_fatturare); ?></strong>
                    </td>
                    <td>
                        <strong>€ <?php e_money($total_incassato); ?></strong>
                    </td>
                    <td>
                        <strong>€ <?php e_money($total_da_incassare); ?></strong>
                    </td>
                </tr>

            <?php else: ?>
                <tr>
                    <td colspan="<?php echo 3 + count($data); ?>">No data available</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="legend">
        <div class="legend-item">
            <span class="legend-color incassato"></span>
            <span>Incassato</span>
        </div>
        <div class="legend-item">
            <span class="legend-color non-incassato"></span>
            <span>Non incassato</span>
        </div>
        <div class="legend-item">
            <span class="legend-color parziale"></span>
            <span>Pagamento parziale</span>
        </div>
        <div class="legend-item">
            <span class="legend-color futuro"></span>
            <span>Pagamento futuro</span>
        </div>

        <div class="legend-item">
            <span class="legend-color non-fatturato-non-incassato"></span>
            <span>Non fatturato non incassato</span>
        </div>
        <div class="legend-item">
            <span class="legend-color non-fatturato-incassato"></span>
            <span>Non fatturato incassato</span>
        </div>
    </div>
</div>

<script>
    $(() => {
        $('.js_open_modale_nuovo_pagamento').on('click', function () {
            var mese = $(this).data('mese');
            var cliente = $(this).data('cliente');
            var anno = $(this).data('anno');
            
            var data_pagamento = '01' + '/' + mese + '/' + anno;
            var centro_di_costo = $(this).data('centro_di_costo');
            
            loadModal(base_url + 'get_ajax/modal_form/payments_form?payments_customer=' + cliente + '&payments_date=' + data_pagamento + '&payments_centro_costo_ricavo=' + centro_di_costo);
        });
        $('.js_open_modale_pagamenti').on('click', function () {
            var mese = $(this).data('mese');
            var cliente = $(this).data('cliente');
            var anno = $(this).data('anno');

            loadModal(base_url + 'get_ajax/layout_modal/modale_payments?mese=' + mese + '&cliente=' + cliente + '&anno=' + anno + '&data_da=<?php echo $data_da; ?>&data_a=<?php echo $data_a; ?>&_size=extra');
        });
        $('.js_open_modale_pagamenti_all').on('click', function () {

            var cliente = $(this).data('cliente');


            loadModal(base_url + 'get_ajax/layout_modal/modale_payments?all=1&cliente=' + cliente + '&data_da=<?php echo $data_da; ?>&data_a=<?php echo $data_a; ?>&_size=extra');
        });
    });
</script>