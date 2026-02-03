<?php
// Mock DB functions for testing logic
$mockDb = [];

function saveUserResponse($phone, $field, $value) {
    global $mockDb;
    $mockDb[$phone][$field] = $value;
    echo "[DB] Saved $field = $value for $phone\n";
    
    // Simulate sendToGoogleSheet logic immediately to show what would happen
    $selectedArea = $mockDb[$phone]['selected_area'] ?? 'unknown';
    $sheetName = 'Tax Refund';
    if ($selectedArea === 'fast_loans') {
        $sheetName = 'fast_loans';
    }
    echo "[SHEET LOGIC] If sent now, Sheet Name would be: '$sheetName'\n\n";
}

function getCurrentStepMessage($step) {
    return "Next step: $step";
}

// Include the script logic (but we need to bypass the real DB require)
// So we'll just copy the relevant handler functions here for testing to ensure logic is identical
// OR we can just test the logic we see in the file. 
// Let's copy the EXACT logic from the file to be sure.

function handleIntroExplainer(&$state, $input) {
    $normalized = strtolower(trim($input));
    $normalized = str_replace(' ', '_', $normalized);
    
    if ($normalized === 'fast_loans' || strpos($input, 'ריביות והלוואות') !== false) {
        $state['step'] = 'loans_credit_card';
        $state['selected_area'] = 'fast_loans';
        saveUserResponse($state['phone_number'], 'selected_area', 'fast_loans');
        return getCurrentStepMessage('loans_credit_card');
    }
}

function handleAreaSelection(&$state, $input) {
    $normalized = strtolower(trim($input));
    $normalized = str_replace(' ', '_', $normalized);

    if ($normalized === 'fast_loans' || strpos($input, 'ריביות והלוואות') !== false) {
        $state['step'] = 'loans_credit_card';
        $state['selected_area'] = 'fast_loans';
        saveUserResponse($state['phone_number'], 'selected_area', 'fast_loans');
        return getCurrentStepMessage('loans_credit_card');
    }
}

// TEST 1: Intro Explainer -> Fast Loans
echo "--- TEST 1: Intro Explainer ---\n";
$state1 = ['phone_number' => '0501111111', 'step' => 'intro_explainer'];
handleIntroExplainer($state1, 'fast_loans');

// TEST 2: Area Selection -> Fast Loans
echo "--- TEST 2: Area Selection ---\n";
$state2 = ['phone_number' => '0502222222', 'step' => 'area_selection'];
handleAreaSelection($state2, 'fast_loans');
