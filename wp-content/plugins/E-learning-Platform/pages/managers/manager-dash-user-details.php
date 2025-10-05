<?php

if (!defined('ABSPATH'))
    exit;
function elearn_manager_dash_user_details()
{
    $page_title = 'User Details';

    // Check if the page already exists
    if (!get_page_by_path('user-details')) {
        // Create Manager Dashboard page
        wp_insert_post([
            'post_name' => 'user-details',
            'post_content' => '[user_details]', // shortcode or leave blank
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_manager_dash_user_details');
add_action('init', 'elearn_manager_dash_user_details');

function elearn_manager_dash_user_details_shortcode()
{
    ob_start();
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to access this page.</p>';
    }

    $current_user = wp_get_current_user();
    $allowed_roles = ['manager', 'administrator'];
    if (!array_intersect($allowed_roles, (array) $current_user->roles)) {
        return '<p>You do not have permission to access this page.</p>';
    }

    $manager_org_id = get_user_meta($current_user->ID, 'organisation_id', true);
    if (empty($manager_org_id))
        $manager_org_id = 0;

    $all_users = get_users([
        'meta_key' => 'organisation_id',
        'meta_value' => $manager_org_id,
    ]);
    ?>

    <div class="user-details" style="display:flex; gap:20px;">

        <!-- Left column: search + user list -->
        <div style="flex:1; min-width:250px; max-width:300px;">
            <h2>Users</h2>
            <input id="user-search-input" type="text" placeholder="Search by name or email..."
                style="margin-bottom:8px; padding:5px; width:100%;">

            <div id="users-list-wrapper" style="max-height:500px; overflow-y:auto; border:1px solid #ccc; padding:10px;">
                <div id="users-list">
                    <?php foreach ($all_users as $user):
                        $data_name = esc_attr(strtolower($user->display_name));
                        ?>
                        <div class="user-item" data-id="<?php echo esc_attr($user->ID); ?>"
                            data-name="<?php echo $data_name; ?>"
                            data-email="<?php echo esc_attr(strtolower($user->user_email)); ?>"
                            data-login="<?php echo esc_attr($user->user_login); ?>"
                            data-roles="<?php echo esc_attr(implode(', ', $user->roles)); ?>"
                            data-registered="<?php echo esc_attr($user->user_registered); ?>"
                            style="padding:5px; border-bottom:1px solid #eee; cursor:pointer;">
                            <strong><?php echo esc_html($user->display_name); ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div id="no-users-found" style="display:none; margin-top:8px; color:#b00;">
                    No users found.
                </div>
            </div>
        </div>

        <!-- Right column: selected user details -->
        <div id="selected-user-details" style="flex:2; min-width:300px; border:1px solid #ddd; padding:15px;">
            <h2>Select a user to view details</h2>
        </div>

    </div>

    <script>
        (function () {
            const searchInput = document.getElementById('user-search-input');
            const users = Array.from(document.querySelectorAll('.user-item'));
            const noUsers = document.getElementById('no-users-found');
            const detailsDiv = document.getElementById('selected-user-details');

            function renderDetails(userId) {
                const user = users.find(u => u.dataset.id === userId);
                if (!user) return;

                detailsDiv.innerHTML = `
            <h2>Details for ${user.querySelector('strong').innerText}</h2>
            <ul>
                <li><strong>Username:</strong> ${user.dataset.login}</li>
                <li><strong>Display Name:</strong> ${user.querySelector('strong').innerText}</li>
                <li><strong>Email:</strong> ${user.dataset.email}</li>
                <li><strong>Role(s):</strong> ${user.dataset.roles}</li>
                <li><strong>Registered On:</strong> ${user.dataset.registered}</li>
            </ul>
        `;
            }

            searchInput.addEventListener('input', () => {
                const q = searchInput.value.toLowerCase();
                let visible = 0;
                users.forEach(u => {
                    const name = u.dataset.name;
                    const email = u.dataset.email;
                    const match = name.includes(q) || email.includes(q);
                    u.style.display = match ? 'block' : 'none';
                    if (match) visible++;
                });
                noUsers.style.display = visible === 0 ? 'block' : 'none';
            });

            users.forEach(u => {
                u.addEventListener('click', () => renderDetails(u.dataset.id));
            });
        })();
    </script>

    <?php
    return ob_get_clean();
}
add_shortcode('user_details', 'elearn_manager_dash_user_details_shortcode');
