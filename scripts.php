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
                'text' => "\u{200B}היי, אני רובין הוד- כאן בשביל לעזור לך לשלם פחות ולקבל יותר.\n\n\u{200B}תרצה שנבדוק יחד איפה אפשר לחסוך כסף כבר עכשיו?\n \n\u{200F}" ,"\n", "","\n",
                'buttons' => [
                     ['id' => 'lets_start', 'text' => 'ספר לי איך זה עובד'],
                    //'tell me how it works'
                     ['id' => 'yes', 'text' => 'כן']

                ]
            ];

        case 'intro_explainer':
            return [
                'text' => "\u{200B}מעולה, בוא נתחיל!\n\u{200B}בעבור איזה תחומים תרצה לבדוק כיצד לחסוך?\n\n\u{200F}" ,"\n", " ","\n",
                'buttons' => [
                    ['id' => 'tax_refund', 'text' => 'החזר מס'],
                    ['id' => 'fast_loans', 'text' => 'ריביות והלוואות'] 
                ]
            ];
            
        case 'area_selection':
            return [
                'text' => "תמיד רציתם לחסוך אבל לא ידעתם איפה להתחיל?  אנחנו פה בשבילכם!\n\n\n\u{200F}אנחנו מערכת לבדיקה אוטמטית וחינמית לזכאות הנחות והצעות שעוזרות לכם לחסוך בהרבה- ריביות , החזרי מס ואפילו חשבונות , שנתחיל?\n \n\u{200F}" ,"\n", "","\n",
                'buttons' => [
                    ['id' => 'tax_refund', 'text' => 'החזר מס'],
                    ['id' => 'fast_loans', 'text' => 'ריביות והלוואות'] 

                ]
            ];
            
