function updateOption(e) {
  let targetOptionGroup = jQuery(e).attr('data-targetOptionGroup')
  // Removing the selected class
  jQuery(e).parent().find(`button`).removeClass("select-option-btn")
  // adding the selected class
  jQuery(e).addClass("select-option-btn")

  if (targetOptionGroup) {
    // hiding the target group
    jQuery(`div[data-optionGroup]`).addClass("displaynone")
    // showing the target group
    jQuery(`[data-optionGroup="${targetOptionGroup}"]`).removeClass("displaynone")
  }

  /*let targetOptionGroup = jQuery(e).find(':selected').attr('data-targetOptionGroup')
  if (targetOptionGroup) {
    var targetDropdownOptionsLength = jQuery(`option[data-target-date=${targetOptionGroup}]`).length
    console.log(targetDropdownOptionsLength, "+targetDropdownOptionsLength+")
    if ( targetDropdownOptionsLength == 0 ) {
      // Disabling all form inputs
      jQuery(e).closest("form").find("input").attr("disabled", true)
      jQuery(e).closest("form").find("button").attr("disabled", true)
      jQuery(e).closest("form").find("select").attr("disabled", true)
      jQuery("#appointment_error_message").text("(No Times Available For This Date)")
      console.log(targetOptionGroup, targetDropdownOptionsLength, "targetOptionGroup")
    } else {
      // Enabling all form inputs
      jQuery("#appointment_error_message").text("")
      jQuery(e).closest("form").find("input").attr("disabled", false)
      jQuery(e).closest("form").find("button").attr("disabled", false)
      jQuery(e).closest("form").find("select").attr("disabled", false)
    }
    jQuery(e).attr("disabled", false)
    jQuery("select.apt_time").find(`option`).hide()
    jQuery("select.apt_time").find(`option[data-target-date=${targetOptionGroup}]`).show()
    jQuery("select.apt_time option:first").prop("selected",true);
  }*/
}

function onTimeSelect(e) {
  jQuery(e).closest("[data-optiongroup]").find("button").removeClass("select-option-btn")
  jQuery(e).addClass("select-option-btn")
  let selectedTimeSlot = jQuery(e).text()

  if( selectedTimeSlot && jQuery(e).closest(".js-otherTimeslotsDropdown").find("button.dropdown-toggle").length > 0 ) {
    jQuery(e).closest(".js-otherTimeslotsDropdown").find("button.dropdown-toggle").text(selectedTimeSlot)
  } 
  else {
    jQuery(e).closest(".btn-group").find(".js-otherTimeslotsDropdown button.dropdown-toggle").text("More")
  }
}

function changeReason(e) {
  let reasonId = jQuery(e).val()
  if (reasonId) {
    let newUrl = updateQueryStringParameter(window.location.href, 'reasonId', reasonId)
    let latUrl = updateQueryStringParameter(newUrl, 'af', "#AppointmentFormWrapper")
    // window.location.href = newUrl+"#AppointmentFormWrapper"
    console.log(reasonId, latUrl)
    window.location.href = latUrl
  }
}

/**
* This function will update key in query string
*
* -ex -
* let url1 = updateQueryStringParameter(product_url, 'variant', variantId)
* let url2 = updateQueryStringParameter(url1, 'diamondType', variant.option1)
* let url3 = updateQueryStringParameter(url2, 'metal', variant.option2)
*
**/
function updateQueryStringParameter(uri, key, value) {
  var re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
  var separator = uri.indexOf('?') !== -1 ? "&" : "?";
  if (uri.match(re)) {
    return uri.replace(re, '$1' + key + "=" + value + '$2');
  }
  else {
    return uri + separator + key + "=" + value;
  }
}

/**
* Simple function to get form data in array and then get its json
* This is very helpful in calling APIs
*
* <form name="myForm" onsubmit="return getAppointmentData();">
*
**/
function getAppointmentData(e) {
  var formData = jQuery(e).serializeArray()
  var formObj  = convertSerializeArrayToObject(formData)
  jQuery('input[name="appointment[phone_number]"]').css("border-color","unset")

  // Setting value for is_new_patient in hidden field 
  let is_new_patient = jQuery(".js-patientTypeButtonWrapper").find("button.select-option-btn").val()
  if (is_new_patient) {
    jQuery('input[name="appointment[is_new_patient]"]').val(is_new_patient)
  } else {
    jQuery('input[name="appointment[is_new_patient]"]').val("")
  }

  // Setting value for days_from_today in hidden field 
  let todayDateSelected = jQuery(".js-daySelectorButtonWrapper").find("button.select-option-btn").val()
  if (todayDateSelected && todayDateSelected == "0") {
    jQuery('input[name="appointment[days_from_today]"]').val(0)
    let timeSelected = jQuery(".js-todayTimeSelectorButtonWrapper").find("button.select-option-btn").val()
    if ( timeSelected ) {
      jQuery('input[name="appointment[apt_time]"]').val(timeSelected)
    }
  }
  else {
    jQuery('input[name="appointment[days_from_today]"]').val(1)
    let timeSelected = jQuery(".js-tomorrowTimeSelectorButtonWrapper").find("button.select-option-btn").val()
    if ( timeSelected ) {
      jQuery('input[name="appointment[apt_time]"]').val(timeSelected)
    }
  }

  if( formObj && formObj['appointment[phone_number]'] ) {
    if( jQuery('input[name="appointment[phone_number]"]').val().length != 10 ) {      
      jQuery('input[name="appointment[phone_number]"]').css("border-color","red")
      return false
    }
    else {      
      return true
      // return false
    }
  }
  else {    
    return true
    // return false
  }   
}

/**
* This is tricky function getting the formdata array and returning
* the object
**/
function convertSerializeArrayToObject( serializeArray ) {
    var o = {};
    jQuery.each(serializeArray, function() {
        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
}