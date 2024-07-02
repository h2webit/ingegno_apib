<?php
$projects_status = $this->apilib->search('projects_status');

if (!empty($projects_status)) :

    $cols = (count($projects_status) <= 4) ? '3' : '2';

    foreach ($projects_status as $status) :
        $projects_count = $this->db->query("SELECT COUNT(*) AS count FROM projects WHERE projects_status = '{$status['projects_status_id']}'")->row()->count;
?>

        <!-- <div class="row"> -->
        <div class="col-md-<?php echo $cols ?>">
            <div class="small-box" style="background-color: <?php echo $status['projects_status_color'] ?? '#4b4b4b'; ?>; color: white;">
                <div class="inner">
                    <h3><?php echo $projects_count; ?></h3>

                    <p><?php e($status['projects_status_value']); ?></p>
                </div>
                <div class="icon">
                    <i class="fas fa-project-diagram"></i>
                </div>
                <a href="<?php echo base_url('projects/main/filter_project_status/' . $status['projects_status_id'].'/commesse'); ?>" class="small-box-footer"><?php e('Filter'); ?> <i class="fa fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <!-- </div> -->
<?php
    endforeach;
endif;
?>