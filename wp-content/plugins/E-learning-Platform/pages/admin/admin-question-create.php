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
    $module_table = $prefix . 'module';
    $question_table = $prefix . 'question';
    $choice_table = $prefix . 'choice';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elearn_create_question'])) {
        $question_type = sanitize_text_field($_POST['question_type']);
        $question_text = sanitize_textarea_field(wp_unslash($_POST['question_text']));

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

        // Link the question to the selected modules
        if (!empty($_POST['link_to_modules'])) {
        foreach ($_POST['link_to_modules'] as $module_id) {
            $wpdb->insert(
                "{$prefix}content_in_modules",
                [
                    'question_question_id' => $question_id,
                    'module_module_id' => intval($module_id),
                ],
                [
                    '%d', // question_id (integer)
                    '%d', // module_id (integer)
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

    // Query all modules from the database
    $modules = $wpdb->get_results("SELECT * FROM {$module_table}");

    // Fetch the module IDs linked to the last created question
    $linked_modules = [];
    if (isset($question_id)) {
        $linked_modules = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT module_module_id FROM {$prefix}content_in_modules WHERE question_question_id = %d",
                $question_id
            )
        );
    }   

    ob_start();
    ?>
    <div class="wrap">
        <h1>Create Question</h1>
        <form method="POST" action="" id="question-form">
        <div class="container">
            <!-- Question form -->
            <div>
                <label for="question_type">Question Type</label>
                <br>
                <select name="question_type" id="question_type" class="regular-text" required>
                    <option value="">Select a type</option>
                    <option value="multiple_choice">Multiple Choice</option>
                    <option value="true_false">True/False</option>
                    <option value="short_answer">Short Answer</option>
                </select>
                <br><br>
                <label for="question_text">Question Text</label>
                <br>
                <textarea name="question_text" id="question_text" rows="5" cols="50" class="regular-text" required></textarea>
                <br>
                <h1>Choices</h1>
                <div id="choices-container">
                    <div class="choice-item gap">
                        <br>
                        <input type="text" name="choices[]" placeholder="Enter choice" class="regular-text" required>
                        <label>
                            <input type="checkbox" name="is_correct[]" value="1">Correct
                        </label>
                        <button type="button" class="button remove-choice-button">Remove</button>
                    </div>
                </div>
                <br>
                <button type="button" id="add-choice-button" class="button">Add Choice</button>
                <br><br>
            </div>
            <div>
                <h2>All Modules</h2>
                <input type="text" id="SearchBox" placeholder="Search modules..." style="margin-bottom: 10px; width: 25%; padding: 8px;">
                <table class="widefat fixed data">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Module Name</th>
                            <th>Description</th>
                            <th>Linking To</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($modules)) : ?>
                            <?php foreach ($modules as $module) : ?>
                                <tr>
                                    <td><?php echo esc_html($module->module_id); ?></td>
                                    <td><?php echo esc_html($module->module_name); ?></td>
                                    <td><?php echo esc_html($module->module_description); ?></td>
                                    <td>
                                        <input type="checkbox" name="link_to_modules[]" value="<?php echo esc_attr($module->module_id); ?>"
                                        <?php echo in_array($module->module_id, $linked_modules) ? 'checked' : ''; ?>>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4">No modules found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <p class="submit">
            <button type="submit" name="elearn_create_question" class="button button-primary">Create Question</button>
        </p>
        </form>
    </div>

    <!-- Add JavaScript for dynamic choice addition and removal -->
    <script>
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
    </script>
    <!-- search box -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const searchBox = document.getElementById("SearchBox");
            const table = document.querySelector(".data"); // Select the table with the class "data"

            searchBox.addEventListener("input", function() {
                const query = searchBox.value.toLowerCase();

                // Get all rows in the table body
                const rows = table.querySelectorAll("tbody tr");
                rows.forEach(row => {
                    const cells = row.querySelectorAll("td");
                    const rowText = Array.from(cells).map(cell => cell.textContent.toLowerCase()).join(" ");
                    if (rowText.includes(query)) {
                        row.style.display = ""; // Show the row
                    } else {
                        row.style.display = "none"; // Hide the row
                    }
                });
            });
        });
    </script>
    <?php
    echo ob_get_clean();
}
