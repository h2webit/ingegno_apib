<div class="box-body layout_box form">

    <div class="box-body">
        <div class="row sortableForm">

            <div class="formColumn js_container_field col-md-12">
                <p style="margin-bottom:20px">Con questo servizio puoi creare l'anagrafica di un nuovo cliente o
                    fornitore, comodamente cercando la p.iva oppure il codice fiscale.</p>
                <div class="form-group"><label class="control-label">Inserisci la P.IVA o il CF:</label><input
                        type="text" id="js_cerca_piva_input" name="piva_cf" class="form-control" placeholder=""
                        maxlength="11">
                </div>
            </div>
        </div>
        <div class="row ">
            <div class="col-md-12">
                <div id="msg_form_cercapivaform" class=""></div>
            </div>
        </div>
    </div>

    <div class="form-actions" style="text-align: center">
        <button type="button" id="js_cerca_piva" class="btn btn-primary">Cerca € 0,08</button>
        <button type="button" id="js_crea_anagrafica" class="btn btn-success" style="display:none">Crea nuova
            anagrafica</button>
    </div>



    <script>
        var ricerca_data;

        $('body').on('click', '#js_cerca_piva', function (e) {
            e.stopImmediatePropagation();

            var getData = $('#js_cerca_piva_input').val();

            var responseBoxId = "msg_form_cercapivaform";
            var token = JSON.parse(atob($('body').data('csrf')));
            var token_name = token.name;
            var token_hash = token.hash;

            // var postData = document.getElementById("postData").value;
            // var getData = document.getElementById("getData").value;

            var xhr = new XMLHttpRequest();
            xhr.open("POST", base_url + "openapi-integration/main/chiamata_servizio_openapi/1", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.setRequestHeader(token_name, token_hash); // Add token to request headers

            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    // Handle the response here
                    var responseBox = document.getElementById(responseBoxId);
                    var jsonResponse = JSON.parse(xhr.responseText);

                    if (jsonResponse.success && jsonResponse.data) {
                        ricerca_data = jsonResponse.data;
                        responseBox.innerHTML = createList(jsonResponse.data);
                    } else {
                        responseBox.innerHTML = "Errore nel caricamento dei dati: " + jsonResponse.message;
                    }

                    $('#js_crea_anagrafica').show();
                }
            };

            xhr.send("getData=" + encodeURIComponent(getData) + "&" + token_name + "=" + token_hash);

        });

        // Funzione per creare una tabella HTML dai dati JSON
        function createList(data) {
            var html = '<ul>';

            data.forEach(function (item) {
                html += '<li style="list-style-type:none">';
                html += '<br><strong>Nome Azienda:</strong> ' + item.companyName;
                html += '<br><strong>Indirizzo:</strong> ' + formatAddress(item.address.registeredOffice);
                html += '<br><strong>Codice Fiscale:</strong> ' + item.taxCode;
                html += '<br><strong>Partita IVA:</strong> ' + item.vatCode;
                html += '<br><strong>Status Attività:</strong> ' + item.activityStatus;
                html += '<br><strong>Data Registrazione:</strong> ' + item.registrationDate;
                html += '<br><strong>Codice SDI:</strong> ' + item.sdiCode;
                html += '<br><strong>Data Timbro SDI:</strong> ' + new Date(item.sdiCodeTimestamp * 1000).toLocaleDateString("it-IT");
                html += '<br><strong>Data Creazione:</strong> ' + new Date(item.creationTimestamp * 1000).toLocaleDateString("it-IT");
                html += '<br><strong>Ultimo Aggiornamento:</strong> ' + new Date(item.lastUpdateTimestamp * 1000).toLocaleDateString("it-IT");
                html += '<br><strong>ID:</strong> ' + item.id;
                html += '</li>';
            });

            html += '</ul>';
            return html;
        }

        // Funzione per formattare l'indirizzo
        function formatAddress(address) {
            var addrString = address.toponym + ' ' + address.street + ' ' + address.streetNumber + ', ' + address.town + ', ' + address.province + ', ' + address.zipCode;
            return addrString;
        }

        // Crea anagrafica
        $('body').on('click', '#js_crea_anagrafica', function (e) {
            e.stopImmediatePropagation();

            $('#msg_form_cercapivaform').append('<br /><br />Attendere ...');

            $.ajax({
                method: 'post',
                async: true,
                url: base_url + "openapi-integration/main/crea_anagrafica",
                dataType: "json",
                data: {
                    data: ricerca_data,
                    [token_name]: token_hash
                },
                success: function (res) {
                    console.log(res);
                    if (res.success == false) {
                        $('#msg_form_cercapivaform').html("Errore: " + res.message);
                    } else {
                        // Redirect to customer detail
                        window.location.href = res.message;
                    }
                }
            });
        });
    </script>
</div>