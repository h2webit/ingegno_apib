<?php
$project_activities = $this->apilib->search('project_activities', ['project_activities_project_id' => $value_id]);
$system = [];
$reminders = [];
$notes = [];

if (!empty($project_activities)) {
    foreach ($project_activities as $index => $activity) {
        //debug($activity);
        if ($activity['project_activities_type'] == 2) {
            $reminders[] = $activity;
        } elseif ($activity['project_activities_type'] == 3) {
            $notes[] = $activity;
        } else {
            $systems[] = $activity;
        }
    }
}

$activities_type = $this->apilib->search('project_activities_type');
$reminder_type = $this->apilib->search('project_activities_reminder_type');
$users = $this->apilib->search('users');

$user = $this->session->userdata('session_login');
$user_id = $user['users_id'];

?>

<style>
    .project_activities_container {
        width: 100%;
        height: 500px;
        overflow-x: hidden;
        overflow-y: scroll;
    }

    .project_activities_container .divider {
        width: 100%;
        border: 1px solid #d1d5db;
        margin: 15px 0;
    }

    .project_activities_reminders {
        margin-bottom: 15px;
    }

    .media_custom {
        padding: 6px;
        border-radius: 4px;
        margin-bottom: 15px;
        min-height: 60px;
        border: 1px solid #ddd;
        background: #f3f4f6;
    }

    .media_custom_system {
        padding: 6px;
        border-radius: 4px;
        margin-bottom: 15px;
        min-height: 40px;
        border: 1px solid #ddd;
        background: #f3f4f6;
    }

    .project_activities_reminders .media_custom .reminder_actions {
        width: 100%;
        display: flex;
        /*justify-content: flex-end;*/
        justify-content: space-between;
        align-items: center;
        font-size: 12px;
        padding-top: 5px;
        padding-right: 10px;
        padding-bottom: 2px;
    }

    .project_activities_reminders .media_custom .reminder_actions .btn {
        font-size: 10px;
    }

    p {
        margin-bottom: 5px;
    }

    .bg-transparent {
        background-color: transparent !important;
    }

    .btn_save_activity {
        width: 100px;
        color: #ffffff !important;
    }

    .done_action {
        margin-right: 5px;
    }

    .form_container {
        display: none;
        margin-top: 10px;
    }

    .toggleForm {
        width: 100%;
    }

    .custom_avatar {
        width: 40px;
        /*height: 40px;*/
    }
</style>

