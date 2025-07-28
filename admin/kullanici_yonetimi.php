<?php 
include '../config/database.php';
yonetici_kontrol();

$hata = '';
$basari = '';

// Kullanıcı düzenleme işlemi
if (isset($_POST['kullanici_duzenle'])) {
    $id = intval($_POST['kullanici_id']);
    $kullanici_adi = guvenli_veri($_POST['kullanici_adi']);
    $email = guvenli_veri($_POST['email']);
    $ad_soyad = guvenli_veri($_POST['ad_soyad']);
    $telefon = guvenli_veri($_POST['telefon']);
    $rol = $_POST['rol'];
    $aktif = isset($_POST['aktif']) ? 1 : 0;
    
    try {
        // Eğer şifre girilmişse şifreyi de güncelle
        if (!empty($_POST['sifre'])) {
            $stmt = $pdo->prepare("UPDATE kullanicilar SET kullanici_adi = ?, email = ?, sifre = ?, ad_soyad = ?, telefon = ?, rol = ?, aktif = ? WHERE id = ?");
            $stmt->execute([$kullanici_adi, $email, md5($_POST['sifre']), $ad_soyad, $telefon, $rol, $aktif, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE kullanicilar SET kullanici_adi = ?, email = ?, ad_soyad = ?, telefon = ?, rol = ?, aktif = ? WHERE id = ?");
            $stmt->execute([$kullanici_adi, $email, $ad_soyad, $telefon, $rol, $aktif, $id]);
        }
        $basari = "Kullanıcı başarıyla güncellendi!";
    } catch (PDOException $e) {
        $hata = "Kullanıcı güncellenirken hata oluştu!";
    }
}

// Kullanıcı silme işlemi
if (isset($_POST['kullanici_sil'])) {
    $id = intval($_POST['kullanici_id']);
    
    // Kendi hesabını silmeyi engelle
    if ($id == $_SESSION['kullanici_id']) {
        $hata = "Kendi hesabınızı silemezsiniz!";
    } else {
        try {
            // Önce kullanıcının sertifikalarını kontrol et
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM sertifikalar WHERE kullanici_id = ?");
            $stmt->execute([$id]);
            $sertifika_sayisi = $stmt->fetchColumn();
            
            if ($sertifika_sayisi > 0) {
                $hata = "Bu kullanıcının $sertifika_sayisi adet sertifikası bulunduğu için silinemez!";
            } else {
                $stmt = $pdo->prepare("DELETE FROM kullanicilar WHERE id = ?");
                $stmt->execute([$id]);
                $basari = "Kullanıcı başarıyla silindi!";
            }
        } catch (PDOException $e) {
            $hata = "Kullanıcı silinirken hata oluştu!";
        }
    }
}

// Kullanıcı ekleme işlemi
if (isset($_POST['kullanici_ekle'])) {
    $kullanici_adi = guvenli_veri($_POST['kullanici_adi']);
    $email = guvenli_veri($_POST['email']);
    $sifre = $_POST['sifre'];
    $ad_soyad = guvenli_veri($_POST['ad_soyad']);
    $telefon = guvenli_veri($_POST['telefon']);
    $rol = $_POST['rol'];
    
    try {
        // Kullanıcı adı ve email kontrolü
        $stmt = $pdo->prepare("SELECT id FROM kullanicilar WHERE kullanici_adi = ? OR email = ?");
        $stmt->execute([$kullanici_adi, $email]);
        
        if ($stmt->rowCount() > 0) {
            $hata = "Bu kullanıcı adı veya email zaten kullanılıyor!";
        } else {
            // Yeni kullanıcı ekleme
            $stmt = $pdo->prepare("INSERT INTO kullanicilar (kullanici_adi, email, sifre, ad_soyad, telefon, rol) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kullanici_adi, $email, md5($sifre), $ad_soyad, $telefon, $rol]);
            $basari = "Kullanıcı başarıyla eklendi!";
        }
    } catch (PDOException $e) {
        $hata = "Kullanıcı eklenirken hata oluştu!";
    }
}

// Filtreleme parametreleri
$arama = isset($_GET['arama']) ? guvenli_veri($_GET['arama']) : '';
$rol_filtre = isset($_GET['rol']) ? $_GET['rol'] : '';
$durum_filtre = isset($_GET['durum']) ? $_GET['durum'] : '';

// Sayfa parametreleri
$sayfa = isset($_GET['sayfa']) ? max(1, intval($_GET['sayfa'])) : 1;
$kayit_per_sayfa = 20;
$offset = ($sayfa - 1) * $kayit_per_sayfa;

// WHERE koşulları
$where_conditions = [];
$params = [];

if (!empty($arama)) {
    $where_conditions[] = "(ad_soyad LIKE ? OR kullanici_adi LIKE ? OR email LIKE ?)";
    $params[] = "%$arama%";
    $params[] = "%$arama%";
    $params[] = "%$arama%";
}

if (!empty($rol_filtre)) {
    $where_conditions[] = "rol = ?";
    $params[] = $rol_filtre;
}

if ($durum_filtre !== '') {
    $where_conditions[] = "aktif = ?";
    $params[] = intval($durum_filtre);
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Toplam kayıt sayısı
$count_sql = "SELECT COUNT(*) FROM kullanicilar $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$toplam_kayit = $count_stmt->fetchColumn();
$toplam_sayfa = ceil($toplam_kayit / $kayit_per_sayfa);

// Ana sorgu
$sql = "SELECT * FROM kullanicilar $where_clause ORDER BY kayit_tarihi DESC LIMIT $kayit_per_sayfa OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$kullanicilar = $stmt->fetchAll();

// İstatistikler
$stats = [
    'toplam' => $pdo->query("SELECT COUNT(*) FROM kullanicilar")->fetchColumn(),
    'aktif' => $pdo->query("SELECT COUNT(*) FROM kullanicilar WHERE aktif = 1")->fetchColumn(),
    'yonetici' => $pdo->query("SELECT COUNT(*) FROM kullanicilar WHERE rol = 'yonetici'")->fetchColumn(),
    'bu_ay' => $pdo->query("SELECT COUNT(*) FROM kullanicilar WHERE MONTH(kayit_tarihi) = MONTH(CURDATE()) AND YEAR(kayit_tarihi) = YEAR(CURDATE())")->fetchColumn()
];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - Online Sertifika</title>
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
        .table-hover tbody tr:hover {
            background-color: #f8f9fa;
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
                <a class="nav-link active" href="kullanici_yonetimi.php">
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
                        <h2><i class="fas fa-users"></i> Kullanıcı Yönetimi</h2>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kullaniciEkleModal">
                            <i class="fas fa-plus"></i> Yeni Kullanıcı Ekle
                        </button>
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
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h3 class="text-primary"><?php echo $stats['toplam']; ?></h3>
                            <p class="card-text">Toplam Kullanıcı</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                            <h3 class="text-success"><?php echo $stats['aktif']; ?></h3>
                            <p class="card-text">Aktif Kullanıcı</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-user-shield fa-3x text-warning mb-3"></i>
                            <h3 class="text-warning"><?php echo $stats['yonetici']; ?></h3>
                            <p class="card-text">Yönetici</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-user-plus fa-3x text-info mb-3"></i>
                            <h3 class="text-info"><?php echo $stats['bu_ay']; ?></h3>
                            <p class="card-text">Bu Ay Katılan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtreleme -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5><i class="fas fa-filter"></i> Filtreleme ve Arama</h5>
                    
                    <form method="GET" action="kullanici_yonetimi.php">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Arama</label>
                                <input type="text" class="form-control" name="arama" 
                                       value="<?php echo htmlspecialchars($arama); ?>" 
                                       placeholder="Ad, kullanıcı adı veya email ara...">
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Rol</label>
                                <select class="form-select" name="rol">
                                    <option value="">Tümü</option>
                                    <option value="yonetici" <?php echo $rol_filtre == 'yonetici' ? 'selected' : ''; ?>>Yönetici</option>
                                    <option value="kullanici" <?php echo $rol_filtre == 'kullanici' ? 'selected' : ''; ?>>Kullanıcı</option>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Durum</label>
                                <select class="form-select" name="durum">
                                    <option value="">Tümü</option>
                                    <option value="1" <?php echo $durum_filtre === '1' ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="0" <?php echo $durum_filtre === '0' ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrele
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="kullanici_yonetimi.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-eraser"></i> Filtreleri Temizle
                            </a>
                            <span class="ms-3 text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Toplam <?php echo $toplam_kayit; ?> kullanıcı bulundu
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Kullanıcı Listesi -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> 
                        Kullanıcı Listesi 
                        <span class="badge bg-primary"><?php echo $toplam_kayit; ?></span>
                    </h5>
                </div>
                
                <div class="card-body p-0">
                    <?php if (empty($kullanicilar)): ?>
                        <div class="alert alert-info text-center m-4">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h5>Kullanıcı Bulunamadı</h5>
                            <p>Arama kriterlerinize uygun kullanıcı bulunamadı.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Kullanıcı</th>
                                        <th>İletişim</th>
                                        <th>Rol</th>
                                        <th>Durum</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>Son Aktivite</th>
                                        <th width="15%">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($kullanicilar as $kullanici): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-3">
                                                        <i class="fas fa-user-circle fa-2x text-muted"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($kullanici['ad_soyad']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($kullanici['kullanici_adi']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="fas fa-envelope text-muted"></i> 
                                                    <?php echo htmlspecialchars($kullanici['email']); ?>
                                                </div>
                                                <?php if ($kullanici['telefon']): ?>
                                                    <div>
                                                        <i class="fas fa-phone text-muted"></i> 
                                                        <?php echo htmlspecialchars($kullanici['telefon']); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $kullanici['rol'] == 'yonetici' ? 'bg-warning' : 'bg-info'; ?>">
                                                    <?php echo $kullanici['rol'] == 'yonetici' ? 'Yönetici' : 'Kullanıcı'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $kullanici['aktif'] ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $kullanici['aktif'] ? 'Aktif' : 'Pasif'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?php echo date('d.m.Y H:i', strtotime($kullanici['kayit_tarihi'])); ?></small>
                                            </td>
                                            <td>
                                                <small class="text-muted">-</small>
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical d-grid gap-1">
                                                    <button type="button" class="btn btn-warning btn-sm" 
                                                            onclick="kullaniciDuzenle(<?php echo htmlspecialchars(json_encode($kullanici)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($kullanici['id'] != $_SESSION['kullanici_id']): ?>
                                                        <button type="button" class="btn btn-danger btn-sm" 
                                                                onclick="kullaniciSil(<?php echo $kullanici['id']; ?>, '<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Kullanıcı Ekleme Modal -->
<div class="modal fade" id="kullaniciEkleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kullanıcı Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ad_soyad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="kullanici_adi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="tel" class="form-control" name="telefon">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="sifre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" name="rol" required>
                            <option value="kullanici">Kullanıcı</option>
                            <option value="yonetici">Yönetici</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="kullanici_ekle" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Kullanıcı Düzenleme Modal -->
<div class="modal fade" id="kullaniciDuzenleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="duzenleForm">
                <input type="hidden" name="kullanici_id" id="duzenle_id">
                <div class="modal-header">
                    <h5 class="modal-title">Kullanıcı Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="ad_soyad" id="duzenle_ad_soyad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="kullanici_adi" id="duzenle_kullanici_adi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" id="duzenle_email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="tel" class="form-control" name="telefon" id="duzenle_telefon">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Yeni Şifre (Boş bırakılırsa değişmez)</label>
                        <input type="password" class="form-control" name="sifre">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol <span class="text-danger">*</span></label>
                        <select class="form-select" name="rol" id="duzenle_rol" required>
                            <option value="kullanici">Kullanıcı</option>
                            <option value="yonetici">Yönetici</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="aktif" id="duzenle_aktif">
                            <label class="form-check-label" for="duzenle_aktif">
                                Aktif Kullanıcı
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="kullanici_duzenle" class="btn btn-warning">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Silme Onay Modal -->
<div class="modal fade" id="silmeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kullanıcıyı Sil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong id="silinecekKullanici"></strong> adlı kullanıcıyı silmek istediğinizden emin misiniz?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Bu işlem geri alınamaz! Kullanıcının tüm verileri silinecektir.
                </div>
            </div>
            <div class="modal-footer">
                <form method="POST" id="silmeForm">
                    <input type="hidden" name="kullanici_id" id="silinecekId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="kullanici_sil" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Sil
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Kullanıcı düzenleme fonksiyonu
function kullaniciDuzenle(kullanici) {
    document.getElementById('duzenle_id').value = kullanici.id;
    document.getElementById('duzenle_ad_soyad').value = kullanici.ad_soyad;
    document.getElementById('duzenle_kullanici_adi').value = kullanici.kullanici_adi;
    document.getElementById('duzenle_email').value = kullanici.email;
    document.getElementById('duzenle_telefon').value = kullanici.telefon || '';
    document.getElementById('duzenle_rol').value = kullanici.rol;
    document.getElementById('duzenle_aktif').checked = kullanici.aktif == 1;
    
    const modal = new bootstrap.Modal(document.getElementById('kullaniciDuzenleModal'));
    modal.show();
}

// Kullanıcı silme fonksiyonu
function kullaniciSil(id, ad_soyad) {
    document.getElementById('silinecekId').value = id;
    document.getElementById('silinecekKullanici').textContent = ad_soyad;
    
    const modal = new bootstrap.Modal(document.getElementById('silmeModal'));
    modal.show();
}
</script>

</body>
</html>