

$(document).ready(function () {

    $('#collapse-tickets').on('click', '.js_ticket', function () {
        $('.js_progress').show();
        var ticket_id = $(this).data('ticket_id');
        $.ajax({
            'url': base_url + 'ticket-planner/main/get_ticket_html/' + ticket_id,
            success: function (html) {
                $('.js-ticket_details').html(html);
                $('.js_progress').hide();
            }
        });
    });

    $('body').on('click', '.js_ticket_link', function () {

        var ticket_id = $(this).data('ticket_id');
        $("li.js_ticket[data-ticket_id='" + ticket_id + "']").trigger('click');
    });
});
