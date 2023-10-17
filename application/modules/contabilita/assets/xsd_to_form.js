function openXmlAttributesPopup(attributes, values, tr) {
    $(".modal_content_custom_input .title").html("");
    $(".modal_content_custom_input .content_custom_input").html("");
    $('.js-pulsanteSalvaAttributi').data('tr', tr);
    // console.log(values);
    // alert(1);
    //TODO: tr è la riga sulla quale ho cliccato il pulsante attributi... devo portarmelo dietro per poi alla fine permettermi di popolare il campo hidden corretto...

    //Titolo
    //TODO: campo desc hidden che cliccando su un punto di domanda compare. Ora non va per apici e doppie virgolette, va fatto escape.
    //const sectionTitle = `<h4 style="cursor:pointer;" onclick="javascript:alert('${attributes.desc}');">${attributes.title}</h4><span class="help_text_custom_attributi">${attributes.help}</span>`;
    const sectionTitle = `<h4 style="cursor:pointer;">${attributes.title}</h4><span class="help_text_custom_attributi">${attributes.help}</span>`;
    $(".modal_content_custom_input .title").prepend(sectionTitle);

    buildField(attributes.figli, values, 15, false, attributes.id);

    $("#modal_xml_converted").modal("show");
}
//Funzione che cerca tra i valori impostati, se esiste element e ne ritorna il valore impostato per popolare in automatico il form in caso di modifica
function findValue(id, element, values) {
    var found_val = false;
    values.forEach((value, index) => {

        if (value.name == `${id}[${element.name}]`) {
            found_val = value.value;
            return false;
        } else {
            // alert(value.name);
            // alert(`${id}[${element.name}]`);
        }
    });
    return found_val;
}
function buildField(block, values, paddingValue = 0, isFiglio = false, id = null) {



    block.forEach((element, index) => {
        var found_val = findValue(id, element, values);
        if (found_val) {
            element.value = found_val;
        }

        //Creo contenitore elemento
        const padding_left = paddingValue + "px";

        let bgColor = "";
        if (isFiglio) {
            bgColor = `rgba(203,213,225, .${paddingValue + 50})`;
        } else {
            bgColor = `rgba(203,213,225, .${paddingValue + 5})`;
        }

        const container = $(`<div class="form-group" style="padding-left: ${padding_left};"></div>`);

        const container_content = `<label class="control-label label_custom_input" style="cursor:pointer;" onclick="javascript:alert('${element.desc}');">${element.label ? element.label : element.name
            }</label><span class="help_text_custom_attributi">${element.help ? element.help : ""}</span><br/><input name="${id}[${element.name}]" type="text" value="${element.value ? element.value : ""}" placeholder="${element.placeholder ? element.placeholder : ""}" pattern="${element.pattern
            }" class="form-control"/></div>`;

        /* $(container_content).appendTo(container);
        $(".content_custom_input").append(container); */

        if (isFiglio) {
            const parent = $(".modal_content_custom_input .content_custom_input .form-group").last();
            $(container_content).appendTo(container);
            parent.append(container);
        } else {
            $(container_content).appendTo(container);
            $(".content_custom_input").append(container);
        }

        if (element.figli && element.figli.length > 0) {
            buildField(element.figli, paddingValue + 15, true, element.id);
        }
    });
}

function saveAttributiAvanzati() {
    var form = $('.form_custom_input');

    var dati = JSON.stringify($(form).serializeArray());



    var tr = $('.js-pulsanteSalvaAttributi').data('tr');
    $('.js_documenti_contabilita_articoli_attributi_sdi', tr).val(btoa(dati));
    $("#modal_xml_converted").modal("hide");
}
