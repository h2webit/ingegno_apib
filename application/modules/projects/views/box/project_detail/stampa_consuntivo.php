<style>
   body {
   font-size: 11px;
   }
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
   .center {
   text-align: center;
   }
</style>
<?php $this->layout->addModuleStylesheet('projects', 'css/counters.css'); ?>
<?php
   //$valore_orario_fatt = 40;
   $project = $this->apilib->view('projects', $value_id);
   $default_currency = $this->db->where('currencies_default', DB_BOOL_TRUE)->get('currencies')->row()->currencies_symbol;
   $hr_installato = $this->datab->module_installed('modulo-hr');
   $contabilita_installato = $this->datab->module_installed('contabilita');
   $prezzo_vendita_totale = 0;
   $totale_ordini = 0;
   $total_balance = 0;
?>
<div class="row">
   <div class="col-sm-12">
      <table class="table table-striped">
         <?php if ($contabilita_installato == DB_BOOL_TRUE): ?>
         <tr>
            <td><strong>Descrizione</strong></td>
            <td><strong>Ore lav</strong></td>
            <td><strong>€ fatt</strong></td>
            <td><strong>Costo interno</strong></td>
            <td><strong>Tot</strong></td>
         </tr>
         <?php
            $ordini = $this->apilib->search('documenti_contabilita_commesse', ['documenti_contabilita_commesse_projects_id' => $value_id, 'documenti_contabilita_tipo <>' => 14]);
            if (!$ordini):
                $label_vendita = "Prezzo di vendita";
                $prezzo_vendita_totale = $project['projects_sold_price'];
            ?>
         <tr>
            <td colspan="4"><?php echo $label_vendita; ?></td>
            <td class="text-green"><?php echo $default_currency . " " . $prezzo_vendita_totale; ?></td>
         </tr>
         <?php
            else:
               foreach ($ordini as $ordine) :
                  $documento_contabilita = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_id' => $ordine['documenti_contabilita_commesse_documenti_contabilita_id']]);
                  if (!empty($documento_contabilita)):
                     $prezzo_vendita = $documento_contabilita['documenti_contabilita_competenze'];
                     $tipologia_ordine = $documento_contabilita['documenti_contabilita_tipo_value'];
                     $label_vendita = "<strong>" . $tipologia_ordine . " " . $documento_contabilita['documenti_contabilita_numero'] . " del " . dateFormat($documento_contabilita['documenti_contabilita_data_emissione']) . "</strong>";
                     $prezzo_vendita_totale += $prezzo_vendita;
                     $totale_ordini += $prezzo_vendita;
                     ?>
                     <tr>
                           <td colspan="4"><?php echo $label_vendita; ?></td>
                           <td class="text-green"><strong><?php echo $default_currency . " " . $prezzo_vendita; ?></strong></td>
                     </tr>
                     <?php
                        $i = 0;
                        $articoli_ordine_cliente = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_contabilita['documenti_contabilita_id']]);
                        foreach ($articoli_ordine_cliente as $articolo_ordine):
                           if ($articolo_ordine['documenti_contabilita_articoli_riga_desc'] == DB_BOOL_TRUE) {
                              $articolo_ordine['documenti_contabilita_articoli_quantita'] = 1;
                           }
                           $prezzo_no_ivato = ($articolo_ordine['documenti_contabilita_articoli_importo_totale'] - $articolo_ordine['documenti_contabilita_articoli_iva']);
                           $i++;
                           ?>
                           <tr>
                              <td colspan="4" style="background:color:red;padding-left:50px;"><?php echo $i . ") "; ?> <?php echo number_format($articolo_ordine['documenti_contabilita_articoli_quantita'], 0, '.', '.') . " x " . $articolo_ordine['documenti_contabilita_articoli_name']; ?></strong></td>
                              <td><?php echo $default_currency . " " . number_format($prezzo_no_ivato, 2, '.', '.');; ?></td>
                           </tr>
                        <?php
                        endforeach;
                  endif;
               endforeach;
            endif;
            ?>
         <tr>
            <td colspan="4" class="text-green"><strong>Totale ordini cliente</strong></td>
            <td class="text-green"><strong><?php echo $default_currency . " " . $prezzo_vendita_totale; ?></strong></td>
         </tr>
         <?php
            endif;
            ?>
         <?php
            $expenses = $this->db->query("SELECT SUM(CASE  WHEN expenses_type = 1 THEN expenses_amount ELSE expenses_amount*(-1) END) as s FROM expenses WHERE expenses_project_id = '{$value_id}'")->row()->s;
            $expenses_detail = $this->db->query("SELECT * FROM expenses WHERE expenses_project_id = '{$value_id}'")->result_array();
            if ($this->datab->module_installed('timesheet')) {
                $worked_hours = $this->db->query("SELECT SUM(timesheet_total_hours) as s FROM timesheet WHERE timesheet_project = '{$value_id}'")->row()->s;
                $worked_hours_cost = $this->db->query("SELECT SUM(timesheet_total_cost) as s FROM timesheet WHERE timesheet_project = '{$value_id}'")->row()->s;
                $worked_hours_detail = $this->db->query("SELECT * FROM timesheet WHERE timesheet_project = '{$value_id}' ORDER BY timesheet_start_time")->result_array();
                // $worked_hours_senza_report = $this->db->query("SELECT * FROM timesheet WHERE timesheet_project = '{$value_id}' AND billable_hours_report_id IS NULL ORDER BY timesheet_start_time")->result_array();
            } else {
                $worked_hours = 0;
                $worked_hours_cost = 0;
                $worked_hours_detail = [];
                //$worked_hours_senza_report = [];
            }
            if ($this->datab->module_installed('billable-hours')) {
                $prezzo_medio = $this->db->query("SELECT AVG(billable_hours_sell_price) AS prezzo_medio FROM billable_hours WHERE billable_hours_type = 1 && billable_hours_project_id = '{$value_id}'")->row()->prezzo_medio;
            } else {
                $prezzo_medio = 0;
            }
            //aggiungo gli interventi
            if ($this->datab->module_installed('tickets-report')) {
               $tickets_reports = [];
               $tickets_report = $this->db->query("SELECT SUM(tickets_reports_billable_hours) as s FROM tickets_reports WHERE tickets_reports_project_id = '{$value_id}'")->row()->s;
               $tickets_report_detail = $this->db->query("SELECT * FROM tickets_reports WHERE tickets_reports_project_id = '{$value_id}' ORDER BY tickets_reports_id")->result_array();
               //calcolo il costo medio di vendita orario
               //calcolo costo totale interventi
               $costo_interventi = 0;
               $hours = 0;
               $ore_diff_report = 0;
               $ore_lavorate_tickets_report = 0;
               $euro_fatturate_tickets_report = 0;
               foreach ($tickets_report_detail as $ticket_report) :
                  //inizializzo la chiave del tickets_reports_id
                  $tickets_reports[$ticket_report['tickets_reports_id']] = [
                     'title' => "Intervento #" . $ticket_report['tickets_reports_id'] . " del " . dateFormat($ticket_report['tickets_reports_date']) . " (",
                  ];
                  
                  //calcolo ore lavorate
                  $start_time = new DateTime($ticket_report['tickets_reports_start_time']);
                  $end_time = new DateTime($ticket_report['tickets_reports_end_time']);
                  $diff = $end_time->diff($start_time);
                  $ore_diff_report = round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
                  $utenti = $this->apilib->search('tickets_reports_tecnici', ['tickets_reports_id' => $ticket_report['tickets_reports_id']]);
                  $conta_utenti = 0;
                  $ciclo = 1;
                  $costo_intervento = 0;

                  foreach ($utenti as $utente_report) {
                     $conta_utenti++;
                     $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                     //trovo il costo orario 
                     if ($hr_installato == DB_BOOL_TRUE) {
                        //trovo il costo orario 
                        $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente['users_id']]);
                        $costo_intervento += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2) * (isset($dipendente['dipendenti_costo_orario']) ? $dipendente['dipendenti_costo_orario'] : 1);
                     } else {
                        $costo = $utente['users_cost_per_hour'] > 0 ? $utente['users_cost_per_hour'] : 5;
                        $costo_intervento += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2) * $costo;
                     }
                     $tickets_reports[$ticket_report['tickets_reports_id']]['title'] .= " ".$utente['users_last_name'] . " " . substr($utente['users_first_name'], 0, 1);
                     
                     if ($ciclo < count($utenti)) {
                        $tickets_reports[$ticket_report['tickets_reports_id']]['title'] .= " - ";

                     }
                     $ciclo++;

                  }
                  $costo_interventi += $costo_intervento;

                  $tickets_reports[$ticket_report['tickets_reports_id']]['title'] .= " ) ";
                  $tickets_reports[$ticket_report['tickets_reports_id']]['ore_lavorate'] = $ore_diff_report * $conta_utenti;
                  $tickets_reports[$ticket_report['tickets_reports_id']]['ore_fatturate'] = number_format($ticket_report['tickets_reports_billable_hours'], 2, '.', ',') ;
                  $tickets_reports[$ticket_report['tickets_reports_id']]['euro_fatturati'] = floor($ticket_report['tickets_reports_billable_hours'] * $prezzo_medio) == ($ticket_report['tickets_reports_billable_hours'] * $prezzo_medio) ? number_format($ticket_report['tickets_reports_billable_hours'] * $prezzo_medio, 0, '.', '') : number_format($ticket_report['tickets_reports_billable_hours'] * $prezzo_medio, 2, '.', '');
                  $tickets_reports[$ticket_report['tickets_reports_id']]['costo_interno'] = floor($costo_intervento) == ($costo_intervento) ? number_format($costo_intervento, 0, '.', '') : number_format($costo_intervento, 2, '.', '');
                  $tickets_reports[$ticket_report['tickets_reports_id']]['totale'] = floor($ticket_report['tickets_reports_billable_hours'] * $prezzo_medio - $costo_intervento) == ( $ticket_report['tickets_reports_billable_hours'] * $prezzo_medio - $costo_intervento ) ? number_format($ticket_report['tickets_reports_billable_hours'] * $prezzo_medio - $costo_intervento, 0, '.', '') : number_format($ticket_report['tickets_reports_billable_hours'] * $prezzo_medio - $costo_intervento, 2, '.', ',') ;

                  $hours += $ore_diff_report * $conta_utenti;
                  $ore_lavorate_tickets_report += $ticket_report['tickets_reports_billable_hours'];
                  $euro_fatturate_tickets_report += $ticket_report['tickets_reports_billable_hours'] * $prezzo_medio;
               endforeach;
            } else {
               $tickets_report = 0;
               $hours = 0;
               $tickets_report_detail = [];
               $costo_interventi = 0;
               $tickets_reports = [];
            }
            //vedo i tickets
            if ($this->datab->module_installed('tickets')) {
                $tickets = $this->db->query("SELECT * FROM tickets WHERE tickets_project_id = '{$value_id}'")->result_array();
            } else {
                $tickets = [];
            }
            //prendo gli articoli ordinati
            $articoli = $this->apilib->search('projects_items', ['projects_items_project' => $value_id]);
            $articoli_ordinati = 0;
            $articoli_ordinati_valore = 0;
            $ordini_fornitori = array();
            foreach ($articoli as $articolo) {
               //metto in un array i documenti di contabilità corrispondenti
               $articoli_dettagli = $this->apilib->searchFirst('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $articolo['projects_items_product']]);
               if (!in_array($articoli_dettagli['documenti_contabilita_articoli_documento'], $ordini_fornitori)) {
                  array_push($ordini_fornitori, $articoli_dettagli['documenti_contabilita_articoli_documento']);
               }
               $articoli_dettagli = $this->apilib->searchFirst('documenti_contabilita_articoli', ['documenti_contabilita_articoli_id' => $articolo['projects_items_product']]);
               $prezzo_no_ivato = ($articoli_dettagli['documenti_contabilita_articoli_importo_totale'] - $articoli_dettagli['documenti_contabilita_articoli_iva']);
               $articoli_ordinati_valore += $prezzo_no_ivato;
               $articoli_ordinati++;
            }
            //trovo articoli ordinati
            //calcolo ore rapportini
            if ($this->datab->module_installed('rapportini')) {
               //calcolo ore lavorate
               $ore_rapportini = 0;
               $rapportini_commessa = $this->apilib->search('rapportini', ['rapportini_commessa' => $value_id]);
         
               foreach ($rapportini_commessa as $rapportino) {
                  $start_time = new DateTime($rapportino['rapportini_ora_inizio']);
                  $end_time = new DateTime($rapportino['rapportini_ora_fine']);
                  $diff = $end_time->diff($start_time);
                  $ore_rapportini += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
               }
            }
            ?>
         <?php
            if (!empty($ordini_fornitori)) :
                $totale_ordini_fornitori = 0;
                foreach ($ordini_fornitori as $ordine_fornitore) :
                  $documento = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_id' => $ordine_fornitore]);
                  $totale_ordini_fornitori += $documento['documenti_contabilita_competenze'];
                  ?>
                  <tr>
                     <td colspan="4"><strong><?php echo $documento['documenti_contabilita_tipo_value'] . " " . $documento['documenti_contabilita_numero']; ?> del <?php echo dateFormat($documento['documenti_contabilita_data_emissione']); ?></strong></td>
                     <td class="text-red"><strong><?php echo $default_currency . " " . $documento['documenti_contabilita_competenze']; ?></strong></td>
                  </tr>
                  <?php
                     if (isset($documento)):
                        $i = 0;
                        $articoli_ordine_cliente = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento['documenti_contabilita_id']]);
                        foreach ($articoli_ordine_cliente as $articolo_ordine):
                           if ($articolo_ordine['documenti_contabilita_articoli_riga_desc'] == DB_BOOL_TRUE) {
                                 $articolo_ordine['documenti_contabilita_articoli_quantita'] = 1;
                           }
                           $prezzo_no_ivato = $articolo_ordine['documenti_contabilita_articoli_importo_totale'] - $articolo_ordine['documenti_contabilita_articoli_iva'];
                           $i++;
                           ?>
                           <tr>
                              <td colspan="4" style="background:color:red;padding-left:50px;"><?php echo $i . ") "; ?> <?php echo number_format($articolo_ordine['documenti_contabilita_articoli_quantita'], 0, '.', '.') . " x " . $articolo_ordine['documenti_contabilita_articoli_name']; ?></strong></td>
                              <td><?php echo $default_currency . " " . number_format($prezzo_no_ivato, 2, '.', '.'); ?></td>
                           </tr>
                           <?php
                        endforeach;
                     endif;
               endforeach;
               ?>
               <tr>
                  <td colspan="4" class="text-red"><strong>Totale ordini fornitori</strong></td>
                  <td class="text-red"><strong><?php echo $default_currency . " " . $totale_ordini_fornitori; ?></strong></td>
               </tr>
               <?php
                  $total_balance = ($totale_ordini - $totale_ordini_fornitori);
                  $margin_balance = ($total_balance > 0) ? (100 * $total_balance) / $prezzo_vendita_totale : 0;
                  ?>
               <tr>
                  <td colspan="4"><strong><?php e('Total'); ?></strong></td>
                  <td class="<?php echo ($total_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo $default_currency; ?><?php echo $total_balance; ?></strong></td>
               </tr>
               <tr>
                  <td colspan="4"><strong><?php e('Expected profit'); ?></strong></td>
                  <td class="<?php echo ($margin_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo number_format($margin_balance, 2, '.', '.'); ?>%</strong></td>
               </tr>
            <?php
            endif;
            ?>
      </table>
   </div>
