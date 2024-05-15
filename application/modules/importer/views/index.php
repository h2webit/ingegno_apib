<?php
    $module_settings = $this->apilib->searchFirst('importer_settings');
    
    $importer_allowed_user_types = array_keys($module_settings['importer_settings_importer_allowed_user_types'] ?? []);
    $exporter_allowed_user_types = array_keys($module_settings['importer_settings_exporter_allowed_user_types'] ?? []);
    
    $current_user_type = $this->auth->get('users_type') ?? null;
?>

<style>
    .big-button span {
        height: auto !important;
        width: 100%;
        padding: 5px;
        border-radius: 5px !important;
        box-shadow: 0 1px 3px rgba(150, 150, 150, 0.12), 0 1px 2px rgba(150, 150, 150,0.24) !important;
        font-size: 3rem;
    }
    
    .big-button i {
        padding: 5px;
        font-size: 5rem !important;
    }

    .info-box {
        background: transparent;
    }
</style>

<div class="row">
    <?php if($module_settings['importer_settings_enable_importer'] == DB_BOOL_TRUE && (empty($importer_allowed_user_types) || $this->datab->is_admin() || in_array($current_user_type, $importer_allowed_user_types)) ): ?>
    <div class="col-sm-3">
        <?php
        $link_concat = '';
        if (basename($_SERVER['REQUEST_URI']) != 'importer') {
            $link_concat = "/" . basename($_SERVER['REQUEST_URI']);
        }
        ?>
        <a href="<?php echo base_url("importer/import/import_start" . $link_concat); ?>">
            <div class="info-box big-button">
                <span class="info-box-icon bg-green">
                    <i class="fas fa-file-import"></i>
                    <br/>
                    <?php e("IMPORT DATA"); ?>
                </span>
            </div>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if($module_settings['importer_settings_enable_exporter'] == DB_BOOL_TRUE && (empty($exporter_allowed_user_types) || $this->datab->is_admin() || in_array($current_user_type, $exporter_allowed_user_types)) ): ?>
    <div class="col-sm-3">
        <a href="<?php echo base_url("main/layout/exporter-templates"); ?>">
            <div class="info-box big-button">
                <span class="info-box-icon bg-red">
                    <i class="fas fa-file-export"></i>
                    <br/>
                    <?php e("EXPORT DATA"); ?>
                </span>
            </div>
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
    $(function() {
        // verifico quanti elementi con .big-button esistono. Se ne esiste solo 1, prendo il suo link (tag a parent) e faccio redirect diretto al link
        if ($('.big-button').length == 1) {
            window.location.href = $('.big-button').parent().attr('href');
        }
    })
</script>