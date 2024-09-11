            <?php /*<a href="compose.html" class="btn btn-primary btn-block margin-bottom">Compose</a>*/ ?>
            <?php
            $domini_associati = $this->apilib->search('customers_domains', [
                'customers_domains_customer' => $value_id,
            ]);
            $btn_nuovo_dominio = base_url("get_ajax/modal_form/new-domain-customer?customers_domains_customer=" .$value_id );

            //https://crm.h2web.it//db_ajax/generic_delete/tickets/7934
            //new-domain-customer
            //href="{base_url}/get_ajax/modal_form/new-domain-customer?customers_domains_customer<?php echo $value_id;
            ?>

            <div class="box box-solid">
                <div class="box-header with-border">
                    <h3 class="box-title">Folders</h3>

                    <div class="box-tools">
                        <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="box-body no-padding">
                    <ul class="nav nav-pills nav-stacked">
                        <li class="active">
                            <a href="javascript:void(0);" class="js_folder" data-folder="inbox">
                                <i class="fa fa-inbox"></i>
                                Inbox<span class="label label-primary pull-right"></span>
                            </a>
                        </li>
                        <li><a href="javascript:void(0);" class="js_folder" data-folder="sent"><i class="fas fa-paper-plane"></i> Sent<span class="label label-primary pull-right"></span></a></li>
                        <!--<li><a href="#"><i class="fa fa-file-text-o"></i> Drafts</a></li>
                        <li><a href="#"><i class="fa fa-filter"></i> Junk <span class="label label-warning pull-right">65</span></a>
                        </li>
                        <li><a href="#"><i class="fa fa-trash-o"></i> Trash</a></li>-->
                    </ul>
                </div>
            </div>
            <a href="<?php echo $btn_nuovo_dominio; ?>" class="btn btn-primary js_open_modal"><?php e('New domain'); ?></a>
            <?php
            if(!empty($domini_associati)):
            ?>
                <div class="box box-solid">
                    <div class="box-header with-border">
                        <h3 class="box-title">Domini associati</h3>

                    </div>

                    <div class="box-body no-padding">
                        <ul class="nav nav-pills nav-stacked">
                            <?php
                            foreach ($domini_associati as $dominio_associati):
                                $btn_url = base_url("db_ajax/generic_delete/customers_domains/" .$dominio_associati['customers_domains_id'] );
                            ?>
                            <li>
                                <a onclick="return confirm('Are you sure you want to delete this item?');" href="<?php echo $btn_url ?>"><i class="fa fa-circle-o text-red"></i> <?php echo $dominio_associati['customers_domains_domain']; ?>
                                <span style="background-color: red!important;" class="label label-primary pull-right">X </span>
                                </a>
                            </li>
                            <?php
                            endforeach;
                            ?>
                        </ul>
                    </div>
                </div>
            <?php
            endif;
            ?>


            <script>
                /* --------------------------------
var testURL = CreateUrl("mytool/GetProjects?cyfy=") + Switch;
$('#myDatatable').DataTable().ajax.url(testURL).load();
            */
                $(() => {
                    $('.js_folder').on('click', function() {
                        var folder = $(this).data('folder');
                        var dataTableMail = $('.js_mailbox_emails').DataTable();
                        var current_url = dataTableMail.ajax.url();
                        var current_base_url = current_url.split('?')[0];
                        var new_url = current_base_url + '?folder=' + folder;
                        //initParams.sAjaxSource = "new_url";
                        var current_parameters = dataTableMail.ajax.params();
                        current_parameters.sAjaxSource = new_url;
                        current_parameters.bDestroy = true;
                        $('.js_mailbox_emails').DataTable(current_parameters);
                    });
                });
                function RicaricaQrcode(idriga) {
                    console.log("qua!");
                    /*$.ajax({
                    url: base_url + 'modulo-hr/qrcode/generate/1',
                    dataType: 'json',
                    data: {
                        reparto: reparto,
                    },
                    success: function(data) {
                        location.reload();
                        //console.log(data);
                        
                        if(data.data.qrcode_active != 1) {
                            location.reload();
                        }
                    }
                    });
                    setInterval(function() {
                    checkValideQrcode();
                    }, tempo_check_qrcode);*/
                }
            </script>