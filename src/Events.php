<?php

namespace Tiqbiz\Api;

class Events extends Sync
{

    public function __construct()
    {
        if (!class_exists(TIQBIZ_API_EVENT_CLASS)) {
            return;
        }

        parent::__construct();

        add_action('add_meta_boxes', array($this, 'addBoxesMetaBox'));
        add_action('add_meta_boxes', array($this, 'addNotificationMetaBox'));
        add_action('save_post', array($this, 'syncEvent'));
    }

    public function addBoxesMetaBox()
    {
        if (!$this->boxes) {
            return;
        }

        add_meta_box(
            'tiqbiz-api-boxes-metabox',
            'Tiqbiz Boxes',
            array($this, 'renderBoxesMetaBox'),
            'calp_event',
            'side',
            'high'
        );
    }

    public function addNotificationMetaBox()
    {
        if (!$this->boxes) {
            return;
        }

        add_meta_box(
            'tiqbiz-api-notification-metabox',
            'Tiqbiz Notification',
            array($this, 'renderSendNotificationMetaBox'),
            'calp_event',
            'side',
            'high'
        );
    }

    public function renderSendNotificationMetaBox($post)
    {
        $send_notification = $this->getPostSendNotificationStatus($post->ID);

        $nonce_id = 'tiqbiz_api_nonce_update_post_send_notification';

        wp_nonce_field($nonce_id, $nonce_id, false);

        ?>
        <input type="hidden" name="tiqbiz_api_send_notification" value="">

        <ul>
            <li>
                <label>
                    <input type="checkbox" name="tiqbiz_api_send_notification"<?php echo $send_notification ? ' checked="checked"' : ''; ?>>
                    Send 24 Hour Tiqbiz Notification
                </label>
            </li>
        </ul>
        <?php
    }

    public function syncEvent($event_id)
    {
        if (get_post_type($event_id) != 'calp_event') {
            return;
        }

        if (!$this->validateSave($event_id)) {
            return;
        }

        $this->savePostBoxes($event_id);
        $this->saveSendNotificationStatus($event_id);

        try {
            $event = new \Calp_Event($event_id);
        } catch (\Exception $e) {
            return;
        }

        $tiqbiz_api_id = (int)get_post_meta($event_id, '_tiqbiz_api_id', true);

        $event_data = array();

        if ($tiqbiz_api_id) {
            $event_data['id'] = $tiqbiz_api_id;
        }

        $event_data['title'] = $event->post->post_title;

        $boxes = $this->getPostBoxes($event_id);
        $send_notification = $this->getPostSendNotificationStatus($event_id);

        if ($event->post->post_status == 'publish' && $boxes) {
            $event_data['content'] = wpautop($event->post->post_content);
            $event_data['boxes'] = $boxes;

            $event_data['start'] = $this->formatDateFromTime($event->start);
            $event_data['end'] = $event->allday ? $event_data['start'] : $this->formatDateFromTime($event->end);

            $event_data['all_day'] = (bool)$event->allday;

            $event_data['show_map'] = (bool)$event->show_map;

            $event_data['location_name'] = $event->venue;
            $event_data['location_address'] = $event->address;

            $event_data['send_notification'] = $send_notification;

            $this->queueAction($event_id, 'createEvent', $event_data);
        } else if ($tiqbiz_api_id) {
            $this->queueAction($event_id, 'deleteEvent', $event_data);
        }
    }

    private function saveSendNotificationStatus($post_id)
    {
        if (!isset($_POST['tiqbiz_api_send_notification'])) {
            return;
        }

        $nonce_id = 'tiqbiz_api_nonce_update_post_send_notification';

        if (!isset($_POST[$nonce_id])) {
            return;
        }

        $nonce = $_POST[$nonce_id];

        if (!wp_verify_nonce($nonce, $nonce_id)) {
            return;
        }

        $send_notification = (bool)$_POST['tiqbiz_api_send_notification'];

        update_post_meta($post_id, '_tiqbiz_api_send_notification', $send_notification);
    }

    private function getPostSendNotificationStatus($post_id)
    {
        global $pagenow;

        if ($pagenow == 'post-new.php') {
            return true;
        }

        return (bool)get_post_meta($post_id, '_tiqbiz_api_send_notification', true);
    }

}