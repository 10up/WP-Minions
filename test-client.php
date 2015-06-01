<?php
$client= new GearmanClient();
$client->addServer();
print $client->do( "reverse", $argv[1] ) . "\n";
