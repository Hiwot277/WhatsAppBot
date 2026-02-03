<?php
require_once __DIR__ . '/db_utils.php';

// Test Case 1: Fast Loans User
$phoneFastLoans = '0500000001';
echo "Testing Fast Loans User ($phoneFastLoans)...\n";

// Simulate saving selected_area
saveUserResponse($phoneFastLoans, 'selected_area', 'fast_loans');

// Simulate sending data
sendToGoogleSheet($phoneFastLoans, 'test_field', 'test_value');

echo "\n--------------------------------------------------\n";

// Test Case 2: Tax Refund User
$phoneTaxRefund = '0500000002';
echo "Testing Tax Refund User ($phoneTaxRefund)...\n";

// Simulate saving selected_area
saveUserResponse($phoneTaxRefund, 'selected_area', 'tax_refund');

// Simulate sending data
sendToGoogleSheet($phoneTaxRefund, 'test_field', 'test_value');

echo "\nDone.\n";
