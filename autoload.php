<?php

function wp_gears_autoloader() {
	global $wp_gears_autoloaded;

	if ( ! $wp_gears_autoloaded ) {
		$composer_autoloader = __DIR__ . '/vendor/autoload.php';

		if ( file_exists( $composer_autoloader ) ) {
			require_once( $composer_autoloader );
		} else {
			spl_autoload_register( 'wp_gears_autoload' );
		}

		$wp_gears_autoloaded = true;
	}
}

function wp_gears_autoload( $class_path ) {
	if ( strpos( $class_path, 'WpGears\\' ) !== false ) {
		$class_file  = __DIR__ . '/includes/';
		$class_file .= str_replace( '\\', '/', $class_path );
		$class_file .= '.php';

		if ( file_exists( $class_file ) ) {
			require_once( $class_file );
		}
	}
}



