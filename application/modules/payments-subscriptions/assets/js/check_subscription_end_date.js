$(document).ready(function () {
    'use strict';
    $('table.js_subscriptions_table').on('draw.dt', function () {
        $('tr', $(this)).each(function () {
            var this_row = $(this);
            $('.js_sub_end_date', this_row).filter(function () {
                var this_element = $(this);
                var end_date = this_element.text();
                var data_moment = moment(end_date, "DD/MM/YYYY");
                var data_now = moment();

                if (data_moment < data_now) {
                    this_row.removeClass('success').addClass("danger");
                } else {
                    this_row.addClass("success");
                }
            })
        });
    });
});