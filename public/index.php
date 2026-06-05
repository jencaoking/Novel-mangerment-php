<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../core/Router.php';

$router = new Router();

$router->get('/', function() {
    echo "<h1>欢迎来到 BookMusic Mall 首页！</h1>";
});

$router->get('/novels', function() {
    echo "<h1>小说商城</h1>";
});

$router->get('/product/{id}', function($id) {
    echo "<h1>正在查看商品详情，商品ID：{$id}</h1>";
});

$uri = $_SERVER['REQUEST_URI'];
$method = $_SERVER['REQUEST_METHOD'];

$router->dispatch($uri, $method);
