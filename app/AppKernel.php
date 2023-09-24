<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;

final class AppKernel extends Kernel
{
    public function registerBundles(): array
    {
        return [];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/di_config.php');
    }

    protected function initializeContainer()
    {
        parent::initializeContainer();

        $this->container->set('kernel', $this);
    }

    protected function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass($this->createCollectingCompilerPass());
    }

    private function createCollectingCompilerPass(): CompilerPassInterface
    {
        return new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $applicationDefinition = $container->findDefinition(Application::class);

                foreach ($container->getDefinitions() as $definition) {
                    if (!is_a($definition->getClass(), Command::class, true)) {
                        continue;
                    }

                    $applicationDefinition->addMethodCall('add', [new Reference($definition->getClass())]);
                }
            }
        };
    }
}