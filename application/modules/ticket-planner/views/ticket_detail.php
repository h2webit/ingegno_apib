<div class="row">
    <div class="col-sm-6">
        <h4 class="text-left" style="font-weight: bold; margin: 0px">
            <?php echo $tickets_subject; ?>
        </h4>
    </div>

    <div class="col-sm-6 text-right">
        <span class="badge bg-primary">
            <?php echo $tickets_category_value; ?>
        </span>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <p style='white-space: pre-line;'>
            <?php echo $tickets_message; ?>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <strong>
            Allegati:&nbsp;
            <?php if ($tickets_status !== '5'): ?>
                <button type="button" class="btn btn-info btn-xs btn-attachments-modal"
                    data-target="#ticket<?php echo $tickets_id; ?>_attachments_modal">Carica <i
                        class="fas fa-upload fa-fw"></i></button>
            <?php endif; ?>
        </strong>
        <br />
        <?php
        if (!empty($tickets_attachments)) {
            $files = json_decode($tickets_attachments, true);

            foreach ($files as $file) {
                echo anchor($this->ticket->base_endpoint . "uploads/" . $file['path_local'], '<i class="fas fa-paperclip fa-fw"></i>' . $file['original_filename'], ['target' => '_blank']) . '<br/>';
            }
        }
        ?>

        <?php echo $this->load->module_view('ticket-planner', 'views/ticket_attachments_modal', ['ticket_id' => $tickets_id, 'tickets_attachments' => $tickets_attachments], true); ?>
    </div>
</div>

<!-- Ticket estimated -->
<?php if (($tickets_estimated_billable > 0 || $tickets_estimated_price > 0)): ?>
    <div class="row">
        <div class="col-sm-12">
            <hr />
        </div>
        <div class="col-sm-12">
            <p>Questo ticket ha ricevuto una quotazione. Si prega di confermare per procedere</p>
        </div>
        <div class="col-sm-3 col-sm-offset-2"
            style="font-size:16px; background-color: #279ad1; color: #ffffff;padding: 10px; text-align:center">
            <p>Quotazione a ore</p>
            <p>
                <strong>
                    <?php echo $tickets_estimated_billable; ?> ore stimate
                </strong>
                <br />
            </p>
            <p>
                <?php if ($tickets_estimated_type_confirm == 2): ?>
                    ✅ Accettato
                <?php elseif ($tickets_estimated_type_confirm == 4): ?>
                    ❌ Rifiutato
                <?php elseif ($tickets_estimated_type_confirm == 3): ?>
                <?php elseif ($tickets_estimated_billable > 0): ?>
                    <a href="" class="js_accept" data-estimated-type-confirm="2"
                        style="color:#72f303; font-weight:bold">Accetta</a>
                    |
                    <a href="" class="js_accept" data-estimated-type-confirm="4"
                        style="color:#f60000; font-weight:bold">Rifiuta</a>
                <?php endif; ?>
            </p>
        </div>

        <div class="col-sm-3 col-sm-offset-1"
            style="font-size:16px; background-color: #279ad1; color: #ffffff;padding: 10px; text-align:center">
            <p>Quotazione una tantum</p>

            <p>
                <strong>
                    €
                    <?php echo $tickets_estimated_price; ?>
                </strong>
            </p>
            <p>
                <?php if ($tickets_estimated_type_confirm == 3): ?>
                    ✅ Accettato
                <?php elseif ($tickets_estimated_type_confirm == 4): ?>
                    ❌ Rifiutato
                <?php elseif ($tickets_estimated_type_confirm == 2): ?>
                <?php elseif ($tickets_estimated_price > 0): ?>
                    <a href="" class="js_accept" data-estimated-type-confirm="3"
                        style="color:#72f303; font-weight:bold">Accetta</a>
                    |
                    <a href="" class="js_accept" data-estimated-type-confirm="4"
                        style="color:#f60000; font-weight:bold">Rifiuta</a>
                <?php endif; ?>
            </p>
        </div>

        <div class="col-sm-12">
            <hr />
            <p>
                <i>I ticket in "Attesa di risposta" verranno chiusi automaticamente entro 5 giorni dall'apertura in caso di
                    mancata risposta/accettazione.</i>
            </p>
        </div>

    </div>

<?php endif; ?>

