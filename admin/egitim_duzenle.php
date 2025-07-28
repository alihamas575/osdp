<?php
session_start();
include '../config/database.php';

// Giriş kontrolü - sisteminizle uyumlu
if (!function_exists('yonetici_kontrol')) {
    function yonetici_kontrol() {
        if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'yonetici') {
            header("Location: ../login.php");
            exit();
        }
    }
}

yonetici_kontrol();

$basarili_mesaj = "";
$hata_mesaj = "";

// Eğitim ID kontrolü
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: egitim_yonetimi.php");
    exit();
}

$egitim_id = (int)$_GET['id'];

// Eğitim bilgilerini getir - $pdo kullanarak
try {
    $stmt = $pdo->prepare("SELECT * FROM egitimler WHERE id = ?");
    $stmt->execute([$egitim_id]);
    $egitim = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$egitim) {
        $hata_mesaj = "Eğitim bulunamadı!";
    } else {
        // Eğitim konularını getir
        $stmt = $pdo->prepare("SELECT * FROM egitim_konulari WHERE egitim_id = ? ORDER BY sira_no");
        $stmt->execute([$egitim_id]);
        $egitim_konulari = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $hata_mesaj = "Eğitim bilgileri alınırken hata oluştu: " . $e->getMessage();
}

// Form gönderildi ise
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['egitim_guncelle']) && isset($egitim)) {
    $egitim_adi = trim($_POST['egitim_adi']);
    $egitim_suresi = (int)$_POST['egitim_suresi'];
    $egitim_kaynagi = trim($_POST['egitim_kaynagi']);
    $gecerlilik_suresi = !empty($_POST['gecerlilik_suresi']) ? (int)$_POST['gecerlilik_suresi'] : NULL;
    
    // Eğitim konuları
    $ana_konular = $_POST['ana_konu'] ?? [];
    $alt_konular = $_POST['alt_konu'] ?? [];
    
    if (empty($egitim_adi)) {
        $hata_mesaj = "Eğitim adı boş olamaz!";
    } elseif ($egitim_suresi <= 0) {
        $hata_mesaj = "Eğitim süresi pozitif bir sayı olmalıdır!";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Eğitimi güncelle
            $stmt = $pdo->prepare("UPDATE egitimler SET egitim_adi = ?, egitim_suresi = ?, egitim_kaynagi = ?, gecerlilik_suresi = ? WHERE id = ?");
            $stmt->execute([$egitim_adi, $egitim_suresi, $egitim_kaynagi, $gecerlilik_suresi, $egitim_id]);
            
            // Eski konuları sil
            $stmt = $pdo->prepare("DELETE FROM egitim_konulari WHERE egitim_id = ?");
            $stmt->execute([$egitim_id]);
            
            // Yeni konuları ekle
            if (!empty($ana_konular)) {
                $sira_no = 1;
                foreach ($ana_konular as $index => $ana_konu) {
                    if (!empty($ana_konu) && !empty($alt_konular[$index])) {
                        $stmt = $pdo->prepare("INSERT INTO egitim_konulari (egitim_id, ana_konu, alt_konu, sira_no) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$egitim_id, $ana_konu, trim($alt_konular[$index]), $sira_no]);
                        $sira_no++;
                    }
                }
            }
            
            $pdo->commit();
            $basarili_mesaj = "Eğitim başarıyla güncellendi!";
            
            // Güncellenmiş verileri tekrar yükle
            $stmt = $pdo->prepare("SELECT * FROM egitimler WHERE id = ?");
            $stmt->execute([$egitim_id]);
            $egitim = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $stmt = $pdo->prepare("SELECT * FROM egitim_konulari WHERE egitim_id = ? ORDER BY sira_no");
            $stmt->execute([$egitim_id]);
            $egitim_konulari = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $pdo->rollback();
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
    <title>Eğitimi Düzenle - Online Sertifika</title>
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
        <main class="col-md-10 p-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Eğitimi Düzenle</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="egitim_yonetimi.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Geri Dön
                    </a>
                </div>
            </div>

            <?php if ($basarili_mesaj): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $basarili_mesaj; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($hata_mesaj): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $hata_mesaj; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($egitim)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Eğitim Bilgileri</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="egitim_adi" class="form-label">Eğitim Adı <span class="text-danger">*</span></label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="egitim_adi" 
                                           name="egitim_adi" 
                                           value="<?php echo htmlspecialchars($egitim['egitim_adi']); ?>" 
                                           placeholder="Örn: İş Güvenliği Uzmanı Eğitimi"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="egitim_suresi" class="form-label">Eğitim Süresi (Saat) <span class="text-danger">*</span></label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="egitim_suresi" 
                                           name="egitim_suresi" 
                                           value="<?php echo $egitim['egitim_suresi']; ?>" 
                                           min="1" 
                                           placeholder="Saat"
                                           required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="gecerlilik_suresi" class="form-label">Geçerlilik Süresi (Yıl)</label>
                                    <input type="number" 
                                           class="form-control" 
                                           id="gecerlilik_suresi" 
                                           name="gecerlilik_suresi" 
                                           value="<?php echo $egitim['gecerlilik_suresi']; ?>" 
                                           min="1" 
                                           placeholder="Yıl (Opsiyonel)">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="egitim_kaynagi" class="form-label">Eğitim Kaynağı/Açıklama</label>
                            <textarea class="form-control" 
                                      id="egitim_kaynagi" 
                                      name="egitim_kaynagi" 
                                      rows="3" 
                                      placeholder="Eğitim hakkında açıklama, kaynak bilgileri vb."><?php echo htmlspecialchars($egitim['egitim_kaynagi']); ?></textarea>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Eğitim Konuları</h5>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="konuEkle()">
                                <i class="fas fa-plus"></i> Konu Ekle
                            </button>
                        </div>

                        <div id="egitim-konulari">
                            <!-- Mevcut konular JavaScript ile yüklenecek -->
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='egitim_yonetimi.php'">
                                <i class="fas fa-times"></i> İptal
                            </button>
                            <button type="submit" name="egitim_guncelle" class="btn btn-primary">
                                <i class="fas fa-save"></i> Eğitimi Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let konuSayisi = 0;
const anaKonular = [
    'Genel Konular',
    'Sağlık Konuları', 
    'Teknik Konular',
    'Diğer Konular'
];

// Mevcut konular
const mevcutKonular = <?php echo json_encode($egitim_konulari ?? []); ?>;

function konuEkle(anaKonu = '', altKonu = '') {
    konuSayisi++;
    const konuHTML = `
        <div class="card mb-3" id="konu-${konuSayisi}">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Ana Konu <span class="text-danger">*</span></label>
                        <select class="form-select" name="ana_konu[]" required>
                            <option value="">Ana Konu Seçin</option>
                            ${anaKonular.map(konu => 
                                `<option value="${konu}" ${konu === anaKonu ? 'selected' : ''}>${konu}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label">Alt Konu/Detay <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="alt_konu[]" 
                               value="${altKonu}" 
                               placeholder="Örn: Risk değerlendirmesi, Acil durum planları vb." required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="konuSil(${konuSayisi})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('egitim-konulari').insertAdjacentHTML('beforeend', konuHTML);
}

function konuSil(konuId) {
    const konuElement = document.getElementById(`konu-${konuId}`);
    if (konuElement) {
        konuElement.remove();
    }
}

// Sayfa yüklendiğinde mevcut konuları yükle
document.addEventListener('DOMContentLoaded', function() {
    if (mevcutKonular.length > 0) {
        mevcutKonular.forEach(konu => {
            konuEkle(konu.ana_konu, konu.alt_konu);
        });
    } else {
        // Hiç konu yoksa en az bir alan ekle
        konuEkle();
    }
});
</script>

</body>
</html>