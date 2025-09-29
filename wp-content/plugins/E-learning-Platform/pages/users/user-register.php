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

function elearn_user_register_shortcode()
{
    ob_start();
?>
    <div class="user-register">
        <h1>User Registration</h1>
        <p>Registration form will be here.</p>
        <form method="post">
            <p>
                <label for="username">Username</label><br>
                <input type="text" name="username" required>
            </p>
            <p>
                <label for="email">Email</label><br>
                <input type="email" name="email" required>
            </p>
            <p>
                <label for="password">Password</label><br>
                <input type="password" name="password" required>
            </p>
            <p>
                <label for="org_id">Organisation ID</label><br>
                <input type="text" name="org_id" required>
            </p>
            <p>
                <label for="access_code">Access Code</label><br>
                <input type="text" name="access_code" required>
            </p>
            <p>
                <input type="submit" name="student_register" value="Register">
            </p>
        </form>
    </div>
<?php
    return ob_get_clean();
}

add_shortcode('user_register', 'elearn_user_register_shortcode');
