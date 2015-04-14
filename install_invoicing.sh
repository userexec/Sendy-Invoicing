#!/bin/bash

sed -i '/<form.*id="pay-form".*>/r send-to-addition.html' ../../send-to.php
echo 'send-to.php modified...'

EDITLINE=`grep -n '.*<input.*type="text".*id="cost_per_recipient".*' ../../edit-brand.php | cut -f1 -d:`
EDITLINE=$(($EDITLINE + 4))r
sed -i "$EDITLINE edit-brand-addition.html" ../../edit-brand.php
echo 'edit-brand.php modified...'

echo 'Invoicing is now ready to use. Enjoy!'