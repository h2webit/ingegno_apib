'use strict';

// BLOCCO SUBMIT FORM CON TASTO INVIO
$(document).on('keypress', 'form', function (event) {
    const pressedElement = $(event.target);
    if (!pressedElement.is('textarea')) {
        return event.keyCode !== 13;
    }
});

// TRASFORMO IL TASTO INVIO, IN TASTO TAB
$(document).on('keydown', 'input, select, buttun[type="button"]', function (event) {

    if (event.keyCode === 13) {
        event.preventDefault();
        const inputs = $(this).closest('form').find(':input:visible');
        inputs.eq(inputs.index(this, true) + 1).focus();
    }
});



//Gestione attributi avanzati fattura elettronica
$(document).on('click', '.js_xml_attributes', function (event) {
    var tr = $(this).closest('tr');
    var values = $('.js_documenti_contabilita_articoli_attributi_sdi', tr).val();
    console.log(values);
    //alert(values);
    if (values) {

        var values_json = JSON.parse(atob(values));
    } else {
        values_json = [];
    }


    openXmlAttributesPopup(JSON.parse(atob($(this).data('attributes'))), values_json, tr);
});
