<?php
/**
 * CodeForge-Engine CLI Installer
 * Installs the framework into the current directory and sets up the configuration.
 */

// Define console colors
define('COLOR_SUCCESS', "\033[32m");
define('COLOR_INFO', "\033[36m");
define('COLOR_WARNING', "\033[33m");
define('COLOR_ERROR', "\033[31m");
define('COLOR_RESET', "\033[0m");
define('COLOR_BOLD', "\033[1m");

// Ensure running via CLI
if (php_sapi_name() !== 'cli') {
    die("This installer can only be run from the command line.\n");
}

// Print ASCII Art Header
echo COLOR_BOLD . COLOR_INFO;
echo "  _____          _      ______                     \n";
echo " / ____|        | |    |  ____|                    \n";
echo "| |     ___   __| | ___| |__ ___  _ __ __ _  ___   \n";
echo "| |    / _ \ / _` |/ _ \  __/ _ \| '__/ _` |/ _ \  \n";
echo "| |___| (_) | (_| |  __/ | | (_) | | | (_| |  __/  \n";
echo " \_____\___/ \__,_|\___|_|  \___/|_|  \__, |\___|  \n";
echo "                                       __/ |       \n";
echo "                                      |___/        \n";
echo "             ⚡ Installer Assistant ⚡             \n";
echo COLOR_RESET . "\n";

// Helper function to prompt user
function prompt($question, $default = '') {
    $suffix = $default !== '' ? " [{$default}]" : '';
    echo COLOR_BOLD . $question . $suffix . ": " . COLOR_RESET;
    $input = trim(fgets(STDIN));
    return $input === '' ? $default : $input;
}

// Helper function to log to console
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

// 1. Prerequisite Checks
logConsole('info', "Running system compatibility checks...");

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    logConsole('error', "CodeForge-Engine requires PHP 8.0.0 or higher. Current version: " . PHP_VERSION);
    exit(1);
}

if (!extension_loaded('zip')) {
    logConsole('error', "The 'zip' PHP extension is required to extract package files.");
    exit(1);
}

if (!extension_loaded('curl') && !ini_get('allow_url_fopen')) {
    logConsole('error', "Either 'curl' extension or 'allow_url_fopen' must be enabled to download package files.");
    exit(1);
}

logConsole('success', "Prerequisites met (PHP " . PHP_VERSION . ", extensions verified).");
echo "\n";

// 2. Ask Confirmation
$currentDir = getcwd();
$dirName = basename($currentDir);
echo "Installation Directory: " . COLOR_BOLD . $currentDir . COLOR_RESET . PHP_EOL;
$confirm = prompt("Do you want to install CodeForge-Engine here? (y/n)", "y");
if (strtolower($confirm) !== 'y' && strtolower($confirm) !== 'yes') {
    logConsole('warning', "Installation cancelled.");
    exit;
}

// Check if directory is not empty
$files = scandir($currentDir);
$isEmpty = count(array_diff($files, ['.', '..', 'installer.php'])) === 0;
if (!$isEmpty) {
    logConsole('warning', "Target directory is not empty. Existing files may be overwritten.");
    $confirmMerge = prompt("Do you want to proceed? (y/n)", "n");
    if (strtolower($confirmMerge) !== 'y' && strtolower($confirmMerge) !== 'yes') {
        logConsole('warning', "Installation cancelled.");
        exit;
    }
}

// 3. Download Zip Archive
$zipUrl = "https://github.com/RecepKandemir22/kutuphane/archive/refs/heads/main.zip";
$zipFile = $currentDir . DIRECTORY_SEPARATOR . 'codeforge_tmp.zip';
$tempExtractDir = $currentDir . DIRECTORY_SEPARATOR . '.codeforge_extracted';

logConsole('info', "Downloading CodeForge-Engine from GitHub...");

$downloaded = false;
if (extension_loaded('curl')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // Add User-Agent since GitHub requires it
    curl_setopt($ch, CURLOPT_USERAGENT, 'CodeForge-Installer/2.0');
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $data) {
        if (file_put_contents($zipFile, $data) !== false) {
            $downloaded = true;
        }
    }
}

if (!$downloaded && ini_get('allow_url_fopen')) {
    // Try file_get_contents with context for User-Agent
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: CodeForge-Installer/2.0\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $data = @file_get_contents($zipUrl, false, $context);
    if ($data) {
        if (file_put_contents($zipFile, $data) !== false) {
            $downloaded = true;
        }
    }
}

if (!$downloaded) {
    logConsole('error', "Failed to download the installation package. Check your internet connection or URL settings.");
    exit(1);
}

logConsole('success', "Package downloaded successfully.");

