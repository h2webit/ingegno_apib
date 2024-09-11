<?php

// Get INGENGO Settings
$ingegno_settings = $this->db->query("SELECT * FROM ingegno_settings")->result_array();
$ingegno_settings = array_key_value_map($ingegno_settings, 'ingegno_settings_key', 'ingegno_settings_value');


$this->load->model('projects/projects');
?>

<style>
    .conteggi_info {
        padding: 10px;
        text-align: center;
        width: 100%;
    }

    .conteggio_info .green {
        color: #16a34a;
    }

    .conteggio_info .blue {
        color: #0284c7;
    }

    .conteggio_info .orange {
        color: #d97706;
    }

    .conteggio_info .purple {
        color: #605ca8;
    }

    .conteggio_info .red {
        color: #8B0000;
    }

    .conteggio_info {
        font-size: 16px;
    }

    .conteggio_counter {
        font-size: 30px;
    }

    .conteggio_label {
        font-size: 16px;
        font-weight: bold;

    }
</style>


<!--------- Saldo ore ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_billable_balance']) && $ingegno_settings['enable_big_counters_billable_balance'] == DB_BOOL_TRUE): ?>
    <?php $billable_hours_balance = $this->projects->get_billable_hours_balance($value_id); ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-balance-scale"></i> Saldo ore</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter <?php echo ($billable_hours_balance > 0) ? 'green' : 'red'; ?>">
                    <?php e_money($billable_hours_balance); ?> </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--------- Ore interventi ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_interventi_hours']) && $ingegno_settings['enable_big_counters_interventi_hours'] == DB_BOOL_TRUE): ?>
    <?php $interventi_hours = $this->projects->get_interventi_hours($value_id); ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-tools"></i> Ore interventi</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter blue">
                    <?php e_money($interventi_hours['lavorate']); ?> </span>
            </div>
        </div>
    </div>

    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-euro-sign"></i> Ore interventi</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter blue">
                    <?php e_money($interventi_hours['fatturate']); ?> </span>
            </div>
        </div>
    </div>
<?php endif; ?>


<!--------- Timesheet ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_timesheet']) && $ingegno_settings['enable_big_counters_timesheet'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-clock"></i> Timesheet</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter blue">
                    <?php e_money($this->projects->get_project_worked_hours($value_id)['worked_hours']); ?> </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--------- € Timesheet ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_timesheet_costo']) && $ingegno_settings['enable_big_counters_timesheet_costo'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-hourglass-half"></i> Costo ore</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter orange">
                    €
                    <?php e_money($this->projects->get_project_worked_hours($value_id)['worked_hours_cost'], '{number}', 0); ?>
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--------- Timesheet ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_spese']) && $ingegno_settings['enable_big_counters_spese'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-shopping-cart"></i> Spese</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter red">
                    € <?php e_money($this->projects->get_project_expenses($value_id)); ?> </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--------- Progress task ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_progress_task']) && $ingegno_settings['enable_big_counters_progress_task'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-percentage"></i> Progress</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter blue">
                    <?php echo number_format($this->projects->get_progress_task($value_id), 0); ?>% </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--------- Ordini cliente ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_ordini_cliente']) && $ingegno_settings['enable_big_counters_ordini_cliente'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-check"></i> Ordini Cli.</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter green">
                    € <?php e_money($this->projects->get_project_orders($value_id)['ordini_cliente'], '{number}', 0); ?>
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>


<!--------- Ordini cliente ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_ordini_fornitore']) && $ingegno_settings['enable_big_counters_ordini_fornitore'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-shopping-cart"></i> Ordini Forn.</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter red">
                    € <?php e_money($this->projects->get_project_orders($value_id)['ordini_fornitore'], '{number}', 0); ?>
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--------- Fatturato ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_fatturato']) && $ingegno_settings['enable_big_counters_fatturato'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-euro-sign"></i> Fatturato</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter green">
                    € <?php e_money($this->projects->get_project_orders($value_id)['fatturato'], '{number}', 0); ?> </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--------- Pagamenti totali ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_totale_pagamenti']) && $ingegno_settings['enable_big_counters_totale_pagamenti'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-euro-sign"></i> Tot. Pagamenti</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter green">
                    € <?php e_money($this->projects->get_project_total_payments($value_id)['all'], '{number}', 0); ?>
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--------- Pagamenti totali ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_pagamenti_non_saldati']) && $ingegno_settings['enable_big_counters_pagamenti_non_saldati'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-euro-sign"></i> Pag. non saldati</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter red">
                    € <?php e_money($this->projects->get_project_total_payments($value_id)['non_pagato'], 0); ?> </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--------- MOL ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_mol_fatturato']) && $ingegno_settings['enable_big_counters_mol_fatturato'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-percentage"></i> MOL Fatturato</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter blue">
                    <?php echo number_format($this->projects->get_project_mol($value_id)['fatturato_percentage'], 0); ?>%
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!--------- MOL ------------>

<?php if (!empty($ingegno_settings['enable_big_counters_mol_ordinato']) && $ingegno_settings['enable_big_counters_mol_ordinato'] == DB_BOOL_TRUE): ?>
    <div class="col-xs-2">
        <div class="conteggi_info">
            <div class="conteggio_info">
                <span class="conteggio_label"><i class="fas fa-percentage"></i> MOL Ordinato</span>
            </div>
            <div class="conteggio_info">
                <span class="conteggio_counter blue">
                    <?php echo number_format($this->projects->get_project_mol($value_id)['ordinato_percentage'], 0); ?>%
                </span>
            </div>
        </div>
    </div>
<?php endif; ?>