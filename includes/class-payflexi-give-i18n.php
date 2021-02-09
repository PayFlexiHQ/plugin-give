<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link  https://payflexi.co
 * @since 1.0.0
 *
 * @package    Payflexi_Give
 * @subpackage Payflexi_Give/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Payflexi_Give
 * @subpackage Payflexi_Give/includes
 * @author     Payflexi <support@payflexi.co>
 */
class Payflexi_Give_i18n
{


    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() 
    {

        load_plugin_textdomain(
            'payflexi-give',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );

    }



}
