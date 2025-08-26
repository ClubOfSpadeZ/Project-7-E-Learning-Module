<?php
/*
Template Name: Universal Fitness Assessment Records Site-Wide - Admins
*/

get_header();

// Check if the user is logged in
if (is_user_logged_in() && current_user_can('administrator')) {
    global $wpdb;

    // Retrieve all seminar records from the database
    $all_seminar_records = $wpdb->get_results(
        "SELECT fs.*, SUBSTRING_INDEX(u.user_email, '@', -1) AS domain, u.display_name 
         FROM universal_fitness_survey fs 
         LEFT JOIN {$wpdb->users} u ON fs.user_id = u.ID 
         ORDER BY fs.date ASC"
    );

    if ($all_seminar_records) {
        echo '
<style>
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
        echo '<h2 id="head">Site-Wide Universal Fitness Assessment Records</h2>';

        // Search input field
        echo '
<input type="text" id="myInput" onkeyup="myFunction()" placeholder="Search for names.." title="Type in a name">';
        echo '
<br>
<br>'; // Add spacing between search input and download button

        // Download as CSV button
        echo '<button id="downloadCSV" class="button">Download as CSV</button>';

        echo '<table id="allSeminarTable">
    ';
        echo '
    <thead><tr><th>User Name</th><th>Email Domain</th><th>Date</th><th>Body Composition</th><th>Situps</th><th>Squats</th><th>Pressups</th><th>Hamstring</th><th>Right leg on top sitting</th><th>Left leg on top sitting</th><th>Shoulder function</th><th>Aerobic Fitness</th><th>Total</th></thead>';  // This is where you need to edit
        echo '
    <tbody>
        ';

        foreach ($all_seminar_records as $record) {
            $user_name = $record->display_name ? $record->display_name : 'Unknown User';

            echo '
        <tr>
            <td>'  . esc_html($user_name) . '</td>
            <td>' . esc_html($record->domain) . '</td>
            <td>' . esc_html($record->date) . '</td>
            <td>' . esc_html($record->q1)  .  '</td>
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

        echo '
    </tbody>';
        echo '
</table>';

        // JavaScript for CSV download and search functionality
        echo '
<script>
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
        link.setAttribute("download", "fitness_assessment_records.csv");
        document.body.appendChild(link);
        link.click();
    }

    document.getElementById("downloadCSV").addEventListener("click", function () {
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
        echo '<p style="font-size:25px">No  records found.</p>';
    }
} else {
    echo '<p style="font-size:25px">Access denied. Please log in as an administrator.</p>';
}

get_footer();
?>
