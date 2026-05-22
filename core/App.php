<?php
namespace Forge\Core;

/**
 * App - CodeForge-Engine core bootstrapper.
 * Manages configuration, core service registration, and application lifecycle.
 */
class App {
    private static $instance = null;
    private $services = [];
    private $config = [];

    private function __construct() {
        $this->registerAutoloader();
        $this->bootSession();
        $this->loadDefaultConfig();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerAutoloader() {
        spl_autoload_register(function ($class) {
            $prefix = 'Forge\\Core\\';
            $baseDir = __DIR__ . '/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        });
    }

    private function bootSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function loadEnv() {
        $envPath = dirname(__DIR__) . '/.env';
        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strpos($line, '#') === 0) continue;
                if (strpos($line, '=') === false) continue;
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                // Strip quotes
                if (preg_match('/^"(.*)"$/', $value, $matches)) {
                    $value = $matches[1];
                } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
                    $value = $matches[1];
                }
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
                putenv("{$name}={$value}");
            }
        }
    }

    private function env($key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        }
        if ($val === null) {
            return $default;
        }
        if (strtolower($val) === 'true') return true;
        if (strtolower($val) === 'false') return false;
        if (strtolower($val) === 'null') return null;
        return $val;
    }

    private function loadDefaultConfig() {
        $this->loadEnv();
        
        $this->config = [
            'db' => [
                'host' => $this->env('DB_HOST', 'localhost'),
                'name' => $this->env('DB_DATABASE', 'kutuphane'),
                'user' => $this->env('DB_USERNAME', 'root'),
                'pass' => $this->env('DB_PASSWORD', ''),
                'charset' => $this->env('DB_CHARSET', 'utf8mb4')
            ],
            'app' => [
                'name' => $this->env('APP_NAME', 'CodeForge App'),
                'env' => $this->env('APP_ENV', 'development'), // development, production
                'debug' => (bool)$this->env('APP_DEBUG', true),
                'url' => $this->env('APP_URL', 'http://localhost/kutuphane')
            ]
        ];
    }

    public function setConfig(array $config) {
        $this->config = array_replace_recursive($this->config, $config);
    }

    public function getConfig($key, $default = null) {
        $parts = explode('.', $key);
        $current = $this->config;
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return $default;
            }
            $current = $current[$part];
        }
        return $current;
    }

    public function register($name, $resolver) {
        $this->services[$name] = $resolver;
    }

    public function get($name) {
        if (!isset($this->services[$name])) {
            // Auto-resolve core classes if they exist
            $className = "Forge\\Core\\" . ucfirst($name);
            if (class_exists($className)) {
                $this->services[$name] = function() use ($className) {
                    return new $className();
                };
            } else {
                throw new \Exception("Service '{$name}' not registered in CodeForge Container.");
            }
        }
        
        if (is_callable($this->services[$name])) {
            $this->services[$name] = call_user_func($this->services[$name], $this);
        }
        return $this->services[$name];
    }
}
