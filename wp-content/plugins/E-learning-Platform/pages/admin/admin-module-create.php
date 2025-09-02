<?php

// Add "Create Module" page to admin menu
function elearn_add_module_create_menu() {
    add_submenu_page(
        'elearn-module-table',         // Parent slug (main module table page)
        'Create Module',                 // Page title
        'Create Module',                 // Menu title
        'manage_options',              // Capability
        'elearn-module-create',        // Menu slug
        'elearn_module_create_page'    // Callback function
    );
}
add_action( 'admin_menu', 'elearn_add_module_create_menu' );

// Display and process the module creation form
function elearn_module_create_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'quiz';

    // Handle form submission
    if ( isset( $_POST['elearn_module_create_nonce'] ) && wp_verify_nonce( $_POST['elearn_module_create_nonce'], 'elearn_module_create' ) ) {
        $title = sanitize_text_field( $_POST['module_title'] );
        $description = sanitize_textarea_field( $_POST['module_description'] );

        if ( ! empty( $title ) ) {
            $wpdb->insert(
                $table_name,
                [
                    'title' => $title,
                    'description' => $description,
                ]
            );
            echo '<div class="notice notice-success is-dismissible"><p>Quiz created successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Quiz title is required.</p></div>';
        }
    }

    // Display form
    ?>
    <div class="wrap">
        <h1>Create Module</h1>
        <form method="post">
            <?php wp_nonce_field( 'elearn_module_create', 'elearn_module_create_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="module_title">Title</label></th>
                    <td><input type="text" name="module_title" id="module_title" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="module_description">Description</label></th>
                    <td><textarea name="module_description" id="module_description" class="large-text" rows="5"></textarea></td>
                </tr>
            </table>
            <p><input type="submit" class="button-primary" value="Create Module"></p>
        </form>
    </div>
    <?php
}