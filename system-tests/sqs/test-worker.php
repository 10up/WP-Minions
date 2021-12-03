<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/config.php';

$connection = new Aws\Sqs\SqsClient( $aws_credentials );

$queue_url = $connection->getQueueUrl( array( 'QueueName' => 'wordpress' ) )->get( 'QueueUrl' );

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function ( $msg ) {
	echo " [x] Received ", $msg, "\n";
};

while ( true ) {
	$message = $connection->receiveMessage( array(
		'QueueUrl' => $queue_url
	) );
	if ( ! empty( $message ) && ! empty( $message['Messages'] ) ) {

		$message = array_pop( $message['Messages'] );

		try {

			call_user_func( $callback, $message['Body'] );
			$connection->deleteMessage( array(
				'QueueUrl'      => $queue_url,
				'ReceiptHandle' => $message['ReceiptHandle']
			) );
		} catch ( Exception $e ) {
			$connection->changeMessageVisibility( array(
				'QueueUrl'          => $queue_url,
				'ReceiptHandle'     => $message['ReceiptHandle'],
				'VisibilityTimeout' => 0
			) );
			echo $e->getMessage();
		}

	}

	sleep( 3 );
}
