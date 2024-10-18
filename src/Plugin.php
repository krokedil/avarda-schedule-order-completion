<?php

namespace Krokedil\Avarda\ScheduleOrderCompletion;

use Krokedil\Avarda\ScheduleOrderCompletion\Scheduler;
use Krokedil\Avarda\ScheduleOrderCompletion\Traits\Singleton;

/**
 * Plugin class for the Avarda Schedule Order Completion feature plugin.
 * Handles the initialization of the plugin, and loading of dependencies.
 *
 * @since 1.0.0
 */
class Plugin {
	use Singleton;

	/**
	 * The instance of the scheduler class.
	 *
	 * @var Scheduler
	 */
	private $scheduler;

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'plugins_loaded', array( $this, 'init' ), 15 ); // Priority 15 to ensure it's loaded after the main plugin.
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init() {
		$this->load_dependencies();
	}

	/**
	 * Load the plugin dependencies.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$this->scheduler = new Scheduler();

		do_action( 'avarda_schedule_order_completion_loaded' );
	}

	/**
	 * Get the instance of the scheduler class.
	 *
	 * @return Scheduler
	 */
	public function scheduler() {
		if ( 0 === did_action( 'avarda_schedule_order_completion_loaded' ) ) {
			wc_doing_it_wrong( Scheduler::class, __( 'The scheduler class has not been initialized yet, it can only be used after the \'avarda_schedule_order_completion_loaded\'.', 'avarda-schedule-order-completion' ), '1.0.0' );
		}

		return $this->scheduler;
	}
}
