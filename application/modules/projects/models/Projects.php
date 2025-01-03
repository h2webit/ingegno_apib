<?php

class Projects extends CI_Model
{
    // Get payments
    public function get_project_total_payments($project_id)
    {
        // Payments canceled excluded
        $payments['all'] = $this->db->query("SELECT SUM(payments_amount) AS s FROM payments WHERE payments_project = '$project_id' AND payments_canceled = '" . DB_BOOL_FALSE . "' ")->row()->s;
        $payments['fatturato'] = $this->db->query("SELECT SUM(payments_amount) AS s FROM payments WHERE payments_project = '$project_id' AND payments_invoice_sent = '" . DB_BOOL_TRUE . "' AND payments_canceled = '" . DB_BOOL_FALSE . "' ")->row()->s;
        $payments['approvato'] = $this->db->query("SELECT SUM(payments_amount) AS s FROM payments WHERE payments_project = '$project_id' AND payments_approved = '" . DB_BOOL_TRUE . "' AND payments_canceled = '" . DB_BOOL_FALSE . "' ")->row()->s;
        $payments['pagato'] = $this->db->query("SELECT SUM(payments_amount) AS s FROM payments WHERE payments_project = '$project_id' AND payments_paid = '" . DB_BOOL_TRUE . "' AND payments_canceled = '" . DB_BOOL_FALSE . "'")->row()->s;
        $payments['non_pagato'] = $this->db->query("SELECT SUM(payments_amount) AS s FROM payments WHERE payments_project = '$project_id' AND payments_paid = '" . DB_BOOL_FALSE . "' AND payments_canceled = '" . DB_BOOL_FALSE . "'")->row()->s;

        return $payments;
    }

    // Get project worked hours
    public function get_project_worked_hours($project_id)
    {
        $data['eta'] = $this->db->query("SELECT projects_estimated_hours FROM projects WHERE projects_id = '$project_id'")->row()->projects_estimated_hours;

        if ($this->datab->module_installed('timesheet') || $this->datab->module_installed('firecrm')) {
            $data['worked_hours'] = $this->db->query("SELECT SUM(timesheet_total_hours) as s FROM timesheet LEFT JOIN tasks ON tasks.tasks_id = timesheet.timesheet_task WHERE (tasks_billable_hours IS NULL OR tasks_billable_hours = 0) AND timesheet_project = '{$project_id}'")->row()->s;
            $data['hours_last_30_days'] = $this->db->query("SELECT SUM(timesheet_total_hours) as s FROM timesheet WHERE timesheet_project = '{$project_id}' AND timesheet_creation_date > (NOW() - INTERVAL 30 day)")->row()->s;
            $data['worked_hours_cost'] = $this->db->query("SELECT SUM(timesheet_total_cost) as s FROM timesheet LEFT JOIN tasks ON tasks.tasks_id = timesheet.timesheet_task WHERE (tasks_billable_hours IS NULL OR tasks_billable_hours = 0) AND timesheet_project = '{$project_id}'")->row()->s;
        } else {
            $data['worked_hours'] = 0;
            $data['hours_last_30_days'] = 0;
            $data['worked_hours_cost'] = 0;
        }
        return $data;
    }

    // Get project progress / task
    public function get_progress_task($project_id)
    {
        if ($this->datab->module_installed('tasks') || $this->datab->module_installed('firecrm')) {
            $total_tasks_hours = $this->db->query("SELECT SUM(CASE WHEN (tasks_estimated_hours>0) THEN tasks_estimated_hours ELSE 1 END) as s FROM tasks WHERE tasks_project_id = '{$project_id}' AND tasks_status IN (SELECT tasks_status_id FROM tasks_status WHERE tasks_status_done_status = '" . DB_BOOL_TRUE . "' OR tasks_status_todo_status = '" . DB_BOOL_TRUE . "')")->row()->s;
            $closed_tasks_hours = $this->db->query("SELECT SUM(CASE WHEN (tasks_estimated_hours>0) THEN tasks_estimated_hours ELSE 1 END) as s FROM tasks WHERE tasks_project_id = '{$project_id}' AND tasks_status IN (SELECT tasks_status_id FROM tasks_status WHERE tasks_status_done_status = '" . DB_BOOL_TRUE . "')")->row()->s;
    
            $project_progress = ($total_tasks_hours > 0) ? (100 * $closed_tasks_hours) / $total_tasks_hours : 0;
        } else {
            $project_progress = 0;
        }

        return $project_progress;
    }

