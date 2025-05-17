<?php
/**
 * WooCommerce Currency Switcher Class
 *
 * Handles currency switching, price conversion, and currency symbol modifications.
 */
class Themcusw_Currency_Switcher {

    /**
     * Constructor - Hooks into WordPress and WooCommerce actions and filters.
     */
    public function __construct() {
        add_action('init', [$this, 'set_currency']); // Set the selected currency

        // Modify WooCommerce currency dynamically
        add_filter('woocommerce_currency', [$this, 'themcusw_modify_currency'], 99);

        // Convert product prices based on the selected currency
        add_filter('woocommerce_product_get_price', [$this, 'themcusw_convert_price'], 10, 2);
        add_filter('woocommerce_product_get_regular_price', [$this, 'themcusw_convert_price'], 10, 2);
        add_filter('woocommerce_product_get_sale_price', [$this, 'themcusw_convert_price'], 10, 2);
        add_filter('woocommerce_available_payment_gateways', [ $this, 'themcusw_filter_gateways_by_currency']);
        
        add_filter( 'pre_option_woocommerce_currency_pos', [ $this, 'themcusw_change_currency_position' ], 10, 2 );

        // Modify currency symbol
        add_filter('woocommerce_currency_symbol', [$this, 'themcusw_custom_currency_symbol'], 10, 2);

        // Register shortcode for currency switcher dropdown
        add_shortcode('themcusw_currency_switcher', [$this, 'themcusw_currency_switcher_shortcode']);

        // Enqueue styles and scripts
        add_action('wp_enqueue_scripts', [$this, 'themcusw_enqueue_assets']);
    }


    function themcusw_filter_gateways_by_currency($gateways) {
        if (is_admin()) {
            return $gateways;
        }

        $currency     = get_woocommerce_currency();
        $manual_rates = get_option('themcusw_currency_switcher_manual_rates', []);

        if (!isset($manual_rates[$currency])) {
            return $gateways;
        }

        $allowed_gateways = $manual_rates[$currency]['gateways'] ?? [];

        // Make sure it's an array
        if (!is_array($allowed_gateways)) {
            $allowed_gateways = explode(',', $allowed_gateways);
            $allowed_gateways = array_map('trim', $allowed_gateways);
        }

        foreach ($gateways as $key => $gateway) {
            if (!in_array($key, $allowed_gateways)) {
                unset($gateways[$key]);
            }
        }

        return $gateways;
    }



    /**
     * Enqueue CSS and JavaScript for the currency switcher
     */
    public function themcusw_enqueue_assets() {
        if (!is_admin()) {
            wp_enqueue_style(
                'themcusw-public-style',
                THEMCUSW_CURRENCY_SWITCHER_PLUGIN_URL . 'assets/css/currency-switcher.css',
                [],
                '1.0.1'
            );

            wp_enqueue_script(
                'themcusw-public-script',
                THEMCUSW_CURRENCY_SWITCHER_PLUGIN_URL . 'assets/js/currency-switcher.js',
                ['jquery'],
                '1.0.1',
                true
            );
            // Pass variables to the external JS file via wp_add_inline_script
            $tmxh_currency_nonce = wp_create_nonce('themcusw_currency_switcher');
            
            // Create a JavaScript variable to store the nonce
            $inline_script = "var tmxh_currency_nonce = '" . esc_js($tmxh_currency_nonce) . "';";
            
            // Add inline script to pass the nonce
            wp_add_inline_script('themcusw-public-script', $inline_script);
        }
    }

    /**
     * Set the selected currency in session and cookies
     */
    public function set_currency() {
        // Get and sanitize input
        $currency = isset($_GET['currency']) ? sanitize_text_field(wp_unslash($_GET['currency'])) : '';
        $nonce = isset($_GET['currency_switcher_nonce']) ? sanitize_text_field(wp_unslash($_GET['currency_switcher_nonce'])) : '';

        // Allow currency change only if nonce is valid
        if ($currency && $nonce && wp_verify_nonce($nonce, 'themcusw_currency_switcher')) {
            setcookie('selected_currency', $currency, time() + MONTH_IN_SECONDS, '/');
            $_SESSION['selected_currency'] = $currency;
            return;
        }
        if (isset($_COOKIE['selected_currency'])) {
            $_SESSION['selected_currency'] = sanitize_text_field(wp_unslash($_COOKIE['selected_currency']));
        }
    }




