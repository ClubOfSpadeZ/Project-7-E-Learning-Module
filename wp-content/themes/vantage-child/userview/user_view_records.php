<?php
/*
Template Name: Seminar Attendance Records
*/
//Developed by Group 4 of the Capstone 2024 Project in Semester 1 at the University of Canberra Australia.
get_header();

if (is_user_logged_in()) {
    global $wpdb;
    $current_user = wp_get_current_user();
    $user_id = $current_user->ID;

    // Retrieve user's attendance records from the custom table
    $attendance_records = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM seminar_preprod2 WHERE user_id = %d ORDER BY sem_date ASC",
            $user_id
        )
    );

    if ($attendance_records) {
        // Get unique courses for dropdown
        $courses = array_unique(wp_list_pluck($attendance_records, 'course'));

        echo '<style>
            #mytable {
                border-collapse: collapse;
                width: 100%;
            }
            #mytable th, #mytable td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            #mytable th {
                background-color: #f2f2f2;
            }
			
			#head {
			font-size: 25px;
			font-weight: bold;
			}
			
			#main {
			font-size:20px;
			}
			
			.space {
			    margin-top: 20px;
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
        
        echo '<h2 id="head">My Seminar Attendance Records</h2>';
        echo '<label for="courseFilter">Filter by Course:</label>';
        echo '<select id="courseFilter" onchange="filterTable()">';
        echo '<option value="">All Courses</option>';
        foreach ($courses as $course) {
            echo '<option value="' . esc_attr($course) . '">' . esc_html($course) . '</option>';
        }
        echo '</select>';
						echo'<br>';

        // Download as CSV button
        echo '<button class="button space" id="downloadCSV">Download as CSV (Filtered)</button>';
		echo '&nbsp;';
        echo '<button class="button" id="downloadAllCSV">Download as CSV (All)</button>';

        echo '<table id="mytable">';
        echo '<thead><tr><th>Date</th> <th> Course </th></tr></thead>';
        echo '<tbody>';
        foreach ($attendance_records as $record) {
            echo '<tr>
                <td>' . esc_html($record->sem_date) . '</td>
                <td>' . esc_html($record->course) . '</td>
            </tr>';
        }

        echo '</tbody>';
        echo '</table>';

        echo '<script>
            function filterTable() {
                var input, filter, table, tr, td, i, txtValue;
                input = document.getElementById("courseFilter");
                filter = input.value.toUpperCase();
                table = document.getElementById("mytable");
                tr = table.getElementsByTagName("tr");
                for (i = 0; i < tr.length; i++) {
                    td = tr[i].getElementsByTagName("td")[1]; // Index 1 corresponds to the Course column
                    if (td) {
                        txtValue = td.textContent || td.innerText;
                        if (filter === "" || txtValue.toUpperCase().indexOf(filter) > -1) {
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
                var headers = document.querySelectorAll("#mytable thead th");
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
                link.setAttribute("download", "attendance_records_filtered.csv");
                document.body.appendChild(link);
                link.click();
            }

            function downloadAllCSV() {
                var table = document.getElementById("mytable");
                var rows = table.getElementsByTagName("tr");
                downloadCSV(rows);
            }

            document.getElementById("downloadCSV").addEventListener("click", function() {
                var filter = document.getElementById("courseFilter").value.toUpperCase();
                var table = document.getElementById("mytable");
                var tr = table.getElementsByTagName("tr");
                var filteredRows = [];

                for (var i = 0; i < tr.length; i++) {
                    var td = tr[i].getElementsByTagName("td")[1]; // Index 1 corresponds to the Course column
                    if (td) {
                        var txtValue = td.textContent || td.innerText;
                        if (filter === "" || txtValue.toUpperCase().indexOf(filter) > -1) {
                            filteredRows.push(tr[i]);
                        }
                    }
                }

                downloadCSV(filteredRows);
            });

            document.getElementById("downloadAllCSV").addEventListener("click", function() {
                downloadAllCSV();
            });
        </script>';
    } else {
        echo '<p>No attendance records found.</p>';
    }
} else {
    echo '<p>Please log in to view your attendance records.</p>';
}

get_footer();
?>
