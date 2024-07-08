<?php

if ($this->auth->get('clienti_id')) { //Questo funziona perchè è in right join. Firegui non permette un doppio right join sulla stessa tabella quindi l'amministrativo devo prenderlo nell'esle manualmente...
    $cliente_id = $this->auth->get('clienti_id');
    $sola_lettura = false;
} elseif ($this->auth->get('sedi_operative_id')) {
    $sede = $this->apilib->view('sedi_operative', $this->auth->get('sedi_operative_id'));
    $cliente = $this->apilib->view('clienti', $sede['sedi_operative_cliente']);
    $cliente_id = $cliente['clienti_id'];
    $sola_lettura = false;
} else {
    $cliente = $this->apilib->searchFirst('clienti', ['clienti_utente_amministrativo' => $this->auth->get('utenti_id')]);
    if ($cliente && $this->auth->get('utenti_tipo') == '15') {
        $sola_lettura = true;
        $cliente_id = $cliente['clienti_id'];
    } elseif ($this->auth->get('utenti_tipo') == '17') { //Utente associato - responsabile sedi
        //Se è questo tipo di utente speciale, prendo le sedi a lui associate
        $sedi = $this->apilib->search('sedi_operative', ["sedi_operative_id IN (SELECT responsabili_sedi_sede FROM responsabili_sedi WHERE responsabili_sedi_associato = '{$this->auth->get('associati_id')}')"]);
    } else {
        return;
    }
}

if (!empty($sede)) { //Se è un utente di tipo sede operativa, vede solo la sua sede
    $sedi = [$sede];
} elseif (!empty($sedi)) {
} elseif (!empty($cliente_id)) {
    $sedi = $this->apilib->search('sedi_operative', [
        'sedi_operative_cliente' => $cliente_id,
        "(sedi_operative_nascosta = '" . DB_BOOL_FALSE . "' OR sedi_operative_nascosta is null)"
    ]);
} else {
    return;
}

//debug($sedi, true);

$layoutEntityData = [];
$grid = $this->datab->get_grid(91);
$grid_layout = $grid['grids']['grids_layout'] ?: DEFAULT_LAYOUT_GRID;

$year = ($this->input->get('Y')) ? $this->input->get('Y') : date('Y');
$month = ($this->input->get('m')) ? $this->input->get('m') : date('m');

?>
<div class="portlet-body tabs ">
    <div class="tabs_157 tabbable-custom">
        <ul class="nav nav-tabs">
            <?php foreach ($sedi as $key =>  $sede) : ?>
                <li class="<?php if ($key == 0) : ?>active<?php endif; ?>">
                    <a href="#box-<?php echo $sede['sedi_operative_id']; ?>" sede_id="<?php echo $sede['sedi_operative_id']; ?>" data-toggle="tab"><?php echo $sede['sedi_operative_reparto']; ?></a>
                </li>
            <?php endforeach; ?>

        </ul>
        <div class="tab-content">
            <?php foreach ($sedi as $key => $sede) : ?>
                <div class="tab-pane<?php if ($key == 0) : ?> active<?php endif; ?>" id="box-<?php echo $sede['sedi_operative_id']; ?>">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="table-scrollable table-scrollable-borderless js-loaded" container_sede_id="<?php echo $sede['sedi_operative_id']; ?>">
                                <!-- QUESTO CONTENUTO VIENE CARICATO IN AJAX -->
                            </div>
                        </div>

                    </div>
                    <div class="row">
                        <div class="col-md-2">
                            <?php
                            $grid_data['data'] = $this->datab->get_grid_data($grid, empty($layoutEntityData) ? $sede['sedi_operative_id'] : ['value_id' => $sede['sedi_operative_id'], 'additional_data' => $layoutEntityData]);
                            echo $this->load->view("pages/layouts/grids/{$grid_layout}", array('grid' => $grid, 'sub_grid' => null, 'grid_data' => $grid_data, 'value_id' => $sede['sedi_operative_id'], 'layout_data_detail' => $layoutEntityData), true);
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>


        </div>
    </div>
</div>
<script>
    function refreshCalendario(sede) {
        loading(true);
        var url = base_url + 'custom/apib/get_calendario_sede/' + sede + '?Y=<?php echo $year; ?>&m=<?php echo $month; ?>';
        $.ajax({
            dataType: "json",
            url: url,
            success: function(data) {
                var sede = data.sede;
                var html = data.html;

                var container = $('div[container_sede_id=' + sede + ']');
                container.html(html);


                try {
                    $('.js_multiselect:not(.select2-offscreen):not(.select2-container)', container).each(function() {
                        var that = $(this);
                        var minInput = that.data('minimum-input-length');

                        that.select2({
                            allowClear: true,
                            minimumInputLength: minInput ? minInput : 0
                        });
                    });
                    $('.select2me', container).select2({
                        allowClear: true
                    });
                } catch (e) {}
                loading(false);
            }
        });
    }
    $(document).ready(function() {
        //        $('.js-loaded').each(function () {
        //            var sede = $(this).attr('sede_id');
        //            refreshCalendario(sede);
        //        });


        $('.nav-tabs li a').on('click', function() {
            //alert(1);
            var sede = $(this).attr('sede_id');
            refreshCalendario(sede);
        });

        $('.nav-tabs li.active a').trigger('click');
    });

    console.log('modalità calendario: <?php echo $this->auth->get('sedi_operative_modalita_calendario'); ?>');
</script>