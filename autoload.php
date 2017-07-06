<?php

function wp_minions_autoloader() {
	global $wp_minions_autoloaded;

	if ( ! $wp_minions_autoloaded ) {
		$composer_autoloader = __DIR__ . '/vendor/autoload.php';

		if ( file_exists( $composer_autoloader ) ) {
			require_once( $composer_autoloader );
		} else {
			spl_autoload_register( 'wp_minions_autoload' );
		}

		$wp_minions_autoloaded = true;
	}
}

function wp_minions_autoload( $class_path ) {
	if ( strpos( $class_path, 'WpMinions\\' ) !== false ) {
		$class_file  = __DIR__ . '/includes/';
		$class_file .= str_replace( '\\', '/', $class_path );
		$class_file .= '.php';

		if ( file_exists( $class_file ) ) {
			require_once( $class_file );
		}
	}
}



