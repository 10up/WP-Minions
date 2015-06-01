<?php
$worker= new GearmanWorker();
$worker->addServer();
$worker->addFunction( "reverse", "my_reverse_function" );
while ( $worker->work() );

function my_reverse_function( $job )  {
	return strrev( $job->workload() );
}
