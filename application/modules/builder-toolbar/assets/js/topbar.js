

$(function () {
    'use strict';

    var toolBarEnabled = false;
    var myArguments;


    jQuery(document).keydown(function (event) {
        // If Control or Command key is pressed and the S key is pressed
        // run save function. 83 is the key code for S.
        if ((event.ctrlKey || event.metaKey) && event.which == 68) {
            // Save Function
            event.preventDefault();

            if ($('#js_enable_dev').length) {

                if (toolBarEnabled == true) {
                    $('#builder_toolbar').hide();
                    toolBarEnabled = false;
                    localStorage.setItem('toolBarEnabled', 'false');
                } else {
                    $('#builder_toolbar').show();
                    toolBarEnabled = true;
                    localStorage.setItem('toolBarEnabled', 'true');
                    checkPermissions();
                }

            }
            return false;
        }
    }
    );

    if (localStorage.getItem("toolBarEnabled")) {
        toolBarEnabled = true;
        $('#builder_toolbar').show();
        checkPermissions();
    }

    $('body').on('click', '#js_enable_dev', function () {


        // var data_post = [];
        // data_post.push({ name: "builderProjectHash", value: builderProjectHash });

        $('#builder_toolbar').show();
        toolBarEnabled = true;
        localStorage.setItem('toolBarEnabled', 'true');


        // $.ajax(base_url + 'firegui/projectConnect/', {
        //     type: 'POST',
        //     data: data_post,
        //     dataType: 'json',

        //     success: function (data) {


        //         $('#builder_toolbar').show();
        //         var toolBarEnabled = true;
        //         localStorage.setItem('toolBarEnabled', 'true');
        //         localStorage.setItem('toolBarToken', data);



        //     },
        // });
    });

    // ********* Console buttons and events *************

    $('body').on('click', '.js_console_command', function () {
        $(this).next().toggleClass('hide');
    });
    $('body').on('click', '.js_show_code', function () {
        $(this).next().next().toggleClass('hide');
    });
    $('body').on('click', '.fakeClose', function () {
        $('.builder_console').toggleClass('hide');
    });

    $('body').on('click', '.fakeZoom', function () {
        if ($('.builder_console').hasClass('full_size')) {
            window.scrollTo(0, document.body.scrollHeight);
        } else {
            window.scrollTo(0, 0);
        }

        $('.builder_console').toggleClass('full_size');
    });

    // ********* Toolbar buttons *************



    $('body').on('change', '#js_toolbar_devtheme', function (e) {
        $.ajax({
            url: base_url + "builder-toolbar/builder/switch_devtheme/",
            success: function (data) {
                window.location.reload();
                if ($('#js_toolbar_devtheme').is(":checked")) {

                    window.location.reload();
                } else {

                }
            },
        });
    });

    $('body').on('change', '#js_toolbar_maintenance', function (e) {

        $.ajax({
            url: base_url + "builder-toolbar/builder/switch_maintenance/",
            success: function (data) {

                if ($('#js_toolbar_maintenance').is(":checked")) {
                    window.location.reload();
                } else {

                    e.preventDefault();
                    e.stopPropagation();
                    var data_post = [];
                    data_post.push({ name: token_name, value: token_hash });

                    loadModal(base_url + 'get_ajax/modal_form/changelog-form', data_post);
                }
            },
        });
    });

    $('body').on('click', '#js_toolbar_download_dump', function () {
        var sys_password = prompt("Please enter system password");
        if (sys_password != null) {
            window.location.href = base_url + 'builder-toolbar/builder/download_dump/' + sys_password;
        }
    });

    $('body').on('click', '#js_toolbar_download_zip', function () {
        var sys_password = prompt("Please enter system password");
        if (sys_password != null) {
            window.location.href = base_url + 'builder-toolbar/builder/download_zip/' + sys_password;
        }
    });

    $('body').on('click', '#js_toolbar_vblink', function () {
        var layout_id = $('#js_layout_content_wrapper').data('layout-id');
        //var token = localStorage.getItem('toolBarToken');
        window.open(base_url_builder + 'main/visual_builder/' + layout_id + '?hash=' + builderProjectHash, '_blank');
    });
    $('body').on('click', '#js_toolbar_vbframe', function () {
        var layout_id = $('#js_layout_content_wrapper').data('layout-id');
        openBuilderFrame(base_url_builder + 'main/visual_builder/' + layout_id + '?hash=' + builderProjectHash);
    });

    $('body').on('click', '#js_toolbar_events', function () {
        var layout_id = $('#js_layout_content_wrapper').data('layout-id');
        openBuilderFrame(base_url_builder + 'main/events_builder' + '?hash=' + builderProjectHash);
    });

    $('body').on('click', '#js_toolbar_entities', function () {
        var layout_id = $('#js_layout_content_wrapper').data('layout-id');
        openBuilderFrame(base_url_builder + 'main/new_entity' + '?hash=' + builderProjectHash);
    });

    $('body').on('click', '#js_toolbar_backup', function () {
        var layout_id = $('#js_layout_content_wrapper').data('layout-id');
        openBuilderFrame(base_url_builder + 'main/database_dumps' + '?hash=' + builderProjectHash);
    });

    $('body').on('click', '#js_toolbar_query', function () {
        var layout_id = $('#js_layout_content_wrapper').data('layout-id');
        openBuilderFrame(base_url_builder + 'main/query' + '?hash=' + builderProjectHash);
    });


    // Exit dev mode
    $('body').on('click', '#js_toolbar_exit', function () {
        localStorage.removeItem("toolBarEnabled");
        localStorage.removeItem('toolBarToken');
        $('#builder_toolbar').hide();
    });


    // Init components
    $('body').on('click', '.js_init_sortableform', function () {
        initBuilderForm();
    });


    $('body').on('click', '#js_toolbar_highlighter', function () {

        $('.js_layout').toggleClass('layout_highlight');
        $('.js_layout_box').toggleClass('box_highlight');
        $('.connectedSortable').toggleClass('row_highlight');
        $('.modal-content').toggleClass('box_highlight');
        $('.js_sidebar_menu_item').toggleClass('js_sidebar_highlight');

        $('.label_highlight').toggleClass('hide');
        $('.builder_toolbar_actions').toggleClass('hide');

        // Reset
        $('.formColumn').removeClass('formColumn_highlights');
        $('.builder_formcolumns_buttons').hide();

        //$(".sortableForm").sortable("disable");
        //$(".sortableMenu").sortable("disable");
        //$(".connectedSortable").sortable("disable");

        // Init
        initBuilderTools();
    });

    $('body').on('click', '#js_toolbar_console', function () {

        $('.builder_console').toggleClass('hide');
        window.scrollTo(0, document.body.scrollHeight);
    });

    // Buttons actions
    $('body').on('click', '.js_builder_toolbar_btn', function () {
        var layout_id = $('#js_layout_content_wrapper').data('layout-id');
        var action = $(this).data('action');
        var element_type = $(this).data('element-type');
        var element_ref = $(this).data('element-ref');

        const json = { "action": action, "type": element_type, "ref": element_ref }
        const string = JSON.stringify(json); // convert Object to a String
        const encodedString = btoa(string);
        //window.open(base_url_builder + 'main/visual_builder-toolbar/builder/'+layout_id+'/'+encodedString, '_blank');
        openBuilderFrame(base_url_builder + 'main/visual_builder/' + layout_id + '/' + encodedString + '?hash=' + builderProjectHash);
    });



});

