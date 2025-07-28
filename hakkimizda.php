<?php include 'config/database.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hakkımızda - Online Sertifika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                    <a class="nav-link" href="index.php">Ana Sayfa</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="hakkimizda.php">Hakkımızda</a>
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
<section class="bg-primary text-white py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold">Hakkımızda</h1>
                <p class="lead">İş Sağlığı ve Güvenliği alanında profesyonel eğitim sertifikası çözümleri sunuyoruz.</p>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="mb-4">Kimiz Biz?</h2>
                <p>Online Sertifika Sistemi, İş Sağlığı ve Güvenliği alanında faaliyet gösteren uzmanların, eğitim sertifikalarını kolayca oluşturabilmesi, yönetebilmesi ve arşivleyebilmesi için geliştirilmiş modern bir web platformudur.</p>
                
                <h3 class="mt-5 mb-3">Misyonumuz</h3>
                <p>İş Sağlığı ve Güvenliği eğitimlerinin belgelendirilmesi sürecini dijitalleştirerek, eğitim uzmanlarının işlerini kolaylaştırmak ve eğitim kalitesini artırmaktır.</p>
                
                <h3 class="mt-5 mb-3">Vizyonumuz</h3>
                <p>Türkiye'nin en çok tercih edilen İSG eğitim sertifikası yönetim platformu olmak ve bu alanda teknolojik standartları belirlemek.</p>
                
                <h3 class="mt-5 mb-3">Neler Sunuyoruz?</h3>
                <div class="row g-4 mt-3">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-certificate text-primary"></i> Sertifika Oluşturma</h5>
                                <p class="card-text">Mevzuata uygun, profesyonel görünümlü sertifikalar oluşturun.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-users text-success"></i> Çoklu Kullanıcı</h5>
                                <p class="card-text">Ekibinizle birlikte çalışın, kullanıcı yetkilerini yönetin.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-file-excel text-info"></i> Toplu İşlem</h5>
                                <p class="card-text">CSV dosyalarıyla yüzlerce sertifikayı tek seferde oluşturun.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-database text-warning"></i> Arşiv Yönetimi</h5>
                                <p class="card-text">Tüm sertifikalarınızı güvenle saklayın ve kolayca erişin.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h3 class="mt-5 mb-3">Teknik Özellikler</h3>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Web tabanlı, her yerden erişim</li>
                    <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Güvenli veritabanı altyapısı</li>
                    <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> PDF çıktı ve yazdırma desteği</li>
                    <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Mobil uyumlu responsive tasarım</li>
                    <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Kullanıcı dostu arayüz</li>
                    <li class="list-group-item"><i class="fas fa-check text-success me-2"></i> Düzenli güncellemeler ve destek</li>
                </ul>
                
                <h3 class="mt-5 mb-3">Yasal Uyumluluk</h3>
                <p>Sistemimiz, aşağıdaki mevzuatlara tam uyumlu olarak tasarlanmıştır:</p>
                <ul>
                    <li>İş Sağlığı ve Güvenliği Kanunu (6331 sayılı)</li>
                    <li>Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik</li>
                    <li>İş Sağlığı ve Güvenliği Risk Değerlendirmesi Yönetmeliği</li>
                    <li>Kişisel Koruyucu Donanım Yönetmeliği</li>
                </ul>
                
                <div class="alert alert-info mt-4">
                    <h5><i class="fas fa-info-circle"></i> Not</h5>
                    <p class="mb-0">Bu platform, İş Sağlığı ve Güvenliği Uzmanı Halim ASA tarafından geliştirilmiş olup, İSG eğitimlerinin belgelendirilmesi amacıyla kullanılmaktadır.</p>
                </div>
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