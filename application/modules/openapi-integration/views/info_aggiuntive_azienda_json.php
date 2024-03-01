<?php
$risultato_id = $value_id;
$dati_extra = $this->db->get_where('openapi_ricerche_risultati', "openapi_ricerche_risultati_id = {$risultato_id}")->row_array();

if (!empty($dati_extra['openapi_ricerche_risultati_dati_extra'])) {
    $dati = json_decode($dati_extra['openapi_ricerche_risultati_dati_extra'], true);
    $customer_extra = $dati['data']['dettaglio'];

} else {
    echo "No extra data found.";
    return false;
}
?>

<div class="box-body layout_box form">

    <div class="box-body">
        <div class="row ">
            <div class="col-md-12">
                <div id="js_risultati_anagrafica_base" style="">
                    <table class="table">
                        <tr>
                            <td>Camera di commercio</td>
                            <td class="js_db_cciaa">
                                <?php echo (!empty($customer_extra['cciaa'])) ? $customer_extra['cciaa'] : ''; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Codice ATECO</td>

                            <td class="js_db_cod_ateco">
                                <?php echo (!empty($customer_extra['codice_ateco'])) ? $customer_extra['codice_ateco'] : ''; ?><br />
                                <small>
                                    <?php echo (!empty($customer_extra['descrizione_ateco'])) ? $customer_extra['descrizione_ateco'] : ''; ?>
                                </small>
                            </td>
                        </tr>
                        <tr>
                            <td>Inizio Attività</td>
                            <td class="js_db_data_inizio">
                                <?php echo (!empty($customer_extra['data_inizio_attivita'])) ? $customer_extra['data_inizio_attivita'] : ''; ?>
                            </td>
                        </tr>

                    </table>
                </div>

                <div id="msg_form_output" class=""></div>
            </div>

            <div class="col-md-6">
                <div id="js_risultati_anagrafica_base" style="">
                    <table class="table">

                        <tr>
                            <td>Bilanci</td>
                            <td class="js_db_bilanci">
                                <?php if (!empty($customer_extra['bilanci'])) {
                                    foreach ($customer_extra['bilanci'] as $bilancio) {
                                        foreach ($bilancio as $key => $value) {
                                            $_key = str_replace("_", " ", $key);
                                            echo "<strong>" . ucfirst($_key) . "</strong>: " . $value . "<br />";
                                        }
                                        echo "<br />";
                                    }
                                } ?>
                            </td>
                        </tr>

                    </table>
                </div>

                <div id="msg_form_output" class=""></div>
            </div>

            <div class="col-md-6">
                <div id="js_risultati_anagrafica_base" style="">
                    <table class="table">

                        <tr>
                            <td>Soci</td>
                            <td class="js_db_soci">
                                <?php if (!empty($customer_extra['soci'])) {
                                    foreach ($customer_extra['soci'] as $socio) {
                                        foreach ($socio as $key => $value) {
                                            $_key = str_replace("_", " ", $key);
                                            echo "<strong>" . ucfirst($_key) . "</strong>: " . $value . "<br />";
                                        }
                                        echo "<br />";
                                    }
                                } ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="msg_form_output" class=""></div>
            </div>
        </div>
    </div>


    <script>
        var ricerca_data;

        $('body').on('click', '#js_acquista_dati', function (e) {
            e.stopImmediatePropagation();

            $.ajax({
                method: 'post',
                async: true,
                url: base_url + "openapi-integration/main/cerca_impresa_advanced/",
                dataType: "json",
                data: {
                    piva_cf: '<?php echo $customer['customers_vat_number']; ?>',
                    [token_name]: token_hash
                },
                success: function (res) {

                    if (res.success == false) {
                        $('#msg_form_output').html("Errore: " + res.message);
                    } else {
                        ricerca_data = res.data;

                        $('.js_acquista_box').hide();
                        $('#js_risultati_anagrafica_base').fadeIn();

                        // Rappresento i dati
                        $('.js_rx_ragione_sociale').html(ricerca_data.denominazione);
                        $('.js_rx_comune').html(ricerca_data.comune);
                        $('.js_rx_indirizzo').html(ricerca_data.indirizzo);
                        $('.js_rx_provincia').html(ricerca_data.provincia);
                        $('.js_rx_cap').html(ricerca_data.cap);
                        $('.js_rx_cf').html(ricerca_data.cf);
                        $('.js_rx_pec').html(ricerca_data.dettaglio.pec);
                        $('.js_rx_sdi').html(ricerca_data.codice_destinatario);

                        // Dati extra
                        $('.js_rx_cciaa').html(ricerca_data.dettaglio.cciaa);
                        $('.js_rx_rea').html(ricerca_data.dettaglio.rea);
                        $('.js_rx_cod_ateco').html(ricerca_data.dettaglio.codice_ateco);
                        $('.js_rx_desc_ateco').html(ricerca_data.dettaglio.descrizione_ateco);
                        $('.js_rx_data_inizio').html(ricerca_data.dettaglio.data_inizio_attivita);


                        // Bilanci
                        var bilanci = ricerca_data.dettaglio.bilanci;
                        for (const [key, value] of Object.entries(bilanci)) {
                            $('.js_rx_bilanci').append("Chiusura bilancio: " + bilanci[key]['data_chiusura_bilancio'] + "<br />");
                            if (bilanci[key]['fatturato'])
                                $('.js_rx_bilanci').append("Fatturato: € " + bilanci[key]['fatturato'] + "<br />");
                            if (bilanci[key]['utile'])
                                $('.js_rx_bilanci').append("Utile: € " + bilanci[key]['utile'] + "<br />");
                            if (bilanci[key]['dipendenti'])
                                $('.js_rx_bilanci').append("Dipendenti: " + bilanci[key]['dipendenti'] + "<br />");
                            if (bilanci[key]['capitale_sociale'])
                                $('.js_rx_bilanci').append("Capitale sociale: € " + bilanci[key]['capitale_sociale'] + "<br />");
                            $('.js_rx_bilanci').append("<br />");
                        }

                        // Soci
                        var soci = ricerca_data.dettaglio.soci;
                        for (const [key, value] of Object.entries(soci)) {
                            $('.js_rx_soci').append("Denominazione: " + soci[key]['denominazione'] + "<br />");
                            $('.js_rx_soci').append("Nome: " + soci[key]['nome'] + "<br />");
                            $('.js_rx_soci').append("Cognome: " + soci[key]['cognome'] + "<br />");
                            $('.js_rx_soci').append("CF Socio: " + soci[key]['cf_socio'] + "<br />");
                            $('.js_rx_soci').append("Quota: " + soci[key]['quota'] + "%<br />");
                            $('.js_rx_soci').append("<br />");
                        }

                        $('#js_aggiorna_anagrafica').show();
                        $('#js_salva_dati_extra').show();
                    }
                }
            });
        })

        function aggiornaDati(full = 0) {
            $('#msg_form_output').html('<br /><br />Attendere ...');

            $.ajax({
                method: 'post',
                async: true,
                url: base_url + "openapi-integration/main/aggiorna_anagrafica/<?php echo $customer['customers_id']; ?>",
                dataType: "json",
                data: {
                    data: ricerca_data,
                    full_data: full,
                    [token_name]: token_hash
                },
                success: function (res) {
                    console.log(res);
                    if (res.success == false) {
                        $('#msg_form_output').html("Errore: " + res.message);
                    } else {
                        $('#msg_form_output').html("I dati sono stati correttamente salvati!");
                        $('.js_pulsanti_action').hide();
                    }
                }
            });
        }
        // Aggiorna anagrafica completa
        $('body').on('click', '#js_aggiorna_anagrafica', function (e) {
            e.stopImmediatePropagation();
            aggiornaDati(1);
        });
        // Inserisci solo dati extra
        $('body').on('click', '#js_salva_dati_extra', function (e) {
            e.stopImmediatePropagation();
            aggiornaDati(0);
        });
    </script>
</div>