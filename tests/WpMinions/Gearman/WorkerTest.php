<?php

namespace WpMinions\Gearman;

class GearmanWorkerTest extends \WP_UnitTestCase {

	public $worker;

	function setUp() {
		parent::setUp();

		$this->worker = new Worker();
	}

	function tearDown() {
		\Mockery::close();
	}

	function test_it_knows_if_no_gearman_servers_are_defined() {
		$actual = $this->worker->get_servers();
		$this->assertEmpty( $actual );
	}

	function test_it_knows_if_gearman_servers_are_defined() {
		global $gearman_servers;
		$gearman_servers = array(
			'192.168.1.10:5555',
		);

		$actual = $this->worker->get_servers();
		$this->assertEquals(
			array( '192.168.1.10:5555' ), $actual
		);

		unset( $GLOBALS['gearman_servers'] );
	}

	function test_it_can_create_a_gearman_worker_if_configured() {
		if ( class_exists( '\GearmanWorker' ) ) {
			$actual = $this->worker->get_gearman_worker();
			$this->assertInstanceOf(
				'\GearmanWorker', $actual
			);
		} else {
			//$this->markTestSkipped();
		}
	}

	function test_it_will_not_register_if_no_valid_gearman_worker() {
		$this->worker->gearman_worker = false;
		$actual = $this->worker->register();
		$this->assertFalse( $actual );
	}

	function test_it_will_not_register_if_failed_to_add_servers() {
		$this->worker->gearman_worker = \Mockery::mock()
			->shouldReceive( 'addServer' )
			->andThrow( new \GearmanException( 'Failed to set exception option' ) )
			->getMock();

		$actual = $this->worker->register();
		$this->assertFalse( $actual );
	}

	function test_it_will_add_default_server_to_worker_if_not_defined() {
		$mock = \Mockery::mock()
			->shouldReceive( 'addServer' )
			->with()
			->andReturn( true )
			->shouldReceive( 'addFunction' )
			->andReturn( true )
			->getMock();

		$this->worker->gearman_worker = $mock;
		$actual = $this->worker->register();
		$this->assertTrue( $actual );
	}

	function test_it_will_add_multiple_servers_to_worker_if_defined() {
		$this->worker->gearman_servers = array(
			'localhost:5554', '127.0.0.1:5555',
		);

		$mock = \Mockery::mock()
			->shouldReceive( 'addServers' )
			->with( implode( ',', $this->worker->gearman_servers ) )
			->andReturn( true )
			->shouldReceive( 'addFunction' )
			->andReturn( true )
			->getMock();

		$this->worker->gearman_worker = $mock;
		$actual = $this->worker->register();
		$this->assertTrue( $actual );
	}

	function test_it_will_not_fail_if_the_worker_failed_to_execute() {
		$mock = \Mockery::mock()
			->shouldReceive( 'work' )
			->andThrow( new \Exception( 'foo error' ) )
			->getMock();

		$this->worker->gearman_worker = $mock;
		$actual = $this->worker->work();

		$this->assertFalse( $actual );
	}

	function test_it_will_start_the_gearman_worker_on_work() {
		$mock = \Mockery::mock()
			->shouldReceive( 'work' )
			->andReturn( true )
			->getMock();

		$this->worker->gearman_worker = $mock;
		$actual = $this->worker->work();

		$this->assertTrue( $actual );
	}

	function test_it_can_execute_job_with_specified_arguments() {
		$payload = array(
			'hook' => 'action_d',
			'args' => array( 'a' => 1, 'b' => 2 ),
			'blog_id' => false,
		);

		$mock = \Mockery::mock()
			->shouldReceive( 'workload' )
			->with()
			->andReturn( json_encode( $payload ) )
			->getMock();

		$action_mock = \Mockery::mock()
			->shouldReceive()
			->andReturn( 'foo' );

		$self = $this;
		$args = $payload['args'];

		add_action( 'action_d', array( $this, 'did_action_d' ) );
		add_action( 'wp_async_task_before_job', array( $this, 'did_before_job' ) );
		add_action( 'wp_async_task_before_job_action_d', array( $this, 'did_before_action_d' ) );
		add_action( 'wp_async_task_after_job', array( $this, 'did_after_job' ) );
		add_action( 'wp_async_task_after_job_action_d', array( $this, 'did_after_action_d' ) );

		$this->expected_args = $args;
		$actual = $this->worker->do_job( $mock );

		$this->assertTrue( $this->ran_action_d );
		$this->assertTrue( $this->before_job );
		$this->assertTrue( $this->did_before_action_d );
		$this->assertTrue( $this->after_job );
		$this->assertTrue( $this->did_after_action_d );
	}

	function did_action_d( $args ) {
		$this->ran_action_d = true;
		$this->assertEquals( $this->expected_args, $args );
	}

	function did_before_job() {
		$this->before_job = true;
	}

	function did_after_job() {
		$this->after_job = true;
	}

	function did_before_action_d() {
		$this->did_before_action_d = true;
	}

	function did_after_action_d() {
		$this->did_after_action_d = true;
	}

	function test_it_will_switch_to_target_blog_if_needed() {
		if ( ! is_multisite() ) {
			return;
		}

		$payload = array(
			'hook' => 'action_e',
			'args' => array( 'a' => 1, 'b' => 2 ),
			'blog_id' => 1,
		);

		$mock = \Mockery::mock()
			->shouldReceive( 'workload' )
			->with()
			->andReturn( json_encode( $payload ) )
			->getMock();

		$action_mock = \Mockery::mock()
			->shouldReceive()
			->andReturn( 'foo' );

		$self = $this;
		$args = $payload['args'];

		add_action( 'action_e', array( $this, 'did_action_e' ) );

		$actual = $this->worker->do_job( $mock );

		$this->assertEquals( 1, $this->actual_site );
	}

	function did_action_e( $args ) {
		$this->actual_site = get_current_blog_id();
	}

}
