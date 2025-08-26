<?php
/*
Template Name: Fitness Assesment Records Org-Wide - Admins
*/

get_header();

// Check if the user is logged in and has the necessary roles
if (is_user_logged_in() && (current_user_can('administrator') || current_user_can('contributor') || current_user_can('WHS'))) {
    global $wpdb;
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_email_domain = substr(strrchr($current_user->user_email, "@"), 1); // Extract the email domain

    // Retrieve all fitness assessment records for users with the same email domain including email
    $shared_fitness_records = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT fitness_survey.*, {$wpdb->users}.user_email FROM fitness_survey 
             LEFT JOIN {$wpdb->users} ON fitness_survey.user_id = {$wpdb->users}.ID
             WHERE {$wpdb->users}.user_email LIKE %s
             ORDER BY fitness_survey.date ASC",
            '%' . $wpdb->esc_like($user_email_domain) . '%'
        )
    );

    if ($shared_fitness_records) {
        echo '
<style>
    #sharedSeminarTable {
        border-collapse: collapse;
        width: 100%;
    }

    #sharedSeminarTable th, #sharedSeminarTable td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    #sharedSeminarTable th {
        background-color: #f2f2f2;
    }

    #main {
        font-size: 20px;
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
        echo '<h2 id="head">Org-Wide Fitness Assessment Records</h2>';

        // Search input field
        echo '<input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search for names.." title="Type in a name">';
        echo '<br><br>'; // Add spacing between search input and download button

        // Download as CSV button
        echo '<button id="downloadCSV" class="button">Download as CSV</button>';

        echo '<table id="sharedSeminarTable">';
        echo '<thead><tr><th>User Name</th><th>Email</th><th>Date</th><th>Body Composition</th><th>Situps</th><th>Squats</th><th>Pressups</th><th>Hamstring</th><th>Right leg on top sitting</th><th>Left leg on top sitting</th><th>Shoulder function</th><th>Aerobic Fitness</th><th>Total</th></tr></thead>';
        echo '<tbody>';

        foreach ($shared_fitness_records as $record) {
            $user_info = get_userdata($record->user_id);
            $user_name = $user_info ? $user_info->display_name : 'Unknown User';

            echo '<tr>
                <td>' . esc_html($user_name) . '</td>
                <td>' . esc_html($record->user_email) . '</td>
                <td>' . esc_html($record->date) . '</td>
                <td>' . esc_html($record->q1) . '</td>
                <td>' . esc_html($record->q2) . '</td>
                <td>' . esc_html($record->q3) . '</td>
                <td>' . esc_html($record->q4) . '</td>
                <td>' . esc_html($record->q5) . '</td>
                <td>' . esc_html($record->q6) . '</td>
                <td>' . esc_html($record->q7) . '</td>
                <td>' . esc_html($record->q8) . '</td>
                <td>' . esc_html($record->q9) . '</td>
                <td>' . esc_html($record->total) . '</td>
            </tr>';
        }

        echo '</tbody>';
        echo '</table>';

        // JavaScript for CSV download and search functionality
        echo '<script>
            function myFunction() {
                var input, filter, table, tr, td, i, txtValue;
                input = document.getElementById("myInput");
                filter = input.value.toUpperCase();
                table = document.getElementById("sharedSeminarTable");
                tr = table.getElementsByTagName("tr");

                for (i = 0; i < tr.length; i++) {
                    td = tr[i].getElementsByTagName("td")[0]; // Change the index to the column you want to search
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            tr[i].style.display = "";
                        } else {
                            tr[i].style.display = "none";
                        }
                    }
                }
            }

            function downloadCSV(rows) {
                var csv = [];

                // Add header row
                var headerRow = [];
                var headers = document.querySelectorAll("#sharedSeminarTable thead th");
                for (var h = 0; h < headers.length; h++) {
                    headerRow.push(headers[h].innerText);
                }
                csv.push(headerRow.join(","));

                // Add data rows
                for (var i = 0; i < rows.length; i++) {
                    var row = [];
                    var cols = rows[i].querySelectorAll("td");

                    for (var j = 0; j < cols.length; j++) {
                        row.push(cols[j].innerText);
                    }

                    csv.push(row.join(","));
                }

                var csvContent = "data:text/csv;charset=utf-8," + csv.join("\\n");
                var encodedUri = encodeURI(csvContent);
                var link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "fitness_assessment_records.csv");
                document.body.appendChild(link);
                link.click();
            }

            document.getElementById("downloadCSV").addEventListener("click", function () {
                var filter = document.getElementById("myInput").value.toUpperCase();
                var table = document.getElementById("sharedSeminarTable");
                var tr = table.getElementsByTagName("tr");
                var filteredRows = [];

                for (var i = 0; i < tr.length; i++) {
                    var td = tr[i].getElementsByTagName("td")[0]; // Change the index to the column you want to search
                    if (td) {
                        var txtValue = td.textContent || td.innerText;
                        if (txtValue.toUpperCase().indexOf(filter) > -1) {
                            filteredRows.push(tr[i]);
                        }
                    }
                }

                downloadCSV(filteredRows);
            });
        </script>';
    } else {
        echo '<p style="font-size:25px">No shared fitness assessment records found.</p>';
    }
} else {
    echo '<p style="font-size:25px">Access denied. Please log in with the appropriate role.</p>';
}

get_footer();
?>