</div>
   <?php
   if ($contabilita_installato == DB_BOOL_TRUE):
      //ORDINI CLIENTI                                              //
      if ($ordini):  
         $totale_ordini_interni = 0;
         ?>
         <br><br><br>
         <div class="row"> 
            <div class="col-sm-12">
               <table class="table table-striped">
                  <tr>
                     <td><strong>Descrizione</strong></td>
                     <td><strong>Tot</strong></td>
                  </tr>
                  <?php           
                     foreach ($ordini as $ordine):
                        $documento_contabilita = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_id' => $ordine['documenti_contabilita_commesse_documenti_contabilita_id']]);
                        if (!empty($documento_contabilita)):
                           $prezzo_vendita = $documento_contabilita['documenti_contabilita_competenze'];
                           $tipologia_ordine = $documento_contabilita['documenti_contabilita_tipo_value'];
                           $label_vendita = "<strong>" . $tipologia_ordine . " " . $documento_contabilita['documenti_contabilita_numero'] . " del " . dateFormat($documento_contabilita['documenti_contabilita_data_emissione']) . "</strong>";
                           $totale_ordini_interni += $prezzo_vendita;
                           ?>
                           <tr>
                              <td><?php echo $label_vendita; ?></td>
                              <td class="text-red"><strong><?php echo $default_currency . " " . $prezzo_vendita; ?></strong></td>
                           </tr>
                           <?php
                           if (isset($documento_contabilita)):
                              $i = 0;
                              $articoli_ordine_cliente = $this->apilib->search('documenti_contabilita_articoli', ['documenti_contabilita_articoli_documento' => $documento_contabilita['documenti_contabilita_id']]);
                              foreach ($articoli_ordine_cliente as $articolo_ordine):
                                 //debug($articolo_ordine,true);
                                 if ($articolo_ordine['documenti_contabilita_articoli_riga_desc'] == DB_BOOL_TRUE) {
                                    $articolo_ordine['documenti_contabilita_articoli_quantita'] = 1;
                                 }
                                 $prezzo_no_ivato = ($articolo_ordine['documenti_contabilita_articoli_importo_totale'] - $articolo_ordine['documenti_contabilita_articoli_iva']);
                                 $i++;
                                 ?>
                                 <tr>
                                    <td style="background:color:red;padding-left:50px;"><?php echo $i . ") "; ?> <?php echo number_format($articolo_ordine['documenti_contabilita_articoli_quantita'], 0, '.', '.') . " x " . $articolo_ordine['documenti_contabilita_articoli_name']; ?></strong></td>
                                    <td><?php echo $default_currency . " " . number_format($prezzo_no_ivato, 2, '.', '.'); ?></td>
                                 </tr>
                              <?php
                              endforeach;
                           endif;
                        endif;
                        ?>
                        <?php
                     endforeach;
                     ?>
                     <tr>
                           <td class="text-red"><strong>Totale ordini interni</strong></td>
                           <td class="text-red"><strong><?php echo $default_currency . " " . number_format($totale_ordini_interni, 2, '.', '.'); ?></strong></td>
                     </tr>
                  <?php
                     $total_balance = ($total_balance - $totale_ordini_interni);
                     $margin_balance = ($total_balance > 0) ? (100 * $total_balance) / $prezzo_vendita_totale : 0;
                     ?>
                  <tr>
                     <td><strong><?php e('Total'); ?></strong></td>
                     <td class="<?php echo ($total_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo $default_currency; ?><?php echo $total_balance; ?></strong></td>
                  </tr>
                  <tr>
                     <td><strong><?php e('Expected profit'); ?></strong></td>
                     <td class="<?php echo ($margin_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo number_format($margin_balance, 2, '.', '.'); ?>%</strong></td>
                  </tr>
               </table>
            </div>
         </div>
         <?php
         endif;
   endif;
   ?>
