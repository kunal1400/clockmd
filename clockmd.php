<?php
/*
Plugin Name: Clockmd
Plugin URI: https://github.com/kunal1400?tab=repositories
Description: Clockmd apis calling
Version: 1.0.0
Author: Kunal Malviya
*/

include 'env.php';

class Clockmd
{

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
  public static function get_instance()
  {
    if (null == self::$instance) {
      self::$instance = new self;
    }
    return self::$instance;
  }

  function __construct()
  {
    add_action('init', array(
      $this,
      'init_action_callbacks'
    ));
    add_action('wp_enqueue_scripts', array(
      $this,
      'frontend_scripts'
    ));
    add_action('admin_menu', array(
      $this,
      'clockmd_menu_pages'
    ));
    add_shortcode('clockmd_show_hospitals', array(
      $this,
      'clockmd_show_hospitals_cb'
    ));
    add_shortcode('clockmd_show_hospitals_with_map', array(
      $this,
      'clockmd_show_hospitals_with_map_cb'
    ));
  }

  function get_with_authorization($endpoint)
  {
    $ch = curl_init($this->apiurl . "/" . $endpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "authtoken: " . $this->authtoken,
      "Content-Type: application/json"
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    return json_decode($body, true);
  }

  function post_with_authorization($endpoint, $data)
  {
    $ch = curl_init($this->apiurl . "/" . $endpoint);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      "authtoken: " . $this->authtoken,
      "Content-Type: application/json"
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    $response = curl_exec($ch);

    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header = substr($response, 0, $header_size);
    $body = substr($response, $header_size);
    return json_decode($body, true);
  }

  function frontend_scripts()
  {

    wp_register_script("popper.min.js", plugin_dir_url(__FILE__) . '/popper.min.js', array(
      'jquery'
    ), time(), false);

    wp_register_script("bootstrap.min.js", plugin_dir_url(__FILE__) . '/bootstrap.min.js', array(
      'jquery',
      'popper.min.js'
    ), time(), false);

    wp_register_script("input.mask.js", plugin_dir_url(__FILE__) . '/input.mask.js', array(
      'jquery'
    ), time(), false);

    wp_register_script("clockmd-script-js", plugin_dir_url(__FILE__) . '/script.js', array(
      'jquery',
      'input.mask.js',
      'bootstrap.min.js'
    ), time(), false);

    wp_enqueue_style('bootstrap4', plugin_dir_url(__FILE__) . '/bootstrap.min.css', array(), time(), false);

    wp_enqueue_style('scheduling', plugin_dir_url(__FILE__) . '/scheduling.css', array(), time(), false);

    wp_enqueue_script("bootstrap.min.js");

    wp_enqueue_script("clockmd-script-js");
  }

  public function getHopitalNameForGf($hospitalName)
  {
    switch ($hospitalName) {
      case 'HasletAvondaleUS287':
        return 'HasletUS287';
        break;
      case 'Haslet Avondale 287':
        return 'HasletUS287';
        break;
      case 'McKinney':
        return 'Mckinney';
        break;
      case 'McKinney Custer':
        return 'Mckinney';
        break;
      case 'Wichita Falls':
        return 'WFalls';
        break;
      default:
        return $hospitalName;
        break;
    }
  }

  public function populateDb($storename, $latitude, $longitude, $full_address, $phone_number, $externalUrl)
  {
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
      $street = $addressArray[0] . ", " . $addressArray[1];
      $city = $addressArray[2];
      $state = $stateAndZip[1];
      $county = end($addressArray);
      $zip = $stateAndZip[2];
    }

    if (is_array($row) && count($row) > 0) {
      // We don't need update now
      $sql = "UPDATE $tblname SET ssf_wp_store='$storename', ssf_wp_address='$street', ssf_wp_city='$city', ssf_wp_state='$state', ssf_wp_country='$county', ssf_wp_zip='$zip', ssf_wp_latitude='$latitude', ssf_wp_longitude='$longitude', ssf_wp_phone='$phone_number', ssf_wp_ext_url='$externalUrl', ssf_wp_is_published='1', ssf_wp_tags='Clinic&#44;' WHERE ssf_wp_store='$storename'";
      return $wpdb->query($sql);
    } else {
      /**
       * Generating the query to insert game with all its levels
       *
       */
      $sql = "INSERT INTO $tblname (ssf_wp_store, ssf_wp_address, ssf_wp_city, ssf_wp_state, ssf_wp_country, ssf_wp_zip, ssf_wp_latitude, ssf_wp_longitude, ssf_wp_phone, ssf_wp_ext_url, ssf_wp_is_published, ssf_wp_tags) VALUES ('$storename','$street','$city','$state','$county','$zip','$latitude','$longitude', '$phone_number', '$externalUrl', '1', 'Clinic&#44;')";
      return $wpdb->query($sql);
    }
  }

