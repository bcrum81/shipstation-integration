<?php
if (!defined('ABSPATH')) exit;

/**
 * Save API key for user and return list of stores
 */
add_action('wp_ajax_shipstation_save_api', function () {
    check_ajax_referer('shipstation_nonce', 'nonce');
    $user_id = get_current_user_id();
    $key = sanitize_text_field($_POST['key']);

    if (!$user_id || empty($key)) {
        wp_send_json_error(['message' => 'Missing API key.']);
    }

    // Save key securely
    update_user_meta($user_id, 'shipstation_api_key', $key);
    delete_user_meta($user_id, 'shipstation_store_ids');

    // Fetch stores
    $response = shipstation_fetch_stores($key);
    wp_send_json_success(['stores' => $response]);
});

/**
 * Admin: Save API key for another user
 */
add_action('wp_ajax_shipstation_admin_save_api', function () {
    check_ajax_referer('shipstation_nonce', 'nonce');
    if (!current_user_can('administrator')) wp_send_json_error(['message' => 'Unauthorized']);

    $user_id = intval($_POST['user_id']);
    $key = sanitize_text_field($_POST['key']);

    if (!$user_id || empty($key)) {
        wp_send_json_error(['message' => 'Missing data.']);
    }

    update_user_meta($user_id, 'shipstation_api_key', $key);
    delete_user_meta($user_id, 'shipstation_store_ids');

    $response = shipstation_fetch_stores($key);
    wp_send_json_success(['stores' => $response]);
});

/**
 * Save selected store IDs
 */
add_action('wp_ajax_shipstation_save_stores', function () {
    check_ajax_referer('shipstation_nonce', 'nonce');
    $store_ids = isset($_POST['store_ids']) ? array_map('intval', (array) $_POST['store_ids']) : [];
    $user_id = current_user_can('administrator') && isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();

    if (!$user_id) {
        wp_send_json_error(['message' => 'Invalid user.']);
    }

    update_user_meta($user_id, 'shipstation_store_ids', $store_ids);
    wp_send_json_success();
});

/**
 * Fetch store list from ShipStation using stored API key
 */
function shipstation_fetch_stores($key) {
    $url = 'https://ssapi.shipstation.com/stores';

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . $key  // Do NOT encode again — this key is already base64
        ],
        'timeout' => 15,
    ]);

    $debug = [
        'raw_response' => $response,
        'code' => wp_remote_retrieve_response_code($response),
        'body' => wp_remote_retrieve_body($response)
    ];

    file_put_contents(__DIR__ . '/debug-fetch-stores.json', json_encode($debug, JSON_PRETTY_PRINT));

    if (is_wp_error($response)) return [];

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) return [];

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!is_array($data)) return [];

    return $data;
}