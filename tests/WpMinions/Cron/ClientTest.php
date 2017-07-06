<?php

namespace WpMinions\Cron;

class CronClientTest extends \WP_UnitTestCase {

	public $client;

	function setUp() {
		parent::setUp();

		$this->client = new Client();
	}

	function test_it_can_be_registered() {
		$actual = $this->client->register();
		$this->assertTrue( $actual );
	}

	function test_it_creates_cron_event_on_add() {
		$actual = $this->client->add( 'cron_action_a' );
		$this->assertTrue( $actual );

		$next_event = wp_next_scheduled( 'cron_action_a', array() );
		$this->assertEquals( time(), $next_event, '', 1000 );
	}

}
