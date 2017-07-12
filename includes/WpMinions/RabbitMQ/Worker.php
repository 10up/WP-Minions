<?php

namespace WpMinions\RabbitMQ;

use WpMinions\Worker as BaseWorker;

/**
 * The Gearman Worker uses the libGearman API to execute Jobs in the
 * Gearman queue. The servers that the Worker should connect to are
 * configured as part of the initialization.
 *
 */
class Worker extends BaseWorker {
	/**
	 * Instance of Connection class
	 *
	 * @var Connection
	 */
	public $connection = null;

	/**
	 * Connect to host and channel
	 */
	private function connect() {
		if ( null !== $this->connection ) {
			return $this->connection;
		}

		$this->connection = new Connection();

		return $this->connection;
	}

	public function register() {
		// Do nothing
	}

	public function work() {
		if ( ! $this->connect() ) {
			return false;
		}

		$channel->basic_consume( 'wordpress', '', false, true, false, false, function( $message ) {
			do_action( $message['hook'], $message['args'] );
		} );

		while(count($channel->callbacks)) {
			$channel->wait();
		}
	}
}
