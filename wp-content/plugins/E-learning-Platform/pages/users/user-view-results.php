<?php
if (!defined('ABSPATH')) exit;

function elearn_create_view_results_page() {
    if (!get_page_by_path('view-results')) {
        wp_insert_post([
            'post_title'   => 'View Results',
            'post_name'    => 'view-results',
            'post_content' => '[view_results]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_create_view_results_page');
add_action('init', 'elearn_create_view_results_page');

add_filter('the_title', function ($title, $id) {
    if (is_page('view-results') && in_the_loop()) {
        return ''; // Remove the title
    }
    return $title;
}, 10, 2);

function elearn_view_results_shortcode() {
    $dashboard_page = get_page_by_path('module-dash');
    $dashboard_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/');

    global $wpdb;
    $user_id = get_current_user_id();
    $username = wp_get_current_user();
    $module_tbl = $wpdb->prefix . 'elearn_module';
    $attempt_tbl = $wpdb->prefix . 'elearn_attempt';
    $cert_tbl = $wpdb->prefix . 'elearn_certificate';
    
    $results = $wpdb->get_results($wpdb->prepare(
        "SELECT m.module_id, m.module_name, a.attempt_id, c.certificate_id, c.certificate_completion
        FROM $module_tbl m
        LEFT JOIN $attempt_tbl a ON m.module_id = a.module_module_id AND a.user_id = %d
        LEFT JOIN $cert_tbl c ON a.attempt_id = c.attempt_id
        ORDER BY m.module_id ASC", // optional default order
        $user_id
    ));

    $user_roles   = (array) $username->roles;
    if (!in_array('student', $user_roles) && !in_array('manager', $user_roles) && !in_array('administrator', $user_roles)) {
        return '<p>You do not have permission to access this page.</p>';
    } elseif (!is_user_logged_in()) {
        return '<p>Please log in to access your dashboard.</p>';
    }
    
    ob_start();        
    //Display all modules with number of attempts, passed or not, most recent certificate completion time
    //if no attempts, show "No attempts yet"
    if (!empty($results)) {
        $modules_data = [];

        foreach ($results as $row) {
            if ($row->module_name === "Demo Module") continue;
            $mod_id   = $row->module_id;
            $mod_name = $row->module_name;
            

            if (!isset($modules_data[$mod_id])) {
                $modules_data[$mod_id] = [
                    'name'      => $mod_name,
                    'attempts'  => 0,
                    'passed'    => 'No',
                    'cert_time' => 'No certificate yet'
                ];
            }

            if ($row->attempt_id) {
                $modules_data[$mod_id]['attempts']++;
                if ($row->certificate_completion) {
                    $modules_data[$mod_id]['passed'] = 'Yes';
                    $modules_data[$mod_id]['cert_time'] = $row->certificate_completion;
                    // Prepare certificate view URL
                    $cert_view_page = get_page_by_path('cert-view');
                    $modules_data[$mod_id]['cert_url']  = $cert_view_page ? add_query_arg(
                        [
                            'module_id' => intval($row->module_id),
                            'cert_id'   => intval($row->certificate_id)
                        ],
                        get_permalink($cert_view_page->ID)
                    ): '#';
                }
            }
        }
    }

    //sort modules by module id
    ksort($modules_data);

    // Now echo table once with final data
    ?>
    <style>
        .elearn-results-table-container {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: hidden;
            border: 2px solid #666;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .elearn-results-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 15px;
            color: #333;
        }

        .elearn-results-table thead {
            position: sticky;
            top: 0;
            background: #666666; 
            color: #fff;
            font-weight: 600;
            z-index: 2;
        }

        .elearn-results-table th,
        .elearn-results-table td {
            padding: 10px 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            border-right: 1px solid #ddd; 
        }

        .elearn-results-table th:last-child,
        .elearn-results-table td:last-child {
            border-right: none;
        }

        .elearn-results-table thead tr,
        .elearn-results-table tbody tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .elearn-results-table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        .elearn-results-table tbody tr:hover {
            background: #eef6fb;
        }

        #elearn-btn-back {
        display: inline-block;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            background: #3498db;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        #elearn-btn-back:hover {
            background: #2c80b4;
            transform: translateY(-2px);    
        }

        .elearn-results-btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 18px;
            font-size: 15px;
            font-weight: 600;
            color: #fff;
            background: #3498db;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .elearn-results-btn:hover {
            background: #2c80b4;
        }
    </style>

    <a href="<?php echo esc_url($dashboard_url); ?>" id="elearn-btn-back">&larr; Back to Dashboard</a><br><br>
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
                        <!-- Link cert time to certificate view page if passed -->
                        <td><?php echo esc_html($data['name'])?></td>
                        <td><?php echo intval($data['attempts'])?></td>
                        <td><?php echo esc_html($data['passed'])?></td>
                        <td><?php 
                            if ($data['passed'] === 'Yes') {
                                echo '<a href="' . esc_url($data['cert_url']) . '" target="_blank" rel="noopener noreferrer">'
                                    . esc_html($data['cert_time']) .
                                    '</a>';
                            } else {
                                echo esc_html($data['cert_time']);
                            }
                            ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
       </table>
    </div>
    <!-- Export to CSV form -->
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php foreach ($modules_data as $mod_name => $data) { ?>
            <input type="hidden" name="modules[]" value="<?php echo esc_attr($mod_name); ?>">
            <input type="hidden" name="attempts_<?php echo esc_attr(sanitize_title($mod_name)); ?>" value="<?php echo esc_attr($data['attempts']); ?>">
            <input type="hidden" name="passed_<?php echo esc_attr(sanitize_title($mod_name)); ?>" value="<?php echo esc_attr($data['passed']); ?>">
            <input type="hidden" name="cert_time_<?php echo esc_attr(sanitize_title($mod_name)); ?>" value="<?php echo esc_attr($data['cert_time']); ?>">
        <?php } ?>
        <input type="hidden" name="user_name" value="<?php echo esc_attr($username->display_name); ?>">
        <input type="hidden" name="action" value="export_personal_results">
        <button type="submit" class="elearn-results-btn">Download Results as CSV</button>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode('view_results', 'elearn_view_results_shortcode');