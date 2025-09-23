<?php
ob_start();

function elearn_add_edit_module_menu() {
    add_submenu_page(
        'N/A',
        'Edit Module',
        'Edit Module',
        'manage_options',   
        'elearn-edit-module',
        'elearn_edit_module_page'
    );
}
add_action('admin_menu', 'elearn_add_edit_module_menu');

function elearn_edit_module_page() {
    global $wpdb;
    $module_table  = $wpdb->prefix . 'elearn_module';
    $content_table = $wpdb->prefix . 'elearn_content_in_modules';
    $question_table = $wpdb->prefix . 'elearn_question';

    $module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : (isset($_POST['module_id']) ? intval($_POST['module_id']) : 0);

    // ---- Save logic ----
    if ($module_id && isset($_POST['save_module'])) {
        $module_name        = sanitize_text_field($_POST['module_Name']);
        $module_description = sanitize_textarea_field($_POST['module_Description']);
        $module_pdf_path    = '';
        $module_thumbnail_path = '';

        if ($module_id) {
            $module = $wpdb->get_row($wpdb->prepare("SELECT * FROM $module_table WHERE module_id = %d", $module_id));
            $module_pdf_path       = $module ? $module->module_pdf_path : '';
            $module_thumbnail_path = $module ? $module->module_thumbnail_path : '';
        }

        // Handle PDF upload
        if (!empty($_FILES['module_PDF']['name'])) {
            $uploaded_file = $_FILES['module_PDF'];
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['basedir'] . '/elearn-modules/';
            $upload_url = $upload_dir['baseurl'] . '/elearn-modules/';

            if (!file_exists($upload_path)) {
                wp_mkdir_p($upload_path);
            }

            $file_name = sanitize_file_name($uploaded_file['name']);
            $file_path = $upload_path . $file_name;
            $file_url  = $upload_url . $file_name;

            if (move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
                $module_pdf_path = $file_url;
            }
        }

        // Handle Thumbnail upload (jpg/png/gif/webp)
        if (!empty($_FILES['module_thumbnail']['name'])) {
            $uploaded_thumb = $_FILES['module_thumbnail'];
            $upload_dir = wp_upload_dir();
            $upload_path = $upload_dir['basedir'] . '/elearn-modules/';
            $upload_url = $upload_dir['baseurl'] . '/elearn-modules/';

            if (!file_exists($upload_path)) {
                wp_mkdir_p($upload_path);
            }

            $thumb_name = sanitize_file_name($uploaded_thumb['name']);
            $thumb_path = $upload_path . $thumb_name;
            $thumb_url  = $upload_url . $thumb_name;

            $allowed_types = ['image/jpeg','image/png','image/gif','image/webp'];
            if (in_array($uploaded_thumb['type'], $allowed_types)) {
                if (move_uploaded_file($uploaded_thumb['tmp_name'], $thumb_path)) {
                    $module_thumbnail_path = $thumb_url;
                }
            }
        }

        // Update module details
        $wpdb->update(
            $module_table,
            [
                'module_name'          => $module_name,
                'module_description'   => $module_description,
                'module_pdf_path'      => $module_pdf_path,
                'module_thumbnail_path'=> $module_thumbnail_path
            ],
            [ 'module_id' => $module_id ],
            [ '%s', '%s', '%s', '%s' ],
            [ '%d' ]
        );

        // Update linked questions
        $new_linked = isset($_POST['linked_questions']) ? array_map('intval', $_POST['linked_questions']) : [];
        $wpdb->delete($content_table, ['module_module_id' => $module_id]);
        foreach ($new_linked as $qid) {
            $wpdb->insert($content_table, [
                'module_module_id'     => $module_id,
                'question_question_id' => $qid
            ]);
        }

        wp_redirect(admin_url('admin.php?page=elearn-edit-module&module_id=' . $module_id . '&saved=1'));
        exit;
    }

    // ---- Fetch module ----
    $module = null;
    if ($module_id) {
        $module = $wpdb->get_row($wpdb->prepare("SELECT * FROM $module_table WHERE module_id = %d", $module_id));
    }

    if (!$module) {
        echo '<div class="notice notice-error"><p>Module not found.</p></div>';
        return;
    }

    // ---- Fetch questions ----
    $linked_questions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT q.question_id, q.question_text
             FROM $question_table q
             INNER JOIN $content_table c ON q.question_id = c.question_question_id
             WHERE c.module_module_id = %d",
            $module_id
        )
    );

    $all_questions = $wpdb->get_results("SELECT question_id, question_text FROM $question_table");
    $linked_ids = wp_list_pluck($linked_questions, 'question_id');

    // ---- Page Content ----

    echo '<div class="wrap"><h1>Edit Module</h1></div>';
    echo '<form method="post" enctype="multipart/form-data">
            <label for="module_Name">Module Name:</label><br>
            <input type="hidden" name="module_id" value="' . intval($module_id) . '">
            <input type="text" id="module_Name" name="module_Name" class="regular-text" value="' . esc_attr($module->module_name) . '"><br><br>

            <label for="module_Description">Module Description:</label><br>
            <textarea id="module_Description" name="module_Description" class="regular-text" rows="5">' . esc_textarea($module->module_description) . '</textarea><br><br>

            <label for="module_PDF">Upload PDF:</label><br>
            <input type="file" id="module_PDF" name="module_PDF" accept=".pdf"><br><br>';

    if (!empty($module->module_pdf_path)) {
        echo '<p>Current PDF: <a href="' . esc_url($module->module_pdf_path) . '" target="_blank">View PDF</a></p>';
    }

    echo '<label for="module_thumbnail">Upload Thumbnail:</label><br>
          <input type="file" id="module_thumbnail" name="module_thumbnail" accept="image/*"><br><br>';

    if (!empty($module->module_thumbnail_path)) {
        echo '<p>Current Thumbnail:<br><img src="' . esc_url($module->module_thumbnail_path) . '" style="max-width:200px; height:auto;"></p>';
    }

    echo '<div class="wrap"><h2>Module Content</h2></div>';
    
    // Build an array of linked question IDs
    $linked_ids = [];
    foreach ($linked_questions as $q) {
        $linked_ids[$q->question_id] = true;
    }

    echo '<label>Module Questions</label>';
    echo '<table class="widefat fixed" cellspacing="0" style="width: auto;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Question Text</th>
                    <th>Linked</th>
                    <th>Edit</th>
                </tr>
            </thead>
            <tbody>';

    // Linked questions
    foreach ($linked_questions as $row) {
        $is_linked = isset($linked_ids[$row->question_id]);
        echo '<tr>
                <td>' . esc_html($row->question_id) . '</td>
                <td>' . esc_html($row->question_text) . '</td>
                <td><input type="checkbox" name="linked_questions[]" value="' . intval($row->question_id) . '" ' . ($is_linked ? 'checked' : '') . '></td>
                <td>
                    <form method="post" action="' . admin_url('admin.php?page=elearn-edit-question') . '" style="display:inline;">
                        <input type="hidden" name="question_id" value="' . intval($row->question_id) . '">
                        <input type="submit" class="button" value="Edit">
                    </form>
                </td>
            </tr>';
    }

    // Unlinked questions
    $i = 1;
    foreach ($all_questions as $row) {
        if (!isset($linked_ids[$row->question_id])) {
            if ($i == 1) {
                echo '<tr><td colspan="4" style="background:#f9f9f9; text-align:center; font-weight:bold;"></td></tr>';
                $i--;
            }
            echo '<tr>
                    <td>' . esc_html($row->question_id) . '</td>
                    <td>' . esc_html($row->question_text) . '</td>
                    <td><input type="checkbox" name="linked_questions[]" value="' . intval($row->question_id) . '"></td>
                    <td>
                        <form method="post" action="' . admin_url('admin.php?page=elearn-edit-question') . '" style="display:inline;">
                            <input type="hidden" name="question_id" value="' . intval($row->question_id) . '">
                            <input type="submit" class="button" value="Edit">
                        </form>
                    </td>
                </tr>';
        }
    }

    echo '</tbody></table><br>';
    echo '<input type="submit" name="save_module" class="button button-primary" value="Save All Changes">';
    echo '</form>';
}
