## Avarda Schedule Order Completion feature plugin.

### Description
This feature plugin will hook in before an order is updated to ensure that the status transition to Completed, can be processed successfully before the status is set in WooCommerce.

If the status can not be set, the order will be scheduled for completion at a later time, allowing for the order to possibly be processed fully by either a customer or manually corrected by an admin before we try to set the status and send the order activation call to Avarda.

The order can be scheduled for retry up to 5 times, and if the order needs to be scheduled it will be set to On Hold to highlight the order for the merchant.

If the order is completed successfully after being scheduled, the order will be set to either Completed or a status set by the filter `avarda_schedule_order_completion_status`

The checks that are made to ensure the order can be completed successfully with Avarda are:
1. The order has a valid Avarda Purchase ID set as the metadata.
2. A request to fetch the purchase from Avarda is made, and ensured that the `processedBackEnd` field is set to `true` in Avarda, which indicates that the order can be processed by the merchant.

Right now we schedule the retry every hour, and will only ever schedule one event to be triggered to avoid overloading the action scheduler with events for each order. If an event is already scheduled to be triggered, that event will have the new order ids added to the list of order ids to retry.

Each result for an order can be overridden by the filter `aco_should_schedule_order_completion`, which is passed the result as the first argument, the order as the second and the purchase from Avarda as the third if a request was made successfully, or a WP Error if it was unsuccessful. This allows merchants to potentially override the feature for specific orders if needed.

This feature prevents the order from ever getting to the Completed or filtered status for completion unless the order can be successfully processed by Avarda. This also prevents any emails from being sent to customers telling them that an order is completed when it is not.

This solves a few issues that can happen, namely:
- Avarda error code 100, Purchase must be in a completed state.
- HTTP error code 502 Bad Gateway from Avardas API, since a request will be made to get the purchase before we attempt to activate it, which uses the same endpoint.
- cURL error 28: Operation timed out after 10001 milliseconds with 0 bytes received, due to the same as above. The requests will be made in quick succession, so if the first one fails, the order will be scheduled for retry.

It does not solve right now:
- HTTP error code 500 from Avardas API, but it could be made to hook in to the error handling from the request itself to active the order, but that will not be able to prevent the status transition.
- Avarda error code 507, Purchase denied due to insufficient customer balance. As above, this can be added in the same way. But could also be done directly when we fetch the order if we want, since we can make a check against the order ballance and the order total in WooCommerce. This was not implemented yet to prevent issues incase of rounding differences.

### Filters
- `avarda_schedule_order_completion_status` - Filter the status that the order will be set to after being successfully completed.
- `avarda_schedule_order_failed_status` - Filter the status that the order will be set to after failing to be completed 5 times, default is on-hold.
- `avarda_schedule_order_on_hold_status` - Filter the status that the order will be set to when it is scheduled for retry, default is on-hold.
- `aco_should_schedule_order_completion` - Filter if an order should be scheduled for completion or not. The result is passed as the first argument, the order as the second and the purchase from Avarda as the third if a request was made successfully, or a WP Error if it was unsuccessful.
- `aco_is_processed_backend` - Filter to be able to override the check for if the Avarda order has been processed in their backend or not. Can be used for testing purposes to force the order to be scheduled for retry.

```php
add_filter( 'avarda_schedule_order_completion_status', function( $status ) {
    return 'processing'; // Or any other status you want to set the order to when it has been successfully completed.
} );

add_filter( 'avarda_schedule_order_failed_status', function( $status ) {
    return 'failed'; // Or any other status you want to set the order to when it has failed to be completed 5 times.
} );

add_filter( 'avarda_schedule_order_on_hold_status', function( $status ) {
    return 'on-hold'; // Or any other status you want to set the order to when it is scheduled for retry.
} );

add_filter( 'aco_should_schedule_order_completion', function( $result, $order, $purchase ) {
    return false;
}, 10, 3 );
```
