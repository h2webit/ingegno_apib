$(document).ready(function () {
    'use strict';
    $('#search_form').remove();
    $('#header_notification_bar, .user-body, .user-footer>div>.js_open_modal').remove();
    $('.menu_settings').hide();
    $('.sidebar-toggle').trigger('click');
});