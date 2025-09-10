<?php

// Add admin menu page
function elearn_add_admin_menu()
{
    add_menu_page(
        'Elearn Modules',
        'Module List',
        'manage_options',
        'elearn-module-table',
        'elearn_module_table_page',
        'dashicons-welcome-learn-more',
        6
    );
}
add_action('admin_menu', 'elearn_add_admin_menu');

// Display module table data
function elearn_module_table_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'elearn_module';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    $dashboard_url = home_url('/dashboard');

    echo '<div class="wrap"><h1>Module Table</h1>';
    if (!empty($results)) {
        echo '<table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($results as $row) {
            $module_view_url = home_url('/view-module').'?module_id='.intval($row->module_id);
            $edit_url = admin_url('admin.php?page=elearn-edit-module&module_id=' . intval($row->module_id));
               echo '<tr>
                    <td>' . esc_html($row->module_id) . '</td>
                    <td>' . esc_html($row->module_name) . '</td>
                    <td>' . esc_html($row->module_description) . '</td>
                    <td>' . esc_html($row->module_crated) . '</td>
                    <td><a href="' . esc_url($edit_url) . '" class="button">Edit</a></td>
                    <td><a href="' . esc_url($module_view_url) . '" class="button">View</a></td>
                </tr>';
        }
        echo '</tbody></table>';
        echo '<p><a href="' . esc_url($dashboard_url) . '" class="button button-primary">Go to Dashboard</a></p>';
    } else {
        echo '<p>No module data found.</p>';
    }
    echo '</div>';
}