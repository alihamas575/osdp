<?php include 'config/database.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Eğitim Sertifikası Sistemi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
        }
        .feature-card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>

<!-- Navigation -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-certificate"></i> Online Sertifika
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">Ana Sayfa</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="hakkimizda.php">Hakkımızda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="iletisim.php">İletişim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="bilgi_bankasi.php">Bilgi Bankası</a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if (isset($_SESSION['kullanici_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['ad_soyad']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <?php if ($_SESSION['rol'] == 'yonetici'): ?>
                                <li><a class="dropdown-item" href="admin/dashboard.php">Yönetici Paneli</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="user/sertifika_olustur.php">Sertifika Oluştur</a></li>
                            <li><a class="dropdown-item" href="user/eski_kayitlar.php">Eski Kayıtlar</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Çıkış Yap</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Giriş Yap
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Online Eğitim Sertifikası Sistemi</h1>
                <p class="lead mb-4">İş Sağlığı ve Güvenliği eğitim sertifikalarınızı kolayca oluşturun, yönetin ve yazdırın. Profesyonel, hızlı ve güvenilir çözüm.</p>
                <?php if (!isset($_SESSION['kullanici_id'])): ?>
                    <a href="login.php" class="btn btn-light btn-lg">
                        <i class="fas fa-rocket"></i> Hemen Başlayın
                    </a>
                <?php else: ?>
                    <a href="user/sertifika_olustur.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus"></i> Sertifika Oluştur
                    </a>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <img src="https://via.placeholder.com/500x400/ffffff/667eea?text=Sertifika+Örneği" 
                     alt="Sertifika Örneği" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="display-5 fw-bold">Neden Bizim Sistemimizi Seçmelisiniz?</h2>
                <p class="lead text-muted">İş Sağlığı ve Güvenliği eğitim sertifikalarınız için ihtiyacınız olan her şey burada.</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card feature-card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-lightning-bolt fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">Hızlı ve Kolay</h5>
                        <p class="card-text">Birkaç dakikada profesyonel sertifikalar oluşturun. Tek tek değil, toplu sertifika oluşturma imkanı.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Mevzuata Uygun</h5>
                        <p class="card-text">Türkiye'deki İSG mevzuatına tam uyumlu sertifika formatları. Az, Tehlikeli ve Çok Tehlikeli işyerleri için.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-users fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Çoklu Kullanıcı</h5>
                        <p class="card-text">Ekibinizle birlikte çalışın. Her kullanıcı kendi sertifikalarını yönetirken, yöneticiler tüm sistemi kontrol edebilir.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-database fa-3x text-warning mb-3"></i>
                        <h5 class="card-title">Kayıt Yönetimi</h5>
                        <p class="card-text">Tüm sertifika kayıtlarınız güvenli şekilde saklanır. İstediğiniz zaman arama, filtreleme ve tekrar yazdırma.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-file-excel fa-3x text-success mb-3"></i>
                        <h5 class="card-title">CSV Desteği</h5>
                        <p class="card-text">Excel dosyasından toplu sertifika oluşturma. Yüzlerce sertifikayı tek seferde hazırlayın.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card feature-card h-100 text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-print fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">PDF Çıktı</h5>
                        <p class="card-text">Yüksek kaliteli PDF formatında sertifikalar. Yazdırma öncesi önizleme ve düzenleme imkanı.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row text-center">
            <div class="col-lg-8 mx-auto">
                <h2 class="display-6 fw-bold mb-3">Şimdi Başlayın</h2>
                <p class="lead mb-4">İş Sağlığı ve Güvenliği eğitim sertifikalarınızı profesyonel şekilde yönetin.</p>
                <?php if (!isset($_SESSION['kullanici_id'])): ?>
                    <a href="login.php" class="btn btn-primary btn-lg me-3">
                        <i class="fas fa-sign-in-alt"></i> Giriş Yap
                    </a>
                    <a href="iletisim.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-phone"></i> İletişime Geçin
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="bg-dark text-white py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <p>&copy; 2025 Online Sertifika Sistemi. Tüm hakları saklıdır.</p>
            </div>
            <div class="col-md-6 text-end">
                <a href="hakkimizda.php" class="text-white me-3">Hakkımızda</a>
                <a href="iletisim.php" class="text-white me-3">İletişim</a>
                <a href="bilgi_bankasi.php" class="text-white">Bilgi Bankası</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>