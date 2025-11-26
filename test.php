<?php
/**
 * Stage 1 — Script Logic (Bot Brain)
 * File: myscripts.php
 * Description: Contains the core conversation logic for the WhatsApp chatbot.
 * The main function, getReply, determines the bot's response based on the user's
 * message, user ID, and current state.
 */

// Define conversation states as constants for clarity
const STATE_START = 'start';
const STATE_AREA_SELECTION = 'area_selection';
const STATE_EMPLOYMENT_STATUS = 'employment_status';
const STATE_SALARY_QUESTION = 'salary_question';
const STATE_TAX_CRITERIA = 'tax_criteria';
const STATE_ELIGIBILITY_CHECK_1 = 'eligibility_check_1';
const STATE_ELIGIBILITY_CHECK_2 = 'eligibility_check_2';
const STATE_COLLECT_NAME = 'collect_name';
const STATE_COLLECT_PHONE = 'collect_phone';
const STATE_COLLECT_ID = 'collect_id';
const STATE_SAVINGS_ASSESSMENT = 'savings_assessment';
const STATE_CONFIRMATION = 'confirmation';
const STATE_NO_SAVINGS = 'no_savings';

/**
 * The main function for the bot's conversation logic.
 *
 * @param string $msg The user's incoming message text.
 * @param string $userId The unique identifier for the user (e.g., phone number).
 * @param string $state The user's current conversation state.
 * @return array The bot's reply structure.
 */
function getReply(string $msg, string $userId, string $state): array
{
    // Normalize message for keyword matching and button ID comparison
    $msg = strtolower(trim($msg));

    // --- Global Keyword Matching (State-Agnostic) ---

    // Help/Reset keyword
    if (in_array($msg, ['help', 'start', 'hi', 'hello', 'reset'])) {
        return handleStart();
    }

    // --- State-Specific Logic ---

    switch ($state) {
        case STATE_START:
            return handleStart();

        case STATE_AREA_SELECTION:
            return handleAreaSelection($msg);

        case STATE_EMPLOYMENT_STATUS:
            return handleEmploymentStatus($msg);

        case STATE_SALARY_QUESTION:
            return handleSalaryQuestion($msg);

        case STATE_TAX_CRITERIA:
            return handleTaxCriteria($msg);

        case STATE_ELIGIBILITY_CHECK_1:
            return handleEligibilityCheck1($msg);

        case STATE_ELIGIBILITY_CHECK_2:
            return handleEligibilityCheck2($msg);

        case STATE_COLLECT_NAME:
            return handleCollectName($msg);

        case STATE_COLLECT_PHONE:
            return handleCollectPhone($msg);

        case STATE_COLLECT_ID:
            return handleCollectId($msg);

        case STATE_SAVINGS_ASSESSMENT:
            return handleSavingsAssessment($msg);

        case STATE_CONFIRMATION:
        case STATE_NO_SAVINGS:
            // Both exit flows lead back to area selection
            return handleExitFlows($msg);

        default:
            // Fallback for unknown state (should not happen if logic is sound)
            return handleUnknown();
    }
}

// --- Handler Functions ---

function handleStart(): array
{
    return [
        'type' => 'buttons',
        'text' => "Hi, I'm Robin Hood 🏹 - here to help you pay less and get more. Would you like to see where you can save money right now?",
        'buttons' => [
            ['id' => 'opt_yes_start', 'title' => 'Yes'],
            ['id' => 'opt_no_start', 'title' => 'No'],
        ],
        'next_state' => STATE_AREA_SELECTION, // Next state is where the user's reply will be processed
    ];
}

function handleAreaSelection(string $msg): array
{
    // The user's message is the button ID from the previous state (STATE_START)
    if ($msg === 'opt_yes_start') {
        return [
            'type' => 'buttons',
            'text' => "Great, let's get started! For which areas would you like to check how to save?",
            'buttons' => [
                ['id' => 'area_tax_refund', 'title' => 'Tax refund'],
                // Add other areas here if needed in the future
            ],
            'next_state' => STATE_EMPLOYMENT_STATUS,
        ];
    } elseif ($msg === 'opt_no_start') {
        // "No" from start menu returns to main menu/exit flow
        return [
            'type' => 'text',
            'text' => "No problem! You can type 'start' or 'help' anytime to see the main menu again.",
            'next_state' => STATE_START, // Reset to start state
        ];
    } elseif ($msg === 'area_tax_refund') {
        // This handles the selection from the Area Selection menu
        return [
            'type' => 'buttons',
            'text' => "Great! So I can check, I'll ask a few short questions (answer them briefly - less than a minute). Are you:",
            'buttons' => [
                ['id' => 'emp_full_6_years', 'title' => 'I have been employed for the entire last 6 years'],
                ['id' => 'emp_part_life', 'title' => 'I was an employee for part of my life'],
                ['id' => 'emp_self_only', 'title' => 'I am self-employed only'],
            ],
            'next_state' => STATE_SALARY_QUESTION, // Next state is where the user's reply will be processed
        ];
    }

    return handleUnknown();
}