<div class="project_activities_container">

    <div class="project_activities_action">
        <button class="btn btn-primary btn-sm toggleForm"><?php e('Create activity'); ?></button>
    </div>

    <div class="form_container">
        <form action="<?php echo base_url('projects/activities/saveActivity'); ?>" method="POST" class="formAjax" id="project_activities_form">
            <?php add_csrf(); ?>

            <input name="project_activities_project_id" class="form-control hidden" value="<?php echo $value_id; ?>">
            <input name="project_activities_created_by" class="form-control hidden" value="<?php echo $user_id; ?>">

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="control-label"><?php e('Type'); ?></label>
                        <select class="form-control select_activity" name="project_activities_type">
                            <?php foreach ($activities_type as $activity) :
                                if ($activity['project_activities_type_id'] != 1) :
                            ?>
                                    <option value="<?php echo $activity['project_activities_type_id']; ?>" <?php echo $activity['project_activities_type_id'] == 3 ? 'selected' : ''; ?>>
                                        <?php echo $activity['project_activities_type_value']; ?>
                                    </option>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </select>
                    </div>
                </div>

                <div class="col-md-4 hidden hidden_custom_field">
                    <div class="form-group">
                        <label class="control-label"><?php e('Reminder type'); ?></label>
                        <select class="form-control select_activity" name="project_activities_reminder_type">
                            <?php foreach ($reminder_type as $reminder) : ?>
                                <option value="<?php echo $reminder['project_activities_reminder_type_id']; ?>">
                                    <?php e($reminder['project_activities_reminder_type_value']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 hidden hidden_custom_field">
                    <div class="form-group">
                        <label class="control-label"><?php e('Assign to'); ?></label>
                        <select class="form-control select_activity" name="project_activities_assign_to">
                            <?php foreach ($users as $user) : ?>
                                <option value="<?php echo $user['users_id']; ?>" <?php echo $user['users_id'] ==  $user_id ? 'selected' : ''; ?>>
                                    <?php echo $user['users_first_name'] . ' ' . $user['users_last_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4 hidden hidden_custom_field">
                    <div class="form-group">
                        <label class="control-label"><?php e('Date'); ?></label>
                        <input name="project_activities_date" class="form-control js_form_datepicker">
                    </div>
                </div>
            </div>

            <div class=" row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="control-label"><?php e('Text'); ?></label>
                        <textarea type="textarea" rows="4" name="project_activities_text" class="form-control" placeholder="<?php e('Enter activities description...'); ?>"></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12 text-center">
                    <button type="submit" class="btn font_bold btn-success btn_save_activity"><?php e('Save'); ?></button>
                </div>
            </div>

        </form>
    </div>

    <div class="project_activities_reminders">
        <?php if (!empty($reminders)) : ?>
            <h4><?php echo e('Reminders'); ?></h4>
            <?php foreach ($reminders as $reminder) : ?>
                <div class="single_reminder">
                    <?php
                    $created_by = $this->apilib->view('users', $reminder['project_activities_created_by']);
                    $assign_to = [];

                    /* dump($reminder);
                    dump(!empty($reminder['project_activities_assign_to']) && $reminder['project_activities_created_by'] != $reminder['project_activities_assign_to']); */

                    if (!empty($reminder['project_activities_assign_to']) && $reminder['project_activities_created_by'] != $reminder['project_activities_assign_to']) {
                        $assign_to = $this->apilib->view('users', $reminder['project_activities_assign_to']);
                    }

                    $datetime1 = new DateTime();
                    $datetime2 = new DateTime($reminder['project_activities_date']);
                    $diff = $datetime2->diff($datetime1);

                    $days = $diff->d;
                    $hours = $diff->h;
                    $minutes = $diff->i;

                    $expiration_date = '';
                    if ($days > 0) {
                        $expiration_date .= "{$days} " . ($days == 1 ? t('day') : t('days'));
                    }
                    if ($hours > 0) {
                        $expiration_date .= " {$hours} " . ($hours == 1 ? t('hour') : t('hours'));
                    }
                    if ($minutes > 0) {
                        $expiration_date .= " {$minutes} " . ($minutes == 1 ? t('minute') : t('minutes'));
                    }
                    ?>

                </div>
                <ul class="media-list">
                    <li class="media media_custom">
                        <div class="media-left">
                            <img class="img-circle custom_avatar" src="<?php echo base_url((!empty($created_by['users_avatar']) ? 'thumb/40/40/1/uploads/' . $created_by['users_avatar'] : 'images/user.png')); ?>">
                        </div>
                        <div class="media-body">
                            <h5 class="media-heading">
                                <b><?php echo $created_by['users_first_name'] . ' ' . $created_by['users_last_name']; ?></b> - <span class="text-muted"><?php e($reminder['project_activities_reminder_type_value']); ?></span>
                                <strong class="label label-default firegui_fontsize10 pull-right">
                                    <?php e('Expired in:'); ?>
                                    <?php echo $expiration_date; ?>
                                </strong>
                            </h5>
                            <p><?php echo !empty($reminder['project_activities_text']) ? $reminder['project_activities_text'] : '-'; ?></p>
                            <div class="reminder_actions">
                                <div>
                                    <?php if (!empty($assign_to)) :  ?>
                                        <?php e('Assign to: ') ?> <?php echo $assign_to['users_first_name'] . ' ' . $assign_to['users_last_name']; ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php if ($reminder['project_activities_done'] == DB_BOOL_FALSE) : ?>
                                        <a class="btn btn-success btn-xs single_actions bg-transparent font_bold text-green text-uppercase done_action js_link_ajax" href="<?php echo base_url('projects/activities/editActivityState/' . $reminder['project_activities_id']); ?>">
                                            <i class="fa fa-check"></i>
                                            <span><?php echo e('Done'); ?></span>
                                        </a>
                                    <?php endif; ?>
                                    <a class="btn btn-danger btn-xs single_actions bg-transparent font_bold text-red text-uppercase delete_action" href="<?php echo base_url('db_ajax/generic_delete/project_activities/' . $reminder['project_activities_id']); ?>">
                                        <i class="fa fa-trash"></i>
                                        <span><?php echo e('Delete'); ?></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="project_activities_activities">
        <?php if (!empty($notes)) : ?>
            <h4><?php echo e('Notes'); ?></h4>
            <?php foreach ($notes as $note) : ?>
                <div class="single_activity">
                    <?php
                    $created_by = $this->apilib->view('users', $note['project_activities_created_by']);
                    ?>
                </div>
                <ul class="media-list">
                    <li class="media media_custom">
                        <div class="media-left">
                            <img class="img-circle custom_avatar" src="<?php echo base_url((!empty($created_by['users_avatar']) ? 'thumb/40/40/1/uploads/' . $created_by['users_avatar'] : 'images/user.png')); ?>">
                        </div>
                        <div class="media-body">
                            <h5 class="media-heading">
                                <b><?php echo $created_by['users_first_name'] . ' ' . $created_by['users_last_name']; ?></b>
                                <strong class="label label-default firegui_fontsize10 pull-right">
                                    <?php echo time_elapsed($note['project_activities_creation_date']); ?>
                                </strong>
                            </h5>
                            <p><?php echo !empty($note['project_activities_text']) ? $note['project_activities_text'] : '-'; ?></p>
                        </div>
                    </li>
                </ul>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="project_activities_systems">
        <?php if (!empty($systems)) : ?>
            <h4><?php echo e('System log'); ?></h4>
            <?php foreach ($systems as $system) : ?>
                <div class="single_activity">
                    <?php
                    if ($system['project_activities_created_by']) {
                        $created_by = $this->apilib->view('users', $system['project_activities_created_by']);
                    } else {
                        $created_by['users_first_name'] = 'System';
                        $created_by['users_last_name'] = '';
                    }

                    ?>
                </div>
                <ul class="media-list">
                    <li class="media media_custom_system">
                        <div class="media-left">
                            <img class="img-circle custom_avatar" src="<?php echo base_url((!empty($created_by['users_avatar']) ? 'thumb/40/40/1/uploads/' . $created_by['users_avatar'] : 'images/user.png')); ?>">
                        </div>
                        <div class="media-body">
                            <div class="media_container">
                                <h5 class="media-heading">
                                    <b><?php echo $created_by['users_first_name'] . ' ' . $created_by['users_last_name']; ?></b>
                                    <strong class="label label-default firegui_fontsize10 pull-right">
                                        <?php echo dateFormat($system['project_activities_creation_date'], 'd/m/Y'); ?>
                                    </strong>
                                </h5>
                                <p>
                                    <?php echo !empty($system['project_activities_text']) ? $system['project_activities_text'] : '-'; ?>
                                </p>
                            </div>
                        </div>
                    </li>
                </ul>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


<script>
    $(function() {
        $('.select_activity').select2();

        const toggleForm = $('.toggleForm');
        const formContainer = $('.form_container');

        toggleForm.on('click', function() {
            formContainer.slideToggle('100');

            if ($('.form_container').is(':hidden')) {
                toggleForm.text('Chiudi');
            } else {
                toggleForm.text('Crea attività');
            }
        });

        const form = $('#project_activities_form');

        $('[name="project_activities_type"]', form).on('change', function() {
            var activity_id = $(this).val();
            if ($(this).val() === "2") {
                $('[name="project_activities_date"]').val(moment().format('DD/MM/YYYY'));
                $('.hidden_custom_field').removeClass('hidden');
                $('.hidden_custom_field').show();
            } else {
                $('[name="project_activities_date"]').val('');
                $('.hidden_custom_field').hide();
            }
        });


        $('[name="project_activities_done"]', form).on('change', function() {
            var done = $(this);
            if (done.is(':checked')) {
                $('[name="project_activities_done_date"]').val(moment().format('DD/MM/YYYY'));
            } else {
                $('[name="project_activities_done_date"]').val('');
            }
        })
    });
</script>