<?php
/**
 * CodeForge-Engine Developer CLI Tool
 * Zero-coding automation engine for web builders.
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
    die("This script can only be run from the command line.\n");
}

// Global logger helper
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

// Bootstrap app only if App.php exists
$app = null;
if (file_exists(__DIR__ . '/core/App.php')) {
    require_once __DIR__ . '/core/App.php';
    try {
        $app = \Forge\Core\App::getInstance();
    } catch (\Exception $e) {
        // App class might fail if config is completely broken, we will handle commands gracefully
    }
}

// Parse argv
$args = $argv;
array_shift($args); // Remove script name

if (empty($args) || in_array('--help', $args) || in_array('-h', $args)) {
    showDeveloperHelp();
    exit;
}

$command = array_shift($args);

// Route CLI commands
switch ($command) {
    case 'init':
        handleInit();
        break;
    case 'make:controller':
        handleMakeController($args);
        break;
    case 'make:model':
        handleMakeModel($args);
        break;
    case 'migrate':
        handleMigrate($app);
        break;
    case 'db:seed':
        handleDbSeed($app);
        break;
    case 'route:list':
        handleRouteList();
        break;
    case 'server':
        handleServer($args);
        break;
    case 'sec:key':
        handleSecKey();
        break;
    case 'sec:audit':
        handleSecAudit($args);
        break;
    case 'ui:scaffold':
        handleUiScaffold($args);
        break;
    case 'ui:minify':
        handleUiMinify($args);
        break;
    default:
        logConsole('error', "Unknown command '{$command}'.");
        showDeveloperHelp();
        exit(1);
}

// ==========================================
// 1. INIT COMMAND
// ==========================================
function handleInit() {
    logConsole('info', "Initializing CodeForge-Engine project structure...");

    // Create folders
    $folders = [
        'app/Controllers',
        'app/Models',
        'database/migrations',
        'public/assets/css',
        'public/assets/js',
        'views/layouts'
    ];

    foreach ($folders as $folder) {
        if (!is_dir(__DIR__ . '/' . $folder)) {
            mkdir(__DIR__ . '/' . $folder, 0755, true);
            logConsole('success', "Created directory: {$folder}");
        }
    }

    // Scaffold .env if not exists
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        $envContent = <<<INI
# Application Configuration
APP_NAME="CodeForge App"
APP_ENV=development
APP_DEBUG=true
APP_URL="http://localhost:8000"
APP_KEY=""

# Database Configuration
DB_HOST=localhost
DB_DATABASE=deneme
DB_USERNAME=root
DB_PASSWORD=""
DB_CHARSET=utf8mb4
INI;
        file_put_contents($envFile, $envContent);
        logConsole('success', "Scaffolded default configuration: .env");
    }

    // Scaffold public/index.php if not exists
    $indexFile = __DIR__ . '/public/index.php';
    if (!file_exists($indexFile)) {
        $indexContent = <<<PHP
<?php
// Public front controller
require_once __DIR__ . '/../core/App.php';

use Forge\Core\App;
use Forge\Core\Router;

spl_autoload_register(function (\$class) {
    \$prefix = 'App\\\\';
    \$baseDir = __DIR__ . '/../app/';
    \$len = strlen(\$prefix);
    if (strncmp(\$prefix, \$class, \$len) !== 0) return;
    \$relativeClass = substr(\$class, \$len);
    \$file = \$baseDir . str_replace('\\\\', '/', \$relativeClass) . '.php';
    if (file_exists(\$file)) require_once \$file;
});

\$app = App::getInstance();
\$router = new Router();

// Routes
\$router->get('/', function() {
    echo "<h1>Welcome to your CodeForge-Engine project!</h1><p>Run <code>php forge.php ui:scaffold landing</code> in terminal to create a premium homepage.</p>";
});

\$app->register('router', function() use (\$router) {
    return \$router;
});

\$router->resolve();
PHP;
        file_put_contents($indexFile, $indexContent);
        logConsole('success', "Scaffolded entry file: public/index.php");
    }

    // Generate APP_KEY
    handleSecKey();

    logConsole('success', "CodeForge project initialized successfully!");
}

// ==========================================
// 2. MAKE:CONTROLLER COMMAND
// ==========================================
function handleMakeController($args) {
    if (empty($args)) {
        logConsole('error', "Missing controller name. Usage: php forge.php make:controller <Name>");
        exit(1);
    }
    
    $name = $args[0];
    if (strpos($name, 'Controller') === false) {
        $name .= 'Controller';
    }
    
    $targetFile = __DIR__ . '/app/Controllers/' . $name . '.php';
    if (file_exists($targetFile)) {
        logConsole('warning', "Controller '{$name}' already exists.");
        exit(1);
    }
    
    $dir = dirname($targetFile);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    $template = <<<PHP
<?php
namespace App\Controllers;

use Forge\Core\Controller;

class {$name} extends Controller {
    
    public function index() {
        return \$this->view('home', ['title' => 'Hello from {$name}']);
    }
}
PHP;

    if (file_put_contents($targetFile, $template) !== false) {
        logConsole('success', "Scaffolded Controller: {$name} ➜ app/Controllers/{$name}.php");
    } else {
        logConsole('error', "Failed writing to: {$targetFile}");
    }
}

// ==========================================
// 3. MAKE:MODEL COMMAND
// ==========================================
function handleMakeModel($args) {
    if (empty($args)) {
        logConsole('error', "Missing model name. Usage: php forge.php make:model <Name> [--table=tableName]");
        exit(1);
    }
    
    $name = $args[0];
    $table = null;
    foreach ($args as $arg) {
        if (strpos($arg, '--table=') === 0) {
            $table = substr($arg, 8);
        }
    }
    
    $targetFile = __DIR__ . '/app/Models/' . $name . '.php';
    if (file_exists($targetFile)) {
        logConsole('warning', "Model '{$name}' already exists.");
        exit(1);
    }
    
    $dir = dirname($targetFile);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    $tableProperty = '';
    if ($table) {
        $tableProperty = "    protected static \$table = '{$table}';\n";
    }
    
    $template = <<<PHP
<?php
namespace App\Models;

use Forge\Core\Model;

class {$name} extends Model {
{$tableProperty}}
PHP;

    if (file_put_contents($targetFile, $template) !== false) {
        logConsole('success', "Scaffolded Model: {$name} ➜ app/Models/{$name}.php");
    } else {
        logConsole('error', "Failed writing to: {$targetFile}");
    }
}

// ==========================================
// 4. MIGRATE COMMAND
// ==========================================
function handleMigrate($app) {
    if (!$app) {
        logConsole('error', "Framework core is missing or not bootstrapped.");
        exit(1);
    }
    try {
        $db = $app->get('database');
    } catch (\Exception $e) {
        logConsole('error', "Database connection failed. Check your DB settings in .env.");
        logConsole('error', $e->getMessage());
        exit(1);
    }
    
    // Create migrations log table
    $db->query("
        CREATE TABLE IF NOT EXISTS `migrations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `migration` VARCHAR(255) UNIQUE NOT NULL,
            `batch` INT NOT NULL,
            `migrated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $executedRaw = $db->table('migrations')->get();
    $executed = array_column($executedRaw, 'migration');
    
    $maxBatch = $db->table('migrations')->select('MAX(batch) as max_batch')->first()['max_batch'] ?? 0;
    $nextBatch = $maxBatch + 1;
    
    $migrationsDir = __DIR__ . '/database/migrations';
    if (!is_dir($migrationsDir)) {
        logConsole('info', "No migrations directory found.");
        exit;
    }
    
    $files = scandir($migrationsDir);
    $migrationFiles = array_filter($files, function($file) use ($migrationsDir) {
        return is_file($migrationsDir . '/' . $file) && pathinfo($file, PATHINFO_EXTENSION) === 'php';
    });
    
    sort($migrationFiles);
    
    $runCount = 0;
    foreach ($migrationFiles as $file) {
        if (in_array($file, $executed)) continue;
        
        logConsole('info', "Migrating: {$file}");
        $migrationObj = include $migrationsDir . '/' . $file;
        
        try {
            $migrationObj->up($db);
            $db->table('migrations')->insert([
                'migration' => $file,
                'batch' => $nextBatch
            ]);
            logConsole('success', "Migrated:  {$file}");
            $runCount++;
        } catch (\Exception $e) {
            logConsole('error', "Migration Failed on: {$file}");
            logConsole('error', $e->getMessage());
            exit(1);
        }
    }
    
    if ($runCount === 0) {
        logConsole('info', "Nothing to migrate. Database is up to date.");
    } else {
        logConsole('success', "Completed running {$runCount} migrations in batch #{$nextBatch}!");
    }
}

// ==========================================
// 5. DB:SEED COMMAND
// ==========================================
function handleDbSeed($app) {
    if (!$app) {
        logConsole('error', "Framework core is missing or not bootstrapped.");
        exit(1);
    }
    try {
        $db = $app->get('database');
    } catch (\Exception $e) {
        logConsole('error', "Database connection failed. Check your DB settings in .env.");
        logConsole('error', $e->getMessage());
        exit(1);
    }

    logConsole('info', "Seeding default data...");

    // Seed books if table exists
    try {
        $booksCount = $db->query("SELECT COUNT(*) as count FROM `books`")[0]['count'] ?? 0;
        if ($booksCount == 0) {
            $db->query("
                INSERT INTO `books` (`title`, `author`, `isbn`, `category`, `total_copies`, `available_copies`) VALUES 
                ('Design Patterns', 'Erich Gamma', '9780201633610', 'Programming', 5, 5),
                ('Clean Code', 'Robert C. Martin', '9780132350884', 'Software Engineering', 3, 3),
                ('Refactoring', 'Martin Fowler', '9780201485677', 'Refactoring', 4, 4),
                ('The Pragmatic Programmer', 'Andrew Hunt', '9780201616224', 'Development', 6, 6)
            ");
            logConsole('success', "Seeded `books` table with default titles.");
        } else {
            logConsole('info', "Table `books` already contains data. Skipped.");
        }
    } catch (\Exception $e) {
        // Table doesn't exist
    }

    // Seed users if table exists
    try {
        $usersCount = $db->query("SELECT COUNT(*) as count FROM `users`")[0]['count'] ?? 0;
        if ($usersCount == 0) {
            $hashedPass = password_hash('admin123', PASSWORD_BCRYPT);
            $db->query("
                INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES 
                ('Administrator', 'admin@example.com', '{$hashedPass}', 'admin'),
                ('Guest User', 'guest@example.com', '{$hashedPass}', 'guest')
            ");
            logConsole('success', "Seeded `users` table with administrator accounts.");
        } else {
            logConsole('info', "Table `users` already contains data. Skipped.");
        }
    } catch (\Exception $e) {
        // Table doesn't exist
    }

    logConsole('success', "Database seeding operation complete!");
}

// ==========================================
// 6. ROUTE:LIST COMMAND
// ==========================================
function handleRouteList() {
    $indexFile = __DIR__ . '/public/index.php';
    if (!file_exists($indexFile)) {
        logConsole('error', "Front entry file `public/index.php` not found.");
        exit(1);
    }

    logConsole('info', "Scanning application routes...");

    $content = file_get_contents($indexFile);
    // Find $router->get, $router->post, etc.
    preg_match_all('/\$router->(get|post|put|delete)\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*([^)]+)\)/i', $content, $matches, PREG_SET_ORDER);

    if (empty($matches)) {
        logConsole('warning', "No routes detected in `public/index.php`.");
        return;
    }

    echo COLOR_BOLD . str_pad("METHOD", 10) . str_pad("URI", 30) . "HANDLER" . COLOR_RESET . PHP_EOL;
    echo str_repeat("-", 80) . PHP_EOL;

    foreach ($matches as $match) {
        $method = strtoupper($match[1]);
        $uri = $match[2];
        $handler = trim($match[3]);

        // Clean up handler format for display
        $handler = str_replace(["\r", "\n", '  '], '', $handler);
        if (strpos($handler, '[') === 0) {
            $handler = preg_replace('/class\s*,/i', 'class @ ', $handler);
        }

        echo str_pad($method, 10) . str_pad($uri, 30) . $handler . PHP_EOL;
    }
}

// ==========================================
// 7. SERVER COMMAND
// ==========================================
function handleServer($args) {
    $port = 8000;
    foreach ($args as $arg) {
        if (strpos($arg, '--port=') === 0) {
            $port = (int)substr($arg, 7);
        }
    }

    logConsole('info', "Starting CodeForge-Engine local development server...");
    logConsole('success', "Server running at: http://localhost:{$port}");
    logConsole('warning', "Press Ctrl+C to stop the server.");

    // Determine PHP binary
    $phpBin = 'php';
    if (defined('PHP_BINARY')) {
        $phpBin = '"' . PHP_BINARY . '"';
    }

    passthru("{$phpBin} -S localhost:{$port} -t public");
}

// ==========================================
// 8. SEC:KEY COMMAND
// ==========================================
function handleSecKey() {
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        logConsole('warning', ".env file missing. Run `php forge.php init` first.");
        return;
    }

    $key = bin2hex(random_bytes(32));
    $content = file_get_contents($envFile);

    if (strpos($content, 'APP_KEY=') !== false) {
        $content = preg_replace('/APP_KEY=[^\r\n]*/', 'APP_KEY="' . $key . '"', $content);
    } else {
        $content .= "\nAPP_KEY=\"" . $key . "\"\n";
    }

    file_put_contents($envFile, $content);
    logConsole('success', "Generated secure application key: APP_KEY=\"{$key}\"");
}