// For resize boxes
function update_cols(layout_boxes_id, cols) {
    $.ajax({
        url: base_url + "builder-toolbar/builder/update_layout_box_cols/" + layout_boxes_id + "/" + cols,
        dataType: 'json',
        cache: false,
    });
}

// For resize fields
function update_field_cols(field_id, cols) {
    $.ajax({
        url: base_url + "builder-toolbar/builder/update_field_cols/" + field_id + "/" + cols,
        dataType: 'json',
        cache: false,
    });
}


/*
*
* -------------------  FORMS ------------------------
*
*/
function initBuilderForm() {

    console.log("Start form sortable");

    /* --- Fields toolbar buttons --- */
    /* Resize Forms fields */
    $('body').on('click', '.js_btn_fields_plus', function () {
        var my_container = $(this).closest('.js_container_field');
        cols = parseInt(my_container.data('cols'));
        var my_field_id = my_container.data("id");

        if (cols < 12) {
            new_cols = cols + 1;
            my_container.removeClass("col-md-" + cols);
            my_container.addClass("col-md-" + new_cols);
            my_container.data('cols', new_cols);
            update_field_cols(my_field_id, new_cols, my_container);
        }

        if (new_cols >= 3) {
            $('.form-group label', my_container).show();
        } else {
            $('.form-group label', my_container).hide();
        }
    });

    $('body').on('click', '.js_btn_fields_minus', function (e) {
        var my_container = $(this).closest('.js_container_field');
        cols = parseInt(my_container.data('cols'));
        var my_field_id = my_container.data("id");

        if (cols > 1) {
            new_cols = cols - 1;
            my_container.removeClass("col-md-" + cols);
            my_container.addClass("col-md-" + new_cols);
            my_container.data('cols', new_cols);
            update_field_cols(my_field_id, new_cols, my_container);
        }

        if (new_cols >= 3) {
            $('.form-group label', my_container).show();
        } else {
            $('.form-group label', my_container).hide();
        }
    });


    /* Remove Forms field */
    $('body').on('click', '.js_btn_fields_delete', function () {
        var my_container = $(this).closest('.js_container_field');
        var my_field_id = my_container.data("id");
        var my_form_id = my_container.data("form_id");
        $.ajax({
            url: base_url + "builder-toolbar/builder/remove_form_field/" + my_field_id + "/" + my_form_id,
            dataType: 'json',
            cache: false,
        });
        my_container.remove();
    });


    $('.formColumn').toggleClass('formColumn_highlights');
    $('.builder_formcolumns_buttons').show();
    $(".sortableForm").sortable({
        connectWith: '.sortableForm',
        cancel: null,

        /* That's fired first */
        start: function (event, ui) {
            myArguments = {};
            ui.placeholder.width(ui.item.width());
            ui.placeholder.height(ui.item.height() - 20);
        },
        /* That's fired second */
        remove: function (event, ui) {
            /* Get array of items in the list where we removed the item */
            myArguments = assembleData(this, myArguments);
        },
        /* That's fired thrird */
        receive: function (event, ui) {
            /* Get array of items where we added a new item */
            myArguments = assembleData(this, myArguments);
        },
        update: function (e, ui) {
            if (this === ui.item.parent()[0]) {
                /* In case the change occures in the same container */
                if (ui.sender == null) {
                    myArguments = assembleData(this, myArguments);
                }
            }
        },
        /* That's fired last */
        stop: function (event, ui) {

            var token = JSON.parse(atob($('body').data('csrf')));
            var token_name = token.name;
            var token_hash = token.hash;

            myArguments[token_name] = token_hash;
            /* Send JSON to the server */
            console.log("Send JSON to the server:<pre>" + JSON.stringify(myArguments) + "</pre>");

            var current_form_id = ui.item.data('form_id');

            $.ajax({
                url: base_url + "builder-toolbar/builder/update_form_fields_position/" + current_form_id + "/",
                type: 'post',
                dataType: 'json',
                data: myArguments,
                cache: false
            });
            console.log(myArguments);
        },
    });
    $('.formColumn').css('cursor', 'move');
}

