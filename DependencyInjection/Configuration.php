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
                        ->scalarNode('protocol')->defaultValue('http')->end()
                        ->scalarNode('host')->defaultValue('localhost')->end()
                        ->scalarNode('port')->defaultValue('9200')->end()
                        ->arrayNode('indexes')
                            ->prototype('scalar')->end()
                        ->end()
                    ->end()
                ->end()
            ->scalarNode('authenticator')->defaultValue('Xola\ElasticsearchProxyBundle\Authenticator')->end()
            ->arrayNode('roles_skip_auth_filter')->prototype('scalar')->end()
            ->end()
        ->end();
        return $treeBuilder;
    }
}