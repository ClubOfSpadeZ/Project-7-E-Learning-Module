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

        // Remove user from org - change user role to 'webpage_user' so they lose access to org content but are in the system


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_user_from_organisation'])) {
    $user_id = intval($_POST['remove_user_id']);
    if ($user_id) {
        $user = new WP_User($user_id);
        $user->set_role('webpage_user');
    }
}

if (isset($_POST['change_user_to_subscriber'])) {
    $user_id_to_change = intval($_POST['change_user_id']);
    if ($user_id_to_change) {
        $user = new WP_User($user_id_to_change);
        $user->set_role('student'); // Change back to student

    }
}

    // Redirect to the same page to prevent form resubmission
    wp_safe_redirect(add_query_arg([], get_permalink()));
    exit; // important!
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
<!-- Search bar -->
<p>
    <input type="text" id="org-user-search" placeholder="Search users..." 
           style="padding:5px; width:100%; max-width:400px; margin-bottom:8px;">
</p>

<!-- Organisation Users Table -->
<table id="org-users-table" style="border-collapse: collapse; width:100%; font-family:sans-serif;">
    <thead>
        <tr>
            <th data-column="0" style="border:1px solid #ccc; padding:8px; background-color:#f0f0f0; cursor:pointer;">ID</th>
            <th data-column="1" style="border:1px solid #ccc; padding:8px; background-color:#f0f0f0; cursor:pointer;">Name</th>
            <th data-column="2" style="border:1px solid #ccc; padding:8px; background-color:#f0f0f0; cursor:pointer;">Email</th>
            <th data-column="3" style="border:1px solid #ccc; padding:8px; background-color:#f0f0f0; cursor:pointer;">Role</th>
            <th style="border:1px solid #ccc; padding:8px; background-color:#f0f0f0;">Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php 
    $row_index = 0;
    foreach ($organisation_users as $user) : 
        $roles = $user->roles; 
        $bg = ($row_index % 2 === 0) ? 'white' : '#fafafa';
        $row_index++;
    ?>
        <tr style="background-color:<?php echo $bg; ?>; border:1px solid #ccc;">
            <td style="border:1px solid #ccc; padding:8px;"><?php echo esc_html($user->ID); ?></td>
            <td style="border:1px solid #ccc; padding:8px;"><?php echo esc_html($user->display_name); ?></td>
            <td style="border:1px solid #ccc; padding:8px;"><?php echo esc_html($user->user_email); ?></td>
            <td style="border:1px solid #ccc; padding:8px;"><?php echo esc_html(implode(', ', $roles)); ?></td>
            <td style="border:1px solid #ccc; padding:8px;">
                <?php if (in_array('webpage_user', $roles)) : ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="change_user_id" value="<?php echo esc_attr($user->ID); ?>">
                        <button type="submit" name="change_user_to_subscriber" 
                                style="padding:5px 10px; font-size:14px; min-width:150px; background-color:#dfd; color:green; cursor:pointer;">
                            Grant Access
                        </button>
                    </form>
                <?php else : ?>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="remove_user_id" value="<?php echo esc_attr($user->ID); ?>">
                        <button type="submit" name="remove_user_from_organisation" 
                                style="padding:5px 10px; font-size:14px; min-width:150px; background-color:#fdd; color:red; cursor:pointer;">
                            Remove Access
                        </button>
                    </form>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
(function(){
    const table = document.getElementById('org-users-table');
    const searchInput = document.getElementById('org-user-search');
    const tbody = table.querySelector('tbody');
    const headers = table.querySelectorAll('th[data-column]');

    // Search
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase();
        Array.from(tbody.rows).forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });

    // Sorting
    headers.forEach(header => {
        let asc = true;
        header.addEventListener('click', () => {
            const colIndex = parseInt(header.dataset.column);
            const rows = Array.from(tbody.rows).filter(r => r.style.display !== 'none');

            rows.sort((a, b) => {
                let aText = a.cells[colIndex].innerText.trim();
                let bText = b.cells[colIndex].innerText.trim();
                if (!isNaN(aText) && !isNaN(bText)) return asc ? aText - bText : bText - aText;
                return asc ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });

            rows.forEach(r => tbody.appendChild(r));
            asc = !asc;

            // Reapply alternating row colors
            rows.forEach((r,i)=> r.style.backgroundColor = (i%2===0 ? '#fff' : '#fafafa'));
        });
    });
})();
</script>


    </div>
    <?php

    return ob_get_clean();
}

add_shortcode('organisation_details', 'elearn_manager_dash_org_details_shortcode');
