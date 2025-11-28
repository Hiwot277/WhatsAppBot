<?php
/**
 * Handle salary range selection with improved input handling
 */
function handleSalaryRange(&$state, $input) {
    // Normalize input
    $input = strtolower(trim($input));
    
    // Map button text to IDs for better matching
    $rangeMap = [
        'under 8,000' => 'under_8000',
        'under 8000' => 'under_8000',
        '8,000 - 18,000' => '8000_18000',
        '8000-18000' => '8000_18000',
        'over 18,000' => 'over_18000',
        'over 18000' => 'over_18000',
        'under_8000' => 'under_8000',
        '8000_18000' => '8000_18000',
        'over_18000' => 'over_18000'
    ];
    
    // Check if input is a valid range text and get the corresponding ID
    $rangeId = $rangeMap[$input] ?? $input;
    
    // Define valid range IDs
    $validRanges = ['under_8000', '8000_18000', 'over_18000'];
    
    // Check if input is a valid range ID
    if (in_array($rangeId, $validRanges)) {
        $state['salary_range'] = $rangeId;
        $state[$state['step']] = $rangeId; // Store in current step for database
        $state['step'] = 'tax_criteria';
        
        return [
            'text' => "Does any of the following apply to you?\n\n• I pay tax on my salary\n• I have a pension/compensation/provident fund/training fund\n• I have paid capital gains tax in the last 6 years\n• I had capital market transactions with profit/loss",
            'buttons' => [
                ['id' => 'yes', 'text' => 'Yes, I qualify'],
                ['id' => 'no', 'text' => 'No, none apply']
            ]
        ];
    }
    
    // Log the unrecognized input for debugging
    error_log("Unrecognized salary range input: " . $input);
    
    // If input is not a valid range, show the salary range question again
    return [
        'text' => "Please select your salary range from the options below:",
        'buttons' => [
            ['id' => 'under_8000', 'text' => 'Under 8,000'],
            ['id' => '8000_18000', 'text' => '8,000 - 18,000'],
            ['id' => 'over_18000', 'text' => 'Over 18,000']
        ]
    ];
}

// For testing
/*
$state = ['step' => 'salary_range'];
$testInputs = ['Under 8,000', '8000-18000', 'over 18000', 'invalid'];

foreach ($testInputs as $input) {
    echo "Testing input: $input\n";
    $result = handleSalaryRange($state, $input);
    echo "State after: " . json_encode($state, JSON_PRETTY_PRINT) . "\n";
    echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
}
*/
