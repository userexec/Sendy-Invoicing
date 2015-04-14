<?php
	// SENDY INVOICE PAYMENT NOTIFIER
?>

<?php include('../config.php'); ?>
<?php include('../../api/_connect.php');?>
<?php include('../helpers/class.phpmailer.php');?>
<?php include('config.php');?>

<?php
	// MONETARY VERIFICATION
	/* Just because the post request contained certain dollar amounts doesn't
	 * mean those were the same ones presented in the invoice. The AJAX call
	 * sent over a key called "verify" which is an md5 of the hash (or 'token'
	 * as this script sees it), delivery_fee, cost_per_recipient, and total.
	 * Check that the hash is the the same here and pass a mailerArg that can
	 * be used to flag price tampering.
	 */

	$verify = md5($_POST['token'] . $_POST['delivery_fee'] . $_POST['cost_per_recipient'] . $_POST['total']);
	$email_notification['tampering'] = ($_POST['verify'] == $verify ? false : true);


	$mailerArgs = $email_notification;


	// ONE-TIME USE SECURITY TOKEN
	/* A random token is placed into the database when payment.php is called.
	 * A hash of the token and campaign name is then sent in the AJAX request
	 * to this script. This script then checks the received campaign name and
	 * hashes it against each of the current security tokens until it finds
	 * a match or determines there are no matches.
	 * If a hash matches, it will send an email and then remove the token.
	 * If no hashes match, no email will be sent.
	 * This prevents inappropriate use of the PHP mailer.
	 *
	 * Outdated security tokens are removed each time this script runs.
	 */
	$inbound_hash = $_POST['token'];

	// Remove any outdated security tokens
	removeOldTokens($mysqli);
	
	// Check the provided API key
	if (verify_api_key($_POST['api_key'])) {
		checkTokens($mysqli, $inbound_hash, $_POST['campaign'], $mailerArgs);
	} else {
		exit;
	}

	/* Pull each current token in the database and check if the 'token' field
	 * of the AJAX request matches the database token hashed with the received
	 * campaign name.
	 *
	 * If a match is found, delete the token in the database and send the
	 * email notification.
	 */
	function checkTokens ($mysqli, $inbound_hash, $campaign_name, $mailerArgs) {
		$q = 'SELECT token FROM security_tokens';
		$r = mysqli_query($mysqli, $q);
		if ($r) {
			while($row = mysqli_fetch_array($r)) {
				if ($inbound_hash == md5($row['token'] . $campaign_name)) {
					deleteToken($mysqli, $row['token']);
					sendMail($mailerArgs);
					break;
				}
			}
		}
	}

	// Delete a specific token
	function deleteToken ($mysqli, $used_token) {
		$q = 'DELETE FROM security_tokens WHERE token="' . $used_token . '";';
		$r = mysqli_query($mysqli, $q);
	}

	// Remove tokens older than 20 minutes
	function removeOldTokens ($mysqli) {
		$q = 'SELECT id FROM security_tokens WHERE date < (NOW() - INTERVAL 20 MINUTE);';
		$r = mysqli_query($mysqli, $q);
		if ($r) {
			$old_tokens = array();

			while($row = mysqli_fetch_array($r)) {
				array_push($old_tokens, $row['id']);
			}
		}

		if ( count($old_tokens) > 0 ) {
			$q = 'DELETE FROM security_tokens WHERE id IN (' . implode(',', $old_tokens) . ');';
			$r = mysqli_query($mysqli, $q);
		}
	}

	// Send the invoice to the payee
	function sendMail ($args) {
		$mail = new PHPMailer();

	  /*$payee_name			= Set at top
		$payee_email		= Set at top
		$server_email		= Set at top*/
		$campaign_name		= $_POST['campaign'];
		$name 				= $_POST['name'];
		$department			= $_POST['department'];
		$send_date 			= $_POST['send_date'];
		$total_recipients	= $_POST['total_recipients'];
		$delivery_fee 		= $_POST['delivery_fee'];
		$cost_per_recipient = $_POST['cost_per_recipient'];
		$recipient_charges	= $_POST['recipient_charges'];
		$total 				= $_POST['total'];

		$message = '<html><body style="padding: 30px;">';
		$message .= '<h1>New Sendy Invoice!</h1>';
		$message .= '<p>A new Sendy invoice has been issued to ' . $name . ' in ' . $department . '.</p>';
		$message .= '<p>The ' . $campaign_name . ' campaign will be sent on ' . $send_date . '.</p>';

		if ($args['tampering']) {
			$message .= '<p style="color: red; font-weight: bold; font-size: 20px;">Tampering detected with this invoice. Please manually verify the proper charges for this campaign in Sendy.</p>';
		}

		$message .= '<table style="width: 100%; border: 1px solid gray; border-collapse: collapse;" cellspacing="0" cellpadding="10"><tr style="background: #CCCCCC;">';
		$message .= '<th style="border: 1px solid gray;">#</th>';
		$message .= '<th style="border: 1px solid gray;">Item Description</th>';
		$message .= '<th style="border: 1px solid gray;">Quantity</th>';
		$message .= '<th style="border: 1px solid gray;">Unit Price</th>';
		$message .= '<th style="border: 1px solid gray;">Total</th>';
		$message .= '</tr><tr>';
		$message .= '<td style="border: 1px solid gray;">1</td>';
		$message .= '<td style="border: 1px solid gray;">Amazon SES usage fee</td>';
		$message .= '<td style="border: 1px solid gray;">1</td>';
		$message .= '<td style="border: 1px solid gray;">' . $delivery_fee . '</td>';
		$message .= '<td style="border: 1px solid gray;">' . $delivery_fee . '</td>';
		$message .= '</tr><tr style="background: #EEEEEE;">';
		$message .= '<td style="border: 1px solid gray;">2</td>';
		$message .= '<td style="border: 1px solid gray;">Recipient fee</td>';
		$message .= '<td style="border: 1px solid gray;">' . $total_recipients . '</td>';
		$message .= '<td style="border: 1px solid gray;">' . $cost_per_recipient . '</td>';
		$message .= '<td style="border: 1px solid gray;">' . $recipient_charges . '</td>';
		$message .= '</tr><tr style="background: #CCCCCC;">';
		$message .= '<td style="border: 1px solid gray;" colspan="4">Total</td>';
		$message .= '<td style="border: 1px solid gray;">'. $total . '</td>';
		$message .= '</tr></table>';
		$message .= '</body></html>';

		$messageAlt = 'New Sendy Invoice!' . '\r\n';
		$messageAlt .= 'A new sendy invoice has been issued to ' . $name . ' in ' . $department . '.' . '\r\n';
		$messageAlt .= 'The ' . $campaign_name . ' campaign will be sent on ' . $send_date . '.' . '\r\n';

		if ($args['tampering']) {
			$messageAlt .= 'Tampering detected with this invoice. Please manually verify the proper charges for this campaign in Sendy.' . '\r\n';
		}

		$messageAlt .= 'Delivery fee: ' . $delivery_fee . '\r\n';
		$messageAlt .= 'Recipient fee: ' . $cost_per_recipient . ' * ' . $total_recipients . ' recipients' . '\r\n';
		$messageAlt .= 'Total: ' . $total . '\r\n';

		$mail->IsSMTP();
		$mail->SMTPDebug 	= 0;
		$mail->SMTPAuth 	= true;
		$mail->SMTPSecure 	= $args['smtp_ssl'];
		$mail->Host 		= $args['smtp_host'];
		$mail->Port 		= $args['smtp_port']; 
		$mail->Username 	= $args['smtp_username'];  
		$mail->Password 	= $args['smtp_password'];

		$mail->CharSet 		= "UTF-8";
		$mail->From 		= $server_email;
		$mail->FromName 	= 'Sendy Invoicing';
		$mail->Subject 		= 'New Sendy Invoice - ' . $campaign_name;
		$mail->AltBody 		= $messageAlt;
		$mail->MsgHTML($message);
		$mail->AddAddress($args['payee_email'], $args['payee_name']);
		$mail->AddReplyTo($args['payee_email'], $args['payee_name']);

		$mail->Send();
	}
?>