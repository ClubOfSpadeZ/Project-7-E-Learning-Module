<?php
// Make sure this is running inside WordPress
if ( ! defined( 'ABSPATH' ) ) exit;
function elearn_manager_dash_access_management() {
    $page_title = 'Access Management';

    // Check if the page already exists
    if (!get_page_by_path('access-management')) {
        // Create Manager Dashboard page
        wp_insert_post([
            'post_title'   => 'Access Management',
            'post_name'    => 'access-management',
            'post_content' => '[access_management]', // shortcode or leave blank
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_manager_dash_access_management');
add_action('init', 'elearn_manager_dash_access_management');

function elearn_manager_dash_access_management_shortcode() {
    ob_start();
    ?>
    <div class="access-management">
        <h1>Manage Organisation</h1>
        
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('access_management', 'elearn_manager_dash_access_management_shortcode');