<div class="row">
    <div class='col-sm-12'>
        <hr>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <p style="font-size: 24px;text-align:center;vertical-align:middle;" class="messages-loader"><i
                class="fas fa-circle-notch fa-spin"></i> Caricamento...</p>

        <div class="chat-container hide">
            <ul class="chats"></ul>
        </div>

        <div class="input-container">
            <form id="new_ticket_comment" method="post">
                <textarea placeholder="Scrivi qui il tuo messaggio" name="message"
                    style="height: 200px;max-height: 500px;resize: vertical;" required></textarea>

                <div class="clearfix" style="margin-top: 10px">
                    <?php if ($tickets_status !== '5'): ?>
                        <a href="<?php echo base_url("ticket-planner/db_ajax/change_value/tickets/{$tickets_id}/tickets_status/5"); ?>"
                            class="btn btn-sm btn-success js_link_ajax">
                            <i class="fas fa-times"></i>
                            <?php e('Chiudi ticket'); ?>
                        </a>
                    <?php /* else: ?>
              <a href="<?php echo base_url("ticket-planner/db_ajax/change_value/tickets/{$tickets_id}/tickets_status/1"); ?>"
                  class="btn btn-sm btn-info js_link_ajax">
                  <i class="fas fa-pen-alt"></i>
                  <?php e('Riapri ticket'); ?>
              </a>
          <?php */endif; ?>

                    <button type="submit" class="pull-right btn btn-sm btn-info">Invia messaggio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        $('.messages-loader').show();
        $('ul.chats').html('')

        const ticket_status = '<?php echo $tickets_status; ?>';
        const ticket_id = '<?php echo $tickets_id; ?>';

        // Quotazione

        $('.js_accept').on('click', function (e) {
            e.preventDefault();

            $('.js_progress').show();
            var estimated_type_confirm = $(this).data('estimated-type-confirm');

            $.ajax({
                url: base_url + 'ticket-planner/db_ajax/accept_quote/' + ticket_id + '/' + estimated_type_confirm,
                type: 'GET',
                dataType: 'json',
                data: {},
                async: false,
                success: function (response) {
                    if (response.status == '0') {
                        alert(response.txt);
                        return false;
                    } else if (response.status == '1') {
                        $("li.js_ticket[data-ticket_id='" + ticket_id + "']").trigger('click');
                    }

                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            })

        })



        $('.btn-attachments-modal').on('click', function (e) {
            e.preventDefault();

            var modal_id = $(this).data('target');

            $(modal_id).modal({
                keyboard: false,
                backdrop: 'static'
            })
        })

        $("#new_ticket_comment").submit(function (event) {
            event.preventDefault(); //prevent default action 

            var form_data = $(this).serializeArray(); //Encode form elements for submission

            // Se il ticket era già stato chiuso e viene commentato, chiedo se si vuole riaprire il ticket....
            // questo perchè molte volte i clienti commentano i ticket senza però riaprirli (nel caso serva riaprirli)

            // 2023-09-29 - michael - cambio di rotta. avviso solo l'utente che sta commentando un ticket chiuso e che quindi le risposte potrebbero non essere monitorate.

            if (ticket_status == '5') {
                alert("ATTENZIONE: Stai commentando un ticket già chiuso. Nel caso di anomalie/bug/richieste inerenti a questo ticket, ad aprire un nuovo ticket specifico.");
            }

            <?php if (false): ?>
                if (ticket_status == '5' && confirm('Stai per commentare un ticket già chiuso. Vuoi riaprirlo?')) {
                    $.ajax({
                        url: base_url + 'ticket-planner/db_ajax/change_value/tickets/<?php echo $tickets_id; ?>/tickets_status/1',
                        type: 'GET',
                        dataType: 'json',
                        data: {},
                        async: false,
                        success: function (response) {
                            if (response.status == '0') {
                                alert(response.txt);
                                return false;
                            }
                        },
                        error: function (jqXHR, textStatus, errorThrown) {
                            console.log(jqXHR);
                            console.log(textStatus);
                            console.log(errorThrown);
                        }
                    })
                }
            <?php endif; ?>

            $.ajax({
                url: base_url + 'ticket-planner/db_ajax/new_chat_message/<?php echo $tickets_id; ?>',
                type: "POST",
                dataType: 'json',
                data: {
                    message: form_data[0]['value'],
                    [token_name]: token_hash,
                },
                async: false,
                success: function (response) {
                    if (response.status == '0') {
                        alert(response.txt);
                        return false;
                    }

                    $('[name="message"]').val('');

                    var message = '<li style="border-bottom: 1px solid #eee;">';
                    message += '  <strong class="user">Cliente</strong>';
                    message += '  <span class="direct-chat-timestamp pull-right datetime">' + moment(response.txt.tickets_messages_creation_date).format('DD/MM/YYYY HH:mm') + '</span> ';
                    message += '  <div class="text">';
                    message += '    <p>' + response.txt.tickets_messages_text + '</p>';
                    message += '  </div> ';
                    message += '</li>';

                    $('ul.chats').prepend(message);
                    $('.chat-container').removeClass('hide');
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.log(jqXHR);
                    console.log(textStatus);
                    console.log(errorThrown);
                }
            });
        });

        $.ajax({
            url: base_url + 'ticket-planner/db_ajax/get_ticket_messages/<?php echo $tickets_id; ?>',
            type: "POST",
            dataType: 'json',
            data: {
                [token_name]: token_hash,
            },
            async: false,
            success: function (res) {
                $('.messages-loader').hide();

                if (res.status == '0') {
                    $('ul.chats').html('<li><div class="alert alert-danger">Si è verificato un errore.</div></li>');
                    $('.chat-container').removeClass('hide');

                    return;
                }

                if (res.txt.length > 0) {
                    $('ul.chats').html(res.txt);
                    $('.chat-container').removeClass('hide');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR);
                console.log(textStatus);
                console.log(errorThrown);
            }
        });
    });
</script>