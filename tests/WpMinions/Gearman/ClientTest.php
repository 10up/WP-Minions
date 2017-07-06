<?php

namespace WpMinions\Gearman;

class GearmanClientTest extends \WP_UnitTestCase {

	public $client;

	function setUp() {
		parent::setUp();

		$this->client = new Client();
	}

	function tearDown() {
		\Mockery::close();
	}

	function test_it_knows_if_no_gearman_servers_are_defined() {
		$actual = $this->client->get_servers();
		$this->assertEmpty( $actual );
	}

	function test_it_knows_if_gearman_servers_are_defined() {
		global $gearman_servers;
		$gearman_servers = array(
			'192.168.1.10:5555',
		);

		$actual = $this->client->get_servers();
		$this->assertEquals(
			array( '192.168.1.10:5555' ), $actual
		);

		unset( $gearman_servers );
	}

	function test_it_can_create_a_gearman_client_if_configured() {
		if ( class_exists( '\GearmanClient' ) ) {
			$actual = $this->client->get_gearman_client();
			$this->assertInstanceOf(
				'\GearmanClient', $actual
			);
		} else {
			//$this->markTestSkipped();
		}
	}

	function test_it_will_not_register_if_no_valid_gearman_client() {
		$this->client->gearman_client = \Mockery::mock()
			->shouldReceive( 'addServer' )
			->andThrow( new \GearmanException( 'Failed to set exception option' ) )
			->getMock();

		$actual = $this->client->register();
		$this->assertFalse( $actual );
	}

	function test_it_will_trap_gearman_error_if_failed_to_register_servers() {
		$this->client->gearman_client = false;
		$actual = $this->client->register();
		$this->assertFalse( $actual );
	}

	function test_it_will_add_default_server_to_client_if_not_defined() {
		$mock = \Mockery::mock()
			->shouldReceive( 'addServer' )
			->with()
			->andReturn( true )
			->getMock();

		$this->client->gearman_client = $mock;
		$actual = $this->client->register();
		$this->assertTrue( $actual );
	}

	function test_it_will_add_multiple_servers_to_client_if_defined() {
		$this->client->gearman_servers = array(
			'localhost:5554', '127.0.0.1:5555',
		);

		$mock = \Mockery::mock()
			->shouldReceive( 'addServers' )
			->with( implode( ',', $this->client->gearman_servers ) )
			->andReturn( true )
			->getMock();

		$this->client->gearman_client = $mock;
		$actual = $this->client->register();
		$this->assertTrue( $actual );
	}

	function test_it_has_a_blog_id_if_on_multisite() {
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$actual = $this->client->get_blog_id();
			$this->assertEquals( 1, $actual );
		} else {
			//$this->markTestSkipped();
		}
	}

	function test_it_does_not_have_blog_id_on_single_site() {
		if ( ! ( function_exists( 'is_multisite' ) && is_multisite() ) ) {
			$actual = $this->client->get_blog_id();
			$this->assertFalse( $actual );
		} else {
			//$this->markTestSkipped();
		}
	}
	
	
	function test_it_uses_blog_id_from_current_site_on_switch() {
		//create a new site and switch to it.
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$blog_id = $this->factory->blog->create();
			switch_to_blog( $blog_id );
			$actual = $this->client->get_blog_id();
			$this->assertEquals( $blog_id, $actual );
			restore_current_blog();
		}
		
	}

	function test_it_has_an_async_group() {
		if ( defined( 'WP_ASYNC_TASK_SALT' ) ) {
			$expected = WP_ASYNC_TASK_SALT . ':WP_Async_Task';
		} else {
			$expected = 'WP_Async_Task';
		}

		$actual = $this->client->get_async_group();
		$this->assertEquals( $expected, $actual );
	}

	function test_it_knows_when_to_use_high_background_method() {
		$actual = $this->client->get_background_method( 'high' );
		$this->assertEquals( 'doHighBackground', $actual );
	}

	function test_it_knows_when_to_use_low_background_method() {
		$actual = $this->client->get_background_method( 'low' );
		$this->assertEquals( 'doLowBackground', $actual );
	}

	function test_it_knows_when_to_use_normal_background_method() {
		$actual = $this->client->get_background_method( 'normal' );
		$this->assertEquals( 'doBackground', $actual );
	}

	function test_it_will_not_add_hook_to_gearman_if_gearman_is_absent() {
		$this->client->gearman_client = false;
		$actual = $this->client->add( 'action_a' );
		$this->assertFalse( $actual );
	}

	function test_it_will_add_hook_to_gearman_client_if_present() {
		$payload = array(
			'hook'    => 'action_b',
			'args'    => array(),
			'blog_id' => function_exists( 'is_multisite' ) && is_multisite() ? get_current_blog_id() : false,
		);

		$group = defined( 'WP_ASYNC_TASK_SALT' ) ? WP_ASYNC_TASK_SALT . ':WP_Async_Task' : 'WP_Async_Task';
		$mock = \Mockery::mock()
			->shouldReceive('doBackground')
			->with( $group, json_encode( $payload ) )
			->andReturn( true )
			->getMock();

		$this->client->gearman_client = $mock;
		$actual = $this->client->add( 'action_b' );
		$this->assertTrue( $actual );
	}

	function test_it_will_add_hook_to_gearman_client_with_custom_arguments_and_priority() {
		$payload = array(
			'hook'    => 'action_c',
			'args'    => array( 'a' => 1, 'b' => 2 ),
			'blog_id' => is_multisite() ? get_current_blog_id() : false,
		);

		$group = defined( 'WP_ASYNC_TASK_SALT' ) ? WP_ASYNC_TASK_SALT . ':WP_Async_Task' : 'WP_Async_Task';
		$mock = \Mockery::mock()
			->shouldReceive('doHighBackground')
			->with( $group, json_encode( $payload ) )
			->andReturn( true )
			->getMock();

		$this->client->gearman_client = $mock;
		$actual = $this->client->add( 'action_b', $payload, 'high' );
		$this->assertTrue( $actual );
	}

}
