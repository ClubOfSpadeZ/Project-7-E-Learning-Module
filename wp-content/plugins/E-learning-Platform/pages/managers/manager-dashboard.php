<?php
// Make sure this is running inside WordPress
if ( ! defined( 'ABSPATH' ) ) exit;
function elearn_manager_dash() {
    $page_title = 'Manager Dashboard';

    // Check if the page already exists
    if (!get_page_by_path('manager-dashboard')) {
        // Create Manager Dashboard page
        wp_insert_post([
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
    $current_user = wp_get_current_user();
    ?>
    <div class="manager-dashboard">
        <h1>Manager Dashboard</h1>
        <?php if ( $current_user->exists() ) : ?>
            <p>Welcome, <?php echo esc_html( $current_user->display_name ); ?>!</p>
        <?php else : ?>
            <p>Welcome, Guest, you shouldn'--t be here!</p>
        <?php endif; ?>
        <ul>
                <li>
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path('organisation-details') ) ); ?>">Manage Organisation</a>
                </li>
                <li>
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path('user-details') ) ); ?>">Manage Users</a>
                </li>   
                <li>
                    <a href="<?php echo esc_url( get_permalink( get_page_by_path(' access-management') ) ); ?>">Manage Access</a>
                </li>
        </ul>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('manager_dashboard', 'elearn_manager_dash_shortcode');