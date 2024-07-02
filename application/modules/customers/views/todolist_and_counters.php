<?php
    $mostra_tutti = $this->input->get('all');
    if ($mostra_tutti) {
        $cols = 6;
    } else {
        $cols = 12;
    }
$customer = $this->apilib->view('customers', $value_id);
?>


<?php if ($this->datab->module_installed('payments-subscriptions')) : ?>

    <?php $subscriptions = $this->apilib->search('subscriptions', ['subscriptions_customer_id' => $value_id, "(subscriptions_end_date IS NULL OR subscriptions_end_date = '' OR DATE(subscriptions_end_date) > DATE(NOW()))"]);?>
    <div class="col-md-<?php echo $cols; ?>">
        <div class="info-box bg-green">
            <span class="info-box-icon"><i class="fas fa-euro-sign"></i></span>

            <div class="info-box-content">
                <span class="info-box-text">Pagamenti ricorrenti</span>

                <?php if (count($subscriptions) > 0):?>
                
                    <?php foreach($subscriptions as $subscription):?>
                    <span class="info-box-number">€ <?php echo number_format($subscription['subscriptions_price'], 0, ',', '.'); ?><?php echo " /".strtolower(t($subscription['subscriptions_recurrence_value']));?></span>
                    <?php endforeach;?>
                <?php else:?>
                        <span class="progress-description"><?php e('No subscription');?></span>
                <?php endif;?>

            </div>
        </div>
    </div>
<?php endif;?>



<?php if ($this->datab->module_installed('contabilita')) : ?>
    <?php $this->load->model('contabilita/conteggi'); ?>
    <?php
    if (method_exists($this->conteggi, 'getFatturatoCustomer')) :
        $fatturato_globale = $this->conteggi->getFatturatoCustomer($value_id);
        $fatturato_anno = $this->conteggi->getFatturatoAnnoCustomer($value_id);
        $insolvenze = $this->conteggi->getInsolvenzeCustomer($value_id);
        if ($mostra_tutti) {
            $fido_concesso = $customer['customers_fido_concesso'];
            $tempo_medio_saldo_giorni = $this->conteggi->getTempoMedioSaldoCustomer($value_id, false);
            $tempo_medio_saldo_giorni_human_readable = $this->conteggi->getTempoMedioSaldoCustomer($value_id, true);
            
            $totale_ordini_vendita = $this->conteggi->getTotaleOrdiniVenditaCustomer($value_id);
            $totale_ordini_acquisto = $this->conteggi->getTotaleOrdiniAcquistoCustomer($value_id);
        }
    ?>

        <div class="col-md-<?php echo $cols; ?>">
            <div class="info-box bg-green">
                <span class="info-box-icon"><i class="fas fa-euro-sign"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">FATTURATO <?php echo date("Y"); ?></span>
                    <span class="info-box-number">€ <?php echo number_format( ($fatturato_anno['imponibile'] ?? 0), 0, ',', '.'); ?></span>

                    <div class="progress">
                        <div class="progress-bar w100"></div>
                    </div>
                    <span class="progress-description">
                        € <?php echo number_format( ($fatturato_anno['iva'] ?? 0), 0,',','.'); ?> iva
                    </span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>

        <div class="col-md-<?php echo $cols; ?>">
            <div class="info-box <?php echo (($fatturato_globale['imponibile'] ?? 0) > 0) ? 'bg-green' : 'bg-red'; ?>">
                <span class="info-box-icon"><i class="fas fa-euro-sign"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">FATTURATO GLOBALE</span>
                    <span class="info-box-number">€ <?php echo number_format($fatturato_globale['imponibile'], 0, ',', '.'); ?></span>

                    <div class="progress">
                        <div class="progress-bar w100"></div>
                    </div>
                    <span class="progress-description">
                        € <?php echo number_format($fatturato_globale['iva'], 0, ',', '.'); ?> iva
                    </span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>

        <div class="col-md-<?php echo $cols; ?>">
            <div class="info-box <?php echo ( ($insolvenze['imponibile'] ?? 0) > 0) ? 'bg-red' : 'bg-green'; ?>">
                <span class="info-box-icon"><i class="fas fa-euro-sign"></i></span>

                <div class="info-box-content">
                    <span class="info-box-text">INSOLVENZE</span>
                    <span class="info-box-number <?php echo ($insolvenze['imponibile'] > 0) ? 'blink_me' : ''; ?>">€ <?php echo number_format($insolvenze['imponibile'], 0, ',', '.'); ?></span>

                    <div class="progress">
                        <div class="progress-bar w100"></div>
                    </div>
                    <span class="progress-description">
                        € <?php echo number_format($insolvenze['iva'], 0); ?> iva
                    </span>
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <?php if ($mostra_tutti) : ?>
        <div class="col-md-<?php echo $cols; ?>">
            <div class="info-box <?php echo ($fido_concesso > 0) ? 'bg-green' : 'bg-red'; ?>">
                <span class="info-box-icon"><i class="fas fa-euro-sign"></i></span>
        
                <div class="info-box-content">
                    <span class="info-box-text">FIDO CONCESSO</span>
                    <span class="info-box-number">€
                        <?php echo number_format($fido_concesso, 0, ',', '.'); ?>
                    </span>
        
                </div>
                <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <div class="col-md-<?php echo $cols; ?>">
            <div class="info-box <?php if ($tempo_medio_saldo_giorni < 30) {
                echo 'bg-green';
            } elseif ($tempo_medio_saldo_giorni < 60) {
                echo 'bg-orange';
            } else {
                echo 'bg-red';
            }; ?>">
            <span class="info-box-icon"><i class="fas fa-euro-sign"></i></span>
            
            <div class="info-box-content">
                <span class="info-box-text">TEMPO MEDIO SALDO</span>
                <span class="info-box-number">
                    <?php echo $tempo_medio_saldo_giorni_human_readable; ?>
                </span>
            
            </div>
            <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <div class="col-md-<?php echo $cols; ?>">
            <div class="info-box <?php echo ($totale_ordini_vendita['totale_ordini'] > 0) ? 'bg-green' : 'bg-red'; ?>">
            <span class="info-box-icon"><i class="fas fa-euro-sign"></i></span>
            
            <div class="info-box-content">
                <span class="info-box-text">ORDINI DI VENDITA</span>
                <span class="info-box-number">
                    <?php echo $totale_ordini_vendita['totale_ordini'] ?>
                </span>
            
            </div>
            <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <div class="col-md-<?php echo $cols; ?>">
            <div class="info-box <?php echo ($totale_ordini_acquisto['totale_ordini'] > 0) ? 'bg-green' : 'bg-red'; ?>">
            <span class="info-box-icon"><i class="fas fa-euro-sign"></i></span>
            
            <div class="info-box-content">
                <span class="info-box-text">ORDINI D'ACQUISTO</span>
                <span class="info-box-number">
                    <?php echo $totale_ordini_acquisto['totale_ordini']; ?>
                </span>
            
            </div>
            <!-- /.info-box-content -->
            </div>
            <!-- /.info-box -->
        </div>
        <?php endif; ?>
        <?php if (!$mostra_tutti) : ?>
        <div class="col-md-12 text-right">
            <button class="btn btn-grey btn-sm"><a href="<?php echo base_url("get_ajax/modal_layout/todolist_and_counters/{$value_id}"); ?>?_size=large&all=1" class="js_open_modal">Vedi tutto...</a></button>
        </div>
        <?php endif; ?>
    <?php endif; ?>

