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

$currentDir = getcwd();
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

echo PHP_EOL;
echo COLOR_BOLD . COLOR_SUCCESS . "====================================================================\n";
echo "🎉 Kurulum Tamamlandı! ({$successCount} dosya kuruldu/güncellendi) 🎉\n";
echo "====================================================================\n" . COLOR_RESET;
echo "Sistemi kullanmaya başlamak için şu adımları izleyin:\n\n";
echo "  1. Formunuza " . COLOR_BOLD . "class=\"forge-form\"" . COLOR_RESET . " ekleyin.\n";
echo "  2. Sayfanızın başına " . COLOR_BOLD . "require_once 'forge-shield/ForgeShield.php';" . COLOR_RESET . " ekleyin.\n";
echo "  3. HTML sayfanızın head etiketlerine stil ve script dosyalarını bağlayın:\n";
echo "     " . COLOR_BOLD . "<link rel=\"stylesheet\" href=\"forge-shield/forge-form.css\">\n";
echo "     <script src=\"forge-shield/forge-form.js\" defer></script>" . COLOR_RESET . "\n";
echo "  4. Form post verilerini işleyen dosyanızın en başında şu kontrolü yapın:\n";
echo "     " . COLOR_BOLD . "\$cleanData = ForgeShield::validate();" . COLOR_RESET . "\n\n";
echo "💡 Kurulum detayları ve entegrasyon kodları " . COLOR_BOLD . "FORGE_SHIELD_GUIDE.txt" . COLOR_RESET . " dosyasına kaydedildi.\n";
echo "Gelişmiş Ajax Form ve Güvenlik kütüphanesini kullandığınız için teşekkürler! ⚡\n";
