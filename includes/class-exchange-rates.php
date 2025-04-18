<?php
/**
 * Class Tmxhnt_WC_Exchange_Rates
 * Handles fetching exchange rates and converting prices based on WooCommerce currency settings.
 */
class Tmxhnt_WC_Exchange_Rates {

    /**
     * Fetch exchange rates from an external API.
     *
     * @return array|false Exchange rates as an associative array or false if request fails.
     */
    public function get_rates() {
        $api_url = 'https://api.exchangerate-api.com/v4/latest/USD';

        // Fetch data using WordPress HTTP API
        $response = wp_remote_get($api_url);

        // Check for errors in the API response
        if (is_wp_error($response)) {
            return false;
        }

        // Decode the response JSON and return exchange rates
        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Convert a given price amount to the selected currency.
     *
     * @param float  $amount   The original price amount.
     * @param string $currency The target currency code.
     * @return float Converted price in the selected currency.
     */
    public function convert_price($amount, $currency) {
        // Check if manual exchange rates are enabled
        $manual_rates = get_option('wc_currency_switcher_manual_rates', []);

        // If manual rates are enabled and the currency exists in manual settings, use it
        if (isset($manual_rates[$currency])) {
            return $amount * floatval($manual_rates[$currency]);
        }

        // Otherwise, fetch live exchange rates
        $rates = $this->get_rates();

        // Return converted price if the exchange rate is available, else return the original amount
        return isset($rates['rates'][$currency]) ? $amount * $rates['rates'][$currency] : $amount;
    }
}
