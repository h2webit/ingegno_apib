$(document).ready(function () {
    'use strict';
    $('#collapse-tickets').on('click', '.js_ticket', function () {
        var ticket_id = $(this).data('ticket_id');
        $.ajax({
            'url': base_url + 'firecrm/tickets/get_ticket_html/' + ticket_id,
            success: function (html) {
                $('.js-ticket_details').html(html);
            }
        });
    });
});
