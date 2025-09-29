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

    if ( ! is_user_logged_in() ) {
        return '<p>You must be logged in to access this page.</p>';
    }

    $current_user = wp_get_current_user();
    $allowed_roles = ['manager', 'administrator'];
    if ( ! array_intersect( $allowed_roles, (array) $current_user->roles ) ) {
        return '<p>You do not have permission to access this page.</p>';
    }

    global $wpdb;

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


    // Search/sort params
    $search = isset($_POST['user_search']) ? sanitize_text_field($_POST['user_search']) : '';
    $sort   = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'asc';

    // Filter users only for the display list
    $users = $all_users;
    if ($search !== '') {
        $users = array_filter($all_users, function($u) use ($search) {
            return stripos($u->display_name, $search) !== false || stripos($u->user_email, $search) !== false;
        });
    }

    // Apply sort
    usort($users, function($a, $b) use ($sort) {
        return $sort === 'asc' ? strcasecmp($a->display_name, $b->display_name) : strcasecmp($b->display_name, $a->display_name);
    });

    // Track selected users independently of search
    $selected_user_ids = isset($_POST['selected_users']) ? (array) $_POST['selected_users'] : [];
    $selected_users = array_filter($all_users, function($u) use ($selected_user_ids) {
        return in_array($u->ID, $selected_user_ids);
    });

    // Get modules
    $table_name = $wpdb->prefix . 'elearn_module';
    $modules = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY module_name ASC");

    // Fetch attempts & certificates
    $attempt_table = $wpdb->prefix . 'elearn_attempt';
    $attempts = $wpdb->get_results("SELECT attempt_id, user_id, module_module_id FROM {$attempt_table}");

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
    </ul>

    <div style="display:flex; gap:20px; align-items:flex-start; margin-top:20px;">

        <!-- User Selection -->
        <div style="flex:1; min-width:300px;">
            <h2>Select Users</h2>

            <input id="user-search-input" type="text" name="user_search" placeholder="Search users..." value="<?php echo esc_attr($search); ?>" style="margin-bottom:8px; padding:5px; width:100%;">

            <select id="sort-order-select" name="sort_order" style="margin-bottom:10px; width:100%;">
                <option value="asc" <?php selected($sort, 'asc'); ?>>Sort A–Z</option>
                <option value="desc" <?php selected($sort, 'desc'); ?>>Sort Z–A</option>
            </select>

            <div style="max-height:400px; overflow-y:auto; border:1px solid #ccc; padding:10px;" id="users-list-wrapper">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">
                    <input type="checkbox" id="select-all-users"> Select All Users
                </label>

                <div id="users-list">
                <?php foreach ($users as $user) : 
                    $data_name  = esc_attr(strtolower($user->display_name));
                    $data_email = esc_attr(strtolower($user->user_email));
                    $checked    = in_array($user->ID, $selected_user_ids) ? 'checked' : '';
                ?>
                    <label class="user-item" data-name="<?php echo $data_name; ?>" data-email="<?php echo $data_email; ?>" style="display:block; margin-bottom:5px;">
                        <input type="checkbox" class="user-checkbox" name="selected_users[]" value="<?php echo esc_attr($user->ID); ?>" <?php echo $checked; ?>>
                        <span class="user-label-name"><?php echo esc_html($user->display_name); ?></span>
                        <span class="user-label-email" style="color:#666; font-size:0.9em;"> &lt;<?php echo esc_html($user->user_email); ?>&gt;</span>
                    </label>
                <?php endforeach; ?>
                </div>

                <div id="no-users-found" style="display:none; margin-top:8px; color:#b00;">
                    No users found.
                </div>
            </div>

            <br>
            <form id="apply-form" method="post" style="margin-top:6px;">
                <input type="hidden" name="user_search" id="apply-user-search" value="<?php echo esc_attr($search); ?>">
                <input type="hidden" name="sort_order" id="apply-sort-order" value="<?php echo esc_attr($sort); ?>">
                <div id="apply-selected-inputs"></div>
                <button type="submit" style="width:100%;">Apply</button>
            </form>

            <script>
            (function(){
                function debounce(fn, wait) {
                    let t;
                    return function(...args) {
                        clearTimeout(t);
                        t = setTimeout(() => fn.apply(this, args), wait);
                    };
                }

                const searchInput = document.getElementById('user-search-input');
                const usersList = document.getElementById('users-list');
                const userItems = Array.from(document.querySelectorAll('.user-item'));
                const noUsersFound = document.getElementById('no-users-found');
                const selectAll = document.getElementById('select-all-users');
                const checkboxes = () => Array.from(document.querySelectorAll('.user-checkbox'));
                const sortSelect = document.getElementById('sort-order-select');

                function filterUsers() {
                    const q = searchInput.value.trim().toLowerCase();
                    let visibleCount = 0;

                    userItems.forEach(item => {
                        const name = item.getAttribute('data-name') || '';
                        const email = item.getAttribute('data-email') || '';
                        const matches = q === '' || name.indexOf(q) !== -1 || email.indexOf(q) !== -1;

                        item.style.display = matches ? 'block' : 'none';
                        if (matches) visibleCount++;
                    });

                    noUsersFound.style.display = visibleCount === 0 ? 'block' : 'none';
                    updateSelectAllState();
                }

                const debouncedFilter = debounce(filterUsers, 150);
                searchInput.addEventListener('input', debouncedFilter);

                selectAll.addEventListener('change', function() {
                    const visibleBoxes = checkboxes().filter(cb => cb.closest('.user-item').style.display !== 'none');
                    visibleBoxes.forEach(cb => cb.checked = selectAll.checked);
                });

                usersList.addEventListener('change', function(e){
                    if (e.target && e.target.classList && e.target.classList.contains('user-checkbox')) {
                        updateSelectAllState();
                    }
                });

                function updateSelectAllState() {
                    const visibleBoxes = checkboxes().filter(cb => cb.closest('.user-item').style.display !== 'none');
                    if (visibleBoxes.length === 0) {
                        selectAll.checked = false;
                        selectAll.indeterminate = false;
                        return;
                    }
                    const allChecked = visibleBoxes.every(cb => cb.checked);
                    const someChecked = visibleBoxes.some(cb => cb.checked);
                    selectAll.checked = allChecked;
                    selectAll.indeterminate = !allChecked && someChecked;
                }

                const applyForm = document.getElementById('apply-form');
                applyForm.addEventListener('submit', function(e) {
                    const container = document.getElementById('apply-selected-inputs');
                    container.innerHTML = '';

                    document.getElementById('apply-user-search').value = searchInput.value;
                    document.getElementById('apply-sort-order').value = sortSelect.value;

                    checkboxes().forEach(cb => {
                        if (cb.checked) {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'selected_users[]';
                            input.value = cb.value;
                            container.appendChild(input);
                        }
                    });
                });

                sortSelect.addEventListener('change', function(){
                    document.getElementById('apply-user-search').value = searchInput.value;
                    document.getElementById('apply-sort-order').value = sortSelect.value;
                    applyForm.submit();
                });

                filterUsers();
            })();
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
