<?php
/**
 * WooCommerce Currency Switcher Widget
 *
 * This widget allows users to switch currencies within WooCommerce.
 */
class Tmxhnt_WC_Currency_Widget extends WP_Widget {

    /**
     * Constructor - Initialize the widget
     */
    public function __construct() {
        parent::__construct(
            'wc_currency_widget', // Widget ID
            esc_html__('WooCommerce Currency Switcher', 'themexhunt-currency-switcher-for-woocommerce'),
            ['description' => esc_html__('A currency switcher for WooCommerce.', 'themexhunt-currency-switcher-for-woocommerce')] 
        );
    }

    /**
     * Display the widget content on the frontend
     *
     * @param array $args     Widget arguments.
     * @param array $instance Widget settings.
     */
    public function widget($args, $instance) {
        echo wp_kses_post($args['before_widget']); // Ensure safe output of widget wrapper
        echo wp_kses_post('<div class="widget-currency-switcher">' . do_shortcode('[wc_currency_switcher]') . '</div>');
        echo wp_kses_post($args['after_widget']); // Ensure safe output of widget wrapper
    }
}

/**
 * Register the WooCommerce Currency Switcher Widget
 */
function tmxhnt_register_wc_currency_widget() {
    register_widget('Tmxhnt_WC_Currency_Widget');
}
add_action('widgets_init', 'tmxhnt_register_wc_currency_widget');
