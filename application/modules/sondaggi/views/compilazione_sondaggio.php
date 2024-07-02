<?php
$sondaggio = $this->apilib->searchFirst('sondaggi', ['sondaggi_id' => $value_id]);

$sondaggio_compilabile = false;
if ($sondaggio['sondaggi_stato_id'] == 2) {
    $sondaggio_compilabile = true;
}

if (!empty($sondaggio)) {
    $sondaggio_id = $value_id;

    $domande_sondaggio = $this->apilib->search('sondaggi_domande', [
        'sondaggi_domande_sondaggio_id' => $value_id,
    ]);

    $steps = $this->apilib->search("sondaggi_step", ['sondaggi_step_sondaggio_id' => $value_id], null, 0, 'sondaggi_step_ordine', 'ASC');

    $sections = [];

    if (!empty($steps)) {
        $domande_step = [];
        foreach ($steps as $index => $step) {
            $domande_step = $this->apilib->search('sondaggi_domande', [
                'sondaggi_domande_sondaggio_id' => $value_id,
                'sondaggi_domande_step' => $step['sondaggi_step_id']
            ], null, 0, 'sondaggi_domande_ordine', 'ASC');

            $sections[$index]['step'] = $step['sondaggi_step_name'];
            $sections[$index]['step_info'] = $step;
            $sections[$index]['step_n'] = $index;
            $sections[$index]['step_finale'] = $index + 1 == count($steps) ? true : false;
            $sections[$index]['domande'] = $domande_step;

        }
        //dump($sections);
    }

} else {
    echo "Nessun sondaggio associato";
}

/**
 * Edit questionario
 */
if ($compilazione_id = $this->input->get('compilazione_id')) {
    $risposte_compilate = array_key_map_data($this->apilib->search('sondaggi_risposte_utenti', ['sondaggi_risposte_utenti_compilazione_sondaggio' => $compilazione_id]), 'sondaggi_risposte_utenti_domanda_id');
    //dump($risposte_compilate);
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
    font-size: 16px;
}

.required_field {
    font-size: 16px;
}

.btn_submit {
    width: 30%;
}

label {
    /*font-size: 0.8em;*/
    font-size: 14px;
}

.column_input_container .form-group {
    margin-top: 0px;
}

.column_input_container .form-group .checkbox {
    margin: 5px 0;
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
    width: 24% !important;
}

#smartwizard>ul>li>a {
    font-size: 1.1em;
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

ul.tabs_header {
    background-color: #ffffff;
}

.step_titolo {
    margin-left: 10px;
}

.btn_save_step {
    margin-right: 10px;
}
</style>

<?php if ($sondaggio_compilabile == false): ?>
<div class="callout callout-warning">
    <h3>Il questionario non è attualmente compilabile.</h3>
</div>
<?php else: ?>

<?php if (!empty($steps)): ?>
<!-- HO DOMANDE RAGGRUPPATE IN STEP, USO WIZARD -->
<form id="form_sondaggio" action="<?php echo base_url('sondaggi/sondaggi/save'); ?>" method="post">
    <?php echo add_csrf() ?>

    <div id="smartwizard">

        <!-- Section -->
        <ul class="text-center tabs_header">
            <?php foreach ($sections as $section): ?>
            <li>
                <a href="<?php echo '#step_' . $section['step_info']['sondaggi_step_id']; ?>">
                    <i class="fas fa-file"></i>
                    <span class="step_titolo"><?php echo $section['step']; ?></span>
                </a>
            </li>
            <?php endforeach;?>
        </ul>

        <!-- Section content -->
        <div class="tab-content">
            <?php foreach ($sections as $section): ?>
            <div id="<?php echo 'step_' . $section['step_info']['sondaggi_step_id']; ?>" class="tab-pane">
                <?php if (!empty($sondaggio_id)): ?><input type="hidden" name="sondaggio_id" value="<?php echo $sondaggio_id ?>"><?php endif;?>
                <input type="hidden" name="current_step" value="<?php echo ($section['step_finale']) ? $section['step_n'] : $section['step_n'] + 1 ?>">
                <input type="hidden" name="compilazione_id" value="<?php echo $compilazione_id ?? ''; ?>" class="js_compilazione_id">


                <div class="row">

                    <?php foreach ($section['domande'] as $domanda): ?>
                    <?php
