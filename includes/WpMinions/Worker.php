<?php

namespace WpMinions;

/**
 * Workers are responsible for executing jobs. They may perform any
 * implementation specific initialization if needed.
 */
abstract class Worker {

	/**
	 * Any implementation specific initialization goes here.
	 *
	 * @return bool True or false based on whether the Worker was registered
	 */
	abstract public function register();

	/**
	 * Start performing work here. The Worker may perform the work
	 * immediately here or trigger actions to perform it later.
	 *
	 * @return bool True or false if the job could be done.
	 */
	abstract public function work();

}
