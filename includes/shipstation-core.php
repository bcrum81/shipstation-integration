<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode for user dashboard
 */
add_shortcode('shipstation_user_dashboard', 'shipstation_render_user_dashboard');

/**
 * Shortcode for admin dashboard
 */
add_shortcode('shipstation_admin_dashboard', 'shipstation_render_admin_dashboard');

/**
 * Render user dashboard view
 */
function shipstation_render_user_dashboard() {
    if (!is_user_logged_in() || current_user_can('administrator')) return '';

    $user_id = get_current_user_id();
    $api_set = get_user_meta($user_id, 'shipstation_api_key', true);
    ob_start();
    ?>
    <div id="shipstation-user-area">
        <?php if (!$api_set): ?>
            <button class="ship-btn" id="shipstation-configure-api">Configure API</button>
        <?php else: ?>
            <button class="ship-btn" id="shipstation-reset-api">Reset API Connection</button>
        <?php endif; ?>
    </div>

    <div id="shipstation-api-popup" class="shipstation-popup hidden">
        <div class="shipstation-popup-content">
            <h3>Enter API Key</h3>
            <input type="text" id="shipstation-api-input" placeholder="API Key">
            <div id="shipstation-store-list"></div>
            <div class="shipstation-popup-actions">
                <button class="ship-btn" id="shipstation-save-user-stores">Save</button>
                <button class="ship-btn cancel" id="shipstation-cancel-user">Cancel</button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render admin dashboard view
 */
function shipstation_render_admin_dashboard() {
    if (!current_user_can('administrator')) return '';

    $users = get_users(['role' => 'subscriber']);
    ob_start();
    ?>
    <div id="shipstation-admin-dashboard">
        <h3>Connected Users</h3>
        <ul>
        <?php foreach ($users as $user):
            $has_api = get_user_meta($user->ID, 'shipstation_api_key', true);
            if (!$has_api) continue;
            ?>
            <li>
                <?php echo esc_html($user->user_email); ?>
                <button class="ship-btn reset" data-user-id="<?php echo esc_attr($user->ID); ?>">Reset</button>
            </li>
        <?php endforeach; ?>
        </ul>
    </div>

    <div id="shipstation-admin-popup" class="shipstation-popup hidden">
        <div class="shipstation-popup-content">
            <h3>Enter API Key for User</h3>
            <input type="hidden" id="shipstation-admin-user-id">
            <input type="text" id="shipstation-admin-api-key" placeholder="API Key">
            <div id="shipstation-admin-store-list"></div>
            <div class="shipstation-popup-actions">
                <button class="ship-btn" id="shipstation-save-admin-stores">Save</button>
                <button class="ship-btn cancel" id="shipstation-cancel-admin">Cancel</button>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

require_once plugin_dir_path(__FILE__) . 'ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'utils.php';