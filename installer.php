<?php
/**
 * ForgeForm & Shield Installer
 * Zero-dependency console script that safely downloads the package files 
 * into the current working directory without overwriting/deleting any other file.
 */

define('COLOR_SUCCESS', "\033[32m");
define('COLOR_INFO', "\033[36m");
define('COLOR_WARNING', "\033[33m");
define('COLOR_ERROR', "\033[31m");
define('COLOR_RESET', "\033[0m");
define('COLOR_BOLD', "\033[1m");

if (php_sapi_name() !== 'cli') {
    die("This installer can only be run from the command line.\n");
}

echo COLOR_BOLD . COLOR_INFO;
echo "  ______                               ______                                \n";
echo " / _____)                             |  _____)                               \n";
echo "| /  ___  ___   ____ ____  ____ ____  | |____  ___   ____ ____                \n";
echo "| | (___)/ _ \ / ___) _  |/ _  )  _ \ |  ____)/ _ \ / ___)    \\               \n";
echo "| \\____/| |_| | |  ( (_| ( (/ /| | | || |    | |_| | |   | | | |              \n";
echo " \\_____/ \\___/|_|   \\___ |\\____)_| |_||_|     \\___/|_|   |_|_|_|              \n";
echo "                   (_____|                                                    \n";
echo "                     ⚡ ForgeForm & Shield Installer ⚡                      \n";
echo COLOR_RESET . PHP_EOL;

function prompt($question, $default = '') {
    $suffix = $default !== '' ? " [{$default}]" : '';
    echo COLOR_BOLD . $question . $suffix . ": " . COLOR_RESET;
    $input = trim(fgets(STDIN));
    return $input === '' ? $default : $input;
}

function logConsole($type, $msg) {
    $color = '';
    $prefix = '';
    switch ($type) {
        case 'success':
            $color = COLOR_SUCCESS;
            $prefix = '✔ SUCCESS: ';
            break;
        case 'info':
            $color = COLOR_INFO;
            $prefix = 'ℹ INFO: ';
            break;
        case 'warning':
            $color = COLOR_WARNING;
            $prefix = '⚠ WARNING: ';
            break;
        case 'error':
            $color = COLOR_ERROR;
            $prefix = '✖ ERROR: ';
            break;
        default:
            $color = COLOR_RESET;
            break;
    }
    echo $color . $prefix . $msg . COLOR_RESET . PHP_EOL;
}