  public function getStoreInfoFromDb( $hospitalId ) {
    global $table_prefix, $wpdb;
    $tblname = $table_prefix . 'ssf_wp_stores';

    $sql = "SELECT * FROM $tblname WHERE ssf_wp_ext_url like '%?hospital=$hospitalId%' ";
    return $wpdb->get_row($sql, ARRAY_A);
  }

  function init_action_callbacks()
  {
    if (!empty($_GET['page']) && !empty($_GET['importclockmd']) && $_GET['importclockmd'] == 1 && $_GET['page'] == "clockmd__settings") {
      $hospitals = $this->get_with_authorization('/hospitals?details=true');
      if (is_array($hospitals) && count($hospitals) > 0) {
        $dbResponse = array();
        foreach ($hospitals as $i => $hospital) {
          $extUrl = site_url() . "/online-appointment-form?hospital=" . $hospital['id'];
          $dbResponse[] = $this->populateDb($hospital['name'], $hospital['latitude'], $hospital['longitude'], $hospital['full_address'], $hospital['phone_number'], $extUrl);
        }
      }
    }

    if (!empty($_POST) && !empty($_POST['appointment']) && is_array($_POST['appointment']) && count($_POST['appointment']) > 0) {

      // Getting the formdata
      $createAppointmentData = $_POST['appointment'];

      $cmdhospitalName = explode(" - ", $createAppointmentData['hospital_name']);
      $location = $this->getHopitalNameForGf(trim($cmdhospitalName[1]));

      // Converting the date format into the api desired format
      $createAppointmentData['dob'] = date("m/d/Y", strtotime($createAppointmentData['dob']));

      // Calling the create appointment API
      $createAppointmentRes = $this->post_with_authorization('/appointments/create', $createAppointmentData);

      if (is_array($createAppointmentRes) && count($createAppointmentRes) > 0) {
        if (isset($createAppointmentRes['error'])) {
          $errorMsgToSend = explode(":", $createAppointmentRes['error'])[1];
          wp_redirect("?action=appointment_error&errmsg=" . $errorMsgToSend . "&hospital=" . $createAppointmentData['hospital_id']);
        } else {
          date_default_timezone_set('America/Chicago');
          /*
          // Appointment confirmed for this clinic name at this date at this time
          $msg = "Appointment confirmed for ".$createAppointmentData['hospital_name'];
          $msg .= " at ".date( "m/d/Y H:i:s A", strtotime($createAppointmentData['apt_time']) );
          // $msg .= " and your confirmation code is ".$createAppointmentRes['confirmation_code'];
          // $msg .= " and appointment queue id is ".$createAppointmentRes['appointment_queue_id'];*/
          $queryString = array(
            'gtm' => 'appointment',
            'checkinid' => 'clockwise',
            'Location' => $location,
            'notifemail' => $createAppointmentData['email'],
            'visitdate' => date('m-d-Y', strtotime($createAppointmentData['apt_time'])),
            'sched' => date('h:i', strtotime($createAppointmentData['apt_time'])),
            'patphne' => $createAppointmentData['phone_number'],
            'fname' => $createAppointmentData['first_name'],
            'lname' => $createAppointmentData['last_name'],
            'patdob' => $createAppointmentData['dob'],
            // 'reason_id' => $createAppointmentData['reason_id'],
            // 'vt' => $createAppointmentRes['reason_description']
          );
          if ($createAppointmentData['is_new_patient'] !== "true") {
            $queryString['patstatus'] = 'Existing Patient';
          } else {
            $queryString['patstatus'] = 'New Patient';
          }
          wp_redirect("https://forms.communitymedcare.com/?" . http_build_query($queryString));
        }
        exit;
      }
    }
  }

