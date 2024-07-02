$(document).ready(function () {
    'use strict';
    $('table.js_table_projects').on('draw.dt', function () {
        $(function () {
            $('tr:has(td:contains("In progress"))').addClass("warning");
            $('tr:has(td:contains("To be scheduled"))').addClass("info");
            $('tr:has(td:contains("Finished"))').addClass("success");
        });
    });
});