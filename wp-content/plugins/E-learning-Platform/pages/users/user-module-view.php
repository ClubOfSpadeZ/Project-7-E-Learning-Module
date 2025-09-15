<?php
if (!defined('ABSPATH')) exit;

function elearn_user_module_view_page() {
    get_header();

    // Check if a module ID is provided
    if (!isset($_GET['module_id'])) {
        echo '<p>Invalid module ID or no module selected.</p>';
        echo '<a href="' . esc_url(home_url('/dashboard')) . '">&larr; Back to Dashboard</a>';
        return;
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $module_id = intval($_GET['module_id']);
    $module_tbl = $wpdb->prefix . 'elearn_module';
    $attempt_tbl = $wpdb->prefix . 'elearn_attempt';
    $certificate_tbl = $wpdb->prefix . 'elearn_certificate';
    $score = 0;
    $total_questions = 0;
    

    $module = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $module_tbl WHERE module_id = %d", $module_id)
    );

    echo '<a href="' . esc_url(home_url('/dashboard')) . '">&larr; Back to Dashboard</a><br><br>';

    if (!$module) {
        echo '<p>Module not found.</p>';
        echo '<a href="' . esc_url(home_url('/dashboard')) . '">&larr; Back to Dashboard</a>';
        return;
    }

    // Display module info
    echo '<div class="elearn-module-view">
            <h2>' . esc_html($module->module_name) . '</h2>
            <p>' . esc_html($module->module_description) . '</p>
            <p>welcome user' .esc_html($user_id).'</p>
        </div>';

    // Fetch questions and choices for the module
        
    if (!$module_id) {
        return '<p>Invalid module ID or no module selected.</p>';
    }

    $module = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}elearn_module WHERE module_id=%d", $module_id));
    if (!$module) {
        return '<p>Module not found.</p>';
    }

    // Get questions & choices
    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT q.question_id, q.question_text, q.question_type, c.choice_id, c.choice_data, c.choice_correct
        FROM {$wpdb->prefix}elearn_module m
        JOIN {$wpdb->prefix}elearn_content_in_modules cim 
            ON m.module_id = cim.module_module_id
        JOIN {$wpdb->prefix}elearn_question q 
            ON cim.question_question_id = q.question_id
        LEFT JOIN {$wpdb->prefix}elearn_choice c 
            ON q.question_id = c.question_id
        WHERE m.module_id = %d
        ORDER BY q.question_id, c.choice_id
    ", $module_id));

    // Organize into nested array
    $questions = [];
    foreach ($rows as $row) {
        $qid = $row->question_id;
        if (!isset($questions[$qid])) {
            $questions[$qid] = [
                'text' => $row->question_text,
                'type' => $row->question_type,
                'choices' => []
            ];
        }
        if ($row->choice_id) {
            $questions[$qid]['choices'][] = [
                'id' => $row->choice_id,
                'data' => $row->choice_data,
                'correct' => (bool) $row->choice_correct,
            ];
        }
    }

    // Output form HTML
    $question_html  = '<form id="elearnForm">';
    $question_html .= wp_nonce_field('submit_quiz', 'quiz_nonce', true, false); 
    $question_html .= '<input type="hidden" name="module_id" value="' . esc_attr($module_id) . '">';

    foreach ($questions as $qid => $qdata) {
        $question_html .= '<div class="elearn-question" data-qid="' . esc_attr($qid) . '">';
        $question_html .= '<h3>' . esc_html($qdata['text']) . '</h3>';

        if ($qdata['type'] === 'multiple_choice' || $qdata['type'] === 'true_false') {
            foreach ($qdata['choices'] as $choice) {
                $question_html .= '<label>';
                $question_html .= '<input type="radio" name="question_' . esc_attr($qid) . '" value="' . esc_attr($choice['id']) . '">';
                $question_html .= esc_html($choice['data']);
                $question_html .= '</label><br>';
            }
        } elseif ($qdata['type'] === 'short_answer') {
            $question_html .= '<label>Answer: <input type="text" name="question_' . esc_attr($qid) . '"></label>';
        }

        $question_html .= '</div><br>';
    }
    $question_html .= '<input type="submit" value="Submit">';
    $question_html .= '</form>';
    $question_html .= '<div id="quizResult"></div>';
    echo $question_html;
    ?>

    <!--JavaScript to handle form submission and AJAX-->
    <script>
    jQuery(document).ready(function($) {
        $('#elearnForm').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const answers = {};

            // Gather answers from form
            $form.find('.elearn-question').each(function() {
                const qid = $(this).data('qid');
                const $selected = $(this).find('input[type="radio"]:checked');
                const $text = $(this).find('input[type="text"]');

                if ($selected.length) answers[qid] = $selected.val();
                else if ($text.length) answers[qid] = $text.val().trim();
            });

            // Submit answers to quiz checker
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'check_quiz',
                module_id: <?php echo $module_id; ?>,
                answers: JSON.stringify(answers),
                quiz_nonce: '<?php echo wp_create_nonce('submit_quiz'); ?>'
            }, function(response) {
                if (!response.success) {
                    $('#quizResult').html('Error submitting quiz: ' + response.data);
                    return; // Stop here if AJAX failed
                }

                const score = response.data.score;
                const total = response.data.total;

                // Show result to user
                if (score === total) {
                    $('#quizResult').html(`Congratulations! You passed with a score of ${score} out of ${total}.`);
                } else {
                    $('#quizResult').html(`You scored ${score} out of ${total}. Please try again, you need 100% to pass.`);
                }

                // Log attempt (and certificate if passed)
                $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'log_attempt',
                    module_id: <?php echo $module_id; ?>,
                    score: score,
                    total: total,
                    quiz_nonce: '<?php echo wp_create_nonce('submit_quiz'); ?>'
                });
            });
        });
    });
    </script>
    <?php 
    get_footer();
}
elearn_user_module_view_page();


