# ⚡ CodeForge-Engine

CodeForge-Engine; sıfır harici bağımlılıkla (zero-dependency) geliştirilmiş, saf **PHP (OOP)**, **Vanilla CSS** ve **Vanilla JS** kullanan, modern ve yüksek performanslı hibrit bir **Full-Stack Web Framework**'üdür. 

Geliştiricilerin, ajansların ve SaaS üreticilerinin harici kütüphane kalabalığına (Composer, Node Modules vb.) boğulmadan; temiz, hızlı, güvenli ve bağımsız web siteleri ile yazılımlar geliştirmesi için tasarlanmıştır.

---

## 🚀 Öne Çıkan Özellikler

* **ActiveRecord ORM (ForgeORM)**: Veritabanı tablolarını otomatik eşleştiren, SQL enjeksiyonlarına karşı güvenli prepared statements kullanan zincirleme sorgu motoru.
* **Developer CLI (Forge CLI)**: Controller, Model, Migration oluşturma ve veritabanı sürüm kontrolü (Migration) işlemlerini yöneten terminal asistanı.
* **Master Layout Desteği**: Ortak arayüz parçalarını (header, navbar, footer) tek bir şablonda toplayıp çıktı tamponlama (output buffering) kullanan gelişmiş şablon yapısı.
* **Otomatik Güvenlik Duvarı (Guard)**: XSS temizleme, CSRF token doğrulama, girdi doğrulama (Validation) kuralları ve Rate Limiting (Throttling) koruması.
* **Oturum ve Yetkilendirme (Auth)**: BCRYPT şifreleme, Session yönetimi ve Çerez (Cookie) tabanlı "Beni Hatırla" özellikleri.
* **Forge UI Kit**: Koyu tema varsayılanlı, modern buzlu cam (glassmorphic) tasarımlı responsive CSS/JS komponent kütüphanesi (Toast, Modal, Tab, responsive grid).

---

## 🛠 Kurulum ve Gereksinimler

### Gereksinimler
* PHP 8.0 veya üzeri (CLI modülünün etkin olması gerekir)
* MySQL / MariaDB Veritabanı Sunucusu
* Apache veya Nginx Web Sunucusu (Örn: XAMPP, WampServer veya Herd)

### Adım 1: Projeyi Klonlayın
Projeyi web sunucunuzun kök dizinine (XAMPP kullanıyorsanız `htdocs` altına) klonlayın:
```bash
git clone https://github.com/kullaniciadi/codeforge-engine.git kutuphane
```

### Adım 2: Veritabanını Oluşturun
MySQL sunucunuzda projeniz için boş bir veritabanı oluşturun.
```sql
CREATE DATABASE kutuphane CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### Adım 3: Veritabanı Bağlantısını Yapılandırın
[core/App.php](file:///c:/xampp/htdocs/kutuphane/core/App.php) dosyasını açarak veritabanı kimlik bilgilerinizi düzenleyin:
```php
$this->config = [
    'db' => [
        'host' => 'localhost',
        'name' => 'kutuphane', // Veritabanı adınız
        'user' => 'root',       // Kullanıcı adınız
        'pass' => '',           // Şifreniz
        'charset' => 'utf8mb4'
    ],
    // ...
];
```

### Adım 4: Migrasyonları Çalıştırın
Terminalinizi açıp projenin kök dizinine gidin ve veritabanı tabloları ile örnek verileri oluşturmak için migrasyon motorunu çalıştırın:
```bash
# Windows (XAMPP varsayılan PHP konumu kullanılarak)
C:\xampp\php\php.exe forge.php migrate

# macOS / Linux veya PHP ortam değişkeni kayıtlı ise
php forge.php migrate
```

Artık tarayıcınızdan `http://localhost/kutuphane` adresine girerek projeyi çalıştırabilirsiniz!

---

## 💻 Developer CLI (Forge CLI) Kullanımı

Forge CLI, geliştirme sürecinizi hızlandıracak şablon yapıcıları barındırır. Proje kök dizininde aşağıdaki komutları kullanabilirsiniz:

### 1. Yeni Controller Oluşturma
```bash
php forge.php make:controller DashboardController
# Çıktı: app/Controllers/DashboardController.php dosyasını oluşturur.
```

### 2. Yeni ActiveRecord Model Oluşturma
```bash
# Tablo ismi otomatik plural (çoğul) eşleşir (Book -> books)
php forge.php make:model Book

# Özel tablo adı belirtmek için:
php forge.php make:model Kitap --table=kutuphane_kitaplar
# Çıktı: app/Models/Kitap.php dosyasını oluşturur.
```

### 3. Yeni Migrasyon Oluşturma
```bash
php forge.php make:migration CreateProductsTable
# Çıktı: database/migrations/m_1779434829_create_products_table.php dosyasını oluşturur.
```

### 4. Bekleyen Migrasyonları Çalıştırma
```bash
php forge.php migrate
# Çıktı: Veritabanındaki migrations tablosunu tarar ve henüz çalıştırılmamış dosyaları çalıştırır.
```

---

## 🚀 Temel Kodlama Kılavuzu

