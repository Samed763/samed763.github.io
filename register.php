<?php
// Veritabanı bağlantısı
require 'db.php'; // Bağlantı dosyasını dahil et

// Hata veya başarı mesajları için değişkenler
$errorMessage = "";
$successMessage = "";

// Form gönderildiyse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];

    // Kullanıcı adı ve şifre kontrolü
    if ($username === 'admin' && $password === 'admin') {
        $errorMessage = "Bu kullanıcı adı ve şifre ile kayıt olamazsınız!";
    } 
    // Şifre eşleşmesini kontrol et
    else if ($password !== $confirmPassword) {
        $errorMessage = "Şifreler eşleşmiyor!";
    } else {
        // Şifreyi hashle
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Kullanıcıyı ekle
        $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $email, $hashedPassword);

        if ($stmt->execute()) {
            $successMessage = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
        } else {
            $errorMessage = "Kayıt başarısız! E-posta veya kullanıcı adı zaten mevcut.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <link rel="stylesheet" href="loginpage.css"> <!-- CSS dosyası -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol</title>
</head>
<body>
    <form action="register.php" method="post" class="login-container">
        <h2>Kayıt Ol</h2>

        <?php
        // Hata veya başarı mesajını göster
        if (!empty($errorMessage)) {
            echo '<p class="error-message">' . $errorMessage . '</p>';
        }
        if (!empty($successMessage)) {
            echo '<p style="color: green;">' . $successMessage . '</p>';
        }
        ?>

        <label for="username">Kullanıcı Adı:</label>
        <input type="text" id="username" name="username" required>

        <label for="email">E-posta:</label>
        <input type="email" id="email" name="email" required>

        <label for="password">Şifre:</label>
        <input type="password" id="password" name="password" required>

        <label for="confirm_password">Şifreyi Onayla:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>

        <button type="submit">Kayıt Ol</button>
        <a href="login.php" class="regiserlink">Zaten hesabınız var mı? Giriş yapın.</a>
    </form>
</body>
</html>
