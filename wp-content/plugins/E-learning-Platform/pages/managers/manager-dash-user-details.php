
<?php
// Make sure this is running inside WordPress
if ( ! defined( 'ABSPATH' ) ) exit;
function elearn_manager_dash_user_details() {
    $page_title = 'User Details';
    $page_check = get_page_by_title($page_title);

    // Check if the page already exists
    if (!get_page_by_path('user-details')) {
        // Create Manager Dashboard page
        wp_insert_post([
            'post_title'   => 'User Details',
            'post_name'    => 'user-details',
            'post_content' => '[user_details]', // shortcode or leave blank
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_manager_dash_user_details');
add_action('init', 'elearn_manager_dash_user_details');

function elearn_manager_dash_user_details_shortcode() {
    ob_start();
    ?>
    <div class="user-details">
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
add_shortcode('elearn_home', 'elearn_manager_dash_user_details_shortcode');