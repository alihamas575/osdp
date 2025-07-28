<?php 
include '../config/database.php';
oturum_kontrol();

// Filtreleme parametreleri
$katilimci_ad = isset($_GET['katilimci_ad']) ? guvenli_veri($_GET['katilimci_ad']) : '';
$kurum_adi = isset($_GET['kurum_adi']) ? guvenli_veri($_GET['kurum_adi']) : '';
$baslangic_tarih = isset($_GET['baslangic_tarih']) ? $_GET['baslangic_tarih'] : '';
$bitis_tarih = isset($_GET['bitis_tarih']) ? $_GET['bitis_tarih'] : '';
$egitim_adi = isset($_GET['egitim_adi']) ? guvenli_veri($_GET['egitim_adi']) : '';
$tehlike_sinifi = isset($_GET['tehlike_sinifi']) ? $_GET['tehlike_sinifi'] : '';

// Sayfa parametreleri
$sayfa = isset($_GET['sayfa']) ? max(1, intval($_GET['sayfa'])) : 1;
$kayit_per_sayfa = 20;
$offset = ($sayfa - 1) * $kayit_per_sayfa;

// WHERE koşulları
$where_conditions = [];
$params = [];

// Kullanıcı yetkisi kontrolü (yönetici tüm kayıtları, kullanıcı sadece kendi kayıtlarını görebilir)
if ($_SESSION['rol'] != 'yonetici') {
    $where_conditions[] = "s.kullanici_id = ?";
    $params[] = $_SESSION['kullanici_id'];
}

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

// WHERE clause oluştur
$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Toplam kayıt sayısı
$count_sql = "
    SELECT COUNT(*) 
    FROM sertifikalar s 
    LEFT JOIN egitimler e ON s.egitim_id = e.id 
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

// Eğitim listesi (filtreleme için)
$egitimler = $pdo->query("SELECT DISTINCT egitim_adi FROM egitimler ORDER BY egitim_adi")->fetchAll();

// Sertifika silme işlemi
if (isset($_POST['sil']) && isset($_POST['sertifika_id'])) {
    $sertifika_id = intval($_POST['sertifika_id']);
    
    // Yetki kontrolü
    $yetki_sql = $_SESSION['rol'] == 'yonetici' ? 
        "SELECT id FROM sertifikalar WHERE id = ?" : 
        "SELECT id FROM sertifikalar WHERE id = ? AND kullanici_id = ?";
    
    $yetki_stmt = $pdo->prepare($yetki_sql);
    $yetki_params = $_SESSION['rol'] == 'yonetici' ? [$sertifika_id] : [$sertifika_id, $_SESSION['kullanici_id']];
    $yetki_stmt->execute($yetki_params);
    
    if ($yetki_stmt->rowCount() > 0) {
        $sil_stmt = $pdo->prepare("DELETE FROM sertifikalar WHERE id = ?");
        $sil_stmt->execute([$sertifika_id]);
        $basari = "Sertifika başarıyla silindi!";
        
        // Sayfayı yenile
        header("Location: eski_kayitlar.php?" . http_build_query($_GET));
        exit();
    } else {
        $hata = "Bu sertifikayı silme yetkiniz yok!";
    }
}

