<?php include 'config/database.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bilgi Bankası - Online Sertifika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .accordion-button:not(.collapsed) {
            background-color: #667eea;
            color: white;
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
                    <a class="nav-link" href="index.php">Ana Sayfa</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="hakkimizda.php">Hakkımızda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="iletisim.php">İletişim</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="bilgi_bankasi.php">Bilgi Bankası</a>
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
                <h1 class="display-4 fw-bold">Bilgi Bankası</h1>
                <p class="lead">Sistemin kullanımı hakkında detaylı bilgi ve rehberler</p>
            </div>
        </div>
    </div>
</section>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        
        <!-- Hızlı Başlangıç -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="text-center mb-4">Hızlı Başlangıç Rehberi</h2>
                <div class="row g-4">
                    <div class="col-md-3">
                        <div class="card feature-card h-100 text-center p-3">
                            <div class="card-body">
                                <i class="fas fa-user-plus fa-3x text-primary mb-3"></i>
                                <h5>1. Kayıt Olun</h5>
                                <p class="small">Giriş sayfasından hesap oluşturun</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card feature-card h-100 text-center p-3">
                            <div class="card-body">
                                <i class="fas fa-chalkboard-teacher fa-3x text-success mb-3"></i>
                                <h5>2. Eğitimci Ekleyin</h5>
                                <p class="small">Bilgilerinizi sisteme kaydedin</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card feature-card h-100 text-center p-3">
                            <div class="card-body">
                                <i class="fas fa-book fa-3x text-info mb-3"></i>
                                <h5>3. Eğitim Türü Ekleyin</h5>
                                <p class="small">Vereceğiniz eğitimleri tanımlayın</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card feature-card h-100 text-center p-3">
                            <div class="card-body">
                                <i class="fas fa-certificate fa-3x text-warning mb-3"></i>
                                <h5>4. Sertifika Oluşturun</h5>
                                <p class="small">Katılımcı bilgilerini girin ve yazdırın</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SSS Accordion -->
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <h2 class="text-center mb-4">Sıkça Sorulan Sorular</h2>
                
                <div class="accordion" id="sssAccordion">
                    
                    <!-- Sistem Kullanımı -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                Sisteme nasıl giriş yaparım?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#sssAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Ana sayfada "Giriş Yap" butonuna tıklayın</li>
                                    <li>Eğer hesabınız yoksa "Kayıt Ol" sekmesine geçin</li>
                                    <li>Bilgilerinizi doldurup kayıt olun</li>
                                    <li>Kayıt olduktan sonra kullanıcı adınız ve şifrenizle giriş yapın</li>
                                </ol>
                                <div class="alert alert-info">
                                    <strong>İpucu:</strong> Email adresinizle de giriş yapabilirsiniz.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Eğitimci Ekleme -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                Eğitimci nasıl eklerim?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#sssAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Sertifika oluşturma sayfasına gidin</li>
                                    <li>Eğitimci 1 veya Eğitimci 2 alanının yanındaki "+" butonuna tıklayın</li>
                                    <li>Açılan formda eğitimci bilgilerini doldurun:
                                        <ul>
                                            <li>Ad Soyad (zorunlu)</li>
                                            <li>Ünvan (İş Güvenliği Uzmanı, İşyeri Hekimi vb.)</li>
                                            <li>Sertifika/Sicil Numarası</li>
                                            <li>İmza dosyası (isteğe bağlı)</li>
                                        </ul>
                                    </li>
                                    <li>"Ekle" butonuna tıklayın</li>
                                </ol>
                                <div class="alert alert-warning">
                                    <strong>Not:</strong> Eklediğiniz eğitimciler tüm sertifikalarda kullanılabilir.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Eğitim Türü Ekleme -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                Yeni eğitim türü nasıl eklerim?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#sssAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Sertifika oluşturma sayfasında "Eğitim Adı" alanının yanındaki "+" butonuna tıklayın</li>
                                    <li>Eğitim bilgilerini doldurun:
                                        <ul>
                                            <li><strong>Eğitim Adı:</strong> Örn: "İş Sağlığı ve Güvenliği Eğitimi"</li>
                                            <li><strong>Eğitim Süresi:</strong> Saat cinsinden</li>
                                            <li><strong>Geçerlilik Süresi:</strong> Yıl cinsinden (boş = sonsuz)</li>
                                            <li><strong>Eğitim Kaynağı:</strong> Hangi yönetmelik gereği zorunlu</li>
                                        </ul>
                                    </li>
                                    <li>Eğitim konularını 4 ana başlık altında ekleyin:
                                        <ul>
                                            <li>Genel Konular</li>
                                            <li>Sağlık Konuları</li>
                                            <li>Teknik Konular</li>
                                            <li>Diğer Konular</li>
                                        </ul>
                                    </li>
                                    <li>"Ekle" butonuna tıklayın</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Sertifika Oluşturma -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                                Sertifika nasıl oluştururum?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#sssAccordion">
                            <div class="accordion-body">
                                <h6>Tek Sertifika İçin:</h6>
                                <ol>
                                    <li>"Sertifika Oluştur" menüsüne gidin</li>
                                    <li>Formdaki tüm alanları doldurun:
                                        <ul>
                                            <li>Eğitim tarihleri</li>
                                            <li>Katılımcı bilgileri</li>
                                            <li>Kurum bilgileri</li>
                                            <li>Eğitim detayları</li>
                                            <li>Eğitimci seçimi</li>
                                        </ul>
                                    </li>
                                    <li>"Önizleme ve Yazdır" butonuna tıklayın</li>
                                    <li>Önizleme ekranında kontrollerinizi yapın</li>
                                    <li>"PDF Olarak Yazdır" butonuna tıklayın</li>
                                </ol>
                                
                                <h6 class="mt-3">Toplu Sertifika İçin:</h6>
                                <ol>
                                    <li>Örnek CSV dosyasını indirin</li>
                                    <li>Excel'de açıp verilerinizi girin</li>
                                    <li>CSV formatında kaydedin</li>
                                    <li>"Dosya Yükle" butonuyla yükleyin</li>
                                    <li>Sistem tüm sertifikaları otomatik oluşturacak</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- CSV Kullanımı -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                                CSV dosyası nasıl hazırlanır?
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#sssAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>Sertifika oluşturma sayfasından "Örnek CSV Dosyasını İndir" butonuna tıklayın</li>
                                    <li>İndirilen dosyayı Excel ile açın</li>
                                    <li>Sütun başlıklarını değiştirmeden verilerinizi girin</li>
                                    <li>Önemli noktalar:
                                        <ul>
                                            <li><strong>Tarih formatı:</strong> YYYY-AA-GG (2025-07-26)</li>
                                            <li><strong>Eğitimci adları:</strong> Sistemde kayıtlı olan eğitimci adlarını kullanın</li>
                                            <li><strong>Tehlike sınıfı:</strong> Az Tehlikeli, Tehlikeli, Çok Tehlikeli, Belirtilmemiş</li>
                                            <li><strong>Eğitim şekli:</strong> Örgün Eğitim veya Uzaktan Eğitim</li>
                                        </ul>
                                    </li>
                                    <li>Dosyayı CSV formatında kaydedin</li>
                                    <li>Sisteme yükleyin</li>
                                </ol>
                                <div class="alert alert-danger">
                                    <strong>Dikkat:</strong> Sütun başlıklarını değiştirmeyin, sadece veri satırlarını doldurun.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Eski Kayıtlar -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSix">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix">
                                Eski sertifikalarımı nasıl bulurum?
                            </button>
                        </h2>
                        <div id="collapseSix" class="accordion-collapse collapse" data-bs-parent="#sssAccordion">
                            <div class="accordion-body">
                                <ol>
                                    <li>"Eski Kayıtlar" menüsüne gidin</li>
                                    <li>Arama filtrelerini kullanın:
                                        <ul>
                                            <li>Katılımcı adı</li>
                                            <li>Kurum adı</li>
                                            <li>Eğitim tarihi</li>
                                            <li>Eğitim türü</li>
                                            <li>Tehlike sınıfı</li>
                                        </ul>
                                    </li>
                                    <li>Listelenen kayıtlar arasında istediğinizi bulun</li>
                                    <li>Satırın sonundaki butonlarla:
                                        <ul>
                                            <li><strong>Yazdır:</strong> Tekrar PDF olarak indir</li>
                                            <li><strong>Düzenle:</strong> Bilgileri güncelle</li>
                                        </ul>
                                    </li>
                                </ol>
                                <div class="alert alert-info">
                                    <strong>Not:</strong> Yöneticiler tüm kayıtları, normal kullanıcılar sadece kendi kayıtlarını görebilir.
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Yasal Bilgiler -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingSeven">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven">
                                İSG eğitim süreleri ve geçerlilik süreleri nedir?
                            </button>
                        </h2>
                        <div id="collapseSeven" class="accordion-collapse collapse" data-bs-parent="#sssAccordion">
                            <div class="accordion-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Tehlike Sınıfı</th>
                                                <th>Eğitim Süresi</th>
                                                <th>Geçerlilik Süresi</th>
                                                <th>Yenileme Eğitimi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>Az Tehlikeli</td>
                                                <td>8 Saat</td>
                                                <td>3 Yıl</td>
                                                <td>8 Saat</td>
                                            </tr>
                                            <tr>
                                                <td>Tehlikeli</td>
                                                <td>12 Saat</td>
                                                <td>2 Yıl</td>
                                                <td>8 Saat</td>
                                            </tr>
                                            <tr>
                                                <td>Çok Tehlikeli</td>
                                                <td>16 Saat</td>
                                                <td>1 Yıl</td>
                                                <td>8 Saat</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="alert alert-warning">
                                    <strong>Yasal Dayanak:</strong> Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Sorun Giderme -->
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingEight">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEight">
                                Karşılaştığım sorunları nasıl çözerim?
                            </button>
                        </h2>
                        <div id="collapseEight" class="accordion-collapse collapse" data-bs-parent="#sssAccordion">
                            <div class="accordion-body">
                                <h6>Sık Karşılaşılan Sorunlar ve Çözümleri:</h6>
                                
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="text-danger">🔴 CSV dosyası yüklenmiyor</h6>
                                                <p><strong>Çözüm:</strong> Dosyanın CSV formatında olduğundan ve örnek dosyadaki sütun başlıklarının değişmediğinden emin olun.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="text-danger">🔴 PDF oluşturulmuyor</h6>
                                                <p><strong>Çözüm:</strong> Tarayıcınızın popup engelleyicisini kapatın ve tekrar deneyin.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="text-danger">🔴 Eğitimci listesinde görünmüyor</h6>
                                                <p><strong>Çözüm:</strong> Sayfayı yenileyin veya önce eğitimciyi ekleyip sonra sertifika formunu doldurun.</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-body">
                                                <h6 class="text-danger">🔴 Şifremi unuttum</h6>
                                                <p><strong>Çözüm:</strong> İletişim sayfasından bizimle iletişime geçin, şifrenizi sıfırlayalım.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="alert alert-info mt-3">
                                    <strong>Hala sorun yaşıyorsanız:</strong> İletişim sayfasından bizimle iletişime geçin. Sorununuzu detaylı açıklayın.
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Önemli Bilgiler -->
        <div class="row mt-5">
            <div class="col-lg-8 mx-auto">
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> Önemli Bilgiler</h5>
                    <ul class="mb-0">
                        <li>Sistem 7/24 çalışmaktadır ancak bakım zamanlarında kısa süreli kesinti olabilir</li>
                        <li>Tüm verileriniz güvenli şekilde saklanmaktadır</li>
                        <li>Oluşturulan sertifikalar yasal geçerliliğe sahiptir</li>
                        <li>Sistem düzenli olarak güncellenmekte ve yeni özellikler eklenmektedir</li>
                        <li>Sorun yaşadığınızda mutlaka bizimle iletişime geçin</li>
                    </ul>
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