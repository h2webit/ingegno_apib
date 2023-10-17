<div class="box-body layout_box form">

    <div class="box-body">
        <div class="row sortableForm">

            <div class="formColumn js_container_field col-md-12">
             <p style="margin-bottom:20px">Con questo servizio puoi creare l'anagrafica di un nuovo cliente o fornitore, comodamente cercando la p.iva oppure il codice fiscale.</p>
                <div class="form-group"><label class="control-label">Inserisci la P.IVA o il CF:</label><input
                        type="text" id="js_cerca_piva_input" name="piva_cf" class="form-control" placeholder=""
                        maxlength="11">
                </div>
            </div>
        </div>
        <div class="row ">
            <div class="col-md-12">
                <div id="msg_form_cercapivaform" class=""></div>
            </div>
        </div>
    </div>

    <div class="form-actions" style="text-align: center">
        <button type="button" id="js_cerca_piva" class="btn btn-primary">Cerca € 0,08</button>
        <button type="button" id="js_crea_anagrafica" class="btn btn-success" style="display:none">Crea nuova anagrafica</button>
    </div>


    <script>
        var ricerca_data;

        $('body').on('click', '#js_cerca_piva', function (e) {
            e.stopImmediatePropagation();

            var piva = $('#js_cerca_piva_input').val();
            $.ajax({
                method: 'post',
                async: true,
                url: base_url + "openapi-integration/main/cerca_cliente_piva",
                dataType: "json",
                data: {
                    piva_cf: piva,
                    [token_name]: token_hash
                },
                success: function (res) {

                    if (res.success == false) {
                        $('#msg_form_cercapivaform').html("Errore: " + res.message);
                        $('#js_crea_anagrafica').hide();
                    } else {
                        ricerca_data = res.data;

                        $('#msg_form_cercapivaform').html("");
                        $('#msg_form_cercapivaform').append('<strong>Denominazione:</strong> ' + res
                            .data.denominazione);
                        $('#msg_form_cercapivaform').append('<br /><strong>Comune:</strong> ' + res
                            .data.comune);
                        $('#msg_form_cercapivaform').append('<br /><strong>Indirizzo:</strong> ' +
                            res.data.indirizzo);
                        $('#msg_form_cercapivaform').append('<br /><strong>CAP:</strong> ' +
                            res.data.cap);
                        $('#msg_form_cercapivaform').append('<br /><strong>Provincia:</strong> ' +
                            res.data.provincia);
                        $('#msg_form_cercapivaform').append('<br /><strong>P.IVA:</strong> ' +
                            res.data.piva);
                        $('#msg_form_cercapivaform').append('<br /><strong>CF:</strong> ' +
                            res.data.cf);
                        $('#msg_form_cercapivaform').append(
                            '<br /><strong>Codice destinatario:</strong> ' +
                            res.data.codice_destinatario);
                        $('#msg_form_cercapivaform').append(
                            '<br /><strong>Stato attività:</strong> ' +
                            res.data.stato_attivita);

                        $('#js_crea_anagrafica').show();
                    }
                }
            });
        })

        // Crea anagrafica
        $('body').on('click', '#js_crea_anagrafica', function (e) {
            e.stopImmediatePropagation();

            $('#msg_form_cercapivaform').append('<br /><br />Attendere ...');

            $.ajax({
                method: 'post',
                async: true,
                url: base_url + "openapi-integration/main/crea_anagrafica",
                dataType: "json",
                data: {
                    data: ricerca_data,
                    [token_name]: token_hash
                },
                success: function (res) {
                    console.log(res);
                    if (res.success == false) {
                        $('#msg_form_cercapivaform').html("Errore: " + res.message);
                    } else {
                        // Redirect to customer detail
                        window.location.href = res.message;
                    }
                }
            });
        });
    </script>
</div>