  function clockmd_show_hospitals_cb($atts)
  {
    $atts = shortcode_atts(array(
      'foo' => 'no foo'
    ), $atts);
    return $this->get_hospitals();
  }

  function get_hospitals()
  {
    $str = "<div class='container appointments-container'>";
    $str .= "<div class='row'>";

    if (!empty($_GET['hospital'])) {
      $hospitalId = $_GET['hospital'];
      $reasonId = 0;
      if (!empty($_GET['reasonId'])) {
        $reasonId = $_GET['reasonId'];
      }

      // If appointmentcreated and msg is set then show notification bar
      if (!empty($_GET['action']) && !empty($_GET['msg']) && $_GET['action'] == "appointment_created") {
        $str .= '<div class="col-md-12"><div class="alert alert-success" role="alert">' . $_GET['msg'] . '</div></div>';
        $str .= '<div class="col-md-12"><a class="btn btn-primary" href="https://forms.communitymedcare.com" target="_blank">Fill Out your Registration Forms</a></div>';
      }
      else {
        // If appointmenterror and msg is set then show notification bar
        if (!empty($_GET['errmsg'])) {
          $str .= '<div class="col-md-12"><div class="alert alert-danger" role="alert">' . $_GET['errmsg'] . '</div></div>';
        }

        // #1. Getting the Hospital Informations
        $hospital = $this->get_with_authorization('/hospitals/' . $hospitalId);

        // #2. Getting the reasons Informations
        $reasonDescription = "";
        $reasons = $this->get_with_authorization('/reasons?hospital_id=' . $hospitalId);

        if (!isset($reasons['error'])) {
          // #3. Generating the reasons dropdown
          $select = '<select required onchange="changeReason(this)" class="form-control form-control-lg reason_description" name="appointment[reason_id]">';
          if (!empty($reasons['reasons']) && is_array($reasons['reasons'])) {
            foreach ($reasons['reasons'] as $i => $reason) {
              if ($i == 0) {
                $reasonDescription = $reason['description'];
                $select .= '<option selected value="' . $reason['id'] . '">' . $reason['description'] . '</option>';
              } else if ($reasonId == $reason['id']) {
                $reasonDescription = $reason['description'];
                $select .= '<option selected value="' . $reason['id'] . '">' . $reason['description'] . '</option>';
              } else {
                $select .= '<option value="' . $reason['id'] . '">' . $reason['description'] . '</option>';
              }
            }
          }
          $select .= '</select>';
          $select .= '<input type="hidden" name="appointment[hospital_id]" value="' . $hospitalId . '" />';
          $select .= '<input type="hidden" name="appointment[hospital_name]" value="' . $hospital['name'] . '" />';

          // #4. Getting the timeslots html
          $timeSlots = $this->get_hospital_available_times($hospitalId, $reasonDescription);

          $str .= $this->getAppointmentForm($select, $timeSlots);
          $str .= "<div class='col-12 col-lg-4 col-xl-6'>
              <div class='appointments-form-logo'>
                <img src='" . $this->clockwiselogo . "'>
              </div>
              <div class='appointments-form-header'>
                <h4>" . $hospital['name'] . "</h4>
                <div class='appointments-address'>
                  <a target='_blank' href='//maps.google.com/?q=" . urlencode($hospital['full_address']) . "'><span data-v-4266784e='' role='img' aria-label='MapMarker icon' class='mdi mdi-map-marker'><svg data-v-4266784e='' fill='currentColor' width='24' height='24' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'><!----> <path data-v-4266784e='' d='M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5M12,2A7,7 0 0,0 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9A7,7 0 0,0 12,2Z'></path></svg></span>" . $hospital['full_address'] . "</a>
                </div>
                <div class='appointments-tel'>
                  <a href='tel:" . $hospital['phone_number'] . "' >" . $hospital['phone_number'] . "</a>
                </div>
              </div>
              <div class='appointments-form-info'>
                <p>Today's Business Hours: ".$hospital['todays_business_hours']."</p>
                <p>Please complete the following information to hold a place in line! <br>
                Note that all times are estimates only.</p>
              </div>";

            // Getting the hospital id from db
            $hospitalInfoFromDb = $this->getStoreInfoFromDb( $hospitalId );
            if ( $hospitalInfoFromDb['ssf_wp_id'] ) {
              $imageUrl = $this->getSsfUploadDir( $hospitalInfoFromDb['ssf_wp_id'] );
              if ( $imageUrl ) {
                $str .= "<img class='clinic-image' src='".$imageUrl."'/>";
              }
            }

          $str .= "</div>";
        } else {
          $str .= '<div class="col-md-12"><center>There are no times available for online scheduling right now. During normal office hours, just come in to the facility we will see you as a regular walk-in. Note: This message will be shown when there are no online visit times available (the clinic is full past their configured time for online patients OR the clinic is closed).</center></div>';
        }
      }
    } else {
      $str .= "<div class='col-md-12'>No Hospital Id</div>";
    }
    $str .= "</div>";
    $str .= "</div>";
    return $str;
  }

