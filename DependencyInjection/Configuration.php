<?php

namespace AntiMattr\GoogleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('google');

        $rootNode
            ->children()
                ->arrayNode('adwords')
                    ->children()
                        ->arrayNode('conversions')
                            ->useAttributeAsKey('name')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('id')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('label')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('value')->isRequired()->cannotBeEmpty()->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('analytics')
                    ->children()
                        ->arrayNode('trackers')
                            ->useAttributeAsKey('id')
                            ->prototype('array')
                                ->children()
                                    ->scalarNode('accountId')->isRequired()->cannotBeEmpty()->end()
                                    ->scalarNode('domain')->end()
                                    ->booleanNode('allowAnchor')->defaultFalse()->end()
                                    ->booleanNode('allowHash')->defaultFalse()->end()
                                    ->booleanNode('allowLinker')->defaultTrue()->end()
                                    ->booleanNode('includeNamePrefix')->defaultTrue()->end()
                                    ->booleanNode('trackPageLoadTime')->defaultFalse()->end()
                                    ->scalarNode('name')->end()
                                    ->scalarNode('setSiteSpeedSampleRate')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('maps')
                    ->children()
                        ->scalarNode('key')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
