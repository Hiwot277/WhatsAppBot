<?php
require_once __DIR__ . '/config.php';

/**
 * Save or update user response in the database
 * 
 * @param string $phoneNumber User's phone number
 * @param string $field Field name to update
 * @param mixed $value Value to save
 * @return bool True on success, false on failure
 */
/**
 * Save or update user response in the database
 * 
 * @param string $phoneNumber User's phone number
 * @param string $field Field name to update
 * @param mixed $value Value to save
 * @return bool|int ID of the record on success, false on failure
 */
function saveUserResponse($phoneNumber, $field, $value) {
    error_log("[DB] saveUserResponse called - Phone: $phoneNumber, Field: $field, Value: $value");
    
    $conn = getDbConnection();
    if (!$conn) {
        $error = "Database connection failed in saveUserResponse";
        error_log("[DB ERROR] $error");
        return false;
    }
    
    // Debug: Log current database connection info
    error_log("[DB] Connected to: " . $conn->host_info);
    error_log("[DB] Database: " . $conn->select_db(DB_NAME));

    // Decide which table to use: tax refund vs fast loans
    $isLoanField = (strpos($field, 'loans_') === 0);
    $tableName = $isLoanField ? 'loans_responses' : 'users_responses';
    
    // Ensure the target table exists (fallback in case initializeDatabase was not run)
    $tableCheck = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($tableName) . "'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        error_log("[DB ERROR] Table '$tableName' does not exist, attempting to create it on the fly");

        if ($tableName === 'users_responses') {
            $createTable = "
                CREATE TABLE IF NOT EXISTS users_responses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    phone_number VARCHAR(20) NOT NULL,
                    full_name VARCHAR(100),
                    employment_status VARCHAR(50),
                    salary_range VARCHAR(50),
                    tax_criteria VARCHAR(10),
                    eligibility_check_1 VARCHAR(10),
                    eligibility_check_2 VARCHAR(10),
                    savings_potential VARCHAR(10), 
                    phone_num_2 VARCHAR(50),
                    id_number VARCHAR(50),
                    welcome_response VARCHAR(50),
                    selected_area VARCHAR(50),
                    savings_potential_response VARCHAR(50),
                    confirmation_response VARCHAR(50),
                    no_savings_response VARCHAR(50),
                    conversation_start TIMESTAMP NULL,
                    conversation_end TIMESTAMP NULL,
                    conversation_complete BOOLEAN DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_phone_number (phone_number),
                    INDEX idx_conversation_complete (conversation_complete)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        } else {
            $createTable = "
                CREATE TABLE IF NOT EXISTS loans_responses (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    phone_number VARCHAR(20) NOT NULL,
                    loans_credit_card VARCHAR(50),
                    loans_employment_status VARCHAR(50),
                    loans_amount VARCHAR(50),
                    loans_pension_fund VARCHAR(10),
                    loans_turnover VARCHAR(50),
                    loans_business_age VARCHAR(50),
                    loans_real_estate VARCHAR(10),
                    loans_full_name VARCHAR(100),
                    loans_id_number VARCHAR(50),
                    loans_savings_potential VARCHAR(50),
                    conversation_start TIMESTAMP NULL,
                    conversation_end TIMESTAMP NULL,
                    conversation_complete BOOLEAN DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_phone_number (phone_number),
                    INDEX idx_conversation_complete (conversation_complete)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }
        
        if (!$conn->query($createTable)) {
            error_log("[DB ERROR] Failed to create table $tableName: " . $conn->error);
            $conn->close();
            return false;
        }
        error_log("[DB] Created table '$tableName'");
    }
    
    // Sanitize input
    $phoneNumber = $conn->real_escape_string($phoneNumber);
    $field = $conn->real_escape_string($field);
    $value = $conn->real_escape_string($value);

    // Special handling for conversation start/end
    $isConversationStart = ($field === 'conversation_start');
    $isEndConversation = ($field === 'conversation_end' || $field === 'conversation_complete');

    // Always work with a single row per phone_number in the target table
    $checkSql = "SELECT id FROM `$tableName` WHERE phone_number = '$phoneNumber' LIMIT 1";
    $result = $conn->query($checkSql);

    if ($result && $result->num_rows > 0) {
        // Existing row for this phone
        $row = $result->fetch_assoc();
        $id = (int)$row['id'];

        if ($isConversationStart) {
            // Reset this row for a new conversation but keep the same id
            if ($tableName === 'users_responses') {
                // Tax refund table columns
                $sql = "UPDATE `$tableName` SET 
                            conversation_start = NOW(),
                            conversation_end = NULL,
                            conversation_complete = 0,
                            full_name = NULL,
                            employment_status = NULL,
                            salary_range = NULL,
                            tax_criteria = NULL,
                            eligibility_check_1 = NULL,
                            eligibility_check_2 = NULL,
                            savings_potential = NULL,
                            updated_at = NOW()
                        WHERE id = $id";
            } else {
                // Fast loans table columns
                $sql = "UPDATE `$tableName` SET 
                            conversation_start = NOW(),
                            conversation_end = NULL,
                            conversation_complete = 0,
                            loans_credit_card = NULL,
                            loans_employment_status = NULL,
                            loans_amount = NULL,
                            loans_pension_fund = NULL,
                            loans_turnover = NULL,
                            loans_business_age = NULL,
                            loans_real_estate = NULL,
                            loans_full_name = NULL,
                            loans_id_number = NULL,
                            loans_savings_potential = NULL,
                            updated_at = NOW()
                        WHERE id = $id";
            }
        } elseif ($isEndConversation) {
            $sql = "UPDATE `$tableName` SET `$field` = '$value', conversation_end = NOW(), updated_at = NOW() WHERE id = $id";
        } else {
            $sql = "UPDATE `$tableName` SET `$field` = '$value', updated_at = NOW() WHERE id = $id";
        }

        error_log("[DB] Executing SQL (update existing): $sql");
        $success = $conn->query($sql);
        if (!$success) {
            error_log("Error updating user response: " . $conn->error);
            error_log("SQL: " . $sql);
            $conn->close();
            return false;
        }

        error_log("Updated users_responses - ID: $id, Field: $field, Value: $value");
        $conn->close();
        
        // Send to Google Sheets (same behavior for tax refund and fast loans)
        sendToGoogleSheet($phoneNumber, $field, $value);
        
        return $id;
    } else {
        // No row yet for this phone_number in the target table
        if ($isConversationStart) {
            $sql = "INSERT INTO `$tableName` (phone_number, conversation_start) VALUES ('$phoneNumber', NOW())";
            error_log("[DB] Executing SQL (insert new with start): $sql");
        } else {
            $sql = "INSERT INTO `$tableName` (phone_number, `$field`, created_at, updated_at) 
                    VALUES ('$phoneNumber', '$value', NOW(), NOW())";
            error_log("[DB] Executing SQL (insert new generic): $sql");
        }

        if ($conn->query($sql)) {
            $id = $conn->insert_id;
            error_log("Created new record for $phoneNumber (ID: $id)");
            $conn->close();
            
            // Send to Google Sheets
            // We need to fetch the full name if it wasn't passed directly (optimization: pass it if known)
            // For now, we'll just send what we have. The GAS script can handle partial updates.
            sendToGoogleSheet($phoneNumber, $field, $value);
            
            return $id;
        } else {
            error_log("Error creating new record: " . $conn->error);
            error_log("SQL: " . $sql);
            $conn->close();
            return false;
        }
    }
}

