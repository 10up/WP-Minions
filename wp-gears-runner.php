<?php
/**
 * WordPress Async Tasks Implementation.
 *
 * IMPORTANT: This file must be placed in the root of the WordPress install!
 *
 * // todo test/verify multisite compatability
 */

ignore_user_abort(true);

if ( ! empty( $_POST ) || defined( 'DOING_AJAX' ) || defined( 'DOING_ASYNC' ) ) {
	die();
}

/**
 * Tell WordPress we are doing the ASYNC task.
 *
 * @var bool
 */
define('DOING_ASYNC', true);

if ( ! defined( 'ABSPATH' ) ) {
	/** Set up WordPress environment */
	require_once( dirname( __FILE__ ) . '/wp-load.php' );
}

global $wp_async_task;

if ( method_exists( $wp_async_task, 'work' ) ) {
	$wp_async_task->work();
}

die();
