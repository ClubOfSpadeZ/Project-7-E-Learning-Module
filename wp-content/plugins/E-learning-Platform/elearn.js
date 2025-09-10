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

        const data = $form.serializeArray();
        data.push({name: 'action', value: 'check_quiz'});
        data.push({name: 'answers', value: JSON.stringify(answers)});

        $.post(elearnQuiz.ajaxurl, data, function(response) {
            if (response.success) {
                $('#quizResult').html(`Your score: ${response.data.score} out of ${response.data.total}`);
            } else {
                $('#quizResult').html('Error: ' + response.data);
            }
        });
    });
});