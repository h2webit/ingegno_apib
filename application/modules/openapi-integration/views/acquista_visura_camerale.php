<?php
$customer_id = $this->input->get('customer_id');
$customer = $this->db->get_where('customers', "customers_id = {$customer_id}")->row_array();
if (empty($customer['customers_vat_number'])) {
    echo "<strong>Servizio attivo solo sulle anagrafiche aziendali.</strong>";
    return false;
}
switch ($customer['customers_group']) {
  case 1:
    echo "<strong>Servizio attivo solo sulle anagrafiche aziendali.</strong>";
    break;
  case 2:
    $tipologia_impresa = "Società";
    //seleziono dati per visura ordinaria
    $servizio_ordinaria = "11";
    $prezzo_ordinaria = "3,40 - 4,90";
     //seleziono dati per visura storica
    $servizio_storica = "12";
    $prezzo_storica = "4,40 - 5,90";
    break;
  case 3:
    $tipologia_impresa = "Impresa individuale";
    //seleziono dati per visura ordinaria
    $servizio_ordinaria = "9";
    $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$servizio_ordinaria'")->row_array();
    $prezzo_ordinaria = $servizio['openapi_servizi_costo_chiamata'];
     //seleziono dati per visura storica
    $servizio_storica = "10";
    $servizio = $this->db->query("SELECT * FROM openapi_servizi WHERE openapi_servizi_id = '$servizio_storica'")->row_array();
    $prezzo_storica = $servizio['openapi_servizi_costo_chiamata'];
    break;
  default:
    echo "<strong>Servizio attivo solo sulle anagrafiche aziendali.</strong>";
}
?>


<div class="box-body layout_box form">

    <div class="box-body">
        <div class="row sortableForm">


            <div class="col-md-12">

                <h4>Scegli il tipo di visura che vuoi acquistare:</h4>

                <table class="table">
                    <tr>
                        <td>Visura ordinaria <small><?php echo $tipologia_impresa; ?></small></td>
                        <td><button type="button" data-servizio_id="<?php echo $servizio_ordinaria; ?>" class="btn btn-primary js_acquista_visura">Acquista €
                                <?php echo $prezzo_ordinaria; ?></button></td>
                    </tr>
                    <tr>
                        <td>Visura storica <small><?php echo $tipologia_impresa; ?></small></td>
                        <td><button type="button" data-servizio_id="<?php echo $servizio_storica; ?>" class="btn btn-primary js_acquista_visura">Acquista €
                                <?php echo $prezzo_storica; ?></button></td>
                    </tr>
                    <tr>
                        <td><div id="msg_form_acquista_visura" class=""></div></td>
                    </tr>
            </div>

        </div>
    </div>




    <script>
        var ricerca_data;


        // Acquista visura
        $('body').on('click', '.js_acquista_visura', function (e) {
            e.stopImmediatePropagation();

            var servizio_id = $(this).data('servizio_id');
            $('#msg_form_acquista_visura').html('<br /><br />Attendere ...');

            $.ajax({
                method: 'post',
                async: true,
                url: base_url + "openapi-integration/main/acquista_visura/",
                dataType: "json",
                data: {
                    piva: '<?php echo $customer['customers_vat_number'];?>',
                    azienda_id: '<?php echo $customer_id;?>',
                    societa: '<?php echo $customer['customers_company'];?>',
                    servizio_id: servizio_id,
                    [token_name]: token_hash
                },
                success: function (res) {
                    console.log(res);
                    if (res.success == false) {
                        $('#msg_form_acquista_visura').html("Errore: " + res.message);
                    } else {
                        // Redirect to customer detail
                        window.location.reload();
                    }
                }
            });
        });
    </script>
</div>