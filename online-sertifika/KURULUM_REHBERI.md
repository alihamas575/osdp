# Online Sertifika Sistemi Kurulum Rehberi

## 🚀 Gereksinimler

- **PHP:** 7.4 veya üzeri
- **MySQL/MariaDB:** 5.7 veya üzeri
- **Web Sunucusu:** Apache/Nginx
- **PHP Eklentileri:**
  - PDO ve PDO_MySQL
  - GD Library (görsel işleme için)
  - mbstring (Türkçe karakter desteği için)
  - fileinfo (dosya tipi kontrolü için)

## 📁 Dosya Yapısı

```
online-sertifika/
├── config/
│   └── database.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── functions.php
├── admin/
│   ├── dashboard.php
│   ├── kullanici_yonetimi.php
│   ├── sertifika_yonetimi.php
│   ├── egitim_yonetimi.php
│   ├── egitimci_yonetimi.php
│   ├── raporlar.php
│   └── ayarlar.php
├── user/
│   ├── sertifika_olustur.php
│   ├── sertifika_onizleme.php
│   ├── sertifika_pdf.php
│   └── eski_kayitlar.php
├── assets/
│   ├── uploads/
│   │   └── imzalar/
│   ├── css/
│   ├── js/
│   └── sertifika_ornegi.csv
├── backups/
├── .htaccess
├── index.php
├── login.php
├── logout.php
├── hakkimizda.php
├── iletisim.php
└── bilgi_bankasi.php
```

## 🔧 Kurulum Adımları

### 1. Dosyaları Yükleme
1. Tüm dosyaları sunucunuzdaki `online-sertifika` klasörüne yükleyin
2. Klasör izinlerini ayarlayın:
   ```bash
   chmod 755 online-sertifika/
   chmod 777 assets/uploads/
   chmod 777 assets/uploads/imzalar/
   chmod 777 backups/
   ```

### 2. Veritabanı Kurulumu

#### cPanel ile:
1. cPanel → phpMyAdmin'e girin
2. Yeni veritabanı oluşturun: `burs5462_online_sertifika_db`
3. Veritabanı kullanıcısı oluşturun ve tüm yetkileri verin
4. Aşağıdaki SQL komutlarını sırasıyla çalıştırın:

```sql
-- 1. Kullanıcılar Tablosu
CREATE TABLE kullanicilar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_adi VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    sifre VARCHAR(255) NOT NULL,
    ad_soyad VARCHAR(100) NOT NULL,
    telefon VARCHAR(20),
    rol ENUM('yonetici', 'kullanici') DEFAULT 'kullanici',
    aktif TINYINT(1) DEFAULT 1,
    kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Eğitimler Tablosu
CREATE TABLE egitimler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    egitim_adi VARCHAR(200) NOT NULL,
    egitim_suresi INT NOT NULL,
    egitim_kaynagi TEXT,
    gecerlilik_suresi INT DEFAULT NULL,
    kullanici_id INT,
    kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id)
);

-- 3. Eğitim Konuları Tablosu
CREATE TABLE egitim_konulari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    egitim_id INT,
    ana_konu ENUM('Genel Konular', 'Sağlık Konuları', 'Teknik Konular', 'Diğer Konular') NOT NULL,
    alt_konu TEXT,
    sira_no INT,
    FOREIGN KEY (egitim_id) REFERENCES egitimler(id) ON DELETE CASCADE
);

-- 4. Eğitimciler Tablosu
CREATE TABLE egitimciler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad VARCHAR(100) NOT NULL,
    unvan VARCHAR(100),
    sertifika_no VARCHAR(50),
    imza_dosyasi VARCHAR(255),
    kullanici_id INT,
    kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id)
);

-- 5. Sertifikalar Tablosu
CREATE TABLE sertifikalar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    egitim_tarihi_1 DATE NOT NULL,
    egitim_tarihi_2 DATE NULL,
    gecerlilik_suresi INT,
    egitim_id INT,
    katilimci_ad_soyad VARCHAR(100) NOT NULL,
    kurum_adi VARCHAR(200),
    gorevi VARCHAR(100),
    tehlike_sinifi ENUM('Az Tehlikeli', 'Tehlikeli', 'Çok Tehlikeli', 'Belirtilmemiş'),
    egitim_suresi INT,
    egitim_sekli ENUM('Örgün Eğitim', 'Uzaktan Eğitim'),
    egitimci_1_id INT,
    egitimci_2_id INT NULL,
    kullanici_id INT,
    kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (egitim_id) REFERENCES egitimler(id),
    FOREIGN KEY (egitimci_1_id) REFERENCES egitimciler(id),
    FOREIGN KEY (egitimci_2_id) REFERENCES egitimciler(id),
    FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(id)
);

-- 6. İlk Yönetici Kullanıcısını Ekleme
INSERT INTO kullanicilar (kullanici_adi, email, sifre, ad_soyad, rol) 
VALUES ('admin', 'admin@bursaisg.com', MD5('admin123'), 'Yönetici', 'yonetici');
```

