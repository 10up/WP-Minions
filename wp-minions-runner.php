<?php
/**
 * WP Minions Runner
 *
 * IMPORTANT: This file must be placed in (or symlinked to) the root of the WordPress install!
 *
 * If Composer is present it will be used, Else a custom autoloader will
 * be used in it's place.
 */

$wp_minions_autoloader_path = wp_minions_autoloader_file();

if ( $wp_minions_autoloader_path ) {
	require_once( $wp_minions_autoloader_path );
} else {
	return;
}

function wp_minions_runner() {
	wp_minions_autoloader();

	$plugin = \WpMinions\Plugin::get_instance();
	$plugin->enable();

	return $plugin->run();
}

function wp_minions_autoloader_file() {
	if ( file_exists( __DIR__ . '/autoload.php' ) ) {
		return __DIR__ . '/autoload.php';
	} else if ( file_exists( __DIR__ . '/wp-content/plugins/wp-minions/autoload.php' ) ) {
		return __DIR__ . '/wp-content/plugins/wp-minions/autoload.php';
	} else if ( defined( 'WP_MINIONS_DIR' ) ) {
		return WP_MINIONS_DIR . '/autoload.php';
	} else {
		error_log( 'WP Minions Fatal Error - Cannot find autoload.php' );
		return false;
	}
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
				'WP Minions Fatal Error - Cannot find wp-load.php'
			);
		}

		require_once( dirname( $_SERVER["SCRIPT_FILENAME"] ) . '/wp-load.php' );
	}

	wp_minions_runner();
}
