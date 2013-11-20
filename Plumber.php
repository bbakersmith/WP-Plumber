<?php

/**
 * Plugin Name: WP Plumber
 * Plugin URI: 
 * Description: Wordpress clogging up your workflow? WP Plumber can help! After all, the internet is just a series of tubes.
 * Version: 0.1
 * Author: Ben Baker-Smith
 * Author URI: http://bitsynthesis.com
 * License: GPL2
 */

$plumber_plugin_directory = dirname(__FILE__).'/lib/wp-plumber/';
require_once($plumber_plugin_directory.'/Plumber.class.php');
require_once($plumber_plugin_directory.'/PlumberRoute.class.php');
require_once($plumber_plugin_directory.'/PlumberSpecialRoutes.class.php');
require_once($plumber_plugin_directory.'/PlumberRouteFactory.class.php');
require_once($plumber_plugin_directory.'/PlumberPods.class.php');

add_action('wp_router_generate_routes', 'Plumber::create_routes', 20);

?>
