<?php
// Test script to verify data flow from scripts.php to database
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db_utils.php';
require_once __DIR__ . '/scripts.php';
require_once __DIR__ . '/processor.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test database connection
$conn = getDbConnection();
if (!$conn) {
    die("[ERROR] Could not connect to database. Check your config.php settings.\n");
}
$conn->close();

error_log("=== Starting test script ===");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test phone number (use a test number to avoid affecting real data)
$testPhone = '1234567890';

// Clear any existing test data
$conn = getDbConnection();
if ($conn) {
    $conn->query("DELETE FROM users_responses WHERE phone_number = '$testPhone'");
    $conn->close();
}

// Simulate a conversation flow with button interactions
function simulateConversation($phone) {
    // Clear any existing state
    $stateFile = __DIR__ . '/state/state_' . md5($phone) . '.json';
    if (file_exists($stateFile)) {
        unlink($stateFile);
    }
    
    // Initialize state
    $state = [];
    $response = null;
    
    // Define the conversation flow with button selections
    $conversation = [
        ['input' => 'Hi', 'expected' => 'save money'],
        ['input' => 'yes', 'expected' => 'check how to save'],
        ['input' => 'tax_refund', 'expected' => 'few short questions'],
        ['input' => 'employed_6yrs', 'expected' => 'salary range'],
        ['input' => '8000_18000', 'expected' => 'tax criteria'],
        ['input' => 'yes', 'expected' => 'eligibility check'],
        ['input' => 'yes', 'expected' => 'savings potential']
    ];
    
    // Process each step in the conversation
    foreach ($conversation as $step) {
        echo "\n[TEST] You: " . $step['input'] . "\n";
        
        try {
            // Process the input through the bot
            $response = processMessage($phone, $step['input']);
            
            // Load the current state
            if (file_exists($stateFile)) {
                $state = json_decode(file_get_contents($stateFile), true) ?? [];
            }
            
            // Log the response and state
            error_log("Input: {$step['input']}");
            error_log("Response: " . print_r($response, true));
            error_log("State: " . print_r($state, true));
            
            // Output the bot's response
            if (is_array($response) && isset($response['text'])) {
                echo "[BOT] " . $response['text'] . "\n";
                
                // Show available buttons if any
                if (isset($response['buttons']) && is_array($response['buttons'])) {
                    echo "[BOT BUTTONS]:\n";
                    foreach ($response['buttons'] as $button) {
                        echo "- [{$button['id']}] {$button['text']}\n";
                    }
                }
                
                // Check if the response matches expectations
                if (stripos($response['text'], $step['expected']) === false) {
                    echo "[WARNING] Unexpected response. Expected something like: " . $step['expected'] . "\n";
                }
            } else {
                echo "[ERROR] Invalid response format\n";
                echo "[DEBUG] " . print_r($response, true) . "\n";
            }
        } catch (Exception $e) {
            echo "[ERROR] Exception: " . $e->getMessage() . "\n";
            return false;
        }
        
        // Small delay between messages
        usleep(200000); // 0.2 seconds
    }
    
    // Final verification of saved data
    if (file_exists($stateFile)) {
        $state = json_decode(file_get_contents($stateFile), true) ?? [];
        echo "\n[FINAL STATE] " . print_r($state, true) . "\n";
    } else {
        echo "\n[ERROR] No state file found after conversation\n";
        return false;
    }
    
    return $state;
}

// Function to verify data in the database
function verifyDataInDatabase($phone) {
    echo "\n[TEST] Verifying data in database...\n";
    
    $conn = getDbConnection();
    if (!$conn) {
        echo "[ERROR] Failed to connect to database\n";
        return false;
    }
    
    // First, check if the table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'users_responses'");
    if (!$tableCheck || $tableCheck->num_rows === 0) {
        echo "[ERROR] Table 'users_responses' does not exist\n";
        $conn->close();
        return false;
    }
    
    // Get table structure for debugging
    echo "\n[DEBUG] Table structure:\n";
    $columns = $conn->query("DESCRIBE users_responses");
    if ($columns) {
        while ($column = $columns->fetch_assoc()) {
            echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
        }
    }
    
    // Check for the specific record
    $result = $conn->query("SELECT * FROM users_responses WHERE phone_number = '$phone'");
    if (!$result) {
        echo "[ERROR] Database query failed: " . $conn->error . "\n";
        $conn->close();
        return false;
    }
    
    if ($result->num_rows === 0) {
        echo "[ERROR] No data found in database for phone: $phone\n";
        
        // Check if there are any records at all
        $allRecords = $conn->query("SELECT COUNT(*) as count FROM users_responses");
        if ($allRecords) {
            $count = $allRecords->fetch_assoc()['count'];
            echo "[DEBUG] Total records in table: $count\n";
            
            if ($count > 0) {
                $sample = $conn->query("SELECT phone_number, created_at FROM users_responses LIMIT 5");
                echo "[DEBUG] Sample records (first 5):\n";
                while ($row = $sample->fetch_assoc()) {
                    echo "- Phone: " . $row['phone_number'] . " (Created: " . $row['created_at'] . ")\n";
                }
            }
        }
        
        $conn->close();
        return false;
    }
    
    // Get the data
    $data = $result->fetch_assoc();
    
    echo "\n[SUCCESS] Found data in database for phone: $phone\n";
    echo "\n=== Data in database ===\n";
    
    // Expected fields and their values (update these based on your test data)
    $expectedData = [
        'phone_number' => $phone,
        'full_name' => 'John Doe',
        'employment_status' => 'Employed',
        'salary_range' => '$50,000 - $75,000'
    ];
    
    $allMatch = true;
    
    // Check each expected field
    foreach ($expectedData as $field => $expectedValue) {
        $actualValue = $data[$field] ?? null;
        $status = ($actualValue == $expectedValue) ? '✓' : '✗';
        
        if ($status === '✗') {
            $allMatch = false;
        }
        
        echo sprintf("%-20s: %-30s %s %s\n", 
            $field, 
            is_null($actualValue) ? 'NULL' : "'$actualValue'",
            $status,
            $status === '✗' ? "(Expected: '$expectedValue')" : ''
        );
    }
    
    // Show all fields from the database for debugging
    echo "\n[DEBUG] All fields from database record:\n";
    foreach ($data as $key => $value) {
        if (!array_key_exists($key, $expectedData)) {
            echo "- $key: " . (is_null($value) ? 'NULL' : "'$value'") . "\n";
        }
    }
    
    $conn->close();
    
    if (!$allMatch) {
        echo "\n[WARNING] Some fields did not match expected values\n";
    } else {
        echo "\n[SUCCESS] All expected fields match!\n";
    }
    
    return $allMatch;
}

// Run the test
echo "=== Starting test: Script to Database Flow ===\n";

// 1. First, simulate the conversation
echo "\n=== Simulating conversation ===\n";
simulateConversation($testPhone);

// 2. Then verify the data was saved to the database
echo "\n=== Verifying database content ===\n";
$success = verifyDataInDatabase($testPhone);

// 3. Output final result
if ($success) {
    echo "\n✓ Test passed: Data was successfully saved to the database!\n";
} else {
    echo "\n✗ Test failed: Data was not saved to the database\n";
}

echo "\n=== Test complete ===\n";
?>
