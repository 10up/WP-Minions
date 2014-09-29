<?php
/**
 * Plugin Name: WP Gears
 * Description: Provides methods for scheduling async tasks using Gearman
 * Version: 0.1
 * Author: Chris Marslender
 */

include __DIR__ . '/inc/class-wp-async-task.php';
include __DIR__ . '/inc/class-tc-async-task.php';
include __DIR__ . '/inc/class-wp-async-task-fallback.php';

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
function wp_async_task_add( $hook, $args ) {
	global $wp_async_task;

	return $wp_async_task->add( $hook, $args );
}


function wp_async_task_init() {
	$async_task = new WP_Async_Task();
	$result = $async_task->init();

	if ( ! $result ) {
		// Fallback
		unset( $async_task );
		// todo fallback to non-gearman implementation!
		$async_task = new WP_Async_Task_Fallback();
	}

	$GLOBALS['wp_async_task'] = $async_task;
}

// Init
wp_async_task_init();
