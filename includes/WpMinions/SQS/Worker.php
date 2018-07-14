<?php

namespace WpMinions\SQS;

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

	public function register() {
		// Do nothing
	}

	/**
	 * Process the job workload, delete them message if processed,
	 * release back to the pool if error.
	 *
	 * @return bool Processing result.
	 */
	public function work() {
		if ( ! $this->connect() ) {
			return false;
		}
		$switched = false;

		$message = $this->get_message();
		if ( empty( $message ) ) {
			return false;
		}

		try {
			$job_data = json_decode( $message['Body'], true );
			$hook     = $job_data['hook'];
			$args     = $job_data['args'];

			if ( function_exists( 'is_multisite' ) && is_multisite() && $job_data['blog_id'] ) {
				$blog_id = $job_data['blog_id'];

				if ( get_current_blog_id() !== $blog_id ) {
					switch_to_blog( $blog_id );
					$switched = true;
				} else {
					$switched = false;
				}
			} else {
				$switched = false;
			}

			do_action( 'wp_async_task_before_job', $hook, $message );
			do_action( 'wp_async_task_before_job_' . $hook, $message );

			do_action( $hook, $args, $message );

			do_action( 'wp_async_task_after_job', $hook, $message );
			do_action( 'wp_async_task_after_job_' . $hook, $message );

			//Delete message from the queue as we have procesessed.
			$this->connection->get_connection()->deleteMessage( array(
				'QueueUrl'      => $this->connection->get_channel(),
				'ReceiptHandle' => $message['ReceiptHandle']
			) );
			$result = true;
		} catch ( \Exception $e ) {
			error_log(
				'SQSWorker->do_job failed: ' . $e->getMessage()
			);
			//Make the message available back into the queue right away.
			$this->connection->get_connection()->changeMessageVisibility( array(
				'QueueUrl'          => $this->connection->get_channel(),
				'ReceiptHandle'     => $message['ReceiptHandle'],
				'VisibilityTimeout' => 0
			) );

			$result = false;
		}

		if ( $switched ) {
			restore_current_blog();
		}
		//Wait 3s to avoid too many requests to AWS.
		sleep( 3 );

		return $result;
	}

	/**
	 * Connect to Aws SQS queue.
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
	 * Get next message from the queue.
	 *
	 * @return \Aws\Result|bool|mixed
	 */
	private function get_message() {
		if ( empty( $this->connection ) ) {
			return false;
		}
		try {
			// Receive a message from the queue
			$message = $this->connection->get_connection()->receiveMessage( array(
				'QueueUrl' => $this->connection->get_channel()
			) );

			if ( $message['Messages'] == null ) {
				// No message to process
				return false;
			}
			// Get the message and return it
			$message = array_pop( $message['Messages'] );

			return $message;
		} catch ( \Exception $e ) {
			error_log(
				'SQSWorker->get_message failed: ' . $e->getMessage()
			);

			return false;
		}
	}
}
