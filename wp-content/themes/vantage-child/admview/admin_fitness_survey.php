<?php
/*
Template Name: Fitness Survey- Admin
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
  
    #white {
    background-color: white;
  }
	
	

  #Excellent, #Dreadful {
    width: 10%; /* Adjust width as needed */
  }
	
	#Dreadful {
		background-color: red;
	}
				
        #TEST {
            border: 1px solid black;
            background-color: red;
            text-align: center; /* Center align text */
            padding: 4px; /* Add padding for spacing */
            width: 5%;
        }
				
		#TEST2 {
            border: 1px solid black;
            background-color: red;
            text-align: center; /* Center align text */
            padding: 4px; /* Add padding for spacing */
            width: 100%;
        }
				
				
					  #q5g{
						  color:green;
					  }
					  
					    #q1r{
						  color:red;
					  }
					  
					   #q3y{
						  color:orange;
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
                    <h2> Elite Fitness Survey</h2>
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

                        <div style="height: 0px;"></div> <!-- Add a gap -->
                        <div class="form-group">
                            <label for="date">Attendance Date:</label>
                            <input type="date" name="date" id="date" required>
                        </div>
						
                       

                        <div style="height: 30px;"></div> <!-- Add a gap -->
						<div class="form-group">
                    <label for="q1"><b>Body Composition</b><br>How close are you to your ideal weight
    <table>
        <tr>
            <td id="white">Kg over Ideal Wt</th>
            <td id="TEST" colspan="1">>25</th>
            <td id="TEST">
                <25</th>
        <td id="YELLOW"><20</th>
        <td id="YELLOW"><15</th>
        <td id="YELLOW"><10</th>
        <td id=""><7</th>
        <td id=""><4</th>
        <td id=""><2</th>
        </tr>
        <tr>
            <td id="white">% fat man</th>
            <td id="RED" colspan="2">26</th>
            <td id="YELLOW">
                <26</th>
        <td id="YELLOW"><34</th>
        <td id="YELLOW"><22</th>
        <td id=""><20</th>
        <td id=""><18</th>
        <td id=""><16</th>
        </tr>

        <tr>
            <td id="white">% fat woman</th>
            <td id="RED" colspan="2">>34</th>
            <td id="YELLOW">
                <34</th>
        <td id="YELLOW"><32</th>
        <td id="YELLOW"><30</th>
        <td id=""><28</th>
        <td id=""><26</th>
        <td id=""><24</th>
        </tr>
        <tr>
            <td id="white">Score</th>
            <td id="RED" colspan="2">0</th>
            <td id="YELLOW">5</th>
            <td id="YELLOW">6</th>
            <td id="YELLOW">7</th>
            <td id="">8</th>
            <td id="">9</th>
            <td id="">10</th>
        </tr>
</table>

							
					
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
                    <label for="q2"><b>Front of Body Strength</b><br> Situps - consecutive number of sit-ups until exhaustion - feet held, hands clasping opposite shoulders, coming up so elbows touch the
knees, upper back (not head) 'hitting' the ground:</label>
									<table>
			
                      <tr>
                      	<th id="white"></th>
						<th id="RED"> <35</th>
						<th id="YELLOW">35</th>
						<th id="YELLOW">39</th>
						<th id="YELLOW">41</th>
						<th id="">44</th>
						<th id="">47</th>
						<th id="">50</th>
                      
                      </tr>
               
                      
                      		  <tr>
						<th id="white">Score</th>
						<th id="white">0</th>
						<th id="white">5</th>
						<th id="white">6</th>
						<th id="white">7</th>
						<th id="white">8</th>
						<th id="white">9</th>
						<th id="white">10</th>
					  </tr>
					</table>
                    <select name="q2" id="q2">
                                 <option value="0" id="q1r">0</option>
                        <option value="5" id="q3y">5</option>
                        <option value="6" id="q3y" >6</option>
                        <option value="7"id="q3y">7</option>
                        <option value="8" id="q5g">8</option>
                        <option value="9" id="q5g">9</option>
                        <option value="10" id="q5g">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q3"><b>Lower Body Strength</b><br> Squats - consecutive number of squats until exhaustion. Bottom must go down to mid-way between knees and ankles. Most people will