// ==========================================
// 9. SEC:AUDIT COMMAND
// ==========================================
function handleSecAudit($args) {
    $fix = in_array('--fix', $args);
    logConsole('info', $fix ? "Running security audit with auto-fix enabled..." : "Running security audit...");

    $scanDirs = [__DIR__ . '/app', __DIR__ . '/public', __DIR__ . '/views'];
    $issues = [];
    $fixedCount = 0;

    foreach ($scanDirs as $dir) {
        if (!is_dir($dir)) continue;

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            if ($ext !== 'php' && $ext !== 'html') continue;

            $filePath = $file->getPathname();
            $content = file_get_contents($filePath);
            $lines = explode("\n", $content);

            // 1. Check for unsafe functions (eval)
            foreach ($lines as $i => $line) {
                if (preg_match('/\beval\s*\(/i', $line)) {
                    $issues[] = [
                        'file' => $filePath,
                        'line' => $i + 1,
                        'type' => 'High Risk',
                        'desc' => "Usage of dangerous eval() statement."
                    ];
                }
            }

            // 2. Check for SQL Injection risks
            foreach ($lines as $i => $line) {
                if (preg_match('/->(query|table|where)\(.*?\$(?:_GET|_POST|_REQUEST|argv)/i', $line)) {
                    $issues[] = [
                        'file' => $filePath,
                        'line' => $i + 1,
                        'type' => 'Critical',
                        'desc' => "Potential SQL Injection risk: Unescaped superglobal variables inside database query."
                    ];
                }
            }

            // 3. Check for Forms missing CSRF Tokens
            if (preg_match('/<form[^>]*method=[\'"]post[\'"]/i', $content)) {
                // If form is POST but doesn't have csrf token
                if (!preg_match('/_csrf_token|csrfToken|csrfField|csrf_token/i', $content)) {
                    if ($fix) {
                        // Inject CSRF field after form opening tag
                        $modifiedContent = preg_replace(
                            '/(<form[^>]*method=[\'"]post[\'"][^>]*>)/i',
                            "$1\n    <?= \\Forge\\Core\\App::getInstance()->get('guard')->csrfField() ?>",
                            $content
                        );
                        file_put_contents($filePath, $modifiedContent);
                        $fixedCount++;
                        logConsole('success', "Auto-patched: Injected CSRF token into form in " . basename($filePath));
                    } else {
                        $issues[] = [
                            'file' => $filePath,
                            'line' => 'Form Block',
                            'type' => 'Medium Risk',
                            'desc' => "POST form is missing CSRF security protection field."
                        ];
                    }
                }
            }
        }
    }

    // Print audit report
    if (empty($issues)) {
        logConsole('success', "Security audit complete. No critical vulnerabilities found!");
    } else {
        echo COLOR_BOLD . COLOR_WARNING . "=== SECURITY AUDIT THREAT REPORT ===" . COLOR_RESET . PHP_EOL;
        foreach ($issues as $issue) {
            echo "[" . COLOR_ERROR . $issue['type'] . COLOR_RESET . "] " . basename($issue['file']) . " (Line {$issue['line']}): " . $issue['desc'] . PHP_EOL;
        }
        echo COLOR_BOLD . COLOR_WARNING . "=====================================" . COLOR_RESET . PHP_EOL;
        if (!$fix) {
            logConsole('warning', "Type `php forge.php sec:audit --fix` to automatically repair CSRF vulnerabilities.");
        }
    }

    if ($fix && $fixedCount > 0) {
        logConsole('success', "Successfully patched {$fixedCount} security vulnerabilities automatically!");
    }
}

