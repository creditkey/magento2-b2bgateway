<?php
// MAGENTO_ROOT must point to an installed Magento development environment.
$root = getenv('MAGENTO_ROOT');
if (!$root || !is_file($root . '/app/bootstrap.php')) {
    throw new RuntimeException('Set MAGENTO_ROOT to the Magento installation root.');
}
require $root . '/app/bootstrap.php';
// Use this checkout rather than the module installed in the test environment.
spl_autoload_register(function ($class) {
    $prefix = 'CreditKey\\B2BGateway\\';
    if (strpos($class, $prefix) === 0) {
        $file = dirname(__DIR__, 2) . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
}, true, true);
