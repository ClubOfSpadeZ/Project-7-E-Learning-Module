
<?php
// Make sure this is running inside WordPress
if ( ! defined( 'ABSPATH' ) ) exit;
function elearn_manager_dash() {
    $page_title = 'Manager Dashboard';
    $page_check = get_page_by_title($page_title);

    // Check if the page already exists
    if (!get_page_by_path('manager-dashboard')) {
        // Create Manager Dashboard page
        wp_insert_post([
            'post_title'   => 'Manager Dashboard',
            'post_name'    => 'manager-dashboard',
            'post_content' => '[manager_dashboard]', // shortcode or leave blank
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_manager_dash');
add_action('init', 'elearn_manager_dash');

function elearn_manager_dash_shortcode() {
    ob_start();
    ?>
    <div class="manager-dashboard">
        <h1>Manager Dashboard</h1>
        <ul>
                <li>
                    <a href="">View Details</a>
                </li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('elearn_home', 'elearn_manager_dash_shortcode');