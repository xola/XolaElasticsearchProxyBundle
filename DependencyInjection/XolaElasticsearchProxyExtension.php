<?php
namespace Xola\ElasticsearchProxyBundle\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class XolaElasticsearchProxyExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();

        $this->processConfiguration($configuration, $configs);
    }
}
