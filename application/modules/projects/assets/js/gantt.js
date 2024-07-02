function initGantt() {

    $(".gantt").gantt({
        source: base_url + "firecrm/gantt/get_gantt_data/" + $(".gantt").data('projects_id'),
        itemsPerPage: 10,
        navigate: 'scroll',
        onAddClick: function (dt, rowId) {

        },
        onRender: function () {
            $('.gantt .leftPanel .name .fn-label:empty').parents('.name').css('background', 'initial');
        },
        onItemClick: function (data) {


            if (typeof (data.tasks_id) != 'undefined') {

                loadModal(base_url + 'get_ajax/layout_modal/task-detail/' + data.tasks_id + '?_size=large');
            }
        },
    });

    // $(".gantt").popover({
    //     selector: ".bar",
    //     title: function _getItemText() {
    //         return this.textContent;
    //     },
    //     container: '.gantt',
    //     content: "Here's some useful information.",
    //     trigger: "hover",
    //     placement: "auto right"
    // });

    prettyPrint();

}

$(function () {
    "use strict";

    var tabToggles = $('ul > li > a[data-toggle="tab"]');
    tabToggles.on('shown.bs.tab', function (e) {
        initGantt();
    });
    initGantt();

});