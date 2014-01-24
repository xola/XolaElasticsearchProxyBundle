<?php
namespace Xola\ElasticsearchProxyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('xola_elasticsearch_proxy');

        $rootNode
            ->children()
                ->arrayNode('client')
                    ->children()
                        ->scalarNode('host')->defaultValue('localhost')->end()
                        ->scalarNode('port')->defaultValue('9200')->end()
                    ->end()
                ->end()
            ->end()
        ->end();
        return $treeBuilder;
    }
}