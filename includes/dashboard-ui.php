<?php
if (!defined('ABSPATH')) exit;

function shipstation_render_user_dashboard() {
    ob_start();
    $user_id = get_current_user_id();
    $key = get_user_meta($user_id, 'shipstation_api_key', true);
    $stores = get_user_meta($user_id, 'shipstation_store_ids', true);

    if (!$key || empty($stores)) {
        echo '<button class="ship-btn" onclick="shipstationShowApiPopup()">Configure API</button>';
    } else {
        echo '<button class="ship-btn" onclick="shipstationConfirmReset()">Reset API Connection</button>';
    }
    ?>

    <!-- USER POPUP -->
    <div id="shipstation-api-popup" class="shipstation-popup" style="display:none;">
        <div class="shipstation-popup-content">
            <h3>Enter Your ShipStation API Key</h3>
            <input type="text" id="shipstation-api-input" placeholder="Base64 API Key">
            <button class="ship-btn" onclick="shipstationSubmitApi()">Connect</button>
            <div id="shipstation-store-list"></div>
            <button class="ship-btn" onclick="shipstationSaveUserStores()">Save</button>
            <br><br><button onclick="shipstationClosePopup()">Close</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('shipstation_user_dashboard', 'shipstation_render_user_dashboard');

function shipstation_render_admin_dashboard() {
    if (!current_user_can('administrator')) return '';

    $users = get_users(['role' => 'subscriber']);
    ob_start();
    echo '<h3>ShipStation User Connections</h3>';
    echo '<ul>';
    foreach ($users as $user) {
        $api = get_user_meta($user->ID, 'shipstation_api_key', true);
        if ($api) {
            echo '<li>' . esc_html($user->user_email) .
                 ' <button class="ship-btn" onclick="shipstationAdminReset(' . $user->ID . ')">Reset</button></li>';
        }
    }
    echo '</ul>';
    ?>

    <!-- ADMIN POPUP -->
    <div id="shipstation-admin-popup" class="shipstation-popup" style="display:none;">
        <div class="shipstation-popup-content">
            <h3>Reset User API Key</h3>
            <input type="hidden" id="shipstation-admin-user-id">
            <input type="text" id="shipstation-admin-api-key" placeholder="Base64 API Key">
            <button class="ship-btn" onclick="shipstationAdminSubmit()">Connect</button>
            <div id="shipstation-admin-store-list"></div>
            <button class="ship-btn">Save</button>
            <br><br><button onclick="shipstationClosePopup()">Close</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('shipstation_admin_dashboard', 'shipstation_render_admin_dashboard');