<?php

namespace WpMinions\SQS;

use WpMinions\Client as BaseClient;


/**
 * The SQS Client uses the Aws API to add jobs to the Amazon SQS.
 */
class Client extends BaseClient {

	/**
	 * Instance of Connection class.
	 *
	 * @var Connection
	 */
	public $connection = null;

	/**
	 * Nothing here.
	 */
	public function register() {
		// Do nothing
	}

	/**
	 * Adds a Job to the SQS Client's Queue.
	 *
	 * @param string $hook The action hook name for the job
	 * @param array $args Optional arguments for the job
	 * @param string $priority Optional priority of the job
	 *
	 * @return bool true or false depending on the Client
	 */
	public function add( $hook, $args = array(), $priority = 'normal' ) {
		if ( ! $this->connect() ) {
			return false;
		}
		$group_id   = null;
		$message_id = null;
		if ( isset( $args['sqs_extra'] ) && ! empty( $args['sqs_extra'] ) ) {
			if ( isset( $args['sqs_extra']['group_id'] ) ) {
				$group_id = $args['sqs_extra']['group_id'];
			}
			if ( isset( $args['sqs_extra']['id'] ) ) {
				$message_id = $args['sqs_extra']['id'];
			}
			unset( $args['sqs_extra'] );
		}
		$job_data = array(
			'hook'    => $hook,
			'args'    => $args,
			'blog_id' => get_current_blog_id(),
		);
		try {
			$job_args = array(
				'QueueUrl'    => $this->connection->get_channel(),
				'MessageBody' => json_encode( $job_data ),
			);
			if ( ! empty( $group_id ) ) {
				$job_args['MessageGroupId'] = $group_id;
			}
			if ( ! empty( $message_id ) ) {
				$job_args['MessageDeduplicationId'] = $message_id;
			}
			$this->connection->get_connection()->sendMessage( $job_args );

			return true;
		} catch ( \Exception $e ) {
			error_log(
				'SQSClient->add failed: ' . $e->getMessage()
			);

			return false;
		}
	}

	/**
	 * Connect to host and channel.
	 */
	private function connect() {
		if ( null !== $this->connection ) {
			return $this->connection;
		}

		try {
			$this->connection = new Connection();
		} catch ( \Exception $e ) {
			error_log(
				'SQSClient->connect failed: ' . $e->getMessage()
			);

			return false;
		}

		return $this->connection;
	}
}