### 3. Veritabanı Bağlantı Ayarları

`config/database.php` dosyasını düzenleyin:

```php
$servername = "localhost";
$username = "burs5462_db_user"; // cPanel'den aldığınız DB kullanıcı adı
$password = "QpmKH7mT2MAyxN7"; // cPanel'den aldığınız DB şifresi
$dbname = "burs5462_online_sertifika_db"; // Veritabanı adı
```

### 4. Güvenlik Ayarları

`.htaccess` dosyasını ana dizine koyun:

```apache
# Güvenlik başlıkları
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"

# Dosya yükleme güvenliği
<Files ~ "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    Order Deny,Allow
    Deny from all
</Files>

# uploads klasöründeki PHP dosyalarını çalıştırma
<Directory "assets/uploads">
    <Files "*.php">
        Order Deny,Allow
        Deny from all
    </Files>
</Directory>

# Gizli dosyaları gizle
<Files ~ "^\.">
    Order allow,deny
    Deny from all
</Files>

# Veritabanı dosyalarını gizle
<Files ~ "\.sql$">
    Order allow,deny
    Deny from all
</Files>

# PHP error reporting kapat (production için)
php_flag display_errors Off

# Dosya boyutu limitleri
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

## 🔐 İlk Giriş

1. Tarayıcınızda `https://bursaisg.com/online-sertifika` adresine gidin
2. "Giriş Yap" butonuna tıklayın
3. İlk giriş bilgileri:
   - **Kullanıcı adı:** admin
   - **Şifre:** admin123

⚠️ **ÖNEMLİ:** İlk girişten sonra mutlaka admin şifresini değiştirin!

## 📋 İlk Ayarlar

### 1. Sistem Ayarları
1. Admin panelinde "Sistem Ayarları" bölümüne gidin
2. Site adını ve açıklamasını güncelleyin
3. Admin email adresini değiştirin
4. Logo yükleyin (opsiyonel)

### 2. İlk Eğitimci Ekleme
1. "Eğitimci Yönetimi" bölümüne gidin
2. "Yeni Eğitimci Ekle" butonuna tıklayın
3. Bilgilerinizi girin ve imza yükleyin

### 3. İlk Eğitim Türü Ekleme
1. Sertifika oluşturma sayfasına gidin
2. "Eğitim Adı" alanındaki "+" butonuna tıklayın
3. İSG eğitim bilgilerini ekleyin

## 🚀 PDF Oluşturma İyileştirmesi

Daha kaliteli PDF çıktıları için mPDF kütüphanesini kurun:

### Composer ile (Önerilen):
```bash
composer require mpdf/mpdf
```

### Manuel kurulum:
1. [mPDF GitHub](https://github.com/mpdf/mpdf/releases) adresinden indirin
2. `vendor/` klasörüne çıkarın
3. `sertifika_pdf.php` dosyasındaki yolu güncelleyin

## 🔧 Opsiyonel Ayarlar

### Email Bildirim Sistemi
1. Sistem Ayarları → Email Ayarları
2. SMTP bilgilerinizi girin:
   - **Gmail için:** smtp.gmail.com, Port: 587
   - **Outlook için:** smtp.live.com, Port: 587

### Otomatik Yedekleme
1. Cron job oluşturun:
```bash
0 2 * * * /usr/bin/php /path/to/online-sertifika/admin/backup.php
```

### SSL Sertifikası
cPanel'den ücretsiz Let's Encrypt SSL sertifikası aktifleştirin.

## 🛠️ Sorun Giderme

### Yaygın Sorunlar:

**1. Veritabanı bağlantı hatası:**
- Database.php dosyasındaki bilgileri kontrol edin
- Veritabanı kullanıcısının yetkilerini kontrol edin

**2. Dosya yükleme çalışmıyor:**
- Klasör izinlerini kontrol edin (777)
- PHP upload_max_filesize ayarını kontrol edin

**3. PDF oluşturulmuyor:**
- mPDF kütüphanesinin kurulu olup olmadığını kontrol edin
- Tarayıcı popup engelleyicisini kapatın

**4. Türkçe karakterler bozuk:**
- Veritabanı karakter setinin utf8 olduğunu kontrol edin
- PHP mbstring eklentisinin yüklü olduğunu kontrol edin

## 📞 Destek

Sorun yaşarsanız:
1. `error_log` dosyasını kontrol edin
2. PHP ve MySQL versiyonlarını kontrol edin
3. Tüm dosya izinlerini kontrol edin
4. İletişim sayfasından bizimle iletişime geçin

## 🔄 Güncelleme

Sistem güncellemeleri için:
1. Mevcut dosyaların yedeğini alın
2. Veritabanının yedeğini alın
3. Yeni dosyaları yükleyin
4. Database.php ayarlarını kontrol edin

---

**Not:** Bu sistem İş Güvenliği Uzmanı Halim ASA tarafından geliştirilmiştir. Kullanım sırasında herhangi bir sorunla karşılaşırsanız iletişime geçmekten çekinmeyin.