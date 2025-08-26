<?php
/*
Template Name: MS Specific Survey- Admin
*/

get_header();
?>
<style>
    option[value="0"],
    option[value="1"],
    option[value="2"],
    option[value="3"] {
        color: red;
    }

    option[value="4"],
    option[value="5"],
    option[value="6"] {
        color: orange;
    }
    option[value="7"],
    option[value="8"],
    option[value="9"],
    option[value="10"]{
        color: green;
    }

	
  table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed; /* Added property */
  }

  th, td {
    border: 1px solid black;
    background-color: green;
    text-align: center; /* Center align text */
    padding: 8px; /* Add padding for spacing */
  }

  #RED {
    background-color: red;
  }

  #YELLOW {
    background-color: orange;
  }
	
	

  #Excellent, #Dreadful {
    width: 10%; /* Adjust width as needed */
  }
	
	#Dreadful {
		background-color: red;
	}

</style>
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
				<b>Musculoskeletal Assesment</b>
			</h2>
	

                    <form style="font-size:20px; color:black;" method="post" action="">
                        <div class="form-group">
                            <label for="staff_name">Staff Name:</label>
                            <select name="staff_name" id="staff_name">
                                  <?php
    foreach ($users as $user) {
        echo '<option value="' . esc_attr($user->user_login) . '" data-email="' . esc_attr($user->user_email) . '">' . esc_html($user->display_name) . '</option>';
    }
    ?>
                            </select>
																					                    <p id="selected_user_email"></p> <!-- Placeholder for selected user's email -->

                        </div>

                        <div style="height: 30px;"></div> <!-- Add a gap -->
                        <div class="form-group">
                            <label for="date">Attendance Date:</label>
                            <input type="date" name="date" id="date" required>
                        </div>
						
                       

                    <div style="height: 30px;"></div> <!-- Add a gap -->
					<div class="form-group">
                    <label for="q1"><b>Current Condition</b><br>Rate the current condition of your musculoskeletal system<br>
					<table>
					  <tr>
						<th id="Dreadful">Dreadful</th>
						<th id="RED">0</th>
						<th id="RED">1</th>
						<th id="RED">2</th>
						<th id="RED">3</th>
						<th id="YELLOW">4</th>
						<th id="YELLOW">5</th>
						<th id="YELLOW">6</th>
						<th>7</th>
						<th>8</th>
						<th>9</th>
						<th>10</th>
						<th id="Excellent">Excellent</th>

					  </tr>
					</table></label>
                    <select name="q1" id="q1" onChange="onChange(this)">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>

				<div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q2"><b>Body Composition.</b>
						<br>How close are you to your ideal weight? Scores based on kilos over your ideal weight.
					<table>
					  <tr>
						<th id="Dreadful">Dreadful</th>
						<th id="RED">0</th>
						<th id="RED">1</th>
						<th id="RED">2</th>
						<th id="RED">3</th>
						<th id="YELLOW">4</th>
						<th id="YELLOW">5</th>
						<th id="YELLOW">6</th>
						<th>7</th>
						<th>8</th>
						<th>9</th>
						<th>10</th>
						<th id="Excellent">Excellent</th>
					  </tr>
					</table>
					</label>
                    <select name="q2" id="q2">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q3"><b>Lower body strength - squat</b><br>
						How many full squats can you do in 60 seconds?
					<table>
					  <tr>
						<th id="Dreadful">Dreadful</th>
						<th id="RED">0</th>
						<th id="RED">1</th>
						<th id="RED">2</th>
						<th id="RED">3</th>
						<th id="YELLOW">4</th>
						<th id="YELLOW">5</th>
						<th id="YELLOW">6</th>
						<th>7</th>
						<th>8</th>
						<th>9</th>
						<th>10</th>
						<th id="Excellent">Excellent</th>
					  </tr>
					</table>
				
					</label>
                    <select name="q3" id="q3">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q4"><b>Front body strength</b><br>
						sit-ups with feet held in 60 seconds
					<table>
					  <tr>
						<th id="Dreadful">Dreadful</th>
						<th id="RED">0</th>
						<th id="RED">1</th>
						<th id="RED">2</th>
						<th id="RED">3</th>
						<th id="YELLOW">4</th>
						<th id="YELLOW">5</th>
						<th id="YELLOW">6</th>
						<th>7</th>
						<th>8</th>
						<th>9</th>
						<th>10</th>
						<th id="Excellent">Excellent</th>
					  </tr>
					</table>
					
					
					</label>
                    <select name="q4" id="q4">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q5"><b>Upper body strength</b><br>
						press-ups in 60 seconds, Men on toes, women on front of thighs with knees, bottom and shoulders in a straight line
											<table>
					  <tr>
						<th id="Dreadful">Dreadful</th>
						<th id="RED">0</th>
						<th id="RED">1</th>
						<th id="RED">2</th>
						<th id="RED">3</th>
						<th id="YELLOW">4</th>
						<th id="YELLOW">5</th>
						<th id="YELLOW">6</th>
						<th>7</th>
						<th>8</th>
						<th>9</th>
						<th>10</th>
						<th id="Excellent">Excellent</th>
					  </tr>
					</table>
					</label>
                    <select name="q5" id="q5">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
					<label for="q6"><b>Sit and reach - hamstring flexibility</b><br>
						siting on the floor, with feet outstretched in front of you, see how far down past your toes you can reach with your fingers. Keep your knees straight.
					<table>
					  <tr>
						<th id="Dreadful">Dreadful</th>
						<th id="RED">0</th>
						<th id="RED">1</th>
						<th id="RED">2</th>
						<th id="RED">3</th>
						<th id="YELLOW">4</th>
						<th id="YELLOW">5</th>
						<th id="YELLOW">6</th>
						<th>7</th>
						<th>8</th>
						<th>9</th>
						<th>10</th>
						<th id="Excellent">Excellent</th>
					  </tr>
					</table>
					</label>
                    <select name="q6" id="q6">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gas -->
                <div class="form-group">
                    <label for="q7"><b>Ability to sit up straight with legs crossed - buttock flexibility
