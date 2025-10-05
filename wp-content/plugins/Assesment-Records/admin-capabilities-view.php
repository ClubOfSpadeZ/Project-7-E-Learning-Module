<?php
// Add a custom admin menu page
function assessment_records_user_capabilities_page() {
    add_menu_page(
        'User Capabilities', // Page title
        'User Capabilities', // Menu title
        'manage_options',    // Capability required to access the page
        'user-capabilities', // Menu slug
        'assessment_records_display_users_with_capabilities', // Callback function to display content
        'dashicons-admin-users', // Icon
        20 // Position
    );
}
add_action('admin_menu', 'assessment_records_user_capabilities_page');

// Callback function to display users with specific capabilities
function assessment_records_display_users_with_capabilities() {
    // Get all users
    $users = get_users();

    echo '<div class="wrap">';
    echo '<h1>Users with Custom Capabilities</h1>';
    echo '<table class="widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>User ID</th>';
    echo '<th>Username</th>';
    echo '<th>Email</th>';
    echo '<th>Roles</th>';
    echo '<th>Capabilities</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($users as $user) {
        $user_data = new WP_User($user->ID);

        // Check if the user has custom capabilities
        $custom_capabilities = array_filter($user_data->allcaps, function ($cap, $key) {
            return in_array($key, ['manage_manager_pages', 'WHS', 'contributor']); // List your custom capabilities here
        }, ARRAY_FILTER_USE_BOTH);

        if (!empty($custom_capabilities)) {
            echo '<tr>';
            echo '<td>' . esc_html($user->ID) . '</td>';
            echo '<td>' . esc_html($user->user_login) . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . implode(', ', $user->roles) . '</td>';
            echo '<td>' . implode(', ', array_keys($custom_capabilities)) . '</td>';
            echo '</tr>';
        }
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}