    // Get interventi hours
    public function get_interventi_hours($project_id)
    {
        if ($this->datab->module_installed('tickets-report') || $this->datab->module_installed('firecrm')) {
            // Calcola le ore lavorate per progetto
            $query_hours_lavorate = "
                SELECT SUM(
                    TIMESTAMPDIFF(SECOND, tickets_reports_start_time, tickets_reports_end_time) / 3600 * COALESCE(num_tecnici, 1)
                ) AS total_hours_lavorate
                FROM (
                    SELECT
                        tr.tickets_reports_start_time,
                        tr.tickets_reports_end_time,
                        COUNT(trt.tickets_reports_tecnici_id) AS num_tecnici
                    FROM tickets_reports tr
                    LEFT JOIN tickets_reports_tecnici trt ON tr.tickets_reports_id = trt.tickets_reports_id
                    WHERE tr.tickets_reports_project_id = {$project_id}
                    GROUP BY tr.tickets_reports_id
                ) AS subquery;
            ";

            $result_hours_lavorate = $this->db->query($query_hours_lavorate)->row_array();
            $hours['lavorate'] = round($result_hours_lavorate['total_hours_lavorate'], 2);

            // Calcola le ore fatturate per progetto
            $query_hours_fatturato = "
                SELECT SUM(tickets_reports_billable_hours) AS total_hours_fatturato
                FROM tickets_reports
                WHERE tickets_reports_project_id = {$project_id};
            ";

            $result_hours_fatturato = $this->db->query($query_hours_fatturato)->row_array();
            $hours['fatturate'] = $result_hours_fatturato['total_hours_fatturato'];
        } else {
            $hours['lavorate'] = 0;
            $hours['fatturate'] = 0;
        }

        return $hours;
    }

    public function get_estimated_hours($project_id)
    {
        return $this->db->where('projects_id', $project_id)->get('projects')->row()->projects_estimated_hours;
    }

    public function get_eta_worked($project_id)
    {
        $worked = $this->get_project_worked_hours($project_id)['worked_hours'];

        $estimated = $this->get_estimated_hours($project_id);

        // creo percentuale tra le due

        $percent = ($estimated > 0) ? ($worked / $estimated) * 100 : 0;

        return $percent;
    }

    // Get billable hours amount
    public function get_billable_hours_balance($project_id)
    {
        return $this->db->query("SELECT SUM(billable_hours_hours) as s FROM billable_hours WHERE billable_hours_project_id = '{$project_id}'")->row()->s;
    }

    public function get_project_expenses($project_id)
    {
        return $this->db->query("SELECT SUM(CASE  WHEN expenses_type = 1 THEN expenses_amount ELSE expenses_amount*(-1) END) as s FROM expenses WHERE expenses_project_id = '{$project_id}'")->row()->s;
    }

    public function get_project_orders($project_id)
    {
        $orders = $this->apilib->search('documenti_contabilita_commesse', "documenti_contabilita_commesse_projects_id = '$project_id' AND documenti_contabilita_tipo IN (1,5,6,14)");

        $total['count_ordini_cliente'] = 0;
        $total['count_ordini_fornitore'] = 0;
        $total['count_ordini_interni'] = 0;
        $total['count_fatturato'] = 0;

        $total['ordini_cliente'] = 0;
        $total['ordini_fornitore'] = 0;
        $total['ordini_interni'] = 0;
        $total['fatturato'] = 0;

        foreach ($orders as $order) {

            switch ($order['documenti_contabilita_tipo']) {
                case 1:
                    $total['count_fatturato']++;
                    $total['fatturato'] += $order['documenti_contabilita_competenze'];
                    break;
                case 5:
                    $total['count_ordini_cliente']++;
                    $total['ordini_cliente'] += $order['documenti_contabilita_competenze'];
                    break;
                case 6:
                    $total['count_ordini_fornitore']++;
                    $total['ordini_fornitore'] += $order['documenti_contabilita_competenze'];
                    break;
                case 14:
                    $total['count_ordini_interni']++;
                    $total['ordini_interni'] += $order['documenti_contabilita_competenze'];
                    break;
            }

        }
        return $total;
    }

    public function get_project_mol($project_id)
    {
        $hour_cost = $this->get_project_worked_hours($project_id)['worked_hours_cost'];
        $expenses = $this->get_project_expenses($project_id);
        $orders = $this->get_project_orders($project_id);

        $mol['total_ordinato'] = $orders['ordini_cliente'] - ($hour_cost + $expenses + $orders['ordini_fornitore']);
        $mol['total_fatturato'] = $orders['fatturato'] - ($hour_cost + $expenses + $orders['ordini_fornitore']);

        $mol['ordinato_percentage'] = ($orders['ordini_cliente'] > 0) ? ($mol['total_ordinato'] / $orders['ordini_cliente']) * 100 : 0;
        $mol['fatturato_percentage'] = ($orders['fatturato'] > 0) ? ($mol['total_fatturato'] / $orders['fatturato']) * 100 : 0;

        return $mol;
    }

