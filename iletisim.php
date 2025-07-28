<?php include 'config/database.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim - Online Sertifika</title>
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
                    <a class="nav-link" href="hakkimizda.php">Hakkımızda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="iletisim.php">İletişim</a>
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
                <h1 class="display-4 fw-bold">İletişim</h1>
                <p class="lead">Sorularınız için bizimle iletişime geçin.</p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- İletişim Bilgileri -->
            <div class="col-lg-6 mb-5">
                <h2 class="mb-4">İletişim Bilgileri</h2>
                
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-user-tie text-primary me-2"></i>
                            İş Güvenliği Uzmanı
                        </h5>
                        <h4 class="text-primary">Halim ASA</h4>
                        <p class="card-text">A Sınıfı İş Güvenliği Uzmanı<br>
                        Sertifika No: 31240<br>
                        İSG Danışmanlık Hizmetleri</p>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Email</h5>
                                <p class="card-text">
                                    <a href="mailto:halim@bursaisg.com" class="text-decoration-none">
                                        halim@bursaisg.com
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-phone fa-3x text-success mb-3"></i>
                                <h5 class="card-title">Telefon</h5>
                                <p class="card-text">
                                    <a href="tel:+905512545638" class="text-decoration-none">
                                        +90 551 254 56 38
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-12">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-globe fa-3x text-info mb-3"></i>
                                <h5 class="card-title">Website</h5>
                                <p class="card-text">
                                    <a href="https://www.bursaisg.com" target="_blank" class="text-decoration-none">
                                        www.bursaisg.com
                                    </a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h4>Çalışma Saatleri</h4>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-clock text-primary me-2"></i> Pazartesi - Cuma: 09:00 - 18:00</li>
                        <li><i class="fas fa-clock text-primary me-2"></i> Cumartesi: 09:00 - 13:00</li>
                        <li><i class="fas fa-clock text-primary me-2"></i> Pazar: Kapalı</li>
                    </ul>
                </div>
            </div>

            <!-- İletişim Formu -->
            <div class="col-lg-6">
                <h2 class="mb-4">Bize Yazın</h2>
                
                <form>
                    <div class="mb-3">
                        <label for="ad_soyad" class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" id="ad_soyad" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="telefon" class="form-label">Telefon</label>
                        <input type="tel" class="form-control" id="telefon">
                    </div>
                    
                    <div class="mb-3">
                        <label for="konu" class="form-label">Konu</label>
                        <select class="form-select" id="konu" required>
                            <option value="">Seçiniz...</option>
                            <option value="genel">Genel Bilgi</option>
                            <option value="teknik">Teknik Destek</option>
                            <option value="egitim">Eğitim Danışmanlığı</option>
                            <option value="sertifika">Sertifika Sorunu</option>
                            <option value="isbirligi">İş Birliği</option>
                            <option value="diger">Diğer</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="mesaj" class="form-label">Mesajınız</label>
                        <textarea class="form-control" id="mesaj" rows="5" required></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-paper-plane"></i> Mesaj Gönder
                    </button>
                </form>
                
                <div class="mt-4">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Bilgi</h6>
                        <p class="mb-0">Mesajınız email olarak tarafımıza iletilecektir. En kısa sürede size dönüş yapacağız.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="bg-light py-5">
    <div class="container">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="display-6 fw-bold">Hizmetlerimiz</h2>
                <p class="lead">İş Sağlığı ve Güvenliği alanında sunduğumuz hizmetler</p>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-hard-hat fa-3x text-primary mb-3"></i>
                        <h5 class="card-title">İSG Danışmanlığı</h5>
                        <p class="card-text">İş sağlığı ve güvenliği konularında uzman danışmanlık hizmetleri.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-chalkboard-teacher fa-3x text-success mb-3"></i>
                        <h5 class="card-title">Eğitim Hizmetleri</h5>
                        <p class="card-text">İSG eğitimleri, sertifikasyon ve uzaktan eğitim programları.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100 text-center">
                    <div class="card-body">
                        <i class="fas fa-clipboard-check fa-3x text-info mb-3"></i>
                        <h5 class="card-title">Risk Değerlendirmesi</h5>
                        <p class="card-text">İşyeri risk analizleri ve güvenlik değerlendirme raporları.</p>
                    </div>
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
<script>
// Form gönderim işlemi
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const adSoyad = document.getElementById('ad_soyad').value;
    const email = document.getElementById('email').value;
    const telefon = document.getElementById('telefon').value;
    const konu = document.getElementById('konu').value;
    const mesaj = document.getElementById('mesaj').value;
    
    // Email oluştur
    const emailBody = `Ad Soyad: ${adSoyad}%0D%0AEmail: ${email}%0D%0ATelefon: ${telefon}%0D%0AKonu: ${konu}%0D%0A%0D%0AMesaj:%0D%0A${mesaj}`;
    
    // Email uygulamasını aç
    window.location.href = `mailto:halim@bursaisg.com?subject=Online Sertifika - ${konu}&body=${emailBody}`;
    
    // Formu temizle
    this.reset();
    
    // Başarı mesajı
    alert('Email uygulamanız açıldı. Mesajınızı göndermek için email uygulamanızdan gönder butonuna tıklayın.');
});
</script>

</body>
</html>