<?php
/**
 * Plugin Name: WC Country Stock Manager
 * Description: Manage WooCommerce stock levels and prices per country
 * Version: 1.0.0
 * Author: Bence Szorgalmatos
 * Author URI: https://github.com/szbence7
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Text Domain: wc-country-stock-manager
 * Domain Path: /languages
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WCSM_VERSION', '1.0.0');
define('WCSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCSM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Main plugin class
class WC_Country_Stock_Manager {
    private static $instance = null;
    private $admin;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'woocommerce_missing_notice']);
            return;
        }

        $this->includes();
        $this->init_hooks();
        $this->init_classes();
    }

    public function includes() {
        require_once WCSM_PLUGIN_DIR . 'includes/class-wcsm-install.php';
        require_once WCSM_PLUGIN_DIR . 'includes/class-wcsm-admin.php';
        require_once WCSM_PLUGIN_DIR . 'includes/class-wcsm-product.php';
    }

    public function init_hooks() {
        register_activation_hook(__FILE__, ['WCSM_Install', 'install']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function init_classes() {
        $this->admin = new WCSM_Admin();
    }

    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php _e('WC Country Stock Manager requires WooCommerce to be installed and active.', 'wc-country-stock-manager'); ?></p>
        </div>
        <?php
    }

    public function load_textdomain() {
        load_plugin_textdomain('wc-country-stock-manager', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_admin_assets($hook) {
        if ('woocommerce_page_wc-country-stock' === $hook || 'post.php' === $hook) {
            wp_enqueue_style('wcsm-admin', WCSM_PLUGIN_URL . 'assets/css/admin.css', [], WCSM_VERSION);
            wp_enqueue_script('wcsm-admin', WCSM_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], WCSM_VERSION, true);
        }
    }
}

// Initialize the plugin
function WCSM() {
    return WC_Country_Stock_Manager::instance();
}

WCSM();
