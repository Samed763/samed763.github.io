<?php
// Veritabanı bağlantısı
require 'db.php'; // Veritabanı bağlantısını içeren dosya

session_start(); // Oturumu başlat

$errorMessage = "";

// Form gönderildiyse işlemleri başlat
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Özel admin kontrolü
    if ($username === 'admin' && $password === 'admin') {
        // Admin giriş bilgileri doğru, admin paneline yönlendir
        $_SESSION['username'] = $username;
        header("Location: adminpanel.php");
        exit;
    }

    // Kullanıcı adı ve şifre kontrolü (veritabanı üzerinden)
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Şifre doğrulama
        if (password_verify($password, $user['password'])) {
            // Giriş başarılı, oturum değişkenini ayarla
            $_SESSION['username'] = $username;
            header("Location: anasayfa.php");
            exit;
        } else {
            $errorMessage = "Şifre yanlış!";
        }
    } else {
        $errorMessage = "Kullanıcı adı bulunamadı!";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <link rel="stylesheet" href="loginpage.css"> <!-- CSS dosyası -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Sayfası</title>
</head>
<body>
    <form action="login.php" method="post" class="login-container">
        <h1 class="headerfint">Law</h1>

        <?php
        // Hata mesajı varsa göster
        if (!empty($errorMessage)) {
            echo '<p class="error-message">' . $errorMessage . '</p>';
        }
        ?>

        <label for="username">Kullanıcı Adı:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Şifre:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">Giriş Yap</button>
        <a href="register.php" class="regiserlink">Hesabınız yok mu? Kayıt olun.</a>
    </form>
</body>
</html>
