<?php

// Add "Create Quiz" page to admin menu
function elearn_add_quiz_create_menu() {
    add_submenu_page(
        'elearn-quiz-table',         // Parent slug (main quiz table page)
        'Create Quiz',                 // Page title
        'Create Quiz',                 // Menu title
        'manage_options',              // Capability
        'elearn-quiz-create',        // Menu slug
        'elearn_quiz_create_page'    // Callback function
    );
}
add_action( 'admin_menu', 'elearn_add_quiz_create_menu' );

// Display and process the quiz creation form
function elearn_quiz_create_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'quiz';

    // Handle form submission
    if ( isset( $_POST['elearn_quiz_create_nonce'] ) && wp_verify_nonce( $_POST['elearn_quiz_create_nonce'], 'elearn_quiz_create' ) ) {
        $title = sanitize_text_field( $_POST['quiz_title'] );
        $description = sanitize_textarea_field( $_POST['quiz_description'] );

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
        <h1>Create Quiz</h1>
        <form method="post">
            <?php wp_nonce_field( 'elearn_quiz_create', 'elearn_quiz_create_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th><label for="quiz_title">Title</label></th>
                    <td><input type="text" name="quiz_title" id="quiz_title" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="quiz_description">Description</label></th>
                    <td><textarea name="quiz_description" id="quiz_description" class="large-text" rows="5"></textarea></td>
                </tr>
            </table>
            <p><input type="submit" class="button-primary" value="Create Quiz"></p>
        </form>
    </div>
    <?php
}