  function getAppointmentForm($dropDown, $timeslots) {
    date_default_timezone_set('America/Chicago');
    $now    = new DateTime();
    $begin  = new DateTime('17:00');
    $end    = new DateTime('20:30');
    $tmpString = "";

    if ( !empty($_GET['hospital']) ) {
      $hospitalId = $_GET['hospital'];
      $hospitalUrl = site_url() . "/online-appointment-form?hospital=2768";
      $lantanaClinicUrl = site_url() . "/online-appointment-form?hospital=5558";

      if ( $hospitalId == "2766" ) {
        $tmpString .= "<h2><div class='col-md-12 col-lg-12 col-xl-12 alert alert-danger text-center nowalkins-noitce'>The Melissa Clinic is not accepting walk-ins (unscheduled visits) the rest of the day. Our McKinney Clinic is only a few miles away, and IS ACCEPTING scheduled visits and walkins. <a href='".$hospitalUrl."'>CLICK HERE FOR MCKINNEY CLINIC</a></div></h2>";
      }
      if ( $hospitalId == "3310" ) {
        $tmpString .= "<h2><div class='col-md-12 col-lg-12 col-xl-12 alert alert-danger text-center nowalkins-noitce'>The Prosper Clinic is not accepting walk-ins (unscheduled visits) the rest of the day. Our McKinney Clinic is only a few miles away, and IS ACCEPTING scheduled visits and walkins. <a href='".$hospitalUrl."'>CLICK HERE FOR MCKINNEY CLINIC</a></div></h2>";
      }
      if ( $hospitalId == "2647" ) {
        $tmpString .= "<h2><div class='col-md-12 col-lg-12 col-xl-12 alert alert-danger text-center nowalkins-noitce'>The Cross Roads Clinic is not accepting walk-ins (unscheduled visits) the rest of the day. Our Lantana Clinic is nearby and IS ACCEPTING scheduled visits and walk-ins. CLICK HERE FOR <a href='".$lantanaClinicUrl."'>LANTANA CLINIC</a></div></h2>";
      }
    }

    if ( $now >= $begin && $now <= $end ) {
      // Do Nothing
    }
    else {
      // $tmpString = Date("H:i:s");
      $tmpString = "";
    }

    return $tmpString.'<div id="AppointmentFormWrapper" class="col-12 col-lg-8 col-xl-6">
      <form class="appointments-form" action="" method="post" onsubmit="return getAppointmentData(this)">
          <div class="form-group row">
            <div class="col-md-12">
            <label class="form-check-label">Select a visit reason</label>' . $dropDown . '</div>
          </div>
          <div class="form-group mobrow appointments-visit-time">
            <label class="form-check-label">Patient Type</label>
            <div class="btn-group d-flex js-patientTypeButtonWrapper" role="group" aria-label="Patient Type Button Wrapper">
              <button onclick="updateOption(this)" value="true" type="button" class="btn btn-outline-primary w-100">New Patient</button>
              <button onclick="updateOption(this)" value="false" type="button" class="btn btn-outline-primary w-100 select-option-btn">Existing Patient</button>
            </div>
          </div>
          ' . $timeslots . '
          <div class="form-group row">
            <div class="resmbottom col-sm-12 col-md-6">
              <label class="form-check-label">First Name</label>
                <input required type="text" name="appointment[first_name]" class="form-control">
            </div>
            <div class="col-sm-12 col-md-6">
              <label class="form-check-label">Last Name</label>
                <input required type="text" name="appointment[last_name]" class="form-control">
            </div>
          </div>
          <div class="form-group row">
            <div class="resmbottom col-sm-12 col-md-6">
              <label class="form-check-label">Date of Birth</label>
                <input required type="date" name="appointment[dob]" class="form-control">
            </div>
            <div class="col-sm-12 col-md-6">
              <label class="form-check-label">Patient Birth Sex</label>
              <select required class="form-control form-control-lg" name="appointment[sex]">
                <option value="">Select</option>
                <option value="M">M</option>
                <option value="F">F</option>
              </select>
            </div>
             </div>
          <div class="form-group row">
            <div class="col-sm-12 col-md-12">
              <label class="form-check-label">Cell Phone Number</label>
                <input required type="tel" name="appointment[phone_number]" class="form-control">
            </div>
          </div>
          <div class="form-group row">
            <div class="col-sm-12 col-md-12">
              <label class="form-check-label">Email</label>
                <input required type="email" name="appointment[email]" class="form-control">
            </div>
          </div>
          <div style="display:none" class="form-group row">
            <div class="col-sm-12 col-md-12">
              <label class="form-check-label">We\'ll send you a text message when it\'s time to show up.</label>
            </div>
            <div class="resmbottom col-sm-12 col-md-4">
              <input min="1" type="number" name="appointment[reminder_minutes]" class="form-control">
            </div>
            <div class="col-sm-12 col-md-8"> minutes before your visit</div>
          </div>
          <div class="form-group row">
            <div class="col-md-12">
              <input type="hidden" name="appointment[can_send_alert_sms]" value="true">
              <input type="hidden" name="appointment[is_new_patient]" />
              <input type="hidden" name="appointment[days_from_today]" />
              <input type="hidden" name="appointment[apt_time]" />
              <button class="btn btn-primary" type="submit">Submit form</button>
            </div>
          </div>
      </form>
    </div>
    <script>
    jQuery(document).ready(function(){
          jQuery("[name=\"appointment[days_from_today]\"]").trigger("click").change()
      var phones = [{ "mask": "(###) ###-####"}];
      jQuery(`input[name="appointment[phone_number]"]`).inputmask({
      mask: phones,
      greedy: false,
      definitions: { "#": { validator: "[0-9]", cardinality: 1}},
            autoUnmask: true
      })
    })
    </script>';
  }

