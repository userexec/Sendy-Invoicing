<?php include('../config.php');?>
<?php include('../functions.php');?>

<?php
	// ENABLE INVOICE PAYMENT
	/* This script toggles a field in the database that determines whether a
	 * particular brand must use invoicing as their payment method.
	 * 
	 * It uses a checkbox item that you place in edit-brand.php to fire an AJAX
	 * call to the server which toggles the invoice field in the apps table
	 * to 1 or 0. When users are ready to send a campaign, the
	 * check-invoicing.php script will ensure that they are directed to the
	 * correct payment method (PayPal or invoicing) depending on their
	 * settings.
	 */

	$app_id = $_POST['i'];

	$q = "SELECT EXISTS(SELECT invoice FROM apps)";
	$r = mysqli_query($mysqli, $q);
	if ($r) {
		// Invoice row exists. Check its value.
		$q = "UPDATE apps SET invoice = IF(invoice=1, 0, 1) WHERE id=" . $app_id;
		$r = mysqli_query($mysqli, $q);
	} else {
		// Invoice row does not exist. Add it to the login table.
		$q = "ALTER TABLE apps ADD invoice TINYINT(1) DEFAULT 0";
		$r = mysqli_query($mysqli, $q);
		$q = "UPDATE apps SET invoice = 1 WHERE id=" . $app_id;
		$r = mysqli_query($mysqli, $q);
	}

	echo 1;
?>