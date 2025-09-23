<?php

function elearn_add_licence_menu()
{
    add_submenu_page(
        'elearn-module',         // Parent slug (Module List)
        'Licences',        // Page title
        'Licences',        // Menu title
        'manage_options',
        'elearn-licence',
        'elearn_licence_page'
    );
}
add_action('admin_menu', 'elearn_add_licence_menu');

function elearn_licence_page()
{
    global $wpdb;

    $prefix = $wpdb->prefix . 'elearn_';
    $licence_table = $prefix . 'licence';

    $licence_create_url = admin_url('admin.php?page=elearn-licence-create');

    echo '<div class="wrap"><h1>Licence Management</h1>';
    
    $results = $wpdb->get_results("SELECT * FROM $licence_table");

    if (!empty($results)) {
        echo '<table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Users</th>
                        <th>Cost</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($results as $row) {
            $edit_url = admin_url('admin.php?page=elearn-edit-licence&licence_id=' . urlencode($row->licence_id));
            $delete_url = admin_url('admin.php?page=elearn-licence&action=delete&licence_id=' . intval($row->licence_id));
            echo '<tr>
                    <td>' . esc_html($row->licence_id) . '</td>
                    <td>' . esc_html($row->licence_name) . '</td>
                    <td>' . esc_html($row->user_amount) . '</td>
                    <td>' . esc_html($row->licence_cost) . '</td>
                    <td>
                        <a href="' . esc_url($edit_url) . '" class="button">Edit</a>
                        <a href="' . esc_url($delete_url) . '" class="button delete-licence" style="color: red; border-color: red;" data-licence-id="' . esc_attr($row->licence_id) . '">Delete</a>
                    </td>
                </tr>';
        }
        echo '</tbody></table>';
    echo '</div>';
    } else {
        echo '<p>No licence data found.</p>';
    }

    echo '<p>
            <a href="' . esc_url($licence_create_url) . '" class="button button-secondary">Licence Create</a>
          </p>';
    echo '</div>';
}