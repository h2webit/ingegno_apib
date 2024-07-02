$(document).ready(function () {
    'use strict';
    $('table.js_expenses_table').on('draw.dt', function () {
        $(function () {
            $('tr:has(td:contains("To be paid"))').addClass("danger");
            $('tr:has(td:contains("Paid"))').addClass("success");
        });
    });
});