</b><br>	With legs crossed and hands clasped behind your back, see if you can sit up straight. Just being able to sit up with hands clasped
					<table>
					  <tr>
						<th id="Dreadful">Dreadful</th>
						<th id="RED">0</th>
						<th id="RED">1</th>
						<th id="RED">2</th>
						<th id="RED">3</th>
						<th id="YELLOW">4</th>
						<th id="YELLOW">5</th>
						<th id="YELLOW">6</th>
						<th>7</th>
						<th>8</th>
						<th>9</th>
						<th>10</th>
						<th id="Excellent">Excellent</th>
					  </tr>
					</table>
					</label>
                    <select name="q7" id="q7">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q8"><b>Shoulder function
</b><br>Wall test Stand with your back to the wall. Place your hands in the surrender position with the Back of your forearms, wrists and hands 
						flat back on the wall. Score 10 if you can do it with ease. 7/10 is just getting the ‘flat position’. Score lower if you can’t do it at all.
					<table>
					  <tr>
						<th id="Dreadful">Dreadful</th>
						<th id="RED">0</th>
						<th id="RED">1</th>
						<th id="RED">2</th>
						<th id="RED">3</th>
						<th id="YELLOW">4</th>
						<th id="YELLOW">5</th>
						<th id="YELLOW">6</th>
						<th>7</th>
						<th>8</th>
						<th>9</th>
						<th>10</th>
						<th id="Excellent">Excellent</th>
					  </tr>
					</table>
					</label>
                    <select name="q8" id="q8">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q9"><b>Strength training behaviour</b><br>Do you have a regular and systematic strength training program either at home or at the gym. Sessions per week.
					<table>
					  <tr>
						<th id="Dreadful">Dreadful</th>
						<th id="RED">0</th>
						<th id="RED">1</th>
						<th id="RED">2</th>
						<th id="RED">3</th>
						<th id="YELLOW">4</th>
						<th id="YELLOW">5</th>
						<th id="YELLOW">6</th>
						<th>7</th>
						<th>8</th>
						<th>9</th>
						<th>10</th>
						<th id="Excellent">Excellent</th>
					  </tr>
					</table>
					</label>
                    <select name="q9" id="q9">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q10"><b>Flexibility training behaviour</b>
					<br>Do you have a regular and systematic flexibility training program either at home or at the gym. It may include yoga, body balance, Pilates ... Sessions per week.
					<table>
					  <tr>
						<th id="Dreadful">Dreadful</th>
						<th id="RED">0</th>
						<th id="RED">1</th>
						<th id="RED">2</th>
						<th id="RED">3</th>
						<th id="YELLOW">4</th>
						<th id="YELLOW">5</th>
						<th id="YELLOW">6</th>
						<th>7</th>
						<th>8</th>
						<th>9</th>
						<th>10</th>
						<th id="Excellent">Excellent</th>
					  </tr>
					</table>
					
					
					</label>
                    <select name="q10" id="q10">
                        <option value="0">0</option>
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10">10</option>
                    </select>
                </div>

                        <div style="height: 30px;"></div> <!-- Add a gap -->

                        <div class="form-group">
                            <input type="submit" name="submit_attendance" value="Submit">
                        </div>
						
                    </form>
		<script>
    // JavaScript to display selected user's email address
    document.getElementById('staff_name').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];
        var selectedUserEmail = selectedOption.getAttribute('data-email');
        document.getElementById('selected_user_email').innerText = 'Selected User Email: ' + selectedUserEmail;
    });
</script>
                    <?php
                    // Process form submission
                    if (isset($_POST['submit_attendance'])) {
                        global $wpdb;

    $user_login = sanitize_text_field($_POST['staff_name']);
    $user_id = get_user_by('login', $user_login)->ID;
    $q1 = sanitize_text_field($_POST['q1']);
							    $q2 = sanitize_text_field($_POST['q2']);
    $q3 = sanitize_text_field($_POST['q3']);
    $q4 = sanitize_text_field($_POST['q4']);
    $q5 = sanitize_text_field($_POST['q5']);
    $q6 = sanitize_text_field($_POST['q6']);
    $q7 = sanitize_text_field($_POST['q7']);
    $q8 = sanitize_text_field($_POST['q8']);
    $q9 = sanitize_text_field($_POST['q9']);
    $q10 = sanitize_text_field($_POST['q10']);
    $date = sanitize_text_field($_POST['date']);
	$total = $q1 + $q2 + $q3 + $q4 + $q5 + $q6 + $q7 + $q8 + $q9 + $q10;



                        // Insert data into the custom table
                  // Insert data into the custom table
    $table_name = 'ms_survey';
    $wpdb->insert(
        $table_name,
        array(
            'user_id' => $user_id,
            'date' => $date, // Corrected variable name
            'q1' => $q1, // Corrected variable name
						'q2' => $q2, // Corrected variable name
			'q3' => $q3, // Corrected variable name
			'q4' => $q4, // Corrected variable name
			'q5' => $q5, // Corrected variable name
			'q6' => $q6, // Corrected variable name
			'q7' => $q7, // Corrected variable name
			'q8' => $q8, // Corrected variable name
			'q9' => $q9, // Corrected variable name
			'q10' => $q10, // Corrected variable name
			'total' => $total, // Corrected variable name


        ),
        array('%d', '%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')
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
