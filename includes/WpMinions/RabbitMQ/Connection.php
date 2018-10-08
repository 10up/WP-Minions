<?php

namespace WpMinions\RabbitMQ;


class Connection {

	private $connection;
	private $channel;

	/**
	 * Init and test connection
	 */
	public function __construct() {
		global $rabbitmq_server;

		if ( class_exists( '\PhpAmqpLib\Connection\AMQPStreamConnection' ) ) {
			if ( empty( $rabbitmq_server ) ) {
				$rabbitmq_server = array();
			}

			$rabbitmq_server = wp_parse_args( $rabbitmq_server, array(
				'host'     => 'localhost',
				'port'     => 5672,
				'username' => 'guest',
				'password' => 'guest',
			) );

			$this->connection = new \PhpAmqpLib\Connection\AMQPStreamConnection( $rabbitmq_server['host'], $rabbitmq_server['port'], $rabbitmq_server['username'], $rabbitmq_server['password'] );
			$this->channel = $this->connection->channel();

			/**
			 * RabbitMQ Queue Declare Filter
			 *
			 * @param bool bit passive
			 * @param bool bit durable
			 * @param bool bit exclusive
			 * @param bool bit auto-delete
			 */
			$rabbitmq_bit_filter = apply_filters( 'wp_minion_rabbitmq_declare_filter', false, true, false, false);

			$this->channel->queue_declare( 'wordpress', $rabbitmq_bit_filter );

			add_action( 'shutdown', array( $this, 'shutdown' ) );
		} else {
			throw new \Exception( 'Could not create connection.' );
		}
	}

	/**
	 * Return connection channel
	 *
	 * @return \PhpAmqpLib\Channel\AMQPChannel
	 */
	public function get_channel() {
		return $this->channel;
	}

	/**
	 * Close connection and channel if they are created
	 */
	public function shutdown() {
		if ( empty( $this->connection ) || empty( $this->channel ) ) {
			return;
		}

		$this->channel->close();
		$this->connection->close();
	}
}
