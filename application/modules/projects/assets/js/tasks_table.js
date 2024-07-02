$(document).ready(function () {
    'use strict';
    $('table').on('draw.dt', function () {
        $(function () {
            $('tr:has(td:contains("Working On"))').addClass("warning");
            $('tr:has(td:contains("To do"))').addClass("info");
            $('tr:has(td:contains("Waiting reply..."))').attr('style', 'background-color: #c7a4ff !important;');
            $('tr:has(td:contains("Canceled"))').addClass("bg-gray");
            $('tr:has(td:contains("Done"))').addClass("success");
            $('tr:has(td:contains("Ready"))').addClass("red");
        });
    });
});