<?php
session_start(); // Oturum başlat

// Kullanıcı giriş yapmamışsa login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Veritabanı bağlantısını dahil et
include 'db.php';

// Form verilerini al
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $document_id = $_POST['document_id'];
    $payment_amount = $_POST['payment_amount'];

    // Güncelleme sorgusu
    $sql = "UPDATE document_details SET payment_amount = ? WHERE document_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("di", $payment_amount, $document_id);

        if ($stmt->execute()) {
            // Başarılı güncelleme
            header("Location: document_detail.php?id=" . $document_id);
            exit;
        } else {
            echo "<p>Hata: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p>Veritabanı hatası: " . $conn->error . "</p>";
    }

    // Bağlantıyı kapat
    $stmt->close();
}

$conn->close();
?>
