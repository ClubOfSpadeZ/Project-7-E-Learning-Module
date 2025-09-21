<?php
if (!defined('ABSPATH')) exit;

function elearn_create_view_results_page() {
    if (!get_page_by_path('view-results')) {
        wp_insert_post([
            'post_title'   => 'View Results',
            'post_name'    => 'view-results',
            'post_content' => '[view-results]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_create_view_results_page');
add_action('init', 'elearn_create_view_results_page');

function elearn_view_results_shortcode() {
    $dashboard_page = get_page_by_path('user-module-dash');
    $dashboard_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/');

    global $wpdb;
    $user_id = get_current_user_id();
    $username = wp_get_current_user();
    $module_tbl = $wpdb->prefix . 'elearn_module';
    $attempt_tbl = $wpdb->prefix . 'elearn_attempt';
    $cert_tbl = $wpdb->prefix . 'elearn_certificate';
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT m.module_id, m.module_name, a.attempt_id, c.certificate_completion
        FROM $module_tbl m
        LEFT JOIN $attempt_tbl a ON m.module_id = a.module_module_id AND a.user_id = %d
        LEFT JOIN $cert_tbl c ON a.attempt_id = c.attempt_id
        ORDER BY m.module_id ASC", // optional default order
        $user_id
    ));
    
    ob_start();        
    //Display all modules with number of attempts, passed or not, most recent certificate completion time
    //if no attempts, show "No attempts yet"
    if (!empty($results)) {
        $modules_data = [];

        foreach ($results as $row) {
            $mod_id   = $row->module_id;
            $mod_name = $row->module_name;

            if (!isset($modules_data[$mod_id])) {
                $modules_data[$mod_id] = [
                    'name'      => $mod_name,
                    'attempts'  => 0,
                    'passed'    => 'No',
                    'cert_time' => 'No attempts yet'
                ];
            }

            if ($row->attempt_id) {
                $modules_data[$mod_id]['attempts']++;
                if ($row->certificate_completion) {
                    $modules_data[$mod_id]['passed'] = 'Yes';
                    $modules_data[$mod_id]['cert_time'] = $row->certificate_completion;
                }
            }
        }
    }

    //sort modules by module id
    ksort($modules_data);

    // Now echo table once with final data
    ?>
    <style>
        .elearn-results-table {
            width: 100%;
            border-collapse: collapse;
        }
        .elearn-results-table th, .elearn-results-table td {
            border: 1px solid black;
            padding: 5px;
            text-align: left;
            border-collapse: collapse;
        }
        
        .elearn-results-table thead, .elearn-results-table tbody {
            display: block;
        }
        .elearn-results-table tbody {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: hidden;
            background-color: #f2f2f2;
        }
        .elearn-results-table thead {
            width: calc(100% - 1em);  scrollbar width
            box-sizing: border-box;
            background-color: #ddd;
        }
        
        .elearn-results-table thead tr, .elearn-results-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

    </style>
    <a href="<?php echo esc_url($dashboard_url); ?>">&larr; Back to Dashboard</a><br><br>
    <div class="elearn-view-results">
        <h2>Personal completion results for <?php echo esc_html ($username->display_name);?></h2>
    </div>
    <div class="elearn-results-table-container">
        <table class="elearn-results-table">
            <thead>
                <tr>
                    <th>Module Name</th>
                    <th>Number of Attempts</th>
                    <th>Module Passed</th>
                    <th>Most Recent Certificate Completion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($modules_data as $mod_id => $data) { ?>
                    <tr>
                        <td><?php echo esc_html($data['name'])?></td>
                        <td><?php echo intval($data['attempts'])?></td>
                        <td><?php echo esc_html($data['passed'])?></td>
                        <td><?php echo esc_html($data['cert_time'])?></td>
                    </tr>
                <?php } ?>
            </tbody>
       </table>
    </div>
    <!-- Export to CSV form -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php foreach ($modules_data as $mod_name => $data) : ?>
            <input type="hidden" name="modules[]" value="<?php echo esc_attr($mod_name); ?>">
            <input type="hidden" name="attempts_<?php echo esc_attr(sanitize_title($mod_name)); ?>" value="<?php echo esc_attr($data['attempts']); ?>">
            <input type="hidden" name="passed_<?php echo esc_attr(sanitize_title($mod_name)); ?>" value="<?php echo esc_attr($data['passed']); ?>">
            <input type="hidden" name="cert_time_<?php echo esc_attr(sanitize_title($mod_name)); ?>" value="<?php echo esc_attr($data['cert_time']); ?>">
        <?php endforeach; ?>
        <input type="hidden" name="user_name" value="<?php echo esc_attr($username->display_name); ?>">
        <input type="hidden" name="action" value="export_personal_results">
        <button type="submit" style="margin-top:10px;">Download Results as CSV</button>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode('view-results', 'elearn_view_results_shortcode');