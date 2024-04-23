<?php
$dipendente_id = $this->auth->get('dipendenti_id');
if(!empty($dipendente_id)) :

$dipendente = $this->apilib->view('dipendenti', $dipendente_id);

$form_id = $this->datab->get_form_id_by_identifier('form-dashboard-dipendente');
$form = $this->datab->get_form($form_id, null);


// Mostro form se timbra entrata uscita è disabilitato o vuoto e se posteriori è attivo
if(
    (empty($dipendente['dipendenti_timbra_entrata_uscita']) || $dipendente['dipendenti_timbra_entrata_uscita'] == DB_BOOL_FALSE) 
    && $dipendente['dipendenti_timbra_posteriori'] == DB_BOOL_TRUE
) {
    $this->load->view("pages/layouts/forms/form_{$form['forms']['forms_layout']}", [
        'form' => $form,
        'ref_id' => $form_id,
        'value_id' => null,
    ], false);

} else if(
    (empty($dipendente['dipendenti_timbra_entrata_uscita']) || $dipendente['dipendenti_timbra_entrata_uscita'] == DB_BOOL_TRUE)
    && $dipendente['dipendenti_timbra_posteriori'] == DB_BOOL_FALSE
) :
    // Mostro button se timbra entrata uscita è abilitato e se posteriori è disabilitato o vuoto

    // Cerco presenza aperta per oggi, se la trovo disabilito btn entrata
    $presenze_odierna = $this->apilib->search('presenze', [
        'presenze_dipendente' => $dipendente_id, 
        'presenze_data_inizio' => date('Y-m-d'), 
        'presenze_data_fine IS NULL or presenze_data_fine = ""'
    ]);

    $entrata_timbrata = DB_BOOL_FALSE;
    
    if(!empty($presenze_odierna)) {
        $entrata_timbrata = DB_BOOL_TRUE;
    }

?>

<style>
.btns_container {
    display: flex;
    justify-content: space-around;
    flex-direction: column;
    gap: 24px;
}

.timbra_entrata,
.timbra_uscita {
    width: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 80px;
    font-weight: bold;
    font-size: 20px;
    margin-bottom: 24px;
}
</style>

<div class="row">
    <div class="col-sm-12">
        <button class="btn btn-primary timbra_entrata js_btn_timbra_entrata" <?php echo $entrata_timbrata == DB_BOOL_TRUE ? 'disabled' : ''; ?>>Timbra entrata</button>
    </div>
    <div class="col-sm-12">
        <button class="btn btn-primary timbra_uscita js_btn_timbra_uscita">Timbra uscita</button>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div id="msg_saving_timbratura" class="text-center"></div>
    </div>
</div>



<script>
var dipendente_id = '<?php echo $dipendente_id; ?>';

/**
 * ! Gestione timbra entrata
 */
$('body').on('click', '.js_btn_timbra_entrata', function(e) {
    e.stopImmediatePropagation();

    const now = new Date();
    const hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const time = `${hours}:${minutes}`;

    $('.js_btn_timbra_entrata').prop('disabled', true);
    $('#msg_saving_timbratura').append('<br /><br />Attendere, salvataggio entrata in corso...');

    request(base_url + 'modulo-hr/app/timbraEntrata', {
        dipendente_id: dipendente_id,
        ora_entrata: time,
        scope: 'DASHBOARD DIPENDENTE',
        [token_name]: token_hash
    }, 'POST', false, false, {}).then(res => {

        if (res && res.status == 0) {
            toast('Errore', 'error', res.txt, 'toastr', false);
            $('#msg_saving_timbratura').html("Errore: " + res.txt);
            $('.js_btn_timbra_entrata').prop('disabled', false);
            return;
        }

        if (res && res.status == 1) {
            toast('', 'success', res.txt, 'toastr', false);
            //$('#msg_saving_timbratura').html("Errore: " + res.message);
            $('#msg_saving_timbratura').html('');
            $('.js_btn_timbra_entrata').prop('disabled', true);

            refreshAjaxLayoutBoxes();
        }
    }).catch(error => {
        console.log(error);
        $('.js_btn_timbra_entrata').prop('disabled', false);
        $('#msg_saving_timbratura').html('');
        toast('Errore', 'error', 'Si è verificato un errore durante la timbratura di entrata', 'toastr', false);
    });
});


/**
 * ! Gestione timbra uscita
 */
$('body').on('click', '.js_btn_timbra_uscita', function(e) {
    e.stopImmediatePropagation();
    const now = new Date();
    const hours = now.getHours();
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const time = `${hours}:${minutes}`;

    $('.js_btn_timbra_uscita').prop('disabled', true);
    $('#msg_saving_timbratura').append('<br /><br />Attendere, salvataggio uscita in corso...');

    request(base_url + 'modulo-hr/app/timbraUscita', {
        dipendente_id: dipendente_id,
        ora_uscita: time,
        scope: 'DASHBOARD DIPENDENTE',
        [token_name]: token_hash
    }, 'POST', false, false, {}).then(res => {
        console.log(res);

        if (res && res.status == 0) {
            toast('Errore', 'error', res.txt, 'toastr', false);
            $('#msg_saving_timbratura').html("Errore: " + res.txt);
            $('.js_btn_timbra_uscita').prop('disabled', false);
            return;
        }

        if (res && res.status == 1) {
            localStorage.setItem('uscitaTimbrata', JSON.stringify({
                dipendente: dipendente_id,
                uscita: res.uscita
            }));
            toast('', 'success', res.txt, 'toastr', false);
            $('#msg_saving_timbratura').html('');
            $('.js_btn_timbra_uscita').prop('disabled', true);

            refreshAjaxLayoutBoxes();
        }
    }).catch(error => {
        console.log(error);
        $('.js_btn_timbra_uscita').prop('disabled', false);
        $('#msg_saving_timbratura').html('');
        toast('Errore', 'error', 'Si è verificato un errore durante la timbratura di uscita', 'toastr', false);
    });
})
</script>

<?php
    endif;
endif;
?>