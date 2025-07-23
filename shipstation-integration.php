<?php
/**
 * Plugin Name: ShipStation Integration
 * Description: Unlock ShipStation orders to allow clients to access their own orders and make updates without needing to give full permissions inside of ShipStation.
 * Version: 1.1
 * Author: ShipsterPro
 */

if (!defined('ABSPATH')) exit;

// Load core logic
require_once plugin_dir_path(__FILE__) . 'includes/shipstation-core.php';

// Load assets (JS and CSS)
add_action('wp_enqueue_scripts', 'shipstation_enqueue_assets');
function shipstation_enqueue_assets() {
    wp_enqueue_style(
        'shipstation-integration-style',
        plugin_dir_url(__FILE__) . 'assets/css/style.css'
    );

    wp_enqueue_script(
        'shipstation-integration-script',
        plugin_dir_url(__FILE__) . 'assets/js/script.js',
        ['jquery'],
        null,
        true
    );

    wp_localize_script('shipstation-integration-script', 'shipstation_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('shipstation_nonce')
    ]);
}