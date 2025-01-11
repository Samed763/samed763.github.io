<?php
session_start(); // Start session

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Include database connection
include 'db.php';

// Define tables and their columns
$tables = [
    'alarm' => ['alarm_title', 'document_id', 'alarm_date', 'alarm_message', 'status', 'created_at', 'updated_at'],
    'documents' => ['lawyer_name', 'client_name', 'document_name', 'document_code'],
    'document_details' => ['document_id', 'details_text', 'start_date'],
    'employee_salaries' => ['employee_name', 'salary_amount', 'payment_date', 'status'],
    'financial_transactions' => ['document_id', 'payment_amount', 'payment_date', 'transaction_type', 'status', 'payed_amount'],
    'payment_details' => ['payment_amount', 'payment_date', 'payment_method', 'document_info', 'category', 'status', 'user_name', 'transaction_type'],
    'users' => ['username', 'email', 'password', 'created_at', 'updated_at']
];

// Define predefined values for specific columns
$predefined_values = [
    'alarm' => [
        'status' => ['active', 'inactive']
    ],
    'employee_salaries' => [
        'status' => ['paid', 'unpaid']
    ],
    'financial_transactions' => [
        'transaction_type' => ['income', 'expense'],
        'status' => ['pending', 'completed', 'overdue']
    ],
    'payment_details' => [
        'status' => ['pending', 'completed', 'overdue'],
        'transaction_type' => ['income', 'expense']
    ]
];

// Get the selected table from the query string
$selected_table = isset($_GET['table']) ? $_GET['table'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create'])) {
    $table = $_POST['table'];
    $columns = $tables[$table];
    $values = [];
    foreach ($columns as $column) {
        $values[$column] = $_POST[$column];
    }

    // Build the SQL query
    $columns_sql = implode(", ", array_keys($values));
    $placeholders = implode(", ", array_fill(0, count($values), '?'));
    $sql = "INSERT INTO $table ($columns_sql) VALUES ($placeholders)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param(str_repeat('s', count($values)), ...array_values($values));
        if ($stmt->execute()) {
            echo "<p>Record created successfully in $table!</p>";
        } else {
            echo "<p>Error creating record: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p>Database error: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Record</title>
    <link rel="stylesheet" href="adminpanelstyle.css">
</head>
<body>
    <div class="edit-container" style="text-align: center;">
        <h2 class="edit-heading">Create New Record</h2>
        <form method="POST" action="" class="edit-form" style="display: inline-block; text-align: left;">
            <input type="hidden" name="table" value="<?php echo $selected_table; ?>">
            <?php foreach ($tables[$selected_table] as $column): ?>
                <div class="form-group">
                    <label for="<?php echo $column; ?>"><?php echo ucfirst($column); ?>:</label>
                    <?php if (isset($predefined_values[$selected_table][$column])): ?>
                        <select name="<?php echo $column; ?>" id="<?php echo $column; ?>">
                            <?php foreach ($predefined_values[$selected_table][$column] as $option): ?>
                                <option value="<?php echo $option; ?>">
                                    <?php echo ucfirst($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php elseif (strpos($column, 'date') !== false): ?>
                        <input type="date" name="<?php echo $column; ?>" id="<?php echo $column; ?>" required>
                    <?php else: ?>
                        <input type="text" name="<?php echo $column; ?>" id="<?php echo $column; ?>" required>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <button type="submit" name="create">Create</button>
        </form>
        <button class="go-back-button" onclick="window.history.back();">Go Back</button>
    </div>
</body>
</html>
