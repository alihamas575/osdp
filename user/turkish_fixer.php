<?php
session_start();
include '../config/database.php';

$test_results = [];
$fix_results = [];

// Manuel düzeltme formu
if (isset($_POST['manual_fix'])) {
    $table = $_POST['table'];
    $column = $_POST['column'];
    $id = intval($_POST['id']);
    $new_value = $_POST['new_value'];
    
    try {
        $stmt = $pdo->prepare("UPDATE $table SET $column = ? WHERE id = ?");
        $stmt->execute([$new_value, $id]);
        $fix_results[] = "✅ $table tablosunda ID:$id kaydı güncellendi";
    } catch (Exception $e) {
        $fix_results[] = "❌ Hata: " . $e->getMessage();
    }
}

// Test formu gönderildiğinde
if (isset($_POST['test_turkish'])) {
    $test_text = $_POST['test_text'];
    
    try {
        // Test verisi ekle
        $stmt = $pdo->prepare("INSERT INTO egitimler (egitim_adi, egitim_suresi, kullanici_id) VALUES (?, 16, 1)");
        $stmt->execute([$test_text]);
        $test_id = $pdo->lastInsertId();
        
        // Geri oku
        $stmt = $pdo->prepare("SELECT egitim_adi FROM egitimler WHERE id = ?");
        $stmt->execute([$test_id]);
        $result = $stmt->fetch();
        
        $test_results[] = "✅ Gönderilen: " . $test_text;
        $test_results[] = "📤 Veritabanından okunan: " . $result['egitim_adi'];
        $test_results[] = "🔍 Eşleşme: " . ($test_text === $result['egitim_adi'] ? 'EVET' : 'HAYIR');
        
        // Karakter analizi
        $test_results[] = "📊 Gönderilen karakter analizi: " . mb_detect_encoding($test_text);
        $test_results[] = "📊 Okunan karakter analizi: " . mb_detect_encoding($result['egitim_adi']);
        
        // Test verisini sil
        $pdo->prepare("DELETE FROM egitimler WHERE id = ?")->execute([$test_id]);
        
    } catch (Exception $e) {
        $test_results[] = "❌ Hata: " . $e->getMessage();
    }
}

