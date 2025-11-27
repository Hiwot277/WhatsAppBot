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

function runScripts(&$from, &$text, array &$state) {
    // Initialize state if not set
    if (!isset($state['step'])) {
        $state = ['step' => 'welcome'];
    }

    $lc = strtolower(trim($text));
    
    // Log the input and current state for debugging
    error_log("Processing input: '$lc' with state: " . json_encode($state));
    
    // Handle restart command at any point
    if (in_array($lc, ['restart', 'start over', 'reset', 'hi', 'hello'])) {
        $state = ['step' => 'welcome'];
        error_log("Resetting to welcome state");
        return getWelcomeMessage();
    }

    try {
        // Main conversation flow
        $currentStep = $state['step'] ?? 'welcome';
        error_log("Current step: $currentStep");
        
        switch ($currentStep) {
            case 'welcome':
                error_log("Handling welcome step");
                return handleWelcome($state, $lc);
                
            case 'area_selection':
                error_log("Handling area selection");
                return handleAreaSelection($state, $lc);
                
            case 'employment_status':
                error_log("Handling employment status");
                return handleEmploymentStatus($state, $lc);
                
            case 'salary_range':
                error_log("Handling salary range");
                return handleSalaryRange($state, $lc);
                
            case 'tax_criteria':
                error_log("Handling tax criteria");
                return handleTaxCriteria($state, $lc);
                
            case 'eligibility_check_1':
                error_log("Handling eligibility check 1");
                return handleEligibilityCheck1($state, $lc);
                
            case 'eligibility_check_2':
                error_log("Handling eligibility check 2");
                return handleEligibilityCheck2($state, $lc);
                
            case 'collect_info':
                error_log("Handling collect info");
                return collectUserInfo($state, $lc);
                
            case 'savings_potential':
                error_log("Handling savings potential");
                return handleSavingsPotential($state, $lc);
                
            default:
                error_log("Unknown step: " . $currentStep);
                $state['step'] = 'welcome';
                return getWelcomeMessage();
        }
    } catch (Exception $e) {
        error_log("Error in runScripts: " . $e->getMessage());
        $state['step'] = 'welcome';
        return [
            'text' => "I encountered an error. Let's start over. How can I help you?",
            'buttons' => [
                ['id' => 'tax_refund', 'text' => 'Tax refund']
            ]
        ];
    }
}

/**
 * Welcome message with initial options
 */
function getWelcomeMessage() {
    return [
        'text' => "Hi, I'm Robin Hood - here to help you pay less and get more. Would you like to see where you can save money right now?",
        'buttons' => [
            ['id' => 'yes', 'text' => 'Yes, show me'],
            ['id' => 'no', 'text' => 'Not now']
        ]
    ];
}

/**
 * Handle welcome response
 */
function handleWelcome(&$state, $input) {
    if ($input === 'yes' || $input === '1') {
        $state['step'] = 'area_selection';
        return [
            'text' => "Great, let's get started! For which areas would you like to check how to save?",
            'buttons' => [
                ['id' => 'tax_refund', 'text' => 'Tax Refund']
            ]
        ];
    } else {
        return [
            'text' => "Feel free to come back when you're ready to save. Just say 'hi' to start again."
        ];
    }
}

/**
 * Handle area selection
 */
function handleAreaSelection(&$state, $input) {
    // Normalize input
    $input = strtolower(trim($input));
    
    // Check if the input is any variation of tax refund selection
    $taxRefundKeywords = ['tax_refund', 'taxrefund', 'tax', 'refund', '1', 'check tax', 'tax return'];
    
    if (in_array($input, $taxRefundKeywords) || strpos($input, 'tax') !== false || strpos($input, 'refund') !== false) {
        $state['step'] = 'employment_status';
        $state['selected_area'] = 'tax_refund';
        
        // Log the state transition
        error_log("Transitioning to employment_status step");
        
        return [
            'text' => "Great! So I can check, I'll ask a few short questions (answer them briefly - less than a minute). Are you:",
            'buttons' => [
                ['id' => 'employed_6yrs', 'text' => 'Employed 6+ yrs'],
                ['id' => 'employed_part', 'text' => 'Employed part'],
                ['id' => 'self_employed', 'text' => 'Self-employed']
            ]
        ];
    }
    
    // If we get here, the input wasn't recognized
    error_log("Unrecognized input in handleAreaSelection: " . $input);
    return [
        'text' => "I'm not sure which area you're referring to. Please select 'Tax refund' to continue:",
        'buttons' => [
            ['id' => 'tax_refund', 'text' => 'Tax refund']
        ]
    ];
}

