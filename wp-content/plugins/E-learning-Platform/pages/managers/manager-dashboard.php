<?php
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

    $manager_org_id = get_user_meta($current_user->ID, 'organisation_id', true);
    if (empty($manager_org_id)) $manager_org_id = 0;

    $all_users = get_users([
        'meta_key'   => 'organisation_id',
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

        if (!isset($attempt_count_lookup[$uid])) $attempt_count_lookup[$uid] = [];
        if (!isset($attempt_count_lookup[$uid][$mid])) $attempt_count_lookup[$uid][$mid] = 0;

        $attempt_count_lookup[$uid][$mid]++;
    }


    $cert_table = $wpdb->prefix . 'elearn_certificate';
    $certs = $wpdb->get_results("SELECT attempt_id, user_id, certificate_completion FROM {$cert_table}");

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
    <p>Welcome, <?php echo esc_html($current_user->display_name); ?>!</p>

    <ul>
        <li><a href="<?php echo esc_url(get_permalink(get_page_by_path('organisation-details'))); ?>">Manage Organisation</a></li>
        <li><a href="<?php echo esc_url(get_permalink(get_page_by_path('user-details'))); ?>">Manage Users</a></li>
    </ul>

    <div style="display:flex; gap:20px; align-items:flex-start; margin-top:20px;">

        <!-- Left Sidebar: User & Module Selection -->
        <div style="flex:1; min-width:300px;">

            <!-- User Selection -->
            <h2>Select Users</h2>
            <input id="user-search-input" type="text" placeholder="Search users..." style="margin-bottom:8px; padding:5px; width:100%;">
            <select id="user-sort-order" style="margin-bottom:10px; width:100%;">
                <option value="asc">Sort A–Z</option>
                <option value="desc">Sort Z–A</option>
            </select>

            <div style="max-height:200px; overflow-y:auto; border:1px solid #ccc; padding:10px;" id="users-list-wrapper">
                <label style="display:block; margin-bottom:5px; font-weight:bold;">
                    <input type="checkbox" id="select-all-users"> Select All Users
                </label>
                <div id="users-list">
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
            <input id="module-search-input" type="text" placeholder="Search modules..." style="margin-bottom:8px; padding:5px; width:100%;">
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
                <button type="button" id="apply-button" style="width:100%;">Apply</button>
            </div>



        </div>

        <!-- Right: Users × Modules Table -->
        <div style="flex:2; min-width:400px;">
            <h2>Users and Progress</h2>
            <div style="overflow-x:auto;">
                <table class="module-user-table" style="border:2px solid black; border-collapse:collapse; width:100%; text-align:center;">
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
        </div>

    </div>
</div>

<script>
(function() {
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
        visible.sort((a,b)=>{
            const nameA = a.querySelector('span').innerText.toLowerCase();
            const nameB = b.querySelector('span').innerText.toLowerCase();
            return sortSelect.value==='asc'? nameA.localeCompare(nameB) : nameB.localeCompare(nameA);
        });
        visible.forEach(item => item.parentNode.appendChild(item));
    }
        document.getElementById('apply-button').addEventListener('click', ()=>{
        const selectedUserIds = getSelectedValues(userItems);
        const selectedModuleObjs = moduleItems.filter(i=>i.querySelector('input').checked).map(i=>{
            return {value:i.querySelector('input').value,label:i.querySelector('.module-label-name').innerText};
        });

        renderTable(selectedUserIds, selectedModuleObjs);
    });

    function updateSelectAll(items, selectAll) {
        const visible = items.filter(item => item.style.display!=='none').map(i=>i.querySelector('input'));
        if(visible.length===0){ selectAll.checked=false; selectAll.indeterminate=false; return; }
        const allChecked = visible.every(cb=>cb.checked);
        const someChecked = visible.some(cb=>cb.checked);
        selectAll.checked = allChecked;
        selectAll.indeterminate = !allChecked && someChecked;
    }

    function getSelectedValues(items){ return items.filter(i=>i.querySelector('input').checked).map(i=>i.querySelector('input').value); }

function renderTable(selectedUsers, selectedModules){
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
            if(certLookup[uidInt] && certLookup[uidInt][midInt]){
                certText = new Date(certLookup[uidInt][midInt]).toLocaleString();
            }

            // Attempts
            const attempts = (attemptCountLookup[uidInt] && attemptCountLookup[uidInt][midInt])
                                ? attemptCountLookup[uidInt][midInt]
                                : 0;

            // Display both stacked
            td.innerText = `Certificate: ${certText}\nAttempts: ${attempts}`;
            td.style.whiteSpace = 'pre-line';       // Allow line breaks
            td.style.border = '1px solid black';    // Add border
            td.style.padding = '4px';
            tr.appendChild(td);
        });

        tbody.appendChild(tr);
    });
}



    // Event Listeners
    userSearch.addEventListener('input', ()=>{ filterAndSort(userItems,userSearch,userSort); updateSelectAll(userItems,selectAllUsers); });
    userSort.addEventListener('change', ()=>{ filterAndSort(userItems,userSearch,userSort); });
    selectAllUsers.addEventListener('change', ()=>{
        userItems.filter(i=>i.style.display!=='none').forEach(i=>i.querySelector('input').checked=selectAllUsers.checked);
    });
    userItems.forEach(i=>i.querySelector('input').addEventListener('change', ()=>updateSelectAll(userItems,selectAllUsers)));

    moduleSearch.addEventListener('input', ()=>{ filterAndSort(moduleItems,moduleSearch,moduleSort); updateSelectAll(moduleItems,selectAllModules); });
    moduleSort.addEventListener('change', ()=>{ filterAndSort(moduleItems,moduleSearch,moduleSort); });
    selectAllModules.addEventListener('change', ()=>{
        moduleItems.filter(i=>i.style.display!=='none').forEach(i=>i.querySelector('input').checked=selectAllModules.checked);
    });
    moduleItems.forEach(i=>i.querySelector('input').addEventListener('change', ()=>updateSelectAll(moduleItems,selectAllModules)));


    // Initial render with everything selected
    const initialUserIds = getSelectedValues(userItems);
    const initialModules = moduleItems.map(i=>{ return {value:i.querySelector('input').value, label:i.querySelector('.module-label-name').innerText}; });
    renderTable(initialUserIds, initialModules);

})();
</script>

<?php
return ob_get_clean();
}
add_shortcode('manager_dashboard', 'elearn_manager_dash_shortcode');
