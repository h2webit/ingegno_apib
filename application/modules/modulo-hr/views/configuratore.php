<?php


$reparti = array_key_value_map($this->apilib->search('reparti'), 'reparti_id', 'reparti_nome');
$gruppi_utenti = array_key_value_map($this->apilib->search('users_type'), 'users_type_id', 'users_type_value');
$giorni_settimana = array_key_value_map($this->apilib->search('orari_di_lavoro_giorno'), 'orari_di_lavoro_giorno_numero', 'orari_di_lavoro_giorno_value');
$searchResult = $this->apilib->search('dipendenti', ['dipendenti_attivo' => DB_BOOL_TRUE]);
$dipendenti = array();
foreach ($searchResult as $item) {
    $dipendenti[$item['dipendenti_id']] = $item['dipendenti_nome'] . ' ' . $item['dipendenti_cognome'];
}
if ($value_id) {
    $regola = $this->apilib->view('hr_rules', $value_id);
    $rule = $regola['hr_rules_json'];
} else {
    $rule = json_encode(null);
}
?>


<link rel="stylesheet" type="text/css"
    href="<?php echo base_url(); ?>script/js/querybuilder/query-builder.default.min.css">
</link>

<style>
    .query-builder .rules-group-container {
        padding: 20px;
    }
</style>

<script src="<?php echo base_url(); ?>script/js/querybuilder/query-builder.min.js"></script>

<div id="builder"></div>

