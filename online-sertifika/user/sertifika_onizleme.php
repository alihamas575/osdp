<?php 
include '../config/database.php';
oturum_kontrol();

if (!isset($_GET['id'])) {
    header("Location: sertifika_olustur.php");
    exit();
}

$sertifika_id = intval($_GET['id']);

// Sertifika bilgilerini çek
$stmt = $pdo->prepare("
    SELECT s.*, 
           e.egitim_adi, e.egitim_kaynagi,
           eg1.ad_soyad as egitimci1_ad, eg1.unvan as egitimci1_unvan, eg1.sertifika_no as egitimci1_sertifika,
           eg2.ad_soyad as egitimci2_ad, eg2.unvan as egitimci2_unvan, eg2.sertifika_no as egitimci2_sertifika
    FROM sertifikalar s
    LEFT JOIN egitimler e ON s.egitim_id = e.id
    LEFT JOIN egitimciler eg1 ON s.egitimci_1_id = eg1.id
    LEFT JOIN egitimciler eg2 ON s.egitimci_2_id = eg2.id
    WHERE s.id = ? AND s.kullanici_id = ?
");
$stmt->execute([$sertifika_id, $_SESSION['kullanici_id']]);
$sertifika = $stmt->fetch();

if (!$sertifika) {
    header("Location: sertifika_olustur.php");
    exit();
}

// Eğitim konularını çek
$konular = [];
if ($sertifika['egitim_id']) {
    $stmt = $pdo->prepare("SELECT * FROM egitim_konulari WHERE egitim_id = ? ORDER BY ana_konu, sira_no");
    $stmt->execute([$sertifika['egitim_id']]);
    $konu_listesi = $stmt->fetchAll();
    
    foreach ($konu_listesi as $konu) {
        $konular[$konu['ana_konu']][] = $konu['alt_konu'];
    }
}

// Tarih formatını düzenle
function tarih_format($tarih) {
    return date('d.m.Y', strtotime($tarih));
}

// Yazdırma işlemi
if (isset($_POST['yazdir'])) {
    // Burada PDF oluşturma işlemi olacak
    // TCPDF kütüphanesi kullanarak sertifikayı PDF olarak oluşturacağız
    header("Location: sertifika_pdf.php?id=" . $sertifika_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifika Önizleme - Online Sertifika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sertifika-container {
            background: white;
            border: 3px solid #0066cc;
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            font-family: 'Times New Roman', serif;
        }
        .sertifika-header {
            text-align: center;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .sertifika-baslik {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
            margin: 15px 0;
        }
        .tarih-info {
            font-size: 14px;
            margin-bottom: 10px;
        }
        .katilimci-info {
            background: #f8f9fa;
            border-left: 4px solid #0066cc;
            padding: 15px;
            margin: 20px 0;
        }
        .konular-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .egitimci-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        .egitimci-box {
            text-align: center;
            width: 45%;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 8px;
        }
        @media print {
            .no-print { display: none !important; }
            .container { max-width: none !important; }
        }
    </style>
</head>
<body class="bg-light">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark no-print">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-certificate"></i> Online Sertifika
        </a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="sertifika_olustur.php">
                <i class="fas fa-arrow-left"></i> Geri Dön
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <!-- Kontrol Butonları -->
    <div class="row no-print mb-3">
        <div class="col-12 text-center">
            <form method="POST" class="d-inline">
                <button type="submit" name="yazdir" class="btn btn-success btn-lg me-2">
                    <i class="fas fa-print"></i> PDF Olarak Yazdır
                </button>
            </form>
            <a href="sertifika_olustur.php?edit=<?php echo $sertifika_id; ?>" class="btn btn-warning btn-lg me-2">
                <i class="fas fa-edit"></i> Düzenle
            </a>
            <button onclick="window.print()" class="btn btn-info btn-lg">
                <i class="fas fa-print"></i> Tarayıcıdan Yazdır
            </button>
        </div>
    </div>

    <!-- Sertifika Önizleme -->
    <div class="sertifika-container">
        <!-- Header -->
        <div class="sertifika-header">
            <div class="row">
                <div class="col-3">
                    <img src="../assets/logo.png" alt="Logo" style="max-height: 80px;" class="img-fluid">
                </div>
                <div class="col-6">
                    <div class="tarih-info">
                        <strong>Eğitim Tarihi:</strong> 
                        <?php echo tarih_format($sertifika['egitim_tarihi_1']); ?>
                        <?php if ($sertifika['egitim_tarihi_2']): ?>
                            - <?php echo tarih_format($sertifika['egitim_tarihi_2']); ?>
                        <?php endif; ?>
                    </div>
                    <div class="tarih-info">
                        <strong>Geçerlilik Süresi:</strong> 
                        <?php echo $sertifika['gecerlilik_suresi'] ? $sertifika['gecerlilik_suresi'] . ' Yıl' : 'Sonsuz'; ?>
                    </div>
                </div>
                <div class="col-3">
                    <!-- Boş alan veya ek logo -->
                </div>
            </div>
            
            <div class="sertifika-baslik">
                <?php echo strtoupper(htmlspecialchars($sertifika['egitim_adi'] ?: 'İŞ SAĞLIĞI VE GÜVENLİĞİ EĞİTİM SERTİFİKASI')); ?>
            </div>
        </div>

        <!-- Katılımcı Bilgileri -->
        <div class="katilimci-info">
            <div class="row">
                <div class="col-md-6">
                    <strong>Katılımcı:</strong> <?php echo strtoupper(htmlspecialchars($sertifika['katilimci_ad_soyad'])); ?><br>
                    <strong>Kurum Adı:</strong> <?php echo strtoupper(htmlspecialchars($sertifika['kurum_adi'])); ?><br>
                    <strong>Görevi:</strong> <?php echo strtoupper(htmlspecialchars($sertifika['gorevi'])); ?>
                </div>
                <div class="col-md-6">
                    <strong>Tehlike Sınıfı:</strong> <?php echo htmlspecialchars($sertifika['tehlike_sinifi']); ?><br>
                    <strong>Eğitim Süresi:</strong> <?php echo $sertifika['egitim_suresi']; ?> Saat<br>
                    <strong>Eğitim Şekli:</strong> <?php echo htmlspecialchars($sertifika['egitim_sekli']); ?>
                </div>
            </div>
        </div>

        <!-- Açıklama Metni -->
        <div class="text-justify mb-3">
            Yukarıda adı geçen kişi, "<?php echo htmlspecialchars($sertifika['egitim_kaynagi'] ?: 'Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik'); ?>" 
            kapsamında verilen <?php echo htmlspecialchars($sertifika['egitim_adi'] ?: 'iş sağlığı ve güvenliği'); ?> eğitimlerini başarıyla tamamlayarak bu eğitim belgesini almaya hak kazanmıştır.
        </div>

        <!-- Eğitim Konuları -->
        <?php if (!empty($konular)): ?>
            <div class="konular-section">
                <div class="row">
                    <?php 
                    $ana_konular = ['Genel Konular', 'Sağlık Konuları', 'Teknik Konular', 'Diğer Konular'];
                    $kolon = 0;
                    foreach ($ana_konular as $ana_konu): 
                        if (!empty($konular[$ana_konu])):
                            if ($kolon % 2 == 0 && $kolon > 0) echo '</div><div class="row">';
                    ?>
                        <div class="col-md-6">
                            <h6><strong><?php echo ($kolon + 1) . '. ' . $ana_konu; ?></strong></h6>
                            <?php foreach ($konular[$ana_konu] as $alt_konu): ?>
                                <div><?php echo htmlspecialchars($alt_konu); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php 
                            $kolon++;
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Eğitimciler -->
        <div class="egitimci-section">
            <div class="egitimci-box">
                <h6><strong>İŞ GÜVENLİĞİ UZMANI</strong></h6>
                <div><strong><?php echo strtoupper(htmlspecialchars($sertifika['egitimci1_ad'])); ?></strong></div>
                <div><?php echo htmlspecialchars($sertifika['egitimci1_unvan']); ?></div>
                <?php if ($sertifika['egitimci1_sertifika']): ?>
                    <div>Sertifika No: <?php echo htmlspecialchars($sertifika['egitimci1_sertifika']); ?></div>
                <?php endif; ?>
                <div style="height: 60px; margin: 10px 0; border-bottom: 1px solid #000;"></div>
                <div><small>İmza</small></div>
            </div>

            <?php if ($sertifika['egitimci2_ad']): ?>
                <div class="egitimci-box">
                    <h6><strong>İŞYERİ HEKİMİ</strong></h6>
                    <div><strong><?php echo strtoupper(htmlspecialchars($sertifika['egitimci2_ad'])); ?></strong></div>
                    <div><?php echo htmlspecialchars($sertifika['egitimci2_unvan']); ?></div>
                    <?php if ($sertifika['egitimci2_sertifika']): ?>
                        <div>Sertifika No: <?php echo htmlspecialchars($sertifika['egitimci2_sertifika']); ?></div>
                    <?php endif; ?>
                    <div style="height: 60px; margin: 10px 0; border-bottom: 1px solid #000;"></div>
                    <div><small>İmza</small></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Alt Bilgi -->
        <div class="text-center mt-4" style="font-size: 12px; color: #666;">
            Sertifika Tarihi: <?php echo date('d.m.Y'); ?> | 
            Sertifika No: OSS-<?php echo str_pad($sertifika['id'], 6, '0', STR_PAD_LEFT); ?>
        </div>
    </div>

    <!-- İstatistikler (Sadece Ekranda) -->
    <div class="row mt-4 no-print">
        <div class="col-12">
            <div class="alert alert-info">
                <h5><i class="fas fa-info-circle"></i> Sertifika Bilgileri</h5>
                <p><strong>Oluşturulma Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($sertifika['kayit_tarihi'])); ?></p>
                <p><strong>Sertifika ID:</strong> <?php echo $sertifika['id']; ?></p>
                <p><strong>Durum:</strong> <span class="badge bg-success">Aktif</span></p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>