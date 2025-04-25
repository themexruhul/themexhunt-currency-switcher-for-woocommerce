<?php
/**
 * Plugin Name: ThemexHunt Currency Switcher for WooCommerce
 * Plugin URI:  https://github.com/themexruhul/themexhunt-currency-switcher-for-woocommerce
 * Description: A currency switcher for WooCommerce with manual rates and formatting options.
 * Version:     1.0.2
 * Author:      ThemexHunt
 * Author URI:  https://themexhunt.com/
 * License:     GPL2
 * Text Domain: themexhunt-currency-switcher-for-woocommerce
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define Plugin Constants
define('TMXHNT_CURRENCY_SWITCHER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('TMXHNT_CURRENCY_SWITCHER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TMXHNT_CURRENCY_SWITCHER_TEXT_DOMAIN', 'themexhunt-currency-switcher-for-woocommerce');

/**
 * Load Plugin Text Domain for Translations
 * This function allows the plugin to be translated into different languages.
 */
function tmxhnt_load_textdomain() {
    load_plugin_textdomain(TMXHNT_CURRENCY_SWITCHER_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'tmxhnt_load_textdomain');

/**
 * Check if WooCommerce is Installed and Activated
 * Displays an admin notice if WooCommerce is missing.
 *
 * @return bool True if WooCommerce is active, false otherwise.
 */
function tmxhnt_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo wp_kses(
                '<div class="error"><p><strong>' . esc_html__('ThemexHunt WooCommerce Currency Switcher', 'themexhunt-currency-switcher-for-woocommerce') . '</strong> ' . esc_html__('requires WooCommerce to be installed and activated.', 'themexhunt-currency-switcher-for-woocommerce') . '</p></div>',
                array(
                    'div'   => array('class' => true),
                    'p'     => array(),
                    'strong'=> array()
                )
            );
        });

        return false;
    }
    return true;
}

/**
 * Plugin Activation Hook
 * Ensures that WooCommerce is installed before activating the plugin.
 * If WooCommerce is missing, the plugin deactivates itself.
 */
function tmxhnt_currency_switcher_activate() {
    if (!tmxhnt_check_woocommerce()) {
        deactivate_plugins(plugin_basename(__FILE__)); // Deactivate if WooCommerce is missing
        wp_die(
            esc_html__('This plugin requires WooCommerce to be installed and activated.', 'themexhunt-currency-switcher-for-woocommerce'),
            esc_html__('Plugin Dependency Error', 'themexhunt-currency-switcher-for-woocommerce'),
            array('back_link' => true)
        );
    }

    // Store activation flag
    update_option('tmxhnt_currency_switcher_activated', true);
}
register_activation_hook(__FILE__, 'tmxhnt_currency_switcher_activate');

/**
 * Plugin Deactivation Hook
 * Removes stored settings or temporary options when the plugin is deactivated.
 */
function tmxhnt_currency_switcher_deactivate() {
    delete_option('tmxhnt_currency_switcher_activated');
}
register_deactivation_hook(__FILE__, 'tmxhnt_currency_switcher_deactivate');

// Require Necessary Files
require_once TMXHNT_CURRENCY_SWITCHER_PLUGIN_PATH . 'includes/class-currency-switcher.php';
require_once TMXHNT_CURRENCY_SWITCHER_PLUGIN_PATH . 'includes/class-exchange-rates.php';
require_once TMXHNT_CURRENCY_SWITCHER_PLUGIN_PATH . 'includes/class-currency-admin.php';
require_once TMXHNT_CURRENCY_SWITCHER_PLUGIN_PATH . 'includes/class-currency-widget.php';

/**
 * Initialize the Plugin
 * Ensures WooCommerce is active before initializing the plugin classes.
 */
function tmxhnt_init_wc_currency_switcher() {
    if (!tmxhnt_check_woocommerce()) {
        return;
    }
    
    // Initialize core plugin classes
    new Tmxhnt_WC_Currency_Switcher();
    new Tmxhnt_WC_Currency_Admin();
}
add_action('plugins_loaded', 'tmxhnt_init_wc_currency_switcher');
