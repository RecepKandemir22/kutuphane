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

function injectSecurityBlockOnly($postContent, $postFile) {
    $modified = false;
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
            $pos = strpos($postContent, '<?php');
            $postContent = substr_replace($postContent, "<?php" . $securityBlock, $pos, 5);
            $modified = true;
            logConsole('success', "'{$postFile}' başına güvenlik ve doğrulama kodları entegre edildi.");
        } else {
            $postContent = "<?php" . $securityBlock . "?>" . PHP_EOL . $postContent;
            $modified = true;
            logConsole('success', "'{$postFile}' başına PHP etiketleriyle birlikte güvenlik kodları entegre edildi.");
        }
    } else {
        logConsole('info', "'{$postFile}' içerisinde ForgeShield doğrulaması zaten mevcut.");
    }
    
    if ($modified) {
        global $currentDir;
        $postFilePath = $currentDir . DIRECTORY_SEPARATOR . $postFile;
        file_put_contents($postFilePath, $postContent);
    }
    return $postContent;
}

function getPostTemplateContent() {
    return <<<'PHP'
<?php
// --- ForgeForm & Shield Güvenlik Entegrasyonu ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'forge-shield/ForgeShield.php';
$cleanData = ForgeShield::validate(20);
// ------------------------------------------------

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Config dosyasını dahil edelim
if (file_exists('config.php')) {
    require_once 'config.php';
}

// Varsayılan Ayarlar (config.php yoksa veya eksikse)
if (!defined('SMTP_DEVELOPER_MODE')) define('SMTP_DEVELOPER_MODE', false);
if (!defined('SMTP_USER'))           define('SMTP_USER', '');
if (!defined('SMTP_PASS'))           define('SMTP_PASS', '');
if (!defined('SMTP_TO_EMAIL'))       define('SMTP_TO_EMAIL', '');
if (!defined('SMTP_HOST'))           define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT'))           define('SMTP_PORT', 587);
if (!defined('SMTP_SECURE'))         define('SMTP_SECURE', 'tls');
if (!defined('SMTP_AUTH'))           define('SMTP_AUTH', true);
if (!defined('SMTP_FROM_EMAIL'))     define('SMTP_FROM_EMAIL', SMTP_USER);

// Gelen POST verilerini alalım
$name = isset($_POST['name']) ? htmlspecialchars(strip_tags(trim($_POST['name'])), ENT_QUOTES, 'UTF-8') : '';
$email = isset($_POST['email']) ? htmlspecialchars(strip_tags(trim($_POST['email'])), ENT_QUOTES, 'UTF-8') : '';
$phone = isset($_POST['phone']) ? htmlspecialchars(strip_tags(trim($_POST['phone'])), ENT_QUOTES, 'UTF-8') : '';
$message = isset($_POST['message']) ? htmlspecialchars(strip_tags(trim($_POST['message'])), ENT_QUOTES, 'UTF-8') : '';

// Hata/Durum Kontrolleri
$mailSent = false;
$simulated = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($name) || empty($email) || empty($phone) || empty($message)) {
        $error = 'Lütfen tüm alanları eksiksiz doldurun.';
    } else {
        if (defined('SMTP_DEVELOPER_MODE') && SMTP_DEVELOPER_MODE === true) {
            // Test Modu Aktif
            $mailSent = true;
            $simulated = true;
        } else {
            // Gerçek SMTP ile Mail Gönderme İşlemi
            if (file_exists('vendor/autoload.php')) {
                require_once 'vendor/autoload.php';
                
                $mail = new PHPMailer(true);
                
                try {
                    // SMTP Ayarları
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = SMTP_AUTH;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = SMTP_SECURE;
                    $mail->Port       = SMTP_PORT;
                    $mail->CharSet    = 'UTF-8';
                    
                    // Alıcı Ayarları
                    $displayFromName = html_entity_decode($name, ENT_QUOTES, 'UTF-8');
                    $mail->setFrom(SMTP_FROM_EMAIL, $displayFromName);
                    $mail->addAddress(SMTP_TO_EMAIL);
                    $mail->addReplyTo($email, $displayFromName);
                    
                    // İçerik Ayarları
                    $mail->isHTML(true);
                    $mail->Subject = $displayFromName . ' - Yeni İletişim Formu Mesajı';
                    $mail->Body    = "
                        <div style='font-family: Arial, sans-serif; padding: 20px; color: #333; line-height: 1.6; border: 1px solid #eee; border-radius: 8px;'>
                            <h2 style='color: #6366f1; border-bottom: 2px solid #f3f4f6; padding-bottom: 10px;'>Yeni İletişim Formu Mesajı</h2>
                            <p><strong>Gönderen Ad Soyad:</strong> {$name}</p>
                            <p><strong>Gönderen E-Posta:</strong> <a href='mailto:{$email}'>{$email}</a></p>
                            <p><strong>Gönderen Telefon:</strong> {$phone}</p>
                            <div style='background-color: #f9fafb; padding: 15px; border-left: 4px solid #6366f1; margin-top: 15px; border-radius: 4px;'>
                                <p style='margin: 0; font-style: italic;'><strong>Mesaj:</strong></p>
                                <p style='margin: 5px 0 0 0;'>{$message}</p>
                            </div>
                        </div>
                    ";
                    
                    $mail->send();
                    $mailSent = true;
                } catch (Exception $e) {
                    $mailSent = false;
                    $error = 'SMTP Bağlantı Hatası: ' . $mail->ErrorInfo;
                }
            } else {
                $mailSent = false;
                $error = 'PHPMailer kütüphanesi yüklenmemiş. Lütfen sihirbaz ile kurulumu tamamlayın veya composer/vendor dizinini kontrol edin.';
            }
        }
    }
} else {
    // POST dışı doğrudan erişimleri anasayfaya yönlendir
    header('Location: index.php');
    exit;
}