need to use a heel raise to successfully complete the test:</label>
														<table>
			
                      <tr>
                      	<th id="white"></th>
						<th id="RED"> <35</th>
						<th id="YELLOW">35</th>
						<th id="YELLOW">38</th>
						<th id="YELLOW">41</th>
						<th id="">44</th>
						<th id="">47</th>
						<th id="">50</th>
                      
                      </tr>
               
                      
                      		  <tr>
						<th id="white">Score</th>
						<th id="white">0</th>
						<th id="white">5</th>
						<th id="white">6</th>
						<th id="white">7</th>
						<th id="white">8</th>
						<th id="white">9</th>
						<th id="white">10</th>
					  </tr>
					</table>
					<select name="q3" id="q3">
                        <option value="0" id="q1r">0</option>
                        <option value="5" id="q3y">5</option>
                        <option value="6" id="q3y" >6</option>
                        <option value="7"id="q3y">7</option>
                        <option value="8" id="q5g">8</option>
                        <option value="9" id="q5g">9</option>
                        <option value="10" id="q5g">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q4"><b>Upper Body Strength</b><br> Pressups</label>
								<table>
			
                      <tr>
                      	<th id="white"></th>
						<th id="RED"> <35</th>
						<th id="YELLOW">35</th>
						<th id="YELLOW">38</th>
						<th id="YELLOW">41</th>
						<th id="">44</th>
						<th id="">47</th>
						<th id="">50</th>
                      
                      </tr>
               
                      
                      		  <tr>
						<th id="white">Score</th>
						<th id="white">0</th>
						<th id="white">5</th>
						<th id="white">6</th>
						<th id="white">7</th>
						<th id="white">8</th>
						<th id="white">9</th>
						<th id="white">10</th>
					  </tr>
					</table>
                    <select name="q4" id="q4">
              <option value="0" id="q1r">0</option>
                        <option value="5" id="q3y">5</option>
                        <option value="6" id="q3y" >6</option>
                        <option value="7"id="q3y">7</option>
                        <option value="8" id="q5g">8</option>
                        <option value="9" id="q5g">9</option>
                        <option value="10" id="q5g">10</option>
                    </select>
                </div>

                <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q5"><strong>Hamstring Flexibility</strong><br>
                    Sit and reach Sitting on the floor with feet outstretched in front of you, see how far down toward, or past your toes you can reach with your fingers. Keep your legs straight, back of knees on the floor</label>
												<table>
			
                      <tr>
                      	<th id="white"></th>
						<th id="RED"> Can't Touch</th>
						<th id="YELLOW">Fingers</th>
						<th id="YELLOW"></th>
						<th id="YELLOW"></th>
						<th id="YELLOW">Palm</th>
						<th id=""></th>
						<th id=""></th>
						 <th id="">Wrist</th>

                      
                      </tr>
               
                      
                      		  <tr>
						<th id="white">Score</th>
						<th id="white">0</th>
						<th id="white">4</th>
						<th id="white">5</th>
						<th id="white">6</th>
						<th id="white">7</th>
						<th id="white">8</th>
						<th id="white">9</th>
						<th id="white">10</th>
					  </tr>
					</table>
                    <select name="q5" id="q5">
                        <option value="0">0</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                        <option value="7" id="q3y">7</option>
                        <option value="8">8</option>
                        <option value="9">9</option>
                        <option value="10" >10</option>
                    </select>
                </div>
                                                        <div style="height: 30px;"></div> <!-- Add a gap -->
 <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q6"><strong>Buttocks Flexibility - Right Leg Under</strong><br>
                    Ability to sit up straight, legs crossed, hands behind back. </label>
					<table>
                      <tr>
                      	<th id="white"></th>
						<th id="RED"> Big Fail</th>
						<th id="RED">Nope</th>
						<th id="YELLOW">Almost</th>
						<th id="YELLOW">Just</th>
						<th id="">Good</th>
						  <th id="">Perfect</th>
                      </tr>
               
                      
                      		  <tr>
						<th id="white">Score</th>
						<th id="white">0</th>
						<th id="white">1</th>
						<th id="white">2</th>
						<th id="white">3</th>
						<th id="white">4</th>
						<th id="white">5</th>

					  </tr>
					</table>
                    <select name="q6" id="q6">
                        <option value="0" id="q1r">0</option>
                        <option value="1"id="q1r">1</option>
                        <option value="2" id="q3y">2</option>
                        <option value="3" id="q3y">3</option>
                        <option value="4" id="q5g">4</option>
                        <option value="5" id="q5g">5</option>

                    </select>
                </div>
						
						
			 <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q7"><strong> Buttocks Flexibility - Left Leg Under</strong><br>
                    Ability to sit up straight, legs crossed, hands behind back. 
