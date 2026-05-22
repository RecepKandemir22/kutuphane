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

// Files to download
$files = [
    'ForgeShield.php' => 'https://raw.githubusercontent.com/RecepKandemir22/kutuphane/main/ForgeShield.php',
    'forge-form.js'   => 'https://raw.githubusercontent.com/RecepKandemir22/kutuphane/main/forge-form.js',
    'forge-form.css'  => 'https://raw.githubusercontent.com/RecepKandemir22/kutuphane/main/forge-form.css'
];

logConsole('info', "Uyum kontrolü yapılıyor...");
if (!extension_loaded('curl') && !ini_get('allow_url_fopen')) {
    logConsole('error', "Hata: PHP curl eklentisi veya 'allow_url_fopen' ayarı aktif olmalıdır.");
    exit(1);
}

logConsole('success', "Kontroller tamamlandı. Dosyalar GitHub üzerinden indiriliyor...");

$successCount = 0;
foreach ($files as $fileName => $url) {
    $targetPath = $currentDir . DIRECTORY_SEPARATOR . $fileName;
    
    // Check if target file already exists
    if (file_exists($targetPath)) {
        logConsole('warning', "'{$fileName}' dosyası zaten mevcut. Üzerine yazılmasını istiyor musunuz? (y/n)");
        $overwrite = prompt("Üzerine yaz? (Overwrite?)", "y");
        if (strtolower($overwrite) !== 'y' && strtolower($overwrite) !== 'yes') {
            logConsole('info', "'{$fileName}' kurulumu atlandı.");
            continue;
        }
    }
    
    logConsole('info', "İndiriliyor: {$fileName}...");
    
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
        logConsole('error', "Hata: '{$fileName}' dosyası indirilemedi. İnternet bağlantınızı kontrol edin.");
        continue;
    }
    
    if (file_put_contents($targetPath, $content) !== false) {
        logConsole('success', "Kuruldu: {$fileName}");
        $successCount++;
    } else {
        logConsole('error', "Hata: '{$fileName}' dosyası yerel diske yazılamadı.");
    }
}

echo PHP_EOL;
echo COLOR_BOLD . COLOR_SUCCESS . "====================================================================\n";
echo "🎉 Kurulum Tamamlandı! ({$successCount} dosya kuruldu/güncellendi) 🎉\n";
echo "====================================================================\n" . COLOR_RESET;
echo "Sistemi kullanmaya başlamak için şu adımları izleyin:\n\n";
echo "  1. Formunuza " . COLOR_BOLD . "class=\"forge-form\"" . COLOR_RESET . " ekleyin.\n";
echo "  2. Sayfanızın başına " . COLOR_BOLD . "require_once 'ForgeShield.php';" . COLOR_RESET . " ekleyin.\n";
echo "  3. HTML sayfanızın head etiketlerine stil ve script dosyalarını bağlayın:\n";
echo "     " . COLOR_BOLD . "<link rel=\"stylesheet\" href=\"forge-form.css\">\n";
echo "     <script src=\"forge-form.js\" defer></script>" . COLOR_RESET . "\n";
echo "  4. Form post verilerini işleyen dosyanızın en başında şu kontrolü yapın:\n";
echo "     " . COLOR_BOLD . "\$cleanData = ForgeShield::validate();" . COLOR_RESET . "\n\n";
echo "Gelişmiş Ajax Form ve Güvenlik kütüphanesini kullandığınız için teşekkürler! ⚡\n";
