<?php

class Normalizzazione extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('timbrature');

        $this->dipendenti = $this->apilib->search('dipendenti', ['dipendenti_attivo' => DB_BOOL_TRUE]);
    }

    public function all($drop = false)
    {
        //Col parametro drop, prima di procede, viene cancellato tutto lo storico
        if ($drop) {
            $this->db->truncate('presenze_normalizzate');
            $this->apilib->clearCache();
        }
        $min_date = '1900-01-01 00:00:00';
        $query = $this->db->select_max('presenze_normalizzate_giorno')->get('presenze_normalizzate');
        if ($query->num_rows() > 0 && !empty($query->row()->presenze_normalizzate_giorno)) {
            
            $min_date = $query->row()->presenze_normalizzate_giorno;
        }
        
        $this->db->select('presenze_data_inizio')
            ->from('presenze')
            ->where('presenze_data_inizio >', $min_date)
            ->order_by('presenze_data_inizio', 'ASC')
            ->limit(1);

        $query = $this->db->get();
        $result = $query->row();

        $day_start = $result ? $result->presenze_data_inizio : null;
       
        $anno_start = date('Y', strtotime($day_start)); 

        for ($i = $anno_start; $i <= date('Y'); $i++) {
            if (!$this->normalizzaAnno($i)) {
                return false;
            }
        }
        return true;
    }

    public function normalizzaAnno($anno)
    {
        if ($anno == date('Y')) {
            $mese_fine = date('m');
        } else {
            $mese_fine = 12;
        }
        for ($i = 1; $i <= $mese_fine; $i++) {

            progress($i, $mese_fine, "Normalizzazione anno $anno");

            $ultimo_giorno_del_mese = date('t', strtotime($anno . '-' . $i . '-01'));

            $presenze = $this->apilib->search('presenze', [
                'presenze_data_inizio >=' => "$anno-$i-01 00:00:00",
                'presenze_data_fine <=' => "$anno-$i-$ultimo_giorno_del_mese 23:59:59"
            ]);
            if ($presenze) {
                if (!$this->normalizzaMese($anno, $i, $presenze)) {
                    return false;
                }
            }
            
        }
        return true;
    }

    public function normalizzaMese($anno, $mese, $presenze = null)
    {
        $ultimo_giorno_del_mese = date('t', strtotime($anno . '-' . $mese . '-01'));
        if ($presenze === null) {
            $presenze = $this->apilib->search('presenze', [
                'presenze_data_inizio >=' => "$anno-$mese-01 00:00:00",
                'presenze_data_fine <=' => "$anno-$mese-$ultimo_giorno_del_mese 23:59:59"
            ]);
        }
        
        for ($i = 1; $i <= $ultimo_giorno_del_mese; $i++) {
            progress($i,$ultimo_giorno_del_mese, "Normalizzazione mese $mese/$anno");
            if (date('Y-m-d', strtotime("$anno-$mese-$i")) > date('Y-m-d')) {
                return false;
            }
            $this->normalizzaGiorno($anno, $mese, $i, $presenze);
        }
        return true;
    }

    public function normalizzaGiorno($anno, $mese, $giorno, $presenze = null)
    {
        if ($presenze === null) {
            $presenze = $this->apilib->search('presenze', [
                'presenze_data_inizio >=' => "$anno-$mese-$giorno 00:00:00",
                'presenze_data_fine <=' => "$anno-$mese-$giorno 23:59:59"
            ]);
        }
        $i = 0;
        foreach ($this->dipendenti as $dipendente) {
            //progress(++$i, count($this->dipendenti), "Normalizzazione giorno $giorno/$mese/$anno");
            $this->normalizzaGiornoDipendente($anno, $mese, $giorno, $dipendente['dipendenti_id'], $presenze);

        }
        return true;
    }

   public function normalizzaGiornoDipendente($anno, $mese, $giorno, $dipendente_id, $presenze = null)
    {
        if ($presenze === null) {
            $presenze = $this->apilib->search('presenze', [
                'presenze_data_inizio >=' => "$anno-$mese-$giorno 00:00:00",
                'presenze_data_fine <=' => "$anno-$mese-$giorno 23:59:59",
                'presenze_dipendente' => $dipendente_id
            ]);
        }

        
        $this->normalizzaPresenzeGiornaliere($anno, $mese, $giorno, $dipendente_id);//, $presenze);
        return true;
    }
    public function normalizzaPresenzeGiornaliere($anno, $mese, $giorno, $dipendente_id, $presenze = null)
    {
        if ($presenze === null) {
            $presenze = $this->apilib->search('presenze', [
                'DATE(presenze_data_inizio)' => "$anno-$mese-$giorno",
                //'presenze_data_fine <=' => "$anno-$mese-$giorno 23:59:59",
                'presenze_dipendente' => $dipendente_id
            ]);
        }
        
        
        $Ymd = sprintf("%04d-%02d-%02d", $anno, $mese, $giorno);
        
        $presenza_normalizzata_exists = $this->apilib->searchFirst('presenze_normalizzate', [
            'presenze_normalizzate_giorno' => $Ymd,
            'presenze_normalizzate_dipendente' => $dipendente_id
        ]);

        if ($presenza_normalizzata_exists) {
            return true;
        }

        $presenza_normalizzata = [
            'presenze_normalizzate_giorno' => $Ymd,
            'presenze_normalizzate_dipendente' => $dipendente_id,
            //'presenze_normalizzate_reparto' => $reparto,
            'presenze_normalizzate_ore_previste' => $this->calcolaOrePreviste($Ymd, $dipendente_id),
            'presenze_normalizzate_ore_straordinarie' => $this->calcolaOreStraordinarie($Ymd, $dipendente_id, $presenze),
            'presenze_normalizzate_ore_ordinarie' => $this->calcolaOreOrdinarie($Ymd, $dipendente_id, $presenze),
            
            'presenze_normalizzate_ore_totali' => $this->calcolaOreTotali($Ymd, $dipendente_id, $presenze),
            'presenze_normalizzate_ore_pausa_pranzo' => $this->calcolaOrePausaPranzo($Ymd, $dipendente_id, $presenze),
            'presenze_normalizzate_ore_richieste' => $this->calcolaOreRichieste($Ymd, $dipendente_id),
            'presenze_normalizzate_ore_ferie' => $this->calcolaOreFerie($Ymd, $dipendente_id),
            'presenze_normalizzate_ore_malattia' => $this->calcolaOreMalattia($Ymd, $dipendente_id),
            'presenze_normalizzate_ore_richieste_ai' => $this->calcolaOreRichieste($Ymd, $dipendente_id, 'AI'),
            'presenze_normalizzate_ore_richieste_cm' => $this->calcolaOreRichieste($Ymd, $dipendente_id, 'CM'),
            'presenze_normalizzate_ore_richieste_cp' => $this->calcolaOreRichieste($Ymd, $dipendente_id, 'CP'),
            'presenze_normalizzate_ore_richieste_in' => $this->calcolaOreRichieste($Ymd, $dipendente_id, 'IN'),
            'presenze_normalizzate_ore_richieste_pl' => $this->calcolaOreRichieste($Ymd, $dipendente_id, 'PL'),
            'presenze_normalizzate_ore_richieste_pv' => $this->calcolaOreRichieste($Ymd, $dipendente_id, 'PV'),
            'presenze_normalizzate_ore_richieste_104' => $this->calcolaOreRichieste($Ymd, $dipendente_id, 'C104'),
            'presenze_normalizzate_buono_pasto' => $this->calcolaBuoniPasto($Ymd, $dipendente_id, $presenze),
            'presenze_normalizzate_ore_banca_ore' => $this->calcolaOreBancaOre($Ymd, $dipendente_id, $presenze),
            'presenze_normalizzate_ore_ferie_maturate' => $this->calcolaOreFerieMaturate($Ymd, $dipendente_id, $presenze),
            'presenze_normalizzate_ore_notturne' => $this->calcolaOreNotturne($Ymd, $dipendente_id),

        ];
        //debug($presenza_normalizzata);
        $this->apilib->create('presenze_normalizzate', $presenza_normalizzata);
        return true;
    }
    public function calcolaOrePreviste($Ymd, $dipendente_id)
    {
        $ore_giornaliere_previste = $this->timbrature->calcolaOreGiornalierePreviste($Ymd, $dipendente_id);
        return $ore_giornaliere_previste;
    }
    public function calcolaOreNotturne($Ymd, $dipendente_id)
    {
        $ore_notturne = $this->timbrature->calcolaOreNotturne($Ymd, $dipendente_id);
        return $ore_notturne;
    }

    public function calcolaOreStraordinarie($Ymd, $dipendente_id, $presenze)
    {
        return $this->timbrature->calcolaOreStraordinarie($Ymd, $dipendente_id, $presenze);
    }
    public function calcolaOreOrdinarie($Ymd, $dipendente_id, $presenze) {
        //TODO: sbagliato? Dovrebbe calcolare le ore lavorate, non quelle previste...
        //return $this->calcolaOreTotali($Ymd, $dipendente_id, $presenze) - $this->calcolaOreStraordinarie($Ymd, $dipendente_id, $presenze);
        return $this->timbrature->calcolaOreOrdinarie($Ymd, $dipendente_id, $presenze);
    }

    public function calcolaOreTotali($Ymd, $dipendente_id, $presenze)
    {
        //TODO: nelle totali andare ad includere anche tutte le ore di ferie/permessi/malattie di quel giorno
        return $this->timbrature->calcolaOreTotali($Ymd, $dipendente_id, $presenze);
    }

    public function calcolaOrePausaPranzo($Ymd, $dipendente_id, $presenze) {
        //Le pause pranzo si calcolano sommando le ore di pausa indicate nelle presenze o, in caso di presenze_pausa vuoto, indicate nel turno di lavoro
        $ore_pausa_pranzo = $this->timbrature->calcolaOrePausaPranzo($Ymd, $dipendente_id, $presenze);
        
        return $ore_pausa_pranzo;
    }

    public function calcolaOreRichieste($Ymd, $dipendente_id, $sottotipogia = null)
    {
        return $this->timbrature->calcolaOreRichieste($Ymd, $dipendente_id, $sottotipogia);

        
    }

    public function calcolaOreFerie($Ymd, $dipendente_id)
    {
        //TODO: calcolare le ore di ferie totali di questo giorno/dipendente
        //MICHAEL
        return 0;
    }
    public function calcolaOreMalattia($Ymd, $dipendente_id)
    {
        //TODO: calcolare le ore di malattia di questo giorno/dipendente
        //MICHAEL
        return 0;
    }

    public function calcolaBuoniPasto($Ymd, $dipendente_id, $presenze)
    {
        //TODO: calcolare i buoni pasto di questo giorno/dipendente in base alle presenze. Se la somma delle ore lavorate nelle presenze è >= 6, allora buoni pasto = 1
        //ANDREA
        return DB_BOOL_FALSE;
    }
    public function calcolaOreFerieMaturate($Ymd, $dipendente_id, $presenze)
    {
        //TODO: calcolare le ore di ferie maturate di questo giorno/dipendente in base alle presenze.
        //Da non fare per ora
        return 0;
    }
    public function calcolaOreBancaOre($Ymd, $dipendente_id, $presenze = null)
    {
        //TODO: calcolare le ore di banca ore di questo giorno/dipendente in base alle presenze.
        //Alessandro
        return 0;
    }
}