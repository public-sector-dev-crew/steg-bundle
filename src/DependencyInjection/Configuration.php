<?php

declare(strict_types=1);

namespace Steg\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('steg');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('connections')
                    ->isRequired()
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('dsn')
                                ->defaultNull()
                                ->info('DSN string, e.g. vllm://gpu-server:8000/v1?model=llama-3.3-70b-awq')
                            ->end()
                            ->scalarNode('base_url')
                                ->defaultNull()
                                ->info('Base URL for the inference server (alternative to DSN)')
                            ->end()
                            ->scalarNode('model')
                                ->defaultNull()
                                ->info('Model name (required when using base_url instead of DSN)')
                            ->end()
                            ->scalarNode('api_key')
                                ->defaultValue('EMPTY')
                                ->info('API key (optional, defaults to EMPTY for local servers)')
                            ->end()
                            ->integerNode('timeout')
                                ->defaultValue(120)
                                ->min(1)
                                ->info('Request timeout in seconds (must be > 0)')
                            ->end()
                        ->end()
                        ->validate()
                            ->ifTrue(static function (array $v): bool {
                                return null === $v['dsn'] && null === $v['base_url'];
                            })
                            ->thenInvalid('Each connection requires either "dsn" or "base_url".')
                        ->end()
                        ->validate()
                            ->ifTrue(static function (array $v): bool {
                                return null === $v['dsn'] && null === $v['model'];
                            })
                            ->thenInvalid('A connection with "base_url" requires "model" to be set.')
                        ->end()
                    ->end()
                ->end()
                ->scalarNode('default_connection')
                    ->defaultNull()
                    ->info('Name of the default connection (defaults to first defined connection)')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
