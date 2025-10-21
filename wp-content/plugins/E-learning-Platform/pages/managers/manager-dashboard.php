<?php
if (!defined('ABSPATH'))
    exit;

function elearn_manager_dash()
{
    if (!get_page_by_path('manager-dashboard')) {
        wp_insert_post([
            'post_name' => 'manager-dashboard',
            'post_content' => '[manager_dashboard]',
            'post_status' => 'publish',
            'post_type' => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_manager_dash');
add_action('init', 'elearn_manager_dash');

function elearn_manager_dash_shortcode()
{
    ob_start();
    ?>
    <style>
        .manager-dashboard {
            font-family: "Segoe UI", Tahoma, sans-serif;
            color: #333;
            max-width: 1400px;
            margin: 0 auto;
            padding: 25px;
        }

        /* Headings & Text */
        .manager-dashboard h1 {
            text-align: center;
            color: #222;
            margin-bottom: 20px;
        }
        .manager-dashboard h2, .manager-dashboard h4 {
            color: #333;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        .manager-dashboard p {
            line-height: 1.6;
            color: #555;
        }


        /* Table Styling */
        .manager-dashboard table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
            margin-top: 10px;
        }
        .manager-dashboard th, 
        .manager-dashboard td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        .manager-dashboard thead {
            background: #666;
            color: #fff;
            position: sticky;
            top: 0;
        }
        .manager-dashboard tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .manager-dashboard tbody tr:hover {
            background: #eef6fb;
        }

        /* Buttons */
        .manager-dashboard button,
        .manager-dashboard .button,
        .manager-dashboard input[type="submit"],
        .manager-dashboard #apply-button {
            background: #3498db;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        .manager-dashboard button:hover,
        .manager-dashboard .button:hover,
        .manager-dashboard input[type="submit"]:hover,
        .manager-dashboard #apply-button:hover {
            background: #2c80b4;
            transform: translateY(-1px);
        }
        .manager-dashboard .button-secondary {
            background: #eee;
            color: #333;
            border: 1px solid #ccc;
        }
        .manager-dashboard .button-secondary:hover {
            background: #ddd;
        }

        /* Forms and Inputs */
        .manager-dashboard input[type="text"],
        .manager-dashboard select {
            width: 100%;
            padding: 6px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 8px;
            box-sizing: border-box;
        }

        /* Scrollable Lists */
        #users-list-wrapper, #modules-list-wrapper {
            max-height: 220px;
            overflow-y: auto;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 8px;
            background: #fafafa;
        }
        .user-item, .module-item {
            display: block;
            margin-bottom: 5px;
            padding: 3px 5px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .user-item:hover, .module-item:hover {
            background: #f0f8ff;
        }

        /* Layout Grid */
        .manager-dashboard > div {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .manager-dashboard > div > div {
            flex: 1;
            min-width: 320px;
        }

        /* Table Container */
        .module-user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            text-align: center;
        }
        .module-user-table th, .module-user-table td {
            border: 1px solid #999;
            padding: 6px;
        }
        .module-user-table th {
            background: #666;
            color: #fff;
        }
        .module-user-table td a {
            color: #3498db;
            text-decoration: none;
        }
        .module-user-table td a:hover {
            text-decoration: underline;
        }

        /* CSV Button */
        #export-csv-form button {
            width: 100%;
            background: #3498db;
            border-radius: 6px;
            padding: 10px;
            font-size: 15px;
        }
    </style>

    <?php

    if (!is_user_logged_in()) {
        return '<p>You must be logged in to access this page.</p>';
    }

    $current_user = wp_get_current_user();
    $allowed_roles = ['manager', 'administrator'];
    if (!array_intersect($allowed_roles, (array) $current_user->roles)) {
        return '<p>You do not have permission to access this page.</p>';
    }

    global $wpdb;

    $manager_org_id = get_user_meta($current_user->ID, 'organisation_id', true);
    if (empty($manager_org_id))
        $manager_org_id = 0;

    $organisation_table = $wpdb->prefix . 'elearn_organisation';
    $org_name = $wpdb->get_var(
        $wpdb->prepare("SELECT organisation_name FROM $organisation_table WHERE organisation_id = %s", $manager_org_id)
    );

    $all_users = get_users([
        'meta_key' => 'organisation_id',
        'meta_value' => $manager_org_id,
    ]);

    $table_name = $wpdb->prefix . 'elearn_module';
    $modules = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY module_name ASC");

    $attempt_table = $wpdb->prefix . 'elearn_attempt';
    $attempts = $wpdb->get_results("SELECT attempt_id, user_id, module_module_id FROM {$attempt_table}");
    $attempt_count_lookup = [];
    foreach ($attempts as $attempt) {
        $uid = $attempt->user_id;
        $mid = $attempt->module_module_id;

        if (!isset($attempt_count_lookup[$uid]))
            $attempt_count_lookup[$uid] = [];
        if (!isset($attempt_count_lookup[$uid][$mid]))
            $attempt_count_lookup[$uid][$mid] = 0;

        $attempt_count_lookup[$uid][$mid]++;
    }


    $cert_table = $wpdb->prefix . 'elearn_certificate';
    $certs = $wpdb->get_results("SELECT attempt_id, user_id, certificate_completion, certificate_id FROM {$cert_table}");

    $cert_lookup = [];
    $cert_view_page = get_page_by_path('cert-view');
    $cert_view_url = $cert_view_page ? get_permalink($cert_view_page->ID) : '#';

    foreach ($certs as $cert) {
        foreach ($attempts as $attempt) {
            if ($attempt->attempt_id == $cert->attempt_id) {
                $uid = $attempt->user_id;
                $mid = $attempt->module_module_id;

                $cert_lookup[$uid][$mid] = [
                    'completed' => $cert->certificate_completion,
                    'url' => add_query_arg([
                        'module_id' => intval($mid),
                        'cert_id' => intval($cert->certificate_id),
                        'user_id' => intval($uid)
                    ], $cert_view_url)
                ];
            }
        }
    }

    ?>
    
    <div class="manager-dashboard">

        <h1>Manager Dashboard</h1>
        <p>Welcome, <?php echo esc_html($current_user->display_name); ?>! </br>
        Here you can access organisation details, supply new staff an acces code and view organisation-wide module completion.</p>
            <h4>Organisation Details:</h4>
        <p><b>Name:</b> <?php echo esc_html($org_name ? $org_name : $manager_org_id); ?></p>
        <p><b>Organisation ID:</b> <?php echo esc_html($manager_org_id); ?></p>


        <ul>
            <li><a href="<?php echo esc_url(get_permalink(get_page_by_path('organisation-details'))); ?>">Manage
                    Organisation</a></li>
            <li><a href="<?php echo esc_url(get_permalink(get_page_by_path('user-details'))); ?>">Manage Users</a></li>
        </ul>

        <?php
        //generate access Code for organisation
    
        $organisation_id = $manager_org_id;

        // Access Code Management
        $access_code_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}elearn_access WHERE organisation_organisation_id = %s",
                $organisation_id
            )
        );

        // Handle removing access codes
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_access_code'])) {
            $result = $wpdb->delete(
                $wpdb->prefix . 'elearn_access',
                ['organisation_organisation_id' => $organisation_id],
                ['%s']
            );

            if ($result !== false) {
                ?>
                <!-- <div class="notice notice-success"><p>Access code removed successfully!</p></div> -->

                <?php
            } else {
                ?>
                <div class="notice notice-error">
                    <!-- <p>Failed to remove access code: <?php echo esc_html($wpdb->last_error); ?></p> -->
                </div>
                <?php
            }
        }

        // Handle creating a new access code
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_access_code'])) {
            if ($access_code_count > 0) {
                ?>
                <div class="notice notice-error">
                    <!-- <p>An access code already exists for this organisation. Please remove the existing code before creating a new one.</p> -->
                </div>
                <?php
            } else {
                // Generate a unique access code
                $access_code = wp_generate_password(12, false); // 12-character alphanumeric code
    
                // Insert into database
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
                    ?>
                    <div class="notice notice-success">
                        <!-- <p>Access code generated successfully: <strong><?php echo esc_html($access_code); ?></strong></p> -->
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="notice notice-error">
                        <!-- <p>Failed to generate access code: <?php echo esc_html($wpdb->last_error); ?></p> -->
                    </div>
                    <?php
                }
            }
        }

        // Fetch access codes for this organisation
        $access_codes = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT access_id, access_code, is_used, access_created, access_used 
        FROM {$wpdb->prefix}elearn_access 
        WHERE organisation_organisation_id = %s",
                $organisation_id
            )
        );
        ?>

        <h4>Access Code</h4>
        <p>The access code below can be eamiled to your staff to register new users within your organisation.</p>
        <?php if (!empty($access_codes)): ?>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Access Code</th>
                        <th>Created</th>
                        <th>Used By:</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($access_codes as $code): ?>
                        <tr>
                            <td><?php echo esc_html($code->access_code); ?></td>
                            <td><?php echo esc_html($code->access_created); ?></td>
                            <td><?php echo $code->access_used ? esc_html($code->access_used) : '0'; ?></td>
                            <td>
                                <?php
                                // Prepare mailto link                              
                                $message = "Dear <User>\r\n\r\n";
                                $message .= "We at {$org_name} use the Fitness Frontline Resource Licensing System.\r\n\r\n";
                                $message .= "The Fitness Frontline Resource Licensing System has:\r\n";
                                $message .= "▪ Free and regular access to a range of health, fitness and wellbeing books\r\n";
                                $message .= "▪ Some books are accompanied by a simple self-paced learning module\r\n\r\n";
                                $message .= "To Set up the Account:\r\n";
                                $message .= "Step 1. Register for a User Account using organisation credentials:\r\n";
                                $message .= "https://workhealthandfitnessrecord.com.au/register/\r\n\r\n";
                                $message .= "Step 2. Link your user account to your organisation for full access to the resources:\r\n";
                                $message .= "https://workhealthandfitnessrecord.com.au/user-register/\r\n\r\n";
                                $message .= "Enter the Access code: {$code->access_code}\r\n\r\n";
                                $message .= "Please feel free to reach out if you have questions about the process.\r\n\r\n";
                                $message .= "Regards\r\n<Insert Signature>";

                                $subject = rawurlencode("Register for Fitness Frontline E-Learning Platform");
                                $body = rawurlencode($message);
                                $mailto = 'mailto:?subject=' . $subject . '&body=' . $body;
                                ?>
                                <a href="<?php echo esc_attr($mailto); ?>"
                                    style="padding:6px 12px; background-color:#0073aa; color:#fff; text-decoration:none; border-radius:3px; display:inline-block;">
                                    Email Access Code
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        <?php else: ?>
            <p>No access codes found for this organisation.</p>
        <?php endif; ?>
        <?php
        // After creating or removing a code
        $access_code_count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}elearn_access WHERE organisation_organisation_id = %s",
                $organisation_id
            )
        );


        if ($access_code_count > 0): ?>
            <!-- Remove Access Code Button (shown if a code exists) -->
            <form method="POST" style="margin-top: 20px; display:inline-block;">
                <button type="submit" name="remove_access_code" class="button button-secondary"
                    style="color: red; border-color: red; padding:5px 15px;">
                    Remove Access Code
                </button>
            </form>
        <?php else: ?>
            <!-- Generate Access Code Button (shown if no code exists) -->
            <form method="POST" style="margin-top: 20px; display:inline-block;">
                <button type="submit" name="create_access_code" class="button button-primary" style="padding:5px 15px;">
                    Generate Access Code
                </button>
            </form>
        <?php endif; ?>



        <!-- Dashboard -->
        <div style="display:flex; gap:20px; align-items:flex-start; margin-top:20px;">

            <!-- Left Sidebar: User & Module Selection -->
            <div style="flex:1; min-width:300px;">

                <!-- User Selection -->
                <h2>Select Users</h2>
                <input id="user-search-input" type="text" placeholder="Search users..."
                    style="margin-bottom:8px; padding:5px; width:100%;">
                <select id="user-sort-order" style="margin-bottom:10px; width:100%;">
                    <option value="asc">Sort A–Z</option>
                    <option value="desc">Sort Z–A</option>
                </select>

            <div style="max-height:200px; overflow-y:auto; border:1px solid #ccc; padding:10px;" id="users-list-wrapper">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">
                    <input type="checkbox" id="select-all-users"> Select All Users
                </label>
                <div id="users-list" style =" text-align:left;">
                    <?php foreach ($all_users as $user) : 
                        $data_name  = esc_attr(strtolower($user->display_name));
                        $data_email = esc_attr(strtolower($user->user_email));
                    ?>
                        <label class="user-item" data-name="<?php echo $data_name; ?>" data-email="<?php echo $data_email; ?>" style="display:block; margin-bottom:5px;">
                            <input type="checkbox" class="user-checkbox" value="<?php echo esc_attr($user->ID); ?>" checked>
                            <span class="user-label-name"><?php echo esc_html($user->display_name); ?></span>
                            <span class="user-label-email" style="color:#666; font-size:0.9em;"> &lt;<?php echo esc_html($user->user_email); ?>&gt;</span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div id="no-users-found" style="display:none; margin-top:8px; color:#b00;">No users found.</div>
            </div>

                <!-- Module Selection -->
                <h2 style="margin-top:20px;">Select Modules</h2>
                <input id="module-search-input" type="text" placeholder="Search modules..."
                    style="margin-bottom:8px; padding:5px; width:100%;">
                <select id="module-sort-order" style="margin-bottom:10px; width:100%;">
                    <option value="asc">Sort A–Z</option>
                    <option value="desc">Sort Z–A</option>
                </select>

            <div style="max-height:200px; overflow-y:auto; border:1px solid #ccc; padding:10px;" id="modules-list-wrapper">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">
                    <input type="checkbox" id="select-all-modules" checked> Select All Modules
                </label>
                <div id="modules-list">
                    <?php foreach ($modules as $module) : ?>
                        <?php if ($module->module_name === "Demo Module") continue; ?>
                        <label class="module-item" data-name="<?php echo esc_attr(strtolower($module->module_name)); ?>" style="display:block; margin-bottom:5px;">
                            <input type="checkbox" class="module-checkbox" value="<?php echo esc_attr($module->module_id); ?>" checked>
                            <span class="module-label-name"><?php echo esc_html($module->module_name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div id="no-modules-found" style="display:none; margin-top:8px; color:#b00;">No modules found.</div>
            </div>

                <!-- Apply Button -->
                <div id="apply-form-wrapper" style="margin-top:6px;">
                    <button type="button" id="apply-button" style="width:100%;">Apply Selected User and Module Filters</button>
                </div>

            </div>

            <!-- Right: Users × Modules Table -->
            <div style="flex:2; min-width:400px;">
                <h2>Users and Progress</h2>
                <p>User and module filters selected on the left.</p>
                <div style="overflow-x:auto;  max-height: 700px;">
                    <table class="module-user-table"
                        style="border:2px solid black; border-collapse:collapse; width:100%; text-align:center;">
                        <thead>
                            <tr id="table-header">
                                <th style="min-width:150px;">User</th>
                                <!-- Module headers dynamically inserted -->
                            </tr>
                        </thead>
                        <tbody id="module-user-tbody">
                            <!-- Table rows dynamically inserted -->
                        </tbody>
                    </table>
                </div>

                <!-- Download CSV Button Under Table -->
                <form id="export-csv-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                    style="margin-top:15px;">
                    <input type="hidden" name="action" value="export_user_progress">
                    <div id="export-user-fields"></div> <!-- JS will populate this -->
                    <button type="submit" style="width:100%;">Download CSV</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        (function () {
            // Users & Modules DOM
            const userSearch = document.getElementById('user-search-input');
            const userSort = document.getElementById('user-sort-order');
            const userItems = Array.from(document.querySelectorAll('.user-item'));
            const selectAllUsers = document.getElementById('select-all-users');

            const moduleSearch = document.getElementById('module-search-input');
            const moduleSort = document.getElementById('module-sort-order');
            const moduleItems = Array.from(document.querySelectorAll('.module-item'));
            const selectAllModules = document.getElementById('select-all-modules');

            const certLookup = <?php echo json_encode($cert_lookup); ?>;
            const attemptCountLookup = <?php echo json_encode($attempt_count_lookup); ?>;

            const applyForm = document.getElementById('apply-form');
            const applyInputs = document.getElementById('apply-selected-inputs');
            const tbody = document.getElementById('module-user-tbody');
            const tableHeader = document.getElementById('table-header');

            function filterAndSort(items, searchInput, sortSelect) {
                const q = searchInput.value.trim().toLowerCase();
                let visible = items.filter(item => {
                    const name = item.getAttribute('data-name');
                    const match = name.includes(q);
                    item.style.display = match ? 'block' : 'none';
                    return match;
                });
                visible.sort((a, b) => {
                    const nameA = a.querySelector('span').innerText.toLowerCase();
                    const nameB = b.querySelector('span').innerText.toLowerCase();
                    return sortSelect.value === 'asc' ? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
                });
                visible.forEach(item => item.parentNode.appendChild(item));

                const noUsersDiv = document.getElementById('no-users-found');
                if (items[0].parentNode.id === 'users-list') { // only for users list
                    noUsersDiv.style.display = visible.length === 0 ? 'block' : 'none';
                }

                const noModulesDiv = document.getElementById('no-modules-found');
                if (items[0].parentNode.id === 'modules-list') { // only for modules list
                    noModulesDiv.style.display = visible.length === 0 ? 'block' : 'none';
                }

            }
            document.getElementById('apply-button').addEventListener('click', () => {
                const selectedUserIds = getSelectedValues(userItems);
                const selectedModuleObjs = moduleItems.filter(i => i.querySelector('input').checked).map(i => {
                    return { value: i.querySelector('input').value, label: i.querySelector('.module-label-name').innerText };
                });

                renderTable(selectedUserIds, selectedModuleObjs);
            });

            function updateSelectAll(items, selectAll) {
                const visible = items.filter(item => item.style.display !== 'none').map(i => i.querySelector('input'));
                if (visible.length === 0) { selectAll.checked = false; selectAll.indeterminate = false; return; }
                const allChecked = visible.every(cb => cb.checked);
                const someChecked = visible.some(cb => cb.checked);
                selectAll.checked = allChecked;
                selectAll.indeterminate = !allChecked && someChecked;
            }

            function getSelectedValues(items) { return items.filter(i => i.querySelector('input').checked).map(i => i.querySelector('input').value); }

            function renderTable(selectedUsers, selectedModules) {
                tbody.innerHTML = '';

                // Clear previous headers except first
                tableHeader.querySelectorAll('th:not(:first-child)').forEach(th => th.remove());

                // Add module headers with borders
                selectedModules.forEach(modName => {
                    const th = document.createElement('th');
                    th.style.minWidth = '120px';
                    th.innerText = modName.label;
                    th.style.border = '1px solid black';   // Add border to header
                    th.style.padding = '4px';               // Add padding for readability
                    tableHeader.appendChild(th);
                });

                selectedUsers.forEach(uid => {
                    const uidInt = parseInt(uid);
                    const userItem = userItems.find(u => parseInt(u.querySelector('input').value) === uidInt);
                    if (!userItem) return;

                    const name = userItem.querySelector('.user-label-name').innerText;
                    const tr = document.createElement('tr');

                    // User name cell
                    const tdName = document.createElement('td');
                    tdName.innerText = name;
                    tdName.style.border = '1px solid black'; // Add border
                    tdName.style.padding = '4px';
                    tr.appendChild(tdName);

                    // Module cells
                    selectedModules.forEach(mod => {
                        const midInt = parseInt(mod.value);
                        const td = document.createElement('td');

                        // Certificate
                        let certText = 'Not Completed';
                        if (certLookup[uidInt] && certLookup[uidInt][midInt]) {
                            const certData = certLookup[uidInt][midInt];
                            if (certData.completed && certData.completed !== '0000-00-00 00:00:00') {
                                certText = `<a href="${certData.url}" target="_blank">${certData.completed}</a>`;
                            } else {
                                certText = 'In Progress';
                            }
                        }


                        // Attempts
                        const attempts = (attemptCountLookup[uidInt] && attemptCountLookup[uidInt][midInt])
                            ? attemptCountLookup[uidInt][midInt]
                            : 0;

                        // Display both stacked
                        td.innerHTML = `Certificate: ${certText}\nAttempts: ${attempts}`;
                        td.style.whiteSpace = 'pre-line';       // Allow line breaks
                        td.style.border = '1px solid black';    // Add border
                        td.style.padding = '4px';
                        tr.appendChild(td);
                    });

                    tbody.appendChild(tr);
                });
            }



            // Event Listeners
            userSearch.addEventListener('input', () => { filterAndSort(userItems, userSearch, userSort); updateSelectAll(userItems, selectAllUsers); });
            userSort.addEventListener('change', () => { filterAndSort(userItems, userSearch, userSort); });
            selectAllUsers.addEventListener('change', () => {
                userItems.filter(i => i.style.display !== 'none').forEach(i => i.querySelector('input').checked = selectAllUsers.checked);
            });
            userItems.forEach(i => i.querySelector('input').addEventListener('change', () => updateSelectAll(userItems, selectAllUsers)));

            moduleSearch.addEventListener('input', () => { filterAndSort(moduleItems, moduleSearch, moduleSort); updateSelectAll(moduleItems, selectAllModules); });
            moduleSort.addEventListener('change', () => { filterAndSort(moduleItems, moduleSearch, moduleSort); });
            selectAllModules.addEventListener('change', () => {
                moduleItems.filter(i => i.style.display !== 'none').forEach(i => i.querySelector('input').checked = selectAllModules.checked);
            });
            moduleItems.forEach(i => i.querySelector('input').addEventListener('change', () => updateSelectAll(moduleItems, selectAllModules)));


            // Initial render with everything selected
            const initialUserIds = getSelectedValues(userItems);
            const initialModules = moduleItems.map(i => { return { value: i.querySelector('input').value, label: i.querySelector('.module-label-name').innerText }; });
            renderTable(initialUserIds, initialModules);


            document.getElementById('export-csv-form').addEventListener('submit', function (e) {
                const container = document.getElementById('export-user-fields');
                container.innerHTML = ''; // Clear previous inputs

                // Find all checked user checkboxes
                const selectedUsers = Array.from(document.querySelectorAll('.user-checkbox'))
                    .filter(cb => cb.checked)
                    .map(cb => cb.value);

                // Add a hidden input for each selected user
                selectedUsers.forEach(uid => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_users[]';
                    input.value = uid;
                    container.appendChild(input);
                });
            });
            const selectedModules = Array.from(document.querySelectorAll('.module-checkbox'))
                .filter(cb => cb.checked)
                .map(cb => cb.value);

            selectedModules.forEach(mid => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_modules[]';
                input.value = mid;
                container.appendChild(input);
            });

        })();
    </script>


    <?php
    return ob_get_clean();
}
add_shortcode('manager_dashboard', 'elearn_manager_dash_shortcode');
