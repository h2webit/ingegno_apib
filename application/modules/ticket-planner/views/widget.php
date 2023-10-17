<?php $this->layout->addModuleStylesheet('ticket-planner', 'css/widget.css'); ?>
<?php $this->layout->addModuleJavascript('ticket-planner', 'js/html2canvas.min.js'); ?>




<div id="widget" class="widget slide_anim">
    <div id="widget_button" class="widget_button">
        <i class="fas fa-headset" style="margin-right:5px"></i> Assistenza
    </div>
    <div class="widget_body">
        <div class="widget_content ">
            <h4>Vuoi aprire un ticket?</h4>

            <form id="form_fast_ticket" class="formAjax" role=" form" method="post"
                action="<?php echo base_url('ticket-planner/main/createFastTicket'); ?>" enctype="multipart/form-data">

                <?php
                add_csrf();
                ?>

                <input type="hidden" class="js_ticket_screenshot" name="tickets_web_screenshot[]" />
                <input type="hidden" class="js_current_url" name="current_url" />
                <input class="form-control" type="text" name="tickets_subject" placeholder="Scrivi l'argomento" />

                <textarea name="tickets_message" class="form-control" placeholder="Descrivi la richiesta..."></textarea>

                <select class="js_customer_projects_list hide select2" name="tickets_project_id"></select>

                <label for="">Categoria <span class="text-danger">*</span></label>
                <select class="js_ticket_categories hide select2" name="tickets_category"></select>

                <p class="js_widget_capture"><i class=" fas fa-camera"></i> Clicca per registrare uno screenshot</p>
                <p class="js_widget_capture_done"></p>
                <input class="btn btn-primary" type="submit" value="Invia Ticket" />

                <div class="msg_form_fast_ticket"></div>
            </form>
        </div>
    </div>
</div>

<script>
    $('#widget_button').click(function () {
        $('#widget').toggleClass('open_widget');
        loadProjects();
        loadCategories();

        // Get current url
        var currentUrl = window.location.href;
        $('.js_current_url').val(currentUrl)
    });

    // Html2Canvas 
    $('.js_widget_capture').click(function () {

        $('.js_widget_capture_done').html(" Registrazione in corso... Attendere...");
        html2canvas(document.querySelector("body")).then(canvas => {
            //document.getElementById('js_page_content').appendChild(canvas);
            var base64 = canvas.toDataURL();
            base64 = base64.replace("data:image/png;base64,", "");
            $('.js_ticket_screenshot').val(base64);
            $('.js_widget_capture_done').html("Screenshot salvato! Puoi inviare il ticket");
        });
    });

    function loadProjects() {
        $.ajax({
            method: 'post',
            url: base_url + "ticket-planner/main/get_customer_projects",
            dataType: "json",
            data: {
                [token_name]: token_hash,
            },
            success: function (ajax_response) {

                console.log(ajax_response);

                var select = $('.js_customer_projects_list');
                select.empty();

                if (ajax_response.status == '1' && ajax_response.data) {
                    var i = 0;
                    $.each(ajax_response.data, function (index, item) {
                        if (i == 0) {
                            select.append('<option value="' + item.projects_id + '" selected="selected">' + item.projects_name + '</option>')
                        } else {
                            select.append('<option value="' + item.projects_id + '">' + item.projects_name + '</option>')
                        }
                        i++;
                    });

                    select.toggleClass('hide');
                }
            }
        });
    }

    function loadCategories() {
        $.ajax({
            method: 'post',
            url: base_url + "ticket-planner/main/get_ticket_categories",
            dataType: "json",
            data: {
                [token_name]: token_hash,
            },
            success: function (ajax_response) {
                var select = $('.js_ticket_categories');

                select.empty();

                if (ajax_response.status == '1' && ajax_response.data) {
                    var i = 0;
                    $.each(ajax_response.data, function (index, item) {
                        // if (i == 0) {
                        //     select.append('<option value="' + item.tickets_category_id + '" selected="selected">' + item.tickets_category_value + '</option>')
                        // } else {
                        //
                        // }

                        select.append('<option value="' + item.tickets_category_id + '">' + item.tickets_category_value + '</option>')

                        i++;
                    });

                    select.prepend('<option value="" selected="selected"> --- </option>');

                    select.toggleClass('hide');
                }
            }
        });
    }
</script>