<?php

namespace WpGears;

/**
 * The main WpGears Plugin object. It creates a client and worker object
 * based the current configuration.
 *
 * When run in Client mode it will allow adding new jobs to the Queue.
 *
 * When run in worker mode it will execute jobs on the Queue. Worker
 * mode also allows adding new jobs to the queue just like Mlient mode.
 */
class Plugin {

	/**
	 * @var Plugin Single instance of the plugin
	 */
	static public $instance;

	/**
	 * Returns the singleton instance of the Plugin. Creates the
	 * instance if it is absent.
	 *
	 * @return Plugin instance of Plugin
	 */
	static public function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new Plugin();
		}

		return self::$instance;
	}

	/**
	 * @var \WpGears\Client The Client object used to enqueue jobs
	 */
	public $client;

	/**
	 * @var \WpGears\Worker The Worker object used to execute jobs
	 */
	public $worker;

	/**
	 * @var string Configuration constants are prefixed by WP_GEARS by default.
	 * ;w
	 * Eg:- WP_GEARS_JOBS_PER_WORKER
	 */
	public $config_prefix = 'WP_GEARS';

	/**
	 * @var int Number of jobs to execute per worker, Default 1
	 */
	public $jobs_per_worker;

	/** @var string Custom worker class */
	public $worker_class;

	/** @var Custom client class */
	public $client_class;

	/*
	 * @var bool Indicates if the plugin executed a job.
	 *
	 * Only one run is allowed.
	 */
	public $did_run    = false;

	/*
	 * @var bool Indicates if the plugin was enabled. The Plugin can only be
	 * enabled once
	 */
	public $did_enable = false;

	/**
	 * Enables the plugin by registering it's clients and workers.
	 * Ignored if already enabled
	 */
	public function enable() {
		if ( ! $this->did_enable ) {
			$this->get_client()->register();
			$this->get_worker()->register();

			$this->did_enable = true;
		}
	}

	/**
	 * Starts processing jobs in the Worker.
	 *
	 * Only one run is permitted.
	 *
	 * @return int The exit status code (only for PHPUnit)
	 */
	public function run() {
		if ( $this->did_run ) {
			return false;
		}

		$this->did_run = true;

		return $this->work();
	}

	/**
	 * Executes jobs on the current Worker. A Worker will taken up
	 * only one job by default. If WP_GEARS_JOBS_PER_WORKER is defined
	 * that many jobs will be executed before it exits.
	 *
	 * This method will exit with the result code based on
	 * success/failure of executing the job.
	 *
	 * @return int 0 for success and 1 for failure
	 */
	public function work() {
		for ( $i = 0; $i < $this->get_jobs_per_worker(); $i++ ) {
			$result      = $this->get_worker()->work();
			$result_code = $result ? 0 : 1;
		}

		return $this->quit( $result_code );
	}

	/**
	 * Adds a new job to the Client with the specified arguments and
	 * priority.
	 *
	 * @param string $hook The action hook name for the job
	 * @param array $args Optional arguments for the job
	 * @param string $priority Optional priority of the job
	 * @return bool true or false depending on the Client
	 */
	public function add( $hook, $args = array(), $priority = 'normal' ) {
		return $this->get_client()->add(
			$hook, $args, $priority
		);
	}

	/* Helpers */
	/**
	 * Returns the Client object used to add jobs. Creates the instance
	 * of the client lazily.
	 *
	 * @return \WpGears\Client The client instance
	 */
	function get_client() {
		if ( is_null( $this->client ) ) {
			$this->client = $this->build_client();
		}

		return $this->client;
	}

	/**
	 * Returns the Worker object used to execute jobs. Creates the instance
	 * of the worker lazily.
	 *
	 * @param \WpGears\Worker The worker instance
	 */
	function get_worker() {
		if ( is_null( $this->worker ) ) {
			$this->worker = $this->build_worker();
		}

		return $this->worker;
	}

	/**
	 * Conditionally builds a new Client object. If the constant
	 * WP_GEARS_CLIENT_CLASS is defined it will return an instance of that
	 * class.
	 *
	 * By default it will detect if Gearman is present and return the
	 * Gearman Client else fallback to the Cron Client.
	 *
	 * @return \WpGears\Client New instance of the Client
	 */
	function build_client() {
		if ( ! $this->has_config( 'CLIENT_CLASS' ) ) {
			if ( class_exists( '\GearmanClient' ) ) {
				return new \WpGears\Gearman\Client();
			} else {
				return new \WpGears\Cron\Client();
			}
		} else {
			$klass = $this->get_config( 'CLIENT_CLASS' );
			return new $klass();
		}
	}

	/**
	 * Conditionally builds a new Worker object. If the constant
	 * WP_GEARS_WORKER_CLASS is defined it will return an instance of
	 * that class.
	 *
	 * By default it will detect if Gearman is present and return the
	 * Gearman Worker else fallback to the Cron Worker.
	 *
	 * @return \WpGears\Worker New instance of the Worker
	 */
	function build_worker() {
		if ( ! $this->has_config( 'WORKER_CLASS' ) ) {
			if ( class_exists( '\GearmanWorker' ) ) {
				return new \WpGears\Gearman\Worker();
			} else {
				return new \WpGears\Cron\Worker();
			}
		} else {
			$klass = $this->get_config( 'WORKER_CLASS' );
			return new $klass();
		}
	}

	/**
	 * Returns the jobs to execute per worker instance
	 *
	 * @return int Defaults to 1
	 */
	function get_jobs_per_worker() {
		return $this->get_config(
			'JOBS_PER_WORKER', 1
		);
	}

	/**
	 * Helper to pickup config options from Constants with fallbacks.
	 *
	 * Order of lookup is,
	 *
	 * 1. Local Property
	 * 2. Constant of that name
	 * 3. Default specified
	 *
	 * Eg:- get_config( 'FOO', 'abc', 'MY' )
	 *
	 * will look for,
	 *
	 * 1. Local Property $this->my_foo
	 * 2. Constant MY_FOO
	 * 3. Defaualt ie:- abc
	 *
	 * @param string $constant Name of constant to lookup
	 * @param string $default Optional default
	 * @param string $config_prefix Optional config prefix, Default is WP_GEARS
	 * @return mixed The value of the config
	 */
	function get_config( $constant, $default = '', $config_prefix = '' ) {
		$variable      = strtolower( $constant );
		$config_prefix = empty( $config_prefix ) ? $this->config_prefix : $config_prefix;
		$constant      = $config_prefix . '_' . $constant;

		if ( property_exists( $this, $variable ) ) {
			if ( is_null( $this->$variable ) ) {
				if ( defined( $constant ) ) {
					$this->$variable = constant( $constant );
				} else {
					$this->$variable = $default;
				}
			}

			return $this->$variable;
		} else {
			throw new \Exception(
				"Fatal Error - Public Var($variable) not declared"
			);
		}
	}

	/**
	 * Checks if a config option is defined. Empty strings are treated
	 * as an undefined config option.
	 *
	 * @param string $constant The name of the constant
	 * @return bool True or false depending on whether the config is present
	 */
	function has_config( $constant ) {
		return ! empty( $this->get_config( $constant ) );
	}

	/**
	 * Helper to quit with a status code. When running under PHPUnit it
	 * returns the status_code instead of exiting immediately.
	 *
	 * @param int $status_code The status between 0-255 to quit with.
	 */
	function quit( $status_code ) {
		if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
			exit( $status_code );
		} else {
			return $status_code;
		}
	}

	/**
	 * Returns the path to the wp-load.php file.
	 *
	 * @return string Path to the wp-load.php file
	 */
	function get_wp_load() {
		$wp_dir  = dirname( $_SERVER['SCRIPT_FILENAME'] ) ;
		return $wp_dir . '/wp-load.php';
	}

	/**
	 * Tries to load WordPress using wp-load.php. If loading fails an
	 * error message is logged and the Script will exit immediately with
	 * a status code of 1.
	 */
	function load_wordpress() {
		$wp_load = $this->get_wp_load();

		if ( file_exists( $wp_load ) ) {
			require_once( $wp_load );
		} else {
			error_log(
				"WP Gears Fatal Error - Cannot find wp-load.php( $wp_load )"
			);

			return $this->quit( 1 );
		}
	}

}
