<?php
session_start();
include '../config/database.php';

// Session kontrol
if (!function_exists('oturum_kontrol')) {
    function oturum_kontrol() {
        if (!isset($_SESSION['kullanici_id'])) {
            header("Location: ../login.php");
            exit();
        }
    }
}

// ✅ guvenli_veri fonksiyonu tanımla
if (!function_exists('guvenli_veri')) {
    function guvenli_veri($veri) {
        return htmlspecialchars(trim($veri));
    }
}

oturum_kontrol();

$hata = '';
$basari = '';

// EDIT MODE
$edit_mode = false;
$edit_sertifika = null;

if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    try {
        $edit_id = intval($_GET['edit']);
        
        // SQL hazırla
        $edit_sql = $_SESSION['rol'] == 'yonetici' ? 
            "SELECT s.*, e.egitim_adi FROM sertifikalar s LEFT JOIN egitimler e ON s.egitim_id = e.id WHERE s.id = ?" : 
            "SELECT s.*, e.egitim_adi FROM sertifikalar s LEFT JOIN egitimler e ON s.egitim_id = e.id WHERE s.id = ? AND s.kullanici_id = ?";
        
        $edit_stmt = $pdo->prepare($edit_sql);
        $edit_params = $_SESSION['rol'] == 'yonetici' ? [$edit_id] : [$edit_id, $_SESSION['kullanici_id']];
        
        $edit_stmt->execute($edit_params);
        $edit_sertifika = $edit_stmt->fetch();
        
        if ($edit_sertifika) {
            $edit_mode = true;
        } else {
            header("Location: eski_kayitlar.php");
            exit();
        }
        
    } catch (Exception $e) {
        $hata = "Sertifika bilgileri alınırken hata oluştu: " . $e->getMessage();
    }
}

// Eğitim ve eğitimci verilerini çek
try {
    $egitimler = $pdo->query("SELECT * FROM egitimler ORDER BY egitim_adi")->fetchAll();
    $egitimciler = $pdo->query("SELECT * FROM egitimciler WHERE kullanici_id = " . $_SESSION['kullanici_id'] . " OR kullanici_id IN (SELECT id FROM kullanicilar WHERE rol = 'yonetici') ORDER BY ad_soyad")->fetchAll();
} catch (Exception $e) {
    $hata = "Veriler yüklenirken hata oluştu: " . $e->getMessage();
}

