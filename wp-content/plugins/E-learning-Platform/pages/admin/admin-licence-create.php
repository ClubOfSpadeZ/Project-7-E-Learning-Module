<?php

function elearn_add_licence_create_menu()
{
    add_submenu_page(
        'elearn-module',         // Parent slug (Module List)
        'Create Licence',        // Page title
        'Create Licences',        // Menu title
        'manage_options',
        'elearn-licence-create',
        'elearn_licence_create_page'
    );
}
add_action('admin_menu', 'elearn_add_licence_create_menu');

function elearn_licence_create_page() {
    global $wpdb;
    $licence_table = $wpdb->prefix . 'elearn_licence';
    
    $content_table = $wpdb->prefix . 'elearn_module_in_licence';
    $module_table = $wpdb->prefix . 'elearn_module';

    // Handle form submission
    if (isset($_POST['save_licence'])) {
        $licence_name = sanitize_text_field($_POST['licence_Name']);
        $licence_description = sanitize_textarea_field($_POST['licence_Description']);
        $licence_users = intval($_POST['licence_Users']);
        $licence_cost = sanitize_text_field($_POST['licence_Cost']);
        $linked_modules = isset($_POST['linked_modules']) ? array_map('intval', $_POST['linked_modules']) : [];

        // Insert new module into the database
        if (!empty($licence_name)) {
            $result = $wpdb->insert(
                $licence_table,
                [
                    'licence_name' => $licence_name,
                    'licence_description' => $licence_description,
                    'user_amount' => $licence_users,
                    'licence_cost' => $licence_cost,
                    'licence_created' => current_time('mysql'),
                ],
                [
                    '%s', // licence_name
                    '%s', // licence_description
                    '%d', // user_amount
                    '%s', // licence_cost
                    '%s', // licence_created
                ]
            );

            if ($result !== false) {
                $licence_id = $wpdb->insert_id;

                // Link selected modules to the new licence
                foreach ($linked_modules as $module_id) {
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $content_table WHERE licence_licence_id = %d AND module_module_id = %d",
                        $licence_id,
                        $module_id
                    ));

                    if (!$exists) {
                        $wpdb->insert($content_table, [
                            'licence_licence_id' => $licence_id,
                            'module_module_id' => $module_id,
                        ]);
                    }
                }

                // Redirect to the licence list page with a success message
                wp_redirect(admin_url('admin.php?page=elearn-licence&created=1'));
                exit;
            } else {
                 echo '<div class="notice notice-error is-dismissible"><p>Failed to create licence: ' . esc_html($wpdb->last_error) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Licence name is required.</p></div>';
        }
    }

    $all_modules = $wpdb->get_results("SELECT module_id, module_name FROM $module_table");

    // Display the form
    echo '<div class="wrap"><h1>Create Licence</h1></div>';
    echo '<form method="post" enctype="multipart/form-data">
            <label for="licence_Name">Licence Name:</label>
            <br>
            <input type="text" id="licence_Name" name="licence_Name" class="regular-text" required>
            <br><br>
            <label for="licence_Description">Licence Description:</label>
            <br>
            <textarea id="licence_Description" name="licence_Description" class="regular-text" rows="5"></textarea>
            <br><br>
            <label for="licence_Users">Number of Users:</label>
            <br>
            <input type="number" id="licence_Users" name="licence_Users" class="regular-text" required>
            <br><br>
            <label for="licence_Cost">Licence Cost:</label>
            <br>
            <input type="text" id="licence_Cost" name="licence_Cost" class="regular-text" required>
            <br><br>';

    echo '<div class="wrap"><h2>Modules In Licence</h2></div>';
    echo '<table class="widefat fixed" cellspacing="0" style="width: auto;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Modules</th>
                    <th>Link</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($all_modules as $row) {
        echo '<tr>
                <td>' . esc_html($row->module_id) . '</td>
                <td>' . esc_html($row->module_name) . '</td>
                <td>
                    <input type="checkbox" name="linked_modules[]" value="' . intval($row->module_id) . '">
                </td>
            </tr>';
    }

    echo '</tbody></table><br>';
    echo '<input type="submit" name="save_licence" class="button button-primary" value="Create Licence">';
    echo '</form>';
}