if (!empty($risposte_compilate) && array_key_exists($domanda['sondaggi_domande_id'], $risposte_compilate)) {
    $risposta_compilata = $risposte_compilate[$domanda['sondaggi_domande_id']];
} else {
    $risposta_compilata = null;
}
//debug($risposta_compilata, true);

if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta singola" || $domanda['sondaggi_domande_tipologia_value'] === "Risposta singola - radio" || $domanda['sondaggi_domande_tipologia_value'] === "Risposta multipla - checkbox") {
    $risposte = $this->apilib->search('sondaggi_domande_risposte', [
        'sondaggi_domande_risposte_domanda_id' => $domanda['sondaggi_domande_id'],
    ]);
}
?>

                    <div class="column_input_container col-sm-<?php echo $column_size[$domanda['sondaggi_domande_spazio_risposta_value']]; ?>">
                        <div class="form-group">
                            <div class="testo_domanda">
                                <?php
                                    echo $domanda['sondaggi_domande_obbligatorio'] == DB_BOOL_FALSE ? $domanda['sondaggi_domande_domanda'] : $domanda['sondaggi_domande_domanda'] . '<span class="text-danger required_field">*</span>';
                                ?>
                            </div>

                            <!-- Risposta breve -->
                            <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta breve"): ?>
                            <div class="js_input_container">
                                <input type="text" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" id="risposta_breve" rows="3" class="w-100 form-control" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>" value="<?php if ($risposta_compilata): ?><?php echo $risposta_compilata['sondaggi_risposte_utenti_risposta_valore']; ?><?php endif;?>"></input>
                            </div>
                            <?php endif;?>

                            <!-- Paragrafo -->
                            <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Paragrafo"): ?>
                            <div class="js_input_container">
                                <textarea name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" id="paragrafo" rows="3" class="w-100 form-control" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>"><?php if ($risposta_compilata) { echo $risposta_compilata['sondaggi_risposte_utenti_risposta_valore']; } ?></textarea>
                            </div>
                            <?php endif;?>

                            <!-- Risposta singola - Select -->
                            <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta singola"): ?>
                            <div class="js_input_container">
                                <select name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" id="risposta_singola_select" class="form-control domanda_select" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>">
                                    <option value=""></option>
                                    <?php foreach ($risposte as $risposta): ?>
                                    <option value="<?php echo $risposta['sondaggi_domande_risposte_id']; ?>" <?php if (!empty($risposta_compilata) && ($risposta_compilata['sondaggi_risposte_utenti_risposta_id'] == $risposta['sondaggi_domande_risposte_id'])): ?> selected<?php endif;?>><?php echo $risposta['sondaggi_domande_risposte_risposta']; ?></option>
                                    <?php endforeach;?>
                                </select>
                            </div>
                            <?php endif;?>

                            <!-- Risposta singola - Radio -->
                            <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta singola - radio"): ?>
                            <div class="js_input_container">
                                <?php foreach ($risposte as $risposta): ?>
                                <div>
                                    <input type="radio" id="risposta_singola_radio" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" value="<?php echo $risposta['sondaggi_domande_risposte_id']; ?>" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>" <?php if (!empty($risposta_compilata) && $risposta_compilata['sondaggi_risposte_utenti_risposta_id'] == $risposta['sondaggi_domande_risposte_id']): ?> checked<?php endif;?> />
                                    <label for="<?php echo $risposta['sondaggi_domande_risposte_risposta']; ?>"><?php echo $risposta['sondaggi_domande_risposte_risposta']; ?></label>
                                </div>
                                <?php endforeach;?>
                            </div>
                            <?php endif;?>

                            <!-- Risposta multipla - checkbox -->
                            <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta multipla - checkbox"): ?>
                            <div class="js_input_container">
                                <?php foreach ($risposte as $risposta): ?>
                                <?php $risposte_checked = empty($risposta_compilata) ? null : json_decode($risposta_compilata['sondaggi_risposte_utenti_risposta_valore'], true);?>
                                <div class="checkbox">
                                    <label>
                                        <input type="hidden" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>][]" id="risposta_multipla" value="<?php echo DB_BOOL_FALSE; ?>" checked data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>">
                                        <input type="checkbox" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>][]" id="risposta_multipla" value="<?php echo $risposta['sondaggi_domande_risposte_id']; ?>" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>" <?php if (!empty($risposta_compilata) && in_array($risposta['sondaggi_domande_risposte_id'], $risposte_checked)): ?> checked<?php endif;?>>
                                        <?php echo $risposta['sondaggi_domande_risposte_risposta']; ?>
                                    </label>
                                </div>
                                <?php endforeach;?>
                            </div>
                            <?php endif;?>

                            <!-- Risposta Data -->
                            <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Data"): ?>
                            <div class="js_input_container">
                                <div class="input-group js_form_datepicker date">
                                    <input name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" type="text" class="form-control" value="<?php if ($risposta_compilata): ?><?php echo $risposta_compilata['sondaggi_risposte_utenti_risposta_valore']; ?><?php else: ?><?php echo date('d/m/Y'); ?><?php endif;?>">
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button"><i class="fas fa-calendar-alt"></i></button>
                                    </span>
                                </div>
                            </div>
                            <?php endif;?>

                            <!-- Risposta Ora -->
                            <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Ora"): ?>
                            <div class="js_input_container">
                                <div class="input-group">
                                    <input name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" type="text" class="form-control js_form_timepicker" value="<?php if ($risposta_compilata): ?><?php echo $risposta_compilata['sondaggi_risposte_utenti_risposta_valore']; ?><?php else: ?><<?php echo date('H:i'); ?><?php endif;?>" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-default-time="<?php echo date('H:i'); ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>">
                                    <span class="input-group-btn">
                                        <button class="btn btn-default" type="button"><i class="far fa-clock"></i></button>
                                    </span>
                                </div>
                            </div>
                            <?php endif;?>

                        </div>
                    </div>
                    <?php endforeach;?>

                    <?php //if ($section['step_finale']): ?>
                    <div class="col-sm-12">
                        <!-- Submitting form -->
                        <div class="alert alert-info" id="submitting_form" style="display: none;">
                            <h4>Salvataggio in corso...</h4>
                        </div>

                        <!-- Error on save -->
                        <div id="msg_compilazione_questionario" class="alert" style="display:none"></div>
                    </div>
                    <?php //endif;?>

                    <div class="col-sm-12">
                        <button class="btn btn-primary prev-btn" type="button"><i class="fas fa-chevron-left"></i> Indietro </button>

                        <?php if ($section['step_finale']): ?>
                        <button class="btn btn-success pull-right btn-save-form" data-step="<?php echo $section['step'] ?>" data-fine="1" type="submit">Salva <i class="fas fa-chevron-right"></i></button>
                        <?php else: ?>
                        <button class="btn btn-primary pull-right next-btn" data-step="<?php echo $section['step'] ?>" data-fine="0" type="button">Avanti <i class="fas fa-chevron-right"></i></button>
                        <button class="btn btn-success pull-right btn-save-form btn_save_step" data-step="<?php echo $section['step'] ?>" data-fine="1" type="submit">Salva e chiudi</button>
                        <?php endif;?>
                    </div>
                </div>
            </div>
            <?php endforeach;?>
        </div>
    </div>
