<?php
if (!defined('ABSPATH')) exit;

get_header();

$view_url = home_url('/view-module');

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
                $module_view_url = home_url('/view-module') . '?module_id=' . intval($row->module_id);
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

get_footer();
