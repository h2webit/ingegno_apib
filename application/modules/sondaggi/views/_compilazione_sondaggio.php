<?php
$sondaggio = $this->apilib->searchFirst('sondaggi', ['sondaggi_id' => $value_id]);

if (!empty($sondaggio)) {
    $sondaggio_id = $value_id;

    $domande_sondaggio = $this->apilib->search('sondaggi_domande', [
        'sondaggi_domande_sondaggio_id' => $value_id
    ]);

    $steps = $this->apilib->search("sondaggi_step", ['sondaggi_step_sondaggio_id' => $value_id ], null, 0, 'sondaggi_step_ordine', 'ASC');

    $sections = [];

    if(!empty($steps)) {
        $domande_step = [];
        foreach ($steps as $index => $step) {
            $domande_step = $this->apilib->search('sondaggi_domande', [
                'sondaggi_domande_sondaggio_id' => $value_id,
                'sondaggi_domande_step' => $step['sondaggi_step_id']
            ]);

            $sections[$index]['step'] = $step['sondaggi_step_name'];
            $sections[$index]['step_info'] = $step;
            $sections[$index]['step_n'] = $index;
            $sections[$index]['step_finale'] = $index+1 == count($steps) ? true : false;
            $sections[$index]['domande'] = $domande_step;

        }
        dump($sections);
    }

} else {
    echo "Nessun sondaggio associato";
}

//Mappatura dimensione colonne
$column_size = [
'100%' => '12',
'66%' => '8',
'50%' => '6',
'33%' => '4',
];
?>

<link rel="stylesheet" href="<?php echo base_url_template("template/adminlte/bower_components/smart-wizard/dist/css/smart_wizard.min.css?v={$this->config->item('version')}") ?>">
<script src="<?php echo base_url_template("template/adminlte/bower_components/smart-wizard/dist/js/jquery.smartWizard.min.js?v={$this->config->item('version')}"); ?>">
</script>

<style>
.mt-0 {
    margin-top: 0px;
}

.mb-30 {
    margin-bottom: 30px;
}

.testo_domanda {
    display: block;
    font-weight: 700;
    margin-bottom: 10px;
}

.required_field {
    font-size: 16px;
}

.btn_submit {
    width: 30%;
}

label {
    font-size: 0.8em;
}

a:hover,
a:visited,
a:focus {
    text-decoration: none !important;
}

a.collapsed {
    font-weight: bold;
}

#smartwizard>ul>li {
    width: 33% !important;
}

#smartwizard>ul>li>a {
    font-size: 1.2em;
    font-weight: bold;
}

button.next-btn,
button.prev-btn {
    font-weight: bold;
}

hr.less_margin {
    margin-top: 1% !important;
    margin-bottom: 1% !important;
}

body #smartwizard {
    font-size: 1.4em;
}
</style>


