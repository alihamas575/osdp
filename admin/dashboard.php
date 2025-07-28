<?php 
include '../config/database.php';
yonetici_kontrol();

// İstatistikler
$total_users = $pdo->query("SELECT COUNT(*) FROM kullanicilar WHERE aktif = 1")->fetchColumn();
$total_certificates = $pdo->query("SELECT COUNT(*) FROM sertifikalar")->fetchColumn();
$total_trainings = $pdo->query("SELECT COUNT(*) FROM egitimler")->fetchColumn();
$total_trainers = $pdo->query("SELECT COUNT(*) FROM egitimciler")->fetchColumn();

// Bugün oluşturulan sertifikalar
$today_certificates = $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE DATE(kayit_tarihi) = CURDATE()")->fetchColumn();

// Bu ay oluşturulan sertifikalar
$this_month_certificates = $pdo->query("SELECT COUNT(*) FROM sertifikalar WHERE MONTH(kayit_tarihi) = MONTH(CURDATE()) AND YEAR(kayit_tarihi) = YEAR(CURDATE())")->fetchColumn();

// Son eklenen kullanıcılar
$recent_users = $pdo->query("SELECT ad_soyad, email, kayit_tarihi FROM kullanicilar WHERE aktif = 1 ORDER BY kayit_tarihi DESC LIMIT 5")->fetchAll();

