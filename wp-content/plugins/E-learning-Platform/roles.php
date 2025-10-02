<?php

function elearn_add_manager_role() {
    add_role(
        'manager',
        'Manager',
        array(
            'read'         => true,
            'edit_posts'   => false,
            'upload_files' => false,
            'manage_manager_pages' => true,
        )
    );
}

function elearn_add_student_role() {
    add_role(
        'student',
        'Student',
        array(
            'read'         => true,
            'edit_posts'   => false,
            'upload_files' => false,
        )
    );
}

function register_webpage_user_role() {
    add_role(
        'webpage_user',
        'Webpage User',
        [
            'read' => true, // basic capability
        ]
    );
}
add_action('init', 'register_webpage_user_role');