function injectCodeIntoFiles($formFile, $postFile) {
    global $currentDir;
    
    if (!empty($formFile)) {
        $formFilePath = $currentDir . DIRECTORY_SEPARATOR . $formFile;
        if (!file_exists($formFilePath)) {
            logConsole('error', "Hata: '{$formFile}' dosyası bulunamadı.");
            return false;
        }
        
        $formContent = file_get_contents($formFilePath);
        $modified = false;
        
        // 1. Inject CSS link inside <head> if not exists
        if (strpos($formContent, 'forge-form.css') === false) {
            $cssTag = '    <link rel="stylesheet" href="forge-shield/forge-form.css">' . PHP_EOL;
            if (strpos($formContent, '</head>') !== false) {
                $formContent = str_replace('</head>', $cssTag . '</head>', $formContent);
                $modified = true;
                logConsole('success', "'{$formFile}' içerisine CSS stil bağlantısı eklendi.");
            } elseif (strpos($formContent, '<head>') !== false) {
                $formContent = str_replace('<head>', '<head>' . PHP_EOL . $cssTag, $formContent);
                $modified = true;
                logConsole('success', "'{$formFile}' içerisine CSS stil bağlantısı eklendi.");
            }
        } else {
            logConsole('info', "'{$formFile}' içerisinde CSS zaten mevcut.");
        }
        
        // 2. Inject JS script tag before </body> if not exists
        if (strpos($formContent, 'forge-form.js') === false) {
            $jsTag = '    <script src="forge-shield/forge-form.js" defer></script>' . PHP_EOL;
            if (strpos($formContent, '</body>') !== false) {
                $formContent = str_replace('</body>', $jsTag . '</body>', $formContent);
                $modified = true;
                logConsole('success', "'{$formFile}' içerisine JS script bağlantısı eklendi.");
            } else {
                $formContent .= PHP_EOL . $jsTag;
                $modified = true;
                logConsole('success', "'{$formFile}' sonuna JS script bağlantısı eklendi.");
            }
        } else {
            logConsole('info', "'{$formFile}' içerisinde JS zaten mevcut.");
        }
        
        // 3. Inject class="forge-form" into <form> tags
        if (strpos($formContent, '<form') !== false) {
            // Find all <form ...> tags
            $pattern = '/<form([^>]*?)>/i';
            $formContent = preg_replace_callback($pattern, function($matches) use (&$modified) {
                $attributes = $matches[1];
                
                // If it already has forge-form class, do nothing
                if (preg_match('/class=["\'][^"\']*?forge-form[^"\']*?["\']/i', $attributes)) {
                    return $matches[0];
                }
                
                $modified = true;
                // If it already has some other class
                if (preg_match('/class=["\']([^"\']*?)["\']/i', $attributes, $classMatches)) {
                    $oldClassAttr = $classMatches[0];
                    $newClassAttr = 'class="' . trim($classMatches[1]) . ' forge-form"';
                    $newAttributes = str_replace($oldClassAttr, $newClassAttr, $attributes);
                    return "<form{$newAttributes}>";
                } else {
                    // No class attribute, append it
                    return "<form class=\"forge-form\"{$attributes}>";
                }
            }, $formContent);
            
            if ($modified) {
                logConsole('success', "'{$formFile}' üzerindeki form etiketlerine 'forge-form' sınıfı enjekte edildi.");
            }
        }
        
        if ($modified) {
            file_put_contents($formFilePath, $formContent);
        }
    }
    
    if (!empty($postFile)) {
        $postFilePath = $currentDir . DIRECTORY_SEPARATOR . $postFile;
        if (!file_exists($postFilePath)) {
            logConsole('error', "Hata: '{$postFile}' dosyası bulunamadı.");
            return false;
        }
        
        $postContent = file_get_contents($postFilePath);
        $modified = false;
        
        // 1. Inject security validation block into PHP file
        if (strpos($postContent, 'ForgeShield::validate') === false) {
            $securityBlock = <<<PHP

// --- ForgeForm & Shield Güvenlik Entegrasyonu ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'forge-shield/ForgeShield.php';
\$cleanData = ForgeShield::validate(20);
// ------------------------------------------------

PHP;
            if (strpos($postContent, '<?php') !== false) {
                // Insert right after <?php
                $pos = strpos($postContent, '<?php');
                $postContent = substr_replace($postContent, "<?php" . $securityBlock, $pos, 5);
                $modified = true;
                logConsole('success', "'{$postFile}' başına güvenlik ve doğrulama kodları entegre edildi.");
            } else {
                // Prepend raw PHP tags
                $postContent = "<?php" . $securityBlock . "?>" . PHP_EOL . $postContent;
                $modified = true;
                logConsole('success', "'{$postFile}' başına PHP etiketleriyle birlikte güvenlik kodları entegre edildi.");
            }
        } else {
            logConsole('info', "'{$postFile}' içerisinde ForgeShield doğrulaması zaten mevcut.");
        }
        
        if ($modified) {
            file_put_contents($postFilePath, $postContent);
        }
    }
    
    return true;
}

$currentDir = getcwd();

