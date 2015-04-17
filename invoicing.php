
<?php
	// SENDY INVOICE PAYMENT INTERFACE

	// SECURITY TOKEN MANAGEMENT
	/* This script requires use of a security token system to allow an
	 * AJAX request to trigger the sending of an email containing invoice
	 * information to the payee. This script generates a token which is
	 * then stored in the database and hashed with the campaign name
	 * into a variable. When the invoice is printed, an AJAX request then 
	 * sends the hash and invoice information to payment-notifier.php, which 
	 * should have received the current campaign name, retrieves currently
	 * available security tokens, and hashes each to check for a match to the
	 * received hash. If a match is found, it removes the used token from the
	 * database and sends the invoice details to the payee. If no match is
	 * found, it will not attempt to send an email. payment-notifier.php will
	 * remove outdated tokens each time it runs to prevent excessive storage
	 * utilization.
	 *
	 * This security token system is necessary to prevent unauthorized or
	 * inappropriate usage of the PHP mailer functionality on the Sendy
	 * server. Without it an attacker may repeatedly call payment-notifier.php
	 * to abuse the system's email capabilities.
	 *
	 * For the system to function, the Sendy SQL database needs a table
	 * security_tokens with the following fields:
	 *
	 * id 		- INT
	 * date 	- DATETIME
	 * token 	- VARCHAR
	 *
	 * You can create this table by running the following query on your
	 * database, or simply allow this script to create the table for you.
	 *
	 * CREATE TABLE security_tokens (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY
	 * KEY, date DATETIME NOT NULL, token VARCHAR(120) NOT NULL);
	 *
	 * The section of the code in which the table is detected or created
	 * can be commented out if you run the above query and do not wish this
	 * script to verify the table's existence each time it is run.
	 */
?>

<?php include('../config.php');?>
<?php include('../functions.php');?>
<?php include('../login/auth.php');?>
<?php include('header-invoice.php');?>
<?php include('config.php');?>

