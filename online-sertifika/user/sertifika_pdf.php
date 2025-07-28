<?php 
include '../config/database.php';
oturum_kontrol();

if (!isset($_GET['id'])) {
    header("Location: sertifika_olustur.php");
    exit();
}

$sertifika_id = intval($_GET['id']);

// Sertifika bilgilerini çek
$stmt = $pdo->prepare("
    SELECT s.*, 
           e.egitim_adi, e.egitim_kaynagi,
           eg1.ad_soyad as egitimci1_ad, eg1.unvan as egitimci1_unvan, eg1.sertifika_no as egitimci1_sertifika,
           eg2.ad_soyad as egitimci2_ad, eg2.unvan as egitimci2_unvan, eg2.sertifika_no as egitimci2_sertifika
    FROM sertifikalar s
    LEFT JOIN egitimler e ON s.egitim_id = e.id
    LEFT JOIN egitimciler eg1 ON s.egitimci_1_id = eg1.id
    LEFT JOIN egitimciler eg2 ON s.egitimci_2_id = eg2.id
    WHERE s.id = ?
");
$stmt->execute([$sertifika_id]);
$sertifika = $stmt->fetch();

if (!$sertifika) {
    die("Sertifika bulunamadı!");
}

// Yetki kontrolü
if ($_SESSION['rol'] != 'yonetici' && $sertifika['kullanici_id'] != $_SESSION['kullanici_id']) {
    die("Bu sertifikayı görüntüleme yetkiniz yok!");
}

// Eğitim konularını çek
$konular = [];
if ($sertifika['egitim_id']) {
    $stmt = $pdo->prepare("SELECT * FROM egitim_konulari WHERE egitim_id = ? ORDER BY ana_konu, sira_no");
    $stmt->execute([$sertifika['egitim_id']]);
    $konu_listesi = $stmt->fetchAll();
    
    foreach ($konu_listesi as $konu) {
        $konular[$konu['ana_konu']][] = $konu['alt_konu'];
    }
}

// Tarih formatını düzenle
function tarih_format($tarih) {
    return date('d.m.Y', strtotime($tarih));
}

// PDF için HTML içeriği oluştur
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { 
            font-family: "DejaVu Sans", Arial, sans-serif; 
            font-size: 12px;
            line-height: 1.4;
            margin: 20px;
        }
        .sertifika-container {
            border: 3px solid #0066cc;
            border-radius: 15px;
            padding: 30px;
            background: white;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .sertifika-baslik {
            font-size: 24px;
            font-weight: bold;
            color: #0066cc;
            margin: 15px 0;
        }
        .tarih-info {
            font-size: 14px;
            margin-bottom: 10px;
        }
        .katilimci-info {
            background: #f8f9fa;
            border-left: 4px solid #0066cc;
            padding: 15px;
            margin: 20px 0;
        }
        .konular-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .egitimci-section {
            margin-top: 30px;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        .egitimci-box {
            text-align: center;
            width: 45%;
            border: 1px solid #dee2e6;
            padding: 15px;
            border-radius: 8px;
            display: inline-block;
            margin: 10px;
            vertical-align: top;
        }
        .float-left { float: left; }
        .float-right { float: right; }
        .clear { clear: both; }
        .text-center { text-align: center; }
        .text-justify { text-align: justify; }
        .mb-3 { margin-bottom: 15px; }
        .mt-4 { margin-top: 20px; }
        .row { overflow: hidden; }
        .col-6 { width: 48%; float: left; margin: 1%; }
        h6 { font-size: 14px; font-weight: bold; margin: 10px 0 5px 0; }
        .konu-baslik { font-weight: bold; margin-top: 10px; }
        .konu-icerik { margin-left: 10px; }
    </style>
</head>
<body>
    <div class="sertifika-container">
        <!-- Header -->
        <div class="header">
            <table width="100%">
                <tr>
                    <td width="30%" style="text-align: left;">
                        <!-- Logo alanı -->
                    </td>
                    <td width="40%" style="text-align: center;">
                        <div class="tarih-info">
                            <strong>Eğitim Tarihi:</strong> 
                            ' . tarih_format($sertifika['egitim_tarihi_1']);

if ($sertifika['egitim_tarihi_2']) {
    $html .= ' - ' . tarih_format($sertifika['egitim_tarihi_2']);
}

$html .= '
                        </div>
                        <div class="tarih-info">
                            <strong>Geçerlilik Süresi:</strong> 
                            ' . ($sertifika['gecerlilik_suresi'] ? $sertifika['gecerlilik_suresi'] . ' Yıl' : 'Sonsuz') . '
                        </div>
                    </td>
                    <td width="30%">
                        <!-- Boş alan -->
                    </td>
                </tr>
            </table>
            
            <div class="sertifika-baslik">
                ' . strtoupper(htmlspecialchars($sertifika['egitim_adi'] ?: 'İŞ SAĞLIĞI VE GÜVENLİĞİ EĞİTİM SERTİFİKASI')) . '
            </div>
        </div>

        <!-- Katılımcı Bilgileri -->
        <div class="katilimci-info">
            <table width="100%">
                <tr>
                    <td width="50%">
                        <strong>Katılımcı:</strong> ' . strtoupper(htmlspecialchars($sertifika['katilimci_ad_soyad'])) . '<br>
                        <strong>Kurum Adı:</strong> ' . strtoupper(htmlspecialchars($sertifika['kurum_adi'])) . '<br>
                        <strong>Görevi:</strong> ' . strtoupper(htmlspecialchars($sertifika['gorevi'])) . '
                    </td>
                    <td width="50%">
                        <strong>Tehlike Sınıfı:</strong> ' . htmlspecialchars($sertifika['tehlike_sinifi']) . '<br>
                        <strong>Eğitim Süresi:</strong> ' . $sertifika['egitim_suresi'] . ' Saat<br>
                        <strong>Eğitim Şekli:</strong> ' . htmlspecialchars($sertifika['egitim_sekli']) . '
                    </td>
                </tr>
            </table>
        </div>

        <!-- Açıklama Metni -->
        <div class="text-justify mb-3">
            Yukarıda adı geçen kişi, "' . htmlspecialchars($sertifika['egitim_kaynagi'] ?: 'Çalışanların İş Sağlığı ve Güvenliği Eğitimlerinin Usul ve Esasları Hakkında Yönetmelik') . '" 
            kapsamında verilen ' . htmlspecialchars($sertifika['egitim_adi'] ?: 'iş sağlığı ve güvenliği') . ' eğitimlerini başarıyla tamamlayarak bu eğitim belgesini almaya hak kazanmıştır.
        </div>';

// Eğitim Konuları
if (!empty($konular)) {
    $html .= '<div class="konular-section">';
    
    $ana_konular = ['Genel Konular', 'Sağlık Konuları', 'Teknik Konular', 'Diğer Konular'];
    $kolon = 0;
    
    $html .= '<table width="100%"><tr>';
    
    foreach ($ana_konular as $sira => $ana_konu) {
        if (!empty($konular[$ana_konu])) {
            if ($kolon % 2 == 0 && $kolon > 0) {
                $html .= '</tr><tr>';
            }
            
            $html .= '<td width="50%" valign="top" style="padding: 10px;">';
            $html .= '<h6>' . ($sira + 1) . '. ' . $ana_konu . '</h6>';
            
            foreach ($konular[$ana_konu] as $alt_konu) {
                $html .= '<div style="margin-left: 10px;">• ' . htmlspecialchars($alt_konu) . '</div>';
            }
            
            $html .= '</td>';
            $kolon++;
        }
    }
    
    $html .= '</tr></table>';
    $html .= '</div>';
}

// Eğitimciler
$html .= '
        <div class="egitimci-section">
            <table width="100%">
                <tr>
                    <td width="45%" style="text-align: center; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px;">
                        <h6><strong>İŞ GÜVENLİĞİ UZMANI</strong></h6>
                        <div><strong>' . strtoupper(htmlspecialchars($sertifika['egitimci1_ad'])) . '</strong></div>
                        <div>' . htmlspecialchars($sertifika['egitimci1_unvan']) . '</div>';

if ($sertifika['egitimci1_sertifika']) {
    $html .= '<div>Sertifika No: ' . htmlspecialchars($sertifika['egitimci1_sertifika']) . '</div>';
}

$html .= '
                        <div style="height: 60px; margin: 10px 0; border-bottom: 1px solid #000;"></div>
                        <div><small>İmza</small></div>
                    </td>
                    <td width="10%"></td>';

if ($sertifika['egitimci2_ad']) {
    $html .= '
                    <td width="45%" style="text-align: center; border: 1px solid #dee2e6; padding: 15px; border-radius: 8px;">
                        <h6><strong>İŞYERİ HEKİMİ</strong></h6>
                        <div><strong>' . strtoupper(htmlspecialchars($sertifika['egitimci2_ad'])) . '</strong></div>
                        <div>' . htmlspecialchars($sertifika['egitimci2_unvan']) . '</div>';
    
    if ($sertifika['egitimci2_sertifika']) {
        $html .= '<div>Sertifika No: ' . htmlspecialchars($sertifika['egitimci2_sertifika']) . '</div>';
    }
    
    $html .= '
                        <div style="height: 60px; margin: 10px 0; border-bottom: 1px solid #000;"></div>
                        <div><small>İmza</small></div>
                    </td>';
} else {
    $html .= '<td width="45%"></td>';
}

$html .= '
                </tr>
            </table>
        </div>

        <!-- Alt Bilgi -->
        <div class="text-center mt-4" style="font-size: 10px; color: #666;">
            Sertifika Tarihi: ' . date('d.m.Y') . ' | 
            Sertifika No: OSS-' . str_pad($sertifika['id'], 6, '0', STR_PAD_LEFT) . '
        </div>
    </div>
</body>
</html>';

// Basit PDF oluşturma (HTML to PDF converter kullanmadan)
// Bu kısımda mPDF veya TCPDF kullanabilirsiniz, burada basit bir HTML çıktısı veriyoruz

// Eğer mPDF yüklüyse:
if (class_exists('Mpdf\Mpdf')) {
    require_once '../vendor/autoload.php';
    
    $mpdf = new \Mpdf\Mpdf([
        'format' => 'A4',
        'orientation' => 'L', // Landscape
        'margin_left' => 10,
        'margin_right' => 10,
        'margin_top' => 10,
        'margin_bottom' => 10,
    ]);
    
    $mpdf->WriteHTML($html);
    $dosya_adi = 'sertifika_' . $sertifika['katilimci_ad_soyad'] . '_' . date('Y-m-d') . '.pdf';
    $mpdf->Output($dosya_adi, 'D'); // D = Download
} else {
    // mPDF yoksa, tarayıcıda HTML olarak göster ve kullanıcının yazdırmasını sağla
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    echo '
    <script>
        window.onload = function() {
            if (confirm("Sertifikayı PDF olarak yazdırmak için tarayıcınızın yazdır özelliğini kullanın. Yazdır penceresini açmak istiyor musunuz?")) {
                window.print();
            }
        }
    </script>';
}
?>