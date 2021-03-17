<div class="col-md-12">
	<form action="" method="post" onsubmitt="return getAppointmentData(this)">
		<div class="form-group row">
		    <div class="col-md-12"><?php echo $dropDown ?></div>
	  	</div>
		<div class="form-group row">
		    <div class="col-md-6">
		    	<label>First Name</label>
		      <input required type="text" name="appointment[first_name]" class="form-control" placeholder="First name">
		    </div>
		    <div class="col-md-6">
		    	<label>Last Name</label>
		      <input required type="text" name="appointment[last_name]" class="form-control" placeholder="Last name">
		    </div>
	  	</div>
	  	<?php echo $timeslots ?>
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
</div>