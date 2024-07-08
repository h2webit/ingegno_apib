

            <div class="portlet-body calendar ">
                <div id="calendar5" class="has-toolbar"></div>
                <script>

                    $(function () {
                        if (!jQuery().fullCalendar) {
                            throw Error('Calendar not loaded');
                        }

                        var jqCalendar = $('#calendar5');
                        var sourceUrl = "<?php echo base_url(); ?>custom/apib/getCalendarioDomiciliare";
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
                        jqCalendar.fullCalendar({
                            defaultView: 'agendaWeek',
                            editable: true,
                            selectable: true,
                            disableDragging: false,
                            header: h,
                            selectHelper: true,
                            minTime: minTime,
                            maxTime: maxTime,
                            monthNames: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
                            monthNamesShort: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                            dayNames: ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'],
                            dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
                            firstDay: 1,
                            timeFormat: 'H:mm',
                            axisFormat: 'H:mm',
                            buttonText: {
                                today: 'Mostra oggi',
                                month: 'Mese',
                                week: 'Sett.',
                                day: 'Giorno'
                            },
                            
                            eventClick: function (event, jsEvent, view) {
//                                loadModal("http:\/\/apib.h2-web.com\/get_ajax\/modal_form\/46" + '/' + event.id, {}, function () {
//                                    jqCalendar.fullCalendar('refetchEvents');
//                                });
                                return false;
                            },
                            eventSources: [{
                                    url: sourceUrl,
                                    type: 'POST',
                                    data: {},
                                    error: function (error) {
                                        console.log(error.responseText);
                                    },
                                    loading: function (bool) {
                                        $('#loading').fadeTo(bool ? 1 : 0);
                                    },
                                    color: '#4B8DF8', // a non-ajax option
                                    textColor: 'white' // a non-ajax option
                                }]
                        });
                    });

                </script>
            </div>
        
    





