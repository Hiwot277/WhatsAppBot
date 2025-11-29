<?php
/**
 * WhatsApp Bot Script for Robin Hood Tax Refund Service
 * 
 * This script handles the conversation flow for the WhatsApp bot, implementing
 * interactive buttons for user responses and maintaining conversation state.
 * 
 * Key Features:
 * - Interactive WhatsApp buttons for better UX
 * - Stateful conversation flow
 * - Input validation
 * - Clear navigation structure
 */

// Include necessary files
require_once __DIR__ . '/db_utils.php';

// --- Conversation Flow Definition (Based on txt file) ---
// 1. welcome (Initial Entry Point)
// 2. area_selection (Area Selection - Tax refund)
// 3. employment_status (Employment Status Question)
// 4. salary_range (Average Salary Question)
// 5. tax_criteria (Tax/Financial Criteria Check)
// 6. eligibility_check_1 (Additional Eligibility Check - First Level)
// 7. eligibility_check_2 (Additional Eligibility Check - Second Level)
// 8. collect_info_name (Collect Full Name)
// 9. collect_info_phone (Collect Phone Number)
// 10. collect_info_id (Collect ID Number)
// 11. savings_potential (Savings Potential Assessment)
// 12. confirmation (Confirmation and Follow-up)
// 13. no_savings (No Savings Potential - Exit Message)

// --- Helper Functions for Messages ---

/**
 * Get the appropriate message and buttons for the current step
 */
function getCurrentStepMessage($step, $state = []) {
    switch ($step) {
        case 'welcome':
            return [
                'text' => "Hi, I'm Robin Hood - here to help you pay less and get more. Would you like to see where you can save money right now?",
                'buttons' => [
                    ['id' => 'yes', 'text' => 'Yes'],
                    ['id' => 'no', 'text' => 'No']
                ]
            ];
            
        case 'area_selection':
            return [
                'text' => "Great, let's get started! For which areas would you like to check how to save?",
                'buttons' => [
                    ['id' => 'tax_refund', 'text' => 'Tax refund']
                ]
            ];
            
        case 'employment_status':
            return [
                'text' => "Great! So I can check, I'll ask a few short questions (answer them briefly - less than a minute). Are you:\n\n1. I have been employed for the entire last 6 years\n2. I was an employee for part of my life (referring to a period of years)\n3. I am self-employed only",
                'buttons' => [
                    ['id' => 'employed_6yrs', 'text' => 'Employed 6 Years'],
                    ['id' => 'employed_part', 'text' => 'Partially Employed'],
                    ['id' => 'self_employed', 'text' => 'Self-Employed Only']
                ]
            ];
            
        case 'salary_range':
            return [
                'text' => "What is your average salary in recent years?",
                'buttons' => [
                    ['id' => 'less_than_8000', 'text' => '<8000'],
                    ['id' => '8000_18000', 'text' => '8000-18000'],
                    ['id' => 'more_than_18000', 'text' => '>18000']
                ]
            ];
            
        case 'tax_criteria':
            return [
                'text' => "Does any of the following apply to you?\n\n• I pay tax on my salary\n• I have a pension/compensation/provident fund/training fund. I have paid tax in the last 6 years\n• I have paid capital gains tax in the last 6 years\n• I had capital market transactions that caused me a profit/loss in the last 6 years",
                'buttons' => [
                    ['id' => 'yes', 'text' => 'Yes'],
                    ['id' => 'no', 'text' => 'No']
                ]
            ];
            
        case 'eligibility_check_1':
            return [
                'text' => "Do you have children, academic studies, insurance payments, or grants you have received that could affect your eligibility for a refund?",
                'buttons' => [
                    ['id' => 'yes', 'text' => 'Yes'],
                    ['id' => 'no', 'text' => 'No']
                ]
            ];
            
        case 'eligibility_check_2':
            // Note: The flow text file has this as a duplicate of check 1, but the original code used different questions.
            // Sticking to the flow text file's question for consistency, but the logic is to confirm eligibility.
            return [
                'text' => "Do you have children, academic studies, insurance payments, or grants you have received that could affect your eligibility for a refund?",
                'buttons' => [
                    ['id' => 'yes', 'text' => 'Yes'],
                    ['id' => 'no', 'text' => 'No']
                ]
            ];
            
        case 'collect_info_name':
            return [
                'text' => "Please provide your Full Name:"
            ];
            
        case 'collect_info_phone':
            return [
                'text' => "Please provide your Phone Number:"
            ];
            
        case 'collect_info_id':
            return [
                'text' => "Please provide your ID Number:"
            ];
            
        case 'savings_potential':
            return [
                'text' => "It looks like you have the potential to save a few hundred shekels a month. Want us to do a free in-depth check to make sure?",
                'buttons' => [
                    ['id' => 'yes_check', 'text' => 'Yes, check for me'],
                    ['id' => 'main_menu', 'text' => 'Back to Main Menu']
                ]
            ];
            
        case 'confirmation':
            return [
                'text' => "Thank you for choosing Robin Hood 🏹 We will update you as soon as we find savings! Shall we continue to save in other areas?",
                'buttons' => [
                    ['id' => 'main_menu', 'text' => 'Back to Main Menu']
                ]
            ];
            
        case 'no_savings':
            return [
                'text' => "Thank you for choosing Robin Hood 🏹 It seems that you currently have no potential for savings in the area of tax refunds, so why not check out another area?",
                'buttons' => [
                    ['id' => 'main_menu', 'text' => 'Back to Main Menu']
                ]
            ];
            
        default:
            return [
                'text' => "I'm sorry, I encountered an error. Please send 'start' to begin again.",
                'end_conversation' => true
            ];
    }
}

