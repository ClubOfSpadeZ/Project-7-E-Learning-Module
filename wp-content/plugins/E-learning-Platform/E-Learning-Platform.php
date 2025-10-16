<?php
/**
 * Plugin Name: E-Learing Platform
 * Plugin URI:  https://example.com/my-custom-plugin
 * Description: A starter template for building WordPress plugins.
 * Version:     1.0.1
 * Author:      Group 7 - UC Capstone Project 2025
 * Author URI:  https://github.com/ClubOfSpadeZ/Project-7-E-Learning-Module
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: E-Learning-Platform
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'ELEARN_VERSION', '1.0.0' );
define( 'ELEARN_PATH', plugin_dir_path( __FILE__ ) );
define( 'ELEARN_URL', plugin_dir_url( __FILE__ ) );

// Include main class file (optional if you want OOP structure)
// require_once MYPLUGIN_PATH . 'includes/class-myplugin.php';
require_once ELEARN_PATH . 'pages/admin/admin-module.php';
require_once ELEARN_PATH . 'pages/admin/admin-module-create.php';
require_once ELEARN_PATH . 'pages/admin/admin-module-edit.php';
require_once ELEARN_PATH . 'pages/admin/admin-question.php';
require_once ELEARN_PATH . 'pages/admin/admin-question-create.php';
require_once ELEARN_PATH . 'pages/admin/admin-question-edit.php';
require_once ELEARN_PATH . 'pages/admin/admin-organisation.php';
require_once ELEARN_PATH . 'pages/admin/admin-organisation-edit.php';
require_once ELEARN_PATH . 'pages/admin/admin-licence.php';
require_once ELEARN_PATH . 'pages/admin/admin-licence-create.php';

// manager dashboard files
require_once ELEARN_PATH . 'pages/managers/manager-dashboard.php';
require_once ELEARN_PATH . 'pages/managers/manager-dash-user-details.php';
require_once ELEARN_PATH . 'pages/managers/manager-dash-org-details.php';
require_once ELEARN_PATH . 'pages/managers/manager-licence.php';

// Student/User pages 
require_once ELEARN_PATH . 'pages/users/user-register.php';
require_once ELEARN_PATH . 'pages/users/user-module-dash.php';
require_once ELEARN_PATH . 'pages/users/user-module-view.php';
require_once ELEARN_PATH . 'pages/users/user-view-results.php';
require_once ELEARN_PATH . 'pages/users/user-cert-view.php';

// Create custom user roles for elerning platform
require_once ELEARN_PATH . 'roles.php';
require_once ELEARN_PATH . 'database-generator.php';

// Plugin activation hook
function elearn_activate() {
    // Code that runs on activation (e.g., create tables, set defaults)
    elearn_database_generator();
    elearn_add_student_role();
    elearn_add_manager_role();

    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'elearn_activate' );

// Plugin deactivation hook
function elearn_deactivate() {
    // Code that runs on deactivation (e.g., cleanup tasks)
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'elearn_deactivate' );

// Initialize plugin
function elearn_init() {
    // Load text domain for translations
    load_plugin_textdomain( 'my-custom-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Example: add a shortcode
    add_shortcode( 'elearn_hello', function() {
        return '<p>Hello from My Custom Plugin!</p>';
    } );
}
add_action( 'plugins_loaded', 'elearn_init' );

// Register the query var
function elearn_register_query_vars($vars) {
    $vars[] = 'elearn_page';
    return $vars;
}
add_filter('query_vars', 'elearn_register_query_vars');


// Example: Add settings link on plugin page
function elearn_settings_link( $links ) {
    $settings_link = '<a href="options-general.php?page=elearn-settings">' . __( 'Settings', 'my-custom-plugin' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'elearn_settings_link' );


function my_add_access_code_field($output) {
    $output .= '
    <p>
        <label for="organisation_access_code">Organisation Access Code</label><br>
        <input type="text" name="organisation_access_code" id="organisation_access_code" required>
    </p>';
    return $output;
}
add_filter('wppb_extra_register_fields', 'my_add_access_code_field');

// Server AJAX request to process quiz submission
add_action('wp_ajax_check_quiz', 'elearn_quiz_check');
add_action('wp_ajax_nopriv_check_quiz', 'elearn_quiz_check');

function elearn_quiz_check() {
    global $wpdb;

    if (!isset($_POST['quiz_nonce']) || !wp_verify_nonce($_POST['quiz_nonce'], 'submit_quiz')) {
        wp_send_json_error('Invalid request');
    }

    $module_id = intval($_POST['module_id']);
    $answers = json_decode(stripslashes($_POST['answers']), true);

    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT q.question_id, q.question_type, c.choice_id, c.choice_correct, c.choice_data
        FROM {$wpdb->prefix}elearn_module m
        JOIN {$wpdb->prefix}elearn_content_in_modules cim 
            ON m.module_id = cim.module_module_id
        JOIN {$wpdb->prefix}elearn_question q 
            ON cim.question_question_id = q.question_id
        LEFT JOIN {$wpdb->prefix}elearn_choice c 
            ON q.question_id = c.question_id
        WHERE m.module_id = %d
    ", $module_id));

    $correct_answers = [];
    foreach ($rows as $row) {
        if ($row->question_type === 'short_answer') {
            $correct_answers[$row->question_id] = strtolower(trim($row->choice_data ?? ''));
        } else {
            if ($row->choice_correct) {
                if (!isset($correct_answers[$row->question_id])) {
                    $correct_answers[$row->question_id] = [];
                }
                $correct_answers[$row->question_id][] = intval($row->choice_id);
            }
        }
    }

    $score = 0;
    $incorrect = [];

    foreach ($correct_answers as $qid => $correct_value) {
        if (!isset($answers[$qid]) || $answers[$qid] === '') {
            // User didn’t answer
            $incorrect[] = $qid;
            continue;
        }

        $ans = $answers[$qid];

        if (is_array($correct_value)) {
            if (in_array(intval($ans), $correct_value, true)) {
                $score++;
            } else {
                $incorrect[] = $qid;
            }
        } else {
            if (is_string($ans) && strtolower(trim($ans)) === $correct_value) {
                $score++;
            } elseif (is_numeric($ans) && intval($ans) === intval($correct_value)) {
                $score++;
            } else {
                $incorrect[] = $qid;
            }
        }
    }

    wp_send_json_success([
        'score' => $score,
        'total' => count($correct_answers),
        'incorrect' => $incorrect
    ]);
}

// Enqueue script
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('elearn-quiz', ELEARN_URL . 'js/elearn.js', ['jquery'], null, true);
    wp_localize_script('elearn-quiz', 'elearnQuiz', [
        'ajaxurl' => admin_url('admin-ajax.php')
    ]);
});

// Enqueue CSS for admin pages
function elearn_enqueue_admin_styles($hook) {
    // Enqueue the CSS file for all admin pages
    wp_enqueue_style(
        'elearn-admin-styles', // Handle for the stylesheet
        ELEARN_URL . 'pages/admin/adminlooksgood.css', // Path to the CSS file
        [], // Dependencies (none in this case)
        '1.0.0' // Version number
    );
}
add_action('admin_enqueue_scripts', 'elearn_enqueue_admin_styles');

// Log attempt + maybe certificate
add_action('wp_ajax_log_attempt', 'elearn_log_attempt');
add_action('wp_ajax_nopriv_log_attempt', 'elearn_log_attempt');

function elearn_log_attempt() {
    check_ajax_referer('submit_quiz', 'quiz_nonce');

    global $wpdb;
    $user_id = get_current_user_id();
    $module_id = intval($_POST['module_id']);
    $score = intval($_POST['score']);
    $total = intval($_POST['total']);

    $attempt_tbl     = $wpdb->prefix . 'elearn_attempt';
    $certificate_tbl = $wpdb->prefix . 'elearn_certificate';

    // Always log attempt
    $wpdb->insert($attempt_tbl, [
        'attempt_time' => current_time('mysql'),
        'attempt_score' => $score,
        'user_id' => $user_id,
        'module_module_id' => $module_id
    ]);
    $attempt_id = $wpdb->insert_id;

    // If passed, insert certificate ----------------------in future, check if already exists and overwrite
    if ($score === $total) {
        $wpdb->insert($certificate_tbl, [
            'certificate_completion' => current_time('mysql'),
            'attempt_id' => $attempt_id,
            'user_id' => $user_id
        ]);
    }

    wp_send_json_success('Attempt logged');
}


add_action('admin_post_export_user_progress', 'elearn_handle_export_user_progress');
add_action('admin_post_nopriv_export_user_progress', 'elearn_handle_export_user_progress');

function elearn_handle_export_user_progress() {
    if (empty($_POST['selected_users'])) {
        wp_die('No users selected for export.');
    }

    global $wpdb;
    $selected_user_ids = array_map('intval', $_POST['selected_users']);

    // Fetch only selected users
    $users = get_users(['include' => $selected_user_ids]);

    // Fetch modules
    $modules = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}elearn_module ORDER BY module_name ASC");

    // Fetch attempts
    $attempts = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}elearn_attempt");

    // Fetch certificates
    $certs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}elearn_certificate");

    // Build certificate lookup table
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

    // Send CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="user_certificates_' . date('Y-m-d') . '.csv"');

    $fp = fopen('php://output', 'w');

    // Header row
    $header = ['User'];
    foreach ($modules as $module) {
        $header[] = $module->module_name;
    }
    fputcsv($fp, $header);

    // Data rows (Certificate completion dates)
    foreach ($users as $user) {
        $row = [$user->display_name];
        foreach ($modules as $module) {
            if (
                isset($cert_lookup[$user->ID][$module->module_id]) &&
                !empty($cert_lookup[$user->ID][$module->module_id]['completed']) &&
                $cert_lookup[$user->ID][$module->module_id]['completed'] !== '0000-00-00 00:00:00'
            ) {
                $certData = $cert_lookup[$user->ID][$module->module_id];
                $dt = new DateTime($certData['completed']);
                $row[] = $dt->format('d/m/Y h:i A');
            } else {
                $row[] = 'Not Completed';
            }
        }
        fputcsv($fp, $row);
    }

    fclose($fp);
    exit;
}


// CSV export handler
function elearn_export_personal_results() {
    if (empty($_POST['modules']) || !is_array($_POST['modules'])) {
        wp_die('No data to export.');
    }

    $user_name = sanitize_text_field($_POST['user_name']);
    $filename = 'results_' . sanitize_title($user_name) . '_' . date('Y-m-d') . '.csv';

    // Force file download headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Column headers
    fputcsv($output, ['Module Name', 'Attempts', 'Passed', 'Certificate Completion']);

    // Loop over submitted data
    foreach ($_POST['modules'] as $mod_name) {
        $slug     = sanitize_title($mod_name);
        $attempts = sanitize_text_field($_POST['attempts_' . $slug]);
        $passed   = sanitize_text_field($_POST['passed_' . $slug]);
        $cert     = sanitize_text_field($_POST['cert_time_' . $slug]);

        fputcsv($output, [$mod_name, $attempts, $passed, $cert]);
    }

    fclose($output);
    exit;
}
add_action('admin_post_export_personal_results', 'elearn_export_personal_results');
add_action('admin_post_nopriv_export_personal_results', 'elearn_export_personal_results');

add_action('wp_ajax_verify_access_code', 'elearn_verify_access_code');
add_action('wp_ajax_nopriv_verify_access_code', 'elearn_verify_access_code');

function elearn_verify_access_code()
{
    // Ensure the user is logged in
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'You must be logged in to perform this action.']);
        wp_die();
    }

    global $wpdb;

    $current_user_id = get_current_user_id();
    $current_user = new WP_User($current_user_id);

    // Check if the user has the "administrator" role
    if (in_array('administrator', $current_user->roles)) {
        wp_send_json_error(['message' => 'Administrators cannot join an organisation.']);
        wp_die();
    }

    $organisation_id = get_user_meta($current_user_id, 'organisation_id', true);

    // Check if the user is already in an organisation
    if (!empty($organisation_id)) {
        wp_send_json_error(['message' => 'You are already part of an organisation.']);
        wp_die();
    }

    // Get the hashed access code from the AJAX request
    $hashed_access_code = sanitize_text_field($_POST['access_code']);

    // Query the database for the hashed access code
    $result = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT organisation_organisation_id, is_used FROM {$wpdb->prefix}elearn_access WHERE hash_code = %s",
            $hashed_access_code
        )
    );

    if ($result) {
            // Add the user to the organisation
            update_user_meta($current_user_id, 'organisation_id', $result->organisation_organisation_id);
            $user = new WP_User($current_user_id);
            $user->set_role('student');

            // Mark the access code as used
            $wpdb->update(
                $wpdb->prefix . 'elearn_access',
                ['is_used' => $result->is_used + 1, 'access_used' => current_time('mysql')],
                ['hash_code' => $hashed_access_code],
                ['%d', '%s'],
                ['%s']
            );

            // Get the URL of the user module dashboard page
            $module_dash_page = get_page_by_path('module-dash');
            $redirect_url = $module_dash_page ? get_permalink($module_dash_page->ID) : home_url('/');

            // Send success response with redirect URL
            wp_send_json_success([
                'message' => 'You have successfully joined the organisation!',
                'redirect_url' => $redirect_url,
            ]);
    } else {
        wp_send_json_error(['message' => 'Invalid access code. Please try again.']);
    }

    wp_die();
}