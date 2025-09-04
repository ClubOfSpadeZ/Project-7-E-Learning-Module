<?php

function elearn_add_create_question_menu() {
    add_submenu_page(
        'elearn-module-table',         // Parent slug (Module List)
        'Create Question',             // Page title
        'Create Question',             // Menu title
        'manage_options',   
        'elearn-create-question',      // Menu slug
        'elearn_create_question_page'  // Callback function
    );
}
add_action('admin_menu', 'elearn_add_create_question_menu');

function elearn_create_question_page() {
    echo '<div class="wrap"><h1>Create Question</h1></div>';
}
