<?php

function elearn_add_edit_question_menu() {
    add_submenu_page(
        'N/A',         // Parent slug (Module List)
        'Edit Question',             // Page title
        'Edit Question',             // Menu title
        'manage_options',   
        'elearn-edit-question',   // Menu slug
        'elearn_edit_question_page' // Callback function
    );
}
add_action('admin_menu', 'elearn_add_edit_question_menu');

function elearn_edit_question_page() {
    if (isset($_GET['saved']) && $_GET['saved'] == 1) {
        echo '<div class="notice notice-success"><p>Question updated successfully!</p></div>';
    }


    global $wpdb;

    $prefix = $wpdb->prefix . 'elearn_';
    $question_table = $prefix . 'question';
    $choice_table = $prefix . 'choice';

    // Get the question ID from the URL
    $question_id = isset($_GET['question_id']) ? intval($_GET['question_id']) : 0;

    // Fetch the question data
    $question = $wpdb->get_row($wpdb->prepare("SELECT * FROM $question_table WHERE question_id = %d", $question_id));

    if (!$question) {
        echo '<div class="wrap"><h1>Question Not Found</h1></div>';
        return;
    }

    // Fetch the choices for the question
    $choices = $wpdb->get_results($wpdb->prepare("SELECT * FROM $choice_table WHERE question_id = %d", $question_id));

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elearn_edit_question'])) {
        $question_type = sanitize_text_field($_POST['question_type']);
        $question_text = sanitize_textarea_field($_POST['question_text']);

        // Update the question in the database
        $wpdb->update(
            $question_table,
            [
                'question_type' => $question_type,
                'question_text' => $question_text,
            ],
            ['question_id' => $question_id],
            [
                '%s', // question_type (string)
                '%s', // question_text (string)
            ],
            ['%d'] // question_id (integer)
        );

        // Delete existing choices
        $wpdb->delete($choice_table, ['question_id' => $question_id], ['%d']);

        // Insert updated choices
        if (!empty($_POST['choices'])) {
            foreach ($_POST['choices'] as $index => $choice_text) {
                $is_correct = isset($_POST['is_correct'][$index]) ? 1 : 0;

                $wpdb->insert(
                    $choice_table,
                    [
                        'question_id' => $question_id,
                        'choice_data' => sanitize_text_field($choice_text),
                        'choice_correct' => $is_correct,
                    ],
                    [
                        '%d', // question_id (integer)
                        '%s', // choice_data (string)
                        '%d', // choice_correct (integer)
                    ]
                );
            }
        }

        if ($wpdb->last_error) {
            echo '<div class="notice notice-error"><p>Error: ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            wp_redirect(admin_url('admin.php?page=elearn-edit-question&question_id=' . $question_id . '&saved=1'));
            exit;
        }
    }

    // Display the form
    echo '<div class="wrap">';
    echo '<h1>Edit Question</h1>';

    echo '<form method="POST" action="" id="question-form">';
    echo '<label for="question_type">Question Type</label>';
    echo '<br>';
    echo '<select name="question_type" id="question_type" class="regular-text" required>';
    echo '<option value="multiple_choice" ' . selected($question->question_type, 'multiple_choice', false) . '>Multiple Choice</option>';
    echo '<option value="true_false" ' . selected($question->question_type, 'true_false', false) . '>True/False</option>';
    echo '<option value="short_answer" ' . selected($question->question_type, 'short_answer', false) . '>Short Answer</option>';
    echo '</select>';
    echo '<br><br>';
    echo '<label for="question_text">Question Text</label>';
    echo '<br>';
    echo '<textarea name="question_text" id="question_text" rows="5" cols="50" class="regular-text" required>' . esc_textarea($question->question_text) . '</textarea>';
    echo '<br>';
    echo '<h1>Choices</h1>';
    echo '<div id="choices-container">';

    // Display existing choices
    if (!empty($choices)) {
        foreach ($choices as $index => $choice) {
            echo '<div class="choice-item">';
            echo '<br>';
            echo '<input type="text" name="choices[]" value="' . esc_attr($choice->choice_data) . '" placeholder="Enter choice" class="regular-text" required>';
            echo '<label><input type="checkbox" name="is_correct[' . $index . ']" value="1" ' . checked($choice->choice_correct, 1, false) . '> Correct</label>';
            echo '<button type="button" class="button remove-choice-button">Remove</button>';
            echo '</div>';
        }
    }

    echo '</div>';
    echo '<br>';
    echo '<button type="button" id="add-choice-button" class="button">Add Choice</button>';
    echo '<br><br>';
    echo '<p class="submit">';
    echo '<button type="submit" name="elearn_edit_question" class="button button-primary">Save Changes</button>';
    echo '</p>';
    echo '</form>';
    echo '</div>';

    // Add JavaScript for dynamic choice addition and removal
    echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            const addChoiceButton = document.getElementById("add-choice-button");
            const choicesContainer = document.getElementById("choices-container");

            // Add new choice
            addChoiceButton.addEventListener("click", function() {
                const choiceItem = document.createElement("div");
                choiceItem.classList.add("choice-item");
                choiceItem.innerHTML = `
                    <br>
                    <input type="text" name="choices[]" placeholder="Enter choice" class="regular-text" required><label><input type="checkbox" name="is_correct[]" value="1"> Correct</label>
                    <button type="button" class="button remove-choice-button">Remove</button>
                `;
                choicesContainer.appendChild(choiceItem);

                // Add event listener to the new remove button
                const removeButton = choiceItem.querySelector(".remove-choice-button");
                removeButton.addEventListener("click", function() {
                    choiceItem.remove();
                });
            });

            // Remove existing choice
            choicesContainer.addEventListener("click", function(event) {
                if (event.target.classList.contains("remove-choice-button")) {
                    event.target.closest(".choice-item").remove();
                }
            });
        });
    </script>';
}