    /**
     * Convert product prices based on the selected currency
     *
     * @param float $price Product price in base currency
     * @param object $product WooCommerce product object
     * @return float Converted price
     */
    public function themcusw_convert_price($price, $product) {
        if (is_admin() || !$price) {
            return $price;
        }

        $selected_currency = isset($_SESSION['selected_currency']) ? sanitize_text_field(wp_unslash($_SESSION['selected_currency'])) : get_woocommerce_currency();
        $manual_rates = get_option('themcusw_currency_switcher_manual_rates', []);

        // Use manual exchange rates if auto conversion is disabled
        if (isset($manual_rates[$selected_currency]['rate'])) {
            return $price * floatval($manual_rates[$selected_currency]['rate']);
        }

        return $price;
    }

    /**
     * Modify WooCommerce currency symbol based on selected currency
     *
     * @param string $symbol Default currency symbol
     * @param string $currency Currency code
     * @return string Modified currency symbol
     */
    public function themcusw_custom_currency_symbol($symbol, $currency) {
        $manual_rates = get_option('themcusw_currency_switcher_manual_rates', []);
        if (isset($manual_rates[$currency]['symbol']) && !empty($manual_rates[$currency]['symbol'])) {
            return sanitize_text_field($manual_rates[$currency]['symbol']);
        }
        return $symbol;
    }

    /**
     * Modify WooCommerce currency dynamically
     *
     * @param string $currency Default WooCommerce currency
     * @return string Modified currency based on session, cookie, or GeoIP
     */
    public function themcusw_modify_currency($currency) {
        // Priority 1: Session (user-selected currency)
        if (!empty($_SESSION['selected_currency'])) {
            return sanitize_text_field(wp_unslash($_SESSION['selected_currency']));
        }

        // Priority 2: Cookie (persisted currency)
        if (!empty($_COOKIE['selected_currency'])) {
            return sanitize_text_field(wp_unslash($_COOKIE['selected_currency']));
        }

        // Fallback: WooCommerce default currency
        return $currency;
    }

    /**
     * Shortcode to display a currency switcher dropdown
     *
     * @return string HTML dropdown for selecting currency
     */
        public function themcusw_currency_switcher_shortcode() {
            if (!get_option('themcusw_currency_switcher_enable')) {
                return ''; // Return empty if switcher is disabled
            }

            $manual_rates = get_option('themcusw_currency_switcher_manual_rates', []);
            if (empty($manual_rates)) {
                return ''; // Return empty if no manual rates exist
            }

            ob_start();
            ?>
            <form method="get" action=""> <!-- Escape URL -->
                <?php wp_nonce_field('themcusw_currency_switcher', 'currency_switcher_nonce'); ?>
                <select id="currency-switcher" name="currency" onchange="this.form.submit();">
                    <?php foreach ($manual_rates as $currency => $data) : 
                        if (!empty($data['hidden'])) { continue; }
                        $selected_currency = isset($_SESSION['selected_currency']) ? sanitize_text_field(wp_unslash($_SESSION['selected_currency'])) : '';
                        ?>
                        <option value="<?php echo esc_attr($currency); ?>" 
                            <?php selected($selected_currency, $currency); ?>>
                            <?php echo esc_html($currency); ?> <!-- Escape the currency output -->
                        </option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php
            return ob_get_clean();
        }


    function themcusw_change_currency_position(){
        $currency = get_woocommerce_currency();
        $rates = get_option('themcusw_currency_switcher_manual_rates', []);
        $position = $rates[$currency]['position'] ?? 'left';
        if( !$rates ) {
            return false;
        }

        if ( 'left' == $position ){
            return 'left';
        } elseif ( 'right' == $position ){
            return 'right';
        } 
    }

}
?>
