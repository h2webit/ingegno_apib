<?php

/**
 * Migrazione campo country testuale a tendina della nazione per le sedi cliente
 */

echo_log('debug', "INIZIO MIGRATION customers");

try {
    echo_log('debug', "Aggiorno le sedi prendendo il country_id dall'anagrafica principale");

    $this->apilib->clearCache();
    $shipping_addresses = $this->apilib->search('customers_shipping_address', ["(customers_country_id IS NOT NULL AND customers_country_id <> '')"]);

    $t = count($shipping_addresses);
    $c = 0;
    if (!empty($shipping_addresses)) {
        foreach ($shipping_addresses as $shipping_address) {
            $this->db->where('customers_shipping_address_id', $shipping_address['customers_shipping_address_id'])->update('customers_shipping_address', ['customers_shipping_address_country_id' => $shipping_address['customers_country_id']]);

            $c++;

            progress($c, $t);
        }
        $this->apilib->clearCache();
    }
} catch (Exception $e) {
    echo_log('error', "ERRORE MIGRATION customers: ". $e->getMessage());
}


$field = $this->db->where('fields_name', 'customers_shipping_address_country')->get('fields')->row();

if (!empty($field)) {
    echo_log('debug', "Elimino il vecchio campo customers_shipping_address_country");
    $deleted = $this->entities->deleteField($field->fields_id, true);
}

echo_log('debug', "FINE MIGRATION customers");
