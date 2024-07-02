<?php
$this->load->model('contabilita/prima_nota');
$conteggi = $this->input->get('nascondi_conteggi') != 1;
$completo = 1;
$nascondi_orfani = 1;
$nascondi_zero = $this->input->get('nascondi_zero') == 1;



$piano_dei_conti = $this->prima_nota->getPianoDeiConti($this->input->get('completo') == 1, $this->input->get('nascondi_orfani') == 1, $conteggi);

$filters = $this->session->userdata(SESS_WHERE_DATA);

// Costruisco uno specchietto di filtri autogenerati leggibile
$filtri = array();

if (!empty($filters["filter_stampe_contabili"])) {
    foreach ($filters["filter_stampe_contabili"] as $field) {
        if ($field['value'] == '-1' || $field['value'] == '') {
            continue;
        }
        $filter_field = $this->datab->get_field($field["field_id"], true);
        // debug($filter_field);

        // Se ha una entità/support collegata
        if ($filter_field['fields_ref']) {

            $entity_data = $this->crmentity->getEntityPreview($filter_field['fields_ref']);
            
            $filtri[] = array("label" => $filter_field["fields_draw_label"], "value" => $entity_data[$field['value']]);
        } else {
            $filtri[] = array("label" => $filter_field["fields_draw_label"], "value" => $field['value']);
        }
    }
}
foreach ($piano_dei_conti as $key => $dati_piano_conti) {
    if (empty($dati_piano_conti['mastri'])) {
        unset($piano_dei_conti[$key]);

    }
}
?>
<style>
    .piano_dei_conti .btn {}

    .pl-15 {
        padding-left: 15px;
    }


    .mastro_container h5 {
        padding: 5px;
        border-radius: 3px;
    }

    .mastro_container h5 .mastro_actions {
        margin-left: 15px;
        visibility: hidden;
        opacity: 0;
        transition: visibility .25s linear, opacity .25s linear;
    }

    .mastro_container:hover h5 .mastro_actions {
        visibility: visible;
        opacity: 1;
    }

    .mastro_totale {
        font-size: 18px;
    }

    tr .conto_actions {
        margin-left: 15px;
        visibility: hidden;
        opacity: 0;
        transition: visibility .25s linear, opacity .25s linear;
    }

    tr:hover .conto_actions {
        visibility: visible;
        opacity: 1;
    }

    .wk_header_filter ul {
        margin: 0;
        padding: 0;
        list-style-type: none;
    }

    .wk_header_filter ul li {

        margin-right: 20px;
        display: inline;
        float: left;
    }
    .euro_red {
        color:#F00;
    }
    .new_page {
            page-break-after: always;
            page-break-inside: always;
            page-break-before: always;
        }
</style>


<div style="margin-bottom:30px;padding-top:30px;">
    <p><strong>
            Documento generato il
        </strong>:
        <?php echo dateFormat(date('Y-m-d H:i:s'),'d/m/Y H:i:s'); ?>
    </p>
    <?php foreach ($filtri as $filtro): ?>
        <p><strong><?php echo $filtro['label']; ?></strong>: <?php echo implode(',', (array) $filtro['value']); ?></p>
    <?php endforeach; ?>

</div>


