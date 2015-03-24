<?php

namespace Tiqbiz\Api;

class Sync extends Api
{

    public function __construct()
    {
        parent::__construct();

        add_action('init', array($this, 'sessionStart'));
        add_action('wp_login', array($this, 'sessionDestroy'));
        add_action('wp_logout', array($this, 'sessionDestroy'));

        add_action('admin_notices', array($this, 'runActions'));

        add_action('wp_ajax_tiqbiz_api_action', array($this, 'actionProxy'));
        add_action('wp_ajax_tiqbiz_api_id_callback', array($this, 'updatePostTiqbizId'));
    }

    public function sessionStart()
    {
        if (!session_id()) {
            session_start();
        }
    }

    public function sessionDestroy()
    {
        session_destroy();
    }

    public function renderBoxesMetaBox($post)
    {
        $checked_boxes = $this->getPostBoxes($post->ID);

        $nonce_id = 'tiqbiz_api_nonce_update_post_boxes';

        wp_nonce_field($nonce_id, $nonce_id, false);

        ?>
        <input type="hidden" name="tiqbiz_api_box" value="">

        <ul>
        <?php

        foreach ($this->boxes as $box) {
            $checked_markup = '';

            if (in_array($box['slug'], $checked_boxes)) {
                $checked_markup = ' checked="checked"';
            }

            ?>
            <li>
                <label>
                    <input type="checkbox" name="tiqbiz_api_box[]" value="<?php echo $box['slug']; ?>"<?php echo $checked_markup; ?>>
                    <?php
                        echo $box['name'], ' - ', $box['description'];
                    ?>
                </label>
            </li>
            <?php
        }

        ?>
        </ul>
        <?php
    }

    public function runActions()
    {
        $actions = $this->getActions();

        if (!$actions) {
            return;
        }

        wp_localize_script('tiqbiz-api-script', 'tiqbiz_api_data', array(
            'timeout' => $this->timeout,
            'action_queue' => array_values($actions)
        ));

        ?>

        <div class="update-nag updated in-progress" id="tiqbiz_api_sync_progress">
            <img src="<?php echo plugin_dir_url(TIQBIZ_API_PLUGIN_PATH) . 'assets/img/logo.png'; ?>" alt="tiqbiz">
            <p class="in-progress-message">
                <span class="spinner"></span>
                <em>Please wait while we sync updates with your Tiqbiz account...</em>
            </p>
            <p class="in-progress-message">
                <strong>Don't close or navigate away from this page until the process has completed.</strong>
            </p>
            <ol></ol>
        </div>

        <div class="update-nag updated" id="tiqbiz_api_sync_success">
            <p>All updates successfully synced with Tiqbiz.</p>
        </div>

        <?php
    }

    public function actionProxy()
    {
        $method = isset($_REQUEST['method']) ? $_REQUEST['method'] : '';
        $payload = isset($_REQUEST['payload']) ? $_REQUEST['payload'] : '';

        try {
            $response = $this->apiAction($method, $payload, true);
        } catch (\Exception $e) {
            $response = json_encode(array(
                'success' => false,
                'error_message' => $e->getMessage()
            ));
        }

        $this->jsonHeader();

        exit($response);
    }

    public function updatePostTiqbizId()
    {
        $return = function($success, $error_message = '') {
            $this->jsonHeader();

            exit(json_encode(array(
                'success' => $success,
                'error_message' => $error_message
            )));
        };

        if (!check_ajax_referer('tiqbiz_api_nonce_update_post_tiqbiz_id', 'nonce', false)) {
            $return(false, 'Invalid nonce');
        }

        if (!isset($_POST['internal_id'])) {
            $return(false, 'Missing \'internal_id\' param');
        }

        $post_id = $_POST['internal_id'];

        if (!$this->validateSave($post_id)) {
            $return(false, 'Invalid user permissions');
        }

        if (!isset($_POST['tiqbiz_api_id'])) {
            $return(false, 'Missing \'tiqbiz_api_id\' param');
        }

        $tiqbiz_api_id = $_POST['tiqbiz_api_id'];

        update_post_meta($post_id, '_tiqbiz_api_id', $tiqbiz_api_id);

        $return(true);
    }

    protected function validateSave($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return false;
        }

        if (wp_is_post_revision($post_id)) {
            return false;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return false;
        }

        return true;
    }

    protected function savePostBoxes($post_id)
    {
        if (!isset($_POST['tiqbiz_api_box'])) {
            return;
        }

        $nonce_id = 'tiqbiz_api_nonce_update_post_boxes';

        if (!isset($_POST[$nonce_id])) {
            return;
        }

        $nonce = $_POST[$nonce_id];

        if (!wp_verify_nonce($nonce, $nonce_id)) {
            return;
        }

        $boxes = array_filter((array)$_POST['tiqbiz_api_box']);

        update_post_meta($post_id, '_tiqbiz_api_boxes', wp_slash(json_encode($boxes)));
    }

    protected function getPostBoxes($post_id)
    {
        return (array)json_decode(get_post_meta($post_id, '_tiqbiz_api_boxes', true));
    }

    protected function queueAction($post_id, $method, $payload)
    {
        if (!isset($_SESSION['tiqbiz_api_action_queue'])) {
            $_SESSION['tiqbiz_api_action_queue'] = array();
        }

        $nonce = wp_create_nonce('tiqbiz_api_nonce_update_post_tiqbiz_id');

        $_SESSION['tiqbiz_api_action_queue'][$post_id . '_' . $action] = array(
            'internal_id' => $post_id,
            'method' => $method,
            'payload' => $payload,
            'nonce' => $nonce
        );
    }

    protected function formatDateFromTime($time)
    {
        $wp_timezone = get_option('timezone_string');

        if ($wp_timezone) {
            date_default_timezone_set($wp_timezone);
        }

        $date = date('Y-m-d\TH:i', $time);

        return $date;
    }

    private function getActions()
    {
        if (
            isset($_SESSION['tiqbiz_api_action_queue']) &&
            is_array($_SESSION['tiqbiz_api_action_queue'])
        ) {
            $actions = $_SESSION['tiqbiz_api_action_queue'];

            $_SESSION['tiqbiz_api_action_queue'] = array();

            return $actions;
        } else {
            return array();
        }
    }

    private function jsonHeader()
    {
        header('Content-Type: application/json');
    }

}