<?php

namespace Tiqbiz\Api;

class Assets extends Api
{

    public function __construct()
    {
        add_action('admin_enqueue_scripts', function() {
            $this->addStylesheets();
            $this->addScripts();
        });
    }

    private function addStylesheets()
    {
        wp_register_style('tiqbiz-api-stylesheet', plugin_dir_url(TIQBIZ_API_PLUGIN_PATH) . 'assets/css/style.css', array(), $this->getPluginVersion());
        wp_enqueue_style('tiqbiz-api-stylesheet');
    }

    private function addScripts()
    {
        wp_register_script('tiqbiz-api-script', plugin_dir_url(TIQBIZ_API_PLUGIN_PATH) . 'assets/js/script.js', array(), $this->getPluginVersion(), true);
        wp_enqueue_script('tiqbiz-api-script');
    }

}