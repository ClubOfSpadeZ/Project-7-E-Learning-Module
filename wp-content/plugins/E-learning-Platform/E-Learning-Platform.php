<?php
/**
 * Plugin Name: E-Learing Platform
 * Plugin URI:  https://example.com/my-custom-plugin
 * Description: A starter template for building WordPress plugins.
 * Version:     1.0.1
 * Author:      Group 7 UC Capstone Project
 * Author URI:  https://github.com/ClubOfSpadeZ/Project-7-E-Learning-Module
 * License:     GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: E-Learing-Platform
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
require_once ELEARN_PATH . 'pages/admin/admin-module-table.php';
require_once ELEARN_PATH . 'pages/admin/admin-module-create.php';
require_once ELEARN_PATH . 'pages/admin/admin-module-edit.php';
require_once ELEARN_PATH . 'pages/admin/admin-question-create.php';

//manager dashboard files
require_once ELEARN_PATH . 'pages/managers/manager-dashboard.php';
require_once ELEARN_PATH . 'pages/managers/manager-dash-user-details.php';
require_once ELEARN_PATH . 'pages/managers/manager-dash-org-details.php';
require_once ELEARN_PATH . 'pages/managers/manager-dash-access-management.php';

// Create custom user roles for elerning platform
require_once ELEARN_PATH . 'roles.php';
require_once ELEARN_PATH . 'database-generator.php';

// Plugin activation hook
function elearn_activate() {
    // Code that runs on activation (e.g., create tables, set defaults)
    elearn_database_generator();
    elearn_add_student_role();
    elearn_add_manager_role();
    elearn_add_rewrite_rules();

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

// Frontend router
function elearn_frontend_router() {
    if ( ! is_user_logged_in() ) {
        wp_redirect( wp_login_url() ); // Redirect guests to login
        exit;
    }

    $elearn_page = get_query_var('elearn_page');
    switch ($elearn_page) {
        case 'user-module-dash':
            include ELEARN_PATH . 'pages/users/user-module-dash.php';
            exit;
        case 'user-module-view':
            include ELEARN_PATH . 'pages/users/user-module-view.php';
            exit;
    }
   
}
add_action('template_redirect', 'elearn_frontend_router');


// Add custom rewrite rule for dashboard
function elearn_add_rewrite_rules() {
    add_rewrite_rule(
        '^dashboard/?$', // URL pattern
        'index.php?elearn_page=user-module-dash',
        'top'

    );
    
    add_rewrite_rule(
        '^view-module/?$', // URL pattern
        'index.php?elearn_page=user-module-view', 
        'top'
    );
    
}
add_action('init', 'elearn_add_rewrite_rules');

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


// Shortcode to display quiz
add_shortcode('elearn_quiz', 'elearn_quiz_shortcode');

function elearn_quiz_shortcode($atts) {
    global $wpdb;
    $module_id = intval($_GET['module_id'] ?? 0);

    if (!$module_id) {
        return '<p>Invalid module ID or no module selected.</p>';
    }

    $module = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}elearn_module WHERE module_id=%d", $module_id));
    if (!$module) {
        return '<p>Module not found.</p>';
    }

    // Get questions & choices
    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT q.question_id, q.question_text, q.question_type, c.choice_id, c.choice_data, c.choice_correct
        FROM {$wpdb->prefix}elearn_module m
        JOIN {$wpdb->prefix}elearn_content_in_modules cim 
            ON m.module_id = cim.module_module_id
        JOIN {$wpdb->prefix}elearn_question q 
            ON cim.question_question_id = q.question_id
        LEFT JOIN {$wpdb->prefix}elearn_choice c 
            ON q.question_id = c.question_id
        WHERE m.module_id = %d
        ORDER BY q.question_id, c.choice_id
    ", $module_id));

    // Organize into nested array
    $questions = [];
    foreach ($rows as $row) {
        $qid = $row->question_id;
        if (!isset($questions[$qid])) {
            $questions[$qid] = [
                'text' => $row->question_text,
                'type' => $row->question_type,
                'choices' => []
            ];
        }
        if ($row->choice_id) {
            $questions[$qid]['choices'][] = [
                'id' => $row->choice_id,
                'data' => $row->choice_data,
                'correct' => (bool) $row->choice_correct,
            ];
        }
    }

    // Output form HTML
    ob_start();
    ?>
    <form id="elearnForm">
        <?php wp_nonce_field('submit_quiz', 'quiz_nonce'); ?>
        <input type="hidden" name="module_id" value="<?php echo $module_id; ?>">
        <?php foreach ($questions as $qid => $qdata): ?>
            <div class="elearn-question" data-qid="<?php echo $qid; ?>">
                <h3><?php echo esc_html($qdata['text']); ?></h3>
                <?php if ($qdata['type'] === 'multiple_choice' || $qdata['type'] === 'true_false'): ?>
                    <?php foreach ($qdata['choices'] as $choice): ?>
                        <label>
                            <input type="radio" name="question_<?php echo $qid; ?>" value="<?php echo $choice['id']; ?>">
                            <?php echo esc_html($choice['data']); ?>
                        </label><br>
                    <?php endforeach; ?>
                <?php elseif ($qdata['type'] === 'short_answer'): ?>
                    <label>
                        Answer: <input type="text" name="question_<?php echo $qid; ?>">
                    </label>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <input type="submit" value="Submit">
    </form>
    <div id="quizResult"></div>
    <?php
    return ob_get_clean();
}


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
                $correct_answers[$row->question_id] = $row->choice_id;
            }
        }
    }

    $score = 0;
    foreach ($answers as $qid => $ans) {
        if (isset($correct_answers[$qid])) {
            if (is_numeric($ans) && $ans == $correct_answers[$qid]) {
                $score++;
            } elseif (is_string($ans) && strtolower(trim($ans)) === $correct_answers[$qid]) {
                $score++;
            }
        }
    }

    wp_send_json_success(['score' => $score, 'total' => count($correct_answers)]);
}

// Enqueue script
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('elearn-quiz', ELEARN_URL . 'js/elearn.js', ['jquery'], null, true);
    wp_localize_script('elearn-quiz', 'elearnQuiz', [
        'ajaxurl' => admin_url('admin-ajax.php')
    ]);
});