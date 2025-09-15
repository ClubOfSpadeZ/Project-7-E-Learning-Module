<?php

function elearn_add_question_create_menu() {
    add_submenu_page(
        'elearn-module',         // Parent slug (Module List)
        'Create Question',       // Page title
        'Create Question',       // Menu title
        'manage_options',   
        'elearn-question-create', // Menu slug
        'elearn_question_create_page' // Callback function
    );
}
add_action('admin_menu', 'elearn_add_question_create_menu');

function elearn_question_create_page() {
    global $wpdb;

    $prefix = $wpdb->prefix . 'elearn_';
    $question_table = $prefix . 'question';
    $choice_table = $prefix . 'choice';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elearn_create_question'])) {
        $question_type = sanitize_text_field($_POST['question_type']);
        $question_text = sanitize_textarea_field($_POST['question_text']);

        // Insert the question into the database
        $wpdb->insert(
            $question_table,
            [
                'question_type' => $question_type,
                'question_text' => $question_text,
            ],
            [
                '%s', // question_type (string)
                '%s', // question_text (string)
            ]
        );

        $question_id = $wpdb->insert_id; // Get the ID of the inserted question

        // Insert choices into the database
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
            echo '<div class="notice notice-success"><p>Question and choices created successfully!</p></div>';
        }
    }

    echo '<div class="wrap">';
    echo '<h1>Create Question</h1>';

    // Question form
    echo '<form method="POST" action="" id="question-form">';
    echo '<label for="question_type">Question Type</label>';
    echo '<br>';
    echo '<select name="question_type" id="question_type" class="regular-text" required>';
    echo '<option value="">Select a type</option>';
    echo '<option value="multiple_choice">Multiple Choice</option>';
    echo '<option value="true_false">True/False</option>';
    echo '<option value="short_answer">Short Answer</option>';
    echo '</select>';
    echo '<br><br>';
    echo '<label for="question_text">Question Text</label>';
    echo '<br>';
    echo '<textarea name="question_text" id="question_text" rows="5" cols="50" class="regular-text" required></textarea>';
    echo '<br>';
    echo '<h1>Choices</h1>';
    echo '<div id="choices-container">';
    echo '<div class="choice-item gap">';
    echo '<br>';
    echo '<input type="text" name="choices[]" placeholder="Enter choice" class="regular-text" required>';
    echo '<label>';
    echo '<input type="checkbox" name="is_correct[]" value="1"> Correct ';
    echo '</label>';
    echo '<button type="button" class="button remove-choice-button">Remove</button>';
    echo '</div>';
    echo '</div>';
    echo '<br>';
    echo '<button type="button" id="add-choice-button" class="button">Add Choice</button>';
    echo '<br><br>';
    echo '<p class="submit">';
    echo '<button type="submit" name="elearn_create_question" class="button button-primary">Create Question</button>';
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
                    <input type="text" name="choices[]" placeholder="Enter choice" class="regular-text" required><label><input type="checkbox" name="is_correct[]" value="1"> Correct </label>
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
