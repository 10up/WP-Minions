<?php

class WpGearsRunnerTest extends \WP_UnitTestCase {

	function setUp() {
		parent::setUp();

		require_once( __DIR__ . '/../wp-gears-runner.php' );
	}

	function tearDown() {
		\Mockery::close();
	}

	function test_it_has_a_custom_autoloader() {
		$this->assertTrue( function_exists( 'wp_gears_autoload' ) );
	}

	function test_it_can_autoload_classes() {
		spl_autoload_register( 'wp_gears_autoload', false, true );

		$klass = new \WpGears\Plugin();
		$this->assertInstanceOf( '\WpGears\Plugin', $klass );
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

		$plugin = \WpGears\Plugin::get_instance();
		$plugin->config_prefix = 'B' . uniqid();
		$plugin->worker = $mock;

		$actual = wp_gears_runner();
		$this->assertEquals( 0, $actual );
	}

}
