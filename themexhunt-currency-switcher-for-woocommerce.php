<?php
/**
 * Plugin Name: ThemexHunt Currency Switcher for WooCommerce
 * Plugin URI:  https://themexhunt.com/themexhunt-currency-switcher-for-woocommerce/
 * Description: A currency switcher for WooCommerce with manual rates and formatting options.
 * Version:     1.0.1
 * Author:      ThemexHunt
 * Author URI:  https://themexhunt.com/
 * Requires Plugins: woocommerce
 * License:     GPL2
 * Text Domain: themexhunt-currency-switcher-for-woocommerce
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define Plugin Constants
define('THEMCUSW_CURRENCY_SWITCHER_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('THEMCUSW_CURRENCY_SWITCHER_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if WooCommerce is Installed and Activated
 * Displays an admin notice if WooCommerce is missing.
 *
 * @return bool True if WooCommerce is active, false otherwise.
 */
function themcusw_check_woocommerce() {
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
function themcusw_currency_switcher_activate() {
    if (!themcusw_check_woocommerce()) {
        deactivate_plugins(plugin_basename(__FILE__)); // Deactivate if WooCommerce is missing
        wp_die(
            esc_html__('This plugin requires WooCommerce to be installed and activated.', 'themexhunt-currency-switcher-for-woocommerce'),
            esc_html__('Plugin Dependency Error', 'themexhunt-currency-switcher-for-woocommerce'),
            array('back_link' => true)
        );
    }

    // Store activation flag
    update_option('themcusw_currency_switcher_activated', true);
}
register_activation_hook(__FILE__, 'themcusw_currency_switcher_activate');

/**
 * Plugin Deactivation Hook
 * Removes stored settings or temporary options when the plugin is deactivated.
 */
function themcusw_currency_switcher_deactivate() {
    delete_option('themcusw_currency_switcher_activated');
}
register_deactivation_hook(__FILE__, 'themcusw_currency_switcher_deactivate');

// Require Necessary Files
require_once THEMCUSW_CURRENCY_SWITCHER_PLUGIN_PATH . 'includes/class-currency-switcher.php';
require_once THEMCUSW_CURRENCY_SWITCHER_PLUGIN_PATH . 'includes/class-currency-admin.php';
require_once THEMCUSW_CURRENCY_SWITCHER_PLUGIN_PATH . 'includes/class-currency-widget.php';

/**
 * Initialize the Plugin
 * Ensures WooCommerce is active before initializing the plugin classes.
 */
function themcusw_currency_switcher_init() {
    if (!themcusw_check_woocommerce()) {
        return;
    }
    
    // Initialize core plugin classes
    new Themcusw_Currency_Switcher();
    new Themcusw_Currency_Admin();
}
add_action('plugins_loaded', 'themcusw_currency_switcher_init');

