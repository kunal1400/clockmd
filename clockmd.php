<?php
/*
Plugin Name: Clockmd
Plugin URI: https://github.com/kunal1400?tab=repositories
Description: Clockmd apis calling
Version: 1.0.0
Author: Kunal Malviya
*/

include 'env.php';

/**
 * 
 */
class Clockmd {

	private $authtoken = AUTH_TOKEN;
	private $apiurl = API_URL;
	private $clockwiselogo = 'https://s3.amazonaws.com/urgentq_production/uploads/hospital/logo/2681/Community_Family_Urgent_Care_Logo_-_Large-01.png';

	/** Refers to a single instance of this class. */
    private static $instance = null;

    /**
     * Creates or returns an instance of this class.
     *
     * @return  WooRevo_pro_AttInfo A single instance of this class.
     */
    public static function get_instance() {
        if ( null == self::$instance ) {
        	self::$instance = new self;
        }
        return self::$instance;  
    }

	function __construct() {
		add_action( 'init', array($this, 'init_action_callbacks') );
		add_action( 'wp_enqueue_scripts', array($this,'frontend_scripts') );
		add_action( 'admin_menu', array($this,'clockmd_menu_pages') );
		add_shortcode( 'clockmd_show_hospitals', array($this, 'clockmd_show_hospitals_cb') );
		add_shortcode( 'clockmd_show_hospitals_with_map', array($this, 'clockmd_show_hospitals_with_map_cb') );
	}

