<?php

/**
 * Plugin Name: Tiqbiz API
 * Plugin URI: http://www.tiqbiz.com/
 * Description: Integrates your WordPress site with the Tiqbiz API
 * Version: 1.0.6
 * Author: Tiqbiz
 * Author URI: http://www.tiqbiz.com/
 * License: CC BY-SA 4.0
 */

defined('ABSPATH') or exit(1);

if (!is_admin()) {
    return;
}

define('TIQBIZ_API_PLUGIN_PATH', __FILE__);
define('TIQBIZ_API_PLUGIN_BASE', plugin_basename(TIQBIZ_API_PLUGIN_PATH));

define('TIQBIZ_API_EVENT_PLUGIN', 'calpress-event-calendar/calpress.php');
define('TIQBIZ_API_EVENT_PLUGIN_PRO', 'calpress-pro/calpress-pro.php');

define('TIQBIZ_API_EVENT_CLASS', '\Calp_Event');

spl_autoload_register(function($class) {
    if (strpos($class, 'Tiqbiz\Api') === 0) {
        require_once plugin_dir_path(TIQBIZ_API_PLUGIN_PATH) . 'src/' . array_pop(explode('\\', $class)) . '.php';
    }
});

use Tiqbiz\Api\Assets;
use Tiqbiz\Api\Posts;
use Tiqbiz\Api\Events;
use Tiqbiz\Api\Settings;

new Assets();
new Posts();
new Events();
new Settings();