<br><br><br>
<div class="row">
   <div class="col-sm-12">
      <table class="table table-striped">
         <tr>
            <td><strong>Descrizione</strong></td>
            <td><strong>Ore lav</strong></td>
            <td><strong>Ore fatt</strong></td>
            <td><strong>€ fatt</strong></td>
            <td><strong>Costo interno</strong></td>
            <td><strong>Tot</strong></td>
         </tr>
         <?php
         if ($tickets_report) :
               //Tolgo il costo degli interventi
               $total_balance = $total_balance + ($euro_fatturate_tickets_report - $costo_interventi);
            ?>
            <tr>
               <td><strong>Interventi</strong></td>
               <td colspan="1" class="text-green"><strong><?php echo floor($hours) == $hours ? number_format($hours, 0, '.', ',') : number_format($hours, 2, '.', ','); ?> <?php $hours > 1 ? e('hours') : e('hour') ?></strong></td>
               <td class="text-green"><strong><?php echo $ore_lavorate_tickets_report; ?> <?php $ore_lavorate_tickets_report > 1 ? e('hours') : e('hour') ?></strong></td>
               <td class="text-green"><strong><?php echo $default_currency . " " . $euro_fatturate_tickets_report; ?></strong></td>
               <td class="text-red"><strong><?php echo $default_currency . " " . $costo_interventi; ?></strong></td>
               <td class="<?php echo (($euro_fatturate_tickets_report - $costo_interventi) > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo $default_currency . " " . ($euro_fatturate_tickets_report - $costo_interventi); ?></strong></td>
            </tr>
            <?php
            $i = 0;
            foreach ($tickets_reports as $ticket_report) :
               $i++;
               ?>
               <tr>
                  <td style="padding-left:50px;"><?php echo $i . ") "; ?>
                     <?php
                     echo $ticket_report['title'];
                     ?>
                  </td>
                  <td><?php echo floor($ticket_report['ore_lavorate']) == $ticket_report['ore_lavorate'] ? number_format($ticket_report['ore_lavorate'], 0, '.', ',') : number_format($ticket_report['ore_lavorate'], 2, '.', ','); ?> <?php $ticket_report['ore_lavorate'] > 1 ? e('hours') : e('hour') ?></td>
                  <td><?php echo floor($ticket_report['ore_fatturate']) == $ticket_report['ore_fatturate'] ? number_format($ticket_report['ore_fatturate'], 0, '.', '') : number_format($ticket_report['ore_fatturate'], 2, '.', ''); ?> <?php $ticket_report['ore_fatturate'] > 1 ? e('hours') : e('hour') ?></td>
                  <td><?php echo $default_currency; ?> <?php echo $ticket_report['euro_fatturati']; ?></td>
                  <td><?php echo $default_currency; ?> <?php echo $ticket_report['costo_interno']; ?></td>
                  <td><?php echo $default_currency; ?> <?php echo $ticket_report['totale']; ?></td>
               </tr>
               <?php
            endforeach;
         endif;
         ?>
         <!-- RAPPORTINI -->
         <?php
         if (!empty($rapportini_commessa)) :
               $totale_costo_rapportini = 0;
               foreach ($rapportini_commessa as $rapportino) :
                  $hours_lavorate = 0;
                  $utenti = $this->apilib->search('rel_rapportini_users', ['rapportini_id' => $rapportino['rapportini_id']]);
                  foreach ($utenti as $utente_report) {
                     $start_time = new DateTime($rapportino['rapportini_ora_inizio']);
                     $end_time = new DateTime($rapportino['rapportini_ora_fine']);
                     $diff = $end_time->diff($start_time);
                     $hours_lavorate += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
                     $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                     //trovo il costo orario 
                     if ($hr_installato == DB_BOOL_TRUE) {
                        //trovo il costo orario 
                        $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente_report['users_id']]);
                        $totale_costo_rapportini += $hours_lavorate * (isset($dipendente['dipendenti_costo_orario']) ? $dipendente['dipendenti_costo_orario'] : 5);
      
                     } else {
                        $costo = $utente['users_cost_per_hour'] > 0 ? $utente['users_cost_per_hour'] : 5;
                        $totale_costo_rapportini += $hours_lavorate * $costo;
                     }
                  }
               endforeach;
               //Tolgo il costo dei rapportini
               $total_balance = $total_balance - $totale_costo_rapportini;
            ?>
            <tr>
               <td><strong>Rapportini</strong></td>
               <td colspan="3" lass="text-green"><strong><?php echo floor($ore_rapportini) == $ore_rapportini ? number_format($ore_rapportini, 0, '.', ',') : number_format($ore_rapportini, 2, '.', ','); ?> <?php $ore_rapportini > 1 ? e('hours') : e('hour') ?></strong></td>
               <td class="text-red"><strong><?php echo $default_currency . " " . $totale_costo_rapportini; ?></strong></td>
               <td class="text-red"><strong><?php echo $default_currency . " " . $totale_costo_rapportini; ?></strong></td>
            </tr>
            <?php
            $i = 0;
            foreach ($rapportini_commessa as $rapportino) :
                $i++;
                $hours_lavorate = 0;
                $utenti = $this->apilib->search('rel_rapportini_users', ['rapportini_id' => $rapportino['rapportini_id']]);
               ?>
               <tr>
                  <td style="padding-left:50px;"><?php echo $i . ") "; ?>
                     <?php
                        echo "Rapportino #" . $rapportino['rapportini_id'] . " del " . dateFormat($rapportino['rapportini_data']) . " (";
                        ?>
                     <?php
                        $costo_intervento = 0;
                        $ciclo = 1;
                        foreach ($utenti as $utente_report) {
                        
                           $start_time = new DateTime($rapportino['rapportini_ora_inizio']);
                           $end_time = new DateTime($rapportino['rapportini_ora_fine']);
                           $diff = $end_time->diff($start_time);
                           $hours_lavorate += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
                        
                           $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                           //trovo il costo orario 
                           if ($hr_installato == DB_BOOL_TRUE) {
                              //trovo il costo orario 
                              $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente_report['users_id']]);
                              $costo_intervento += $hours_lavorate * (isset($dipendente['dipendenti_costo_orario']) ? $dipendente['dipendenti_costo_orario'] : 5);
                              } else {
                              $costo = $utente['users_cost_per_hour'] > 0 ? $utente['users_cost_per_hour'] : 5;
                              $costo_intervento += $hours_lavorate * $costo;
                              }
                        
                           //$dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente_report['users_id']]);
                           
                           //$costo_intervento += $hours_lavorate * $dipendente['dipendenti_costo_orario'];
                           echo $utente['users_last_name'] . " " . substr($utente['users_first_name'], 0, 1);
                           if ($ciclo < count($utenti)) {
                              echo " - ";
                           }
                           $ciclo++;
                        }
                        echo " ) "  ?></strong>
                  </td>
                  <td colspan="3" lass="text-green"><?php echo floor($hours_lavorate) == $hours_lavorate ? number_format($hours_lavorate, 0, '.', ',') : number_format($hours_lavorate, 2, '.', ','); ?> <?php $hours_lavorate > 1 ? e('hours') : e('hour') ?></td>
                  <td><?php echo $default_currency . " " . $costo_intervento; ?></td>
                  <td><?php echo $default_currency . " " . $costo_intervento; ?></td>
               </tr>
            <?php
            endforeach;
         endif;
         ?>
         <!-- FINE RAPPORTINI -->
         <!-- TIMESHEET -->
         <?php
            if ($worked_hours_detail) :
               ?>
               <tr>
                  <td><strong>Resoconto ore lavorate</strong></td>
                  <td colspan="3" class="text-red"><strong><?php echo floor($worked_hours) == $worked_hours ? number_format($worked_hours, 0, '.', ',') : number_format($worked_hours, 2, '.', ','); ?> <?php $worked_hours > 1 ? e('hours') : e('hour') ?></strong></td>
                  <td class="text-red"><strong><?php echo $default_currency; ?><?php echo floor($worked_hours_cost) == $worked_hours_cost ? number_format($worked_hours_cost, 0, '.', ',') : number_format($worked_hours_cost, 2, '.', ','); ?></strong></td>
                  <td class="text-red"><strong><?php echo $default_currency; ?><?php echo floor($worked_hours_cost) == $worked_hours_cost ? number_format($worked_hours_cost, 0, '.', ',') : number_format($worked_hours_cost, 2, '.', ','); ?></strong></td>
               </tr>
               <?php
                  $i = 0;
                  foreach ($worked_hours_detail as $ora_lavorata) :
                     $i++;
                     $utente = $this->apilib->searchFirst('users', ['users_id' => $ora_lavorata['timesheet_member']]);
                     $total_balance = $total_balance - $ora_lavorata['timesheet_total_cost'];
                     ?>
                     <tr>
                        <td style="padding-left:50px;"><?php echo $i . ") "; ?><?php echo $utente['users_last_name'] . " " . substr($utente['users_first_name'], 0, 1) . " - " . dateFormat($ora_lavorata['timesheet_start_time']); ?></strong></td>
                        <td colspan="3"><?php echo floor($ora_lavorata['timesheet_total_hours']) == $ora_lavorata['timesheet_total_hours'] ? number_format($ora_lavorata['timesheet_total_hours'], 0, '.', ',') : number_format($ora_lavorata['timesheet_total_hours'], 2, '.', ','); ?> <?php $ora_lavorata['timesheet_total_hours'] > 1 ? e('hours') : e('hour') ?></td>
                        <td><?php echo $default_currency; ?><?php echo floor($ora_lavorata['timesheet_total_cost']) == $ora_lavorata['timesheet_total_cost'] ? number_format($ora_lavorata['timesheet_total_cost'], 0, '.', ',') : number_format($ora_lavorata['timesheet_total_cost'], 2, '.', ','); ?></td>
                        <td><?php echo $default_currency; ?><?php echo floor($ora_lavorata['timesheet_total_cost']) == $ora_lavorata['timesheet_total_cost'] ? number_format($ora_lavorata['timesheet_total_cost'], 0, '.', ',') : number_format($ora_lavorata['timesheet_total_cost'], 2, '.', ','); ?></td>
                     </tr>
                     <?php
                  endforeach;
            endif;
            ?>
         <!-- FINE TIMESHEET -->
         <?php
         if ($expenses_detail) :
            ?>
            <tr>
               <td colspan="5"><strong><?php e('Expenses'); ?></strong></td>
               <td class="text-red"><strong><?php echo $default_currency . " " . number_format($expenses, 2, '.', '.'); ?></strong></td>
            </tr>
            <?php
               $i = 0;
               foreach ($expenses_detail as $spesa) :
                  $i++;
                  ?>
                  <tr>
                     <td colspan="5" style="padding-left:50px;"><?php echo $i . ") "; ?> <?php echo $spesa['expenses_description']; ?></strong></td>
                     <td><?php echo $default_currency . " " . number_format($spesa['expenses_amount'], 2, '.', '.'); ?></td>
                  </tr>
                  <?php
               endforeach;
               $total_balance = ($total_balance - $expenses);
         endif;
         ?>
         <?php
         $margin_balance = ($prezzo_vendita_totale > 0) ? (100 * $total_balance) / $prezzo_vendita_totale : 0;
         ?>
         <?php 
         if ($contabilita_installato == DB_BOOL_TRUE): ?>
            <tr>
               <td colspan="5"><strong><?php e('Total'); ?></strong></td>
               <td class="<?php echo ($total_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo $default_currency; ?><?php echo $total_balance; ?></strong></td>
            </tr>
            <tr>
               <td colspan="5"><strong><?php e('Expected profit'); ?></strong></td>
               <td class="<?php echo ($margin_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo number_format($margin_balance, 2, '.', '.'); ?>%</strong></td>
            </tr>
            <?php 
         endif; ?>
      </table>
   </div>
