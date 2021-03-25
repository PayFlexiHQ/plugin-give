<?php
/**
 * Class Give_PayFlexi_Admin_Settings
 *
 * @since 1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Give_Payflexi_Admin_Settings' ) ) :

class Give_Payflexi_Admin_Settings {
	/**
	 * Instance.
	 *
	 * @since  1.0
	 * @access static
	 *
	 * @var object $instance
	 */
	static private $instance;

	/**
	 * Payment gateways ID
	 *
	 * @since 1.0
	 *
	 * @var string $gateways_id
	 */
	private $gateways_id = '';

	/**
	 * Payment gateways label
	 *
	 * @since 1.0
	 *
	 * @var string $gateways_label
	 */
	private $gateways_label = '';

	/**
	 * Singleton pattern.
	 *
	 * @since  1.0
	 * @access private
	 *
	 * Give_Payflexi_Admin_Settings constructor.
	 */
	private function __construct() {
	}

	/**
	 * Get instance.
	 *
	 * @since  1.0
	 * @access static
	 *
	 * @return static
	 */
	static function get_instance() {
		if ( null === static::$instance ) {
			self::$instance = new static();
		}

		return self::$instance;
	}

	/**
	 * Setup hooks
	 *
	 * @since  1.0
	 * @access public
	 */
	public function setup() {

		$this->gateways_id    = 'payflexi';
		$this->gateways_label = __( 'PayFlexi Flexible Checkout', 'give-payflexi' );

		// Add payment gateway to payment gateways list.
		add_filter( 'give_payment_gateways', array( $this, 'register_gateway' ) );

		if ( is_admin() ) {
			// Add section to payment gateways tab.
			add_filter( 'give_get_sections_gateways', array( $this, 'add_gateways_section' ) );
			
			// Add section settings.
			add_filter( 'give_get_settings_gateways', array( $this, 'add_settings' ) );
			add_action( 'give_admin_field_payflexi_webhooks', array( $this, 'webhook_field' ), 10, 2 );
		}

	}

	/**
	 * Registers the PayFlexi Payment Gateway.
	 *
	 * @param array $gateways Payment Gateways List.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function register_gateway( $gateways ) {
		$gateways[ $this->gateways_id ] = array(
			'admin_label'    => $this->gateways_label,
			'checkout_label' => give_payflexi_get_payment_method_label(),
		);

		return $gateways;
	}

	/**
	 * Add PayFlexi to payment gateway section
	 *
	 * @param array $section Payment Gateway Sections.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function add_gateways_section( $section ) {
		$section[ $this->gateways_id ] = __( 'PayFlexi', 'give-payflexi' );
		return $section;
	}


	/**
	 * Adds the PayFlexi Settings to the Payment Gateways.
	 *
	 * @param array $settings Payment Gateway Settings.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @return array
	 */
	public function add_settings( $settings ) {

		if ( $this->gateways_id !== give_get_current_setting_section() ) {
			return $settings;
		}

		$payflexi_settings = array(
			array(
				'id'   => $this->gateways_id,
				'type' => 'title',
			),
			array(
				'id'   => 'give_payflexi_test_public_key',
				'name' => __( 'Test Public Key', 'give-payflexi' ),
				'desc' => __( 'Test Public Key provided by PayFlexi', 'give-payflexi' ),
				'type' => 'api_key',
				'size' => 'regular',
			),
			array(
				'id'   => 'give_payflexi_test_secret_key',
				'name' => __( 'Test Secret Key', 'give-payflexi' ),
				'desc' => __( 'Test Secret Key provided by PayFlexi', 'give-payflexi' ),
				'type' => 'api_key',
				'size' => 'regular',
            ),
            array(
				'id'   => 'give_payflexi_live_public_key',
				'name' => __( 'Live Public Key', 'give-payflexi' ),
				'desc' => __( 'Live Public Key provided by PayFlexi', 'give-payflexi' ),
				'type' => 'api_key',
				'size' => 'regular',
			),
			array(
				'id'   => 'give_payflexi_live_secret_key',
				'name' => __( 'Live Secret Key', 'give-payflexi' ),
				'desc' => __( 'Live Secret Key provided by PayFlexi', 'give-payflexi' ),
				'type' => 'api_key',
				'size' => 'regular',
			),
			array(
				'title'        => __( 'PayFlexi Webhooks', 'give-payflexi' ),
				'desc'         => __( 'Webhooks are important to setup so that GiveWP can communicate properly with the payment gateway. Note: webhooks cannot be setup on localhost or websites in maintenance mode.', 'give-payflexi' ),
				'wrapper_class' => 'give-payflexi-webhooks-tr',
				'id'            => 'payflexi_webhooks',
				'type'          => 'payflexi_webhooks',
			),
			array(
				'title'       => __( 'Collect Billing Details', 'give-payflexi' ),
				'id'          => 'give_payflexi_billing_details',
				'type'        => 'radio_inline',
				'options'     => array(
					'enabled'  => __( 'Enabled', 'give-payflexi' ),
					'disabled' => __( 'Disabled', 'give-payflexi' ),
				),
				'default'     => 'disabled',
				'description' => __( 'This option will enable the billing details section for PayFlexi which requires the donor\'s address to complete the donation. These fields are not required by PayFlexi to process the transaction, but you may have the need to collect the data.', 'give-payflexi' ),
			),
			array(
				'id'   => $this->gateways_id,
				'type' => 'sectionend',
			),
		);

		return $payflexi_settings;
	}

	/**
	 * Webhook field.
	 *
	 * @param $value
	 * @param $option_value
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return mixed
	 */
	public function webhook_field( $value, $option_value ) {
		?>
		<tr valign="top" <?php echo ! empty( $value['wrapper_class'] ) ? 'class="' . $value['wrapper_class'] . '"' : ''; ?>>
			<th scope="row" class="titledesc">
				<label for=""><?php echo esc_attr( $value['title'] ); ?></label>
			</th>

			<td class="give-forminp give-forminp-api_key">
				<div class="give-payflexi-webhook-sync-wrap">
					<p class="give-payflexi-webhook-explanation" style="margin-bottom: 15px;">
						<?php
						esc_html_e( 'In order for PayFlexi to function properly, you must configure your webhooks.', 'give-payflexi' );
						echo sprintf(
						/* translators: 1. Webhook settings page. */
							__( ' You can  visit your <a href="%1$s" target="_blank">PayFlexi Merchant Dashboard</a> to add a new webhook. ', 'give-payflexi' ),
							esc_url_raw( 'https://merchant.payflexi.co/developers?tab=api-keys-integrations' )
						);
						esc_html_e( 'Please add a new webhook endpoint for the following URL:', 'give-payflexi' );
						?>
					</p>
					<p style="margin-bottom: 15px;">
						<strong><?php echo esc_html__( 'Webhook URL:', 'give-payflexi' ); ?></strong>
						<input style="width: 400px;" type="text" readonly="true" value="<?php echo site_url() . '/?give-listener=payflexi'; ?>"/>
					</p>
					<?php
					$webhook_received_on = give_get_option( 'give_payflexi_last_webhook_received_timestamp' );
					if ( ! empty( $webhook_received_on ) ) {
						$date_time_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
						?>
						<p>
							<strong><?php esc_html_e( 'Last webhook received on', 'give-payflexi' ); ?></strong>
                            <?php echo date_i18n( esc_html( $date_time_format ), $webhook_received_on ); ?>
						</p>
						<?php
					}
					?>
					<p>
						<?php
						echo sprintf(
							/* translators: 1. Documentation on webhook setup. */
							__( 'See our <a href="%1$s" target="_blank">documentation</a> for more information.', 'give-payflexi' ),
							esc_url_raw( 'http://developers.payflexi.co' )
						);
						?>
					</p>
				</div>

				<p class="give-field-description">
					<?php esc_attr( $value['desc'] ); ?>
				</p>
			</td>
		</tr>
		<?php
	}
}

endif;

// Initialize settings.
Give_Payflexi_Admin_Settings::get_instance()->setup();
