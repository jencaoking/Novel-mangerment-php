<?php
require_once '../core/Autoloader.php';
try {
    $router = new \Core\Router();
    $router->get('/', 'HomeController@index');
    $router->get('/product/{id}', 'HomeController@show');
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $router->dispatch($uri, $method);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString();
}