</div>
<br><br><br>
<?php
if ($tickets) :
   ?>
   <span class="center">
      <h3>Dettagli ticket</h3>
   </span>
   <div class="row">
      <div class="col-sm-12">
         <table class="table table-striped">
            <tr>
               <td><strong>Descrizione</strong></td>
               <td><strong>Ore lav</strong></td>
               <td><strong>Ore fatt</strong></td>
               <td><strong>€ fatt</strong></td>
               <td><strong>Costo interno</strong></td>
               <td><strong>Tot</strong></td>
            </tr>
            <tr>
               <td colspan="6"><strong><?php e('Resoconto tickets'); ?></strong></td>
            </tr>
            <?php
            $i = 0;
            foreach ($tickets as $ticket) :
               $i++;
               $utenti = $this->apilib->search('tickets_tecnici', ['tickets_id' => $ticket['tickets_id']]);
               ?>
               <tr>
                  <td colspan="6" style="padding-left:50px;"><?php echo $i . ") Ticket #" . $ticket['tickets_id'] . " del " . dateFormat($ticket['tickets_creation_date']); ?>
                     <?php
                        echo " - " . $ticket['tickets_subject']; ?>
                  </td>
               </tr>
               <?php
               $reports = $this->db
                  ->select('tickets_reports_id')
                  ->where('tickets_reports_ticket_id', $ticket['tickets_id'])
                  ->get('tickets_reports')
                  ->result_array();
           

               foreach ($reports as $ticket_report) :
                  if($tickets_reports[$ticket_report['tickets_reports_id']]):
                  ?>
                  <tr>
                     <td style="padding-left:100px;">
                     <?php
                     echo $tickets_reports[$ticket_report['tickets_reports_id']]['title'];
                     ?>
                     </td>
                     <td><?php echo floor($tickets_reports[$ticket_report['tickets_reports_id']]['ore_lavorate']) == $tickets_reports[$ticket_report['tickets_reports_id']]['ore_lavorate'] ? number_format($tickets_reports[$ticket_report['tickets_reports_id']]['ore_lavorate'], 0, '.', ',') : number_format($tickets_reports[$ticket_report['tickets_reports_id']]['ore_lavorate'], 2, '.', ','); ?> <?php $tickets_reports[$ticket_report['tickets_reports_id']]['ore_lavorate'] > 1 ? e('hours') : e('hour') ?></td>
                     <td><?php echo floor($tickets_reports[$ticket_report['tickets_reports_id']]['ore_fatturate']) == $tickets_reports[$ticket_report['tickets_reports_id']]['ore_fatturate'] ? number_format($tickets_reports[$ticket_report['tickets_reports_id']]['ore_fatturate'], 0, '.', '') : number_format($tickets_reports[$ticket_report['tickets_reports_id']]['ore_fatturate'], 2, '.', ''); ?> <?php $tickets_reports[$ticket_report['tickets_reports_id']]['ore_fatturate'] > 1 ? e('hours') : e('hour') ?></td>
                     <td><?php echo $default_currency; ?> <?php echo $tickets_reports[$ticket_report['tickets_reports_id']]['euro_fatturati']; ?></td>
                     <td><?php echo $default_currency; ?> <?php echo $tickets_reports[$ticket_report['tickets_reports_id']]['costo_interno']; ?></td>
                     <td><?php echo $default_currency; ?> <?php echo $tickets_reports[$ticket_report['tickets_reports_id']]['totale']; ?></td>
                  </tr>
                  <?php
                  endif;
               endforeach;
            endforeach;
            ?>
         </table>
      </div>
   </div>
   <?php
