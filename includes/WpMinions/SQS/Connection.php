<?php

namespace WpMinions\SQS;

/**
 * Handles connection to SQS Queue.
 */
class Connection {
	/**
	 * @var string AWS queue name.
	 */
	private $queue_name;
	/**
	 * @var array AWS credentials.
	 */
	private $aws_credentials;

	/**
	 * @var \Aws\Sqs\SqsClient Connection client.
	 */
	private $connection;

	/**
	 * Init connection to SQS queue.
	 */
	public function __construct() {
		global $aws_credentials;

		if ( class_exists( '\Aws\Sqs\SqsClient' ) ) {
			if ( empty( $aws_credentials ) ) {
				$aws_credentials = array();
			}

			$this->queue_name      = 'fleet';
			$this->aws_credentials = $aws_credentials;
			$this->connection      = new \Aws\Sqs\SqsClient( $this->aws_credentials );

		} else {
			throw new \Exception( 'Could not create connection.' );
		}
	}

	/**
	 * Return queue url.
	 *
	 * @return mixed|null Queue url.
	 */
	public function get_channel() {
		return $this->connection->getQueueUrl( array( 'QueueName' => $this->queue_name ) )->get( 'QueueUrl' );
	}

	/**
	 * Return connection client.
	 *
	 * @return \Aws\Sqs\SqsClient Client.
	 */
	public function get_connection() {
		return $this->connection;
	}

}
