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
            // Update the user's organisation_id metadata
            update_user_meta($user_id_to_add, 'organisation_id', $organisation_id);

            // Update the user's role to "student"
            $user = new WP_User($user_id_to_add);
            $user->set_role('student');

            echo '<div class="notice notice-success"><p>User added to the organisation successfully and role updated to "student"!</p></div>';
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

    $organisation_users = $wpdb->get_results("SELECT user_id, display_name, user_email FROM " . $wpdb->prefix . "usermeta
                                        INNER JOIN " . $wpdb->prefix . "users ON " . $wpdb->prefix . "usermeta.user_id = " . $wpdb->prefix . "users.ID
                                        WHERE meta_key = 'organisation_id' AND meta_value = '$organisation_id';");

    if (!empty($organisation_users)) {
        echo '<table class="widefat fixed" cellspacing="0">
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

    $users = $wpdb->get_results("SELECT ID, display_name, user_email FROM " . $wpdb->prefix . "users
                                    WHERE ID NOT IN (SELECT user_id FROM " . $wpdb->prefix . "usermeta
                                    WHERE meta_key = 'organisation_id' AND meta_value = '$organisation_id');");

    if (!empty($users)) {
        echo '<table class="widefat fixed" cellspacing="0">
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

    echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                const searchBox = document.getElementById("userSearchBox");
                const userTables = document.querySelectorAll(".widefat");

                searchBox.addEventListener("input", function() {
                    const query = searchBox.value.toLowerCase();

                    userTables.forEach(table => {
                        const rows = table.querySelectorAll("tbody tr");
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