case 'employment_status':
    return [
        'text' => "מעולה! כדי שאוכל לבדוק, אשאל כמה שאלות קצרות (המענה עליהם קצר – פחות מדקה).\n\nהאם אתה:\n\u{200F}1. אני שכיר בכל תקופת ה-6 השנים האחרונות\n\u{200F}2. הייתי בחלק מחיי שכיר (בהתייחסות לתקופה של שנים)\n\u{200F}3. אני עצמאי בלבד\n \n\u{200F}","\n", "",
        'buttons' => [
            ['id' => 'employed_6yrs', 'text' => '1'],
            ['id' => 'employed_part', 'text' => '2'],
            ['id' => 'self_employed', 'text' => '3']
        ]
    ];  
            
        case 'salary_range':
            return [
                'text' => "מה גובה השכר הממוצע שלך בשנים האחרונות?"
,//"What is your average salary in recent years?",
                'buttons' => [
                    ['id' => 'less_than_8000', 'text' => 'עד 8,000'],
                    ['id' => '8000_18000', 'text' => '8,000–18,000'],
                    ['id' => 'more_than_18000', 'text' => 'מעל 18,000']
                ]
            ];
            
        case 'tax_criteria':
            return [
        // 'text' => "מעולה! כדי שאוכל לבדוק, אשאל כמה שאלות קצרות (הענה עליהם קצר – פחות מדקה).\n\nהאם אתה:\n\u{200F}1. אני שכיר בכל תקופת ה-6 השנים האחרונות\n\u{200F}2. הייתי בחלק מחיי שכיר (בהתייחסות לתקופה של שנים)\n\u{200F}3. אני עצמאי בלבד\n \n\u{200F}","\n", "",

                'text' => "האם אחד מהסעיפים הבאים תקפים אלייך?
\n\u{200F} 1. אני משלם מס מהשכר שלך
\u{200F} 2. אני בעל פידיון פנסיה/פיצויים/קופות גמל/קרן השתלמות ושילמתי מס ב- 6 שנים אחרונות
\u{200F} 3. שילמתי מס שבח ב6 שנים אחרונות
\u{200F} 4. היו לי פעולות בשוק ההון שגרמו לי לרווח/הפסד ב- 6 שנים אחרונות \n \n\u{200F}","\n", "",//"Does any of the following apply to you?\n\n- I pay tax on my salary\n- I have a pension/compensation/provident fund/training fund. I have paid tax in the last 6 years\n- I have paid capital gains tax in the last 6 years\n- I had capital market transactions that caused me a profit/loss in the last 6 years",
                'buttons' => [
                    ['id' => 'yes', 'text' => 'כן'],
                    ['id' => 'no', 'text' => 'לא']
                ]
            ];
            
        case 'eligibility_check_1':
            return [
                'text' => "האם יש לך ילדים, לימודים אקדמיים, תשלומים לביטוחים או מענקים שקיבלת שיכולים להשפיע על זכאות להחזר?\n \n\u{200F}" ,"\n", "","\n",//"Do you have children, academic studies, insurance payments, or grants you have received that could affect your eligibility for a refund?",
                'buttons' => [
                    ['id' => 'yes', 'text' => 'כן'],
                    ['id' => 'no', 'text' => 'לא']
                ]
            ];
            
        case 'eligibility_check_2':
            return [
                'text' => " האם ביצעת החזר מס ב6 שנים האחרונות?",
                'buttons' => [
                    ['id' => 'yes', 'text' => 'כן'],
                    ['id' => 'no', 'text' => 'לא']
                ]
            ];
            
        case 'collect_info_name':
            return [
                'text' => "מה שמך המלא?"
            ];
            
        case 'collect_info_phone':
            return [
                'text' => "מה מספר הטלפון שלך?"
            ];
            
        case 'collect_info_id':
            return [
                'text' => "מה תעודת הזהות שלך?"
            ];
            
        case 'savings_potential':
            return [
                'text' => "נראה שיש לך פוטנציאל לחיסכון של כמה מאות שקלים בחודש
רוצה שנבצע בדיקה מעמיקה חינם כדי לוודא?\n \n\u{200F}" ,"\n", "","\n",//"It looks like you have the potential to save a few hundred shekels a month. Want us to do a free in-depth check to make sure?",
                'buttons' => [
                    ['id' => 'yes_check', 'text' => 'כן, תבדקו לי'],
                    ['id' => 'main_menu', 'text' => 'תפריט ראשי']
                ]
            ];

        case 'tax_refund_example':
            return [
                'text' => "דוגמה זריזה לאיך עובד החזר מס
אם עבדת ב-6 השנים האחרונות ושילמת יותר מס ממה שהיית צריך, המדינה עשויה להיות חייבת לך כסף.החזרים יכולים להגיע מ: פערים בתעסוקה, לימודים, ילדים, הפקדות לפנסיה, פעילות בשוק ההון ועוד גורמים רבים.אנחנו נבדוק עכשיו את המקרה שלך לעומק ונעדכן אותך בסכום שמגיע לך.\n \n\u{200F}" ,"\n", "","\n",//"Here is a quick example of how a tax refund works:\nIf you worked during the last 6 years and paid more tax than required, the state may owe you money back.\nRefunds can come from: employment gaps, studies, children, pension deposits, capital market activity, and many other factors.\nWe’ll now check your case in detail and update you with the amount you deserve.",
                'buttons' => [
                    ['id' => 'continue', 'text' => 'המשך']
                ]
            ];
            
        case 'confirmation':
            return [
                'text' => "תודה שבחרת ברובין הוד 🏹
\u{200F} אנחנו נעדכן אותך ברגע שיימצא חיסכון!
\u{200F}שנמשיך לחסוך בעוד תחומים?\n \n\u{200F}","\n", "","\n",//"Thank you for choosing Robin Hood 🏹 We will update you as soon as we find savings! Shall we continue to save in other areas?",
                'buttons' => [
                    ['id' => 'main_menu', 'text' => 'תפריט ראשי']
                ]
            ];
            
        case 'no_savings':
            return [
                'text' => "תודה שבחרת ברובין הוד 🏹
\u{200F}נראה שכרגע אין לך פוטנציאל לחיסכון בתחום החזרי המס, שנבחר לבדוק תחום אחר?\n \n\u{200F}" ,"\n", "","\n",//"Thank you for choosing Robin Hood 🏹 It seems that you currently have no potential for savings in the area of tax refunds, so why not check out another area?",
                'buttons' => [
                    ['id' => 'main_menu', 'text' => 'תפריט ראשי']
                ]
            ];

        // --- Fast Loans Flow Messages ---

        case 'loans_credit_card':
            return [
                'text' => "מעולה! בתוך מספר שניות נוכל לברר את זכאותך להלוואה מהירה בריבית משתלמת! כדי שאוכל לבדוק, אשאל כמה שאלות קצרות (פחות מדקה).\n\nאם יש לך כרטיס אשראי (לא דיירקט)?\n \n\u{200F}" ,"\n", "","\n",
                'buttons' => [
                    ['id' => 'yes', 'text' => 'כן'],
                    ['id' => 'no', 'text' => 'לא']
                ]
            ];

        case 'loans_employment_status':
            return [
                'text' => "מה הסטטוס התעסוקתי שלך? \n\u{200F}" ,
                'buttons' => [
                    ['id' => 'employee', 'text' => 'שכיר'],
                    ['id' => 'self_employed', 'text' => 'עצמאי']
                ]
            ];

        case 'loans_amount':
            return [
                'text' => "מה גובה הלוואה בה אתה מעוניין? \n\u{200F}" ,
                'buttons' => [
                    ['id' => 'above_30k', 'text' => 'מעל ל 30,000'],
                    ['id' => 'below_30k', 'text' => 'מתחת ל 30,000']
                ]
            ];

        case 'loans_pension_fund':
            return [
                'text' => "האם יש לך קופת גמל או קרן פנסיה עם צבירה של מעל 40,000 ש\"\"ח וללא שעבודים כנגדה?\n \n\u{200F}" ,"\n", "","\n",
                'buttons' => [
                    ['id' => 'yes', 'text' => 'כן'],
                    ['id' => 'no', 'text' => 'לא']
                ]
            ];

        case 'loans_turnover':
            return [
                'text' => "מה מחזור המכירות השנתי שלך? \n\u{200F}",
                'buttons' => [
                    ['id' => 'below_500k', 'text' => 'מתחת ל-500 אלף ש"ח'],
                    ['id' => 'above_500k', 'text' => 'מעל 500 אלף ש"ח']
                ]
            ];

        case 'loans_business_age':
            return [
                'text' => "מה שנים העסק קיים?\n\u{200F}",
                'buttons' => [
                    ['id' => 'more_than_year', 'text' => 'יותר משנה'],
                    ['id' => 'less_than_year', 'text' => 'פחות משנה']
                ]
            ];

        case 'loans_real_estate':
            return [
                'text' => "אם יש לך נכס נדל\"\"ן ללא שעבודים? \n\u{200F}" ,
                'buttons' => [
                    ['id' => 'yes', 'text' => 'כן'],
                    ['id' => 'no', 'text' => 'לא']
                ]
            ];

        case 'loans_collect_name':
            return [
                'text' => "מה שמך?\n\u{200F}" ,
            ];

        case 'loans_collect_id':
            return [
                'text' => "מה תעודת הזהות שלך?\n\u{200F}" ,
            ];

        case 'loans_savings_potential':
            return [
                'text' => "נראה שיש לך פוטנציאל לחיסכון של כמה מאות שקלים בחודש. רוצה שנבצע בדיקה מעמיקה חינם כדי לוודא?\n \n\u{200F}" ,"\n", "","\n",
                'buttons' => [
                    ['id' => 'yes_check', 'text' => 'כן, תבדקו לי'],
                    ['id' => 'main_menu', 'text' => 'תפריט ראשי']
                ]
            ];

        case 'loans_thank_you':
            return [
                'text' => "תודה שבחרת ברובין הוד 🏹 אנחנו נעדכן אותך ברגע שיימצא חיסכון! שנמשיך לחסוך בעוד תחומים? \n\u{200F}" , "","\n",
                'buttons' => [
                    ['id' => 'main_menu', 'text' => 'תפריט ראשי']
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
    if ($input === 'yes') {
        $state['step'] = 'intro_explainer';
        saveUserResponse($state['phone_number'], 'welcome_response', $input);
        return getCurrentStepMessage('intro_explainer');
    }

    if ($input === 'lets_start' || $input === 'tell me how it works') {
        $state['step'] = 'area_selection';
        saveUserResponse($state['phone_number'], 'welcome_response', $input);
        return getCurrentStepMessage('area_selection');
    }
    return null;
}

function handleIntroExplainer(&$state, $input) {
    $normalized = strtolower(trim($input));
    $normalized = str_replace(' ', '_', $normalized);
    
    if ($normalized === 'tax_refund') {
        $state['step'] = 'employment_status';
        saveUserResponse($state['phone_number'], 'intro_explainer_response', 'tax_refund');
        return getCurrentStepMessage('employment_status');
    }

    if ($normalized === 'fast_loans' || strpos($input, 'ריביות והלוואות') !== false) {
        $state['step'] = 'loans_credit_card';
        saveUserResponse($state['phone_number'], 'intro_explainer_response', 'fast_loans');
        return getCurrentStepMessage('loans_credit_card');
    }
    return null;
}



function handleAreaSelection(&$state, $input) {
    $normalized = strtolower(trim($input));
    $normalized = str_replace(' ', '_', $normalized);
    
    // Check for ID or Hebrew text
    if ($normalized === 'tax_refund' || strpos($input, 'החזר מס') !== false) {
        $state['step'] = 'employment_status';
        $state['selected_area'] = 'tax_refund';
        saveUserResponse($state['phone_number'], 'selected_area', 'tax_refund');
        return getCurrentStepMessage('employment_status');
    }

    if ($normalized === 'fast_loans' || strpos($input, 'ריביות והלוואות') !== false) {
        $state['step'] = 'loans_credit_card';
        $state['selected_area'] = 'fast_loans';
        saveUserResponse($state['phone_number'], 'selected_area', 'fast_loans');
        return getCurrentStepMessage('loans_credit_card');
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
        $state['step'] = 'intro_explainer';
        saveUserResponse($state['phone_number'], 'savings_potential_response', 'main_menu');
        return getCurrentStepMessage('intro_explainer');
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
        $state['step'] = 'intro_explainer';
        saveUserResponse($state['phone_number'], 'confirmation_response', 'main_menu');
        return getCurrentStepMessage('intro_explainer');
    }
    return null;
}

function handleNoSavings(&$state, $input) {
    if ($input === 'main_menu' || $input === 'main menu') {
        $state['step'] = 'intro_explainer';
        saveUserResponse($state['phone_number'], 'no_savings_response', 'main_menu');
        return getCurrentStepMessage('intro_explainer');
    }
    return null;
}

// --- Fast Loans Handlers ---

function handleLoansCreditCard(&$state, $input) {
    if ($input === 'yes' || $input === 'no') {
        $state['step'] = 'loans_employment_status';
        saveUserResponse($state['phone_number'], 'loans_credit_card', $input);
        return getCurrentStepMessage('loans_employment_status');
    }
    return null;
}

function handleLoansEmploymentStatus(&$state, $input) {
    if ($input === 'employee' || $input === 'self_employed') {
        $state['step'] = 'loans_amount';
        saveUserResponse($state['phone_number'], 'loans_employment_status', $input);
        return getCurrentStepMessage('loans_amount');
    }
    return null;
}

function handleLoansAmount(&$state, $input) {
    if ($input === 'above_30k' || $input === 'below_30k') {
        $empStatus = getUserResponse($state['phone_number'], 'loans_employment_status');
        
        // Branch based on employment status
        if ($empStatus === 'employee') {
            $state['step'] = 'loans_pension_fund';
            saveUserResponse($state['phone_number'], 'loans_amount', $input);
            return getCurrentStepMessage('loans_pension_fund');
        } elseif ($empStatus === 'self_employed') {
            $state['step'] = 'loans_turnover';
            saveUserResponse($state['phone_number'], 'loans_amount', $input);
            return getCurrentStepMessage('loans_turnover');
        }
        
        // Fallback if status not found (shouldn't happen if flow is followed)
        $state['step'] = 'loans_pension_fund'; 
        return getCurrentStepMessage('loans_pension_fund');
    }
    return null;
}

function handleLoansPensionFund(&$state, $input) {
    if ($input === 'yes' || $input === 'no') {
        $state['step'] = 'loans_collect_name';
        saveUserResponse($state['phone_number'], 'loans_pension_fund', $input);
        return getCurrentStepMessage('loans_collect_name');
    }
    return null;
}

function handleLoansTurnover(&$state, $input) {
    if ($input === 'below_500k' || $input === 'above_500k') {
        $state['step'] = 'loans_business_age';
        saveUserResponse($state['phone_number'], 'loans_turnover', $input);
        return getCurrentStepMessage('loans_business_age');
    }
    return null;
}

function handleLoansBusinessAge(&$state, $input) {
    if ($input === 'more_than_year' || $input === 'less_than_year') {
        $state['step'] = 'loans_real_estate';
        saveUserResponse($state['phone_number'], 'loans_business_age', $input);
        return getCurrentStepMessage('loans_real_estate');
    }
    return null;
}

function handleLoansRealEstate(&$state, $input) {
    if ($input === 'yes' || $input === 'no') {
        $state['step'] = 'loans_collect_name';
        saveUserResponse($state['phone_number'], 'loans_real_estate', $input);
        return getCurrentStepMessage('loans_collect_name');
    }
    return null;
}

function handleLoansCollectName(&$state, $input) {
    if (!empty($input)) {
        $state['full_name'] = $input;
        $state['step'] = 'loans_collect_id';
        saveUserResponse($state['phone_number'], 'loans_full_name', $input);
        return getCurrentStepMessage('loans_collect_id');
    }
    return getCurrentStepMessage('loans_collect_name');
}

function handleLoansCollectID(&$state, $input) {
    if (preg_match('/^[\d\s\-]{6,20}$/', $input)) {
        $state['id_number'] = $input;
        $state['step'] = 'loans_savings_potential';
        saveUserResponse($state['phone_number'], 'loans_id_number', $input);
        return getCurrentStepMessage('loans_savings_potential');
    }
    return [
        'text' => "Please enter a valid ID number (digits, -, or spaces). " . getCurrentStepMessage('loans_collect_id')['text']
    ];
}

function handleLoansSavingsPotential(&$state, $input) {
    if ($input === 'yes_check') {
        $state['step'] = 'loans_thank_you';
        saveUserResponse($state['phone_number'], 'loans_savings_potential', 'yes_check');
        return getCurrentStepMessage('loans_thank_you');
    }
    
    if ($input === 'main_menu') {
        $state['step'] = 'intro_explainer';
        saveUserResponse($state['phone_number'], 'loans_savings_potential', 'main_menu');
        return getCurrentStepMessage('intro_explainer');
    }
    return null;
}

function handleLoansThankYou(&$state, $input) {
    if ($input === 'main_menu') {
        $state['step'] = 'intro_explainer';
        return getCurrentStepMessage('intro_explainer');
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
            'welcome' => ['lets_start', 'tell me how it works', 'yes'],
            'intro_explainer' => ['tax_refund', 'tax refund', 'החזר מס', 'fast_loans', 'ריביות והלוואות'],
            'area_selection' => ['tax_refund', 'tax refund', 'החזר מס', 'fast_loans', 'ריביות והלוואות'],
            'employment_status' => ['employed_6yrs', 'employed_part', 'self_employed', '1', '2', '3'],
            'salary_range' => ['less_than_8000', '8000_18000', 'more_than_18000', 'less than 8,000', '8,000–18,000', 'more than 18,000', 'עד 8,000', 'מעל 18,000'],
            'tax_criteria' => ['yes', 'no', 'כן', 'לא'],
            'eligibility_check_1' => ['yes', 'no', 'כן', 'לא'],
            'eligibility_check_2' => ['yes', 'no', 'כן', 'לא'],
            'savings_potential' => ['yes_check', 'main_menu', 'yes, check for me', 'main menu', 'כן, תבדקו לי', 'תפריט ראשי'],
            'tax_refund_example' => ['continue', 'המשך'],
            'confirmation' => ['main_menu', 'main menu', 'תפריט ראשי'],
            'no_savings' => ['main_menu', 'main menu', 'תפריט ראשי'],
            
            // Fast Loans Buttons
            'loans_credit_card' => ['yes', 'no', 'כן', 'לא'],
            'loans_employment_status' => ['employee', 'self_employed', 'שכיר', 'עצמאי'],
            'loans_amount' => ['above_30k', 'below_30k', 'מעל ל 30,000', 'מתחת ל 30,000'],
            'loans_pension_fund' => ['yes', 'no', 'כן', 'לא'],
            'loans_turnover' => ['below_500k', 'above_500k', 'מתחת ל-500 אלף ש"ח', 'מעל 500 אלף ש"ח'],
            'loans_business_age' => ['more_than_year', 'less_than_year', 'יותר משנה', 'פחות משנה'],
            'loans_real_estate' => ['yes', 'no', 'כן', 'לא'],
            'loans_savings_potential' => ['yes_check', 'main_menu', 'כן, תבדקו לי', 'לא, קח אותי בחזרה לתפריט הראשי כדי להמשיך לחסוך!'],
            'loans_thank_you' => ['main_menu', 'קח אותי בחזרה לתפריט הראשי כדי להמשיך לחסוך!'],
        ];
        
        $isFreeTextStep = in_array($currentStep, [
            'collect_info_name', 'collect_info_phone', 'collect_info_id',
            'loans_collect_name', 'loans_collect_id'
        ]);
        $isButtonInput = isset($validButtons[$currentStep]) && in_array($lc, $validButtons[$currentStep]);
        
        if (!$isFreeTextStep && !$isButtonInput) {
            error_log("Invalid input for button step $currentStep: '$lc'");
            return [
                'text' => " ליחצו על הכפתורים או תשלחו start",//"   Please use the buttons or send 'Start' "
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
            'exit_flow' => 'handleNoSavings',
            
            // Fast Loans Handlers
            'loans_credit_card' => 'handleLoansCreditCard',
            'loans_employment_status' => 'handleLoansEmploymentStatus',
            'loans_amount' => 'handleLoansAmount',
            'loans_pension_fund' => 'handleLoansPensionFund',
            'loans_turnover' => 'handleLoansTurnover',
            'loans_business_age' => 'handleLoansBusinessAge',
            'loans_real_estate' => 'handleLoansRealEstate',
            'loans_collect_name' => 'handleLoansCollectName',
            'loans_collect_id' => 'handleLoansCollectID',
            'loans_savings_potential' => 'handleLoansSavingsPotential',
            'loans_thank_you' => 'handleLoansThankYou',
        ];
        
        if (isset($handlerMap[$currentStep])) {
            $handler = $handlerMap[$currentStep];
            $reply = $handler($state, $lc);
            
            if ($reply === null) {
                return [
                    'text' => " ליחצו על הכפתורים או תשלחו start",//"Please use the buttons or send 'Start' "
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
