<?php

namespace WpMinions\SimpleQueueService;

use WpMinions\Client as BaseClient;
use Aws\Sqs\SqsClient;

/**
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
		$client = $this->get_sqs_client();

		if ( ! $client  ) {
			return false;
		}
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
			$queueURL = $client->createQueue(
				array(
					'QueueName' => $this->get_queue_name()
				)
			);
			$callable = array( $client, 'sendMessage' );

			return call_user_func( $callable, array(
				'QueueUrl'    => $queueURL,
				'MessageBody' => $payload,
			) );
		} else {
			return false;
		}
	}

	/* Helpers */

	/**
	 * The Function Group used to split libGearman functions on a
	 * multi-network install.
	 *
	 * @return string The prefixed group name
	 */
	function get_queue_name() {
		$key = '';

		if ( defined( 'WP_ASYNC_TASK_SALT' ) ) {
			$key .= WP_ASYNC_TASK_SALT . ':';
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
			if ( class_exists( 'SqsClient' ) ) {
				$this->sqs_client = SqsClient::factory(array(
					'profile' => self::get_profile_name(),
					'region'  => self::get_region_name()
				));
			} else {
				$this->sqs_client = false;
			}
		}

		return $this->sqs_client;
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
	 * Caches and returns the current blog id for adding to the Job meta
	 * data. False if not a multisite install.
	 *
	 * @return int|false The current blog ids id.
	 */
	static function get_blog_id() {
		return function_exists( 'is_multisite' ) && is_multisite() ? get_current_blog_id() : false;
	}

}
