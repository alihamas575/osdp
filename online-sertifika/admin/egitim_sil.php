<?php
session_start();
include '../config/database.php';

header('Content-Type: application/json');

// Giriş kontrolü
if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'yonetici') {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['egitim_id'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit();
}

$egitim_id = (int)$_POST['egitim_id'];

try {
    // Önce bu eğitime ait sertifika var mı kontrol et
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sertifikalar WHERE egitim_id = ?");
    $stmt->execute([$egitim_id]);
    $sertifika_sayisi = $stmt->fetchColumn();
    
    if ($sertifika_sayisi > 0) {
        echo json_encode([
            'success' => false, 
            'message' => "Bu eğitime ait $sertifika_sayisi adet sertifika bulunduğu için eğitim silinemez. Önce bu eğitime ait sertifikaları silmeniz gerekir."
        ]);
        exit();
    }
    
    $pdo->beginTransaction();
    
    // Eğitim konularını sil
    $stmt = $pdo->prepare("DELETE FROM egitim_konulari WHERE egitim_id = ?");
    $stmt->execute([$egitim_id]);
    
    // Eğitimi sil
    $stmt = $pdo->prepare("DELETE FROM egitimler WHERE id = ?");
    $stmt->execute([$egitim_id]);
    
    if ($stmt->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Eğitim başarıyla silindi']);
    } else {
        $pdo->rollback();
        echo json_encode(['success' => false, 'message' => 'Eğitim bulunamadı']);
    }
    
} catch (Exception $e) {
    $pdo->rollback();
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>