function initBuilderTools() {

    /* Change title layout box */
    $('.js_layouts_boxes_title').each(function () {
        var title = $(this).text().trim();
        var layou_box_id = $(this).data('layou_box_id');
        $(this).replaceWith('<input data-layou_box_id="' + layou_box_id + '" class="form-control input-sm inline_input_text js_update_layout_box_title" type="text" name="title" value="' + title + '" />');
    });

    $('body').on('change', '.js_update_layout_box_title', function () {
        var my_layout_box_id = $(this).data("layou_box_id");
        var new_title = $(this).val();

        try {
            var token = JSON.parse(atob(datatable.data('csrf')));
            var token_name = token.name;
            var token_hash = token.hash;
        } catch (e) {
            var token = JSON.parse(atob($('body').data('csrf')));
            var token_name = token.name;
            var token_hash = token.hash;
        }

        $.ajax({
            url: base_url + "builder-toolbar/builder/update_layout_box_title/" + my_layout_box_id,
            type: 'post',
            data: { title: new_title, [token_name]: token_hash },
            dataType: 'json',
            cache: false,
        });
    });

    /* Move to another layout */
    $('body').on('click', '.js_btn_move_to_layout', function () {

        var new_layout_id = prompt("Please enter the new layout ID where you want to move the box", "");
        if (!new_layout_id) {
            return false;
        } else {
            var my_container = $(this).closest('.js_container_layout_box');
            var my_layout_box = $('.js_layout_box', my_container);
            var my_layout_box_id = my_layout_box.data("id");

            $.ajax({
                url: base_url + "builder-toolbar/builder/move_layout_box/" + my_layout_box_id + "/" + new_layout_id,
                dataType: 'json',
                cache: false,
            });
            my_container.remove();
        }

    });


    $('body').on('click', '.js_btn_delete', function () {
        var my_container = $(this).closest('.js_container_layout_box');
        var my_layout_box = $('.js_layout_box', my_container);
        var my_layout_box_id = my_layout_box.data("id");
        $.ajax({
            url: base_url + "builder-toolbar/builder/delete_layout_box/" + my_layout_box_id,
            dataType: 'json',
            cache: false,
        });
        my_container.remove();
    });


    /* Resize Boxes */
    $('body').on('click', '.js_btn_plus', function () {
        var my_container = $(this).closest('.js_container_layout_box');
        var my_layout_box = $('.js_layout_box', my_container);
        console.log(my_layout_box);
        cols = parseInt(my_container.data('cols'));
        var my_layout_box_id = my_layout_box.data("id");

        if (cols < 12) {
            new_cols = cols + 1;
            my_container.removeClass("col-md-" + cols);
            my_container.addClass("col-md-" + new_cols);
            my_container.data('cols', new_cols);
            update_cols(my_layout_box_id, new_cols, my_container);
        }

        if (new_cols >= 3) {
            $('.label_highlight', my_container).show();
        } else {
            $('.label_highlight', my_container).hide();
        }
    });

    $('body').on('click', '.js_btn_minus', function () {
        var my_container = $(this).closest('.js_container_layout_box');
        var my_layout_box = $('.js_layout_box', my_container);
        cols = parseInt(my_container.data('cols'));
        var my_layout_box_id = my_layout_box.data("id");

        if (cols > 1) {
            new_cols = cols - 1;
            my_container.removeClass("col-md-" + cols);
            my_container.addClass("col-md-" + new_cols);
            my_container.data('cols', new_cols);
            update_cols(my_layout_box_id, new_cols, my_container);
        }

        if (new_cols >= 3) {
            $('.label_highlight', my_container).show();
        } else {
            $('.label_highlight', my_container).hide();
        }
    });


    /* Sort Sidebar menu items */
    $(".sortableMenu").sortable({
        placeholder: 'ui-state-highlight',
        connectWith: '.sortableMenu',

        /* That's fired first */
        start: function (event, ui) {
            myArguments = {};
        },
        /* That's fired second */
        remove: function (event, ui) {
            /* Get array of items in the list where we removed the item */
            myArguments = assembleData(this, myArguments);
        },
        /* That's fired thrird */
        receive: function (event, ui) {
            /* Get array of items where we added a new item */
            myArguments = assembleData(this, myArguments);
        },
        update: function (e, ui) {
            if (this === ui.item.parent()[0]) {
                /* In case the change occures in the same container */
                if (ui.sender == null) {
                    myArguments = assembleData(this, myArguments);
                }
            }
        },
        /* That's fired last */
        stop: function (event, ui) {

            var token = JSON.parse(atob($('body').data('csrf')));
            var token_name = token.name;
            var token_hash = token.hash;

            myArguments[token_name] = token_hash;
            /* Send JSON to the server */
            console.log("Send JSON to the server:<pre>" + JSON.stringify(myArguments) + "</pre>");

            $.ajax({
                url: base_url + "builder-toolbar/builder/update_menu_item_position/",
                type: 'post',
                dataType: 'json',
                data: myArguments,
                cache: false
            });
        },
    });

    $('.js_sidebar_menu_item a, .menu_item').css('cursor', 'move');


   
    // // Sidebar edit button
    // document.querySelectorAll('.js_sidebar_menu_item > a, .js_submenu_item > a').forEach(item => {

    //     item.addEventListener('mouseenter', function () {
    //         this.classList.add('js_menu_item_highlighted');

    //         var button = document.createElement('button');
    //         button.innerHTML = '<i class="fas fa-eye"></i>';
    //         button.className = 'js_sidebar_permission_button';
    //         button.style.position = 'absolute';
    //         button.style.right = '0px';
    //         button.style.top = '50%';
    //         button.style.transform = 'translateY(-50%)';
    //         button.dataset.layoutId = this.dataset.layoutId;
            
    //         button.addEventListener('click', function (e) {
    //             e.preventDefault();
    //             e.stopPropagation();
    //             showLayoutPermissions(this.dataset.layoutId);
                
                
    //         });

    //         this.appendChild(button);
    //     });

    //     item.addEventListener('mouseleave', function () {
    //         this.classList.remove('js_menu_item_highlighted');

    //         var button = this.querySelector('.js_sidebar_permission_button');
    //         if (button) {
    //             this.removeChild(button);
    //         }

    //         var div = this.querySelector('.js_item_div_permissions');
    //         if (div) {
    //             this.removeChild(div);
    //         }
    //     });
    // });




    /* Sort layoutboxes */
    $(".connectedSortable").sortable({
        placeholder: 'ui-state-highlight',
        connectWith: '.connectedSortable',
        handle: '.js_layout_box',
        forcePlaceholderSize: true,
        forceHelperSize: true,
        tolerance: 'pointer',
        revert: 'invalid',
        /* That's fired first */
        start: function (event, ui) {
            myArguments = {};
            //ui.placeholder.width(ui.item.width());
            //ui.placeholder.height(ui.item.height() - 20);

            ui.placeholder.css({
                width: ui.item.innerWidth() - 30 + 1,
                height: ui.item.innerHeight() - 15 + 1,
                padding: ui.item.css("padding"),
                marginTop: 0
            });
        },
        /* That's fired second */
        remove: function (event, ui) {
            /* Get array of items in the list where we removed the item */
            myArguments = assembleData(this, myArguments);
        },
        /* That's fired thrird */
        receive: function (event, ui) {
            /* Get array of items where we added a new item */
            myArguments = assembleData(this, myArguments);
        },
        update: function (e, ui) {
            if (this === ui.item.parent()[0]) {
                /* In case the change occures in the same container */
                if (ui.sender == null) {
                    myArguments = assembleData(this, myArguments);
                }
            }
        },
        /* That's fired last */
        stop: function (event, ui) {

            var token = JSON.parse(atob($('body').data('csrf')));
            var token_name = token.name;
            var token_hash = token.hash;

            myArguments[token_name] = token_hash;
            /* Send JSON to the server */
            console.log("Send JSON to the server:<pre>" + JSON.stringify(myArguments) + "</pre>");

            var current_layout_id = ui.item.closest('.js_layout').attr('data-layout_id');
            var last_box_moved = ui.item.attr('id');

            $.ajax({
                url: base_url + "builder-toolbar/builder/update_layout_box_position/" + current_layout_id + "/" + last_box_moved,
                type: 'post',
                dataType: 'json',
                data: myArguments,
                cache: false
            });
            
        },
    });

    $('.connectedSortable .box-header, .connectedSortable .js_layout_box').css('cursor', 'move');

    // END Sortable


    //Foreach sublayout get permissions
    
    $('.js_layout').each(function () {
        var layout_id = $(this).first().data('layout_id');
        checkPermissions(layout_id);
    });
    $('.js_sidebar_menu_item a, js_submenu_item a').each(function () {
        var layout_id = $(this).first().data('layout-id');
        if (layout_id > 0) {
            checkPermissions(layout_id, $(this).closest('li'));
        }
            
    });
}