// Kapsamlı otomatik düzeltme
if (isset($_POST['comprehensive_fix'])) {
    try {
        $fixed_count = 0;
        
        // 1. Karakterleri UTF-8 Binary'den düzelt
        $conversion_map = [
            // Yaygın bozuk karakter çiftleri
            'Ã§' => 'ç', 'Ã¦' => 'æ', 'Ã¼' => 'ü', 'Ã¶' => 'ö', 'Ã¤' => 'ä',
            'Ã±' => 'ñ', 'Ã©' => 'é', 'Ã¨' => 'è', 'Ã¡' => 'á', 'Ã ' => 'à',
            'Ã­' => 'í', 'Ã¬' => 'ì', 'Ã³' => 'ó', 'Ã²' => 'ò', 'Ãº' => 'ú',
            'Ã¹' => 'ù', 'ÄŸ' => 'ğ', 'Ä±' => 'ı', 'Å?' => 'ş', 'Ä°' => 'İ',
            'Ã‡' => 'Ç', 'Ãœ' => 'Ü', 'Ã–' => 'Ö', 'ÄŸ' => 'ğ', 'Å??' => 'ş',
            
            // ? karakteri kombinasyonları 
            '??' => 'ı', '?ğ' => 'ğ', '?ş' => 'ş', '?ç' => 'ç', '?ü' => 'ü', '?ö' => 'ö',
            '???' => 'İşç', '??G' => 'İSG', '??i' => 'İşi', '??l' => 'İşl', '??e' => 'İşe',
            'Dan??man' => 'Danışman', '??retmen' => 'Öğretmen', 'M??hendis' => 'Mühendis',
            'G??venlik' => 'Güvenlik', '??irket' => 'Şirket', 'T??rk' => 'Türk',
            'E??itim' => 'Eğitim', 'Sa??l??k' => 'Sağlık', '??al????an' => 'Çalışan',
            'Uzmanl????k' => 'Uzmanlık', 'Sorumlu????u' => 'Sorumluğu', 'G??venli????i' => 'Güvenliği',
            '??yeri' => 'İşyeri', 'Hekimi' => 'Hekimi', 'TEMEL' => 'TEMEL',
            
            // Tek karakter düzeltmeleri
            '?' => 'ş', '?' => 'ğ', '?' => 'ü', '?' => 'ö', '?' => 'ç', '?' => 'ı'
        ];
        
        // Eğitimler tablosunu düzelt
        $stmt = $pdo->query("SELECT id, egitim_adi FROM egitimler");
        while ($row = $stmt->fetch()) {
            $original = $row['egitim_adi'];
            $fixed = $original;
            
            foreach ($conversion_map as $wrong => $correct) {
                $fixed = str_replace($wrong, $correct, $fixed);
            }
            
            if ($original !== $fixed) {
                $update_stmt = $pdo->prepare("UPDATE egitimler SET egitim_adi = ? WHERE id = ?");
                $update_stmt->execute([$fixed, $row['id']]);
                $fixed_count++;
                $fix_results[] = "🔧 Eğitim ID:" . $row['id'] . " → '$original' → '$fixed'";
            }
        }
        
        // Sertifikalar tablosunu düzelt
        $stmt = $pdo->query("SELECT id, katilimci_ad_soyad, kurum_adi, gorevi FROM sertifikalar");
        while ($row = $stmt->fetch()) {
            $fields = ['katilimci_ad_soyad', 'kurum_adi', 'gorevi'];
            
            foreach ($fields as $field) {
                $original = $row[$field];
                $fixed = $original;
                
                foreach ($conversion_map as $wrong => $correct) {
                    $fixed = str_replace($wrong, $correct, $fixed);
                }
                
                if ($original !== $fixed) {
                    $update_stmt = $pdo->prepare("UPDATE sertifikalar SET $field = ? WHERE id = ?");
                    $update_stmt->execute([$fixed, $row['id']]);
                    $fixed_count++;
                    $fix_results[] = "🔧 Sertifika ID:" . $row['id'] . " $field → '$original' → '$fixed'";
                }
            }
        }
        
        // Eğitimciler tablosunu düzelt
        $stmt = $pdo->query("SELECT id, ad_soyad, unvan FROM egitimciler");
        while ($row = $stmt->fetch()) {
            $fields = ['ad_soyad', 'unvan'];
            
            foreach ($fields as $field) {
                $original = $row[$field];
                $fixed = $original;
                
                foreach ($conversion_map as $wrong => $correct) {
                    $fixed = str_replace($wrong, $correct, $fixed);
                }
                
                if ($original !== $fixed) {
                    $update_stmt = $pdo->prepare("UPDATE egitimciler SET $field = ? WHERE id = ?");
                    $update_stmt->execute([$fixed, $row['id']]);
                    $fixed_count++;
                    $fix_results[] = "🔧 Eğitimci ID:" . $row['id'] . " $field → '$original' → '$fixed'";
                }
            }
        }
        
        $fix_results[] = "✅ Toplamda $fixed_count alan düzeltildi";
        
    } catch (Exception $e) {
        $fix_results[] = "❌ Düzeltme hatası: " . $e->getMessage();
    }
}

