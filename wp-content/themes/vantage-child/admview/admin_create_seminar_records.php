<?php
/*
Template Name: Seminar Attendance Form - HR Admin
*/

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="entry-content">
            <?php
            // Check if the current user has admin or contributor roles
            $current_user = wp_get_current_user();
            if (in_array('administrator', (array)$current_user->roles) || in_array('contributor', (array)$current_user->roles)) {
                $current_user = wp_get_current_user();
                $user_id = $current_user->ID;
                $user_email_domain = substr(strrchr($current_user->user_email, "@"), 1);

                if (current_user_can('manage_options')) {
                    // If the user is an admin, allow them to search for any user
                    $users = get_users(array('role__in' => array('subscriber', 'contributor')));
                } else {
                    // If the user is not an admin, allow them to search for users with the same email domain
                    $users = get_users(array('role__in' => array('subscriber', 'contributor'), 'search' => "*@$user_email_domain"));
                }

                if ($users) {
                    ?>
					<h2>
						 Enter Seminar Attendance - Organisation Wide
			</h2>
                    <form style="font-size:20px; color:black;" method="post" action="">
                        <div class="form-group">
                            <label for="staff_name">Staff Name:</label>
                            <select name="staff_name" id="staff_name">
                                <?php
                                foreach ($users as $user) {
                                    echo '<option value="' . esc_attr($user->user_email) . '">' . esc_html($user->display_name) . '</option>';
                                }
                                ?>
                            </select>
							                    <p id="selected_user_email"></p> <!-- Placeholder for selected user's email -->

                        </div>

                        <div style="height: 0px;"></div> <!-- Add a gap -->

                        <div class="form-group">
                            <label for="course">Select Course:</label>
                            <select name="course" id="course">
                                <option value="Smoke Free">Smoke Free</option>
                                <option value="Manual Handling">Manual Handling</option>
								<option value="Models of Good Health">Models of Good Health</option>
								<option value="Seven Habits of Fit and Healthy People">Seven Habits of Fit and Healthy People</option>
								<option value="Musculo-skeletal Health seminar">Musculo-skeletal Health seminar</option>
								<option value="Manual Handling seminar">Manual Handling seminar</option>
								<option value="Pro-Active Rehab">Pro-Active Rehab</option>
								<option value="How to Manage Stress">How to Manage Stress</option>
								<option value="Work-Life Balance">Work-Life Balance</option>
								<option value="How to relax">How to relax</option>
								<option value="How to get a good night's sleep">How to get a good night's sleep</option>
								<option value="Seven Habits of Fit and Healthy People">Seven Habits of Fit and Healthy People</option>
								<option value="Integral Training">Integral Training</option>
								<option value="How to Improve Metabolic Health">How to Improve Metabolic Health</option>
								<option value="	How to improve Aerobic Fitness">	How to improve Aerobic Fitness</option>
								<option value="Complete Fitness Workout">Complete Fitness Workout</option>
								<option value="	Hourglass Diet">Hourglass Diet</option>
								<option value="How to Lower Blood Pressure Glucose and Cholesterol">How to Lower Blood Pressure Glucose and Cholesterol</option>
								<option value="Headache Free">Headache Free</option>

								
                            </select>
                        </div>

                        <div style="height: 30px;"></div> <!-- Add a gap -->

                        <div class="form-group">
                            <label for="sem_date">Attendance Date:</label>
                            <input type="date" name="sem_date" id="sem_date" required>
                        </div>

                        <div style="height: 30px;"></div> <!-- Add a gap -->

                        <div class="form-group">
                            <input type="submit" name="submit_attendance" value="Submit">
                        </div>
                    </form>


                    <script>
                        // JavaScript to display selected user's email address
                        document.getElementById('staff_name').addEventListener('change', function() {
                            var selectedUser = this.value;
                            document.getElementById('selected_user_email').innerText = 'Selected User Email: ' + selectedUser;
                        });
                    </script>

                    <?php
                    // Process form submission
                    if (isset($_POST['submit_attendance'])) {
                        global $wpdb;

                        $user_email = sanitize_text_field($_POST['staff_name']);
                        $user_id = get_user_by('email', $user_email)->ID;
                        $course = sanitize_text_field($_POST['course']);
                        $sem_date = sanitize_text_field($_POST['sem_date']);

                        // Insert data into the custom table
                        $table_name = 'seminar_preprod2';
                        $wpdb->insert(
                            $table_name,
                            array(
                                'user_id' => $user_id,
                                'sem_date' => $sem_date,
                                'course' => $course,
                            ),
                            array('%d', '%s', '%s')
                        );

                        echo '<p>Attendance recorded successfully!</p>';
                    }
                } else {
                    echo '<p>No staff members found for the current user.</p>';
                }
            } else {
                echo '<p>Access denied. You do not have the required roles to view this page.</p>';
            }
            ?>
        </div>
    </main>
</div>

<?php
get_footer();
?>
