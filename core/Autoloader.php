<?php
spl_autoload_register(function ($className) {
    $classPath = str_replace('\\', '/', $className);
    if (strpos($classPath, 'App/') === 0) {
        $file = __DIR__ . '/../app/' . substr($classPath, 4) . '.php';
    } elseif (strpos($classPath, 'Core/') === 0) {
        $file = __DIR__ . '/../core/' . substr($classPath, 5) . '.php';
    } else {
        return;
    }
    if (file_exists($file)) {
        require_once $file;
    }
});
