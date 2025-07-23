<?php
if (!defined('ABSPATH')) exit;

function shipstation_log($data, $filename = 'log.json') {
    $upload_dir = wp_upload_dir();
    $log_file = trailingslashit($upload_dir['basedir']) . 'shipstation-' . $filename;

    file_put_contents($log_file, json_encode($data, JSON_PRETTY_PRINT), FILE_APPEND);
}

function shipstation_get_api_key($user_id = null) {
    $uid = $user_id ?: get_current_user_id();
    $encoded = get_user_meta($uid, 'shipstation_api_key', true);
    return $encoded ? base64_decode($encoded) : null;
}

function shipstation_get_store_ids($user_id = null) {
    $uid = $user_id ?: get_current_user_id();
    return get_user_meta($uid, 'shipstation_store_ids', true) ?: [];
}