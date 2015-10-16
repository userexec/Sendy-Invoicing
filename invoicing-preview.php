<?php include('../config.php');?>
<?php include('../functions.php');?>
<?php include('../login/auth.php');?>
<?php include('header-invoice.php');?>
<?php include('config.php');?>

<?php
	$campaign_id = mysqli_real_escape_string($mysqli, $_GET['cid']);
	$app = mysqli_real_escape_string($mysqli, $_GET['uid']);
	$send_date = mysqli_real_escape_string($mysqli, $_GET['date']);
	$total_recipients = mysqli_real_escape_string($mysqli, $_GET['recipients']);

	// Determine send date
	if ($send_date == 'false') {
		$send_date = date("m-d-Y");
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
	$q = "SELECT name, company FROM login WHERE app = '$app'";
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

	// Get total
	$total = $delivery_fee + $cost_per_recipient * $total_recipients;
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
				<p>This preview invoice should be sent to the department for whom you are sending this email. Please verify that the information is correct, press "Print invoice," and print to PDF if emailing the invoice, or print normally if mailing.</p>

				<!-- Print -->
				<a href="javascript:window.print()">
					<button type="button" class="btn btn-primary">Print invoice</button>
				</a>

				<!-- Close -->
				<button type="button" class="btn btn-danger" onclick="window.close();">Close window/tab</button>
			</div>
		</div>
	</body>
</html>