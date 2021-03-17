
function updateOption(e) {
	let targetOptionGroup = jQuery(e).find(':selected').attr('data-targetOptionGroup')
	if (targetOptionGroup) {
		jQuery("select.apt_time").find(`option`).show()
		jQuery("select.apt_time").find(`option[data-target-date=${targetOptionGroup}]`).hide()
		jQuery("select.apt_time option:first").prop("selected",true);
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
	var formObj = convertSerializeArrayToObject(formData)
	console.log(formData, formObj, "formData+formObj")
	return false
}

/**
* This is tricky function getting the formdata array and returning
* the object
**/
function convertSerializeArrayToObject( serializeArray ) {
    var o = {};
    $.each(serializeArray, function() {
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

// /**
// * Function for calling post api
// **/
// function callPostApi(data, cb) {
//   fetch('URL', {
//     method: 'POST',
//     headers: {
//       'Content-Type': 'application/json'
//     },
//     body: JSON.stringify(data)
//   })
//   .then(response => response.json())
//   .then((data) => {
//     cb(data)
//   })
// }