// ==========================================
// 10. UI:SCAFFOLD COMMAND
// ==========================================
function handleUiScaffold($args) {
    if (empty($args)) {
        logConsole('error', "Missing layout type. Usage: php forge.php ui:scaffold <dashboard|auth|landing> [--theme=dark|light|glass|nord] [--accent=indigo|emerald|rose|amber|cyan] [--style=modern|minimalist|bold]");
        exit(1);
    }

    $layout = $args[0];

    // Defaults
    $theme = 'glass';
    $accent = 'indigo';
    $style = 'modern';

    // Parse options
    foreach ($args as $arg) {
        if (strpos($arg, '--theme=') === 0) $theme = substr($arg, 8);
        if (strpos($arg, '--accent=') === 0) $accent = substr($arg, 9);
        if (strpos($arg, '--style=') === 0) $style = substr($arg, 8);
    }

    logConsole('info', "Generating UI theme variables (Theme: {$theme}, Accent: {$accent}, Style: {$style})...");

    // 1. Generate Custom Theme Stylesheet
    $themeCssFile = __DIR__ . '/public/assets/css/theme-custom.css';
    $dir = dirname($themeCssFile);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    // CSS variables configuration based on inputs
    $bg_primary = '#0a0b10';
    $bg_secondary = 'rgba(20, 22, 34, 0.65)';
    $bg_card = 'rgba(26, 29, 46, 0.45)';
    $bg_input = 'rgba(15, 17, 28, 0.7)';
    $text_primary = '#f4f4f7';
    $text_secondary = '#94a3b8';
    $border_color = 'rgba(255, 255, 255, 0.08)';
    $glass_blur = 'blur(16px)';

    if ($theme === 'light') {
        $bg_primary = '#f8fafc';
        $bg_secondary = 'rgba(255, 255, 255, 0.8)';
        $bg_card = 'rgba(241, 245, 249, 0.55)';
        $bg_input = '#ffffff';
        $text_primary = '#0f172a';
        $text_secondary = '#475569';
        $border_color = 'rgba(0, 0, 0, 0.08)';
        $glass_blur = 'blur(10px)';
    } elseif ($theme === 'dark') {
        $bg_primary = '#090d16';
        $bg_secondary = '#111827';
        $bg_card = '#1f2937';
        $bg_input = '#111827';
        $text_primary = '#f9fafb';
        $text_secondary = '#9ca3af';
        $border_color = 'rgba(255, 255, 255, 0.05)';
        $glass_blur = 'none';
    } elseif ($theme === 'nord') {
        $bg_primary = '#2e3440';
        $bg_secondary = '#3b4252';
        $bg_card = '#434c5e';
        $bg_input = '#2e3440';
        $text_primary = '#eceff4';
        $text_secondary = '#d8dee9';
        $border_color = 'rgba(228, 232, 240, 0.1)';
        $glass_blur = 'none';
    }

    // Accents mapping
    $primary = '#6366f1';
    $primary_hover = '#4f46e5';
    $primary_glow = 'rgba(99, 102, 241, 0.4)';

    if ($accent === 'emerald') {
        $primary = '#10b981';
        $primary_hover = '#059669';
        $primary_glow = 'rgba(16, 185, 129, 0.4)';
    } elseif ($accent === 'rose') {
        $primary = '#f43f5e';
        $primary_hover = '#e11d48';
        $primary_glow = 'rgba(244, 63, 94, 0.4)';
    } elseif ($accent === 'amber') {
        $primary = '#f59e0b';
        $primary_hover = '#d97706';
        $primary_glow = 'rgba(245, 158, 11, 0.4)';
    } elseif ($accent === 'cyan') {
        $primary = '#06b6d4';
        $primary_hover = '#0891b2';
        $primary_glow = 'rgba(6, 182, 212, 0.4)';
    }

    // Style mapping (radii)
    $radius_sm = '8px';
    $radius_md = '14px';
    $radius_lg = '20px';
    $border_thickness = '1px';

    if ($style === 'minimalist') {
        $radius_sm = '0px';
        $radius_md = '0px';
        $radius_lg = '0px';
    } elseif ($style === 'bold') {
        $radius_sm = '12px';
        $radius_md = '18px';
        $radius_lg = '30px';
        $border_thickness = '2px';
    }

    $cssThemeVars = <<<CSS
/* Auto-generated by CodeForge CLI */
:root {
    --bg-primary: {$bg_primary};
    --bg-secondary: {$bg_secondary};
    --bg-card: {$bg_card};
    --bg-input: {$bg_input};
    
    --border-color: {$border_color};
    --border-hover: {$primary};
    --border-thickness: {$border_thickness};
    
    --text-primary: {$text_primary};
    --text-secondary: {$text_secondary};
    
    --primary: {$primary};
    --primary-hover: {$primary_hover};
    --primary-glow: {$primary_glow};
    
    --radius-sm: {$radius_sm};
    --radius-md: {$radius_md};
    --radius-lg: {$radius_lg};
    
    --glass-blur: {$glass_blur};
}
CSS;

    file_put_contents($themeCssFile, $cssThemeVars);
    logConsole('success', "Generated custom design system stylesheet at: assets/css/theme-custom.css");

    // 2. Generate Layout View Templates
    $viewsDir = __DIR__ . '/views';
    if (!is_dir($viewsDir)) mkdir($viewsDir, 0755, true);

    switch ($layout) {
        case 'dashboard':
            $templateContent = <<<PHP
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Gelişmiş Panel | CodeForge</title>
    <link rel="stylesheet" href="assets/css/forge-ui.css">
    <link rel="stylesheet" href="assets/css/theme-custom.css">
    <style>
        .sidebar { width: 260px; height: 100vh; position: fixed; background: var(--bg-secondary); border-right: var(--border-thickness) solid var(--border-color); padding: 24px; }
        .main-content { margin-left: 260px; padding: 40px; }
        .sidebar-logo { font-size: 20px; font-weight: 700; color: var(--primary); margin-bottom: 40px; }
        .sidebar-menu { list-style: none; display: flex; flex-direction: column; gap: 16px; }
        .sidebar-link { text-decoration: none; color: var(--text-secondary); font-weight: 500; transition: var(--transition); display: block; padding: 10px; border-radius: var(--radius-sm); }
        .sidebar-link.active, .sidebar-link:hover { color: var(--text-primary); background: rgba(255, 255, 255, 0.05); }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-logo">⚡ Forge Dashboard</div>
        <ul class="sidebar-menu">
            <li><a href="#" class="sidebar-link active">Ana Sayfa</a></li>
            <li><a href="#" class="sidebar-link">Raporlar</a></li>
            <li><a href="#" class="sidebar-link">Kullanıcılar</a></li>
            <li><a href="#" class="sidebar-link">Ayarlar</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="forge-container">
            <h2>Hoş Geldiniz, Yönetici</h2>
            <p>Tasarım stili: <strong>{$style}</strong>, Tema: <strong>{$theme}</strong> olarak başarıyla yapılandırıldı.</p>
            
            <div class="forge-grid cols-3 mt-8">
                <div class="forge-card">
                    <h4>Kullanıcılar</h4>
                    <p class="stat-number" style="font-size:32px; font-weight:700; color:var(--primary);">1,280</p>
                </div>
                <div class="forge-card">
                    <h4>Aylık Kazanç</h4>
                    <p class="stat-number" style="font-size:32px; font-weight:700; color:var(--primary);">$14,240</p>
                </div>
                <div class="forge-card">
                    <h4>Sistem Sağlığı</h4>
                    <p class="stat-number" style="font-size:32px; font-weight:700; color:var(--primary);">%99.9</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
PHP;
            file_put_contents($viewsDir . '/dashboard.php', $templateContent);
            logConsole('success', "Scaffolded unique dashboard view to: views/dashboard.php");
            break;

        case 'auth':
            $templateContent = <<<PHP
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Giriş Yap | CodeForge</title>
    <link rel="stylesheet" href="assets/css/forge-ui.css">
    <link rel="stylesheet" href="assets/css/theme-custom.css">
</head>
<body class="flex-center" style="display:flex; height: 100vh; background: var(--bg-primary);">
    <div class="forge-card" style="width:100%; max-width:400px; margin:auto;">
        <h3 class="text-center" style="margin-bottom:24px;">Giriş Paneli</h3>
        <form method="post" action="login">
            <?= \Forge\Core\App::getInstance()->get('guard')->csrfField() ?>
            <div class="forge-form-group">
                <label class="forge-label">E-posta Adresi</label>
                <input type="email" name="email" class="forge-input" required placeholder="admin@example.com">
            </div>
            <div class="forge-form-group">
                <label class="forge-label">Şifre</label>
                <input type="password" name="password" class="forge-input" required placeholder="••••••••">
            </div>
            <button type="submit" class="forge-btn forge-btn-primary w-full mt-4">Giriş Yap</button>
        </form>
    </div>
</body>
</html>
PHP;
            file_put_contents($viewsDir . '/login.php', $templateContent);
            logConsole('success', "Scaffolded unique auth views to: views/login.php");
            break;

        case 'landing':
            $templateContent = <<<PHP
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Açılış Sayfası | CodeForge</title>
    <link rel="stylesheet" href="assets/css/forge-ui.css">
    <link rel="stylesheet" href="assets/css/theme-custom.css">
    <style>
        .hero { text-align: center; padding: 120px 24px 80px 24px; }
        .hero h1 { font-size: 54px; margin-bottom: 24px; background: linear-gradient(135deg, var(--text-primary) 30%, var(--primary) 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body>
    <nav class="forge-navbar">
        <div class="forge-container forge-navbar-inner">
            <a href="#" class="forge-brand">⚡ CodeForge</a>
            <div class="forge-nav-links">
                <a href="#" class="forge-nav-link active">Özellikler</a>
                <a href="#" class="forge-nav-link">Ürünler</a>
                <a href="#" class="forge-nav-link">İletişim</a>
            </div>
        </div>
    </nav>
    <div class="hero">
        <div class="forge-container">
            <h1>Harika Fikirlerinizi Gerçeğe Dönüştürün</h1>
            <p style="font-size:20px; max-width:600px; margin: 0 auto 32px auto; color: var(--text-secondary);">Mevcut projelerinizi profesyonel terminal araçlarımızla üst düzeye taşıyın. Hiçbir ekstra kod yazmanız gerekmez.</p>
            <button class="forge-btn forge-btn-primary">Hemen Keşfedin</button>
        </div>
    </div>
</body>
</html>
PHP;
            file_put_contents($viewsDir . '/landing.php', $templateContent);
            logConsole('success', "Scaffolded unique landing view to: views/landing.php");
            break;

        default:
            logConsole('error', "Invalid layout type: '{$layout}'");
            break;
    }
}

// ==========================================
// 11. UI:MINIFY COMMAND
// ==========================================
function handleUiMinify($args) {
    if (empty($args)) {
        logConsole('error', "Missing target file path. Usage: php forge.php ui:minify <assets/css/file.css>");
        exit(1);
    }

    $filePath = $args[0];
    if (!file_exists($filePath)) {
        logConsole('error', "Target file not found: {$filePath}");
        exit(1);
    }

    logConsole('info', "Compressing asset: " . basename($filePath) . "...");

    $content = file_get_contents($filePath);

    // Minification regex routines
    $content = preg_replace('!/\*[^*]*\*+([^/*][^*]*\*+)*/!', '', $content); // Remove multiline comments
    $content = preg_replace('/^\s*\/\/.*$/m', '', $content); // Remove singleline comments
    $content = preg_replace('/\s+/', ' ', $content); // Collapse whitespaces
    $content = str_replace(
        [' {', '{ ', ' }', '} ', ' :', ': ', ' ;', '; ', ' ,', ', '],
        ['{', '{', '}', '}', ':', ':', ';', ';', ',', ','],
        $content
    );

    $ext = pathinfo($filePath, PATHINFO_EXTENSION);
    $minFile = dirname($filePath) . '/' . pathinfo($filePath, PATHINFO_FILENAME) . '.min.' . $ext;

    if (file_put_contents($minFile, trim($content)) !== false) {
        $savedPercent = round((1 - (filesize($minFile) / filesize($filePath))) * 100, 2);
        logConsole('success', "Compressed file generated successfully at: {$minFile} (Saved {$savedPercent}% size)");
    } else {
        logConsole('error', "Failed writing to: {$minFile}");
    }
}

// ==========================================
// HELP SCREEN
// ==========================================
function showDeveloperHelp() {
    echo COLOR_BOLD . "CodeForge-Engine Developer CLI" . COLOR_RESET . PHP_EOL;
    echo "==================================" . PHP_EOL;
    echo "Zero-coding automation framework and code builder." . PHP_EOL . PHP_EOL;
    echo COLOR_BOLD . "Usage:" . COLOR_RESET . PHP_EOL;
    echo "  php forge.php <command> [arguments] [options]" . PHP_EOL . PHP_EOL;
    
    echo COLOR_BOLD . "System & Database Commands:" . COLOR_RESET . PHP_EOL;
    echo "  init                         Initializes project structure and default config." . PHP_EOL;
    echo "  make:controller <Name>       Creates a new Controller stub class." . PHP_EOL;
    echo "  make:model <Name> [--table]  Creates an ActiveRecord model." . PHP_EOL;
    echo "  migrate                      Runs outstanding database migrations." . PHP_EOL;
    echo "  db:seed                      Inserts mock data catalog tables." . PHP_EOL;
    echo "  route:list                   Scans and lists registered application routes." . PHP_EOL;
    echo "  server [--port=8000]         Starts secure development web server." . PHP_EOL . PHP_EOL;
    
    echo COLOR_BOLD . "Security (Guard) Commands:" . COLOR_RESET . PHP_EOL;
    echo "  sec:key                      Generates a secure cryptographic APP_KEY." . PHP_EOL;
    echo "  sec:audit [--fix]            Scans security faults. Auto-patches CSRF vulnerabilities." . PHP_EOL . PHP_EOL;
    
    echo COLOR_BOLD . "Front-End / UI Commands:" . COLOR_RESET . PHP_EOL;
    echo "  ui:scaffold <layout> ...     Generates unique page views. Layout options: dashboard, auth, landing." . PHP_EOL;
    echo "                               Options: --theme=dark|light|glass|nord" . PHP_EOL;
    echo "                                        --accent=indigo|emerald|rose|amber|cyan" . PHP_EOL;
    echo "                                        --style=modern|minimalist|bold" . PHP_EOL;
    echo "  ui:minify <file>             Minifies custom CSS/JS to improve page speed." . PHP_EOL;
}
