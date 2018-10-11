<?php

namespace WpMinions\SimpleQueueService;

use WpMinions\Client as BaseClient;
use Aws\Sqs\SqsClient as SqsClient;
use Aws\Exception\AwsException as AwsException;
use \RuntimeException as RuntimeException;

/**
 * Client for adding new tasks to an AWS SQS queue.
 */
class Client extends BaseClient {

	/**
	 * @var SqsClient The AWS SDK for PHP Client instance
	 */
	public $sqs_client;

	/**
	 * Creates a new SQS Client instance and configures the
	 * queue that it should connect to.
	 *
	 * @return bool True or false if successful
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
	 * Adds a Job to the SQS Queue.
	 *
	 * @param string $hook The action hook name for the job
	 * @param array $args Optional arguments for the job
	 * @param string $priority Optional priority of the job (ignored for SQS)
	 * @return bool true or false depending on the Client
	 */
	public function add( $hook, $args = array(), $priority = 'normal' ) {
		$job_data = array(
			'hook'    => $hook,
			'args'    => $args,
			'blog_id' => $this->get_blog_id(),
		);

		$client = $this->get_sqs_client();

		if ( $client !== false ) {
			$payload  = json_encode( $job_data );
			$result = $client->createQueue(
				array(
					'QueueName' => Connection::get_queue_name()
				)
			);

			$callable = array( $client, 'sendMessage' );

			return call_user_func( $callable, array(
				'QueueUrl'    => $result['QueueUrl'],
				'MessageBody' => $payload,
			) );
			
		} else {
			return false;
		}
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
	 * Caches and returns the current blog id for adding to the Job meta
	 * data. False if not a multisite install.
	 *
	 * @return int|false The current blog ids id.
	 */
	static function get_blog_id() {
		return function_exists( 'is_multisite' ) && is_multisite() ? get_current_blog_id() : false;
	}

}