// Son oluşturulan sertifikalar
$recent_certificates = $pdo->query("
    SELECT s.katilimci_ad_soyad, s.kurum_adi, s.kayit_tarihi, u.ad_soyad as olusturan 
    FROM sertifikalar s 
    LEFT JOIN kullanicilar u ON s.kullanici_id = u.id 
    ORDER BY s.kayit_tarihi DESC 
    LIMIT 10
")->fetchAll();

// Aylık istatistikler (Son 6 ay)
$monthly_stats = $pdo->query("
    SELECT 
        DATE_FORMAT(kayit_tarihi, '%Y-%m') as ay,
        COUNT(*) as toplam
    FROM sertifikalar 
    WHERE kayit_tarihi >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(kayit_tarihi, '%Y-%m')
    ORDER BY ay
")->fetchAll();

// Kullanıcı ekleme işlemi
if (isset($_POST['kullanici_ekle'])) {
    $kullanici_adi = guvenli_veri($_POST['kullanici_adi']);
    $email = guvenli_veri($_POST['email']);
    $sifre = $_POST['sifre'];
    $ad_soyad = guvenli_veri($_POST['ad_soyad']);
    $telefon = guvenli_veri($_POST['telefon']);
    $rol = $_POST['rol'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO kullanicilar (kullanici_adi, email, sifre, ad_soyad, telefon, rol) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kullanici_adi, $email, md5($sifre), $ad_soyad, $telefon, $rol]);
        $basari = "Kullanıcı başarıyla eklendi!";
    } catch (PDOException $e) {
        $hata = "Kullanıcı eklenirken hata oluştu!";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli - Online Sertifika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 15px;
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
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="kullanici_yonetimi.php">
                    <i class="fas fa-users"></i> Kullanıcı Yönetimi
                </a>
                <a class="nav-link" href="sertifika_yonetimi.php">
                    <i class="fas fa-certificate"></i> Sertifika Yönetimi
                </a>
                <a class="nav-link" href="egitim_yonetimi.php">
                    <i class="fas fa-book"></i> Eğitim Yönetimi
                </a>
                <a class="nav-link" href="egitimci_yonetimi.php">
                    <i class="fas fa-chalkboard-teacher"></i> Eğitimci Yönetimi
                </a>
                <a class="nav-link" href="raporlar.php">
                    <i class="fas fa-chart-bar"></i> Raporlar
                </a>
                <a class="nav-link" href="ayarlar.php">
                    <i class="fas fa-cog"></i> Sistem Ayarları
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
                    <div class="card dashboard-card p-4">
                        <h1 class="mb-0">
                            <i class="fas fa-tachometer-alt"></i> Yönetici Paneli
                        </h1>
                        <p class="mb-0">Hoş geldiniz, <?php echo $_SESSION['ad_soyad']; ?>! Sistem istatistikleriniz aşağıda yer almaktadır.</p>
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

            <!-- İstatistik Kartları -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-users fa-3x text-primary mb-3"></i>
                            <h3 class="text-primary"><?php echo $total_users; ?></h3>
                            <p class="card-text">Toplam Kullanıcı</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-certificate fa-3x text-success mb-3"></i>
                            <h3 class="text-success"><?php echo $total_certificates; ?></h3>
                            <p class="card-text">Toplam Sertifika</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-book fa-3x text-info mb-3"></i>
                            <h3 class="text-info"><?php echo $total_trainings; ?></h3>
                            <p class="card-text">Eğitim Türü</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-chalkboard-teacher fa-3x text-warning mb-3"></i>
                            <h3 class="text-warning"><?php echo $total_trainers; ?></h3>
                            <p class="card-text">Eğitimci</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- İkinci Sıra İstatistikler -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-calendar-day fa-2x text-info mb-3"></i>
                            <h4 class="text-info"><?php echo $today_certificates; ?></h4>
                            <p class="card-text">Bugün Oluşturulan</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-calendar-alt fa-2x text-primary mb-3"></i>
                            <h4 class="text-primary"><?php echo $this_month_certificates; ?></h4>
                            <p class="card-text">Bu Ay Oluşturulan</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card stat-card text-center p-3">
                        <div class="card-body">
                            <i class="fas fa-chart-line fa-2x text-success mb-3"></i>
                            <h4 class="text-success">
                                <?php 
                                $ortalama = $total_certificates > 0 ? round($this_month_certificates / max(1, date('j')), 1) : 0;
                                echo $ortalama;
                                ?>
                            </h4>
                            <p class="card-text">Günlük Ortalama</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafik ve Tablolar -->
            <div class="row">
                <!-- Aylık Grafik -->
                <div class="col-md-8 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-bar"></i> Aylık Sertifika İstatistikleri</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="monthlyChart" height="100"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Hızlı İşlemler -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-bolt"></i> Hızlı İşlemler</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kullaniciEkleModal">
                                    <i class="fas fa-user-plus"></i> Yeni Kullanıcı Ekle
                                </button>
                                <a href="../user/sertifika_olustur.php" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Sertifika Oluştur
                                </a>
                                <a href="kullanici_yonetimi.php" class="btn btn-info">
                                    <i class="fas fa-users"></i> Kullanıcıları Yönet
                                </a>
                                <a href="raporlar.php" class="btn btn-warning">
                                    <i class="fas fa-file-excel"></i> Rapor İndir
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Son Aktiviteler -->
            <div class="row">
                <!-- Son Kullanıcılar -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-user-clock"></i> Son Eklenen Kullanıcılar</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Ad Soyad</th>
                                            <th>Email</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['ad_soyad']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($user['kayit_tarihi'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Son Sertifikalar -->
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-certificate"></i> Son Oluşturulan Sertifikalar</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Katılımcı</th>
                                            <th>Kurum</th>
                                            <th>Oluşturan</th>
                                            <th>Tarih</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_certificates as $cert): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($cert['katilimci_ad_soyad']); ?></td>
                                            <td><?php echo htmlspecialchars($cert['kurum_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($cert['olusturan']); ?></td>
                                            <td><?php echo date('d.m.Y', strtotime($cert['kayit_tarihi'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Kullanıcı Ekleme Modal -->
<div class="modal fade" id="kullaniciEkleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kullanıcı Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" name="ad_soyad" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" class="form-control" name="kullanici_adi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="tel" class="form-control" name="telefon">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Şifre</label>
                        <input type="password" class="form-control" name="sifre" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select class="form-select" name="rol" required>
                            <option value="kullanici">Kullanıcı</option>
                            <option value="yonetici">Yönetici</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="kullanici_ekle" class="btn btn-primary">Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Aylık grafik
const monthlyData = <?php echo json_encode($monthly_stats); ?>;
const labels = monthlyData.map(item => {
    const [year, month] = item.ay.split('-');
    const date = new Date(year, month - 1);
    return date.toLocaleDateString('tr-TR', { year: 'numeric', month: 'short' });
});
const data = monthlyData.map(item => item.toplam);

const ctx = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Oluşturulan Sertifika Sayısı',
            data: data,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>

</body>
</html>