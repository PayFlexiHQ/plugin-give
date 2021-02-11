<?php
/**
 * List of general function used to process PayFlexi Payment Gateway
 *
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether PayFlexi gateway is in test mode or not.
 *
 * @since 1.0
 *
 * @return bool
 */
function give_payflexi_is_test_mode() {
	return apply_filters( 'give_payflexi_is_test_mode', give_is_test_mode() );
}

/**
 * Get PayFlexi API URL.
 *
 * @since 1.0
 *
 * @return string
 */
function give_payflexi_get_api_url() {

	$payflexi_redirect = 'https://api.payflexi.test';

	return $payflexi_redirect;
}

/**
 * Get Payment Method Label.
 *
 * @since 1.0
 *
 * @return string
 */
function give_payflexi_get_payment_method_label() {
	return give_get_option( 'payflexi_checkout_label', __( 'PayFlexi (Pay in Instalment)', 'give-payflexi' ) );
}

/**
 * Get PayFlexi merchant credentials.
 *
 * @since 1.0
 *
 * @return array
 */
function give_payflexi_get_merchant_credentials() {
	$credentials = array(
		'public_key'  => give_get_option( 'give_payflexi_test_public_key', '' ),
		'secret_key' => give_get_option( 'give_payflexi_test_secret_key', '' ),
	);

	if ( ! give_payflexi_is_test_mode() ) {
		$credentials = array(
            'public_key'  => give_get_option( 'give_payflexi_live_public_key', '' ),
            'secret_key' => give_get_option( 'give_payflexi_live_secret_key', '' ),
		);
	}

	return $credentials;

}

/**
 * Validate PayFlexi Signature to verify donation.
 *
 * @param array  $data      Response Variables.
 * @param string $signature PayFlexi Signature.
 *
 * @since 1.0
 *
 * @return bool
 */
function give_payflexi_validate_signature( $data, $signature ) {
	$result = $data['signature'] === $signature;

	return $result;
}
