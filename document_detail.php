<?php
session_start(); // Oturum başlat

// Kullanıcı giriş yapmamışsa, login.php'ye yönlendir
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Veritabanı bağlantısını dahil et
include 'db.php';

// Belge ID'sini al
if (isset($_GET['id'])) {
    $document_id = $_GET['id'];

    // Veritabanından belge bilgilerini ve detaylarını al
    $sql = "SELECT d.id, d.lawyer_name, d.client_name, d.document_name, UPPER(d.document_code) AS document_code, 
                   dd.details_text, dd.start_date, ft.payment_amount, ft.payed_amount
            FROM documents d
            LEFT JOIN document_details dd ON d.id = dd.document_id
            LEFT JOIN financial_transactions ft ON d.id = ft.document_id
            WHERE d.id = ?";
    
    // Sorguyu hazırlayın
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $document_id); // id parametresi ile sorgu bağlanıyor
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Sonuç varsa, belgeyi ve detayları al
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
        } else {
            echo "<p>Belge bulunamadı.</p>";
            exit;
        }
    } else {
        echo "<p>Veritabanı hatası: " . $conn->error . "</p>";
        exit;
    }
} else {
    echo "<p>Geçersiz erişim!</p>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_payment'])) {
        $document_id = $_POST['document_id'];
        $new_payment_amount = $_POST['payment_amount'];

        // Update the payment amount in the database
        $update_query = "UPDATE financial_transactions SET payment_amount = ? WHERE document_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("di", $new_payment_amount, $document_id);
        $stmt->execute();
        $stmt->close();

        // Refresh the page to show updated payment amount
        header("Location: document_detail.php?id=" . $document_id);
        exit;
    } elseif (isset($_POST['partial_payment'])) {
        $document_id = $_POST['document_id'];
        $partial_amount = $_POST['partial_amount'];

        // Update the payed_amount in the database
        $update_query = "UPDATE financial_transactions SET payed_amount = payed_amount + ? WHERE document_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("di", $partial_amount, $document_id);
        $stmt->execute();
        $stmt->close();

        // Refresh the page to show updated payment amount
        header("Location: document_detail.php?id=" . $document_id);
        exit;
    } elseif (isset($_POST['pay_salary'])) {
        $salary_id = $_POST['salary_id'];
        $document_id = $_POST['document_id'];
        $payment_amount = $_POST['payment_amount'];

        // Update the salary status to 'paid'
        $update_query = "UPDATE employee_salaries SET status = 'paid' WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $salary_id);
        $stmt->execute();
        $stmt->close();

        // Insert the payment as an expense in financial transactions
        $insert_query = "INSERT INTO financial_transactions (document_id, payment_amount, transaction_type, status) VALUES (?, ?, 'expense', 'completed')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("id", $document_id, $payment_amount);
        $stmt->execute();
        $stmt->close();

        // Refresh the page to show updated payment amount
        header("Location: document_detail.php?id=" . $document_id);
        exit;
    }
}

?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <link rel="stylesheet" href="styles.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belge Detayı</title>
    <link rel="stylesheet" href="styles.css">
    <script>
    function toggleForm(formId) {
        const form = document.getElementById(formId);
        if (form.style.display === 'none' || form.style.display === '') {
            form.style.display = 'block'; // Formu göster
        } else {
            form.style.display = 'none'; // Formu gizle
        }
    }
    </script>
</head>
<body>
<div class="navbar">
        <a href="anasayfa.php">Ana Sayfa</a>
        <a href="evraklar.php">Evraklar</a>
        <a href="finansal.php">Finansal</a>
        <a href="alarm.php">Alarm</a>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <div class="document-detail">
        <h2>Belge Detayı</h2>

        <div class="document-info">
            <p><strong>Avukat:</strong> <?php echo htmlspecialchars($row['lawyer_name']); ?></p>
            <p><strong>Müvekkil:</strong> <?php echo htmlspecialchars($row['client_name']); ?></p>
            <p><strong>Belge Adı:</strong> <?php echo htmlspecialchars($row['document_name']); ?></p>
            <p><strong>Belge Kodu:</strong> <?php echo htmlspecialchars($row['document_code']); ?></p>
        </div>

        <div class="document-details">
            <h3>Detaylar</h3>
            <p><strong>Başlangıç Tarihi:</strong> <?php echo htmlspecialchars($row['start_date']); ?></p>
            <p><strong>Ödeme Miktarı:</strong> <?php echo number_format($row['payment_amount'] - $row['payed_amount'], 2, ',', '.'); ?> TL</p>
            <p><strong>Açıklama:</strong> <?php echo htmlspecialchars($row['details_text']); ?></p>

            <!-- Ödeme Miktarını Güncelleme Başlığı -->
            <h3 class="toggle-header" onclick="toggleForm('update-payment-form')">Ödeme Miktarını Güncelle</h3>
            
            <!-- Gizli Form -->
            <div id="update-payment-form" style="display: none;">
                <form method="POST" action="">
                    <input type="hidden" name="document_id" value="<?php echo htmlspecialchars($document_id); ?>">
                    <label for="payment_amount">Yeni Ödeme Miktarı (TL):</label>
                    <input type="number" id="payment_amount" name="payment_amount" step="0.01" required>
                    <button type="submit" name="update_payment" class="update-button">Güncelle</button>
                </form>
            </div>

            <!-- Kısmi Ödeme Başlığı -->
            <h3 class="toggle-header" onclick="toggleForm('partial-payment-form')">Kısmi Ödeme Yap</h3>
            
            <!-- Gizli Form -->
            <div id="partial-payment-form" style="display: none;">
                <form method="POST" action="">
                    <input type="hidden" name="document_id" value="<?php echo htmlspecialchars($document_id); ?>">
                    <label for="partial_amount">Ödenecek Miktar (TL):</label>
                    <input type="number" id="partial_amount" name="partial_amount" step="0.01" required>
                    <button type="submit" name="partial_payment" class="update-button">Öde</button>
                </form>
            </div>
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

<?php
// Bağlantıyı kapat
$conn->close();
?>
