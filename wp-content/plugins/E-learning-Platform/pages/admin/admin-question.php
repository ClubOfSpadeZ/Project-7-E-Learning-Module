<?php

function elearn_add_question_menu() {
    add_submenu_page(
        'elearn-module',         // Parent slug (Module List)
        'Questions',             // Page title
        'Questions',             // Menu title
        'manage_options',   
        'elearn-question',      // Menu slug
        'elearn_question_page'  // Callback function
    );
}
add_action('admin_menu', 'elearn_add_question_menu');
function elearn_handle_question_delete() {
    global $wpdb;

    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['question_id'])) {
        $question_id = intval($_GET['question_id']);
        $question_table = $wpdb->prefix . 'elearn_question';

        $wpdb->delete($question_table, ['question_id' => $question_id], ['%d']);

        wp_redirect(admin_url('admin.php?page=elearn-question'));
        exit;
    }
}
add_action('admin_init', 'elearn_handle_question_delete');
function elearn_question_page() {
    global $wpdb;

    $prefix = $wpdb->prefix . 'elearn_';
    $table_name = $prefix . 'question';

    // Fetch questions from the database
    $questions = $wpdb->get_results("SELECT * FROM $table_name");

    $create_url = admin_url('admin.php?page=elearn-question-create');
    $module_url = admin_url('admin.php?page=elearn-module');

    echo '<div class="wrap">';
    echo '<div class="wrap"><h1>Questions</h1></div>';

    if (!empty($questions)) {
        echo '<table class="widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>ID</th>';
        echo '<th>Type</th>';
        echo '<th>Text</th>';
        echo '<th>Actions</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ($questions as $question) {
            $edit_url = admin_url('admin.php?page=elearn-edit-question&question_id=' . intval($question->question_id));
            $delete_url = admin_url('admin.php?page=elearn-question&action=delete&question_id=' . intval($question->question_id));
            echo '<tr>';
            echo '<td>' . esc_html($question->question_id) . '</td>';
            echo '<td>' . esc_html($question->question_type) . '</td>';
            echo '<td>' . esc_html($question->question_text) . '</td>';
            echo '<td>
                        <a href="' . esc_url($edit_url) . '" class="button">Edit</a>
                        <a href="' . esc_url($delete_url) . '" class="button delete-module" style="color: red; border-color: red;" data-module-id="' . esc_attr($question->question_id) . '">Delete</a>
                    </td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No questions found.</p>';
    }

    echo '<p>
            <a href="' . esc_url($module_url) . '" class="button button-primary">Go to Modules</a>
            <a href="' . esc_url($create_url) . '" class="button button-secondary">Add New Question</a>
          </p>';
    echo '</div>';

}