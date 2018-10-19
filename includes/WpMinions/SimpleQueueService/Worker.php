<?php

namespace WpMinions\SimpleQueueService;

use WpMinions\Worker as BaseWorker;
use Aws\Result;

/**
 * Async task Worker implementation for AWS SQS.
 */
class Worker extends BaseWorker {

	/**
	 * @var SqsClient The AWS SDK for PHP Client instance
	 */
	public $sqs_client;

	/**
	 * Creates a SQS Worker and connects to AWS SQS.
	 * The callback that will execute a job's hook is also setup here.
	 *
	 * @return bool True if operation was successful else false.
	 */
	public function register() {
		try {
			$this->sqs_client = $this->get_sqs_client();
		}
		catch (Exception $e) {
			error_log( "Fatal SQS Error: Failed to connect" );
			error_log( "  Cause: " . $e->getMessage() );
		}

		return $this->sqs_client !== false;
	}

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
	 * @return bool True if the job could be executed, else false
	 */
	public function work() {
		
		$payload = false;
		$receiptHandle = false;
		$switched = false;

		try {
			if ( $this->sqs_client !== false ) {
				$createQueueResult = $this->sqs_client->createQueue(
					array(
						'QueueName' => Connection::get_queue_name()
					)
				);
	
				$callable = array( $this->sqs_client, 'receiveMessage' );
	
				$receiveMessageResult = call_user_func( $callable, array(
					'QueueUrl'    => $createQueueResult['QueueUrl'],
					'MaxNumberOfMessages' => 1,
				) );

				if( $receiveMessageResult instanceof \Aws\Result ) {
					$messages = $receiveMessageResult->get('Messages');
					if( isset( $messages[0]['Body'] ) ) {
						$payload = json_decode( $messages[0]['Body'] );
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

		if( !empty( $payload ) ) {
			$hook = isset( $payload->hook ) ? $payload->hook : '';
			$args = isset( $payload->args ) ? (array) $payload->args : array();

			if ( function_exists( 'is_multisite' ) && is_multisite() && $job_data['blog_id'] ) {
				$blog_id = $payload->blog_id;

				if ( get_current_blog_id() !== $blog_id ) {
					switch_to_blog( $blog_id );
					$switched = true;
				}
			}

			if( !empty( $hook ) ) {
				do_action( $hook, $args );
			}

			do_action( 'wp_async_task_after_work', $payload, $this );

			if ( $switched ) {
				restore_current_blog();
			}

		}

		if( !empty( $payload ) && !empty( $receiptHandle ) ) {

			try {
				$callable = array( $this->sqs_client, 'deleteMessage' );
	
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
	 * Builds the SQS Client Instance if the extension is
	 * installed. Once created returns the previous instance without
	 * reinitialization.
	 *
	 * @return Aws\Sqs\SqsClient|false An instance of SqsClient
	 */
	function get_sqs_client() {
		if ( is_null( $this->sqs_client ) ) {
			$this->sqs_client = Connection::connect();
		}

		return $this->sqs_client;
	}

}
