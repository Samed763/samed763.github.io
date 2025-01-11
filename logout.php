<?php
session_start();
session_destroy(); // Oturumu sonlandır
header("Location: login.php"); // Login sayfasına yönlendir
exit;
$logMessage = "çalışıt logout.php.";
echo "<script>console.log('PHP: " . addslashes($logMessage) . "');</script>";
?>
