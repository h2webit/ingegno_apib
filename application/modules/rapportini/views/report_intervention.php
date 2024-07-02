<?php
$settings = $this->apilib->searchFirst('settings');

$rapportino = $this->apilib->view('rapportini', $value_id);
//dump($rapportino);

$sede = [];
if(!empty($rapportino['projects_customer_address'])) {
    $sede = $this->apilib->view('customers_shipping_address', $rapportino['projects_customer_address']);
}

$firma_cliente = $rapportino['rapportini_firma_cliente'] ?? null;
$firma_operatore = $rapportino['rapportini_firma_operatore'] ?? null;

if (!empty($firma_operatore)) {
    $binary = \base64_decode($firma_operatore);
    $data = \getimagesizefromstring($binary);

    if ($data[0] > $data[1]) {
        //orientamento corretto
    } else {
        $immagine = imagecreatefromstring($binary);
        $rotated_imaged = imagerotate($immagine, 90, 0);
        imagealphablending($rotated_imaged, false);
        imagesavealpha($rotated_imaged, true);
        ob_start();
        imagepng($rotated_imaged);
        $bin = ob_get_clean();

        $firma_operatore = base64_encode($bin);
    }
}

if (!empty($firma_cliente)) {
    $binary = \base64_decode($firma_cliente);
    $data = \getimagesizefromstring($binary);

    if ($data[0] > $data[1]) {
        //orientamento corretto
    } else {
        $immagine = imagecreatefromstring($binary);
        $rotated_imaged = imagerotate($immagine, 90, 0);
        imagealphablending($rotated_imaged, false);
        imagesavealpha($rotated_imaged, true);
        ob_start();
        imagepng($rotated_imaged);
        $bin = ob_get_clean();

        $firma_cliente = base64_encode($bin);
    }
}

$checklist = [];
//dump($compilazione);
if(!empty($rapportino['rapportini_compilazione_id'])) {
$compilazione = $this->apilib->view('sondaggi_compilazioni', $rapportino['rapportini_compilazione_id']);
    
    if(!empty($compilazione)) {
        $sondaggio = $this->apilib->view('sondaggi', $compilazione['sondaggi_compilazioni_sondaggio_id']);
        //dump($sondaggio);
        if(!empty($sondaggio)) {
            $domande = $this->apilib->search('sondaggi_domande', [
                'sondaggi_domande_sondaggio_id' => $sondaggio['sondaggi_id']
            ]);

            $domande_risposte = [];

            if(!empty($domande)) {
                foreach ($domande as $index => $domanda) {
                    $domande_risposte[$index]['domanda'] = $domanda;
                    $risposta = $this->apilib->searchFirst('sondaggi_risposte_utenti', [
                        'sondaggi_risposte_utenti_domanda_id' => $domanda['sondaggi_domande_id'],
                        'sondaggi_risposte_utenti_compilazione_sondaggio' => $compilazione['sondaggi_compilazioni_id']
                    ]);

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
                        }  else {
                            // Riposta non fornita
                            $domande_risposte[$index]['risposta_valore'] = null;
                        }
                    }
                    //dump($domande[$index]);
                }
            }
            //dump($domande_risposte);
        }
    }
}

?>

<style>
.section_title {
    margin-bottom: 8px;
    margin-top: 16px;
    padding: 12px;
    font-weight: bold;
    font-size: 16px;
    text-transform: uppercase;
    background-color: #cbe0f1;
}

.codice_rapportino {
    margin-bottom: 8px;
    margin-top: 16px;
    padding: 12px;
    font-weight: bold;
    font-size: 16px;
    text-transform: uppercase;
    text-align: center;
}

.domanda_container {
    margin-bottom: 12px;
}
</style>


<div class="row">
    <div class="col-sm-12">
        <div class="codice_rapportino">Rapportino #<?php echo $rapportino['rapportini_codice']; ?></div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="section_title">Dati del cliente</div>
    </div>
</div>

<div class="row" style="margin-bottom: 12px;">
    <div class="col-sm-6">
        <strong class="text-left">Azienda</strong>
    </div>
    <div class="col-sm-6">
        <div class="text-right">
            <?php echo $rapportino['customers_full_name']; ?>
        </div>
    </div>
</div>

<div class="row" style="margin-bottom: 12px;">
    <div class="col-sm-6">
        <strong class="text-left">Commessa</strong>
    </div>
    <div class="col-sm-6">
        <div class="text-right">
            <?php echo $rapportino['projects_name']; ?>
        </div>
    </div>
</div>

