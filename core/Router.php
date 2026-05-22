<?php
namespace Forge\Core;

/**
 * Router - CodeForge-Engine URL Routing system.
 * Resolves request URIs to controller actions or closures, extracting path parameters.
 */
class Router {
    private $routes = [];

    public function get($path, $action) {
        $this->addRoute('GET', $path, $action);
    }

    public function post($path, $action) {
        $this->addRoute('POST', $path, $action);
    }

    private function addRoute($method, $path, $action) {
        // Convert curly braces like {id} into named regex capture groups
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $path);
        // Add start and end delimiters
        $pattern = '#^' . $pattern . '$#';
        
        $this->routes[$method][$pattern] = $action;
    }

    public function resolve() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove query strings if they exist
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        
        // Get subdirectory if kutuphane is running inside a subfolder
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $baseFolder = dirname($scriptName);
        
        // Normalize the base folder (ensure no trailing/leading slashes mismatch)
        $baseFolder = rtrim(str_replace('\\', '/', $baseFolder), '/');
        
        // Remove base folder prefix from request URI
        if ($baseFolder !== '' && strpos($uri, $baseFolder) === 0) {
            $uri = substr($uri, strlen($baseFolder));
        }
        
        $uri = '/' . ltrim($uri, '/');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if (!isset($this->routes[$method])) {
            return $this->handleNotFound();
        }

        foreach ($this->routes[$method] as $pattern => $action) {
            if (preg_match($pattern, $uri, $matches)) {
                // Extract named matches for dynamic route params
                $params = array_filter($matches, function($key) {
                    return is_string($key);
                }, ARRAY_FILTER_USE_KEY);

                return $this->execute($action, $params);
            }
        }

        return $this->handleNotFound();
    }

    private function execute($action, $params = []) {
        if (is_callable($action)) {
            return call_user_func_array($action, $params);
        }

        if (is_array($action) && count($action) === 2) {
            $controllerClass = $action[0];
            $method = $action[1];

            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                if (method_exists($controllerInstance, $method)) {
                    return call_user_func_array([$controllerInstance, $method], $params);
                }
            }
        }

        return $this->handleError("Invalid route callback or controller method not found.");
    }

    private function handleNotFound() {
        http_response_code(404);
        if (file_exists(__DIR__ . '/../views/errors/404.php')) {
            include __DIR__ . '/../views/errors/404.php';
        } else {
            echo "<h1>404 Not Found</h1><p>The page you are looking for does not exist on this server.</p>";
        }
        exit;
    }

    private function handleError($message) {
        http_response_code(500);
        echo "<h1>500 Internal Server Error</h1><p>{$message}</p>";
        exit;
    }
}