// CLI Entegrasyon Komutu Kontrolü
if (isset($argv[1]) && $argv[1] === 'integrate') {
    $formFile = isset($argv[2]) ? $argv[2] : '';
    $postFile = isset($argv[3]) ? $argv[3] : '';
    
    if (empty($formFile) && empty($postFile)) {
        echo COLOR_BOLD . COLOR_ERROR . "Hata: En az bir dosya adı girmelisiniz.\n" . COLOR_RESET;
        echo "Kullanım: php installer.php integrate [form_dosyasi.php] [post_dosyasi.php]\n";
        exit(1);
    }
    
    injectCodeIntoFiles($formFile, $postFile);
    exit(0);
}

echo "Kurulum Dizini (Installation Directory): " . COLOR_BOLD . $currentDir . COLOR_RESET . PHP_EOL;
$confirm = prompt("Bu dizine ForgeForm & Shield dosyalarını kurmak istiyor musunuz? (y/n)", "y");

if (strtolower($confirm) !== 'y' && strtolower($confirm) !== 'yes') {
    logConsole('warning', "Kurulum iptal edildi (Installation cancelled).");
    exit(0);
}

// 1. Create forge-shield sub-directory if it doesn't exist
$subDir = $currentDir . DIRECTORY_SEPARATOR . 'forge-shield';
if (!is_dir($subDir)) {
    if (!mkdir($subDir, 0755, true)) {
        logConsole('error', "Hata: 'forge-shield' klasörü oluşturulamadı. Yazma izinlerini kontrol edin.");
        exit(1);
    }
    logConsole('success', "'forge-shield' klasörü oluşturuldu.");
}

// Files to download
$files = [
    'ForgeShield.php' => 'https://raw.githubusercontent.com/RecepKandemir22/kutuphane/main/forge-shield/ForgeShield.php',
    'forge-form.js'   => 'https://raw.githubusercontent.com/RecepKandemir22/kutuphane/main/forge-shield/forge-form.js',
    'forge-form.css'  => 'https://raw.githubusercontent.com/RecepKandemir22/kutuphane/main/forge-shield/forge-form.css'
];

logConsole('info', "Uyum kontrolü yapılıyor...");
if (!extension_loaded('curl') && !ini_get('allow_url_fopen')) {
    logConsole('error', "Hata: PHP curl eklentisi veya 'allow_url_fopen' ayarı aktif olmalıdır.");
    exit(1);
}

logConsole('success', "Kontroller tamamlandı. Dosyalar GitHub üzerinden indiriliyor...");

$successCount = 0;
foreach ($files as $fileName => $url) {
    $targetPath = $subDir . DIRECTORY_SEPARATOR . $fileName;
    
    // Check if target file already exists
    if (file_exists($targetPath)) {
        logConsole('warning', "'forge-shield/{$fileName}' dosyası zaten mevcut. Üzerine yazılmasını istiyor musunuz? (y/n)");
        $overwrite = prompt("Üzerine yaz? (Overwrite?)", "y");
        if (strtolower($overwrite) !== 'y' && strtolower($overwrite) !== 'yes') {
            logConsole('info', "'forge-shield/{$fileName}' kurulumu atlandı.");
            continue;
        }
    }
    
    logConsole('info', "İndiriliyor: forge-shield/{$fileName}...");
    
    $content = false;
    // Method 1: Curl
    if (extension_loaded('curl')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'ForgeForm-Installer/1.0');
        $content = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $content = false;
        }
    }
    
    // Method 2: file_get_contents (fallback)
    if ($content === false && ini_get('allow_url_fopen')) {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: ForgeForm-Installer/1.0\r\n"
            ]
        ];
        $context = stream_context_create($options);
        $content = @file_get_contents($url, false, $context);
    }
    
    if ($content === false) {
        logConsole('error', "Hata: 'forge-shield/{$fileName}' dosyası indirilemedi. İnternet bağlantınızı kontrol edin.");
        continue;
    }
    
    if (file_put_contents($targetPath, $content) !== false) {
        logConsole('success', "Kuruldu: forge-shield/{$fileName}");
        $successCount++;
    } else {
        logConsole('error', "Hata: 'forge-shield/{$fileName}' dosyası yerel diske yazılamadı.");
    }
}

