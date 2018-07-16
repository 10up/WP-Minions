<?php

require_once __DIR__ . '/../../vendor/autoload.php';
global $aws_credentials;
$aws_credentials = array(
	'region'      => 'us-east-1',
	'version'     => 'latest',
	'credentials' => array(
		'key'    => '', //Testing AWS key.
		'secret' => ' ' //Testing AWS secret.
	)
);

$connection = new Aws\Sqs\SqsClient( $aws_credentials );

$queue_url = $connection->getQueueUrl( array( 'QueueName' => 'wordpress' ) )->get( 'QueueUrl' );

$connection->sendMessage( array(
	'QueueUrl'    => $queue_url,
	'MessageBody' => 'Hello World!'
) );