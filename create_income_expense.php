<?php
session_start(); // Oturum başlat

// Kullanıcı giriş yapmamışsa, login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Veritabanı bağlantısını dahil et
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['delete_id']) && isset($_POST['table_name'])) {
        // ...existing code...
    } else {
        $payment_date = $_POST['payment_date'];
        $payment_method = $_POST['payment_method'];
        $document_info = $_POST['document_info'];
        $category = $_POST['category'];
        $status = $_POST['status'];
        $user_name = $_SESSION['username'];
        $transaction_type = $_POST['transaction_type'];

        // Yeni gelir/gider kaydı ekle
        $sql_insert = "INSERT INTO payment_details (payment_amount, payment_date, payment_method, document_info, category, status, user_name, transaction_type) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt_insert = $conn->prepare($sql_insert)) {
            $stmt_insert->bind_param("dsssssss", $payment_amount, $payment_date, $payment_method, $document_info, $category, $status, $user_name, $transaction_type);

            if ($stmt_insert->execute()) {
                echo "<p>Yeni kayıt başarıyla eklendi!</p>";
            } else {
                echo "<p>Hata: " . $stmt_insert->error . "</p>";
            }

            $stmt_insert->close();
        } else {
            echo "<p>Veritabanı hatası: " . $conn->error . "</p>";
        }

        // Bağlantıyı kapat
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gelir/Gider Kaydı Oluştur</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="navbar">
        <a href="anasayfa.php">Ana Sayfa</a>
        <a href="evraklar.php">Evraklar</a>
        <a href="finansal.php">Finansal</a>
        <a href="alarm.php">Alarm</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="form-container">
        <h2>Gelir/Gider Kaydı Oluştur</h2>
        <form method="POST" action="create_income_expense.php">
            <label for="payment_amount">Ödeme Miktarı:</label>
            <input type="number" id="payment_amount" name="payment_amount" required>

            <label for="payment_date">Ödeme Tarihi:</label>
            <input type="date" id="payment_date" name="payment_date" required>

            <label for="payment_method">Ödeme Yöntemi:</label>
            <input type="text" id="payment_method" name="payment_method" required>

            <label for="document_info">Ödeme Hakkında Bilgi:</label>
            <input type="text" id="document_info" name="document_info" required>

            <label for="category">Kategori:</label>
            <input type="text" id="category" name="category" required>

            <label for="status">Durum:</label>
            <select id="status" name="status" required>
                <option value="pending">Beklemede</option>
                <option value="completed">Tamamlandı</option>
            </select>

            <label for="transaction_type">İşlem Türü:</label>
            <select id="transaction_type" name="transaction_type" required>
                <option value="income">Gelir</option>
                <option value="expense">Gider</option>
            </select>

            <button type="submit" class="submit-button">Kaydet</button>
        </form>
    </div>

    <div class="footer">
        <div class="footer-content">
            <p>&copy; 2024 Tüm Hakları Saklıdır.</p>
            <ul class="footer-links">
                <li><a href="hakkinda.html">Hakkında</a></li>
                <li><a href="iletisim.html">İletişim</a></li>
                <li><a href="gizlilik-politikasi.html">Gizlilik Politikası</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
