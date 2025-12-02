<?php
require_once 'db_utils.php';

$testPhone = "972500000001"; // Different test phone
echo "Testing DB save for phone: $testPhone\n";

// 1. Create user (simulate start)
echo "1. Creating user...\n";
$id = saveUserResponse($testPhone, 'conversation_start', 'start');
if (!$id) {
    die("Failed to create user.\n");
}
echo "User created with ID: $id\n";

// 2. Save Phone Num 2
echo "2. Saving phone_num_2...\n";
$res = saveUserResponse($testPhone, 'phone_num_2', '050-9999999');
if ($res) {
    echo "Success! Saved phone_num_2.\n";
} else {
    echo "FAILED to save phone_num_2.\n";
}

// 3. Save ID Number
echo "3. Saving id_number...\n";
$res = saveUserResponse($testPhone, 'id_number', '999999999');
if ($res) {
    echo "Success! Saved id_number.\n";
} else {
    echo "FAILED to save id_number.\n";
}

echo "Done. If you see Success, the DB is working. Check Google Sheets for phone $testPhone.\n";
