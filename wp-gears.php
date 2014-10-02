<?php
/**
 * Plugin Name: WP Gears
 * Description: Provides methods for scheduling async tasks using Gearman
 * Version: 0.1
 * Author: Chris Marslender
 */

include __DIR__ . '/includes/abstract-wp-async-task.php';
include __DIR__ . '/includes/class-gearman-async-task.php';
include __DIR__ . '/includes/class-wp-async-task-fallback.php';

/*
 * Users with setups where multiple installs share a common wp-config.php or $table_prefix can use this to segregate jobs from each site
 */
if ( ! defined( 'WP_ASYNC_TASK_SALT' ) ) {
	define( 'WP_ASYNC_TASK_SALT', '' );
}

/**
 * Adds a single async task to gearman
 *
 * @since 0.1
 */
function wp_async_task_add( $hook, $args = array(), $priority = 'normal' ) {
	global $wp_async_task;

	return $wp_async_task->add( $hook, $args, $priority );
}


function wp_async_task_init() {
	$async_task = new Gearman_Async_Task();
	$result = $async_task->init();

	if ( ! $result ) {
		// Fallback
		unset( $async_task );
		$async_task = new WP_Async_Task_Fallback();
	}

	$GLOBALS['wp_async_task'] = $async_task;
}

// Init
wp_async_task_init();

add_action( 'plugins_loaded', function() {
	global $wp_async_task;

	if ( class_exists( 'Debug_Bar_Extender' ) ) {
		Debug_Bar_Extender::instance()->trace_var( $wp_async_task );
	}
});
