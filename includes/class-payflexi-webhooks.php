<?php
/**
 * Give - PayFlexi | Process Webhooks
 *
 * @since 1.0.0
 *
 * @package    Give
 * @subpackage PayFlexi
 * @copyright  Copyright (c) 2019, GiveWP
 * @license    https://opensource.org/licenses/gpl-license GNU Public License
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Give_PayFlexi_Webhooks' ) ) {

	/**
	 * Class Give_PayFlexi_Webhooks
	 *
	 * @since 1.0.0
	 */
	class Give_PayFlexi_Webhooks {

		/**
		 * Give_PayFlexi_Webhooks constructor.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @return void
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'listen' ) );
		}

		/**
		 * Listen for PayFlexi webhook events.
		 *
		 * @access public
		 * @since  1.0.0
		 *
		 * @return void
		 */
		public function listen() {

			$give_listener = give_clean( filter_input( INPUT_GET, 'give-listener' ) );

			// Must be a payflexi listener to proceed.
			if ( ! isset( $give_listener ) || 'payflexi' !== $give_listener ) {
				return;
			}

			if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || ! array_key_exists('HTTP_X_PAYFLEXI_SIGNATURE', $_SERVER)) {
				exit;
			}

			// Retrieve the request's body and parse it as JSON.
			$body  = @file_get_contents( 'php://input' );

			$credentials = give_payflexi_get_merchant_credentials();		

			if ($_SERVER['HTTP_X_PAYFLEXI_SIGNATURE'] !== hash_hmac('sha512', $body, $credentials['secret_key'])) {
				exit;
			}

			$event = json_decode( $body );

			ray(['Webhook Event' => $event]);

			$processed_event = $this->process( $event );

			if ( false === $processed_event ) {
				$message = __( 'Something went wrong with processing the payment gateway event.', 'give-payflexi' );
			} else {
				$message = sprintf(
				/* translators: 1. Processing result. */
					__( 'Processed event: %s', 'give-payflexi' ),
					$processed_event
				);

				give_record_gateway_error(
					__( 'PayFlexi - Webhook Received', 'give-payflexi' ),
					sprintf(
					/* translators: 1. Event ID 2. Event Type 3. Message */
						__( 'Webhook received with ID %1$s and TYPE %2$s which processed and returned a message %3$s.', 'give-payflexi' ),
						$event->id,
						$event->type,
						$message
					)
				);
			}

			status_header( 200 );
			exit( $message );
		}

		/**
		 * Process Webhooks.
		 *
		 * @since  1.0.0
		 * @access public
		 *
		 * @param \PayFlexi\Event $event Event.
		 *
		 * @return bool|string
		 */
		public function process($event) {

			// Next, proceed with additional webhooks.
			if ('transaction.approved' == $event->event) {
				status_header( 200 );

				// Update time of webhook received whenever the event is retrieved.
				give_update_option( 'give_payflexi_last_webhook_received_timestamp', current_time( 'timestamp', 1 ) );

		
				$event_type = $event->event;

				/**
				 * @todo Add switch case here in case any webhook trigger is required for one-time donations.
				 */

				do_action( 'give_payflexi_event_' . $event_type, $event );

				return $event_type;

			}

			// If failed.
			status_header( 500 );
			die( '-1' );
		}
	}

	// Initialize PayFlexi Webhooks.
	new Give_PayFlexi_Webhooks();
}
