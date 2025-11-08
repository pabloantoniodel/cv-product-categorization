<?php
declare(strict_types=1);

spl_autoload_register(
    static function (string $class): void {
        $prefix = 'Cv\\ProductCategorization\\';

        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }

        $relative_class = substr($class, strlen($prefix));
        $relative_class = str_replace('\\', '/', $relative_class);

        $file = __DIR__ . '/' . $relative_class . '.php';

        if (file_exists($file)) {
            require $file;
        }
    }
);

