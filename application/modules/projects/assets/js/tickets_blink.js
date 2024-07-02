$(document).ready(function () {
    'use strict';
    var tickets_tab_element = $('ul.nav-tabs').find('li').find('a:contains("Tickets")').first();
    var tickets_tab_text = tickets_tab_element.text();
    var tickets_tab_new_text = tickets_tab_text + ' <span class="blink_me"><i class="fas fa-exclamation-triangle text-danger"></i></span>';

    tickets_tab_element.html(tickets_tab_new_text);
});