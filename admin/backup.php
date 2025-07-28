<?php
/**
 * Otomatik Veritabanı Yedekleme Sistemi
 * Bu dosya cron job ile çalıştırılabilir
 * 
 * Cron job örneği:
 * 0 2 * * * /usr/bin/php /path/to/online-sertifika/admin/backup.php
 */

// CLI modunda çalışıp çalışmadığını kontrol et
if (php_sapi_name() !== 'cli' && !defined('ALLOW_WEB_ACCESS')) {
    // Web erişimi için güvenlik kontrolü
    session_start();
    if (!isset($_SESSION['kullanici_id']) || $_SESSION['rol'] != 'yonetici') {
        die('Yetkisiz erişim!');
    }
}

include '../config/database.php';

// Log fonksiyonu
function yazLog($mesaj) {
    $log_dosyasi = '../backups/backup.log';
    $tarih = date('Y-m-d H:i:s');
    file_put_contents($log_dosyasi, "[$tarih] $mesaj\n", FILE_APPEND | LOCK_EX);
    echo "[$tarih] $mesaj\n";
}

// Klasör oluştur
function klasorOlustur($klasor) {
    if (!is_dir($klasor)) {
        if (!mkdir($klasor, 0755, true)) {
            yazLog("HATA: $klasor klasörü oluşturulamadı!");
            return false;
        }
        yazLog("BILGI: $klasor klasörü oluşturuldu.");
    }
    return true;
}

// Eski yedekleri temizle
function eskiYedekleriTemizle($klasor, $gun = 30) {
    $dosyalar = glob($klasor . 'backup_*.sql');
    $silinecek_tarih = time() - ($gun * 24 * 60 * 60);
    $silinen_sayi = 0;
    
    foreach ($dosyalar as $dosya) {
        if (filemtime($dosya) < $silinecek_tarih) {
            if (unlink($dosya)) {
                $silinen_sayi++;
                yazLog("BILGI: Eski yedek silindi - " . basename($dosya));
            }
        }
    }
    
    if ($silinen_sayi > 0) {
        yazLog("BILGI: $silinen_sayi adet eski yedek dosyası temizlendi.");
    }
}

// Ana yedekleme fonksiyonu
function veritabaniYedekAl($pdo) {
    try {
        $yedek_klasor = '../backups/';
        
        // Klasör kontrolü
        if (!klasorOlustur($yedek_klasor)) {
            return false;
        }
        
        // Yedek dosya adı
        $yedek_dosyasi = $yedek_klasor . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        yazLog("BAŞLADI: Veritabanı yedeği alınıyor...");
        
        // Tabloları al
        $tablolar = ['kullanicilar', 'egitimler', 'egitim_konulari', 'egitimciler', 'sertifikalar'];
        
        // Sistem ayarları tablosu varsa ekle
        try {
            $pdo->query("SELECT 1 FROM sistem_ayarlari LIMIT 1");
            $tablolar[] = 'sistem_ayarlari';
        } catch (PDOException $e) {
            // Tablo yoksa, sorun değil
        }
        
        $yedek_icerik = "-- Online Sertifika Sistemi Veritabanı Yedeği\n";
        $yedek_icerik .= "-- Oluşturulma Tarihi: " . date('Y-m-d H:i:s') . "\n";
        $yedek_icerik .= "-- PHP Sürümü: " . PHP_VERSION . "\n\n";
        $yedek_icerik .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tablolar as $tablo) {
            yazLog("BILGI: $tablo tablosu yedekleniyor...");
            
            try {
                // Tablo yapısını al
                $stmt = $pdo->prepare("SHOW CREATE TABLE `$tablo`");
                $stmt->execute();
                $create_table = $stmt->fetch();
                
                if ($create_table) {
                    $yedek_icerik .= "-- Tablo yapısı: $tablo\n";
                    $yedek_icerik .= "DROP TABLE IF EXISTS `$tablo`;\n";
                    $yedek_icerik .= $create_table['Create Table'] . ";\n\n";
                    
                    // Tablo verilerini al
                    $stmt = $pdo->prepare("SELECT * FROM `$tablo`");
                    $stmt->execute();
                    $veriler = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($veriler)) {
                        $yedek_icerik .= "-- Tablo verileri: $tablo\n";
                        $yedek_icerik .= "LOCK TABLES `$tablo` WRITE;\n";
                        
                        foreach ($veriler as $veri) {
                            $kolonlar = array_keys($veri);
                            $degerler = array_map(function($v) use ($pdo) {
                                return $v === null ? 'NULL' : $pdo->quote($v);
                            }, array_values($veri));
                            
                            $yedek_icerik .= "INSERT INTO `$tablo` (`" . implode('`, `', $kolonlar) . "`) VALUES (" . implode(', ', $degerler) . ");\n";
                        }
                        
                        $yedek_icerik .= "UNLOCK TABLES;\n\n";
                        yazLog("BILGI: $tablo tablosundan " . count($veriler) . " kayıt yedeklendi.");
                    } else {
                        yazLog("BILGI: $tablo tablosu boş.");
                    }
                }
            } catch (PDOException $e) {
                yazLog("HATA: $tablo tablosu yedeklenirken hata oluştu - " . $e->getMessage());
                continue;
            }
        }
        
        $yedek_icerik .= "SET FOREIGN_KEY_CHECKS=1;\n";
        $yedek_icerik .= "-- Yedekleme tamamlandı: " . date('Y-m-d H:i:s') . "\n";
        
        // Dosyaya yaz
        if (file_put_contents($yedek_dosyasi, $yedek_icerik)) {
            $dosya_boyutu = filesize($yedek_dosyasi);
            yazLog("BAŞARILI: Yedek alındı - " . basename($yedek_dosyasi) . " (" . round($dosya_boyutu/1024, 2) . " KB)");
            
            // Eski yedekleri temizle
            eskiYedekleriTemizle($yedek_klasor, 30);
            
            return $yedek_dosyasi;
        } else {
            yazLog("HATA: Yedek dosyası yazılamadı!");
            return false;
        }
        
    } catch (Exception $e) {
        yazLog("HATA: Yedekleme sırasında beklenmeyen hata - " . $e->getMessage());
        return false;
    }
}

