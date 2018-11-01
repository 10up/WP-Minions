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
				'vhost'    => '/',
			) );

			$this->connection = new \PhpAmqpLib\Connection\AMQPStreamConnection( $rabbitmq_server['host'], $rabbitmq_server['port'], $rabbitmq_server['username'], $rabbitmq_server['password'], $rabbitmq_server['vhost'] );
			$this->channel    = $this->connection->channel();

			$rabbitmq_declare_passive_filter    = apply_filters( 'wp_minion_rabbitmq_declare_passive_filter', false );
			$rabbitmq_declare_durable_filter    = apply_filters( 'wp_minion_rabbitmq_declare_durable_filter', true );
			$rabbitmq_declare_exclusive_filter  = apply_filters( 'wp_minion_rabbitmq_declare_exclusive_filter', false );
			$rabbitmq_declare_autodelete_filter = apply_filters( 'wp_minion_rabbitmq_declare_autodelete_filter', false );

			$this->channel->queue_declare( 'wordpress', $rabbitmq_declare_passive_filter, $rabbitmq_declare_durable_filter, $rabbitmq_declare_exclusive_filter, $rabbitmq_declare_autodelete_filter );

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