<div id="smartwizard">
    <ul class="text-center">
        <?php foreach($sections as $section) : ?>
        <li><a href="<?php echo '#step_'.$section['step_info']['sondaggi_step_id']; ?>"><i class="fas fa-file"></i><br /><?php echo $section['step']; ?></a></li>
        <?php endforeach; ?>
    </ul>

    <div>
        <div class="row">
            <div class="col-sm-6 col-sm-offset-3">
                <div class="callout callout-success js_alert" style="display:none"></div>
            </div>
        </div>
        <?php foreach ($sections as $section) : ?>
        <div id="<?php echo '#step_'.$section['step_info']['sondaggi_step_id']; ?>">
            <style>
            div.form-group {
                margin-top: 1% !important;
            }
            </style>

            <form id="step_<?php echo $section['step_info']['sondaggi_step_id']; ?>" enctype="multipart/form-data" style="margin-top: 1% !important; margin-bottom: 2.5% !important;" class="compilazione_questionario">
                <!--                 <input type="hidden" name="rapporto_riparazione_riparatore" value="<?php //echo $riparatori_id; ?>">
                <input type="hidden" name="rapporto_riparazione_perizia" value="<?php //echo $perizia_id ?>">
                <input type="hidden" name="perizie_elmar_riparatori_id" value="<?php //echo $perizie_elmar_riparatori_id ?>"> -->
                <?php if (!empty($sondaggio_id)) : ?><input type="hidden" name="sondaggii_id" value="<?php echo $sondaggio_id ?>"><?php endif; ?>
                <input type="hidden" name="current_step" value="<?php echo ($section['step_finale']) ? $section['step_n'] : $section['step_n'] + 1 ?>">

                <?php foreach ($section['domande'] as $domanda) : ?>
                <?php
                    if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta singola" || $domanda['sondaggi_domande_tipologia_value'] === "Risposta singola - radio" || $domanda['sondaggi_domande_tipologia_value'] === "Risposta multipla - checkbox") {
                        $risposte = $this->apilib->search('sondaggi_domande_risposte', [
                            'sondaggi_domande_risposte_domanda_id' => $domanda['sondaggi_domande_id']
                        ]);
                    }
                ?>

                <div class="column_input_container col-sm-<?php echo $column_size[$domanda['sondaggi_domande_spazio_risposta_value']]; ?>">
                    <div class="form-group">
                        <div class="testo_domanda">
                            <?php
                                if ($domanda['sondaggi_domande_obbligatorio'] == DB_BOOL_FALSE) {
                                    echo $domanda['sondaggi_domande_domanda'];
                                } else {
                                    echo $domanda['sondaggi_domande_domanda'] . '<span class="text-danger required_field">*</span>';
                                }
                                ?>
                        </div>

                        <!-- Risposta breve -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta breve") : ?>
                        <div class="js_input_container">
                            <input type="text" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" id="risposta_breve" rows="3" class="w-100 form-control" data-domanda_id="<?php echo $domanda['sondaggi_domande_id'];?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'].':'.$domanda['sondaggi_domande_valore_sblocco'] : ''; ?>"></input>
                        </div>
                        <?php endif; ?>

                        <!-- Paragrafo -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Paragrafo") : ?>
                        <div class="js_input_container">
                            <textarea name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" id="paragrafo" rows="3" class="w-100 form-control" data-domanda_id="<?php echo $domanda['sondaggi_domande_id'];?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'].':'.$domanda['sondaggi_domande_valore_sblocco'] : ''; ?>"></textarea>
                        </div>
                        <?php endif; ?>

                        <!-- Risposta singola - Select -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta singola") : ?>
                        <div class="js_input_container">
                            <select name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" id="risposta_singola_select" class="form-control domanda_select" data-domanda_id="<?php echo $domanda['sondaggi_domande_id'];?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'].':'.$domanda['sondaggi_domande_valore_sblocco'] : ''; ?>">
                                <option value=""></option>
                                <?php foreach ($risposte as $risposta) : ?>
                                <option value="<?php echo $risposta['sondaggi_domande_risposte_id']; ?>"><?php echo $risposta['sondaggi_domande_risposte_risposta']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>

                        <!-- Risposta singola - Radio -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta singola - radio") : ?>
                        <div class="js_input_container">
                            <?php foreach ($risposte as $risposta) : ?>
                            <div>
                                <input type="radio" id="risposta_singola_radio" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" value="<?php echo $risposta['sondaggi_domande_risposte_id']; ?>" data-domanda_id="<?php echo $domanda['sondaggi_domande_id'];?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'].':'.$domanda['sondaggi_domande_valore_sblocco'] : ''; ?>" />
                                <label for="<?php echo $risposta['sondaggi_domande_risposte_risposta']; ?>"><?php echo $risposta['sondaggi_domande_risposte_risposta']; ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Risposta multipla - checkbox -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta multipla - checkbox") : ?>
                        <div class="js_input_container">
                            <?php foreach ($risposte as $risposta) : ?>
                            <div class="checkbox">
                                <label>
                                    <input type="checkbox" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>][]" id="risposta_multipla" value="<?php echo $risposta['sondaggi_domande_risposte_id']; ?>" data-domanda_id="<?php echo $domanda['sondaggi_domande_id'];?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'].':'.$domanda['sondaggi_domande_valore_sblocco'] : ''; ?>">
                                    <?php echo $risposta['sondaggi_domande_risposte_risposta']; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Risposta Data -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Data") : ?>
                        <div class="js_input_container">
                            <div class="input-group js_form_datepicker date">
                                <input name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" type="text" class="form-control" value="<?php echo date('d/m/Y'); ?>">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button"><i class="fas fa-calendar-alt"></i></button>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Risposta Ora -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Ora") : ?>
                        <div class="js_input_container">
                            <div class="input-group">
                                <input name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" type="text" class="form-control js_form_timepicker" value="<?php echo date('H:i'); ?>" data-domanda_id="<?php echo $domanda['sondaggi_domande_id'];?>" data-default-time="<?php echo date('H:i'); ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'].':'.$domanda['sondaggi_domande_valore_sblocco'] : ''; ?>">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button"><i class="far fa-clock"></i></button>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>


                    </div>
                </div>
                <?php endforeach; ?>
            </form>
            <div class="clearfix">
                <button class="btn btn-primary prev-btn" type="button"><i class="fas fa-chevron-left"></i> Indietro </button>

                <?php if ($section['step_finale']) : ?>
                <button class="btn btn-success pull-right next-btn" data-step="<?php echo $section['step'] ?>" data-fine="1" type="button">Fine <i class="fas fa-chevron-right"></i></button>
                <?php else : ?>
                <button class="btn btn-success pull-right next-btn" data-step="<?php echo $section['step'] ?>" data-fine="0" type="button">Avanti <i class="fas fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

