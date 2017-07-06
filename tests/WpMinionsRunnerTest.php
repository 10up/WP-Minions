<?php

class WpMinionsRunnerTest extends \WP_UnitTestCase {

	function setUp() {
		parent::setUp();

		require_once( __DIR__ . '/../wp-minions-runner.php' );
	}

	function tearDown() {
		\Mockery::close();
	}

	function test_it_has_a_custom_autoloader() {
		$this->assertTrue( function_exists( 'wp_minions_autoload' ) );
	}

	function test_it_can_autoload_classes() {
		spl_autoload_register( 'wp_minions_autoload', false, true );

		$klass = new \WpMinions\Plugin();
		$this->assertInstanceOf( '\WpMinions\Plugin', $klass );
	}

	function test_it_will_execute_a_job_on_run() {
		$mock = \Mockery::mock()
			->shouldReceive( 'register' )
			->andReturn( true )
			->shouldReceive( 'work' )
			->with()
			->once()
			->andReturn( true )
			->getMock();

		$plugin = \WpMinions\Plugin::get_instance();
		$plugin->config_prefix = 'B' . uniqid();
		$plugin->worker = $mock;

		$actual = wp_minions_runner();
		$this->assertEquals( 0, $actual );
	}

}
