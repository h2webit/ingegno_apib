<section class="content-header">

    <section class="content">
        <div class="row">

            <div class="box-body view ">

                <style>
                .big-button span {
                    height: auto !important;
                    width: 100%;
                    padding: 10px;
                    margin-top: 30px;
                }

                .text {
                    margin-top: 35px;
                    font-family: "Arial";
                    font-size: 0.40em;
                }

                .big-button i {
                    padding: 10px;
                }

                .info-box {
                    font-size: 0.80em !important;
                    background: transparent;
                }

                .ui-autocomplete {
                    max-height: 250px;
                    overflow-y: auto;
                    /* prevent horizontal scrollbar */
                    overflow-x: hidden;
                }
                </style>

                <div class="row">
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <?php
                        $link_concat = '';
                        if(basename($_SERVER['REQUEST_URI']) != 'importer'){
                            $link_concat = "/".basename($_SERVER['REQUEST_URI']);
                        }
                        ?>
                        <a href="<?php echo base_url("importer/import/import_start".$link_concat); ?>">
                            <div class="info-box big-button">
                                <span class="info-box-icon bg-green">
                                    <i class="fas fa-file-import">
                                        <div class="text"><?php e("CSV IMPORT"); ?></div>
                                    </i>
                                </span>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 col-sm-6 col-xs-12">
                        <a href="<?php echo base_url("importer/export/start"); ?>">
                            <div class="info-box big-button">
                                <span class="info-box-icon bg-red">
                                    <i class="fas fa-file-export">
                                        <div class="text"><?php e("CSV EXPORT"); ?></div>
                                    </i>
                                </span>
                            </div>
                        </a>
                    </div>

                    <div class="clearfix visible-sm-block"></div>
                </div>


            </div>

        </div>
    </section>