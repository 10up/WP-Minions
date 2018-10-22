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

	const DELAY_BETWEEN_ITERATIONS = 5; /* seconds */

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
	 * @return bool False if the jobs could be executed, else never returns
	 */
	public function work() {
		
		$queue = false;

		if( false === $this->sqs_client ) {
			if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
				error_log( 'SQSWorker could not execute: sqs_client failed to initialize' );
			}

			return false;
		}

		$receiveMessageCallable = array( $this->sqs_client, 'receiveMessage' );

		if( $queue = $this->get_queue( $this->sqs_client ) ) {
			while( true ) {

				$payload = false;
				$receiptHandle = false;
		
				try {	
						$receiveMessageResult = call_user_func( $receiveMessageCallable, array(
							'QueueUrl'    => $queue['QueueUrl'],
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
				} catch ( \Exception $e ) {
					if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
						error_log( 'SQSWorker failed to get message: ' . $e->getMessage() );
					}
				}

				if( !empty( $payload ) ) {
					$this->process_payload( $payload );
				}
				else {
					// Sleep to let the server rest before checking the queue again.
					sleep( self::DELAY_BETWEEN_ITERATIONS );
				}

				if( !empty( $receiptHandle ) ) {
					$this->delete_message( $queue['QueueUrl'], $receiptHandle );
				}

			}
		}

		return false;
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

	/**
	 * Get the information to connect to an SQS queue
	 * @param Aws\Sqs\SqsClient $sqs_client
	 * @return Aws\Result queue information, URL in [QueueUrl] value
	 */
	function get_queue( $sqs_client ) {
		$queue = false;

		try {
			$queue = $sqs_client->createQueue(
				array(
					'QueueName' => Connection::get_queue_name()
				)
			);
		} catch ( \Exception $e ) {
			if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
				error_log( 'SQSWorker failed to create queue: ' . $e->getMessage() );
			}
		}

		return $queue;
	}

	/**
	 * Process the payload from an SQS queue
	 * 
	 * @param stdClass $payload
	 * @return void
	 */
	function process_payload( $payload ) {
		$hook = isset( $payload->hook ) ? $payload->hook : '';
		$args = isset( $payload->args ) ? (array) $payload->args : array();
		
		$switched = false;

		if ( function_exists( 'is_multisite' ) && is_multisite() && !empty( $payload->blog_id ) ) {
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

	/**
	 * Delete a message from an SQS queue
	 * 
	 * @param string $queueURL Queue URL
	 * @param string $receiptHandle Unique message receipt handle
	 * @return Aws\Result|false
	 */
	function delete_message( $queueURL, $receiptHandle ) {
		$deleteMessageCallable = array( $this->sqs_client, 'deleteMessage' );
		$deleteMessageResult = false;

		try {		
			$deleteMessageResult = call_user_func( $deleteMessageCallable, array(
				'QueueUrl'    => $queueURL,
				'ReceiptHandle' => $receiptHandle,
			) );

		}
		catch ( \Exception $e ) {
			if ( ! defined( 'PHPUNIT_RUNNER' ) ) {
				error_log( 'SQSWorker failed to delete message: ' . $e->getMessage() );
			}
		}

		return $deleteMessageResult;
	}

}
