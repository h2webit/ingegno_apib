$(document).ready(function () {
    'use strict';
    var fields = [
        'users_show_in_social',
        'users_cost_per_hour',
        'users_show_in_kanban',
        'users_show_in_calendar',
        'users_active',
    ];

    $.each(fields, function (index, field_name) {
        $('[name="' + field_name + '"]').closest('.form-group').remove();
    });
});