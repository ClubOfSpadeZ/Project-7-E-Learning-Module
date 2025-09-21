<?php
if (!defined('ABSPATH')) exit;

/*Create "User Module Dashboard" page automatically*/
function elearn_create_module_dash_page() {
    if (!get_page_by_path('user-module-dash')) {
        wp_insert_post([
            'post_title'   => 'Module Dashboard',
            'post_name'    => 'user-module-dash',
            'post_content' => '[user_module_dash]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_create_module_dash_page');
add_action('init', 'elearn_create_module_dash_page');

function elearn_user_module_dash_shortcode() {
    ob_start();

    $view_page = get_page_by_path('view-results');
    $view_url = $view_page ? get_permalink($view_page->ID) : home_url('/');

    ?>
    <div class="elearn-dashboard">
        <h2>Welcome to Your E-Learning Dashboard</h2>
        <a href="<?php echo esc_url($view_url); ?>" class="button">View Results</a>
    </div>

    <br>

    <div class="elearn-modules">
        <h2>Available Modules</h2>
        <ul>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'elearn_module';
            $results = $wpdb->get_results("SELECT * FROM $table_name");

            if (!empty($results)) {
                foreach ($results as $row) {
                    $module_view_page = get_page_by_path('module-view');
                    $module_view_url = $module_view_page ? get_permalink($module_view_page->ID) . '?module_id=' . intval($row->module_id) : '#';
                    echo '<li><a href="' . esc_url($module_view_url) . '">'
                        . esc_html($row->module_name) . ': '
                        . esc_html($row->module_description)
                        . '</a></li>';
                }
            } else {
                echo '<p>No modules available at the moment.</p>';
            }
            ?>
        </ul>
    </div>
    <?php

    return ob_get_clean(); // return the HTML
}
add_shortcode('user_module_dash', 'elearn_user_module_dash_shortcode');




