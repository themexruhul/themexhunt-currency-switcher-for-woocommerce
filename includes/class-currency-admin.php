<?php
class Tmxhnt_WC_Currency_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_post_tmxh_save_currency_settings', [$this, 'handle_save_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        add_action('admin_menu', [$this, 'add_nav_menu_metabox']);
        add_filter('wp_setup_nav_menu_item', [$this, 'mark_currency_type_label']);
        add_filter('wp_setup_nav_menu_item', [$this, 'setup_nav_menu_item']);
    }

    public function add_settings_page() {
        add_options_page(
            esc_html__('Currency Switcher Settings', 'themexhunt-currency-switcher-for-woocommerce'),
            esc_html__('TMXH Currency Switcher', 'themexhunt-currency-switcher-for-woocommerce'),
            'manage_options',
            'tmxhnt-currency-switcher',
            [$this, 'settings_page']
        );
    }

    public function sanitize_checkbox($value) {
        return (bool) $value;
    }

    public function enqueue_admin_assets($hook) {

        // Enqueue styles
        wp_enqueue_style('tmxhnt-admin-styles', TMXHNT_CURRENCY_SWITCHER_PLUGIN_URL . 'assets/css/admin-style.css', [], '1.0.0', 'all');

        // Enqueue scripts
        wp_enqueue_script('tmxhnt-admin-script', TMXHNT_CURRENCY_SWITCHER_PLUGIN_URL . 'assets/js/admin-script.js', ['jquery'], '1.0.0', true);
    }

    public function sanitize_manual_rates($input) {
        $clean = [];

        foreach ($input as $key => $data) {
            if (empty($data['code'])) continue;

            $code    = strtoupper(trim(sanitize_text_field($data['code'])));
            $rate    = isset($data['rate']) ? floatval($data['rate']) : 1.0;
            $symbol  = sanitize_text_field($data['symbol'] ?? '');
            $hidden  = !empty($data['hidden']) ? 1 : 0;
            $position = in_array($data['position'] ?? '', ['left', 'right']) ? $data['position'] : 'left';
            $gateway = array_map( 'sanitize_text_field', (array) $data['gateways'] );

            $clean_rates[$code] = [
                'rate'     => $rate,
                'symbol'   => $symbol,
                'hidden'   => $hidden,
                'position' => $position,
                'gateways' => $gateway,
            ];
        }

        return $clean_rates;




        return $clean;
    }
    public function handle_save_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to perform this action.', 'themexhunt-currency-switcher-for-woocommerce'));
        }

        check_admin_referer('tmxh_save_currency_settings');

        $enabled = isset($_POST['wc_currency_switcher_enable']) ? 1 : 0;
        update_option('wc_currency_switcher_enable', $enabled);

        $rates_raw = filter_input(INPUT_POST, 'wc_currency_switcher_manual_rates', FILTER_DEFAULT);

        if (is_array($rates_raw)) {
            $cleaned_rates = $this->sanitize_manual_rates(wp_unslash($rates_raw));
            update_option('wc_currency_switcher_manual_rates', $cleaned_rates);
        }

        wp_redirect(add_query_arg('settings-updated', 'true', admin_url('options-general.php?page=tmxhnt-currency-switcher')));
        exit;
    }
    public function settings_page() {
        $manual_rates = get_option('wc_currency_switcher_manual_rates', []);
        $currencies = get_woocommerce_currencies();
        $available_gateways = WC()->payment_gateways()->payment_gateways();
        ?>
        <div class="wrap tmxhnt-admin-wrap">
            <h1><?php esc_html_e('WooCommerce Currency Switcher Settings', 'themexhunt-currency-switcher-for-woocommerce'); ?></h1>

        <?php 
        $updated = filter_input(INPUT_GET, 'settings-updated', FILTER_SANITIZE_STRING);
        if ($updated === 'true') : ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Settings saved successfully.', 'themexhunt-currency-switcher-for-woocommerce'); ?></p>
            </div>
        <?php endif; ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="tmxh_save_currency_settings">
                <?php wp_nonce_field('tmxh_save_currency_settings'); ?>
                <?php do_settings_sections('wc_currency_switcher_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php echo esc_html__('Enable Currency Switcher', 'themexhunt-currency-switcher-for-woocommerce'); ?></th>
                        <td>
                            <input type="checkbox" name="wc_currency_switcher_enable" value="1" <?php checked(get_option('wc_currency_switcher_enable'), 1); ?> />
                        </td>
                    </tr>
                </table>

                <h2><?php echo esc_html__('Manual Currency Rates', 'themexhunt-currency-switcher-for-woocommerce'); ?></h2>
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
                                  <select name="wc_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][hidden]">
                                    <option value="0" <?php selected(empty($data['hidden'])); ?>>No</option>
                                    <option value="1" <?php selected(!empty($data['hidden'])); ?>>Yes</option>
                                  </select>
                                </td>
                                <td>
                                    <select name="wc_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][code]" class="currency-selector">
                                        <?php foreach ($currencies as $key => $currency): ?>
                                            <option value="<?php echo esc_attr($key); ?>" <?php selected($code, $key); ?>>
                                                <?php echo esc_html($currency); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                  <select name="wc_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][position]">
                                    <option value="left" <?php selected($data['position'] ?? '', 'left'); ?>>Left $99</option>
                                    <option value="right" <?php selected($data['position'] ?? '', 'right'); ?>>Right 99$</option>
                                  </select>
                                </td>
                                <td>
                                    <input type="text" name="wc_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][rate]" value="<?php echo esc_attr($data['rate']); ?>" />
                                </td>
                                <td>
                                    <input type="text" name="wc_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][symbol]" value="<?php echo esc_attr($data['symbol']); ?>" class="currency-symbol" />
                                </td>
                                <td>
                                    <select name="wc_currency_switcher_manual_rates[<?php echo esc_attr($code); ?>][gateways][]" multiple>

                                        <option value="">
                                            <?php esc_html_e('All Gateways', 'themexhunt-currency-switcher-for-woocommerce'); ?>
                                        </option>
                                        <?php foreach ($available_gateways as $gateway_id => $gateway): ?>
                                            <option value="<?php echo esc_attr($gateway_id); ?>" 
                                                    <?php if (!empty($data['gateways']) && in_array($gateway_id, (array) $data['gateways'], true)) echo 'selected'; ?>>
                                                <?php echo esc_html($gateway->get_title()); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
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

                <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const table = document.querySelector('#manual-currency-table tbody');
                    const addBtn = document.querySelector('#add-currency-row');
                    const currencies = <?php echo wp_json_encode($currencies); ?>;
                    const currencySymbols = {
                        <?php 
                        foreach ($currencies as $key => $currency) {
                            $symbol = get_woocommerce_currency_symbol($key);
                            echo "'" . esc_js($key) . "': '" . esc_js($symbol) . "',";
                        } 
                        ?>
                    };

                    function escapeHtml(text) {
                        return text
                            .replace(/&/g, "&amp;")
                            .replace(/</g, "&lt;")
                            .replace(/>/g, "&gt;")
                            .replace(/"/g, "&quot;")
                            .replace(/'/g, "&#039;");
                    }

                    function updateSymbol(selectElement) {
                        const selectedCurrency = selectElement.value;
                        const row = selectElement.closest('tr');
                        const symbolInput = row.querySelector('.currency-symbol');

                        if (currencySymbols[selectedCurrency]) {
                            symbolInput.value = currencySymbols[selectedCurrency];
                        }
                    }

                    document.querySelectorAll('.currency-selector').forEach(select => {
                        select.addEventListener('change', function () {
                            updateSymbol(this);
                        });
                    });

                    addBtn.addEventListener('click', function () {
                        const rowIndex = Date.now();
                        const newRow = document.createElement('tr');

                        let currencyOptions = '';
                        for (const key in currencies) {
                            const label = escapeHtml(currencies[key]);
                            const safeKey = escapeHtml(key);
                            currencyOptions += `<option value="${safeKey}">${label}</option>`;
                        }

                        newRow.innerHTML = `
                            <td>
                                <select name="wc_currency_switcher_manual_rates[row${rowIndex}][hidden]">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </td>
                            <td>
                                <select name="wc_currency_switcher_manual_rates[row${rowIndex}][code]" class="currency-selector">
                                    ${currencyOptions}
                                </select>
                            </td>
                            <td>
                                <select name="wc_currency_switcher_manual_rates[row${rowIndex}][position]">
                                    <option value="left">Left $99</option>
                                    <option value="left_space">Left with space $ 99</option>
                                    <option value="right">Right 99$</option>
                                    <option value="right_space">Right with space 99 $</option>
                                </select>
                            </td>
                            <td><input type="text" name="wc_currency_switcher_manual_rates[row${rowIndex}][rate]" /></td>
                            <td><input type="text" name="wc_currency_switcher_manual_rates[row${rowIndex}][symbol]" class="currency-symbol" /></td>
                            <td>
                                <select name="wc_currency_switcher_manual_rates[row${rowIndex}][gateways][]" multiple>
                                    <option value="">All Gateways</option>
                                    <?php foreach ($available_gateways as $gateway_id => $gateway): ?>
                                        <option value="<?php echo esc_attr($gateway_id); ?>">
                                            <?php echo esc_html($gateway->get_title()); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td><button type="button" class="button remove-currency">Remove</button></td>
                        `;
                        table.appendChild(newRow);

                        newRow.querySelector('.currency-selector').addEventListener('change', function () {
                            updateSymbol(this);
                        });
                    });

                    document.addEventListener('click', function (e) {
                        if (e.target.classList.contains('remove-currency')) {
                            e.target.closest('tr').remove();
                        }
                    });
                });

                </script>

                <?php submit_button(); ?>

                <h2><?php echo esc_html__('Shortcode', 'themexhunt-currency-switcher-for-woocommerce'); ?></h2>
                <p>
                    <?php echo esc_html__('Use this shortcode anywhere:', 'themexhunt-currency-switcher-for-woocommerce'); ?>
                    <code id="currency-switcher-shortcode">[wc_currency_switcher]</code>
                    <button type="button" class="button" onclick="copyShortcode()">
                        <?php echo esc_html__('Copy', 'themexhunt-currency-switcher-for-woocommerce'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }




    public function add_nav_menu_metabox() {
        add_meta_box(
            'tmxhnt-currency-switcher',
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
            $obj->classes = ['tmxhnt-currency-switcher-item'];
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


    public function mark_currency_type_label($menu_item) {
        if (isset($menu_item->object, $menu_item->url) && 'custom' === $menu_item->object && $menu_item->url === '#tmxhcurrency#') {
            $menu_item->type_label = esc_html__('Currency Switcher', 'themexhunt-currency-switcher-for-woocommerce');
        }
        return $menu_item;
    }

    public function setup_nav_menu_item($item) {
        if (!is_admin() && isset($item->url) && $item->url === '#tmxhcurrency#') {
            $item->url = '';
            $item->title = do_shortcode('[wc_currency_switcher]');
        }
        return $item;
    }




}
?>