endif;
// SALDO ORE
//trovo eventuali pacchetti ore acquistati
if ($this->datab->module_installed('billable-hours')) {
      $billable = $this->apilib->search('billable_hours', ['billable_hours_project_id' => $value_id]);
      //calcolo il prezzo di vendita ed il costo interno per il totale
      $totale_ore_fatturate = 0;
      $totale_vendita_ore_fatturate = 0;
      $totale_ore_lavorate = 0;
      $prezzo_fatturato_ticket_report = 0;
      $array_billable = [];
      $conta_array = 0;
      $costo_interno_totale = 0;
      foreach ($billable as $billable_hour) :
         //vedo se sono carichi di ore
         $tipologia_ora = $this->apilib->searchFirst('billable_hours_type', ['billable_hours_type_id' => $billable_hour['billable_hours_type']]);
         if ($billable_hour['billable_hours_type'] == 1 || $billable_hour['billable_hours_type'] == 4){
            $totale_ore_fatturate += $billable_hour['billable_hours_sell_price'] * $billable_hour['billable_hours_hours'];
            $totale_vendita_ore_fatturate += $billable_hour['billable_hours_sell_price'] * $billable_hour['billable_hours_hours'];
            $totale_ore_lavorate += $billable_hour['billable_hours_hours'];
            $array_billable[$conta_array][] = $billable_hour['billable_hours_type'];
            $array_billable[$conta_array][] = $billable_hour['billable_hours_sell_price'];
            $array_billable[$conta_array][] = $billable_hour['billable_hours_hours'];
            $array_billable[$conta_array][] = $billable_hour['billable_hours_creation_date'];
         }else{
            if($billable_hour['billable_hours_report_id'] != null OR !empty($billable_hour['billable_hours_report_id'])){
               $utenti = $this->apilib->search('tickets_reports_tecnici', ['tickets_reports_id' => $billable_hour['billable_hours_report_id']]);
               //vedo quante ore era il report
               // se esiste il ticket collegato @todo
               $report = $this->apilib->searchFirst('tickets_reports', ['tickets_reports_id' => $billable_hour['billable_hours_report_id']]);
               $start_time = new DateTime($report['tickets_reports_start_time']);
               $end_time = new DateTime($report['tickets_reports_end_time']);
               $diff = $end_time->diff($start_time);
               $hours_lavorate = round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
               //calcolo per ogni utente
               $costo_interno = 0;
               $conta_dipendenti = 0;
               foreach ($utenti as $utente_report) {
                  $conta_dipendenti++;
                  $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);

                  if ($hr_installato == DB_BOOL_TRUE) {
                     //trovo il costo orario 
                     //trovo il costo orario se l'utente esiste @todo
                     $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente_report['users_id']]);
                     $costo_interno += $hours_lavorate * (isset($dipendente['dipendenti_costo_orario']) ? $dipendente['dipendenti_costo_orario'] : 5);
                     //calcolo prezzo fatturato

                     $prezzo_fatturato_ticket_report = $report['tickets_reports_billable_hours'] * (isset($dipendente['dipendenti_valore_orario']) ? $dipendente['dipendenti_valore_orario'] : 1);;
                     } else {
                     $costo_interno += $hours_lavorate * ($utente['users_cost_per_hour'] > 0 ? $utente['users_cost_per_hour'] : 5);
                     $prezzo_fatturato_ticket_report = $report['tickets_reports_billable_hours'] * 10;

                     }

               }
               //calcolo il totale delle ore fatturate
               $costo_interno_totale += $costo_interno;
               $totale_ore_lavorate += $billable_hour['billable_hours_hours'];
               $totale_ore_fatturate -= $costo_interno;
               $array_billable[$conta_array][] = $billable_hour['billable_hours_type'];
               $array_billable[$conta_array][] = $billable_hour['billable_hours_hours'];
               $array_billable[$conta_array][] = $prezzo_fatturato_ticket_report;
               $array_billable[$conta_array][] = $billable_hour['billable_hours_creation_date'];
               $array_billable[$conta_array][] = $costo_interno;
               $array_billable[$conta_array][] = $billable_hour['billable_hours_report_id'];
               $array_billable[$conta_array][] = $billable_hour['tickets_reports_date'];
               $array_billable[$conta_array][] = 'intervento';
            } else {
               $utenti = $this->apilib->search('tasks_members', ['tasks_id' => $billable_hour['billable_hours_task_id']]);
               $task = $this->apilib->searchFirst('tasks', ['tasks_id' => $billable_hour['billable_hours_task_id']]);
               //vedo quante ore era il report

               $hours_lavorate = $this->db->query("SELECT SUM(timesheet_total_hours) AS total FROM timesheet WHERE timesheet_task = ".$billable_hour['billable_hours_task_id']."")->row()->total;

               //calcolo per ogni utente
               $costo_interno = 0;
               $conta_dipendenti = 0;
               foreach ($utenti as $utente_report) {
                  $conta_dipendenti++;
                  $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                  //trovo il costo orario se l'utente esiste @todo
                  //calcolo prezzo fatturato

                  if ($hr_installato == DB_BOOL_TRUE) {
                     //trovo il costo orario 
                     $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente_report['users_id']]);
                     $costo_interno += $hours_lavorate * $dipendente['dipendenti_costo_orario'];
                     $prezzo_fatturato_ticket_report = $task['tasks_billable_hours'] * (isset($dipendente['dipendenti_valore_orario']) ? $dipendente['dipendenti_valore_orario'] : 1);;
                  } else {
                     $costo_interno += $hours_lavorate * ($utente['users_cost_per_hour'] > 0 ? $utente['users_cost_per_hour'] : 5);
                     $prezzo_fatturato_ticket_report = $report['tickets_reports_billable_hours'] * 10;

                     }

               }
               
               //calcolo il totale delle ore fatturate
               $costo_interno_totale += $costo_interno;
               $totale_ore_lavorate += $billable_hour['billable_hours_hours'];
               $totale_ore_fatturate -= $costo_interno;
               $array_billable[$conta_array][] = $billable_hour['billable_hours_type'];
               $array_billable[$conta_array][] = $billable_hour['billable_hours_hours'];
               $array_billable[$conta_array][] = $prezzo_fatturato_ticket_report;
               $array_billable[$conta_array][] = $billable_hour['billable_hours_creation_date'];
               $array_billable[$conta_array][] = $costo_interno;
               $array_billable[$conta_array][] = $billable_hour['billable_hours_task_id'];
               $array_billable[$conta_array][] = $billable_hour['tasks_done_date'];
               $array_billable[$conta_array][] = 'task';
            }
         }
         $conta_array++;
      endforeach;
} else {
      $totale_ore_fatturate = 0;
      $billable = [];
}
if ($totale_ore_fatturate) :
   ?>
   <span class="center">
      <h3>Dettagli ore da fatturare</h3>
   </span>
   <div class="row">
      <div class="col-sm-12">
         <table class="table table-striped">
            <tr>
               <td><strong>Descrizione</strong></td>
               <td><strong>Ore lav</strong></td>
               <td><strong>Prezzo di vendita orario</strong></td>
               <td><strong>Costo interno</strong></td>
               <td><strong>Tot</strong></td>
            </tr>
            <tr>
               <td colspan="5"><strong>Resoconto ore da fatturare</strong></td>
            </tr>
            <?php
               //debug($array_billable,true);
               $i = 1;
               foreach ($array_billable as $billable):
                  $tipologia_ore = $this->apilib->searchFirst('billable_hours_type', ['billable_hours_type_id' => $billable[0]]);
                  $tipologia_ore_value = $tipologia_ore['billable_hours_type_value'];
                  if($billable[0] == 1):
                     $prezzo_vendita_billable = number_format($billable[1] * $billable[2], 2, '.', '');
                     ?>
                     <tr>
                        <td colspan="1" style="padding-left:50px;"><?php echo $i++ . ") "; ?><?php echo $tipologia_ore_value . " ( caricate il " . dateFormat($billable[3]) . " )"; ?></strong></td>
                        <td colspan="1" class="text-green"><strong><?php echo floor($billable[2]) == $billable[2] ? number_format($billable[2], 0, '.', '') : number_format($billable[2], 2, '.', ''); ?> <?php $billable[2] > 1 ? e('hours') : e('hour') ?></strong></td>
                        <td colspan="2" class="text-green"><strong><?php echo $default_currency; ?> <?php echo floor($billable[1]) == $billable[1] ? number_format($billable[1], 0, '.', '') : number_format($billable[1], 2, '.', ''); ?></strong></td>
                        <td colspan="1" class="text-green"><strong><?php echo $default_currency; ?> <?php echo floor($billable[1] * $billable[2]) == $billable[1] * $billable[2] ? number_format($billable[1] * $billable[2], 0, '.', '') : number_format($billable[1] * $billable[2], 2, '.', ''); ?></strong></td>
                     </tr>
                     <?php                        
                  elseif($billable[0] == 4):
                     $prezzo_vendita_billable = number_format($billable[1] * $billable[2], 2, '.', '');
                     ?>
                     <tr>
                        <td colspan="1" style="padding-left:50px;"><?php echo $i++ . ") "; ?><?php echo $tipologia_ore_value . " ( caricate il " . dateFormat($billable[3]) . " )"; ?></strong></td>
                        <td colspan="4" class="text-green"><strong><?php echo floor($billable[2]) == $billable[2] ? number_format($billable[2], 0, '.', '') : number_format($billable[2], 2, '.', ''); ?> <?php $billable[2] > 1 ? e('hours') : e('hour') ?></strong></td>
                     </tr>
                     <?php    
                  else :
                     ?>
                     <tr>
                        <!-- ( intervento #" . $billable_hour['billable_hours_report_id'] . " del " . dateFormat($billable_hour['tickets_reports_date']) . " ) -->
                        <td colspan="1" style="padding-left:50px;"><?php echo $i++ . ") "; ?><?php echo $tipologia_ore_value . " ( ". $billable[7] ." #" . $billable[5] . " del " . dateFormat($billable[6]) . " )"; ?></strong></td>
                        <td colspan="2" class="text-red"><strong><?php echo floor($billable[1]) == $billable[1] ? number_format($billable[1], 0, '.', '') : number_format($billable[1], 2, '.', ''); ?> <?php $billable[1] < -1 ? e('hours') : e('hour') ?></strong></td>
                        <td colspan="1" class="text-red"><strong><?php echo $default_currency . " " . $billable[4]; ?></strong></td>
                        <td colspan="2" class="text-red"><strong><?php echo $default_currency . " " . $billable[4]; ?></strong></td>
                     </tr>
                     <?php                     
                  endif;
               endforeach;
               ?>
            <tr>
               <td colspan="1"><strong>Saldo attuale: </strong></td>
               <td colspan="1" class="<?php echo $totale_ore_lavorate > 1 ? 'text-green' : 'text-red' ?>"><strong><?php echo floor($totale_ore_lavorate) == $totale_ore_lavorate ? number_format($totale_ore_lavorate, 0, '.', '') : number_format($totale_ore_lavorate, 2, '.', ''); ?> <?php $totale_ore_lavorate > 1 ? e('hours') : e('hour') ?> </strong></td>
               <td colspan="1" class="text-green"><strong><?php echo $default_currency; ?> <?php echo floor($totale_vendita_ore_fatturate) == $totale_vendita_ore_fatturate ? number_format($totale_vendita_ore_fatturate, 0, '.', '') : number_format($totale_vendita_ore_fatturate, 2, '.', ''); ?></strong></td>
               <td colspan="1" class="text-red"><strong><?php echo $default_currency; ?> <?php echo floor($costo_interno_totale) == $costo_interno_totale ? number_format($costo_interno_totale, 0, '.', '') : number_format($costo_interno_totale, 2, '.', ''); ?></strong></td>
               <td colspan="1" class="<?php echo ($totale_ore_fatturate > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo $default_currency; ?><?php echo floor($totale_ore_fatturate) == $totale_ore_fatturate ? number_format($totale_ore_fatturate, 0, '.', '') : number_format($totale_ore_fatturate, 2, '.', ''); ?></strong></td>
            </tr>
         </table>
      </div>
   </div>
   <?php
endif;
?>