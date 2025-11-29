<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');

echo "=== Database Setup Script ===\n";

try {
    // Connect to MySQL server without selecting a database
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error . "\n");
    }
    
    echo "Connected to MySQL server successfully.\n";
    
    // Create database if it doesn't exist
    $dbName = 'whatsapp_bot';
    $sql = "CREATE DATABASE IF NOT EXISTS `$dbName`";
    
    if ($conn->query($sql) === TRUE) {
        echo "Database '$dbName' created successfully or already exists.\n";
    } else {
        echo "Error creating database: " . $conn->error . "\n";
    }
    
    // Select the database
    $conn->select_db($dbName);
    
    // Create users_responses table
    $sql = "CREATE TABLE IF NOT EXISTS `users_responses` (
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
    
    if ($conn->query($sql) === TRUE) {
        echo "Table 'users_responses' created successfully or already exists.\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
    
    // Show tables in the database
    $result = $conn->query("SHOW TABLES");
    
    if ($result->num_rows > 0) {
        echo "\nTables in database '$dbName':\n";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "\n";
        }
    } else {
        echo "No tables found in database '$dbName'.\n";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "\nDatabase setup completed.\n";
?>
