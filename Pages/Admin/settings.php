<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">	
	<h1>ClockWiseMd CommunityMedCare Imports</h1>
	<p>Clicking this button will import all the data from ClockWiseMD and will populate in the Super Store Finder plugin. You only need to do this 1 time or when any information has been added or changed in ClockWiseMD.</p>
	<hr/>
	<?php 
	if ( !empty($_GET['importclockmd']) && $_GET['importclockmd'] == 1 ) {
		echo "<i>Import has been done.</i>";
		echo "<hr/>";
	}
	?>
	<form action="" method="GET">
		<input type="hidden" name="page" value="clockmd__settings">
		<input type="hidden" name="importclockmd" value="1">
		<button type="submit" class="button button-primary">Pull Hospitals From ClockMD</button>
	</form>
</div>