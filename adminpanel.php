<?php
session_start(); // Start session

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Include database connection
include 'db.php';

// Function to delete a row from a table
function deleteRow($conn, $table, $id) {
    $sql = "DELETE FROM $table WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo "<p>Record deleted successfully from $table!</p>";
        } else {
            echo "<p>Error deleting record: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        echo "<p>Database error: " . $conn->error . "</p>";
    }
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete'])) {
    $table = $_POST['table'];
    $id = $_POST['id'];
    deleteRow($conn, $table, $id);
}

// Define tables and their columns
$tables = [
    'alarm' => ['id', 'alarm_title', 'document_id', 'alarm_date', 'alarm_message', 'status', 'created_at', 'updated_at'],
    'documents' => ['id', 'lawyer_name', 'client_name', 'document_name', 'document_code'],
    'document_details' => ['id', 'document_id', 'details_text', 'start_date'],
    'employee_salaries' => ['id', 'employee_name', 'salary_amount', 'payment_date', 'status'],
    'financial_transactions' => ['id', 'document_id', 'payment_amount', 'payment_date', 'transaction_type', 'status', 'payed_amount'],
    'payment_details' => ['id', 'payment_amount', 'payment_date', 'payment_method', 'document_info', 'category', 'status', 'user_name', 'transaction_type'],
    'users' => ['id', 'username', 'email', 'password', 'created_at', 'updated_at']
];

// Get the selected table from the query string or default to the first table
$selected_table = isset($_GET['table']) ? $_GET['table'] : array_keys($tables)[0];

// Get the sort column and order from the query string or default to the first column and ascending order
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : $tables[$selected_table][0];
$sort_order = isset($_GET['order']) && $_GET['order'] == 'desc' ? 'desc' : 'asc';

// Toggle sort order for the next click
$next_order = $sort_order == 'asc' ? 'desc' : 'asc';

// Get the current page number from the query string or default to 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Get the limit from the query string or default to 20
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $limit;

// Handle filter request
$filters = [];
foreach ($tables[$selected_table] as $column) {
    if (isset($_GET[$column]) && $_GET[$column] !== '') {
        $filters[$column] = $_GET[$column];
    }
}

// Build the WHERE clause for filtering
$where_clauses = [];
foreach ($filters as $column => $value) {
    $where_clauses[] = "$column LIKE '%" . $conn->real_escape_string($value) . "%'";
}
$where_sql = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Get the total number of records for the selected table with filters
$total_sql = "SELECT COUNT(*) FROM $selected_table $where_sql";
$total_result = $conn->query($total_sql);
$total_rows = $total_result->fetch_row()[0];
$total_pages = ceil($total_rows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="adminpanelstyle.css">
</head>
<body>
    <div class="navbar">
        <?php foreach ($tables as $table => $columns): ?>
            <a href="?table=<?php echo $table; ?>" <?php if ($selected_table == $table) echo 'class="active"'; ?>>
                <?php echo ucfirst($table); ?>
            </a>
        <?php endforeach; ?>
        <a href="logout.php" class="logout">Logout</a>
    </div>
    <div class="container">
        <form method="GET" action="" class="form-limit">
            <input type="hidden" name="table" value="<?php echo $selected_table; ?>">
            <input type="hidden" name="sort" value="<?php echo $sort_column; ?>">
            <input type="hidden" name="order" value="<?php echo $sort_order; ?>">
            <label for="limit">Records per page:</label>
            <select name="limit" id="limit" onchange="this.form.submit()">
                <option value="20" <?php if ($limit == 20) echo 'selected'; ?>>20</option>
                <option value="50" <?php if ($limit == 50) echo 'selected'; ?>>50</option>
                <option value="100" <?php if ($limit == 100) echo 'selected'; ?>>100</option>
            </select>
        </form>
        <form method="GET" action="" class="form-filter">
            <input type="hidden" name="table" value="<?php echo $selected_table; ?>">
            <input type="hidden" name="sort" value="<?php echo $sort_column; ?>">
            <input type="hidden" name="order" value="<?php echo $sort_order; ?>">
            <input type="hidden" name="limit" value="<?php echo $limit; ?>">
            <?php foreach ($tables[$selected_table] as $column): ?>
                <div class="form-group">
                    <label for="<?php echo $column; ?>"><?php echo ucfirst($column); ?>:</label>
                    <input type="text" name="<?php echo $column; ?>" id="<?php echo $column; ?>" value="<?php echo isset($filters[$column]) ? $filters[$column] : ''; ?>">
                </div>
            <?php endforeach; ?>
            <div style="width: 100%; text-align: center; margin-top: 10px;">
                <button type="submit">Filter</button>
            </div>
        </form>
        <?php
        if (array_key_exists($selected_table, $tables)) {
            $columns = $tables[$selected_table];
            $sql = "SELECT * FROM $selected_table $where_sql ORDER BY $sort_column $sort_order LIMIT $limit OFFSET $offset";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                echo "<h2>$selected_table</h2>";
                echo "<div class='create-button-container'><a href='admincreate.php?table=$selected_table' class='create-button'>Create New</a></div>"; // Modified line
                echo "<table><tr>";
                // Fetch table headers
                foreach ($columns as $column) {
                    echo "<th><a href='?table=$selected_table&sort=$column&order=$next_order'>$column</a></th>";
                }
                echo "<th>Action</th></tr>";

                // Fetch table rows
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($columns as $column) {
                        echo "<td>{$row[$column]}</td>";
                    }
                    echo "<td>
                            <form method='POST' action=''>
                                <input type='hidden' name='table' value='$selected_table'>
                                <input type='hidden' name='id' value='{$row['id']}'>
                                <input type='submit' name='delete' value='Delete'>
                            </form>
                            <a href='edit.php?table=$selected_table&id={$row['id']}' class='edit-button'>Edit</a>
                          </td>";
                    echo "</tr>";
                }
                echo "</table>";

                // Pagination links
                echo "<div class='pagination'>";
                for ($i = 1; $i <= $total_pages; $i++) {
                    echo "<a href='?table=$selected_table&page=$i&sort=$sort_column&order=$sort_order&limit=$limit'";
                    if ($page == $i) echo " class='active'";
                    echo ">$i</a> ";
                }
                echo "</div>";
            } else {
                echo "<p>0 results for $selected_table</p>";
            }
        } else {
            echo "<p>Invalid table selected.</p>";
        }

        $conn->close();
        ?>
    </div>
</body>
</html>