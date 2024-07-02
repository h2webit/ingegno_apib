<?php
//calcolo le billable hours per il saldo ore
$billable = 0;
$billable = $this->db->query("SELECT SUM(billable_hours_hours) as s FROM billable_hours WHERE billable_hours_project_id = '{$value_id}'")->row()->s;
?>


<div class="row">
    <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fas fa-hourglass"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Saldo ore</span>
                <span class="info-box-number "><?php echo number_format($billable, 2, ',', '.'); ?> </span>
                <div class="progress">
                    <div class="progress-bar" style="width: 100.00%"></div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.col -->
</div>