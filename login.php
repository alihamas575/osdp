<?php 
// Error reporting ve debug modunu aç
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Debug log dizisi
$debug_log = [];
$debug_log[] = "🚀 Script başladı: " . date('Y-m-d H:i:s');

// Session temizleme butonu
if (isset($_GET['clear_session'])) {
    $debug_log[] = "🧹 Session temizleme isteği alındı";
    session_start();
    session_destroy();
    session_start();
    $debug_log[] = "✅ Session temizlendi ve yeniden başlatıldı";
    header("Location: login.php");
    exit();
}

// Session'ı başlat
try {
    session_start();
    $debug_log[] = "✅ Session başlatıldı. ID: " . session_id();
} catch (Exception $e) {
    $debug_log[] = "❌ Session başlatılamadı: " . $e->getMessage();
}

// Database bağlantısını test et
try {
    include 'config/database.php';
    $debug_log[] = "✅ Database.php dahil edildi";
    
    // Test sorgusu
    $test_query = $pdo->query("SELECT COUNT(*) as count FROM kullanicilar");
    $test_result = $test_query->fetch();
    $debug_log[] = "✅ Database bağlantısı OK. Kullanıcı sayısı: " . $test_result['count'];
} catch (Exception $e) {
    $debug_log[] = "❌ Database hatası: " . $e->getMessage();
}

$hata = '';
$basari = '';
$debug_info = '';

// DEBUG: Admin şifresini kontrol et
if (isset($_GET['debug_admin'])) {
    try {
        $debug_hash = md5('admin123');
        $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi = 'admin'");
        $stmt->execute();
        $admin = $stmt->fetch();
        
        if ($admin) {
            $debug_info = "
                <strong>Admin Debug Bilgisi:</strong><br>
                Veritabanındaki Hash: " . $admin['sifre'] . "<br>
                admin123 MD5 Hash: " . $debug_hash . "<br>
                Eşleşme: " . ($admin['sifre'] === $debug_hash ? 'EVET' : 'HAYIR') . "<br>
                Aktif: " . ($admin['aktif'] == 1 ? 'EVET' : 'HAYIR') . "<br>
                Rol: " . $admin['rol'] . "
            ";
            $debug_log[] = "✅ Admin debug bilgisi alındı";
        } else {
            $debug_info = "❌ Admin kullanıcısı bulunamadı!";
            $debug_log[] = "❌ Admin kullanıcısı bulunamadı";
        }
    } catch (Exception $e) {
        $debug_log[] = "❌ Admin debug hatası: " . $e->getMessage();
    }
}

// Güvenli veri fonksiyonu
if (!function_exists('guvenli_veri')) {
    function guvenli_veri($data) {
        return htmlspecialchars(strip_tags(trim($data)));
    }
    $debug_log[] = "✅ guvenli_veri fonksiyonu tanımlandı";
}

