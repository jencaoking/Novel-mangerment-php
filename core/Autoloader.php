<?php
spl_autoload_register(function ($className) {
    // 白名单过滤，防止路径穿越
    $classPath = preg_replace('#[^A-Za-z0-9_/]#', '', str_replace('\\', '/', $className));
    if (strpos($classPath, 'App/') === 0) {
        $file = __DIR__ . '/../app/' . substr($classPath, 4) . '.php';
    } elseif (strpos($classPath, 'Core/') === 0) {
        $file = __DIR__ . '/../core/' . substr($classPath, 5) . '.php';
    } else {
        return;
    }
    if (file_exists($file)) {
        require_once $file;
    } else {
        error_log("Autoloader: File not found for class $className (expected: $file)");
    }
});
