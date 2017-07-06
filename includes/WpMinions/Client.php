<?php

namespace WpMinions;

/**
 * Base class for all WpMinions Clients. A Client is responsible for
 * adding jobs to it's Queue. It may optionally perform additional
 * initialization to setup it's initial state.
 */
abstract class Client {

	/**
	 * Any implementation specific initialization goes here.
	 *
	 * @return bool True or false based on whether the client was registered
	 */
	abstract function register();

	/**
	 * Adds the job to the Queue. Clients may receive multiple calls to
	 * add jobs to the Queue.
	 *
	 * @param string $hook The action hook name for the job
	 * @param array $args Optional arguments for the job
	 * @param string $priority Optional priority of the job
	 * @return bool true or false depending on the Client
	 */
	abstract function add( $action, $params = array(), $priority );

}
