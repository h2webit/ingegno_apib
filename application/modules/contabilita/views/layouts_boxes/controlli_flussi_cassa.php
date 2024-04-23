<div class="row">
<?php
$campi_filtro = $this->db->where_in('fields_name', [
    'flussi_cassa_confermato', 
    'flussi_cassa_risorsa', 
    'flussi_cassa_data', 
    'flussi_cassa_metodo', 
    'flussi_cassa_tipo', 
    'flussi_cassa_saldato',
    'flussi_cassa_creation_date'
    ])->get('fields')->result_array();

foreach ($campi_filtro as $campo) {
    //Trick (pay attention to the double $$ sign...)
    //debug($campo);
    $field_name = $campo['fields_name'];
    $$field_name = $campo['fields_id'];
}


$filtro_movimenti = @$this->session->userdata(SESS_WHERE_DATA)['filtro_flussi_movimenti'];
//debug($flussi_cassa_data);
$where = $where_documenti = ['1=1'];

if (!empty($filtro_movimenti)) {
    foreach ($filtro_movimenti as $field => $filtro) {
        $value = $filtro['value'];
        if ($value == -1) {
            continue;
        }
        $field_id = $filtro['field_id'];
        switch ($field_id) {
            case $flussi_cassa_data: //Data
                $data_expl = explode(' - ', $value);
                $data_da = $data_expl[0];
                $data_a = @$data_expl[1];
                $data_a_mysql = implode('-', array_reverse(explode('/', $data_a)));
                //debug($data_a_mysql);
                $where[] = "date(flussi_cassa_data) <= '$data_a_mysql'";
                
                break;
            case $flussi_cassa_risorsa: //Risorsa

                $where[] = "flussi_cassa_risorsa = '$value'";

                break;
            case $flussi_cassa_tipo: //Tipo

                $where[] = "flussi_cassa_tipo = '$value'";

                break;
            case $flussi_cassa_metodo: //Metodo

                $where[] = "flussi_cassa_metodo = '$value'";

                break;

            case $flussi_cassa_confermato: //Contatto

                $where[] = "flussi_cassa_confermato = '$value'";

                break;
            case $flussi_cassa_creation_date: //Tipo


                break;
            default:
                debug("Campo filtro '{$field}' non gestito");
                debug($this->db->where('fields_id', $filtro['field_id'])->get('fields')->row_array());
                debug($filtro);
                break;
        }
    }
}
$where_flussi_cassa_orfani = $where;
$where_flussi_cassa_orfani[] = "flussi_cassa_id NOT IN (SELECT flussi_cassa_id FROM flussi_cassa_scadenze_collegate WHERE documenti_contabilita_scadenze_id IS NOT NULL AND documenti_contabilita_scadenze_id IN (SELECT documenti_contabilita_scadenze_id FROM documenti_contabilita_scadenze))";
$where_flussi_cassa_orfani[] = "flussi_cassa_id NOT IN (SELECT flussi_cassa_id FROM flussi_cassa_spese_scadenze_collegate WHERE spese_scadenze_id IS NOT NULL AND spese_scadenze_id IN (SELECT spese_scadenze_id FROM spese_scadenze))";

$where_str = implode(' AND ', $where_flussi_cassa_orfani);
$flussi_orfani = $this->apilib->search('flussi_cassa', $where_str);
if ($flussi_orfani) : ?>


    <div class="col-md-4 " >
        <div class="callout callout-warning">
           <span>Hai <strong><?php echo count($flussi_orfani); ?></strong> flussi cassa orfani, ovvero non collegati a una scadenza di una spesa o di una fattura.
        </span>

        <small style="width: 100%;display: block;text-align: center;">
            
   
        </small>
        <a class=" js_open_modal" href="<?php echo base_url(); ?>get_ajax/modal_layout/flussi_cassa_orfani/<?php echo $value_id; ?>?_size=large">Clicca qui</a> per visualizzarli
</div>
        </div>
    
<?php endif; ?>
<?php

$where_scadenze_saldate_orfane = $where_documenti;
$primo_flusso_registrato = $this->apilib->searchFirst('flussi_cassa', [], 0, 'flussi_cassa_data');
$where_scadenze_saldate_orfane[] = "documenti_contabilita_scadenze_saldata = 1 AND documenti_contabilita_scadenze_id NOT IN (SELECT documenti_contabilita_scadenze_id FROM flussi_cassa_scadenze_collegate)";
$where_scadenze_saldate_orfane[] = "documenti_contabilita_scadenze_documento NOT IN (SELECT rel_doc_contabilita_rif_documenti_padre FROM rel_doc_contabilita_rif_documenti WHERE rel_doc_contabilita_rif_documenti_id IN (SELECT documenti_contabilita_id FROM documenti_contabilita WHERE documenti_contabilita_tipo = 3))";
$where_scadenze_saldate_orfane[] = "documenti_contabilita_tipo = 1";
$where_scadenze_saldate_orfane[] = "documenti_contabilita_scadenze_saldato_con <> 22";
if ($primo_flusso_registrato) {
    $where_scadenze_saldate_orfane[] = "documenti_contabilita_data_emissione > '{$primo_flusso_registrato['flussi_cassa_data']}'";
}

$where_str = implode(' AND ', $where_scadenze_saldate_orfane);
//debug($where_str, true);
$scadenze_orfane = $this->apilib->search('documenti_contabilita_scadenze', $where_str);
if ($scadenze_orfane) : ?>


    <div class="col-md-4  " >
        <div class="callout callout-danger">
           <span>Hai <strong><?php echo count($scadenze_orfane); ?></strong> scadenze marcate come saldate, non presenti nei flussi cassa.
        </span>

        <!-- <small style="width: 100%;display: block;text-align: center;">
        <ul>    
        <?php foreach ($scadenze_orfane as $scadenza) : ?>
            <li>Fattura n° <?php echo $scadenza['documenti_contabilita_numero']; ?>/<?php echo $scadenza['documenti_contabilita_serie']; ?> del <?php echo dateFormat($scadenza['documenti_contabilita_data_emissione']); ?> di <?php echo json_decode($scadenza['documenti_contabilita_destinatario'], true)['ragione_sociale']; ?></li>
                <?php endforeach; ?>
        </ul>
        </small> -->

         <a class=" js_open_modal" href="<?php echo base_url(); ?>get_ajax/modal_layout/flussi_scadenze_saldate_orfane/<?php echo $value_id; ?>?_size=large">Clicca
                qui</a> per visualizzarle
        
            </div>
        </div>
        
<?php endif; ?>
</div>