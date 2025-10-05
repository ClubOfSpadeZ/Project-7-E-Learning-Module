<?php
if ( ! defined( 'ABSPATH' ) ) exit;
function elearn_user_register()
{
    if (!get_page_by_path('user-register')) {
        wp_insert_post([
            'post_title'   => 'User Registration',
            'post_name'    => 'user-register',
            'post_content' => '[user_register]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}

register_activation_hook(__FILE__, 'elearn_user_register');
add_action('init', 'elearn_user_register');

add_filter('the_title', function ($title, $id) {
    if (is_page('user-register') && in_the_loop()) {
        return ''; // Remove the title
    }
    return $title;
}, 10, 2);

function elearn_user_register_shortcode()
{
    if (!is_user_logged_in()) {
        // Redirect non-logged-in users to the registration page
        return '<p>You must be logged in to access this page. <a href="' . esc_url(wp_registration_url()) . '">Register here</a>.</p>';
    }

    $current_user_id = get_current_user_id();
    $organisation_id = get_user_meta($current_user_id, 'organisation_id', true);

    // Check if the user is already in an organisation
    if (!empty($organisation_id)) {
        return '<p>You are already part of an organisation.</p>';
    }

    // Display the form
    ob_start();
    ?>
    <div class="user-register">
        <h1>User Registration</h1>
        <p>To join your organisation, please enter the Access Code provided by your manager.</p>
        <form method="post" id="user-register-form">
            <p>
                <label for="access_code">Access Code</label><br>
                <input type="text" id="access_code" name="access_code" required>
            </p>
            <p>
                <input type="submit" name="student_register" value="Register">
            </p>
        </form>
    </div>
    <script>
        document.getElementById('user-register-form').addEventListener('submit', async function(event) {
            event.preventDefault(); // Prevent the form from submitting traditionally

            const accessCodeInput = document.getElementById('access_code');
            const accessCode = accessCodeInput.value;

            console.log('Access Code Entered:', accessCode); // Debugging

            // Hash the access code using SHA-256
            const hashedAccessCode = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(accessCode));
            const hashedAccessCodeHex = Array.from(new Uint8Array(hashedAccessCode))
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');

            console.log('Hashed Access Code:', hashedAccessCodeHex); // Debugging

            // Send the hashed access code via AJAX
            const response = await fetch(elearnQuiz.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'verify_access_code',
                    access_code: hashedAccessCodeHex,
                }),
            });

            const result = await response.json();
            console.log('Server Response:', result); // Debugging

            if (result.success) {
                alert(result.data.message);
                window.location.href = result.data.redirect_url; // Redirect to the module dashboard
            } else {
                alert(result.data.message);
            }
        });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('user_register', 'elearn_user_register_shortcode');
