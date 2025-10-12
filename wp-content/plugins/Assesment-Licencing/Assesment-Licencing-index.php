<?php
/**
 * Plugin Name: Assessment Licencing
 * Plugin URI: https://example.com
 * Description: A plugin to manage Licences for fitness assessment records and elearning.
 * Version: 1.0
 * Author: Group 6 - UC Capstone Project 2025
 * Author URI: https://example.com
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Hook the table creation function to plugin activation
register_activation_hook(__FILE__, 'create_license_registrations_table');

function create_license_registrations_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'license_registrations';

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id BIGINT UNSIGNED,
        first_name TEXT,
        surname TEXT,
        work_title TEXT,
        organisation TEXT,
        email TEXT,
        work_phone TEXT,
        mobile TEXT,
        comments TEXT,
        license_code TEXT,
        invoice_sent TINYINT(1) DEFAULT 0,
        invoice_amount FLOAT DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        is_paid TINYINT(1) DEFAULT 0,
        payment_link TEXT,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    if (!empty($wpdb->last_error)) {
        $error_message = "SQL Error: " . $wpdb->last_error;
        error_log($error_message, 3, __DIR__ . '/sql-errors.log'); // Log to plugin directory
        echo "<pre>" . esc_html($error_message) . "</pre>"; // Display the error
    }

    wp_cache_flush();
}

