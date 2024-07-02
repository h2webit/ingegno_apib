<?php

// Check moduli altrimenti non può funzionare il nuovo consuntivo

if (!$this->datab->module_installed('timesheet') || !$this->datab->module_installed('contabilita') || $this->datab->module_installed('payments')) {
  echo "Moduli richiesti non presenti. Contattare l'assistenza.";
  return false;
}


$this->layout->addModuleStylesheet('projects', 'css/counters.css');

$project = $this->apilib->view('projects', $value_id);

// Get INGENGO Settings
$ingegno_settings = $this->db->query("SELECT * FROM ingegno_settings")->result_array();
$ingegno_settings = array_key_value_map($ingegno_settings, 'ingegno_settings_key', 'ingegno_settings_value');


$this->load->model('projects/projects');

$worked_hours = $this->projects->get_project_worked_hours($value_id);
$ordini = $this->projects->get_project_orders($value_id);
$spese = $this->projects->get_project_expenses($value_id);
$mol = $this->projects->get_project_mol($value_id);
$payments = $this->projects->get_project_total_payments($value_id);

?>
<div class="row">
  <div class="col-sm-12">


    <table class="table">
      <tr>
        <th></th>
        <th>Ordinato</th>
        <th>Fatturato</th>
        <th>Pagamenti</th>
      </tr>

      <!-- Ordini cliente -->
      <tr>
        <td>Ordini cliente (<?php echo $ordini['count_ordini_cliente']; ?> ordini)</td>
        <td class="text-green">€ <?php e_money($ordini['ordini_cliente']); ?></td>
        <td></td>
        <td></td>
      </tr>

      <!-- Fatturato -->

      <tr>
        <td>Fatture emesse (<?php echo $ordini['count_fatturato']; ?> fatture)</td>
        <td></td>
        <td class="text-green">€ <?php e_money($ordini['fatturato']); ?></td>
        <td></td>
      </tr>

      <!-- Pagamenti -->
      <tr>
        <td>Pagamenti inseriti (0 pagamenti)</td>
        <td></td>
        <td></td>
        <td class="text-green">€ <?php e_money($payments['all']); ?></td>
      </tr>

      <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
      </tr>

      <tr>
        <td class="text-bold">Totale Timesheets (<?php e_money($worked_hours['worked_hours']); ?> ore)
        </td>

        <td class="text-red">
          € <?php e_money($worked_hours['worked_hours_cost']); ?></td>
        <td class="text-red">
          € <?php e_money($worked_hours['worked_hours_cost']); ?></td>
        <td class="text-red">
          € <?php e_money($worked_hours['worked_hours_cost']); ?></td>
      </tr>

      <tr>
        <td class="text-bold">Totale Ordini fornitore
        </td>

        <td class="text-red">
          <?php e_money($ordini['ordini_fornitore']); ?>
        </td>
        <td class="text-red">
          <?php e_money($ordini['ordini_fornitore']); ?>
        </td>
        <td class="text-red">
          <?php e_money($ordini['ordini_fornitore']); ?>
        </td>
      </tr>

      <tr>
        <td class="text-bold">Totale spese extra</td>

        <td class="text-red"><?php e_money($spese); ?></td>
        <td class="text-red"><?php e_money($spese); ?></td>
        <td class="text-red"><?php e_money($spese); ?></td>
      </tr>


      <tr>
        <td class="text-bold">Totale</td>
        <td></td>
        <td></td>
        <td></td>
      </tr>

      <tr>
        <td class="text-bold">Margine lordo</td>
        <td class="<?php echo ($mol['total_ordinato'] > 0) ? 'text-green' : 'text-red'; ?>">€
          <?php e_money($mol['total_ordinato']); ?>
        </td>
        <td class="<?php echo ($mol['total_fatturato'] > 0) ? 'text-green' : 'text-red'; ?>">€
          <?php e_money($mol['total_fatturato']); ?>
        </td>
        <td></td>
      </tr>

      <tr>
        <td>% MOL</td>
        <td class="<?php echo ($mol['ordinato_percentage'] > 0) ? 'text-green' : 'text-red'; ?>">
          <?php e_money($mol['ordinato_percentage']); ?>%
        </td>
        <td class="<?php echo ($mol['fatturato_percentage'] > 0) ? 'text-green' : 'text-red'; ?>">
          <?php e_money($mol['fatturato_percentage']); ?>%
        </td>
        <td></td>
      </tr>


    </table>


  </div>
</div>