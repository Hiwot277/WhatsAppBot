<?php
require_once __DIR__ . '/db_utils.php';

function addColumnIfNotExists($conn, $table, $column, $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($check->num_rows == 0) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql)) {
            echo "Added column $column to $table\n";
        } else {
            echo "Error adding column $column: " . $conn->error . "\n";
        }
    } else {
        echo "Column $column already exists in $table\n";
    }
}

$conn = getDbConnection();
if (!$conn) {
    die("Connection failed: " . $conn->connect_error);
}

$columns = [
    'loans_credit_card' => 'VARCHAR(50)',
    'loans_employment_status' => 'VARCHAR(50)',
    'loans_amount' => 'VARCHAR(50)',
    'loans_pension_fund' => 'VARCHAR(10)',
    'loans_turnover' => 'VARCHAR(50)',
    'loans_business_age' => 'VARCHAR(50)',
    'loans_real_estate' => 'VARCHAR(10)',
    'loans_full_name' => 'VARCHAR(100)',
    'loans_id_number' => 'VARCHAR(50)',
    'loans_savings_potential' => 'VARCHAR(50)'
];

foreach ($columns as $col => $def) {
    addColumnIfNotExists($conn, 'users_responses', $col, $def);
}

$conn->close();
echo "Schema update complete.\n";
?>