</form>
<?php else: ?>
<!-- NON HO DOMANDE RAGGRUPPATE IN STEP, USO FORM NORMALE -->
<div class="row">
    <div class="col-sm-12">
        <form id="form_sondaggio" action="<?php echo base_url('sondaggi/sondaggi/save'); ?>" method="post">
            <?php add_csrf()?>
            <?php if (!empty($sondaggio_id)): ?><input type="hidden" name="sondaggio_id" value="<?php echo $sondaggio_id ?>"><?php endif;?>
            <input type="hidden" name="compilazione_id" value="<?php echo $compilazione_id ?? ''; ?>" class="js_compilazione_id">

            <div class="row">
                <?php if (empty($domande_sondaggio)): ?>
                <h3 class="text-danger">Non sono state definite le domande per il sondaggio selezionato</h3>
                <?php else: ?>

                <?php foreach ($domande_sondaggio as $domanda): ?>
                <?php
if (!empty($risposte_compilate) && array_key_exists($domanda['sondaggi_domande_id'], $risposte_compilate)) {
    $risposta_compilata = $risposte_compilate[$domanda['sondaggi_domande_id']];
} else {
    $risposta_compilata = null;
}

if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta singola" || $domanda['sondaggi_domande_tipologia_value'] === "Risposta singola - radio" || $domanda['sondaggi_domande_tipologia_value'] === "Risposta multipla - checkbox") {
    $risposte = $this->apilib->search('sondaggi_domande_risposte', [
        'sondaggi_domande_risposte_domanda_id' => $domanda['sondaggi_domande_id'],
    ]);
    //dump($risposte);
}
?>

                <div class="column_input_container col-sm-<?php echo $column_size[$domanda['sondaggi_domande_spazio_risposta_value']]; ?>">
                    <div class="form-group">
                        <div class="testo_domanda">
                            <?php
                                echo $domanda['sondaggi_domande_obbligatorio'] == DB_BOOL_FALSE ? $domanda['sondaggi_domande_domanda'] : $domanda['sondaggi_domande_domanda'] . '<span class="text-danger required_field">*</span>';
                            ?>
                        </div>

                        <!-- Risposta breve -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta breve"): ?>
                        <div class="js_input_container">
                            <input type="text" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" id="risposta_breve" rows="3" class="w-100 form-control" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>" value="<?php if ($risposta_compilata): ?><?php echo $risposta_compilata['sondaggi_risposte_utenti_risposta_valore']; ?><?php endif;?>"></input>
                        </div>
                        <?php endif;?>

                        <!-- Paragrafo -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Paragrafo"): ?>
                        <div class="js_input_container">
                            <textarea name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" id="paragrafo" rows="3" class="w-100 form-control" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>"><?php if ($risposta_compilata) { echo $risposta_compilata['sondaggi_risposte_utenti_risposta_valore']; }; ?></textarea>
                        </div>
                        <?php endif;?>

                        <!-- Risposta singola - Select -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta singola"): ?>
                        <div class="js_input_container">
                            <select name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" id="risposta_singola_select" class="form-control domanda_select" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>">
                                <option value=""></option>
                                <?php foreach ($risposte as $risposta): ?>
                                <option value="<?php echo $risposta['sondaggi_domande_risposte_id']; ?>" <?php if ($risposta_compilata && ($risposta_compilata['sondaggi_risposte_utenti_risposta_id'] == $risposta['sondaggi_domande_risposte_id'])): ?> selected<?php endif;?>><?php echo $risposta['sondaggi_domande_risposte_risposta']; ?></option>
                                <?php endforeach;?>
                            </select>
                        </div>
                        <?php endif;?>

                        <!-- Risposta singola - Radio -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta singola - radio"): ?>
                        <div class="js_input_container">
                            <?php foreach ($risposte as $risposta): ?>
                            <div>
                                <input type="radio" id="risposta_singola_radio" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" value="<?php echo $risposta['sondaggi_domande_risposte_id']; ?>" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>" <?php if (!empty($risposta_compilata) && ($risposta_compilata['sondaggi_risposte_utenti_risposta_id'] == $risposta['sondaggi_domande_risposte_id'])): ?> checked<?php endif;?> />
                                <label for="<?php echo $risposta['sondaggi_domande_risposte_risposta']; ?>"><?php echo $risposta['sondaggi_domande_risposte_risposta']; ?></label>
                            </div>
                            <?php endforeach;?>
                        </div>
                        <?php endif;?>

                        <!-- Risposta multipla - checkbox -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Risposta multipla - checkbox"): ?>
                        <div class="js_input_container">
                            <?php foreach ($risposte as $risposta): ?>
                            <?php $risposte_checked = empty($risposta_compilata) ? null : json_decode($risposta_compilata['sondaggi_risposte_utenti_risposta_valore'], true);?>
                            <div class="checkbox">
                                <label>
                                    <input type="hidden" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>][]" id="risposta_multipla" value="<?php echo DB_BOOL_FALSE; ?>" checked data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>">
                                    <input type="checkbox" name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>][]" id="risposta_multipla" value="<?php echo $risposta['sondaggi_domande_risposte_id']; ?>" data-domanda_id="<?php echo $domanda['sondaggi_domande_id']; ?>" data-trigger_on="<?php echo $domanda['sondaggi_domande_sbloccato_da'] ? $domanda['sondaggi_domande_sbloccato_da'] . ':' . $domanda['sondaggi_domande_valore_sblocco'] : ''; ?>" <?php if (!empty($risposta_compilata) && in_array($risposta['sondaggi_domande_risposte_id'], $risposte_checked)): ?> checked<?php endif;?>>
                                    <?php echo $risposta['sondaggi_domande_risposte_risposta']; ?>
                                </label>
                            </div>
                            <?php endforeach;?>
                        </div>
                        <?php endif;?>

                        <!-- Risposta Data -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Data"): ?>
                        <div class="js_input_container">
                            <div class="input-group js_form_datepicker date">
                                <input name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" type="text" class="form-control" value="<?php if ($risposta_compilata): ?><?php echo $risposta_compilata['sondaggi_risposte_utenti_risposta_valore']; ?><?php else: ?><?php echo date('d/m/Y'); ?><?php endif;?>">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button"><i class="fas fa-calendar-alt"></i></button>
                                </span>
                            </div>
                        </div>
                        <?php endif;?>

                        <!-- Risposta Ora -->
                        <?php if ($domanda['sondaggi_domande_tipologia_value'] === "Ora"): ?>
                        <div class="js_input_container">
                            <div class="input-group">
                                <input name="risposta[<?php echo $domanda['sondaggi_domande_id'] ?>]" type="text" class="form-control" value="<?php if ($risposta_compilata): ?><?php echo $risposta_compilata['sondaggi_risposte_utenti_risposta_valore']; ?><?php else: ?><?php echo date('d/m/Y'); ?><?php endif;?>">
                                <span class="input-group-btn">
                                    <button class="btn btn-default" type="button"><i class="far fa-clock"></i></button>
                                </span>
                            </div>
                        </div>
                        <?php endif;?>


                    </div>
                </div>
                <?php endforeach;?>

                <?php endif;?>
            </div>

            <!-- Submitting form -->
            <div class="alert alert-info" id="submitting_form" style="display: none;">
                <h4>Salvataggio in corso...</h4>
            </div>

            <!-- Error on save -->
            <div id="msg_compilazione_questionario" class="alert" style="display:none"></div>

            <div class="form-actions">
                <div class="row">
                    <div class="col-xs-12 text-center">
                        <button type="submit" class="btn btn-lg btn-success btn_submit"><?php e('Save');?></button>
                    </div>
                </div>
            </div>
        </form>

    </div>
