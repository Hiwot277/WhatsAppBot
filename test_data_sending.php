<?php
require_once 'db_utils.php';

$testPhone = "972500000000"; // Test phone number

echo "Testing data sending for phone: $testPhone\n";

// Test 1: Phone Number 2
echo "1. Sending phone_num_2...\n";
$res1 = sendToGoogleSheet($testPhone, 'phone_num_2', '050-1234567');
// Note: sendToGoogleSheet returns void but logs to error_log. 
// We will check the output of this script which captures stdout/stderr if possible, 
// or we can modify sendToGoogleSheet to return something, but better to just rely on the logs printed to screen if configured,
// or just trust the function's internal logging.
// Actually, let's just use the function as is. It prints to error_log.

// Test 2: ID Number
echo "2. Sending id_number...\n";
sendToGoogleSheet($testPhone, 'id_number', '123456789');

echo "Done. Check your Google Sheet for a row with phone $testPhone.\n";
echo "You should see '050-1234567' in phone_num_2 and '123456789' in id_number.\n";
