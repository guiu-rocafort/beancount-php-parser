<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'Beancount\\Parser\\' => __DIR__ . '/../src/',
        'Beancount\\Parser\\Tests\\' => __DIR__ . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }
});