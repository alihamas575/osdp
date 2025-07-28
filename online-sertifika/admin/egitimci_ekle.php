<?php
session_start();
include '../config/database.php';
yonetici_kontrol();

$basarili_mesaj = "";
$hata_mesaj = "";

// Form gönderildi ise
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['egitimci_ekle'])) {
    $ad_soyad = trim($_POST['ad_soyad']);
    $unvan = trim($_POST['unvan']);
    $sertifika_no = trim($_POST['sertifika_no']);
    
    if (empty($ad_soyad)) {
        $hata_mesaj = "Ad Soyad alanı boş olamaz!";
    } else {
        try {
            // İmza dosyası yükleme
            $imza_dosyasi = null;
            if (isset($_FILES['imza_dosyasi']) && $_FILES['imza_dosyasi']['error'] == 0) {
                $upload_dir = '../assets/uploads/imzalar/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['imza_dosyasi']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $imza_dosyasi = time() . '_' . uniqid() . '.' . $file_extension;
                    $upload_path = $upload_dir . $imza_dosyasi;
                    
                    if (!move_uploaded_file($_FILES['imza_dosyasi']['tmp_name'], $upload_path)) {
                        $hata_mesaj = "İmza dosyası yüklenirken hata oluştu!";
                    }
                } else {
                    $hata_mesaj = "Sadece JPG, JPEG, PNG, GIF formatları kabul edilir!";
                }
            }
            
            if (empty($hata_mesaj)) {
                $stmt = $pdo->prepare("INSERT INTO egitimciler (ad_soyad, unvan, sertifika_no, imza_dosyasi, kullanici_id) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$ad_soyad, $unvan, $sertifika_no, $imza_dosyasi, $_SESSION['kullanici_id']]);
                
                $basarili_mesaj = "Eğitimci başarıyla eklendi!";
                
                // Form alanlarını temizle
                $ad_soyad = "";
                $unvan = "";
                $sertifika_no = "";
            }
            
        } catch (Exception $e) {
            $hata_mesaj = "Hata: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Eğitimci Ekle - Online Sertifika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: #2c3e50;
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: #bdc3c7;
            border-radius: 10px;
            margin: 5px 0;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: #34495e;
            color: white;
        }
    </style>
</head>
<body class="bg-light">

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar p-3">
            <div class="text-center mb-4">
                <h4><i class="fas fa-certificate"></i> Online Sertifika</h4>
                <hr class="text-light">
            </div>
            
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="kullanici_yonetimi.php">
                    <i class="fas fa-users"></i> Kullanıcı Yönetimi
                </a>
                <a class="nav-link" href="sertifika_yonetimi.php">
                    <i class="fas fa-certificate"></i> Sertifika Yönetimi
                </a>
                <a class="nav-link" href="egitim_yonetimi.php">
                    <i class="fas fa-book"></i> Eğitim Yönetimi
                </a>
                <a class="nav-link active" href="egitimci_yonetimi.php">
                    <i class="fas fa-chalkboard-teacher"></i> Eğitimci Yönetimi
                </a>
                <a class="nav-link" href="raporlar.php">
                    <i class="fas fa-chart-bar"></i> Raporlar
                </a>
                <a class="nav-link" href="ayarlar.php">
                    <i class="fas fa-cog"></i> Sistem Ayarları
                </a>
                <hr class="text-light">
                <a class="nav-link" href="../user/sertifika_olustur.php">
                    <i class="fas fa-plus"></i> Sertifika Oluştur
                </a>
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-home"></i> Ana Sayfa
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                </a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <main class="col-md-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Yeni Eğitimci Ekle</h1>
                <a href="egitimci_yonetimi.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>

            <?php if ($basarili_mesaj): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $basarili_mesaj; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($hata_mesaj): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $hata_mesaj; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Eğitimci Bilgileri</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="ad_soyad" class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="ad_soyad" name="ad_soyad" 
                                           value="<?php echo isset($ad_soyad) ? htmlspecialchars($ad_soyad) : ''; ?>" 
                                           placeholder="Örn: Dr. Mehmet Yılmaz" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="unvan" class="form-label">Ünvan</label>
                                    <input type="text" class="form-control" id="unvan" name="unvan" 
                                           value="<?php echo isset($unvan) ? htmlspecialchars($unvan) : ''; ?>" 
                                           placeholder="Örn: İş Güvenliği Uzmanı">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sertifika_no" class="form-label">Sertifika No</label>
                                    <input type="text" class="form-control" id="sertifika_no" name="sertifika_no" 
                                           value="<?php echo isset($sertifika_no) ? htmlspecialchars($sertifika_no) : ''; ?>" 
                                           placeholder="Sertifika numarası">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="imza_dosyasi" class="form-label">İmza Dosyası</label>
                                    <input type="file" class="form-control" id="imza_dosyasi" name="imza_dosyasi" accept="image/*">
                                    <div class="form-text">JPG, JPEG, PNG, GIF formatları kabul edilir.</div>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='egitimci_yonetimi.php'">
                                <i class="fas fa-times"></i> İptal
                            </button>
                            <button type="submit" name="egitimci_ekle" class="btn btn-success">
                                <i class="fas fa-plus-circle"></i> Eğitimci Ekle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>