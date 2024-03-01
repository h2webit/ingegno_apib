<?php

$this->load->model('openapi-integration/openapi');
?>


<div style="background-color: #f2f2f2; padding: 10px; margin-top: 10px;">
    <p style="font-weight:bold">Come si usa?</p>
    <?php echo $layout_data_detail['openapi_servizi_code_sample']; ?>
</div>

<hr>


<form id="dataForm">
    <?php add_csrf(); ?>
    <div class="form-group">
        <label for="postData">Post Data (formato JSON)</label>
        <textarea class="form-control" name="postData" id="postData" rows="5" cols="50"></textarea>
    </div>
    <div class="form-group">
        <label for="getData">Get Data (formato String, viene appeso all'url, serve su alcune api come cerca p.iva, targa
            etc).</label>
        <input type="text" class="form-control" name="getData" id="getData"></input>
        <small>Il valore verrà appeso all'url. Se la chiamata prevede piu valori in get separarli da "/"</small>
    </div>
    <button type="button" id="submit_form" onclick="sendData('responseBox', 'openapi_servizi_endpoint_production')"
        class="btn btn-primary">Test
        chiamata (Production)</button>
    <button type="button" onclick="sendData('responseBox', 'openapi_servizi_endpoint_sandbox')"
        class="btn btn-primary">Test
        chiamata (SandBox)</button>

    <div id="responseBox" style="background-color: #f2f2f2; padding: 10px; margin-top: 10px;"></div>

    <?php if (!empty($layout_data_detail['openapi_servizi_endpoint_richiesta'])): ?>
        <br />
        <div class="form-group formRichiesta">
            <label for="getData">Richiesta ID:</label>
            <input type="text" class="form-control" name="getDataRichiesta" id="getDataRichiesta"></input>
            <small>Il servizio in questione potrebbe generare una richiesta. Se ottenuto, inserire qui la richiesta id e
                chiedere il risultato.</small>
        </div>

        <button type="button" onclick="sendData('responseBoxRichiesta', 'openapi_servizi_endpoint_richiesta')"
            class="btn btn-primary">Test
            richiesta (Production)</button>
        <button type="button" onclick="sendData('responseBoxRichiesta', 'openapi_servizi_endpoint_richiesta_sandbox')"
            class="btn btn-primary">Test
            richiesta (SandBox)</button>

        <div id="responseBoxRichiesta" style="background-color: #f2f2f2; padding: 10px; margin-top: 10px;"></div>

    <?php endif; ?>

</form>

<script>
    function sendData(responseBoxId, endpoint) {
        var token = JSON.parse(atob($('body').data('csrf')));
        var token_name = token.name;
        var token_hash = token.hash;

        var postData = document.getElementById("postData").value;
        var getData = document.getElementById("getData").value;

        <?php if (!empty($layout_data_detail['openapi_servizi_endpoint_richiesta'])): ?>
            var getDataRichiesta = document.getElementById("getDataRichiesta").value;
            if (getDataRichiesta) {
                postData = "";
                getData = getDataRichiesta;
            }
        <?php endif; ?>


        var xhr = new XMLHttpRequest();
        xhr.open("POST", base_url + "openapi-integration/main/chiamata_servizio_openapi/<?php echo $value_id; ?>", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.setRequestHeader(token_name, token_hash); // Add token to request headers
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                // Handle the response here
                var responseBox = document.getElementById(responseBoxId);
                responseBox.innerHTML = JSON.stringify(JSON.parse(xhr.responseText), null, 2);
            }
        };
        xhr.send("postData=" + encodeURIComponent(postData) + "&getData=" + encodeURIComponent(getData) + "&" + token_name + "=" + token_hash + "&endpoint=" + encodeURIComponent(endpoint));

    }

</script>