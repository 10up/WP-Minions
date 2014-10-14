<?php
/**
 * WordPress Async Tasks Implementation.
 *
 * IMPORTANT: This file must be placed in (or symlinked to) the root of the WordPress install!
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
	/** Set up WordPress environment - using SCRIPT_FILENAME so that this file works even if its a symlink! */
	if ( ! file_exists( dirname( $_SERVER["SCRIPT_FILENAME"] ) . '/wp-load.php' ) ) {
		throw new Exception( "Cannot find wp-load.php" );
	}

	require_once( dirname( $_SERVER["SCRIPT_FILENAME"] ) . '/wp-load.php' );
}

global $wp_async_task;

if ( method_exists( $wp_async_task, 'work' ) ) {
	$wp_async_task->work();
}

die();
