<?php
/**
 * Plugin Name: WP Minions
 * Description: Job Queue for WordPress
 * Version: 3.0.0
 * Author: Chris Marslender, Darshan Sawardekar, 10up
 * Author URI: http://10up.com/
 * License: GPLv2 or later
 */

require_once __DIR__ . '/autoload.php';

/*
 * Users with setups where multiple installs share a common wp-config.php or $table_prefix can use this to segregate jobs from each site
 */
if ( ! defined( 'WP_ASYNC_TASK_SALT' ) ) {
	define( 'WP_ASYNC_TASK_SALT', '' );
}

/**
 * Adds a single async task to the gearman job queue.
 *
 * @param string $hook The action that will be called when running this job.
 * @param array $args An array of args that should be passed to the callback when the action hook is called.
 * @param string $priority Priority of the job (low, normal, high). Default normal.
 *
 * @since 0.1
 */
function wp_async_task_add( $hook, $args = array(), $priority = 'normal' ) {
	$plugin = \WpMinions\Plugin::get_instance();

	return $plugin->add( $hook, $args, $priority );
}

function wp_async_task_init() {
	wp_minions_autoloader();

	$plugin = \WpMinions\Plugin::get_instance();
	$plugin->enable();

	$GLOBALS['wp_async_task'] = $plugin;
}

add_action( 'plugins_loaded', function() {
	global $wp_async_task;

	if ( class_exists( 'Debug_Bar_Extender' ) ) {
		Debug_Bar_Extender::instance()->trace_var( $wp_async_task );
	}
});

// Init
if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
	wp_async_task_init();
}

