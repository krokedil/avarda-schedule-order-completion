<?php
namespace Krokedil\Avarda\ScheduleOrderCompletion\Traits;

trait Singleton {
	/**
	 * Instance of the class.
	 *
	 * @var object
	 */
	protected static $instance;

	/**
	 * Get the instance of the class.
	 *
	 * @return object
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * Prevent cloning of the instance of the class.
	 *
	 * @return void
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, esc_html__( 'Cloning is forbidden.', 'avarda-schedule-order-completion' ), '1.0.0' );
	}

	/**
	 * Prevent unserializing of the instance of the class.
	 *
	 * @return void
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, esc_html__( 'Unserializing is forbidden.', 'avarda-schedule-order-completion' ), '1.0.0' );
	}
}
