<?php
if (!$value_id) return;

$sede = $this->apilib->view('customers_shipping_address', $value_id);
$settings = $this->db->get('customers_settings')->row_array();

$mittente = str_replace_placeholders($settings['customers_settings_shipping_address_tpl_mittente'], $sede);
$destinatario = str_replace_placeholders($settings['customers_settings_shipping_address_tpl_destinatario'], $sede);
?>

<style>
    .big-font-size {
        font-size: 3rem;
    }
    
    .big-font-size .title {
        font-weight: bold;
    }
    
    .big-font-size .body {
        font-size: 2.5rem !important;
    }
</style>

<div class="big-font-size">
    <p class="title">Mittente:</p>
    <div class="body" style='margin-left: 50px;'><?php echo $mittente?></div>
    
    <p class="title" style="margin-top: 20px;">Destinatario:</p>
    <div class="body" style='margin-left: 50px;'><?php echo $destinatario ?></div>
</div>
