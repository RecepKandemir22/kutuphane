# ⚡ CodeForge-Engine

CodeForge-Engine; sıfır harici bağımlılıkla (zero-dependency) geliştirilmiş; **Saf PHP (OOP)**, **Vanilla CSS** ve **Vanilla JS** kullanan, modern, yüksek performanslı ve çok yönlü (hibrit) bir **Web Geliştirme Kütüphanesidir**.

Bu projeyle ister **sadece şık bir statik web sitesi (Pure HTML/CSS/JS)**, ister **veritabanı gerektirmeyen hızlı dinamik sayfalar**, isterseniz de **gelişmiş bir Full-Stack Web Uygulaması (SaaS)** üretebilirsiniz. Terminal veya veritabanı kurulumları tamamen isteğe bağlıdır (opsiyoneldir).

---

## 🧭 Hızını Seç: 3 Farklı Çalışma Modu

CodeForge-Engine, projenizin ihtiyacına göre şekil alabilen 3 farklı modda çalışabilir:

### 🎨 Mod 1: Statik Tasarım Modu (No PHP, No DB, No Terminal!)
Sadece modern, hızlı ve göz alıcı (glassmorphic) statik web siteleri yapmak isteyenler için.
* **Ne Gerekli?** Sadece bir tarayıcı (Chrome, Edge vb.) ve kod editörü.
* **Kurulum Yok:** Veritabanı ayarı, PHP kurulumu veya terminal komutları ile uğraşmazsınız.
* **Nasıl Kullanılır?** `public/assets/css/forge-ui.css` ve `public/assets/js/forge-core.js` dosyalarını statik HTML sayfalarınıza dahil edip hemen kodlamaya başlarsınız.

### ⚡ Mod 2: Dinamik PHP & Şablon Modu (No DB, No Terminal!)
Master Layout (Şablonlama) ve dinamik sayfa yönlendirmesi (Routing) kullanmak isteyen ama veritabanına ihtiyaç duymayan web siteleri için.
* **Ne Gerekli?** PHP 8.0+ (Örn: XAMPP).
* **Kurulum Yok:** Veritabanı kurmanıza veya terminale komut yazmanıza gerek yoktur.
* **Nasıl Kullanılır?** PHP sunucunuzu çalıştırıp `public/index.php` içerisine rotalarınızı yazar, views klasöründe HTML sayfalarınızı oluşturursunuz.

### 🔥 Mod 3: Full-Stack Engine (ORM, CLI & Database)
Veritabanı kullanan, kullanıcı üyelik sistemi (Auth), veri tabanı sorgu motoru (ActiveRecord ORM) ve terminal asistanı (CLI) içeren profesyonel web uygulamaları ve SaaS projeleri için.
* **Ne Gerekli?** PHP 8.0+, MySQL ve terminal erişimi.
* **Nasıl Kullanılır?** Veritabanı bilgilerinizi girip terminalden `php forge.php migrate` komutuyla tabloları kurarsınız.

---

## 📦 Kurulum Seçenekleri (Tek Komutla Kurulum & CDN)

CodeForge-Engine'i projenize dahil etmek çok kolaydır. İhtiyacınıza göre aşağıdaki yöntemlerden birini seçebilirsiniz:

### 1. Tek Komutla CLI Kurulumu (Önerilen 🚀)
Boş bir proje klasörü oluşturun, içine girin ve terminalinizde (PowerShell, CMD veya Bash) aşağıdaki tek satırlık PHP komutunu çalıştırın:

```bash
php -r "eval(file_get_contents('https://raw.githubusercontent.com/RecepKandemir22/kutuphane/main/installer.php'));"
```

Bu komut:
* Gerekli PHP sürüm ve eklentilerini kontrol eder.
* Framework dosyalarını otomatik olarak klasörünüze indirir.
* Veritabanı ve uygulama ayarlarını terminalden interaktif olarak alıp `.env` dosyanızı oluşturur.

### 2. CDN Desteği (Arayüz Tasarımı İçin)
Projenize hiçbir dosya indirmeden, doğrudan CDN linklerini kullanarak HTML sayfalarınızı tasarlayabilirsiniz:

```html
<!-- CodeForge CSS (Head kısmına) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/RecepKandemir22/kutuphane@main/public/assets/css/forge-ui.css">

<!-- CodeForge JS (Body sonuna) -->
<script src="https://cdn.jsdelivr.net/gh/RecepKandemir22/kutuphane@main/public/assets/js/forge-core.js"></script>
```

