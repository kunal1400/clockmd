<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="wrap">	
	<h1>ClockMd Pages</h1>
	<p>On click of this button all the data from clockmd will be populate in super store finder plugin</p>
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