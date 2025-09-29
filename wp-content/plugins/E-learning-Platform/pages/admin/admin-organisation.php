<?php

function elearn_add_organisation_menu()
{
    add_submenu_page(
        'elearn-module',         // Parent slug (Module List)
        'Organisations',        // Page title
        'Organisations',        // Menu title
        'manage_options',
        'elearn-organisation',
        'elearn_organisation_page'
    );
}
add_action('admin_menu', 'elearn_add_organisation_menu');

function validate_abn($abn)
{
    $weights = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];

    // Remove spaces
    $abn = preg_replace('/\s+/', '', $abn);

    // Must be 11 digits
    if (!preg_match('/^\d{11}$/', $abn)) {
        return false;
    }

    // Subtract 1 from first digit
    $digits = str_split($abn);
    $digits[0] = $digits[0] - 1;

    // Apply weights
    $sum = 0;
    foreach ($digits as $i => $digit) {
        $sum += $digit * $weights[$i];
    }

    // Valid if divisible by 89
    return ($sum % 89 === 0);
}

function generate_abn()
{
    do {
        // Generate random 11 digits, first digit must be >=1
        $digits = [];
        $digits[0] = rand(1, 9);
        for ($i = 1; $i < 11; $i++) {
            $digits[$i] = rand(0, 9);
        }

        $abn = implode('', $digits);
    } while (!validate_abn($abn)); // repeat until valid

    // Format with spaces like 51 824 753 556
    return substr($abn, 0, 2) . ' ' .
        substr($abn, 2, 3) . ' ' .
        substr($abn, 5, 3) . ' ' .
        substr($abn, 8, 3);
}

function generate_org_id($number)
{
    // $number = sequential org count (e.g. 100, 101, 102…)
    $prefix = 'o';

    // Use the generate_abn() function to create the random number
    $random_abn = generate_abn(); // This will generate a valid ABN

    // Remove spaces from the ABN to use it as part of the organisation ID
    $random_abn = str_pad($number, 4, '0', STR_PAD_LEFT) . str_replace(' ', '', $random_abn);

    if ($random_abn[0] === '0') {
        $random_abn[0] = '1';
    }

    // Add a dash every 3 digits
    $formatted_abn = implode('-', str_split($random_abn, 3));

    // Return the organisation ID with the prefix and the formatted ABN
    return $prefix . $formatted_abn;
}


function elearn_organisation_page()
{
    global $wpdb;

    $prefix = $wpdb->prefix . 'elearn_';
    $organisation_table = $prefix . 'organisation';

    // Handle "Create Organisation" button click
    if (isset($_POST['create_organisation'])) {
        // Get the current count of organisations
        $org_count = $wpdb->get_var("SELECT COUNT(*) FROM $organisation_table");

        // Generate a unique organisation ID
        $new_org_id = generate_org_id($org_count + 1);

        // Insert the new organisation into the database
        $wpdb->insert(
            $organisation_table,
            [
                'organisation_id' => $new_org_id,
                'organisation_name' => 'New Organisation',
                'organisation_address' => null,
                'organisation_phone' => null,
                'organisation_email' => null,
                'organisation_abn' => null,
                'organisation_created' => current_time('mysql'),
            ],
            [
                '%s', // organisation_id
                '%s', // organisation_name
                '%s', // organisation_address
                '%s', // organisation_phone
                '%s', // organisation_email
                '%s', // organisation_abn
                '%s', // organisation_created
            ]
        );

        // Display a success message
        if ($wpdb->last_error) {
            echo '<div class="notice notice-error"><p>Error: ' . esc_html($wpdb->last_error) . '</p></div>';
        } else {
            echo '<div class="notice notice-success"><p>Organisation created successfully! ID: ' . esc_html($new_org_id) . '</p></div>';
        }
    }

    $results = $wpdb->get_results("SELECT * FROM $organisation_table");

    // Display the page
    echo '<div class="wrap">';
    echo '<h1>Organisations</h1>';
    echo '<form method="POST" action="">';
    echo '<button type="submit" name="create_organisation" class="button button-primary">Create Organisation</button>';
    echo '</form>';
    echo '</div>';

     echo '<div class="wrap"><h1>Organisations Table</h1>';
    if (!empty($results)) {
        echo '<table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Phone</th>
                        <th>Email</th>
                        <th>ABN</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($results as $row) {
            $edit_url = admin_url('admin.php?page=elearn-edit-organisation&organisation_id=' . urlencode($row->organisation_id));
            $delete_url = admin_url('admin.php?page=elearn-organisation&action=delete&organisation_id=' . intval($row->organisation_id));
               echo '<tr>
                    <td>' . esc_html($row->organisation_id) . '</td>
                    <td>' . esc_html($row->organisation_name) . '</td>
                    <td>' . esc_html($row->organisation_address) . '</td>
                    <td>' . esc_html($row->organisation_phone) . '</td>
                    <td>' . esc_html($row->organisation_email) . '</td>
                    <td>' . esc_html($row->organisation_abn) . '</td>
                    <td>' . esc_html($row->organisation_created) . '</td>
                    <td>
                        <a href="' . esc_url($edit_url) . '" class="button">Edit</a>
                        <a href="' . esc_url($delete_url) . '" class="button delete-organisation" style="color: red; border-color: red;" data-organisation-id="' . esc_attr($row->organisation_id) . '">Delete</a>
                    </td>
                </tr>';
        }
        echo '</tbody></table>';
    echo '</div>';
    } else {
        echo '<p>No organisation data found.</p>';
    }
}
