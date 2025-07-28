<?php
// UTF-8 zorlamalı ayarlar
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Veritabanı bağlantı ayarları
$servername = "localhost";
$username = "";
$password = "";
$dbname = "";

try {
    // PDO ile UTF-8 zorlamalı bağlantı
    $dsn = "mysql:host=$servername;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // Ekstra UTF-8 komutları
    $pdo->exec("SET CHARACTER SET utf8mb4");
    $pdo->exec("SET character_set_connection=utf8mb4");
    $pdo->exec("SET character_set_client=utf8mb4");
    $pdo->exec("SET character_set_results=utf8mb4");
    $pdo->exec("SET collation_connection=utf8mb4_unicode_ci");
    
} catch(PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Session sadece başlatılmadıysa başlat
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// UTF-8 güvenli veri fonksiyonu
function guvenli_veri($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function oturum_kontrol() {
    if (!isset($_SESSION['kullanici_id'])) {
        header("Location: ../login.php");
        exit();
    }
}

function yonetici_kontrol() {
    if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'yonetici') {
        header("Location: ../index.php");
        exit();
    }
}
?>