### 3. Git Klonlama İle Kurulum
```bash
git clone https://github.com/RecepKandemir22/kutuphane.git proje-adi
```

---

## 🚀 Hızlı Başlangıç Kılavuzları

### 🎨 Mod 1 (Statik HTML/CSS/JS) Başlangıç
Sadece arayüz kütüphanesini kullanmak için HTML dosyanızın `<head>` kısmına CSS'i, `<body>` sonuna ise JS dosyasını ekleyin:

```html
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Statik Web Sitem</title>
    <!-- CDN veya İndirdiğiniz CSS Dosyası -->
    <link rel="stylesheet" href="forge-ui.css">
</head>
<body>
    <div class="forge-container mt-8">
        <div class="forge-card">
            <h1>Merhaba Dünya!</h1>
            <p>CodeForge-Engine arayüz bileşenleri çalışıyor.</p>
            <button class="forge-btn forge-btn-primary" onclick="Forge.toast('Toast Bildirimi!', 'success')">Bildirim Gönder</button>
        </div>
    </div>

    <!-- CDN veya İndirdiğiniz JS Dosyası -->
    <script src="forge-core.js"></script>
</body>
</html>
```

---

### ⚡ Mod 2 (Dinamik PHP / Veritabanı Olmadan) Başlangıç
1. Projeyi XAMPP `htdocs` klasörüne atın.
2. `public/index.php` dosyasını açıp rotalarınızı (sayfalarınızı) tanımlayın:
```php
$router->get('/hakkimizda', function() {
    echo "Hakkımızda Sayfası İçeriği";
});
```
3. Dosyaları şablonlarla (layout) render etmek için Controller sınıflarını kullanabilirsiniz.

---

### 🔥 Mod 3 (Full-Stack / ORM & Veritabanı) Başlangıç
1. Boş bir MySQL veritabanı oluşturun.
2. Proje ana dizinindeki `.env` dosyasını düzenleyerek veritabanı bağlantı bilgilerinizi girin (`DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).
3. Terminalde proje ana dizinine gidip tabloları otomatik oluşturun:
```bash
# Windows XAMPP kullanıyorsanız:
C:\xampp\php\php.exe forge.php migrate

# Mac / Linux veya ortam değişkenleriniz yüklü ise:
php forge.php migrate
```

---

## 🎨 Yerleşik Arayüz Bileşenleri (Forge UI Kit)

Dışarıdan Bootstrap, Tailwind veya jQuery yüklemenize gerek yok. `forge-ui.css` ve `forge-core.js` ile gelen bazı özellikler:

* **Buzlu Cam Efekti (Glassmorphism):** Modern `.forge-card`, `.forge-navbar` ve `.forge-modal` tasarımları.
* **Responsive Grid:** `.forge-grid.cols-3` gibi esnek grid sistemleri.
* **Dinamik Modallar:** Tek tıkla açılıp kapanan pencereler.
  ```javascript
  Forge.modal('modal-id', 'show'); // Modalı açar
  ```
* **Toast Bildirimleri:** Modern animasyonlu bildirim pencereleri.
  ```javascript
  Forge.toast('Kayıt Başarılı!', 'success');
  ```
* **Otomatik Form Kontrolü:** Boş bırakılan alanları kırmızı uyarılarla denetler.
  ```javascript
  Forge.validateForm(document.getElementById('my-form'));
  ```

---

## 🛠 Developer CLI (Forge CLI) Komutları (Opsiyonel)

Mod 3'te uygulama geliştirirken kod yazımını hızlandırmak için terminalden şu komutları kullanabilirsiniz:

```bash
# Yeni bir Controller oluşturur:
php forge.php make:controller BookController

# Yeni bir ActiveRecord veritabanı modeli oluşturur:
php forge.php make:model Book

# Veritabanında tablo oluşturmak için migrasyon dosyası yaratır:
php forge.php make:migration CreateUsersTable
```

---

## 🔒 Güvenlik Özellikleri (Guard)
* **XSS Temizliği:** Gelen input verileri otomatik arındırılır.
* **CSRF Koruması:** `<?= $guard->csrfField() ?>` ile formlarınız güvenceye alınır.
* **Rate Limiter (Hız Sınırlandırıcı):** Sayfalara dakikada yapılabilecek maksimum istek limiti belirlenebilir.

---

## 📄 Lisans
Bu proje açık kaynaklı olup **MIT Lisansı** altında dağıtılmaktadır. Dilediğiniz gibi ticari veya kişisel projelerinizde kullanabilirsiniz.