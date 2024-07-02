<?php 
$contracts = $this->db->query("SELECT COUNT(*) AS c FROM contracts WHERE YEAR(contracts_date) = YEAR(CURRENT_TIMESTAMP) ")->row()->c;
$payments = $this->db->query("SELECT SUM(payments_amount) AS c FROM payments WHERE payments_canceled = '".DB_BOOL_FALSE."' AND YEAR(payments_date) = YEAR(CURRENT_TIMESTAMP) ")->row()->c;
$invoices = $this->db->query("SELECT SUM(billing_documents_total) AS c FROM billing_documents WHERE billing_documents_type = 1 AND YEAR(billing_documents_date) = YEAR(CURRENT_TIMESTAMP) ")->row()->c;

$debited_billable_hours = $this->db->query("SELECT SUM(billable_hours_hours) as total FROM billable_hours WHERE billable_hours_type = 2 AND billable_hours_project_id = '{$value_id}'")->row()->total;
$paid_billable_hours = $this->db->query("SELECT SUM(billable_hours_hours) as total FROM billable_hours WHERE billable_hours_type = 1 AND billable_hours_project_id = '{$value_id}'")->row()->total;
$free_billable_hours = $this->db->query("SELECT SUM(billable_hours_hours) as total FROM billable_hours WHERE billable_hours_type = 4 AND billable_hours_project_id = '{$value_id}'")->row()->total;
$unbilled_billable_hours = abs(($paid_billable_hours - $debited_billable_hours)+$free_billable_hours);

?>
<div class="row">

<div class="col-lg-3 col-md-3 col-sm-4 col-xs-6">
    <div class="small-box bg-primary">
        <div class="inner">
            <h3><?php echo $contracts; ?></h3>

            <p>Contracts Current year</p>
        </div>
        <div class="icon">
            <i class="fas fa-hourglass"></i>
        </div>
        <a href="javascript:;" class="small-box-footer more">
            Contracts <i class="fa fa-arrow-circle-right"></i>
        </a>
    </div>
</div>


<div class="col-lg-3 col-md-3 col-sm-4 col-xs-6">
    <div class="small-box bg-primary">
        <div class="inner">
            <h3><?php echo number_format($payments, 2,',','.'); ?></h3>

            <p>Total Payments Current year</p>
        </div>
        <div class="icon">
            <i class="fas fa-hourglass"></i>
        </div>
        <a href="javascript:;" class="small-box-footer more">
            Payments <i class="fa fa-arrow-circle-right"></i>
        </a>
    </div>
</div>

<div class="col-lg-3 col-md-3 col-sm-4 col-xs-6">
    <div class="small-box bg-primary">
        <div class="inner">
            <h3><?php echo number_format($invoices, 2,',','.'); ?></h3>

            <p>Total Invoices Current year</p>
        </div>
        <div class="icon">
            <i class="fas fa-hourglass"></i>
        </div>
        <a href="javascript:;" class="small-box-footer more">
            Invoices <i class="fa fa-arrow-circle-right"></i>
        </a>
    </div>
</div>

<div class="col-lg-3 col-md-3 col-sm-4 col-xs-6">
    <div class="small-box bg-primary">
        <div class="inner">
            <h3><?php echo number_format($unbilled_billable_hours, 2, '.', ','); ?></h3>

            <p>Unbilled Billable Hours</p>
        </div>
        <div class="icon">
            <i class="fas fa-hourglass"></i>
        </div>
        <a href="javascript:;" class="small-box-footer more">
            Unbilled Hours <i class="fa fa-arrow-circle-right"></i>
        </a>
    </div>
</div>

</div>                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    