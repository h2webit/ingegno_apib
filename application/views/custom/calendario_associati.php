<style>
    @media print {

        .col-lg-2,
        h3.page-title,
        .caption,
        .portlet-title {
            display: none;
        }

    }
</style>


<div class="portlet-body calendar ">
    <div class="row">
        <div class="col-lg-2 col-md-2">
            <h3>Associati</h3>
            <?php foreach ($this->apilib->search('associati', ['associati_non_attivo' => '0'], 0, null, 'associati_cognome') as $associato) : ?>
                <label class="checkbox">
                    <input type="checkbox" name="cal_filter_associato[]" class="js_check_filter_associato" value="<?php echo $associato['associati_id']; ?>" />
                    <?php echo ucfirst(strtolower($associato['associati_cognome'])); ?> <?php echo substr(ucfirst(strtolower($associato['associati_nome'])), 0, 5); ?>.</label>
            <?php endforeach; ?>

            <div class="calendar_custom_area"></div>
        </div>

        <div class="col-lg-8 col-md-6">
            <div id="calendar5" class="has-toolbar"></div>
        </div>

        <div class="col-lg-2 col-md-4">
            <h3>Sedi</h3>
            <?php $old_sede = '';
            foreach ($this->apilib->search('sedi_operative', [
                'sedi_operative_id IN (SELECT appuntamenti_impianto FROM appuntamenti)',
                "(sedi_operative_nascosta <> '1' OR sedi_operative_nascosta IS NULL)"
            ], 0, null, 'sedi_operative_cliente, sedi_operative_ordine') as $sede) : ?>
                <?php if ($sede['clienti_ragione_sociale'] != $old_sede) : ?>
                    <p><strong><?php echo $sede['clienti_ragione_sociale']; ?></strong></p>
                    <?php $old_sede = $sede['clienti_ragione_sociale']; ?>
                <?php endif; ?>
                <label class="checkbox">
                    <input type="checkbox" name="cal_filter_sede[]" class="js_check_filter_sede" value="<?php echo $sede['sedi_operative_id']; ?>" />
                    <?php echo $sede['sedi_operative_reparto']; ?></label>
            <?php endforeach; ?>

            <div class="calendar_custom_area"></div>
        </div>
    </div>

    <!--<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.15.1/moment-with-locales.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.0.1/fullcalendar.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.0.1/locale-all.js"></script>-->

    <script>
        var jqCalendarView;
        $(function() {

            var jqCalendar = $('#calendar5');
            var sourceUrl = "<?php echo base_url(); ?>custom/apib/getCalendarioAssociato";
            var minTime = "00:00";
            var maxTime = "24:00";

            var date = new Date();
            var d = date.getDate();
            var m = date.getMonth();
            var y = date.getFullYear();
            var h = {};
            if (jqCalendar.width() <= 400) {
                jqCalendar.addClass("mobile");
                h = {
                    left: 'title, prev, next',
                    center: '',
                    right: 'today,month,agendaWeek,agendaDay'
                };
            } else {
                jqCalendar.removeClass("mobile");
                if (Metronic.isRTL()) {
                    h = {
                        right: 'title',
                        center: '',
                        left: 'prev,next,today,month,agendaWeek,agendaDay'
                    };
                } else {
                    h = {
                        left: 'title',
                        center: '',
                        right: 'prev,next,today,month,agendaWeek,agendaDay'
                    };
                }
            }

            jqCalendar.fullCalendar('destroy'); // destroy the calendar
            jqCalendarView = jqCalendar.fullCalendar({
                defaultView: 'agendaWeek',
                editable: true,
                selectable: true,
                disableDragging: false,
                height: 'auto',
                header: h,
                //            locale: 'it',
                lang: 'it',
                monthNames: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
                monthNamesShort: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                dayNames: ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'],
                dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
                firstDay: 1,
                buttonText: {
                    today: 'Mostra oggi',
                    month: 'Mese',
                    week: 'Sett.',
                    day: 'Giorno'
                },
                timeFormat: 'H:mm',
                columnFormat: {
                    agendaWeek: 'ddd D MMMM'
                },
                axisFormat: 'H:mm',
                minTime: minTime,
                maxTime: maxTime,
                allDayHtml: "<i class='fa fa-clock-o'></i>",
                eventRender: function(event, element) {
                    element.attr('data-id', event.id).css({
                        'margin-bottom': '1px',
                        'border': '1px solid #aaa'
                    });
                },
                selectHelper: true,
                //            select: function (start, end, allDay) {
                //                var fStart = formatDate(start.toDate());    // formatted start
                //                var fEnd = formatDate(end.toDate());        // formatted end
                //                var allDay = isAlldayEvent(fStart, fEnd, 'DD/MM/YYYY HH:mm');
                //                        var data = {"appuntamenti_giorno" : fStart, "appuntamenti_giorno" : fEnd, };
                //                loadModal("http:\/\/sfera.h2-web.com\/dev\/mastercrm_apib\/get_ajax\/modal_form\/46", data, function () {
                //                    jqCalendar.fullCalendar('refetchEvents');
                //                }, 'get');
                //
                //                if (allDay) {
                //                    end.date(end.date() + 1);
                //                    end.minutes(end.minutes() - 1);
                //                }
                //            },
                eventClick: function(event, jsEvent, view) {
                    //                                loadModal("http:\/\/apib.h2-web.com\/get_ajax\/modal_form\/46" + '/' + event.id, {}, function () {
                    //                                    jqCalendar.fullCalendar('refetchEvents');
                    //                                });
                    return false;
                },
                //            eventDrop: function (event, dayDelta, minuteDelta, allDay, revertFunc, jsEvent, ui, view) {
                //                var allDay = isAlldayEvent(event.start, event.end);
                //                var fStart = event.start.format('DD/MM/YYYY HH:mm');    // formatted start
                //                var fEnd = event.end.format('DD/MM/YYYY HH:mm'); // formatted end
                //                        var data = {"appuntamenti_id" : event.id,"appuntamenti_giorno" : fStart, "appuntamenti_giorno" : fEnd, };
                //
                //                $.ajax({
                //                    url: "http://sfera.h2-web.com/dev/mastercrm_apib/db_ajax/update_calendar_event/5",
                //                    type: 'POST',
                //                    dataType: 'json',
                //                    data: data,
                //                    success: function (data) {
                //                        if (parseInt(data.status) < 1) {
                //                            revertFunc();
                //                            alert(data.txt);
                //                        }
                //                    },
                //                    error: function () {
                //                        revertFunc();
                //                        alert('There was an error while saving the event');
                //                    },
                //                });
                //            },
                //            eventResize: function (event, dayDelta, minuteDelta, revertFunc) {
                //                var allDay = isAlldayEvent(event.start, event.end);
                //                var fStart = event.start.format('DD/MM/YYYY HH:mm');    // formatted start
                //                var fEnd = event.end.format('DD/MM/YYYY HH:mm'); // formatted end
                //                        var data = {"appuntamenti_id" : event.id,"appuntamenti_giorno" : fStart, "appuntamenti_giorno" : fEnd, };
                //
                //
                //                $.ajax({
                //                    url: "http://sfera.h2-web.com/dev/mastercrm_apib/db_ajax/update_calendar_event/5",
                //                    type: 'POST',
                //                    dataType: 'json',
                //                    data: data,
                //                    success: function (data) {
                //                        if (parseInt(data.status) < 1) {
                //                            revertFunc();
                //                            alert(data.txt);
                //                        }
                //                    },
                //                    error: function () {
                //                        revertFunc();
                //                        alert('There was an error while saving the event');
                //                    },
                //                });
                //            },

                eventSources: [{
                    url: sourceUrl,
                    type: 'POST',
                    data: function() {
                        var values = [];
                        $('.js_check_filter_associato').filter('[type=checkbox]:checked').each(function() {
                            values.push($(this).val());
                        });

                        var values_sedi = [];
                        $('.js_check_filter_sede').filter('[type=checkbox]:checked').each(function() {
                            values_sedi.push($(this).val());
                        });

                        return {
                            filtro_associati: values,
                            filtro_sedi: values_sedi
                        };
                    },
                    error: function(error) {
                        console.log(error.responseText);
                    },
                    loading: function(bool) {
                        $('#loading').fadeTo(bool ? 1 : 0);
                    },
                    color: '#4B8DF8', // a non-ajax option
                    textColor: 'white' // a non-ajax option
                }],
                viewRender: function(view) {
                    window.sessionStorage.setItem(sessionStorageKey, JSON.stringify({
                        view: view.name,
                        date: jqCalendar.fullCalendar('getDate').toISOString()
                    }));
                }
            });



            $('.js_check_filter_associato,.js_check_filter_sede').on('change', function() {
                jqCalendar.fullCalendar('refetchEvents');
            });


            // Ripristina sessione
            var sessionStorageKey = jqCalendar.attr('id');

            try {
                var calendarSession = JSON.parse(window.sessionStorage.getItem(sessionStorageKey));
                jqCalendar.fullCalendar('changeView', calendarSession.view);
                jqCalendar.fullCalendar('gotoDate', calendarSession.date);
            } catch (e) {
                // ... skip ...
            }
        });
    </script>
</div>