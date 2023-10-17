$(() => {
    generaScadenze = function (totale, totale_iva) { //Questa funzione sovrascrive quella nativa (che non fa niente) e serve proprio a gestire scadenze custom basate sulla configurazione cliente

        //In base all'id cliente, verifico se ha impostato un template di scadenze (basandomi sul codice)
        var entita_cliente = $('[name="dest_entity_name"]').val();
        //console.log(cliente_raw_data);
        var template_pagamento_id = $('[name="documenti_contabilita_template_pagamento"]').val();
        if (!template_pagamento_id) {
            //Cerco il campo nei dati cliente
            for (var campo in cliente_raw_data) {
                if (campo.includes("template_pagamento")) {
                    template_pagamento_id = cliente_raw_data[campo];
                    break;
                }
            }
        }

        if (template_pagamento_id) {
            //Cerco il template
            //console.log(codice_pagamento);
            //console.log(template_scadenze);
            for (var i in template_scadenze) { //Ciclo l'array delle scadenze (configurate a monte)
                var template_scadenza = template_scadenze[i];
                if (template_scadenza.documenti_contabilita_template_pagamenti_id == template_pagamento_id) { //Trovo quella il cui codice coincide
                    //A questo punto posso generare le scadenze in Automatico
                    var residuo = totale;

                    // console.log(template_scadenze);
                    // console.log(i);
                    //alert('stop1');

                    $('.js_rows_scadenze .row_scadenza').not(':first').remove();

                    for (var k in template_scadenza.documenti_contabilita_tpl_pag_scadenze) { //A questo punto ciclo le singole righe di scadenza per generare effettivamente n scadenze che rispettino le regole impostate
                        //increment_scadenza();
                        var tpl_riga_scadenza = template_scadenza.documenti_contabilita_tpl_pag_scadenze[k];

                        var metodo_di_pagamento = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_metodo;
                        var json_data = JSON.stringify(tpl_riga_scadenza);

                        var tipo = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_tipo_value;

                        var giorni = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_giorni;

                        var saldata_automatico = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_saldata;

                        if (k >= template_scadenza.documenti_contabilita_tpl_pag_scadenze.length - 1) {
                            //Se è l'ultima scadenza, ignoro tutto e la rimanenza la assegno a questa scadenza (devo pagare tutto prima o poi, a prescindere dalle percentuali configurate e soprattutto evito eventuali problemi di arrotondamenti o perdite di decimali, errori o simili...)
                            var ammontare_scadenza = residuo;
                        } else {

                            //console.log(tpl_riga_scadenza);
                            var percentuale = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_percentuale;
                            var prima_iva = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_prima_iva;
                            var solo_iva = tpl_riga_scadenza.documenti_contabilita_tpl_pag_scadenze_solo_iva;


                            //TODO: Salvare tutto il json per rielaborazioni future all'interno della riga pagamento

                            if (solo_iva == 1) {
                                var ammontare_scadenza = totale_iva;

                            } else if (prima_iva == 1) {
                                alert('TODO: l\'informazione "prima iva" non viene ancora gestita');
                                // var totale_previsto = percCalc(totale, percentuale);
                                // var ammontare_scadenza = totale_iva;
                                var ammontare_scadenza = percCalc(totale, percentuale);
                            } else {
                                //alert(2);
                                var ammontare_scadenza = percCalc(totale, percentuale);
                            }
                        }

                        residuo -= ammontare_scadenza;

                        var data_scadenza = dataCalc(moment($('[name="documenti_contabilita_data_emissione"]').val(), 'DD/MM/YYYY').format('DD/MM/YYYY'), tipo, giorni, cliente_raw_data);

                        //A questo punto imposto la scadenza la scadenza
                        var ultima_riga_scadenza = $('.js_rows_scadenze .row_scadenza:last');
                        //ultima_riga_scadenza.css('background-color', 'red');
                        // alert(data_scadenza);
                        $('.documenti_contabilita_scadenze_template_json', ultima_riga_scadenza).val(json_data);

                        $('.documenti_contabilita_scadenze_scadenza', ultima_riga_scadenza).val(data_scadenza);
                        //console.log('metodo: ' + metodo_di_pagamento);
                        $('.documenti_contabilita_scadenze_saldato_con', ultima_riga_scadenza).val(metodo_di_pagamento);
                        //console.log(ammontare_scadenza);
                        $('.documenti_contabilita_scadenze_ammontare', ultima_riga_scadenza).val(parseFloat(ammontare_scadenza).toFixed(2));

                        if (saldata_automatico == 1) {
                            $('[name*="documenti_contabilita_scadenze_data_saldo"]', ultima_riga_scadenza).val(data_scadenza);
                        }
                        if (residuo > 0) {
                            //alert(residuo);
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
        console.log(data);
        //alert(1);
        var $momentDate = moment(data, 'DD/MM/YYYY');
        var $dataScadenzaBase = $momentDate.clone();
        
        
        if (giorni == 30) {
            $dataScadenzaBase =  $dataScadenzaBase.add(1, 'month');
        } else if (giorni == 60) {
            $dataScadenzaBase =  $dataScadenzaBase.add(2, 'month');
        } else if (giorni == 90) {
            $dataScadenzaBase =  $dataScadenzaBase.add(3, 'month');
        } else if (giorni == 120) {
            $dataScadenzaBase =  $dataScadenzaBase.add(4, 'month');
        } else {
            $dataScadenzaBase =  $dataScadenzaBase.add(giorni, 'days');
        }

        if (tipo == 'Fine mese') { //Fine mese
            var $dataScadenzaBase = $dataScadenzaBase.endOf('month');
        } else if (tipo == 'Data Fattura') { //Data fattura
            //var $dataScadenzaBase = $momentDate;
        } else {
            var $dataScadenzaBase = $momentDate;
            alert('Tipo scadenza (Fine Mese o Data Fattura) non specificato o errato. Controllare la configurazione scadenze.');
        }
        if (typeof cliente_raw_data !== 'undefined') {
            var giorni_spostamento = cliente_raw_data.customers_pag_gg_spostamento;
            var giorno_fisso = cliente_raw_data.customers_pag_giorno_fisso;
            if (giorni_spostamento > 0) {
                $dataScadenzaBase.add(giorni_spostamento, 'days');
            } else if (giorno_fisso > 0) {
                $dataScadenzaBase.date(giorno_fisso);
            }
        }
        

        return $dataScadenzaBase.format('DD/MM/YYYY');
        

    }
});