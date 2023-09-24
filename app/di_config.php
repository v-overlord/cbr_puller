<?php

use CbrPuller\Cache\AbstractCache;
use CbrPuller\Cache\Redis;
use CbrPuller\Logger;
use CbrPuller\Puller\AbstractPuller;
use CbrPuller\Puller\Cbr;
use CbrPuller\Service\FetcherRate;
use CbrPuller\Service\PublishBroker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpClient\HttpClient;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    // @TODO: You can use the configuration DI to load the selected one, BDUF.
    $services->alias(AbstractPuller::class, Cbr::class);
    $services->alias(AbstractCache::class, Redis::class);
    $services->set('di')->synthetic();
    $services->alias(ContainerInterface::class, 'di');
    $services->load('CbrPuller\\', '../src');
    $services->set(Application::class)->public();
    $services->set(FetcherRate::class)->public();
    $services->set(PublishBroker::class)->public();
    $services->set(HttpClient::class)->public();
    $services->set(LoggerInterface::class)->factory([Logger::class, 'build'])->args([
        service('di')
    ])->public();
};
