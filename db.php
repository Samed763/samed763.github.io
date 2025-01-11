<?php
$host = "localhost"; // Veritabanı sunucusu (genelde 'localhost')
$username = "root"; // Veritabanı kullanıcı adı
$password = ""; // Şifre (genelde boş bırakılır, XAMPP'de varsayılan)
$database = "okuldb1"; // Kullanmak istediğiniz veritabanı adı

// Bağlantıyı oluştur
$conn = new mysqli($host, $username, $password, $database);

// Bağlantıyı kontrol et
if ($conn->connect_error) {
    // Eğer bağlantı hatası varsa, console.log ile hata mesajı gönder
    echo "<script>console.log('Bağlantı başarısız: " . $conn->connect_error . "');</script>";
} else {
    // Eğer bağlantı başarılıysa, console.log ile başarılı mesajı gönder
    echo "<script>console.log('Bağlantı başarılı!');</script>";
}

// Veritabanı işlemleri burada yapılabilir.

?>
