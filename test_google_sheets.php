<?php
require_once __DIR__ . '/db_utils.php';

echo "Testing Google Sheets Integration...\n";

$phone = "972500000000";
$field = "test_field_" . time();
$value = "test_value_" . time();

echo "Sending data: Phone=$phone, Field=$field, Value=$value\n";

sendToGoogleSheet($phone, $field, $value);

echo "Check your Google Sheet (and the PHP error log) to see if it worked.\n";
echo "Note: If you haven't updated the Google Apps Script yet, it might appear as a new row instead of a column update.\n";
