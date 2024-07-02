
$(() => {
    'use strict';
    var delay = 300000;
    var working_on = false;
    var running_check = false;
    var send_alarm = function () {
        running_check = false;
        check_working();
        var url = base_url + 'firecrm/main/send_monitor_alert_not_working_on';
        $.ajax({
            url: url,
            dataType: 'json',
            complete: function () {
                loading(false);
            },
            success: function (msg) {


            },
            error: function (xhr, ajaxOptions, thrownError) {
                var errorContainerID = 'ajax-error-container';
                var errorContainer = $('#' + errorContainerID);

                if (errorContainer.size() === 0) {
                    errorContainer = $('<div/>').attr('id', errorContainerID).css({
                        "z-index": 99999999,
                        "background-color": '#fff'
                    });
                    $('body').prepend(errorContainer);
                }

                console.log("Errore ajax:" + xhr.responseText);
            }
        });

    }
    var send_alarm_delayed = function () {
        if (running_check == false) {
            running_check = true;



            setTimeout(function () {
                if (!working_on) {
                    //send_alarm();
                    check_working(true);
                }
            }, delay);

        }
    };
    var check_working = function (immediate_alarm) {

        var now = new Date().getHours();
        if (base_url == "https://crm.h2web.it/" && (now >= 9 && now <= 18) && !(now > 13 && now < 14)) {

            var url = base_url + 'firecrm/main/check_working_on';
            loading(true);
            $.ajax({
                url: url,
                dataType: 'json',
                complete: function () {
                    loading(false);
                },
                success: function (msg) {
                    if (msg.working == true) {
                        working_on = true;
                        setTimeout(function () { check_working(false); }, delay);
                    } else {
                        working_on = false;
                        if (immediate_alarm) {
                            send_alarm();
                        } else {
                            send_alarm_delayed();
                        }

                    }

                },
                error: function (xhr, ajaxOptions, thrownError) {

                    var errorContainerID = 'ajax-error-container';
                    var errorContainer = $('#' + errorContainerID);

                    if (errorContainer.size() === 0) {
                        errorContainer = $('<div/>').attr('id', errorContainerID).css({
                            "z-index": 99999999,
                            "background-color": '#fff'
                        });
                        $('body').prepend(errorContainer);
                    }

                    console.log("Errore ajax:" + xhr.responseText);
                }

            });
        } else {
            console.log("out time: " + now);
        }
    };

    check_working(false);


});

