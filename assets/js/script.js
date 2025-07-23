console.log('✅ ShipStation plugin JavaScript loaded.');

function shipstationShowApiPopup() {
    jQuery('#shipstation-api-popup').fadeIn();
}

function shipstationClosePopup() {
    jQuery('.shipstation-popup').fadeOut();
    jQuery('#shipstation-store-list').html('');
    jQuery('#shipstation-api-input').val('');
    jQuery('#shipstation-admin-api-key').val('');
    jQuery('#shipstation-admin-store-list').html('');
}

function shipstationConfirmReset() {
    if (confirm("Are you sure you want to clear your previous settings?")) {
        shipstationShowApiPopup();
    }
}

function shipstationSubmitApi() {
    const key = jQuery('#shipstation-api-input').val().trim();
    if (!key) return alert("Please enter your API key.");

    jQuery.ajax({
        url: shipstation_ajax.ajax_url,
        method: 'POST',
        data: {
            action: 'shipstation_save_api',
            key,
            nonce: shipstation_ajax.nonce
        },
        success: function(response) {
            console.log("ShipStation debug response:", response);
            if (response.success && response.data.stores) {
                const html = response.data.stores.map(store =>
                    `<label><input type="checkbox" value="${store.storeId}"> ${store.storeName}</label>`
                ).join('');
                jQuery('#shipstation-store-list').html(html);
            } else {
                alert(response.data.message || "Store fetch failed.");
            }
        },
        error: function(xhr, status, error) {
            console.log("ShipStation AJAX error:", xhr.responseText || status);
            alert("AJAX request failed.");
        }
    });
}

function shipstationSaveUserStores() {
    const selected = [];
    jQuery('#shipstation-store-list input:checked').each(function () {
        selected.push(jQuery(this).val());
    });

    jQuery.ajax({
        url: shipstation_ajax.ajax_url,
        method: 'POST',
        data: {
            action: 'shipstation_save_stores',
            store_ids: selected,
            nonce: shipstation_ajax.nonce
        },
        success: function (response) {
            console.log('✅ Store selections saved for user:', response);
            if (response.success) {
                alert("Stores saved!");
                shipstationClosePopup();
                location.reload();
            } else {
                alert("Error saving stores.");
            }
        },
        error: function(xhr) {
            console.log("❌ Save stores AJAX error:", xhr.responseText);
        }
    });
}

function shipstationAdminReset(user_id) {
    jQuery('#shipstation-admin-user-id').val(user_id);
    jQuery('#shipstation-admin-popup').fadeIn();
}

function shipstationAdminSubmit() {
    const key = jQuery('#shipstation-admin-api-key').val().trim();
    const user_id = jQuery('#shipstation-admin-user-id').val();

    if (!key) return alert("Please enter an API key.");

    jQuery.ajax({
        url: shipstation_ajax.ajax_url,
        method: 'POST',
        data: {
            action: 'shipstation_admin_save_api',
            user_id,
            key,
            nonce: shipstation_ajax.nonce
        },
        success: function (response) {
            console.log("ShipStation Admin debug response:", response);
            if (response.success && response.data.stores) {
                const html = response.data.stores.map(store =>
                    `<label><input type="checkbox" value="${store.storeId}"> ${store.storeName}</label>`
                ).join('');
                jQuery('#shipstation-admin-store-list').html(html);
            } else {
                alert(response.data.message || "Error fetching stores.");
            }
        },
        error: function(xhr) {
            console.log("❌ Admin AJAX error:", xhr.responseText);
        }
    });
}

// Save user store selections
jQuery(document).on('click', '#shipstation-save-user-stores', function () {
    shipstationSaveUserStores();
});

// Save admin store selections
jQuery(document).on('click', '#shipstation-save-admin-stores', function () {
    const selected = [];
    const user_id = jQuery('#shipstation-admin-user-id').val();

    jQuery('#shipstation-admin-store-list input:checked').each(function () {
        selected.push(jQuery(this).val());
    });

    jQuery.ajax({
        url: shipstation_ajax.ajax_url,
        method: 'POST',
        data: {
            action: 'shipstation_save_stores',
            store_ids: selected,
            user_id: user_id,
            nonce: shipstation_ajax.nonce
        },
        success: function (response) {
            console.log('✅ Store selections saved for admin:', response);
            if (response.success) {
                alert("Stores updated.");
                shipstationClosePopup();
                location.reload();
            } else {
                alert("Failed to save stores.");
            }
        },
        error: function(xhr) {
            console.log("❌ Admin save stores AJAX error:", xhr.responseText);
        }
    });
});