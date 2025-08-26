<?php
/*
Template Name: Global Admin - View All Users Page
*/

// Function to export users as CSV
function export_users_csv($users) {
    // Set headers for CSV file
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=users.csv');
    // Create a file pointer connected to the output stream
    $output = fopen('php://output', 'w');
    // Write the headers to the file
    fputcsv($output, array('Name', 'Email Address', 'Domain', 'Role'));
    // Write the user data to the file
    foreach ($users as $user) {
        $user_roles = $user->roles;
        $user_role = !empty($user_roles) ? $user_roles[0] : 'N/A'; // Get the first role assigned to the user
        $email_parts = explode('@', $user->user_email);
        $domain = end($email_parts);
        fputcsv($output, array($user->display_name, $user->user_email, $domain, $user_role));
    }
    // Close the file pointer
    fclose($output);
    // Prevent any other output
    exit;
}

// Check if export action is triggered
if (isset($_GET['export'])) {
    if (is_user_logged_in() && current_user_can('administrator')) {
        $export_type = sanitize_text_field($_GET['export']);
        if ($export_type === 'all') {
            // Query all users
            $args = array(
                'role__in' => array('administrator', 'subscriber', 'contributor'), // Include 'contributor' role
            );
            $users_query = new WP_User_Query($args);
            $users = $users_query->get_results();
            // Export all users as CSV
            export_users_csv($users);
        } elseif ($export_type === 'filtered') {
            if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
                $search_query = sanitize_text_field($_GET['search_query']);

                // Query users with matching username or email
                $args = array(
                    'search' => '*' . $search_query . '*',
                    'search_columns' => array('user_login', 'user_email'),
                    'role__in' => array('administrator', 'subscriber', 'contributor'), // Include 'contributor' role
                );
                $users_query = new WP_User_Query($args);
                $users = $users_query->get_results();

                // Export filtered users as CSV
                export_users_csv($users);
            } else {
                // No search query provided, redirect to the page without export action
                wp_redirect(get_permalink());
                exit;
            }
        }
    } else {
        // Handle permissions error
        exit('You do not have permission to access this page.');
    }
}

// Get header
get_header();

// Check if user is logged in and has admin role
if (is_user_logged_in() && current_user_can('administrator')) {
    // Display user filtering form and export buttons
    // You can customize this part according to your design preferences
    echo '<form id="searchForm" method="GET">';
    echo '<h2> View All Users - Global Administrator </h2>';
    echo '<input type="text" id="searchInput" name="search_query" placeholder="Search by Name or Email">';
    echo '<button type="submit" id="searchButton">Search</button>';
    echo '<br> <br>';
    echo '<button class="button"><a href="' . esc_url(add_query_arg(array('export' => 'all'))) . '">Export All as CSV</a></button>';
    echo '&nbsp;';
    echo '<button class="button"><a href="' . esc_url(add_query_arg(array('export' => 'filtered'))) . '">Export Filtered as CSV</a></button>';
    echo '</form>';

    echo '<style>
            #userTable {
                border-collapse: collapse;
                width: 100%;
            }
            #userTable th, #userTable td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            #userTable th {
                background-color: #f2f2f2;
            }
            #main {
                font-size:20px;
            }
            #head {
                font-size: 25px;
                font-weight: bold;
            }
            .button {
                background-color: #add8e6;
                color: black;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                font-size: 16px;
            }
           
        </style>';

    // Display table for users
    echo '<table id="userTable">';
    echo '<thead><tr><th>Name</th><th>Email Address</th><th>Domain</th><th>Role</th></tr></thead>';
    echo '<tbody>';

    // Check if search query is set
    if (isset($_GET['search_query']) && !empty($_GET['search_query'])) {
        $search_query = sanitize_text_field($_GET['search_query']);

        // Query users with matching username or email
        $args = array(
            'search' => '*' . $search_query . '*',
            'search_columns' => array('user_login', 'user_email'),
            'role__in' => array('administrator', 'subscriber', 'contributor'), // Include 'contributor' role
        );
        $users_query = new WP_User_Query($args);
        $users = $users_query->get_results();
    } else {
        // Query all users if no search query is provided
        $args = array(
            'role__in' => array('administrator', 'subscriber', 'contributor'), // Include 'contributor' role
        );
        $users_query = new WP_User_Query($args);
        $users = $users_query->get_results();
    }

    // Display all users in table rows
    foreach ($users as $user) {
        $user_roles = $user->roles;
        $user_role = !empty($user_roles) ? $user_roles[0] : 'N/A'; // Get the first role assigned to the user
        $email_parts = explode('@', $user->user_email);
        $domain = end($email_parts);
        echo '<tr>';
        echo '<td>' . esc_html($user->display_name) . '</td>';
        echo '<td>' . esc_html($user->user_email) . '</td>';
        echo '<td>' . esc_html($domain) . '</td>';
        echo '<td>' . esc_html($user_role) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

} else {
    // Display message for non-admin users
    echo '<p>Sorry, you do not have permission to access this page.</p>';
}

// Get footer
get_footer();
?>
