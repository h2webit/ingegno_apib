var selectedPersona = null;
var selectedMezzo = null;

function handleUserToggle() {
    $(".toggleUsers").on("click", function () {
        const toggler = $(this);
        const togglerContainer = toggler.parent();
        const target = togglerContainer.siblings(".container-persone");

        target.toggleClass("container-persone_visible");

        toggler.text(target.hasClass("container-persone_visible") ? "Mostra di meno" : "Mostra tutti");
    });
}

window.onscroll = function () {
    myFunction();
};

var header = document.getElementById("myHeader");
var sticky = header.offsetTop;

function myFunction() {
    if (window.pageYOffset > sticky) {
        header.classList.add("sticky");
    } else {
        header.classList.remove("sticky");
    }
}

var getRowUsers = function (riga) {
    var $tr = $('tr[data-riga="' + riga + '"]');
    var $box_persone = $(".box-persone", $tr);
    var users = [];
    $(".persona", $box_persone).each(function (i, persona) {
        var $persona = $(persona);
        users.push($persona.data("users_id"));
    });
    return users;
};
var getRowAutomezzi = function (riga) {
    var $tr = $('tr[data-riga="' + riga + '"]');
    var $box_automezzi = $(".box-automezzi", $tr);
    var automezzi = [];
    $(".automezzo", $box_automezzi).each(function (i, automezzo) {
        var $automezzo = $(automezzo);
        automezzi.push($automezzo.data("automezzi_id"));
    });
    return automezzi;
};
var aggiornaAppuntamento = function (id, day, riga) {
    $.ajax({
        method: "get",
        url: base_url + "planner-squadre/planner/aggiornaAppuntamento/" + id + "/" + day + "/" + riga,

        success: function (ajax_response) {
            console.log("Salvato!");
            //salvaRiga(riga);
        },
    });
};
var aggiungiMezzoAppuntamento = function (id, mezzo_id) {
    $.ajax({
        method: "get",
        url: base_url + "planner-squadre/planner/aggiungiMezzoAppuntamento/" + id + "/" + mezzo_id,

        success: function (ajax_response) {
            console.log("Salvato!");
        },
    });
};
var aggiungiPersonaAppuntamento = function (id, persona_id) {
    $.ajax({
        method: "get",
        url: base_url + "planner-squadre/planner/aggiungiPersonaAppuntamento/" + id + "/" + persona_id,

        success: function (ajax_response) {
            console.log("Salvato!");
        },
    });
};
var rimuoviMezzoAppuntamento = function (id, mezzo_id, dom_automezzo) {
    $.ajax({
        method: "get",
        url: base_url + "planner-squadre/planner/rimuoviMezzoAppuntamento/" + id + "/" + mezzo_id,

        success: function (ajax_response) {
            console.log("Salvato!");
            dom_automezzo && dom_automezzo.remove();
        },
    });
};
var rimuoviPersonaAppuntamento = function (id, persona_id, dom_persona) {
    $.ajax({
        method: "get",
        url: base_url + "planner-squadre/planner/rimuoviPersonaAppuntamento/" + id + "/" + persona_id,

        success: function (ajax_response) {
            console.log("Salvato!");
            dom_persona && dom_persona.remove();
        },
    });
};
var salvaRiga = function (riga) {
    var $tr = $('tr[data-riga="' + riga + '"]');

    var users = getRowUsers(riga);
    var automezzi = getRowAutomezzi(riga);

    console.log(users);
    console.log(automezzi);

    var data = {};
    data.users = users;
    data.automezzi = automezzi;
    data.riga = riga;

    //TODO:
    data.giorni = ["2024-09-02", "2024-09-03", "2024-09-04", "2024-09-05", "2024-09-06", "2024-09-07"];

    //TODO: ajax per salvare la riga passando data
    console.log("TODO: ajax per salvare la riga passando data");
    /*         $.ajax({
                method: 'post',
                url: base_url + "planner-squadre/planner/salvaRigaCalendarioLavoriSquadre",
                data: data,
                success: function(ajax_response) {
                    if (ajax_response.status == '0') {
                        alert(ajax_response.txt);
                    } else {
                        // refreshare ajax
                    }
                    //alert('Salvato!');
                    console.log('Salvato!');
                }
            }); */
};

