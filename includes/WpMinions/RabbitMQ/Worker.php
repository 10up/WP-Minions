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

		try {
			$this->connection = new Connection();
		} catch ( \Exception $e ) {
			return false;
		}

		return $this->connection;
	}

	public function register() {
		// Do nothing
	}

	public function work() {
		if ( ! $this->connect() ) {
			return false;
		}

		$this->connection->get_channel()->basic_consume( 'wordpress', '', false, true, false, false, function( $message ) {
			try {
				$job_data = json_decode( $message->body, true );
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

				$result = true;
			} catch ( \Exception $e ) {
				error_log(
					'RabbitMQWorker->do_job failed: ' . $e->getMessage()
				);
				$result = false;
			}

			if ( $switched ) {
				restore_current_blog();
			}
		} );

		while ( count( $this->connection->get_channel()->callbacks ) ) {
			$this->connection->get_channel()->wait();
		}
	}
}
