<?php
$automezzo_id = $this->input->get('automezzo_id');
$automezzo = $this->apilib->view('automezzi', $automezzo_id);
?>

<div class="box-body layout_box form">

    <div class="box-body">
        <div class="row sortableForm">
            <div class="formColumn js_container_field col-md-12">
                <p style="margin-bottom:20px" class="text-center">Con questo servizio puoi aggiornare i dati dell'automezzo selezionato</p>
            </div>

            <div class="formColumn js_container_field col-md-12 text-center js_acquista_box">
                <button type="button" id="js_acquista_dati" class="btn btn-primary">
                    <i class="fas fa-eur"></i>
                    Acquista dati automezzo € 0,80
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">

                <!-- <div id="js_risultati_ricerca" style="display:none"> -->
                <div id="js_risultati_ricerca" style="display:none">
                    <!-- <h4>Anagrafica di base</h4> -->
                    <table class="table">
                        <tr>
                            <th width="220"></th>
                            <th width="390">Dati scaricati</th>
                            <th width="390">Dati salvati</th>
                        </tr>
                        <tr>
                            <td>Marca</td>
                            <td class="js_rx_marca"></td>
                            <td class="js_db_marca">
                                <?php echo $automezzo['automezzi_marca']; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Modello</td>
                            <td class="js_rx_modello"></td>
                            <td class="js_db_modello">
                                <?php echo $automezzo['automezzi_modello']; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Versione</td>
                            <td class="js_rx_versione"></td>
                            <td class="js_db_versione">
                                <?php echo $automezzo['automezzi_versione']; ?>
                            </td>

                        </tr>
                        <tr>
                            <td>Targa</td>
                            <td class="js_rx_targa"></td>
                            <td class="js_db_targa">
                                <?php echo $automezzo['automezzi_targa']; ?>
                            </td>

                        </tr>
                        <tr>
                            <td>Anno immatricolazione</td>
                            <td class="js_rx_immatricolazione"></td>
                            <td class="js_db_immatricolazione">
                                <?php echo $automezzo['automezzi_anno_immatricolazione']; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Alimentazione</td>
                            <td class="js_rx_alimentazione"></td>
                            <td class="js_db_alimentazione">
                                <?php echo $automezzo['automezzi_alimentazione_value']; ?>
                            </td>
                            </td>
                        </tr>
                        <tr>
                            <td>Cavalli</td>
                            <td class="js_rx_cavalli"></td>
                            <td class="js_db_cavalli">
                                <?php echo $automezzo['automezzi_cavalli']; ?>
                            </td>
                            </td>
                        </tr>
                        <tr>
                            <td>Kw</td>
                            <td class="js_rx_kw"></td>
                            <td class="js_db_kw">
                                <?php echo $automezzo['automezzi_kw']; ?>
                            </td>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div id="msg_update_automezzo" class="text-center"></div>
            </div>
        </div>
    </div>

    <div class="form-actions" style="text-align: center">
        <button type="button" id="js_ricerca_dati" class="btn btn-success js_pulsanti_action" style="display: none;">Salva dati aggiornati</button>
    </div>


    <script>
    var ricerca_data;

    $('body').on('click', '#js_acquista_dati', function(e) {
        e.stopImmediatePropagation();

        $('#js_acquista_dati').prop('disabled', true);
        $('#msg_update_automezzo').append('<br /><br />Attendere ...');

        request(base_url + 'openapi-integration/main/cerca_automezzo', {
            cerca_targa: '<?php echo $automezzo['automezzi_targa'];?>',
            [token_name]: token_hash
        }, 'POST', false, false, {}).then(data => {

            if (!data.success) {
                toast('Errore', 'error', data.message, 'toastr', false);
                $('#msg_update_automezzo').html("Errore: " + data.message);
                $('#js_crea_automezzo').hide();
                $('#js_acquista_dati').prop('disabled', false);
                return;
            }

            if (data.success) {
                ricerca_data = data.data;

                $('.js_acquista_box').hide();
                $('#js_risultati_ricerca').fadeIn();

                // Rappresento i dati
                $('.js_rx_marca').html(data.data.CarMake);
                $('.js_rx_modello').html(data.data.CarModel);
                $('.js_rx_versione').html(data.data.Version);
                $('.js_rx_targa').html(data.data.LicensePlate);
                $('.js_rx_immatricolazione').html(data.data.RegistrationYear);
                $('.js_rx_alimentazione').html(data.data.FuelType);
                $('.js_rx_cavalli').html(data.data.PowerCV);
                $('.js_rx_kw').html(data.data.PowerKW);

                $('#js_aggiorna_anagrafica').show();
                $('.js_pulsanti_action').show();

                $('#msg_update_automezzo').html("");
            }
        }).catch(error => {
            console.log(error);
        });
    })

    $('body').on('click', '#js_ricerca_dati', function(e) {
        e.stopImmediatePropagation();

        $('.js_pulsanti_action').hide();

        $('#msg_update_automezzo').append('<br /><br />Attendere ...');

        request(base_url + 'openapi-integration/main/aggiorna_automezzo/<?php echo $automezzo_id; ?>', {
            data: JSON.stringify(ricerca_data),
            [token_name]: token_hash
        }, 'POST', false, false, {}).then(data => {

            if (!data.success) {
                toast('Errore', 'error', data.message, 'toastr', false);
                $('#msg_update_automezzo').html("Errore: " + data.message);
                $('#js_crea_automezzo').show();
                $('.js_pulsanti_action').hide();
                return;
            } else {
                $('#msg_update_automezzo').html("<strong>I dati sono stati correttamente salvati!</strong>");
                /* setTimeout(() => {
                    window.location.reload();
                }, 3000); */
            }
        }).catch(error => {
            console.log(error);
        });
    })
    </script>
</div>