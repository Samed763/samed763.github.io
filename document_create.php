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
    // Formdan gelen verileri al
    $lawyer_name = $_POST['lawyer_name'];
    $client_name = $_POST['client_name'];
    $document_name = $_POST['document_name'];
    $document_code = strtoupper($_POST['document_code']); // document_code'u büyük harfe çevir

    // document_code doğrulaması
    if (!preg_match('/^[A-Z]{3}\d{3}$/', $document_code)) {
        echo "<p>Hata: Evrak kodu 3 büyük harf ve 3 rakamdan oluşmalıdır.</p>";
        exit;
    }

    // Evrak detayları için ek alanlar al
    $details_text = $_POST['details_text']; // Evrak detayları
    $start_date = $_POST['start_date']; // Evrak başlangıç tarihi
    $payment_date = $_POST['payment_date']; // Ödeme tarihi
    $payment_amount = $_POST['payment_amount']; // Ödeme miktarı
    $transaction_type = "income"; // İşlem türü her zaman "income" olarak ayarlanır
    $status = $_POST['status']; // Durum

    // 1. Adım: documents tablosuna evrakı ekle
    $sql_documents = "INSERT INTO documents (lawyer_name, client_name, document_name, document_code) 
                      VALUES (?, ?, ?, ?)";

    if ($stmt_documents = $conn->prepare($sql_documents)) {
        // Parametreleri bağla ve sorguyu çalıştır
        $stmt_documents->bind_param("ssss", $lawyer_name, $client_name, $document_name, $document_code);

        if ($stmt_documents->execute()) {
            // Yeni eklenen evrakın id'sini al
            $document_id = $stmt_documents->insert_id;
        } else {
            echo "<p>Hata: " . $stmt_documents->error . "</p>";
            exit;
        }

        $stmt_documents->close();
    } else {
        echo "<p>Veritabanı hatası: " . $conn->error . "</p>";
        exit;
    }

    // 2. Adım: financial_transactions tablosuna finansal detayları ekle
    $sql_financial = "INSERT INTO financial_transactions (document_id, payment_amount, payment_date, transaction_type, status) 
                      VALUES (?, ?, ?, ?, ?)";

    if ($stmt_financial = $conn->prepare($sql_financial)) {
        // Parametreleri bağla ve sorguyu çalıştır
        $stmt_financial->bind_param("issss", $document_id, $payment_amount, $payment_date, $transaction_type, $status);

        if ($stmt_financial->execute()) {
            echo "<p>Yeni evrak başarıyla eklendi!</p>";
        } else {
            echo "<p>Hata: " . $stmt_financial->error . "</p>";
        }

        $stmt_financial->close();
    } else {
        echo "<p>Veritabanı hatası: " . $conn->error . "</p>";
    }

    // Bağlantıyı kapat
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <link rel="stylesheet" href="styles.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Evrak Oluştur</title>
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
        <h2>Yeni Evrak Oluştur</h2>
        
        <form method="POST" action="document_create.php">
            <label for="lawyer_name">Avukat Adı:</label>
            <input type="text" id="lawyer_name" name="lawyer_name" required>

            <label for="client_name">Müvekkil Adı:</label>
            <input type="text" id="client_name" name="client_name" required>

            <label for="document_name">Evrak Adı:</label>
            <input type="text" id="document_name" name="document_name" required>

            <label for="document_code">Evrak Kodu:</label>
            <input type="text" id="document_code" name="document_code" pattern="[A-Z]{3}\d{3}" title="3 büyük harf ve 3 rakamdan oluşmalıdır" required>

            <label for="details_text">Evrak Detayları:</label>
            <textarea id="details_text" name="details_text" rows="4" required></textarea>

            <label for="start_date">Evrak Başlangıç Tarihi:</label>
            <input type="date" id="start_date" name="start_date" required>

            <label for="payment_date">Ödeme Tarihi:</label>
            <input type="date" id="payment_date" name="payment_date" required>

            <label for="payment_amount">Ödeme Miktarı:</label>
            <input type="number" id="payment_amount" name="payment_amount" required>

            <!-- İşlem Türü alanını kaldır -->
            <!-- <label for="transaction_type">İşlem Türü:</label>
            <select id="transaction_type" name="transaction_type" required>
                <option value="income">Gelir</option>
            </select> -->

            <label for="status">Durum:</label>
            <select id="status" name="status" required>
                <option value="pending">Beklemede</option>
                <option value="completed">Tamamlandı</option>
            </select>

            <button type="submit" class="submit-button">Evrak Ekle</button>
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
