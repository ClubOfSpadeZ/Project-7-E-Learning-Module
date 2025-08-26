<?php
/*
Template Name: All Seminar Records Sitewide - Admins
*/

get_header();

// Check if the user is logged in and has the necessary roles
if (is_user_logged_in() && current_user_can('administrator')) {
    global $wpdb;

    // Retrieve all seminar records from the database
    $all_seminar_records = $wpdb->get_results(
        "SELECT * FROM seminar_preprod2 ORDER BY sem_date ASC"
    );

    if ($all_seminar_records) {
        echo '<style>
            #allSeminarTable {
                border-collapse: collapse;
                width: 100%;
            }
            #allSeminarTable th, #allSeminarTable td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            #allSeminarTable th {
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
        echo '<h2 id="head">Site-Wide Seminar Records</h2>';

        // Search input field
        echo '<input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search for names.." title="Type in a name">';
        echo '<br><br>'; // Add spacing between search input and download button

        // Download as CSV button
        echo '<button id="downloadCSV" class="button">Download as CSV</button>';

        echo '<table id="allSeminarTable">';
        echo '<thead><tr><th>User Name</th><th>Domain</th><th>Date</th><th>Course</th></tr></thead>';
        echo '<tbody>';

        foreach ($all_seminar_records as $record) {
            $user_info = get_userdata($record->user_id);
            $user_name = $user_info ? $user_info->display_name : 'Unknown User';
            $user_email_domain = substr(strrchr($user_info->user_email, "@"), 1); // Extract the email domain


            echo '<tr>
                <td>' . esc_html($user_name) . '</td>
                                <td>' . esc_html($user_email_domain) . '</td>

                <td>' . esc_html($record->sem_date) . '</td>
                <td>' . esc_html($record->course) . '</td>
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
                table = document.getElementById("allSeminarTable");
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
                var headers = document.querySelectorAll("#allSeminarTable thead th");
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
                link.setAttribute("download", "seminar_records.csv");
                document.body.appendChild(link);
                link.click();
            }

            document.getElementById("downloadCSV").addEventListener("click", function() {
                var filter = document.getElementById("myInput").value.toUpperCase();
                var table = document.getElementById("allSeminarTable");
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
        echo '<p style="font-size:25px">No seminar records found.</p>';
    }
} else {
    echo '<p  style="font-size:25px">Access denied. Please log in with the appropriate role.</p>';
}

get_footer();
?>
