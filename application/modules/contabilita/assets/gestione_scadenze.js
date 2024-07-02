$(() => {
    generaScadenze = function (totale, totale_iva) {
        var entita_cliente = $('[name="dest_entity_name"]').val();
        var template_pagamento_id = $('[name="documenti_contabilita_template_pagamento"]').val();
        if (!template_pagamento_id) {
            for (var campo in cliente_raw_data) {
                if (campo.includes("template_pagamento")) {
                    template_pagamento_id = cliente_raw_data[campo];
                    break;
                }
            }
        }

        if (template_pagamento_id) {
            for (var i in template_scadenze) {
                var template_scadenza = template_scadenze[i];
                if (template_scadenza.documenti_contabilita_template_pagamenti_id == template_pagamento_id) {
                    var residuo = totale;

                    $('.js_rows_scadenze .row_scadenza').not(':first').remove();

                    for (var k in template_scadenza.documenti_contabilita_tpl_pag_scadenze) {
                        var tpl_riga_scadenza = template_scadenza.documenti_contabilita_tpl_pag_scadenze[k];
                        var metodo_di_pagamento = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_metodo;
                        var json_data = JSON.stringify(tpl_riga_scadenza);
                        var tipo = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_tipo_value;
                        var giorni = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_giorni;
                        var saldata_automatico = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_saldata;

                        var ammontare_scadenza;
                        if (k >= template_scadenza.documenti_contabilita_tpl_pag_scadenze.length - 1) {
                            ammontare_scadenza = residuo;
                        } else {
                            var percentuale = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_percentuale;
                            if (percentuale == 100 && template_scadenza.documenti_contabilita_tpl_pag_scadenze.length > 1) {
                                percentuale = 100 / template_scadenza.documenti_contabilita_tpl_pag_scadenze.length;
                            }

                            var prima_iva = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_prima_iva;
                            var solo_iva = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_solo_iva;

                            if (solo_iva == 1) {
                                ammontare_scadenza = totale_iva;
                            } else if (prima_iva == 1) {
                                alert('TODO: l\'informazione "prima iva" non viene ancora gestita');
                                ammontare_scadenza = percCalc(totale, percentuale);
                            } else {
                                ammontare_scadenza = percCalc(totale, percentuale);
                            }
                        }

                        residuo -= ammontare_scadenza;

                        var data_scadenza = dataCalc(moment($('[name="documenti_contabilita_data_emissione"]').val(), 'DD/MM/YYYY').format('DD/MM/YYYY'), tipo, giorni, cliente_raw_data);

                        var ultima_riga_scadenza = $('.js_rows_scadenze .row_scadenza:last');
                        $('.documenti_contabilita_scadenze_template_json', ultima_riga_scadenza).val(json_data);
                        $('.documenti_contabilita_scadenze_scadenza', ultima_riga_scadenza).val(data_scadenza);
                        $('.documenti_contabilita_scadenze_saldato_con', ultima_riga_scadenza).val(metodo_di_pagamento);
                        $('.documenti_contabilita_scadenze_ammontare', ultima_riga_scadenza).val(parseFloat(ammontare_scadenza).toFixed(2));

                        if (saldata_automatico == 1) {
                            $('[name*="documenti_contabilita_scadenze_data_saldo"]', ultima_riga_scadenza).val(data_scadenza);
                        }
                        if (residuo > 0) {
                            increment_scadenza();
                        }
                    }
                }
            }
        }
    }

    percCalc = function (value, perc) {
        return (((value / 100) * perc).toFixed(2));
    }

    dataCalc = function (data, tipo, giorni, cliente_raw_data) {
        var $momentDate = moment(data, 'DD/MM/YYYY');
        var $dataScadenzaBase = $momentDate.clone();

        var mesi = Math.floor(giorni / 30);

        console.log(mesi, giorni)

        if (mesi >= 1) {
            $dataScadenzaBase = $dataScadenzaBase.add(mesi, 'month');
        } else {
            $dataScadenzaBase = $dataScadenzaBase.add(giorni, 'days');
        }

        if (tipo == 'Fine mese') {
            $dataScadenzaBase = $dataScadenzaBase.endOf('month');
        } else if (tipo == 'Data Fattura') {
            //var $dataScadenzaBase = $momentDate;
        } else {
            var $dataScadenzaBase = $momentDate;
            alert('Tipo scadenza (Fine Mese o Data Fattura) non specificato o errato. Controllare la configurazione scadenze.');
        }
        if ((giorni / 30) !== mesi) {
            var giorni_calcolati = giorni - (mesi * 30);
            $dataScadenzaBase = $dataScadenzaBase.add(giorni_calcolati, 'days');
        }

        if (typeof cliente_raw_data !== 'undefined') {
            var mese_escluso_1 = cliente_raw_data.customers_pag_escluso_mese_1;
            var mese_escluso_2 = cliente_raw_data.customers_pag_escluso_mese_2;
            var giorni_spostamento = cliente_raw_data.customers_pag_gg_spostamento;
            var giorno_fisso = cliente_raw_data.customers_pag_giorno_fisso;

            if ((mese_escluso_1 > 0 && $dataScadenzaBase.format('M') == mese_escluso_1) || (mese_escluso_2 > 0 && $dataScadenzaBase.format('M') == mese_escluso_2)) {
                if (giorni_spostamento > 0) {
                    $dataScadenzaBase.add(giorni_spostamento, 'days');
                }
            }
            if (giorno_fisso > 0) {
                $dataScadenzaBase.add('1 month');
                $dataScadenzaBase.date(giorno_fisso);
            }
        }

        return $dataScadenzaBase.format('DD/MM/YYYY');
    }
});
