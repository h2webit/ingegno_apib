<?php



?>
<div class="callout callout-danger Metronic-alerts alert alert-info">
    <h4>Attenzione!</h4>

    <p>
        Hai <strong>
            <?php echo $conteggio; ?>
        </strong> fatture elettroniche
        che non risultano correttamente accettate o inviate allo SDI. <br />Di
        seguito alcune di queste (le più recenti):


    <ul>
        <?php echo implode(' ', $fatture_numero); ?>
    </ul>
    <br /> Si invita a controllarne lo stato e procedere al corretto invio.
    </p>
</div>