// Mevcut sorunlu verileri listele
$problematic_data = [];
try {
    // Sorunlu eğitimler
    $stmt = $pdo->query("SELECT id, egitim_adi FROM egitimler WHERE egitim_adi REGEXP '[^a-zA-Z0-9ğĞüÜşŞıİöÖçÇ [:space:](),-.]' OR egitim_adi LIKE '%?%' LIMIT 5");
    $problematic_egitimler = $stmt->fetchAll();
    
    // Sorunlu sertifikalar
    $stmt = $pdo->query("SELECT id, katilimci_ad_soyad, kurum_adi, gorevi FROM sertifikalar WHERE katilimci_ad_soyad LIKE '%?%' OR kurum_adi LIKE '%?%' OR gorevi LIKE '%?%' OR katilimci_ad_soyad REGEXP '[^a-zA-Z0-9ğĞüÜşŞıİöÖçÇ [:space:](),-.]' LIMIT 5");
    $problematic_sertifikalar = $stmt->fetchAll();
    
    // Sorunlu eğitimciler
    $stmt = $pdo->query("SELECT id, ad_soyad, unvan FROM egitimciler WHERE ad_soyad LIKE '%?%' OR unvan LIKE '%?%' OR ad_soyad REGEXP '[^a-zA-Z0-9ğĞüÜşŞıİöÖçÇ [:space:](),-.]' LIMIT 5");
    $problematic_egitimciler = $stmt->fetchAll();
    
} catch (Exception $e) {
    $problematic_data[] = "❌ Veri okuma hatası: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gelişmiş Türkçe Karakter Düzeltme</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        .test-result {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            white-space: pre-wrap;
        }
        .problematic-item {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 8px;
            margin: 5px 0;
        }
        .manual-fix {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 5px;
            padding: 10px;
            margin: 5px 0;
        }
    </style>
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-certificate"></i> Gelişmiş Türkçe Düzeltme
        </a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="sertifika_olustur.php">
                <i class="fas fa-arrow-left"></i> Geri Dön
            </a>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <h2><i class="fas fa-language"></i> Gelişmiş Türkçe Karakter Düzeltme</h2>
    <hr>

    <!-- Test Bölümü -->
    <div class="test-section">
        <h4><i class="fas fa-vial"></i> Database Charset Testi</h4>
        
        <form method="POST">
            <div class="row">
                <div class="col-md-8">
                    <input type="text" class="form-control" name="test_text" 
                           value="İş Güvenliği Uzmanı Danışmanlık Şirketi Öğretimi" 
                           placeholder="Türkçe karakterli test metni girin">
                </div>
                <div class="col-md-4">
                    <button type="submit" name="test_turkish" class="btn btn-primary w-100">
                        <i class="fas fa-play"></i> Charset Test Et
                    </button>
                </div>
            </div>
        </form>

        <?php if (!empty($test_results)): ?>
            <div class="test-result">
                <?php echo implode("\n", $test_results); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Sorunlu Veriler ve Manuel Düzeltme -->
    <div class="test-section">
        <h4><i class="fas fa-exclamation-triangle text-warning"></i> Sorunlu Veriler ve Manuel Düzeltme</h4>
        
        <?php if (!empty($problematic_egitimler)): ?>
            <h6>Sorunlu Eğitimler:</h6>
            <?php foreach ($problematic_egitimler as $egitim): ?>
                <div class="manual-fix">
                    <strong>ID: <?php echo $egitim['id']; ?></strong><br>
                    Mevcut: "<?php echo htmlspecialchars($egitim['egitim_adi']); ?>"<br>
                    <form method="POST" class="mt-2">
                        <input type="hidden" name="table" value="egitimler">
                        <input type="hidden" name="column" value="egitim_adi">
                        <input type="hidden" name="id" value="<?php echo $egitim['id']; ?>">
                        <div class="input-group">
                            <input type="text" class="form-control" name="new_value" value="<?php echo htmlspecialchars($egitim['egitim_adi']); ?>">
                            <button type="submit" name="manual_fix" class="btn btn-success btn-sm">Düzelt</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($problematic_sertifikalar)): ?>
            <h6>Sorunlu Sertifikalar:</h6>
            <?php foreach ($problematic_sertifikalar as $sert): ?>
                <div class="manual-fix">
                    <strong>Sertifika ID: <?php echo $sert['id']; ?></strong><br>
                    
                    <?php if (preg_match('/[^a-zA-Z0-9ğĞüÜşŞıİöÖçÇ [:space:](),-.]/', $sert['katilimci_ad_soyad']) || strpos($sert['katilimci_ad_soyad'], '?') !== false): ?>
                        Katılımcı: "<?php echo htmlspecialchars($sert['katilimci_ad_soyad']); ?>"<br>
                        <form method="POST" class="mt-1">
                            <input type="hidden" name="table" value="sertifikalar">
                            <input type="hidden" name="column" value="katilimci_ad_soyad">
                            <input type="hidden" name="id" value="<?php echo $sert['id']; ?>">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="new_value" value="<?php echo htmlspecialchars($sert['katilimci_ad_soyad']); ?>">
                                <button type="submit" name="manual_fix" class="btn btn-success btn-sm">Düzelt</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <?php if (preg_match('/[^a-zA-Z0-9ğĞüÜşŞıİöÖçÇ [:space:](),-.]/', $sert['kurum_adi']) || strpos($sert['kurum_adi'], '?') !== false): ?>
                        Kurum: "<?php echo htmlspecialchars($sert['kurum_adi']); ?>"<br>
                        <form method="POST" class="mt-1">
                            <input type="hidden" name="table" value="sertifikalar">
                            <input type="hidden" name="column" value="kurum_adi">
                            <input type="hidden" name="id" value="<?php echo $sert['id']; ?>">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="new_value" value="<?php echo htmlspecialchars($sert['kurum_adi']); ?>">
                                <button type="submit" name="manual_fix" class="btn btn-success btn-sm">Düzelt</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <?php if (preg_match('/[^a-zA-Z0-9ğĞüÜşŞıİöÖçÇ [:space:](),-.]/', $sert['gorevi']) || strpos($sert['gorevi'], '?') !== false): ?>
                        Görev: "<?php echo htmlspecialchars($sert['gorevi']); ?>"<br>
                        <form method="POST" class="mt-1">
                            <input type="hidden" name="table" value="sertifikalar">
                            <input type="hidden" name="column" value="gorevi">
                            <input type="hidden" name="id" value="<?php echo $sert['id']; ?>">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="new_value" value="<?php echo htmlspecialchars($sert['gorevi']); ?>">
                                <button type="submit" name="manual_fix" class="btn btn-success btn-sm">Düzelt</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($problematic_egitimciler)): ?>
            <h6>Sorunlu Eğitimciler:</h6>
            <?php foreach ($problematic_egitimciler as $egitimci): ?>
                <div class="manual-fix">
                    <strong>Eğitimci ID: <?php echo $egitimci['id']; ?></strong><br>
                    
                    <?php if (preg_match('/[^a-zA-Z0-9ğĞüÜşŞıİöÖçÇ [:space:](),-.]/', $egitimci['ad_soyad']) || strpos($egitimci['ad_soyad'], '?') !== false): ?>
                        Ad Soyad: "<?php echo htmlspecialchars($egitimci['ad_soyad']); ?>"<br>
                        <form method="POST" class="mt-1">
                            <input type="hidden" name="table" value="egitimciler">
                            <input type="hidden" name="column" value="ad_soyad">
                            <input type="hidden" name="id" value="<?php echo $egitimci['id']; ?>">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="new_value" value="<?php echo htmlspecialchars($egitimci['ad_soyad']); ?>">
                                <button type="submit" name="manual_fix" class="btn btn-success btn-sm">Düzelt</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <?php if (preg_match('/[^a-zA-Z0-9ğĞüÜşŞıİöÖçÇ [:space:](),-.]/', $egitimci['unvan']) || strpos($egitimci['unvan'], '?') !== false): ?>
                        Ünvan: "<?php echo htmlspecialchars($egitimci['unvan']); ?>"<br>
                        <form method="POST" class="mt-1">
                            <input type="hidden" name="table" value="egitimciler">
                            <input type="hidden" name="column" value="unvan">
                            <input type="hidden" name="id" value="<?php echo $egitimci['id']; ?>">
                            <div class="input-group input-group-sm">
                                <input type="text" class="form-control" name="new_value" value="<?php echo htmlspecialchars($egitimci['unvan']); ?>">
                                <button type="submit" name="manual_fix" class="btn btn-success btn-sm">Düzelt</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <?php if (empty($problematic_egitimler) && empty($problematic_sertifikalar) && empty($problematic_egitimciler)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Sorunlu veri bulunamadı! Tüm veriler düzgün görünüyor.
            </div>
        <?php endif; ?>
    </div>

    <!-- Otomatik Düzeltme -->
    <div class="test-section">
        <h4><i class="fas fa-magic text-danger"></i> Kapsamlı Otomatik Düzeltme</h4>
        <p class="text-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>DİKKAT:</strong> Bu işlem tüm bilinen bozuk karakter kombinasyonlarını düzeltecek!
        </p>
        
        <form method="POST" onsubmit="return confirm('Kapsamlı otomatik düzeltme yapmak istediğinizden emin misiniz?')">
            <button type="submit" name="comprehensive_fix" class="btn btn-danger">
                <i class="fas fa-magic"></i> Kapsamlı Otomatik Düzeltme Yap
            </button>
        </form>

        <?php if (!empty($fix_results)): ?>
            <div class="test-result mt-3" style="max-height: 400px; overflow-y: auto;">
                <?php echo implode("\n", $fix_results); ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>