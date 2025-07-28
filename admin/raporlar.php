<?php 
include '../config/database.php';
yonetici_kontrol();

// CSV indirme işlemi
if (isset($_GET['csv_indir'])) {
    // Filtreleme parametrelerini al
    $katilimci_ad = isset($_GET['katilimci_ad']) ? guvenli_veri($_GET['katilimci_ad']) : '';
    $kurum_adi = isset($_GET['kurum_adi']) ? guvenli_veri($_GET['kurum_adi']) : '';
    $baslangic_tarih = isset($_GET['baslangic_tarih']) ? $_GET['baslangic_tarih'] : '';
    $bitis_tarih = isset($_GET['bitis_tarih']) ? $_GET['bitis_tarih'] : '';
    $egitim_adi = isset($_GET['egitim_adi']) ? guvenli_veri($_GET['egitim_adi']) : '';
    $tehlike_sinifi = isset($_GET['tehlike_sinifi']) ? $_GET['tehlike_sinifi'] : '';
    
    // WHERE koşulları
    $where_conditions = [];
    $params = [];
    
    if (!empty($katilimci_ad)) {
        $where_conditions[] = "s.katilimci_ad_soyad LIKE ?";
        $params[] = "%$katilimci_ad%";
    }
    
    if (!empty($kurum_adi)) {
        $where_conditions[] = "s.kurum_adi LIKE ?";
        $params[] = "%$kurum_adi%";
    }
    
    if (!empty($baslangic_tarih)) {
        $where_conditions[] = "s.egitim_tarihi_1 >= ?";
        $params[] = $baslangic_tarih;
    }
    
    if (!empty($bitis_tarih)) {
        $where_conditions[] = "s.egitim_tarihi_1 <= ?";
        $params[] = $bitis_tarih;
    }
    
    if (!empty($egitim_adi)) {
        $where_conditions[] = "e.egitim_adi LIKE ?";
        $params[] = "%$egitim_adi%";
    }
    
    if (!empty($tehlike_sinifi)) {
        $where_conditions[] = "s.tehlike_sinifi = ?";
        $params[] = $tehlike_sinifi;
    }
    
    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // CSV için veri çek
    $sql = "
        SELECT 
            s.katilimci_ad_soyad,
            s.kurum_adi,
            s.gorevi,
            s.tehlike_sinifi,
            s.egitim_tarihi_1,
            s.egitim_tarihi_2,
            s.egitim_suresi,
            s.gecerlilik_suresi,
            s.egitim_sekli,
            e.egitim_adi,
            e.egitim_kaynagi,
            eg1.ad_soyad as egitimci1,
            eg2.ad_soyad as egitimci2,
            u.ad_soyad as olusturan,
            s.kayit_tarihi
        FROM sertifikalar s 
        LEFT JOIN egitimler e ON s.egitim_id = e.id
        LEFT JOIN kullanicilar u ON s.kullanici_id = u.id
        LEFT JOIN egitimciler eg1 ON s.egitimci_1_id = eg1.id
        LEFT JOIN egitimciler eg2 ON s.egitimci_2_id = eg2.id
        $where_clause
        ORDER BY s.kayit_tarihi DESC
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $sertifikalar = $stmt->fetchAll();
    
    // CSV başlıkları
    $headers = [
        'Katılımcı Ad Soyad',
        'Kurum Adı',
        'Görevi',
        'Tehlike Sınıfı',
        'Eğitim Tarihi 1',
        'Eğitim Tarihi 2',
        'Eğitim Süresi (Saat)',
        'Geçerlilik Süresi (Yıl)',
        'Eğitim Şekli',
        'Eğitim Adı',
        'Eğitim Kaynağı',
        'Eğitimci 1',
        'Eğitimci 2',
        'Oluşturan',
        'Kayıt Tarihi'
    ];
    
    // CSV dosyası oluştur
    $filename = 'sertifika_raporu_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    
    $output = fopen('php://output', 'w');
    
    // UTF-8 BOM ekle (Excel'de Türkçe karakterlerin doğru görünmesi için)
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Başlıkları yaz
    fputcsv($output, $headers, ';');
    
    // Verileri yaz
    foreach ($sertifikalar as $sertifika) {
        $row = [
            $sertifika['katilimci_ad_soyad'],
            $sertifika['kurum_adi'],
            $sertifika['gorevi'],
            $sertifika['tehlike_sinifi'],
            $sertifika['egitim_tarihi_1'],
            $sertifika['egitim_tarihi_2'] ?: '',
            $sertifika['egitim_suresi'],
            $sertifika['gecerlilik_suresi'] ?: 'Sonsuz',
            $sertifika['egitim_sekli'],
            $sertifika['egitim_adi'] ?: 'İSG Eğitimi',
            $sertifika['egitim_kaynagi'] ?: '',
            $sertifika['egitimci1'] ?: '',
            $sertifika['egitimci2'] ?: '',
            $sertifika['olusturan'],
            date('d.m.Y H:i', strtotime($sertifika['kayit_tarihi']))
        ];
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit();
}

// İstatistikler
$bugun = date('Y-m-d');
$bu_ay_baslangic = date('Y-m-01');
$bu_yil_baslangic = date('Y-01-01');

// Genel istatistikler
$stats = [
    'toplam_sertifika' => $pdo->query("SELECT COUNT(*) FROM sertifikalar")->fetchColumn(),
    'toplam_kullanici' => $pdo->query("SELECT COUNT(*) FROM kullanicilar WHERE aktif = 1")->fetchColumn(),
    'toplam_egitim' => $pdo->query("SELECT COUNT(*) FROM egitimler")->fetchColumn(),
    'toplam_egitimci' => $pdo->query("SELECT COUNT(*) FROM egitimciler")->fetchColumn(),
    
    'bugun_sertifika' => $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE DATE(kayit_tarihi) = '$bugun'")->fetchColumn(),
    'bu_ay_sertifika' => $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE kayit_tarihi >= '$bu_ay_baslangic'")->fetchColumn(),
    'bu_yil_sertifika' => $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE kayit_tarihi >= '$bu_yil_baslangic'")->fetchColumn(),
    
    'gecerli_sertifika' => $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE gecerlilik_suresi IS NULL OR DATE_ADD(egitim_tarihi_1, INTERVAL gecerlilik_suresi YEAR) >= '$bugun'")->fetchColumn(),
    'suresi_dolmus' => $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE gecerlilik_suresi IS NOT NULL AND DATE_ADD(egitim_tarihi_1, INTERVAL gecerlilik_suresi YEAR) < '$bugun'")->fetchColumn(),
    'yakinda_dolacak' => $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE gecerlilik_suresi IS NOT NULL AND DATE_ADD(egitim_tarihi_1, INTERVAL gecerlilik_suresi YEAR) BETWEEN '$bugun' AND DATE_ADD('$bugun', INTERVAL 3 MONTH)")->fetchColumn()
];

// Tehlike sınıfı dağılımı
$tehlike_dagilim = $pdo->query("
    SELECT tehlike_sinifi, COUNT(*) as sayi 
    FROM sertifikalar 
    GROUP BY tehlike_sinifi 
    ORDER BY sayi DESC
")->fetchAll();

// En çok kullanılan eğitimler
$en_cok_egitimler = $pdo->query("
    SELECT e.egitim_adi, COUNT(*) as sayi 
    FROM sertifikalar s 
    LEFT JOIN egitimler e ON s.egitim_id = e.id 
    GROUP BY s.egitim_id 
    ORDER BY sayi DESC 
    LIMIT 10
")->fetchAll();

// Kullanıcı bazında istatistikler
$kullanici_stats = $pdo->query("
    SELECT u.ad_soyad, COUNT(*) as sertifika_sayisi 
    FROM sertifikalar s 
    LEFT JOIN kullanicilar u ON s.kullanici_id = u.id 
    GROUP BY s.kullanici_id 
    ORDER BY sertifika_sayisi DESC 
    LIMIT 10
")->fetchAll();

// Aylık trend (Son 12 ay)
$aylik_trend = $pdo->query("
    SELECT 
        DATE_FORMAT(kayit_tarihi, '%Y-%m') as ay,
        COUNT(*) as sayi
    FROM sertifikalar 
    WHERE kayit_tarihi >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(kayit_tarihi, '%Y-%m')
    ORDER BY ay
")->fetchAll();

// Kurum bazında istatistikler
$kurum_stats = $pdo->query("
    SELECT kurum_adi, COUNT(*) as sertifika_sayisi 
    FROM sertifikalar 
    GROUP BY kurum_adi 
    ORDER BY sertifika_sayisi DESC 
    LIMIT 10
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Raporlar ve İstatistikler - Online Sertifika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .report-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
                <a class="nav-link active" href="raporlar.php">
                    <i class="fas fa-chart-bar"></i> Raporlar
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
                        <h2><i class="fas fa-chart-bar"></i> Raporlar ve İstatistikler</h2>
                        <div>
                            <button class="btn btn-success me-2" onclick="tumVerileriIndir()">
                                <i class="fas fa-download"></i> Tüm Verileri İndir
                            </button>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ozelRaporModal">
                                <i class="fas fa-file-alt"></i> Özel Rapor Oluştur
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Genel İstatistikler -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-certificate fa-3x text-primary mb-3"></i>
                            <h3 class="text-primary"><?php echo number_format($stats['toplam_sertifika']); ?></h3>
                            <p class="card-text">Toplam Sertifika</p>
                            <small class="text-muted">Bugün: <?php echo $stats['bugun_sertifika']; ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-users fa-3x text-success mb-3"></i>
                            <h3 class="text-success"><?php echo number_format($stats['toplam_kullanici']); ?></h3>
                            <p class="card-text">Aktif Kullanıcı</p>
                            <small class="text-muted">Sistem kullanıcıları</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-check-circle fa-3x text-info mb-3"></i>
                            <h3 class="text-info"><?php echo number_format($stats['gecerli_sertifika']); ?></h3>
                            <p class="card-text">Geçerli Sertifika</p>
                            <small class="text-muted">Süresi dolmamış</small>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning mb-3"></i>
                            <h3 class="text-warning"><?php echo number_format($stats['yakinda_dolacak']); ?></h3>
                            <p class="card-text">Yakında Dolacak</p>
                            <small class="text-muted">3 ay içinde</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dönemsel İstatistikler -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-calendar-day fa-2x text-primary mb-2"></i>
                            <h4 class="text-primary"><?php echo number_format($stats['bugun_sertifika']); ?></h4>
                            <p class="card-text">Bugün Oluşturulan</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-calendar-alt fa-2x text-success mb-2"></i>
                            <h4 class="text-success"><?php echo number_format($stats['bu_ay_sertifika']); ?></h4>
                            <p class="card-text">Bu Ay Oluşturulan</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                            <h4 class="text-info"><?php echo number_format($stats['bu_yil_sertifika']); ?></h4>
                            <p class="card-text">Bu Yıl Oluşturulan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafikler -->
            <div class="row mb-4">
                <!-- Aylık Trend -->
                <div class="col-md-8">
                    <div class="card report-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-line"></i> Aylık Sertifika Trendi (Son 12 Ay)</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="aylikTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tehlike Sınıfı Dağılımı -->
                <div class="col-md-4">
                    <div class="card report-card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-pie"></i> Tehlike Sınıfı Dağılımı</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="tehlikeDagilimiChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Detaylı Tablolar -->
            <div class="row">
                <!-- En Çok Kullanılan Eğitimler -->
                <div class="col-md-6">
                    <div class="card report-card">
                        <div class="card-header">
                            <h5><i class="fas fa-trophy"></i> En Çok Kullanılan Eğitimler</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Sıra</th>
                                            <th>Eğitim Adı</th>
                                            <th>Sertifika Sayısı</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($en_cok_egitimler as $index => $egitim): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($egitim['egitim_adi'] ?: 'İSG Eğitimi'); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $egitim['sayi']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kullanıcı Performansı -->
                <div class="col-md-6">
                    <div class="card report-card">
                        <div class="card-header">
                            <h5><i class="fas fa-user-chart"></i> Kullanıcı Performansı</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Sıra</th>
                                            <th>Kullanıcı</th>
                                            <th>Oluşturulan Sertifika</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($kullanici_stats as $index => $kullanici): ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($kullanici['ad_soyad']); ?></td>
                                                <td>
                                                    <span class="badge bg-success"><?php echo $kullanici['sertifika_sayisi']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kurum İstatistikleri -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card report-card">
                        <div class="card-header">
                            <h5><i class="fas fa-building"></i> En Çok Sertifika Alan Kurumlar</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Sıra</th>
                                            <th>Kurum Adı</th>
                                            <th>Toplam Sertifika</th>
                                            <th>Yüzde</th>
                                            <th>Grafik</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $toplam = $stats['toplam_sertifika'];
                                        foreach ($kurum_stats as $index => $kurum): 
                                            $yuzde = $toplam > 0 ? round(($kurum['sertifika_sayisi'] / $toplam) * 100, 1) : 0;
                                        ?>
                                            <tr>
                                                <td><?php echo $index + 1; ?></td>
                                                <td><?php echo htmlspecialchars($kurum['kurum_adi']); ?></td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $kurum['sertifika_sayisi']; ?></span>
                                                </td>
                                                <td><?php echo $yuzde; ?>%</td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" role="progressbar" 
                                                             style="width: <?php echo $yuzde; ?>%" 
                                                             aria-valuenow="<?php echo $yuzde; ?>" 
                                                             aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Özel Rapor Modal -->
<div class="modal fade" id="ozelRaporModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Özel Rapor Oluştur</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="raporlar.php" method="GET">
                <input type="hidden" name="csv_indir" value="1">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Katılımcı Adı</label>
                            <input type="text" class="form-control" name="katilimci_ad" placeholder="Ad soyad ara...">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Kurum Adı</label>
                            <input type="text" class="form-control" name="kurum_adi" placeholder="Kurum ara...">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" name="baslangic_tarih">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" name="bitis_tarih">
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Eğitim Türü</label>
                            <select class="form-select" name="egitim_adi">
                                <option value="">Tümü</option>
                                <?php
                                $egitimler = $pdo->query("SELECT DISTINCT egitim_adi FROM egitimler ORDER BY egitim_adi")->fetchAll();
                                foreach ($egitimler as $egitim):
                                ?>
                                    <option value="<?php echo htmlspecialchars($egitim['egitim_adi']); ?>">
                                        <?php echo htmlspecialchars($egitim['egitim_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Tehlike Sınıfı</label>
                            <select class="form-select" name="tehlike_sinifi">
                                <option value="">Tümü</option>
                                <option value="Az Tehlikeli">Az Tehlikeli</option>
                                <option value="Tehlikeli">Tehlikeli</option>
                                <option value="Çok Tehlikeli">Çok Tehlikeli</option>
                                <option value="Belirtilmemiş">Belirtilmemiş</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle"></i>
                        Seçtiğiniz kriterlere uygun tüm sertifika kayıtları CSV formatında indirilecektir.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-download"></i> CSV İndir
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Aylık trend grafiği
const aylikData = <?php echo json_encode($aylik_trend); ?>;
const aylikLabels = aylikData.map(item => {
    const [year, month] = item.ay.split('-');
    const date = new Date(year, month - 1);
    return date.toLocaleDateString('tr-TR', { year: 'numeric', month: 'short' });
});
const aylikValues = aylikData.map(item => item.sayi);

const aylikCtx = document.getElementById('aylikTrendChart').getContext('2d');
new Chart(aylikCtx, {
    type: 'line',
    data: {
        labels: aylikLabels,
        datasets: [{
            label: 'Oluşturulan Sertifika Sayısı',
            data: aylikValues,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});

// Tehlike sınıfı dağılımı
const tehlikeData = <?php echo json_encode($tehlike_dagilim); ?>;
const tehlikeLabels = tehlikeData.map(item => item.tehlike_sinifi);
const tehlikeValues = tehlikeData.map(item => item.sayi);
const tehlikeColors = ['#28a745', '#ffc107', '#dc3545', '#6c757d'];

const tehlikeCtx = document.getElementById('tehlikeDagilimiChart').getContext('2d');
new Chart(tehlikeCtx, {
    type: 'doughnut',
    data: {
        labels: tehlikeLabels,
        datasets: [{
            data: tehlikeValues,
            backgroundColor: tehlikeColors,
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Tüm verileri indir
function tumVerileriIndir() {
    if (confirm('Tüm sertifika verilerini CSV olarak indirmek istediğinizden emin misiniz?')) {
        window.open('raporlar.php?csv_indir=1', '_blank');
    }
}
</script>

</body>
</html>