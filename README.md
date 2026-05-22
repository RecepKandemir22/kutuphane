# 🛡️ ForgeForm & Shield

**ForgeForm & Shield**, mevcut web sitelerinizin form yapılarını bozmadan asenkron **AJAX** formlarına dönüştüren; aynı zamanda arka planda **CSRF**, **Matematiksel Captcha**, **Rate Limit (Anti-Flood)** ve gelişmiş **XSS temizliği** sağlayan sıfır bağımlılıklı (**zero-dependency**), ultra hafif bir kütüphanedir.

Kurulumu tamamen eklemeli (additive) olup, sitenizdeki mevcut hiçbir dosyayı bozmaz veya silmez.

---

## 🚀 Hızlı Kurulum (Tek Komut)

Terminalinizi açıp projenizin kök dizinine giderek aşağıdaki tek satırlık PHP komutunu çalıştırmanız yeterlidir:

```bash
php -r "eval('?>' . file_get_contents('https://raw.githubusercontent.com/RecepKandemir22/kutuphane/main/installer.php'));"
```

Bu komut projenizin kök dizinine şu 3 dosyayı güvenli bir şekilde indirir:
1. `ForgeShield.php` (Arka yüz güvenlik ve doğrulama motoru)
2. `forge-form.js` (Ön yüz asenkron Ajax & Captcha enjektör motoru)
3. `forge-form.css` (Captcha stilleri, spinner ve bildirim/toast tasarımları)

---

## 🛠️ Entegrasyon Kılavuzu

### 1. Ön Yüz Entegrasyonu (HTML / CSS / JS)

Güzelleştirmek ve koruma altına almak istediğiniz formunuza `forge-form` sınıfını eklemeniz ve ilgili script/stil dosyalarını sayfanıza bağlamanız yeterlidir:

```html
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <!-- Paket stil dosyasını ekleyin -->
    <link rel="stylesheet" href="forge-form.css">
</head>
<body>

    <!-- Sadece "forge-form" sınıfını eklemeniz yeterlidir -->
    <form action="contact-process.php" method="POST" class="forge-form">
        <div class="forge-form-group">
            <label for="email">E-Posta Adresi</label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="forge-form-group">
            <label for="message">Mesajınız</label>
            <textarea id="message" name="message" required></textarea>
        </div>
        
        <button type="submit">Gönder</button>
    </form>

    <!-- Paket script dosyasını ekleyin -->
    <script src="forge-form.js" defer></script>
</body>
</html>
```

### 2. Arka Yüz Entegrasyonu (PHP)

Form verilerini post ettiğiniz PHP dosyanızın (`action` dosyasının) en başına paketi dahil edin ve doğrulamayı çalıştırın:

```php
<?php
// Session başlatılmamışsa başlatılması gerekir
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Güvenlik sınıfını dahil edin
require_once 'ForgeShield.php';

// 2. Doğrulamayı başlatın (Anti-Flood, CSRF, Captcha ve XSS Temizliği)
// validate() metodu hata durumunda doğrudan JSON hatası döner ve betiği durdurur.
// 20 saniye rate limit sınırı uygular (20 saniyede bir form gönderme izni).
$cleanData = ForgeShield::validate(20);

// 3. Doğrulama başarılı ise verileriniz artık tertemiz ve güvenlidir!
$email = $cleanData['email'];
$message = $cleanData['message'];

// Mail gönderme veya veritabanına ekleme işlemlerinizi burada yapabilirsiniz.
// ...

// Ön yüze başarı mesajı dönün
ForgeShield::responseJSON('success', 'Mesajınız başarıyla ve güvenle iletildi!');
```

---

## 🛡️ Güvenlik ve Koruma Özellikleri

### 1. Çift Tıklama Koruması (Anti Double-Submit)
Ziyaretçi gönder butonuna bastığı an buton kilitlenir (`disabled` olur) ve buton içeriği "Gönderiliyor... ⏳" durumuna geçer. Bu sayede sunucuya peş peşe istek (spam request) gönderilemez.

### 2. Flood Koruması (Rate Limiting)
Aynı kullanıcının sunucuyu şişirmesini engellemek için `$_SESSION` tabanlı zaman denetimi yapılır. Kullanıcı belirlenen süreden (örn. 20 saniye) önce ikinci kez göndermeye çalışırsa sistem işlemi durdurarak ön yüze kaç saniye beklemesi gerektiğini bildiren bir hata döner.

### 3. Matematiksel Captcha
Spam botlarını engellemek için formlara dinamik ve hafif matematiksel doğrulamalar (Örn: `4 + 2 = ?`) enjekte edilir. Sonuç arka planda kontrol edilerek doğrulanır.

### 4. CSRF Koruması
Olası dış kaynaklı sahte form post isteklerini engellemek için tarayıcı oturumuna özel üretilen şifreli anahtar (CSRF Token) formla birlikte post edilerek sunucuda doğrulanır.

### 5. XSS Filtresi
Form üzerinden gelen tüm veriler otomatik olarak `strip_tags` ve `htmlspecialchars` filtrelerinden geçirilerek zararlı HTML/JS kodlarından arındırılır.

---

## 📄 Lisans
Bu proje **MIT Lisansı** altında dağıtılmaktadır. Dilediğiniz gibi ticari ve bireysel projelerinizde kullanabilirsiniz.