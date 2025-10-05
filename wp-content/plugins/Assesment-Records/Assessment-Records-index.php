<?php
/**
 * Plugin Name: Assessment Records
 * Plugin URI: https://example.com
 * Description: A plugin to manage fitness assessment records for users and administrators.
 * Version: 1.0
 * Author: Group 4 - Capstone 2024
 * Author URI: https://example.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('ASSESSMENT_RECORDS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASSESSMENT_RECORDS_PLUGIN_URL', plugin_dir_url(__FILE__));

// require_once ASSESSMENT_RECORDS_PLUGIN_DIR . 'admin-capabilities-view.php';

// Enqueue styles and scripts
function assessment_records_enqueue_assets() {
    wp_enqueue_style('assessment-records-style', ASSESSMENT_RECORDS_PLUGIN_URL . 'assets/css/style.css', [], '1.0');
    wp_enqueue_script('assessment-records-script', ASSESSMENT_RECORDS_PLUGIN_URL . 'assets/js/script.js', ['jquery'], '1.0', true);
}
add_action('wp_enqueue_scripts', 'assessment_records_enqueue_assets');

// Create tables on plugin activation
function assessment_records_create_tables() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();

    $tables = [];

    // SQL queries to create tables
    $tables[] = "CREATE TABLE IF NOT EXISTS `universal_fitness_survey` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `date` DATE NOT NULL,
        `q1` INT DEFAULT 0,
        `q2` INT DEFAULT 0,
        `q3` INT DEFAULT 0,
        `q4` INT DEFAULT 0,
        `q5` INT DEFAULT 0,
        `q6` INT DEFAULT 0,
        `q7` INT DEFAULT 0,
        `q8` INT DEFAULT 0,
        `q9` INT DEFAULT 0,
        `total` INT DEFAULT 0
    ) $charset_collate;";

    $tables[] = "CREATE TABLE IF NOT EXISTS `fitness_survey` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `date` DATE NOT NULL,
        `q1` INT DEFAULT 0,
        `q2` INT DEFAULT 0,
        `q3` INT DEFAULT 0,
        `q4` INT DEFAULT 0,
        `q5` INT DEFAULT 0,
        `q6` INT DEFAULT 0,
        `q7` INT DEFAULT 0,
        `q8` INT DEFAULT 0,
        `q9` INT DEFAULT 0,
        `total` INT DEFAULT 0
    ) $charset_collate;";

    $tables[] = "CREATE TABLE IF NOT EXISTS `ms_survey` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `date` DATE NOT NULL,
        `q1` INT DEFAULT 0,
        `q2` INT DEFAULT 0,
        `q3` INT DEFAULT 0,
        `q4` INT DEFAULT 0,
        `q5` INT DEFAULT 0,
        `q6` INT DEFAULT 0,
        `q7` INT DEFAULT 0,
        `q8` INT DEFAULT 0,
        `q9` INT DEFAULT 0,
        `q10` INT DEFAULT 0,
        `total` INT DEFAULT 0
    ) $charset_collate;";

    $tables[] = "CREATE TABLE IF NOT EXISTS `specific_joint` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `date` DATE NOT NULL,
        `q1` INT DEFAULT 0,
        `q2` INT DEFAULT 0,
        `q3` INT DEFAULT 0,
        `q4` INT DEFAULT 0,
        `q5` INT DEFAULT 0,
        `q6` INT DEFAULT 0,
        `q7` INT DEFAULT 0,
        `q8` INT DEFAULT 0,
        `q9` INT DEFAULT 0,
        `q10` INT DEFAULT 0,
        `q11` INT DEFAULT 0,
        `q12` INT DEFAULT 0,
        `q13` INT DEFAULT 0,
        `q14` INT DEFAULT 0,
        `q15` INT DEFAULT 0,
        `total` INT DEFAULT 0
    ) $charset_collate;";

    $tables[] = "CREATE TABLE IF NOT EXISTS `seminar_preprod2` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `sem_date` DATE NOT NULL,
        `course` VARCHAR(255) NOT NULL
    ) $charset_collate;";

    // Execute the SQL queries
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    foreach ($tables as $sql) {
        dbDelta($sql);
    }

    foreach ($tables as $sql) {
        $wpdb->query($sql);

        if (!empty($wpdb->last_error)) {
            $error_message = "SQL Error: " . $wpdb->last_error;
            error_log($error_message, 3, __DIR__ . '/sql-errors.log'); // Log to a custom file
            echo "<pre>" . esc_html($error_message) . "</pre>"; // Display the error
        }
    }
}
register_activation_hook(__FILE__, 'assessment_records_create_tables');

// Create custom roles on plugin activation
function assessment_records_create_roles() {
    if (!get_role('manager')) {
        add_role(
            'manager',
            'Manager',
            [
                'read' => true,
                'edit_posts' => false,
                'upload_files' => false,
                'manage_options' => true,
                'manage_manager_pages' => true,
                'WHS' => true,
                'contributor' => true,
            ]
        );
    }

    if (!get_role('student')) {
        add_role(
            'student',
            'Student',
            [
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'upload_files' => false,
            ]
        );
    }
}
register_activation_hook(__FILE__, 'assessment_records_create_roles');

function elearn_update_roles() {
    // Update 'manager' role
    $manager_role = get_role('manager');
    if ($manager_role) {
        $manager_role->add_cap('contributor'); // Add the 'contributor' capability
        $manager_role->add_cap('WHS'); // Add the 'WHS' capability
        $manager_role->remove_cap('manage_options'); // Ensure managers can manage options
    }
}
add_action('init', 'elearn_update_roles');