$(document).ready(function () {
    'use strict';
    $('table.js_tickets_table').on('draw.dt', function () {
        $(function () {
            $('tr:has(td:contains("In Progress"))').addClass("warning");
            $('tr:has(td:contains("Open"))').addClass("info");
            $('tr:has(td:contains("Waiting answer"))').attr('style', 'background-color: #c7a4ff !important;');
            $('tr:has(td:contains("On Hold"))').addClass("danger");
            $('tr:has(td:contains("Closed"))').addClass("success");
        });
    });


});