<script>
    var strutture = <?php e_json($reparti); ?>;
    var gruppi_utenti = <?php e_json($gruppi_utenti); ?>;
    var selector = $('#builder');
    var rules_basic = <?php echo $rule; ?>;
    var giorni_settimana = <?php e_json($giorni_settimana); ?>;
    var dipendenti = <?php e_json($dipendenti); ?>;


    $('[name="hr_rules_json"]').hide().closest('.form-group').prepend(selector);

    selector.queryBuilder({
        lang: {
            "__locale": "Italiano (it)",
            "__author": "Matteo Puppis, https://www.h2web.it",
            "add_rule": "Aggiungi regola",
            "add_group": "Aggiungi gruppo",
            "delete_rule": "Rimuovi",
            "delete_group": "Rimuovi",
            "conditions": {
                "AND": "E",
                "OR": "OPPURE"
            },
            "operators": {
                "equal": "uguale a",
                "not_equal": "diverso da",
                "in": "uguale a uno tra questi",
                "not_in": "non tra questi",
                "less": "minore di",
                "less_or_equal": "minore o uguale a",
                "greater": "maggiore di",
                "greater_or_equal": "maggiore o uguale a",
                "between": "compreso tra",
                "not_between": "non compreso tra",
                "begins_with": "inizia con",
                "not_begins_with": "non inizia con",
                "contains": "contiene",
                "not_contains": "non contiene",
                "ends_with": "finisce con",
                "not_ends_with": "non finisce con",
                "is_empty": "è vuoto",
                "is_not_empty": "non è vuoto",
                "is_null": "è nullo",
                "is_not_null": "non è nullo"
            },
            "errors": {
                "no_filter": "Nessun filtro specificato",
                "empty_group": "Gruppo vuoto",
                "radio_empty": "Nessuna selezione",
                "checkbox_empty": "Nessuna spunta",
                "select_empty": "Tendina vuota",
                "string_empty": "Stringa vuota",
                "string_exceed_min_length": "Deve contenere massimo {0} caratteri",
                "string_exceed_max_length": "Non deve contenere massimo {0} caratteri",
                "string_invalid_format": "Formato non valido ({0})",
                "number_nan": "Non è un numero",
                "number_not_integer": "Non è un numero intero",
                "number_not_double": "Non è un numero reale",
                "number_exceed_min": "Deve essere maggiore di {0}",
                "number_exceed_max": "Deve essere minore di {0}",
                "number_wrong_step": "Deve essere multiplo di {0}",
                "number_between_invalid": "Valore non valido, {0} è maggiore di {1}",
                "datetime_empty": "Valore vuoto",
                "datetime_invalid": "Formato data non valido ({0})",
                "datetime_exceed_min": "Deve essere sucessiva a {0}",
                "datetime_exceed_max": "Deve essere minore di {0}",
                "datetime_between_invalid": "Valori non validi, {0} è maggiore di {1}",
                "boolean_not_valid": "Non è 1 o 0",
                "operator_not_multiple": "L'operatore \"{1}\" non accetta valori multipli"
            },
            "invert": "Inverti",
            "NOT": "NON"

        },
        // plugins: ['bt-tooltip-errors'], not in the code/ cdn
        filters: [{
            id: 'data_in',
            label: 'Data (entrata)',
            type: 'date',
            validation: {
                format: 'YYYY-MM-DD'
            },
            plugin: 'datepicker',
            plugin_config: {
                format: 'yyyy-mm-dd',
                todayBtn: 'linked',
                todayHighlight: true,
                autoclose: true
            }
        },
        {
            id: 'data_out',
            label: 'Data (uscita)',
            type: 'date',
            validation: {
                format: 'YYYY-MM-DD'
            },
            plugin: 'datepicker',
            plugin_config: {
                format: 'yyyy-mm-dd',
                todayBtn: 'linked',
                todayHighlight: true,
                autoclose: true
            }
        },
        {
            id: 'differenza_entrata_orario_previsto',
            label: 'Differenza in entrata',
            type: 'double',
            validation: {
                min: 0,
                step: 0.01
            },
            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
        },
        {
            id: 'differenza_uscita_orario_previsto',
            label: 'Differenza in uscita',
            type: 'double',
            validation: {
                min: 0,
                step: 0.01
            },
            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
        },
        {
            id: 'dipendente',
            label: 'Dipendente',
            type: 'integer',
            input: 'select',
            values: dipendenti,
            operators: ['equal', 'not_equal']
        },
        {
            id: 'giorno_in',
            label: 'Giorno della settimana (entrata)',
            type: 'integer',
            input: 'select',
            values: giorni_settimana,
            operators: ['equal', 'not_equal']
        },
        {
            id: 'giorno_out',
            label: 'Giorno della settimana (uscita)',
            type: 'integer',
            input: 'select',
            values: giorni_settimana,
            operators: ['equal', 'not_equal']
        },
        {
            id: 'gruppo',
            label: 'Gruppo utenti',
            type: 'integer',
            input: 'select',
            values: gruppi_utenti,
            operators: ['equal', 'not_equal']
        },

        {
            id: 'ore_previste',
            label: 'Ore previste del turno',
            type: 'double',
            validation: {
                min: 0,
                step: 0.01
            },
            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
        },
        {
            id: 'ora_fine',
            label: 'Ora fine',
            type: 'string',
            // validation: {
            //     format: 'HH:ii'
            // },
            // plugin: 'timepicker',
            // plugin_config: {
            //     format: 'HH:ii',
            //     todayBtn: 'linked',
            //     todayHighlight: true,
            //     autoclose: true,
            //     showMeridian: false,
            //     minuteStep: 1
            // }
            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
        },
        {
            id: 'ora_inizio',
            label: 'Ora inizio',
            type: 'string',
            // validation: {
            //     format: 'HH:ii'
            // },
            // plugin: 'timepicker',
            // plugin_config: {
            //     format: 'HH:ii',
            //     todayBtn: 'linked',
            //     todayHighlight: true,
            //     autoclose: true,
            //     showMeridian: false,
            //     minuteStep: 1
            // }
            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
        },
        {
            id: 'ora_rientro',
            label: 'Ora rientro',
            type: 'string',
            // validation: {
            //     format: 'HH:ii'
            // },
            // plugin: 'timepicker',
            // plugin_config: {
            //     format: 'HH:ii',
            //     todayBtn: 'linked',
            //     todayHighlight: true,
            //     autoclose: true,
            //     showMeridian: false,
            //     minuteStep: 1
            // }
            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
        },
        {
            id: 'ore_giornaliere_lavorate',
            label: 'Ore giornaliere lavorate',
            type: 'string',

            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
        },
        {
            id: 'straordinario',
            label: 'Straordinario',
            type: 'integer',
            input: 'radio',
            values: {
                1: 'Si',
                0: 'No'
            },
            operators: ['equal']
        },
        {
            id: 'ignora_pausa',
            label: 'Ignora pausa',
            type: 'integer',
            input: 'radio',
            values: {
                1: 'Si',
                0: 'No'
            },
            operators: ['equal']
        },
        {
            id: 'etichetta_timbratura_precedente',
            label: 'Etichetta timbratura precedente',
            type: 'string',
            operators: ['equal', 'not_equal']
        },
        {
            id: 'turni_giornalieri_previsti',
            label: 'Turni giornalieri previsti',
            type: 'string',
            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
        },
        {
            id: 'ore_straordinarie',
            label: 'Ore straordinarie',
            type: 'string',
            operators: ['equal', 'not_equal', 'less', 'less_or_equal', 'greater', 'greater_or_equal']
        },
        

        ],

        rules: rules_basic
    });


    $('#btn-get').on('click', function () {
        var result = $('#builder').queryBuilder('getRules');
        if (!$.isEmptyObject(result)) {
            alert(JSON.stringify(result, null, 2));
        } else {
            //   console.log("invalid object :");
        }
        // console.log(result);
    });

    $('#btn-reset').on('click', function () {
        $('#builder').queryBuilder('reset');
    });

    $('#btn-set').on('click', function () {
        //$('#builder').queryBuilder('setRules', rules_basic);
        var result = $('#builder').queryBuilder('getRules');
        if (!$.isEmptyObject(result)) {
            rules_basic = result;
        }
    });

    //When rules changed :
    $('#builder').on('getRules.queryBuilder.filter', function (e) {
        //$log.info(e.value);
    });





    selector.queryBuilder().on('rulesChanged.queryBuilder afterDeleteRule.queryBuilder', function () {
        //debugger
        //$('.js_grids_where', modal).html($(this).queryBuilder('getSQL', 'question_mark'));
        try {
            //alert(1);
            //var objSQL = selector.queryBuilder('getSQL', false, true);

            var rules = selector.queryBuilder('getRules');


            //debugger
            if (rules != null) {
                $('[name="hr_rules_json"]').val(JSON.stringify(rules));
            } else {
                $('[name="hr_rules_json"]', modal).val('');
            }
        } catch (e) {
            //console.log(e);
        }
    });
</script>