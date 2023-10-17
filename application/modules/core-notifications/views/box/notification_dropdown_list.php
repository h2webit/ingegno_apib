<?php $user_session = $this->auth->getSessionUserdata(); ?>
<meta name='enable-user-sounds' content="<?php echo (!isset($user_session['users_enable_notifications_sound']) || $this->auth->get('users_enable_notifications_sound') == DB_BOOL_FALSE ? 'false' : 'true'); ?>" />

<!-- BEGIN NOTIFICATION DROPDOWN -->
<li class="dropdown messages-menu" id="header_notification_bar">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
        <i class="far fa-bell notification_icon"></i>
        <span class="js_notification_number_label badge bg-red"></span>
    </a>
    <ul class="dropdown-menu">
        <li class="header">
            <span><?php e('You have'); ?> <span class="js_notification_number bold">0</span> <?php e('new notifications'); ?></span>
            <a href="#" onclick="CrmNotifier.readAll();return false;" role='button' class="read_all_notification_icon">
                <i class="fas fa-check"></i>
            </a>
        </li>

        <li>
            <!-- inner menu: contains the actual data -->
            <ul class="menu js_notification_dropdown_list dropdown-menu-list scroller firegui_notification">
                <li></li>
            </ul>
        </li>
    </ul>
</li>
<!-- END NOTIFICATION DROPDOWN -->
