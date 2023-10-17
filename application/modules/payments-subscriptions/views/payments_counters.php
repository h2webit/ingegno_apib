<?php

$default_currency = $this->db->where('currencies_default', DB_BOOL_TRUE)->get('currencies')->row()->currencies_symbol;
$payments_cur_year = $this->db->where('payments_canceled', DB_BOOL_FALSE)->where('YEAR(payments_date) = YEAR(CURRENT_DATE())')->get('payments')->result_array();

$sum_cur_year = array_reduce($payments_cur_year, function ($sum1, $payment) {
    if (empty($payment['payments_amount'])) {
        $payment['payments_amount'] = 0;
    }

    return $sum1 + $payment['payments_amount'];
}, 0);

$payments_unpaid = $this->db->where('payments_canceled', DB_BOOL_FALSE)->where('YEAR(payments_date) = YEAR(CURRENT_DATE())')->where('payments_paid', DB_BOOL_FALSE)->get('payments')->result_array();

$sum_unpaid = array_reduce($payments_unpaid, function ($sum2, $payment) {
    if (empty($payment['payments_amount'])) {
        $payment['payments_amount'] = 0;
    }

    return $sum2 + $payment['payments_amount'];
}, 0);

$payments_invoice_not_sent = $this->db->where('payments_canceled', DB_BOOL_FALSE)->where('YEAR(payments_date) = YEAR(CURRENT_DATE())')->where('payments_invoice_sent', DB_BOOL_FALSE)->get('payments')->result_array();

$sum_invoice_not_sent = array_reduce($payments_invoice_not_sent, function ($sum3, $payment) {
    if (empty($payment['payments_amount'])) {
        $payment['payments_amount'] = 0;
    }

    return $sum3 + $payment['payments_amount'];
}, 0);

?>
<div class="row">
    <div class="col-sm-4">
        <div class="small-box bg-green">
            <div class="inner">
                <h3><?php echo $default_currency; ?> <?php echo number_format($sum_cur_year, 2, '.', ' '); ?></h3>

                <p>Current Year</p>
            </div>
            <div class="icon">
                <i class="far fa-calendar"></i>
            </div>
        </div>
    </div>



    <div class="col-sm-4">
        <div class="small-box bg-orange">
            <div class="inner">
                <h3><?php echo $default_currency; ?> <?php echo number_format($sum_invoice_not_sent, 2, '.', ' '); ?></h3>

                <p>Invoice not sent</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-import"></i>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="small-box bg-red">
            <div class="inner">
                <h3><?php echo $default_currency; ?> <?php echo number_format($sum_unpaid, 2, '.', ' '); ?></h3>

                <p>Unpaid</p>
            </div>
            <div class="icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
    </div>

</div>