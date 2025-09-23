<?php
if (!defined('ABSPATH')) exit;

function elearn_manager_licence()
{
    if (!get_page_by_path('manager-licence')) {
        wp_insert_post([
            'post_title'   => 'Manager Licence',
            'post_name'    => 'manager-licence',
            'post_content' => '[manager_licence]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
}

register_activation_hook(__FILE__, 'elearn_manager_licence');
add_action('init', 'elearn_manager_licence');

function elearn_manager_licence_shortcode()
{
    if (!is_user_logged_in()) {
        return '<p>You must be logged in to view this page.</p>';
    }

    global $wpdb;
    $current_user_id = get_current_user_id();

    // Get the organisation_id from user metadata
    $organisation_id = get_user_meta($current_user_id, 'organisation_id', true);

    if (!$organisation_id) {
        return '<p>No organisation linked to your account.</p>';
    }

    $prefix = $wpdb->prefix . 'elearn_';
    $licences_table = $prefix . 'licence';
    $licences_in_org_table = $prefix . 'licences_in_organisation';

    // Query to fetch licences for the organisation
    $licences = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT l.licence_id, l.licence_name, l.user_amount, l.licence_cost
             FROM $licences_table l
             INNER JOIN $licences_in_org_table lo
             ON l.licence_id = lo.licence_licence_id
             WHERE lo.organisation_organisation_id = %s",
            $organisation_id
        )
    );

    ob_start();
    ?>
    <div class="manager-licence">
        <h1>Active Licences</h1>
        <?php if (!empty($licences)) : ?>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Licence ID</th>
                        <th>Licence Name</th>
                        <th>User Amount</th>
                        <th>Licence Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($licences as $licence) : ?>
                        <tr>
                            <td><?php echo esc_html($licence->licence_id); ?></td>
                            <td><?php echo esc_html($licence->licence_name); ?></td>
                            <td><?php echo esc_html($licence->user_amount); ?></td>
                            <td><?php echo esc_html($licence->licence_cost); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <p>No active licences found for your organisation.</p>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('manager_licence', 'elearn_manager_licence_shortcode');
