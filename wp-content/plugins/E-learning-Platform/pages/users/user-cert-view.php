<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function elearn_cert_view()
{
    if (!get_page_by_path('cert-view')) {
        wp_insert_post([
            'post_title'   => 'User Certificate View',
            'post_name'    => 'cert-view',
            'post_content' => '[cert_view]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}

register_activation_hook(__FILE__, 'elearn_cert_view');
add_action('init', 'elearn_cert_view');

add_filter('the_title', function ($title, $id) {
    if (is_page('cert-view') && in_the_loop()) {
        return ''; // Remove the title
    }
    return $title;
}, 10, 2);

function elearn_cert_shortcode() {
    
    global $wpdb;
    $username = wp_get_current_user();
    $name = $username->display_name;
    $dashboard_page = get_page_by_path('module-dash');
    $dashboard_url  = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/');
    $results_page = get_page_by_path('view-results');
    $results_url = $results_page ? get_permalink($results_page->ID) : home_url('/');

    $user_roles   = (array) $username->roles;

    if (!is_user_logged_in()) {
        return '<p>Please log in to access certficates.</p>
                <a href="https://healthfitlearning.wp.local/login">&larr; Login</a>';                
    } elseif (!in_array('student', $user_roles) && !in_array('manager', $user_roles) && !in_array('administrator', $user_roles)) {
        return '<p>You do not have permission to access this page.</p>
                <a href="' . esc_url($dashboard_url) . '">&larr; Back to Dashboard</a>';
    } elseif (!isset($_GET['cert_id'])||!isset($_GET['module_id'])) {
        return '<p>Invalid certificate or module ID.</p>
                <a href="' . esc_url($dashboard_url) . '">&larr; Back to Dashboard</a>';
    }
    // based on cert_id and module_id passed in URL get modulde name and cert time
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : get_current_user_id();
    //stops managers from viewing certs of users outside their organisation
    $viewer = wp_get_current_user();
    if (in_array('manager', $viewer->roles)) {
        $viewer_org = get_user_meta($viewer->ID, 'organisation_id', true);
        $target_org = get_user_meta($user_id, 'organisation_id', true);
        if ($viewer_org !== $target_org) {
            return '<p>You are not authorised to view this certificate.</p>';
        }
    }
    $module_id = intval($_GET['module_id']);
    $cert_id = intval($_GET['cert_id']);
    $module_tbl = $wpdb->prefix . 'elearn_module';
    $attempt_tbl = $wpdb->prefix . 'elearn_attempt';
    $cert_tbl = $wpdb->prefix . 'elearn_certificate';
    $organisation_tbl = $wpdb->prefix . 'elearn_organisation';
    $cert_details = $wpdb->get_row($wpdb->prepare(
        "SELECT m.module_name, c.certificate_completion
        FROM $cert_tbl c
        JOIN $attempt_tbl a ON c.attempt_id = a.attempt_id
        JOIN $module_tbl m ON a.module_module_id = m.module_id
        WHERE c.certificate_id = %d AND a.user_id = %d AND m.module_id = %d",
        $cert_id, $user_id, $module_id
    ));
    //format date
    $cert_date = $cert_details ? date('d/m/Y', strtotime($cert_details->certificate_completion)) : '';
    //get organisation name
    $organisation_id = trim(get_user_meta($user_id, 'organisation_id', true));
    $organisation_name = 'No Organisation';
    if (!empty($organisation_id)) {
        $organisation_row = $wpdb->get_row($wpdb->prepare(
            "SELECT organisation_name 
            FROM $organisation_tbl
            WHERE organisation_id = %s",
            $organisation_id
        ));
        if (!empty($organisation_row->organisation_name)) {
            $organisation_name = esc_html($organisation_row->organisation_name);
        };
    }

    ob_start();
    ?>
    <style>
        .certificate {
            width: 800px;
            height: 600px;
            padding: 40px;
            text-align: center;
            border: 10px solid #4CAF50;
            background-color: #fff;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            position: relative;
        }

        .certificate h1 {
            font-size: 48px;
            margin-bottom: 0;
            color: #333;
        }

        .certificate h2 {
            font-size: 24px;
            margin-top: 5px;
            color: #555;
        }

        .certificate .recipient {
            font-size: 32px;
            font-weight: bold;
            margin: 30px 0;
            color: #000;
        }

        .certificate .details {
            font-size: 20px;
            margin: 20px 0;
            color: #555;
        }

        .certificate .footer {
            position: absolute;
            bottom: 40px;
            left: 0;
            width: 100%;
            display: flex;
            justify-content: space-around;
            font-size: 16px;
            color: #333;
        }

        .certificate .signature {
            width: 200px;
            text-align: center;
            padding-top: 5px;
        }

        .certificate .signature hr {
            border: none;
            border-top: 1px solid #333;
            margin: 5px 0 0 0;
        }

    </style>

      <?php 
    //get user name from user_id in URL
    $user = get_userdata($user_id);
    $name = $user ? $user->display_name : 'Unknown User';
    ?>

    <!-- show all cert details here -->
    <div class="certificate">
        <h1>Certificate of Completion</h1>
        <h2>This is to certify that</h2>
        <div class="recipient"><?php echo $name ?></div>

        <div class="details">
            has successfully completed the module:<br>
            <strong><?php echo $cert_details->module_name ?></strong>
        </div>
        <div class="details">
            Date of Attainment: <strong><?php echo $cert_date ?></strong>
        </div>
        <div class="footer">
            <div class="signature">
                Fitness Frontline
                <hr><i>Provider:</i></div>
            <div class="signature">
                <?php echo $organisation_name; ?>
                <hr><i>Organisation:</i></div>
        </div>
    </div>
    <?php
    return ob_get_clean(); // return the HTML
}

add_shortcode('cert_view', 'elearn_cert_shortcode');
