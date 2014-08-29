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
}

function wp_async_task_switch_to_blog( $blog_id ) {
	global $wp_async_task;

	return $wp_async_task->switch_to_blog( $blog_id );
}

class WP_Async_Task {

	//todo may need a CPT to track jobs in the database

	public function add( $hook, $args ) {
		global $gearman_servers;

		$job_id = 0; // todo this should be a post ID that corresponds to the CPT for the job
		// todo need to store $hook, $args with the post - OR else pass all of this information to the doBackground payload (second param, serialized(can I use JSON?) data)

		// now queue up the job
		if( ! $job_id ) {
			return false;
		}

		$client = new GearmanClient();
		$client->addServers( implode( ',', $gearman_servers ) );
		return $client->doBackground( 'WP_Async_Task', $job_id ); // todo Need blog_id, possibly salt value (for multiple single-sites), and post_id
	}

	public function switch_to_blog( $blog_id ) {
		// todo switch_to_blog
	}
}
