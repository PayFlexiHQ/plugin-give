<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Payflexi_Give
 * @subpackage Payflexi_Give/includes
 * @author     Payflexi <support@payflexi.co>
 */
class Payflexi_Give
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    Payflexi_Give_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
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

    /**
     * The current version of the plugin.
     *
     * @since  1.0.0
     * @access protected
     * @var    string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        if (defined('PLUGIN_NAME_VERSION')) {
            $this->version = PLUGIN_NAME_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'payflexi-give';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Payflexi_Give_Loader. Orchestrates the hooks of the plugin.
     * - Payflexi_Give_i18n. Defines internationalization functionality.
     * - Payflexi_Give_Admin. Defines all hooks for the admin area.
     * - Payflexi_Give_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
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
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-payflexi-give-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-payflexi-give-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin
         * area.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-payflexi-give-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        include_once plugin_dir_path(dirname(__FILE__)) . 'public/class-payflexi-give-public.php';

        $this->loader = new Payflexi_Give_Loader();

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since 1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since  1.0.0
     * @return string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since  1.0.0
     * @return Payflexi_Give_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since  1.0.0
     * @return string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Paystack_Give_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since  1.0.0
     * @access private
     */
    private function set_locale()
    {

        $plugin_i18n = new Payflexi_Give_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since  1.0.0
     * @access private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new Payflexi_Give_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Add menu
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');

        // Add Settings link to the plugin
        $plugin_basename = plugin_basename(plugin_dir_path(__DIR__) . $this->plugin_name . '.php');
        $this->loader->add_filter('plugin_action_links_' . $plugin_basename, $plugin_admin, 'add_action_links');

        /**
         * Register gateway so it shows up as an option in the Give gateway settings
         *
         * @param array $gateways
         *
         * @return array
         */
        function give_payflexi_register_gateway($gateways)
        {
            $gateways['payflexi'] = array(
                'admin_label' => esc_attr__('PayFlexi Flexible Checkout', 'payflexi-give'),
                'checkout_label' => esc_attr__('PayFlexi (Pay in Instalments)', 'payflexi-give'),
            );
            return $gateways;
        }

        add_filter('give_payment_gateways', 'give_payflexi_register_gateway', 1);

        function give_payflexi_settings($settings)
        {

            $check_settings = array(
                array(
                    'name' => __('PayFlexi Flexible Checkout', 'payflexi-give'),
                    'desc' => '',
                    'type' => 'give_title',
                    'id' => 'give_title_payflexi',
                ),
                array(
                    'name' => __('Test Secret Key', 'payflexi-give'),
                    'desc' => __('Enter your PayFlexi Test Secret Key', 'payflexi-give'),
                    'id' => 'payflexi_test_secret_key',
                    'type' => 'text',
                    'row_classes' => 'give-payflexi-test-secret-key',
                ),
                array(
                    'name' => __('Test Public Key', 'payflexi-give'),
                    'desc' => __('Enter your PayFlexi Test Public Key', 'payflexi-give'),
                    'id' => 'payflexi_test_public_key',
                    'type' => 'text',
                    'row_classes' => 'give-payflexi-test-public-key',
                ),
                array(
                    'name' => __('Live Secret Key', 'payflexi-give'),
                    'desc' => __('Enter your PayFlexi Live Secret Key', 'payflexi-give'),
                    'id' => 'payflexi_live_secret_key',
                    'type' => 'text',
                    'row_classes' => 'give-payflexi-live-secret-key',
                ),
                array(
                    'name' => __('Live Public Key', 'payflexi-give'),
                    'desc' => __('Enter your PayFlexi Live Public Key', 'payflexi-give'),
                    'id' => 'payflexi_live_public_key',
                    'type' => 'text',
                    'row_classes' => 'give-payflexi-live-public-key',
                ),
                array(
                    'name' => __('Billing Details', 'payflexi-give'),
                    'desc' => __('This will enable you to collect donor details. This is not required by PayFlexi (except email) but you might need to collect all information for record purposes', 'payflexi-give'),
                    'id' => 'payflexi_billing_details',
                    'type' => 'radio_inline',
                    'default' => 'disabled',
                    'options' => array(
                        'enabled' => __('Enabled', 'payflexi-give'),
                        'disabled' => __('Disabled', 'payflexi-give'),
                    ),
                ),
            );

            return array_merge($settings, $check_settings);
        }

        add_filter('give_settings_gateways', 'give_payflexi_settings');

        add_action('parse_request', array($this, 'handle_api_requests'), 0);

    }

    public function handle_api_requests()
    {

        global $wp;
        if (!empty($_GET[Payflexi_Give::API_QUERY_VAR])) { // WPCS: input var okay, CSRF ok.
            $wp->query_vars[Payflexi_Give::API_QUERY_VAR] = sanitize_key(wp_unslash($_GET[Payflexi_Give::API_QUERY_VAR])); // WPCS: input var okay, CSRF ok.

            $key = $wp->query_vars[Payflexi_Give::API_QUERY_VAR];

            if ($key && ($key === 'verify') && isset($_GET['pf_cancelled'])) {
                wp_redirect( give_get_failed_transaction_uri() );
            }

            if ($key && ($key === 'verify') && isset($_GET['pf_declined'])) {
                wp_redirect( give_get_failed_transaction_uri() );
            }
       
            if ($key && ($key === 'verify') && isset($_GET['pf_approved'])) {
                $this->verify_transaction();
                die();
            }

        }

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since  1.0.0
     * @access private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Payflexi_Give_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

        function give_payflexi_credit_card_form($form_id, $echo = true)
        {
            $billing_fields_enabled = give_get_option('payflexi_billing_details');

            if ($billing_fields_enabled == 'enabled') {
                do_action('give_after_cc_fields');
            } else {
                //Remove Address Fields if user has option enabled
                remove_action('give_after_cc_fields', 'give_default_cc_address_fields');
            }
            return $form_id;
        }
        add_action('give_payflexi_cc_form', 'give_payflexi_credit_card_form');

        /**
         * This action will run the function attached to it when it's time to process the donation
         * submission.
         **/
        function give_process_payflexi_purchase($purchase_data)
        {
            // Make sure we don't have any left over errors present.
            give_clear_errors();

            // Any errors?
            $errors = give_get_errors();
            if ( !$errors ) {

                $form_id         = intval( $purchase_data['post_data']['give-form-id'] );
                $price_id        = ! empty( $purchase_data['post_data']['give-price-id'] ) ? $purchase_data['post_data']['give-price-id'] : 0;
                $donation_amount = ! empty( $purchase_data['price'] ) ? $purchase_data['price'] : 0;

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
                    'gateway' => 'payflexi',
                );
    
                // Record the pending payment
                $payment = give_insert_payment($payment_data);
    			
                if (!$payment) {             
                    give_record_gateway_error(__('Payment Error', 'give'), sprintf(__('Payment creation failed before sending donor to PayFlexi. Payment data: %s', 'give'), json_encode($payment_data)), $payment);
                    // Problems? send back
                    give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']."&message=-some weird error happened-&payment_id=".json_encode($payment));
                } else {
				
                    //Begin processing payment
                    
                    if (give_is_test_mode()) {
                        $public_key = give_get_option('payflexi_test_public_key');
                        $secret_key = give_get_option('payflexi_test_secret_key');
                    } else {
                        $public_key = give_get_option('payflexi_live_public_key');
                        $secret_key = give_get_option('payflexi_live_secret_key');
                    }
    
                    $ref = $purchase_data['purchase_key']; // . '-' . time() . '-' . preg_replace("/[^0-9a-z_]/i", "_", $purchase_data['user_email']);
                    $currency = give_get_currency();
    
                    $verify_url = home_url() . '?' . http_build_query(
                        [
                            Payflexi_Give::API_QUERY_VAR => 'verify',
                            'reference' => $ref,
                        ]
                    );
					
				    $url = "https://api.payflexi.test/merchants/transactions";
                    $fields = [
                        'email' => $payment_data['user_email'],
                        'amount' => $payment_data['price'],
                        'reference' => $ref,
                        'callback_url' => $verify_url,
                        'currency'=> $currency,
                        'domain' => 'global',
                        'meta' => [
                            'title'=> $payment_data['give_form_title'],
                        ]
                        
                    ];
				    //open connection
				    $ch = curl_init();

                    //set the url, number of POST vars, POST data
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
                    curl_setopt($ch, CURLOPT_HEADER, false );
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        "Authorization: Bearer ". $secret_key,
                        "Content-Type:  application/json",
                        "Accept: application/json"
                    ));

				    //execute post
                    $result = curl_exec($ch);
                    curl_close( $ch );
           
                    $json_response = json_decode($result, true);
                    
                        if(!$json_response['errors']){
                            wp_redirect($json_response['checkout_url']);
                            exit;
                        }else{
                            give_send_back_to_checkout( '?payment-mode=payflexi'.'&error='.$json_response['message'] );
                        }
                }
    
            }else{
                give_send_back_to_checkout( '?payment-mode=payflexi'.'&errors='.json_encode($errors) );
            }
           
        }

        add_action('give_gateway_payflexi', 'give_process_payflexi_purchase');

    }

    public function Verify_transaction()
    {
        $ref = $_GET['reference'];
        $payment = give_get_payment_by('key', $ref);
        // die(json_encode($payment));

        if ($payment === false) {
            die('not a valid ref');
        }


        if (give_is_test_mode()) {
            $secret_key = give_get_option('payflexi_test_secret_key');
        } else {
            $secret_key = give_get_option('payflexi_live_secret_key');
        }

        $url = "https://api.payflexi.test/merchants/transactions/" . $ref;

        $args = array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key,
            ),
        );
        
        $request = wp_remote_get($url, $args);

        if (is_wp_error($request)) {
            return false; // Bail early
        }

        $body = wp_remote_retrieve_body($request);

        $result = json_decode($body);

        // var_dump($result);

        if (!$result->errors) {

            // the transaction was successful, you can deliver value
            
            give_update_payment_status($payment->ID, 'complete');

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