function handleEmploymentStatus(string $msg): array
{
    // The user's message is the button ID from the previous state (STATE_AREA_SELECTION)
    if (in_array($msg, ['emp_full_6_years', 'emp_part_life'])) {
        // Proceed to Salary Question
        return [
            'type' => 'buttons',
            'text' => "What is your average salary in recent years?",
            'buttons' => [
                ['id' => 'sal_less_800', 'title' => 'Less than 800'],
                ['id' => 'sal_8k_12k', 'title' => '8,000–12,000'],
                ['id' => 'sal_12k_18k', 'title' => '12,000–18,000'],
                ['id' => 'sal_more_18k', 'title' => 'More than 18,000'],
            ],
            'next_state' => STATE_TAX_CRITERIA,
        ];
    } elseif ($msg === 'emp_self_only') {
        // End flow (not applicable) -> Proceed to No Savings Exit Message
        return [
            'type' => 'buttons',
            'text' => "Thank you for choosing Robin Hood 🏹 It seems that you currently have no potential for savings in the area of tax refunds, so why not check out another area?",
            'buttons' => [
                ['id' => 'back_to_menu', 'title' => 'Take me back to the main menu to continue saving!'],
            ],
            'next_state' => STATE_NO_SAVINGS,
        ];
    }

    return handleUnknown();
}

function handleSalaryQuestion(string $msg): array
{
    // The user's message is the button ID from the previous state (STATE_EMPLOYMENT_STATUS)
    if (in_array($msg, ['sal_less_800', 'sal_8k_12k', 'sal_12k_18k', 'sal_more_18k'])) {
        // Store salary value (in a real app, this would be saved to user state)
        // Proceed to Tax/Financial Criteria Check
        $criteria_text = "Does any of the following apply to you?\n\n"
                       . "- I pay tax on my salary\n"
                       . "- I have a pension/compensation/provident fund/training fund. I have paid tax in the last 6 years\n"
                       . "- I have paid capital gains tax in the last 6 years\n"
                       . "- I had capital market transactions that caused me a profit/loss in the last 6 years";

        return [
            'type' => 'buttons',
            'text' => $criteria_text,
            'buttons' => [
                ['id' => 'tax_criteria_yes', 'title' => 'Yes'],
                ['id' => 'tax_criteria_no', 'title' => 'No'],
            ],
            'next_state' => STATE_TAX_CRITERIA, // User's reply will be processed in the same state
        ];
    }

    return handleUnknown();
}

function handleTaxCriteria(string $msg): array
{
    // The user's message is the button ID from the previous state (STATE_SALARY_QUESTION)
    if ($msg === 'tax_criteria_yes') {
        // Proceed to Additional Eligibility Check (First Level)
        return [
            'type' => 'buttons',
            'text' => "Do you have children, academic studies, insurance payments, or grants you have received that could affect your eligibility for a refund?",
            'buttons' => [
                ['id' => 'eligibility_yes_1', 'title' => 'Yes'],
                ['id' => 'eligibility_no_1', 'title' => 'No'],
            ],
            'next_state' => STATE_ELIGIBILITY_CHECK_1,
        ];
    } elseif ($msg === 'tax_criteria_no') {
        // Proceed to No Savings Potential - Exit Message
        return [
            'type' => 'buttons',
            'text' => "Thank you for choosing Robin Hood 🏹 It seems that you currently have no potential for savings in the area of tax refunds, so why not check out another area?",
            'buttons' => [
                ['id' => 'back_to_menu', 'title' => 'Take me back to the main menu to continue saving!'],
            ],
            'next_state' => STATE_NO_SAVINGS,
        ];
    }

    return handleUnknown();
}

