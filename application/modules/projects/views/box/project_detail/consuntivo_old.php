<?php
//@TODO = impostare un valore nei settings come valore "orario da fatturare"
$valore_orario_fatt = 40;
$project = $this->apilib->view('projects', $value_id);
$default_currency = $this->db->where('currencies_default', DB_BOOL_TRUE)->get('currencies')->row()->currencies_symbol;
$hr_installato = $this->datab->module_installed('modulo-hr');
$contabilita_installato = $this->datab->module_installed('contabilita');
?>
<?php $this->layout->addModuleStylesheet('projects', 'css/counters.css'); ?>
  <div class="row">
    <div class="col-sm-12">
      <table class="table">
          <?php
          $prezzo_vendita_totale = 0;
          if ($contabilita_installato == DB_BOOL_TRUE):
            $ordini = $this->apilib->search('documenti_contabilita_commesse', ['documenti_contabilita_commesse_projects_id' => $value_id, 'documenti_contabilita_tipo <>' => 14]);
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
              foreach ($ordini as $ordine){
                $documento_contabilita = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_id' => $ordine['documenti_contabilita_commesse_documenti_contabilita_id']]);
                if(!empty($documento_contabilita)){
                  //$prezzo_vendita = $documento_contabilita['documenti_contabilita_totale'];
                  $prezzo_vendita = $documento_contabilita['documenti_contabilita_competenze'];
                  $tipologia_ordine = $documento_contabilita['documenti_contabilita_tipo_value'];
                  $label_vendita = $tipologia_ordine." " .$documento_contabilita['documenti_contabilita_numero'] . " del " . dateFormat($documento_contabilita['documenti_contabilita_data_emissione']);
                  $prezzo_vendita_totale += $prezzo_vendita;
                  ?>
                  <tr>
                    <td><?php echo $label_vendita; ?></td>
                    <td class="text-green"><?php echo $default_currency . " " . $prezzo_vendita; ?></td>
                  </tr>
                  <?php
                }
                
              }
              ?>
              <tr>
                  <td class="text-green"><strong>Totale ordini cliente</strong></td>
                  <td class="text-green"><strong><?php echo $default_currency . " " . number_format($prezzo_vendita_totale, 2 ,'.',''); ?></strong></td>
              </tr>
              <?php
            }
          endif;
          ?>
          <?php
          if ($this->datab->module_installed('billable-hours')) {
            $prezzo_medio = $this->db->query("SELECT AVG(billable_hours_sell_price) AS prezzo_medio FROM billable_hours WHERE billable_hours_type = 1 && billable_hours_project_id = '{$value_id}'")->row()->prezzo_medio;
          } else {
            $prezzo_medio = 0;
          }

          $expenses = $this->db->query("SELECT SUM(CASE  WHEN expenses_type = 1 THEN expenses_amount ELSE expenses_amount*(-1) END) as s FROM expenses WHERE expenses_project_id = '{$value_id}'")->row()->s;
          //aggiungo gli interventi
          if ($this->datab->module_installed('tickets-report')) {
            $tickets_report = $this->db->query("SELECT SUM(tickets_reports_billable_hours) as s FROM tickets_reports WHERE tickets_reports_project_id = '{$value_id}'")->row()->s;
            $tickets_report_detail = $this->db->query("SELECT * FROM tickets_reports WHERE tickets_reports_project_id = '{$value_id}' ORDER BY tickets_reports_id")->result_array();
            //calcolo il costo medio di vendita orario
            //calcolo costo totale interventi
            $costo_interventi = 0;
            $hours = 0;
            $ore_lavorate_tickets_report = 0;
            $euro_fatturate_tickets_report = 0;
            foreach ($tickets_report_detail as $ticket_report) :
                //calcolo ore lavorate
                $start_time = new DateTime($ticket_report['tickets_reports_start_time']);
                $end_time = new DateTime($ticket_report['tickets_reports_end_time']);
                $diff = $end_time->diff($start_time);
                $hours += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
                $utenti = $this->apilib->search('tickets_reports_tecnici', ['tickets_reports_id' => $ticket_report['tickets_reports_id']]);
                $conta_utenti = 0;
                
                foreach ($utenti as $utente_report) {
                  $conta_utenti++;
                  $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                  if ($hr_installato == DB_BOOL_TRUE) {
                    //trovo il costo orario 
                    $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente['users_id']]);
                    $costo_interventi += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2) * (isset($dipendente['dipendenti_costo_orario']) ? $dipendente['dipendenti_costo_orario'] : 1);
                  } else {
                    $costo = $utente['users_cost_per_hour'] > 0 ? $utente['users_cost_per_hour'] : 5;
                    $costo_interventi += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2) * $costo;
                  }
                }

                /*foreach ($utenti as $utente_report) {
                    $conta_utenti++;
                    $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                    //trovo il costo orario 
                    $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente['users_id']]);
                    $costo_interventi += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2) * $dipendente['dipendenti_costo_orario'];
                }*/
                $hours = $hours * $conta_utenti;
                $ore_lavorate_tickets_report += $ticket_report['tickets_reports_billable_hours'];
                $euro_fatturate_tickets_report += $ticket_report['tickets_reports_billable_hours'] * $prezzo_medio;
            endforeach;
          } else {
              $tickets_report = 0;
              $hours = 0;
              $tickets_report_detail = [];
              $costo_interventi = 0;
          }
            /*
            $tickets_report = $this->db->query("SELECT SUM(tickets_reports_billable_hours) as s FROM tickets_reports WHERE tickets_reports_project_id = '{$value_id}'")->row()->s;
            $ore_fatturate = $tickets_report*$valore_orario_fatt;
            $tickets_report_detail = $this->db->query("SELECT * FROM tickets_reports WHERE tickets_reports_project_id = '{$value_id}' ORDER BY tickets_reports_id")->result_array();
            //calcolo costo totale interventi
            $costo_interventi = 0;
            $hours = 0;
            $ore_fatturate_tickets_report = 0;
            foreach ($tickets_report_detail as $ticket_report) :
              $utenti = $this->apilib->search('tickets_reports_tecnici', ['tickets_reports_id' => $ticket_report['tickets_reports_id']]);
              foreach ($utenti as $utente_report) {
                  $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                  //trovo il costo orario 
                  $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente['users_id']]);
                  $costo_interventi += $ticket_report['tickets_reports_billable_hours'] * $dipendente['dipendenti_costo_orario'];
                  //calcolo ore lavorate
                  $start_time = new DateTime($ticket_report['tickets_reports_start_time']);
                  $end_time = new DateTime($ticket_report['tickets_reports_end_time']);
                  $diff = $end_time->diff($start_time);
                  $hours += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
                  $ore_fatturate_tickets_report += $ticket_report['tickets_reports_billable_hours'] *$dipendente['dipendenti_valore_orario'];
              }
            endforeach;                            
          } else {
              $tickets_report = 0;
              $hours = 0;
              $tickets_report_detail = [];
              $costo_interventi = 0;
          }*/
          //Timesheet

          if ($this->datab->module_installed('timesheet')) {
            $worked_hours = $this->db->query("SELECT SUM(timesheet_total_hours) as s FROM timesheet WHERE timesheet_project = '{$value_id}'")->row()->s;
            $worked_hours_cost = $this->db->query("SELECT SUM(timesheet_total_cost) as s FROM timesheet WHERE timesheet_project = '{$value_id}'")->row()->s;
          } else {
            $worked_hours = 0;
            $worked_hours_cost = 0;
          }
          //ordini fornitore
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
              $prezzo_no_ivato = ($articoli_dettagli['documenti_contabilita_articoli_importo_totale']-$articoli_dettagli['documenti_contabilita_articoli_iva']);
              $articoli_ordinati_valore += $prezzo_no_ivato;
              $articoli_ordinati++;
          }
          $total_balance = $prezzo_vendita_totale - $expenses - $worked_hours_cost;
          //ordini fornitore
          if (!empty($ordini_fornitori)) :
              $totale_ordini = 0;
              foreach($ordini_fornitori as $ordine_fornitore):
                  $documento = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_id' => $ordine_fornitore]);
                  $totale_ordini += $documento['documenti_contabilita_competenze'];
              ?>
                  <tr>
                      <td><?php echo $documento['documenti_contabilita_tipo_value']. " " .$documento['documenti_contabilita_numero']; ?> del <?php echo dateFormat($documento['documenti_contabilita_data_emissione']); ?></></td>

                      <td class="text-red"><?php echo $default_currency . " " . $documento['documenti_contabilita_competenze']; ?></td>
                  </tr>
              <?php
              endforeach;
              ?>
              <tr>
                  <td class="text-red"><strong>Totale ordini fornitori</strong></td>
                  <td class="text-red"><strong><?php echo $default_currency . " " . number_format($totale_ordini, 2 ,'.',''); ?></strong></td>
              </tr>
              <?php
              $total_balance = $total_balance - $totale_ordini;
              $margin_balance = ($prezzo_vendita_totale - $totale_ordini > 0) ? (100 * ($prezzo_vendita_totale - $totale_ordini)) / $prezzo_vendita_totale : 0;

              ?>
              <tr>
                  <td><strong>Totale parziale</strong></td>
                  <td class="<?php echo (($prezzo_vendita_totale - $totale_ordini) > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo $default_currency . " " . number_format($prezzo_vendita_totale - $totale_ordini, 2 ,'.',''); ?></strong></td>
              </tr>
              <tr>
                  <td><strong><?php e('Expected profit'); ?></strong></td>
                  <td class="<?php echo ($margin_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo number_format($margin_balance, 2, '.', ''); ?>%</strong></td>
              </tr>
              <?php
          endif;
          //ORDINI INTERNI
          if ($contabilita_installato == DB_BOOL_TRUE):
            $ordini = $this->apilib->search('documenti_contabilita_commesse', ['documenti_contabilita_commesse_projects_id' => $value_id, 'documenti_contabilita_tipo' => 14]);
            if ($ordini):  
                $prezzo_ordini_interni = 0;
                $totale_ordini_interni = 0;
                foreach ($ordini as $ordine){
                  $documento_contabilita = $this->apilib->searchFirst('documenti_contabilita', ['documenti_contabilita_id' => $ordine['documenti_contabilita_commesse_documenti_contabilita_id']]);
                  if(!empty($documento_contabilita)){
                    //$prezzo_vendita = $documento_contabilita['documenti_contabilita_totale'];
                    $prezzo_vendita = $documento_contabilita['documenti_contabilita_competenze'];
                    $tipologia_ordine = $documento_contabilita['documenti_contabilita_tipo_value'];
                    $label_vendita = $tipologia_ordine." " .$documento_contabilita['documenti_contabilita_numero'] . " del " . dateFormat($documento_contabilita['documenti_contabilita_data_emissione']);
                    $prezzo_ordini_interni = $prezzo_vendita;
                    $totale_ordini_interni += $prezzo_vendita;
                    ?>
                    <tr>
                      <td><?php echo $label_vendita; ?></td>
                      <td class="text-red"><?php echo $default_currency . " " . $prezzo_vendita; ?></td>
                    </tr>
                    <?php
                  }
                  
                }
                $total_balance = $total_balance - $totale_ordini_interni;
                ?>
                <tr>
                    <td class="text-red"><strong>Totale ordini interni</strong></td>
                    <td class="text-red"><strong><?php echo $default_currency . " " . number_format($prezzo_ordini_interni, 2 ,'.',''); ?></strong></td>
                </tr>
                <?php
            endif;
          endif;
          //calcolo ore rapportini
          if ($this->datab->module_installed('rapportini')) {
            //calcolo ore lavorate
            $ore_rapportini = 0;
            $rapportini_commessa = $this->apilib->search('rapportini', ['rapportini_commessa' => $value_id]);
            $totale_costo_rapportini = 0;
            foreach ($rapportini_commessa as $rapportino) {
                $hours_lavorate = 0;
                $start_time = new DateTime($rapportino['rapportini_ora_inizio']);
                $end_time = new DateTime($rapportino['rapportini_ora_fine']);
                $diff = $end_time->diff($start_time);
                $ore_rapportini += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
                $ore_lavoro_rapportino = round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
                $utenti = $this->apilib->search('rel_rapportini_users', ['rapportini_id' => $rapportino['rapportini_id']]);
                foreach ($utenti as $utente_report) {
                    $utente = $this->apilib->searchFirst('users', ['users_id' => $utente_report['users_id']]);
                    //trovo il costo orario 
                    //$dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente_report['users_id']]);
                    /*$start_time = new DateTime($rapportino['rapportini_ora_inizio']);
                    $end_time = new DateTime($rapportino['rapportini_ora_fine']);
                    $diff = $end_time->diff($start_time);*/
                    //$hours_lavorate += round(($diff->s / 3600) + ($diff->i / 60) + $diff->h + ($diff->days * 24), 2);
                    //se hr installato uso il costo su dipendenti altrimenti tengo conto del costo su user (se non c'è mette 5 di default)
                    if ($hr_installato == DB_BOOL_TRUE) {
                      //trovo il costo orario 
                      $dipendente = $this->apilib->searchFirst('dipendenti', ['dipendenti_user_id' => $utente_report['users_id']]);
                      $totale_costo_rapportini += $ore_lavoro_rapportino * (isset($dipendente['dipendenti_costo_orario']) ? $dipendente['dipendenti_costo_orario'] : 1);
                    } else {
                      $costo = $utente['users_cost_per_hour'] > 0 ? $utente['users_cost_per_hour'] : 5;
                      $totale_costo_rapportini += $ore_lavoro_rapportino * $costo;
                    }
                }
            }   
            $total_balance = $total_balance - $totale_costo_rapportini;        
          }


        ?>
        <!-- TICKETS REPORT -->
        <?php
        if ($tickets_report) :
          ?>
              <tr>
                  <td>Totale interventi ( <?php echo number_format($tickets_report, 2, '.', '.'); ?> <?php $tickets_report > 1 ? e('hours') : e('hour') ?> )</td>
                  <td class="text-green"><?php echo $default_currency . " " . number_format($euro_fatturate_tickets_report - $costo_interventi, 2, '.', ''); ?></td>
              </tr>
          <?php
          $total_balance = $total_balance + ($euro_fatturate_tickets_report - $costo_interventi);

        endif;
        ?>
        <!-- RAPPORTINI -->
        <?php
        if (!empty($rapportini_commessa)) :
          ?>
              <tr>
                  <td>Totale rapportini ( <?php echo number_format($ore_rapportini, 2, '.', '.'); ?> <?php $ore_rapportini > 1 ? e('hours') : e('hour') ?> )</td>
                  <td class="text-red"><?php echo $default_currency . " " . number_format($totale_costo_rapportini, 2, '.', ''); ?> </td>
              </tr>
          <?php
        endif;
        if ( $expenses > 0 ) :
        ?>
          <tr>
            <td><?php e('Expenses'); ?></td>
            <td class="text-red"><?php echo $default_currency . " " . number_format($expenses, 2, '.', ''); ?></td>
          </tr>
        <?php
          endif;
        ?>
        <tr>
          <td>Totale Timesheets ( <?php echo number_format($worked_hours, 2, '.', '.'); ?> <?php e('hours'); ?> )</td>

          <td class="text-red"><?php echo $default_currency . " " . number_format($worked_hours_cost, 2, '.', ''); ?></td>
        </tr>
        <?php if ($contabilita_installato == DB_BOOL_TRUE): ?>
          <tr>
            <td><strong><?php e('Total'); ?></strong></td>

            <td class="<?php echo ($total_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo $default_currency . " " . number_format($total_balance, 2, '.', ''); ?></strong></td>
          </tr>
          <?php
          $margin_balance = ($prezzo_vendita_totale > 0) ? (100 * $total_balance) / $prezzo_vendita_totale : 0;
          ?>
          <tr>
            <td><strong><?php e('Expected profit'); ?></strong></td>

            <td class="<?php echo ($margin_balance > 0) ? 'text-green' : 'text-red' ?>"><strong><?php echo number_format($margin_balance, 2, '.', ''); ?>%</strong></td>
          </tr>
        <?php endif; ?>
      </table>
    </div>
  </div>