<?php
ob_start();
// Add "Create Module" page to admin menu
function elearn_add_module_create_menu() {
    add_submenu_page(
        'elearn-module',         // Parent slug (main module table page)
        'Create Module',         // Page title
        'Create Module',         // Menu title
        'manage_options',        // Capability
        'elearn-module-create',  // Menu slug
        'elearn_module_create_page' // Callback function
    );
}
add_action('admin_menu', 'elearn_add_module_create_menu');

// Display and process the module creation form
function elearn_module_create_page() {
    global $wpdb;
    $module_table = $wpdb->prefix . 'elearn_module';
    $content_table = $wpdb->prefix . 'elearn_content_in_modules';
    $question_table = $wpdb->prefix . 'elearn_question';

    // Handle form submission
    if (isset($_POST['save_module'])) {
        $module_name = sanitize_text_field(wp_unslash($_POST['module_Name']));
        $module_description = sanitize_textarea_field(wp_unslash($_POST['module_Description']));
        $linked_questions = isset($_POST['linked_questions']) ? array_map('intval', $_POST['linked_questions']) : [];
        $module_pdf_path = '';

        // Handle file upload
        if (!empty($_FILES['module_PDF']['name'])) {
            $uploaded_file = $_FILES['module_PDF'];
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['basedir'] . '/elearn-modules/';
            $upload_url = $upload_dir['baseurl'] . '/elearn-   modules/';

            // Ensure the directory exists
            if (!file_exists($upload_path)) {
                wp_mkdir_p($upload_path);
            }

            $file_name = sanitize_file_name($uploaded_file['name']);
            $file_path = $upload_path . $file_name;
            $file_url = $upload_url . $file_name;

            if (move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
                $module_pdf_path = $file_url;
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to upload PDF.</p></div>';
            }
        }

        // Insert new module into the database
        if (!empty($module_name)) {
            $result = $wpdb->insert(
                $module_table,
                [
                    'module_name' => $module_name,
                    'module_description' => $module_description,
                    'module_created' => current_time('mysql'),
                    'module_pdf_path' => $module_pdf_path // Save the PDF path
                ],
                [
                    '%s', '%s', '%s', '%s'
                ]
            );

            if ($result !== false) {
                $module_id = $wpdb->insert_id;

                // Link selected questions to the new module
                foreach ($linked_questions as $qid) {
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $content_table WHERE module_module_id = %d AND question_question_id = %d",
                        $module_id,
                        $qid
                    ));

                    if (!$exists) {
                        $wpdb->insert($content_table, [
                            'module_module_id' => $module_id,
                            'question_question_id' => $qid
                        ]);
                    }
                }

                // Redirect to the module list page with a success message
                wp_redirect(admin_url('admin.php?page=elearn-module&created=1'));
                exit;
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to create module: ' . esc_html($wpdb->last_error) . '</p></div>';
            }
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Module name is required.</p></div>';
        }
    }

    // Fetch all questions
    $all_questions = $wpdb->get_results("SELECT question_id, question_text FROM $question_table");

    // Display the form
    echo '<div class="wrap"><h1>Create Module</h1></div>';
    echo '<form method="post" enctype="multipart/form-data">
            <label for="module_Name">Module Name:</label>
            <br>
            <input type="text" id="module_Name" name="module_Name" class="regular-text" required>
            <br><br>
            <label for="module_Description">Module Description:</label>
            <br>
            <textarea id="module_Description" name="module_Description" class="regular-text" rows="5"></textarea>
            <br><br>
            <label for="module_PDF">Upload PDF:</label>
            <br>
            <input type="file" id="module_PDF" name="module_PDF" accept=".pdf">
            <br><br>';

    // Display questions with checkboxes for linking
    echo '<div class="wrap"><h2>Link Questions</h2></div>';
    echo '<table class="widefat fixed" cellspacing="0" style="width: auto;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Question Text</th>
                    <th>Link</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($all_questions as $row) {
        echo '<tr>
                <td>' . esc_html($row->question_id) . '</td>
                <td>' . esc_html($row->question_text) . '</td>
                <td>
                    <input type="checkbox" name="linked_questions[]" value="' . intval($row->question_id) . '">
                </td>
            </tr>';
    }

    echo '</tbody></table><br>';
    echo '<input type="submit" name="save_module" class="button button-primary" value="Create Module">';
    echo '</form>';
}