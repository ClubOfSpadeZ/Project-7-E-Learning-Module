<?php
// Make sure this is running inside WordPress
if ( ! defined( 'ABSPATH' ) ) exit;

function elearn_manager_dash() {
    if (!get_page_by_path('manager-dashboard')) {
        wp_insert_post([
            'post_name'    => 'manager-dashboard',
            'post_content' => '[manager_dashboard]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_manager_dash');
add_action('init', 'elearn_manager_dash');

function elearn_manager_dash_shortcode() {
    ob_start();
    $current_user = wp_get_current_user();
    global $wpdb;

    // Get all users
    $users = get_users();

    // Handle search & sort
    $search = isset($_POST['user_search']) ? sanitize_text_field($_POST['user_search']) : '';
    $sort   = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'asc';

    if ($search) {
        $users = array_filter($users, function($u) use ($search) {
            return stripos($u->display_name, $search) !== false || stripos($u->user_email, $search) !== false;
        });
    }

    usort($users, function($a, $b) use ($sort) {
        return $sort === 'asc' ? strcasecmp($a->display_name, $b->display_name) : strcasecmp($b->display_name, $a->display_name);
    });

    $selected_user_ids = isset($_POST['selected_users']) ? $_POST['selected_users'] : [];
    $selected_users = array_filter($users, function($u) use ($selected_user_ids) {
        return in_array($u->ID, $selected_user_ids);
    });

    // Get modules
    $table_name = $wpdb->prefix . 'elearn_module';
    $modules = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY module_name ASC");

    // Fetch all attempts
    $attempt_table = $wpdb->prefix . 'elearn_attempt';
    $attempts = $wpdb->get_results("SELECT attempt_id, user_id, module_module_id FROM {$attempt_table}");

    // Fetch all certificates
    $cert_table = $wpdb->prefix . 'elearn_certificate';
    $certs = $wpdb->get_results("SELECT attempt_id, user_id, certificate_completion FROM {$cert_table}");

    // Build lookup [user_id][module_id] => certificate_time
    $cert_lookup = [];
    foreach ($certs as $cert) {
        foreach ($attempts as $attempt) {
            if ($attempt->attempt_id == $cert->attempt_id) {
                $cert_lookup[$attempt->user_id][$attempt->module_module_id] = $cert->certificate_completion;
            }
        }
    }
    ?>

<div class="manager-dashboard">
    <h1>Manager Dashboard</h1>

    <?php if ($current_user->exists()) : ?>
        <p>Welcome, <?php echo esc_html($current_user->display_name); ?>!</p>
    <?php else : ?>
        <p>Welcome, Guest, you shouldn’t be here!</p>
    <?php endif; ?>

    <ul>
        <li><a href="<?php echo esc_url(get_permalink(get_page_by_path('organisation-details'))); ?>">Manage Organisation</a></li>
        <li><a href="<?php echo esc_url(get_permalink(get_page_by_path('user-details'))); ?>">Manage Users</a></li>
        <li><a href="<?php echo esc_url(get_permalink(get_page_by_path('access-management'))); ?>">Manage Access</a></li>
        <li><a href="<?php echo esc_url(get_permalink(get_page_by_path('reports'))); ?>">Reports</a></li>
    </ul>

    <div style="display:flex; gap:20px; align-items:flex-start; margin-top:20px;">

        <!-- User Selection -->
        <div style="flex:1; min-width:300px;">
            <h2>Select Users</h2>
            <form method="post">
                <input type="text" name="user_search" placeholder="Search users..." value="<?php echo esc_attr($search); ?>" style="margin-bottom:8px; padding:5px; width:100%;">
                <select name="sort_order" onchange="this.form.submit()" style="margin-bottom:10px; width:100%;">
                    <option value="asc" <?php selected($sort, 'asc'); ?>>Sort A–Z</option>
                    <option value="desc" <?php selected($sort, 'desc'); ?>>Sort Z–A</option>
                </select>

                <div style="max-height:400px; overflow-y:auto; border:1px solid #ccc; padding:10px;">
                    <label style="display:block; margin-bottom:5px; font-weight:bold;">
                        <input type="checkbox" id="select-all-users"> Select All Users
                    </label>

                    <?php foreach ($users as $user) : ?>
                        <label style="display:block; margin-bottom:5px;">
                            <input type="checkbox" class="user-checkbox" name="selected_users[]" value="<?php echo esc_attr($user->ID); ?>"
                                <?php echo in_array($user->ID, $selected_user_ids) ? 'checked' : ''; ?>>
                            <?php echo esc_html($user->display_name); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <br>
                <input type="submit" value="Apply" style="width:100%;">
            </form>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const selectAll = document.getElementById('select-all-users');
                const checkboxes = document.querySelectorAll('.user-checkbox');

                selectAll.addEventListener('change', function() {
                    checkboxes.forEach(cb => cb.checked = selectAll.checked);
                });
            });
            </script>
        </div>

        <!-- Modules × Users Table -->
        <div style="flex:2; min-width:400px;">
            <?php if (!empty($selected_users)) : ?>
                <h2>Users and Progress</h2>

                <style>
                .module-user-table {
                    border: 2px solid black;
                    border-collapse: collapse;
                    width: 100%;
                    text-align: center;
                }
                .module-user-table th, .module-user-table td {
                    border: 2px solid black;
                    padding: 5px;
                    word-wrap: break-word;
                }
                </style>

                <div style="overflow-x:auto;">
                    <table class="module-user-table">
                        <thead>
                            <tr>
                                <th style="min-width:150px;">User</th>
                                <?php foreach ($modules as $module) : ?>
                                    <th style="min-width:120px;"><?php echo esc_html( $module->module_name . ' (' . $module->module_id . ')' ); ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($selected_users as $user) : ?>
                                <tr>
                                    <td><?php echo esc_html($user->display_name); ?></td>
                                    <?php foreach ($modules as $module) : ?>
                                        <td>
                                            <?php
                                                $user_id = $user->ID;
                                                $module_id = $module->module_id;

                                                if (isset($cert_lookup[$user_id][$module_id])) {
                                                    $cert_time = $cert_lookup[$user_id][$module_id];
                                                    $dt = new DateTime($cert_time);
                                                    $value = $dt->format('d/m/Y h:i A');
                                                } else {
                                                    $value = 'N/A';
                                                }

                                                echo esc_html($value);
                                            ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php foreach ($selected_user_ids as $uid) : ?>
                        <input type="hidden" name="selected_users[]" value="<?php echo esc_attr($uid); ?>">
                    <?php endforeach; ?>
                    <input type="hidden" name="action" value="export_user_progress">
                    <button type="submit" style="margin-top:10px;">Download CSV</button>
                </form>

            <?php endif; ?>
        </div>

    </div>
</div>

<?php
    return ob_get_clean();
}
add_shortcode('manager_dashboard', 'elearn_manager_dash_shortcode');
