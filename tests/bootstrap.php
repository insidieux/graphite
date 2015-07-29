<?php
$libRoot = realpath(__DIR__.'/../src');

function class2File($className)
{
    return str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
}

// init very simple lib autoload
spl_autoload_register(function ($className) use ($libRoot) {
    $file = $libRoot . DIRECTORY_SEPARATOR . class2File($className);
    if (file_exists($file)) {
        include $file;
    }
});

// test classes autoload
spl_autoload_register(function ($className) {
    $file = dirname(__DIR__) . DIRECTORY_SEPARATOR . class2File($className);
    if (file_exists($file)) {
        include $file;
    }
});
