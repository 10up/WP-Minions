<?php

namespace WpGears;

class PluginTest extends \WP_UnitTestCase {

	public $plugin;

	function setUp() {
		parent::setUp();

		$this->plugin = new Plugin();
		$this->config_prefix = 'A' . uniqid();
	}

	function tearDown() {
		\Mockery::close();
	}

	function test_it_has_a_singleton_instance() {
		$instance1 = Plugin::get_instance();
		$instance2 = Plugin::get_instance();

		$this->assertSame( $instance1, $instance2 );
	}

	function test_it_does_not_recreate_client() {
		$client1 = $this->plugin->get_client();
		$client2 = $this->plugin->get_client();

		$this->assertSame( $client1, $client2 );
	}

	function test_it_does_not_recreate_worker() {
		$worker1 = $this->plugin->get_worker();
		$worker2 = $this->plugin->get_worker();

		$this->assertSame( $worker1, $worker2 );
	}

	function test_it_will_execute_one_job_per_worker_by_default() {
		$actual = $this->plugin->get_jobs_per_worker();
		$this->assertEquals( 1, $actual );
	}

	function test_it_will_execute_specified_jobs_per_worker() {
		$this->plugin->config_prefix = 'A';
		define( 'A_JOBS_PER_WORKER', 50 );

		$actual = $this->plugin->get_jobs_per_worker();
		$this->assertEquals( 50, $actual );
	}

	function test_it_will_use_a_custom_client_class_if_defined() {
		$mock = \Mockery::mock( '\WpGears\Client' );
		$this->plugin->config_prefix = 'B';
		define( 'B_CLIENT_CLASS', get_class( $mock ) );

		$actual = $this->plugin->build_client();
		$this->assertInstanceOf( get_class( $mock ), $actual );
	}

	function test_it_will_use_a_custom_worker_class_if_defined() {
		$mock = \Mockery::mock( '\WpGears\Worker' );
		$this->plugin->config_prefix = 'C';
		define( 'C_WORKER_CLASS', get_class( $mock ) );

		$actual = $this->plugin->build_worker();
		$this->assertInstanceOf( get_class( $mock ), $actual );
	}

	function test_it_will_build_a_gearman_client_if_gearman_is_present() {
		if ( ! class_exists( '\GearmanClient' ) ) {
			$mock = \Mockery::mock( 'alias:GearmanClient' );
			$klass = get_class( $mock );
		} else {
			$klass = '\GearmanClient';
		}

		$actual = $this->plugin->build_client();
		$this->assertInstanceOf(
			'\WpGears\Gearman\Client', $actual
		);
	}

	function test_it_will_build_a_gearman_worker_if_gearman_is_absent() {
		if ( ! class_exists( '\GearmanWorker' ) ) {
			$mock = \Mockery::mock( 'alias:GearmanWorker' );
			$klass = get_class( $mock );
		} else {
			$klass = '\GearmanWorker';
		}

		$actual = $this->plugin->build_worker();
		$this->assertInstanceOf(
			'\WpGears\Gearman\Worker', $actual
		);
	}

	function test_it_will_build_a_cron_client_if_gearman_is_missing() {
		if ( ! class_exists( '\GearmanClient' ) ) {
			$actual = $this->plugin->build_client();
			$this->assertInstanceOf(
				'\WpGears\Cron\Client', $actual
			);
		}
	}

	function test_it_will_build_a_cron_worker_if_gearman_is_missing() {
		if ( ! class_exists( '\GearmanWorker' ) ) {
			$actual = $this->plugin->build_worker();
			$this->assertInstanceOf(
				'\WpGears\Cron\Worker', $actual
			);
		}
	}

	function test_it_can_pass_jobs_to_client() {
		$mock = \Mockery::mock()
			->shouldReceive( 'add' )
			->with( 'action_a', array( 1, 2, 3 ), 'high' )
			->once()
			->andReturn( true )
			->getMock();

		$this->plugin->client = $mock;
		$actual = $this->plugin->add( 'action_a', array( 1, 2, 3 ), 'high' );
		$this->assertTrue( $actual );
	}

	function test_it_will_execute_one_worker_and_exit_by_default() {
		$mock = \Mockery::mock()
			->shouldReceive( 'work' )
			->with()
			->once()
			->andReturn( true )
			->getMock();

		$this->plugin->worker = $mock;

		$actual = $this->plugin->work();
		$this->assertEquals( 0, $actual );
	}

	function test_it_will_return_1_exit_code_if_worker_failed() {
		$mock = \Mockery::mock()
			->shouldReceive( 'work' )
			->with()
			->once()
			->andReturn( false )
			->getMock();

		$this->plugin->worker = $mock;

		$actual = $this->plugin->work();
		$this->assertEquals( 1, $actual );
	}

	function test_it_will_execute_multiple_jobs_on_worker_if_specified() {
		$mock = \Mockery::mock()
			->shouldReceive( 'work' )
			->with()
			->times( 10 )
			->andReturn( true )
			->getMock();

		$this->plugin->worker = $mock;

		$this->plugin->config_prefix = 'D';
		define( 'D_JOBS_PER_WORKER', 10 );

		$actual = $this->plugin->work();
		$this->assertEquals( 0, $actual );
	}

	function test_it_will_build_client_and_worker_on_enabled() {
		$this->plugin->enable();

		$this->assertInstanceOf(
			'\WpGears\Worker', $this->plugin->worker
		);

		$this->assertInstanceOf(
			'\WpGears\Client', $this->plugin->client
		);
	}

	function test_it_will_not_run_if_already_run() {
		$this->plugin->did_run = true;
		$actual = $this->plugin->run();
		$this->assertFalse( $actual );
	}

	function test_it_will_perform_a_job_on_run() {
		$mock = \Mockery::mock()
			->shouldReceive( 'work' )
			->with()
			->once()
			->andReturn( true )
			->getMock();

		$this->plugin->worker = $mock;
		$actual = $this->plugin->run();

		$this->assertEquals( 0, $actual );
	}

	/* helpers */

}