function tarih_format($tarih) {
    return $tarih ? date('d.m.Y', strtotime($tarih)) : '';
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eski Kayıtlar - Online Sertifika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
    </style>
</head>
<body class="bg-light">

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-certificate"></i> Online Sertifika
        </a>
        <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['ad_soyad']; ?>
                </a>
                <ul class="dropdown-menu">
                    <?php if ($_SESSION['rol'] == 'yonetici'): ?>
                        <li><a class="dropdown-item" href="../admin/dashboard.php">Yönetici Paneli</a></li>
                    <?php endif; ?>
                    <li><a class="dropdown-item" href="sertifika_olustur.php">Sertifika Oluştur</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../logout.php">Çıkış Yap</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-history"></i> Eski Kayıtlar</h2>
                <a href="sertifika_olustur.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Yeni Sertifika
                </a>
            </div>
        </div>
    </div>

    <!-- Hata/Başarı Mesajları -->
    <?php if (isset($hata)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $hata; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($basari)): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle"></i> <?php echo $basari; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filtre Kartı -->
    <div class="filter-card p-4 mb-4">
        <h5 class="mb-3"><i class="fas fa-filter"></i> Filtreleme ve Arama</h5>
        
        <form method="GET" action="eski_kayitlar.php">
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
                
                <div class="col-md-4">
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
                
                <div class="col-md-4">
                    <label class="form-label">Tehlike Sınıfı</label>
                    <select class="form-select" name="tehlike_sinifi">
                        <option value="">Tümü</option>
                        <option value="Az Tehlikeli" <?php echo $tehlike_sinifi == 'Az Tehlikeli' ? 'selected' : ''; ?>>Az Tehlikeli</option>
                        <option value="Tehlikeli" <?php echo $tehlike_sinifi == 'Tehlikeli' ? 'selected' : ''; ?>>Tehlikeli</option>
                        <option value="Çok Tehlikeli" <?php echo $tehlike_sinifi == 'Çok Tehlikeli' ? 'selected' : ''; ?>>Çok Tehlikeli</option>
                        <option value="Belirtilmemiş" <?php echo $tehlike_sinifi == 'Belirtilmemiş' ? 'selected' : ''; ?>>Belirtilmemiş</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filtrele
                        </button>
                    </div>
                </div>
                
                <div class="col-12">
                    <a href="eski_kayitlar.php" class="btn btn-outline-secondary">
                        <i class="fas fa-eraser"></i> Filtreleri Temizle
                    </a>
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
            <h5>Kayıt Bulunamadı</h5>
            <p>Arama kriterlerinize uygun sertifika kaydı bulunamadı.</p>
            <a href="sertifika_olustur.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> İlk Sertifikanızı Oluşturun
            </a>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h5 class="mb-0">
                            <i class="fas fa-list"></i> 
                            Sertifika Kayıtları 
                            <span class="badge bg-primary"><?php echo $toplam_kayit; ?></span>
                        </h5>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-success btn-sm" onclick="topluYazdir()">
                            <i class="fas fa-print"></i> Toplu Yazdır
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-dark">
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
                                <?php if ($_SESSION['rol'] == 'yonetici'): ?>
                                    <th>Oluşturan</th>
                                <?php endif; ?>
                                <th width="15%">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sertifikalar as $sertifika): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="sertifika-checkbox" 
                                               value="<?php echo $sertifika['id']; ?>">
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
                                    <?php if ($_SESSION['rol'] == 'yonetici'): ?>
                                        <td>
                                            <small><?php echo htmlspecialchars($sertifika['olusturan']); ?></small>
                                            <br>
                                            <small class="text-muted"><?php echo date('d.m.Y', strtotime($sertifika['kayit_tarihi'])); ?></small>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="btn-group-vertical d-grid gap-1">
                                            <a href="sertifika_onizleme.php?id=<?php echo $sertifika['id']; ?>" 
                                               class="btn btn-primary btn-sm" title="Görüntüle">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="sertifika_pdf.php?id=<?php echo $sertifika['id']; ?>" 
                                               class="btn btn-success btn-sm" title="PDF İndir" target="_blank">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                            <a href="sertifika_olustur.php?edit=<?php echo $sertifika['id']; ?>" 
                                               class="btn btn-warning btn-sm" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($_SESSION['rol'] == 'yonetici' || $sertifika['kullanici_id'] == $_SESSION['kullanici_id']): ?>
                                                <button type="button" class="btn btn-danger btn-sm" title="Sil"
                                                        onclick="sertifikaSil(<?php echo $sertifika['id']; ?>, '<?php echo htmlspecialchars($sertifika['katilimci_ad_soyad']); ?>')">
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
                    <button type="submit" name="sil" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Sil
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sertifika silme fonksiyonu
function sertifikaSil(id, katilimci) {
    document.getElementById('silinecekId').value = id;
    document.getElementById('silinecekKatilimci').textContent = katilimci;
    
    const modal = new bootstrap.Modal(document.getElementById('silmeModal'));
    modal.show();
}

// Tümünü seç/bırak
function toggleAll(source) {
    const checkboxes = document.querySelectorAll('.sertifika-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = source.checked;
    });
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
            window.open('sertifika_pdf.php?id=' + id, '_blank');
        });
    }
}

// Sayfa yenilendiğinde checkbox durumlarını kontrol et
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.sertifika-checkbox');
    const selectAll = document.getElementById('selectAll');
    
    function updateSelectAllState() {
        const totalCheckboxes = checkboxes.length;
        const checkedCheckboxes = document.querySelectorAll('.sertifika-checkbox:checked').length;
        
        selectAll.checked = totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes;
        selectAll.indeterminate = checkedCheckboxes > 0 && checkedCheckboxes < totalCheckboxes;
    }
    
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectAllState);
    });
    
    updateSelectAllState();
});
</script>

</body>
</html>