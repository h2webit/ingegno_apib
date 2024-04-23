<?php
$settings = $this->apilib->searchFirst('settings');

$reparto = $this->apilib->view('reparti', $value_id);
if(empty($reparto['reparti_tag_nfc_code'])){
    $reparto['reparti_tag_nfc_code'] = substr(str_shuffle('0123456789'),1,12);
    $this->apilib->edit('reparti', $value_id, ['reparti_tag_nfc_code' => $reparto['reparti_tag_nfc_code']]);
}

if(!empty($reparto) && !empty($reparto['reparti_tag_nfc_code'])) :
?>

<div class="row" style="margin-bottom: 24px;">
    <div class="col-sm-6 col-sm-offset-3">
        <img src="<?php echo base_url('uploads/' . $settings['settings_company_logo']); ?>" class="img-responsive" style="max-height: 100px; margin: 0 auto;" alt="logo">
    </div>
</div>


<div class="row">
    <div class="col-sm-12 text-center">
        <h3><?php echo $reparto['reparti_nome']; ?></h3>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 text-center">
        <div style="padding: 32px;">
            <img src="https://api.qrserver.com/v1/create-qr-code/?data=<?php echo $reparto['reparti_tag_nfc_code']; ?>&amp;size=200x200" alt="" title="" />
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12 text-center text-uppercase">
        <h3>controllo accessi</h3>
    </div>
</div>
<?php endif; ?>