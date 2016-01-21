<?php

class WpGearsTest extends \WP_UnitTestCase {

	function setUp() {
		parent::setUp();

		require_once( __DIR__ . '/../wp-gears.php' );
	}

	function tearDown() {
		\Mockery::close();
	}

	function test_it_has_a_custom_autoloader() {
		$this->assertTrue( function_exists( 'wp_gears_autoload' ) );
	}

	function test_it_can_initialize_the_plugin() {
		wp_async_task_init();

		$plugin = \WpGears\Plugin::get_instance();
		$this->assertTrue( $plugin->did_enable );
	}

	function test_it_can_add_job_to_client_object() {
		$mock = \Mockery::mock()
			->shouldReceive( 'register' )
			->atMost(1)
			->andReturn( true )
			->shouldReceive( 'add' )
			->with( 'action_b', array( 1, 2, 3 ), 'low' )
			->once()
			->andReturn( true )
			->getMock();

		$plugin = \WpGears\Plugin::get_instance();
		$plugin->client = $mock;

		$actual = wp_async_task_add( 'action_b', array( 1, 2, 3 ), 'low' );
		$this->assertTrue( $actual );
	}

}
