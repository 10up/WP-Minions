<?php
/**
 * Plugin Name: WP Gears
 * Description: Provides methods for scheduling async tasks using Gearman
 * Version: 0.1
 * Author: Chris Marslender
 */

/*
 * $gearman_servers = array(
 *     '127.0.0.1:4730',
 * );
 *
 * todo should probably have a default fallback if gearman is not available for whatever reason
 */

/*
 * Users with setups where multiple installs share a common wp-config.php or $table_prefix can use this to segregate
 * jobs from each site
 */
if ( ! defined( 'WP_ASYNC_TASK_SALT' ) ) {
	define( 'WP_ASYNC_TASK_SALT', '' );
}

/**
 * Adds a single async task to gearman
 *
 * @since 0.1
 */
function wp_async_task_add( $hook, $args ) {
	global $wp_async_task;

	return $wp_async_task->add( $hook, $args );
}


function wp_async_task_init() {
	$async_task = new WP_Async_Task();
	$result = $async_task->init();

	if ( ! $result ) {
		// Fallback
		unset( $async_task );
		// todo fallback to non-gearman implementation!
	}

	$GLOBALS['wp_async_task'] = $async_task;
}

class WP_Async_Task {

	/**
	 * @var GearmanClient
	 */
	protected $_client;

	/**
	 * @var GearmanWorker
	 */
	protected $_worker;


	public function __construct() {
		return $this;
	}

	public function init() {
		// Only use gearman implementation when WP_GEARS is defined and true
		if ( ! defined( 'WP_GEARS' ) || ! WP_GEARS ) {
			return false;
		}
		global $gearman_servers;

		if ( ! class_exists( 'GearmanClient' ) || ! class_exists( 'GearmanWorker' ) ) {
			return false;
		}

		if ( defined( 'DOING_ASYNC' ) && DOING_ASYNC ) {
			$this->_worker = new GearmanWorker();

			if ( empty( $gearman_servers ) ) {
				return $this->_worker->addServer();
			} else {
				return $this->_worker->addServers( implode( ',', $gearman_servers ) );
			}
		} else {
			$this->_client = new GearmanClient();
			if ( empty( $gearman_servers ) ) {
				return $this->_client->addServer();
			} else {
				return $this->_client->addServers( implode( ',', $gearman_servers ) );
			}
		}
	}

	//todo may need a CPT to track jobs in the database - For now just storing in Gearman - Will be problematic if gearmand restarts!
	// ^^ Actually, should probably just hook gearmand up to redis, to track jobs there (Pretty sure this is possible)

	public function add( $hook, $args ) {
		$jobdata = array();
		$jobdata['hook'] = $hook;
		$jobdata['args'] = $args;
		$jobdata['blog_id'] = ( function_exists( 'is_multisite' ) && is_multisite() ) ? get_current_blog_id() : null;

		return $this->_client->doBackground( $this->gearman_function(), json_encode( $jobdata ) );
	}

	/**
	 * Returns the gearman function group for this install
	 *
	 * @return string
	 */
	public function gearman_function() {
		$key = '';

		if ( WP_ASYNC_TASK_SALT ) {
			$key .= WP_ASYNC_TASK_SALT . ':';
		}

		$key .= 'WP_Async_Task';

		return $key;
	}


	/* Task Runner */
	public function work() {
		$this->_worker->addFunction( $this->gearman_function(), array( $this, 'do_job' ) );

		while( $this->_worker->work() );
	}

	public function do_job( $job ) {
		$job_data = json_decode( $job->workload(), true );

		if ( function_exists( 'is_multisite' ) && is_multisite() && $job_data['blog_id'] ) {
			switch_to_blog( $job_data['blog_id'] );
		}

		do_action( $job_data['hook'], $job_data['args'] );

		return true;
	}
}

// Init
wp_async_task_init();
