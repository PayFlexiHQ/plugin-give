<?php

/**
 * Plugin Name: PayFlexi Instalment Payment Gateway for Give
 * Plugin URI: http://developers.payflexi.co
 * Description: PayFlexi Flexible Checkout Payment for Give allows site to accept installment payments for donations from their customers, anywhere on the Give plugin platform. Accept payments via Stripe, PayStack, Flutterwave, and more. 
 * Version:1.0.0
 * Author: PayFlexi
 * Author URI: https://payflexi.co
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: give-payflexi
 * Domain Path: /languages
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

final class Give_PayFlexi
{

    /**
     * Instance.
     *
     * @since  1.0
     * @access static
     * @var
     */
    static private $instance;

    /**
     * Singleton pattern.
     *
     * @since  1.0
     * @access private
     */
    private function __construct()
    { }


    /**
     * Get instance.
     *
     * @since  1.0
     * @access static
     *
     * @return static
     */
    static function get_instance()
    {
        if (null === static::$instance) {
            self::$instance = new static();
            self::$instance->setup();
        }

        return self::$instance;
    }

    /**
     * Setup Give PayFlexi.
     *
     * @since  1.0.0
     * @access private
     */
    private function setup()
    {

        // Setup constants.
        $this->setup_constants();

        // Give init hook.
        add_action('plugins_loaded', array($this, 'init'), 10);
    }

    /**
     * Init the plugin in give_init so environment variables are set.
     *
     * @since 1.0.0
     */
    public function init()
    {

        if (is_admin()) {
            // Process plugin activation.
            require_once GIVE_PAYFLEXI_DIR . 'includes/admin/plugin-activation.php';
        }

        $this->load_files();
        $this->payments = new Give_Payflexi_payment();
        $this->setup_hooks();
        $this->load_textdomain();
    }

    /**
     * Setup constants.
     *
     * @since  1.0
     * @access public
     *
     * @return Give_PayFlexi
     */
    public function setup_constants()
    {

        if (!defined('GIVE_PAYFLEXI_VERSION')) {
            define('GIVE_PAYFLEXI_VERSION', '1.0.2');
        }

        if (!defined('GIVE_PAYFLEXI_MIN_GIVE_VER')) {
            define('GIVE_PAYFLEXI_MIN_GIVE_VER', '2.1.0');
        }

        if (!defined('GIVE_PAYFLEXI_FILE')) {
            define('GIVE_PAYFLEXI_FILE', __FILE__);
        }

        if (!defined('GIVE_PAYFLEXI_BASENAME')) {
            define('GIVE_PAYFLEXI_BASENAME', plugin_basename(GIVE_PAYFLEXI_FILE));
        }

        if (!defined('GIVE_PAYFLEXI_URL')) {
            define('GIVE_PAYFLEXI_URL', plugins_url('/', GIVE_PAYFLEXI_FILE));
        }

        if (!defined('GIVE_PAYFLEXI_DIR')) {
            define('GIVE_PAYFLEXI_DIR', plugin_dir_path(GIVE_PAYFLEXI_FILE));
        }
    }

    /**
     * Load files
     *
     * @since  1.0
     * @access public
     */
    public function load_files()
    {

        // Load helper functions.
        require_once GIVE_PAYFLEXI_DIR . 'includes/functions.php';

        // Load plugin settings.
        require_once GIVE_PAYFLEXI_DIR . 'includes/admin/class-admin-settings.php';

        // Process payment
        require_once GIVE_PAYFLEXI_DIR . 'includes/class-payflexi-payment.php';
        require_once GIVE_PAYFLEXI_DIR . 'includes/class-payflexi-webhooks.php';

        // Load frontend actions.
        require_once GIVE_PAYFLEXI_DIR . 'includes/actions.php';
        //require_once GIVE_PAYFLEXI_DIR . 'includes/filters.php';

        return self::$instance;
    }

    /**
     * Setup hooks.
     *
     * @since  1.0
     * @access public
     * @return Give_PayFlexi
     */
    public function setup_hooks()
    {
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue'));

        return self::$instance;
    }


    /**
     * Load the text domain.
     *
     * @access private
     * @since  1.0
     *
     * @return void
     */
    public function load_textdomain()
    {

        // Set filter for plugin's languages directory.
        $lang_dir = dirname(GIVE_PAYFLEXI_BASENAME) . '/languages/';
        $lang_dir = apply_filters('give_payflexi_languages_directory', $lang_dir);

        // Traditional WordPress plugin locale filter.
        $locale  = apply_filters('plugin_locale', get_locale(), 'give-payflexi');
        $mo_file = sprintf('%1$s-%2$s.mo', 'give-payflexi', $locale);

        // Setup paths to current locale file.
        $local_mo_file  = $lang_dir . $mo_file;
        $global_mo_file = WP_LANG_DIR . '/give-payflexi/' . $mo_file;

        if (file_exists($global_mo_file)) {
            load_textdomain('give-payflexi', $global_mo_file);
        } elseif (file_exists($local_mo_file)) {
            load_textdomain('give-payflexi', $local_mo_file);
        } else {
            // Load the default language files.
            load_plugin_textdomain('give-payflexi', false, $lang_dir);
        }
    }

    /**
     * Load frontend scripts
     *
     * @since  1.0.0
     * @access public
     *
     * @return void
     */
    public function frontend_enqueue()
    { }
}


if (!function_exists('Give_PayFlexi')) {
    function Give_PayFlexi()
    {
        return Give_PayFlexi::get_instance();
    }

    Give_PayFlexi();
}
