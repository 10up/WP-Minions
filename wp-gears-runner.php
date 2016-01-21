<?php
/**
 * WpGears Runner
 *
 * IMPORTANT: This file must be placed in (or symlinked to) the root of the WordPress install!
 *
 * If Composer is present it will be used, Else a custom autoloader will
 * be used in it's place.
 */

require_once __DIR__ . '/autoload.php';

function wp_gears_runner() {
	wp_gears_autoloader();

	$plugin = \WpGears\Plugin::get_instance();
	$plugin->enable();

	return $plugin->run();
}

if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
	ignore_user_abort( true );

	if ( ! empty( $_POST ) || defined( 'DOING_AJAX' ) || defined( 'DOING_ASYNC' ) ) {
		die();
	}

	define( 'DOING_ASYNC', true );

	if ( ! defined( 'ABSPATH' ) ) {
		/** Set up WordPress environment - using SCRIPT_FILENAME so that this file works even if its a symlink! */
		if ( ! file_exists( dirname( $_SERVER["SCRIPT_FILENAME"] ) . '/wp-load.php' ) ) {
			error_log(
				'WP Gears Fatal Error - Cannot find wp-load.php'
			);
		}

		require_once( dirname( $_SERVER["SCRIPT_FILENAME"] ) . '/wp-load.php' );
	}

	wp_gears_runner();
}
