<?php

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
    $module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
    $module_table = $wpdb->prefix . 'elearn_module';
    $question_table = $wpdb->prefix . 'elearn_question';
    $choice_table = $wpdb->prefix . 'elearn_choice';
    $content_in_modules_table = $wpdb->prefix . 'elearn_content_in_modules';

    // Handle question creation
    if (isset($_POST['create_question'])) {
        $question_text = sanitize_text_field($_POST['question_text']);
        $question_type = sanitize_text_field($_POST['question_type']);
        if ($question_text && $question_type) {
            $wpdb->insert(
                $question_table,
                [
                    'question_type' => $question_type,
                    'question_text' => $question_text
                ]
            );
            echo '<div class="notice notice-success is-dismissible"><p>Question created!</p></div>';
        }
    }

    // Handle choice creation
    if (isset($_POST['create_choice'])) {
        $choice_data = sanitize_text_field($_POST['choice_data']);
        $choice_correct = isset($_POST['choice_correct']) ? 1 : 0;
        $question_id = intval($_POST['question_id']);
        if ($choice_data && $question_id) {
            $wpdb->insert(
                $choice_table,
                [
                    'question_id' => $question_id,
                    'choice_data' => $choice_data,
                    'choice_correct' => $choice_correct
                ]
            );
            echo '<div class="notice notice-success is-dismissible"><p>Choice added!</p></div>';
        }
    }

    // Handle linking question to module
    if (isset($_POST['link_question_module'])) {
        $selected_module_id = intval($_POST['selected_module_id']);
        $question_id = intval($_POST['question_id']);
        if ($selected_module_id && $question_id) {
            // Prevent duplicate entries
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $content_in_modules_table WHERE module_module_id = %d AND question_question_id = %d",
                $selected_module_id, $question_id
            ));
            if (!$exists) {
                $wpdb->insert(
                    $content_in_modules_table,
                    [
                        'module_module_id' => $selected_module_id,
                        'question_question_id' => $question_id
                    ]
                );
                echo '<div class="notice notice-success is-dismissible"><p>Question linked to module!</p></div>';
            } else {
                echo '<div class="notice notice-warning is-dismissible"><p>Already linked!</p></div>';
            }
        }
    }

    // Get module info
    $module = $wpdb->get_row($wpdb->prepare("SELECT * FROM $module_table WHERE module_id = %d", $module_id));
    echo '<div class="wrap">';
    if ($module) {
        echo '<h1>Edit Module: ' . esc_html($module->module_name) . '</h1>';
        echo '<h2>Add Question</h2>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="question_text">Question Text</label></th>
                        <td><input type="text" name="question_text" id="question_text" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="question_type">Question Type</label></th>
                        <td>
                            <select name="question_type" id="question_type">
                                <option value="multiple_choice">Multiple Choice</option>
                                <option value="true_false">True/False</option>
                                <option value="short_answer">Short Answer</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p><input type="submit" name="create_question" class="button-primary" value="Create Question"></p>
            </form>';

        // List all modules for dropdowns
        $all_modules = $wpdb->get_results("SELECT * FROM $module_table");

        // List questions for this module
        $questions = $wpdb->get_results("SELECT * FROM $question_table");
        if ($questions) {
            echo '<h2>Questions</h2>';
            foreach ($questions as $question) {
                echo '<div style="margin-bottom:20px;padding:10px;border:1px solid #ccc;">';
                echo '<strong>' . esc_html($question->question_text) . '</strong> (' . esc_html($question->question_type) . ')';

                // List choices for this question
                $choices = $wpdb->get_results($wpdb->prepare("SELECT * FROM $choice_table WHERE question_id = %d", $question->question_id));
                if ($choices) {
                    echo '<ul>';
                    foreach ($choices as $choice) {
                        echo '<li>' . esc_html($choice->choice_data) . ($choice->choice_correct ? ' <strong>(Correct)</strong>' : '') . '</li>';
                    }
                    echo '</ul>';
                }

                // Add Choice form
                echo '<form method="post" style="margin-top:10px;">
                        <input type="hidden" name="question_id" value="' . intval($question->question_id) . '">
                        <input type="text" name="choice_data" placeholder="Choice text" required>
                        <label><input type="checkbox" name="choice_correct" value="1"> Correct</label>
                        <input type="submit" name="create_choice" class="button" value="Add Choice">
                      </form>';

                // Link question to module dropdown
                echo '<form method="post" style="margin-top:10px;">
                        <input type="hidden" name="question_id" value="' . intval($question->question_id) . '">
                        <select name="selected_module_id">';
                foreach ($all_modules as $mod) {
                    echo '<option value="' . intval($mod->module_id) . '"' . ($mod->module_id == $module_id ? ' selected' : '') . '>' . esc_html($mod->module_name) . '</option>';
                }
                echo '</select>
                        <input type="submit" name="link_question_module" class="button" value="Link to Module">
                      </form>';

                // Show linked modules for this question
                $linked_modules = $wpdb->get_results($wpdb->prepare(
                    "SELECT m.module_name FROM $content_in_modules_table cim
                     JOIN $module_table m ON cim.module_module_id = m.module_id
                     WHERE cim.question_question_id = %d", $question->question_id));
                if ($linked_modules) {
                    echo '<p><strong>Linked Modules:</strong> ';
                    foreach ($linked_modules as $lm) {
                        echo esc_html($lm->module_name) . ' ';
                    }
                    echo '</p>';
                }

                echo '</div>';
            }
        }
    } else {
        echo '<p>Module not found.</p>';
    }
    echo '</div>';
}