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

    private function loadDefaultConfig() {
        $this->config = [
            'db' => [
                'host' => 'localhost',
                'name' => 'kutuphane',
                'user' => 'root',
                'pass' => '',
                'charset' => 'utf8mb4'
            ],
            'app' => [
                'name' => 'CodeForge App',
                'env' => 'development', // development, production
                'debug' => true,
                'url' => 'http://localhost/kutuphane'
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