<div class="container-fluid box box-danger">

    
        <?php foreach ($piano_dei_conti as $key => $mastro_tipo) : ?>
            <div class="row">
            <?php $piano_dei_conti[$key]['totale'] = 0; ?>
            <div class="new_page piano_dei_conti col-md-12" style="">
                <div class="box-header">
                    <h3 class="box-title">
                        <?php echo $mastro_tipo['documenti_contabilita_mastri_tipo_value']; ?>

                        
                    </h3>
                </div>

                <div class="box-body">
                    <?php foreach ($mastro_tipo['mastri'] as $mastro) : ?>
                        <div class="mastro_container" id="mastro_<?php echo $mastro['documenti_contabilita_mastri_id']; ?>">
                            <h5 class="bg-primary">
                                <strong class="text-uppercase"><?php echo $mastro['documenti_contabilita_mastri_codice']; ?> <?php echo $mastro['documenti_contabilita_mastri_descrizione']; ?></strong>
                                
                            </h5>

                            <div class="table-responsive">
                                <table class="table table-striped table-hover table-condensed">
                                    <thead style="position:relative;">
                                        <tr>
                                            <th>Descrizione</th>
                                            <?php if ($conteggi) : ?>
                                                <th class="text-center">Dare</th>
                                                <th class="text-center">Avere</th>
                                                <th class="text-center">Saldo</th>
                                            <?php endif; ?>
                                           
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($mastro['conti'] as $conto) : ?>
                                            <?php if ($this->input->get('nascondi_zero') == 1 && $conteggi && $conto['totale'] == 0) {
                                                continue;
                                            } ?>
                                            <tr style="opacity: 1; color:#14518d;">
                                                <td>
                                                    <span class="clickable">
                                                        &nbsp;<b><?php echo $conto['documenti_contabilita_conti_codice_completo']; ?></b> <?php echo $conto['documenti_contabilita_conti_descrizione']; ?>
                                                    </span>
                                                    
                                                </td>
                                                <?php if ($conteggi) : ?>
                                                    <td class="text-right">
                                                        <?php //debug($conto,true); ?>
                                                        <?php //e_money($conto['dare']); ?> 
                                                    </td>
                                                    <td class="text-right">
                                                        
                                                                        <?php //e_money($conto['avere']); ?> 
                                                                    </td>
                                                                    <td class="text-right<?php if ($conto['totale'] < 0): ?> euro_red<?php endif; ?>">
                                                        
                                                                                        <?php //e_money($conto['totale']); ?> 
                                                                                    </td>
                                                <?php endif; ?>
                                                

                                            </tr>
                                            <?php foreach ($conto['sottoconti'] as $sottoconto) : ?>

                                                <?php if ($this->input->get('nascondi_zero') == 1 && $conteggi && $sottoconto['totale'] == 0) {
                                                    continue;
                                                } ?>

                                                <tr>
                                                    <td style="padding-left: 30px;opacity: 1;">
                                                        <span class="clickable">
                                                            &nbsp;<b><?php echo $sottoconto['documenti_contabilita_sottoconti_codice_completo']; ?></b> <?php echo $sottoconto['documenti_contabilita_sottoconti_descrizione']; ?>
                                                        </span>
                                                    </td>
                                                    <?php if ($conteggi) : ?>
                                                        <td class="text-right">
                                                            
                                                            <?php e_money($sottoconto['dare']); ?> €
                                                        </td>
                                                        <td class="text-right">
                                                            
                                                                                <?php e_money($sottoconto['avere']); ?> €
                                                                            </td>
                                                                            <td class="text-right<?php if ($sottoconto['totale'] < 0) : ?> euro_red<?php endif; ?>">
                                                            
                                                                                                    <?php e_money($sottoconto['totale']); ?> €
                                                                                                </td>
                                                    <?php endif; ?>
                                                    

                                                </tr>
                                            <?php endforeach; ?>
                                            <?php if ($conteggi) : ?>
                                            <tr>
                                                <td style="padding-left: 30px;opacity: 1;font-weight:bold;color:#14518d;">
                                                    <span class="clickable">
                                                        &nbsp;<b>Totale <?php echo $conto['documenti_contabilita_conti_descrizione']; ?></b>
                                                    </span>
                                                </td>
                                                <td class="text-right"  style="font-weight:bold;">
                                                    <?php //debug($conto,true);  ?>
                                                    <?php e_money($conto['dare']); ?> €
                                                </td>
                                                <td class="text-right"  style="font-weight:bold;">
                                                
                                                    <?php e_money($conto['avere']); ?> €
                                                </td>
                                                <td class="text-right<?php if ($conto['totale'] < 0): ?> euro_red<?php endif; ?>" style="font-weight:bold;">
                                                
                                                    <?php e_money($conto['totale']); ?> €
                                                </td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td colspan="4" style="padding-left: 0px;opacity: 1;">
                                                    <span class="clickable">
                                                        &nbsp;<b>&nbsp;</b>
                                                    </span>
                                                </td>
                                                </tr>
                                        <?php endforeach; ?>

                                    </tbody>

                                    <tfoot>
                                        <tr>
                                            
                                            <th class="text-right text-uppercase">Totali (<?php echo $mastro['documenti_contabilita_mastri_descrizione']; ?>):</th>
                                            <?php if ($conteggi) : ?>
                                                <th class="text-right"><?php e_money($mastro['dare']); ?> €</th>
                                                <th class="text-right"><?php e_money($mastro['avere']); ?> €
                                                            </th>
                                                            <th class="text-right<?php if ($mastro['totale'] < 0): ?> euro_red<?php endif; ?>"><?php e_money($mastro['totale']); ?> €
                                                                        </th>
                                            <?php endif; ?>
                                        </tr>

                                                                <?php
                                                                $piano_dei_conti[$key]['totale'] += $mastro['totale'];
                                                                ?>

                                    </tfoot>
                                </table>

                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>

               
            </div>

            <div>
                <span class="text-right text-uppercase" style="font-size: 2em;  text-align: right;  display: block;  margin-right: 20px;  margin-top: 10px;">
                TOTALE (<?php echo $mastro_tipo['documenti_contabilita_mastri_tipo_value']; ?>): <?php e_money(abs($piano_dei_conti[$key]['totale'])); ?> €
                                                            </span>
            </div>
                                                                
        </div>
        <div class="new_page"></div>
                                                                <?php endforeach; ?>


                                                                <div style="margin-top:50px;">
                <span class="text-right text-uppercase" style="font-size: 1em;  text-align: right;  display: block;  margin-right: 20px;  margin-top: 10px;">
                TOTALE PATRIMONIALE(<?php echo $piano_dei_conti[0]['documenti_contabilita_mastri_tipo_value']; ?>):
                                                                        <?php e_money(abs($piano_dei_conti[0]['totale'])); ?> €
                                                                    </span>
                                                                    <span class="text-right text-uppercase" style="font-size: 1em;  text-align: right;  display: block;  margin-right: 20px;  margin-top: 10px;">
                TOTALE PATRIMONIALE(<?php echo $piano_dei_conti[1]['documenti_contabilita_mastri_tipo_value']; ?>):
                                                                        <?php e_money(abs($piano_dei_conti[1]['totale'])); ?> €
                                                                    </span>
                                                                    <hr />
                                                                    <span class="text-right text-uppercase" style="font-size: 2em;  text-align: right;  display: block;  margin-right: 20px;  margin-top: 10px;">
                DIFFERENZA:
                                                                        <?php e_money(abs($piano_dei_conti[0]['totale']) - abs($piano_dei_conti[1]['totale'])); ?> €
                                                                    </span>
                                                                </div>
    
</div>
