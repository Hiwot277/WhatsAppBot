<?php
require_once __DIR__ . '/scripts.php';

function testEligibilityCheck1() {
    echo "Testing eligibility_check_1 logic...\n";

    // Test Case 1: Input 'yes'
    $state = ['step' => 'eligibility_check_1', 'phone_number' => '1234567890'];
    $input = 'yes';
    echo "Input: $input\n";
    // runScripts expects &$from, &$text, array &$state
    $from = '1234567890';
    $response = runScripts($from, $input, $state);
    
    if ($state['step'] === 'eligibility_check_2') {
        echo "[PASS] 'yes' leads to eligibility_check_2\n";
    } else {
        echo "[FAIL] 'yes' leads to " . $state['step'] . "\n";
    }

    // Test Case 2: Input 'no'
    $state = ['step' => 'eligibility_check_1', 'phone_number' => '1234567890'];
    $input = 'no';
    echo "Input: $input\n";
    $from = '1234567890';
    $response = runScripts($from, $input, $state);
    
    if ($state['step'] === 'eligibility_check_2') {
        echo "[PASS] 'no' leads to eligibility_check_2\n";
    } else {
        echo "[FAIL] 'no' leads to " . $state['step'] . "\n";
    }
}

testEligibilityCheck1();
?>
