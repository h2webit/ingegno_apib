<?php
$this->load->model('contabilita/prima_nota');
$conteggi = $this->input->get('nascondi_conteggi') != 1;
$completo = 1;
$nascondi_orfani = 1;
$piano_dei_conti = $this->prima_nota->getPianoDeiConti($this->input->get('completo') == 1, $this->input->get('nascondi_orfani') == 1, $conteggi);


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
</style>


<div class="container-fluid box box-danger">

    <div class="row">
        <?php foreach ($piano_dei_conti as $mastro_tipo) : ?>

            <div class="piano_dei_conti col-md-12">
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
                                    <thead>
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
                                                    <span class="clickable" id="movimenti-55">
                                                        &nbsp;<b><?php echo $conto['documenti_contabilita_conti_codice_completo']; ?></b> <?php echo $conto['documenti_contabilita_conti_descrizione']; ?>
                                                    </span>
                                                    
                                                </td>
                                                <?php if ($conteggi) : ?>
                                                    <td class="text-right">
                                                        <?php //debug($conto,true); ?>
                                                        <?php e_money($conto['dare']); ?> €
                                                    </td>
                                                    <td class="text-right">
                                                        
                                                                        <?php e_money($conto['avere']); ?> €
                                                                    </td>
                                                                    <td class="text-right<?php if ($conto['totale'] < 0): ?> euro_red<?php endif; ?>">
                                                        
                                                                                        <?php e_money($conto['totale']); ?> €
                                                                                    </td>
                                                <?php endif; ?>
                                                

                                            </tr>
                                            <?php foreach ($conto['sottoconti'] as $sottoconto) : ?>

                                                <?php if ($this->input->get('nascondi_zero') == 1 && $conteggi && $sottoconto['totale'] == 0) {
                                                    continue;
                                                } ?>

                                                <tr>
                                                    <td style="padding-left: 30px;opacity: 0.75;">
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
                                        <?php endforeach; ?>

                                    </tbody>

                                    <tfoot>
                                        <tr>
                                            
                                            <th class="text-right text-uppercase">Totali:</th>
                                            <?php if ($conteggi) : ?>
                                                <th class="text-right"><?php e_money($mastro['dare']); ?> €</th>
                                                <th class="text-right"><?php e_money($mastro['avere']); ?> €
                                                            </th>
                                                            <th class="text-right<?php if ($mastro['totale'] < 0): ?> euro_red<?php endif; ?>"><?php e_money($mastro['totale']); ?> €
                                                                        </th>
                                            <?php endif; ?>
                                        </tr>
                                    </tfoot>
                                </table>

                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>

               
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function PrintElem(elem) {
        var mywindow = window.open('', 'PRINT', 'height=400,width=600');

        mywindow.document.write('<html><head><title>' + document.title + '</title>');
        mywindow.document.write('</head><body >');
        mywindow.document.write('<h1>' + document.title + '</h1>');
        mywindow.document.write(document.getElementById(elem).innerHTML);
        mywindow.document.write('</body></html>');

        mywindow.document.close(); // necessary for IE >= 10
        mywindow.focus(); // necessary for IE >= 10*/

        mywindow.print();
        mywindow.close();

        return true;
    }

    $(() => {
        $('.js_checkbox_conteggi,.js_checkbox_clienti_fornitori,.js_checkbox_nasconti_conti_senza_registrazioni,.js_checkbox_nascondi_importi_a_zero').on('click', function() {
            var nascondi_conteggi = (!$('.js_checkbox_conteggi').is(':checked') ? '0' : '1');
            var completo = (!$('.js_checkbox_clienti_fornitori').is(':checked') ? '0' : '1');
            var nascondi_orfani = (!$('.js_checkbox_nasconti_conti_senza_registrazioni').is(':checked') ? '0' : '1');
            var nascondi_zero = (!$('.js_checkbox_nascondi_importi_a_zero').is(':checked') ? '0' : '1');
            var redirect = base_url + 'main/layout/piano-dei-conti?completo=' + completo + '&nascondi_conteggi=' + nascondi_conteggi + '&nascondi_orfani=' + nascondi_orfani + '&nascondi_zero=' + nascondi_zero;
            location.href = redirect;
        });


    });
</script>