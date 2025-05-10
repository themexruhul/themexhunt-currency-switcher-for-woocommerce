<?php
class Themcusw_Currency_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'themcusw_add_settings_page']);
        add_action('admin_post_themcusw_save_currency_settings', [$this, 'themcusw_handle_save_settings']);
        add_action('admin_enqueue_scripts', [$this, 'themcusw_enqueue_admin_assets']);

        add_action('admin_menu', [$this, 'themcusw_add_nav_menu_metabox']);
        add_filter('wp_setup_nav_menu_item', [$this, 'themcusw_mark_currency_type_label']);
        add_filter('wp_setup_nav_menu_item', [$this, 'themcusw_setup_nav_menu_item']);
    }

    public function themcusw_add_settings_page() {
        add_options_page(
            esc_html__('Currency Switcher Settings', 'themexhunt-currency-switcher-for-woocommerce'),
            esc_html__('THEMCUSW', 'themexhunt-currency-switcher-for-woocommerce'),
            'manage_options',
            'themcusw-currency-switcher',
            [$this, 'settings_page']
        );
    }

    public function themcusw_enqueue_admin_assets($hook) {
        // Enqueue styles
        wp_enqueue_style('themcusw-admin-styles', THEMCUSW_CURRENCY_SWITCHER_PLUGIN_URL . 'assets/css/admin-style.css', [], '1.0.0', 'all');

        // Enqueue scripts
        wp_enqueue_script('themcusw-admin-script', THEMCUSW_CURRENCY_SWITCHER_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], '1.0.0', true);

        $currencies = get_woocommerce_currencies();

        $available_gateways = WC()->payment_gateways()->payment_gateways();

        $currencySymbols = [];
        foreach ($currencies as $key => $currency) {
            $currencySymbols[$key] = get_woocommerce_currency_symbol($key);
        }

        // Prepare available gateways for JavaScript
        $availableGateways = [];
        foreach ($available_gateways as $gateway_id => $gateway) {
            $availableGateways[] = [
                'id' => $gateway_id,
                'title' => $gateway->get_title()
            ];
        }

        // Encode the PHP arrays to JSON for JavaScript consumption
        $currencies = wp_json_encode($currencies);
        $currencySymbols = wp_json_encode($currencySymbols);
        $availableGateways = wp_json_encode($availableGateways);

        // Create the inline script that passes the PHP variables to JavaScript
        $inline_script = "
            window.currencies = $currencies;
            window.currencySymbols = $currencySymbols;
            window.availableGateways = $availableGateways;
        ";

        // Add the inline script after the script is enqueued
        wp_add_inline_script('themcusw-admin-script', $inline_script);
    }

    public function sanitize_manual_rates($input) {
        $clean_rates = [];

        foreach ($input as $data) {
            if (empty($data['code'])) continue;

            $code    = strtoupper(trim(sanitize_text_field($data['code'])));
            $rate    = isset($data['rate']) ? floatval($data['rate']) : 1.0;
            $symbol  = sanitize_text_field($data['symbol'] ?? '');
            $hidden  = !empty($data['hidden']) ? 1 : 0;
            $position = in_array($data['position'] ?? '', ['left', 'right', 'left_space', 'right_space']) ? $data['position'] : 'left';
            $gateways = isset($data['gateways']) ? $data['gateways'] : ['not-set'];
            $gateway = array_map('sanitize_text_field', (array) $gateways);

            $clean_rates[$code] = [
                'rate'     => $rate,
                'symbol'   => $symbol,
                'hidden'   => $hidden,
                'position' => $position,
                'gateways' => $gateway,
            ];
        }

        return $clean_rates;
    }
    public function themcusw_handle_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'themexhunt-currency-switcher-for-woocommerce'));
        }

        check_admin_referer('themcusw_save_currency_settings');

        $enabled_raw = filter_input(INPUT_POST, 'themcusw_currency_switcher_enable', FILTER_UNSAFE_RAW);
        $enabled = is_string($enabled_raw) ? esc_html( wp_strip_all_tags( trim($enabled_raw) ) ) : '';
        update_option('themcusw_currency_switcher_enable', $enabled);


        if ( ! empty( $_POST['themcusw_currency_switcher_manual_rates'] ) && is_array( $_POST['themcusw_currency_switcher_manual_rates'] ) ) {
            $sanitized_rates = map_deep( wp_unslash( $_POST['themcusw_currency_switcher_manual_rates'] ), 'sanitize_text_field' );
            update_option( 'themcusw_currency_switcher_manual_rates', $sanitized_rates );
        }


        // Escape and redirect to prevent header injection
        $redirect_url = esc_url_raw(add_query_arg([
            'settings-updated' => 'true',
            '_wpnonce'         => wp_create_nonce('tmxh_settings_updated'),
        ], admin_url('options-general.php?page=themcusw-currency-switcher')));

        wp_safe_redirect($redirect_url);
        exit;
    }




    public function settings_page() {
        $manual_rates = get_option('themcusw_currency_switcher_manual_rates', []);
        $currencies = get_woocommerce_currencies();
        $available_gateways = WC()->payment_gateways()->payment_gateways();
        ?>
        <div class="wrap tmxhnt-admin-wrap">
            <h1><?php esc_html_e('WooCommerce Currency Switcher Settings', 'themexhunt-currency-switcher-for-woocommerce'); ?></h1>

        <?php 
        $updated = false;

        if (isset($_GET['settings-updated'], $_GET['_wpnonce'])) {
            $nonce = sanitize_text_field(wp_unslash($_GET['_wpnonce']));
            $valid = wp_verify_nonce($nonce, 'tmxh_settings_updated');
            if ($valid && sanitize_text_field(wp_unslash($_GET['settings-updated'])) === 'true') {
                $updated = true;
            }
        }


        if ($updated) : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Settings saved successfully.', 'themexhunt-currency-switcher-for-woocommerce'); ?></p>
            </div>
        <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="themcusw_save_currency_settings">
                <?php do_settings_sections('themcusw_currency_switcher_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Enable Currency Switcher', 'themexhunt-currency-switcher-for-woocommerce'); ?></th>
                        <td>
                            <input type="checkbox" name="themcusw_currency_switcher_enable" value="1" <?php checked(get_option('themcusw_currency_switcher_enable'), 1); ?> />
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Manual Currency Rates', 'themexhunt-currency-switcher-for-woocommerce'); ?></h2>
                <?php wp_nonce_field('themcusw_save_currency_settings'); ?>
                <table class="form-table" id="manual-currency-table">
                    <thead>
                        <tr>
                          <th><?php esc_html_e('Hide?', 'themexhunt-currency-switcher-for-woocommerce'); ?></th>
                          <th><?php esc_html_e('Currency', 'themexhunt-currency-switcher-for-woocommerce'); ?></th>
                          <th><?php esc_html_e('Position', 'themexhunt-currency-switcher-for-woocommerce'); ?></th>
                          <th><?php esc_html_e('Rate', 'themexhunt-currency-switcher-for-woocommerce'); ?></th>
                          <th><?php esc_html_e('Symbol', 'themexhunt-currency-switcher-for-woocommerce'); ?></th>
                          <th><?php esc_html_e('Payment Gatway', 'themexhunt-currency-switcher-for-woocommerce'); ?></th>
                          <th><?php esc_html_e('Action', 'themexhunt-currency-switcher-for-woocommerce'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($manual_rates as $code => $data): ?>
                            <tr>
                                <td>
                                  <select name="themcusw_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][hidden]">
                                    <option value="0" <?php selected(empty($data['hidden'])); ?>>No</option>
                                    <option value="1" <?php selected(!empty($data['hidden'])); ?>>Yes</option>
                                  </select>
                                </td>
                                <td>
                                    <select name="themcusw_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][code]" class="currency-selector">
                                        <?php foreach ($currencies as $key => $currency): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($code, $key); ?>>
                                                <?php echo esc_html($currency); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                  <select name="themcusw_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][position]">
                                    <option value="left" <?php selected($data['position'] ?? '', 'left'); ?>>Left $99</option>
                                    <option value="right" <?php selected($data['position'] ?? '', 'right'); ?>>Right 99$</option>
                                  </select>
                                </td>
                                <td>
                                    <input type="text" name="themcusw_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][rate]" value="<?php echo esc_attr($data['rate']); ?>" />
                                </td>
                                <td>
                                    <input type="text" name="themcusw_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][symbol]" value="<?php echo esc_attr($data['symbol']); ?>" class="currency-symbol" />
                                </td>
                                <td>
                                    <?php foreach ($available_gateways as $gateway_id => $gateway): ?>
                                        <label style="display:block; margin-bottom:4px;">
                                            <input type="checkbox"
                                                   name="themcusw_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][gateways][]"
                                                   value="<?php echo esc_attr($gateway_id); ?>"
                                                   <?php
                                                   if (!empty($data['gateways']) && in_array($gateway_id, (array) $data['gateways'], true)) {
                                                       echo 'checked';
                                                   }
                                                   ?>>
                                            <?php echo esc_html($gateway->get_title()); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <button type="button" class="button remove-currency"><?php echo esc_html__('Remove', 'themexhunt-currency-switcher-for-woocommerce'); ?></button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <p>
                    <button type="button" class="button" id="add-currency-row"><?php echo esc_html__('Add Currency', 'themexhunt-currency-switcher-for-woocommerce'); ?></button>
                </p>

                <?php submit_button(); ?>

                <h2><?php echo esc_html__('Shortcode', 'themexhunt-currency-switcher-for-woocommerce'); ?></h2>
                <p>
                    <?php echo esc_html__('Use this shortcode anywhere:', 'themexhunt-currency-switcher-for-woocommerce'); ?>
                    <code id="currency-switcher-shortcode">[themcusw_currency_switcher]</code>
                    <button type="button" class="button" onclick="copyShortcode()">
                        <?php echo esc_html__('Copy', 'themexhunt-currency-switcher-for-woocommerce'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }




    public function themcusw_add_nav_menu_metabox() {
        add_meta_box(
            'themcusw-currency-switcher',
            esc_html__('Currency Switcher', 'themexhunt-currency-switcher-for-woocommerce'),
            [$this, 'render_nav_menu_metabox'],
            'nav-menus',
            'side',
            'default'
        );
    }

    public function render_nav_menu_metabox($object) {
        global $nav_menu_selected_id;

        $items = [
            '#tmxhcurrency#' => __('Currency Switcher', 'themexhunt-currency-switcher-for-woocommerce')
        ];

        $items_obj = [];
        foreach ($items as $value => $title) {
            $obj = new stdClass();
            $obj->db_id = 0;
            $obj->object = 'tmxhcurrency';
            $obj->object_id = $value;
            $obj->menu_item_parent = 0;
            $obj->type = 'custom';
            $obj->title = $title;
            $obj->url = $value;
            $obj->target = '';
            $obj->attr_title = '';
            $obj->classes = ['themcusw-currency-switcher-item'];
            $obj->xfn = '';

            $items_obj[$title] = $obj;
        }

        $walker = new Walker_Nav_Menu_Checklist([]);

        ?>
        <div id="tmxh-currency-links" class="tmxh-currency-linksdiv">
            <div id="tabs-panel-tmxh-currency-links-all" class="tabs-panel tabs-panel-active">
                <ul id="tmxh-currency-links-checklist" class="categorychecklist form-no-clear">
                    <?php echo walk_nav_menu_tree(array_map('wp_setup_nav_menu_item', $items_obj), 0, (object) ['walker' => $walker]); ?>
                </ul>
            </div>
            <p class="button-controls">
                <span class="add-to-menu">
                    <input type="submit" class="button-secondary submit-add-to-menu right"
                        value="<?php echo esc_attr__('Add to Menu', 'themexhunt-currency-switcher-for-woocommerce'); ?>"
                        name="add-tmxh-currency-links-menu-item" id="submit-tmxh-currency-links" />
                    <span class="spinner"></span>
                </span>
            </p>
        </div>
        <?php
    }


    public function themcusw_mark_currency_type_label($menu_item) {
        if (isset($menu_item->object, $menu_item->url) && 'custom' === $menu_item->object && $menu_item->url === '#tmxhcurrency#') {
            $menu_item->type_label = esc_html__('Currency Switcher', 'themexhunt-currency-switcher-for-woocommerce');
        }
        return $menu_item;
    }

    public function themcusw_setup_nav_menu_item($item) {
        if (!is_admin() && isset($item->url) && $item->url === '#tmxhcurrency#') {
            $item->url = '';
            $item->title = do_shortcode('[themcusw_currency_switcher]');
        }
        return $item;
    }




}
?>
