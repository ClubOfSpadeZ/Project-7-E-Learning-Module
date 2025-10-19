<?php
if (!defined('ABSPATH')) exit;

/*Create "User Module Dashboard" page automatically*/
function elearn_create_module_dash() {
    if (!get_page_by_path('module-dash')) {
        wp_insert_post([
            'post_title'   => 'Module Dashboard',
            'post_name'    => 'module-dash',
            'post_content' => '[module_dash]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_create_module_dash');
add_action('init', 'elearn_create_module_dash');

add_filter('the_title', function ($title, $id) {
    if (is_page('module-dash') && in_the_loop()) {
        return ''; // Remove the title
    }
    return $title;
}, 10, 2);

function elearn_module_dash_shortcode() {

    //display content only for logged-in users with specific roles
    $current_user = wp_get_current_user();
    $user_roles   = (array) $current_user->roles;
    
    if (!is_user_logged_in()) {
        return '<p>Please log in to access modules.</p>
                <a href="' . esc_url(home_url('/login')) . '">&larr; Login</a>';
    }
    ob_start();

    $view_page = get_page_by_path('view-results');
    $view_url = $view_page ? get_permalink($view_page->ID) : home_url('/');

    ?>
    <style>
       
        /* Dashboard container */
        .elearn-dashboard {
            text-align: center;
            margin-bottom: 30px;
        }

        .elearn-dashboard h2,
        .elearn-modules h2 {
            font-size: 1.8em;
            margin-bottom: 15px;
            color: #333;
            text-align: center;
        }

        /* View Results button */
        .elearn-dashboard #view-results-btn {
            display: inline-block;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            background: #3498db;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            transition: background 0.3s ease, transform 0.2s ease;
        }
        .elearn-dashboard #view-results-btn:hover {
            background: #2c80b4;
            transform: translateY(-2px);
        }

        /* Wrapper for the list of modules */
        .module-list-wrapper {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        /* Each module card */
        .module-item {
            display: flex;
            flex-direction: column;   
            align-items: center;     
            text-align: center;     
            padding: 15px;
            
            border: 1px solid #ddd;
            border-radius: 8px;
            color: inherit;
            background: #f9f9f9;
            transition: box-shadow 0.2s ease, transform 0.2s ease;
            text-decoration: none;
        }

        /* Hover effect */
        .module-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-3px);
        }

        /* Module name */
        .module-item h3 {
            font-size: 1.2em;
            color: #333;
            margin: 0 0 10px;
        }

        /* Thumbnail */
        .module-item img {
            max-width: 125px;
            height: auto;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        /* Description */
        .module-item p {
            color: #555;
            font-size: 0.95em;
            line-height: 1.4;
            margin: 0;
        }
    </style>

    <?php 
    // If the user is not a student, manager, or administrator, show limited view
    if (!in_array('student', $user_roles) && !in_array('manager', $user_roles) && !in_array('administrator', $user_roles)) { ?>
        <div class="elearn-dashboard">
            <h2>Welcome to Your Demo E-Learning Dashboard</h2>
            <p>Your Results will be linked here</a>
        </div>

        <div class="elearn-modules">
            <h2>Test Module</h2>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'elearn_module';
            $results = $wpdb->get_results("SELECT * FROM $table_name");

            if (!empty($results)) {
                echo '<div class="module-list-wrapper">';
                //only display demo module
                foreach ($results as $row) {
                    if ($row->module_name !== 'Demo Module') {
                        continue; // skip this iteration
                    }
                    $module_view_page = get_page_by_path('module-view');
                    $module_view_url  = $module_view_page ? get_permalink($module_view_page->ID) . '?module_id=' . intval($row->module_id) : '#';

                    echo '<a href="' . esc_url($module_view_url) . '" class="module-item">
                            <h3>' . esc_html($row->module_name) . '</h3>';

                    if (!empty($row->module_thumbnail_path)) {
                        echo '<img src="' . esc_url($row->module_thumbnail_path) . '" 
                                alt="' . esc_attr($row->module_name) . ' Thumbnail">';
                    }

                    echo '<p>' . esc_html($row->module_description) . '</p>
                        </a>';
                }
                echo '</div>';
            } else {
                echo '<p style="text-align:center;">No modules available at the moment.</p>';
            }
            ?>
        </div>
    
    <?php
    // If the user is a student, manager, or administrator, show full view exluding demo module
    } else { ?>
        <div class="elearn-dashboard">
            <h2>Welcome to Your E-Learning Dashboard</h2>
            <a href="<?php echo esc_url($view_url); ?>" id="view-results-btn">📊 Click here to View Results</a>
        </div>

        <div class="elearn-modules">
            <h2>Available Modules</h2></br>
            <p>Click on the image of any module to start learning:</p>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'elearn_module';
            $results = $wpdb->get_results("SELECT * FROM $table_name");

            if (!empty($results)) {
                echo '<div class="module-list-wrapper">';
                foreach ($results as $row) {
                    //display all module execept moudle named Demo Module
                    if ($row->module_name === 'Demo Module') {
                        continue; // skip this iteration
                    }
                    $module_view_page = get_page_by_path('module-view');
                    $module_view_url  = $module_view_page ? get_permalink($module_view_page->ID) . '?module_id=' . intval($row->module_id) : '#';

                    echo '<a href="' . esc_url($module_view_url) . '" class="module-item">
                            <h3>' . esc_html($row->module_name) . '</h3>';

                    if (!empty($row->module_thumbnail_path)) {
                        echo '<img src="' . esc_url($row->module_thumbnail_path) . '" 
                                alt="' . esc_attr($row->module_name) . ' Thumbnail">';
                    }

                    echo '<p>' . esc_html($row->module_description) . '</p>
                        </a>';
                }
                echo '</div>';
            } else {
                echo '<p style="text-align:center;">No modules available at the moment.</p>';
            }
            ?>
        </div>
    <?php }

    return ob_get_clean(); // return the HTML
}
add_shortcode('module_dash', 'elearn_module_dash_shortcode');
