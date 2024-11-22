<?php
if (!defined('ABSPATH')) {
    exit;
}

class WCSM_Admin {
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menus'], 99);
        add_action('admin_init', [$this, 'init_settings']);
        add_action('woocommerce_product_data_tabs', [$this, 'add_product_data_tab']);
        add_action('woocommerce_product_data_panels', [$this, 'add_product_data_panel']);
        add_action('woocommerce_process_product_meta', [$this, 'save_product_data']);
        add_action('admin_menu', [$this, 'add_log_submenu'], 101);
        add_action('wp_ajax_wcsm_save_product_data', [$this, 'ajax_save_product_data']);
        add_action('wp_ajax_wcsm_bulk_update', [$this, 'ajax_bulk_update']);
        add_action('admin_init', [$this, 'handle_export']);
    }

    public function add_admin_menus() {
        // Főmenü
        add_menu_page(
            __('Country Stock Manager', 'wc-country-stock-manager'),
            __('Country Stock', 'wc-country-stock-manager'),
            'manage_woocommerce',
            'wc-country-stock',
            [$this, 'render_settings_page'],
            'dashicons-store',
            56
        );

        // Almenük
        add_submenu_page(
            'wc-country-stock',
            __('Settings', 'wc-country-stock-manager'),
            __('Settings', 'wc-country-stock-manager'),
            'manage_woocommerce',
            'wc-country-stock',
            [$this, 'render_settings_page']
        );

        // Termék lista almenü
        add_submenu_page(
            'wc-country-stock',
            __('Products', 'wc-country-stock-manager'),
            __('Products', 'wc-country-stock-manager'),
            'manage_woocommerce',
            'wc-country-stock-products',
            [$this, 'render_products_page']
        );

        // Log almenü
        add_submenu_page(
            'wc-country-stock',
            __('Stock Log', 'wc-country-stock-manager'),
            __('Stock Log', 'wc-country-stock-manager'),
            'manage_woocommerce',
            'wc-country-stock-log',
            [$this, 'render_log_page']
        );
    }

    public function init_settings() {
        register_setting('wcsm_options', 'wcsm_managed_countries');
    }

    public function render_settings_page() {
        // Kontinensek és országok csoportosítása
        $continents = [
            'europe' => [
                'name' => __('Europe', 'wc-country-stock-manager'),
                'countries' => ['HU', 'RO', 'RS', 'HR', 'SK', 'SI', 'AT', 'CZ', 'PL', 'DE', 'IT', 'FR', 'ES', 'PT', 'GB', 'IE', 'NL', 'BE', 'DK', 'SE', 'NO', 'FI', 'EE', 'LV', 'LT', 'BG', 'GR', 'MT', 'CY']
            ],
            'asia' => [
                'name' => __('Asia', 'wc-country-stock-manager'),
                'countries' => ['CN', 'JP', 'KR', 'IN', 'ID', 'MY', 'SG', 'TH', 'VN', 'PH']
            ],
            'america' => [
                'name' => __('Americas', 'wc-country-stock-manager'),
                'countries' => ['US', 'CA', 'MX', 'BR', 'AR', 'CL', 'CO', 'PE']
            ],
            'other' => [
                'name' => __('Other Regions', 'wc-country-stock-manager'),
                'countries' => ['AU', 'NZ', 'ZA', 'AE', 'IL', 'TR', 'RU']
            ]
        ];
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Country Stock Manager Settings', 'wc-country-stock-manager'); ?></h1>
            
            <div class="notice notice-info">
                <p><?php _e('Select the countries you want to manage separate stock and prices for.', 'wc-country-stock-manager'); ?></p>
            </div>

            <style>
                .wcsm-continent {
                    margin-bottom: 30px;
                    background: #fff;
                    padding: 20px;
                    border-radius: 5px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                }
                .wcsm-continent h2 {
                    margin-top: 0;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #eee;
                    color: #23282d;
                }
                .wcsm-country-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 10px;
                    padding: 15px 0;
                }
                .wcsm-country-item {
                    padding: 5px;
                    background: #f8f9fa;
                    border-radius: 3px;
                }
                .wcsm-country-item:hover {
                    background: #f0f0f1;
                }
                .wcsm-country-item label {
                    display: block;
                    cursor: pointer;
                }
                .wcsm-country-item input[type="checkbox"] {
                    margin-right: 8px;
                }
            </style>

            <form method="post" action="options.php">
                <?php settings_fields('wcsm_options'); ?>
                
                <?php
                $all_countries = WC()->countries->get_countries();
                $managed_countries = get_option('wcsm_managed_countries', []);

                foreach ($continents as $continent_key => $continent) {
                    ?>
                    <div class="wcsm-continent">
                        <h2><?php echo esc_html($continent['name']); ?></h2>
                        <div class="wcsm-country-grid">
                            <?php
                            foreach ($continent['countries'] as $country_code) {
                                if (isset($all_countries[$country_code])) {
                                    ?>
                                    <div class="wcsm-country-item">
                                        <label>
                                            <input type="checkbox" 
                                                   name="wcsm_managed_countries[]" 
                                                   value="<?php echo esc_attr($country_code); ?>"
                                                   <?php checked(in_array($country_code, $managed_countries)); ?>>
                                            <?php echo esc_html($all_countries[$country_code]); ?>
                                        </label>
                                    </div>
                                    <?php
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>

                <?php submit_button(__('Save Selected Countries', 'wc-country-stock-manager')); ?>
            </form>
        </div>
        <?php
    }

    public function add_product_data_tab($tabs) {
        $tabs['country_stock'] = [
            'label' => __('Country Stock', 'wc-country-stock-manager'),
            'target' => 'country_stock_data',
            'class' => ['show_if_simple', 'show_if_variable'],
        ];
        return $tabs;
    }

    public function add_product_data_panel() {
        global $post;
        $managed_countries = get_option('wcsm_managed_countries', []);
        wp_nonce_field('wcsm_save_data', 'wcsm_nonce');
        ?>
        <div id="country_stock_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <?php
                foreach ($managed_countries as $country_code) {
                    $country_name = WC()->countries->get_countries()[$country_code];
                    $stock = WCSM_Product::get_country_stock($post->ID, $country_code);
                    $price = WCSM_Product::get_country_price($post->ID, $country_code);
                    ?>
                    <p class="form-field">
                        <label><?php echo esc_html($country_name); ?></label>
                        <span class="wrap">
                            <input type="number" 
                                   name="country_stock[<?php echo esc_attr($country_code); ?>]" 
                                   value="<?php echo esc_attr($stock); ?>"
                                   placeholder="<?php esc_attr_e('Stock', 'wc-country-stock-manager'); ?>">
                            <input type="number" 
                                   step="0.01" 
                                   name="country_price[<?php echo esc_attr($country_code); ?>]" 
                                   value="<?php echo esc_attr($price); ?>"
                                   placeholder="<?php esc_attr_e('Price', 'wc-country-stock-manager'); ?>">
                        </span>
                    </p>
                    <?php
                }
                ?>
            </div>
        </div>
        <?php
    }

    public function save_product_data($post_id) {
        if (!isset($_POST['wcsm_nonce']) || !wp_verify_nonce($_POST['wcsm_nonce'], 'wcsm_save_data')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_product', $post_id)) {
            return;
        }

        $country_stock = isset($_POST['country_stock']) ? $_POST['country_stock'] : [];
        $country_price = isset($_POST['country_price']) ? $_POST['country_price'] : [];

        foreach ($country_stock as $country_code => $stock) {
            WCSM_Product::update_country_stock($post_id, $country_code, $stock);
        }

        foreach ($country_price as $country_code => $price) {
            WCSM_Product::update_country_price($post_id, $country_code, $price);
        }
    }

    public function add_log_submenu() {
        add_submenu_page(
            'wc-country-stock',
            __('Stock Log', 'wc-country-stock-manager'),
            __('Stock Log', 'wc-country-stock-manager'),
            'manage_woocommerce',
            'wc-country-stock-log',
            [$this, 'render_log_page']
        );
    }

    public function render_log_page() {
        global $wpdb;
        
        // Get filters
        $country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        // Build query
        $query = "SELECT l.*, p.post_title as product_name 
                  FROM {$wpdb->prefix}wcsm_stock_log l
                  LEFT JOIN {$wpdb->posts} p ON p.ID = l.product_id
                  WHERE 1=1";
        
        if ($country) {
            $query .= $wpdb->prepare(" AND country_code = %s", $country);
        }
        if ($date_from) {
            $query .= $wpdb->prepare(" AND created_at >= %s", $date_from);
        }
        if ($date_to) {
            $query .= $wpdb->prepare(" AND created_at <= %s", $date_to);
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 100";
        
        $logs = $wpdb->get_results($query);
        $countries = WC()->countries->get_countries();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Stock Movement Log', 'wc-country-stock-manager'); ?></h1>
            
            <!-- Filters -->
            <div class="wcsm-log-filters">
                <form method="get">
                    <input type="hidden" name="page" value="wc-country-stock-log">
                    <select name="country">
                        <option value=""><?php _e('All Countries', 'wc-country-stock-manager'); ?></option>
                        <?php
                        foreach (get_option('wcsm_managed_countries', []) as $code) {
                            printf(
                                '<option value="%s" %s>%s</option>',
                                esc_attr($code),
                                selected($code, $country, false),
                                esc_html($countries[$code])
                            );
                        }
                        ?>
                    </select>
                    
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>" placeholder="From Date">
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>" placeholder="To Date">
                    
                    <button type="submit" class="button"><?php _e('Filter', 'wc-country-stock-manager'); ?></button>
                </form>
            </div>

            <!-- Log Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Date', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('Product', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('Country', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('Change Type', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('Stock Change', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('Price Change', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('User', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('Notes', 'wc-country-stock-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></td>
                            <td><?php echo esc_html($log->product_name); ?></td>
                            <td><?php echo esc_html($countries[$log->country_code] ?? $log->country_code); ?></td>
                            <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $log->change_type))); ?></td>
                            <td>
                                <?php if ($log->old_stock !== $log->new_stock): ?>
                                    <span class="stock-change <?php echo $log->new_stock > $log->old_stock ? 'positive' : 'negative'; ?>">
                                        <?php echo sprintf('%d → %d', $log->old_stock, $log->new_stock); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($log->old_price !== $log->new_price): ?>
                                    <?php echo sprintf('%s → %s', 
                                        wc_price($log->old_price), 
                                        wc_price($log->new_price)
                                    ); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($log->user_id) {
                                    $user = get_user_by('id', $log->user_id);
                                    echo esc_html($user ? $user->display_name : '#' . $log->user_id);
                                }
                                ?>
                            </td>
                            <td><?php echo esc_html($log->notes); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_products_page() {
        $managed_countries = get_option('wcsm_managed_countries', []);
        $all_countries = WC()->countries->get_countries();
        $selected_country = isset($_GET['country']) ? sanitize_text_field($_GET['country']) : reset($managed_countries);
        
        // Search/Filter parameters
        $search = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
        $category = isset($_GET['category']) ? intval($_GET['category']) : 0;
        $stock_status = isset($_GET['stock_status']) ? sanitize_text_field($_GET['stock_status']) : '';
        
        // Pagination settings
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        
        // Build query args
        $args = [
            'limit' => $per_page,
            'offset' => ($current_page - 1) * $per_page,
            'status' => 'publish',
            'paginate' => true
        ];

        if ($search) {
            $args['s'] = $search;
        }
        if ($category) {
            $args['category'] = [$category];
        }
        if ($stock_status) {
            $args['stock_status'] = $stock_status;
        }

        // Get paginated products
        $products_query = wc_get_products($args);
        $products = $products_query->products;
        $total_products = $products_query->total;
        $total_pages = ceil($total_products / $per_page);
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo esc_html__('Country Stock Products', 'wc-country-stock-manager'); ?></h1>
            
            <!-- Export Button -->
            <form method="post" class="wcsm-export-form" style="display: inline-block; margin-left: 10px;">
                <?php wp_nonce_field('wcsm_export', 'wcsm_export_nonce'); ?>
                <input type="hidden" name="action" value="wcsm_export">
                <input type="hidden" name="country" value="<?php echo esc_attr($selected_country); ?>">
                <button type="submit" class="page-title-action">
                    <?php _e('Export to CSV', 'wc-country-stock-manager'); ?>
                </button>
            </form>

            <!-- Filters -->
            <div class="wcsm-filters">
                <form method="get" id="product-filter-form">
                    <input type="hidden" name="page" value="wc-country-stock-products">
                    
                    <!-- Country Selector -->
                    <select name="country" id="country-selector">
                        <?php foreach ($managed_countries as $code): ?>
                            <option value="<?php echo esc_attr($code); ?>" 
                                    <?php selected($code, $selected_country); ?>>
                                <?php echo esc_html($all_countries[$code]); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Search -->
                    <input type="text" 
                           id="product-search" 
                           name="search" 
                           value="<?php echo esc_attr($search); ?>" 
                           placeholder="<?php esc_attr_e('Search products...', 'wc-country-stock-manager'); ?>">

                    <!-- Category Filter -->
                    <?php
                    wp_dropdown_categories([
                        'taxonomy' => 'product_cat',
                        'name' => 'category',
                        'show_option_all' => __('All Categories', 'wc-country-stock-manager'),
                        'selected' => $category
                    ]);
                    ?>

                    <!-- Stock Status -->
                    <select name="stock_status">
                        <option value=""><?php _e('All Stock Status', 'wc-country-stock-manager'); ?></option>
                        <option value="instock" <?php selected($stock_status, 'instock'); ?>>
                            <?php _e('In Stock', 'wc-country-stock-manager'); ?>
                        </option>
                        <option value="outofstock" <?php selected($stock_status, 'outofstock'); ?>>
                            <?php _e('Out of Stock', 'wc-country-stock-manager'); ?>
                        </option>
                    </select>

                    <button type="submit" class="button"><?php _e('Filter', 'wc-country-stock-manager'); ?></button>
                </form>
            </div>

            <!-- Bulk Actions -->
            <div class="wcsm-bulk-actions">
                <select id="bulk-action-selector">
                    <option value=""><?php _e('Bulk Actions', 'wc-country-stock-manager'); ?></option>
                    <option value="update_stock"><?php _e('Update Stock', 'wc-country-stock-manager'); ?></option>
                    <option value="update_price"><?php _e('Update Price', 'wc-country-stock-manager'); ?></option>
                </select>
                <input type="number" id="bulk-stock" placeholder="<?php esc_attr_e('Stock', 'wc-country-stock-manager'); ?>">
                <input type="number" step="0.01" id="bulk-price" placeholder="<?php esc_attr_e('Price', 'wc-country-stock-manager'); ?>">
                <button id="bulk-action-button" class="button"><?php _e('Apply', 'wc-country-stock-manager'); ?></button>
            </div>

            <!-- Products Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" id="select-all-products">
                        </th>
                        <th><?php _e('Product', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('SKU', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('Stock', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('Price', 'wc-country-stock-manager'); ?></th>
                        <th><?php _e('Actions', 'wc-country-stock-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ($products as $product) {
                        $stock = WCSM_Product::get_country_stock($product->get_id(), $selected_country);
                        $price = WCSM_Product::get_country_price($product->get_id(), $selected_country);
                        ?>
                        <tr>
                            <td class="check-column">
                                <input type="checkbox" class="product-select" value="<?php echo esc_attr($product->get_id()); ?>">
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($product->get_id()); ?>">
                                    <?php echo esc_html($product->get_name()); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($product->get_sku()); ?></td>
                            <td>
                                <input type="number" 
                                       class="stock-input" 
                                       data-product="<?php echo esc_attr($product->get_id()); ?>"
                                       data-country="<?php echo esc_attr($selected_country); ?>"
                                       value="<?php echo esc_attr($stock); ?>">
                            </td>
                            <td>
                                <input type="number" 
                                       step="0.01" 
                                       class="price-input"
                                       data-product="<?php echo esc_attr($product->get_id()); ?>"
                                       data-country="<?php echo esc_attr($selected_country); ?>"
                                       value="<?php echo esc_attr($price); ?>">
                            </td>
                            <td>
                                <button class="button save-row" 
                                        data-product="<?php echo esc_attr($product->get_id()); ?>">
                                    <?php _e('Save', 'wc-country-stock-manager'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>

            <!-- Add pagination after the table -->
            <div class="wcsm-pagination">
                <?php
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo; Previous'),
                    'next_text' => __('Next &raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page,
                    'type' => 'list'
                ]);
                ?>
                <p class="wcsm-pagination-info">
                    <?php
                    printf(
                        __('Showing %1$d-%2$d of %3$d products', 'wc-country-stock-manager'),
                        (($current_page - 1) * $per_page) + 1,
                        min($current_page * $per_page, $total_products),
                        $total_products
                    );
                    ?>
                </p>
            </div>
        </div>
        <?php
    }

    public function ajax_save_product_data() {
        check_ajax_referer('wcsm_nonce', 'security');

        $product_id = intval($_POST['product_id']);
        $country = sanitize_text_field($_POST['country']);
        $stock = intval($_POST['stock']);
        $price = floatval($_POST['price']);

        WCSM_Product::update_country_stock($product_id, $country, $stock);
        WCSM_Product::update_country_price($product_id, $country, $price);

        wp_send_json_success();
    }

    public function ajax_bulk_update() {
        check_ajax_referer('wcsm_nonce', 'security');

        $action = sanitize_text_field($_POST['bulk_action']);
        $country = sanitize_text_field($_POST['country']);
        $products = array_map('intval', $_POST['products']);
        
        foreach ($products as $product_id) {
            switch ($action) {
                case 'update_stock':
                    WCSM_Product::update_country_stock($product_id, $country, intval($_POST['stock']));
                    break;
                case 'update_price':
                    WCSM_Product::update_country_price($product_id, $country, floatval($_POST['price']));
                    break;
            }
        }

        wp_send_json_success();
    }

    public function handle_export() {
        if (!isset($_POST['action']) || $_POST['action'] !== 'wcsm_export') {
            return;
        }

        if (!isset($_POST['wcsm_export_nonce']) || !wp_verify_nonce($_POST['wcsm_export_nonce'], 'wcsm_export')) {
            wp_die('Invalid nonce');
        }

        $country = sanitize_text_field($_POST['country']);
        $products = wc_get_products(['limit' => -1]);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=country-stock-' . $country . '-' . date('Y-m-d') . '.csv');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        
        // CSV Header
        fputcsv($output, [
            'Product ID',
            'Product Name',
            'SKU',
            'Category',
            'Stock',
            'Price',
            'Regular Price',
            'Sale Price'
        ]);

        // CSV Data
        foreach ($products as $product) {
            $terms = get_the_terms($product->get_id(), 'product_cat');
            $category = $terms ? $terms[0]->name : '';
            
            fputcsv($output, [
                $product->get_id(),
                $product->get_name(),
                $product->get_sku(),
                $category,
                WCSM_Product::get_country_stock($product->get_id(), $country),
                WCSM_Product::get_country_price($product->get_id(), $country),
                $product->get_regular_price(),
                $product->get_sale_price()
            ]);
        }

        fclose($output);
        exit;
    }
}

// Initialize the admin class
$wcsm_admin = new WCSM_Admin();
