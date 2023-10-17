<?php
$customer_id = $this->input->get('customer_id');
$customer = $this->db->get_where('customers', "customers_id = {$customer_id}")->row_array();

if (empty($customer['customers_vat_number'])) {
    echo "<strong>Servizio attivo solo sulle anagrafiche aziendali.</strong>";
    return false;
}
?>


<div class="box-body layout_box form">

    <div class="box-body">
        <div class="row sortableForm">


            <div class="col-md-12">

                <h4>Scegli il tipo di bilancio che vuoi acquistare:</h4>

                <table class="table">
                    <tr>
                        <td>Bilancio ottico <small>Ultimo bilancio depositato</small></td>
                        <td><button type="button" data-servizio_id="4" class="btn btn-primary js_acquista_bilancio">Acquista €
                                5,00</button></td>
                    </tr>
            <tr>
                <td><div id="msg_form_acquista_bilancio" class=""></div></td>
            </tr>

            </div>

        </div>

    </div>

    <script>
        var ricerca_data;


        // Acquista bilancio
        $('body').on('click', '.js_acquista_bilancio', function (e) {
            e.stopImmediatePropagation();

            var servizio_id = $(this).data('servizio_id');
            //$('#msg_form_acquista_bilancio').append('<br /><br />Attendere ...');
            $('#msg_form_acquista_bilancio').html('<br /><br />Attendere ...');

            $.ajax({
                method: 'post',
                async: true,
                url: base_url + "openapi-integration/main/acquista_bilancio/",
                dataType: "json",
                data: {
                    piva: '<?php echo $customer['customers_vat_number'];?>',
                    azienda_id: '<?php echo $customer_id;?>',
                    servizio_id: servizio_id,
                    [token_name]: token_hash
                },
                success: function (res) {
                    console.log(res);
                    if (res.success == false) {
                        $('#msg_form_acquista_bilancio').html("Errore: " + res.message);
                    } else {
                        // Redirect to customer detail
                        window.location.href = res.message;
                    }
                }
            });
        });
    </script>
</div>