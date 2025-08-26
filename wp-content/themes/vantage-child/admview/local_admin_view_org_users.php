<?php
/*
Template Name: Local Admin - View Org Users
*/

// Get header
get_header();

// Check if user is logged in
if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
    $current_email = $current_user->user_email;
    $email_domain = substr($current_email, strpos($current_email, '@') + 1);

    // Check if user is administrator or contributor
    if (current_user_can('administrator') || current_user_can('contributor')) {
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
                color: #000;
                padding: 10px 20px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                font-size: 16px;
            }
            .button:hover {
                background-color: #8ab8d5;
            }
        </style>';
        // Display table for users
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">';
        echo '    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
';
        echo '<h2>Users in your organisation (@' . $email_domain . ')</h2>';
        echo '<button class="button" onclick="exportToCSV()">Export to CSV</button>';
        echo '<table id="userTable" class="table">';
        echo '<thead><tr><th>Name</th><th>Email Address</th><th>Role</th></tr></thead>';
        echo '<tbody>';

        // Query users with matching email domain and roles
        $args = array(
            'search'         => '*' . $email_domain . '*', // Search by email domain
            'search_columns' => array('user_email'),
            'role__in'       => array('subscriber', 'contributor', 'editor', 'author', 'administrator'), // Filter by roles
        );
        $users = get_users($args);

        // Display users in table rows
        foreach ($users as $user) {
            $user_roles = $user->roles;
            $user_role = !empty($user_roles) ? $user_roles[0] : 'N/A'; // Get the first role assigned to the user
            echo '<tr>';
            echo '<td>' . esc_html($user->display_name) . '</td>';
            echo '<td>' . esc_html($user->user_email) . '</td>';
            echo '<td>' . esc_html($user_role) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';

        // JavaScript function for exporting to CSV
        echo '<script>
            function exportToCSV() {
                var csv = [];
                var rows = document.querySelectorAll("#userTable tr");

                for (var i = 0; i < rows.length; i++) {
                    var row = [], cols = rows[i].querySelectorAll("td, th");
                    for (var j = 0; j < cols.length; j++) 
                        row.push(cols[j].innerText);
                    csv.push(row.join(","));
                }

                // Download CSV file
                var csvContent = "data:text/csv;charset=utf-8," + csv.join("\\n");
                var encodedUri = encodeURI(csvContent);
                var link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "users.csv");
                document.body.appendChild(link);
                link.click();
            }
        </script>';
    } else {
        // Display message for users without permission
        echo '<p>Sorry, you do not have permission to access this page.</p>';
    }
} else {
    // Display message for non-logged-in users
    echo '<p>Please log in to view this page.</p>';
}

// Get footer
get_footer();
?>
