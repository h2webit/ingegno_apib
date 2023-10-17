<?php

/**
 * Migrazione campo preview unico per i clienti
 */

log_message('info', "INIZIO MIGRATION customers");

try {
    log_message('info', "Aggiorno i clienti con nome e cognome");
    $this->db->query("UPDATE customers SET customers_full_name = CONCAT(customers_name, ' ', customers_last_name) WHERE customers_company IS NULL OR customers_company = ''");
} catch (Exception $e) {
    log_message('error', "ERRORE MIGRATION customers: ". $e->getMessage());
}

try {
    log_message('info', 'Aggiorno i clienti con ragione sociale');
    $this->db->query("UPDATE customers SET customers_full_name = customers_company WHERE customers_company IS NOT NULL AND customers_company <> ''");
} catch (Exception $e) {
    log_message('error', 'ERRORE MIGRATION customers: ' . $e->getMessage());
}

log_message('info', "FINE MIGRATION customers");
