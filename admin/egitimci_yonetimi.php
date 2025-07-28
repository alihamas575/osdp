<?php 
include '../config/database.php';
yonetici_kontrol();

$hata = '';
$basari = '';

// Sistem ayarları tablosunu oluştur (eğer yoksa)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sistem_ayarlari (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ayar_adi VARCHAR(100) UNIQUE NOT NULL,
            ayar_degeri TEXT,
            aciklama TEXT,
            guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Varsayılan ayarları ekle
    $varsayilan_ayarlar = [
        ['site_adi', 'Online Sertifika Sistemi', 'Web sitesi adı'],
        ['site_aciklama', 'İş Sağlığı ve Güvenliği Eğitim Sertifikası Yönetim Sistemi', 'Site açıklaması'],
        ['logo_dosyasi', '', 'Logo dosya adı'],
        ['admin_email', 'admin@bursaisg.com', 'Sistem yöneticisi email adresi'],
        ['smtp_host', '', 'SMTP sunucu adresi'],
        ['smtp_port', '587', 'SMTP port numarası'],
        ['smtp_kullanici', '', 'SMTP kullanıcı adı'],
        ['smtp_sifre', '', 'SMTP şifresi'],
        ['smtp_ssl', '1', 'SMTP SSL kullanımı (1=Evet, 0=Hayır)'],
        ['sertifika_onizleme', '1', 'Sertifika önizleme zorunlu (1=Evet, 0=Hayır)'],
        ['otomatik_yedek', '1', 'Otomatik veritabanı yedeği (1=Evet, 0=Hayır)'],
        ['yedek_saklama_gun', '30', 'Yedek dosyalarını kaç gün saklayacak'],
        ['sistem_bakimi', '0', 'Sistem bakım modu (1=Evet, 0=Hayır)'],
        ['bakim_mesaji', 'Sistem bakımda. Lütfen daha sonra tekrar deneyiniz.', 'Bakım modu mesajı'],
        ['max_dosya_boyutu', '5', 'Maksimum dosya boyutu (MB)'],
        ['izin_verilen_dosya_tipleri', 'jpg,jpeg,png,pdf', 'İzin verilen dosya tipleri']
    ];
    
    foreach ($varsayilan_ayarlar as $ayar) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO sistem_ayarlari (ayar_adi, ayar_degeri, aciklama) VALUES (?, ?, ?)");
        $stmt->execute($ayar);
    }
} catch (PDOException $e) {
    // Tablo oluşturulamadı, sessizce devam et
}

// Ayar güncelleme işlemi
if (isset($_POST['ayarlari_kaydet'])) {
    try {
        $pdo->beginTransaction();
        
        foreach ($_POST as $key => $value) {
            if ($key !== 'ayarlari_kaydet') {
                $stmt = $pdo->prepare("UPDATE sistem_ayarlari SET ayar_degeri = ? WHERE ayar_adi = ?");
                $stmt->execute([guvenli_veri($value), $key]);
            }
        }
        
        $pdo->commit();
        $basari = "Ayarlar başarıyla kaydedildi!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $hata = "Ayarlar kaydedilirken hata oluştu!";
    }
}

// Logo yükleme işlemi
if (isset($_POST['logo_yukle'])) {
    if (isset($_FILES['logo_dosyasi']) && $_FILES['logo_dosyasi']['error'] == 0) {
        $dosya = $_FILES['logo_dosyasi'];
        $dosya_adi = $dosya['name'];
        $dosya_tmp = $dosya['tmp_name'];
        $dosya_boyutu = $dosya['size'];
        
        // Dosya kontrolü
        $izin_verilen = ['jpg', 'jpeg', 'png'];
        $dosya_uzantisi = strtolower(pathinfo($dosya_adi, PATHINFO_EXTENSION));
        
        if (!in_array($dosya_uzantisi, $izin_verilen)) {
            $hata = "Sadece JPG, JPEG ve PNG dosyaları yüklenebilir!";
        } elseif ($dosya_boyutu > 5 * 1024 * 1024) { // 5MB
            $hata = "Dosya boyutu 5MB'dan büyük olamaz!";
        } else {
            $yeni_dosya_adi = 'logo_' . time() . '.' . $dosya_uzantisi;
            $hedef_klasor = '../assets/uploads/';
            
            if (!is_dir($hedef_klasor)) {
                mkdir($hedef_klasor, 0777, true);
            }
            
            if (move_uploaded_file($dosya_tmp, $hedef_klasor . $yeni_dosya_adi)) {
                // Eski logoyu sil
                $stmt = $pdo->prepare("SELECT ayar_degeri FROM sistem_ayarlari WHERE ayar_adi = 'logo_dosyasi'");
                $stmt->execute();
                $eski_logo = $stmt->fetchColumn();
                
                if ($eski_logo && file_exists($hedef_klasor . $eski_logo)) {
                    unlink($hedef_klasor . $eski_logo);
                }
                
                // Yeni logoyu kaydet
                $stmt = $pdo->prepare("UPDATE sistem_ayarlari SET ayar_degeri = ? WHERE ayar_adi = 'logo_dosyasi'");
                $stmt->execute([$yeni_dosya_adi]);
                
                $basari = "Logo başarıyla yüklendi!";
            } else {
                $hata = "Logo yüklenirken hata oluştu!";
            }
        }
    } else {
        $hata = "Lütfen geçerli bir logo dosyası seçin!";
    }
}

