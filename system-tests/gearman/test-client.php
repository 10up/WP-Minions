<?php
$client= new GearmanClient();
$client->addServer();

// Ensure we have compatibility with php7 and older versions of php
if ( method_exists( $client, 'doNormal' ) ) {
	print $client->doNormal( "reverse", $argv[1] ) . "\n";
} else {
	print $client->do( "reverse", $argv[1] ) . "\n";
}
