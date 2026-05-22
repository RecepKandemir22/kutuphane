<?php
namespace Forge\Core;

/**
 * Controller - Base Controller for CodeForge-Engine application logic.
 */
class Controller {
    
    /**
     * Renders a view template and extracts variables, supporting layouts
     */
    protected function view($name, $data = [], $layout = null) {
        $viewFile = __DIR__ . '/../views/' . $name . '.php';
        
        if (!file_exists($viewFile)) {
            http_response_code(500);
            die("<h1>500 Internal Error</h1><p>View template '{$name}' not found.</p>");
        }

        // Extract variables to local scope
        extract($data);

        // Include security and helper shortcuts for convenience in views
        $app = App::getInstance();
        $guard = $app->get('guard');
        $auth = $app->get('auth');

        if ($layout) {
            // Buffer the view content
            ob_start();
            include $viewFile;
            $content = ob_get_clean();

            // Load layout and inject captured content
            $layoutFile = __DIR__ . '/../views/layouts/' . $layout . '.php';
            if (file_exists($layoutFile)) {
                include $layoutFile;
            } else {
                echo $content;
            }
        } else {
            include $viewFile;
        }
    }

    /**
     * Returns JSON response to client
     */
    protected function json($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Redirects to a new location
     */
    protected function redirect($url) {
        $app = App::getInstance();
        $baseUrl = $app->getConfig('app.url');
        
        // If relative URL, prepend base url
        if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
            $url = rtrim($baseUrl, '/') . '/' . ltrim($url, '/');
        }
        
        header("Location: {$url}");
        exit;
    }
}