function handleEligibilityCheck1(string $msg): array
{
    // The user's message is the button ID from the previous state (STATE_TAX_CRITERIA)
    if ($msg === 'eligibility_yes_1') {
        // Proceed to Additional Eligibility Check (Second Level)
        return [
            'type' => 'buttons',
            'text' => "Do you have children, academic studies, insurance payments, or grants you have received that could affect your eligibility for a refund?",
            'buttons' => [
                ['id' => 'eligibility_yes_2', 'title' => 'Yes'],
                ['id' => 'eligibility_no_2', 'title' => 'No'],
            ],
            'next_state' => STATE_ELIGIBILITY_CHECK_2,
        ];
    } elseif ($msg === 'eligibility_no_1') {
        // Proceed to No Savings Potential - Exit Message
        return [
            'type' => 'buttons',
            'text' => "Thank you for choosing Robin Hood 🏹 It seems that you currently have no potential for savings in the area of tax refunds, so why not check out another area?",
            'buttons' => [
                ['id' => 'back_to_menu', 'title' => 'Take me back to the main menu to continue saving!'],
            ],
            'next_state' => STATE_NO_SAVINGS,
        ];
    }

    return handleUnknown();
}

function handleEligibilityCheck2(string $msg): array
{
    // The user's message is the button ID from the previous state (STATE_ELIGIBILITY_CHECK_1)
    if ($msg === 'eligibility_yes_2') {
        // Proceed to Collect User Information (Start with Full Name)
        return [
            'type' => 'text',
            'text' => "Great! Please provide your Full Name.",
            'next_state' => STATE_COLLECT_NAME,
        ];
    } elseif ($msg === 'eligibility_no_2') {
        // Proceed to No Savings Potential - Exit Message
        return [
            'type' => 'buttons',
            'text' => "Thank you for choosing Robin Hood 🏹 It seems that you currently have no potential for savings in the area of tax refunds, so why not check out another area?",
            'buttons' => [
                ['id' => 'back_to_menu', 'title' => 'Take me back to the main menu to continue saving!'],
            ],
            'next_state' => STATE_NO_SAVINGS,
        ];
    }

    return handleUnknown();
}

function handleCollectName(string $msg): array
{
    // In a real application, we would save $msg as the user's name here.
    // For now, we just proceed to the next question.
    return [
        'type' => 'text',
        'text' => "Thank you, [Name Placeholder]. Now, please provide your Phone Number.",
        'next_state' => STATE_COLLECT_PHONE,
    ];
}

function handleCollectPhone(string $msg): array
{
    // In a real application, we would validate and save $msg as the user's phone number here.
    // For now, we just proceed to the next question.
    return [
        'type' => 'text',
        'text' => "Got it. Finally, please provide your ID Number.",
        'next_state' => STATE_COLLECT_ID,
    ];
}

function handleCollectId(string $msg): array
{
    // In a real application, we would validate and save $msg as the user's ID number here.
    // After collecting all info, proceed to Savings Potential Assessment
    return [
        'type' => 'buttons',
        'text' => "It looks like you have the potential to save a few hundred shekels a month. Want us to do a free in-depth check to make sure?",
        'buttons' => [
            ['id' => 'assessment_yes', 'title' => 'Yes, check for me'],
            ['id' => 'assessment_back', 'title' => 'Take me back to the main menu to continue saving!'],
        ],
        'next_state' => STATE_SAVINGS_ASSESSMENT,
    ];
}

function handleSavingsAssessment(string $msg): array
{
    // The user's message is the button ID from the previous state (STATE_COLLECT_ID)
    if ($msg === 'assessment_yes') {
        // Proceed to Confirmation Message
        return [
            'type' => 'buttons',
            'text' => "Thank you for choosing Robin Hood 🏹 We will update you as soon as we find savings! Shall we continue to save in other areas?",
            'buttons' => [
                ['id' => 'back_to_menu', 'title' => 'Take me back to the main menu to continue saving!'],
            ],
            'next_state' => STATE_CONFIRMATION,
        ];
    } elseif ($msg === 'assessment_back') {
        // Return to Step 2 (Area Selection)
        return handleAreaSelection('opt_yes_start'); // Simulate 'Yes' to the start question to get to area selection
    }

    return handleUnknown();
}

function handleExitFlows(string $msg): array
{
    // This handles the 'back_to_menu' button from STATE_CONFIRMATION and STATE_NO_SAVINGS
    if ($msg === 'back_to_menu') {
        // Return to Step 2 (Area Selection)
        return handleAreaSelection('opt_yes_start'); // Simulate 'Yes' to the start question to get to area selection
    }

    return handleUnknown();
}

function handleUnknown(): array
{
    return [
        'type' => 'text',
        'text' => "I'm sorry, I didn't understand that. Please use the buttons provided or type 'start' to begin the conversation.",
        'next_state' => STATE_START, // Send them back to the start state
    ];
}