$(document).ready(function () {
    console.log("TEST");

    $(".content-header.page-title").hide();

    /* if (plannerMode) {
        initializeDragAndDrop();
    } else {
        initializeClickMode();
    } */

    handleUserToggle();

    $(".btn-showall").on("click", function () {
        var this_btn = $(this);
        var impiegati = $("#impiegati");

        if (impiegati.is(":visible")) {
            impiegati.css("display", "none");
        } else {
            impiegati.css("display", "flex");
        }
    });

    $(".js_open_selection, .js_card_clicked").on("click", function () {
        selectedPersona = null;
        selectedMezzo = null;

        var windowWidth = $(window).width();
        var cardPosition = $(this).offset().left;

        if (cardPosition > windowWidth / 2) {
            $(".fixed_container").css({
                right: "auto",
                left: "1px",
            });
        } else {
            $(".fixed_container").css({
                left: "auto",
                right: "1px",
            });
        }

        $(".fixed_container").show(500, "easeInOutQuad");
    });

    $(".close_persone_mezzi").on("click", function () {
        selectedPersona = null;
        selectedMezzo = null;
        $(".fixed_container").hide(500, "easeInOutQuad");
    });

    $(".toggle_fullscreen").on("click", function () {
        var elem = document.documentElement;

        if (!document.fullscreenElement) {
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    });

    console.log("aaaaaaaaa");

    //MODALITA DRAG AND DROP
    if (plannerMode) {
        console.log("aaaa");

        /************ CONTAINER PERSONE ****************/
        $(".container-persone")
            .sortable({
                connectWith: ".box-persone",
                items: ".persona",
                opacity: 0.8,
                forceHelperSize: true,
                placeholder: "portlet-sortable-placeholder round-all",
                forcePlaceholderSize: true,
                tolerance: "pointer",
            })
            .on("sortupdate", function (event, ui) {
                if (this === ui.item.parent().parent()[0]) {
                    var riga_precedente = ui.item.data("previous_riga");

                    var riga_id = ui.item.closest("tr").data("riga");
                    ui.item.data("previous_riga", riga_id);
                    // salvaRiga(riga_precedente);
                    // salvaRiga(riga_id);
                    var persona_id = ui.item.data("users_id");
                    var appuntamento_id = ui.item.data("appuntamento_id");

                    rimuoviPersonaAppuntamento(appuntamento_id, persona_id);
                }
            });
        $(".container-mezzi")
            .sortable({
                connectWith: ".box-automezzi",
                items: ".automezzo",
                opacity: 0.8,
                forceHelperSize: true,
                placeholder: "portlet-sortable-placeholder round-all",
                forcePlaceholderSize: true,
                tolerance: "pointer",
            })
            .on("sortupdate", function (event, ui) {
                if (this === ui.item.parent()[0]) {
                    var riga_precedente = ui.item.data("previous_riga");

                    var riga_id = ui.item.closest("tr").data("riga");
                    ui.item.data("previous_riga", riga_id);
                    console.log(ui.item.parent());
                    // salvaRiga(riga_precedente);
                    // salvaRiga(riga_id);
                    var mezzo_id = ui.item.data("automezzi_id");
                    var appuntamento_id = ui.item.data("appuntamento_id");

                    rimuoviMezzoAppuntamento(appuntamento_id, mezzo_id);
                }
            });

        /************ CONTAINER MEZZI ****************/
        $(".box-persone")
            .sortable({
                connectWith: [".box-persone", ".container-persone"],
                items: ".persona",
                opacity: 0.8,
                forceHelperSize: true,
                placeholder: "portlet-sortable-placeholder round-all",
                forcePlaceholderSize: true,
                tolerance: "pointer",
            })
            .on("sortupdate", function (event, ui) {
                if (this === ui.item.parent()[0]) {
                    var riga_precedente = ui.item.data("previous_riga");

                    var riga_id = ui.item.closest("tr").data("riga");
                    ui.item.data("previous_riga", riga_id);
                    // salvaRiga(riga_precedente);
                    // salvaRiga(riga_id);
                    var appuntamento_id = $(event.currentTarget.closest(".appuntamento")).data("appuntamento_id");
                    ui.item.data("appuntamento_id", appuntamento_id);
                    var persona_id = ui.item.data("users_id");

                    aggiungiPersonaAppuntamento(appuntamento_id, persona_id);
                    $("#tecnici").prepend(ui.item.clone());
                }
            });

        $(".box-automezzi")
            .sortable({
                connectWith: [".box-automezzi", ".container-mezzi"],
                items: ".automezzo",
                opacity: 0.8,
                forceHelperSize: true,
                placeholder: "portlet-sortable-placeholder round-all",
                forcePlaceholderSize: true,
                tolerance: "pointer",
            })
            .on("sortupdate", function (event, ui) {
                if (this === ui.item.parent()[0]) {
                    var riga_precedente = ui.item.data("previous_riga");

                    var riga_id = ui.item.closest("tr").data("riga");
                    ui.item.data("previous_riga", riga_id);

                    var appuntamento_id = $(event.currentTarget.closest(".appuntamento")).data("appuntamento_id");
                    ui.item.data("appuntamento_id", appuntamento_id);

                    var mezzo_id = ui.item.data("automezzi_id");

                    aggiungiMezzoAppuntamento(appuntamento_id, mezzo_id);
                    $(".container-mezzi").prepend(ui.item.clone());
                }
            });

        //Rendo draggabili anche gli appuntamenti dentro il planner
        $(".container_appuntamenti")
            .sortable({
                connectWith: [".container_appuntamenti"],
                items: ".appuntamento",
                opacity: 0.8,
                forceHelperSize: true,
                placeholder: "portlet-sortable-placeholder",
                forcePlaceholderSize: true,
                tolerance: "pointer",
                delay: 500,
            })
            .on("sortupdate", function (event, ui) {
                if (this === ui.item.parent()[0]) {
                    //console.log('sortupdate appuntamento');
                    //console.log(event.currentTarget);
                    var day = $(event.currentTarget).data("day");
                    var riga = $(event.currentTarget).data("riga");

                    var appuntamento_id = ui.item.data("appuntamento_id");

                    //alert('sposto appuntamento ' + appuntamento_id);

                    aggiornaAppuntamento(appuntamento_id, day, riga);
                }
            });
    } else {
        //MODALITA CLICK

        /**
         * ! Selezione persona
         */
        const container_persone = $(".container-persone");
        var selected_persona = null;
        $(".js_selected_persona", container_persone).on("click", function () {
            //console.log($(this));
            selected_persona = $(this);
            selected_persona.addClass("opacity_clicked");
        });
        /**
         * ! Click su card, devo incollare persona
         */
        var card_clicked = null;
        $(".js_card_clicked").on("click", function () {
            card_clicked = $(this);
            if (selected_persona) {
                var riga_id = card_clicked.closest("tr").data("riga");
                var persona_id = selected_persona.data("users_id");
                var appuntamento_id = card_clicked.data("appuntamento_id");

                aggiungiPersonaAppuntamento(appuntamento_id, persona_id);

                var cloned_persona = selected_persona.clone(false, true);
                cloned_persona.attr("data-appuntamento_id", appuntamento_id);
                cloned_persona.attr("data-previous_riga", riga_id);
                cloned_persona.removeClass("opacity_clicked");

                var card_container_automezzi = card_clicked.find(".box-persone");
                card_container_automezzi.append(cloned_persona);

                selected_persona.removeClass("opacity_clicked");
                selected_persona = null;
                card_clicked = null;
            }
        });
        /**
         * ! Rimozione persona dall'appuntamento
         */
        const js_card_clicked = $(".js_card_clicked");
        //        $('.persona', '.js_card_clicked').on('click', function() {
        $(".js_card_clicked").on("click", ".persona", function () {
            //console.log($(this))
            var persona_clicked = $(this);
            card_clicked = persona_clicked.closest(".js_card_clicked");

            if (persona_clicked) {
                var riga_id = card_clicked.closest("tr").data("riga");
                card_clicked.data("previous_riga", riga_id);

                var persona_id = persona_clicked.data("users_id");
                var appuntamento_id = card_clicked.data("appuntamento_id");

                var riferimento_persona = persona_clicked.find("img.avatar").data("original-title");

                x = confirm("Vuoi rimuovere " + riferimento_persona + " dall'appuntamento?");
                if (x) {
                    rimuoviPersonaAppuntamento(appuntamento_id, persona_id, persona_clicked);
                }

                riga_id = null;
                persona_clicked = null;
                card_clicked = null;
                riferimento_persona = null;
            }
        });

        /**
         * ! Selezione automezzo
         */
        const container_mezzi = $(".container-mezzi");
        var selected_automezzo = null;
        $(".js_selected_automezzo", container_mezzi).on("click", function () {
            //console.log($(this));
            selected_automezzo = $(this);
            selected_automezzo.addClass("opacity_clicked");
        });
        /**
         * ! Click su card, devo incollare automezzo
         */
        var card_clicked = null;
        $(".js_card_clicked").on("click", function () {
            card_clicked = $(this);
            if (selected_automezzo) {
                var riga_id = card_clicked.closest("tr").data("riga");
                var mezzo_id = selected_automezzo.data("automezzi_id");
                var appuntamento_id = card_clicked.data("appuntamento_id");

                aggiungiMezzoAppuntamento(appuntamento_id, mezzo_id);

                var cloned_automezzo = selected_automezzo.clone(false, true);
                cloned_automezzo.attr("data-appuntamento_id", appuntamento_id);
                cloned_automezzo.attr("data-previous_riga", riga_id);
                cloned_automezzo.removeClass("opacity_clicked");
                cloned_automezzo.removeClass("js_selected_automezzo");

                var card_container_automezzi = card_clicked.find(".box-automezzi");
                card_container_automezzi.append(cloned_automezzo);

                selected_automezzo.removeClass("opacity_clicked");
                selected_automezzo = null;
                card_clicked = null;
            }
        });
        /**
         * ! Rimozione automezzo dall'appuntamento
         */
        //        $('.automezzo', '.js_card_clicked').on('click', function() {
        $(".js_card_clicked").on("click", ".automezzo", function () {
            var automezzo_clicked = $(this);
            card_clicked = automezzo_clicked.closest(".js_card_clicked");

            if (automezzo_clicked) {
                var riga_id = card_clicked.closest("tr").data("riga");
                card_clicked.data("previous_riga", riga_id);

                var mezzo_id = automezzo_clicked.data("automezzi_id");
                var appuntamento_id = card_clicked.data("appuntamento_id");

                x = confirm("Vuoi rimuovere questo automezzo dall'appuntamento?");
                if (x) {
                    rimuoviMezzoAppuntamento(appuntamento_id, mezzo_id, automezzo_clicked);
                }

                riga_id = null;
                automezzo_clicked = null;
                card_clicked = null;
            }
        });
    }

    $(".js_aggiungi_cliente").on("click", function () {
        var url = $(this).data("url");
        var riga_id = $(this).closest("tr").data("riga");
        var users = getRowUsers(riga_id);
        var automezzi = getRowAutomezzi(riga_id);
        //console.log(users);
        var append_pars = "";
        // for (var i in users) {
        //     console.log(users[i]);
        //     append_pars += '&appuntamenti_persone[' + users[i] + '] = ' + users[i];
        // }
        // for (var i in automezzi) {
        //     append_pars += '&appuntamenti_automezzi[' + automezzi[i] + ']=' + automezzi[i];
        // }
        append_pars += "&appuntamenti_riga=" + riga_id;
        url = url + append_pars;

        var data_post = [];
        data_post.push({
            name: token_name,
            value: token_hash,
        });

        loadModal(url, data_post);
    });

    //Copia appuntamento per il giorno successivo
    $(".js_copia_appuntamento").on("click", function () {
        //console.log('copia appuntamento...');
        /*$.ajax({
            method: 'get',
            url: base_url + "planner-squadre/planner/aggiungiMezzoAppuntamento/" + id + "/" + mezzo_id,
            success: function(ajax_response) {
                window.location.reload(true);
                console.log('Appuntamento copiato per il giorno successivo');
            }
        });*/
    });

    $("body").on("click", ".btn-danger.js_confirm_button.js_link_ajax", function (e) {
        $(".modal").modal("toggle");
    });

    /* Show/hide more users */
    $(".toggleUsers").on("click", function () {
        const toggler = $(this);
        const toggler_container = $(this).parent();
        const target = toggler_container.siblings(".container-persone");

        target.toggleClass("container-persone_visible");

        if (target.hasClass("container-persone_visible")) {
            toggler.text("Mostra di meno");
        } else {
            toggler.text("Mostra tutti");
        }
    });
});
