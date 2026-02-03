<?php
// Database configuration
// define('DB_HOST', 'localhost');
// define('DB_USERNAME', 'pdnatsrzkb');  // Database username
// define('DB_PASSWORD', 'fpA4Pf9Mnu');  // Database password
// define('DB_NAME', 'pdnatsrzkb');

define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');  // Database username
define('DB_PASSWORD', '');  // Database password
define('DB_NAME', 'whatsapp_bot');


// Google Sheets Webhook URL
define('GOOGLE_SHEETS_WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbxEhCeIdf7_vFehAWt5JF9jiKA6jBzW8pgKadhlHMlcyvfNhF7BofF3gA55PDRlYJg/exec');

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create database connection
function getDbConnection() {
    // Debug: Log connection attempt
    error_log("[DB] Attempting to connect to database: " . DB_NAME . " on " . DB_HOST . " as " . DB_USERNAME);
    
    try {
        $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    } catch (Exception $e) {
        $error = "Database connection failed: " . $e->getMessage();
        error_log("[DB ERROR] $error");
        return false;
    }
    
    // Check connection
    if ($conn->connect_error) {
        $error = "Database connection failed: " . $conn->connect_error;
        error_log("[DB ERROR] $error");
        return false;
    }
    
    // Set charset to ensure proper encoding
    $conn->set_charset("utf8mb4");
    
    error_log("[DB] Successfully connected to database");
    return $conn;
}

// Create tables if they don't exist
function initializeDatabase() {
    $conn = getDbConnection();
    if (!$conn) {
        error_log("Failed to connect to database in initializeDatabase");
        return false;
    }
    
    // Create the main tax refund table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS users_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        phone_number VARCHAR(20) NOT NULL,
        full_name VARCHAR(100),
        employment_status VARCHAR(50),
        salary_range VARCHAR(50),
        tax_criteria VARCHAR(10),
        eligibility_check_1 VARCHAR(10),
        eligibility_check_2 VARCHAR(10),
        savings_potential VARCHAR(10),
        conversation_start TIMESTAMP NULL,
        conversation_end TIMESTAMP NULL,
        conversation_complete BOOLEAN DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_phone_number (phone_number),
        INDEX idx_conversation_complete (conversation_complete)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($sql) !== TRUE) {
        error_log("Error creating users_responses table: " . $conn->error);
        $conn->close();
        return false;
    }

    // Create a separate table for fast loans flow answers
    $sqlLoans = "CREATE TABLE IF NOT EXISTS loans_responses (
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

    if ($conn->query($sqlLoans) !== TRUE) {
        error_log("Error creating loans_responses table: " . $conn->error);
        $conn->close();
        return false;
    }
    
    // Check and add full_name column if it doesn't exist
    $checkColumn = $conn->query("SHOW COLUMNS FROM users_responses LIKE 'full_name'");
    if ($checkColumn && $checkColumn->num_rows == 0) {
        $alterSql = "ALTER TABLE users_responses ADD COLUMN full_name VARCHAR(100) AFTER phone_number";
        if ($conn->query($alterSql) !== TRUE) error_log("Error adding full_name column: " . $conn->error);
    }

    // List of additional columns to ensure existence
    $missingColumns = [
        'welcome_response' => "VARCHAR(255)",
        'selected_area' => "VARCHAR(50)",
        'phone_num_2' => "VARCHAR(50)",
        'id_number' => "VARCHAR(50)",
        'savings_potential_response' => "VARCHAR(50)",
        'confirmation_response' => "VARCHAR(50)",
        'no_savings_response' => "VARCHAR(50)"
    ];

    foreach ($missingColumns as $col => $def) {
        $check = $conn->query("SHOW COLUMNS FROM users_responses LIKE '$col'");
        if ($check && $check->num_rows == 0) {
            $alter = "ALTER TABLE users_responses ADD COLUMN $col $def";
            if ($conn->query($alter) !== TRUE) {
                error_log("Error adding $col column: " . $conn->error);
            } else {
                error_log("Successfully added column $col");
            }
        }
    }
    
    // Log table structure for debugging
    $result = $conn->query("DESCRIBE users_responses");
    if ($result) {
        $columns = [];
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        error_log("users_responses table columns: " . implode(', ', $columns));
    }
    
    $conn->close();
    return true;
}

// Initialize database on include
initializeDatabase();
