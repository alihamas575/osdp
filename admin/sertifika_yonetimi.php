<?php 
include '../config/database.php';
yonetici_kontrol();

$hata = '';
$basari = '';

// Toplu sertifika silme işlemi
if (isset($_POST['toplu_sil']) && isset($_POST['secili_sertifikalar'])) {
    $secili_ids = $_POST['secili_sertifikalar'];
    
    if (is_array($secili_ids) && !empty($secili_ids)) {
        try {
            $placeholders = str_repeat('?,', count($secili_ids) - 1) . '?';
            $stmt = $pdo->prepare("DELETE FROM sertifikalar WHERE id IN ($placeholders)");
            $stmt->execute($secili_ids);
            
            $silinen_sayi = $stmt->rowCount();
            $basari = "$silinen_sayi adet sertifika başarıyla silindi!";
        } catch (PDOException $e) {
            $hata = "Sertifikalar silinirken hata oluştu!";
        }
    }
}

// Sertifika silme işlemi
if (isset($_POST['sertifika_sil'])) {
    $id = intval($_POST['sertifika_id']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM sertifikalar WHERE id = ?");
        $stmt->execute([$id]);
        $basari = "Sertifika başarıyla silindi!";
    } catch (PDOException $e) {
        $hata = "Sertifika silinirken hata oluştu!";
    }
}

// Filtreleme parametreleri
$katilimci_ad = isset($_GET['katilimci_ad']) ? guvenli_veri($_GET['katilimci_ad']) : '';
$kurum_adi = isset($_GET['kurum_adi']) ? guvenli_veri($_GET['kurum_adi']) : '';
$baslangic_tarih = isset($_GET['baslangic_tarih']) ? $_GET['baslangic_tarih'] : '';
$bitis_tarih = isset($_GET['bitis_tarih']) ? $_GET['bitis_tarih'] : '';
$egitim_adi = isset($_GET['egitim_adi']) ? guvenli_veri($_GET['egitim_adi']) : '';
$tehlike_sinifi = isset($_GET['tehlike_sinifi']) ? $_GET['tehlike_sinifi'] : '';
$olusturan = isset($_GET['olusturan']) ? guvenli_veri($_GET['olusturan']) : '';

// Sayfa parametreleri
$sayfa = isset($_GET['sayfa']) ? max(1, intval($_GET['sayfa'])) : 1;
$kayit_per_sayfa = 25;
$offset = ($sayfa - 1) * $kayit_per_sayfa;

// WHERE koşulları
$where_conditions = [];
$params = [];

// Filtreleme koşulları
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

if (!empty($olusturan)) {
    $where_conditions[] = "u.ad_soyad LIKE ?";
    $params[] = "%$olusturan%";
}

// WHERE clause oluştur
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Toplam kayıt sayısı
$count_sql = "
    SELECT COUNT(*) 
    FROM sertifikalar s 
    LEFT JOIN egitimler e ON s.egitim_id = e.id 
    LEFT JOIN kullanicilar u ON s.kullanici_id = u.id
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$toplam_kayit = $count_stmt->fetchColumn();
$toplam_sayfa = ceil($toplam_kayit / $kayit_per_sayfa);

// Ana sorgu
$sql = "
    SELECT s.*, 
           e.egitim_adi,
           u.ad_soyad as olusturan,
           eg1.ad_soyad as egitimci1_ad,
           eg2.ad_soyad as egitimci2_ad
    FROM sertifikalar s 
    LEFT JOIN egitimler e ON s.egitim_id = e.id
    LEFT JOIN kullanicilar u ON s.kullanici_id = u.id
    LEFT JOIN egitimciler eg1 ON s.egitimci_1_id = eg1.id
    LEFT JOIN egitimciler eg2 ON s.egitimci_2_id = eg2.id
    $where_clause
    ORDER BY s.kayit_tarihi DESC
    LIMIT $kayit_per_sayfa OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$sertifikalar = $stmt->fetchAll();