<div class="row" style="margin-bottom: 12px;">
    <div class="col-sm-6">
        <strong class="text-left">Indirizzo sede</strong>
    </div>
    <div class="col-sm-6">
        <div class="text-right">
            <?php echo $sede['customers_shipping_address_street'] ?? '-'; ?>
        </div>
    </div>
</div>



<div class="row">
    <div class="col-sm-12">
        <div class="section_title">Data ed orari</div>
    </div>
</div>

<div class="row" style="margin-bottom: 12px;">
    <div class="col-sm-6">
        <strong class="text-left">Data</strong>
    </div>
    <div class="col-sm-6">
        <div class="text-right">
            <?php echo dateFormat($rapportino['rapportini_data'], 'd/m/Y'); ?>
        </div>
    </div>
</div>

<div class="row" style="margin-bottom: 12px;">
    <div class="col-sm-6">
        <strong class="text-left">Orari</strong>
    </div>
    <div class="col-sm-6">
        <div class="text-right">
            <?php echo "{$rapportino['rapportini_ora_inizio']} - {$rapportino['rapportini_ora_fine']}"; ?>
        </div>
    </div>
</div>

<div class="row" style="margin-bottom: 12px;">
    <div class="col-sm-6">
        <strong class="text-left">Ore totali effettuate</strong>
    </div>
    <div class="col-sm-6">
        <div class="text-right">
            <?php
            $inizio_rapportino = new DateTime($rapportino['rapportini_data'].' '.$rapportino['rapportini_ora_inizio']);
            $fine_rapportino = new DateTime($rapportino['rapportini_data'].' '.$rapportino['rapportini_ora_fine']);
            $diff_rapportino = $fine_rapportino->diff($inizio_rapportino);
            $hours_rapportino = round(($diff_rapportino->s / 3600) + ($diff_rapportino->i / 60) + $diff_rapportino->h + ($diff_rapportino->days * 24), 2);
            
            echo number_format($hours_rapportino, 2, '.', ',');
        ?>
        </div>
    </div>
</div>



<div class="row">
    <div class="col-sm-12" style="margin-bottom: 12px;">
        <div class="section_title">Operatori</div>
        <?php
        foreach($rapportino['rapportini_operatori'] as $key => $operatore) {
            echo $operatore.'</br>';
        }
        ?>
    </div>
</div>


<div class="row">
    <div class="col-sm-12" style="margin-bottom: 12px;">
        <div class="section_title">Note aggiuntive</div>
        <?php echo !empty($rapportino['rapportini_note']) ? $rapportino['rapportini_note'] : '-'; ?>
    </div>
</div>


<?php if(!empty($compilazione)) : ?>
<div class="row">
    <div class="col-sm-12">
        <div class="section_title">Checklist</div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12" style="margin-bottom: 25px;">
        <div class="row">
            <?php
                foreach($domande_risposte as $dom_ris) {
                    echo '<div class="col-sm-12 domanda_container">';
                    //Domanda
                    echo "<div class='domanda'><strong>{$dom_ris['domanda']['sondaggi_domande_domanda']}</strong></div>";
                    //Risposta
                    if(empty($dom_ris['risposta_valore'])) {
                        echo '<div class="risposta"> - </div>';
                    } else {
                        if(is_array($dom_ris['risposta_valore'])) {
                            echo '<ul>';
                            foreach($dom_ris['risposta_valore'] as $ris) {
                                echo '<li>'.$ris.'</li>';
                            }
                            echo '</ul>';
                        } else {
                            echo "<div class='risposta'>{$dom_ris['risposta_valore']}</div>";
                        }
                    }
                    echo '</div>';
                }
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <?php if (!empty($firma_cliente)) : ?>
    <div class="col-sm-6 text-center">
        <strong>Firma cliente</strong>
        <img src="<?php echo 'data:image/png;base64,' . $firma_cliente; ?>" class="img-responsive" style="max-height: 250px; margin: 0 auto;">
    </div>
    <?php endif; ?>

    <?php if (!empty($firma_operatore)) : ?>
    <strong>Firma operatore</strong>
    <div class="col-sm-6 text-center">
        <img src="<?php echo 'data:image/png;base64,' . $firma_operatore; ?>" class="img-responsive" style="max-height: 250px; margin: 0 auto;">
    </div>
    <?php endif; ?>
</div>


<?php if (!empty($rapportino['rapportini_immagini'])) : ?>
<div class="row" style="margin-top: 20px;">
    <div class="col-sm-12">
        <hr />
    </div>
    <?php foreach (json_decode($rapportino['rapportini_immagini'], true) as $immagine) : ?>
    <div class="col-sm-4" style="margin-bottom: 40px;">
        <img src="<?php echo base_url('uploads/' . $immagine['path_local']); ?>" class="img-responsive">
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>