// Giriş işlemi - DETAYLI DEBUG
if (isset($_POST['giris'])) {
    $debug_log[] = "🔑 GİRİŞ İŞLEMİ BAŞLADI";
    
    try {
        // 1. Form verilerini al
        $kullanici_adi = isset($_POST['kullanici_adi']) ? guvenli_veri($_POST['kullanici_adi']) : '';
        $sifre = isset($_POST['sifre']) ? $_POST['sifre'] : '';
        
        $debug_log[] = "📝 Form verileri alındı - Kullanıcı: '$kullanici_adi', Şifre uzunluğu: " . strlen($sifre);
        
        // 2. Boş alan kontrolü
        if (empty($kullanici_adi) || empty($sifre)) {
            $hata = "Kullanıcı adı ve şifre boş olamaz!";
            $debug_log[] = "❌ Boş alan hatası";
        } else {
            $debug_log[] = "✅ Boş alan kontrolü geçti";
            
            // 3. Şifre hash'i hesapla
            $sifre_hash = md5($sifre);
            $debug_log[] = "🔐 Şifre hash'i hesaplandı: $sifre_hash";
            
            // 4. Database sorgusu hazırla
            $sql = "SELECT * FROM kullanicilar WHERE (kullanici_adi = ? OR email = ?) AND sifre = ? AND aktif = 1";
            $debug_log[] = "📄 SQL sorgusu: $sql";
            $debug_log[] = "📄 Parametreler: ['$kullanici_adi', '$kullanici_adi', '$sifre_hash']";
            
            $stmt = $pdo->prepare($sql);
            $debug_log[] = "✅ SQL sorgusu hazırlandı";
            
            // 5. Sorguyu çalıştır
            $stmt->execute([$kullanici_adi, $kullanici_adi, $sifre_hash]);
            $debug_log[] = "✅ SQL sorgusu çalıştırıldı";
            
            // 6. Sonuç kontrolü
            $row_count = $stmt->rowCount();
            $debug_log[] = "📊 Bulunan kayıt sayısı: $row_count";
            
            if ($row_count > 0) {
                $debug_log[] = "🎉 Kullanıcı bulundu!";
                
                // 7. Kullanıcı bilgilerini al
                $kullanici = $stmt->fetch();
                $debug_log[] = "📋 Kullanıcı bilgileri alındı: ID=" . $kullanici['id'] . ", Rol=" . $kullanici['rol'];
                
                // 8. Session'ı temizle
                session_destroy();
                session_start();
                $debug_log[] = "🔄 Session temizlendi ve yeniden başlatıldı";
                
                // 9. Session değişkenlerini ayarla
                $_SESSION['kullanici_id'] = $kullanici['id'];
                $_SESSION['kullanici_adi'] = $kullanici['kullanici_adi'];
                $_SESSION['ad_soyad'] = $kullanici['ad_soyad'];
                $_SESSION['rol'] = $kullanici['rol'];
                $debug_log[] = "✅ Session değişkenleri ayarlandı";
                
                // 10. Session'ın set edildiğini kontrol et
                if (isset($_SESSION['kullanici_id'])) {
                    $debug_log[] = "✅ Session kontrol edildi - kullanici_id: " . $_SESSION['kullanici_id'];
                } else {
                    $debug_log[] = "❌ Session set edilmedi!";
                }
                
                // 11. Yönlendirme URL'ini belirle
                $redirect_url = ($kullanici['rol'] == 'yonetici') ? 'admin/dashboard.php' : 'user/sertifika_olustur.php';
                $debug_log[] = "🎯 Yönlendirme URL'i: $redirect_url";
                
                // 12. Dosya varlığını kontrol et
                if (file_exists($redirect_url)) {
                    $debug_log[] = "✅ Hedef dosya mevcut: $redirect_url";
                } else {
                    $debug_log[] = "❌ Hedef dosya bulunamadı: $redirect_url";
                }
                
                // 13. Yönlendirme yap
                $debug_log[] = "🚀 Yönlendirme yapılıyor...";
                
                // JavaScript ve PHP header ile çift yönlendirme
                echo "<script>
                    console.log('Debug: Yönlendirme başlıyor...');
                    alert('Giriş başarılı! Debug log:" . addslashes(implode("\\n", array_slice($debug_log, -5))) . "');
                    window.location.href = '$redirect_url';
                </script>";
                
                header("Location: $redirect_url");
                exit();
                
            } else {
                $debug_log[] = "❌ Kullanıcı bulunamadı";
                
                // Debug için manuel kontrol
                $manual_check = $pdo->prepare("SELECT kullanici_adi, email, sifre, aktif FROM kullanicilar WHERE kullanici_adi = ? OR email = ?");
                $manual_check->execute([$kullanici_adi, $kullanici_adi]);
                $manual_result = $manual_check->fetch();
                
                if ($manual_result) {
                    $debug_log[] = "🔍 Manuel kontrol - Kullanıcı mevcut:";
                    $debug_log[] = "   - Kullanıcı adı: " . $manual_result['kullanici_adi'];
                    $debug_log[] = "   - Email: " . $manual_result['email'];
                    $debug_log[] = "   - DB Şifre: " . $manual_result['sifre'];
                    $debug_log[] = "   - Girilen Şifre Hash: " . $sifre_hash;
                    $debug_log[] = "   - Şifre Eşleşme: " . ($manual_result['sifre'] === $sifre_hash ? 'EVET' : 'HAYIR');
                    $debug_log[] = "   - Aktif: " . ($manual_result['aktif'] == 1 ? 'EVET' : 'HAYIR');
                } else {
                    $debug_log[] = "❌ Manuel kontrol - Kullanıcı hiç bulunamadı";
                }
                
                $hata = "Kullanıcı adı/email veya şifre hatalı!";
            }
        }
    } catch (Exception $e) {
        $debug_log[] = "💥 HATA: " . $e->getMessage();
        $debug_log[] = "📍 Dosya: " . $e->getFile() . " Satır: " . $e->getLine();
        $hata = "Giriş sırasında hata oluştu: " . $e->getMessage();
    }
}