</div>




<script>
function loading(status) {
    if (status == true) {
        return true;
    } else {
        return false;
    }
}

//var rapporto_id = <?php //echo ($rapporto_id) ? $rapporto_id : 'null'; ?>;
$(function() {

    var anchor_settings = {};

    anchor_settings = {
        anchorClickable: true,
        enableAllAnchors: true,
        markDoneStep: true,
        enableAnchorOnDoneStep: true
    }

    $('#smartwizard').smartWizard({
        keyboardSettings: {
            keyNavigation: false,
            keyLeft: [9999999],
            keyRight: [9999999]
        },
        backButtonSupport: false,
        toolbarSettings: {
            toolbarPosition: 'none',
        },
        transitionEffect: 'fade',
        useURLhash: false,
        showStepURLhash: false,
        includeFinishButton: true,

        anchorSettings: anchor_settings,

        selected: 0,

        <?php //if (isset($rapporto['rapporto_riparazione_step'])) : ?>
        //selected: <?php// echo $rapporto['rapporto_riparazione_step']; ?>,
        <?php //endif; ?>
    });

    $(".prev-btn").on("click", function() {
        $('#smartwizard').smartWizard("prev");
        return true;
    });

    /*             <?php //if (!empty($rapporto)) : ?>
                var campi_rapporto = <?php //echo json_encode($rapporto); ?>;

                $('form').populate(campi_rapporto);

                $('input[type="radio"]:checked').trigger('change');

                <?php //endif; ?> */

    $('.next-btn').on('click', function(e) {
        e.preventDefault();

        var step = $(this).data('step');
        var fine = $(this).data('fine');

        var this_button = $(this);
        this_button.prop('disabled', true);

        var form = $('form#step_' + step);

        var formData = new FormData($('form#step_' + step)[0]);

        if (fine == '1') {
            formData.append('step_finale', 'true');
        }

        $.ajax(base_url + 'custom/wizard/saveStep', {
            async: false,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response, status, xhr) {
                var data = JSON.parse(response);

                if (data['status'] == '1') {
                    if (typeof data['data'] !== 'undefined') {
                        $('<input>').attr({
                            type: 'hidden',
                            name: 'rapporto_id',
                            value: data['data']
                        }).appendTo('form');

                        rapporto_id = data['data'];

                        $('.componente_raporto').val(rapporto_id);
                    }

                    $('.js_alert').text(data['txt']).show();

                    setTimeout(function() {
                        $('.js_alert').text('').hide();
                    }, 2500);

                    if (fine == '1') {
                        window.location.href = base_url;
                    } else {
                        $('#smartwizard').smartWizard("next");
                    }
                } else {
                    alert('Si è verificato un errore durante il salvataggio.');
                }

                this_button.prop('disabled', false);
            },
            error: function(jqXhr, textStatus, errorMessage) {
                console.log(errorMessage);
            }
        });


    });





















    /**
     * PRIMA DELLE MODIFICHE A WIZARD
     */
    $('.domanda_select').select2();


    //Nascondo tutti i campi che dipendono da qualcuno
    /*     var campi_dipendenti = $('[data-trigger_on]');
        console.log(campi_dipendenti)
        campi_dipendenti.each(function(index) {
            const data_attr = $(this).data("trigger_on");
            console.log(data_attr)
            console.log(data_attr.length)
            if (data_attr.length != 0) {
                $(this).closest(".column_input_container").hide();
            }
        }); */


    var array_selected_checkbox = [];
    //Show / hide fields based on condition
    $(".compilazione_questionario :input").on("change", function() {

        var changed_input = $(this);
        const sbloccato_da_domanda = $(this).data("domanda_id");
        console.log('sbloccato da domanda: ', sbloccato_da_domanda);
        console.log('changed_input ', changed_input, changed_input.val());
        $(':input[data-trigger_on*="' + sbloccato_da_domanda + '"]', changed_input.closest("form")).each(function() {

            if (changed_input.attr('type') == 'radio') {
                //console.log(changed_input.prop("checked"));
                if (changed_input.prop("checked") === true) {
                    var value_of_dependent_field = changed_input.val();
                }
                //console.log(value_of_dependent_field)
            } else {
                var value_of_dependent_field = changed_input.val();
            }

            if (changed_input.attr('type') == 'checkbox') {
                //Prendo il valore della checkbox selezionata
                if (changed_input.prop("checked") === true) {
                    var value_of_dependent_field = changed_input.val();
                    /* Inserisco una sola volta tutti i valori selezionati
                    (lo userò dopo per capire se tra quelli selezionati c'è uno di quelli che sblocca un campo) */
                    if (array_selected_checkbox.indexOf(value_of_dependent_field) === -1) {
                        array_selected_checkbox.push(value_of_dependent_field);
                    }
                    //console.log('checkbox selezionata, valore dipendente: ', value_of_dependent_field)
                } else {
                    //Devo rimuovere il valore appena deselezionato dall'array dei selezionati
                    array_selected_checkbox = array_selected_checkbox.filter(selected => selected != value_of_dependent_field);
                    //console.log('checkbox deselezionata, valore dipendente: ', value_of_dependent_field)
                }
            } else {
                var value_of_dependent_field = changed_input.val();
                //console.log('non checkbox, valore dipendente: ', value_of_dependent_field)
            }

            console.log(array_selected_checkbox)
            console.log('domanda_id: ', $(this).data("domanda_id"));

            if ($(this).data("trigger_on").includes(":")) {
                var expl = $(this).data("trigger_on").split(":");
                var vals = expl[1].split(",");
            } else {
                var vals = null;
            }

            console.log('valore di sblocco (vals): ', vals)

            if (vals !== null) {
                //Se è un array (come nel caso checkbox) devo vedere se c'è un elemento in comune tra vals e l'array dei selezionati
                if (Array.isArray(array_selected_checkbox) && array_selected_checkbox.length != 0) {
                    var isInCommon = array_selected_checkbox.some(item => vals.includes(item));
                    if (isInCommon) {
                        $(this).closest(".column_input_container").show();
                    } else {
                        $(this).closest(".column_input_container").hide();
                    }
                    return;
                } else {
                    //Nascondo nel caso in cui ultimo elemento che rimuovo è proprio quello che permetteva lo sblocco del campo
                    $(this).closest(".column_input_container").hide();
                }
                if (vals.includes(value_of_dependent_field)) {
                    $(this).closest(".column_input_container").show();
                } else {
                    $(this).closest(".column_input_container").hide();
                }
            } else {
                if (value_of_dependent_field && value_of_dependent_field != 0) {
                    $(this).closest(".column_input_container").show();
                } else {
                    $(this).closest(".column_input_container").hide();
                }
            }
        });
    });
    $(".compilazione_questionario :input").trigger("change")



    //Gestione campi required
    $('form.compilazione_questionario').on('submit', function(e) {
        e.preventDefault();

        var this_form = $(this);
        $('#msg_compilazione_questionario', this_form).html('').hide();

        var formData = new FormData(this);

        $.ajax({
            url: base_url + 'sondaggi/sondaggi/save',
            async: false,
            type: 'POST',
            dataType: 'json',
            cache: false,
            contentType: false,
            processData: false,
            data: formData,
            success: function(res) {
                if (res.status == '0') {
                    $('#msg_compilazione_questionario', this_form).html(res.txt).removeClass('alert-success').addClass('alert-danger').show();
                } else {
                    $('#msg_compilazione_questionario', this_form).html(res.txt).removeClass('alert-danger').addClass('alert-success').show();
                }

                setTimeout(function() {
                    location.reload();
                }, 2000);
                return false;
            }
        });

        return false;
    });
});
</script>