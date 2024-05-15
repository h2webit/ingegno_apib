

<form style="display: hidden" action="<?php echo base_url(); ?>modules-permissions/modules_permissions/massive_import" method="POST"
    id="form_bulk_modules_permissions">
    <?php add_csrf(); ?>

    <input type="hidden" id="bulk_modules_permissions_ids" name="ids" value="" />
    

</form>

<script>
    $(document).ready(function () {
        $('.js-bulk-action').each(function (index) {
            var grid_container = $(this).closest('div[data-layout-box]');

            //aggiungo opzione download
            $(this).append('<option value="Import">Import permissions</option>');

            $(this).on('change', function () {
                var chkbx_ids = $("input:checkbox.js_bulk_check:checked", grid_container).map(function () {
                    return $(this).val();
                }).get();

                if (chkbx_ids.length <= 0) {
                    $("input:checkbox.js_bulk_check:checked", grid_container).val('').trigger('change');
                    return false;
                }



                if ($(this).val() == 'Import') {

                    
                    
                    $('#bulk_modules_permissions_ids').val(JSON.stringify(chkbx_ids));
                    $('#form_bulk_modules_permissions').submit();


                }
            });
        });

    });
</script>