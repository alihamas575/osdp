<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

// Giriş kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'GET' || !isset($_GET['egitim_id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit();
}

$egitim_id = (int)$_GET['egitim_id'];

try {
    // Eğitim bilgilerini getir
    $stmt = $pdo->prepare("SELECT egitim_adi, egitim_suresi FROM egitimler WHERE id = ?");
    $stmt->execute([$egitim_id]);
    $egitim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$egitim) {
        echo json_encode(['success' => false, 'message' => 'Eğitim bulunamadı']);
        exit();
    }
    
    // Eğitim konularını getir
    $stmt = $pdo->prepare("
        SELECT ana_konu, alt_konu, sira_no 
        FROM egitim_konulari 
        WHERE egitim_id = ? 
        ORDER BY sira_no
    ");
    $stmt->execute([$egitim_id]);
    $konular = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Konuları ana konulara göre grupla
    $gruplu_konular = [];
    foreach ($konular as $konu) {
        $gruplu_konular[$konu['ana_konu']][] = $konu['alt_konu'];
    }
    
    echo json_encode([
        'success' => true,
        'egitim' => $egitim,
        'konular' => $konular,
        'gruplu_konular' => $gruplu_konular
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>