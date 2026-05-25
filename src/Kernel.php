<?php

declare(strict_types=1);

namespace App;

use Bref\SymfonyBridge\BrefKernel as BaseKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        configureContainer as parentConfigureContainer;
        configureRoutes as parentConfigureRoutes;
    }

    private function configureContainer(ContainerConfigurator $container, LoaderInterface $loader, ContainerBuilder $builder): void
    {
        $this->parentConfigureContainer($container, $loader, $builder);

        $configDir = $this->getConfigDir();

        $container->import($configDir . '/services.xml');
        $container->import($configDir . '/services/*.xml');

        if (is_dir($configDir . '/services/' . $this->environment)) {
            $container->import($configDir . '/services/' . $this->environment . '/*.xml');
        }
    }

    private function configureRoutes(RoutingConfigurator $routes): void
    {
        $this->parentConfigureRoutes($routes);

        $configDir = $this->getConfigDir();

        $routes->import($configDir . '/routes.xml');
    }
}
