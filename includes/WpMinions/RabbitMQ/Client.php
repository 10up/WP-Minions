<?php

namespace WpMinions\RabbitMQ;

use WpMinions\Client as BaseClient;


/**
 * The Gearman Client uses the libGearman API to add jobs to the Gearman
 * Queue. The servers that the client should connect to are setup as
 * part of the initialization.
 */
class Client extends BaseClient {

	/**
	 * Instance of Connection class
	 *
	 * @var Connection
	 */
	public $connection = null;

	/**
	 * Setup backend
	 */
	public function register() {
		// Do nothing
	}

	/**
	 * Connect to host and channel
	 */
	private function connect() {
		if ( null !== $this->connection ) {
			return $this->connection;
		}

		try {
			$this->connection = new Connection();
		} catch ( \Exception $e ) {
			return false;
		}

		return $this->connection;
	}

	/**
	 * Adds a Job to the libGearman Client's Queue.
	 *
	 * @param string $hook The action hook name for the job
	 * @param array $args Optional arguments for the job
	 * @param string $priority Optional priority of the job
	 * @return bool true or false depending on the Client
	 */
	public function add( $hook, $args = array(), $priority = 'normal' ) {
		if ( ! $this->connect() ) {
			return false;
		}

		$job_data = array(
			'hook'    => $hook,
			'args'    => $args,
			'blog_id' => get_current_blog_id(),
		);

		$message = new \PhpAmqpLib\Message\AMQPMessage(
			json_encode( $job_data ) );

		$this->connection->get_channel()->basic_publish( $message, '', 'wordpress' );
	}
}
