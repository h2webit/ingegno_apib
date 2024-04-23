<style>
    .btn-view {
        color: #000;
        text-align: center !important;
        background-color: #f5f5f5 !important;
    }

    .btn-active {
        color: #fff !important;
        background-color: #337ab7 !important;
        border-color: #337ab7 !important;
    }

    .font-bold {
        font-weight: bold;
    }

    .label-container {
        border: 2px solid black;
        padding: 5px;
        margin: 5px;
        border-radius: 2px;
        background-color: #f5f5f5;
        margin-left: 30%;
        margin-right: 30%;
    }

    .form_control{
        border: 1px solid #000;
    }
</style>

<div class="js_barcode_grid text-center">

    <div class="text-center js_barcode_container">

        <div class="row">
            <div class="col-md-12 ">
                Indica il numero di qrcode da generare:
            </div>
            <form class="formAjax" id="new_fattura" method="post" action="<?php echo base_url('modulo-hr/qrcode/genera_elenco_barcode'); ?>">

                <div class="col-md-12">
                    <div class="form-group">
                        <label>Quantità:</label>
                        <input class="form_control" type="text" name="quantita" value="1" size="3" /> 
                    </div>
                </div>
                <button type="submit" class="btn btn-success">Genera</button>

            </form>
        </div>
    </div>
</div>

<script>
    $(function() {


        $('.btn-print').on('click', function() {
            var quantita = $('[name="quantita"]').val();

            var _params = {
                quantita : quantita
            };
            var params = new URLSearchParams(_params).toString();

            var url = base_url + 'modulo-hr/qrcode/genera_elenco_barcode/?' + params;

            window.open(url);
        });
    });
</script>