<?php
$pagamenti_cache = $this->apilib->search('pagamenti_cache');
$associati = $this->apilib->search('associati', ['associati_non_attivo' => DB_BOOL_FALSE], 0, null, 'associati_cognome,associati_nome');
$associati_non_attivi = $this->apilib->search('associati', ['associati_non_attivo' => DB_BOOL_TRUE], 0, null, 'associati_cognome,associati_nome');

?>

<?php if (empty($pagamenti_cache)) : ?>
    <div class="portlet-body form">
        <form id="generazione_pagamenti" role="form" method="post" action="<?php echo base_url("custom/pagamenti/generaPagamenti"); ?>" class="formAjax" enctype="multipart/form-data">

            <!-- FORM HIDDEN DATA -->

            <div class="form-body">
                <div class="row">
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="control-label">Anno</label>

                            <select class="form-control select2me" name="pagamenti_anno">
                                <?php for ($i = date('Y'); $i >= date('Y') - 10; $i--) : ?>
                                    <option value="<?php echo $i; ?>" <?php if ($i == date('Y')) : ?> selected="selected" <?php endif; ?>><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="form-group">
                            <label class="control-label">Mese</label>

                            <select class="form-control select2me" name="pagamenti_mese">
                                <option value="1">Gennaio</option>
                                <option value="2">Febbraio</option>
                                <option value="3">Marzo</option>
                                <option value="4">Aprile</option>
                                <option value="5">Maggio</option>
                                <option value="6">Giugno</option>
                                <option value="7">Luglio</option>
                                <option value="8">Agosto</option>
                                <option value="9">Settembre</option>
                                <option value="10">Ottobre</option>
                                <option value="11">Novembre</option>
                                <option value="12">Dicembre</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="control-label">Associato</label>

                            <select class="form-control select2me" name="pagamenti_associato" data-ref="pagamenti_associato">
                                <option value="">Tutti</option>
                                <?php foreach ($associati as $associato) : ?>
                                    <option value="<?php echo $associato['associati_id']; ?>"><?php echo $associato['associati_cognome']; ?> <?php echo $associato['associati_nome']; ?></option>
                                <?php endforeach; ?>
                                <option value=""> </option>
                                <option value=""> </option>
                                <option value="">---NON ATTIVI--- </option>
                                <option value=""> </option>
                                <option value=""> </option>
                                <?php foreach ($associati_non_attivi as $associato) : ?>
                                    <option value="<?php echo $associato['associati_id']; ?>"><?php echo $associato['associati_cognome']; ?> <?php echo $associato['associati_nome']; ?></option>
                                <?php endforeach; ?>

                            </select>
                        </div>
                    </div>
                    <div class="col-lg-12">
                        <div class="form-group">
                            <label class="control-label">Comportamento</label>

                            <select class="form-control select2me" name="comportamento" data-ref="comportamento">
                                <option value="1">Genera solo nuovi pagamenti</option>
                                <option value="2">Elimina e rigenera mese (solo i non pagati)</option>
                                <option value="3">Elimina e rigenera (tutti, anche i pagati)</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div id="msg_generazione_pagamenti" class="alert alert-danger hide"></div>
                    </div>
                </div>
            </div>

            <div class="form-actions right">

                <button type="submit" class="btn green">Genera</button>
            </div>
        </form>
    </div>
<?php else : ?>
    <div class="portlet-body">
        <div class="row">
            <div class="col-lg-7">
                Generazione pagamenti in corso...
            </div>
            <div class="col-lg-5" style="font-size:3em;text-align: center;">
                <span class="js-remaining-counter"><?php echo count($pagamenti_cache); ?></span><br />
                rimanenti
            </div>
        </div>
    </div>
    <script>
        function doGenera() {
            var url = base_url + "custom/pagamenti/doGenera/";
            $.ajax(url, {
                dataType: 'json',
                method: 'post',
                success: function(output) {
                    if (output.remaining > 0) {
                        $('#grid_123').DataTable().ajax.reload();
                        $('.js-remaining-counter').html(output.remaining);
                        //setTimeout("doGenera()", 1000);
                        doGenera();
                    } else {
                        location.reload();
                    }
                }

            });
        }
        $(document).ready(function() {
            doGenera();
        });
    </script>
<?php endif; ?>