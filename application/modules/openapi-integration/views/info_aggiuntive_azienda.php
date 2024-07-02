<?php
$customer_id = $this->input->get('customer_id');
$this->mycache->clearCache();
$customer = $this->db->get_where('customers', "customers_id = {$customer_id}")->row_array();
$this->mycache->clearCache();
$customer_extra = $this->db->get_where('customers_dati_extra', "customers_dati_extra_customer_id = {$customer_id}")->row_array();
$this->mycache->clearCache();

?>

<div class="box-body layout_box form">

    <div class="box-body">
        <div class="row sortableForm">
            <div class="formColumn js_container_field col-md-12">
                <p>Il servizio consente, tramite l'inserimento di partita iva / codice fiscale di un'impresa, di
                    ottenere tutte le informazioni presenti nel cerca imprese base come denominazione, città, indirizzo,
                    provincia e CAP più altre dettagliate come <strong>REA, PEC, codice ATECO, numero dipendenti,
                        fatturato,</strong> utili ultimi 3 anni.</p>
                <p class="text-center js_acquista_box" style="margin-top:20px">

                    <?php if ($customer['customers_vat_number']): ?>
                        <button type="button" id="js_acquista_dati" class="btn btn-primary"><i class="fas fa-eur"></i>
                            Acquista dati aziendali € 0,35</button>
                    <?php else: ?>
                        <strong>Servizio attivo solo per le aziende, con Partita IVA italiana.</strong>
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <div class="row ">
            <div class="col-md-12">

                <div id="js_risultati_anagrafica_base" style="display:none">
                    <h4>Anagrafica di base</h4>
                    <table class="table">
                        <tr>
                            <th width="300"></th>
                            <th width="500">Dati scaricati</th>
                            <th>Dati salvati in angarafica</th>
                        </tr>
                        <tr>
                            <td>Ragione sociale</td>
                            <td class="js_rx_ragione_sociale"></td>
                            <td class="js_db_ragione_sociale">
                                <?php echo $customer['customers_company']; ?>
                            </td>

                        </tr>
                        <tr>
                            <td>Comune</td>
                            <td class="js_rx_comune"></td>
                            <td class="js_db_comune">
                                <?php echo $customer['customers_city']; ?>
                            </td>

                        </tr>
                        <tr>
                            <td>Indirizzo</td>
                            <td class="js_rx_indirizzo"></td>
                            <td class="js_db_indirizzo">
                                <?php echo $customer['customers_city']; ?>
                            </td>

                        </tr>
                        <tr>
                            <td>Provincia</td>
                            <td class="js_rx_provincia"></td>
                            <td class="js_db_provincia">
                                <?php echo $customer['customers_province']; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>CAP</td>
                            <td class="js_rx_cap"></td>
                            <td class="js_db_cap">
                                <?php echo $customer['customers_zip_code']; ?>
                            </td>
                            </td>
                        </tr>
                        <tr>
                            <td>CF</td>
                            <td class="js_rx_cf"></td>
                            <td class="js_db_cf">
                                <?php echo $customer['customers_cf']; ?>
                            </td>
                            </td>
                        </tr>
                        <tr>
                            <td>PEC</td>
                            <td class="js_rx_pec"></td>
                            <td class="js_db_pec">
                                <?php echo $customer['customers_pec']; ?>
                            </td>
                            </td>
                        </tr>
                        <tr>
                            <td>Codice destinatario</td>
                            <td class="js_rx_sdi"></td>
                            <td class="js_db_sdi">
                                <?php echo $customer['customers_sdi']; ?>
                            </td>
                            </td>
                        </tr>

                    </table>
                    <h4>Dati extra</h4>
                    <table class="table">
                        <tr>
                            <th width="300"></th>
                            <th width="500">Dati scaricati</th>
                            <th>Dati salvati in angarafica</th>
                        </tr>
                        <tr>
                            <td>Camera di commercio</td>
                            <td class="js_rx_cciaa"></td>
                            <td class="js_db_cciaa">
                                <?php echo (!empty($customer_extra['customers_dati_extra_cciaa'])) ? $customer_extra['customers_dati_extra_cciaa'] : ''; ?>
                            </td>
                        </tr>

                        <tr>
                            <td>REA</td>
                            <td class="js_rx_rea"></td>
                            <td class="js_db_rea">
                                <?php echo (!empty($customer_extra['customers_dati_extra_rea'])) ? $customer_extra['customers_dati_extra_rea'] : ''; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Codice ATECO</td>
                            <td class="js_rx_cod_ateco"></td>
                            <td class="js_db_cod_ateco">
                                <?php echo (!empty($customer_extra['customers_dati_extra_codice_ateco'])) ? $customer_extra['customers_dati_extra_codice_ateco'] : ''; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Descriz. ATECO</td>
                            <td class="js_rx_desc_ateco"></td>
                            <td class="js_db_desc_ateco">
                                <?php echo (!empty($customer_extra['customers_dati_extra_descrizione_ateco'])) ? $customer_extra['customers_dati_extra_descrizione_ateco'] : ''; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Inizio Attività</td>
                            <td class="js_rx_data_inizio"></td>
                            <td class="js_db_data_inizio">
                                <?php echo (!empty($customer_extra['customers_dati_extra_data_inizio_attivita'])) ? $customer_extra['customers_dati_extra_data_inizio_attivita'] : ''; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Bilanci</td>
                            <td class="js_rx_bilanci"></td>
                            <td class="js_db_bilanci">
                                <?php if (!empty($customer_extra['customers_dati_extra_bilanci'])) {
                                    $bilanci = json_decode($customer_extra['customers_dati_extra_bilanci'], true);
                                    foreach ($bilanci as $bilancio) {
                                        foreach ($bilancio as $key => $value) {
                                            $_key = str_replace("_", " ", $key);
                                            echo "<strong>" . ucfirst($_key) . "</strong>: " . $value . "<br />";
                                        }
                                        echo "<br />";
                                    }
                                } ?>
                            </td>
                        </tr>
                        <tr>
                            <td>Soci</td>
                            <td class="js_rx_soci"></td>
                            <td class="js_db_soci">
                                <?php if (!empty($customer_extra['customers_dati_extra_soci'])) {
                                    $soci = json_decode($customer_extra['customers_dati_extra_soci'], true);
                                    if (!empty($soci)):
                                        foreach ($soci as $socio) {
                                            foreach ($socio as $key => $value) {
                                                $_key = str_replace("_", " ", $key);
                                                echo "<strong>" . ucfirst($_key) . "</strong>: " . $value . "<br />";
                                            }
                                            echo "<br />";
                                        }
                                    endif;
                                } ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <div id="msg_form_output" class=""></div>
            </div>
        </div>
    </div>

    <div class="form-actions" style="text-align: center">
        <button id="js_aggiorna_anagrafica" class="btn btn-success js_pulsanti_action" style="display:none">Aggiorna
            tutto</button>
        <button id="js_salva_dati_extra" class="btn btn-success js_pulsanti_action" style="display:none">Aggiorna solo
            dati extra</button>
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