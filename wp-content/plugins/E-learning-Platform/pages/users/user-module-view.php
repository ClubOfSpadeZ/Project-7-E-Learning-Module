<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Create "Module View" page on activation
 */
function elearn_create_module_view_page() {
    if ( ! get_page_by_path( 'module-view' ) ) {
        wp_insert_post( [
            'post_title'   => 'Module View',
            'post_name'    => 'module-view',
            'post_content' => '[module_view]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ] );
    }
}
register_activation_hook( __FILE__, 'elearn_create_module_view_page' );
add_action( 'init', 'elearn_create_module_view_page' );

add_filter('the_title', function ($title, $id) {
    if (is_page('module-view') && in_the_loop()) {
        return ''; // Remove the title
    }
    return $title;
}, 10, 2);

/**
 * Shortcode: [module_view]
 */
function elearn_module_view_shortcode() {
    $dashboard_page = get_page_by_path('module-dash');
    $dashboard_url  = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/');
    
    $current_user = wp_get_current_user();
    $user_roles   = (array) $current_user->roles;
    
    global $wpdb;
    $user_id   = get_current_user_id();
    $module_id = intval($_GET['module_id']);

    $module_tbl      = $wpdb->prefix . 'elearn_module';
    $content_in_mods = $wpdb->prefix . 'elearn_content_in_modules';
    $question_tbl    = $wpdb->prefix . 'elearn_question';
    $choice_tbl      = $wpdb->prefix . 'elearn_choice';

    $module = $wpdb->get_row($wpdb->prepare("SELECT * FROM $module_tbl WHERE module_id = %d", $module_id));
    //display content only for logged-in users with specific roles unless it is the Demo Module
    if (!is_user_logged_in()) {
        return '<p>Please log in to access modules.</p>
            <a href="' . esc_url(home_url('/login')) . '">&larr; Login</a>';
    } elseif (!in_array('student', $user_roles) && !in_array('manager', $user_roles) && !in_array('administrator', $user_roles) && $module->module_name !== 'Demo Module') {
        return '<p>You do not have permission to access this page.</p>
                <a href="' . esc_url($dashboard_url) . '">&larr; Back to Dashboard</a>';
    } elseif (!isset($_GET['module_id'])) {
        return '<p>Invalid module ID or no module selected.</p>
                <a href="' . esc_url($dashboard_url) . '">&larr; Back to Dashboard</a>';
    }

    // Get questions and choices
    $rows = $wpdb->get_results($wpdb->prepare("
        SELECT q.question_id, q.question_text, q.question_type, 
               c.choice_id, c.choice_data, c.choice_correct
        FROM $module_tbl m
        JOIN $content_in_mods cim ON m.module_id = cim.module_module_id
        JOIN $question_tbl q ON cim.question_question_id = q.question_id
        LEFT JOIN $choice_tbl c ON q.question_id = c.question_id
        WHERE m.module_id = %d
        ORDER BY q.question_id, c.choice_id
    ", $module_id));

    // Organize questions
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

    ob_start();
    ?>
    <style>
    .elearn-module-view {
        max-width: 95%;
        margin: 0 auto 30px auto;
        text-align: center;
    }
    .elearn-module-view iframe {
        width: 100%;
        height: 600px;
        margin-top: 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
    }
    #elearnForm {
        max-width: 800px;
        margin: 0 auto 30px auto;
    }
    .elearn-question {
        margin-bottom: 20px;
    }
    .elearn-question h5 {
        margin-bottom: 10px;
        font-size: 1.1em;
    }
    .elearn-question label {
        display: block;
        margin-bottom: 6px;
        cursor: pointer;
    }
    input[type="text"] {
        width: 100%;
        max-width: 400px;
        padding: 6px 8px;
        border-radius: 4px;
        border: 1px solid #ccc;
    }
    #quizResult {
        text-align: center;
        margin-top: 20px;
        font-weight: bold;
        font-size: 1.1em;
    }
    #quizResult.pass {
        color: green;
    }
    #quizResult.fail {
        color: red;
    }
    #elearn-btn-back {
       display: inline-block;
        padding: 12px 24px;
        font-size: 16px;
        font-weight: 600;
        color: white;
        background: #3498db;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        transition: background 0.3s ease, transform 0.2s ease;
    }
    #elearn-btn-back:hover {
        background: #2c80b4;
        transform: translateY(-2px);    
    }

    #elearn-submit-btn {
        display: inline-block;
        padding: 12px 24px;
        font-size: 16px;
        font-weight: 600;
        color: #fff;
        background: #3498db;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        transition: background 0.3s ease, transform 0.2s ease;
    }
    #elearn-submit-btn:hover {
        background: #2c80b4;
        transform: translateY(-2px);
    }

    </style>

    <a href="<?php echo esc_url($dashboard_url); ?>" id="elearn-btn-back">&larr; Back to Dashboard</a>

    <div class="elearn-module-view">
        <h2><?php echo esc_html($module->module_name); ?></h2>
        <p><?php echo esc_html($module->module_description); ?></p>
        <p><strong>User:</strong> <?php echo esc_html(wp_get_current_user()->display_name); ?></p>
        <iframe src="<?php echo esc_url($module->module_pdf_path); ?>">This is where the learning content will be displayed</iframe>
    </div>

    <form id="elearnForm">
        <?php echo wp_nonce_field('submit_quiz', 'quiz_nonce', true, false); ?>
        <input type="hidden" name="module_id" value="<?php echo esc_attr($module_id); ?>">

        <?php $qcount = 1; foreach ($questions as $qid => $qdata): ?>
            <div class="elearn-question" data-qid="<?php echo esc_attr($qid); ?>">
                <h5>Question <?php echo $qcount; ?>: <?php echo esc_html($qdata['text']); ?></h5>

                <?php if ($qdata['type'] === 'multiple_choice' || $qdata['type'] === 'true_false'): ?>
                    <?php foreach ($qdata['choices'] as $choice): ?>
                        <label>
                            <input type="radio" name="question_<?php echo esc_attr($qid); ?>" value="<?php echo esc_attr($choice['id']); ?>">
                            <?php echo esc_html($choice['data']); ?>
                        </label>
                    <?php endforeach; ?>
                <?php elseif ($qdata['type'] === 'short_answer'): ?>
                    <label>Answer:
                        <input type="text" name="question_<?php echo esc_attr($qid); ?>">
                    </label>
                <?php endif; ?>
            </div>
        <?php $qcount++; endforeach; ?>

        <input type="submit" value="Submit Quiz" id="elearn-submit-btn">
    </form>

    <div id="quizResult"></div>

    <script>
    jQuery(document).ready(function($) {
        $('#elearnForm').on('submit', function(e) {
            e.preventDefault();
            const $form = $(this);
            const answers = {};
            $form.find('.elearn-question').each(function() {
                const qid = $(this).data('qid');
                const $selected = $(this).find('input[type="radio"]:checked');
                const $text = $(this).find('input[type="text"]');
                if ($selected.length) answers[qid] = $selected.val();
                else if ($text.length) answers[qid] = $text.val().trim();
            });

            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'check_quiz',
                module_id: <?php echo $module_id; ?>,
                answers: JSON.stringify(answers),
                quiz_nonce: '<?php echo wp_create_nonce('submit_quiz'); ?>'
            }, function(response) {
                if (!response.success) {
                    $('#quizResult').removeClass('pass fail').html('Error: ' + response.data);
                    return;
                }
                const score = response.data.score;
                const total = response.data.total;
                if (score === total) {
                    $('#quizResult')
                        .removeClass('fail')
                        .addClass('pass')
                        .html(`🎉 Congratulations! You passed with ${score}/${total}.`);
                } else {
                    let resultMessage = `You scored ${score}/${total}. Please try again — 100% required to pass.`;

                    if (response.data.incorrect && response.data.incorrect.length > 0) {
                        const incorrectNums = [];
                        $('.elearn-question').each(function(index) {
                            if (response.data.incorrect.includes($(this).data('qid'))) {
                                incorrectNums.push(index + 1);
                            }
                        });
                        resultMessage += `<br><br><strong>Incorrect Answers:</strong> ${incorrectNums.map(n => 'Question ' + n).join(', ')}`;
                    }

                    $('#quizResult')
                        .removeClass('pass')
                        .addClass('fail')
                        .html(resultMessage);
                }
                // Log attempt if not the demo module
                if ('<?php echo esc_js($module->module_name); ?>' !== 'Demo Module') {
                    $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                        action: 'log_attempt',
                        module_id: <?php echo $module_id; ?>,
                        score: score,
                        total: total,
                        quiz_nonce: '<?php echo wp_create_nonce('submit_quiz'); ?>'
                    });
                }
            });
        });
    });
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('module_view', 'elearn_module_view_shortcode');
