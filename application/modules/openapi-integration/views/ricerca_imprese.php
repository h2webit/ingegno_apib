<?php
$parametri_ricerca = $this->db->get_where('openapi_ricerce_imprese', "openapi_ricerce_imprese_id = {$value_id}")->row_array();

?>


<div class="row">
    <div class="col-md-12">

        <h4>Ci sono due modalità per la ricerca dei dati</h4>
        <p>Per questa ricerca è obbligatorio impostare il limite, in quanto il costo viene calcolato sul numero di
            risultati ottenuti.</p>

        <table class="table">
            <tr>
                <td>Ricerca semplice <br /><small>Verranno estratte solo le ragioni sociali e i comuni</small></td>
                <td><button type="button" data-servizio_id="6"
                        class="btn btn-primary js_acquista_servizio">Gratis</button></td>
            </tr>

            <tr>
                <td>Ricerca completa<br /> <small>Verranno estratte tutte le informazioni complete, esempio P.IVA,
                        Indirizzo, CAP.</small></td>
                <td><button type="button" data-servizio_id="3" class="btn btn-primary js_acquista_servizio">€0,04 a
                        risultato</button></td>
            </tr>
        </table>
    </div>

</div>
<div class="row ">
    <div class="col-md-12">
        <div id="msg_form_servizio_info" class=""></div>
    </div>
</div>


<script>
    var ricerca_data;


    // Acquista Rircerca
    $('body').on('click', '.js_acquista_servizio', function (e) {
        e.stopImmediatePropagation();

        var servizio_id = $(this).data('servizio_id');
        $('#msg_form_servizio_info').html('<br /><br />Ricerca in corso. Attendere...');

        $.ajax({
            method: 'post',
            async: true,
            url: base_url + "openapi-integration/main/ricerca_imprese/",
            dataType: "json",
            data: {
                data: '<?php echo json_encode($parametri_ricerca); ?>',
                servizio_id: servizio_id,
                [token_name]: token_hash
            },
            success: function (res) {
                console.log(res);
                if (res.success == false) {
                    $('#msg_form_servizio_info').html("Errore: " + res.message);
                } else {
                    // Redirect to customer detail
                    window.location.reload();
                }
            }
        });
    });


    // Acquista dati azienda
    $('body').on('click', '.js_acquista_dati', function (e) {
        e.stopImmediatePropagation();

        loading(true);

        var piva = $(this).data('piva');
        var risultato_id = $(this).data('risultato_id');
        var this_button = $(this);

        $(this_button).html("Wait...");

        $.ajax({
            method: 'post',
            async: true,
            url: base_url + "openapi-integration/main/cerca_impresa_advanced/" + risultato_id,
            dataType: "json",
            data: {
                piva_cf: piva,
                [token_name]: token_hash
            },
            success: function (res) {
                loading(false);
                $(this_button).hide();
                $(this_button).next().show();
            }
        });
    })
</script>