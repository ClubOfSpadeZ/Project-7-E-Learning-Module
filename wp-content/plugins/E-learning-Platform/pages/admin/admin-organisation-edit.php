<?php

function elearn_add_edit_organisation_menu()
{
    add_submenu_page(
        'N/A',
        'Edit Organisations',
        'Edit Organisations',
        'manage_options',
        'elearn-edit-organisation',
        'elearn_edit_organisation_page'
    );
}
add_action('admin_menu', 'elearn_add_edit_organisation_menu');

function elearn_enqueue_admin_styles($hook)
{
    // Check if we are on the specific admin page
    wp_enqueue_style(
        'elearn-admin-styles', // Handle for the stylesheet
        plugin_dir_url(__FILE__) . 'adminlooksgood.css', // Path to the CSS file
        [],
        '1.0.0' // Version number
    );
}
add_action('admin_enqueue_scripts', 'elearn_enqueue_admin_styles');

function elearn_edit_organisation_page()
{
    global $wpdb;

    $prefix = $wpdb->prefix . 'elearn_';
    $organisation_table = $prefix . 'organisation';

    // Get the organisation ID from the URL
    $organisation_id = isset($_GET['organisation_id']) ? sanitize_text_field($_GET['organisation_id']) : '';

    // Fetch the organisation data
    $organisation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $organisation_table WHERE organisation_id = %s", $organisation_id));

    if (!$organisation) {
        echo '<div class="wrap"><h1>Organisation Not Found</h1></div>';
        return;
    }

    // Handle form submission for organisation details
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elearn_edit_organisation'])) {
        $organisation_name = sanitize_text_field($_POST['organisation_name']);
        $organisation_address = sanitize_text_field($_POST['organisation_address']);
        $organisation_phone = sanitize_text_field($_POST['organisation_phone']);
        $organisation_email = sanitize_email($_POST['organisation_email']);
        $organisation_abn = sanitize_text_field($_POST['organisation_abn']);
        $organisation_manager = intval($_POST['organisation_manager']);

        // Update the organisation in the database
        $wpdb->update(
            $organisation_table,
            [
                'organisation_name' => $organisation_name,
                'organisation_address' => $organisation_address,
                'organisation_phone' => $organisation_phone,
                'organisation_email' => $organisation_email,
                'organisation_abn' => $organisation_abn,
            ],
            ['organisation_id' => $organisation_id],
            [
                '%s', // organisation_name
                '%s', // organisation_address
                '%s', // organisation_phone
                '%s', // organisation_email
                '%s', // organisation_abn
            ],
            ['%s'] // organisation_id
        );

        // Update the manager's organisation metadata and role
        if ($organisation_manager) {
            // Update the organisation_id metadata for the manager
            update_user_meta($organisation_manager, 'organisation_id', $organisation_id);

            // Update the user's role to "manager"
            $user = new WP_User($organisation_manager);
            $user->set_role('manager');
        }

        if ($wpdb->last_error) {
            echo '<div class="notice notice-error"><p>Error: ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Organisation updated successfully!</p></div>';
        }

        // Refresh the organisation data
        $organisation = $wpdb->get_row($wpdb->prepare("SELECT * FROM $organisation_table WHERE organisation_id = %s", $organisation_id));
    }

    // Handle form submission for adding a user to the organisation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user_to_organisation'])) {
        $user_id_to_add = intval($_POST['add_user_id']);
        if ($user_id_to_add) {
            // Check if the user has the "administrator" role
            $user = new WP_User($user_id_to_add);
            if (in_array('administrator', $user->roles)) {
                echo '<div class="notice notice-error"><p>Administrators cannot be added to an organisation.</p></div>';
            } else {
                // Update the user's organisation_id metadata
                update_user_meta($user_id_to_add, 'organisation_id', $organisation_id);

                // Update the user's role to "student"
                $user->set_role('student');

                echo '<div class="notice notice-success"><p>User added to the organisation successfully and role updated to "student"!</p></div>';
            }
        }
    }

    // Handle form submission for removing a user from the organisation
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_user_from_organisation'])) {
        $user_id_to_remove = intval($_POST['remove_user_id']);
        if ($user_id_to_remove) {
            // Remove the user's organisation_id metadata
            delete_user_meta($user_id_to_remove, 'organisation_id');

            $user = new WP_User($user_id_to_remove);
            $user->set_role('subscriber');

            echo '<div class="notice notice-success"><p>User removed from the organisation successfully!</p></div>';
        }
    }

    // Fetch all users with the "organisation_manager" role
    $managers = get_users([
        'meta_key' => 'organisation_id',
        'meta_value' => $organisation_id,
        'meta_compare' => '='
    ]);

    // Fetch all users not already part of the organisation
    $all_users = get_users();

    // Display the form
    echo '<div class="wrap">';
    echo '<h1>Edit Organisation</h1>';
    echo '<div class="container">';
    echo '<div>';
    echo '<form method="POST" action="">';
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th><label for="organisation_name">Name</label></th>';
    echo '<td><input type="text" name="organisation_name" id="organisation_name" value="' . esc_attr($organisation->organisation_name) . '" class="regular-text" required></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="organisation_address">Address</label></th>';
    echo '<td><input type="text" name="organisation_address" id="organisation_address" value="' . esc_attr($organisation->organisation_address) . '" class="regular-text"></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="organisation_phone">Phone</label></th>';
    echo '<td><input type="text" name="organisation_phone" id="organisation_phone" value="' . esc_attr($organisation->organisation_phone) . '" class="regular-text"></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="organisation_email">Email</label></th>';
    echo '<td><input type="email" name="organisation_email" id="organisation_email" value="' . esc_attr($organisation->organisation_email) . '" class="regular-text"></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="organisation_abn">ABN</label></th>';
    echo '<td><input type="text" name="organisation_abn" id="organisation_abn" value="' . esc_attr($organisation->organisation_abn) . '" class="regular-text"></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th><label for="organisation_manager">Manager</label></th>';
    echo '<td>';
    echo '<select name="organisation_manager" id="organisation_manager" class="regular-text">';
    echo '<option value="">Select a Manager</option>';
    foreach ($managers as $manager) {
        echo '<option value="' . esc_attr($manager->ID) . '" ' . selected(get_user_meta($manager->ID, 'organisation_id', true), $organisation_id, false) . '>';
        echo esc_html($manager->display_name);
        echo '</option>';
    }
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '<p class="submit">';
    echo '<button type="submit" name="elearn_edit_organisation" class="button button-primary">Save Changes</button>';
    echo '</p>';
    echo '</form>';

    echo '<input type="text" id="userSearchBox" placeholder="Search users..." style="margin-bottom: 10px; width: 20%; padding: 8px;">';
    echo '</div>';

    echo '<div>';
    $access_code_count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}elearn_access WHERE organisation_organisation_id = %s",
            $organisation_id
        )
    );

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_access_code'])) {
        $result = $wpdb->delete(
            $wpdb->prefix . 'elearn_access',
            ['organisation_organisation_id' => $organisation_id],
            ['%s']
        );

        if ($result !== false) {
            echo '<div class="notice notice-success"><p>Access code removed successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Failed to remove access code: ' . esc_html($wpdb->last_error) . '</p></div>';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_access_code'])) {
        if ($access_code_count > 0) {
            echo '<div class="notice notice-error"><p>An access code already exists for this organisation. Please remove the existing code before creating a new one.</p></div>';
        } else {
            // Generate a unique access code
            $access_code = wp_generate_password(12, false); // Generate a 12-character alphanumeric code

            // Insert the access code into the database
            $result = $wpdb->insert(
                $wpdb->prefix . 'elearn_access',
                [
                    'access_code' => $access_code,
                    'hash_code' => hash('sha256', $access_code),
                    'organisation_organisation_id' => $organisation_id,
                    'is_used' => 0,
                    'access_created' => current_time('mysql'),
                    'access_used' => null,
                ],
                [
                    '%s', // access_code
                    '%s', // hash_code
                    '%s', // organisation_organisation_id
                    '%d', // is_used
                    '%s', // access_created
                    '%s', // access_used
                ]
            );

            if ($result !== false) {
                echo '<div class="notice notice-success"><p>Access code generated successfully: <strong>' . esc_html($access_code) . '</strong></p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Failed to generate access code: ' . esc_html($wpdb->last_error) . '</p></div>';
            }
        }
    }

    $access_codes = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT access_id, access_code, is_used, access_created, access_used 
            FROM {$wpdb->prefix}elearn_access 
            WHERE organisation_organisation_id = %s",
            $organisation_id
        )
    );

    echo '<h2>Access Codes</h2>';
    if (!empty($access_codes)) {
        echo '<table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Access Code</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Used</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($access_codes as $code) {
            echo '<tr>
                    <td>' . esc_html($code->access_id) . '</td>
                    <td>' . esc_html($code->access_code) . '</td>
                    <td>' . ($code->is_used ? 'Used' : 'Unused') . '</td>
                    <td>' . esc_html($code->access_created) . '</td>
                    <td>' . ($code->access_used ? esc_html($code->access_used) : 'N/A') . '</td>
                </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No access codes found for this organisation.</p>';
    }
    
    if ($access_code_count > 0) {
    // Display the "Remove Access Code" button
    echo '<form method="POST" action="" style="margin-top: 20px;">
            <button type="submit" name="remove_access_code" class="button button-secondary" style="color: red; border-color: red;">Remove Access Code</button>
          </form>';
    } else {
        // Display the "Generate Access Code" button
        echo '<form method="POST" action="" style="margin-top: 20px;">
                <button type="submit" name="create_access_code" class="button button-primary">Generate Access Code</button>
            </form>';
    }
    echo '</div>';

    $organisation_users = $wpdb->get_results("SELECT user_id, display_name, user_email FROM " . $wpdb->prefix . "usermeta
                                        INNER JOIN " . $wpdb->prefix . "users ON " . $wpdb->prefix . "usermeta.user_id = " . $wpdb->prefix . "users.ID
                                        WHERE meta_key = 'organisation_id' AND meta_value = '$organisation_id';");

    echo '<div>';
    echo '<h2>Organisation Users</h2>';
    if (!empty($organisation_users)) {
        echo '<table class="widefat fixed userdata" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($organisation_users as $row) {
            // Get the user's role
            $user = new WP_User($row->user_id);
            $roles = !empty($user->roles) ? implode(', ', $user->roles) : 'No Role';
            echo '<tr>
                    <td>' . esc_html($row->user_id) . '</td>
                    <td>' . esc_html($row->display_name) . '</td>
                    <td>' . esc_html($row->user_email) . '</td>
                    <td>' . esc_html($roles) . '</td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="remove_user_id" value="' . esc_attr($row->user_id) . '">
                            <button type="submit" name="remove_user_from_organisation" class="button" style="color: red; border-color: red;">Remove</button>
                        </form>
                    </td>
                </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No organisation data found.</p>';
    }
    echo '</div>';

    $users = $wpdb->get_results("SELECT ID, display_name, user_email FROM " . $wpdb->prefix . "users
                                    WHERE ID NOT IN (SELECT user_id FROM " . $wpdb->prefix . "usermeta
                                    WHERE meta_key = 'organisation_id' AND meta_value = '$organisation_id');");

    echo '<div>';
    echo '<h2>Existing Website Users</h2>';
    if (!empty($users)) {
        echo '<table class="widefat fixed userdata" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($users as $row) {
            $user = new WP_User($row->ID);
            $roles = !empty($user->roles) ? implode(', ', $user->roles) : 'No Role';
            echo '<tr>
                    <td>' . esc_html($row->ID) . '</td>
                    <td>' . esc_html($row->display_name) . '</td>
                    <td>' . esc_html($row->user_email) . '</td>
                    <td>' . esc_html($roles) . '</td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="add_user_id" value="' . esc_attr($row->ID) . '">
                            <button type="submit" name="add_user_to_organisation" class="button" style="color: green; border-color: green;">Add</button>
                        </form>
                    </td>
                </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No User data found.</p>';
    }
    echo '</div>';
    echo '</div></div>';

    echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const searchBox = document.getElementById("userSearchBox");
                const userTables = document.querySelectorAll(".userdata"); // Select all tables with the class "userdata"

                searchBox.addEventListener("input", function() {
                    const query = searchBox.value.toLowerCase();

                    userTables.forEach(userTable => {
                        const rows = userTable.querySelectorAll("tbody tr");
                        rows.forEach(row => {
                            const cells = row.querySelectorAll("td");
                            const rowText = Array.from(cells).map(cell => cell.textContent.toLowerCase()).join(" ");
                            if (rowText.includes(query)) {
                                row.style.display = "";
                            } else {
                                row.style.display = "none";
                            }
                        });
                    });
                });
            });
        </script>';
}
