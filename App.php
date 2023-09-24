<?php

require_once 'vendor/autoload.php';

use App\AppKernel;
use Symfony\Component\Console\Application;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$kernel = new AppKernel($_ENV['APP_ENV'], (bool)$_ENV['APP_ENABLE_DEBUG']);
$kernel->boot();

$container = $kernel->getContainer();
$container->set('di', $container);

/**
 * @var Application $application
 */
$application = $container->get(Application::class);
$application->run();
