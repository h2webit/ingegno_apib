<?php

$compilazione = $this->db
    ->join('users', 'users_id = sondaggi_compilazioni_user_id', 'left')
    ->where('sondaggi_compilazioni_id', $value_id)
    ->get('sondaggi_compilazioni')
    ->row_array();
//dump($compilazione);

if(!empty($compilazione)) {
    $sondaggio = $this->db
    ->where('sondaggi_id', $compilazione['sondaggi_compilazioni_sondaggio_id'])
    ->get('sondaggi')
    ->row_array();

    if(!empty($sondaggio)) {
        $domande = $this->db
        ->where('sondaggi_domande_sondaggio_id', $sondaggio['sondaggi_id'])
        ->get('sondaggi_domande')
        ->result_array();

        $domande_risposte = [];
        if(!empty($domande)) {
            foreach ($domande as $index => $domanda) {
                $domande_risposte[$index]['domanda'] = $domanda;

                $risposta = $this->db
                ->where('sondaggi_risposte_utenti_domanda_id', $domanda['sondaggi_domande_id'])
                ->where('sondaggi_risposte_utenti_compilazione_sondaggio', $compilazione['sondaggi_compilazioni_id'])
                ->get('sondaggi_risposte_utenti')
                ->row_array();

                
                if(!empty($risposta)) {
                    //Select singola
                    if (!empty($risposta['sondaggi_risposte_utenti_risposta_id']) && !empty($risposta['sondaggi_domande_risposte_risposta'])) {
                        $domande_risposte[$index]['risposta_valore'] = $risposta['sondaggi_domande_risposte_risposta'];
                    }
                    elseif(!empty($risposta['sondaggi_risposte_utenti_risposta_valore'])) {
                        //Array
                        if (is_valid_json($risposta['sondaggi_risposte_utenti_risposta_valore'])) {
                            $risposte = json_decode((string) $risposta['sondaggi_risposte_utenti_risposta_valore'], true);

                            if (is_array($risposte)) {
                                $arr_risposte = [];

                                foreach ($risposte as $index2 => $risposta_id) {
                                    $risposta = $this->apilib->view('sondaggi_domande_risposte', $risposta_id);
                    
                                    if (!empty($risposta['sondaggi_domande_risposte_risposta'])) {
                                        $arr_risposte[$index2] = $risposta['sondaggi_domande_risposte_risposta'];
                                    }
                                }

                                $domande_risposte[$index]['risposta_valore'] = $arr_risposte;
                            } else {
                                $domande_risposte[$index]['risposta_valore'] = $risposta['sondaggi_risposte_utenti_risposta_valore'];
                            }
                    
                        } else {
                            //Campo tesuale / ora / data
                            $domande_risposte[$index]['risposta_valore'] = $risposta['sondaggi_risposte_utenti_risposta_valore'];
                        }
                    }
                }
                //dump($domande[$index]);
            }
        }

        //dump($domande_risposte);
    }
}
?>

<style>
.domanda_container {
    border-bottom: 1px solid #F2E8E8;
    margin-bottom: 16px;
}

.domanda_container .domanda {
    margin-bottom: 4px;
    font-weight: 600;
}
</style>

<div class="row">
    <div class="col-sm-12">
        <h4 class="text-center"><?php echo $sondaggio['sondaggi_titolo'];?></h4>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <h5 class="text-center">Questionario #<?php echo $compilazione['sondaggi_compilazioni_id']; ?> - Compilato da <?php echo $compilazione['users_first_name'].' '.$compilazione['users_last_name'];?> in data <?php echo dateFormat($compilazione['sondaggi_compilazioni_data_ora_inizio'], 'd/m/Y'); ?></h5>
    </div>
</div>

<div class="row">
    <?php
        foreach($domande_risposte as $dom_ris) :
            if(!empty($dom_ris['risposta_valore'])) :     
    ?>
    <div class="domanda_container">
        <div class="domanda"><?php echo $dom_ris['domanda']['sondaggi_domande_domanda'];?></div>
        <div class="risposte">
            <?php if(is_array($dom_ris['risposta_valore'])) : ?>
            <?php
                echo '<ul>';
                foreach($dom_ris['risposta_valore'] as $ris) {
                    echo '<li>'.$ris.'</li>';
                }
                echo '</ul>';
            ?>
            <?php else : ?>
            <div class="risposta"><?php echo $dom_ris['risposta_valore']; ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php
            endif;
        endforeach;
    ?>
</div>