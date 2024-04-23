<?php
    $settings = $this->apilib->searchFirst('settings');

    $title = (!empty($title)) ? $title : t('Stampa PDF');
?>

<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title><?php echo $title; ?></title>

        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.4.1/css/bootstrap.min.css" />
    </head>

    <body>
        <div class="row">
            <div class="col-sm-6">
                <!-- <img src="<?php echo base_url('uploads/' . $settings['settings_company_logo']); ?>" class="img-responsive"> -->
            </div>

            <div class="col-sm-6">
                <p style="text-align: right !important;">
                    <strong><?php echo $settings['settings_company_name']; ?></strong> <br />
                    <?php echo $settings['settings_company_address'] ?> -
                    <?php echo $settings['settings_company_city'] ? $settings['settings_company_city'] : '/' ?><br />
                    <?php e('Phone'); echo $settings['settings_company_telephone'] ? " " . $settings['settings_company_telephone'] : '/' ?>
                    <?php echo t('CF'), ': ', $settings['settings_company_codice_fiscale'] ? $settings['settings_company_codice_fiscale'] : '/'; ?>
                    -
                    <?php echo t('P.IVA'), ': ', $settings['settings_company_vat_number'] ? $settings['settings_company_vat_number'] : '/'; ?>
                </p>
            </div>
        </div>

        <?php echo $html; ?>
    </body>

</html>