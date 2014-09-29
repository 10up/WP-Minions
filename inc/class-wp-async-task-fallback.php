<?php

class WP_Async_Task_Fallback extends WP_Async_Task {

	public function add( $hook, $args ) {
		// todo need to implement this
		throw new Exception( "Gearman not configured (using callback class) - Fallback not complete yet though :-) ");
	}

}