/**
 * Handle employment status
 */
function handleEmploymentStatus(&$state, $input) {
    // Normalize input
    $input = strtolower(trim($input));
    
    // Log the received input
    error_log("handleEmploymentStatus received input: " . $input);
    
    // Define all possible employment status keywords
    $selfEmployedKeywords = ['self_employed', 'self-employed', 'self employed', 'self', 'own business'];
    $employed6yrsKeywords = ['employed_6yrs', 'employed 6+ yrs', '6+', '6+ years', 'six plus', 'six+', '6plus', '6 plus'];
    $employedPartKeywords = ['employed_part', 'employed part', 'part time', 'part-time', 'parttime'];
    
    // Determine which employment status was selected
    if (in_array($input, $selfEmployedKeywords) || strpos($input, 'self') !== false) {
        $state['employment_status'] = 'self_employed';
        error_log("User selected self-employed, moving to salary range");
    } 
    elseif (in_array($input, $employed6yrsKeywords) || strpos($input, '6') !== false || strpos($input, 'six') !== false) {
        $state['employment_status'] = 'employed_6yrs';
        error_log("User selected employed 6+ years, moving to salary range");
    }
    elseif (in_array($input, $employedPartKeywords) || strpos($input, 'part') !== false) {
        $state['employment_status'] = 'employed_part';
        error_log("User selected employed part-time, moving to salary range");
    }
    else {
        // If we get here, the input wasn't recognized
        error_log("Unrecognized employment status: " . $input);
        $state['employment_status'] = 'unknown';
        error_log("User selected unknown employment status, moving to salary range");
    }
    
    // All valid employment statuses proceed to salary range
    $state['step'] = 'salary_range';
    
    return [
        'text' => "What is your average salary in recent years?",
        'buttons' => [
            ['id' => 'under_8000', 'text' => 'Under 8,000'],
            ['id' => '8000_18000', 'text' => '8,000 - 18,000'],
            ['id' => 'over_18000', 'text' => 'Over 18,000']
        ]
    ];
}

/**
 * Handle salary range selection
 */
function handleSalaryRange(&$state, $input) {
    // Normalize input
    $input = strtolower(trim($input));
    
    // Define valid salary ranges
    $validRanges = ['under_8000', '8000_18000', 'over_18000'];
    
    // Check if input is a valid range ID
    if (in_array($input, $validRanges)) {
        $state['step'] = 'tax_criteria';
        $state['salary_range'] = $input;
        
        return [
            'text' => "Does any of the following apply to you?\n\n• I pay tax on my salary\n• I have a pension/compensation/provident fund/training fund\n• I have paid capital gains tax in the last 6 years\n• I had capital market transactions with profit/loss",
            'buttons' => [
                ['id' => 'yes', 'text' => 'Yes, I qualify'],
                ['id' => 'no', 'text' => 'No, none apply']
            ]
        ];
    }
    
    // If input is not a valid range, show the salary range question again
    return [
        'text' => "Please select your salary range:",
        'buttons' => [
            ['id' => 'under_8000', 'text' => 'Under 8,000'],
            ['id' => '8000_18000', 'text' => '8,000 - 18,000'],
            ['id' => 'over_18000', 'text' => 'Over 18,000']
        ]
    ];
}

/**
 * Handle tax criteria response
 */