// --- Handler Functions ---

function handleWelcome(&$state, $input) {
    if ($input === 'yes') {
        $state['step'] = 'area_selection';
        // Save response to DB
        saveUserResponse($state['phone_number'], 'welcome_response', $input);
        return getCurrentStepMessage('area_selection');
    } else { // 'no' or any other non-yes button
        // End flow
        $state['step'] = 'exit_flow';
        saveUserResponse($state['phone_number'], 'welcome_response', $input);
        return [
            'text' => "Feel free to come back when you're ready to save. Just say 'start' to begin again."
        ];
    }
}

function handleAreaSelection(&$state, $input) {
    $normalized = strtolower(trim($input));
    $normalized = str_replace(' ', '_', $normalized);
    if ($normalized === 'tax_refund') {
        $state['step'] = 'employment_status';
        $state['selected_area'] = 'tax_refund';
        // Save response to DB
        saveUserResponse($state['phone_number'], 'selected_area', $input);
        return getCurrentStepMessage('employment_status');
    }
    // Invalid input. Return null to trigger the generic invalid input message in runScripts.
    return null;
}

function handleEmploymentStatus(&$state, $input) {
    if ($input === 'self_employed') {
        // End flow (not applicable)
        $state['step'] = 'no_savings';
        saveUserResponse($state['phone_number'], 'employment_status', $input);
        return getCurrentStepMessage('no_savings');
    }
    
    if (in_array($input, ['employed_6yrs', 'employed_part'])) {
        $state['step'] = 'salary_range';
        saveUserResponse($state['phone_number'], 'employment_status', $input);
        return getCurrentStepMessage('salary_range');
    }
    // Invalid input handled by runScripts
    return null;
}

function handleSalaryRange(&$state, $input) {
    $validRanges = ['less_than_8000', '8000_18000', 'more_than_18000'];
    
    if (in_array($input, $validRanges)) {
        $state['step'] = 'tax_criteria';
        saveUserResponse($state['phone_number'], 'salary_range', $input);
        return getCurrentStepMessage('tax_criteria');
    }
    // Invalid input handled by runScripts
    return null;
}

function handleTaxCriteria(&$state, $input) {
    if ($input === 'yes') {
        $state['step'] = 'eligibility_check_1';
        saveUserResponse($state['phone_number'], 'tax_criteria', $input);
        return getCurrentStepMessage('eligibility_check_1');
    }
    
    if ($input === 'no') {
        $state['step'] = 'no_savings';
        saveUserResponse($state['phone_number'], 'tax_criteria', $input);
        return getCurrentStepMessage('no_savings');
    }
    // Invalid input handled by runScripts
    return null;
}

