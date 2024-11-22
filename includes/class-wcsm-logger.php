<?php
if (!defined('ABSPATH')) {
    exit;
}

class WCSM_Logger {
    public static function log_stock_change($product_id, $country_code, $old_stock, $new_stock, $change_type, $order_id = null) {
        global $wpdb;
        
        $data = [
            'product_id' => $product_id,
            'country_code' => $country_code,
            'old_stock' => $old_stock,
            'new_stock' => $new_stock,
            'change_type' => $change_type,
            'order_id' => $order_id,
            'user_id' => get_current_user_id(),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'created_at' => current_time('mysql'),
            'notes' => self::get_change_note($change_type, $old_stock, $new_stock, $order_id)
        ];
        
        $wpdb->insert($wpdb->prefix . 'wcsm_stock_log', $data);
    }

    public static function log_price_change($product_id, $country_code, $old_price, $new_price) {
        global $wpdb;
        
        $data = [
            'product_id' => $product_id,
            'country_code' => $country_code,
            'old_price' => $old_price,
            'new_price' => $new_price,
            'change_type' => 'price_update',
            'user_id' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'notes' => sprintf('Price updated from %s to %s', $old_price, $new_price)
        ];
        
        $wpdb->insert($wpdb->prefix . 'wcsm_stock_log', $data);
    }

    private static function get_change_note($type, $old_stock, $new_stock, $order_id) {
        switch ($type) {
            case 'order_placed':
                return sprintf('Order #%d reduced stock from %d to %d', $order_id, $old_stock, $new_stock);
            case 'manual_update':
                return sprintf('Manual stock update from %d to %d', $old_stock, $new_stock);
            case 'order_cancelled':
                return sprintf('Order #%d cancelled, stock restored from %d to %d', $order_id, $old_stock, $new_stock);
            default:
                return sprintf('Stock changed from %d to %d', $old_stock, $new_stock);
        }
    }
}
