"use strict";
var timer_interval;
//$(".navbar").append($(".js_topbar_timetracker"));
$(".nav_container_custom .left .navbar-left .navbar-custom-menu").prepend($(".js_topbar_timetracker"));
$(".navbar .js_topbar_timetracker").not(":first").remove();

var minutesLabel = document.getElementById("minutes");
var secondsLabel = document.getElementById("seconds");
var hoursLabel = document.getElementById("hours");

if ($(".js_topbar_timetracker").data("interval")) {
    clearInterval(timer_interval);
    var totalSeconds = pad($(".js_topbar_timetracker").data("interval"));
    timer_interval = setInterval(setTime, 1000);
}

function setTime() {
    ++totalSeconds;
    secondsLabel.innerHTML = pad(totalSeconds % 60);
    minutesLabel.innerHTML = pad(parseInt(totalSeconds / 60) % 60);
    hoursLabel.innerHTML = pad(parseInt(totalSeconds / 60 / 60));
}

function pad(val) {
    var valString = val + "";
    if (valString.length < 2) {
        return "0" + valString;
    } else {
        return valString;
    }
}

$("body").on("click", ".js_play_task", function (e) {
    e.preventDefault(); // Prevent follow links
    e.stopPropagation(); // Prevent propagation on parent DOM elements
    e.stopImmediatePropagation(); // Prevent other handlers to be fired

    var task_id = $(".js_top_tasks_select").val();

    var url = base_url + "firecrm/main/task_working_on/1/" + task_id;
    loading(true);
    $.ajax({
        url: url,
        dataType: "json",
        complete: function () {
            loading(false);
        },
        success: function (msg) {
            handleSuccess(msg);

            //Redraw timetracker_topbar
            console.log(msg.timetracker_topbar_html);

            $(".js_topbar_timetracker").remove();
            $(".navbar").append($(msg.timetracker_topbar_html));
            // $('.navbar .js_topbar_timetracker').not(':first').remove();
        },
        error: function (xhr, ajaxOptions, thrownError) {
            var errorContainerID = "ajax-error-container";
            var errorContainer = $("#" + errorContainerID);

            if (errorContainer.size() === 0) {
                errorContainer = $("<div/>").attr("id", errorContainerID).css({
                    "z-index": 99999999,
                    "background-color": "#fff",
                });
                $("body").prepend(errorContainer);
            }

            console.log("Errore ajax:" + xhr.responseText);
        },
    });
});

setTimeout(function () {
    $(window).trigger("resize");
}, 2000);
