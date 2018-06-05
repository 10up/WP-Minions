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
		$job_data = array(
			'hook'    => $hook,
			'args'    => $args,
			'blog_id' => get_current_blog_id(),
		);

		$job_data = apply_filters( 'wp_async_task_add_job_data', $job_data );

		if ( function_exists( 'is_multisite' ) && is_multisite() && $job_data['blog_id'] ) {
			$blog_id = $job_data['blog_id'];

			if ( get_current_blog_id() !== $blog_id ) {
				switch_to_blog( $blog_id );
				$switched = true;
			} else {
				$switched = false;
			}
		}

		wp_schedule_single_event( time(), $job_data['hook'], $job_data['args'] );

		if ( $switched ) {
			restore_current_blog();
		}

		return true;
	}
}
