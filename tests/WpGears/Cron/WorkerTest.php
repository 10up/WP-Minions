<?php

namespace WpGears\Cron;

class CronWorkerTest extends \WP_UnitTestCase {

	public $worker;

	function setUp() {
		parent::setUp();

		$this->worker = new Worker();
	}

	function test_it_can_be_registered() {
		$actual = $this->worker->register();
		$this->assertTrue( $actual );
	}

	function test_it_can_start_working_on_work() {
		$actual = $this->worker->work();
		$this->assertTrue( $actual );
	}

}
