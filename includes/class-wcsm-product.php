<?php
if (!defined('ABSPATH')) {
    exit;
}

class WCSM_Product {
    public function __construct() {
        add_filter('woocommerce_product_get_price', [$this, 'get_country_specific_price'], 10, 2);
        add_filter('woocommerce_product_get_stock_quantity', [$this, 'get_country_specific_stock'], 10, 2);
    }

    public function get_country_specific_price($price, $product) {
        $country_code = $this->get_current_country_code();
        $country_price = self::get_country_price($product->get_id(), $country_code);
        return $country_price !== null ? $country_price : $price;
    }

    public function get_country_specific_stock($quantity, $product) {
        $country_code = $this->get_current_country_code();
        $country_stock = self::get_country_stock($product->get_id(), $country_code);
        return $country_stock !== null ? $country_stock : $quantity;
    }

    private function get_current_country_code() {
        if (function_exists('trp_get_current_language')) {
            $language_code = trp_get_current_language();
            $country_map = $this->get_country_code_map();
            return isset($country_map[$language_code]) ? $country_map[$language_code] : strtoupper($language_code);
        }
        return WC()->countries->get_base_country();
    }

    private function get_country_code_map() {
        return [
            'hu' => 'HU',
            'ro' => 'RO',
            'sr' => 'RS',
        ];
    }

    public static function get_country_stock($product_id, $country_code) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT stock_quantity FROM {$wpdb->prefix}wcsm_country_stock 
            WHERE product_id = %d AND country_code = %s",
            $product_id,
            $country_code
        ));
    }

    public static function get_country_price($product_id, $country_code) {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT price FROM {$wpdb->prefix}wcsm_country_stock 
            WHERE product_id = %d AND country_code = %s",
            $product_id,
            $country_code
        ));
    }

    public static function update_country_stock($product_id, $country_code, $stock) {
        global $wpdb;
        $existing = self::get_country_stock($product_id, $country_code);

        if ($existing === null) {
            $wpdb->insert(
                $wpdb->prefix . 'wcsm_country_stock',
                [
                    'product_id' => $product_id,
                    'country_code' => $country_code,
                    'stock_quantity' => $stock
                ],
                ['%d', '%s', '%d']
            );
        } else {
            $wpdb->update(
                $wpdb->prefix . 'wcsm_country_stock',
                ['stock_quantity' => $stock],
                [
                    'product_id' => $product_id,
                    'country_code' => $country_code
                ],
                ['%d'],
                ['%d', '%s']
            );
        }
    }

    public static function update_country_price($product_id, $country_code, $price) {
        global $wpdb;
        $existing = self::get_country_price($product_id, $country_code);

        if ($existing === null) {
            $wpdb->insert(
                $wpdb->prefix . 'wcsm_country_stock',
                [
                    'product_id' => $product_id,
                    'country_code' => $country_code,
                    'price' => $price
                ],
                ['%d', '%s', '%f']
            );
        } else {
            $wpdb->update(
                $wpdb->prefix . 'wcsm_country_stock',
                ['price' => $price],
                [
                    'product_id' => $product_id,
                    'country_code' => $country_code
                ],
                ['%f'],
                ['%d', '%s']
            );
        }
    }
}

new WCSM_Product();