<?php
	//POST data
	$schedule = $_POST['pay-and-schedule'];
	$cron = $_POST['cron'];
	
	if($schedule=='true') {
		$campaign_id = is_numeric($_POST['campaign_id']) ? $_POST['campaign_id'] : exit;
		$app = is_numeric($_POST['app']) ? $_POST['app'] : exit;
		$email_list = $_POST['email_lists'];
		$paypal_email = $_POST['paypal2'];
		$total = $_POST['grand_total_val2'];		
		$send_date = $_POST['send_date'];
		$hour = $_POST['hour'];
		$ampm = $_POST['ampm'];
		if($ampm=='pm' && $hour!=12)
			$hour += 12;
		if($ampm=='am' && $hour==12)
			$hour = 00;
		$min = $_POST['min'];
		$timezone = $_POST['timezone'];
		$send_date_array = explode('-', $send_date);
		$month = $send_date_array[0];
		$day = $send_date_array[1];
		$year = $send_date_array[2];
		$the_date = mktime($hour, $min, 0, $month, $day, $year);
		$total_recipients = mysqli_real_escape_string($mysqli, $_POST['total_recipients2']);
	} else {
		$campaign_id = mysqli_real_escape_string($mysqli, $_POST['cid']);
		$app = mysqli_real_escape_string($mysqli, $_POST['uid']);
		$email_list = $_POST['email_list'];
		$paypal_email = $_POST['paypal'];
		$total = $_POST['grand_total_val'];
		$send_date = date("m-d-Y");
		$email_list_implode = implode(',', $email_list);
		$total_recipients = mysqli_real_escape_string($mysqli, $_POST['total_recipients']);
	}
	
	// Set language
	$q = "SELECT login.language FROM campaigns, login WHERE campaigns.id = '$campaign_id' AND login.app = campaigns.app";
	$r = mysqli_query($mysqli, $q);
	if ($r && mysqli_num_rows($r) > 0) while($row = mysqli_fetch_array($r)) $language = $row['language'];
	set_locale($language);

	// Get updated cron entry from main account
	$q = "SELECT cron FROM login WHERE id = 1";
	$r = mysqli_query($mysqli, $q);
	if ($r) {
	    while($row = mysqli_fetch_array($r)) {
			$cron = $row['cron'];
		}  
	}

	// Get currency
	$q = "SELECT currency FROM apps WHERE id = '$app'";
	$r = mysqli_query($mysqli, $q);
	if ($r) {
	    while($row = mysqli_fetch_array($r)) {
			$currency = $row['currency'];
		}  
	}
	
	// Get campaign name
	$q = "SELECT title FROM campaigns WHERE id = '$campaign_id'";
	$r = mysqli_query($mysqli, $q);
	if ($r) {
	    while($row = mysqli_fetch_array($r)) {
			$campaign = $row['title'];
		}
	}

	// Get username
	$q = 'SELECT name, company FROM login WHERE id = "'.get_app_info('userID').'"';
	$r = mysqli_query($mysqli, $q);
    if ($r) {
		while($row = mysqli_fetch_array($r)) {
			$name = $row['name'];
			$department = $row['company'];
		}
	}

	// Get fees
	$q = 'SELECT delivery_fee, cost_per_recipient FROM apps WHERE app_name = "'.$department.'"';
	$r = mysqli_query($mysqli, $q);
    if ($r) {
		while($row = mysqli_fetch_array($r)) {
			$delivery_fee = $row['delivery_fee'];
			$cost_per_recipient = $row['cost_per_recipient'];
		}
		$recipient_charges = $total_recipients * $cost_per_recipient;
	}

	/* SECURE TOKEN DATABASE TABLE CHECKER */
	/* The MySQL database will need a security_tokens table with specific rows.
	 * This function checks for the existence of this table and creates it
	 * if necessary. If this table has already been created, feel free to
	 * comment this out.
	 */
	function checkSecurityTokenTable($mysqli) {
		$q = 'SHOW TABLES LIKE "security_tokens"';
		$r = mysqli_query($mysqli, $q);
		if (mysqli_num_rows($r) === 0) {
			$q = 'CREATE TABLE security_tokens (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, date DATETIME NOT NULL, token VARCHAR(120) NOT NULL);';
			$r = mysqli_query($mysqli, $q);
		}
	}
	checkSecurityTokenTable($mysqli);

	/* SECURE TOKEN GENERATION */
	/* The following two functions rely upon openssl_random_pseudo_bytes() to
	 * choose an entropy source and generate a random number for use in the
	 * secure token exchange with payment-notifier.php. PHP 5.3+ required.
	 */
	function crypto_rand_secure($min, $max) {
		$range = $max - $min;
		if ($range < 0) return $min; // not so random...
		$log = log($range, 2);
		$bytes = (int) ($log / 8) + 1; // length in bytes
		$bits = (int) $log + 1; // length in bits
		$filter = (int) (1 << $bits) - 1; // set all lower bits to 1
		do {
			$rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
			$rnd = $rnd & $filter; // discard irrelevant bits
		} while ($rnd >= $range);
			return $min + $rnd;
		}

	function getToken($length=32){
		$token = "";
		$codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
		$codeAlphabet.= "0123456789";
		for($i=0;$i<$length;$i++){
			$token .= $codeAlphabet[crypto_rand_secure(0,strlen($codeAlphabet))];
		}
		return $token;
	}

	/* SECURE TOKEN STORAGE */
	/* The generated secure token will now be stored in the database for later
	 * retrieval, comparison, and removal by payment-notifier.php
	 */
	function newSecurityToken($mysqli) {
		$token = getToken();
		$q = 'INSERT INTO security_tokens (date, token) VALUES ("' . date("Y-m-d H:i:s") . '", "' . $token . '")';
		$r = mysqli_query($mysqli, $q);
		return $token;
	}

	// Store a token and generate its hash to be sent with the AJAX request
	$hash = md5(newSecurityToken($mysqli) . $campaign);
