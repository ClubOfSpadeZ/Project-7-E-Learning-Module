<?php

// Add admin menu page
function elearn_add_admin_menu()
{
    add_menu_page(
        'Quiz Table',
        'Quiz Table',
        'manage_options',
        'elearn-quiz-table',
        'elearn_quiz_table_page',
        'dashicons-welcome-learn-more',
        6
    );
}
add_action('admin_menu', 'elearn_add_admin_menu');

// Display quiz table data
function elearn_quiz_table_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'quiz';
    $results = $wpdb->get_results("SELECT * FROM $table_name");

    echo '<div class="wrap"><h1>Quiz Table</h1>';
    if (!empty($results)) {
        echo '<table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($results as $row) {
            echo '<tr>
                    <td>' . esc_html($row->id) . '</td>
                    <td>' . esc_html($row->title) . '</td>
                    <td>' . esc_html($row->description) . '</td>
                    <td>' . esc_html($row->created_at) . '</td>
                  </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>No quiz data found.</p>';
    }
    echo '</div>';
}