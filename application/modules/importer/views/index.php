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
</div>