<?php
require_once __DIR__ . '/db_utils.php';

echo "Testing ID Number Save to Google Sheets...\n";

$phone = "972500000000"; // Test phone
$field = "id_number";
$value = "123456789";   // Test ID

echo "Sending ID Number: Phone=$phone, Field=$field, Value=$value\n";

// This function calls the webhook
sendToGoogleSheet($phone, $field, $value);

echo "Done. Check your Google Sheet for ID: 123456789\n";
