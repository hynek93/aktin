<?php

use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Nette\Bootstrap\Configurator;
use Doctrine\ORM\EntityManagerInterface;

require __DIR__ . '/vendor/autoload.php';

$configurator = new Configurator();
$configurator->setDebugMode(true);
$configurator->enableTracy(__DIR__ . '/log');
$configurator->setTempDirectory(__DIR__ . '/temp');

$configurator->createRobotLoader()
    ->addDirectory(__DIR__ . '/app/')
    ->register();

$configurator->addConfig(__DIR__ . '/app/config/common.neon');

$container = $configurator->createContainer();

/** @var EntityManagerInterface $entityManager */
$entityManager = $container->getByType(EntityManagerInterface::class);

if (!$entityManager) {
    throw new RuntimeException("EntityManager not found in DI container.");
}

return ConsoleRunner::createHelperSet($entityManager);