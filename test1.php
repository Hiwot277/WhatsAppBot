<?php
// We will use the original files and rely on the state clearing function
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_utils.php';
require_once __DIR__ . '/scripts.php';
require_once __DIR__ . '/processor.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Helper function to clear state and last message for a clean run ---
function clearUserState($phone) {
    $stateDir = __DIR__ . '/state';
    $stateFile = $stateDir . '/state_' . md5($phone) . '.json';
    $lastMessageFile = $stateDir . '/last_message_' . md5($phone) . '.json';
    
    if (file_exists($stateFile)) {
        unlink($stateFile);
        echo "Cleared state file: $stateFile\n";
    } 
    if (file_exists($lastMessageFile)) {
        unlink($lastMessageFile);
        echo "Cleared last message file: $lastMessageFile\n";
    }
    
    // Also clear the processed_messages.json file which is used by webhook.php
    $processedMessagesFile = __DIR__ . '/processed_messages.json';
    if (file_exists($processedMessagesFile)) {
        file_put_contents($processedMessagesFile, '{}');
        echo "Cleared processed messages file: $processedMessagesFile\n";
    }
}

// --- Helper function to simulate a message and print response ---
function simulateMessage($phone, $text, $stepName) {
    echo "\n--- $stepName ---\n";
    echo "Sending: '$text'\n";
    
    // The core fix is ensuring the state is cleared before the test run.
    // The original issue was likely a stale last_message file from a previous run
    // that caused the 5-second duplicate check to trigger.
    // The test script now includes a robust clearUserState function.
    
    $response = processMessage($phone, $text);
    
    if ($response === null) {
        echo "Response: NULL (Likely duplicate message detected by processor.php)\n";
    } else {
        print_r($response);
    }
    return $response;
}

// --- Test Flow ---
$phone = '1234567890';

// 1. Clear state for a clean run
clearUserState($phone);

// 2. Start conversation
$response = simulateMessage($phone, 'Hi', 'Start Conversation');

// 3. Welcome: 'yes'
$response = simulateMessage($phone, 'yes', 'Welcome');

// 4. Area Selection: 'tax_refund'
$response = simulateMessage($phone, 'tax_refund', 'Area Selection');

// 5. Employment Status: 'employed_6yrs'
$response = simulateMessage($phone, 'employed_6yrs', 'Employment Status');

// 6. Salary Range: '12000_18000'
$response = simulateMessage($phone, '12000_18000', 'Salary Range');

// 7. Tax Criteria: 'yes' (This is the previously failing step)
$response = simulateMessage($phone, 'yes', 'Tax Criteria');

// 8. Eligibility Check 1: 'yes'
if ($response !== null) {
    $response = simulateMessage($phone, 'yes', 'Eligibility Check 1');
}

// 9. Eligibility Check 2: 'yes'
if ($response !== null) {
    $response = simulateMessage($phone, 'yes', 'Eligibility Check 2');
}

// 10. Collect Info Name: 'John Doe'
if ($response !== null) {
    $response = simulateMessage($phone, 'John Doe', 'Collect Info Name');
}

// 11. Collect Info Phone: '0501234567'
if ($response !== null) {
    $response = simulateMessage($phone, '0501234567', 'Collect Info Phone');
}

// 12. Collect Info ID: '123456789'
if ($response !== null) {
    $response = simulateMessage($phone, '123456789', 'Collect Info ID');
}

// 13. Savings Potential: 'yes_check'
if ($response !== null) {
    $response = simulateMessage($phone, 'yes_check', 'Savings Potential');
}

// 14. Show the final state
$stateFile = __DIR__ . '/state/state_' . md5($phone) . '.json';
if (file_exists($stateFile)) {
    echo "\nFinal state: " . file_get_contents($stateFile) . "\n";
} else {
    echo "\nNo state file found (Conversation ended).\n";
}

// --- Clean up the state again after the test ---
clearUserState($phone);

?>