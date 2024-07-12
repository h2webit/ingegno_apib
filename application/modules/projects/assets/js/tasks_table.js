$(document).ready(function () {
    'use strict';
    $('table').on('draw.dt', function () {
        $(function () {
            $('tr:has(td:contains("In lavorazione"))').addClass("warning");
            $('tr:has(td:contains("Da fare"))').addClass("info");
            $('tr:has(td:contains("Attesa di risposta"))').attr('style', 'background-color: #c7a4ff !important;');
            $('tr:has(td:contains("Attesa di brief"))').attr('style', 'background-color: #fb923c !important;');
            $('tr:has(td:contains("Annullata"))').addClass("bg-gray");
            $('tr:has(td:contains("Chiusa"))').addClass("success");
            $('tr:has(td:contains("In consegna"))').addClass("red");
        });
    });
});