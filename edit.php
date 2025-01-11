<?php
session_start(); // Start session

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Include database connection
include 'db.php';

// Get table and id from query string
$table = $_GET['table'];
$id = $_GET['id'];

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

// Fetch the row to be edited
$sql = "SELECT * FROM $table WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Update the row with new values
    $update_sql = "UPDATE $table SET ";
    $params = [];
    $types = '';

    foreach ($row as $column => $value) {
        if ($column != 'id') {
            $update_sql .= "$column = ?, ";
            $params[] = $_POST[$column];
            $types .= 's';
        }
    }

    $update_sql = rtrim($update_sql, ', ') . " WHERE id = ?";
    $params[] = $id;
    $types .= 'i';

    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo "<p>Record updated successfully!</p>";
    } else {
        echo "<p>Error updating record: " . $stmt->error . "</p>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Record</title>
    <link rel="stylesheet" href="adminpanelstyle.css">
</head>
<body>
    <div class="edit-container">
        <h2 class="edit-heading">Edit Record</h2>
        <form method="POST" action="" class="edit-form">
            <?php foreach ($row as $column => $value): ?>
                <label for="<?php echo $column; ?>"><?php echo ucfirst($column); ?>:</label>
                <?php if (isset($predefined_values[$table][$column])): ?>
                    <select name="<?php echo $column; ?>" id="<?php echo $column; ?>">
                        <?php foreach ($predefined_values[$table][$column] as $option): ?>
                            <option value="<?php echo $option; ?>" <?php if ($value == $option) echo 'selected'; ?>>
                                <?php echo ucfirst($option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input type="text" name="<?php echo $column; ?>" id="<?php echo $column; ?>" value="<?php echo $value; ?>">
                <?php endif; ?>
            <?php endforeach; ?>
            <button type="submit">Update</button>
        </form>
        <button class="go-back-button" onclick="window.history.back();">Go Back</button>
    </div>
</body>
</html>