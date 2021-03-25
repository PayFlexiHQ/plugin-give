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
						__( 'Webhook received returned an error message.', 'give-payflexi' ),
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
			if ('transaction.approved' == $event->event && 'approved' == $event->data->status) {
				status_header( 200 );
				// Update time of webhook received whenever the event is retrieved.
				give_update_option( 'give_payflexi_last_webhook_received_timestamp', current_time( 'timestamp', 1 ) );
				
				$reference = $event->data->reference;
				$initial_reference = $event->data->initial_reference;

				$payment = give_get_payment_by('key', $initial_reference);
				$payment_id   = absint($payment->ID);
				$saved_txn_ref = give_get_meta($payment_id, '_give_payflexi_transaction_reference', true, false, 'donation');
				$donation_amount = give_get_meta($payment_id, '_give_payflexi_donation_amount', true, false, 'donation');
				$amount_paid  = $event->data->txn_amount ? $event->data->txn_amount : 0;
				$total_amount_paid = $event->data->total_amount_paid;
		
				if ($amount_paid < $donation_amount ) {
					if($reference === $initial_reference && (!$saved_txn_ref || empty($saved_txn_ref))){
						give_update_meta($payment_id, '_give_payflexi_installment_amount_paid', $amount_paid, '', 'donation');
						give_update_payment_meta($payment_id,  '_give_payment_total', $amount_paid);
						give_update_payment_status($payment_id, 'complete');
						give_insert_payment_note($payment, 'Instalment Payment made: ' . $amount_paid);
					}
					if($reference !== $initial_reference && (!$saved_txn_ref || !empty($saved_txn_ref))){
						$installment_amount_paid = give_get_meta($payment_id, '_give_payflexi_installment_amount_paid', true, false, 'donation');
						$total_installment_amount_paid = $installment_amount_paid + $amount_paid;
						give_update_meta($payment_id, '_give_payflexi_installment_amount_paid', $total_installment_amount_paid, '', 'donation');
						if($total_amount_paid >= $donation_amount){
							give_update_payment_meta($payment_id,  '_give_payment_total', $donation_amount);
							give_update_payment_status($payment_id, 'complete');
							give_insert_payment_note($payment, 'Instalment Payment made: ' . $donation_amount);
						}else{
							give_increase_total_earnings($amount_paid);
							give_update_payment_meta($payment_id,  '_give_payment_total', $total_installment_amount_paid);
							give_update_payment_status($payment_id, 'complete');
							give_insert_payment_note($payment, 'Instalment Payment made: ' . $amount_paid);
						}
					}
				}else{
					give_update_payment_status($payment_id, 'complete');
				}

				return true;

			}

			// If failed.
			status_header( 500 );
			die( '-1' );
		}
	}

	// Initialize PayFlexi Webhooks.
	new Give_PayFlexi_Webhooks();
}
