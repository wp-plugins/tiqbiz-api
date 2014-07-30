<?php

namespace Tiqbiz\Api;

class Assets extends Api
{

    public function __construct()
    {
        $that = $this; // php 5.3 compatibility

        add_action('admin_enqueue_scripts', function() use ($that) {
            $that->addStylesheets();
            $that->addScripts();
        });
    }

    public function addStylesheets()
    {
        wp_register_style('tiqbiz-api-stylesheet', plugin_dir_url(TIQBIZ_API_PLUGIN_PATH) . 'assets/css/style.css', array(), $this->getPluginVersion());
        wp_enqueue_style('tiqbiz-api-stylesheet');
    }

    public function addScripts()
    {
        wp_register_script('tiqbiz-api-script', plugin_dir_url(TIQBIZ_API_PLUGIN_PATH) . 'assets/js/script.js', array(), $this->getPluginVersion(), true);
        wp_enqueue_script('tiqbiz-api-script');
    }

}