<?php
/**
 * General Action Hooks
 *
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print cc field in donation form conditionally.
 *
 * @param int $form_id Donation Form ID.
 *
 * @since 1.0
 *
 * @return bool
 */
function give_payflexi_cc_form_callback( $form_id ) {

	if ( give_is_setting_enabled( give_get_option( 'payflexi_billing_details' ) ) ) {
		give_default_cc_address_fields( $form_id );
		return true;
	}

	return false;
}

add_action( 'give_payflexi_cc_form', 'give_payflexi_cc_form_callback' );


/**
 * Auto set pending payment to abandoned.
 *
 * @since 1.0
 *
 * @param int $payment_id
 */
function give_payflexi_set_donation_abandoned_callback( $payment_id ) {
	/**
	 * @var Give_Payment $payment Payment object.
	 */
	$payment = new Give_Payment( $payment_id );

	if ( 'pending' === $payment->status ) {
		$payment->update_status( 'abandoned' );
	}
}

add_action( 'give_payflexi_set_donation_abandoned', 'give_payflexi_set_donation_abandoned_callback' );

