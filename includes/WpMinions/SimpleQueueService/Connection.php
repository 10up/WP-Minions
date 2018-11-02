<?php

namespace WpMinions\SimpleQueueService;

use Aws\Sqs\SqsClient;

/**
 * Utility methods for connecting to AWS SQS and configuring the queue.
 */
class Connection {

	/**
	 * Establish a connection to AWS SQS.
	 * 
	 * @throws RuntimeException if AWS PHP SDK isn't loaded
	 */
	public static function connect() {
		global $awssqs_server;

		if ( class_exists( 'Aws\Sqs\SqsClient' ) ) {
			$clientConfig = array(
				'version' => '2012-11-05',
				'region'  => self::get_region_name(),
			);

			if( !empty( $awssqs_server ) ) {
				if( isset( $awssqs_server['access_key'] ) && isset( $awssqs_server['secret'] ) ) {
					$clientConfig['credentials'] = array(
						'key'    => $awssqs_server['access_key'],
						'secret' => $awssqs_server['secret'],
					);
				}
			}
			else {
				$clientConfig['profile'] = self::get_profile_name();
			}

			return SqsClient::factory( $clientConfig );
		} else {
			throw new RuntimeException('AWS SDK not loaded');
		}
	}

	/**
	 * Builds a queue name for the async tasks.
	 *
	 * @param string $baseName The unprefixed queue name
	 * @return string Queue name, possibly prefixed
	 */
	public static function get_queue_name( $baseName = 'WP_Async_Task' ) {
		$key = '';

		if ( defined( 'WP_ASYNC_TASK_SALT' ) ) {
			$key .= WP_ASYNC_TASK_SALT . '-';
		}

		$key .= $baseName;

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
		global $awssqs_server;

		if( isset( $awssqs_server['region'] ) ) {
			return $awssqs_server['region'];
		}
		else if( isset( $_ENV['AWS_DEFAULT_REGION '] ) ) {
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
		global $awssqs_server;

		if( isset( $awssqs_server['profile'] ) ) {
			return $awssqs_server['profile'];
		}
		else if( isset( $_ENV['AWS_PROFILE'] ) ) {
			return $_ENV['AWS_PROFILE'];
		}
		else {
			return $default;
		}
	}

}