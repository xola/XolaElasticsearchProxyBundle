<?php

namespace Xola\ElasticsearchProxyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        // Symfony >=4.2 requires passing the root name to the TreeBuilder constructor
        $treeBuilder = new TreeBuilder('xola_elasticsearch_proxy');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            ->arrayNode('client')
            ->children()
            ->scalarNode('protocol')->defaultValue('http')->end()
            ->scalarNode('host')->defaultValue('localhost')->end()
            ->scalarNode('port')->defaultValue('9200')->end()
            ->arrayNode('indexes')
            ->prototype('scalar')->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('roles_skip_auth_filter')->prototype('scalar')->end()
            ->end()
            ->end();

        return $treeBuilder;
    }
}