// 2. Create FORGE_SHIELD_GUIDE.txt file in the root of the project
$guidePath = $currentDir . DIRECTORY_SEPARATOR . 'FORGE_SHIELD_GUIDE.txt';
$guideContent = <<<EOT
================================================================================
🛡️ FORGEFORM & SHIELD - ENTEGRASYON KILAVUZU
================================================================================

ForgeForm & Shield başarıyla kuruldu! Bu kılavuz, kütüphaneyi mevcut sitenize
nasıl entegre edeceğinizi adım adım açıklar.

--------------------------------------------------------------------------------
1. ADIM: HTML FORM ENTEGRASYONU
--------------------------------------------------------------------------------
Korumak ve AJAX ile göndermek istediğiniz formunuza "forge-form" class'ını ekleyin.

Örnek:
<form action="post-islemi.php" method="POST" class="forge-form">
    <!-- Form alanlarınız (Ad, Soyad, Mesaj vs.) -->
    <input type="text" name="name" required>
    <button type="submit">Gönder</button>
</form>

--------------------------------------------------------------------------------
2. ADIM: STİL VE SCRIPT DOSYALARININ EKLENMESİ
--------------------------------------------------------------------------------
Formun bulunduğu HTML sayfasının <head> bölümüne CSS dosyasını, sayfanın sonuna
(veya defer niteliği ile) JS dosyasını ekleyin:

<!-- head etiketleri arasına -->
<link rel="stylesheet" href="forge-shield/forge-form.css">

<!-- body kapanışından hemen önce -->
<script src="forge-shield/forge-form.js" defer></script>

--------------------------------------------------------------------------------
3. ADIM: BACKEND PHP GÜVENLİK KONTROLÜ
--------------------------------------------------------------------------------
Form verilerinin post edildiği PHP dosyanızın (formun action kısmındaki dosya)
en başına şu PHP kodlarını ekleyin:

<?php
// PHP session başlatılmadıysa başlatın (CSRF ve Captcha kontrolü için gereklidir)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Güvenlik sınıfını dahil edin
require_once 'forge-shield/ForgeShield.php';

// 2. Doğrulamayı çalıştırın (CSRF, Matematiksel Captcha, XSS Temizliği ve Rate Limit)
// validate(20) ifadesi: aynı kullanıcının 20 saniyede en fazla 1 istek atabilmesini sağlar (Anti-Flood).
// Hata durumunda otomatik olarak JSON yanıtı döner ve script çalışmasını durdurur.
\$cleanData = ForgeShield::validate(20);

// 3. Artık \$cleanData içindeki verileriniz temizlenmiş ve güvenlidir.
\$name = \$cleanData['name'];

// Buradan sonra mail gönderme veya veritabanı kayıt işlemlerinizi yapabilirsiniz.
// İşlem başarılı olduğunda ön yüze başarı bildirimini şu şekilde gönderin:
ForgeShield::responseJSON('success', 'Mesajınız başarıyla ve güvenle gönderildi!');
?>

--------------------------------------------------------------------------------
4. ADIM: GERÇEK E-POSTA (SMTP) AYARLARI
--------------------------------------------------------------------------------
Formlardan gönderilen mesajların gerçekten e-postanıza ulaşması için projenizin 
kök dizininde otomatik olarak oluşturulan "config.php" dosyasını yapılandırın.

Gmail SMTP için gerekli sunucu, port, protokol vb. tüm teknik ayarlar 
otomatik olarak tanımlanmıştır ve değiştirmenize gerek yoktur.

1. "config.php" dosyasını açın.
2. Gerçek gönderimi aktif etmek için "SMTP_DEVELOPER_MODE" ayarını "false" yapın:
   define('SMTP_DEVELOPER_MODE', false);
