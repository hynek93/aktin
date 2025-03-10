<?php

use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\DBAL\DriverManager;

require __DIR__ . '/vendor/autoload.php';

$config = new PhpFile(__DIR__ . '/migrations.php');
$connection = DriverManager::getConnection([
    'dbname' => 'aktin',
    'user' => 'root',
    'password' => 'root',
    'host' => 'db',
    'driver' => 'pdo_mysql',
    'charset' => 'utf8mb4',
]);

return DependencyFactory::fromConnection($config, $connection);