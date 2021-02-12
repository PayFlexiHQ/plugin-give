<?php

/**
 * Process PayFlexi Payments.
 */

// Bailout, if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class Give_Payflexi_payment
{
    protected $loader;

    const API_QUERY_VAR = 'payflexi-give-api';

    /**
     * The unique identifier of this plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;


    public function __construct()
    {
        $this->plugin_name = 'give-payflexi';
        $this->load_dependencies();
        add_action('parse_request', array($this, 'handle_api_requests'));
        add_action('give_gateway_payflexi', array($this, 'give_process_payflexi_purchase'));
    }


    /**
     * Load the required dependencies for this plugin.
     *
     * @since  1.0.0
     * @access private
     */
    private function load_dependencies()
    {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-payflexi-loader.php';

        $this->loader = new Give_PayFlexi_Loader();
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since  1.0.0
     * @return Give_PayFlexi_Loader
     */
    public function get_loader()
    {
        return $this->loader;
    }

    public function handle_api_requests()
    {
        global $wp;
        if (!empty($_GET[Give_Payflexi_payment::API_QUERY_VAR])) { // WPCS: input var okay, CSRF ok.
            $wp->query_vars[Give_Payflexi_payment::API_QUERY_VAR] = sanitize_key(wp_unslash($_GET[Give_Payflexi_payment::API_QUERY_VAR])); // WPCS: input var okay, CSRF ok.

            $key = $wp->query_vars[Give_Payflexi_payment::API_QUERY_VAR];

            if ($key && ($key === 'verify') && isset($_GET['pf_cancelled'])) {
                $this->cancel_transaction();
                die();
            }

            if ($key && ($key === 'verify') && isset($_GET['pf_declined'])) {
                $this->decline_transaction();
                die();
            }

            if ($key && ($key === 'verify') && isset($_GET['pf_approved'])) {
                $this->verify_transaction();
                die();
            }
        }
    }

    /**
     * This action will run the function attached to it when it's time to process the donation
     * submission.
     **/
    public function give_process_payflexi_purchase($purchase_data)
    {
        // Make sure we don't have any left over errors present.
        give_clear_errors();

        // Any errors?
        $errors = give_get_errors();
        if (!$errors) {

            $form_id         = intval($purchase_data['post_data']['give-form-id']);
            $price_id        = !empty($purchase_data['post_data']['give-price-id']) ? $purchase_data['post_data']['give-price-id'] : 0;
            $donation_amount = !empty($purchase_data['price']) ? $purchase_data['price'] : 0;

            $payment_data = array(
                'price' => $donation_amount,
                'give_form_title' => $purchase_data['post_data']['give-form-title'],
                'give_form_id' => $form_id,
                'give_price_id' => $price_id,
                'date' => $purchase_data['date'],
                'user_email' => $purchase_data['user_email'],
                'purchase_key' => $purchase_data['purchase_key'],
                'currency' => give_get_currency(),
                'user_info' => $purchase_data['user_info'],
                'status' => 'pending',
                'gateway' => $purchase_data['gateway'],
            );

            // Record the pending payment
            $payment = give_insert_payment($payment_data);

            if (!$payment) {
                give_record_gateway_error(__('Payment Error', 'give-payflexi'), sprintf(__('Payment creation failed before sending donor to PayFlexi. Payment data: %s', 'give'), json_encode($payment_data)), $payment);
                // Problems? send back
                give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway'] . "&message=-some weird error happened-&payment_id=" . json_encode($payment));
            }

            // Auto set payment to abandoned in one hour if donor is not able to donate in that time.
            wp_schedule_single_event(current_time('timestamp', 1) + HOUR_IN_SECONDS, 'give_payflexi_set_donation_abandoned', array($payment));

            // Get Merchant Details.
            $merchant = give_payflexi_get_merchant_credentials();

            //Begin processing payment
            $first_name = isset($purchase_data['post_data']['give_first']) ? $purchase_data['post_data']['give_first'] : $purchase_data['user_info']['first_name'];
            $last_name  = isset($purchase_data['post_data']['give_last']) ? $purchase_data['post_data']['give_last'] : $purchase_data['user_info']['last_name'];
            $name    = $first_name . ' ' . $last_name;
            $ref = $purchase_data['purchase_key'];
            $currency = give_get_currency();

            $verify_url = home_url() . '?' . http_build_query(
                [
                    Give_Payflexi_payment::API_QUERY_VAR => 'verify',
                    'reference' => $ref,
                ]
            );

            $url = "https://api.payflexi.test/merchants/transactions";
            $fields = [
                'email' => $payment_data['user_email'],
                'name' => $name,
                'amount' => $payment_data['price'],
                'reference' => $ref,
                'callback_url' => $verify_url,
                'currency' => $currency,
                'domain' => 'global',
                'meta' => [
                    'title' => $payment_data['give_form_title'],
                    'donation_id' => $payment
                ]

            ];
            //open connection
            $ch = curl_init();

            //set the url, number of POST vars, POST data
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Authorization: Bearer " . $merchant['secret_key'],
                "Content-Type:  application/json",
                "Accept: application/json"
            ));

            //execute post
            $result = curl_exec($ch);
            curl_close($ch);

            $json_response = json_decode($result, true);

            if (!$json_response['errors']) {
                wp_redirect($json_response['checkout_url']);
                exit;
            } else {
                give_send_back_to_checkout("?payment-mode={$purchase_data['gateway']}&form-id={$purchase_data['post_data']['give-form-id']}");
            }
        } else {
            give_send_back_to_checkout("?payment-mode={$purchase_data['gateway']}&form-id={$purchase_data['post_data']['give-form-id']}");
        }
    }


    public function verify_transaction()
    {
        $ref = $_GET['reference'];
        $payment = give_get_payment_by('key', $ref);

        if ($payment === false) {
            die('not a valid ref');
        }

        $merchant = give_payflexi_get_merchant_credentials();

        $url = "https://api.payflexi.test/merchants/transactions/" . $ref;

        $headers = array(
            'Authorization' => 'Bearer ' . $merchant['secret_key'],
        );

        $args = array(
            'sslverify' => false, //Set to true on production
            'headers'    => $headers,
            'timeout'    => 60,
        );

        $request = wp_remote_get($url, $args);

        if (!is_wp_error($request) && 200 == wp_remote_retrieve_response_code($request)) {

            $body = wp_remote_retrieve_body($request);

            $result = json_decode($body);

            if (!$result->errors) {

                $payment_id   = absint($payment->ID);
                $transaction_id = $result->data->transaction_id;

                //Insert Donation Note.
                give_insert_payment_note(
                    $payment_id,
                    sprintf(
                        __('Transaction Successful. PayFlexi Transaction ID: %s', 'give-payflexi'),
                        $transaction_id
                    )
                );
                //Set Transaction ID to Processed Donation.
                give_set_payment_transaction_id($payment_id, $transaction_id);
                
                $payment_amount = give_donation_amount( $payment_id );
                $amount_paid    = $result->data->txn_amount ? $result->data->txn_amount : 0;

                if ($amount_paid < $payment_amount ) {
                    give_update_meta($payment_id, '_payflexi_installment_amount_paid', $amount_paid, '', 'donation');
                    give_update_payment_meta($payment_id,  '_give_payment_total', $amount_paid);
                    give_update_payment_status($payment_id, 'complete');
                    give_insert_payment_note($payment, 'Instalment Payment made: ' . $amount_paid);
                }else{
                    give_update_payment_status($payment_id, 'complete');
                }

                wp_redirect(give_get_success_page_uri());
                exit;
            } else {
                // the transaction was not successful, do not deliver value'
                give_update_payment_status($payment->ID, 'failed');
                give_insert_payment_note($payment, 'ERROR: ' . $result->data->message);
                echo json_encode(
                    [
                        'status' => 'not-given',
                        'message' => "Transaction was not successful: Last gateway response was: " . $result['data']['gateway_response'],
                    ]
                );
            }
        }
    }

    public function cancel_transaction()
    {
        $ref = $_GET['reference'];
        $payment = give_get_payment_by('key', $ref);

        if ($payment === false) {
            die('not a valid ref');
        }

        $payment_id   = absint($payment->ID);

        // Record Payment Gateway Error.
        give_record_gateway_error(
            __('Donor cancelled the donation %s', 'give-payflexi'),
            $payment_id
        );

        // Set Donation Status to Cancelled.
        give_update_payment_status($payment_id, 'cancelled');

        // Remove Scheduled Cron Hook, when donation is cancelled.
        wp_clear_scheduled_hook('give_payflexi_set_donation_abandoned', array($payment_id));

        // Send Donor to Failed Donation Page.
        wp_redirect(give_get_failed_transaction_uri());
    }

    public function decline_transaction()
    {
        $ref = $_GET['reference'];
        $payment = give_get_payment_by('key', $ref);

        if ($payment === false) {
            die('not a valid ref');
        }

        $payment_id   = absint($payment->ID);

        // Record Payment Gateway Error.
        give_record_gateway_error(
            __('Donor cancelled the donation %s', 'give-payflexi'),
            $payment_id
        );

        // Set Donation Status to Cancelled.
        give_update_payment_status($payment_id, 'failed');

        // Remove Scheduled Cron Hook, when donation is cancelled.
        wp_clear_scheduled_hook('give_payflexi_set_donation_abandoned', array($payment_id));

        // Send Donor to Failed Donation Page.
        wp_redirect(give_get_failed_transaction_uri());
    }
}