3. Üst kısımdaki SADECE 3 ALANI doldurun:
   - SMTP_USER: Gönderen Gmail adresiniz (Oturum açacak hesap)
   - SMTP_PASS: Google hesabınızdan alacağınız 16 haneli "Uygulama Şifresi"
   - SMTP_TO_EMAIL: Mesajların gönderileceği alıcı (hedef) e-posta adresi.

--------------------------------------------------------------------------------
🛡️ Hangi Korumalar Sağlanıyor?
--------------------------------------------------------------------------------
1. CSRF Koruması: Dış sitelerden gelen sahte form gönderimlerini engeller.
2. Matematiksel Captcha: Spam botlarını ve otomatik form doldurucuları durdurur.
3. Rate Limiting (Anti-Flood): Belirtilen sürede (örn. 20 sn) tek gönderim limiti uygular.
4. XSS Temizliği: Gelen tüm verilere strip_tags ve htmlspecialchars uygular.
5. Çift Tıklama Engeli: Gönder butonunu işlem süresince kilitler ve spinner gösterir.

ForgeForm & Shield'ı tercih ettiğiniz için teşekkür ederiz!
================================================================================
EOT;

if (file_put_contents($guidePath, $guideContent) !== false) {
    logConsole('success', "Entegrasyon kılavuzu oluşturuldu: FORGE_SHIELD_GUIDE.txt");
} else {
    logConsole('warning', "Entegrasyon kılavuzu dosyası (FORGE_SHIELD_GUIDE.txt) oluşturulamadı.");
}

// 3. Create default config.php file if it doesn't exist
$configPath = $currentDir . DIRECTORY_SEPARATOR . 'config.php';
if (!file_exists($configPath)) {
    $configContent = <<<PHP
<?php
/**
 * ForgeForm & Shield - SMTP Yapılandırma Dosyası (Gmail Uyumlu)
 * 
 * E-postaların gerçekten gönderilebilmesi için aşağıdaki 3 bilgiyi doldurmanız yeterlidir.
 * Gmail SMTP ayarları (Sunucu, Port, Güvenlik) otomatik olarak tanımlanmıştır.
 */

// Geliştirici Modu (Test Modu)
// TRUE iken: E-posta gönderimi simüle edilir (tasarım testi için).
// FALSE iken: Gerçek e-posta gönderimi aktif olur.
define('SMTP_DEVELOPER_MODE', true);

// =========================================================================
// ✍️ DOLDURMANIZ GEREKEN ALANLAR (Sadece bu 3 alanı düzenleyin)
// =========================================================================

// 1. Gmail Adresiniz (Mail göndermek için kullanılacak hesap)
define('SMTP_USER', 'sizin-epostaniz@gmail.com');

// 2. Gmail Uygulama Şifreniz (16 Haneli Şifre)
// Google Hesabınız > Güvenlik > Uygulama Şifreleri bölümünden oluşturulan 16 haneli şifredir.
// Örn: 'abcd efgh ijkl mnop'
define('SMTP_PASS', 'xxxx xxxx xxxx xxxx');

// 3. Mesajların İletileceği Alıcı E-posta Adresi
// İletişim formundan gönderilen mesajların ulaşmasını istediğiniz yetkili e-posta adresi.
define('SMTP_TO_EMAIL', 'hedef-eposta@gmail.com');


// =========================================================================
// ⚙️ GMAIL SMTP SABİT AYARLARI (Bu bölüme dokunmanıza gerek yoktur)
// =========================================================================
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);

// Gönderici Başlık Bilgileri
define('SMTP_FROM_EMAIL', SMTP_USER); // Gönderen e-posta (Oturum açan hesap ile aynı olmalıdır)
define('SMTP_FROM_NAME', 'ForgeForm İletişim');
PHP;

    if (file_put_contents($configPath, $configContent) !== false) {
        logConsole('success', "Varsayılan SMTP yapılandırma dosyası oluşturuldu: config.php");
    }
}

