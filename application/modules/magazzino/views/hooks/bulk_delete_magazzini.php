

<?php
$magazzini = $this->apilib->search('magazzini', [],null,0, null, 'asc', 1);
//debug($magazzini);
$this->layout->addModuleJavascript('magazzino', 'sweetalert.js'); 

?>

<form style="display: hidden" action="<?php echo base_url(); ?>magazzino/movimenti/deleteMagazzini" method="POST"
      id="form_bulk_delete_magazzini">
    <?php add_csrf(); ?>

    <input type="hidden" id="bulk_magazzini_ids" name="ids" value=""/>
    <input type="hidden" id="bulk_magazzino_replace" name="magazzino_replace" value=""/>
    
</form>

<script>
    $(document).ready(function () {
        $('.js-bulk-action').each(function (index) {
            var grid_container = $(this).closest('div[data-layout-box]');

            //aggiungo opzione download
            $(this).append('<option value="elimina_magazzini">Elimina...</option>');
            
            $(this).on('change', function () {
                var chkbx_ids = $("input:checkbox.js_bulk_check:checked", grid_container).map(function () {
                    return $(this).val();
                }).get();

                if (chkbx_ids.length <= 0) {
                    $("input:checkbox.js_bulk_check:checked", grid_container).val('').trigger('change');
                    return false;
                }

                

                if ($(this).val() == 'elimina_magazzini') {
                    const magazzini_obj = <?php e_json($magazzini); ?>;
                    let magazzini = {};
                    console.log(chkbx_ids);
                    console.log(magazzini_obj)
                    magazzini_obj.forEach(magazzino => {
                        //Lo aggiungo solo se non è tra quelli selezionati
                        if (chkbx_ids.indexOf(magazzino.magazzini_id) === -1) {
                            magazzini[magazzino.magazzini_id] = magazzino.magazzini_titolo;
                        }
                    });
                     Swal.fire({
                        title: 'Scelta magazzino',
                        input: 'select',
                        inputOptions: magazzini,
                        inputPlaceholder: '- Seleziona il magazzino su cui trasferire la merce eventualmente movimentata',
                        showCancelButton: true,
                        showLoaderOnConfirm: true,
                        inputValidator: (value) => {
                            return new Promise((resolve) => {
                                if (value) {
                                    resolve()
                                } else {
                                    resolve('Devi selezionare un valore')
                                }
                            })
                        }
                    }).then(choosen => {
                        if (choosen.isConfirmed) {
                            $('#bulk_magazzino_replace', $('#form_bulk_delete_magazzini')).val(choosen.value);
                            $('#bulk_magazzini_ids', $('#form_bulk_delete_magazzini')).val(JSON.stringify(chkbx_ids));

                            $('#form_bulk_delete_magazzini').submit();

                            
                        }
                    });
                    
                        
                    
                }
            });
        });

    });
</script>
