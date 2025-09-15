<?php

// Add admin menu page
function elearn_add_admin_menu()
{
    add_menu_page(
        'E-learning',
        'Modules',
        'manage_options',
        'elearn-module',
        'elearn_module_page',
        'dashicons-welcome-learn-more',
        6
    );
}
add_action('admin_menu', 'elearn_add_admin_menu');

function elearn_handle_module_delete() {
    global $wpdb;

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['module_id'])) {
        $module_id = intval($_GET['module_id']);
        $module_table = $wpdb->prefix . 'elearn_module';
        $content_table = $wpdb->prefix . 'elearn_content_in_modules';

        // Delete rows from elearn_content_in_modules linked to the module_id
        $wpdb->delete($content_table, ['module_module_id' => $module_id], ['%d']);

        // Delete the module from elearn_module
        $wpdb->delete($module_table, ['module_id' => $module_id], ['%d']);

        // Redirect to avoid resubmission
        wp_redirect(admin_url('admin.php?page=elearn-module'));
        exit;
    }
}
add_action('admin_init', 'elearn_handle_module_delete');

// Display module table data
function elearn_module_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'elearn_module';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    $dashboard_url = home_url('/dashboard');
    $module_create_url = admin_url('admin.php?page=elearn-module-create');
    
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
            $edit_url = admin_url('admin.php?page=elearn-edit-module&module_id=' . intval($row->module_id));
            $delete_url = admin_url('admin.php?page=elearn-module&action=delete&module_id=' . intval($row->module_id));
               echo '<tr>
                    <td>' . esc_html($row->module_id) . '</td>
                    <td>' . esc_html($row->module_name) . '</td>
                    <td>' . esc_html($row->module_description) . '</td>
                    <td>' . esc_html($row->module_created) . '</td>
                    <td>
                        <a href="' . esc_url($edit_url) . '" class="button">Edit</a>
                        <a href="' . esc_url($dashboard_url) . '" class="button">View</a>
                        <a href="' . esc_url($delete_url) . '" class="button delete-module" style="color: red; border-color: red;" data-module-id="' . esc_attr($row->module_id) . '">Delete</a>
                    </td>
                </tr>';
        }
        echo '</tbody></table>';
    echo '</div>';
    } else {
        echo '<p>No module data found.</p>';
    }
    echo '<p>
            <a href="' . esc_url($dashboard_url) . '" class="button button-primary">Go to Dashboard</a>
            <a href="' . esc_url($module_create_url) . '" class="button button-secondary">Module Create</a>
          </p>';
    echo '</div>';

    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const deleteButtons = document.querySelectorAll(".delete-module");
            deleteButtons.forEach(button => {
                button.addEventListener("click", function(event) {
                    if (!confirm("Are you sure you want to delete this module? This action cannot be undone.")) {
                        event.preventDefault();
                    }
                });
            });
        });
    </script>';
}