?>

		<style>
			.container, .container-fluid {
				position: relative;
			}

			.row {
				padding: 1em;
			}

			#invoice {
				margin: 3rem auto;
			}

			#itemization table {
				width: 100%;
			}

			#accept_infobox {
				padding: 1em;
				border-radius: 8px;
			}

			#send_infobox {
				padding: 1em;
				border-radius: 8px;
			}

			#printAgain {
				position: absolute;
				top: 25%;
				left: 0px;
				width: 100%;
				height: 30%;
				text-align: center;
				z-index: 1;
			}
			#printAgainContainer {
				display: inline-block;
				border-radius: 3rem;
				background: lightgray;
				padding: 2rem;
			}
			#printIcon {
				display: inline-block;
				background: gray;
				color: white;
				border-radius: 50%;
				font-size: 14rem;
				line-height: 20rem;
				width: 20rem;
				height: 20rem;
			}
			
			#invoice {
				border: 1px solid #DDD;
				box-shadow: 0px 10px 35px -15px black;
			}
			@media print {
				#invoice {
					opacity: 1 !important;
				}
				#buttons, #utility_header, #printAgain {
					display: none;
					opacity: 0;
				}
			}
		</style>

		<div id="invoiceContainer" class="container-fluid">
			<div id="printAgain">
				<div id="printAgainContainer">
					<div id="printIcon">
						<i class="fa fa-print"></i>
					</div>
					<br><br>
					<a href="javascript:window.print()">
						<button type="button" class="btn btn-primary">Print again</button>
					</a>
				</div>
			</div>
			<br><br>
			<div id="invoice" class="container">
				<div id="letterhead" class="row">
					<div class="col-md-8">
						<h1><?php echo $invoice_info['company_name']; ?></h1>
						<p>
							<?php echo $invoice_info['address_line_1']; ?>
							<br>
							<?php echo $invoice_info['address_line_2']; ?>
						</p>
						<p>
							<?php echo $invoice_info['phone_number']; ?>
							<br>
							<a href="mailto:<?php echo $invoice_info['email_address']; ?>"><?php echo $invoice_info['email_address']; ?></a>
						</p>
					</div>
					<div class="col-md-4" style="text-align: right;">
						<h1 style="font-weight: bold;">INVOICE</h1>
						<p>
							<?php echo date('F j\, Y');?>
							<br>
							<?php echo $invoice_info['invoice_for']; ?>
						</p>
						<p>
							<strong>Attn: <?php echo $name?></strong>
							<br>
							<?php echo $department?>

						</p>
					</div>
				</div>
				<hr>
				<div id="message" class="row">
					<p>Dear <?php echo $name?>,</p>
					<br>
					<p>Please find below the charges associated with the <strong><?php echo htmlentities($campaign, ENT_QUOTES);?></strong> mass email campaign, to be sent on <?php echo ($send_date ? htmlentities($send_date, ENT_QUOTES) : date("m-d-Y")); ?>. This email will be sent to <?php echo htmlentities($total_recipients, ENT_QUOTES);?> recipients.</span></p>
					
					<p>Please make payment at your earliest convenience, and do not hesitate to contact us with any questions. You may JE the $<?php echo htmlentities($total, ENT_QUOTES);?> to the following account: <?php echo $invoice_info['account_number']; ?>.</p>
					<br>
					<p>Thanks,</p>

					<p><?php echo $invoice_info['company_name_long']; ?></p>
				</div>
				<div id="itemization" class="row">
					<table class="table table-striped table-bordered">
						<tbody>
							<tr>
								<th class="active">#</th>
								<th class="active">Item Description</th>
								<th class="active">Quantity</th>
								<th class="active">Unit Price</th>
								<th class="active">Total</th>
							</tr>
							<tr>
								<td>1</td>
								<td>Amazon SES usage fee</td>
								<td>1</td>
								<td><?php echo htmlentities($delivery_fee, ENT_QUOTES);?></td>
								<td><?php echo htmlentities($delivery_fee, ENT_QUOTES);?></td>
							</tr>
							<tr>
								<td>2</td>
								<td>Recipient fee</td>
								<td><?php echo htmlentities($total_recipients, ENT_QUOTES);?></td>
								<td><?php echo htmlentities($cost_per_recipient, ENT_QUOTES);?></td>
								<td><?php echo htmlentities($recipient_charges, ENT_QUOTES);?></td>
							</tr>
							<tr>
								<td class="success" colspan="4">
									Total
								</td>
								<td class="success">
									<?php echo htmlentities($total, ENT_QUOTES);?>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>

		<div id="buttons" class="container">
			<!-- The accept box is shown to users before they have accepted and printed the invoice. -->
			<div id="accept_infobox" class="row bg-info">
				<p>Before you can proceed and send this email, you must print this invoice. Please click the button below to accept the charges and print, and a "Send or schedule campaign" button will appear. A copy of the invoice will be automatically sent to <?php echo $invoice_info['company_name_long']; ?>.</p>

				<!-- Accept charges -->
				<a href="javascript:window.print()" onclick="acceptCharges()">
					<button type="button" class="btn btn-primary">Accept charges and print invoice</button>
				</a>

				<!-- Cancel -->
				<a href="<?php echo htmlentities(APP_PATH, ENT_QUOTES);?>/send-to?i=<?php echo htmlentities($app, ENT_QUOTES);?>&c=<?php echo htmlentities($campaign_id, ENT_QUOTES);?>">
					<button type="button" class="btn btn-danger">Cancel</button>
				</a>
			</div>

			<!-- The send box is shown to users after printing and contains a button to send the campaign. This is necessary in case the user needs to print the invoice again -->
			<div id="send_infobox" class="row bg-success">
				<p>Thank you! Your invoice has been recorded. <strong>Please ensure that you have printed a copy of your invoice</strong>, and then press the "Send or schedule campaign" button. You will receive an email notification when your campaign has been sent.</p>

				<!-- Send campaign -->
				<a href="
					<?php if ($schedule == 'true') : ?>
						<?php echo htmlentities(APP_PATH, ENT_QUOTES);?>/sending?i=<?php echo htmlentities($app, ENT_QUOTES);?>&c=<?php echo htmlentities($campaign_id, ENT_QUOTES);?>&e=<?php echo htmlentities($email_list, ENT_QUOTES);?>&s=true&cr=<?php echo htmlentities($cron, ENT_QUOTES);?>&date=<?php echo htmlentities($the_date, ENT_QUOTES);?>&timezone=<?php echo htmlentities($timezone, ENT_QUOTES);?>&recipients=<?php echo htmlentities($total_recipients, ENT_QUOTES);?>
					<?php else : ?>
						<?php echo htmlentities(APP_PATH, ENT_QUOTES);?>/sending?i=<?php echo htmlentities($app, ENT_QUOTES)?>&c=<?php echo htmlentities($campaign_id, ENT_QUOTES);?>&e=<?php echo htmlentities($email_list_implode, ENT_QUOTES);?>&s=false&cr=<?php echo htmlentities($cron, ENT_QUOTES);?>&recipients=<?php echo htmlentities($total_recipients, ENT_QUOTES);?>
					<?php endif; ?>
				">
					<button type="button" class="btn btn-success">Send or schedule campaign</button>
				</a>
			</div>
		</div>

		<script>
			$('#send_infobox').hide();
			$('#printAgain').hide();

			function acceptCharges() {
				notifyPayee();
				revealSend();
			}

			function revealSend() {
				$('#accept_infobox').hide();
				$('#send_infobox').show();
				$('#invoice').css('opacity', '0.3');
				$('#printAgain').show();
			}

			<?php
				/* PAYMENT NOTIFIER */
				/* The following AJAX request posts data to payment-notifier.php
				 * when the user accepts charges and prints the invoice.
				 * payment-notifier.php will then verify the security token
				 * to ensure it's clear to send an email and check a hash of the
				 * monetary values it received against the "verify" hash to ensure
				 * the user did not tamper with the request.
				 */
			?>

			function notifyPayee() {
				$.ajax({
					url: 'payment-notifier.php',
					type: 'POST',
					cache: false,
					data: 	'api_key=<?php echo $email_notification["api_key"]; ?>' + 
							'&token=<?php echo $hash; ?>' +
							'&verify=<?php echo md5($hash . $delivery_fee . $cost_per_recipient . $total); ?>' +
							'&campaign=<?php echo $campaign; ?>' +
							'&name=<?php echo urlencode($name); ?>' +
							'&department=<?php echo urlencode($department); ?>' +
							'&send_date=<?php echo $send_date; ?>' +
							'&total_recipients=<?php echo $total_recipients; ?>' +
							'&delivery_fee=<?php echo $delivery_fee; ?>' +
							'&cost_per_recipient=<?php echo $cost_per_recipient; ?>' +
							'&recipient_charges=<?php echo $recipient_charges; ?>' +
							'&total=<?php echo $total; ?>'
				});
			}
		</script>
	</body>
</html>