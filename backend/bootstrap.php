<?php
declare(strict_types=1);

$projectRoot = dirname(__DIR__);

define('HERBAL_PROJECT_ROOT', $projectRoot);
define('HERBAL_DATA_DIR', $projectRoot . DIRECTORY_SEPARATOR . 'data');
define('HERBAL_XML_PATH', HERBAL_DATA_DIR . DIRECTORY_SEPARATOR . 'herbs.xml');

date_default_timezone_set('Asia/Shanghai');

spl_autoload_register(static function (string $class): void {
    $prefix = 'HerbalPlatform\\';
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