function handleEligibilityCheck1(&$state, $input) {
    if ($input === 'yes') {
        $state['step'] = 'eligibility_check_2';
        saveUserResponse($state['phone_number'], 'eligibility_check_1', $input);
        return getCurrentStepMessage('eligibility_check_2');
    }
    
    if ($input === 'no') {
        $state['step'] = 'no_savings';
        saveUserResponse($state['phone_number'], 'eligibility_check_1', $input);
        return getCurrentStepMessage('no_savings');
    }
    // Invalid input handled by runScripts
    return null;
}

function handleEligibilityCheck2(&$state, $input) {
    if ($input === 'yes') {
        $state['step'] = 'collect_info_name';
        saveUserResponse($state['phone_number'], 'eligibility_check_2', $input);
        return getCurrentStepMessage('collect_info_name');
    }
    
    if ($input === 'no') {
        $state['step'] = 'no_savings';
        saveUserResponse($state['phone_number'], 'eligibility_check_2', $input);
        return getCurrentStepMessage('no_savings');
    }
    // Invalid input handled by runScripts
    return null;
}

function handleCollectInfoName(&$state, $input) {
    // Free text input, no button validation needed
    if (!empty($input)) {
        $state['full_name'] = $input;
        $state['step'] = 'collect_info_phone';
        saveUserResponse($state['phone_number'], 'full_name', $input);
        return getCurrentStepMessage('collect_info_phone');
    }
    // If input is empty, re-ask the question
    return getCurrentStepMessage('collect_info_name');
}

function handleCollectInfoPhone(&$state, $input) {
    // Simple phone validation (digits, +, -, spaces)
    if (preg_match('/^[\d\s\-\+]{6,20}$/', $input)) {
        $state['phone_number_input'] = $input; // Store the user's input phone number
        $state['step'] = 'collect_info_id';
        saveUserResponse($state['phone_number'], 'phone_number_input', $input);
        return getCurrentStepMessage('collect_info_id');
    }
    
    // Invalid phone number format
    return [
        'text' => "Please enter a valid phone number (digits, +, -, or spaces). " . getCurrentStepMessage('collect_info_phone')['text']
    ];
}

function handleCollectInfoID(&$state, $input) {
    // Simple ID validation (digits, -, spaces)
    if (preg_match('/^[\d\s\-]{6,20}$/', $input)) {
        $state['id_number'] = $input;
        $state['step'] = 'savings_potential';
        saveUserResponse($state['phone_number'], 'id_number', $input);
        return getCurrentStepMessage('savings_potential');
    }
    
    // Invalid ID number format
    return [
        'text' => "Please enter a valid ID number (digits, -, or spaces). " . getCurrentStepMessage('collect_info_id')['text']
    ];
}

function handleSavingsPotential(&$state, $input) {
    if ($input === 'yes_check') {
        $state['step'] = 'confirmation';
        saveUserResponse($state['phone_number'], 'savings_potential_response', $input);
        return getCurrentStepMessage('confirmation');
    }
    
    if ($input === 'main_menu') {
        $state['step'] = 'area_selection';
        saveUserResponse($state['phone_number'], 'savings_potential_response', $input);
        return getCurrentStepMessage('area_selection');
    }
    // Invalid input handled by runScripts
    return null;
}

function handleConfirmation(&$state, $input) {
    if ($input === 'main_menu') {
        $state['step'] = 'area_selection';
        saveUserResponse($state['phone_number'], 'confirmation_response', $input);
        return getCurrentStepMessage('area_selection');
    }
    // Invalid input handled by runScripts
    return null;
}

function handleNoSavings(&$state, $input) {
    if ($input === 'main_menu') {
        $state['step'] = 'area_selection';
        saveUserResponse($state['phone_number'], 'no_savings_response', $input);
        return getCurrentStepMessage('area_selection');
    }
    // Invalid input handled by runScripts
    return null;
}


// --- Main Script Logic ---

