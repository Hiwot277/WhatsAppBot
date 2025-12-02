<?php
/**
 * WhatsApp Bot Script for Robin Hood Tax Refund Service
 * 
 * This script handles the conversation flow for the WhatsApp bot, implementing
 * interactive buttons for user responses and maintaining conversation state.
 */

require_once __DIR__ . '/db_utils.php';

// --- Helper Functions for Messages ---

function getCurrentStepMessage($step, $state = []) {
    switch ($step) {
        case 'welcome':
            return [
                'text' => "Hi, I'm Robin Hood - here to help you pay less and get more. Would you like to see where you can save money right now?",
                'buttons' => [
                    ['id' => 'lets_start', 'text' => 'tell me how it works']
                ]
            ];

        case 'intro_explainer':
            // This step seems skipped in the new flow or merged. 
            // The txt says "tell me how it works" -> Step 2 (Area Selection).
            // So we might not need this, or we can keep it as a passthrough if needed.
            // For now, I'll align it with the previous logic but it might be bypassed.
            return [
                'text' => "Have you always wanted to save money but didn't know where to start? We're here for you! We are a free, automatic system for checking eligibility for discounts and offers that help you save a lot - on interest, tax refunds and even bills, where to start?",
                'buttons' => [
                    ['id' => 'tax_refund', 'text' => 'Tax refund']
                ]
            ];
            
        case 'area_selection':
            return [
                'text' => "Have you always wanted to save money but didn't know where to start? We're here for you! We are a free, automatic system for checking eligibility for discounts and offers that help you save a lot - on interest, tax refunds and even bills, where to start?",
                'buttons' => [
                    ['id' => 'tax_refund', 'text' => 'Tax refund']
                ]
            ];
            
        case 'employment_status':
            return [
                'text' => "Great! So I can check, I'll ask a few short questions (answer them briefly - less than a minute). Are you:\n\n1. I have been employed for the entire last 6 years\n2. I was an employee for part of my life (referring to a period of years)\n3. I am self-employed only",
                'buttons' => [
                    ['id' => 'employed_6yrs', 'text' => '1'],
                    ['id' => 'employed_part', 'text' => '2'],
                    ['id' => 'self_employed', 'text' => '3']
                ]
            ];
            
        case 'salary_range':
            return [
                'text' => "What is your average salary in recent years?",
                'buttons' => [
                    ['id' => 'less_than_8000', 'text' => 'Less than 8,000'],
                    ['id' => '8000_18000', 'text' => '8,000–18,000'],
                    ['id' => 'more_than_18000', 'text' => 'More than 18,000']
                ]
            ];
            
        case 'tax_criteria':
            return [
                'text' => "Does any of the following apply to you?\n\n- I pay tax on my salary\n- I have a pension/compensation/provident fund/training fund. I have paid tax in the last 6 years\n- I have paid capital gains tax in the last 6 years\n- I had capital market transactions that caused me a profit/loss in the last 6 years",
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
            return [
                'text' => "Do you have children, academic studies, insurance payments, or grants you have received that could affect your eligibility for a refund?",
                'buttons' => [
                    ['id' => 'yes', 'text' => 'Yes'],
                    ['id' => 'no', 'text' => 'No']
                ]
            ];
            
        case 'collect_info_name':
            return [
                'text' => "What is your full name?"
            ];
            
        case 'collect_info_phone':
            return [
                'text' => "What is your phone number?"
            ];
            
        case 'collect_info_id':
            return [
                'text' => "What is your ID number?"
            ];
            
        case 'savings_potential':
            return [
                'text' => "It looks like you have the potential to save a few hundred shekels a month. Want us to do a free in-depth check to make sure?",
                'buttons' => [
                    ['id' => 'yes_check', 'text' => 'Yes, check for me'],
                    ['id' => 'main_menu', 'text' => 'Main Menu']
                ]
            ];

        case 'tax_refund_example':
            return [
                'text' => "Here is a quick example of how a tax refund works:\nIf you worked during the last 6 years and paid more tax than required, the state may owe you money back.\nRefunds can come from: employment gaps, studies, children, pension deposits, capital market activity, and many other factors.\nWe’ll now check your case in detail and update you with the amount you deserve.",
                'buttons' => [
                    ['id' => 'continue', 'text' => 'Continue']
                ]
            ];
            
        case 'confirmation':
            return [
                'text' => "Thank you for choosing Robin Hood 🏹 We will update you as soon as we find savings! Shall we continue to save in other areas?",
                'buttons' => [
                    ['id' => 'main_menu', 'text' => 'Main Menu']
                ]
            ];
            
        case 'no_savings':
            return [
                'text' => "Thank you for choosing Robin Hood 🏹 It seems that you currently have no potential for savings in the area of tax refunds, so why not check out another area?",
                'buttons' => [
                    ['id' => 'main_menu', 'text' => 'Main Menu']
                ]
            ];
            
        default:
            return [
                'text' => "Sorry, I encountered an error. Please send 'start' to restart.",
                'end_conversation' => true
            ];
    }
}

// --- Handler Functions ---

function handleWelcome(&$state, $input) {
    if ($input === 'lets_start' || $input === 'tell me how it works') {
        $state['step'] = 'area_selection';
        saveUserResponse($state['phone_number'], 'welcome_response', $input);
        return getCurrentStepMessage('area_selection');
    }
    return null;
}

function handleIntroExplainer(&$state, $input) {
    if ($input === 'lets_start') {
        $state['step'] = 'area_selection';
        return getCurrentStepMessage('area_selection');
    }
    return null;
}

            error_log("Invalid input for button step $currentStep: '$lc'");

function handleAreaSelection(&$state, $input) {
    $normalized = strtolower(trim($input));
    $normalized = str_replace(' ', '_', $normalized);
    if ($normalized === 'tax_refund') {
        $state['step'] = 'employment_status';
        $state['selected_area'] = 'tax_refund';
        saveUserResponse($state['phone_number'], 'selected_area', 'tax_refund');
        return getCurrentStepMessage('employment_status');
    }
    return null;
}

function handleEmploymentStatus(&$state, $input) {
    if ($input === 'self_employed' || $input === '3') {
        $state['step'] = 'no_savings';
        saveUserResponse($state['phone_number'], 'employment_status', 'self_employed');
        return getCurrentStepMessage('no_savings');
    }
    
    if (in_array($input, ['employed_6yrs', 'employed_part', '1', '2'])) {
        $state['step'] = 'salary_range';
        saveUserResponse($state['phone_number'], 'employment_status', $input);
        return getCurrentStepMessage('salary_range');
    }
    return null;
}

function handleSalaryRange(&$state, $input) {
    $validRanges = ['less_than_8000', '8000_18000', 'more_than_18000', 'less than 8,000', '8,000–18,000', 'more than 18,000'];
    
    if (in_array($input, $validRanges)) {
        $state['step'] = 'tax_criteria';
        saveUserResponse($state['phone_number'], 'salary_range', $input);
        return getCurrentStepMessage('tax_criteria');
    }
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
    return null;
}

function handleEligibilityCheck1(&$state, $input) {
    if ($input === 'yes') {
        $state['step'] = 'eligibility_check_2';
        saveUserResponse($state['phone_number'], 'eligibility_check_1', $input);
        return getCurrentStepMessage('eligibility_check_2');
    }
    
    if ($input === 'no') {
        $state['step'] = 'eligibility_check_2';
        saveUserResponse($state['phone_number'], 'eligibility_check_1', $input);
        return getCurrentStepMessage('eligibility_check_2');
    }
    return null;
}

function handleEligibilityCheck2(&$state, $input) {
    if ($input === 'yes') {
        $state['step'] = 'collect_info_name';
        saveUserResponse($state['phone_number'], 'eligibility_check_2', $input);
        return getCurrentStepMessage('collect_info_name');
    }
    
    if ($input === 'no') {
        $state['step'] = 'collect_info_name';
        saveUserResponse($state['phone_number'], 'eligibility_check_2', $input);
        return getCurrentStepMessage('collect_info_name');
    }
    return null;
}

function handleCollectInfoName(&$state, $input) {
    if (!empty($input)) {
        $state['full_name'] = $input;
        $state['step'] = 'collect_info_phone';
        saveUserResponse($state['phone_number'], 'full_name', $input);
        return getCurrentStepMessage('collect_info_phone');
    }
    return getCurrentStepMessage('collect_info_name');
}

function handleCollectInfoPhone(&$state, $input) {
    if (preg_match('/^[\d\s\-\+]{6,20}$/', $input)) {
        $state['phone_num_2'] = $input;
        $state['step'] = 'collect_info_id';
        saveUserResponse($state['phone_number'], 'phone_num_2', $input);
        return getCurrentStepMessage('collect_info_id');
    }
    return [
        'text' => "Please enter a valid phone number (digits, +, -, or spaces). " . getCurrentStepMessage('collect_info_phone')['text']
    ];
}

function handleCollectInfoID(&$state, $input) {
    error_log("handleCollectInfoID called with input: '$input'");
    if (preg_match('/^[\d\s\-]{6,20}$/', $input)) {
        $state['id_number'] = $input;
        $state['step'] = 'savings_potential';
        error_log("Saving ID number: $input for phone: " . $state['phone_number']);
        saveUserResponse($state['phone_number'], 'id_number', $input);
        return getCurrentStepMessage('savings_potential');
    }
    return [
        'text' => "Please enter a valid ID number (digits, -, or spaces). " . getCurrentStepMessage('collect_info_id')['text']
    ];
}

function handleSavingsPotential(&$state, $input) {
    if ($input === 'yes_check' || $input === 'yes, check for me') {
        $state['step'] = 'tax_refund_example';
        saveUserResponse($state['phone_number'], 'savings_potential_response', 'yes_check');
        return getCurrentStepMessage('tax_refund_example');
    }
    
    if ($input === 'main_menu' || $input === 'main menu') {
        $state['step'] = 'area_selection';
        saveUserResponse($state['phone_number'], 'savings_potential_response', 'main_menu');
        return getCurrentStepMessage('area_selection');
    }
    return null;
}

function handleTaxRefundExample(&$state, $input) {
    if ($input === 'continue') {
        $state['step'] = 'confirmation';
        return getCurrentStepMessage('confirmation');
    }
    return null;
}

function handleConfirmation(&$state, $input) {
    if ($input === 'main_menu' || $input === 'main menu') {
        $state['step'] = 'area_selection';
        saveUserResponse($state['phone_number'], 'confirmation_response', 'main_menu');
        return getCurrentStepMessage('area_selection');
    }
    return null;
}

function handleNoSavings(&$state, $input) {
    if ($input === 'main_menu' || $input === 'main menu') {
        $state['step'] = 'area_selection';
        saveUserResponse($state['phone_number'], 'no_savings_response', 'main_menu');
        return getCurrentStepMessage('area_selection');
    }
    return null;
}


// --- Main Script Logic ---

function runScripts(&$from, &$text, array &$state) {
    $lc = strtolower(trim($text));
    error_log("Processing input: '$lc' with state: " . json_encode($state));
    
    if (in_array($lc, ['hey', 'hi', 'hello', 'start', 'restart'])) {
        $state['step'] = 'welcome';
        return getCurrentStepMessage('welcome');
    }
    
    if (!isset($state['step'])) {
        $state['step'] = 'welcome';
        return getCurrentStepMessage('welcome');
    }

    try {
        $currentStep = $state['step'] ?? 'welcome';
        
        $validButtons = [
            'welcome' => ['lets_start', 'tell me how it works'],
            'intro_explainer' => ['tax_refund', 'tax refund'],
            'area_selection' => ['tax_refund', 'tax refund'],
            'employment_status' => ['employed_6yrs', 'employed_part', 'self_employed', '1', '2', '3'],
            'salary_range' => ['less_than_8000', '8000_18000', 'more_than_18000', 'less than 8,000', '8,000–18,000', 'more than 18,000'],
            'tax_criteria' => ['yes', 'no'],
            'eligibility_check_1' => ['yes', 'no'],
            'eligibility_check_2' => ['yes', 'no'],
            'savings_potential' => ['yes_check', 'main_menu', 'yes, check for me', 'main menu'],
            'tax_refund_example' => ['continue'],
            'confirmation' => ['main_menu', 'main menu'],
            'no_savings' => ['main_menu', 'main menu'],
        ];
        
        $isFreeTextStep = in_array($currentStep, ['collect_info_name', 'collect_info_phone', 'collect_info_id']);
        $isButtonInput = isset($validButtons[$currentStep]) && in_array($lc, $validButtons[$currentStep]);
        
        if (!$isFreeTextStep && !$isButtonInput) {
            error_log("Invalid input for button step $currentStep: '$lc'");
            return [
                'text' => "   Please use the buttons or send 'Start' "
            ];
        }
        
        $handlerMap = [
            'welcome' => 'handleWelcome',
            'intro_explainer' => 'handleIntroExplainer',
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
            'tax_refund_example' => 'handleTaxRefundExample',
            'confirmation' => 'handleConfirmation',
            'no_savings' => 'handleNoSavings',
            'exit_flow' => 'handleNoSavings'
        ];
        
        if (isset($handlerMap[$currentStep])) {
            $handler = $handlerMap[$currentStep];
            $reply = $handler($state, $lc);
            
            if ($reply === null) {
                return [
                    'text' => "   Please use the buttons or send 'Start' "
                ];
            }
            return $reply;
        }
        
        error_log("No handler found for step: $currentStep");
        return [
            'text' => "   אנא השתמש בכפתורים או שלח 'Start' ",
            'end_conversation' => true
        ];
        
    } catch (Exception $e) {
        error_log("Error in runScripts: " . $e->getMessage());
        return [
            'text' => "   אנא השתמש בכפתורים או שלח 'Start' ",
            'end_conversation' => true
        ];
    }
}
