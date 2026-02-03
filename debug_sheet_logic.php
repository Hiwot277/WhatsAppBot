<?php
// Mock database functions
function getUserResponse($phone, $field) {
    if ($phone === '0500000001') return 'fast_loans';
    return 'tax_refund';
}

// Logic from db_utils.php
function testLogic($phoneNumber) {
    $selectedArea = getUserResponse($phoneNumber, 'selected_area');
    $sheetName = 'Tax Refund'; // Default sheet name

    if ($selectedArea === 'fast_loans') {
        $sheetName = 'fast_loans';
    }
    
    echo "Phone: $phoneNumber, Selected Area: $selectedArea, Sheet Name: $sheetName\n";
}

testLogic('0500000001'); // Should be 'fast loans'
testLogic('0500000002'); // Should be 'Tax Refund'