echo PHP_EOL;
echo COLOR_BOLD . COLOR_INFO . "--------------------------------------------------------------------\n";
echo "🔄 Otomatik Entegrasyon Sihirbazı (Auto-Integration Wizard)\n";
echo "--------------------------------------------------------------------\n" . COLOR_RESET;
$auto = prompt("Mevcut dosyalarınıza entegrasyon kodlarını otomatik enjekte etmek ister misiniz? (y/n)", "y");
if (strtolower($auto) === 'y' || strtolower($auto) === 'yes') {
    logConsole('info', "Dizindeki dosyalar form etiketi için taranıyor...");
    
    // Scan current directory for php and html files
    $detectedForms = [];
    $allFiles = glob("*.{php,html}", GLOB_BRACE);
    if (!empty($allFiles)) {
        foreach ($allFiles as $file) {
            $fileName = basename($file);
            // Skip installer, config and directory paths
            if ($fileName === 'installer.php' || $fileName === 'config.php' || strpos($file, 'forge-shield') !== false || strpos($file, 'vendor') !== false) {
                continue;
            }
            $content = file_get_contents($file);
            if (strpos($content, '<form') !== false) {
                $detectedForms[] = $file;
            }
        }
    }
    
    if (!empty($detectedForms)) {
        logConsole('success', "Algılanan Form Dosyaları: " . implode(', ', $detectedForms));
        foreach ($detectedForms as $formFile) {
            $content = file_get_contents($formFile);
            $postFile = '';
            
            // Extract the action attribute of the form
            if (preg_match('/<form[^>]*?action=["\']([^"\']*?)["\']/i', $content, $actionMatches)) {
                $actionValue = $actionMatches[1];
                // Clean anchors or query params
                $actionValueClean = explode('?', $actionValue)[0];
                $actionValueClean = explode('#', $actionValueClean)[0];
                $actionValueClean = trim($actionValueClean);
                
                $actionPath = $currentDir . DIRECTORY_SEPARATOR . $actionValueClean;
                if (!empty($actionValueClean) && file_exists($actionPath)) {
                    $postFile = $actionValueClean;
                }
            }
            
            logConsole('info', "'{$formFile}'" . ($postFile ? " ve işlem dosyası '{$postFile}'" : "") . " için entegrasyon başlatılıyor...");
            injectCodeIntoFiles($formFile, $postFile);
        }
        echo PHP_EOL;
    } else {
        logConsole('warning', "Dizinde otomatik entegrasyon yapılabilecek form içeren bir HTML/PHP dosyası bulunamadı.");
    }
}

echo PHP_EOL;
echo COLOR_BOLD . COLOR_SUCCESS . "====================================================================\n";
echo "🎉 Kurulum Tamamlandı! ({$successCount} dosya kuruldu/güncellendi) 🎉\n";
echo "====================================================================\n" . COLOR_RESET;
echo "Sistemi kullanmaya başlamak için şu adımları izleyin:\n\n";
echo "  1. Formunuza otomatik olarak " . COLOR_BOLD . "class=\"forge-form\"" . COLOR_RESET . " eklenmiştir.\n";
echo "  2. Sayfanıza otomatik olarak CSS ve script dosyaları bağlanmıştır.\n";
echo "  3. Gerçek mail gönderimi için " . COLOR_BOLD . "config.php" . COLOR_RESET . " dosyasını düzenleyin.\n\n";
echo "💡 Kurulum detayları ve entegrasyon kodları " . COLOR_BOLD . "FORGE_SHIELD_GUIDE.txt" . COLOR_RESET . " dosyasına kaydedildi.\n";
echo "💡 Dilediğiniz zaman şu terminal komutuyla da manuel entegrasyon yapabilirsiniz:\n";
echo "   " . COLOR_BOLD . "php installer.php integrate [form_dosyasi.php] [post_dosyasi.php]" . COLOR_RESET . "\n\n";
echo "Gelişmiş Ajax Form ve Güvenlik kütüphanesini kullandığınız için teşekkürler! ⚡\n";

