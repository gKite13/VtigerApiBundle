<?php

namespace Gkite13\VtigerApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class Gkite13VtigerApiExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $definition = $container->getDefinition('gkite13_vtiger_api.vtiger_api');

        $this->setCachePool($definition, $config);

        $definition->setArgument(1, new Reference('gkite13.http_client'));
        $definition->setArgument(2, $config['api']['site_url']);
        $definition->setArgument(3, $config['api']['user']);
        $definition->setArgument(4, $config['api']['access_key']);
        if(isset($config['cache']['cache_key'])) {
            $definition->setArgument(5, $config['cache']['cache_key']);
        }
        if(isset($config['cache']['expire_time'])) {
            $definition->setArgument(6, $config['cache']['expire_time']);
        }
    }

    private function setCachePool(Definition $definition, array $config): void
    {
        if (isset($config['cache']['pool'])) {
            $definition->setArgument(0, new Reference($config['cache']['pool']));
        } else {
            $definition->setArgument(0, new Reference('cache.app'));
        }
    }
}
