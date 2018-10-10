<?php

namespace WpMinions\SimpleQueueService;

use WpMinions\Worker as BaseWorker;
use Aws\Sqs\SqsClient;
use Aws\Result;

/**
 *
 */
class Worker extends BaseWorker {

	/**
	 * @var SqsClient The AWS SDK for PHP Client instance
	 */
	public $sqs_client;

	/**
	 * Creates a SQS Worker and initializes the servers it should
	 * connect to. The callback that will execute a job's hook is also setup here.
	 *
	 * @return bool True if operation was successful else false.
	 */
	public function register() {
		try {
			$client = $this->get_sqs_client();
		}
		catch (Exception $e) {
			error_log( "Fatal SQS Error: Failed to connect" );
			error_log( "  Cause: " . $e->getMessage() );
		}

		return $client !== false;
	}

	/**
	 * Pulls a job from the SQS Queue and tries to execute it.
	 * Errors are logged if the Job failed to execute.
	 *
	 * @return bool True if the job could be executed, else false
	 */
	public function work() {
		$payload = false;
		$receiptHandle = false;
		$client = $this->get_sqs_client();

		try {
			if ( $client !== false ) {
				$createQueueResult = $client->createQueue(
					array(
						'QueueName' => $this->get_queue_name()
					)
				);
	
				$callable = array( $client, 'receiveMessage' );
	
				$receiveMessageResult = call_user_func( $callable, array(
					'QueueUrl'    => $createQueueResult['QueueUrl'],
					'MaxNumberOfMessages' => 1,
				) );

				if( $receiveMessageResult instanceof \Aws\Result ) {
					$messages = $receiveMessageResult->get('Messages');
					if( isset( $messages[0]['Body'] ) ) {
						$payload = $messages[0]['Body'];
					}
					if( isset( $messages[0]['ReceiptHandle'] ) ) {
						$receiptHandle = $messages[0]['ReceiptHandle'];
					}
				}
				
			}

		} catch ( \Exception $e ) {
			if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
				error_log( 'SQSWorker failed to get message: ' . $e->getMessage() );
			}
		}

		do_action( 'wp_async_task_after_work', $payload, $this );

		if( !empty( $payload ) && !empty( $receiptHandle ) ) {

			try {			
				$callable = array( $client, 'deleteMessage' );
	
				$deleteMessageResult = call_user_func( $callable, array(
					'QueueUrl'    => $createQueueResult['QueueUrl'],
					'ReceiptHandle' => $receiptHandle,
				) );

			}
			catch ( \Exception $e ) {
				if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
					error_log( 'SQSWorker failed to delete message: ' . $e->getMessage() );
				}
			}
		}

		return $payload !== false;
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

	/**
	 * Retrieves the region for this queue.
	 * Looks in the AWS_DEFAULT_REGION environment variable.
	 * Defaults to a hard-coded value.
	 * @return string region name
	 */
	static function get_region_name() {
		if( isset( $_ENV['AWS_DEFAULT_REGION '] ) ) {
			return $_ENV['AWS_DEFAULT_REGION '];
		}
		else {
			return 'us-east-1';
		}
	}

	/**
	 * Retrieves the profile name for this queue.
	 * Looks in the AWS_PROFILE environment variable.
	 * Defaults to 'default'.
	 * @return string profile name
	 */
	static function get_profile_name() {
		if( isset( $_ENV['AWS_PROFILE'] ) ) {
			return $_ENV['AWS_PROFILE'];
		}
		else {
			return 'default';
		}
	}

	/**
	 * The Function Group used to split libGearman functions on a
	 * multi-network install.
	 *
	 * @return string The prefixed group name
	 */
	function get_queue_name() {
		$key = '';

		if ( defined( 'WP_ASYNC_TASK_SALT' ) ) {
			$key .= WP_ASYNC_TASK_SALT . '-';
		}

		$key .= 'WP_Async_Task';

		return $key;
	}

	/**
	 * Builds the SQS Client Instance if the extension is
	 * installed. Once created returns the previous instance without
	 * reinitialization.
	 *
	 * @return Aws\Sqs\SqsClient|false An instance of SqsClient
	 */
	function get_sqs_client() {
		if ( is_null( $this->sqs_client ) ) {
			if ( class_exists( 'Aws\Sqs\SqsClient' ) ) {
				$this->sqs_client = SqsClient::factory(array(
					'version' => '2012-11-05',
					'profile' => self::get_profile_name(),
					'region'  => self::get_region_name(),
				));
			} else {
				$this->sqs_client = false;
				throw new RuntimeException('AWS SDK not loaded');
			}
		}

		return $this->sqs_client;
	}

}