/**
 * Send data to Google Sheets via Webhook (Blocking with Timeout)
 * 
 * @param string $phoneNumber
 * @param string $field
 * @param string $value
 * @return void
 */
function sendToGoogleSheet($phoneNumber, $field, $value) {
    if (!defined('GOOGLE_SHEETS_WEBHOOK_URL') || empty(GOOGLE_SHEETS_WEBHOOK_URL)) {
        return;
    }

    // Determine sheet name primarily based on the field name
    $url = GOOGLE_SHEETS_WEBHOOK_URL;
    if (strpos($field, 'loans_') === 0) {
        // Any loans_* field always goes to the fast_loans sheet
        $sheetName = 'fast_loans';
    } else {
        // All other fields (tax refund + shared) go to the Tax Refund sheet
        $sheetName = 'Tax Refund';
    }

    error_log("!!! DEBUG !!! Phone: $phoneNumber, Field: '$field', TargetSheet: '$sheetName'");
    
    // Prepare data
    $data = [
        'phone' => $phoneNumber,
        'field' => $field,
        'value' => $value,
        'sheet_name' => $sheetName,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    error_log("[Google Sheets] Sending data: " . json_encode($data));
    
    // Setup cURL
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    // Follow redirects is CRITICAL for Google Scripts
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    
    // Timeout: 3 seconds (was 10) to prevent hanging the webhook
    curl_setopt($ch, CURLOPT_TIMEOUT, 3); 
    
    // Execute
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log("[Google Sheets] Error: " . curl_error($ch));
    } else {
        error_log("[Google Sheets] Sent data. HTTP Code: $httpCode.");
    }
    
    curl_close($ch);
}

/**
 * Save user's full name
 * 
 * @param string $phoneNumber User's phone number
 * @param string $fullName User's full name
 * @return bool True on success, false on failure
 */
function saveUserName($phoneNumber, $fullName) {
    return saveUserResponse($phoneNumber, 'full_name', $fullName);
}

/**
 * Get user's responses
 * 
 * @param string $phoneNumber User's phone number
 * @return array|false User data as associative array, or false if not found
 */
function getUserResponses($phoneNumber) {
    $conn = getDbConnection();
    if (!$conn) {
        error_log("Database connection failed in getUserResponses");
        return false;
    }
    
    $phoneNumber = $conn->real_escape_string($phoneNumber);
    $sql = "SELECT * FROM users_responses WHERE phone_number = '$phoneNumber' LIMIT 1";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $conn->close();
        return $data;
    }
    
    $conn->close();
    return false;
    
}

/**
 * Get a specific user response field
 * 
 * @param string $phoneNumber User's phone number
 * @param string $field Field name to retrieve
 * @return mixed|false The value of the field, or false if not found
 */
function getUserResponse($phoneNumber, $field) {
    $data = getUserResponses($phoneNumber);
    if ($data && isset($data[$field])) {
        return $data[$field];
    }
    return false;
}

/**
 * Get all user responses (for admin purposes)
 * 
 * @return array|false Array of all user responses, or false on failure
 */
function getAllUserResponses() {
    $conn = getDbConnection();
    if (!$conn) {
        error_log("Database connection failed in getAllUserResponses");
        return false;
    }
    
    $sql = "SELECT * FROM users_responses ORDER BY updated_at DESC";
    $result = $conn->query($sql);
    
    $users = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    $conn->close();
    return $users;
}
