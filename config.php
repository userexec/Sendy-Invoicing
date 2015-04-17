<?php
	$invoice_info = array(
		'company_name'		=> 'Sample Company', 						// Who the invoices will be from
		'company_name_long' => 'Sample Company Inc.',
		'address_line_1'	=> '105 Company Building, 12th St.',		// Street address of company
		'address_line_2'	=> 'City, ST 00000-0000',					// City, State, and ZIP of company
		'phone_number'		=> '(000) 000-0000',						// Phone number of contact for customer
		'email_address'		=> 'sample@sample.com',						// email of contact for customer
		'invoice_for'		=> 'Invoice for Mass Email Services',		// Text for invoice description
		'account_number'	=> 'R0000 / 000000'							// The account to pay
	);

	/* Admins familiar with HTML and CSS may further customize all wording
	 * and invoice visuals in invoicing.php.
	 */

	$email_notification = array(
		'api_key'			=> '00000000000000000000'	// Your Sendy API key
		'payee_name' 		=> 'John Smith',			// Who receives new invoice notifications?
		'payee_email'		=> 'jsmith@sample.com',		// What is his or her email address?
		'server_email'		=> 'sendy@sample.com',		// The From: field for the notifications
		'smtp_host'			=> 'smtp.gmail.com',		// e.g. 'smtp.gmail.com'
		'smtp_port'			=> '465',					// e.g. '465'
		'smtp_ssl'			=> 'ssl',					// e.g. 'ssl'
		'smtp_username'		=> 'cjones@sample.com',		// The username to log into your SMTP account
		'smtp_password'		=> 'supersecretpassword'	// The password to log into your SMTP account
	);
?>