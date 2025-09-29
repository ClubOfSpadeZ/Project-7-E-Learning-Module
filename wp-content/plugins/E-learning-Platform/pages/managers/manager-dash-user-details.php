<?php
// Make sure this is running inside WordPress
if ( ! defined( 'ABSPATH' ) ) exit;
function elearn_manager_dash_user_details() {
    $page_title = 'User Details';

    // Check if the page already exists
    if (!get_page_by_path('user-details')) {
        // Create Manager Dashboard page
        wp_insert_post([
            'post_title'   => 'User Details',
            'post_name'    => 'user-details',
            'post_content' => '[user_details]', // shortcode or leave blank
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_manager_dash_user_details');
add_action('init', 'elearn_manager_dash_user_details');

function elearn_manager_dash_user_details_shortcode() {
    ob_start();
    if ( ! is_user_logged_in() ) {
        return '<p>You must be logged in to access this page.</p>';
    }

    $current_user = wp_get_current_user();
    $allowed_roles = ['manager', 'administrator'];
    if ( ! array_intersect( $allowed_roles, (array) $current_user->roles ) ) {
        return '<p>You do not have permission to access this page.</p>';
    }

        // Get current manager's organisation_id from usermeta
    $manager_org_id = get_user_meta($current_user->ID, 'organisation_id', true);

    // Fallback: if no org_id found, set to 0 (so they see no users)
    if (empty($manager_org_id)) {
        $manager_org_id = 0;
    }

    // Load all users from the same organisation
    $all_users = get_users([
        'meta_key'   => 'organisation_id',
        'meta_value' => $manager_org_id,
    ]);
    // Fetch all users (optionally filter by role)
    $users = $all_users;

    // Selected user ID from dropdown or form submission
    $selected_user_id = !empty($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $selected_user = $selected_user_id ? get_userdata($selected_user_id) : null;

    // Handle form submission for updates
    if ( isset($_POST['update_user_nonce']) && wp_verify_nonce($_POST['update_user_nonce'], 'update_user_action') ) {
        if ( current_user_can('edit_users') && $selected_user ) {
            $userdata = array('ID' => $selected_user_id);

            if ( isset($_POST['display_name']) ) $userdata['display_name'] = sanitize_text_field($_POST['display_name']);
            if ( isset($_POST['user_email']) && is_email($_POST['user_email']) ) $userdata['user_email'] = sanitize_email($_POST['user_email']);
            if ( isset($_POST['user_login']) ) $userdata['user_login'] = sanitize_user($_POST['user_login'], true);

            wp_update_user($userdata);

            echo '<div class="updated"><p>User updated successfully!</p></div>';

            // Refresh selected user after update
            $selected_user = get_userdata($selected_user_id);
        }
    }

    ?>

    <div class="user-details">
        <h1>User Details</h1>

        <!-- User selection dropdown -->
        <form method="post">
            <label for="user_id">Select a user:</label><br>
            <select name="user_id" id="user_id" onchange="this.form.submit()">
                <option value="">-- Select User --</option>
                <?php foreach ( $users as $user ) : ?>
                    <option value="<?php echo esc_attr($user->ID); ?>" <?php selected($selected_user_id, $user->ID); ?>>
                        <?php echo esc_html($user->display_name . ' (' . $user->user_email . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Display selected user details -->
        <?php if ( $selected_user ) : ?>
            <h2>Details for <?php echo esc_html($selected_user->display_name); ?></h2>
            <ul>
                <li><strong>Username:</strong> <?php echo esc_html($selected_user->user_login); ?></li>
                <li><strong>Display Name:</strong> <?php echo esc_html($selected_user->display_name); ?></li>
                <li><strong>Email:</strong> <?php echo esc_html($selected_user->user_email); ?></li>
                <li><strong>Role(s):</strong> <?php echo esc_html(implode(', ', $selected_user->roles)); ?></li>
                <li><strong>Registered On:</strong> <?php echo esc_html($selected_user->user_registered); ?></li>
            </ul>

            <!-- Edit button -->
            <button type="button" onclick="document.getElementById('edit-form').style.display='block'; this.style.display='none';">
                Edit User
            </button>

            <!-- Edit form (hidden by default) -->
            <form method="post" id="edit-form" style="display:none; margin-top:15px;">
                <?php wp_nonce_field('update_user_action', 'update_user_nonce'); ?>
                <input type="hidden" name="user_id" value="<?php echo esc_attr($selected_user->ID); ?>">

                <p>
                    <label for="user_login">Username:</label><br>
                    <input type="text" name="user_login" value="<?php echo esc_attr($selected_user->user_login); ?>" required>
                </p>

                <p>
                    <label for="display_name">Display Name:</label><br>
                    <input type="text" name="display_name" value="<?php echo esc_attr($selected_user->display_name); ?>" required>
                </p>

                <p>
                    <label for="user_email">Email:</label><br>
                    <input type="email" name="user_email" value="<?php echo esc_attr($selected_user->user_email); ?>" required>
                </p>

                <p>
                    <button type="submit">Save Changes</button>
                </p>
            </form>
        <?php endif; ?>
    </div>

    <?php
    return ob_get_clean();
}
add_shortcode('user_details', 'elearn_manager_dash_user_details_shortcode');
