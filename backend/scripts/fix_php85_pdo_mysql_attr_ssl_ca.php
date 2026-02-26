<?php

declare(strict_types=1);

if (PHP_VERSION_ID < 80500) {
    exit(0);
}

$databaseConfigPath = __DIR__.'/../vendor/laravel/framework/config/database.php';

if (! is_file($databaseConfigPath)) {
    exit(0);
}

$original = file_get_contents($databaseConfigPath);
if ($original === false) {
    fwrite(STDERR, "Failed to read {$databaseConfigPath}\n");
    exit(1);
}

$legacy = 'PDO::MYSQL_ATTR_SSL_CA';
$patched = '(PHP_VERSION_ID >= 80500 ? Pdo\\Mysql::ATTR_SSL_CA : PDO::MYSQL_ATTR_SSL_CA)';

if (! str_contains($original, $legacy)) {
    exit(0);
}

$updated = str_replace($legacy, $patched, $original);
if ($updated === $original) {
    exit(0);
}

if (file_put_contents($databaseConfigPath, $updated) === false) {
    fwrite(STDERR, "Failed to write {$databaseConfigPath}\n");
    exit(1);
}

fwrite(STDOUT, "Patched {$databaseConfigPath} for PHP 8.5 PDO constant compatibility.\n");

