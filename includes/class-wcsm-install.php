<?php
if (!defined('ABSPATH')) {
    exit;
}

class WCSM_Install {
    public static function install() {
        $current_version = get_option('wcsm_version', '0');
        
        self::create_tables();
        self::create_options();
        
        update_option('wcsm_version', WCSM_VERSION);
        
        if (version_compare($current_version, WCSM_VERSION, '<')) {
            self::update_plugin();
        }
    }

    private static function create_tables() {
        global $wpdb;

        $wpdb->hide_errors();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $collate = '';
        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }

        // Country stock table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wcsm_country_stock (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            country_code VARCHAR(2) NOT NULL,
            stock_quantity INT NOT NULL DEFAULT 0,
            price DECIMAL(19,4) NULL,
            sale_price DECIMAL(19,4) NULL,
            stock_status VARCHAR(20) NOT NULL DEFAULT 'instock',
            PRIMARY KEY  (id),
            KEY product_country (product_id, country_code)
        ) $collate;";

        dbDelta($sql);
    }

    private static function create_options() {
        add_option('wcsm_version', WCSM_VERSION);
        add_option('wcsm_managed_countries', []);
    }

    private static function update_plugin() {
        global $wpdb;
        
        // Example: Add new column if upgrading to version that needs it
        // $wpdb->query("ALTER TABLE {$wpdb->prefix}wcsm_country_stock ADD COLUMN new_column...");
    }
}
