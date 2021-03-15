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
		add_shortcode( 'clockmd_show_hospitals', array($this, 'shortcode_callback') );
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
		// Enqueuing thickbox style for admin pop up
		wp_register_script( "clockmd-script-js",
			plugin_dir_url( __FILE__ ) . '/script.js',
			array( 'jquery' ), 
			time(), 
			false 
		);

		wp_enqueue_script( "clockmd-script-js" );

		wp_enqueue_style('bootstrap4', 'https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css');
		wp_enqueue_script( 'boot1','https://code.jquery.com/jquery-3.3.1.slim.min.js', array( 'jquery' ),'',true );
		wp_enqueue_script( 'boot2','https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js', array( 'jquery' ),'',true );
		wp_enqueue_script( 'boot3','https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js', array( 'jquery' ),'',true );
	}	

	function init_action_callbacks() {
		if ( !empty($_POST) && !empty($_POST['appointment']) && is_array($_POST['appointment']) && count($_POST['appointment']) > 0 ) {
			
			// Getting the formdata
			$createAppointmentData = $_POST['appointment'];
			
			// Converting the date format into the api desired format
			$createAppointmentData['dob'] = date( "m/d/Y", strtotime($createAppointmentData['dob']) );
			
			// Calling the create appointment API
			$createAppointmentRes = $this->post_with_authorization('/appointments/create', $createAppointmentData);

			if ( is_array($createAppointmentRes) && count($createAppointmentRes) > 0 ) {
				$msg = "Appointment confirmed your";
				$msg .= " confirmation code is ".$createAppointmentRes['confirmation_code'];
				$msg .= " and appointment queue id is ".$createAppointmentRes['appointment_queue_id'];
				wp_redirect("?action=appointmentcreated&msg=$msg");
				exit;
			}
		}
	}

	function shortcode_callback( $atts ) {
		$atts = shortcode_atts( array(
	        'foo' => 'no foo'
	    ), $atts );
	    return $this->get_hospitals();
	}

	function get_hospitals() {
		$str = "<div class='container'>";
		$str .= "<div class='row'>";

		// If appointmentcreated and msg is set then show notification bar
		if ( !empty($_GET['action']) && $_GET['action'] == 'appointmentcreated' && !empty($_GET['msg']) ) {
			$str .= '<div class="alert alert-success" role="alert">'.$_GET['msg'].'</div>';
		}

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

			// #3. Generating the reasons dropdown			
			$select = '<select required onchange="changeReason(this)" class="form-control form-control-lg reason_description" name="appointment[reason_description]">';
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

			// #4. Getting the timeslots html			
			$timeSlots = $this->get_hospital_available_times( $hospitalId, $reasonDescription );

			$str .= "<div class='col-md-12'>
					<center>
						<h4>".$hospital['name']."</h4>
						<div>".$hospital['full_address']."</div>
						<div>".$hospital['phone_number']."</div>
						<p><div>Please Note: you can also try one of our other nearby clinics</div></p>
						<p><div>Please complete the information below to hold a place in line!</div>
						<div>Note that all times are estimates only.</div>
						<div>But first, please make sure you don't <a href='https://www.911.gov/needtocallortext911.html' target='_blank'>need to call 911</a></div></p>
						<p><b>Select a visit reason:</b></p>
					</center>
				</div>";

			$str .= $this->getAppointmentForm( $select, $timeSlots );
		}
		else {			
			$hospitals = $this->get_with_authorization( '/hospitals?details=true' );
			foreach ($hospitals as $i => $hospital) {
				$str .= "<div class='col-md-4'>
					<a href='?action=createappointment&hospital=".$hospital['id']."'><h4>".$hospital['name']."</h4></a>
					<div>".$hospital['full_address']."</div>
					<div>".$hospital['phone_number']."</div>
				</div>";			
			}
		}		
		$str .= "</div>";
		$str .= "</div>";

		return $str;
	}

	function getAppointmentForm( $dropDown, $timeslots ) {
		// date_default_timezone_set("Asia/Bangkok");
		return '<div class="col-md-12">
			<form action="" method="post" onsubmitt="return getAppointmentData(this)">
				<div class="form-group row">
				    <div class="col-md-12">'.$dropDown.'</div>
			  	</div>
				<div class="form-group row">
				    <div class="col-md-6">
				      <input required type="text" name="appointment[first_name]" class="form-control" placeholder="First name">
				    </div>
				    <div class="col-md-6">
				      <input required type="text" name="appointment[last_name]" class="form-control" placeholder="Last name">
				    </div>
			  	</div>
			  	'.$timeslots.'			  	
			  	<div class="form-group row">
				    <div class="col-md-6">
				      <input required type="email" name="appointment[email]" class="form-control" placeholder="Email">
				    </div>
				    <div class="col-md-6">
				      <input type="tel" name="appointment[phone_number]" class="form-control" placeholder="111-222-3333" pattern="[0-9]{3}-[0-9]{3}-[0-9]{4}">
				    </div>
			  	</div>
			  	<div class="form-group row">
				    <div class="col-md-6">
				      	<select required class="form-control form-control-lg" name="appointment[is_new_patient]">
							<option value="">Patient Type</option>
							<option value="true">New Patient</option>
							<option value="false">Existing Patient</option>
						</select>
				    </div>
				    <div class="col-md-6">
				      <input required type="date" name="appointment[dob]" class="form-control" placeholder="Date of Birth">
				    </div>
			  	</div>
			  	<div class="form-group row">
				    <div class="col-md-12">
				        <button class="btn btn-primary" type="submit">Submit form</button>
				    </div>
			  	</div>
			</form>
		</div>';
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
		    	<select required onchange="updateOption(this)" class="form-control form-control-lg" name="appointment[days_from_today]">
					<option data-targetOptionGroup="'.$todayTimeStamp.'" value="0">Today - '.$todayDate.'</option>
					<option data-targetOptionGroup="'.$nextDayTimeStamp.'" value="1">Tomorrow - '.$nextDate.'</option>
				</select>
		    </div>
		    <div class="col-md-6">
		    	<select required class="form-control form-control-lg apt_time" name="appointment[apt_time]">
		    		<option value="">-- Select --</option>
		    		'.$timeOptions.'
		    	</select>
		    </div>
	  	</div>';

	  	return $timeSlotsHtml;
	}
}

Clockmd::get_instance();