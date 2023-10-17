<?php

class Main extends MX_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->settings = $this->db->get('settings')->row_array();
        $this->load->model('core-notifications/clientnotifications');
    }

    // FROM NATIVE DATAB

    /**
     * Notifiche
     */
    public function get_notifications($limit = null, $offset = 0)
    {
        return $this->clientnotifications->search([], $limit, $offset);
    }

    public function readAllNotifications()
    {
        return $this->clientnotifications->setReadAll();
    }

    public function readNotification($notificationId)
    {
        $this->clientnotifications->setRead($notificationId);
    }

    public function readDesktopNotified($notificationId)
    {
        $this->clientnotifications->setDesktopNotified($notificationId);
    }



    // FROM NATIVE DB AJAX AND GET AJAX METHODS

    public function notify_read($notificationId = null)
    {
        if ($notificationId && is_numeric($notificationId)) {
            $this->readNotification($notificationId);
        } else {
            $this->readAllNotifications();
        }
    }

    public function notify_desktop_notified($notificationId = null)
    {
        if ($notificationId && is_numeric($notificationId)) {
            $this->readDesktopNotified($notificationId);
        }
    }
    public function dropdown_notification_list()
    {
        // Check logged
        if ($this->auth->guest()) {
            set_status_header(401); // Unauthorized
            die('User not logged');
        }

        $notifications = $this->get_notifications(30, 0);

        // From 2.7.0 REMOVED. Now we have auto-update system
        //Check client version. If old add a notification on top
        // if ($version = checkClientVersion()) {
        //     $notifications = array_merge([
        //         [
        //             'notifications_type' => NOTIFICATION_TYPE_SYSTEM,
        //             'notifications_id' => null,
        //             'notifications_user_id' => null,
        //             'notifications_title' => '[System] Update available',
        //             'notifications_message' => "new version available ({$version})!<br />Click here to update.",
        //             'notifications_read' => DB_BOOL_FALSE,
        //             'notifications_date_creation' => date('Y-m-d h:i:s'),
        //             'notifications_link' => base_url('openbuilder/updateClient/1'),
        //             'href' => base_url('openbuilder/updateClient/1'),
        //             'label' => [
        //                 'class' => 'label-info',
        //                 'icon' => 'fas fa-globe-americas',
        //             ],
        //             'datespan' => date('d M'),
        //         ]
        //     ], $notifications);
        // }

        echo json_encode(
            array(
                'view' => $this->load->view('box/notification_dropdown_item', array('notifications' => $notifications), true),
                'count' => count($unread = array_filter($notifications, function ($n) {
                    return $n['notifications_read'] === DB_BOOL_FALSE;
                })),
                'errors' => count(array_filter($unread, function ($n) {
                    return $n['notifications_type'] == 0;
                })),
                'data' => $notifications,
            )
        );
    }
}
?>