<?php
namespace Krokedil\Avarda\ScheduleOrderCompletion;

defined( 'ABSPATH' ) || exit;

/**
 * Scheduler class for the Avarda Schedule Order Completion feature plugin.
 * Handles the logic to verify and schedule order completions when required.
 *
 * @since 1.0.0
 */
class Scheduler {
	/**
	 * The status to use for the scheduled order completion.
	 *
	 * @var string
	 */
	private $completed_status = 'completed';

	/**
	 * The failed order status to set the order to after 5 reschedules.
	 *
	 * @var string
	 */
	private $failed_status = 'failed';

	/**
	 * The on hold order status to set the order to when it is scheduled.
	 *
	 * @var string
	 */
	private $on_hold_status = 'on-hold';

	/**
	 * Class constructor.
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'woocommerce_order_status_completed', array( $this, 'maybe_prevent_order_status_change' ), 5 );
		add_action( 'aco_scheduled_order_completion', array( $this, 'process_scheduled_order_completion' ) );

		apply_filters( 'avarda_schedule_order_completion_status', $this->completed_status );
		apply_filters( 'avarda_schedule_order_failed_status', $this->failed_status );
		apply_filters( 'avarda_schedule_order_on_hold_status', $this->on_hold_status );
	}

	/**
	 * Maybe prevent the order status change.
	 *
	 * @param int $order_id The order id.
	 *
	 * @return void
	 */
	public function maybe_prevent_order_status_change( $order_id ) {
		// Get the WC_Order object from the order id.
		$order = wc_get_order( $order_id );

		// If we could not get the order, or its not a Avarda order, we don't need to do anything.
		if ( ! $order || 'aco' !== $order->get_payment_method() ) {
			return;
		}

		$result = $this->should_schedule_order_completion( $order );
		if ( $result ) { // If the result true, we need to reschedule it.
			$this->schedule_order_completion( $order );

			// Unhook the order completion action from Avarda so it does not run, and set it to on-hold.
			remove_action( 'woocommerce_order_status_completed', array( ACO_WC()->order_management, 'activate_reservation' ) );
			$order->update_status( $this->on_hold_status, __( 'The Avarda order could not be activated. It will be scheduled to try again later.', 'avarda-schedule-order-completion' ) );
			$order->save();
		}
	}

	/**
	 * Process the scheduled order completion event.
	 *
	 * @param array<int> $order_ids The order ids to process.
	 *
	 * @return void
	 */
	public function process_scheduled_order_completion( $order_ids ) {
		$to_reschedule = array();
		foreach ( $order_ids as $order_id ) {
			/**
			 * Get the WC_Order order from the order id.
			 *
			 * @var \WC_Order $order
			 */
			$order = wc_get_order( $order_id );

			if ( ! $order || 'aco' !== $order->get_payment_method() || $this->completed_status === $order->get_status() ) { // If we could not get the order, its not a Avarda order, or its already completed, we don't need to do anything.
				continue;
			}

			$result = $this->should_schedule_order_completion( $order, true );
			if ( $result ) { // If the result true, we need to reschedule it.
				$order->add_order_note( __( 'The Avarda order could not be activated after being scheduled. It will be scheduled to try again later.', 'avarda-schedule-order-completion' ) );
				$to_reschedule[] = $order;
				continue;
			}

			// If the order has a failed status, just continue.
			if ( $this->failed_status === $order->get_status() ) {
				continue;
			}

			// If the purchase is processed, we can complete the order.
			$order->update_status( $this->completed_status, __( 'Scheduled completion of the Avarda order.', 'avarda-schedule-order-completion' ) );
			$order->save();
		}

		// If we have orders to reschedule, we need to schedule them again.
		if ( ! empty( $to_reschedule ) ) {
			$this->schedule_order_completion( $to_reschedule );
		}
	}

