<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

if (isset($_GET['module_id'])) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'elearn_module';
    $module_id = intval($_GET['module_id']);
    $module = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE module_id = %d", $module_id));

    if ($module) {
        echo '<div class="elearn-module-view">
                <h2>' . esc_html($module->module_name) . '</h2>
                <p>' . esc_html($module->module_description) . '</p>
              </div>';
    } else {
        echo '<p>Module not found.</p>';
    }
} else {
    echo '<p>Invalid module ID or no module selceted</p>';
}
get_footer();
//transfer relevant db module info to page for user to view

