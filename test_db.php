<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/scripts.php';

/**
 * Save user response to the database
 */
function saveUserResponse($phone, $field, $value) {
    $conn = getDbConnection();
    if (!$conn) {
        error_log("Failed to connect to database");
        return false;
    }

    // Sanitize input
    $phone = $conn->real_escape_string($phone);
    $field = $conn->real_escape_string($field);
    $value = $conn->real_escape_string($value);
    
    // Check if record exists
    $check = $conn->query("SELECT id FROM users_responses WHERE phone_number = '$phone'");
    
    if ($check && $check->num_rows > 0) {
        // Update existing record
        $sql = "UPDATE users_responses 
                SET `$field` = '$value', updated_at = NOW() 
                WHERE phone_number = '$phone'";
    } else {
        // Insert new record
        $sql = "INSERT INTO users_responses (phone_number, `$field`, created_at, updated_at) 
                VALUES ('$phone', '$value', NOW(), NOW())";
    }
    
    $result = $conn->query($sql);
    $conn->close();
    
    return $result !== false;
}

/**
 * Get user responses by phone number
 */
function getUserResponses($phone) {
    $conn = getDbConnection();
    if (!$conn) {
        error_log("Failed to connect to database");
        return false;
    }
    
    $phone = $conn->real_escape_string($phone);
    $result = $conn->query("SELECT * FROM users_responses WHERE phone_number = '$phone'");
    $conn->close();
    
    return $result ? $result->fetch_assoc() : false;
}

/**
 * Get all user responses
 */
function getAllUserResponses() {
    $conn = getDbConnection();
    if (!$conn) {
        error_log("Failed to connect to database");
        return false;
    }
    
    $result = $conn->query("SELECT * FROM users_responses ORDER BY updated_at DESC");
    $users = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    $conn->close();
    return $users;
}
// Test database connection
function testDbConnection() {
    $conn = getDbConnection();
    if (!$conn) {
        echo "✗ Failed to connect to database\n";
        echo "Error: " . mysqli_connect_error() . "\n";
        return false;
    }
    
    echo "✓ Connected to database successfully\n";
    
    // Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'users_responses'");
    if (!$result || $result->num_rows === 0) {
        echo "✗ Table 'users_responses' does not exist\n";
        $conn->close();
        return false;
    }
    
    // Display table structure
    echo "\n=== Table Structure ===\n";
    $columns = $conn->query("DESCRIBE users_responses");
    if ($columns) {
        while ($column = $columns->fetch_assoc()) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    }
    
    // Check for existing records
    $result = $conn->query("SELECT COUNT(*) as count FROM users_responses");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "\n=== Existing Records ===\n";
        echo "Total records: " . $row['count'] . "\n\n";
        
        if ($row['count'] > 0) {
            $sample = $conn->query("SELECT * FROM users_responses ORDER BY updated_at DESC LIMIT 3");
            if ($sample && $sample->num_rows > 0) {
                echo "=== Sample Records (3 most recent) ===\n";
                while ($data = $sample->fetch_assoc()) {
                    echo "ID: " . $data['id'] . "\n";
                    echo "Phone: " . $data['phone_number'] . "\n";
                    echo "Name: " . ($data['full_name'] ?? 'N/A') . "\n";
                    echo "Status: " . ($data['employment_status'] ?? 'N/A') . "\n";
                    echo "Salary: " . ($data['salary_range'] ?? 'N/A') . "\n";
                    echo "Updated: " . $data['updated_at'] . "\n\n";
                }
            }
        } else {
            echo "No records found in users_responses table\n";
        }
    }
    
    $conn->close();
    return true;
}

// Define test data
$testPhone = '1234567890';
$testName = 'Test User';
$testStatus = 'Employed';
$testSalary = '$50,000 - $75,000';

// Run the test
if (testDbConnection()) {
    // Test saving and retrieving data
    echo "\n=== Testing Database Operations ===\n";
    testSaveUserResponse($testPhone, $testName, $testStatus, $testSalary);
}

// Test saving and retrieving data
echo "\n=== Testing Database Operations ===\n";
testSaveUserResponse($testPhone, $testName, $testStatus, $testSalary);

/**
 * Test saving and retrieving user responses
 */
function testSaveUserResponse($phone, $name, $status, $salary) {
    echo "\n[TEST] Saving test record...\n";
    
    // Save test data
    $result1 = saveUserResponse($phone, 'full_name', $name);
    $result2 = saveUserResponse($phone, 'employment_status', $status);
    $result3 = saveUserResponse($phone, 'salary_range', $salary);
    
    if ($result1 && $result2 && $result3) {
        echo "✓ Test record saved successfully\n";
        
        // Retrieve the saved data using our function
        echo "\n[TEST] Retrieving saved record...\n";
        $userData = getUserResponses($phone);
        
        if ($userData) {
            echo "✓ Retrieved record successfully\n";
            echo "\n=== Saved Record ===\n";
            echo "Phone: " . ($userData['phone_number'] ?? 'N/A') . "\n";
            echo "Name: " . ($userData['full_name'] ?? 'N/A') . "\n";
            echo "Status: " . ($userData['employment_status'] ?? 'N/A') . "\n";
            echo "Salary: " . ($userData['salary_range'] ?? 'N/A') . "\n";
            echo "Created: " . ($userData['created_at'] ?? 'N/A') . "\n";
            echo "Updated: " . ($userData['updated_at'] ?? 'N/A') . "\n";
            
            // Test updating the record
            $newName = $name . " Updated";
            echo "\n[TEST] Updating name to '$newName'...\n";
            $updateResult = saveUserResponse($phone, 'full_name', $newName);
            
            if ($updateResult) {
                echo "✓ Record updated successfully\n";
                $updatedData = getUserResponses($phone);
                echo "New name: " . ($updatedData['full_name'] ?? 'N/A') . "\n";
                echo "Update time: " . ($updatedData['updated_at'] ?? 'N/A') . "\n";
            } else {
                echo "✗ Failed to update record\n";
            }
        } else {
            echo "✗ Failed to retrieve saved record\n";
        }
        
        // Clean up test data
        echo "\n[TEST] Cleaning up test data...\n";
        $conn = getDbConnection();
        if ($conn) {
            $cleanup = $conn->query("DELETE FROM users_responses WHERE phone_number = '" . $conn->real_escape_string($phone) . "'");
            if ($cleanup) {
                echo "✓ Test data cleaned up successfully\n";
            } else {
                echo "✗ Failed to clean up test data: " . $conn->error . "\n";
            }
            $conn->close();
        }
    } else {
        echo "✗ Failed to save test record\n";
        echo "Results: name=" . ($result1 ? 'OK' : 'FAIL') . 
             ", status=" . ($result2 ? 'OK' : 'FAIL') . 
             ", salary=" . ($result3 ? 'OK' : 'FAIL') . "\n";
    }
}

// Test getting all users
echo "\n=== Testing getAllUserResponses() ===\n";
$allUsers = getAllUserResponses();
if ($allUsers !== false) {
    echo "Found " . count($allUsers) . " user(s) in the database\n";
    if (count($allUsers) > 0) {
        echo "\nFirst user details:\n";
        $firstUser = $allUsers[0];
        echo "ID: " . $firstUser['id'] . "\n";
        echo "Phone: " . $firstUser['phone_number'] . "\n";
        echo "Name: " . ($firstUser['full_name'] ?? 'N/A') . "\n";
        echo "Last Updated: " . $firstUser['updated_at'] . "\n";
    }
} else {
    echo "✗ Failed to retrieve user list\n";
}

echo "\n=== Test Complete ===\n";
