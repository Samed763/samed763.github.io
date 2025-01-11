<?php

session_start(); // Oturum başlat

// Kullanıcı giriş yapmamışsa, login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
$connection = new mysqli("localhost", "root", "", "okuldb1");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $employee_name = $_POST['employee_name'];
    $salary_amount = $_POST['salary_amount'];
    $payment_date = $_POST['payment_date'];
    $status = $_POST['status'];

    $query = "INSERT INTO employee_salaries (employee_name, salary_amount, payment_date, status) VALUES ('$employee_name', '$salary_amount', '$payment_date', '$status')";
    if ($connection->query($query) === TRUE) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $query . "<br>" . $connection->error;
    }
    $connection->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Employee Salary</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Create Employee Salary</h2>
        <form method="POST">
            <label for="employee_name">Employee Name:</label>
            <input type="text" id="employee_name" name="employee_name" required>
            
            <label for="salary_amount">Salary Amount:</label>
            <input type="number" id="salary_amount" name="salary_amount" required>
            
            <label for="payment_date">Payment Date:</label>
            <input type="date" id="payment_date" name="payment_date" required>
            
            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="unpaid">Unpaid</option>
                <option value="paid">Paid</option>
            </select>
            
            <button type="submit" class="submit-button">Submit</button>
        </form>
        <br>
        <a href="anasayfa.php" class="return-button">Return to Anasayfa</a>
    </div>
</body>
</html>