// Sertifika güncelleme işlemi
if (isset($_POST['sertifika_guncelle']) && $edit_mode) {
    try {
        $sertifika_id = intval($_POST['sertifika_id']);
        $egitim_tarihi_1 = $_POST['egitim_tarihi_1'];
        $egitim_tarihi_2 = !empty($_POST['egitim_tarihi_2']) ? $_POST['egitim_tarihi_2'] : NULL;
        $gecerlilik_suresi = !empty($_POST['gecerlilik_suresi_form']) ? intval($_POST['gecerlilik_suresi_form']) : NULL;
        $egitim_id = !empty($_POST['egitim_id']) ? intval($_POST['egitim_id']) : NULL;
        $katilimci_ad_soyad = guvenli_veri($_POST['katilimci_ad_soyad']);
        $kurum_adi = guvenli_veri($_POST['kurum_adi']);
        $gorevi = guvenli_veri($_POST['gorevi']);
        $tehlike_sinifi = $_POST['tehlike_sinifi'];
        $egitim_suresi = intval($_POST['egitim_suresi_form']);
        $egitim_sekli = $_POST['egitim_sekli'];
        $egitimci_1_id = intval($_POST['egitimci_1_id']);
        $egitimci_2_id = !empty($_POST['egitimci_2_id']) ? intval($_POST['egitimci_2_id']) : NULL;
        
        // Yetki kontrolü
        $yetki_sql = $_SESSION['rol'] == 'yonetici' ? 
            "SELECT id FROM sertifikalar WHERE id = ?" : 
            "SELECT id FROM sertifikalar WHERE id = ? AND kullanici_id = ?";
        
        $yetki_stmt = $pdo->prepare($yetki_sql);
        $yetki_params = $_SESSION['rol'] == 'yonetici' ? [$sertifika_id] : [$sertifika_id, $_SESSION['kullanici_id']];
        $yetki_stmt->execute($yetki_params);
        
        if ($yetki_stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE sertifikalar SET egitim_tarihi_1 = ?, egitim_tarihi_2 = ?, gecerlilik_suresi = ?, egitim_id = ?, katilimci_ad_soyad = ?, kurum_adi = ?, gorevi = ?, tehlike_sinifi = ?, egitim_suresi = ?, egitim_sekli = ?, egitimci_1_id = ?, egitimci_2_id = ? WHERE id = ?");
            
            $stmt->execute([$egitim_tarihi_1, $egitim_tarihi_2, $gecerlilik_suresi, $egitim_id, $katilimci_ad_soyad, $kurum_adi, $gorevi, $tehlike_sinifi, $egitim_suresi, $egitim_sekli, $egitimci_1_id, $egitimci_2_id, $sertifika_id]);
            
            $basari = "Sertifika başarıyla güncellendi!";
        } else {
            $hata = "Bu sertifikayı düzenleme yetkiniz yok!";
        }
    } catch (Exception $e) {
        $hata = "Sertifika güncellenirken hata oluştu: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Sertifika Düzenle' : 'Sertifika Oluştur'; ?> - Online Sertifika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            margin: -1px -1px 20px -1px;
        }
        .konular-container {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            background: #f8f9fa;
        }
        .konu-baslik {
            font-weight: bold;
            color: #495057;
            margin-top: 8px;
            margin-bottom: 4px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 2px;
        }
        .konu-item {
            font-size: 0.9em;
            color: #6c757d;
            margin-left: 15px;
            margin-bottom: 3px;
        }
        .spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
                    <li><a class="dropdown-item" href="eski_kayitlar.php">Eski Kayıtlar</a></li>
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
            <h2><i class="fas fa-<?php echo $edit_mode ? 'edit' : 'plus-circle'; ?>"></i> 
                <?php echo $edit_mode ? 'Sertifika Düzenle' : 'Yeni Sertifika Oluştur'; ?>
            </h2>
            <hr>
        </div>
    </div>

    <?php if ($hata): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $hata; ?>
        </div>
    <?php endif; ?>

    <?php if ($basari): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $basari; ?>
        </div>
    <?php endif; ?>

    <!-- Ana Sertifika Formu -->
    <div class="form-section border p-4">
        <div class="section-header">
            <h4><i class="fas fa-certificate"></i> Sertifika Bilgileri</h4>
        </div>

        <form method="POST" id="sertifikaForm">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="sertifika_id" value="<?php echo $edit_sertifika['id']; ?>">
            <?php endif; ?>
            
            <div class="row">
                <!-- Sol Kolon -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Eğitim Tarihi 1</label>
                        <input type="date" class="form-control" name="egitim_tarihi_1" 
                               value="<?php echo $edit_mode ? $edit_sertifika['egitim_tarihi_1'] : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Eğitim Tarihi 2 (İsteğe Bağlı)</label>
                        <input type="date" class="form-control" name="egitim_tarihi_2" 
                               value="<?php echo $edit_mode && $edit_sertifika['egitim_tarihi_2'] ? $edit_sertifika['egitim_tarihi_2'] : ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Geçerlilik Süresi (Yıl)</label>
                        <input type="number" class="form-control" name="gecerlilik_suresi_form" id="gecerlilikSuresi"
                               value="<?php echo $edit_mode && $edit_sertifika['gecerlilik_suresi'] ? $edit_sertifika['gecerlilik_suresi'] : ''; ?>" 
                               placeholder="Boş bırakılırsa sonsuz">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Eğitim Adı <span class="text-danger">*</span></label>
                        <select class="form-select" name="egitim_id" id="egitimSelect" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($egitimler as $egitim): ?>
                                <option value="<?php echo $egitim['id']; ?>" 
                                        data-sure="<?php echo $egitim['egitim_suresi']; ?>"
                                        data-gecerlilik="<?php echo $egitim['gecerlilik_suresi']; ?>"
                                        <?php echo $edit_mode && $edit_sertifika['egitim_id'] == $egitim['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($egitim['egitim_adi']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Katılımcı Ad Soyad</label>
                        <input type="text" class="form-control" name="katilimci_ad_soyad" 
                               value="<?php echo $edit_mode ? htmlspecialchars($edit_sertifika['katilimci_ad_soyad']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kurum Adı</label>
                        <input type="text" class="form-control" name="kurum_adi" 
                               value="<?php echo $edit_mode ? htmlspecialchars($edit_sertifika['kurum_adi']) : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Görevi</label>
                        <input type="text" class="form-control" name="gorevi" 
                               value="<?php echo $edit_mode ? htmlspecialchars($edit_sertifika['gorevi']) : ''; ?>" required>
                    </div>
                </div>

                <!-- Sağ Kolon -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Tehlike Sınıfı</label>
                        <select class="form-select" name="tehlike_sinifi" required>
                            <option value="">Seçiniz...</option>
                            <option value="Az Tehlikeli" <?php echo $edit_mode && $edit_sertifika['tehlike_sinifi'] == 'Az Tehlikeli' ? 'selected' : ''; ?>>Az Tehlikeli</option>
                            <option value="Tehlikeli" <?php echo $edit_mode && $edit_sertifika['tehlike_sinifi'] == 'Tehlikeli' ? 'selected' : ''; ?>>Tehlikeli</option>
                            <option value="Çok Tehlikeli" <?php echo $edit_mode && $edit_sertifika['tehlike_sinifi'] == 'Çok Tehlikeli' ? 'selected' : ''; ?>>Çok Tehlikeli</option>
                            <option value="Belirtilmemiş" <?php echo $edit_mode && $edit_sertifika['tehlike_sinifi'] == 'Belirtilmemiş' ? 'selected' : ''; ?>>Belirtilmemiş</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Eğitim Süresi (Saat)</label>
                        <input type="number" class="form-control" name="egitim_suresi_form" id="egitimSuresi" 
                               value="<?php echo $edit_mode ? $edit_sertifika['egitim_suresi'] : ''; ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Eğitim Şekli</label>
                        <select class="form-select" name="egitim_sekli" required>
                            <option value="">Seçiniz...</option>
                            <option value="Örgün Eğitim" <?php echo $edit_mode && $edit_sertifika['egitim_sekli'] == 'Örgün Eğitim' ? 'selected' : ''; ?>>Örgün Eğitim</option>
                            <option value="Uzaktan Eğitim" <?php echo $edit_mode && $edit_sertifika['egitim_sekli'] == 'Uzaktan Eğitim' ? 'selected' : ''; ?>>Uzaktan Eğitim</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Eğitimci 1 (İş Güvenliği Uzmanı)</label>
                        <select class="form-select" name="egitimci_1_id" required>
                            <option value="">Seçiniz...</option>
                            <?php foreach ($egitimciler as $egitimci): ?>
                                <option value="<?php echo $egitimci['id']; ?>"
                                        <?php echo $edit_mode && $edit_sertifika['egitimci_1_id'] == $egitimci['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($egitimci['ad_soyad']); ?> 
                                    (<?php echo htmlspecialchars($egitimci['unvan']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Eğitimci 2 (İşyeri Hekimi - İsteğe Bağlı)</label>
                        <select class="form-select" name="egitimci_2_id">
                            <option value="">Seçiniz...</option>
                            <?php foreach ($egitimciler as $egitimci): ?>
                                <option value="<?php echo $egitimci['id']; ?>"
                                        <?php echo $edit_mode && $edit_sertifika['egitimci_2_id'] == $egitimci['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($egitimci['ad_soyad']); ?> 
                                    (<?php echo htmlspecialchars($egitimci['unvan']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Eğitim Konuları Bölümü -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="card-title mb-0">
                                <i class="fas fa-list-check"></i> Eğitim Konuları
                            </h6>
                        </div>
                        <div class="card-body">
                            <div id="egitim-konulari-container">
                                <div class="text-muted text-center py-3">
                                    <i class="fas fa-info-circle"></i> Eğitim seçin, konuları görmek için
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-12">
                    <?php if ($edit_mode): ?>
                        <button type="submit" name="sertifika_guncelle" class="btn btn-warning btn-lg">
                            <i class="fas fa-save"></i> Değişiklikleri Kaydet
                        </button>
                        <a href="eski_kayitlar.php" class="btn btn-secondary btn-lg ms-2">
                            <i class="fas fa-times"></i> İptal
                        </a>
                    <?php else: ?>
                        <button type="submit" name="sertifika_olustur" class="btn btn-primary btn-lg">
                            <i class="fas fa-eye"></i> Önizleme ve Yazdır
                        </button>
                        <a href="eski_kayitlar.php" class="btn btn-outline-secondary btn-lg ms-2">
                            <i class="fas fa-history"></i> Eski Kayıtlar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ✅ TEK VE TEMİZ: Eğitim konularını getiren fonksiyon
function egitimKonulariGetir(egitimId) {
    if (!egitimId) {
        document.getElementById('egitim-konulari-container').innerHTML = 
            '<div class="text-muted text-center py-3"><i class="fas fa-info-circle"></i> Eğitim seçin, konuları görmek için</div>';
        return;
    }
    
    // Loading göster
    document.getElementById('egitim-konulari-container').innerHTML = 
        '<div class="text-center py-3"><i class="fas fa-spinner spin"></i> Eğitim konuları yükleniyor...</div>';
    
    // ✅ DÜZELTME: Doğru URL yolu
    fetch(`../admin/egitim_konulari_getir.php?egitim_id=${egitimId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let konularHTML = '';
                
                if (data.konular && data.konular.length > 0) {
                    konularHTML = '<div class="konular-container">';
                    
                    // Ana konulara göre grupla ve sıralı göster
                    const anaKonular = ['Genel Konular', 'Sağlık Konuları', 'Teknik Konular', 'Diğer Konular'];
                    
                    anaKonular.forEach(anaKonu => {
                        if (data.gruplu_konular[anaKonu]) {
                            konularHTML += `
                                <div class="konu-baslik">
                                    <i class="fas fa-folder text-primary"></i> ${anaKonu} (${data.gruplu_konular[anaKonu].length} konu)
                                </div>
                            `;
                            
                            data.gruplu_konular[anaKonu].forEach(altKonu => {
                                konularHTML += `<div class="konu-item"><i class="fas fa-check-circle text-success"></i> ${altKonu}</div>`;
                            });
                        }
                    });
                    
                    konularHTML += '</div>';
                } else {
                    konularHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Bu eğitime ait konu bulunamadı.</div>';
                }
                
                document.getElementById('egitim-konulari-container').innerHTML = konularHTML;
            } else {
                document.getElementById('egitim-konulari-container').innerHTML = 
                    `<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ${data.message}</div>`;
            }
        })
        .catch(error => {
            document.getElementById('egitim-konulari-container').innerHTML = 
                '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Konular yüklenirken hata oluştu.</div>';
            console.error('Error:', error);
        });
}

// Eğitim seçildiğinde bilgileri otomatik doldur ve konuları yükle
document.getElementById('egitimSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        // Eğitim süresi ve geçerlilik süresini otomatik doldur
        document.getElementById('egitimSuresi').value = selectedOption.getAttribute('data-sure');
        
        const gecerlilikInput = document.getElementById('gecerlilikSuresi');
        const gecerlilikValue = selectedOption.getAttribute('data-gecerlilik');
        if (gecerlilikValue && gecerlilikValue !== 'null') {
            gecerlilikInput.value = gecerlilikValue;
        } else {
            gecerlilikInput.value = '';
        }
        
        // Eğitim konularını yükle
        egitimKonulariGetir(selectedOption.value);
    } else {
        // Alanları temizle
        document.getElementById('egitimSuresi').value = '';
        document.getElementById('gecerlilikSuresi').value = '';
        egitimKonulariGetir('');
    }
});

// Sayfa yüklendiğinde seçili eğitim varsa konuları yükle
document.addEventListener('DOMContentLoaded', function() {
    const egitimSelect = document.getElementById('egitimSelect');
    if (egitimSelect.value) {
        egitimKonulariGetir(egitimSelect.value);
    }
});
</script>

</body>
</html>