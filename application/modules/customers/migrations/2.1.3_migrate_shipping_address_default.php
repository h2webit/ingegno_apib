<?php

/** Migrazione campo default shipping address */

try {
    echo_log('info', "MIGRATION customers: Migrating default shipping address...");
    
    $query = "UPDATE customers_shipping_address csa
    JOIN customers c ON c.customers_id = csa.customers_shipping_address_customer_id
    SET csa.customers_shipping_address_default = (
        CASE
            WHEN LOWER(csa.customers_shipping_address_street) = LOWER(c.customers_address)
                 AND LOWER(csa.customers_shipping_address_city) = LOWER(c.customers_city)
                 AND (
                     (c.customers_group = 1 AND csa.customers_shipping_address_type = 2) OR
                     (c.customers_group = 2 AND csa.customers_shipping_address_type = 1)
                 )
            THEN 1
            ELSE 0
        END
    )
    WHERE csa.customers_shipping_address_customer_id IN (
        SELECT c2.customers_id
        FROM customers c2
        JOIN customers_shipping_address csa2
        ON c2.customers_id = csa2.customers_shipping_address_customer_id
        WHERE csa2.customers_shipping_address_type IN (1, 2)
        AND (csa2.customers_shipping_address_default = 0 OR csa2.customers_shipping_address_default IS NULL)
    )";
    $this->db->query($query);
} catch (Exception $e) {
    echo_log('error', "ERRORE MIGRATION customers: ". $e->getMessage());
}