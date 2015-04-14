<?php include('../config.php');?>
<?php include('../functions.php');?>

<?php
	// CHECK INVOICING
	/* This script checks if a user should be billed via invoice.
	 * It works by checking the invoicing field in the apps table for a value
	 * of 1 or 0. This value will only be there if you've placed the code
	 * from toggle-invoicing.php as instructed in its comments.
	 */

	$app_id = $_POST['i'];
	$invoicing = 0;

	$q = "SELECT invoice FROM apps WHERE id=" . $app_id;
	$r = mysqli_query($mysqli, $q);
	if ($r) {
		while($row = mysqli_fetch_array($r)) {
			$invoicing = $row['invoice'];
		}
		echo $invoicing;
	} else {
		echo 0;
	}
?>