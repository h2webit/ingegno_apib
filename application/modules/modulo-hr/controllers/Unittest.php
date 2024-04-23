<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Unittest extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('timbrature');
        
    }

    public function index($run = false) {

        $tests = [
            [
                'params' => [
                    'impostazioni_hr_range_minuti_entrata' => 15,
                    'impostazioni_hr_range_minuti_uscita' => 30,
                    'impostazioni_hr_attiva_chiusura_automatica' => 0,
                    'impostazioni_hr_banca_ore' => 1,
                    'impostazioni_hr_time_refresh' => 45,
                    'impostazioni_hr_range_calcolo' => 60,
                    'impostazioni_hr_reperibilita' => 1,
                    'impostazioni_hr_tolleranza_entrata' => 15,
                    'impostazioni_hr_correggi_anomalia' => 0,
                    'impostazioni_hr_ora_chiusura_automatica' => 1,
                    'impostazioni_hr_chiusura_dipendenti_straordinario' => 1,
                    'dipendenti_consenti_straordinari' => 0,
                    'dipendenti_trasferta_giornaliera' => 0,
                    'presenze' => [
                        [
                            'inizio' => '2024-02-13 09:00:00',
                            'fine' => '2024-02-13 19:30:00'
                        ]
                        ],
                    'dipendente_id' => 1,
                    'turni' => $this->apilib->search('turni_di_lavoro', ['turni_di_lavoro_dipendente' => 1]), //Mario Rossi il martedì ha 9-18 come orario con pausa pranzo

                ],
                'expected' => [
                    'ore_totali' => 9.5,
                    'ore_straordinario' => 1.5,
                    'ore_pausa' => 1,
                    'anomalia' => 0,
                    'buono_pasto' => 1,
                    'ore_ordinarie' => 8,
                    'ore_previste' => 8
                ]
            ],
            
        ];

        foreach ($tests as $test) {
            $this->test($test['params'], $test['expected']);
        }
    }
        public function test($params,$expected) {
                $impostazioni = $this->db->get_where('impostazioni_hr', ['impostazioni_hr_id' => 1])->row_array();
                $dipendente = $this->db->get_where('dipendenti', ['dipendenti_id' => $params['dipendente_id']])->row_array();
                
                $impostazioni_overrided = array_merge($impostazioni, $params);
                $dipentente_overrided = array_merge($dipendente, $params);

                //Sovrascrivo i dati dipendente e le impostazioni, dopo le ripristinerò
                //Tengo solo i campi che iniziano con "dipendenti_"
                $dipentente_overrided = array_filter($dipentente_overrided, function($key) {
                    return strpos($key, 'dipendenti_') === 0;
                }, ARRAY_FILTER_USE_KEY);
                //Tengo solo i campi che iniziano con "impostazioni_hr_"
                $impostazioni_overrided = array_filter($impostazioni_overrided, function($key) {
                    return strpos($key, 'impostazioni_hr_') === 0;
                }, ARRAY_FILTER_USE_KEY);
                

                $this->apilib->edit('dipendenti', $params['dipendente_id'],$dipentente_overrided);
                $this->apilib->edit('impostazioni_hr', $impostazioni['impostazioni_hr_id'], $impostazioni_overrided); 

                $timbrature_create = [];
                foreach ($params['presenze'] as $presenza) {
                    $simulazione = $this->simulaTimbratura($params, $presenza);
            //debug($simulazione);
                    $timbrature_create[] = $simulazione[0]['presenze_id'];
                    $timbrature_create[] = $simulazione[1]['presenze_id'];
                }
                
                $dipendente_id = $params['dipendente_id'];
                $presenze = $this->apilib->search('presenze', ['presenze_dipendente' => $dipendente_id]);
                $Ymd = date('Y-m-d', strtotime($presenze[0]['presenze_data_inizio']) );
                if (!$presenze) {
                    return 0;
                }
                $ore_strordinarie = 0;
                foreach ($presenze as $presenza) {
                    $ore_strordinarie += $this->timbrature->calcolaOreStraordinarieBase($presenza);
                }


                
                $result = [
                    'ore_totali' => $ore_strordinarie + $this->timbrature->calcolaOreOrdinarie($Ymd, $dipendente_id, $presenze),
                    'ore_straordinario' => $ore_strordinarie,
                    'ore_pausa' => $this->timbrature->calcolaOrePausaPranzo($Ymd, $dipendente_id, $presenze),
                    'anomalia' => 0,
                    'buono_pasto' => $this->timbrature->calcolaBuoniPasto($Ymd, $dipendente_id, $presenze),
                    'ore_ordinarie' => $this->timbrature->calcolaOreOrdinarie($Ymd, $dipendente_id, $presenze),
                    'ore_previste' =>  $this->timbrature->calcolaOrePreviste($Ymd, $dipendente_id)

                ];
                
                $this->apilib->edit('dipendenti', $params['dipendente_id'], $dipendente);
                $this->apilib->edit('impostazioni_hr', $impostazioni['impostazioni_hr_id'], $impostazioni);


                foreach (array_unique($timbrature_create) as $presenza_id) {
                    $this->apilib->delete('presenze', $presenza_id);
                }
                $this->compareResults($result, $expected);
    }
    public function compareResults($result, $expected) {
        foreach ($expected as $key => $value) {
            if ($result[$key] != $value) {
                echo "Test fallito: $key. Atteso: $value, ottenuto: " . $result[$key] . "<br>";
            } else {
                echo "Test passato: $key. Atteso: $value, ottenuto: " . $result[$key] . "<br>";
            }
        }
    }
    public function simulaTimbratura($params, $presenza) {
        $dipendente_id = $params['dipendente_id'];
        $entrata = strtotime($presenza['inizio']);
        $uscita = strtotime($presenza['fine']);
        $ora_entrata = date('H:i', $entrata);
        $ora_uscita = date('H:i', $uscita);

        $data_entata = date('Y-m-d', $entrata);
        $data_uscita = date('Y-m-d', $uscita);
        $scope = 'UNIT_TEST';
        $reparto = null;
        $latitude = null;
        $longitude = null;

        $entrata =$this->timbrature->timbraEntrata($dipendente_id, $ora_entrata, $data_entata, $scope, $reparto, null, null, $latitude, $longitude);
        $uscita = $this->timbrature->timbraUscita($dipendente_id, $ora_uscita, $data_uscita, null, $scope);
        return [$entrata, $uscita];
    }
    public function getRandomCombinations($num_random_tests = 20) {
        //TODO: Costruisco un array con tutte le variabili in gioco (matrice multidimensionale da leggere tramite foreach di foreach di foreach...)
        //TODO: i parametri da utilizzare e da mettere in combinazione saranno tutti quelli globali più quelli del dipendente, ovvero:
        //GLOBALI: impostazioni_hr_range_minuti_entrata, impostazioni_hr_range_minuti_uscita,impostazioni_hr_attiva_chiusura_automatica,impostazioni_hr_banca_ore
        //impostazioni_hr_time_refresh,impostazioni_hr_range_calcolo,impostazioni_hr_numeri,impostazioni_hr_reperibilita,impostazioni_hr_tolleranza_entrata,impostazioni_hr_correggi_anomalia,
        //impostazioni_hr_ora_chiusura_automatica,impostazioni_hr_chiusura_dipendenti_straordinario
        //DIPENDENTE: dipendenti_consenti_straordinari, dipendenti_trasferta_giornaliera,

        $globalParams = [
            'impostazioni_hr_range_minuti_entrata' => [0, 15, 30, 45, 60],
            'impostazioni_hr_range_minuti_uscita' => [0, 15, 30, 45, 60],
            'impostazioni_hr_attiva_chiusura_automatica' => [0, 1],
            'impostazioni_hr_banca_ore' => [0, 1],
            'impostazioni_hr_time_refresh' => [0, 15, 30, 45, 60],
            'impostazioni_hr_range_calcolo' => [0, 15, 30, 45, 60],
            'impostazioni_hr_reperibilita' => [0, 1],
            'impostazioni_hr_tolleranza_entrata' => [0, 15, 30, 45, 60],
            'impostazioni_hr_correggi_anomalia' => [0, 1],
            'impostazioni_hr_ora_chiusura_automatica' => [0, 1],
            'impostazioni_hr_chiusura_dipendenti_straordinario' => [0, 1]
        ];

        $dipendenteParams = [
            'dipendenti_consenti_straordinari' => [0, 1],
            'dipendenti_trasferta_giornaliera' => [0, 1]
        ];

        $combinations = [];

        
        for ($i = 0; $i < $num_random_tests; $i++) {
            $combination = [];
            foreach ($globalParams as $key => $value) {
                $combination[$key] = $value[array_rand($value)];
            }
            foreach ($dipendenteParams as $key => $value) {
                $combination[$key] = $value[array_rand($value)];
            }
            $combinations[] = $combination;
        }

        debug($combinations, true);
    }

}