<?php else : ?>

    <link href='https://fonts.googleapis.com/css?family=Handlee' rel='stylesheet' type='text/css' />
    <link href='https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css' rel='stylesheet' type='text/css' />

    <?php $this->layout->addModuleStylesheet('customers', 'todolist/css/waves.min.css'); ?>
    <?php $this->layout->addModuleStylesheet('customers', 'todolist/css/animate.min.css'); ?>
    <?php $this->layout->addModuleStylesheet('customers', 'todolist/css/todo.css'); ?>

    <?php
    $current_layout = $this->layout->getCurrentLayoutIdentifier();

    $todoitems = [];

    if ($current_layout) {
        if ($current_layout === 'project-detail') {
            $todoitems = $this->apilib->search('todolist', array('todolist_user' => $this->auth->get('id'), 'todolist_project_id' => $value_id), null, null, 'todolist_id ASC');
        } elseif ($current_layout === 'customer-detail') {
            $todoitems = $this->apilib->search('todolist', array('todolist_user' => $this->auth->get('id'), 'todolist_customer_id' => $value_id), null, null, 'todolist_id ASC');
        } elseif ($current_layout === 'supplier-detail') {
            $todoitems = $this->apilib->search('todolist', array('todolist_user' => $this->auth->get('id'), 'todolist_supplier_id' => $value_id), null, null, 'todolist_id ASC');
        } else {
            $todoitems = $this->apilib->search('todolist', array('todolist_user' => $this->auth->get('id')), null, null, 'todolist_id ASC');
        }
    }

    ?>

    <!-- Todo Lists -->
    <div id="todo-lists">
        <div class="tl-header">
            <h2>Todo-List</h2>
            <small>Manage your personal todo-list</small>
        </div>

        <div class="clearfix"></div>

        <div class="tl-body">
            <div id="add-tl-item">
                <i class="add-new-item zmdi zmdi-plus"></i>

                <div class="add-tl-body">
                    <textarea name="todolist_text" placeholder="What's your plan?"></textarea>
                    <input type="hidden" name="todolist_user" value="<?php echo $this->auth->get('id'); ?>" />

                    <?php if ($current_layout && $current_layout === 'project-detail') : ?>
                        <input type="hidden" name="todolist_project_id" value="<?php echo $value_id; ?>" />
                    <?php elseif ($current_layout && $current_layout === 'customer-detail') : ?>
                        <input type="hidden" name="todolist_customer_id" value="<?php echo $value_id; ?>" />
                    <?php elseif ($current_layout && $current_layout === 'supplier-detail') : ?>
                        <input type="hidden" name="todolist_supplier_id" value="<?php echo $value_id; ?>" />
                    <?php endif; ?>

                    <div class="add-tl-actions">
                        <a href="" data-tl-action="dismiss"><i class="zmdi zmdi-close"></i></a>
                        <a href="" data-tl-action="save"><i class="zmdi zmdi-check"></i></a>
                    </div>
                </div>
            </div>

            <?php foreach ($todoitems as $todoitem) : ?>
                <div class="checkbox media">
                    <div class="media-body">
                        <label>
                            <input class="toggle" type="checkbox" value="<?php echo $todoitem['todolist_id']; ?>" <?php echo ($todoitem['todolist_deleted'] == DB_BOOL_TRUE) ? 'checked' : '' ?>>
                            <i class="input-helper"></i>
                            <span><?php echo $todoitem['todolist_text']; ?></span>
                        </label>
                        <span class="pull-right js_remove_todo" role="button" data-todo-id="<?php echo $todoitem['todolist_id']; ?>">
                            <i class="fas fa-trash-alt"></i>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>

        </div>
    </div>

    <?php $this->layout->addModuleJavascript('customers', 'todolist/js/todo.js'); ?>
<?php endif; ?>
