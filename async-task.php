<?php
/**
 * WP_Async_Task dropin for gearman
 *
 * todo need global $gearman_servers array - AND a default fallback
 *      $gearman_servers = array(
 *            '127.0.0.1:4730',
 *      );
 *
 *
 */

/**
 * Adds a single async task to gearman
 *
 * @since todo
 */
function wp_async_task_add( $hook, $args ) {
	global $wp_async_task;

	return $wp_async_task->add( $hook, $args );
}


function wp_async_task_init() {
	$GLOBALS['wp_async_task'] = new WP_Async_Task();

	if ( defined( 'DOING_ASYNC' ) && DOING_ASYNC ) {
		$GLOBALS['wp_async_task_runner'] = new WP_Async_Task_Runner();
	}
}

function wp_async_task_switch_to_blog( $blog_id ) {
	global $wp_async_task;

	return $wp_async_task->switch_to_blog( $blog_id );
}

class WP_Async_Task {

	//todo may need a CPT to track jobs in the database - For now just storing in Gearman - Will be problematic if gearmand restarts!

	public function add( $hook, $args ) {
		global $gearman_servers;

		$client = new GearmanClient();

		if ( empty( $gearman_servers ) ) {
			$client->addServer();
		} else {
			$client->addServers( implode( ',', $gearman_servers ) );
		}

		$jobdata = array();
		$jobdata['hook'] = $hook;
		$jobdata['args'] = $args;

		return $client->doBackground( 'WP_Async_Task', json_encode( $jobdata ) ); // todo Need blog_id, possibly salt value (for multiple single-sites)
	}

	public function switch_to_blog( $blog_id ) {
		// todo switch_to_blog
	}
}

class WP_Async_Task_Runner {

	public function work() {
		global $gearman_servers;

		$worker = new GearmanWorker();

		if ( empty( $gearman_servers ) ) {
			$worker->addServer();
		} else {
			$worker->addServers( implode( ',', $gearman_servers ) );
		}

		// We are working with the WP_Async_Task function
		$worker->addFunction( 'WP_Async_Task', array( 'WP_Async_Task_Runner', 'work' ) );

		// WORK!
		while( $worker->work() );
	}
}
