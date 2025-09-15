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

