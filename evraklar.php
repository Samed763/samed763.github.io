<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evraklar</title>
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
        <!-- Yeni Evrak Oluştur Butonu -->
        <a href="document_create.php" class="create-document-button">Yeni Evrak Oluştur</a>

        <h2>Evrak Listesi</h2>

        <!-- Filtreleme Formu -->
        <form method="GET" action="evraklar.php" class="filter-form">
            <div class="filter-group">
            <label for="lawyer_name">Avukat Adı:</label>
            <input type="text" id="lawyer_name" name="lawyer_name">
            </div>
            <div class="filter-group">
            <label for="client_name">Müvekkil Adı:</label>
            <input type="text" id="client_name" name="client_name">
            </div>


            <div class="filter-group">
            <label for="document_code">Evrak Kodu:</label>
            <input type="text" id="document_code" name="document_code">
            </div>


            <button type="submit" class="filter-button">Filtrele</button>
        </form>

        <div class="document-list">
        <?php
        session_start(); // Oturum başlat

        // Kullanıcı giriş yapmamışsa, login.php'ye yönlendir
        if (!isset($_SESSION['username'])) {
            header("Location: login.php");
            exit;
        }
        // Veritabanı bağlantısını dahil et
        include 'db.php';

        // Veritabanı adını güncelle
        $conn->select_db('okuldb1');

        // Filtreleme sorgusu
        $whereClauses = [];
        $params = [];

        if (!empty($_GET['lawyer_name'])) {
            $whereClauses[] = "lawyer_name LIKE ?";
            $params[] = "%" . $_GET['lawyer_name'] . "%";
        }

        if (!empty($_GET['client_name'])) {
            $whereClauses[] = "client_name LIKE ?";
            $params[] = "%" . $_GET['client_name'] . "%";
        }

        if (!empty($_GET['document_code'])) {
            $whereClauses[] = "document_code LIKE ?";
            $params[] = "%" . $_GET['document_code'] . "%";
        }

        $sql = "SELECT id, lawyer_name, client_name, document_name, document_code FROM documents";
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        // Sorguyu hazırla ve çalıştır
        $stmt = $conn->prepare($sql);

        if (!empty($params)) {
            $types = str_repeat("s", count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Her satırı döngüyüle işle
            while ($row = $result->fetch_assoc()) {
                echo "
                <div class='document-card'>
                    <h3>Avukat: {$row['lawyer_name']}</h3>
                    <p><strong>Müvekkil:</strong> {$row['client_name']}</p>
                    <p><strong>Evrak Adı:</strong> {$row['document_name']}</p>
                    <p><strong>Evrak Kodu:</strong> {$row['document_code']}</p>
                    <a href='document_detail.php?id={$row['id']}' class='details-link'>Detaylar</a>
                </div>";
            }
        } else {
            echo "<p>Evrak bulunamadı.</p>";
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
            <li><a href="gizlilik-politikasi.html">Gizlilik Politikası</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
