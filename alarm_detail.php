<?php
session_start(); // Oturum başlat

// Kullanıcı giriş yapmamışsa login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Veritabanı bağlantısını dahil et
include 'db.php';

// Alarm ID'sini al
$alarm_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($alarm_id > 0) {
    // Alarm detaylarını sorgula
    $sql = "SELECT a.id, a.alarm_title, a.alarm_date, a.alarm_message, a.status, d.client_name, d.document_name 
            FROM alarm a 
            JOIN documents d ON a.document_id = d.id 
            WHERE a.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $alarm_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $alarm = $result->fetch_assoc();
    } else {
        echo "<p>Alarm bulunamadı.</p>";
        exit;
    }

    $stmt->close();
} else {
    echo "<p>Geçersiz Alarm ID.</p>";
    exit;
}

// Veritabanı bağlantısını kapat
$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alarm Detayları</title>
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

    <div class="content">
        <h1>Alarm Detayları</h1>
        <div class="document-detail">
            <h2><?php echo htmlspecialchars($alarm['alarm_title']); ?></h2>
            <p><strong>Alarm Tarihi:</strong> <?php echo htmlspecialchars($alarm['alarm_date']); ?></p>
            <p><strong>Durum:</strong> <?php echo htmlspecialchars($alarm['status']); ?></p>
            <p><strong>Müvekkil Adı:</strong> <?php echo htmlspecialchars($alarm['client_name']); ?></p>
            <p><strong>Evrak Adı:</strong> <?php echo htmlspecialchars($alarm['document_name']); ?></p>
            <p><strong>Alarm Mesajı:</strong> <?php echo nl2br(htmlspecialchars($alarm['alarm_message'])); ?></p>
        </div>
    </div>

    <div class="footer">
        <div class="footer-content">
            <p>&copy; 2024 Tüm Hakları Saklıdır.</p>
            <ul class="footer-links">
            <li><a href="hakkinda.html">Hakkında</a></li>
            <li><a href="iletisim.html">İletişim</a></li>
            <li><a href="gizlilik-politikasi.html">Gizlilik Politikası</a></li>            </ul>
        </div>
    </div>
</body>
</html>
    