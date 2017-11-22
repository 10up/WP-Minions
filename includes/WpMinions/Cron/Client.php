<?php

namespace WpMinions\Cron;

use WpMinions\Client as BaseClient;

/**
 * The Cron Client uses WPCron to add jobs. This is the fallback used if
 * Gearman is absent.
 */
class Client extends BaseClient {

	/**
	 * The Cron Client has no initialization, just returns true.
	 *
	 * @return bool Always true
	 */
	public function register() {
		return true;
	}

	/**
	 * Adds the job to the WP Cron queue. It will be executed on the
	 * next WordPress request.
	 *
	 * @param string $hook The action hook name for the job
	 * @param array $args Optional arguments for the job
	 * @param string $priority Ignored in WP-Cron
	 * @return bool Always true
	 */
	public function add( $hook, $args = array(), $priority = 'normal' ) {
		// Priority isn't really something we can manage with wp-cron
		wp_schedule_single_event( time(), $hook, array( $args ) );

		return true;
	}

}
