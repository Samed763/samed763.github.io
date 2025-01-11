<?php
session_start(); // Oturum başlat

// Kullanıcı giriş yapmamışsa login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Veritabanı bağlantısını dahil et
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Formdan gelen verileri al
    $document_id = $_POST['document_id'];
    $alarm_title = $_POST['alarm_title'];  // Alarm başlığını alıyoruz
    $alarm_date = $_POST['alarm_date'];
    $alarm_message = $_POST['alarm_message'];
    $status = $_POST['status'];

    // document_id'nin varlığını kontrol et
    $sql_check = "SELECT id FROM documents WHERE id = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("i", $document_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
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
        } else {
            echo "<p>Hata: Geçersiz Evrak ID.</p>";
        }

        $stmt_check->close();
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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alarmlar</title>
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

    <div class="documents">
        <!-- Yeni Alarm Oluştur Butonu -->
        <a href="alarm_create.php" class="create-document-button">Yeni Alarm Oluştur</a>

        <h2>Alarm Listesi</h2>

        <!-- Filtreleme Formu -->
        <form method="GET" action="alarm.php" class="filter-form">
            <div class="filter-group">
                <label for="alarm_title">Alarm Başlığı:</label>
                <input type="text" id="alarm_title" name="alarm_title">
            </div>
            <div class="filter-group">
                <label for="alarm_date">Alarm Tarihi:</label>
                <input type="date" id="alarm_date" name="alarm_date">
            </div>
            <button type="submit" class="filter-button">Filtrele</button>
        </form>

        <div class="document-list">
        <?php
        // Veritabanı bağlantısını dahil et
        include 'db.php';

        // Veritabanı adını güncelle
        $conn->select_db('okuldb1'); // Veritabanı adını 'okuldb1' olarak kontrol edin.

        // Filtreleme sorgusu
        $whereClauses = [];
        $params = [];

        if (!empty($_GET['alarm_title'])) {
            $whereClauses[] = "alarm_title LIKE ?";  // alarm_title'ı sorguluyoruz.
            $params[] = "%" . $_GET['alarm_title'] . "%";
        }

        if (!empty($_GET['alarm_date'])) {
            $whereClauses[] = "alarm_date = ?";
            $params[] = $_GET['alarm_date'];
        }

        $sql = "SELECT id, alarm_title, alarm_date, status FROM alarm";  // alarm_title'ı da seçiyoruz.
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        // Sorguyu hazırla ve çalıştır
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("Sorgu hazırlanırken hata oluştu: " . $conn->error);
        }

        if (!empty($params)) {
            $types = str_repeat("s", count($params)); // Parametre türü
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Her satırı döngüyle işle
            while ($row = $result->fetch_assoc()) {
                echo "
                <div class='document-card'>
                    <h3>Başlık: {$row['alarm_title']}</h3> <!-- alarm_title ile başlık gösteriliyor -->
                    <p><strong>Tarih:</strong> {$row['alarm_date']}</p>
                    <p><strong>Durum:</strong> {$row['status']}</p>
                    <a href='alarm_detail.php?id={$row['id']}' class='details-link'>Detaylar</a>
                </div>";
            }
        } else {
            echo "<p>Alarm bulunamadı.</p>";
        }

        // Bağlantıyı kapat
        $stmt->close();
        $conn->close();
        ?>
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
