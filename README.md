# Sendy-Invoicing

Invoicing add-on for Sendy email server - Bill brands via invoice

![Sendy Invoicing Add-on](https://cloud.githubusercontent.com/assets/5970137/7149270/f9aceb88-e2d0-11e4-8846-2c42139fa4a4.png)

If your department or company can only do internal transactions via invoice or you need an alternative to PayPal, this simple, unofficial add-on provides customizable invoicing on a brand-by-brand basis.

## Compatibility and Due Caution

This add-on is tested with Sendy version 2.0.2. If you are running an older or newer version of Sendy, please test first on a non-production server. The install script attempts to insert two blocks of code within Sendy's files, and there is no guarantee that the target locations will remain the same across versions. Some correction is done automatically with broad regular expressions, but any substantial change to the insertion points will cause the add-on to fail to install.

Unfortunately, updating your Sendy server may also break this add-on. If Sendy's send-to.php or edit-brand.php are overwritten in the update, the Sendy Invoicing add-on will no longer function. You can try re-running install_invoicing.sh to attempt reinsertion of the required code, but again, there is no guarantee the target locations have stayed the same. If you rely on the Sendy Invoicing add-on and need to update Sendy, please check this repository again to see if an update has been issued, or be familiar enough with Sendy and the Sendy Invoicing add-on to make (and if you're feeling nice, merge!) the necessary modifications.

## Installation

Installing this add-on is as easy as cloning this repository into the right place and running a shell script. Simply do the following:

1. cd into your sendy/includes directory (this may be a different path on your server)

    ```
    cd /var/www/html/sendy/includes/
    ```


2. clone this repository

    ```
    git clone https://github.com/userexec/Sendy-Invoicing.git
    ```
    
    
3. cd into the new directory

    ```
    cd Sendy-Invoicing
    ```
    
    
4. make install_invoicing.sh executable

    ```
    chmod +x install_invoicing.sh
    ```
    
    
5. run the install script

    ```
    ./install_invoicing.sh
    ```


## Configuration

Once you have installed the Sendy Invoicing add-on, you will need to configure the wording of the invoices and the SMTP settings necessary to deliver new invoice notifications to you for recordkeeping. These settings are contained within Sendy-Invoicing/config.php.

## Usage

### Enabling invoicing

Once the Sendy Invoicing add-on is in place, a "Charge via invoicing" checkbox will appear under the campaign fee settings on each brand. Check the box to enable invoicing for the brand.

![Edit Brand Page](https://cloud.githubusercontent.com/assets/5970137/7149272/f9ad4ae2-e2d0-11e4-9931-8cc32c807818.png)

Please note that invoicing is activated on a brand-by-brand basis, so some brands can be billed via PayPal while others receive invoices.

### Paying via invoice

When brands using invoicing press the "Proceed to pay for campaign" or "Schedule and pay for campaign" buttons, they will be taken to an invoice page with details about their campaign and the applicable charges. After reviewing the invoice, they may choose "Accept charges and print invoice" or "Cancel."

![User Invoice](https://cloud.githubusercontent.com/assets/5970137/7149270/f9aceb88-e2d0-11e4-8846-2c42139fa4a4.png)

Upon accepting the invoice, the browser's print function is automatically called and a print preview is shown.

![Print Invoice](https://cloud.githubusercontent.com/assets/5970137/7149269/f9ace0d4-e2d0-11e4-99d7-42e0e7ed7a2a.png)

If printing fails, the users has the opportunity to print again as many times as they need before sending the campaign.

![Print Again](https://cloud.githubusercontent.com/assets/5970137/7149271/f9ad3e12-e2d0-11e4-81c0-ac727252dc6c.png)

Once a printed invoice is obtained, users may press "Send or schedule campaign" to complete the workflow.

### Recording invoices

Whenever a user presses the "Accept charges and print invoice" button, a record of the invoice is automatically emailed to you using the details specified in Sendy-Invoicing/config.php. This copy is for your billing and record-keeping use and contains all pertinent information found in the user's printed invoice.

![Invoice Record](https://cloud.githubusercontent.com/assets/5970137/7149273/f9aefffe-e2d0-11e4-8605-2fd521923280.png)

## Removal

Removing the Sendy Invoicing add-on is as simple as deleting the Sendy-Invoicing folder from your sendy/includes directory and removing the two blocks of code it placed in sendy/send-to.php and sendy/edit-brand.php. The blocks of code that were inserted can be found, in full, in send-to-addition.html and edit-brand-addition.html.

To remove any trace of the Sendy Invoicing add-on from your database, drop the 'security_tokens' table and the 'invoice' column in the 'apps' table.