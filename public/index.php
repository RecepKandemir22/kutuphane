<?php
// ====================================================================
// PUBLIC ENTRY POINT (FRONT CONTROLLER)
// Boots the App container and dispatches the incoming request.
// ====================================================================

// Include core framework App bootstrapper
require_once __DIR__ . '/../core/App.php';

use Forge\Core\App;
use Forge\Core\Router;

// Register App controllers autoloader
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . '/../app/';
    
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

// Initialize Framework Core Container
$app = App::getInstance();

// Setup Routing
$router = new Router();

// Register Application Routes
$router->get('/', [\App\Controllers\HomeController::class, 'index']);
$router->get('/api/info', [\App\Controllers\HomeController::class, 'api']);

// Register router inside container for global access if needed
$app->register('router', function() use ($router) {
    return $router;
});

// Dispatch the request
$router->resolve();