</div>
<?php endif;?>

<script>
function loading(status) {
    if (status == true) {
        return true;
    } else {
        return false;
    }
}

$(function() {
    $('.domanda_select').select2();


    var array_selected_checkbox = [];
    //Show / hide fields based on condition
    $("#form_sondaggio :input").on("change", function() {

        var changed_input = $(this);
        const sbloccato_da_domanda = $(this).data("domanda_id");
        console.log('sbloccato da domanda: ', sbloccato_da_domanda);
        console.log('changed_input ', changed_input, changed_input.val());
        $(':input[data-trigger_on*="' + sbloccato_da_domanda + '"]', changed_input.closest("form")).each(function() {

            if (changed_input.attr('type') == 'radio') {
                if (changed_input.prop("checked") === true) {
                    var value_of_dependent_field = changed_input.val();
                }
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
                    console.log('checkbox selezionata, valore dipendente: ', value_of_dependent_field)
                } else {
                    //Devo rimuovere il valore appena deselezionato dall'array dei selezionati
                    array_selected_checkbox = array_selected_checkbox.filter(selected => selected != value_of_dependent_field);
                    console.log('checkbox deselezionata, valore dipendente: ', value_of_dependent_field)
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
    $("#form_sondaggio :input").trigger("change");




    $('#smartwizard').smartWizard({
        theme: 'default',
        keyNavigation: false,
        backButtonSupport: true,
        toolbarSettings: {
            toolbarPosition: 'none',
        },
        transitionEffect: 'fade',
        useURLhash: false,
        showStepURLhash: false,
        includeFinishButton: false,

        anchorSettings: {
            anchorClickable: true,
            enableAllAnchors: true,
            markDoneStep: true,
            enableAnchorOnDoneStep: true
        }
    });

    /**
     * Torna allo step precedente
     */
    $('.prev-btn').on('click', function(e) {
        e.preventDefault();
        $('#smartwizard').smartWizard("prev");
    });

    /**
     * Avanza allo stato successivo
     */
    $('.next-btn').on('click', function(e) {
        e.preventDefault();
        $('#smartwizard').smartWizard("next");
    });

    /**
     * Salva form
     */

    $('form#form_sondaggio').on('submit', function(e) {
        e.preventDefault();
        $('.btn-save-form').prop('disabled', true);

        var this_form = $(this);
        //Svuoto e nascondo eventuale msg di errore del salvataggio precedente
        $('#msg_compilazione_questionario', this_form).html('').hide();
        //Mostro loading testuale
        $('#submitting_form', this_form).show();

        var formData = new FormData(this);
        // Display the key/value pairs
        for (const pair of formData.entries()) {
            console.log(`${pair[0]}, ${pair[1]}`);
        }


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
                console.log(res);
                if (res.status == '0') {
                    //Nascondo loading
                    $('#submitting_form', this_form).hide();
                    //Mostro errore e abilito button
                    $('#msg_compilazione_questionario', this_form).html(res.txt).removeClass('alert-success').addClass('alert-danger').show();
                    $('.btn-save-form').prop('disabled', false);
                    /**
                     * Imposto compilazione id, nel controller se ho questo campo in input non devo creare alcun sondaggio_compilazione perchè sto "modificando" sempre lo stesso
                     * Questo avviene nel caso in cui non ho risposto a tutte le domande obbligatorie, invio form, torna errore, compilo tutte le obbligatorie ed invio di nuovo
                     */
                    if (res.compilazione_id) {
                        $('.js_compilazione_id').val(res.compilazione_id);
                    }
                    return false;
                } else {
                    //Nascondo loading
                    $('#submitting_form', this_form).hide();
                    //Mostro errore e abilito button
                    $('#msg_compilazione_questionario', this_form).html(res.txt).removeClass('alert-danger').addClass('alert-success').show();
                    $('.btn-save-form').prop('disabled', false);
                }

                setTimeout(function() {
                    location.reload();
                }, 2000);
                return false;
            },
            error: function(xhr, ajaxOptions, thrownError) {
                console.log(xhr);
                console.log(ajaxOptions);
                //Nascondo loading e abilito button
                $('#submitting_form', this_form).hide();
                $('.btn-save-form').prop('disabled', false);
            }
        });

        return false;
    });


});
</script>

<?php endif;?>