// Sortable function
function assembleData(object, arguments) {
    var data = $(object).sortable('toArray', { attribute: 'data-id' }); // Get array data
    var row_id = $(object).data("row"); // Get step_id and we will use it as property name
    var arrayLength = data.length; // no need to explain

    /* Create step_id property if it does not exist */
    if (!arguments.hasOwnProperty(row_id)) {
        arguments[row_id] = new Array();
    }

    /* Loop through all items */
    for (var i = 0; i < arrayLength; i++) {
        if (data[i]) {
            var task_id = data[i];
            /* push all image_id onto property step_id (which is an array) */
            arguments[row_id].push(task_id);
        }
    }
    return arguments;
}

function openBuilderFrame(link) {

    if ($('#builderFrameWrapper').is(':visible')) {
        $('#builderFrameWrapper').hide();
    } else {

        $('#builderFrame').attr('src', link);
        $('#builderFrameWrapper').show();
    }
}
function closeBuilderFrame() {
    $('#builderFrameWrapper').hide();
    refreshAjaxLayoutBoxes();
}

function checkPermissions(layout_id, container) {
    if (typeof container === 'undefined') {
        if (typeof layout_id === 'undefined') {
            var layout_id = $('#js_layout_content_wrapper').data('layout-id');
            var container = $('#builder_toolbar');
        } else {
            var container = $('.js_layout[data-layout_id="' + layout_id + '"]').first();

        }
    } else {
        //Dovrei aver passato tutto
    }
    
    


    if (layout_id) {
        $.ajax({
            url: base_url + "builder-toolbar/builder/check_user_permissions/" + layout_id,
            dataType: 'json',
            success: function (data) {


                if (data.only_super_admin == true) {
                    $('.js_button_user_permissions', container).attr('title', 'This page is visible only for SuperAdmin');
                    $('.js_check_users_permissions span', container).removeClass('fa-eye');
                    $('.js_check_users_permissions span', container).addClass('fa-eye-slash');
                    $('.js_check_users_permissions span', container).css('color', '#000000');
                } else {
                    $('.js_button_user_permissions', container).attr('title', 'This page is visible to other users');
                    $('.js_check_users_permissions span', container).removeClass('fa-eye-slash');
                    $('.js_check_users_permissions span', container).addClass('fa-eye blink_me');
                    $('.js_check_users_permissions span', container).css('color', '#ff0000');

                    // All null gruop count
                    var nullGroupCount = data.users_can_view.filter(function (user) {
                        return user.permissions_group === null;
                    }).length;
                    $('.js_users_can_view ul', container).html('');
                    $.each(data.all_groups, function (i, p) {
                        // Stabilisce se la checkbox dovrebbe essere selezionata
                        var isChecked = data.users_can_view.some(function (user) {
                            return user.permissions_group === p.permissions_group;
                        });

                        // Aggiunge la checkbox alla lista
                        if (p.permissions_group == null) {
                            var inputElement = ' +';
                            p.permissions_group = nullGroupCount + ' users without group';
                        } else {
                            var inputElement = '<input class="js_checkbox_group" data-group-layout-id="' + layout_id + '" type="checkbox" name="" value="'+p.permissions_group+'" ' + (isChecked ? 'checked="checked"' : '') + ' />';
                        }
                        $('.js_users_can_view', container).first().prepend('<li>' + inputElement + ' <strong> ' + p.permissions_group + '</strong> </li>');
                    });

                    //Se la pagina è di un modulo, mostro nuovametne tutti i gruppi per permettere di dare accesso a tutto il modulo e non solo a questo layout!
                    
                    //alert(1);
                    if (data.layout.layouts_module) {
                        

                        
                        
                        $('.js_users_can_view', container).first().append('<li class="divider"></li>');
                        $('.js_users_can_view', container).first().append('<li class=""><strong>Grant full access to module ' + data.layout.layouts_module + '</strong></li>');
                        $.each(data.all_groups.slice().reverse(), function (i, p) {
                            // Stabilisce se la checkbox dovrebbe essere selezionata
                            var isChecked = data.users_can_view.some(function (user) {
                                return user.permissions_group === p.permissions_group;
                            });

                            // Aggiunge la checkbox alla lista
                            if (p.permissions_group == null) {
                                var inputElement = ' +';
                                p.permissions_group = nullGroupCount + ' users without group';
                            } else {
                                var inputElement = '<input class="js_checkbox_group" data-group-layout-id="' + layout_id + '" data-module_name="' + data.layout.layouts_module +'" type="checkbox" name="" value="' + p.permissions_group + '" ' + (isChecked ? 'checked="checked"' : '') + ' />';
                            }
                            $('.js_users_can_view', container).first().append('<li>' + inputElement + ' <strong> ' + p.permissions_group + '</strong> </li>');
                        });
                    }

                    // Add group permission
                    container.on('click', '.js_checkbox_group[data-group-layout-id="' + layout_id +'"]', function () {
                        var checked = $(this).is(':checked');
                        
                            var token = JSON.parse(atob($('body').data('csrf')));
                            var token_name = token.name;
                            var token_hash = token.hash;
                        var group = $(this).val();
                        var module = $(this).data('module_name');
                            // Esegui la chiamata fetch quando la checkbox viene selezionata
                            // fetch(base_url + 'builder-toolbar/builder/add_group_permission/' + layout_id, { // Sostituisci con il tuo URL
                            //     method: 'POST', // o 'GET', a seconda delle tue necessità
                            //     headers: {
                            //         'Content-Type': 'application/json'
                            //         // Aggiungi altri headers se necessario
                            //     },
                            //     body: JSON.stringify({
                            //         [token_name]: token_hash
                            //     })
                            // })
                            //     .then(response => response.json()) // or .text() or another parsing method if you're not receiving JSON
                            //     .then(data => {
                            //         // Gestisci la risposta del server qui
                            //         console.log(data);
                            //     })
                            //     .catch(error => {
                            //         // Gestisci eventuali errori qui
                            //         console.error(error);
                            //     });
                            
                            
                            $.ajax(base_url + 'builder-toolbar/builder/add_group_permission/' + layout_id, {
                                type: 'POST',
                                data: {
                                    [token_name]: token_hash,
                                    'group': group,
                                    'checked': checked,
                                    'module': module,
                                },
                                dataType: 'json',

                                success: function (data) {


                                    $('#builder_toolbar').show();
                                    var toolBarEnabled = true;
                                    localStorage.setItem('toolBarEnabled', 'true');
                                    localStorage.setItem('toolBarToken', data);



                                },
                            });

                        
                        // Aggiungi eventualmente un else per gestire il caso in cui la checkbox viene deselezionata
                    });

                    // $.each(data.all_groups, function (i, p) {
                    //     $('.js_users_can_view').prepend('<li><input type="checkbox" name="" value="" /> <strong>PP' + p.permissions_group + '</strong> </li>');
                    // });
                    // $.each(data.users_can_view, function (i, p) {
                    //     // super_admin = "";
                    //     // if (p.permissions_admin == 1) {
                    //     //     super_admin = "- *superAdmin*";
                    //     // }
                    //     // $('.js_users_can_view').prepend('<li><a target="_blank" href="' + base_url + 'main/layout/user-detail/' + p.users_id + '">(' + p.users_id + ') <strong>' + p.users_first_name + ' ' + p.users_last_name + '</strong> ' + super_admin + ' - ' + p.permissions_group + '</a></li>');
                    //     $('.js_users_can_view').prepend('<li><input type="checkbox" name="" value="" /> <strong>' + p.permissions_group + '</strong> </li>');
                    // });
                }
            },
        });
    }

}

function showLayoutPermissions(layout_id) {
    //Todo mostrare un div autogenerato (in che posizione?) che carica il checkPermission() di quel layoutid
    checkPermissions(layout_id);
}