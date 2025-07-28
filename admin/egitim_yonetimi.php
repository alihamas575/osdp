<?php 
include '../config/database.php';
yonetici_kontrol();

$hata = '';
$basari = '';

// Eğitim silme işlemi - AJAX ile değişti, bu kısım artık kullanılmayacak
if (isset($_POST['egitim_sil'])) {
    $id = intval($_POST['egitim_id']);
    
    try {
        // Önce bu eğitimle ilgili sertifika var mı kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM sertifikalar WHERE egitim_id = ?");
        $stmt->execute([$id]);
        $sertifika_sayisi = $stmt->fetchColumn();
        
        if ($sertifika_sayisi > 0) {
            $hata = "Bu eğitimle ilgili $sertifika_sayisi adet sertifika bulunduğu için silinemez!";
        } else {
            // Önce eğitim konularını sil
            $stmt = $pdo->prepare("DELETE FROM egitim_konulari WHERE egitim_id = ?");
            $stmt->execute([$id]);
            
            // Sonra eğitimi sil
            $stmt = $pdo->prepare("DELETE FROM egitimler WHERE id = ?");
            $stmt->execute([$id]);
            
            $basari = "Eğitim başarıyla silindi!";
        }
    } catch (PDOException $e) {
        $hata = "Eğitim silinirken hata oluştu!";
    }
}

// Filtreleme parametreleri
$arama = isset($_GET['arama']) ? guvenli_veri($_GET['arama']) : '';

// Sayfa parametreleri
$sayfa = isset($_GET['sayfa']) ? max(1, intval($_GET['sayfa'])) : 1;
$kayit_per_sayfa = 20;
$offset = ($sayfa - 1) * $kayit_per_sayfa;

// WHERE koşulları
$where_conditions = [];
$params = [];