// Kayıt işlemi (kısaltılmış)
if (isset($_POST['kayit'])) {
    $debug_log[] = "📝 Kayıt işlemi başladı";
    // ... (kayıt kodları aynı kalabilir)
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Online Sertifika (Debug Mode)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            background: transparent;
            border-bottom: 2px solid #667eea;
            color: #667eea;
            font-weight: 600;
        }
        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            border: none;
        }
        .btn-success:hover {
            background: linear-gradient(135deg, #218838 0%, #1e9c81 100%);
        }
        .debug-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 0.85em;
            font-family: monospace;
        }
        .debug-log {
            background: #1e1e1e;
            color: #00ff00;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
            font-size: 0.8em;
            font-family: 'Courier New', monospace;
            max-height: 300px;
            overflow-y: auto;
            white-space: pre-wrap;
        }
        .session-info {
            background: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
            font-size: 0.85em;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7">
                <div class="login-card p-4">
                    <div class="text-center mb-4">
                        <h2><i class="fas fa-certificate text-primary"></i> Online Sertifika</h2>
                        <p class="text-muted">DEBUG MODE - Her aşama izleniyor</p>
                        
                        <!-- Debug Butonları -->
                        <div class="mb-2">
                            <a href="?debug_admin=1" class="btn btn-sm btn-outline-info">Admin Debug</a>
                            <a href="?clear_session=1" class="btn btn-sm btn-outline-danger">Session Temizle</a>
                            <button class="btn btn-sm btn-outline-secondary" onclick="toggleDebugLog()">Debug Log Göster/Gizle</button>
                        </div>
                    </div>

                    <!-- Debug Log -->
                    <div class="debug-log" id="debugLog" style="display: none;">
                        <strong>🔍 DEBUG LOG:</strong><br>
                        <?php echo implode("\n", $debug_log); ?>
                    </div>

                    <!-- Mevcut Session Bilgisi -->
                    <?php if (!empty($_SESSION)): ?>
                        <div class="session-info">
                            <strong>⚠️ Aktif Session Var:</strong><br>
                            <?php foreach ($_SESSION as $key => $value): ?>
                                <?php echo $key; ?>: <?php echo htmlspecialchars($value); ?><br>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Debug Bilgisi -->
                    <?php if ($debug_info): ?>
                        <div class="debug-info">
                            <?php echo $debug_info; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($hata): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $hata; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($basari): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo $basari; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Tabs -->
                    <ul class="nav nav-tabs mb-4" id="loginTabs" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link active w-100" id="giris-tab" data-bs-toggle="tab" data-bs-target="#giris" type="button">
                                <i class="fas fa-sign-in-alt"></i> Giriş Yap
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100" id="kayit-tab" data-bs-toggle="tab" data-bs-target="#kayit" type="button">
                                <i class="fas fa-user-plus"></i> Kayıt Ol
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="loginTabContent">
                        <!-- Giriş Formu -->
                        <div class="tab-pane fade show active" id="giris" role="tabpanel">
                            <form method="POST" onsubmit="showDebugBeforeSubmit()">
                                <div class="mb-3">
                                    <label for="kullanici_adi_giris" class="form-label">Kullanıcı Adı veya Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                        <input type="text" class="form-control" id="kullanici_adi_giris" name="kullanici_adi" value="admin" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="sifre_giris" class="form-label">Şifre</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="sifre_giris" name="sifre" value="admin123" required>
                                    </div>
                                </div>
                                <button type="submit" name="giris" class="btn btn-primary w-100">
                                    <i class="fas fa-sign-in-alt"></i> Giriş Yap (Debug Mode)
                                </button>
                            </form>
                            
                            <!-- Test Bilgileri -->
                            <div class="debug-info mt-3">
                                <strong>Test Bilgileri:</strong><br>
                                Admin: admin / admin123<br>
                                Current Time: <?php echo date('Y-m-d H:i:s'); ?><br>
                                PHP Version: <?php echo PHP_VERSION; ?><br>
                                Session Status: <?php echo session_status(); ?>
                            </div>
                        </div>

                        <!-- Kayıt Formu (basitleştirilmiş) -->
                        <div class="tab-pane fade" id="kayit" role="tabpanel">
                            <p class="text-center">Kayıt özelliği debug modunda devre dışı</p>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <a href="index.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left"></i> Ana Sayfaya Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleDebugLog() {
    const debugLog = document.getElementById('debugLog');
    debugLog.style.display = debugLog.style.display === 'none' ? 'block' : 'none';
}

function showDebugBeforeSubmit() {
    document.getElementById('debugLog').style.display = 'block';
    console.log('Form gönderiliyor...');
}

// Sayfa yüklendiğinde debug log'ı göster
document.addEventListener('DOMContentLoaded', function() {
    toggleDebugLog();
});
</script>
</body>
</html>