# ⚡ CodeForge-Engine

CodeForge-Engine; sıfır harici bağımlılıkla (zero-dependency) geliştirilmiş; **Saf PHP (OOP)**, **Vanilla CSS** ve **Vanilla JS** kullanan, modern, yüksek performanslı ve çok yönlü (hibrit) bir **Geliştirici Kütüphanesi ve Kod Üretim Motorudur**.

Bu projeyle, elinizde bulunan mevcut ham dosyaları (HTML/CSS/JS/PHP) terminal asistanını kullanarak sıfırdan kod yazmakla uğraşmadan profesyonel, şık ve güvenli birer web sitesine dönüştürebilirsiniz.

---

## 🧭 Hızını Seç: 3 Farklı Çalışma Modu

CodeForge-Engine, projenizin ihtiyacına göre şekil alabilen 3 farklı modda çalışabilir:

### 🎨 Mod 1: Statik Tasarım Modu (Sadece Arayüz)
Sadece modern, hızlı ve göz alıcı (glassmorphic) statik web siteleri yapmak isteyenler için.
* **Nasıl Kullanılır?** `assets/css/forge-ui.css` ve `assets/js/forge-core.js` dosyalarını HTML sayfalarınıza dahil edip hemen kodlamaya başlarsınız.

### ⚡ Mod 2: Dinamik PHP & Şablon Modu (No DB)
Master Layout (Şablonlama) ve dinamik sayfa yönlendirmesi (Routing) kullanmak isteyen ama veritabanına ihtiyaç duymayan web siteleri için.
* **Nasıl Kullanılır?** PHP sunucunuzu çalıştırıp `public/index.php` içerisine rotalarınızı yazar, views klasöründe HTML sayfalarınızı oluşturursunuz.

### 🔥 Mod 3: Full-Stack Engine (ORM, CLI & Database)
Veritabanı kullanan, üyelik sistemi (Auth), veri tabanı sorgu motoru (ActiveRecord ORM) ve terminal asistanı (CLI) içeren profesyonel web uygulamaları için.

---

## 📦 Kurulum (Tek Komutla Kurulum)

Terminalinizde (PowerShell, CMD veya Bash) aşağıdaki tek satırlık PHP komutunu çalıştırarak kütüphaneyi anında bulunduğunuz klasöre kurabilirsiniz:

```bash
php -r "eval('?>' . file_get_contents('https://raw.githubusercontent.com/RecepKandemir22/kutuphane/main/installer.php'));"
```

---

## 🛠 Terminal Asistanı: 10 Süper Güçlü Komut (`forge.php`)

Kütüphaneyi kurduktan sonra terminal üzerinden aşağıdaki komutları kullanarak tüm geliştirme sürecini otomatikleştirebilirsiniz:

### ⚙️ Sistem & Veritabanı Komutları

#### 1. Proje Başlatıcı (`init`)
Boş bir dizinde gerekli klasör yapılarını, varsayılan `.env` ayarlarını ve giriş dosyalarını otomatik kurar:
```bash
php forge.php init
```

#### 2. Kod İskeletlerini Üretme (`make:controller` & `make:model`)
Saniyeler içinde yeni denetleyici (controller) ve veritabanı modeli (ActiveRecord) sınıfları oluşturur:
```bash
php forge.php make:controller UrunlerController
php forge.php make:model Urun --table=urunler
```

#### 3. Veritabanı Yönetimi (`migrate` & `db:seed`)
Veritabanı tablolarınızı kurar ve test edebilmeniz için örnek verilerle doldurur:
```bash
php forge.php migrate
php forge.php db:seed
```

#### 4. Rota Listesi (`route:list`)
Sitede tanımlanmış tüm URL rotalarını ve yönlendirilen sınıfları terminalde listeler:
```bash
php forge.php route:list
```

#### 5. Yerel Sunucu (`server`)
Yerel web sunucusunu tek tuşla başlatır:
```bash
php forge.php server --port=8000
```

---

### 🛡️ Güvenlik (Security) Komutları

#### 6. Güvenlik Anahtarı Üretici (`sec:key`)
Uygulama oturum ve şifreleme işlemlerinde kullanılacak olan güçlü şifreleme anahtarını (APP_KEY) otomatik üretip `.env` dosyanıza kaydeder:
```bash
php forge.php sec:key
```

#### 7. Otomatik Güvenlik Taraması & Yama (`sec:audit`)
Projenizdeki güvenlik açıklarını (eval kullanımı, SQL injection riski, CSRF koruması olmayan formlar) tarar:
```bash
# Güvenlik durumunu raporlar:
php forge.php sec:audit

# Bulunan form güvenlik açıklarını kod yazmanıza gerek kalmadan otomatik olarak yamalar:
php forge.php sec:audit --fix
```

---

### 🎨 Ön Yüz (Frontend / UI) Komutları

#### 8. Kişiselleştirilmiş Arayüz Üretici (`ui:scaffold`)
Sıfırdan arayüz tasarlamak zorunda kalmadan, terminal parametreleriyle tamamen size özel renk ve tasarımlarda sayfalar üretir. 

10 farklı insan bu komutu kullandığında, verdikleri parametrelere göre **tamamen farklı ve benzersiz** siteler oluşur:
```bash
# Seçenekler:
#   <layout>: dashboard (Yönetim Paneli), auth (Giriş/Kayıt), landing (Tanıtım Sayfası)
#   --theme: glass (Buzlu cam), dark (Karanlık), light (Aydınlık), nord (Kutup)
#   --accent: indigo (Mor), emerald (Yeşil), rose (Gül), amber (Altın), cyan (Turkuaz)
#   --style: modern (Yuvarlak köşeli), minimalist (Köşeli, düz), bold (Kalın çerçeveli)

# Örnek 1 (Buzlu cam efektli, yeşil ağırlıklı modern yönetim paneli):
php forge.php ui:scaffold dashboard --theme=glass --accent=emerald --style=modern

# Örnek 2 (Karanlık tema, gül rengi vurgulu minimalist giriş sayfası):
php forge.php ui:scaffold auth --theme=dark --accent=rose --style=minimalist
```

#### 9. Hız Optimizasyonu (`ui:minify`)
Mevcut CSS veya JS dosyalarınızı sıkıştırarak sitenizin Google PageSpeed puanlarını yükseltir ve açılış hızını artırır:
```bash
php forge.php ui:minify public/assets/css/forge-ui.css
```

---

## 📄 Lisans
Bu proje açık kaynaklı olup **MIT Lisansı** altında dağıtılmaktadır. Dilediğiniz gibi projelerinizde kullanabilirsiniz.