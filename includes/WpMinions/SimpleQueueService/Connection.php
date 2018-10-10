<?php

namespace WpMinions\SimpleQueueService;

use Aws\Sqs\SqsClient;

class Connection {

	/**
	 * Establish a connection to AWS SQS
	 * @throws RuntimeException if AWS PHP SDK isn't loaded
	 */
	public static function connect() {
		if ( class_exists( 'Aws\Sqs\SqsClient' ) ) {
			return SqsClient::factory(array(
				'version' => '2012-11-05',
				'profile' => self::get_profile_name(),
				'region'  => self::get_region_name(),
			));
		} else {
			throw new RuntimeException('AWS SDK not loaded');
		}
	}

	/**
	 * The Function Group used to split libGearman functions on a
	 * multi-network install.
	 *
	 * @return string The prefixed group name
	 */
	public static function get_queue_name() {
		$key = '';

		if ( defined( 'WP_ASYNC_TASK_SALT' ) ) {
			$key .= WP_ASYNC_TASK_SALT . '-';
		}

		$key .= 'WP_Async_Task';

		return $key;
	}

	/**
	 * Retrieves the AWS region for this queue.
	 * Looks in the AWS_DEFAULT_REGION environment variable.
	 * Defaults to a hard-coded value.
	 * @param string $default default value
	 * @return string region name
	 */
	public static function get_region_name( $default = 'us-east-1' ) {
		if( isset( $_ENV['AWS_DEFAULT_REGION '] ) ) {
			return $_ENV['AWS_DEFAULT_REGION '];
		}
		else {
			return $default;
		}
	}

	/**
	 * Retrieves the profile name for this queue.
	 * Looks in the AWS_PROFILE environment variable.
	 * Defaults to 'default'.
	 * @param string $default default value
	 * @return string profile name
	 */
	public static function get_profile_name( $default = 'default' ) {
		if( isset( $_ENV['AWS_PROFILE'] ) ) {
			return $_ENV['AWS_PROFILE'];
		}
		else {
			return $default;
		}
	}

}