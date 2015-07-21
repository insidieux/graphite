<?php
$libRoot = realpath('./../Libs/');

// init very simple lib autoload
spl_autoload_register(function ($className) use ($libRoot) {
    $file = $libRoot . '/' . str_replace('\\', '/', $className) . '.php';
    if (file_exists($file)) {
        include $file;
    }
});

// test classes autoload
spl_autoload_register(function ($className) {
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $className) . '.php';
    if (file_exists($file)) {
        include $file;
    }
});
