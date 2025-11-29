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
    
    // Check if table exists
    $tableName = 'users_responses'; // Using the correct table name
    $tableCheck = $conn->query("SHOW TABLES LIKE '$tableName'");
    if (!$tableCheck) {
        error_log("[DB ERROR] Failed to check for table '$tableName': " . $conn->error);
        $conn->close();
        return false;
    }
    
    if ($tableCheck->num_rows === 0) {
        error_log("[DB ERROR] Table '$tableName' does not exist");
        // Try to create the table
        $createTable = "
            CREATE TABLE IF NOT EXISTS `$tableName` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `phone_number` VARCHAR(20) NOT NULL,
                `full_name` VARCHAR(100),
                `employment_status` VARCHAR(50),
                `salary_range` VARCHAR(50),
                `tax_criteria` VARCHAR(10),
                `eligibility_check_1` VARCHAR(10),
                `eligibility_check_2` VARCHAR(10),
                `savings_potential` VARCHAR(10),
                `conversation_start` TIMESTAMP NULL,
                `conversation_end` TIMESTAMP NULL,
                `conversation_complete` BOOLEAN DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_phone_number` (`phone_number`),
                INDEX `idx_conversation_complete` (`conversation_complete`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        if (!$conn->query($createTable)) {
            error_log("[DB ERROR] Failed to create table '$tableName': " . $conn->error);
            $conn->close();
            return false;
        }
        error_log("[DB] Created table '$tableName'");
    } else {
        error_log("[DB] Table '$tableName' exists");
    }
    
    // Sanitize input
    $phoneNumber = $conn->real_escape_string($phoneNumber);
    $field = $conn->real_escape_string($field);
    $value = $conn->real_escape_string($value);
    
    // Special handling for conversation start/end
    $isNewConversation = ($field === 'conversation_start');
    $isEndConversation = ($field === 'conversation_end' || $field === 'conversation_complete');
    
    // For new conversations, create a new record
    if ($isNewConversation) {
        $sql = "INSERT INTO users_responses (phone_number, conversation_start) VALUES ('$phoneNumber', NOW())";
        error_log("[DB] Executing SQL: $sql");
        if ($conn->query($sql)) {
            $id = $conn->insert_id;
            $conn->close();
            error_log("Started new conversation for $phoneNumber (ID: $id)");
            return $id;
        } else {
            error_log("Error starting new conversation: " . $conn->error);
            $conn->close();
            return false;
        }
    }
    
    // Get the most recent conversation for this user
    $checkSql = "SELECT id FROM users_responses WHERE phone_number = '$phoneNumber' ORDER BY id DESC LIMIT 1";
    $result = $conn->query($checkSql);
    
    if ($result && $result->num_rows > 0) {
        // Update existing record
        $row = $result->fetch_assoc();
        $id = $row['id'];
        
        if ($isEndConversation) {
            $sql = "UPDATE users_responses SET `$field` = '$value', conversation_end = NOW() WHERE id = $id";
        } else {
            $sql = "UPDATE users_responses SET `$field` = '$value', updated_at = NOW() WHERE id = $id";
        }
        
        $success = $conn->query($sql);
        if (!$success) {
            error_log("Error updating user response: " . $conn->error);
            error_log("SQL: " . $sql);
            $conn->close();
            return false;
        }
        
        error_log("Updated users_responses - ID: $id, Field: $field, Value: $value");
        $conn->close();
        return $id;
    } else {
        // If no record exists and this isn't a conversation start, create one
        if ($isEndConversation) {
            // If ending a conversation that never started, log it but don't create a record
            error_log("Warning: Attempted to end non-existent conversation for $phoneNumber");
            $conn->close();
            return false;
        }
        
        // Create new record for existing conversation
        $sql = "INSERT INTO users_responses (phone_number, `$field`, created_at, updated_at) 
                VALUES ('$phoneNumber', '$value', NOW(), NOW())";
        
        if ($conn->query($sql)) {
            $id = $conn->insert_id;
            error_log("Created new record for $phoneNumber (ID: $id)");
            $conn->close();
            return $id;
        } else {
            error_log("Error creating new record: " . $conn->error);
            $conn->close();
            return false;
        }
    }
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
