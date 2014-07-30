<?php

namespace Tiqbiz\Api;

class Settings extends Api
{

    public function __construct()
    {
        parent::__construct();

        add_action('admin_menu', array($this, 'settingsPage'));
        add_action('admin_init', array($this, 'settingsInit'));

        add_filter('plugin_action_links_' . TIQBIZ_API_PLUGIN_BASE, array($this, 'settingsLink'));

    }

    public function settingsPage()
    {
        add_options_page(
            'Tiqbiz API Settings',
            'Tiqbiz API Settings',
            'manage_options',
            'tiqbiz-api-settings',
            array($this, 'renderSettingsPage')
        );
    }

    public function settingsLink($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=tiqbiz-api-settings') . '">Settings</a>';

        array_unshift($links, $settings_link);

        return $links;
    }

    public function renderSettingsPage()
    {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>

            <h2>Tiqbiz API Settings</h2>

            <form method="post" action="options.php" id="tiqbiz_api_settings">

            <?php
                settings_fields('tiqbiz_api_settings_group');
                do_settings_sections('tiqbiz-api-settings');
                submit_button('Update');
            ?>

                <dl>
                    <dt>Wordpress Version</dt>
                    <dd><?php echo get_bloginfo('version'); ?></dd>
                    <dt>Plugin Version</dt>
                    <dd><?php echo $this->getPluginVersion(); ?></dd>
                    <dt>CalPress Version</dt>
                    <dd><?php echo $this->getPluginVersion(TIQBIZ_API_EVENT_PLUGIN); ?></dd>
                    <dt>PHP Version</dt>
                    <dd><?php echo phpversion(); ?></dd>
                </dl>
            </form>
        </div>
        <?php
    }

    public function settingsInit()
    {
        register_setting(
            'tiqbiz_api_settings_group',
            'tiqbiz_api_settings',
            array($this, 'sanitize')
        );

        add_settings_section(
            'api_settings',
            'API Settings',
            array($this, 'settingsPreamble'),
            'tiqbiz-api-settings'
        );

        add_settings_field(
            'cid',
            'CID',
            array($this, 'cidField'),
            'tiqbiz-api-settings',
            'api_settings'
        );

        add_settings_field(
            'api_key',
            'API Authentication Key',
            array($this, 'apiKeyField'),
            'tiqbiz-api-settings',
            'api_settings'
        );

        add_settings_field(
            'boxes',
            'Synced Tiqbiz Boxes',
            array($this, 'boxesField'),
            'tiqbiz-api-settings',
            'api_settings'
        );

        add_settings_field(
            'timeout',
            'API Request Timeout',
            array($this, 'timeoutField'),
            'tiqbiz-api-settings',
            'api_settings'
        );
    }

    public function sanitize($input)
    {
        $clean_input = array();

        if (isset($input['cid'])) {
            $clean_input['cid'] = absint($input['cid']);

            $this->cid = $clean_input['cid'];
        }

        if (isset($input['api_key'])) {
            $clean_input['api_key'] = sanitize_text_field($input['api_key']);

            $this->api_key = $clean_input['api_key'];
        }

        if (isset($input['timeout'])) {
            $clean_input['timeout'] = absint($input['timeout']);

            $this->timeout = $clean_input['timeout'];
        }

        try {
            $clean_input['boxes'] = $this->getBoxes();
        } catch (\Exception $e) {
            $clean_input['boxes'] = array();
        }

        return $clean_input;
    }

    public function settingsPreamble()
    {
        $this->checkSettings();

        echo 'Enter your settings below - these will be provided by the Tiqbiz team.';
    }

    public function cidField()
    {
        echo sprintf(
            '<input type="number" id="cid" name="tiqbiz_api_settings[cid]" value="%s" max="9999">',
            esc_attr($this->cid)
        );
    }

    public function apiKeyField()
    {
        echo sprintf(
            '<input type="text" id="api_key" name="tiqbiz_api_settings[api_key]" value="%s" maxlength="40">',
            esc_attr($this->api_key)
        );
    }

    public function boxesField() {
        if ($this->boxes) {
            echo '<ul id="boxes">';

            foreach ($this->boxes as $box) {
                echo '<li>', $box['name'], ' - ', $box['description'], '</li>';
            }

            echo '</ul>';
        } else {
            echo 'None, yet.';
        }
    }

    public function timeoutField()
    {
        echo sprintf(
            '<input type="number" id="timeout" name="tiqbiz_api_settings[timeout]" value="%s" max="100"> seconds' .
            '<p><em>Please don\'t change this unless directed to do so by the Tiqbiz team</em></p>',
            esc_attr($this->timeout)
        );
    }

    private function checkSettings()
    {
        if (!$this->cid || !$this->api_key) {
            return;
        }

        try {
            $name = $this->getApiAuthName();
        } catch (\Exception $e) {
            $error = $e->getMessage();
            
        }

        if ($name) {
            ?>
            <div class="updated">
                <p>Tiqbiz API plugin set up correctly for <?php echo $name; ?></p>
            </div>
            <?php
        } else {
            ?>
            <div class="error">
                <p>There seems to be a problem with the CID or API Key (or with communicating with the Tiqbiz server)</p>
            <?php

            if (isset($error)) {
                ?>
                <p class="dampen">Error message: <?php echo $error; ?> (this may help the Tiqbiz team solve technical issues)</p>
                <?php
            }

            ?>
                </div>
            <?php
        }
    }

}