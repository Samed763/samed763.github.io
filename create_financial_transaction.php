<?php

session_start(); // Oturum başlat

// Kullanıcı giriş yapmamışsa, login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}
$connection = new mysqli("localhost", "root", "", "okuldb1");

// Belgeleri veritabanından çek
$query_documents = "SELECT id, document_name, document_code FROM documents";
$result_documents = $connection->query($query_documents);

$documents = [];
if ($result_documents) {
    while ($row = $result_documents->fetch_assoc()) {
        $documents[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $document_id = $_POST['document_id'];
    $payment_amount = abs($_POST['payment_amount']); // Ensure positive amount
    $payment_date = $_POST['payment_date'];
    $transaction_type = $_POST['transaction_type'];
    $status = $_POST['status'];
    $operation = $_POST['operation'];

    // Mevcut ödeme miktarını al
    $query_get_amount = "SELECT payment_amount FROM financial_transactions WHERE document_id = '$document_id'";
    $result_get_amount = $connection->query($query_get_amount);
    if ($result_get_amount && $result_get_amount->num_rows > 0) {
        $row = $result_get_amount->fetch_assoc();
        $current_amount = $row['payment_amount'];

        // Miktar değişikliğini hesapla
        if ($operation == 'add') {
            $payment_amount = $current_amount + $payment_amount;
        } else if ($operation == 'subtract') {
            $payment_amount = $current_amount - $payment_amount;
            if ($payment_amount < 0) {
                $payment_amount = abs($payment_amount);
                $transaction_type = 'expense';
            }
        } else if ($operation == 'net') {
            $payment_amount = $payment_amount;
        }

        // Finansal işlemi güncelle
        $query_update = "UPDATE financial_transactions SET payment_amount = '$payment_amount', payment_date = '$payment_date', transaction_type = '$transaction_type', status = '$status' WHERE document_id = '$document_id'";
        if ($connection->query($query_update) === TRUE) {
            echo "Record updated successfully";
        } else {
            echo "Error: " . $query_update . "<br>" . $connection->error;
        }
    } else {
        // Yeni finansal işlem oluştur
        $query_insert = "INSERT INTO financial_transactions (document_id, payment_amount, payment_date, transaction_type, status) VALUES ('$document_id', '$payment_amount', '$payment_date', '$transaction_type', '$status')";
        if ($connection->query($query_insert) === TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $query_insert . "<br>" . $connection->error;
        }
    }

    $connection->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Financial Transaction</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="form-container">
        <h2>Create Financial Transaction</h2>
        <form method="POST">
            <label for="document_id">Document:</label>
            <select id="document_id" name="document_id" required>
                <?php foreach ($documents as $document): ?>
                    <option value="<?php echo $document['id']; ?>">
                        <?php echo $document['document_name'] . " (" . $document['document_code'] . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label for="payment_amount">Payment Amount:</label>
            <input type="number" id="payment_amount" name="payment_amount" required>

            <label for="payment_date">Payment Date:</label>
            <input type="date" id="payment_date" name="payment_date" required>
            
            <label for="operation">Operation:</label>
            <select id="operation" name="operation" required>
                <option value="net">Net</option>
                <option value="add">Add</option>
                <option value="subtract">Subtract</option>
            </select>
            
            <label for="transaction_type">Transaction Type:</label>
            <select id="transaction_type" name="transaction_type">
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>
            
            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="pending">Pending</option>
                <option value="completed">Completed</option>
                <option value="overdue">Overdue</option>
                <option value="upcoming">Upcoming</option>
            </select>
            
            <button type="submit" class="submit-button">Submit</button>
        </form>
        <br>
        <a href="anasayfa.php" class="return-button">Return to Anasayfa</a>
    </div>
</body>
</html>