	/**
	 * Schedule the order completion.
	 *
	 * @param \WC_Order|array<\WC_Order> $orders The order or orders to schedule.
	 *
	 * @return void
	 */
	public function schedule_order_completion( $orders ) {
		// If the orders is not an array, sent the single order to an array.
		if ( ! is_array( $orders ) ) {
			$orders = array( $orders );
		}

		$order_ids = array();
		foreach ( $orders as $order ) {
			$order_ids[] = $order->get_id(); // Get the order id from the order.
		}

		if ( empty( $order_ids ) ) {
			return; // Bail early if no order ids are found.
		}

		$args = array(
			'hook'   => 'aco_scheduled_order_completion',
			'status' => \ActionScheduler_Store::STATUS_PENDING,
		);

		/**
		 * Get the scheduled events for the hook.
		 *
		 * @var \ActionScheduler_Action[] $events The scheduled events.
		 */
		$events = as_get_scheduled_actions( $args );
		$data   = array();
		// If we did get an event, we need to check if any of the orders are already scheduled.
		foreach ( $events as $event ) {
			$data          = $event->get_args(); // Get the data from the event.
			$scheduled_ids = $data[0]; // Get the scheduled ids from the data.
			$diff          = array_diff( $order_ids, $scheduled_ids ); // Get the diff between the scheduled ids and the order ids. This will give us the order ids that are not in the scheduled event.

			// If the diff is empty, no new orders should be scheduled.
			if ( empty( $diff ) ) {
				return;
			}

			$order_ids = array_merge( $scheduled_ids, $diff ); // Merge the diff with the data to get all the order ids from the new and old data.
			break;
		}

		// If we did not find the order in the scheduled events, we need to delete the event, and reschedule it with the new order. This is to prevent adding to many events for the same hook.
		if ( isset( $event ) ) {
			as_unschedule_action( 'aco_scheduled_order_completion', $data );
		}

		$time_to_run = isset( $event ) ? $event->get_schedule()->get_date()->getTimestamp() : time() + 3600; // @phpstan-ignore-line
		as_schedule_single_action( $time_to_run, 'aco_scheduled_order_completion', array( $order_ids ) );
	}

	/**
	 * Verify if the order should be scheduled for completion or not.
	 *
	 * @param \WC_Order $order The order object.
	 * @param bool      $update_count If the meta data for the reschedule count should be updated. This only needs to be done if triggered by the scheduler.
	 *
	 * @return bool
	 */
	public function should_schedule_order_completion( &$order, $update_count = true ) {
		// Get the purchase from Avarda.
		$purchase_id      = $order->get_meta( '_wc_avarda_purchase_id' );
		$reschedule_count = 0;
		$payment          = null;

		if ( ! empty( $purchase_id ) ) { // If we have a purchase id, we can try to get the payment.
			$payment = ACO_WC()->api->request_get_payment( $purchase_id ); // @phpstan-ignore-line

			if ( ! is_wp_error( $payment ) && $payment['processedBackEnd'] ) { // If we could get the payment, and it is processed, we can complete the order.
				return apply_filters( 'aco_should_schedule_order_completion', false, $order, $payment );
			}

			// Get the amount of times the order has been rescheduled.
			$reschedule_count = intval( $order->get_meta( '_aco_reschedule_completion_count' ) ?: 0 ); // phpcs:ignore

			// If the order was rescheduled 5 times, we need to set the order to failed, and stop it from being rescheduled.
			if ( $reschedule_count >= 5 ) {
				$order->set_status( $this->failed_status, __( 'The Avarda order could not be activated. The order has been rescheduled 5 times and will not be scheduled again.', 'avarda-schedule-order-completion' ) );
				$order->save();

				return apply_filters( 'aco_should_schedule_order_completion', false, $order, $payment );
			}
		}

		$order->set_status( $this->on_hold_status, __( 'The Avarda order could not be activated. It will be scheduled to try again later.', 'avarda-schedule-order-completion' ) );
		if ( $update_count ) { // If the count should be updated, we need to update the reschedule count. This only needs to be done if the trigger is not from the scheduled event.
			$order->update_meta_data( '_aco_reschedule_completion_count', strval( $reschedule_count + 1 ) );
		}

		$order->save();
		return apply_filters( 'aco_should_schedule_order_completion', true, $order, $payment );
	}
}