function runScripts(&$from, &$text, array &$state) {
    $lc = strtolower(trim($text));
    
    // Log the input and current state for debugging
    error_log("Processing input: '$lc' with state: " . json_encode($state));
    
    // --- Input Triggers: 'hey', 'hi', 'start', or 'restart' ---
    // The state reset is handled in processor.php. If the state is 'welcome', we just return the welcome message.
    if ($state['step'] === 'welcome' && in_array($lc, ['hey', 'hi', 'hello', 'start', 'restart'])) {
        error_log("Restarting conversation for $from");
        return getCurrentStepMessage('welcome');
    }
    
    // If the state is empty (which should only happen if processor.php didn't reset it)
    if (!isset($state['step'])) {
        $state['step'] = 'welcome';
        return getCurrentStepMessage('welcome');
    }

    try {
        $currentStep = $state['step'] ?? 'welcome';
        error_log("Current step: $currentStep");
        
        // Define valid button IDs for each step
        $validButtons = [
            'welcome' => ['yes', 'no'],
            'area_selection' => ['tax_refund', 'tax refund'],
            'employment_status' => ['employed_6yrs', 'employed_part', 'self_employed'],
            'salary_range' => ['less_than_8000', '8000_18000', 'more_than_18000'],
            'tax_criteria' => ['yes', 'no'],
            'eligibility_check_1' => ['yes', 'no'],
            'eligibility_check_2' => ['yes', 'no'],
            'savings_potential' => ['yes_check', 'main_menu'],
            'confirmation' => ['main_menu'],
            'no_savings' => ['main_menu'],
            // Free text steps: collect_info_name, collect_info_phone, collect_info_id
        ];
        
        // --- Invalid Input Handling ---
        $isFreeTextStep = in_array($currentStep, ['collect_info_name', 'collect_info_phone', 'collect_info_id']);
        $isButtonInput = isset($validButtons[$currentStep]) && in_array($lc, $validButtons[$currentStep]);
        
        // If it's a button step and the input is not a valid button ID, return invalid message.
        if (!$isFreeTextStep && !$isButtonInput) {
            // Invalid input for a button-based step
            error_log("Invalid input for button step $currentStep: '$lc'");
            return [
                'text' => "Please use the buttons or send start."
            ];
        }
        
        // If it's a free text step, we still check for 'start'/'restart'
        if ($isFreeTextStep && in_array($lc, ['hey', 'hi', 'hello', 'start', 'restart'])) {
            // This case is handled by the initial restart check, but good to have a safeguard.
            // The initial check should have caught this, so we proceed to the handler.
        }
        
        // --- Routing to the appropriate handler ---
        $handlerMap = [
            'welcome' => 'handleWelcome',
            'area_selection' => 'handleAreaSelection',
            'employment_status' => 'handleEmploymentStatus',
            'salary_range' => 'handleSalaryRange',
            'tax_criteria' => 'handleTaxCriteria',
            'eligibility_check_1' => 'handleEligibilityCheck1',
            'eligibility_check_2' => 'handleEligibilityCheck2',
            'collect_info_name' => 'handleCollectInfoName',
            'collect_info_phone' => 'handleCollectInfoPhone',
            'collect_info_id' => 'handleCollectInfoID',
            'savings_potential' => 'handleSavingsPotential',
            'confirmation' => 'handleConfirmation',
            'no_savings' => 'handleNoSavings',
            'exit_flow' => 'handleNoSavings' // Use no_savings handler for main menu return logic
        ];
        
        if (isset($handlerMap[$currentStep])) {
            $handler = $handlerMap[$currentStep];
            error_log("Calling handler: $handler");
            $reply = $handler($state, $lc);
            
            // If handler returns null, it means the input was invalid for a button step, 
            // which should have been caught by the check above.
            // If the handler returns null, it means the handler itself determined the input was invalid
            // and did not handle the state transition. We should return the invalid input message.
            if ($reply === null) {
                return [
                    'text' => "Please use the buttons or send start."
                ];
            }
            
            return $reply;
        }
        
        // If we get here, we don't have a handler for this step
        error_log("No handler found for step: $currentStep");
        return [
            'text' => "I'm sorry, I encountered an error. Please send 'start' to begin again.",
            'end_conversation' => true
        ];
        
    } catch (Exception $e) {
        error_log("Error in runScripts: " . $e->getMessage());
        return [
            'text' => "I'm sorry, I encountered an error. Please send 'start' to begin again.",
            'end_conversation' => true
        ];
    }
}
