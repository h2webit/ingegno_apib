$(document).ready(function () {
    'use strict';
    $('table.js_billing_hours_table').on('draw.dt', function () {
        $(function () {
            $('tr:has(td:contains("Debited hours"))').addClass("danger");
            $('tr:has(td:contains("Paid hours"))').addClass("success");
        });
    });
});