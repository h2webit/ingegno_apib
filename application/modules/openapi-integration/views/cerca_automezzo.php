<div class="box-body layout_box form">

    <div class="box-body">
        <div class="row sortableForm">

            <div class="formColumn js_container_field col-md-12">
                <p style="margin-bottom:20px">Con questo servizio puoi censire un nuovo automezzo cercandone la targa</p>
                <div class="form-group">
                    <label class="control-label">Inserisci la targa:</label>
                    <input type="text" id="js_cerca_targa_input" name="cerca_targa" class="form-control" placeholder="" maxlength="11">
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div id="msg_form_cerca_automezzo_form" class=""></div>
            </div>
        </div>
    </div>

    <div class="form-actions" style="text-align: center">
        <button type="button" id="js_cerca_targa" class="btn btn-primary">Cerca € 0,80</button>
        <button type="button" id="js_crea_automezzo" class="btn btn-success" style="display:none">Crea nuovo automezzo</button>
    </div>


    <script>
    var ricerca_data;

    $('body').on('click', '#js_cerca_targa', function(e) {
        e.stopImmediatePropagation();

        var targa = $('#js_cerca_targa_input').val();

        request(base_url + 'openapi-integration/main/cerca_automezzo', {
            "cerca_targa": targa,
            [token_name]: token_hash
        }, 'POST', false, false, {}).then(data => {

            if (!data.success) {
                toast('Errore', 'error', data.message, 'toastr', false);
                $('#msg_form_cerca_automezzo_form').html("Errore: " + data.message);
                $('#js_crea_automezzo').hide();
                return;
            }

            if (data.success) {
                ricerca_data = data.data;

                $('#msg_form_cerca_automezzo_form').html("");

                $('#msg_form_cerca_automezzo_form').append('<strong>Marca:</strong> ' + data
                    .data.CarMake);
                $('#msg_form_cerca_automezzo_form').append('<br /><strong>Modello:</strong> ' + data
                    .data.CarModel);
                $('#msg_form_cerca_automezzo_form').append('<br /><strong>Versione:</strong> ' +
                    data.data.Version);
                $('#msg_form_cerca_automezzo_form').append('<br /><strong>Targa:</strong> ' + data
                    .data.LicensePlate);
                $('#msg_form_cerca_automezzo_form').append('<br /><strong>Anno immatricolazione:</strong> ' +
                    data.data.RegistrationYear);
                $('#msg_form_cerca_automezzo_form').append('<br /><strong>Alimentazione:</strong> ' +
                    data.data.FuelType);
                $('#msg_form_cerca_automezzo_form').append('<br /><strong>Cavalli:</strong> ' +
                    data.data.PowerCV);
                $('#msg_form_cerca_automezzo_form').append('<br /><strong>Kw:</strong> ' +
                    data.data.PowerKW);

                $('#js_crea_automezzo').show();
            }
        }).catch(error => {
            console.log(error);
        });
    })

    // Crea automezzo
    $('body').on('click', '#js_crea_automezzo', function(e) {
        e.stopImmediatePropagation();

        $('#msg_form_cerca_automezzo_form').append('<br /><br />Attendere ...');

        request(base_url + 'openapi-integration/main/inserisci_automezzo', {
            data: JSON.stringify(ricerca_data),
            [token_name]: token_hash
        }, 'POST', false, false, {}).then(data => {
            if (!data.success) {
                $('#msg_form_cerca_automezzo_form').html("Errore: " + data.message);
                return;
            } else {
                // Redirect to automezzo detail
                window.location.href = data.message;
            }
        }).catch(error => {
            console.log(error);
        });
    });
    </script>
</div>