<?php
$reminder_categories = $this->apilib->search('reminder_categories');

if (!empty($reminder_categories)) :

    $cols = (count($reminder_categories) <= 4) ? '3' : '2';

    foreach ($reminder_categories as $status) :
        $reminders_count = $this->db->query("SELECT COUNT(*) AS count FROM reminders WHERE reminders_category = '{$status['reminder_categories_id']}'")->row()->count;
?>

        <div class="col-md-<?php echo $cols ?>">
            <div class="small-box" style="background-color: <?php echo $status['reminder_categories_color'] ?? '#4b4b4b'; ?>; color: white;">
                <div class="inner">
                    <h3><?php echo $reminders_count; ?></h3>

                    <p><?php e($status['reminder_categories_text']); ?></p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
            </div>
        </div>
<?php
    endforeach;
endif;
?>
