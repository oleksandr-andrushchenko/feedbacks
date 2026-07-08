<?php
declare(strict_types=1);

namespace App;

use Bref\SymfonyBridge\BrefKernel as BaseKernel;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        configureContainer as parentConfigureContainer;
        configureRoutes as parentConfigureRoutes;
    }

    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new class implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                if ($container->hasDefinition('config_builder.warmer')) {
                    $container->removeDefinition('config_builder.warmer');
                }
            }
        });
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