// İstatistikler
$stats = [
    'toplam' => $pdo->query("SELECT COUNT(*) FROM sertifikalar")->fetchColumn(),
    'bugün' => $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE DATE(kayit_tarihi) = CURDATE()")->fetchColumn(),
    'bu_ay' => $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE MONTH(kayit_tarihi) = MONTH(CURDATE()) AND YEAR(kayit_tarihi) = YEAR(CURDATE())")->fetchColumn(),
    'geçerli' => $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE gecerlilik_suresi IS NULL OR DATE_ADD(egitim_tarihi_1, INTERVAL gecerlilik_suresi YEAR) >= CURDATE()")->fetchColumn()
];

// Eğitim listesi (filtreleme için)
$egitimler = $pdo->query("SELECT DISTINCT egitim_adi FROM egitimler ORDER BY egitim_adi")->fetchAll();

// Kullanıcı listesi (filtreleme için)
$kullanicilar = $pdo->query("SELECT DISTINCT ad_soyad FROM kullanicilar ORDER BY ad_soyad")->fetchAll();

function tarih_format($tarih) {
    return $tarih ? date('d.m.Y', strtotime($tarih)) : '';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifika Yönetimi - Online Sertifika</title>
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
        .stat-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .filter-card {
            background: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
        .status-badge {
            font-size: 0.75rem;
        }
        .sticky-header {
            position: sticky;
            top: 0;
            z-index: 10;
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
                <a class="nav-link active" href="sertifika_yonetimi.php">
                    <i class="fas fa-certificate"></i> Sertifika Yönetimi
                </a>
                <a class="nav-link" href="egitim_yonetimi.php">
                    <i class="fas fa-book"></i> Eğitim Yönetimi
                </a>
                <a class="nav-link" href="raporlar.php">
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
                        <h2><i class="fas fa-certificate"></i> Sertifika Yönetimi</h2>
                        <div>
                            <button class="btn btn-warning me-2" onclick="topluIslem()">
                                <i class="fas fa-tools"></i> Toplu İşlemler
                            </button>
                            <a href="../user/sertifika_olustur.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Yeni Sertifika
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

            <!-- İstatistik Kartları -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-certificate fa-3x text-primary mb-3"></i>
                            <h3 class="text-primary"><?php echo $stats['toplam']; ?></h3>
                            <p class="card-text">Toplam Sertifika</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-calendar-day fa-3x text-success mb-3"></i>
                            <h3 class="text-success"><?php echo $stats['bugün']; ?></h3>
                            <p class="card-text">Bugün Oluşturulan</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-calendar-alt fa-3x text-info mb-3"></i>
                            <h3 class="text-info"><?php echo $stats['bu_ay']; ?></h3>
                            <p class="card-text">Bu Ay Oluşturulan</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-check-circle fa-3x text-warning mb-3"></i>
                            <h3 class="text-warning"><?php echo $stats['geçerli']; ?></h3>
                            <p class="card-text">Geçerli Sertifika</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtre Kartı -->
            <div class="filter-card p-4 mb-4">
                <h5 class="mb-3"><i class="fas fa-filter"></i> Gelişmiş Filtreleme</h5>
                
                <form method="GET" action="sertifika_yonetimi.php" id="filtreForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Katılımcı Adı</label>
                            <input type="text" class="form-control" name="katilimci_ad" 
                                   value="<?php echo htmlspecialchars($katilimci_ad); ?>" 
                                   placeholder="Ad soyad ara...">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Kurum Adı</label>
                            <input type="text" class="form-control" name="kurum_adi" 
                                   value="<?php echo htmlspecialchars($kurum_adi); ?>" 
                                   placeholder="Kurum ara...">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Başlangıç Tarihi</label>
                            <input type="date" class="form-control" name="baslangic_tarih" 
                                   value="<?php echo $baslangic_tarih; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Bitiş Tarihi</label>
                            <input type="date" class="form-control" name="bitis_tarih" 
                                   value="<?php echo $bitis_tarih; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Eğitim Türü</label>
                            <select class="form-select" name="egitim_adi">
                                <option value="">Tümü</option>
                                <?php foreach ($egitimler as $egitim): ?>
                                    <option value="<?php echo htmlspecialchars($egitim['egitim_adi']); ?>"
                                            <?php echo $egitim_adi == $egitim['egitim_adi'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($egitim['egitim_adi']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Tehlike Sınıfı</label>
                            <select class="form-select" name="tehlike_sinifi">
                                <option value="">Tümü</option>
                                <option value="Az Tehlikeli" <?php echo $tehlike_sinifi == 'Az Tehlikeli' ? 'selected' : ''; ?>>Az Tehlikeli</option>
                                <option value="Tehlikeli" <?php echo $tehlike_sinifi == 'Tehlikeli' ? 'selected' : ''; ?>>Tehlikeli</option>
                                <option value="Çok Tehlikeli" <?php echo $tehlike_sinifi == 'Çok Tehlikeli' ? 'selected' : ''; ?>>Çok Tehlikeli</option>
                                <option value="Belirtilmemiş" <?php echo $tehlike_sinifi == 'Belirtilmemiş' ? 'selected' : ''; ?>>Belirtilmemiş</option>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Oluşturan</label>
                            <select class="form-select" name="olusturan">
                                <option value="">Tümü</option>
                                <?php foreach ($kullanicilar as $kullanici): ?>
                                    <option value="<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>"
                                            <?php echo $olusturan == $kullanici['ad_soyad'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($kullanici['ad_soyad']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrele
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <a href="sertifika_yonetimi.php" class="btn btn-outline-secondary">
                                <i class="fas fa-eraser"></i> Filtreleri Temizle
                            </a>
                            <button type="button" class="btn btn-success ms-2" onclick="csvIndir()">
                                <i class="fas fa-file-csv"></i> CSV İndir
                            </button>
                            <span class="ms-3 text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Toplam <?php echo $toplam_kayit; ?> kayıt bulundu
                            </span>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Sonuçlar Tablosu -->
            <?php if (empty($sertifikalar)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h5>Sertifika Bulunamadı</h5>
                    <p>Arama kriterlerinize uygun sertifika kaydı bulunamadı.</p>
                    <a href="../user/sertifika_olustur.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Yeni Sertifika Oluşturun
                    </a>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-header sticky-header bg-white">
                        <div class="row align-items-center">
                            <div class="col">
                                <h5 class="mb-0">
                                    <i class="fas fa-list"></i> 
                                    Sertifika Kayıtları 
                                    <span class="badge bg-primary"><?php echo $toplam_kayit; ?></span>
                                </h5>
                            </div>
                            <div class="col-auto">
                                <div class="btn-group">
                                    <button class="btn btn-success btn-sm" onclick="topluYazdir()" id="topluYazdirBtn" disabled>
                                        <i class="fas fa-print"></i> Toplu Yazdır (<span id="seciliSayi">0</span>)
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="topluSil()" id="topluSilBtn" disabled>
                                        <i class="fas fa-trash"></i> Toplu Sil
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark sticky-header">
                                    <tr>
                                        <th width="5%">
                                            <input type="checkbox" id="selectAll" onchange="toggleAll(this)">
                                        </th>
                                        <th>Katılımcı</th>
                                        <th>Kurum</th>
                                        <th>Eğitim</th>
                                        <th>Tarih</th>
                                        <th>Tehlike Sınıfı</th>
                                        <th>Süre</th>
                                        <th>Geçerlilik</th>
                                        <th>Oluşturan</th>
                                        <th width="12%">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sertifikalar as $sertifika): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="sertifika-checkbox" 
                                                       value="<?php echo $sertifika['id']; ?>"
                                                       onchange="updateButtons()">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($sertifika['katilimci_ad_soyad']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($sertifika['gorevi']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($sertifika['kurum_adi']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($sertifika['egitim_adi'] ?: 'İSG Eğitimi'); ?>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($sertifika['egitim_sekli']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo tarih_format($sertifika['egitim_tarihi_1']); ?>
                                                <?php if ($sertifika['egitim_tarihi_2']): ?>
                                                    <br><small class="text-muted">- <?php echo tarih_format($sertifika['egitim_tarihi_2']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php
                                                    echo $sertifika['tehlike_sinifi'] == 'Az Tehlikeli' ? 'bg-success' : 
                                                        ($sertifika['tehlike_sinifi'] == 'Tehlikeli' ? 'bg-warning' : 
                                                        ($sertifika['tehlike_sinifi'] == 'Çok Tehlikeli' ? 'bg-danger' : 'bg-secondary'));
                                                ?> status-badge">
                                                    <?php echo htmlspecialchars($sertifika['tehlike_sinifi']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $sertifika['egitim_suresi']; ?> Saat</td>
                                            <td>
                                                <?php 
                                                if ($sertifika['gecerlilik_suresi']) {
                                                    $gecerlilik_tarihi = date('Y-m-d', strtotime($sertifika['egitim_tarihi_1'] . ' +' . $sertifika['gecerlilik_suresi'] . ' years'));
                                                    $bugun = date('Y-m-d');
                                                    
                                                    if ($gecerlilik_tarihi < $bugun) {
                                                        echo '<span class="badge bg-danger">Süresi Dolmuş</span>';
                                                    } elseif ($gecerlilik_tarihi <= date('Y-m-d', strtotime('+3 months'))) {
                                                        echo '<span class="badge bg-warning">Yakında Dolacak</span>';
                                                    } else {
                                                        echo '<span class="badge bg-success">Geçerli</span>';
                                                    }
                                                    echo '<br><small class="text-muted">' . tarih_format($gecerlilik_tarihi) . '</small>';
                                                } else {
                                                    echo '<span class="badge bg-info">Sonsuz</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars($sertifika['olusturan']); ?></small>
                                                <br>
                                                <small class="text-muted"><?php echo date('d.m.Y', strtotime($sertifika['kayit_tarihi'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical d-grid gap-1">
                                                    <a href="../user/sertifika_onizleme.php?id=<?php echo $sertifika['id']; ?>" 
                                                       class="btn btn-primary btn-sm" title="Görüntüle" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="../user/sertifika_pdf.php?id=<?php echo $sertifika['id']; ?>" 
                                                       class="btn btn-success btn-sm" title="PDF İndir" target="_blank">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </a>
                                                    <a href="../user/sertifika_olustur.php?edit=<?php echo $sertifika['id']; ?>" 
                                                       class="btn btn-warning btn-sm" title="Düzenle">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-danger btn-sm" title="Sil"
                                                            onclick="sertifikaSil(<?php echo $sertifika['id']; ?>, '<?php echo htmlspecialchars($sertifika['katilimci_ad_soyad']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Sayfalama -->
                <?php if ($toplam_sayfa > 1): ?>
                    <nav aria-label="Sayfa navigasyonu" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($sayfa > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['sayfa' => $sayfa - 1])); ?>">
                                        <i class="fas fa-chevron-left"></i> Önceki
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $sayfa - 2);
                            $end = min($toplam_sayfa, $sayfa + 2);
                            
                            for ($i = $start; $i <= $end; $i++):
                            ?>
                                <li class="page-item <?php echo $i == $sayfa ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['sayfa' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($sayfa < $toplam_sayfa): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['sayfa' => $sayfa + 1])); ?>">
                                        Sonraki <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        
                        <div class="text-center text-muted">
                            Sayfa <?php echo $sayfa; ?> / <?php echo $toplam_sayfa; ?> 
                            (Toplam <?php echo $toplam_kayit; ?> kayıt)
                        </div>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Silme Onay Modal -->
<div class="modal fade" id="silmeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Sertifikayı Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong id="silinecekKatilimci"></strong> adlı katılımcının sertifikasını silmek istediğinizden emin misiniz?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Bu işlem geri alınamaz!
                </div>
            </div>
            <div class="modal-footer">
                <form method="POST" id="silmeForm">
                    <input type="hidden" name="sertifika_id" id="silinecekId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="sertifika_sil" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Sil
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Toplu Silme Modal -->
<div class="modal fade" id="topluSilmeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Toplu Sertifika Silme</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Seçili <strong id="topluSilinecekSayi"></strong> adet sertifikayı silmek istediğinizden emin misiniz?</p>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Bu işlem geri alınamaz! Tüm seçili sertifikalar kalıcı olarak silinecektir.
                </div>
            </div>
            <div class="modal-footer">
                <form method="POST" id="topluSilmeForm">
                    <div id="topluSilmeInputlar"></div>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="toplu_sil" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Toplu Sil
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Tümünü seç/bırak
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.sertifika-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = source.checked;
    });
    updateButtons();
}

// Buton durumlarını güncelle
function updateButtons() {
    const seciliCheckboxes = document.querySelectorAll('.sertifika-checkbox:checked');
    const sayi = seciliCheckboxes.length;
    
    document.getElementById('seciliSayi').textContent = sayi;
    document.getElementById('topluYazdirBtn').disabled = sayi === 0;
    document.getElementById('topluSilBtn').disabled = sayi === 0;
    
    // Tümünü seç checkbox durumunu güncelle
    const tumCheckboxes = document.querySelectorAll('.sertifika-checkbox');
    const selectAll = document.getElementById('selectAll');
    
    if (sayi === 0) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
    } else if (sayi === tumCheckboxes.length) {
        selectAll.checked = true;
        selectAll.indeterminate = false;
    } else {
        selectAll.checked = false;
        selectAll.indeterminate = true;
    }
}

// Toplu yazdırma
function topluYazdir() {
    const seciliIds = [];
    document.querySelectorAll('.sertifika-checkbox:checked').forEach(checkbox => {
        seciliIds.push(checkbox.value);
    });
    
    if (seciliIds.length === 0) {
        alert('Lütfen en az bir sertifika seçin.');
        return;
    }
    
    if (confirm(seciliIds.length + ' adet sertifikayı PDF olarak indirmek istediğinizden emin misiniz?')) {
        // Her sertifika için ayrı pencerede PDF aç
        seciliIds.forEach(id => {
            window.open('../user/sertifika_pdf.php?id=' + id, '_blank');
        });
    }
}

// Toplu silme
function topluSil() {
    const seciliIds = [];
    document.querySelectorAll('.sertifika-checkbox:checked').forEach(checkbox => {
        seciliIds.push(checkbox.value);
    });
    
    if (seciliIds.length === 0) {
        alert('Lütfen en az bir sertifika seçin.');
        return;
    }
    
    // Modal içeriğini güncelle
    document.getElementById('topluSilinecekSayi').textContent = seciliIds.length;
    
    // Hidden inputları oluştur
    const inputlarDiv = document.getElementById('topluSilmeInputlar');
    inputlarDiv.innerHTML = '';
    seciliIds.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'secili_sertifikalar[]';
        input.value = id;
        inputlarDiv.appendChild(input);
    });
    
    const modal = new bootstrap.Modal(document.getElementById('topluSilmeModal'));
    modal.show();
}

// Tekil sertifika silme
function sertifikaSil(id, katilimci) {
    document.getElementById('silinecekId').value = id;
    document.getElementById('silinecekKatilimci').textContent = katilimci;
    
    const modal = new bootstrap.Modal(document.getElementById('silmeModal'));
    modal.show();
}

// CSV indirme
function csvIndir() {
    const form = document.getElementById('filtreForm');
    const formData = new FormData(form);
    formData.append('csv_indir', '1');
    
    const params = new URLSearchParams();
    for (const pair of formData) {
        params.append(pair[0], pair[1]);
    }
    
    window.open('raporlar.php?' + params.toString(), '_blank');
}

// Sayfa yüklendiğinde buton durumlarını kontrol et
document.addEventListener('DOMContentLoaded', function() {
    updateButtons();
    
    // Checkbox event listener'ları ekle
    document.querySelectorAll('.sertifika-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateButtons);
    });
});

// Toplu işlemler
function topluIslem() {
    const seciliIds = [];
    document.querySelectorAll('.sertifika-checkbox:checked').forEach(checkbox => {
        seciliIds.push(checkbox.value);
    });
    
    if (seciliIds.length === 0) {
        alert('Lütfen en az bir sertifika seçin.');
        return;
    }
    
    const islemler = [
        'PDF İndir',
        'Toplu Sil',
        'Geçerlilik Kontrol',
        'Email Gönder'
    ];
    
    let menu = 'Toplu İşlemler (' + seciliIds.length + ' sertifika seçili):\n\n';
    islemler.forEach((islem, index) => {
        menu += (index + 1) + '. ' + islem + '\n';
    });
    
    const secim = prompt(menu + '\nLütfen işlem numarasını girin:');
    
    switch(secim) {
        case '1':
            topluYazdir();
            break;
        case '2':
            topluSil();
            break;
        case '3':
            alert('Geçerlilik kontrol özelliği yakında eklenecek.');
            break;
        case '4':
            alert('Email gönderme özelliği yakında eklenecek.');
            break;
        default:
            if (secim !== null) {
                alert('Geçersiz seçim!');
            }
    }
}
</script>

</body>
</html>