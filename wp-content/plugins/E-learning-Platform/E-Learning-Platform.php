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
