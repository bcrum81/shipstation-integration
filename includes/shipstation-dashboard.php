<?php
if (!defined('ABSPATH')) exit; // Prevent direct access

add_shortcode('shipstation_dashboard', 'render_shipstation_dashboard');

function render_shipstation_dashboard() {
    if (!is_user_logged_in()) {
        return '<p>Please <a href="/wp-login.php">log in</a> to access your dashboard.</p>';
    }

    $user_id = get_current_user_id();
    $api_key = get_user_meta($user_id, 'shipstation_api_key', true);

    // Handle API key save
    if (isset($_POST['save_api_key']) && !empty($_POST['shipstation_api_key'])) {
        check_admin_referer('save_api_key_nonce');
        $new_api_key = sanitize_text_field($_POST['shipstation_api_key']);
        update_user_meta($user_id, 'shipstation_api_key', $new_api_key);
        $api_key = $new_api_key;
        echo '<p style="color:green;">API key saved.</p>';
    }

    // Handle store selection save
    if (isset($_POST['save_stores']) && !empty($_POST['selected_stores'])) {
        check_admin_referer('save_stores_nonce');
        $selected_stores = array_map('intval', $_POST['selected_stores']);
        update_user_meta($user_id, 'shipstation_selected_stores', $selected_stores);
        echo '<p style="color:green;">Store selection saved.</p>';
    }

    ob_start();
    ?>

    <h2>ShipStation Dashboard</h2>

    <!-- API Key Form -->
    <form method="post">
        <?php wp_nonce_field('save_api_key_nonce'); ?>
        <label for="shipstation_api_key">ShipStation API Key (Base64):</label><br>
        <input type="text" name="shipstation_api_key" value="<?php echo esc_attr($api_key); ?>" style="width: 400px;"><br>
        <button type="submit" name="save_api_key">Save API Key</button>
    </form>

    <hr>

    <?php
    if (!$api_key) {
        echo '<p>Please enter your API key above to continue.</p>';
        return ob_get_clean();
    }

    // Get stores from ShipStation
    $stores = shipstation_get_stores($api_key);
    if ($stores === false) {
        echo '<p style="color:red;">Error fetching stores. Please check your API key.</p>';
        return ob_get_clean();
    }

    // Get previously selected stores
    $selected_stores = get_user_meta($user_id, 'shipstation_selected_stores', true);
    if (!is_array($selected_stores)) $selected_stores = [];

    ?>

    <!-- Store Selection Form -->
    <form method="post">
        <?php wp_nonce_field('save_stores_nonce'); ?>
        <h3>Select Stores</h3>
        <?php foreach ($stores as $store): ?>
            <label>
                <input type="checkbox" name="selected_stores[]" value="<?php echo esc_attr($store['storeId']); ?>"
                <?php checked(in_array($store['storeId'], $selected_stores)); ?>>
                <?php echo esc_html($store['storeName']); ?>
            </label><br>
        <?php endforeach; ?>
        <button type="submit" name="save_stores">Save Store Selection</button>
    </form>

    <hr>

    <!-- Order Search Form -->
    <h3>Search Orders</h3>
    <form method="post">
        <input type="text" name="search_query" placeholder="Order Number or Customer Email" style="width:300px;">
        <button type="submit" name="search_order">Search</button>
    </form>

    <?php
    // Handle order search
    if (isset($_POST['search_order']) && !empty($_POST['search_query'])) {
        $query = sanitize_text_field($_POST['search_query']);
        $orders = shipstation_search_orders($api_key, $query);
        if ($orders) {
            echo '<h4>Order Results:</h4>';
            foreach ($orders as $order) {
                ?>
                <div style="border:1px solid #ccc; padding:10px; margin:10px 0;">
                    <strong>Order #:</strong> <?php echo esc_html($order['orderNumber']); ?><br>
                    <strong>Customer:</strong> <?php echo esc_html($order['customerUsername']); ?> (<?php echo esc_html($order['customerEmail']); ?>)<br>
                    <strong>Status:</strong> <?php echo esc_html($order['orderStatus']); ?><br>
                    <strong>Ship To:</strong> <?php echo esc_html($order['shipTo']['name'] . ', ' . $order['shipTo']['street1']); ?><br><br>

                    <!-- Placeholder action buttons -->
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?php echo esc_attr($order['orderId']); ?>">
                        <button type="submit" name="place_hold">Place on Hold</button>
                    </form>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?php echo esc_attr($order['orderId']); ?>">
                        <button type="submit" name="remove_hold">Remove Hold</button>
                    </form>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?php echo esc_attr($order['orderId']); ?>">
                        <button type="submit" name="cancel_order">Cancel Order</button>
                    </form>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="order_id" value="<?php echo esc_attr($order['orderId']); ?>">
                        <button type="submit" name="update_address">Update Address</button>
                    </form>
                </div>
                <?php
            }
        } else {
            echo '<p>No orders found for this query.</p>';
        }
    }

    return ob_get_clean();
}

// ===== ShipStation API helper functions ===== //

function shipstation_get_stores($api_key) {
    $response = wp_remote_get('https://ssapi.shipstation.com/stores', [
        'headers' => ['Authorization' => 'Basic ' . $api_key],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) return false;

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) return false;

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return $data ?? false;
}

function shipstation_search_orders($api_key, $query) {
    $url = 'https://ssapi.shipstation.com/orders?customerEmail=' . urlencode($query) . '&orderNumber=' . urlencode($query);
    $response = wp_remote_get($url, [
        'headers' => ['Authorization' => 'Basic ' . $api_key],
        'timeout' => 15,
    ]);

    if (is_wp_error($response)) return false;

    $code = wp_remote_retrieve_response_code($response);
    if ($code !== 200) return false;

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    return $data['orders'] ?? false;
}