### 1. ActiveRecord Model Kullanımı
Modellerimiz [core/Model.php](file:///c:/xampp/htdocs/kutuphane/core/Model.php) sınıfından kalıtım alır.

```php
use App\Models\Book;

// 1. Tüm kayıtları getirme
$books = Book::all();
foreach ($books as $book) {
    echo $book->title . ' - ' . $book->author;
}

// 2. ID'ye göre tek bir kayıt bulma
$book = Book::find(1);
if ($book) {
    // Özelliği güncelleme
    $book->available_copies = 4;
    $book->save(); // Güncelleme sorgusunu tetikler
}

// 3. Yeni kayıt ekleme
$newBook = new Book();
$newBook->title = 'Clean Code';
$newBook->author = 'Robert C. Martin';
$newBook->isbn = '9780132350884';
$newBook->category = 'Software';
$newBook->total_copies = 5;
$newBook->available_copies = 5;
$newBook->save(); // Insert sorgusunu tetikler ve ID atar

// 4. İlişkili Zincirleme Sorgu Yapma
$activeBooks = Book::query()
    ->where('category', '=', 'Software')
    ->where('available_copies', '>', 0)
    ->orderBy('title', 'ASC')
    ->get();
```

### 2. Controller ve Görünüm (View) Yönetimi
Controller'lar görünümleri yüklerken parametreleri otomatik extract eder ve bunları opsiyonel bir şablon (layout) ile sarmalayabilir:

**Controller Yapısı (`app/Controllers/HomeController.php`):**
```php
namespace App\Controllers;

use Forge\Core\Controller;
use App\Models\Book;

class HomeController extends Controller {
    public function index() {
        $books = Book::all();
        
        // views/home.php dosyasını, views/layouts/app.php şablonu içinde render eder.
        return $this->view('home', [
            'pageTitle' => 'Anasayfa',
            'books' => $books
        ], 'app');
    }
}
```

**Layout Yapısı (`views/layouts/app.php`):**
```html
<!DOCTYPE html>
<html>
<head>
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="assets/css/forge-ui.css">
</head>
<body>
    <nav class="forge-navbar">...</nav>
    
    <!-- İçerik buraya enjekte edilir -->
    <main><?= $content ?></main> 

    <script src="assets/js/forge-core.js"></script>
</body>
</html>
```

---

## 🎨 Forge UI Kit Kullanımı

Arayüz kütüphanesi saf CSS ve JS ile optimize edilmiştir. Harici hiçbir CSS framework'üne ihtiyaç duymaz.

### CSS Komponentleri (`forge-ui.css`)
* **Responsive Grid**: `.forge-grid` sınıfı altına `.cols-1`, `.cols-2`, `.cols-3` sınıfları eklenerek kullanılır.
* **Kartlar**: `.forge-card` ile modern cam efekti (glassmorphism) sunar.
* **Tablolar**: `.forge-table-responsive` ve `.forge-table` ile duyarlı, responsive tablolar.
* **Formlar**: `.forge-form-group`, `.forge-label`, `.forge-input` yapıları ile modern giriş alanları.
* **Rozetler**: `.forge-badge` `.forge-badge-success` vb. ile durum etiketleri.

### JavaScript Metotları (`forge-core.js`)
* **Toast Bildirimi Gönderme**:
  ```javascript
  Forge.toast('İşlem başarıyla tamamlandı!', 'success', 3000);
  Forge.toast('Hata oluştu!', 'danger');
  ```
* **Modal Açma/Kapatma**:
  ```javascript
  Forge.modal('modal-id', 'show'); // Açar
  Forge.modal('modal-id', 'hide'); // Kapatır
  ```
* **Form Validasyonu**:
  ```javascript
  // input etiketlerinde 'required' veya type='email' olan alanları denetler.
  const isValid = Forge.validateForm(document.getElementById('my-form'));
  ```

---

## 🔒 Güvenlik (Guard)
Framework, SQL Injection engellemenin yanında [core/Guard.php](file:///c:/xampp/htdocs/kutuphane/core/Guard.php) aracılığıyla şu güvenlik özelliklerini sunar:

* **CSRF Koruması**: Formlarınızın içerisine otomatik CSRF alanı ekleyebilirsiniz:
  ```php
  <form action="/save" method="POST">
      <?= $guard->csrfField() ?>
      ...
  </form>
  ```
* **XSS Filtreleme**: Gelen istek verilerini otomatik sterilize eder:
  ```php
  $cleanPost = $guard->sanitize($_POST);
  ```
* **Rate Limiting (İstek Sınırlandırma)**: Brute-force saldırılarını engellemek için IP veya sayfa bazlı istek kısıtlama ekleyebilirsiniz:
  ```php
  // Aynı rota için dakikada maksimum 60 isteğe izin ver
  $guard->throttle('api_route', 60, 60);
  ```

---

## 📄 Lisans
Bu proje MIT Lisansı altında açık kaynak olarak sunulmaktadır. Dilediğiniz gibi kullanıp geliştirebilirsiniz.
```

Keyfini çıkarın! ⚡ CodeForge-Engine ile hızlı ve hafif kalın.