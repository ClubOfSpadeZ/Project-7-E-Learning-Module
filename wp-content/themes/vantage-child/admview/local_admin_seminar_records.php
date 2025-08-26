<?php
/*
Template Name:  Seminar Records - HR Admins
*/

get_header();

// Check if the user is logged in and has the necessary roles
if (is_user_logged_in() && (current_user_can('administrator') || current_user_can('contributor') || current_user_can('WHS'))) {
    global $wpdb;
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;
    $user_email_domain = substr(strrchr($current_user->user_email, "@"), 1); // Extract the email domain

    // Retrieve all seminar records for users with the same email domain including email
    $shared_seminar_records = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT seminar_preprod2.*, {$wpdb->users}.user_email FROM seminar_preprod2 
             LEFT JOIN {$wpdb->users} ON seminar_preprod2.user_id = {$wpdb->users}.ID
             WHERE {$wpdb->users}.user_email LIKE %s
             ORDER BY seminar_preprod2.sem_date ASC",
            '%' . $wpdb->esc_like($user_email_domain) . '%'
        )
    );

    if ($shared_seminar_records) {
        echo '<style>
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
        echo '<h2 id="head">Org-Wide Seminar Records</h2>';

        // Filter by seminar dropdown select menu
        echo '<label for="seminarFilter">Filter by Seminar:</label>';
        echo '<select id="seminarFilter" onchange="filterRecords()">';
        echo '<option value="all">All Seminars</option>';
        $seminars = array(); // Array to store unique seminar names
        foreach ($shared_seminar_records as $record) {
            $seminars[$record->course] = $record->course;
        }
        foreach ($seminars as $seminar) {
            echo '<option value="' . esc_attr($seminar) . '">' . esc_html($seminar) . '</option>';
        }
        echo '</select>';
        echo '<br><br>'; // Add spacing between filter and table

        // Filter by name input field
        echo '<label for="nameFilter">Filter by Name:</label>';
        echo '<input type="text" id="nameFilter" onkeyup="filterRecords()" placeholder="Search by name..." title="Type in a name">';
        echo '<br><br>'; // Add spacing between filter and table

        // Filter by year select menu
        echo '<label for="yearFilter">Filter by Year:</label>';
        echo '<select id="yearFilter" onchange="filterRecords()">';
        echo '<option value="all">All Years</option>';
        $years = array(); // Array to store unique years
        foreach ($shared_seminar_records as $record) {
            $year = date('Y', strtotime($record->sem_date));
            $years[$year] = $year;
        }
        foreach ($years as $year) {
            echo '<option value="' . esc_attr($year) . '">' . esc_html($year) . '</option>';
        }
        echo '</select>';
        echo '<br><br>'; // Add spacing between filter and table

        // Download as CSV button
        echo '<button id="downloadCSV" class="button">Download as CSV</button>';

        echo '<table id="sharedSeminarTable">';
        echo '<thead><tr><th>User Name</th><th>Email</th><th>Date</th><th>Course</th></tr></thead>';
        echo '<tbody>';

        foreach ($shared_seminar_records as $record) {
            $user_info = get_userdata($record->user_id);
            $user_name = $user_info ? $user_info->display_name : 'Unknown User';

            echo '<tr class="seminarRow" data-seminar="' . esc_attr($record->course) . '" data-year="' . esc_attr(date('Y', strtotime($record->sem_date))) . '">
                <td>' . esc_html($user_name) . '</td>
                <td>' . esc_html($record->user_email) . '</td>
                <td>' . esc_html($record->sem_date) . '</td>
                <td>' . esc_html($record->course) . '</td>
            </tr>';
        }

        echo '</tbody>';
        echo '</table>';

        // JavaScript for CSV download and seminar, name, and year filtering
        echo '<script>
            function filterRecords() {
                var selectedSeminar = document.getElementById("seminarFilter").value.toUpperCase();
                var nameFilter = document.getElementById("nameFilter").value.toUpperCase();
                var selectedYear = document.getElementById("yearFilter").value.toUpperCase();
                var rows = document.getElementsByClassName("seminarRow");

                for (var i = 0; i < rows.length; i++) {
                    var seminar = rows[i].getAttribute("data-seminar").toUpperCase();
                    var userName = rows[i].getElementsByTagName("td")[0].innerText.toUpperCase();
                    var year = rows[i].getAttribute("data-year").toUpperCase();
                    if ((selectedSeminar === "ALL" || seminar === selectedSeminar) && 
                        (selectedYear === "ALL" || year === selectedYear) &&
                        userName.indexOf(nameFilter) > -1) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }

            function downloadCSV() {
                var rows = document.querySelectorAll("#sharedSeminarTable tbody tr");
                console.log("Number of rows:", rows.length);
                var csv = [];

                // Add header row
                var headerRow = [];
                document.querySelectorAll("#sharedSeminarTable thead th").forEach(function(th) {
                    headerRow.push(th.innerText);
                });
                csv.push(headerRow.join(","));

                // Add data rows
                rows.forEach(function(row) {
                    if (row.style.display !== "none") {
                        var rowData = [];
                        row.querySelectorAll("td").forEach(function(col) {
                            rowData.push(col.innerText);
                        });
                        csv.push(rowData.join(","));
                    }
                });

                console.log("CSV data:", csv);

                var csvContent = "data:text/csv;charset=utf-8," + csv.join("\\n");
                var encodedUri = encodeURI(csvContent);
                var link = document.createElement("a");
                link.setAttribute("href", encodedUri);
                link.setAttribute("download", "seminar_records.csv");
                document.body.appendChild(link);
                link.click();
            }
            document.getElementById("downloadCSV").addEventListener("click", function() {
                console.log("Download button clicked");
                downloadCSV();
            });


        </script>';
        
    } else {
        echo '<p style="font-size:25px">No shared seminar records found.</p>';
    }
} else {
    echo '<p  style="font-size:25px">Access denied. Please log in with the appropriate role.</p>';
}

get_footer();
?>
