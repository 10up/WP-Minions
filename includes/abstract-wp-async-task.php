<?php

abstract class WP_Async_Task {

	public function __construct() {}

	abstract function add( $hook, $args = array() );
}

