<style>
    #turni_massivi .formColumn{
        margin-top:10px;
    }
</style>
<?php
$dipendenti = $this->apilib->search('dipendenti', ['dipendenti_attivo' => 1]);
$giorni = $this->apilib->search('orari_di_lavoro_giorno');
$pause = $this->apilib->search('orari_di_lavoro_ore_pausa', '', '', 0, 'orari_di_lavoro_ore_pausa_value');
?>
<form id="turni_massivi">
    <div class="form-body">
        <div class="row sortableForm">
            <div class="formColumn js_container_field col-md-12">
                <label for="select1">Dipendente:</label>
                <select name="dipendenti[]" class="form-control" id="select2" multiple>
                    <?php foreach($dipendenti as $dipendente): ?>
                        <?php
                        echo '<option value="' . $dipendente['dipendenti_id'] . '">' . $dipendente['dipendenti_cognome'] . ' ' . substr($dipendente['dipendenti_nome'], 0, 1) . '.</option>';
                        ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="formColumn js_container_field col-md-12">
                <label for="select1">Giorno:</label>
                <select name="giorni[]" class="form-control" id="select1" multiple>
                    <?php foreach($giorni as $giorno): ?>
                    <?php echo '
                    <option value="'.$giorno['orari_di_lavoro_giorno_numero'].'">'.$giorno['orari_di_lavoro_giorno_value'].'</option>'; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="formColumn js_container_field col-md-6">
                <div class="form-group">
                    <label class="control-label">Ora inizio</label>
                    <input name="turni_di_lavoro_ora_inizio" type="time" class="form-control timepicker">
                </div>
            </div>
            <div class="formColumn js_container_field col-md-6">
                <div class="form-group">
                    <label class="control-label">Ora fine</label>
                    <input name="turni_di_lavoro_ora_fine" type="time" class="form-control timepicker">
                </div>
            </div>
            <div class="formColumn js_container_field col-md-6">
                <label for="select1">Ore di pausa:</label>
                <select name="pausa" class="form-control">
                    <?php foreach($pause as $pausa): ?>
                    <?php echo '
                    <option value="'.$pausa['orari_di_lavoro_ore_pausa_id'].'">'.$pausa['orari_di_lavoro_ore_pausa_value'].' h</option>'; ?>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <fieldset class="js_form_fieldset">

            <legend><span>Mostra</span><span style="display:none;">Nascondi</span> Orario notturno <i class="fa fa-arrow-right"></i></legend>
            <div class="row sortableForm">
                <div class="formColumn js_container_field col-md-6">
                    <div class="form-group">
                        <label class="control-label">Ora inizio notturno</label>
                        <input name="turni_di_lavoro_ora_inizio_notturno" type="time" class="form-control timepicker">
                    </div>
                </div>
                <div class=" formColumn js_container_field col-md-6">
                    <div class="form-group">
                        <label class="control-label">Ora fine notturno</label>
                        <input name="turni_di_lavoro_ora_fine_notturno" type="time" class="form-control timepicker">
                    </div>
                </div>
            </div>
        </fieldset>
        <div class="form-actions">                   
            <div class="pull-right">
                <button type="submit" class="btn btn-primary">Salva</button>
            </div>
        </div>
    </div>
</form>
<script>
    // Inizializza i campi di selezione multipla con Select2
    $(document).ready(function() {
        $('#select1').select2();
        $('#select2').select2();
    });

    // Gestione del submit del form
    $("#turni_massivi").submit(function (event) {
        event.preventDefault(); // Previeni il comportamento predefinito del submit

        var formData = $(this).serialize(); // Raccogli i dati del form
        formData += '&<?php echo $this->security->get_csrf_token_name(); ?>=<?php echo $this->security->get_csrf_hash(); ?>';


        $.ajax({
            type: "POST",
            url: "<?php echo base_url('modulo-hr/turni/creazione_massiva'); ?>", // Sostituisci "url_del_controller" con l'URL del tuo controller
            data: formData,
            dataType: "json", // Imposta il tipo di dati previsto come JSON
            success: function (data) {
                console.log(data);
                // Gestisci la risposta di successo
                if (data.success) {
                    // Fai qualcosa se la richiesta ha avuto successo, ad esempio ricarica la pagina
                    location.reload();
                } else {
                    // Mostra un messaggio di errore se la richiesta non ha avuto successo
                    alert(data.error);
                }
            },
            error: function (xhr, status, error) {
                // Gestisci gli errori Ajax
                alert("Si è verificato un errore: " + error);
            }
        });
    });
</script>