// Veritabanı yedeği alma
if (isset($_POST['veritabani_yedek'])) {
    try {
        $yedek_klasor = '../backups/';
        if (!is_dir($yedek_klasor)) {
            mkdir($yedek_klasor, 0777, true);
        }
        
        $yedek_dosyasi = $yedek_klasor . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Basit yedekleme (mysqldump kullanmadan)
        $tablolar = ['kullanicilar', 'egitimler', 'egitim_konulari', 'egitimciler', 'sertifikalar', 'sistem_ayarlari'];
        $yedek_icerik = "-- Database Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tablolar as $tablo) {
            $yedek_icerik .= "-- Tablo: $tablo\n";
            $yedek_icerik .= "DROP TABLE IF EXISTS `$tablo`;\n";
            
            // Tablo yapısını al
            $stmt = $pdo->prepare("SHOW CREATE TABLE `$tablo`");
            $stmt->execute();
            $create_table = $stmt->fetch();
            if ($create_table) {
                $yedek_icerik .= $create_table['Create Table'] . ";\n\n";
            }
            
            // Verileri al
            $stmt = $pdo->prepare("SELECT * FROM `$tablo`");
            $stmt->execute();
            $veriler = $stmt->fetchAll();
            
            foreach ($veriler as $veri) {
                $degerler = array_map(function($v) use ($pdo) {
                    return $v === null ? 'NULL' : $pdo->quote($v);
                }, array_values($veri));
                
                $yedek_icerik .= "INSERT INTO `$tablo` VALUES (" . implode(', ', $degerler) . ");\n";
            }
            $yedek_icerik .= "\n";
        }
        
        if (file_put_contents($yedek_dosyasi, $yedek_icerik)) {
            $basari = "Veritabanı yedeği başarıyla alındı: " . basename($yedek_dosyasi);
        } else {
            $hata = "Veritabanı yedeği alınırken hata oluştu!";
        }
    } catch (Exception $e) {
        $hata = "Yedekleme sırasında hata oluştu!";
    }
}

// Sistem ayarlarını çek
$ayarlar = [];
try {
    $stmt = $pdo->query("SELECT ayar_adi, ayar_degeri, aciklama FROM sistem_ayarlari ORDER BY ayar_adi");
    while ($row = $stmt->fetch()) {
        $ayarlar[$row['ayar_adi']] = $row;
    }
} catch (PDOException $e) {
    // Ayarlar çekilemedi
}

// İstatistikler
$stats = [
    'toplam_dosya_boyutu' => 0,
    'yedek_sayisi' => 0,
    'son_giris' => date('Y-m-d H:i:s')
];

// Dosya boyutunu hesapla
$upload_klasoru = '../assets/uploads/';
if (is_dir($upload_klasoru)) {
    $dosyalar = glob($upload_klasoru . '*');
    foreach ($dosyalar as $dosya) {
        if (is_file($dosya)) {
            $stats['toplam_dosya_boyutu'] += filesize($dosya);
        }
    }
}

// Yedek sayısını hesapla
$yedek_klasoru = '../backups/';
if (is_dir($yedek_klasoru)) {
    $yedekler = glob($yedek_klasoru . '*.sql');
    $stats['yedek_sayisi'] = count($yedekler);
}

