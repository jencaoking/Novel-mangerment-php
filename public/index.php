<?php
require_once '../core/Autoloader.php';
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

try {
    $router = new \Core\Router();
    $router->get('/', 'HomeController@index');
    $router->get('/novels', 'ProductController@novels');
    $router->get('/music', 'ProductController@music');
    $router->get('/product/{id}', 'ProductController@show');
    $router->post('/product/{id}', 'ProductController@buy');
    
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    $router->dispatch($uri, $method);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString();
}