if (!empty($arama)) {
    $where_conditions[] = "(e.egitim_adi LIKE ? OR e.egitim_kaynagi LIKE ?)";
    $params[] = "%$arama%";
    $params[] = "%$arama%";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Toplam kayıt sayısı
$count_sql = "SELECT COUNT(*) FROM egitimler e $where_clause";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$toplam_kayit = $count_stmt->fetchColumn();
$toplam_sayfa = ceil($toplam_kayit / $kayit_per_sayfa);

// Ana sorgu
$sql = "
    SELECT e.*, 
           u.ad_soyad as olusturan,
           (SELECT COUNT(*) FROM sertifikalar WHERE egitim_id = e.id) as sertifika_sayisi
    FROM egitimler e 
    LEFT JOIN kullanicilar u ON e.kullanici_id = u.id
    $where_clause
    ORDER BY e.egitim_adi
    LIMIT $kayit_per_sayfa OFFSET $offset
";

<a class="nav-link" href="egitim_yonetimi.php">
    <i class="fas fa-book"></i> Eğitim Yönetimi
</a>
<!-- ✅ BU SATIRI EKLEYİN -->
<a class="nav-link" href="egitimci_yonetimi.php">
    <i class="fas fa-chalkboard-teacher"></i> Eğitimci Yönetimi
</a>
<a class="nav-link" href="raporlar.php">
    <i class="fas fa-chart-bar"></i> Raporlar
</a>
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$egitimler = $stmt->fetchAll();

// İstatistikler
$stats = [
    'toplam_egitim' => $pdo->query("SELECT COUNT(*) FROM egitimler")->fetchColumn(),
    'toplam_konu' => $pdo->query("SELECT COUNT(*) FROM egitim_konulari")->fetchColumn(),
    'en_cok_kullanilan' => $pdo->query("SELECT COUNT(*) as sayi FROM sertifikalar GROUP BY egitim_id ORDER BY sayi DESC LIMIT 1")->fetchColumn() ?: 0
];
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eğitim Yönetimi - Online Sertifika</title>
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
        .egitim-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .egitim-card:hover {
            transform: translateY(-2px);
        }
        .konu-badge {
            font-size: 0.75rem;
            margin: 2px;
        }
        .konular-container {
            max-height: 150px;
            overflow-y: auto;
        }
        .konu-baslik {
            font-weight: bold;
            color: #495057;
            margin-top: 8px;
            margin-bottom: 4px;
        }
        .konu-item {
            font-size: 0.85em;
            color: #6c757d;
            margin-left: 10px;
            margin-bottom: 2px;
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
                <a class="nav-link active" href="egitim_yonetimi.php">
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
                        <h2><i class="fas fa-book"></i> Eğitim Yönetimi</h2>
                        <!-- ✅ DÜZELTME: Doğru yönlendirme -->
                        <a href="egitim_ekle.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Yeni Eğitim Ekle
                        </a>
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
                <div class="col-md-4">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-book fa-3x text-primary mb-3"></i>
                            <h3 class="text-primary"><?php echo $stats['toplam_egitim']; ?></h3>
                            <p class="card-text">Toplam Eğitim Türü</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-list fa-3x text-success mb-3"></i>
                            <h3 class="text-success"><?php echo $stats['toplam_konu']; ?></h3>
                            <p class="card-text">Toplam Eğitim Konusu</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-star fa-3x text-warning mb-3"></i>
                            <h3 class="text-warning"><?php echo $stats['en_cok_kullanilan']; ?></h3>
                            <p class="card-text">En Çok Kullanılan</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtreleme -->
            <div class="card mb-4">
                <div class="card-body">
                    <h5><i class="fas fa-filter"></i> Arama ve Filtreleme</h5>
                    
                    <form method="GET" action="egitim_yonetimi.php">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Eğitim Adı veya Kaynağı Ara</label>
                                <input type="text" class="form-control" name="arama" 
                                       value="<?php echo htmlspecialchars($arama); ?>" 
                                       placeholder="Eğitim adı veya kaynak ara...">
                            </div>
                            
                            <div class="col-md-4">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Ara
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <a href="egitim_yonetimi.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-eraser"></i> Temizle
                            </a>
                            <span class="ms-3 text-muted">
                                <i class="fas fa-info-circle"></i> 
                                Toplam <?php echo $toplam_kayit; ?> eğitim bulundu
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Eğitim Listesi -->
            <?php if (empty($egitimler)): ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-3x mb-3"></i>
                    <h5>Eğitim Bulunamadı</h5>
                    <p>Henüz hiç eğitim tanımlanmamış veya arama kriterlerinize uygun eğitim bulunamadı.</p>
                    <!-- ✅ DÜZELTME: Doğru yönlendirme -->
                    <a href="egitim_ekle.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> İlk Eğitimi Ekleyin
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($egitimler as $egitim): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card egitim-card h-100">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h6 class="mb-0">
                                            <i class="fas fa-book text-primary"></i>
                                            <?php echo htmlspecialchars($egitim['egitim_adi']); ?>
                                        </h6>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="fas fa-cog"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <!-- ✅ DÜZELTME: Doğru yönlendirme -->
                                                    <a class="dropdown-item" href="egitim_duzenle.php?id=<?php echo $egitim['id']; ?>">
                                                        <i class="fas fa-edit"></i> Düzenle
                                                    </a>
                                                </li>
                                                <li>
                                                    <!-- ✅ DÜZELTME: AJAX silme -->
                                                    <a class="dropdown-item text-danger" href="#" onclick="egitimSil(<?php echo $egitim['id']; ?>, '<?php echo htmlspecialchars($egitim['egitim_adi']); ?>')">
                                                        <i class="fas fa-trash"></i> Sil
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-6">
                                            <small class="text-muted">Süre</small>
                                            <div><strong><?php echo $egitim['egitim_suresi']; ?> Saat</strong></div>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted">Geçerlilik</small>
                                            <div><strong><?php echo $egitim['gecerlilik_suresi'] ? $egitim['gecerlilik_suresi'] . ' Yıl' : 'Sonsuz'; ?></strong></div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($egitim['egitim_kaynagi']): ?>
                                        <div class="mb-3">
                                            <small class="text-muted">Kaynak</small>
                                            <div class="small"><?php echo htmlspecialchars($egitim['egitim_kaynagi']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- ✅ YENİ: Eğitim Konuları Detaylı Görünüm -->
                                    <?php
                                    $stmt = $pdo->prepare("SELECT ana_konu, alt_konu FROM egitim_konulari WHERE egitim_id = ? ORDER BY sira_no");
                                    $stmt->execute([$egitim['id']]);
                                    $konular = $stmt->fetchAll();
                                    
                                    // Konuları ana konulara göre grupla
                                    $gruplu_konular = [];
                                    foreach ($konular as $konu) {
                                        $gruplu_konular[$konu['ana_konu']][] = $konu['alt_konu'];
                                    }
                                    ?>
                                    
                                    <?php if (!empty($gruplu_konular)): ?>
                                        <div class="mb-3">
                                            <small class="text-muted">Eğitim Konuları</small>
                                            <div class="konular-container">
                                                <?php 
                                                $ana_konular_sirali = ['Genel Konular', 'Sağlık Konuları', 'Teknik Konular', 'Diğer Konular'];
                                                foreach ($ana_konular_sirali as $ana_konu):
                                                    if (isset($gruplu_konular[$ana_konu])):
                                                ?>
                                                    <div class="konu-baslik"><?php echo $ana_konu; ?> (<?php echo count($gruplu_konular[$ana_konu]); ?>)</div>
                                                    <?php foreach ($gruplu_konular[$ana_konu] as $alt_konu): ?>
                                                        <div class="konu-item">• <?php echo htmlspecialchars($alt_konu); ?></div>
                                                    <?php endforeach; ?>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-3">
                                            <small class="text-muted text-warning">Eğitim Konuları</small>
                                            <div class="small text-warning">Henüz konu eklenmemiş</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-certificate"></i> 
                                            <?php echo $egitim['sertifika_sayisi']; ?> sertifika
                                        </small>
                                        <?php if ($egitim['olusturan']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-user"></i> 
                                                <?php echo htmlspecialchars($egitim['olusturan']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ✅ YENİ: AJAX ile Eğitim silme fonksiyonu
function egitimSil(egitimId, egitimAdi) {
    if (confirm(`"${egitimAdi}" adlı eğitimi silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz ve bu eğitime ait tüm konular da silinecektir.`)) {
        fetch('egitim_sil.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `egitim_id=${egitimId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => {
            alert('Bir hata oluştu: ' + error);
        });
    }
}
</script>

</body>
</html>