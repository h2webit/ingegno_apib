<style>
    tr {
        border-radius: 0px !important;
        border-right: 0px !important;
        border-bottom: 1px solid #ddd !important;
        border-left: 0px !important;
    }

    .text-green {
        color: green;
    }

    .text-red {
        color: red;
    }
</style>
<?php $this->layout->addModuleStylesheet('projects', 'css/counters.css'); ?>

<?php
$project = $this->apilib->view('projects', $value_id);
$default_currency = $this->db->where('currencies_default', DB_BOOL_TRUE)->get('currencies')->row()->currencies_symbol;
?>
<div class="row">
    <div class="col-sm-12">
        <table class="table table-striped">
            <?php
            $prezzo_vendita_totale = 0;
            $ordini = $this->apilib->search('documenti_contabilita_commesse', ['documenti_contabilita_commesse_projects_id' => $value_id]);
            if (!$ordini) {
                $label_vendita = "Prezzo di vendita";
                $prezzo_vendita_totale = $project['projects_sold_price'];
            ?>
                <tr>
                    <td><?php echo $label_vendita; ?></td>
                    <td class="text-green"><?php echo $default_currency . " " . $prezzo_vendita_totale; ?></td>
                </tr>
                <?php
            } else {
                foreach ($ordini as $ordine) {
                    $documento_contabilita = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_id' => $ordine['documenti_contabilita_commesse_documenti_contabilita_id']]);
                    $prezzo_vendita = $documento_contabilita['documenti_contabilita_totale'];
                    if($documento_contabilita['documenti_contabilita_tipo']==5){
                        $tipologia_ordine = "Ordine cliente";
                    }elseif($documento_contabilita['documenti_contabilita_tipo']==14){
                        $tipologia_ordine = "Ordine interno";
                    }
                    $label_vendita = "<strong>".$tipologia_ordine." " . $documento_contabilita['documenti_contabilita_numero'] . " del " . dateFormat($documento_contabilita['documenti_contabilita_data_emissione']) . "</strong>";
                    $prezzo_vendita_totale += $prezzo_vendita;
                ?>
                    <tr>
                        <td colspan="2"><?php echo $label_vendita; ?></td>
                        <td class="text-green"><strong><?php echo $default_currency . " " . $prezzo_vendita; ?></strong></td>
                    </tr>
                    <?php
                    if (isset($documento_contabilita)) {
                        $i = 0;
                        $articoli_ordine_cliente = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_contabilita['documenti_contabilita_id']]);
                        foreach ($articoli_ordine_cliente as $articolo_ordine) {
                            $i++;
                    ?>
                            <tr>
                                <td colspan="2" style="padding-left:50px;"><?php echo $i . ") "; ?> <?php echo $articolo_ordine['documenti_contabilita_articoli_name']; ?></strong></td>
                                <td><?php echo $default_currency . " " . $articolo_ordine['documenti_contabilita_articoli_importo_totale']; ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    <?php
                    }
                    ?>

            <?php
                }
            }



            $expenses = $this->db->query("SELECT SUM(CASE  WHEN expenses_type = 1 THEN expenses_amount ELSE expenses_amount*(-1) END) as s FROM expenses WHERE expenses_project_id = '{$value_id}'")->row()->s;
            $expenses_detail = $this->db->query("SELECT * FROM expenses WHERE expenses_project_id = '{$value_id}'")->result_array();
            if ($this->datab->module_installed('timesheet')) {
                $worked_hours = $this->db->query("SELECT SUM(timesheet_total_hours) as s FROM timesheet WHERE timesheet_project = '{$value_id}'")->row()->s;
                $worked_hours_cost = $this->db->query("SELECT SUM(timesheet_total_cost) as s FROM timesheet WHERE timesheet_project = '{$value_id}'")->row()->s;
                $worked_hours_detail = $this->db->query("SELECT * FROM timesheet WHERE timesheet_project = '{$value_id}' ORDER BY timesheet_start_time")->result_array();
            } else {
                $worked_hours = 0;
                $worked_hours_cost = 0;
                $worked_hours_detail = [];
            }
            //aggiungo gli interventi
            if ($this->datab->module_installed('tickets-report')) {
                $tickets_report = $this->db->query("SELECT SUM(tickets_reports_billable_hours) as s FROM tickets_reports WHERE tickets_reports_project_id = '{$value_id}'")->row()->s;
                $tickets_report_detail = $this->db->query("SELECT * FROM tickets_reports WHERE tickets_reports_project_id = '{$value_id}' ORDER BY tickets_reports_id")->result_array();
                //calcolo costo totale interventi
                $costo_interventi = 0;
                foreach ($tickets_report_detail as $ticket_report) :
                    $utenti = $this->apilib->search('tickets_reports_tecnici', ['tickets_reports_id' => $ticket_report['tickets_reports_id']]);
                    foreach ($utenti as $utente_report) {
                        $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                        //trovo il costo orario 
                        $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente_report['users_id']]);
                        $costo_interventi += $ticket_report['tickets_reports_billable_hours'] * $dipendente['dipendenti_valore_orario'];
                    }
                endforeach;                            
            } else {
                $tickets_report = 0;
                $tickets_report_detail = [];
                $costo_interventi = 0;
            }
            //vedo i tickets
            if ($this->datab->module_installed('tickets')) {
                $tickets = $this->db->query("SELECT * FROM tickets WHERE tickets_project_id = '{$value_id}'")->result_array();
            } else {
                $tickets = [];
            }
            $articoli = $this->apilib->search('projects_items', ['projects_items_project' => $value_id]);
            $articoli_ordinati = 0;
            $articoli_ordinati_valore = 0;
            foreach ($articoli as $articolo) {
                $articoli_dettagli = $this->apilib->searchFirst('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $articolo['projects_items_product']]);
                $articoli_ordinati_valore += $articoli_dettagli['documenti_contabilita_articoli_importo_totale'];
                $articoli_ordinati++;
            }
            //trovo articoli ordinati

            $total_balance = $prezzo_vendita_totale - $expenses - $worked_hours_cost - $articoli_ordinati_valore;
            $margin_balance = ($prezzo_vendita_totale > 0) ? (100 * $total_balance) / $prezzo_vendita_totale : 0;

            ?>
            <?php
            if ($expenses_detail) :
            ?>
                <tr>
                    <td colspan="2"><strong><?php e('Expenses'); ?></strong></td>
                    <td class="text-red"><strong><?php echo $default_currency . " " . number_format($expenses, 2, '.', '.'); ?></strong></td>
                </tr>
                <?php
                $i = 0;
                foreach ($expenses_detail as $spesa) :
                    $i++;
                ?>
                    <tr>
                        <td colspan="2" style="padding-left:50px;"><?php echo $i . ") "; ?> <?php echo $spesa['expenses_description']; ?></strong></td>
                        <td><?php echo $default_currency . " " . number_format($spesa['expenses_amount'], 2, '.', '.'); ?></td>
                    </tr>
            <?php
                endforeach;
            endif;
            ?>
            <?php
            if ($worked_hours_detail) :
            ?>
                <tr>
                    <td><strong><?php e('Worked hours'); ?></strong></td>
                    <td class="text-red"><strong><?php echo number_format($worked_hours, 2, '.', '.'); ?> <?php e('hours'); ?></strong></td>
                    <td class="text-red"><strong><?php echo $default_currency . " " . number_format($worked_hours_cost, 2, '.', '.'); ?></strong></td>
                </tr>
                <?php
                $i = 0;
                foreach ($worked_hours_detail as $ora_lavorata) :
                    $i++;
                    $utente = $this->apilib->searchFirst('users', ['users_id' => $ora_lavorata['timesheet_member']]);
                ?>
                    <tr>
                        <td style="padding-left:50px;"><?php echo $i . ") "; ?><?php echo $utente['users_last_name'] . " " . substr($utente['users_first_name'], 0, 1) . " - " . dateFormat($ora_lavorata['timesheet_start_time']); ?></strong></td>
                        <td><?php echo number_format($ora_lavorata['timesheet_total_hours'], 2, '.', ',') . " "; ?> <?php $ora_lavorata['timesheet_total_hours'] > 1 ? e('hours') : e('hour') ?></td>
                        <td><?php echo $default_currency . " " . number_format($ora_lavorata['timesheet_total_cost'], 2, '.', ','); ?></td>
                    </tr>
            <?php
                endforeach;
            endif;
            ?>
            <?php
            if ($tickets_report) :
            ?>
                <tr>
                    <td><strong>Interventi</strong></td>

                    <td class="text-red"><strong><?php echo number_format($tickets_report, 2, '.', '.'); ?> <?php $tickets_report > 1 ? e('hours') : e('hour') ?></strong></td>
                    <td class="text-red"><strong><?php echo $default_currency . " " . number_format($costo_interventi, 2, '.', '.'); ?></strong></td>
                </tr>
                <?php
                $i = 0;
                foreach ($tickets_report_detail as $ticket_report) :
                    $i++;
                    //trovo i singoli tecnici
                    $utenti = $this->apilib->search('tickets_reports_tecnici', ['tickets_reports_id' => $ticket_report['tickets_reports_id']]);
                ?>
                    <tr>
                        <td style="padding-left:50px;"><?php echo $i . ") "; ?>
                            <?php
                            echo "Intervento #" . $ticket_report['tickets_reports_id'] . " del " . dateFormat($ticket_report['tickets_reports_date']) . " (";
                            ?>
                            <?php
                            $costo_intervento = 0;
                            $ciclo = 1;
                            foreach ($utenti as $utente_report) {
                                $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                                //trovo il costo orario 
                                $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente_report['users_id']]);
                                $costo_intervento += $ticket_report['tickets_reports_billable_hours'] * $dipendente['dipendenti_valore_orario'];
                                echo $utente['users_last_name'] . " " . substr($utente['users_first_name'], 0, 1);
                                if($ciclo < count($utenti)){
                                    echo " - ";
                                }
                                $ciclo++;
                            }

                            echo " ) "  ?></strong></td>
                        <td><?php echo number_format($ticket_report['tickets_reports_billable_hours'], 2, '.', ',') . " "; ?> <?php $ticket_report['tickets_reports_billable_hours'] > 1 ? e('hours') : e('hour') ?></td>
                        <td><?php echo $default_currency . " " . number_format($costo_intervento, 2, '.', '.'); ?></td>
                    </tr>
            <?php
                endforeach;
            endif;
            ?>
            <?php
            if ($articoli) :
            ?>
                <tr>
                    <td colspan="2"><strong><?php e('Articoli ordinati'); ?> (<?php echo $articoli_ordinati; ?>)</strong></td>

                    <td class="text-red"><strong><?php echo $default_currency . " " . $articoli_ordinati_valore; ?></strong></td>
                </tr>
                <?php
                $i = 0;
                foreach ($articoli as $articolo) :
                    $i++;
                ?>
                    <tr>
                        <td colspan="2" style="padding-left:50px;"><?php echo $i . ") "; ?><?php echo $articolo['documenti_contabilita_articoli_name']; ?></strong></td>
                        <td><?php echo $default_currency . " " . (($articolo['documenti_contabilita_articoli_prezzo'] + $articolo['documenti_contabilita_articoli_prezzo'] / 100 * $articolo['documenti_contabilita_articoli_iva_perc']) * $articolo['documenti_contabilita_articoli_quantita']); ?></td>
                    </tr>
            <?php
                endforeach;
            endif;
            ?>
            <tr>
                <td colspan="2"><strong><?php e('Total'); ?></strong></td>

                <td class="<?php echo ($total_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo $default_currency . " " . $total_balance; ?></strong></td>
            </tr>

            <tr>
                <td colspan="2"><strong><?php e('Expected profit'); ?></strong></td>

                <td class="<?php echo ($margin_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo number_format($margin_balance, 2, '.', '.'); ?>%</strong></td>
            </tr>
        </table>
    </div>
