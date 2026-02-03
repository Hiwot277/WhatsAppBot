<?php
require_once 'db_utils.php';

$conn = getDbConnection();
if (!$conn) {
    die("Connection failed: " . $conn->connect_error);
}

$columnsToAdd = [
    'phone_num_2' => 'VARCHAR(50)',
    'id_number' => 'VARCHAR(50)',
    'welcome_response' => 'VARCHAR(50)',
    'selected_area' => 'VARCHAR(50)',
    'savings_potential_response' => 'VARCHAR(50)',
    'confirmation_response' => 'VARCHAR(50)',
    'no_savings_response' => 'VARCHAR(50)'
];

foreach ($columnsToAdd as $column => $type) {
    // Check if column exists
    $check = $conn->query("SHOW COLUMNS FROM users_responses LIKE '$column'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE users_responses ADD COLUMN $column $type";
        if ($conn->query($sql) === TRUE) {
            echo "Column '$column' added successfully.\n";
        } else {
            echo "Error adding column '$column': " . $conn->error . "\n";
        }
    } else {
        echo "Column '$column' already exists.\n";
    }
}

$conn->close();
echo "Database update complete.\n";
