$(document).ready(function () {
    'use strict';
    $('table.js_payments_table').on('draw.dt', function () {
        $(function () {
            $('[data-field_name="payments_paid"]').closest('tr').addClass("danger");
            $('[data-field_name="payments_invoice_sent"]').filter(':checked').closest('tr').removeClass('danger').addClass("warning");
            $('[data-field_name="payments_paid"]').filter(':checked').closest('tr').removeClass('warning danger').addClass("success");
            $('[data-field_name="payments_canceled"]').filter(':checked').closest('tr').removeClass('warning danger').addClass("bg-gray");
        });
    });

});