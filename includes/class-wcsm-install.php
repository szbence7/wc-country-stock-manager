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

        $collate = $wpdb->get_charset_collate();

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

        // New log table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wcsm_stock_log (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT UNSIGNED NOT NULL,
            country_code VARCHAR(2) NOT NULL,
            old_stock INT NOT NULL,
            new_stock INT NOT NULL,
            old_price DECIMAL(19,4) NULL,
            new_price DECIMAL(19,4) NULL,
            change_type VARCHAR(50) NOT NULL,
            order_id BIGINT UNSIGNED NULL,
            user_id BIGINT UNSIGNED NULL,
            ip_address VARCHAR(100) NULL,
            created_at DATETIME NOT NULL,
            notes TEXT NULL,
            PRIMARY KEY (id),
            KEY product_country (product_id, country_code),
            KEY created_at (created_at)
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
