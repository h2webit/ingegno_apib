<?php foreach ($notifications as $notification) : ?>
<?php
    echo sprintf(
        '<li data-notification="%s" class="notification %s" data-title="%s" data-message="%s" data-type="%s">',
        $notification['notifications_id'],
        ($notification['notifications_read'] === DB_BOOL_FALSE) ? 'unread' : '',
        $notification['notifications_title'],
        htmlspecialchars($notification['notifications_message'], ENT_QUOTES, 'UTF-8'),
        $notification['notifications_type']
    );
?>

<a href="<?php echo $notification['href']; ?>">
    <h4>
        <?php echo ($notification['notifications_title']) ? (strlen($notification['notifications_title']) > 45 ? substr($notification['notifications_title'], 0, 42).'...' : $notification['notifications_title']) : 'Notification'; ?>
        <?php if($notification['notifications_read'] === DB_BOOL_FALSE) : ?>
        <span class="unread_notification_icon"></span>
        <?php endif; ?>
    </h4>
    <p class="notification_text"><?php echo preg_replace('|<br\s*/?>|i', '&nbsp; ', substr($notification['notifications_message'], 0, 50)); ?></p>
    <small><i class="fa fa-clock-o"></i><?php echo $notification['datespan']; ?></small>
</a>
<?php echo '<li>'; ?>
<?php endforeach; ?>