<?php

$anno = (!empty($this->input->get('anno'))) ? $this->input->get('anno') : date('Y');

$curr_year = date('Y');
$years = range($curr_year - 7, $curr_year + 3);
$serie_escape = array('PN', 'UD', 'FE', 'RE');

function calcola_variazione($sum_prev, $sum)
{

  if (!empty($sum_prev)) {

    $calcolo = (($sum - $sum_prev) / $sum_prev) * 100;


    $signal = ($calcolo > 0) ? 'text-green' : 'text-red';
    $signal_char = ($calcolo > 0) ? '▴' : '▾';
    return '<span class="' . $signal . '">' . $signal_char . number_format(abs($calcolo), 0, '.', ',') . '%</span>';
  } else {
    return false;
  }
}

function fill_holes($array)
{
  $filled_array = [];
  $orig_pointer = 0;
  for ($i = 1; $i <= 12; $i++) {
    if (!empty($array[$orig_pointer]['idMonth']) && $array[$orig_pointer]['idMonth'] == $i) {
      $filled_array[] = $array[$orig_pointer];
      $orig_pointer++;
    } else {
      $filled_array[] = [
        'idMonth' => $i,
        'month' => mese_testuale($i),
        'total' => 0
      ];
    }
  }

  return $filled_array;
}
?>
<style>
  .text-right {
    text-align: right !important;
  }

  .first-col {
    text-align: left !important;
  }

  .table_budget_targets td {
    text-align: right !important;
  }

  .table_budget_targets td.first-col {
    text-align: left !important;
    font-weight: bold;
  }
</style>
<div class="col-md-12" style="background-color:#ffffff">


  <?php

  $fatturatoMensile = $this->conteggi->getFatturatoAnnoMensile($anno);
  $fatturatoMensilePrec = $this->conteggi->getFatturatoAnnoMensile($anno - 1);
  
  // debug($fatturatoMensilePrec,true);
  $speseMensile = $this->conteggi->getSpeseAnnoMensile($anno);
  $speseCategorie = $this->db->query("SELECT * FROM spese LEFT JOIN spese_categorie ON spese_categorie_id = spese_categoria WHERE DATE_FORMAT(spese_data_emissione, '%Y') = '$anno' GROUP BY spese_categoria")->result_array();

  ?>

  <table class=" table text-center table-striped table_budget_targets">
    <thead>
      <tr>
        <td></td>
        <?php for ($i = 1; $i <= 12; $i++): ?>
          <td><b>
              <?php echo $i; ?>
            </b></td>
        <?php endfor; ?>
        <td class="text-right"><b>Totali</b></td>
        <td class="text-right"><b>Media/mese</b></td>
      </tr>
    </thead>
    <tbody>

      <td colspan="14"><b></b></td>


      <tr>
        <td class="first-col">Fatturato</td>
        <?php $sum = 0;
        $sum_prec = 0;
        ?>
        <?php

        // Fix riempie i mesi mancanti
        while (count($fatturatoMensile) < 12) {
          $fatturatoMensile[] = [
            'imponibile' => 0
          ];
        }

        foreach ($fatturatoMensile as $key => $fatturato): ?>
          <?php //$y_invoices[$year][$invoice['idMonth']] = $invoice['total'];         ?>
          <td>
            €
            <?php echo number_format($fatturato['imponibile'], 0, ',', '.'); ?>
            <?php echo @calcola_variazione($fatturatoMensilePrec[$key]['imponibile'], $fatturato['imponibile']); ?>
          </td>
          <?php $sum += $fatturato['imponibile'];
          $sum_prec += $fatturatoMensilePrec[$key]['imponibile'];
          ?>
        <?php endforeach; ?>

        <td class="text-right"><b>€
            <?php echo number_format($sum, 0, ',', '.'); ?>
          </b>
          <?php echo calcola_variazione($sum_prec, $sum); ?>
        </td>
        <td class="text-right">
          €
          <?php $media = $sum / 12;
          echo number_format($media, 0, ',', '.'); ?>

        </td>
      </tr>

      <tr>
        <td colspan="14"><b></b></td>
      </tr>


      <tr>
        <td class="first-col">Spese</td>
        <?php $sum = 0; ?>
        <?php foreach ($speseMensile as $spese): ?>
          <?php //$y_invoices[$year][$invoice['idMonth']] = $invoice['total'];         ?>
          <td>
            €
            <?php echo number_format($spese['imponibile'], 0, ',', '.'); ?>
            <?php //echo @calcola_variazione($y_invoices, $anno, $fatturato['imponibile'], $invoice['idMonth']);         ?>
          </td>
          <?php $sum += $spese['imponibile']; ?>
        <?php endforeach; ?>

        <td class="text-right"><b>€
            <?php echo number_format($sum, 0, ',', '.'); ?>
          </b>
          <?php //echo calcola_variazione($invoices_sum, $year, $sum);         ?>
        </td>
        <td class="text-right">
          €
          <?php $media = $sum / 12;
          echo number_format($media, 0, ',', '.'); ?>

        </td>
      </tr>


      <?php foreach ($speseCategorie as $categoria): ?>
        <?php if (empty($categoria['spese_categoria']))
          continue; ?>
        <tr>
          <td class="first-col" style="font-weight:normal">
            -
            <?php echo $categoria['spese_categorie_nome']; ?>
          </td>
          <?php $sum = 0; ?>
          <?php $speseCategoriaMensile = $this->conteggi->getSpeseAnnoMensile($anno, $categoria['spese_categoria']); ?>
          <?php foreach ($speseCategoriaMensile as $spese): ?>
            <?php //$y_invoices[$year][$invoice['idMonth']] = $invoice['total'];         ?>
            <td>
              €
              <?php echo number_format($spese['imponibile'], 0, ',', '.'); ?>
              <?php //echo @calcola_variazione($y_invoices, $anno, $fatturato['imponibile'], $invoice['idMonth']);         ?>
            </td>
            <?php $sum += $spese['imponibile']; ?>
          <?php endforeach; ?>

          <td class="text-right"><b>€
              <?php echo number_format($sum, 0, ',', '.'); ?>
            </b>
            <?php //echo calcola_variazione($invoices_sum, $year, $sum);         ?>
          </td>

          <td class="text-right">
            €
            <?php $media = $sum / 12;
            echo number_format($media, 0, ',', '.'); ?>

          </td>
        </tr>


      <?php endforeach; ?>





    </tbody>

  </table>


</div>