// İstek AJAX ise JSON yanıtı dön
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || isset($_POST['forge_csrf_token']);
if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    if ($mailSent) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Mesajınız başarıyla ve güvenle gönderildi! ' . ($simulated ? '(Test Modu Simülasyonu)' : '')
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $error ?: 'E-posta gönderimi başarısız oldu.'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşlem Sonucu</title>
    <!-- Google Font (Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #080b11;
            --card-bg: rgba(15, 22, 36, 0.7);
            --border-color: rgba(255, 255, 255, 0.06);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --success-color: #10b981;
            --danger-color: #ef4444;
            --shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.7);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.18) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.15) 0, transparent 55%);
            background-size: cover;
            background-attachment: fixed;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .result-card {
            width: 100%;
            max-width: 500px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow);
            text-align: center;
            animation: cardEntrance 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes cardEntrance {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .icon-wrapper {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 24px;
        }

        .icon-success {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .icon-danger {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        h1 {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        .subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 30px;
        }

        .details-list {
            background: rgba(17, 24, 39, 0.4);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 24px;
            text-align: left;
            margin-bottom: 30px;
        }

        .details-title {
            font-size: 0.85rem;
            font-weight: 600;
            color: #818cf8;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
            padding-bottom: 8px;
        }

        .detail-row {
            margin-bottom: 14px;
            font-size: 0.95rem;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-row:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .detail-value {
            color: var(--text-primary);
            font-weight: 400;
        }

        .message-box {
            background: rgba(255, 255, 255, 0.02);
            border: 1px dashed rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 10px 12px;
            margin-top: 4px;
            font-size: 0.9rem;
            color: var(--text-primary);
            word-break: break-word;
            max-height: 100px;
            overflow-y: auto;
        }

        .btn-back {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            width: 100%;
            background: var(--accent-gradient);
            border: none;
            border-radius: 12px;
            padding: 16px;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .btn-back:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        }

        .btn-back:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>

    <div class="result-card">
        <?php if ($mailSent): ?>
            <div class="icon-wrapper icon-success">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h1>Form Gönderimi Başarılı!</h1>
            <p class="subtitle">İletişim bilgileriniz ve mesajınız başarıyla alınmıştır.</p>
        <?php else: ?>
            <div class="icon-wrapper icon-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
            <h1>İşlem Başarısız!</h1>
            <p class="subtitle"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <div class="details-list">
            <div class="details-title">Gönderilen Bilgiler</div>
            
            <div class="detail-row">
                <span class="detail-label">Ad Soyad</span>
                <span class="detail-value"><?= $name ? $name : '-' ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">E-Posta</span>
                <span class="detail-value"><?= $email ? $email : '-' ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Telefon</span>
                <span class="detail-value"><?= $phone ? $phone : '-' ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Mesaj</span>
                <div class="message-box"><?= $message ? nl2br($message) : '-' ?></div>
            </div>
        </div>

        <a href="index.php" class="btn-back">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            <span>Geri Dön</span>
        </a>
    </div>

</body>
</html>
PHP;
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
            // Automatically create it with the full SMTP mailer logic!
            $postContent = getPostTemplateContent();
            if (file_put_contents($postFilePath, $postContent) !== false) {
                logConsole('success', "'{$postFile}' dosyası bulunamadı, hazır SMTP mail gönderim şablonuyla otomatik oluşturuldu.");
            } else {
                logConsole('error', "Hata: '{$postFile}' dosyası oluşturulamadı.");
                return false;
            }
        } else {
            $postContent = file_get_contents($postFilePath);
            
            // Check if the file contains PHPMailer / SMTP code
            if (strpos($postContent, 'PHPMailer') === false && strpos($postContent, 'SMTP_HOST') === false) {
                $trimmed = trim($postContent);
                if (empty($trimmed) || strlen($trimmed) < 150) {
                    $postContent = getPostTemplateContent();
                    file_put_contents($postFilePath, $postContent);
                    logConsole('success', "'{$postFile}' dosyası boş veya çok kısa olduğu için hazır SMTP mail gönderim şablonuyla güncellendi.");
                } else {
                    logConsole('warning', "'{$postFile}' içerisinde SMTP mail gönderme kodları bulunamadı.");
                    $update = prompt("Bu dosyayı yedekleyip hazır SMTP mail gönderim şablonu ile değiştirmek ister misiniz? (y/n)", "y");
                    if (strtolower($update) === 'y' || strtolower($update) === 'yes') {
                        if (@rename($postFilePath, $postFilePath . '.bak')) {
                            logConsole('info', "Eski dosya yedeklendi: '{$postFile}.bak'");
                        }
                        $postContent = getPostTemplateContent();
                        file_put_contents($postFilePath, $postContent);
                        logConsole('success', "'{$postFile}' dosyası hazır SMTP mail gönderim şablonuyla güncellendi.");
                    } else {
                        // If they choose not to overwrite, just inject the safety check block
                        injectSecurityBlockOnly($postContent, $postFile);
                    }
                }
            } else {
                // If it already has SMTP/PHPMailer code, just inject the safety check block
                injectSecurityBlockOnly($postContent, $postFile);
            }
        }
    }
    
    return true;
}

function createSampleFiles() {
    global $currentDir;
    
    $indexPath = $currentDir . DIRECTORY_SEPARATOR . 'index.php';
    $postPath  = $currentDir . DIRECTORY_SEPARATOR . 'post.php';
    
    if (file_exists($indexPath) || file_exists($postPath)) {
        logConsole('warning', "Dizinde zaten 'index.php' veya 'post.php' dosyası mevcut. Üzerine yazılsın mı? (y/n)");
        $overwrite = prompt("Üzerine yaz? (Overwrite?)", "n");
        if (strtolower($overwrite) !== 'y' && strtolower($overwrite) !== 'yes') {
            logConsole('info', "Örnek dosyaların oluşturulması iptal edildi.");
            return;
        }
    }
    
    $indexContent = <<<'HTML'
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium İletişim Formu</title>
    <!-- Google Font (Outfit) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #080b11;
            --card-bg: rgba(15, 22, 36, 0.7);
            --border-color: rgba(255, 255, 255, 0.06);
            --text-primary: #f3f4f6;
            --text-secondary: #9ca3af;
            --accent-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            --accent-hover: linear-gradient(135deg, #4f46e5 0%, #9333ea 100%);
            --input-bg: rgba(17, 24, 39, 0.5);
            --focus-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
            --shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.7);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.18) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.15) 0, transparent 55%);
            background-size: cover;
            background-attachment: fixed;
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .form-card {
            width: 100%;
            max-width: 500px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow);
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px -12px rgba(99, 102, 241, 0.25);
        }

        .form-header {
            text-align: center;
            margin-bottom: 35px;
        }

        .form-header h1 {
            font-size: 2.2rem;
            font-weight: 700;
            background: var(--accent-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            letter-spacing: -0.02em;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 14px 16px;
            color: var(--text-primary);
            font-size: 0.95rem;
            font-weight: 400;
            outline: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .form-control:focus {
            border-color: #6366f1;
            background: rgba(17, 24, 39, 0.7);
            box-shadow: var(--focus-shadow);
        }

        .form-group:focus-within label {
            color: #818cf8;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 110px;
        }

        .btn-submit {
            width: 100%;
            background: var(--accent-gradient);
            border: none;
            border-radius: 12px;
            padding: 16px;
            color: #ffffff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn-submit:hover {
            background: var(--accent-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(99, 102, 241, 0.45);
        }

        .btn-submit:active {
            transform: translateY(0);
        }
    </style>
    <link rel="stylesheet" href="forge-shield/forge-form.css">
</head>
<body>

    <div class="form-card">
        <div class="form-header">
            <h1>İletişim Formu</h1>
            <p>Bizimle iletişime geçmek için formu doldurabilirsiniz.</p>
        </div>

        <form class="forge-form" action="post.php" method="POST">
            <div class="form-group">
                <label for="name">Adınız Soyadınız</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Ahmet Yılmaz" required autocomplete="name">
            </div>

            <div class="form-group">
                <label for="email">E-Posta Adresiniz</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="ahmet@example.com" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="phone">Telefon Numaranız</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="0500 000 00 00" required autocomplete="tel">
            </div>

            <div class="form-group">
                <label for="message">Mesajınız</label>
                <textarea id="message" name="message" class="form-control" placeholder="Mesajınızı buraya yazın..." required></textarea>
            </div>

            <button type="submit" class="btn-submit">
                <span>Gönder</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="22" y1="2" x2="11" y2="13"></line>
                    <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                </svg>
            </button>
        </form>
    </div>

    <script src="forge-shield/forge-form.js" defer></script>
</body>
</html>
HTML;

    $postContent = getPostTemplateContent();

    if (file_put_contents($indexPath, $indexContent) !== false) {
        logConsole('success', "Örnek premium iletişim formu oluşturuldu: index.php");
    }
    if (file_put_contents($postPath, $postContent) !== false) {
        logConsole('success', "Örnek mail gönderim işlemcisi oluşturuldu: post.php");
    }
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
2. SMTP_DEVELOPER_MODE varsayılan olarak "false" (gerçek gönderim aktif) durumundadır.
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
define('SMTP_DEVELOPER_MODE', false);

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
PHP;

    if (file_put_contents($configPath, $configContent) !== false) {
        logConsole('success', "Varsayılan SMTP yapılandırma dosyası oluşturuldu: config.php");
    }
}

// 4. Install PHPMailer directly from GitHub (no composer needed)
echo PHP_EOL;
echo COLOR_BOLD . COLOR_INFO . "--------------------------------------------------------------------\n";
echo "📦 PHPMailer Kurulumu (E-Posta Göndermek İçin Gerekli)\n";
echo "--------------------------------------------------------------------\n" . COLOR_RESET;

$vendorDir    = $currentDir . DIRECTORY_SEPARATOR . 'vendor';
$phpmailerDir = $vendorDir . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'phpmailer' . DIRECTORY_SEPARATOR . 'src';
$autoloadPath = $vendorDir . DIRECTORY_SEPARATOR . 'autoload.php';

if (file_exists($autoloadPath)) {
    logConsole('info', "PHPMailer zaten kurulu (vendor/autoload.php mevcut). Atlanıyor.");
} else {
    // Create vendor directory structure
    if (!is_dir($phpmailerDir)) {
        mkdir($phpmailerDir, 0755, true);
    }

    $phpmailerFiles = [
        'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
        'SMTP.php'      => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php',
        'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php',
    ];

    $phpmailerSuccess = true;
    foreach ($phpmailerFiles as $fileName => $url) {
        $targetPath = $phpmailerDir . DIRECTORY_SEPARATOR . $fileName;
        logConsole('info', "İndiriliyor: vendor/phpmailer/phpmailer/src/{$fileName}...");

        $content = false;
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
            if ($httpCode !== 200) $content = false;
        }
        if ($content === false && ini_get('allow_url_fopen')) {
            $ctx = stream_context_create(['http' => ['method' => 'GET', 'header' => "User-Agent: ForgeForm-Installer/1.0\r\n"]]);
            $content = @file_get_contents($url, false, $ctx);
        }

        if ($content === false) {
            logConsole('error', "Hata: '{$fileName}' indirilemedi.");
            $phpmailerSuccess = false;
            continue;
        }
        file_put_contents($targetPath, $content);
        logConsole('success', "Kuruldu: vendor/phpmailer/phpmailer/src/{$fileName}");
    }

    if ($phpmailerSuccess) {
        // Create a minimal autoload.php that loads PHPMailer classes
        $autoloadContent = <<<'AUTOLOAD'
<?php
/**
 * Minimal Autoloader - ForgeForm Installer tarafından otomatik oluşturuldu.
 * PHPMailer sınıflarını doğrudan yükler (Composer gerektirmez).
 */
spl_autoload_register(function ($class) {
    $classMap = [
        'PHPMailer\\PHPMailer\\PHPMailer' => __DIR__ . '/phpmailer/phpmailer/src/PHPMailer.php',
        'PHPMailer\\PHPMailer\\SMTP'      => __DIR__ . '/phpmailer/phpmailer/src/SMTP.php',
        'PHPMailer\\PHPMailer\\Exception' => __DIR__ . '/phpmailer/phpmailer/src/Exception.php',
    ];
    if (isset($classMap[$class])) {
        require_once $classMap[$class];
    }
});
AUTOLOAD;
        file_put_contents($autoloadPath, $autoloadContent);
        logConsole('success', "vendor/autoload.php oluşturuldu. PHPMailer kullanıma hazır!");
        $successCount++;
    } else {
        logConsole('error', "PHPMailer kurulumu tamamlanamadı. İnternet bağlantınızı kontrol edin.");
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
                
                if (!empty($actionValueClean) && 
                    $actionValueClean !== '#' && 
                    !preg_match('/^(https?:\/\/|\/\/|javascript:)/i', $actionValueClean)) {
                    $postFile = $actionValueClean;
                }
            }
            
            logConsole('info', "'{$formFile}'" . ($postFile ? " ve işlem dosyası '{$postFile}'" : "") . " için entegrasyon başlatılıyor...");
            injectCodeIntoFiles($formFile, $postFile);
        }
        echo PHP_EOL;
    } else {
        logConsole('warning', "Dizinde otomatik entegrasyon yapılabilecek form içeren bir HTML/PHP dosyası bulunamadı.");
        $createSamples = prompt("Hazır SMTP mail gönderimi ve güvenlik korumalı örnek 'index.php' ve 'post.php' dosyalarını oluşturmak ister misiniz? (y/n)", "y");
        if (strtolower($createSamples) === 'y' || strtolower($createSamples) === 'yes') {
            createSampleFiles();
        }
    }
}

