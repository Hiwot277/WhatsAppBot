<?php
require_once __DIR__ . '/scripts.php';

function testStep($stepName) {
    echo "Testing step: $stepName\n";
    $message = getCurrentStepMessage($stepName);
    
    if (isset($message['buttons'])) {
        foreach ($message['buttons'] as $button) {
            $title = $button['text'];
            $len = mb_strlen($title);
            echo "  Button ID: {$button['id']}, Title: '$title', Length: $len\n";
            
            if ($len > 20) {
                echo "  [FAIL] Button title too long! (> 20 chars)\n";
                exit(1);
            } else {
                echo "  [PASS] Length OK.\n";
            }
        }
    } else {
        echo "  No buttons in this step.\n";
    }
    
    if ($stepName === 'employment_status') {
        if (strpos($message['text'], '1. I have been employed') !== false) {
            echo "  [PASS] Text contains full descriptions.\n";
        } else {
            echo "  [FAIL] Text missing full descriptions.\n";
            exit(1);
        }
    }
    echo "\n";
}

// Test the modified steps
testStep('employment_status');
testStep('savings_potential');
testStep('confirmation');
testStep('no_savings');

echo "All tests passed!\n";