</div>
<?php
if ($tickets) :
?>
    <div class="row">
        <div class="col-sm-12">
            <table class="table table-striped">
                    <tr>
                        <td colspan="3"><strong><?php e('Tickets'); ?></strong></td>
                    </tr>
                    <?php
                    $i = 0;
                    foreach ($tickets as $ticket) :
                        $i++;
                        $utenti = $this->apilib->search('tickets_tecnici', ['tickets_id' => $ticket['tickets_id']]);
                    ?>
                        <tr>
                            <td colspan="3" style="padding-left:50px;"><?php echo $i . ") Ticket #".$ticket['tickets_id']." del ".dateFormat($ticket['tickets_assign_date']); ?>
                                <?php
                                $ciclo = 1;
                                foreach ($utenti as $utente_report) {
                                    $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                                    echo " ( ".$utente['users_last_name'] . " " . substr($utente['users_first_name'], 0, 1);
                                    if($ciclo < count($utenti)){
                                        echo " - ";
                                    }
                                    $ciclo++;
                                }
                                echo " ) ".$ticket['tickets_subject']; ?></td>
                        </tr>
                        <?php
                            $reports = $this->db
                            ->where('tickets_reports_ticket_id', $ticket['tickets_id'])
                            ->get('tickets_reports')->result_array();
                            foreach($reports as $report):
                            ?>
                            <tr>
                                <td style="padding-left:100px;">
                                <?php
                                echo "Intervento #" . $report['tickets_reports_id'] . " del " . dateFormat($report['tickets_reports_date']) . " (";
                                ?>
                                <?php
                                $utenti = $this->apilib->search('tickets_reports_tecnici', ['tickets_reports_id' => $report['tickets_reports_id']]);
                                $costo_intervento = 0;
                                $ciclo = 1;
                                foreach ($utenti as $utente_report) {
                                    $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                                    //trovo il costo orario 
                                    $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente_report['users_id']]);
                                    $costo_intervento += $report['tickets_reports_billable_hours'] * $dipendente['dipendenti_valore_orario'];
                                    echo $utente['users_last_name'] . " " . substr($utente['users_first_name'], 0, 1);
                                    if($ciclo < count($utenti)){
                                        echo " - ";
                                    }
                                    $ciclo++;
                                }

                                echo " ) "  ?></td>
                                <td><?php echo number_format($report['tickets_reports_billable_hours'], 2, '.', ',') . " "; ?> <?php $report['tickets_reports_billable_hours'] > 1 ? e('hours') : e('hour') ?></td>
                                <td><?php echo $default_currency . " " . number_format($costo_intervento, 2, '.', '.'); ?></td>
                            </tr>
                <?php
                            endforeach;
                    endforeach;
                ?>
            </table>
        </div>
    </div>
<?php
endif;
?>