echo PHP_EOL;
echo COLOR_BOLD . COLOR_SUCCESS . "====================================================================\n";
echo "🎉 Kurulum Tamamlandı! ({$successCount} dosya kuruldu/güncellendi) 🎉\n";
echo "====================================================================\n" . COLOR_RESET;
echo "Sistemi kullanmaya başlamak için şu adımları izleyin:\n\n";
echo "  1. Formunuza otomatik olarak " . COLOR_BOLD . "class=\"forge-form\"" . COLOR_RESET . " eklenmiştir.\n";
echo "  2. Sayfanıza otomatik olarak CSS ve script dosyaları bağlanmıştır.\n";
echo "  3. PHPMailer otomatik olarak kuruldu (vendor/ klasörü oluşturuldu).\n";
echo "  4. Gerçek mail gönderimi için " . COLOR_BOLD . "config.php" . COLOR_RESET . " dosyasını düzenleyin:\n";
echo "       - SMTP_DEVELOPER_MODE => false (varsayılan olarak zaten false)\n";
echo "       - SMTP_USER: Gmail adresiniz\n";
echo "       - SMTP_PASS: Google 16 haneli Uygulama Şifresi\n";
echo "       - SMTP_TO_EMAIL: Mesajların gideceği e-posta\n\n";
echo "💡 Kurulum detayları ve entegrasyon kodları " . COLOR_BOLD . "FORGE_SHIELD_GUIDE.txt" . COLOR_RESET . " dosyasına kaydedildi.\n";
echo "💡 Dilediğiniz zaman şu terminal komutuyla da manuel entegrasyon yapabilirsiniz:\n";
echo "   " . COLOR_BOLD . "php installer.php integrate [form_dosyasi.php] [post_dosyasi.php]" . COLOR_RESET . "\n\n";
echo "Gelişmiş Ajax Form ve Güvenlik kütüphanesini kullandığınız için teşekkürler! ⚡\n";

