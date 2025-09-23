<?php
// Make sure this is running inside WordPress
if (!defined('ABSPATH')) exit;

function elearn_manager_dash_org_details() {
    // Create page if it doesn't exist
    if (!get_page_by_path('organisation-details')) {
        wp_insert_post([
            'post_title'   => 'Organisation Details',
            'post_name'    => 'organisation-details',
            'post_content' => '[organisation_details]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_manager_dash_org_details');
add_action('init', 'elearn_manager_dash_org_details');

function elearn_manager_dash_org_details_shortcode() {
    if (!is_user_logged_in()) return '<p>You must be logged in to access this page.</p>';

    $current_user = wp_get_current_user();
    $allowed_roles = ['manager', 'administrator'];
    if (!array_intersect($allowed_roles, (array)$current_user->roles)) {
        return '<p>You do not have permission to access this page.</p>';
    }

    // --- Organisation detection logic ---
    $organisation_id = trim(get_user_meta($current_user->ID, 'organisation_id', true));


    if (empty($organisation_id)) return '<p>Your account is not assigned to any organisation.</p>';
    // -------------------------------------------------------------

    global $wpdb;
    $prefix = $wpdb->prefix . 'elearn_';
    $organisation_table = $prefix . 'organisation';

    ob_start();

    // Fetch organisation info using %s since organisation_id is a string
    $organisation = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $organisation_table WHERE organisation_id = %s", $organisation_id)
    );
    if (!$organisation) return '<p>Organisation not found.</p>';

    // Handle POST updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Update organisation
        if (isset($_POST['elearn_edit_organisation'])) {
            $wpdb->update(
                $organisation_table,
                [
                    'organisation_name'    => sanitize_text_field($_POST['organisation_name']),
                    'organisation_address' => sanitize_text_field($_POST['organisation_address']),
                    'organisation_phone'   => sanitize_text_field($_POST['organisation_phone']),
                    'organisation_email'   => sanitize_email($_POST['organisation_email']),
                    'organisation_abn'     => sanitize_text_field($_POST['organisation_abn']),
                ],
                ['organisation_id' => $organisation_id],
                ['%s','%s','%s','%s','%s'],
                ['%s'] // <- use string placeholder here
            );


            echo '<div class="updated"><p>Organisation updated successfully!</p></div>';
            $organisation = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $organisation_table WHERE organisation_id = %s", $organisation_id)
            );
        }

        // Add user to org
        if (isset($_POST['add_user_to_organisation'])) {
            $user_id = intval($_POST['add_user_id']);
            if ($user_id) {
                update_user_meta($user_id, 'organisation_id', $organisation_id);
                $user = new WP_User($user_id);
                $user->set_role('student');
                echo '<div class="updated"><p>User added to organisation!</p></div>';
            }
        }

        // Remove user from org
        if (isset($_POST['remove_user_from_organisation'])) {
            $user_id = intval($_POST['remove_user_id']);
            if ($user_id) {
                delete_user_meta($user_id, 'organisation_id');
                $user = new WP_User($user_id);
                $user->set_role('subscriber');
                echo '<div class="updated"><p>User removed from organisation!</p></div>';
            }
        }
    }

    // Fetch organisation users
    $organisation_users = get_users([
        'meta_key'   => 'organisation_id',
        'meta_value' => $organisation_id,
    ]);

    // Fetch managers (for dropdown)
    $managers = get_users([
        'meta_key'   => 'organisation_id',
        'meta_value' => $organisation_id,
    ]);

    // Fetch users not in organisation
    $all_users = get_users();
    $non_org_users = array_filter($all_users, function($u) use ($organisation_users) {
        return !in_array($u->ID, wp_list_pluck($organisation_users, 'ID'));
    });

    ?>
    <div class="organisation-details">
        <h1>Organisation Details: <?php echo esc_html($organisation->organisation_name); ?></h1>
        <!-- Organisation Edit Form -->
        <form method="post">
            <table class="form-table">
                <tr>
                    <th>Name</th>
                    <td><input type="text" name="organisation_name" value="<?php echo esc_attr($organisation->organisation_name); ?>" required></td>
                </tr>
                <tr>
                    <th>Address</th>
                    <td><input type="text" name="organisation_address" value="<?php echo esc_attr($organisation->organisation_address); ?>"></td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td><input type="text" name="organisation_phone" value="<?php echo esc_attr($organisation->organisation_phone); ?>"></td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td><input type="email" name="organisation_email" value="<?php echo esc_attr($organisation->organisation_email); ?>"></td>
                </tr>
                <tr>
                    <th>ABN</th>
                    <td><input type="text" name="organisation_abn" value="<?php echo esc_attr($organisation->organisation_abn); ?>"></td>
                </tr>
            </table>
            <p><button type="submit" name="elearn_edit_organisation" class="button button-primary">Save Changes</button></p>
        </form>

        <hr>
        <!-- Organisation Users Table -->
        <h2>Organisation Users</h2>
        <table class="widefat fixed">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($organisation_users as $user) : ?>
                <tr>
                    <td><?php echo esc_html($user->ID); ?></td>
                    <td><?php echo esc_html($user->display_name); ?></td>
                    <td><?php echo esc_html($user->user_email); ?></td>
                    <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                    <td>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="remove_user_id" value="<?php echo esc_attr($user->ID); ?>">
                            <button type="submit" name="remove_user_from_organisation" style="color:red;">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('organisation_details', 'elearn_manager_dash_org_details_shortcode');