// Email gönderme fonksiyonu (opsiyonel)
function emailGonder($yedek_dosyasi) {
    try {
        // Sistem ayarlarından email bilgilerini al
        global $pdo;
        $stmt = $pdo->prepare("SELECT ayar_adi, ayar_degeri FROM sistem_ayarlari WHERE ayar_adi IN ('admin_email', 'smtp_host', 'smtp_kullanici', 'smtp_sifre')");
        $stmt->execute();
        $ayarlar = [];
        while ($row = $stmt->fetch()) {
            $ayarlar[$row['ayar_adi']] = $row['ayar_degeri'];
        }
        
        if (!empty($ayarlar['admin_email'])) {
            $konu = 'Online Sertifika - Otomatik Yedek Tamamlandı';
            $mesaj = "Veritabanı yedeği başarıyla alındı.\n\n";
            $mesaj .= "Dosya: " . basename($yedek_dosyasi) . "\n";
            $mesaj .= "Boyut: " . round(filesize($yedek_dosyasi)/1024, 2) . " KB\n";
            $mesaj .= "Tarih: " . date('d.m.Y H:i:s') . "\n\n";
            $mesaj .= "Bu otomatik bir mesajdır.";
            
            $headers = 'From: noreply@bursaisg.com' . "\r\n" .
                      'Reply-To: noreply@bursaisg.com' . "\r\n" .
                      'X-Mailer: PHP/' . phpversion();
            
            if (mail($ayarlar['admin_email'], $konu, $mesaj, $headers)) {
                yazLog("BILGI: Email bildirimi gönderildi - " . $ayarlar['admin_email']);
            }
        }
    } catch (Exception $e) {
        yazLog("UYARI: Email gönderilemedi - " . $e->getMessage());
    }
}

// Ana çalıştırma
try {
    yazLog("==========================================");
    yazLog("BAŞLADI: Otomatik yedekleme sistemi");
    
    // Veritabanı bağlantısını kontrol et
    if (!$pdo) {
        yazLog("HATA: Veritabanı bağlantısı kurulamadı!");
        exit(1);
    }
    
    // Sistem ayarlarını kontrol et
    $otomatik_yedek = true;
    try {
        $stmt = $pdo->prepare("SELECT ayar_degeri FROM sistem_ayarlari WHERE ayar_adi = 'otomatik_yedek'");
        $stmt->execute();
        $ayar = $stmt->fetchColumn();
        $otomatik_yedek = ($ayar == '1');
    } catch (PDOException $e) {
        // Ayar tablosu yoksa varsayılan olarak devam et
    }
    
    if (!$otomatik_yedek) {
        yazLog("BILGI: Otomatik yedekleme kapalı, işlem durduruldu.");
        exit(0);
    }
    
    // Yedekleme işlemini başlat
    $yedek_dosyasi = veritabaniYedekAl($pdo);
    
    if ($yedek_dosyasi) {
        yazLog("BAŞARILI: Otomatik yedekleme tamamlandı.");
        
        // Email bildirimi gönder (opsiyonel)
        emailGonder($yedek_dosyasi);
        
        // Web erişimi ise dosyayı indir
        if (php_sapi_name() !== 'cli' && defined('ALLOW_WEB_ACCESS')) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($yedek_dosyasi) . '"');
            header('Content-Length: ' . filesize($yedek_dosyasi));
            readfile($yedek_dosyasi);
        }
        
        exit(0);
    } else {
        yazLog("HATA: Yedekleme başarısız!");
        exit(1);
    }
    
} catch (Exception $e) {
    yazLog("KRITIK HATA: " . $e->getMessage());
    exit(1);
} finally {
    yazLog("BİTTİ: Otomatik yedekleme sistemi");
    yazLog("==========================================");
}
?>