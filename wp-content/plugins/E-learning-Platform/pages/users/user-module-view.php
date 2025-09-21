<?php
if (!defined('ABSPATH')) exit;

function elearn_create_module_view_page() {
    if (!get_page_by_path('module-view')) {
        wp_insert_post([
            'post_title'   => 'Module View',
            'post_name'    => 'module-view',
            'post_content' => '[module_view]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}
register_activation_hook(__FILE__, 'elearn_create_module_view_page');
add_action('init', 'elearn_create_module_view_page');


/**
 * Shortcode: [module_view]
 */
function elearn_module_view_shortcode() {
    $dashboard_page = get_page_by_path('user-module-dash');
    $dashboard_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/');

    if (!isset($_GET['module_id'])) {
        return '<p>Invalid module ID or no module selected.</p>
                <a href="' . esc_url($dashboard_url) . '">&larr; Back to Dashboard</a>';
    }

    global $wpdb;
    $user_id   = get_current_user_id();
    $module_id = intval($_GET['module_id']);

    // Table names
    $module_tbl      = $wpdb->prefix . 'elearn_module';
    $content_in_mods = $wpdb->prefix . 'elearn_content_in_modules';
    $question_tbl    = $wpdb->prefix . 'elearn_question';
    $choice_tbl      = $wpdb->prefix . 'elearn_choice';

    // Fetch module info
    $module = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM $module_tbl WHERE module_id = %d", $module_id)
    );

    if (!$module) {
        return '<p>Module not found.</p>
                <a href="' . esc_url($dashboard_url) . '">&larr; Back to Dashboard</a>';
    }

    ob_start();
    // Get questions & choices
    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT q.question_id, q.question_text, q.question_type, 
               c.choice_id, c.choice_data, c.choice_correct
        FROM $module_tbl m
        JOIN $content_in_mods cim 
            ON m.module_id = cim.module_module_id
        JOIN $question_tbl q 
            ON cim.question_question_id = q.question_id
        LEFT JOIN $choice_tbl c 
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
                'text'    => $row->question_text,
                'type'    => $row->question_type,
                'choices' => []
            ];
        }
        if ($row->choice_id) {
            $questions[$qid]['choices'][] = [
                'id'      => $row->choice_id,
                'data'    => $row->choice_data,
                'correct' => (bool) $row->choice_correct,
            ];
        }
    }
    ?>
    <!-- Page display -->
    <style>
        .elearn-question {
            size: 10px;
            margin-bottom: 20px;
        }
        #quizResult {
            margin-top: 20px;
            font-weight: bold;
        }
    </style>

    <a href="<?php echo esc_url($dashboard_url); ?>">&larr; Back to Dashboard</a><br><br>

    <div class="elearn-module-view">
        <h2><?php echo esc_html($module->module_name); ?></h2>
        <p><?php echo esc_html($module->module_description); ?></p>
        <p>Welcome user <?php echo esc_html($user_id); ?></p>
        <embed src="<?php echo esc_html($module->module_pdf_path); ?>" type="application/pdf" />
    </div>
    <hr>


    <form id="elearnForm">
        <?php echo wp_nonce_field('submit_quiz', 'quiz_nonce', true, false); ?>
        <input type="hidden" name="module_id" value="<?php echo esc_attr($module_id); ?>">

        <?php foreach ($questions as $qid => $qdata): ?>
            <div class="elearn-question" data-qid="<?php echo esc_attr($qid); ?>">
                <h5><?php echo esc_html($qdata['text']); ?></h5>

                <?php if ($qdata['type'] === 'multiple_choice' || $qdata['type'] === 'true_false'): ?>
                    <?php foreach ($qdata['choices'] as $choice): ?>
                        <label>
                            <input type="radio" 
                                   name="question_<?php echo esc_attr($qid); ?>" 
                                   value="<?php echo esc_attr($choice['id']); ?>">
                            <?php echo esc_html($choice['data']); ?>
                        </label><br>
                    <?php endforeach; ?>
                <?php elseif ($qdata['type'] === 'short_answer'): ?>
                    <label>Answer: 
                        <input type="text" name="question_<?php echo esc_attr($qid); ?>">
                    </label>
                <?php endif; ?>
            </div><br>
        <?php endforeach; ?>

        <input type="submit" value="Submit">
    </form>

    <div id="quizResult"></div>

    <script>
    jQuery(document).ready(function($) {
        $('#elearnForm').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const answers = {};

            // Collect answers
            $form.find('.elearn-question').each(function() {
                const qid = $(this).data('qid');
                const $selected = $(this).find('input[type="radio"]:checked');
                const $text = $(this).find('input[type="text"]');

                if ($selected.length) answers[qid] = $selected.val();
                else if ($text.length) answers[qid] = $text.val().trim();
            });

            // Submit via AJAX
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'check_quiz',
                module_id: <?php echo $module_id; ?>,
                answers: JSON.stringify(answers),
                quiz_nonce: '<?php echo wp_create_nonce('submit_quiz'); ?>'
            }, function(response) {
                if (!response.success) {
                    $('#quizResult').html('Error submitting quiz: ' + response.data);
                    return;
                }

                const score = response.data.score;
                const total = response.data.total;

                if (score === total) {
                    $('#quizResult').html(`Congratulations! You passed with a score of ${score} out of ${total}.`);
                } else {
                    $('#quizResult').html(`You scored ${score} out of ${total}. Please try again, you need 100% to pass.`);
                }

                // Log attempt
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

    return ob_get_clean();
}
add_shortcode('module_view', 'elearn_module_view_shortcode');