    public function get_last_projects($user_id)
    {
        $last_tasks = $this->db->query("
        SELECT
            *
        FROM
            projects
        WHERE
        projects_status < 4
        ORDER BY projects_id DESC
        LIMIT 100")->result_array();

        return $last_tasks;
    }

    public function get_current_project($user_id)
    {
        $last_task = $this->apilib->searchFirst(
            'timesheet',
            [
                "timesheet_end_time IS NULL AND timesheet_member = '$user_id'",
                'projects_status <' => '4',
            ],
            0,
            null,
            'ASC',
            2
        );
        return $last_task;
    }

    // Get rapportini hours
    public function get_rapportini_hours($project_id)
    {
        if ($this->datab->module_installed('rapportini')) {
            // Calcola le ore lavorate in rapportini per progetto
            $query_ore_rapportini = "
                SELECT 
                    COALESCE(SUM(TIME_TO_SEC(rapportini_ora_fine) - TIME_TO_SEC(rapportini_ora_inizio)) / 3600, 0) AS totale_ore_lavorate
                FROM 
                    rapportini
                WHERE 
                    rapportini_commessa = '{$project_id}' AND (rapportini_ora_inizio IS NOT NULL AND rapportini_ora_fine IS NOT NULL);
            ";

            $result_hours_rapportini = $this->db->query($query_ore_rapportini)->row_array();

            $hours['lavorate'] = number_format($result_hours_rapportini['totale_ore_lavorate'], 2, ',', '.');
        } else {
            $hours['lavorate'] = number_format(0, 2, ',', '.');
            ;
        }

        return $hours;
    }
    
    // ATTENZIONE, QUESTA IDENTICA FUNZIONE E' STATA USATA ANCHE SUL MODEL Leads_model NEL MODULO SALES. SARANNO ENTRAMBE DA DISMETTERE QUANDO SI PORTERA' NATIVA LA FUNZIONE
    public function genera_codice_commessa($format, $last_code) {
        // Ottiene l'anno corrente
        $current_year = date('Y');
        
        // Inizializza i placeholder con valori predefiniti
        $placeholders = [
            'incr' => '',
            'year2' => substr($current_year, -2),
            'year4' => $current_year,
            'reset_year' => false
        ];
        
        // Estrae i placeholder e i loro valori dal formato fornito
        preg_match_all('/{(\w+)\s*(\d*)}/', $format, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $key = $match[1];
            $value = isset($match[2]) ? $match[2] : '';
            if (!empty($match[2])) {
                $placeholders[$key] = $value;
            }
        }
        
        // Analizza l'ultimo codice per estrarre l'incremento e l'anno
        preg_match_all('/(\d+)/', $last_code, $last_parts);
        $extracted_numbers = $last_parts[0];
        $last_increment = isset($extracted_numbers[0]) ? intval($extracted_numbers[0]) : 0;
        $last_year = isset($extracted_numbers[1]) ? $extracted_numbers[1] : '';
        
        // Determina se è necessario resettare l'incremento
        $reset_needed = false;
        if ($placeholders['reset_year']) {
            if (strpos($format, '{year2}') !== false) {
                $reset_needed = $last_year != $placeholders['year2'];
            } elseif (strpos($format, '{year4}') !== false) {
                $reset_needed = $last_year != $placeholders['year4'];
            }
        }
        
        // Imposta il nuovo incremento
        $new_increment = $reset_needed ? 1 : $last_increment + 1;
        
        // Genera il nuovo codice sostituendo i placeholder
        $new_code = $format;
        $new_code = preg_replace_callback('/{(\w+)\s*(\d*)}/', function($match) use ($placeholders, $new_increment) {
            $key = $match[1];
            if ($key == 'incr') {
                // Aggiunge padding all'incremento se necessario
                $padding = $placeholders['incr'];
                return str_pad($new_increment, $padding, '0', STR_PAD_LEFT);
            } elseif ($key == 'year2' || $key == 'year4') {
                // Sostituisce con l'anno a 2 o 4 cifre
                return $placeholders[$key];
            } elseif ($key == 'reset_year') {
                // Rimuove completamente il placeholder reset_year
                return '';
            }
            // Restituisce il placeholder originale se non riconosciuto
            return $match[0];
        }, $new_code);
        
        // Rimuove eventuali spazi doppi creati dalla rimozione di reset_year
        $new_code = preg_replace('/\s+/', ' ', trim($new_code));
        
        return $new_code;
    }
}