	function get_with_authorization( $endpoint ) {
	    $ch = curl_init( $this->apiurl."/".$endpoint );
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        "authtoken: ".$this->authtoken,
	        "Content-Type: application/json"
	    ));
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_HEADER, true);
	    $response = curl_exec($ch);

	    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	    $header      = substr($response, 0, $header_size);
	    $body        = substr($response, $header_size);
	    return json_decode($body, true);
	}

	function post_with_authorization( $endpoint, $data ) {
	    $ch = curl_init( $this->apiurl."/".$endpoint );
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	        "authtoken: ".$this->authtoken,
	        "Content-Type: application/json"
	    ));
    	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));	    
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($ch, CURLOPT_HEADER, true);
	    $response = curl_exec($ch);

	    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	    $header      = substr($response, 0, $header_size);
	    $body        = substr($response, $header_size);
	    return json_decode($body, true);
	}

	function frontend_scripts() {

		wp_register_script( "input.mask.js",
			plugin_dir_url( __FILE__ ) . '/input.mask.js',
			array( 'jquery' ), 
			time(), 
			false 
		);

		wp_register_script( "clockmd-script-js",
			plugin_dir_url( __FILE__ ) . '/script.js',
			array( 'jquery', 'input.mask.js' ), 
			time(), 
			false 
		);		

		wp_enqueue_style('bootstrap4', 
			plugin_dir_url( __FILE__ ) . '/bootstrap.min.css',
			array(), 
			time(), 
			false 
		);

		/**********************************************************
		* Scripts for Hospital List with Google Map
		*********************************************************
		wp_enqueue_script( 'mustache', 
			'//s3-us-west-1.amazonaws.com/clockwisepublic/mustache.js', 
			array( 'jquery' ), 
			'', 
			false 
		);

		wp_enqueue_script( 'pubnub', 
			'//cdn.pubnub.com/pubnub.min.js', 
			array( 'jquery' ), 
			'', 
			false 
		);

		wp_enqueue_script( 'googlemap', 
			'//maps.googleapis.com/maps/api/js', 
			array( 'jquery' ), 
			'', 
			false 
		);

		wp_enqueue_script( 'geoposition', 
			'//s3-us-west-1.amazonaws.com/clockwisepublic/geoposition.js', 
			array( 'jquery' ), 
			'', 
			false 
		);

		wp_enqueue_script( 'infobox', 
			'//s3-us-west-1.amazonaws.com/clockwisepublic/infobox.js', 
			array( 'jquery' ), 
			'', 
			false 
		);

		wp_enqueue_script( 'groups', 
			'//www.clockwisemd.com/groups/'.GROUP_ID.'.js', 
			array( 'jquery' ), 
			'', 
			true 
		);

		wp_enqueue_style( 'clockwise', 
			'//s3-us-west-1.amazonaws.com/clockwisepublic/clockwise_map.css', 
			array( 'jquery' ), 
			'', 
			false 
		);

		wp_enqueue_script( "clockmd-script-js" );
		/**********************************************************
		* /END: Scripts for Hospital List with Google Map
		**********************************************************/		
	}	

	public function populateDb($storename, $latitude, $longitude, $full_address, $phone_number, $externalUrl) {
		global $table_prefix, $wpdb;
		// $time_taken = time();
		$tblname = $table_prefix . 'ssf_wp_stores';
		
		$sql = "SELECT * FROM $tblname WHERE ssf_wp_store='$storename'";
		$row = $wpdb->get_row($sql, ARRAY_A);

		$city = "";
		$state = "";
		$county = "";
		$zip = "";		
		if (!empty($full_address)) {
			$addressArray = explode(",", $full_address);
			$stateAndZip = explode(" ", $addressArray[3]);

			// Setting the params
			$city 	= $addressArray[2];
			$state 	= $stateAndZip[1];
			$county = end($addressArray);
			$zip 	= $stateAndZip[2];
		}		

		if ( is_array($row) && count($row) > 0 ) {
			// We don't need update now
	    	$sql = "UPDATE $tblname SET ssf_wp_store='$storename', ssf_wp_address='$full_address', ssf_wp_city='$city', ssf_wp_state='$state', ssf_wp_country='$county', ssf_wp_zip='$zip', ssf_wp_latitude='$latitude', ssf_wp_longitude='$longitude', ssf_wp_phone='$phone_number', ssf_wp_ext_url='$externalUrl', ssf_wp_is_published='1', ssf_wp_tags='Clinic&#44;' WHERE ssf_wp_store='$storename'";
	    	return $wpdb->query($sql);
		} 
		else {
			/**
			* Generating the query to insert game with all its levels
			**/
			$sql = "INSERT INTO $tblname (ssf_wp_store, ssf_wp_address, ssf_wp_city, ssf_wp_state, ssf_wp_country, ssf_wp_zip, ssf_wp_latitude, ssf_wp_longitude, ssf_wp_phone, ssf_wp_ext_url, ssf_wp_is_published, ssf_wp_tags) VALUES ('$storename','$full_address','$city','$state','$county','$zip','$latitude','$longitude', '$phone_number', '$externalUrl', '1', 'Clinic&#44;')";
			return $wpdb->query($sql);
		}

	}

	function init_action_callbacks() {
		if ( !empty($_GET['page']) && !empty($_GET['importclockmd']) && $_GET['importclockmd'] == 1 && $_GET['page'] == "clockmd__settings" ) {
			$hospitals = $this->get_with_authorization( '/hospitals?details=true' );
			if (is_array($hospitals) && count($hospitals) > 0) {				
				$dbResponse = array();
				foreach ($hospitals as $i => $hospital) {
					$extUrl = site_url()."/online-appointment-form?hospital=".$hospital['id'];
					$dbResponse[] = $this->populateDb($hospital['name'], $hospital['latitude'], $hospital['longitude'], $hospital['full_address'], $hospital['phone_number'], $extUrl);					
				}				
			}			
		}

		if ( !empty($_POST) && !empty($_POST['appointment']) && is_array($_POST['appointment']) && count($_POST['appointment']) > 0 ) {
			
			// Getting the formdata
			$createAppointmentData = $_POST['appointment'];
			
			// Converting the date format into the api desired format
			$createAppointmentData['dob'] = date( "m/d/Y", strtotime($createAppointmentData['dob']) );
			
			// Calling the create appointment API
			$createAppointmentRes = $this->post_with_authorization('/appointments/create', $createAppointmentData);
			
			if ( is_array($createAppointmentRes) && count($createAppointmentRes) > 0 ) {
				if ( isset($createAppointmentRes['error']) ) {
					wp_redirect( "?action=appointment_error&errmsg=".$createAppointmentRes['error']."&hospital=".$createAppointmentData['hospital_id'] );
				}
				else {
					date_default_timezone_set('America/Chicago');
					// Appointment confirmed for this clinic name at this date at this time
					$msg = "Appointment confirmed for ".$createAppointmentData['hospital_name'];
					$msg .= " at ".date( "m/d/Y H:i:s A", strtotime($createAppointmentData['apt_time']) );
					// $msg .= " and your confirmation code is ".$createAppointmentRes['confirmation_code'];
					// $msg .= " and appointment queue id is ".$createAppointmentRes['appointment_queue_id'];
					wp_redirect( "?action=appointment_created&msg=$msg&hospital=".$createAppointmentData['hospital_id'] );
				}				
				exit;
			}
		}
	}

	function clockmd_show_hospitals_cb( $atts ) {
		$atts = shortcode_atts( array(
	        'foo' => 'no foo'
	    ), $atts );
	    return $this->get_hospitals();
	}

	/*function get_hospitals() {
		$str = "<div class='container'>";
		$str .= "<div class='row'>";

		if ( !empty($_GET['action']) && $_GET['action'] == 'createappointment' && !empty($_GET['hospital']) ) {
			$hospitalId = $_GET['hospital'];
			$reasonId = 0;
			if ( !empty($_GET['reasonId']) ) {
				$reasonId = $_GET['reasonId'];
			}

			// #1. Getting the Hospital Informations
			$hospital = $this->get_with_authorization( '/hospitals/'.$hospitalId );			
			
			// #2. Getting the reasons Informations
			$reasonDescription = "";
			$reasons = $this->get_with_authorization( '/reasons?hospital_id='.$hospitalId );
			
			if ( !isset($reasons['error']) ) {
				// #3. Generating the reasons dropdown
				$select = '<select required onchange="changeReason(this)" class="form-control form-control-lg reason_description" name="appointment[reason_id]">';
				if ( !empty($reasons['reasons']) && is_array($reasons['reasons']) ) {
					foreach ($reasons['reasons'] as $i => $reason) {
						if ($i == 0) {
							$reasonDescription = $reason['description'];
							$select .= '<option selected value="'.$reason['id'].'">'.$reason['description'].'</option>';
						}
						else if ( $reasonId == $reason['id'] ) {
							$reasonDescription = $reason['description'];
							$select .= '<option selected value="'.$reason['id'].'">'.$reason['description'].'</option>';
						} 
						else {
							$select .= '<option value="'.$reason['id'].'">'.$reason['description'].'</option>';
						}
					}
				}
				$select .= '</select>';
				$select .= '<input type="hidden" name="appointment[hospital_id]" value="'.$hospitalId.'" />';
				$select .= '<input type="hidden" name="appointment[hospital_name]" value="'.$hospital['name'].'" />';

				// #4. Getting the timeslots html			
				$timeSlots = $this->get_hospital_available_times( $hospitalId, $reasonDescription );

				$str .= "<div class='col-md-12'>
						<img src='".$this->clockwiselogo."'>
						<center>
							<h4>".$hospital['name']."</h4>
							<div>".$hospital['full_address']."</div>
							<div>".$hospital['phone_number']."</div>
							<p><div>Please Note: you can also try one of our other nearby clinics</div></p>
							<p><div>Please complete the information below to hold a place in line!</div>
							<div>Note that all times are estimates only.</div>
							<div>But first, please make sure you don't <a href='https://www.911.gov/needtocallortext911.html' target='_blank'>need to call 911</a></div></p>
						</center>
					</div>";

				// If appointmentcreated and msg is set then show notification bar
				if ( !empty($_GET['msg']) ) {
					$str .= '<div class="col-md-12"><div class="alert alert-success" role="alert">'.$_GET['msg'].'</div></div>';
				}

				// If appointmenterror and msg is set then show notification bar
				if ( !empty($_GET['errmsg']) ) {
					$str .= '<div class="col-md-12"><div class="alert alert-danger" role="alert">'.$_GET['errmsg'].'</div></div>';
				}

				$str .= $this->getAppointmentForm( $select, $timeSlots );	
			}
			else {
				$str .= '<div class="col-md-12"><center>There are no times available for online scheduling right now. During normal office hours, just come in to the facility we will see you as a regular walk in.Note: This message will be shown when there are no online visit times available (the clinic is full past their configured time for online patients OR the clinic is closed).</center></div>';
			}			
		}
		else {
			$hospitals = $this->get_with_authorization( '/hospitals?details=true' );
			foreach ($hospitals as $i => $hospital) {
				$str .= "<div class='col-md-4'>
					<div class='card'>
						<img src='".$this->clockwiselogo."'>
						<div class='card-body'>
							<h5 class='card-title'><a href='?action=createappointment&hospital=".$hospital['id']."'>".$hospital['name']."</a></h5>
							<p class='card-text align-center'>".$hospital['full_address']."</p>
							<p class='card-text align-center'>".$hospital['phone_number']."</p>
							<a href='?action=createappointment&hospital=".$hospital['id']."' class='btn btn-primary'>Checkin Online</a>
						</div>
					</div>
				</div>";
			}
		}

		$str .= "</div>";
		$str .= "</div>";

		return $str;
	}*/

	function get_hospitals() {
		$str = "<div class='container'>";
		$str .= "<div class='row'>";

		if ( !empty($_GET['hospital']) ) {
			$hospitalId = $_GET['hospital'];
			$reasonId = 0;
			if ( !empty($_GET['reasonId']) ) {
				$reasonId = $_GET['reasonId'];
			}

			// If appointmentcreated and msg is set then show notification bar
			if ( !empty($_GET['action']) && !empty($_GET['msg']) && $_GET['action'] == "appointment_created" ) {
				$str .= '<div class="col-md-12"><div class="alert alert-success" role="alert">'.$_GET['msg'].'</div></div>';
				$str .= '<div class="col-md-12"><a class="btn btn-primary" href=" https://forms.communitymedcare.com" target="_blank">Fill Out your Registration Forms</a></div>';
			}
			else {
				// #1. Getting the Hospital Informations
				$hospital = $this->get_with_authorization( '/hospitals/'.$hospitalId );			
				
				// #2. Getting the reasons Informations
				$reasonDescription = "";
				$reasons = $this->get_with_authorization( '/reasons?hospital_id='.$hospitalId );
				
				if ( !isset($reasons['error']) ) {
					// #3. Generating the reasons dropdown
					$select = '<select required onchange="changeReason(this)" class="form-control form-control-lg reason_description" name="appointment[reason_id]">';
					if ( !empty($reasons['reasons']) && is_array($reasons['reasons']) ) {
						foreach ($reasons['reasons'] as $i => $reason) {
							if ($i == 0) {
								$reasonDescription = $reason['description'];
								$select .= '<option selected value="'.$reason['id'].'">'.$reason['description'].'</option>';
							}
							else if ( $reasonId == $reason['id'] ) {
								$reasonDescription = $reason['description'];
								$select .= '<option selected value="'.$reason['id'].'">'.$reason['description'].'</option>';
							} 
							else {
								$select .= '<option value="'.$reason['id'].'">'.$reason['description'].'</option>';
							}
						}
					}
					$select .= '</select>';
					$select .= '<input type="hidden" name="appointment[hospital_id]" value="'.$hospitalId.'" />';
					$select .= '<input type="hidden" name="appointment[hospital_name]" value="'.$hospital['name'].'" />';

					// #4. Getting the timeslots html			
					$timeSlots = $this->get_hospital_available_times( $hospitalId, $reasonDescription );

					$str .= "<div class='col-md-12'>
							<img src='".$this->clockwiselogo."'>
							<center>
								<h4>".$hospital['name']."</h4>
								<div>".$hospital['full_address']."</div>
								<div>".$hospital['phone_number']."</div>
								<p><div>Please Note: you can also try one of our other nearby clinics</div></p>
								<p><div>Please complete the information below to hold a place in line!</div>
								<div>Note that all times are estimates only.</div>
								<div>But first, please make sure you don't <a href='https://www.911.gov/needtocallortext911.html' target='_blank'>need to call 911</a></div></p>
							</center>
						</div>";				

					// If appointmenterror and msg is set then show notification bar
					if ( !empty($_GET['errmsg']) ) {
						$str .= '<div class="col-md-12"><div class="alert alert-danger" role="alert">'.$_GET['errmsg'].'</div></div>';
					}
					$str .= $this->getAppointmentForm( $select, $timeSlots );	
				}
				else {
					$str .= '<div class="col-md-12"><center>There are no times available for online scheduling right now. During normal office hours, just come in to the facility we will see you as a regular walk in.Note: This message will be shown when there are no online visit times available (the clinic is full past their configured time for online patients OR the clinic is closed).</center></div>';
				}
			}
		}
		else {
			$str .= "<div class='col-md-12'>No Hospital Id</div>";
		}
		$str .= "</div>";
		$str .= "</div>";
		return $str;
	}

	function getAppointmentForm( $dropDown, $timeslots ) {
		// date_default_timezone_set("Asia/Bangkok");
		return '<div id="AppointmentFormWrapper" class="col-md-12">
			<form action="" method="post" onsubmitt="return getAppointmentData(this)">
				<div class="form-group row">
				    <div class="col-md-12">
				    <label class="form-check-label"><b>Select a visit reason:</b></label>
				    	'.$dropDown.'
				    </div>
			  	</div>
				<div class="form-group row">
				    <div class="col-md-6">
				    	<label class="form-check-label">First Name</label>
				      	<input required type="text" name="appointment[first_name]" class="form-control" placeholder="First name">
				    </div>
				    <div class="col-md-6">
				    	<label class="form-check-label">Last Name</label>
				      	<input required type="text" name="appointment[last_name]" class="form-control" placeholder="Last name">
				    </div>
			  	</div>
			  	'.$timeslots.'			  	
			  	<div class="form-group row">
				    <div class="col-md-6">
				    	<label class="form-check-label">Email</label>
				      	<input required type="email" name="appointment[email]" class="form-control" placeholder="Email">
				    </div>
				    <div class="col-md-6">
				    	<label class="form-check-label">Phone Number</label>
				      	<input type="tel" name="appointment[phone_number]" class="form-control">
				    </div>
			  	</div>
			  	<div class="form-group row">
				    <div class="col-md-6">
				    	<label class="form-check-label">Patient Type</label>
				      	<select required class="form-control form-control-lg" name="appointment[is_new_patient]">
							<option value="">Patient Type</option>
							<option value="true">New Patient</option>
							<option value="false">Existing Patient</option>
						</select>
				    </div>
				    <div class="col-md-6">
				    	<label class="form-check-label">Date of Birth</label>				    	
				      	<input required type="date" name="appointment[dob]" class="form-control">
				    </div>
			  	</div>
			  	<div class="form-group row">
				    <div class="col-md-12">
				        <button class="btn btn-primary" type="submit">Submit form</button>
				    </div>
			  	</div>
			</form>
		</div>
		<script>
		jQuery(document).ready(function(){
		  var phones = [{ "mask": "(###) ###-####"}];
		  jQuery(`input[name="appointment[phone_number]"]`).inputmask({
			mask: phones, 
			greedy: false, 
			definitions: { "#": { validator: "[0-9]", cardinality: 1}} 
			})
		})
		</script>';
	}

	function get_hospital_available_times( $hospitalId, $reason_description ) {
		/**
		* Generating the API Path with Query string
		**/
		$requestPath  = '/hospitals/'.$hospitalId.'/available_times';
		$requestPath .= '?';
		$requestPath .= http_build_query(array(
			'slot_type' => 'online',
			'reason_description' => $reason_description
		));

		/**
		* Calling the generated API
		**/
		$timeSlots = $this->get_with_authorization( $requestPath );

		/**
		* Creating the variables
		**/
		$timeSlotsHtml 		= "";
		$todayDate 			= date("m/d/Y");
		$nextDayTimeStamp 	= strtotime("+1 day");
		$nextDate  			= date("m/d/Y", $nextDayTimeStamp);
		$todayTimeStamp	  	= strtotime(date("m/d/Y"));

		/**
		* Generating all Dropdowns with target Id so that it can be hide/show using jquery
		**/
	  	$timeOptions = "";
		foreach ($timeSlots as $i => $times) {
			foreach ($times as $j => $t) {
				$date = strtotime($t['date']);
				foreach ($t['times'] as $k => $ts) {
					if ( $todayTimeStamp == $date ) {
			  			$timeOptions .= '<option data-target-date="'.$date.'" value="'.$ts['time'].'">'.$ts['display_time'].'</option>';
					} else {
			  			$timeOptions .= '<option style="display:none" data-target-date="'.$date.'" value="'.$ts['time'].'">'.$ts['display_time'].'</option>';
					}
				}
			}			
		}

		$timeSlotsHtml .= '<div class="form-group row">
		    <div class="col-md-6">
				<label class="form-check-label">Appointment Date</label>
		    	<select required onchange="updateOption(this)" class="form-control form-control-lg" name="appointment[days_from_today]">
					<option data-targetOptionGroup="'.$todayTimeStamp.'" value="0">Today - '.$todayDate.'</option>
					<option data-targetOptionGroup="'.$nextDayTimeStamp.'" value="1">Tomorrow - '.$nextDate.'</option>
				</select>
		    </div>
		    <div class="col-md-6">
		    	<label class="form-check-label">Appointment Time</label>
		    	<select required class="form-control form-control-lg apt_time" name="appointment[apt_time]">
		    		<option value="">-- Select --</option>
		    		'.$timeOptions.'
		    	</select>
		    </div>
	  	</div>';

	  	return $timeSlotsHtml;
	}

	function clockmd_show_hospitals_with_map_cb() {
		return 'This shortcode has been disabled';
		// return '<div>
		// 	<div class="groups-map" id="map-panel">
		// 	   <div class="address-box" id="panel">
		// 	      <div class="row-fluid" style="display:flex;flex-direction:row;">
		// 	         <input style="flex-grow:10;height:5%;" id="address" placeholder="Current Address" type="text" value="" />
		// 	         <input style="flex-grow:1;" class="btn btn-primary" id="address-search-btn" onclick="codeAddress()" type="button" value="Search Nearby" />
		// 	         &nbsp;
		// 	         <input style="flex-grow:1;" class="btn btn-primary" id="current-location-btn" onclick="findPosition()" style="display:none;" type="button" value="Use My Location" />
		// 	         &nbsp;
		// 	         <input class="btn btn-danger" id="clear-address-btn" onclick="reDrawMap()" style="display:none;flex-grow:1;" type="button" value="Clear Search" />
		// 	      </div>
		// 	   </div>
		// 	   <div class="row-fluid row-fluid_maps">
		// 	      <div id="map-canvas">
		// 	      </div>
		// 	      <div class="directions-panel" id="directionsPanel" style="float:right;height:65%;display:none">
		// 	      </div>
		// 	      <div class="directions-clinic-info" id="clinicInfo" style="float:right;height:35%;display:none">
		// 	      </div>
		// 	   </div>
		// 	</div>
		// 	<div class="row-fluid row-fluid_maps" id="group-hospital-list"></div>
		// 	<div style="display:none;">
		// 	   <!-- This is a clockwise mustache.js template. For more info goto https://mustache.github.io/ -->
		// 	   <script id="map_full_hospital" type="text/template">
		// 	      <div class="map-window-full" id="hospital-window-{{id}}">
		// 	        <h5 class="opensans"><strong>{{{hospital_name_link}}}</strong></h5>
		// 	        <h5 class="opensans">{{{drive_time}}}</h5>
		// 	        <h5 class="opensans"><strong class="current_wait_placeholder">{{{current_queue_length}}}</strong>&nbsp;in line.</h5>
		// 	        <h5 class="opensans">{{{address_1}}}</h5>
		// 	        <h5 class="opensans">{{{address_2}}}</h5>
		// 	        <h5 class="opensans">{{{city}}}, {{{state}}} {{{zip}}}</h5>
		// 	        <h5 class="opensans">{{{phone_number}}}</h5>{{{schedule_button}}}
		// 	      </div>
		// 	   </script>
		// 	   <script id="map_wait_window" type="text/template">
		// 	      <div class="map-window-wait" id="hospital-window-{{id}}">
		// 	        <h5 class="opensans"><strong>{{{hospital_name_link}}}</strong></h5>
		// 	        <h5 class="opensans">{{{drive_time}}}</h5>
		// 	        <h5 class="opensans"><strong class="current_wait_placeholder">{{{current_queue_length}}}</strong>&nbsp;in line.</h5>
		// 	      </div>
		// 	   </script>
		// 	   <script id="list_full_hospital" type="text/template">
		// 	      <div class="span4 margin-group margin-top text-center" id="list-hospital-{{id}}" style="height:300px">
		// 	        <h4 class="opensans">
		// 	          <a class="map-tooltip" onclick="focusMap({{id}})" target="_blank" title="">
		// 	            <img src="{{icon_url}}" />&nbsp;
		// 	          </a><strong>{{{hospital_name_link}}}</strong>
		// 	        </h4>
		// 	        <h4 class="opensans drive_time_header">{{{drive_time}}}</h4>
		// 	        <h4 class="opensans"><strong class="current_wait_placeholder">{{{current_queue_length}}}</strong>&nbsp;in line.</h4><h4 class="opensans">{{{address_1}}}</h4>
		// 	        <h4 class="opensans">{{{address_2}}}</h4>
		// 	        <h4 class="opensans">{{{city}}}, {{{state}}} {{{zip}}}</h4>
		// 	        <h4 class="opensans">{{{phone_number}}}</h4>{{{schedule_button}}}
		// 	      </div>
		// 	   </script>
		// 	   <!-- This is the end of the mustache.js template -->
		// 	</div>
		// </div>';
	}

	function clockmd_menu_pages() {
		$menu_name 	= 'ClockMD Settings';
		$menu_slug 	= 'clockmd__settings';
		$menu_main 	= 'ClockMD Settings';

		add_menu_page($menu_name, $menu_main, 'manage_options', $menu_slug, '', plugin_dir_url(__FILE__).'assets/img/logo-wp.png', 57);

		add_submenu_page( $menu_slug, $menu_name, $menu_main, 'manage_options', $menu_slug, array($this, 'clockmd_settings_page'));		
	}

	function clockmd_settings_page() {
		include_once 'Pages/Admin/settings.php';
	}

}

Clockmd::get_instance();