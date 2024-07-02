var tasks_items, description_text, keep_container;

function newKeepItem(item) {

    var cloned_row = $('.js_keep_row_main').clone();
    cloned_row.removeClass('js_keep_row_main');
    cloned_row.removeClass('dnone');

    var task_item_checkbox = $('.js_task_item_checkbox', cloned_row);
    task_item_checkbox.attr('name', task_item_checkbox.data('name'));

    var task_item_text = $('.js_task_item_text', cloned_row);
    task_item_text.attr('name', task_item_text.data('name'));

    cloned_row.show();

    if (typeof item !== 'undefined') {

        if (item.task_item_checkbox == '1') {
            task_item_text.addClass('js_task_item_text_done');
            task_item_checkbox.attr('checked', 'checked');
        }
        task_item_text.html(item.task_item_text);

        if ($('.js_keep_row:contains(' + item.task_item_text + ')').length == 0) {
            keep_container.append(cloned_row[0].outerHTML).insertAfter(tasks_items);
        } else {
            console.log('item already present');
        }
    } else {
        keep_container.append(cloned_row[0].outerHTML).insertAfter(tasks_items);
    }

    $('.js_task_item_text:last', keep_container).focus();

    autosize($('textarea'));

}

function checkDoubleOrEmptyItems() {
    $('.js_task_item_text').not(':last').each(function () {
        if ($(this).val() == '') {
            $(this).parent().remove();
        }
    });
}

function throwChangedItems() {
    var json_data = getJsonData();
    tasks_items.val(json_data);
}

function getJsonData() {
    var items = [];
    $.each($('.js_keep_row').not('.js_keep_row_main'), function (i, elem) {

        var item = {};
        item.task_item_checkbox = ($('.js_task_item_checkbox', elem).is(":checked")) ? '1' : '0';
        item.task_item_text = $('.js_task_item_text', elem).val();

        if (item.task_item_text) {
            items.push(item);
        }
    });
    return JSON.stringify(items);
}

function checkLastLine() {
    if ($('.js_task_item_text:last').val() == '') {
        $('.js_keep_row:last').remove();
        $('.js_task_item_text:last').focus();
    }
}
$(document).on('keydown', '.js_keep_container :input:not(textarea):not(:submit)', function (e) {

});

$(document).ready(function () {
    'use strict';
    tasks_items = $('[name="tasks_items"]');
    description_text = tasks_items.val();
    keep_container = $('.js_keep_container');

    tasks_items.hide();

    $(document).on('keydown', '.js_keep_container :input:not(:submit)', function (e) {
        if (e.key == 'Enter') { //Enter
            e.preventDefault();
            e.stopPropagation();
            newKeepItem();
            throwChangedItems();

            return false;
        } else if (e.keyCode == 8) { //Backspace
            checkLastLine();
            throwChangedItems();
        } else {
            throwChangedItems();
        }

    });
    keep_container.on('click', '.js_task_item_checkbox', function (e) {
        if ($(this).is(':checked')) {
            $('.js_task_item_text', $(this).parent()).addClass('js_task_item_text_done');
        } else {
            $('.js_task_item_text', $(this).parent()).removeClass('js_task_item_text_done');
        }
        throwChangedItems();
    });

    keep_container.on('click', '.js_delete_task_item', function (e) {
        $(this).parent().remove();
        throwChangedItems();
    });

    try {
        var description_json = JSON.parse(description_text);
    } catch (err) {
        var description_json = [];
    }

    for (var i in description_json) {
        newKeepItem(description_json[i]);
    }
    newKeepItem();
    setTimeout(function () { window.dispatchEvent(new Event('resize')); }, 500);

});