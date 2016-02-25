<?php

// init very simple lib autoload
spl_autoload_register(function ($className) {

    $root = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');

    $nsMap = [
        'tests'    => $root,
        'Graphite' => $root . DIRECTORY_SEPARATOR . 'src',
    ];
    
    $namespace = strstr($className, '\\', true);

    if (isset($nsMap[$namespace])) {
        $file = $nsMap[$namespace] . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        if (file_exists($file)) {
            include $file;
        }
    }
});