// 4. Extract Package
logConsole('info', "Extracting framework files...");
$zip = new ZipArchive;
if ($zip->open($zipFile) === TRUE) {
    if (!is_dir($tempExtractDir)) {
        mkdir($tempExtractDir, 0755, true);
    }
    $zip->extractTo($tempExtractDir);
    $zip->close();
} else {
    logConsole('error', "Failed to open downloaded zip archive.");
    @unlink($zipFile);
    exit(1);
}

// Identify the root folder inside zip (GitHub zips folder as repo-branch)
$extractedDirs = array_diff(scandir($tempExtractDir), ['.', '..']);
$repoDirName = reset($extractedDirs);
$sourcePath = $tempExtractDir . DIRECTORY_SEPARATOR . $repoDirName;

if (empty($repoDirName) || !is_dir($sourcePath)) {
    logConsole('error', "Invalid package structure inside zip.");
    cleanDir($tempExtractDir);
    @unlink($zipFile);
    exit(1);
}

// Helper to recursively copy directories
function copyDir($src, $dst) {
    $dir = opendir($src);
    if (!is_dir($dst)) {
        mkdir($dst, 0755, true);
    }
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . '/' . $file)) {
                // Skip version control folders
                if ($file === '.git' || $file === '.github') {
                    continue;
                }
                copyDir($src . '/' . $file, $dst . '/' . $file);
            } else {
                // Skip installer files to prevent self-overwrite
                if ($file === 'installer.php') {
                    continue;
                }
                copy($src . '/' . $file, $dst . '/' . $file);
            }
        }
    }
    closedir($dir);
}

// Helper to recursively delete directories
function cleanDir($dir) {
    if (!is_dir($dir)) return;
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? cleanDir("$dir/$file") : unlink("$dir/$file");
    }
    rmdir($dir);
}

logConsole('info', "Copying files to target directory...");
copyDir($sourcePath, $currentDir);

// Clean up temp files
logConsole('info', "Cleaning up temporary files...");
@unlink($zipFile);
cleanDir($tempExtractDir);

logConsole('success', "CodeForge-Engine files installed.");
echo "\n";

// 5. Environment Configuration
$configDb = prompt("Would you like to configure your database & app settings now? (y/n)", "y");
if (strtolower($configDb) === 'y' || strtolower($configDb) === 'yes') {
    // Generate defaults
    $defaultAppName = ucwords(str_replace(['-', '_'], ' ', $dirName));
    $defaultAppUrl = "http://localhost/" . $dirName;
    $defaultDbName = str_replace(['-', ' '], '_', strtolower($dirName));
    
    $appName = prompt("Application Name", $defaultAppName);
    $appUrl = prompt("Application URL", $defaultAppUrl);
    $dbHost = prompt("Database Host", "localhost");
    $dbName = prompt("Database Name", $defaultDbName);
    $dbUser = prompt("Database Username", "root");
    $dbPass = prompt("Database Password", "");
    
    $envContent = <<<INI
# ====================================================================
# CodeForge-Engine Environment Configuration
# ====================================================================

# Application Settings
APP_NAME="{$appName}"
APP_ENV=development
APP_DEBUG=true
APP_URL="{$appUrl}"

# Database Configuration
DB_HOST={$dbHost}
DB_DATABASE={$dbName}
DB_USERNAME={$dbUser}
DB_PASSWORD={$dbPass}
DB_CHARSET=utf8mb4
INI;
    
    file_put_contents($currentDir . DIRECTORY_SEPARATOR . '.env', $envContent);
    logConsole('success', ".env configuration file created successfully.");
} else {
    // Copy env.example
    if (file_exists($currentDir . DIRECTORY_SEPARATOR . '.env.example')) {
        copy($currentDir . DIRECTORY_SEPARATOR . '.env.example', $currentDir . DIRECTORY_SEPARATOR . '.env');
        logConsole('info', "Copied default .env.example to .env.");
    }
}

// 6. Complete
echo "\n";
echo COLOR_BOLD . COLOR_SUCCESS . "====================================================================\n";
echo "🎉 CodeForge-Engine Successfully Installed! 🎉\n";
echo "====================================================================\n" . COLOR_RESET;
echo "Here is how to get started:\n\n";
echo "  1. " . COLOR_BOLD . "Start" . COLOR_RESET . " your local MySQL / Apache servers (e.g. XAMPP).\n";
echo "  2. If you configured database settings, run migrations to scaffold tables:\n";
echo "     " . COLOR_BOLD . "php forge.php migrate" . COLOR_RESET . "\n";
echo "  3. Start a development server:\n";
echo "     " . COLOR_BOLD . "php -S localhost:8000 -t public" . COLOR_RESET . "\n";
echo "  4. Open " . COLOR_BOLD . "http://localhost:8000" . COLOR_RESET . " in your browser.\n\n";
echo "Thank you for developing with CodeForge-Engine! ⚡\n";
