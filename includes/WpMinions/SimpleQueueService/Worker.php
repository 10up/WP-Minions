<?php

namespace WpMinions\SimpleQueueService;

use WpMinions\Worker as BaseWorker;
use Aws\Sqs\SqsClient;

/**
 *
 */
class Worker extends BaseWorker {

	/**
	 * Creates a SQS Worker and initializes the servers it should
	 * connect to. The callback that will execute a job's hook is also setup here.
	 *
	 * @return bool True if operation was successful else false.
	 */
	public function register() {
		$client = $this->get_sqs_client();

		if ( ! $client  ) {
			return false;
		}
	}

	/**
	 * Pulls a job from the SQS Queue and tries to execute it.
	 * Errors are logged if the Job failed to execute.
	 *
	 * @return bool True if the job could be executed, else false
	 */
	public function work() {
		$client = $this->get_sqs_client();

		try {
			$result = $worker->work();
		} catch ( \Exception $e ) {
			if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
				error_log( 'SQSWorker->work failed: ' . $e->getMessage() );
			}
			$result = false;
		}

		do_action( 'wp_async_task_after_work', $result, $this );

		return $result;
	}

	/* Helpers */
	/**
	 * Executes a Job pulled from SQS. On a multisite instance
	 * it switches to the target site before executing the job. And the
	 * site is restored once executing is finished.
	 *
	 * The job data contains,
	 *
	 * 1. hook - The name of the target hook to execute
	 * 2. args - Optional arguments to pass to the target hook
	 * 3. blog_id - Optional blog on a multisite to switch to, before execution
	 *
	 * Actions are fired before and after execution of the target hook.
	 *
	 * Eg:- for the action 'foo' The order of execution of actions is,
	 *
	 * 1. wp_async_task_before_job
	 * 2. wp_async_task_before_job_foo
	 * 3. foo
	 * 4. wp_async_task_after_job
	 * 5. wp_async_task_after_job_foo
	 *
	 * @param array $job The job object data.
	 * @return bool True or false based on the status of execution
	 */
	function do_job( $job ) {
		$switched = false;

		try {
			$job_data = json_decode( $job->workload(), true );
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

			do_action( 'wp_async_task_before_job', $hook, $job );
			do_action( 'wp_async_task_before_job_' . $hook, $job );

			do_action( $hook, $args, $job );

			do_action( 'wp_async_task_after_job', $hook, $job );
			do_action( 'wp_async_task_after_job_' . $hook, $job );

			$result = true;
		} catch ( \Exception $e ) {
			error_log(
				'SQSWorker->do_job failed: ' . $e->getMessage()
			);
			$result = false;
		}

		if ( $switched ) {
			restore_current_blog();
		}

		return $result;
	}

	/**
	 * The Function Group used to split libSQS functions on a
	 * multi-network install.
	 *
	 * @return string The prefixed group name
	 */
	function get_async_group() {
		$key = '';

		if ( defined( 'WP_ASYNC_TASK_SALT' ) ) {
			$key .= WP_ASYNC_TASK_SALT . ':';
		}

		$key .= 'WP_Async_Task';

		return $key;
	}

}