  function get_hospital_available_times($hospitalId, $reason_description)
  {
    /**
     * Generating the API Path with Query string
     *
     */
    $requestPath = '/hospitals/' . $hospitalId . '/available_times';
    $requestPath .= '?';
    $requestPath .= http_build_query(array(
      'slot_type' => 'online',
      'reason_description' => $reason_description
    ));

    /**
     * Calling the generated API
     *
     */
    $timeSlots = $this->get_with_authorization($requestPath);

    /**
     * Creating the variables
     *
     */
    $timeSlotsHtml = "";
    $todayDate = date("m/d/Y");
    $nextDayTimeStamp = strtotime("+1 day");
    $nextDate = date("m/d/Y", $nextDayTimeStamp);
    $todayTimeStamp = strtotime(date("m/d/Y"));

    /**
     * Generating all Dropdowns with target Id so that it can be hide/show using jquery
     *
     */
    $todayTimeOptions = "";
    $todayDropdownTimeOptions = "";
    $todayTimeCounter = 0;

    $tomorrowTimeOptions = "";
    $tomorrowDropdownTimeOptions = "";
    $tomorrowTimeCounter = 0;

    $isTodayTimeAvailable = "(No Times Available)";

    foreach ($timeSlots as $i => $times) {
      foreach ($times as $j => $t) {
        $date = strtotime($t['date']);
        if ( is_array($t['times']) && count($t['times']) > 0 ) {
          foreach ($t['times'] as $k => $ts) {
            if ($todayTimeStamp == $date) {
              $isTodayTimeAvailable = "";
              if ( $todayTimeCounter == 0 ) {
                $todayTimeOptions .= '<button onclick="onTimeSelect(this)" type="button" data-target-date="' . $todayTimeStamp . '" value="' . $ts['time'] . '" class="btn btn-outline-primary w-100 select-option-btn">' . $ts['display_time'] . '</button>';
              }
              else if ( $todayTimeCounter > 0 && $todayTimeCounter < 4 ) {
                $todayTimeOptions .= '<button onclick="onTimeSelect(this)" type="button" data-target-date="' . $todayTimeStamp . '" value="' . $ts['time'] . '" class="btn btn-outline-primary w-100">' . $ts['display_time'] . '</button>';
              }
              else {
                $todayDropdownTimeOptions .= '<button onclick="onTimeSelect(this)" href="#" data-target-date="' . $todayTimeStamp . '" value="' . $ts['time'] . '" class="dropdown-item">' . $ts['display_time'] . '</button>';
              }
              $todayTimeCounter++;
            }
            else {
              if ( $tomorrowTimeCounter == 0 ) {
                $tomorrowTimeOptions .= '<button onclick="onTimeSelect(this)" type="button" data-target-date="' . $todayTimeStamp . '" value="' . $ts['time'] . '" class="btn btn-outline-primary w-100 select-option-btn">' . $ts['display_time'] . '</button>';
              }
              else if ( $tomorrowTimeCounter > 0 && $tomorrowTimeCounter < 4 ) {
                $tomorrowTimeOptions .= '<button onclick="onTimeSelect(this)" type="button" data-target-date="' . $todayTimeStamp . '" value="' . $ts['time'] . '" class="btn btn-outline-primary w-100">' . $ts['display_time'] . '</button>';
              }
              else {
                $tomorrowDropdownTimeOptions .= '<button onclick="onTimeSelect(this)" href="#" data-target-date="' . $todayTimeStamp . '" value="' . $ts['time'] . '" class="dropdown-item">' . $ts['display_time'] . '</button>';
              }
              $tomorrowTimeCounter++;
            }
          }
        }
        else {
          if ( !empty ($_GET['hospital']) ) {
            $hospitalId = $_GET['hospital'];
            $McKinneyClinicHospitalUrl = site_url() . "/online-appointment-form?hospital=2768";
            $PrincetonClinicHospitalUrl = site_url() . "/online-appointment-form?hospital=2695";
            $CrossRoadsClinicHospitalUrl = site_url() . "/online-appointment-form?hospital=2647";
            if ( $todayTimeStamp == $date ) {
              if ( $hospitalId == "2766" ) {
                $todayTimeOptions = "<div class='col-md-12 col-lg-12 col-xl-12 alert alert-danger text-center nowalkins-noitce'>This clinic has no more spots available today. Check our <a href='".$McKinneyClinicHospitalUrl."'>McKinney Clinic</a> or <a href='".$PrincetonClinicHospitalUrl."'>Princeton Clinic</a> for availability. If you are injured, we can still see you as a walk-in at this clinic.</div>";
              }
              elseif ( $hospitalId == "3310" ) {
                $todayTimeOptions = "<div class='col-md-12 col-lg-12 col-xl-12 alert alert-danger text-center nowalkins-noitce'>This clinic has no more spots available today. Check our <a href='".$McKinneyClinicHospitalUrl."'>McKinney Clinic</a> or <a href='".$CrossRoadsClinicHospitalUrl."'>Cross Roads Clinic</a> for availability. If you are injured, we can still see you as a walk-in at this clinic.</div>";
              }
              else {
                $todayTimeOptions = "<div class='col-md-12 col-lg-12 col-xl-12 alert alert-danger text-center nowalkins-noitce'>This clinic has no more spots available today.</div>";
              }
            }
            else {
              if ( $hospitalId == "2766" ) {
                $tomorrowTimeOptions = "<div class='col-md-12 col-lg-12 col-xl-12 alert alert-danger text-center nowalkins-noitce'>This clinic has no more spots available. Check our <a href='".$McKinneyClinicHospitalUrl."'>McKinney Clinic</a> or <a href='".$PrincetonClinicHospitalUrl."'>Princeton Clinic</a> for availability. If you are injured, we can still see you as a walk-in at this clinic.</div>";
              }
              elseif ( $hospitalId == "3310" ) {
                $tomorrowTimeOptions = "<div class='col-md-12 col-lg-12 col-xl-12 alert alert-danger text-center nowalkins-noitce'>This clinic has no more spots available. Check our <a href='".$McKinneyClinicHospitalUrl."'>McKinney Clinic</a> or <a href='".$CrossRoadsClinicHospitalUrl."'>Cross Roads Clinic</a> for availability. If you are injured, we can still see you as a walk-in at this clinic.</div>";
              }
              else {
                $tomorrowTimeOptions = "<div class='col-md-12 col-lg-12 col-xl-12 alert alert-danger text-center nowalkins-noitce'>This clinic has no more spots available.</div>";
              }
            }
          }
        }
      }
    }

    $timeSlotsHtml .= '<div class="form-group mobrow appointments-visit-time">
      <label class="form-check-label">Visit Time</label>
      <div class="btn-group d-flex js-daySelectorButtonWrapper" role="group" aria-label="Day Selector Button Wrapper">
        <button onclick="updateOption(this)" data-targetOptionGroup="' . $todayTimeStamp . '" value="0" type="button" class="btn select-option-btn btn-outline-primary w-100">' . $todayDate . '</button>
        <button onclick="updateOption(this)" data-targetOptionGroup="' . $nextDayTimeStamp . '" value="1" type="button" class="btn btn-outline-primary w-100">' . $nextDate . '</button>
      </div>
    </div>';
    $timeSlotsHtml .= '<div data-optionGroup="'.$todayTimeStamp.'" class="btn-group form-group d-flex mob-appointments-visit-time appointments-visit-time js-todayTimeSelectorButtonWrapper" role="group" aria-label="Button group with nested dropdown">'.$todayTimeOptions;

    if ( !empty($todayDropdownTimeOptions) ) {
      $timeSlotsHtml .= '<div class="btn-group js-otherTimeslotsDropdown" role="group">
        <button type="button" class="btn btn-outline-primary w-100 dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">More</button>
        <div class="dropdown-menu dropdownMenuMaxheight" aria-labelledby="more-time">
          '.$todayDropdownTimeOptions.'
        </div>
      </div>';
    }
    $timeSlotsHtml .= '</div>';
    $timeSlotsHtml .= '<div data-optionGroup="'.$nextDayTimeStamp.'" class="displaynone btn-group form-group d-flex mob-appointments-visit-time appointments-visit-time js-tomorrowTimeSelectorButtonWrapper" role="group" aria-label="Button group with nested dropdown">'.$tomorrowTimeOptions;

    if ( !empty($tomorrowDropdownTimeOptions) ) {
      $timeSlotsHtml .= '<div class="btn-group js-otherTimeslotsDropdown" role="group">
        <button type="button" class="btn btn-outline-primary w-100 dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">More</button>
        <div class="dropdown-menu dropdownMenuMaxheight" aria-labelledby="more-time">
          '.$tomorrowDropdownTimeOptions.'
        </div>
      </div>';
    }
    $timeSlotsHtml .= '</div>';

    return $timeSlotsHtml;
  }