</label>
												<table>
			
                      <tr>
                      	<th id="white"></th>
						<th id="RED"> Big Fail</th>
						<th id="RED">Nope</th>
						<th id="YELLOW">Almost</th>
						<th id="YELLOW">Just</th>
						<th id="">Good</th>
						  <th id="">Perfect</th>



       
                      </tr>
               
                      
                      		  <tr>
						<th id="white">Score</th>
						<th id="white">0</th>
						<th id="white">1</th>
						<th id="white">2</th>
						<th id="white">3</th>
						<th id="white">4</th>
						<th id="white">5</th>

					  </tr>
					</table>
                    <select name="q7" id="q7">
                        <option value="0" id="q1r">0</option>
                        <option value="1"id="q1r">1</option>
                        <option value="2" id="q3y">2</option>
                        <option value="3" id="q3y">3</option>
                        <option value="4" id="q5g">4</option>
                        <option value="5" id="q5g">5</option>

                    </select>
                </div>			
						
		
							 <div style="height: 30px;"></div> <!-- Add a gap -->
                <div class="form-group">
                    <label for="q8"><strong> Shoulder Function</strong> <br> Stand with heels and back against the wall, arms and wrists vertical in the ‘surrender’ position. The further they are away, from the wall (in cms) the lower the score.</label>
			<table>
			
                      <tr>
                      	<th id="white"></th>
						<th id="RED">>15</th>
						<th id="RED">15</th>
						<th id="RED">13</th>
						<th id="RED">11</th>
						<th id="YELLOW">9</th>
						<th id="YELLOW">7</th>
						<th id="YELLOW">5</th>
						<th>3</th>
						<th>2</th>
						<th>1</th>
						<th>Flat</th>
                      
                      </tr>
               
                      
                      		  <tr>
						<th id="white">Score</th>
						<th id="white">0</th>
						<th id="white">1</th>
						<th id="white">2</th>
						<th id="white">3</th>
						<th id="white">4</th>
						<th id="white">5</th>
						<th id="white">6</th>
						<th id="white">7</th>
						<th id="white">8</th>
						<th id="white">9</th>
						<th id="white">10</th>
					  </tr>
					</table>
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
                    <label for="q9">Aerobic fitness - 5 minute, 20m lap run.
			  		<table>
								  <tr>
						<th id="white">M</th>
						<th id="RED"><22</th>
						<th id="RED">22</th>
						<th id="RED">24</th>
						<th id="RED">26</th>
						<th id="YELLOW">28</th>
						<th id="YELLOW">32</th>
						<th id="YELLOW">33</th>
						<th>34</th>
						<th>38</th>
						<th>38</th>
						<th>40</th>
					  </tr>
								  <tr>
						<th id="white">F</th>
						<th id="RED"><20</th>
						<th id="RED">20</th>
						<th id="RED">22</th>
						<th id="RED">24</th>
						<th id="YELLOW">26</th>
						<th id="YELLOW">28</th>
						<th id="YELLOW">30</th>
						<th>32</th>
						<th>34</th>
						<th>36</th>
						<th>38</th>
					  </tr>
					  <tr>
						<th id="white">Poor</th>
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
						<th id="Excellent">Good</th>
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
	$q9 = $q9 *3;
   // $q10 = sanitize_text_field($_POST['q10']);
   // $q11 = sanitize_text_field($_POST['q12']);
    //$q12 = sanitize_text_field($_POST['q12']);
    $date = sanitize_text_field($_POST['date']);
	$total = $q1 + $q2 + $q3 + $q4 + $q5 + $q6 + $q7 + $q8 + $q9;


                        // Insert data into the custom table
                  // Insert data into the custom table
    $table_name = 'fitness_survey';
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
			//'q10' => $q10, // Corrected variable name
			//'q11' => $q11, // Corrected variable name
			//'q12' => $q12, // Corrected variable name

			'total' => $total, // Corrected variable name


        ),
        array('%d', '%s', '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')
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
