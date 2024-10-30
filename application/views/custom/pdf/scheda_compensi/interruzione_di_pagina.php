</table></div>
<h2 class="new_page"></h2>
<div class="col-sm-12 text">
    <table class="tg" >
        <tr>
            <th class="tg-7nj3">NOMINATIVO</th>
            <th class="tg-7nj3">PERIODO MESE LAVORATO</th>
            <th class="tg-7nj3">ORE TOT O NUM. PRESTAZIONI</th>
            <?php if ($this->auth->get('utenti_tipo') != 15) : ?>  
                <th class="tg-pbc0">
                    <div class="red">TARIFFA</div>
                </th>
                <th class="tg-7nj3">TOTALE</th>
            <?php endif; ?>
            <th class="tg-slju">CLIENTE</th>
            <?php if ($this->auth->get('utenti_tipo') != 15) : ?>  
                <th class="tg-slju">Tot.da fatturare</th>
                <?php endif; ?>
        </tr>