  function clockmd_show_hospitals_with_map_cb()
  {
    return 'This shortcode has been disabled';
  }

  function clockmd_menu_pages()
  {
    $menu_name = 'ClockMD Settings';
    $menu_slug = 'clockmd__settings';
    $menu_main = 'ClockMD Settings';

    add_menu_page($menu_name, $menu_main, 'manage_options', $menu_slug, '', plugin_dir_url(__FILE__) . 'assets/img/logo-wp.png', 57);

    add_submenu_page($menu_slug, $menu_name, $menu_main, 'manage_options', $menu_slug, array(
      $this,
      'clockmd_settings_page'
    ));

    add_submenu_page($menu_slug, "Alert Notices", "Alert Notices", 'manage_options', "alert_notices", array(
      $this,
      'clockmd_settings_page'
    ));
  }

  function clockmd_settings_page()
  {
    include_once 'Pages/Admin/settings.php';
  }

  function getSsfUploadDir( $edit ) {
    $ssf_wp_uploads = wp_upload_dir();
    if ( is_ssl() ) {
      $ssf_wp_uploads = str_replace( 'http://', 'https://', $ssf_wp_uploads );
    }
    $dir = $ssf_wp_uploads['basedir']."/ssf-wp-uploads/images/".$edit."/";
    if ( is_dir($dir) ) {
      $image_upload_path = "../wp-content/uploads/ssf-wp-uploads/images/";
      $images = @scandir($dir);
      foreach($images as $k => $v):
      endforeach;

      return $image_upload_path.$edit.'/'.$v;
    }
    else {
      return null;
    }
  }
}

Clockmd::get_instance();
