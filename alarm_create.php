<?php
session_start(); // Oturum başlat

// Kullanıcı giriş yapmamışsa login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Veritabanı bağlantısını dahil et
include 'db.php';

// Belgeleri veritabanından çek
$query_documents = "SELECT id, document_name, document_code FROM documents";
$result_documents = $conn->query($query_documents);

$documents = [];
if ($result_documents) {
    while ($row = $result_documents->fetch_assoc()) {
        $documents[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen verileri al
    $document_id = $_POST['document_id'];
    $alarm_title = $_POST['alarm_title'];  // Alarm başlığını alıyoruz
    $alarm_date = $_POST['alarm_date'];
    $alarm_message = $_POST['alarm_message'];
    $status = $_POST['status'];

    // Alarmlar tablosuna alarm ekle
    $sql_alarm = "INSERT INTO alarm (document_id, alarm_title, alarm_date, alarm_message, status) 
                  VALUES (?, ?, ?, ?, ?)";

    if ($stmt_alarm = $conn->prepare($sql_alarm)) {
        // Parametreleri bağla ve sorguyu çalıştır
        $stmt_alarm->bind_param("issss", $document_id, $alarm_title, $alarm_date, $alarm_message, $status);

        if ($stmt_alarm->execute()) {
            echo "<p>Yeni alarm başarıyla eklendi!</p>";
        } else {
            echo "<p>Hata: " . $stmt_alarm->error . "</p>";
        }

        $stmt_alarm->close();
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
    <title>Yeni Alarm Oluştur</title>
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
        <h2>Yeni Alarm Oluştur</h2>

        <form method="POST" action="alarm_create.php">
            <label for="document_id">Evrak:</label>
            <select id="document_id" name="document_id" required>
                <?php foreach ($documents as $document): ?>
                    <option value="<?php echo $document['id']; ?>">
                        <?php echo $document['document_name'] . " (" . $document['document_code'] . ")"; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="alarm_title">Alarm Başlığı:</label>
            <input type="text" id="alarm_title" name="alarm_title" required placeholder="Alarm Başlığını Girin">

            <label for="alarm_date">Alarm Tarihi:</label>
            <input type="date" id="alarm_date" name="alarm_date" required>

            <label for="alarm_message">Alarm Mesajı:</label>
            <textarea id="alarm_message" name="alarm_message" rows="4" required></textarea>

            <label for="status">Durum:</label>
            <select id="status" name="status" required>
                <option value="active">Aktif</option>
                <option value="inactive">Pasif</option>
            </select>

            <button type="submit" class="submit-button">Alarm Ekle</button>
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
