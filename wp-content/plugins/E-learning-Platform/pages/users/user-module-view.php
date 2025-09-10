<?php
// Stop direct access for security
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include WordPress header template
get_header();

// Check if a module ID is provided in the URL
if (!isset($_GET['module_id'])) {
    echo '<p>Invalid module ID or no module selected.</p>';
    return; // Stop execution if module_id is missing
}

global $wpdb;

// Sanitize module ID to ensure it's an integer
$module_id  = intval($_GET['module_id']);
$module_tbl = $wpdb->prefix . 'elearn_module';

// Query the database to retrieve the module information
$module = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM $module_tbl WHERE module_id = %d", $module_id)
);

// If the module does not exist, display an error
if (!$module) {
    echo '<p>Module not found.</p>';
    return;
}

// Display module information
echo '<div class="elearn-module-view">
        <h2>' . esc_html($module->module_name) . '</h2>
        <p>' . esc_html($module->module_description) . '</p>
      </div>';

// Get all qs and associated choices for this module
$query = $wpdb->prepare("
    SELECT q.question_id, q.question_text, q.question_type,
           c.choice_id, c.choice_data, c.choice_correct
    FROM {$wpdb->prefix}elearn_module m
    JOIN {$wpdb->prefix}elearn_content_in_modules cim 
        ON m.module_id = cim.module_module_id
    JOIN {$wpdb->prefix}elearn_question q 
        ON cim.question_question_id = q.question_id
    LEFT JOIN {$wpdb->prefix}elearn_choice c 
        ON q.question_id = c.question_id
    WHERE m.module_id = %d
    ORDER BY q.question_id, c.choice_id
", $module_id);

// Execute the query
$rows = $wpdb->get_results($query);

// Organize questions into a nested array for easier display
$questions = [];
foreach ($rows as $row) {
    $qid = $row->question_id;

    // Initialize a question entry if it doesn't exist
    if (!isset($questions[$qid])) {
        $questions[$qid] = [
            'text'    => $row->question_text,
            'type'    => $row->question_type,
            'choices' => []
        ];
    }

    // Add each choice to the question
    if ($row->choice_id) {
        $questions[$qid]['choices'][] = [
            'id'      => $row->choice_id,
            'data'    => $row->choice_data,
            'correct' => (bool) $row->choice_correct, // True/false value for server-side reference
        ];
    }
}

// call the shortcode to display the quiz form
echo do_shortcode('[elearn_quiz]');
?>


<!-- Div to display quiz results -->
<div id="quizResult"></div>

<script>
jQuery(document).ready(function($) {
    // Handle form submission
    $('#elearnForm').on('submit', function(e) {
        e.preventDefault(); // Prevent page reload

        const $form = $(this);
        const answers = {};

        // Loop through each question container
        $form.find('.elearn-question').each(function() {
            const qid = $(this).data('qid'); // Retrieve question ID
            const $selected = $(this).find('input[type="radio"]:checked'); // Check for selected radio
            const $text = $(this).find('input[type="text"]'); // Check for short-answer input

            // Save selected radio or text input to answers object
            if ($selected.length) answers[qid] = $selected.val();
            else if ($text.length) answers[qid] = $text.val().trim();
        });

        // Send answers to the server using AJAX
        $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'check_quiz', // AJAX action defined in the plugin
            module_id: <?php echo $module_id; ?>, // Current module ID
            answers: JSON.stringify(answers), // Convert answers object to JSON
            quiz_nonce: '<?php echo wp_create_nonce('submit_quiz'); ?>' // Security nonce
        }, function(response) {
            // Display results or error
            if (response.success) {
                $('#quizResult').html(`Your score: ${response.data.score} out of ${response.data.total}`);
            } else {
                $('#quizResult').html('Error: ' + response.data);
            }
        });
    });
});
</script>

<?php
// Include WordPress footer template
get_footer();
