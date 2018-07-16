<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/config.php';


$connection = new Aws\Sqs\SqsClient( $aws_credentials );

$queue_url = $connection->getQueueUrl( array( 'QueueName' => 'wordpress' ) )->get( 'QueueUrl' );

$connection->sendMessage( array(
	'QueueUrl'    => $queue_url,
	'MessageBody' => 'Hello World!'
) );