function formatBytes($size, $precision = 2) {
    $base = log($size, 1024);
    $suffixes = array('B', 'KB', 'MB', 'GB', 'TB');   
    return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Ayarları - Online Sertifika</title>
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
        .ayar-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
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
                <a class="nav-link" href="raporlar.php">
                    <i class="fas fa-chart-bar"></i> Raporlar
                </a>
                <a class="nav-link active" href="ayarlar.php">
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
        <div class="col-md-10 p-4">
            <!-- Header -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2><i class="fas fa-cog"></i> Sistem Ayarları</h2>
                        <div>
                            <form method="POST" class="d-inline">
                                <button type="submit" name="veritabani_yedek" class="btn btn-warning me-2">
                                    <i class="fas fa-database"></i> Veritabanı Yedeği Al
                                </button>
                            </form>
                            <a href="../index.php" class="btn btn-info">
                                <i class="fas fa-eye"></i> Siteyi Görüntüle
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hata/Başarı Mesajları -->
            <?php if ($hata): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $hata; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($basari): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?php echo $basari; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Sistem Durumu -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-server fa-2x text-success mb-2"></i>
                            <h6>Sistem Durumu</h6>
                            <span class="badge bg-success">Çalışıyor</span>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-hdd fa-2x text-info mb-2"></i>
                            <h6>Dosya Boyutu</h6>
                            <strong><?php echo formatBytes($stats['toplam_dosya_boyutu']); ?></strong>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-archive fa-2x text-warning mb-2"></i>
                            <h6>Yedek Sayısı</h6>
                            <strong><?php echo $stats['yedek_sayisi']; ?></strong>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-clock fa-2x text-primary mb-2"></i>
                            <h6>PHP Sürümü</h6>
                            <strong><?php echo PHP_VERSION; ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ayar Formları -->
            <div class="row">
                <!-- Genel Ayarlar -->
                <div class="col-md-6">
                    <div class="card ayar-card">
                        <div class="card-header">
                            <h5><i class="fas fa-globe"></i> Genel Ayarlar</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">Site Adı</label>
                                    <input type="text" class="form-control" name="site_adi" 
                                           value="<?php echo htmlspecialchars($ayarlar['site_adi']['ayar_degeri'] ?? ''); ?>">
                                    <small class="text-muted"><?php echo $ayarlar['site_adi']['aciklama'] ?? ''; ?></small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Site Açıklaması</label>
                                    <textarea class="form-control" name="site_aciklama" rows="3"><?php echo htmlspecialchars($ayarlar['site_aciklama']['ayar_degeri'] ?? ''); ?></textarea>
                                    <small class="text-muted"><?php echo $ayarlar['site_aciklama']['aciklama'] ?? ''; ?></small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Yönetici Email</label>
                                    <input type="email" class="form-control" name="admin_email" 
                                           value="<?php echo htmlspecialchars($ayarlar['admin_email']['ayar_degeri'] ?? ''); ?>">
                                    <small class="text-muted"><?php echo $ayarlar['admin_email']['aciklama'] ?? ''; ?></small>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sertifika_onizleme" value="1" 
                                               <?php echo ($ayarlar['sertifika_onizleme']['ayar_degeri'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">
                                            Sertifika Önizleme Zorunlu
                                        </label>
                                        <small class="d-block text-muted"><?php echo $ayarlar['sertifika_onizleme']['aciklama'] ?? ''; ?></small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="sistem_bakimi" value="1" 
                                               <?php echo ($ayarlar['sistem_bakimi']['ayar_degeri'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">
                                            Sistem Bakım Modu
                                        </label>
                                        <small class="d-block text-muted"><?php echo $ayarlar['sistem_bakimi']['aciklama'] ?? ''; ?></small>
                                    </div>
                                </div>
                                
                                <button type="submit" name="ayarlari_kaydet" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Kaydet
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Logo Yükleme -->
                <div class="col-md-6">
                    <div class="card ayar-card">
                        <div class="card-header">
                            <h5><i class="fas fa-image"></i> Logo Yönetimi</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($ayarlar['logo_dosyasi']['ayar_degeri'])): ?>
                                <div class="text-center mb-3">
                                    <img src="../assets/uploads/<?php echo $ayarlar['logo_dosyasi']['ayar_degeri']; ?>" 
                                         alt="Mevcut Logo" class="img-fluid" style="max-height: 100px;">
                                    <p class="mt-2 text-muted">Mevcut Logo</p>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label">Yeni Logo Seç</label>
                                    <input type="file" class="form-control" name="logo_dosyasi" accept="image/*" required>
                                    <small class="text-muted">Desteklenen formatlar: JPG, JPEG, PNG (Max: 5MB)</small>
                                </div>
                                
                                <button type="submit" name="logo_yukle" class="btn btn-success">
                                    <i class="fas fa-upload"></i> Logo Yükle
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Email Ayarları ve Yedekleme -->
            <div class="row mt-4">
                <!-- Email Ayarları -->
                <div class="col-md-6">
                    <div class="card ayar-card">
                        <div class="card-header">
                            <h5><i class="fas fa-envelope"></i> Email Ayarları</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label">SMTP Sunucu</label>
                                    <input type="text" class="form-control" name="smtp_host" 
                                           value="<?php echo htmlspecialchars($ayarlar['smtp_host']['ayar_degeri'] ?? ''); ?>"
                                           placeholder="smtp.gmail.com">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Port</label>
                                            <input type="number" class="form-control" name="smtp_port" 
                                                   value="<?php echo htmlspecialchars($ayarlar['smtp_port']['ayar_degeri'] ?? '587'); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-check mt-4">
                                                <input class="form-check-input" type="checkbox" name="smtp_ssl" value="1" 
                                                       <?php echo ($ayarlar['smtp_ssl']['ayar_degeri'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                                <label class="form-check-label">SSL Kullan</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Kullanıcı Adı</label>
                                    <input type="text" class="form-control" name="smtp_kullanici" 
                                           value="<?php echo htmlspecialchars($ayarlar['smtp_kullanici']['ayar_degeri'] ?? ''); ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Şifre</label>
                                    <input type="password" class="form-control" name="smtp_sifre" 
                                           placeholder="Değiştirmek için yeni şifre girin">
                                </div>
                                
                                <button type="submit" name="ayarlari_kaydet" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Email Ayarlarını Kaydet
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Yedekleme Ayarları -->
                <div class="col-md-6">
                    <div class="card ayar-card">
                        <div class="card-header">
                            <h5><i class="fas fa-shield-alt"></i> Yedekleme ve Güvenlik</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="otomatik_yedek" value="1" 
                                               <?php echo ($ayarlar['otomatik_yedek']['ayar_degeri'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                        <label class="form-check-label">
                                            Otomatik Veritabanı Yedeği
                                        </label>
                                        <small class="d-block text-muted"><?php echo $ayarlar['otomatik_yedek']['aciklama'] ?? ''; ?></small>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Yedek Saklama Süresi (Gün)</label>
                                    <input type="number" class="form-control" name="yedek_saklama_gun" 
                                           value="<?php echo htmlspecialchars($ayarlar['yedek_saklama_gun']['ayar_degeri'] ?? '30'); ?>"
                                           min="1" max="365">
                                    <small class="text-muted"><?php echo $ayarlar['yedek_saklama_gun']['aciklama'] ?? ''; ?></small>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Maksimum Dosya Boyutu (MB)</label>
                                    <input type="number" class="form-control" name="max_dosya_boyutu" 
                                           value="<?php echo htmlspecialchars($ayarlar['max_dosya_boyutu']['ayar_degeri'] ?? '5'); ?>"
                                           min="1" max="100">
                                    <small class="text-muted"><?php echo $ayarlar['max_dosya_boyutu']['aciklama'] ?? ''; ?></small>
                                </div>
                                
                                <button type="submit" name="ayarlari_kaydet" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Güvenlik Ayarlarını Kaydet
                                </button>
                            </form>
                            
                            <hr>
                            
                            <!-- Manuel Yedekleme -->
                            <div class="d-grid">
                                <form method="POST">
                                    <button type="submit" name="veritabani_yedek" class="btn btn-warning">
                                        <i class="fas fa-download"></i> Manuel Yedek Al
                                    </button>
                                </form>
                            </div>
                            
                            <?php if ($stats['yedek_sayisi'] > 0): ?>
                                <div class="mt-3 text-center">
                                    <small class="text-muted">
                                        Toplam <?php echo $stats['yedek_sayisi']; ?> yedek dosyası bulunuyor
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sistem Bilgileri -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card ayar-card">
                        <div class="card-header">
                            <h5><i class="fas fa-info-circle"></i> Sistem Bilgileri</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>PHP Sürümü:</strong><br>
                                    <span class="text-muted"><?php echo PHP_VERSION; ?></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Sunucu:</strong><br>
                                    <span class="text-muted"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Bilinmiyor'; ?></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>MySQL Sürümü:</strong><br>
                                    <span class="text-muted"><?php echo $pdo->getAttribute(PDO::ATTR_SERVER_VERSION); ?></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Maksimum Upload:</strong><br>
                                    <span class="text-muted"><?php echo ini_get('upload_max_filesize'); ?></span>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Sistem Saati:</strong><br>
                                    <span class="text-muted"><?php echo date('d.m.Y H:i:s'); ?></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Bellek Limiti:</strong><br>
                                    <span class="text-muted"><?php echo ini_get('memory_limit'); ?></span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Çalışma Süresi:</strong><br>
                                    <span class="text-muted"><?php echo ini_get('max_execution_time'); ?>s</span>
                                </div>
                                <div class="col-md-3">
                                    <strong>Disk Kullanımı:</strong><br>
                                    <span class="text-muted"><?php echo formatBytes($stats['toplam_dosya_boyutu']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>