// --- Test Block ---

if (basename(__FILE__) === 'myscripts.php' && php_sapi_name() === 'cli') {
    echo "--- Running myscripts.php Test Block ---\n\n";

    // Mock user ID and initial state
    $testUserId = '972501234567';
    $currentState = STATE_START;
    $conversation = [];

    /**
     * Helper function to simulate a user message and process the reply.
     * @param string $message The message/button ID to send.
     * @param string $expectedState The state the bot should be in after the reply.
     */
    function simulateMessage(string $message, string $expectedState)
    {
        global $testUserId, $currentState, $conversation;

        echo "User (\$currentState): " . $message . "\n";
        $reply = getReply($message, $testUserId, $currentState);
        $conversation[] = ['user' => $message, 'bot' => $reply];

        $nextState = $reply['next_state'] ?? $currentState;
        $currentState = $nextState;

        echo "Bot (\$currentState): " . $reply['text'] . "\n";
        if (!empty($reply['buttons'])) {
            echo "Options: " . implode(', ', array_column($reply['buttons'], 'title')) . "\n";
        }
        echo "Expected Next State: " . $expectedState . "\n";
        echo "Actual Next State: " . $currentState . "\n";
        echo "--- \n";

        if ($currentState !== $expectedState) {
            echo "!!! TEST FAILED: State mismatch. Expected {$expectedState}, got {$currentState} !!!\n";
            exit(1);
        }
    }

    // Scenario 1: Full successful flow
    echo "--- Scenario 1: Successful Flow ---\n";
    simulateMessage('start', STATE_AREA_SELECTION); // Initial message
    simulateMessage('opt_yes_start', STATE_EMPLOYMENT_STATUS); // Yes, start
    simulateMessage('area_tax_refund', STATE_SALARY_QUESTION); // Tax refund
    simulateMessage('emp_full_6_years', STATE_TAX_CRITERIA); // Employed full 6 years
    simulateMessage('sal_12k_18k', STATE_TAX_CRITERIA); // Salary 12k-18k
    simulateMessage('tax_criteria_yes', STATE_ELIGIBILITY_CHECK_1); // Tax criteria met
    simulateMessage('eligibility_yes_1', STATE_ELIGIBILITY_CHECK_2); // Eligibility check 1 Yes
    simulateMessage('eligibility_yes_2', STATE_COLLECT_NAME); // Eligibility check 2 Yes
    simulateMessage('John Doe', STATE_COLLECT_PHONE); // Collect Name
    simulateMessage('0501234567', STATE_COLLECT_ID); // Collect Phone
    simulateMessage('123456789', STATE_SAVINGS_ASSESSMENT); // Collect ID
    simulateMessage('assessment_yes', STATE_CONFIRMATION); // Savings Assessment Yes
    simulateMessage('back_to_menu', STATE_EMPLOYMENT_STATUS); // Back to menu (returns to area selection, which immediately moves to employment status)

    // Scenario 2: No Savings Flow (Self-employed only)
    echo "\n--- Scenario 2: No Savings Flow (Self-employed only) ---\n";
    $currentState = STATE_AREA_SELECTION; // Reset state for new scenario
    simulateMessage('area_tax_refund', STATE_EMPLOYMENT_STATUS); // Tax refund
    simulateMessage('emp_self_only', STATE_NO_SAVINGS); // Self-employed only
    simulateMessage('back_to_menu', STATE_EMPLOYMENT_STATUS); // Back to menu

    // Scenario 3: No Savings Flow (No tax criteria)
    echo "\n--- Scenario 3: No Savings Flow (No tax criteria) ---\n";
    $currentState = STATE_AREA_SELECTION; // Reset state for new scenario
    simulateMessage('area_tax_refund', STATE_EMPLOYMENT_STATUS); // Tax refund
    simulateMessage('emp_part_life', STATE_TAX_CRITERIA); // Employed part of life
    simulateMessage('sal_less_800', STATE_TAX_CRITERIA); // Salary less than 800
    simulateMessage('tax_criteria_no', STATE_NO_SAVINGS); // No tax criteria
    simulateMessage('back_to_menu', STATE_EMPLOYMENT_STATUS); // Back to menu

    // Scenario 4: Unknown message
    echo "\n--- Scenario 4: Unknown Message ---\n";
    $currentState = STATE_START; // Reset state for new scenario
    simulateMessage('random text', STATE_START); // Unknown message

    echo "\n--- Test Block Complete. All scenarios passed. ---\n";
}

// End of myscripts.php
?>
