<?php

namespace WpMinions\Cron;

use WpMinions\Worker as BaseWorker;

/**
 * The WP-Cron implementation does not have a concept of Workers. We
 * use stub implementations to match the required base API.
 */
class Worker extends BaseWorker {

	/**
	 * WP-Cron does not have an Worker initialization.
	 *
	 * @return bool Always true
	 */
	public function register() {
		return true;
	}

	/**
	 * Work is done automatically on the next WordPress request.
	 *
	 * @return bool Always true
	 */
	function work() {
		return true;
	}

}