function handleTaxCriteria(&$state, $input) {
    // Normalize input
    $input = strtolower(trim($input));
    
    if ($input === 'no' || $input === 'no, none') {
        $state['step'] = 'no_savings';
        return [
            'text' => "Thank you for choosing Robin Hood 🏹 It seems that you currently have no potential for savings in the area of tax refunds. Would you like to check another area?",
            'buttons' => [
                ['id' => 'yes', 'text' => 'Check other areas'],
                ['id' => 'no', 'text' => 'No thanks']
            ]
        ];
    }
    
    // Handle 'yes' or any other affirmative response
    if ($input === 'yes' || $input === 'yes, i qualify' || $input === '1') {
        $state['step'] = 'eligibility_check_1';
        return [
            'text' => "Do you have children, academic studies, insurance payments, or grants that could affect your eligibility for a refund?",
            'buttons' => [
                ['id' => 'yes', 'text' => 'Yes, I do'],
                ['id' => 'no', 'text' => 'No, none']
            ]
        ];
    }
    
    // If we get here, the input wasn't recognized
    return [
        'text' => "I'm not sure how to interpret that. Please select one of the options below:",
        'buttons' => [
            ['id' => 'yes', 'text' => 'Yes, I qualify'],
            ['id' => 'no', 'text' => 'No, none apply']
        ]
    ];
}

/**
 * Handle first eligibility check
 */
function handleEligibilityCheck1(&$state, $input) {
    if ($input === 'no') {
        $state['step'] = 'no_savings';
        return [
            'text' => "Thank you for checking with Robin Hood 🏹 Currently, no additional savings found. Check back later!",
            'buttons' => [
                ['id' => 'restart', 'text' => 'Start over']
            ]
        ];
    }
    
    $state['step'] = 'eligibility_check_2';
    return [
        'text' => "To confirm, you have children, studies, or insurance that could qualify you for additional tax benefits?",
        'buttons' => [
            ['id' => 'yes', 'text' => 'Yes, confirm'],
            ['id' => 'no', 'text' => 'No, I was mistaken']
        ]
    ];
}

/**
 * Handle second eligibility check
 */
function handleEligibilityCheck2(&$state, $input) {
    if ($input === 'no') {
        $state['step'] = 'no_savings';
        return [
            'text' => "No problem! If your situation changes, feel free to check again. Would you like to try another area?",
            'buttons' => [
                ['id' => 'yes', 'text' => 'Other areas'],
                ['id' => 'no', 'text' => 'Maybe later']
            ]
        ];
    }
    
    $state['step'] = 'collect_info';
    $state['info_needed'] = ['name', 'phone', 'id'];
    $state['collected_info'] = [];
    
    return [
        'text' => "Great! Let's collect some information to check your eligibility. What is your full name?"
    ];
}

/**
 * Collect user information
 */
function collectUserInfo(&$state, $input) {
    if (empty($state['collected_info']['name'])) {
        $state['collected_info']['name'] = $input;
        return [
            'text' => "Thanks! What's your phone number?"
        ];
    }
    
    if (empty($state['collected_info']['phone'])) {
        // Simple phone validation
        if (!preg_match('/^[0-9\-\+\(\)\s]{6,20}$/', $input)) {
            return [
                'text' => "Please enter a valid phone number (digits only, 6-20 characters):"
            ];
        }
        $state['collected_info']['phone'] = $input;
        return [
            'text' => "Almost done! What's your ID number?"
        ];
    }
    
    if (empty($state['collected_info']['id'])) {
        // Simple ID validation
        if (!preg_match('/^[0-9\-\s]{6,20}$/', $input)) {
            return [
                'text' => "Please enter a valid ID number (digits only, 6-20 characters):"
            ];
        }
        $state['collected_info']['id'] = $input;
        $state['step'] = 'savings_potential';
        
        return [
            'text' => "It looks like you have the potential to save a few hundred shekels a month. Want us to do a free in-depth check to make sure?",
            'buttons' => [
                ['id' => 'yes', 'text' => 'Yes, check for me'],
                ['id' => 'no', 'text' => 'Main menu']
            ]
        ];
    }
}

/**
 * Handle savings potential response
 */
function handleSavingsPotential(&$state, $input) {
    if ($input === 'yes') {
        // Here you would typically save the user's information to your database
        // and trigger any follow-up processes
        
        $state['step'] = 'thank_you';
        return [
            'text' => "Thank you for choosing Robin Hood 🏹 We will update you as soon as we find savings! Would you like to check other areas for potential savings?",
            'buttons' => [
                ['id' => 'yes', 'text' => 'Check other areas'],
                ['id' => 'no', 'text' => 'All set, thanks!']
            ]
        ];
    }
    
    // If user doesn't want to proceed, return to main menu
    $state['step'] = 'welcome';
    return getWelcomeMessage();
}